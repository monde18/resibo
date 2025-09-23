<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ✅ Restrict to admin only
if ($_SESSION['role'] !== 'Admin') {
    echo "<div style='padding:20px;font-family:sans-serif;color:red;'>
            ❌ Access Denied. Admins only.
          </div>";
    exit;
}

$result = $conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Activity Logs</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f6f9fc; font-family: system-ui, sans-serif; }
    .card { border-radius: 12px; box-shadow: 0 6px 18px rgba(16,24,40,0.06); }
    th { background: #f1f5f9; }
  </style>
</head>
<body class="p-4">

<div class="container">
  <div class="card p-4">
    <h3 class="mb-3">📜 Activity Logs</h3>
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead>
          <tr>
            <th>Date</th>
            <th>User</th>
            <th>Action</th>
            <th>Details</th>
            <th>IP Address</th>
            <th>Device</th>
          </tr>
        </thead>
        <tbody>
          <?php while($log = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $log['created_at'] ?></td>
            <td><?= htmlspecialchars($log['username']) ?></td>
            <td><span class="badge bg-primary"><?= htmlspecialchars($log['action']) ?></span></td>
            <td><?= htmlspecialchars($log['details']) ?></td>
            <td><?= $log['ip_address'] ?></td>
            <td>
              <span title="<?= htmlspecialchars($log['user_agent']) ?>">
                <?= htmlspecialchars(substr($log['user_agent'],0,40)) ?>...
              </span>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
