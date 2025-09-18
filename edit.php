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
            <label class="form-label">Reference No.</label>
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
                <?php foreach ($fees as $fcode => $fee): ?>
                  <option value="<?= $fcode ?>"
                    data-name="<?= htmlspecialchars($fee['name']) ?>"
                    data-const="<?= htmlspecialchars($fee['const']) ?>"
                    data-amount="<?= htmlspecialchars($fee['value']) ?>"
                    <?= ($code==$fcode?'selected':'') ?>>
                    <?= $fcode ?> — <?= $fee['name'] ?>
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
                <?php foreach ($fees as $fcode => $fee): ?>
                  <option value="<?= $fcode ?>"><?= $fcode ?> — <?= $fee['name'] ?></option>
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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
const fees = <?= json_encode($fees) ?>;

function fmt(n){ 
  return (Number(n)||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
}

function recalcTotal(){
  let total = 0;
  $('.amount').each(function(){
    total += parseFloat($(this).val()) || 0;
  });
  $('#totalAmount').text(fmt(total));
}

function initRowEvents(row){
  $(row).find('.code-select').on('change', function(){
    let selected = $(this).val();
    let f = fees[selected] || {};
    let acct = $(row).find('.acctname');
    let constv = $(row).find('.constval');
    let amt = $(row).find('.amount');

    if(selected === "OTHER"){
      acct.val('').prop('readonly', false).removeClass('readonly-bg');
      constv.val('').prop('readonly', false).removeClass('readonly-bg');
      amt.val('').prop('readonly', false);
    } else if(f && f.name){
      acct.val(f.name).prop('readonly', true).addClass('readonly-bg');
      if(f.const){
        constv.val(f.const).prop('readonly', true).addClass('readonly-bg');
        amt.val(f.value).prop('readonly', true);
      } else {
        constv.val('').prop('readonly', true).addClass('readonly-bg');
        amt.val('').prop('readonly', false);
      }
    } else {
      acct.val('').prop('readonly', true).addClass('readonly-bg');
      constv.val('').prop('readonly', true).addClass('readonly-bg');
      amt.val('').prop('readonly', false);
    }
    recalcTotal();
  });

  $(row).find('.amount').on('input', recalcTotal);

  $(row).find('.reset-row').on('click', function(){
    $(row).find('input,select').val('');
    $(row).find('.acctname,.constval').prop('readonly', true).addClass('readonly-bg');
    recalcTotal();
    $(row).addClass('row-highlight');
    setTimeout(()=>$(row).removeClass('row-highlight'),400);
  });

  $(row).find('.remove-row').on('click', function(){
    if($('.payment-row').length > 1){
      $(row).remove();
      recalcTotal();
    } else {
      $(row).find('input,select').val('');
      $(row).find('.acctname,.constval').prop('readonly', true).addClass('readonly-bg');
      recalcTotal();
    }
  });
}

$(function(){
  $('.payment-row').each(function(){ initRowEvents(this); });

  $('#addRow').on('click', function(){
    if($('.payment-row').length >= 8){ alert("Max 8 rows."); return; }
    let clone = $('.payment-row').first().clone();
    clone.find('input,select').val('');
    clone.find('.acctname,.constval').prop('readonly', true).addClass('readonly-bg');
    $('#paymentRows').append(clone);
    initRowEvents(clone);
  });

  recalcTotal();
});
</script>
</body>
</html>
