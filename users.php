<?php
// users.php — User Management with OR assignment + credentials + edit + overlap checks
include 'config.php';
session_start();

function sanitize($v) { return htmlspecialchars(trim($v)); }

$error = '';
$success = '';

// Handle Create User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $fname    = sanitize($_POST['fname'] ?? '');
    $lname    = sanitize($_POST['lname'] ?? '');
    $role     = sanitize($_POST['role'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $or_start = intval($_POST['or_start'] ?? 0);
    $or_end   = intval($_POST['or_end'] ?? 0);

    if (!$fname || !$lname || !$role || !$or_start || !$or_end || !$username || !$password) {
        $error = "All fields are required.";
    } elseif ($or_start > $or_end) {
        $error = "OR Start must be less than OR End.";
    } else {
        // Check username uniqueness
        $check = $conn->prepare("SELECT COUNT(*) FROM users WHERE username=?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->bind_result($cnt);
        $check->fetch();
        $check->close();

        if ($cnt > 0) {
            $error = "⚠️ Username <strong>$username</strong> is already taken.";
        } else {
            // Check OR overlap
            $overlap = $conn->prepare("
                SELECT username, or_start, or_end
                FROM users
                WHERE NOT (or_end < ? OR or_start > ?)
            ");
            $overlap->bind_param("ii", $or_start, $or_end);
            $overlap->execute();
            $result = $overlap->get_result();

            if ($result->num_rows > 0) {
                $conflicts = [];
                while ($c = $result->fetch_assoc()) {
                    $conflicts[] = "{$c['username']} ({$c['or_start']}–{$c['or_end']})";
                }
                $error = "⚠️ OR range $or_start–$or_end overlaps with: <br>" . implode("<br>", $conflicts);
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (first_name,last_name,username,password,role,or_start,or_end) VALUES (?,?,?,?,?,?,?)");
                $stmt->bind_param("ssssiii", $fname,$lname,$username,$hash,$role,$or_start,$or_end);
                if ($stmt->execute()) {
                    $success = "✅ User <strong>$username</strong> created successfully!";
                } else {
                    $error = "Database error: ".$stmt->error;
                }
                $stmt->close();
            }
            $overlap->close();
        }
    }
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id       = intval($_POST['id']);
    $fname    = sanitize($_POST['fname'] ?? '');
    $lname    = sanitize($_POST['lname'] ?? '');
    $role     = sanitize($_POST['role'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $or_start = intval($_POST['or_start'] ?? 0);
    $or_end   = intval($_POST['or_end'] ?? 0);
    $password = $_POST['password'] ?? '';

    if (!$fname || !$lname || !$role || !$or_start || !$or_end || !$username) {
        $error = "All fields except password are required.";
    } elseif ($or_start > $or_end) {
        $error = "OR Start must be less than OR End.";
    } else {
        // Check username uniqueness for other users
        $check = $conn->prepare("SELECT COUNT(*) FROM users WHERE username=? AND id<>?");
        $check->bind_param("si", $username, $id);
        $check->execute();
        $check->bind_result($cnt);
        $check->fetch();
        $check->close();

        if ($cnt > 0) {
            $error = "⚠️ Username <strong>$username</strong> is already taken.";
        } else {
            // Check OR overlap with others
            $overlap = $conn->prepare("
                SELECT username, or_start, or_end
                FROM users
                WHERE id<>? AND NOT (or_end < ? OR or_start > ?)
            ");
            $overlap->bind_param("iii", $id, $or_start, $or_end);
            $overlap->execute();
            $result = $overlap->get_result();

            if ($result->num_rows > 0) {
                $conflicts = [];
                while ($c = $result->fetch_assoc()) {
                    $conflicts[] = "{$c['username']} ({$c['or_start']}–{$c['or_end']})";
                }
                $error = "⚠️ OR range $or_start–$or_end overlaps with: <br>" . implode("<br>", $conflicts);
            } else {
                if ($password) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET first_name=?,last_name=?,username=?,password=?,role=?,or_start=?,or_end=? WHERE id=?");
                    $stmt->bind_param("ssssiiii", $fname,$lname,$username,$hash,$role,$or_start,$or_end,$id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET first_name=?,last_name=?,username=?,role=?,or_start=?,or_end=? WHERE id=?");
                    $stmt->bind_param("ssssiii", $fname,$lname,$username,$role,$or_start,$or_end,$id);
                }
                if ($stmt->execute()) {
                    $success = "✅ User <strong>$username</strong> updated successfully!";
                } else {
                    $error = "Database error: ".$stmt->error;
                }
                $stmt->close();
            }
            $overlap->close();
        }
    }
}

// Fetch users
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
    .accent { color: #0ea5a4; }
    .progress { height: 6px; border-radius: 4px; }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-4">
  <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-lg-10">

      <!-- Create User -->
      <div class="card p-4 mb-4">
        <h4>Create User</h4>
        <form method="post">
          <input type="hidden" name="create_user" value="1">
          <div class="row g-3 mb-3">
            <div class="col-md-6"><input type="text" name="fname" class="form-control" placeholder="First Name" required></div>
            <div class="col-md-6"><input type="text" name="lname" class="form-control" placeholder="Last Name" required></div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6"><input type="text" name="username" class="form-control" placeholder="Username" required></div>
            <div class="col-md-6"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <select name="role" class="form-select" required>
                <option value="">-- Select Role --</option>
                <option value="Admin">Admin</option>
                <option value="Cashier">Cashier</option>
                <option value="Encoder">Encoder</option>
              </select>
            </div>
            <div class="col-md-3"><input type="number" name="or_start" class="form-control" placeholder="OR Start" required></div>
            <div class="col-md-3"><input type="number" name="or_end" class="form-control" placeholder="OR End" required></div>
          </div>
          <button type="submit" class="btn btn-success">Save</button>
        </form>
      </div>

      <!-- Users Table -->
      <div class="card p-4">
        <h5>User List</h5>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th><th>Name</th><th>Username</th><th>Role</th><th>OR Range</th>
                <th>Usage</th><th>Last OR Used</th><th>Created</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!$users): ?>
              <tr><td colspan="9" class="text-center text-muted">No users yet.</td></tr>
            <?php else: foreach ($users as $u):
              $left = $u['total_or'] - $u['used_or'];
              $pct = $u['total_or'] > 0 ? round(($u['used_or']/$u['total_or'])*100) : 0;

              $conflicts = [];
              foreach ($users as $other) {
                if ($u['id'] != $other['id']) {
                  if (!($u['or_end'] < $other['or_start'] || $u['or_start'] > $other['or_end'])) {
                    $conflicts[] = $other['username']." (".$other['or_start']."–".$other['or_end'].")";
                  }
                }
              }
            ?>
              <tr class="<?= $conflicts ? 'table-danger' : '' ?>">
                <td><?= $u['id'] ?></td>
                <td><?= $u['first_name']." ".$u['last_name'] ?></td>
                <td><?= $u['username'] ?></td>
                <td><?= $u['role'] ?></td>
                <td>
                  <?= str_pad($u['or_start'],5,'0',STR_PAD_LEFT) ?>–
                  <?= str_pad($u['or_end'],5,'0',STR_PAD_LEFT) ?>
                  <?php if ($conflicts): ?>
                    <br><span class="badge bg-danger" data-bs-toggle="tooltip" title="<?= implode(', ', $conflicts) ?>">⚠ Overlap</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div><?= $u['used_or'] ?> used / <?= $left ?> left</div>
                  <div class="progress"><div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div></div>
                </td>
                <td><?= $u['last_used'] ? str_pad($u['last_used'],5,'0',STR_PAD_LEFT) : '-' ?></td>
                <td><?= $u['created_at'] ?></td>
                <td>
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $u['id'] ?>">Edit</button>
                </td>
              </tr>

              <!-- Edit Modal -->
              <div class="modal fade" id="editModal<?= $u['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <form method="post">
                      <input type="hidden" name="edit_user" value="1">
                      <input type="hidden" name="id" value="<?= $u['id'] ?>">
                      <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-2"><input type="text" name="fname" value="<?= $u['first_name'] ?>" class="form-control" placeholder="First Name" required></div>
                        <div class="mb-2"><input type="text" name="lname" value="<?= $u['last_name'] ?>" class="form-control" placeholder="Last Name" required></div>
                        <div class="mb-2"><input type="text" name="username" value="<?= $u['username'] ?>" class="form-control" required></div>
                        <div class="mb-2"><input type="password" name="password" class="form-control" placeholder="Leave blank to keep password"></div>
                        <div class="mb-2">
                          <select name="role" class="form-select" required>
                            <option value="Admin" <?= $u['role']=="Admin"?"selected":"" ?>>Admin</option>
                            <option value="Cashier" <?= $u['role']=="Cashier"?"selected":"" ?>>Cashier</option>
                            <option value="Encoder" <?= $u['role']=="Encoder"?"selected":"" ?>>Encoder</option>
                          </select>
                        </div>
                        <div class="row g-2">
                          <div class="col-md-6"><input type="number" name="or_start" value="<?= $u['or_start'] ?>" class="form-control" required></div>
                          <div class="col-md-6"><input type="number" name="or_end" value="<?= $u['or_end'] ?>" class="form-control" required></div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el) })
});
</script>
</body>
</html>
