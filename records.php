<?php include 'config.php'; ?>
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
        <label class="form-label">Filter by Reference No.</label>
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
            <th>Reference No.</th>
            <th>Payments</th>
            <th>Total</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $result = $conn->query("SELECT * FROM payments WHERE archived = 0 OR archived IS NULL ORDER BY date DESC");
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
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Print Receipt</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <p>Confirm details for printing the receipt.</p>
      <div class="mb-3">
        <label class="form-label">Payor</label>
        <input type="text" class="form-control" id="printPayor" placeholder="Enter payor name">
      </div>
      <div class="mb-3">
        <label class="form-label">Nature of Collection</label>
        <input type="text" class="form-control" id="printNature" placeholder="Enter nature of collection">
      </div>
      <div class="mb-3">
        <label class="form-label">Payment Method</label>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="receivedCash" name="received[]" value="Cash">
          <label class="form-check-label" for="receivedCash">Cash</label>
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
      { extend:'print', text:'<span class="material-icons">print</span> Print All', className:'btn btn-info', exportOptions:{ columns:[0,1,2,3,4] } },
      'excel','pdf','colvis'
    ],
    order:[[0,'desc']], columnDefs:[{ orderable:false, targets:5 }]
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

  // Print modal logic
  window.openPrintModal=function(id){
    $('#printRecordId').val(id);
    const row = table.row($('tr[data-id="'+id+'"]')).data();
    $('#printPayor').val(row[1]); // Payee column (index 1)
    $('#printNature').val(row[3].split('<br>')[0].replace(/<\/?[^>]+(>|$)/g, "").split(' - ')[1] || ''); // First payment nature
    $('#receivedCash').prop('checked', false);
    $('#printModal').modal('show');
  };
  window.printRecord=function(){
    const id=$('#printRecordId').val();
    const payor=$('#printPayor').val();
    const nature=$('#printNature').val();
    const received=$('#receivedCash').is(':checked') ? ['Cash'] : [];

    let form=document.createElement("form");
    form.method="POST";
    form.action="receipt.php";
    form.target="_blank";

    let inputId=document.createElement("input");
    inputId.type="hidden";
    inputId.name="id";
    inputId.value=id;
    form.appendChild(inputId);

    let inputPayor=document.createElement("input");
    inputPayor.type="hidden";
    inputPayor.name="payee";
    inputPayor.value=payor;
    form.appendChild(inputPayor);

    let inputNature=document.createElement("input");
    inputNature.type="hidden";
    inputNature.name="account_name1";
    inputNature.value=nature;
    form.appendChild(inputNature);

    let inputReceived=document.createElement("input");
    inputReceived.type="hidden";
    inputReceived.name="received[]";
    inputReceived.value=received[0] || '';
    form.appendChild(inputReceived);

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

  // Reset filters
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