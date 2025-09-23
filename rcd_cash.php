<?php include 'config.php';


?>
<
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Payment Records</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap + DataTables -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <!-- Select2 -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <style>
    body { background:#f9fafb; }
    .card { border-radius:14px; box-shadow:0 8px 20px rgba(0,0,0,.08); }
    .btn-icon { display:flex; align-items:center; gap:4px; }
    table.dataTable thead th { background:#f1f3f5; }
    .payments-block {
      display:inline-block;
      background:#e9ecef;
      padding:4px 8px;
      margin:2px 2px;
      border-radius:6px;
      font-family: monospace;
    }
    .filters { margin-bottom: 1rem; }
    .action-buttons { display: flex; flex-wrap: wrap; gap: 0.3rem; }
    .action-buttons .btn { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
    .btn-print { background-color: #17a2b8; border-color: #17a2b8; color: white; }
    .btn-print:hover { background-color: #138496; border-color: #117a8b; }
    .btn-archive { background-color: #6c757d; border-color: #6c757d; color: white; }
    .btn-archive:hover { background-color: #5a6268; border-color: #545b62; }
    .modal-content { border-radius: 14px; }
    @media print {
      body * { visibility: hidden; }
      .printable, .printable * { visibility: visible; }
      .printable { position: absolute; left: 0; top: 0; width: 100%; }
      .dataTables_info, .dataTables_paginate, .dt-buttons, .filters, .btn { display: none !important; }
      table { width: 100% !important; font-size: 12px; }
    }
  </style>
</head>
<body>
<?php if (isset($_GET['status'])): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999">
  <div class="toast align-items-center text-white bg-<?= $_GET['status']==='success'?'success':'danger' ?> border-0 show" role="alert">
    <div class="d-flex">
      <div class="toast-body">
        <?= $_GET['status']==='success' ? '✅ Record updated successfully!' : '❌ Update failed. Please try again.' ?>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include 'navbar.php'; ?>
<?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
  <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
    ✅ Payment record updated successfully!
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<div class="container py-4">

  <div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4><span class="material-icons">table_view</span> Payment Records</h4>
    </div>

    <!-- Filters -->
    <div class="row filters">
      <div class="col-md-3">
        <label class="form-label">Filter by Payee</label>
        <select id="payeeFilter" class="form-select">
          <option value="">All Payees</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Filter by OR No.</label>
        <select id="refFilter" class="form-select">
          <option value="">All References</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Date From</label>
        <input type="date" id="minDate" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label">Date To</label>
        <input type="date" id="maxDate" class="form-control">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button id="resetFilters" class="btn btn-outline-secondary w-100">
          <span class="material-icons">restart_alt</span> Reset Filters
        </button>
      </div>
    </div>

    <div class="table-responsive">
      <table id="recordsTable" class="table table-striped table-hover align-middle nowrap" style="width:100%">
        <thead>
          <tr>
            <th>Date</th>
            <th>Payee</th>
            <th>OR No.</th>
            <th>Payments</th>
            <th>Total</th>
            <th>Cash Received</th>
            <th>Change</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $result = $conn->query("SELECT * FROM payments WHERE archived = 0 OR archived IS NULL ORDER BY date ASC");
        while ($row = $result->fetch_assoc()):
          $payments = [];
          for ($i=1; $i<=8; $i++) {
            if (!empty($row["code$i"]) || !empty($row["account_name$i"])) {
              $amount = $row["amount$i"] ?? 0;
              $payments[] = "<div class='payments-block'>{$row["code$i"]} - {$row["account_name$i"]}<br>(₱".number_format($amount,2).")</div>";
            }
          }
        ?>
        <tr data-id="<?= $row['id'] ?>">
          <td><?= $row['date'] ?></td>
          <td><?= htmlspecialchars($row['payee']) ?></td>
          <td><?= htmlspecialchars($row['reference_no']) ?></td>
          <td><?= implode("<br>", $payments) ?></td>
          <td><strong>₱<?= number_format($row['total'],2) ?></strong></td>
          <td><span class="text-success">₱<?= number_format($row['cash_received'],2) ?></span></td>
          <td><span class="text-danger">₱<?= number_format($row['change_amount'],2) ?></span></td>
          <td>
            <div class="action-buttons">
              <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning btn-icon"><span class="material-icons">edit</span></a>
              <a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete this record?')" class="btn btn-sm btn-danger btn-icon"><span class="material-icons">delete</span></a>
              <button class="btn btn-sm btn-print btn-icon" onclick="openPrintModal(<?= $row['id'] ?>)" data-bs-toggle="modal" data-bs-target="#printModal"><span class="material-icons">print</span></button>
              <button class="btn btn-sm btn-archive btn-icon" onclick="openArchiveModal(<?= $row['id'] ?>)" data-bs-toggle="modal" data-bs-target="#archiveModal"><span class="material-icons">archive</span></button>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Archive Modal -->
<div class="modal fade" id="archiveModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Archive Record</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <p>Please provide a reason for archiving this record.</p>
      <div class="mb-3">
        <select class="form-select" id="archiveReason">
          <option value="">Select a reason</option>
          <option value="Completed">Transaction Completed</option>
          <option value="Obsolete">Obsolete Record</option>
          <option value="Duplicate">Duplicate Entry</option>
          <option value="Error">Data Error</option>
          <option value="Other">Other Reason</option>
        </select>
      </div>
      <div class="mb-3" id="otherReasonContainer" style="display:none;">
        <textarea class="form-control" id="otherReason" rows="2" placeholder="Specify other reason"></textarea>
      </div>
      <input type="hidden" id="archiveRecordId">
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      <button class="btn btn-primary" onclick="archiveRecord()">Archive Record</button>
    </div>
  </div></div>
</div>

<!-- Print Modal -->
<div class="modal fade" id="printModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Print Receipt</h5>
      <button class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <p>Confirm details for printing the receipt.</p>

      <div class="mb-3">
        <label class="form-label">Official Receipt (OR) Number</label>
        <div class="input-group">
          <input type="text" class="form-control" id="printOR" readonly>
          <button class="btn btn-outline-warning" type="button" id="editORBtn">Change OR</button>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Payor</label>
        <input type="text" class="form-control" id="printPayor" readonly>
      </div>

      <div class="mb-3">
        <label class="form-label">Payments</label>
        <div class="table-responsive">
          <table class="table table-bordered" id="printPaymentsTable">
            <thead>
              <tr>
                <th>Code</th>
                <th>Account</th>
                <th>Amount (₱)</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <label class="form-label">Total</label>
          <input type="text" class="form-control" id="printTotal" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Cash Received</label>
          <input type="text" class="form-control" id="printCashReceived" readonly>
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-md-6">
          <label class="form-label">Change</label>
          <input type="text" class="form-control" id="printChange" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Payment Method</label>
          <input type="text" class="form-control" value="Cash" readonly>
        </div>
      </div>

      <input type="hidden" id="printRecordId">
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      <button class="btn btn-primary btn-print" onclick="printRecord()">Print Receipt</button>
    </div>
  </div></div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function(){
  // Date range filter
  $.fn.dataTable.ext.search.push(function(settings,data){
    let min=$('#minDate').val(), max=$('#maxDate').val(), d=new Date(data[0]);
    if(!min&&!max) return true;
    if(min && d < new Date(min)) return false;
    if(max && d > new Date(max)) return false;
    return true;
  });

  let table=$('#recordsTable').DataTable({
    responsive:true,
    dom:'Bfrtip',
    buttons:[
      { extend:'print', text:'<span class="material-icons">print</span> Print All', className:'btn btn-info', exportOptions:{ columns:[0,1,2,3,4,5,6] } },
      { extend:'excel', exportOptions:{ columns:[0,1,2,3,4,5,6] } },
      { extend:'pdf', exportOptions:{ columns:[0,1,2,3,4,5,6] } },
      'colvis'
    ],
    order:[[0,'desc']], columnDefs:[{ orderable:false, targets:7 }]
  });

  // Archive modal logic
  $('#archiveReason').on('change',function(){ $(this).val()==='Other' ? $('#otherReasonContainer').show() : $('#otherReasonContainer').hide(); });
  window.openArchiveModal=function(id){ $('#archiveRecordId').val(id); $('#archiveReason').val(''); $('#otherReason').val(''); $('#otherReasonContainer').hide(); };
  window.archiveRecord=function(){
    const id=$('#archiveRecordId').val(), reason=$('#archiveReason').val(), other=$('#otherReason').val();
    if(!reason) return alert('Select a reason.');
    const final=reason==='Other'?other:reason;
    if(!final) return alert('Please provide a reason.');
    $.post('archive.php',{id:id,reason:final,action:'archive'},function(res){
      if(res.success){ alert('Record archived successfully!'); $('#archiveModal').modal('hide'); table.row($('tr[data-id="'+id+'"]')).remove().draw(); }
      else alert('Error: '+res.message);
    },'json').fail(()=>alert('Error archiving record.'));
  };

  // OR edit button
  $('#editORBtn').on('click', function(){
    let input = $('#printOR');
    input.prop('readonly', false).focus();
    $(this).text('Editing...').prop('disabled', true);
  });

  // Open Print Modal
  window.openPrintModal=function(id){
    $('#printRecordId').val(id);
    $('#printPaymentsTable tbody').empty();

    $.post('get_record.php', {id:id}, function(res){
      if(res.success){
        $('#printOR').val(res.data.reference_no).prop('readonly', true);
        $('#editORBtn').text('Change OR').prop('disabled', false);

        $('#printPayor').val(res.data.payee);
        $('#printTotal').val("₱ " + parseFloat(res.data.total).toFixed(2));
        $('#printCashReceived').val("₱ " + parseFloat(res.data.cash_received).toFixed(2));
        $('#printChange').val("₱ " + parseFloat(res.data.change_amount).toFixed(2));

        res.data.payments.forEach(function(p){
          $('#printPaymentsTable tbody').append(`
            <tr>
              <td>${p.code}</td>
              <td>${p.account}</td>
              <td class="text-end">₱ ${parseFloat(p.amount).toFixed(2)}</td>
            </tr>
          `);
        });

        $('#printModal').modal('show');
      } else {
        alert("Error fetching record: " + res.message);
      }
    },'json');
  };

  // Print Record
  window.printRecord=function(){
    const id = $('#printRecordId').val();
    const orNumber = $('#printOR').val();

    let form = document.createElement("form");
    form.method = "POST";
    form.action = "print_receipt.php";
    form.target = "_blank";

    let inputId = document.createElement("input");
    inputId.type = "hidden";
    inputId.name = "id";
    inputId.value = id;
    form.appendChild(inputId);

    let inputOR = document.createElement("input");
    inputOR.type = "hidden";
    inputOR.name = "or_number";
    inputOR.value = orNumber;
    form.appendChild(inputOR);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    $('#printModal').modal('hide');
  };

  // Dropdown filters
  table.column(1).data().unique().sort().each(d=>$('#payeeFilter').append(`<option value="${d}">${d}</option>`));
  table.column(2).data().unique().sort().each(d=>$('#refFilter').append(`<option value="${d}">${d}</option>`));
  $('#payeeFilter,#refFilter').select2({theme:'bootstrap5'});
  $('#payeeFilter').on('change',function(){ table.column(1).search(this.value).draw(); });
  $('#refFilter').on('change',function(){ table.column(2).search(this.value).draw(); });
  $('#minDate,#maxDate').on('change',()=>table.draw());

  $('#resetFilters').on('click',function(){
    $('#payeeFilter').val('').trigger('change');
    $('#refFilter').val('').trigger('change');
    $('#minDate,#maxDate').val('');
    table.search('').columns().search('').draw();
  });
});
</script>
</body>
</html>
