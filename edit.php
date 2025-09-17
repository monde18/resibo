<?php
include 'config.php';
include 'fees.php'; // <-- your big fees array

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
    body { background: #f8f9fa; }
    .card { border-radius: 12px; box-shadow: 0 6px 15px rgba(0,0,0,0.08); border: none; }
    .form-control, .form-select { border-radius: 8px; padding: 8px; }
    .form-label { font-weight: 500; color: #495057; margin-bottom: 8px; }
    .btn { border-radius: 8px; padding: 8px 16px; font-weight: 500; }
    .table th { background-color: #f1f3f5; font-weight: 600; }
    .payment-row { transition: all 0.2s ease; }
    .header-section { background: linear-gradient(135deg, #6c5ce7, #8e44ad); color: white; border-radius: 12px 12px 0 0; }
    .total-display { background-color: #e8f5e8; border-radius: 8px; padding: 15px; font-weight: 600; }
  </style>
</head>
<body>
<div class="container py-4">
  <?php include 'navbar.php'; ?>
  
  <div class="card">
    <div class="header-section p-4">
      <div class="d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><span class="material-icons">edit</span> Edit Payment Record</h4>
        <a href="records.php" class="btn btn-light btn-icon">
          <span class="material-icons">arrow_back</span> Back to Records
        </a>
      </div>
    </div>
    
    <div class="card-body p-4">
      <form method="post" action="update.php">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">

        <!-- Transaction Info -->
        <div class="row mb-4">
          <div class="col-md-4 mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" value="<?= $row['date'] ?>" class="form-control" required>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Payee</label>
            <input type="text" name="payee" value="<?= htmlspecialchars($row['payee']) ?>" class="form-control" required>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Reference No.</label>
            <input type="text" name="refno" value="<?= htmlspecialchars($row['reference_no']) ?>" class="form-control" required>
          </div>
        </div>

        <!-- Payments Table -->
        <div class="table-responsive">
          <table class="table table-hover" id="paymentsTable">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Account Name</th>
                <th>Const</th>
                <th>Amount</th>
                <th style="width:120px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php for ($i=1; $i<=8; $i++): ?>
              <tr class="payment-row">
                <td>
                  <select name="code[]" class="form-select form-select-sm code-select">
                    <option value="">-- Select --</option>
                    <?php foreach ($fees as $code => $fee): ?>
                      <option value="<?= $code ?>" 
                        data-name="<?= htmlspecialchars($fee['name']) ?>" 
                        data-amount="<?= htmlspecialchars($fee['value']) ?>"
                        <?= ($row["code$i"]==$code?'selected':'') ?>>
                        <?= $code ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td>
                  <input type="text"
                         name="acctname[]"
                         value="<?= htmlspecialchars($row["account_name$i"] ?? '') ?>"
                         class="form-control form-control-sm acctname-input"
                         readonly>
                </td>
                <td>
                  <input type="text" name="constval[]" value="" class="form-control form-control-sm">
                </td>
                <td>
                  <input type="number" step="0.01"
                         name="amount[]"
                         value="<?= htmlspecialchars($row["amount$i"] ?? '') ?>"
                         class="form-control form-control-sm amount">
                </td>
                <td>
                  <button type="button" class="btn btn-outline-danger btn-sm remove-row" title="Remove">
                    <span class="material-icons" style="font-size:18px;">delete</span>
                  </button>
                  <button type="button" class="btn btn-outline-secondary btn-sm refresh-row" title="Clear">
                    <span class="material-icons" style="font-size:18px;">refresh</span>
                  </button>
                </td>
              </tr>
              <?php endfor; ?>
            </tbody>
          </table>
        </div>

        <!-- Total Amount -->
        <div class="row mt-4">
          <div class="col-md-6">
            <div class="total-display">
              <div class="d-flex justify-content-between align-items-center">
                <span class="fs-5">Total Amount:</span>
                <span class="fs-4 text-success">₱<span id="totalAmount"><?= number_format($row['total'],2) ?></span></span>
              </div>
            </div>
          </div>
          <div class="col-md-6 text-end">
            <button type="submit" class="btn btn-success btn-lg btn-icon">
              <span class="material-icons">save</span> Save Changes
            </button>
            <a href="records.php" class="btn btn-outline-secondary btn-lg">
              Cancel
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(function(){
  const fees = <?= json_encode($fees) ?>;

  function recalcTotal(){
    let total = 0;
    $('.amount').each(function(){
      total += parseFloat($(this).val()) || 0;
    });
    $('#totalAmount').text(total.toFixed(2));
  }

  // Auto-fill account name + amount when code changes
  $(document).on('change', '.code-select', function(){
    let selected = $(this).find(':selected');
    let row = $(this).closest('tr');
    let acctName = selected.data('name') || '';
    let defaultAmt = selected.data('amount') || '';

    row.find('.acctname-input').val(acctName);
    if (defaultAmt && !row.find('.amount').val()) {
      row.find('.amount').val(defaultAmt);
    }
    recalcTotal();
  });

  $(document).on('input','.amount', recalcTotal);

  $(document).on('click','.remove-row', function(){
    let row = $(this).closest('tr');
    row.find('select,input').val('');
    recalcTotal();
  });

  $(document).on('click','.refresh-row', function(){
    let row = $(this).closest('tr');
    row.find('select,input').val('');
    recalcTotal();
  });

  recalcTotal();
});
</script>
</body>
</html>
