<?php
// index.php — Payments Entry with OR validation, usage marking, and activity logging
include 'config.php';


session_start();

function sanitize($v) { return htmlspecialchars(trim($v)); }

$submitted = false;
$error = '';
$warning = '';
$saved_total = 0;

$or_start = $or_end = $last_used = null;

$uid = $_SESSION['user_id'] ?? null;
if ($uid) {
    // get user OR range
    $stmt = $conn->prepare("SELECT or_start, or_end FROM users WHERE id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->bind_result($or_start, $or_end);
    $stmt->fetch();
    $stmt->close();

    // get last OR used from payments
    $stmt2 = $conn->prepare("SELECT MAX(reference_no) FROM payments WHERE reference_no BETWEEN ? AND ?");
    $stmt2->bind_param("ii", $or_start, $or_end);
    $stmt2->execute();
    $stmt2->bind_result($last_used);
    $stmt2->fetch();
    $stmt2->close();

    if (!$last_used) $last_used = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date  = sanitize($_POST['date'] ?? '');
    $payee = sanitize($_POST['payee'] ?? '');
    $refno = intval($_POST['refno'] ?? 0);

    $codes     = $_POST['code'] ?? [];
    $acctnames = $_POST['acctname'] ?? [];
    $amounts   = $_POST['amount'] ?? [];

    // ✅ Basic validation
    if (!$date || !$payee || !$refno) {
        $error = "Date, Payee and Reference No. are required.";
    } elseif (!$uid) {
        $error = "You must be logged in.";
    } else {
        // 🔐 OR range check
        $stmt = $conn->prepare("SELECT or_start, or_end FROM users WHERE id=?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->bind_result($or_start, $or_end);
        $stmt->fetch();
        $stmt->close();

        if ($refno < $or_start || $refno > $or_end) {
            $error = "⚠️ OR No. $refno is not within your assigned range ($or_start - $or_end).";
        } else {
            // 🔐 check if OR already marked used
            $chk = $conn->prepare("SELECT is_used FROM or_numbers WHERE user_id=? AND or_number=?");
            $chk->bind_param("ii", $uid, $refno);
            $chk->execute();
            $chk->bind_result($is_used);
            if ($chk->fetch() && $is_used == 1) {
                $error = "⚠️ OR No. $refno has already been used.";
            }
            $chk->close();
        }
    }

    // ✅ Continue only if no errors
    if (!$error) {
        // duplicate OR in payments
        $check = $conn->prepare("SELECT COUNT(*) FROM payments WHERE reference_no=?");
        $check->bind_param("i", $refno);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();

        if ($count > 0) {
            $warning = "⚠️ OR No. <strong>" . htmlentities($refno) . "</strong> already exists.";
        } else {
            // get constants from fees
            $feesLookup = [];
            $res = $conn->query("SELECT code, constant_value FROM fees");
            while ($row = $res->fetch_assoc()) {
                if ($row['constant_value'] !== null && $row['constant_value'] !== '') {
                    $feesLookup[$row['code']] = $row['constant_value'];
                }
            }

            $codesArr = [];
            $acctsArr = [];
            $amtsArr  = [];

            for ($i=0; $i<8; $i++) {
                $code   = sanitize($codes[$i] ?? '');
                $acct   = sanitize($acctnames[$i] ?? '');
                $rawAmt = $amounts[$i] ?? '';
                $amt    = str_replace(',', '', $rawAmt);

                if ($code === '' && $acct === '' && $amt === '') {
                    $codesArr[$i] = $acctsArr[$i] = $amtsArr[$i] = null;
                    continue;
                }

                $amt = is_numeric($amt) ? number_format((float)$amt, 2, '.', '') : null;

                // auto-fill constant value
                if ($code && strtoupper($code) !== 'OTHER' && isset($feesLookup[$code]) && $feesLookup[$code] !== '') {
                    $amt = $feesLookup[$code];
                }

                $codesArr[$i] = $code ?: null;
                $acctsArr[$i] = $acct ?: null;
                $amtsArr[$i]  = $amt;
            }

            // total
            $total = 0.0;
            foreach ($amtsArr as $a) {
                if ($a !== null && is_numeric($a)) {
                    $total += (float)$a;
                }
            }
            $total = number_format($total, 2, '.', '');

            // save payment
            $sql = "
                INSERT INTO payments
                (`date`, payee, reference_no,
                 code1, account_name1, amount1,
                 code2, account_name2, amount2,
                 code3, account_name3, amount3,
                 code4, account_name4, amount4,
                 code5, account_name5, amount5,
                 code6, account_name6, amount6,
                 code7, account_name7, amount7,
                 code8, account_name8, amount8,
                 total, cash_received, change_amount,
                 archived, archive_reason, archived_date
                ) VALUES (
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?, ?, ?, ?
                )
            ";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error = "Prepare failed: " . $conn->error;
            } else {
                $cash_received = floatval($_POST['cash_received'] ?? 0);
                $change_amount = $cash_received - floatval($total);
                $archived = 0;
                $archive_reason = NULL;
                $archived_date = NULL;

                $types = "ssi" . str_repeat("ssd", 8) . "dddiss";

                $stmt->bind_param(
                    $types,
                    $date, $payee, $refno,
                    $codesArr[0], $acctsArr[0], $amtsArr[0],
                    $codesArr[1], $acctsArr[1], $amtsArr[1],
                    $codesArr[2], $acctsArr[2], $amtsArr[2],
                    $codesArr[3], $acctsArr[3], $amtsArr[3],
                    $codesArr[4], $acctsArr[4], $amtsArr[4],
                    $codesArr[5], $acctsArr[5], $amtsArr[5],
                    $codesArr[6], $acctsArr[6], $amtsArr[6],
                    $codesArr[7], $acctsArr[7], $amtsArr[7],
                    $total, $cash_received, $change_amount,
                    $archived, $archive_reason, $archived_date
                );

                if ($stmt->execute()) {
                    $last_id = $conn->insert_id;

                    // ✅ mark OR as used
                    $upd = $conn->prepare("UPDATE or_numbers 
                                           SET is_used=1, used_date=NOW(), used_code=?, used_account=? 
                                           WHERE user_id=? AND or_number=?");
                    if ($upd) {
                        $firstCode = $codesArr[0] ?? '';
                        $firstAcct = $acctsArr[0] ?? '';
                        $upd->bind_param("ssii", $firstCode, $firstAcct, $uid, $refno);
                        $upd->execute();
                        $upd->close();
                    }

                    // ✅ log activity
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                    $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $action = "ADD_PAYMENT";
                    $details = "Payment ID: $last_id, OR#: $refno, Payee: $payee, Total: ₱" . number_format($total, 2);

                    $log = $conn->prepare("INSERT INTO activity_logs 
                        (user_id, username, action, details, ip_address, user_agent) 
                        VALUES (?,?,?,?,?,?)");
                    $log->bind_param(
                        "isssss",
                        $_SESSION['user_id'],
                        $_SESSION['username'],
                        $action,
                        $details,
                        $ip,
                        $agent
                    );
                    $log->execute();
                    $log->close();

                    // redirect to print
                    header("Location: print_receipt.php?id=$last_id");
                    exit;
                } else {
                    $error = "Database error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

if (isset($_GET['success']) && isset($_SESSION['saved_total'])) {
    $submitted = true;
    $saved_total = $_SESSION['saved_total'];
    unset($_SESSION['saved_total']);
}
?>




<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Payments Entry — Merged (MySQL)</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    body { background: #f6f9fc; }
    .card { border-radius: 12px; box-shadow: 0 6px 18px rgba(16,24,40,0.06); }
    .small-muted { font-size: .85rem; color: #6b7280; }
    .accent { color: #0ea5a4; }
    .input-group .form-control:focus { box-shadow: none; border-color: #a7f3d0; }
    .row-highlight { background: rgba(14,165,233,0.07); transition: background .45s ease; }
    .btn-group-sm .btn { padding: 0.25rem 0.4rem; }
    .readonly-bg { background-color: #f8fafc; }
  </style>
</head>
<body>
<?php include 'nvbar.php'; ?>

<div class="container py-4">
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <?php if ($warning): ?>
    <div class="alert alert-warning"><?= $warning ?></div>
  <?php endif; ?>

  <?php if ($submitted): ?>
    <div class="alert alert-success">
      ✅ Transaction saved! Total ₱<?= number_format($saved_total, 2) ?>
    </div>
  <?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card p-4">
        <div class="d-flex align-items-center mb-3">
          <span class="material-icons bg-light rounded-circle p-2 me-3 accent">receipt_long</span>
          <div>
            <h4 class="mb-0">Payments Entry (E-RECEIPT)</h4>
          </div>
        </div>
 <?php if ($uid): ?>
          <div class="alert alert-info d-flex justify-content-between">
            <div>
              <strong>|  OR Range:
              <?= str_pad($or_start, 5, '0', STR_PAD_LEFT) ?> – <?= str_pad($or_end, 5, '0', STR_PAD_LEFT) ?></strong>
            </div>
            <div>
              <strong>Last OR Used:
              <?= $last_used ? str_pad($last_used, 5, '0', STR_PAD_LEFT) : "None yet" ?></strong>
            </div>

          </div>
        <?php endif; ?>
        <form id="paymentsForm" method="post" class="needs-validation" novalidate>
          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label">Date</label>
              <input required name="date" type="date" class="form-control" value="<?= isset($_POST['date']) ? sanitize($_POST['date']) : date('Y-m-d') ?>">
            </div>
           <div class="col-md-5">
  <label class="form-label">Payee</label>
  <input 
    required 
    name="payee" 
    id="payee"
    type="text" 
    class="form-control" 
    placeholder="Payee name" 
    value="<?= isset($_POST['payee']) ? sanitize($_POST['payee']) : '' ?>" auto-complete="off"
  >
</div>
            <div class="col-md-4">
              <label class="form-label">OR No.</label>
              <input required name="refno" type="number" class="form-control" placeholder="Reference / OR / TR #" value="<?= isset($_POST['refno']) ? sanitize($_POST['refno']) : '' ?>">
            </div>
          </div>

          <hr>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Payments (up to 8 rows)</h6>
            <div class="small-muted">Each row has Reset (clear) and Delete controls.</div>
          </div>

          <div id="paymentRows">
            <!-- First Payment Row -->
            <div class="row g-2 align-items-end mb-2 payment-row">
              <div class="col-md-3">
                <label class="form-label">Code</label>
                <select class="form-select code-select" name="code[]">
                  <option value="">-- Select --</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Account</label>
                <input type="text" name="acctname[]" class="form-control acctname readonly-bg" readonly>
              </div>
              <div class="col-md-2">
                <label class="form-label">Const.</label>
                <input type="text" name="constval[]" class="form-control text-end constval readonly-bg" readonly>
              </div>
              <div class="col-md-2">
                <label class="form-label">Amount</label>
                <input type="number" step="0.01" name="amount[]" class="form-control text-end amount">
              </div>
              <div class="col-md-1 d-flex gap-1">
                <button type="button" class="btn btn-outline-danger btn-sm remove-row" title="Remove Row">
                  <span class="material-icons">remove</span>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm reset-row" title="Clear Row">
                  <span class="material-icons">sync</span>
                </button>
              </div>
            </div>
          </div>

          <div class="mb-3 d-flex align-items-center gap-2">
            <button type="button" id="addRow" class="btn btn-outline-primary btn-sm">
              <span class="material-icons">add</span> Add Payment
            </button>
            <button type="button" id="clearAll" class="btn btn-outline-secondary btn-sm">
              <span class="material-icons">clear_all</span> Clear All
            </button>
          </div>
          <hr>
<div class="row mt-3">
  <div class="col-md-6 small-muted">
    Tip: Selecting a code auto-fills account & constant.  
    If no constant is saved, you can type Const + Amount manually.  
    If you pick "OTHER", you can enter all fields manually.
  </div>
  <div class="col-md-6">
    <div class="mb-2 d-flex justify-content-end align-items-center gap-2">
      <strong><span class="text-muted" style="color:red;">Total:</span></strong>
      <div class="input-group" style="max-width:180px;">
        <span class="input-group-text">₱</span>
        <input type="text" id="totalAmount" class="form-control text-end" readonly value="0.00" style="color:red;font-weight:bold;">
      </div>
    </div>
      <hr>
    <div class="mb-2 d-flex justify-content-end align-items-center gap-2">
     <strong style="color:blue;"> <span class="text-muted" >Cash Received:</span></strong>
      <div class="input-group" style="max-width:180px;">
        <span class="input-group-text">₱</span>
        <input type="number" step="0.01" id="cashReceived" class="form-control text-end" name="cash_received" placeholder="0.00" style="color:blue;font-weight:bold;" >
      </div>
    </div>
    <hr>
    <div class="d-flex justify-content-end align-items-center gap-2">
      <span class="text-muted">Change:</span>
      <div class="input-group" style="max-width:180px;">
        <span class="input-group-text">₱</span>
        <input type="text" id="changeAmount" class="form-control text-end" readonly value="0.00" style="font-weight:bold;">
      </div>
    </div>
  </div>
</div>

<hr>
          <div class="mt-4 d-flex justify-content-end">
            <button type="submit" class="btn btn-success me-2"><span class="material-icons">add_circle</span> Save</button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php
// build JS data for codes
$fees = [];
$result = $conn->query("SELECT code, account_name, constant_value, amount FROM fees");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fees[$row['code']] = [
            "name" => $row['account_name'],
            "const" => $row['constant_value'],
            "value" => ($row['amount'] !== null && $row['amount'] !== '') ? number_format($row['amount'], 2, '.', '') : ''
        ];
    }
}
?>
const codesData = <?php echo json_encode($fees, JSON_PRETTY_PRINT); ?>;

function fmt(n) {
  const num = Number(n) || 0;
  return num.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
}

function populateCodeSelect(sel) {
  if (!sel) return;
  sel.innerHTML = '<option value="">-- Select --</option>';
  Object.keys(codesData).sort().forEach(k=>{
    const opt = document.createElement('option');
    opt.value = k;
    const val = (codesData[k].value && codesData[k].value !== "") ? (' (₱' + fmt(codesData[k].value) + ')') : '';
    opt.textContent = k + ' — ' + codesData[k].name + val;
    sel.appendChild(opt);
  });
  // Add "Other"
  const otherOpt = document.createElement('option');
  otherOpt.value = "OTHER";
  otherOpt.textContent = "OTHER — Manual Entry";
  sel.appendChild(otherOpt);
}

function onCodeChange(event) {
  const sel = event.target;
  const row = sel.closest('.payment-row');
  const acct = row.querySelector('.acctname');
  const constv = row.querySelector('.constval');
  const amt = row.querySelector('.amount');

  if (sel.value === "OTHER") {
    // Manual entry → everything editable
    acct.value = '';
    acct.readOnly = false;
    acct.classList.remove('readonly-bg');

    constv.value = '';
    constv.readOnly = false;   // ✅ now editable
    constv.classList.remove('readonly-bg');

    amt.value = '';
    amt.readOnly = false;
  } else if (sel.value && codesData[sel.value]) {
    acct.value = codesData[sel.value].name || '';
    acct.readOnly = true;
    acct.classList.add('readonly-bg');

    if (codesData[sel.value].const && codesData[sel.value].const.trim() !== "") {
      // Has constant + amount → lock both
      constv.value = codesData[sel.value].const;
      constv.readOnly = true;
      constv.classList.add('readonly-bg');

      amt.value = codesData[sel.value].value || '';
      amt.readOnly = true;
    } else {
      // No constant/amount → constant locked, amount editable
      constv.value = '';
      constv.readOnly = true;   // ✅ always locked for predefined codes
      constv.classList.add('readonly-bg');

      amt.value = '';
      amt.readOnly = false;
    }
  } else {
    acct.value = '';
    acct.readOnly = true;
    acct.classList.add('readonly-bg');

    constv.value = '';
    constv.readOnly = true;
    constv.classList.add('readonly-bg');

    amt.value = '';
    amt.readOnly = false;
  }
  updateTotal();
}


function clearRowFromButton(btn) {
  const row = btn.closest('.payment-row');
  if (!row) return;
  const sel = row.querySelector('.code-select');
  if (sel) sel.value = '';
  const acct = row.querySelector('.acctname'); if (acct) { acct.value = ''; acct.readOnly = true; acct.classList.add('readonly-bg'); }
  const constv = row.querySelector('.constval'); if (constv) { constv.value = ''; constv.readOnly = true; constv.classList.add('readonly-bg'); }
  const amt = row.querySelector('.amount'); if (amt) { amt.value = ''; amt.readOnly = false; }
  updateTotal();
  row.classList.add('row-highlight');
  setTimeout(()=> row.classList.remove('row-highlight'), 450);
}

function updateTotal() {
  let total = 0;
  document.querySelectorAll('.amount').forEach(inp=>{
    const v = parseFloat(inp.value || 0);
    if (!isNaN(v)) total += v;
  });
  document.getElementById('totalAmount').value = fmt(total);
}

function initRowEvents(row) {
  const sel = row.querySelector('.code-select');
  if (sel) populateCodeSelect(sel);
  if (sel) sel.addEventListener('change', onCodeChange);

  const amt = row.querySelector('.amount');
  if (amt) amt.addEventListener('input', updateTotal);

  const resetBtn = row.querySelector('.reset-row');
  if (resetBtn) resetBtn.addEventListener('click', function(){ clearRowFromButton(resetBtn); });

  const delBtn = row.querySelector('.remove-row');
  if (delBtn) {
    delBtn.addEventListener('click', function(){
      const rows = document.querySelectorAll('.payment-row');
      if (rows.length > 1) {
        row.remove();
        updateTotal();
      } else {
        clearRowFromButton(delBtn);
      }
    });
  }
}

document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.payment-row').forEach(initRowEvents);

  document.getElementById('addRow').addEventListener('click', function(){
    const rows = document.querySelectorAll('.payment-row');
    if (rows.length >= 8) {
      alert("You can only add up to 8 payments.");
      return;
    }
    const first = rows[0];
    const clone = first.cloneNode(true);

    clone.querySelectorAll('input, select').forEach(el => {
      el.value = '';
      if (el.classList && (el.classList.contains('acctname') || el.classList.contains('constval'))) {
        el.readOnly = true;
        el.classList.add('readonly-bg');
      } else {
        el.readOnly = false;
      }
    });

    document.getElementById('paymentRows').appendChild(clone);
    initRowEvents(clone);
  });

  document.getElementById('clearAll').addEventListener('click', function(){
    const rows = Array.from(document.querySelectorAll('.payment-row'));
    rows.forEach((r, idx) => {
      if (idx === 0) {
        r.querySelectorAll('input, select').forEach(el => {
          el.value = '';
          if (el.classList && (el.classList.contains('acctname') || el.classList.contains('constval'))) {
            el.readOnly = true;
            el.classList.add('readonly-bg');
          } else {
            el.readOnly = false;
          }
        });
      } else {
        r.remove();
      }
    });
    updateTotal();
  });

  updateTotal();
});
function updateChange() {
  const total = parseFloat(document.getElementById('totalAmount').value.replace(/,/g,'')) || 0;
  const cash = parseFloat(document.getElementById('cashReceived').value) || 0;
  const change = cash - total;
  document.getElementById('changeAmount').value = fmt(change >= 0 ? change : 0);
}

document.addEventListener('DOMContentLoaded', function(){
  // existing init...
  const cashInput = document.getElementById('cashReceived');
  if (cashInput) {
    cashInput.addEventListener('input', updateChange);
  }
});

</script>
</body>
</html>
