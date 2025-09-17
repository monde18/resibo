<?php
// users.php — User Management with OR number assignment + usage stats
include 'config.php';
session_start();

function sanitize($v) { return htmlspecialchars(trim($v)); }

$error = '';
$success = '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = sanitize($_POST['fname'] ?? '');
    $lname = sanitize($_POST['lname'] ?? '');
    $role  = sanitize($_POST['role'] ?? '');
    $or_start = intval($_POST['or_start'] ?? 0);
    $or_end   = intval($_POST['or_end'] ?? 0);

    if (!$fname || !$lname || !$role || !$or_start || !$or_end) {
        $error = "All fields are required.";
    } elseif ($or_start > $or_end) {
        $error = "OR Start must be less than OR End.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (first_name,last_name,role,or_start,or_end) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssii", $fname,$lname,$role,$or_start,$or_end);
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            $stmt->close();

            // Generate OR numbers for this user
            for ($i = $or_start; $i <= $or_end; $i++) {
                $ins = $conn->prepare("INSERT INTO or_numbers (user_id, or_number) VALUES (?,?)");
                $ins->bind_param("ii", $userId, $i);
                $ins->execute();
                $ins->close();
            }

            $success = "✅ User created successfully with OR range $or_start - $or_end!";
        } else {
            $error = "Database error: ".$stmt->error;
        }
    }
}

// Fetch users with OR stats
$users = [];
$sql = "
  SELECT u.*,
         COUNT(o.id) AS total_or,
         SUM(o.is_used=1) AS used_or,
         MAX(CASE WHEN o.is_used=1 THEN o.or_number END) AS last_used
  FROM users u
  LEFT JOIN or_numbers o ON u.id = o.user_id
  GROUP BY u.id
  ORDER BY u.created_at DESC
";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $users[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>User Management</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    body { background: #f6f9fc; }
    .card { border-radius: 12px; box-shadow: 0 6px 18px rgba(16,24,40,0.06); }
    .small-muted { font-size: .85rem; color: #6b7280; }
    .accent { color: #0ea5a4; }
    .progress { height: 6px; border-radius: 4px; }
    .progress-bar { font-size: .75rem; }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-4">

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-lg-8">

      <!-- User Creation Form -->
      <div class="card p-4 mb-4">
        <div class="d-flex align-items-center mb-3">
          <span class="material-icons bg-light rounded-circle p-2 me-3 accent">group_add</span>
          <h4 class="mb-0">Create User</h4>
        </div>

        <form method="post">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">First Name</label>
              <input type="text" name="fname" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Last Name</label>
              <input type="text" name="lname" class="form-control" required>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Role</label>
              <select name="role" class="form-select" required>
                <option value="">-- Select Role --</option>
                <option value="Admin">Admin</option>
                <option value="Cashier">Cashier</option>
                <option value="Encoder">Encoder</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">OR Start</label>
              <input type="number" name="or_start" class="form-control" placeholder="00001" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">OR End</label>
              <input type="number" name="or_end" class="form-control" placeholder="00050" required>
            </div>
          </div>

          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-success">
              <span class="material-icons">save</span> Save
            </button>
          </div>
        </form>
      </div>

      <!-- Users Table -->
      <div class="card p-4">
        <h5 class="mb-3">User List</h5>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Role</th>
                <th>OR Range</th>
                <th style="width:200px;">Usage</th>
                <th>Last OR Used</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($users) === 0): ?>
                <tr><td colspan="7" class="text-center text-muted">No users yet.</td></tr>
              <?php else: ?>
                <?php foreach ($users as $u): 
                  $left = $u['total_or'] - $u['used_or'];
                  $pct  = $u['total_or'] > 0 ? round(($u['used_or'] / $u['total_or']) * 100) : 0;
                ?>
                  <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= $u['first_name']." ".$u['last_name'] ?></td>
                    <td><?= $u['role'] ?></td>
                    <td><?= str_pad($u['or_start'],5,'0',STR_PAD_LEFT) ?> - <?= str_pad($u['or_end'],5,'0',STR_PAD_LEFT) ?></td>
                    <td>
                      <div class="small mb-1">
                        <span class="text-success"><?= $u['used_or'] ?> used</span> /
                        <span class="text-primary"><?= $left ?> left</span>
                      </div>
                      <div class="progress">
                        <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
                      </div>
                    </td>
                    <td>
                      <?= $u['last_used'] 
                            ? str_pad($u['last_used'],5,'0',STR_PAD_LEFT) 
                            : '<span class="text-muted">None yet</span>' ?>
                    </td>
                    <td><?= $u['created_at'] ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

</body>
</html>
