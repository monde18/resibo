<?php
include 'config.php';
include 'fees.php'; // your big fees array

$id = intval($_GET['id'] ?? 0);
$result = $conn->query("SELECT * FROM payments WHERE id=$id");
$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Payment Record</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    body { background: #f6f9fc; }
    .card { border-radius: 12px; box-shadow: 0 6px 15px rgba(0,0,0,0.08); border: none; }
    .form-control, .form-select { border-radius: 8px; padding: 8px; }
    .form-label { font-weight: 500; color: #495057; margin-bottom: 4px; }
    .btn { border-radius: 8px; font-weight: 500; }
    .accent { color: #0ea5a4; }
    .readonly-bg { background-color: #f8fafc; }
    .row-highlight { background: rgba(14,165,233,0.07); transition: background .45s ease; }
  </style>
</head>
<body>
<div class="container py-4">
  <?php include 'navbar.php'; ?>
  <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
  <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
    ✅ Payment record updated successfully!
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

  
  <div class="card">
    <div class="p-4 bg-primary text-white rounded-top">
      <div class="d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><span class="material-icons">edit</span> Edit Payment</h4>
        <a href="records.php" class="btn btn-light btn-sm">
          <span class="material-icons">arrow_back</span> Back
        </a>
      </div>
    </div>
    
    <div class="card-body p-4">
      <form method="post" action="update.php">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">

        <!-- Transaction Info -->
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" name="date" value="<?= $row['date'] ?>" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Payee</label>
            <input type="text" name="payee" value="<?= htmlspecialchars($row['payee']) ?>" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">OR No.</label>
            <input type="text" name="refno" value="<?= htmlspecialchars($row['reference_no']) ?>" class="form-control" required>
          </div>
        </div>

        <hr>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0">Payments</h6>
          <div class="small text-muted">Each row has Reset and Delete controls.</div>
        </div>

        <div id="paymentRows">
        <?php 
        $hasRow = false;
        for ($i=1; $i<=8; $i++):
          $code  = trim($row["code$i"] ?? '');
          $acct  = trim($row["account_name$i"] ?? '');
          $const = trim($row["const$i"] ?? '');
          $amt   = trim($row["amount$i"] ?? '');
          
          if($code !== '' || $acct !== '' || $const !== '' || $amt !== ''):
            $hasRow = true;
        ?>
          <div class="row g-2 align-items-end mb-2 payment-row">
            <div class="col-md-3">
              <label class="form-label">Code</label>
              <select name="code[]" class="form-select code-select">
                <option value="">-- Select --</option>
                <?php foreach ($fees as $fcode => $fee): 
                    $fname  = htmlspecialchars($fee['name']  ?? '');
                    $fconst = htmlspecialchars($fee['const'] ?? '');
                    $fvalue = htmlspecialchars($fee['value'] ?? '');
                ?>
                  <option value="<?= htmlspecialchars($fcode) ?>"
                    data-name="<?= $fname ?>"
                    data-const="<?= $fconst ?>"
                    data-amount="<?= $fvalue ?>"
                    <?= ($code==$fcode?'selected':'') ?>>
                    <?= htmlspecialchars($fcode) ?> — <?= $fname ?>
                  </option>
                <?php endforeach; ?>
                <option value="OTHER" <?= ($code=="OTHER"?'selected':'') ?>>OTHER — Manual Entry</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Account</label>
              <input type="text" name="acctname[]" value="<?= htmlspecialchars($acct) ?>" class="form-control acctname">
            </div>
            <div class="col-md-2">
              <label class="form-label">Const.</label>
              <input type="text" name="constval[]" value="<?= htmlspecialchars($const) ?>" class="form-control constval">
            </div>
            <div class="col-md-2">
              <label class="form-label">Amount</label>
              <input type="number" step="0.01" name="amount[]" value="<?= htmlspecialchars($amt) ?>" class="form-control amount">
            </div>
            <div class="col-md-2 d-flex gap-1">
              <button type="button" class="btn btn-outline-danger btn-sm remove-row"><span class="material-icons">remove</span></button>
              <button type="button" class="btn btn-outline-secondary btn-sm reset-row"><span class="material-icons">sync</span></button>
            </div>
          </div>
        <?php 
          endif;
        endfor; 

        if(!$hasRow): // show one empty row if no payments exist
        ?>
          <div class="row g-2 align-items-end mb-2 payment-row">
            <div class="col-md-3">
              <label class="form-label">Code</label>
              <select name="code[]" class="form-select code-select">
                <option value="">-- Select --</option>
                <?php foreach ($fees as $fcode => $fee): 
                    $fname  = htmlspecialchars($fee['name']  ?? '');
                ?>
                  <option value="<?= htmlspecialchars($fcode) ?>"><?= htmlspecialchars($fcode) ?> — <?= $fname ?></option>
                <?php endforeach; ?>
                <option value="OTHER">OTHER — Manual Entry</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Account</label>
              <input type="text" name="acctname[]" class="form-control acctname readonly-bg" readonly>
            </div>
            <div class="col-md-2">
              <label class="form-label">Const.</label>
              <input type="text" name="constval[]" class="form-control constval readonly-bg" readonly>
            </div>
            <div class="col-md-2">
              <label class="form-label">Amount</label>
              <input type="number" step="0.01" name="amount[]" class="form-control amount">
            </div>
            <div class="col-md-2 d-flex gap-1">
              <button type="button" class="btn btn-outline-danger btn-sm remove-row"><span class="material-icons">remove</span></button>
              <button type="button" class="btn btn-outline-secondary btn-sm reset-row"><span class="material-icons">sync</span></button>
            </div>
          </div>
        <?php endif; ?>
        </div>

        <div class="mb-3">
          <button type="button" id="addRow" class="btn btn-outline-primary btn-sm">
            <span class="material-icons">add</span> Add Payment
          </button>
        </div>

        <!-- Total -->
        <div class="row mt-4">
          <div class="col-md-6">
            <div class="p-3 bg-light rounded">
              <strong>Total:</strong> ₱<span id="totalAmount"><?= number_format($row['total'],2) ?></span>
            </div>
            <!-- Cash Received & Change -->
<div class="row mt-3">
  <div class="col-md-6">
    <label class="form-label">Cash Received</label>
    <div class="input-group">
      <span class="input-group-text">₱</span>
      <input type="number" step="0.01" 
             name="cash_received" 
             id="cashReceived" 
             value="<?= htmlspecialchars($row['cash_received']) ?>" 
             class="form-control text-end" 
             style="color:blue;font-weight:bold;">
    </div>
  </div>
  <div class="col-md-6">
    <label class="form-label">Change</label>
    <div class="input-group">
      <span class="input-group-text">₱</span>
      <input type="text" 
             name="change_amount" 
             id="changeAmount" 
             value="<?= htmlspecialchars($row['change_amount']) ?>" 
             class="form-control text-end" 
             style="font-weight:bold;" readonly>
    </div>
  </div>
</div>

          </div>
          <div class="col-md-6 text-end">
            <button type="submit" class="btn btn-success btn-lg">
              <span class="material-icons">save</span> Save Changes
            </button>
            <a href="records.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>


<?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="updateToast" class="toast align-items-center text-bg-success border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body">
        ✅ Payment record updated successfully!
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
<script>
  const toastEl = document.getElementById('updateToast');
  if (toastEl) {
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
  }
</script>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
<?php
// build JS data same as index.php
$fees = [];
$result = $conn->query("SELECT code, account_name, constant_value, amount FROM fees");
if ($result && $result->num_rows > 0) {
    while ($rowf = $result->fetch_assoc()) {
        $fees[$rowf['code']] = [
            "name" => $rowf['account_name'],
            "const" => $rowf['constant_value'],
            "value" => ($rowf['amount'] !== null && $rowf['amount'] !== '') 
                        ? number_format($rowf['amount'], 2, '.', '') : ''
        ];
    }
}
?>
const codesData = <?= json_encode($fees, JSON_PRETTY_PRINT) ?>;

function fmt(n) {
  const num = Number(n) || 0;
  return num.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
}

function populateCodeSelect(sel, selected="") {
  if (!sel) return;
  sel.innerHTML = '<option value="">-- Select --</option>';
  Object.keys(codesData).sort().forEach(k=>{
    const opt = document.createElement('option');
    opt.value = k;
    const val = (codesData[k].value && codesData[k].value !== "") ? (' (₱' + fmt(codesData[k].value) + ')') : '';
    opt.textContent = k + ' — ' + codesData[k].name + val;
    if (selected === k) opt.selected = true;
    sel.appendChild(opt);
  });
  const otherOpt = document.createElement('option');
  otherOpt.value = "OTHER";
  otherOpt.textContent = "OTHER — Manual Entry";
  if (selected === "OTHER") otherOpt.selected = true;
  sel.appendChild(otherOpt);
}

function onCodeChange(event) {
  const sel = event.target;
  const row = sel.closest('.payment-row');
  const acct = row.querySelector('.acctname');
  const constv = row.querySelector('.constval');
  const amt = row.querySelector('.amount');

  if (sel.value === "OTHER") {
    acct.value = ''; acct.readOnly = false; acct.classList.remove('readonly-bg');
    constv.value = ''; constv.readOnly = false; constv.classList.remove('readonly-bg');
    amt.value = ''; amt.readOnly = false;
  } else if (sel.value && codesData[sel.value]) {
    acct.value = codesData[sel.value].name || '';
    acct.readOnly = true; acct.classList.add('readonly-bg');
    if (codesData[sel.value].const && codesData[sel.value].const.trim() !== "") {
      constv.value = codesData[sel.value].const;
      constv.readOnly = true; constv.classList.add('readonly-bg');
      amt.value = codesData[sel.value].value || '';
      amt.readOnly = true;
    } else {
      constv.value = ''; constv.readOnly = true; constv.classList.add('readonly-bg');
      amt.value = ''; amt.readOnly = false;
    }
  } else {
    acct.value = ''; acct.readOnly = true; acct.classList.add('readonly-bg');
    constv.value = ''; constv.readOnly = true; constv.classList.add('readonly-bg');
    amt.value = ''; amt.readOnly = false;
  }
  recalcTotal();
}

function recalcTotal() {
  let total = 0;
  document.querySelectorAll('.amount').forEach(inp=>{
    const v = parseFloat(inp.value || 0);
    if (!isNaN(v)) total += v;
  });
  document.getElementById('totalAmount').textContent = fmt(total);
}

function initRowEvents(row) {
  const sel = row.querySelector('.code-select');
  if (sel) {
    populateCodeSelect(sel, sel.value);
    sel.addEventListener('change', onCodeChange);
  }
  const amt = row.querySelector('.amount');
  if (amt) amt.addEventListener('input', recalcTotal);

  const resetBtn = row.querySelector('.reset-row');
  if (resetBtn) resetBtn.addEventListener('click', function(){
    row.querySelectorAll('input,select').forEach(el=>{
      el.value = '';
      if (el.classList.contains('acctname') || el.classList.contains('constval')) {
        el.readOnly = true; el.classList.add('readonly-bg');
      }
    });
    recalcTotal();
    row.classList.add('row-highlight');
    setTimeout(()=> row.classList.remove('row-highlight'),400);
  });

  const delBtn = row.querySelector('.remove-row');
  if (delBtn) delBtn.addEventListener('click', function(){
    if (document.querySelectorAll('.payment-row').length > 1) {
      row.remove(); recalcTotal();
    } else {
      row.querySelectorAll('input,select').forEach(el=>{
        el.value = '';
        if (el.classList.contains('acctname') || el.classList.contains('constval')) {
          el.readOnly = true; el.classList.add('readonly-bg');
        }
      });
      recalcTotal();
    }
  });
}

document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.payment-row').forEach(initRowEvents);

  document.getElementById('addRow').addEventListener('click', function(){
    const rows = document.querySelectorAll('.payment-row');
    if (rows.length >= 8) { alert("Max 8 rows."); return; }

    const first = rows[0];
    const clone = first.cloneNode(true);
    clone.querySelectorAll('input, select').forEach(el => {
      el.value = '';
      if (el.classList.contains('acctname') || el.classList.contains('constval')) {
        el.readOnly = true;
        el.classList.add('readonly-bg');
      } else {
        el.readOnly = false;
      }
    });

    document.getElementById('paymentRows').appendChild(clone);
    initRowEvents(clone);
  });

  recalcTotal();
});

function updateChange() {
  const total = parseFloat(document.getElementById('totalAmount').textContent.replace(/,/g,'')) || 0;
  const cash = parseFloat(document.getElementById('cashReceived').value) || 0;
  const change = cash - total;
  document.getElementById('changeAmount').value = fmt(change >= 0 ? change : 0);
}

document.addEventListener('DOMContentLoaded', function(){
  const cashInput = document.getElementById('cashReceived');
  if (cashInput) {
    cashInput.addEventListener('input', updateChange);
  }
  // ensure initial calculation on load
  updateChange();
});

</script>


</body>
</html>
