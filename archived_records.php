<?php include 'config.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Archived Payment Records</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    body { background:#f9fafb; }
    .card { border-radius:14px; box-shadow:0 8px 20px rgba(0,0,0,.08); }
    .archived-row { background-color: #f8f9fa !important; }
  </style>
</head>
<body> 
<?php include 'navbar.php'; ?>
<div class="container py-4">
 
  <div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4><span class="material-icons">archive</span> Archived Payment Records</h4>
      <a href="records.php" class="btn btn-primary btn-icon">
        <span class="material-icons">arrow_back</span> Back to Active Records
      </a>
    </div>
    <div class="table-responsive">
      <table id="archivedTable" class="table table-striped table-hover align-middle nowrap">
        <thead>
          <tr>
            <th>Date</th>
            <th>Payee</th>
            <th>OR No.</th>
            <th>Total</th>
            <th>Archive Reason</th>
            <th>Archived Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // First check if the archive columns exist
          $checkColumns = $conn->query("SHOW COLUMNS FROM payments LIKE 'archived'");
          if ($checkColumns->num_rows > 0) {
            // Check if archive_reason column exists
            $checkReasonColumn = $conn->query("SHOW COLUMNS FROM payments LIKE 'archive_reason'");
            $hasReasonColumn = ($checkReasonColumn->num_rows > 0);
            
            // Check if archived_date column exists
            $checkDateColumn = $conn->query("SHOW COLUMNS FROM payments LIKE 'archived_date'");
            $hasDateColumn = ($checkDateColumn->num_rows > 0);
            
            // Build the query based on available columns
            $query = "SELECT id, date, payee, reference_no, total, archived";
            if ($hasReasonColumn) $query .= ", archive_reason";
            if ($hasDateColumn) $query .= ", archived_date";
            $query .= " FROM payments WHERE archived = 1 ORDER BY " . ($hasDateColumn ? "archived_date DESC" : "date DESC");
            
            $result = $conn->query($query);
            if ($result) {
              while ($row = $result->fetch_assoc()):
          ?>
          <tr class="archived-row">
            <td><?= $row['date'] ?></td>
            <td><?= htmlspecialchars($row['payee']) ?></td>
            <td><?= htmlspecialchars($row['reference_no']) ?></td>
            <td><strong>₱<?= number_format($row['total'],2) ?></strong></td>
            <td><?= $hasReasonColumn ? htmlspecialchars($row['archive_reason'] ?? 'No reason provided') : 'Not recorded' ?></td>
            <td><?= $hasDateColumn ? ($row['archived_date'] ?? 'Unknown') : 'Not recorded' ?></td>
            <td>
              <button class="btn btn-sm btn-info" onclick="restoreRecord(<?= $row['id'] ?>)">
                <span class="material-icons">unarchive</span> Restore
              </button>
            </td>
          </tr>
          <?php 
              endwhile;
            } else {
              echo '<tr><td colspan="7" class="text-center">Error executing query: ' . $conn->error . '</td></tr>';
            }
          } else {
            echo '<tr><td colspan="7" class="text-center">No archived records system available. The archive column does not exist in the database.</td></tr>';
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
  $('#archivedTable').DataTable();
});

function restoreRecord(id) {
  if (confirm('Are you sure you want to restore this record?')) {
    $.post('archive.php', { 
      id: id,
      action: 'restore' 
    }, function(response) {
      if (response.success) {
        alert('Record restored successfully!');
        location.reload();
      } else {
        alert('Error: ' + response.message);
      }
    }, 'json').fail(function() {
      alert('Error restoring record.');
    });
  }
}
</script>
</body>
</html>