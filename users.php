<?php
// users.php — User Management with OR tracking + Activity Logs
include 'config.php';
session_start();

// ========== Helpers ==========
function sanitize($v) { return htmlspecialchars(trim($v)); }

function logActivity($conn, $userId, $username, $action, $details = '') {
    $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent  = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt   = $conn->prepare("INSERT INTO activity_logs 
        (user_id, username, action, details, ip_address, user_agent) 
        VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("isssss", $userId, $username, $action, $details, $ip, $agent);
    $stmt->execute();
    $stmt->close();
}

// ========== Initialize ==========
$error = '';
$success = '';

// ========== Create User ==========
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
        // Check if username already exists
        $check = $conn->prepare("SELECT COUNT(*) FROM users WHERE username=?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->bind_result($cnt);
        $check->fetch();
        $check->close();

        if ($cnt > 0) {
            $error = "⚠️ Username " . htmlspecialchars($username) . " is already taken.";
        } else {
            // Check OR range overlap
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
                    $conflicts[] = htmlspecialchars($c['username']) . " (" . $c['or_start'] . "–" . $c['or_end'] . ")";
                }
                $error = "⚠️ OR range {$or_start}–{$or_end} overlaps with:<br>" . implode("<br>", $conflicts);
            } else {
                // Insert user
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (first_name,last_name,username,password,role,or_start,or_end) VALUES (?,?,?,?,?,?,?)");
                $stmt->bind_param("ssssiii", $fname, $lname, $username, $hash, $role, $or_start, $or_end);
                if ($stmt->execute()) {
                    $success = "✅ User <strong>" . htmlspecialchars($username) . "</strong> created successfully!";

                    // Log activity
                    if (isset($_SESSION['user_id'])) {
                        $details = "Created user: $username (Role: $role, OR Range: $or_start–$or_end)";
                        logActivity($conn, $_SESSION['user_id'], $_SESSION['username'], "CREATE_USER", $details);
                    }
                } else {
                    $error = "Database error: " . $stmt->error;
                }
                $stmt->close();
            }
            $overlap->close();
        }
    }
}

// ========== Edit User ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id       = intval($_POST['id'] ?? 0);
    $fname    = sanitize($_POST['fname'] ?? '');
    $lname    = sanitize($_POST['lname'] ?? '');
    $role     = sanitize($_POST['role'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $or_start = intval($_POST['or_start'] ?? 0);
    $or_end   = intval($_POST['or_end'] ?? 0);
    $password = $_POST['password'] ?? '';

    if (!$id || !$fname || !$lname || !$role || !$or_start || !$or_end || !$username) {
        $error = "All fields except password are required.";
    } elseif ($or_start > $or_end) {
        $error = "OR Start must be less than OR End.";
    } else {
        // Username uniqueness check
        $check = $conn->prepare("SELECT COUNT(*) FROM users WHERE username=? AND id<>?");
        $check->bind_param("si", $username, $id);
        $check->execute();
        $check->bind_result($cnt);
        $check->fetch();
        $check->close();

        if ($cnt > 0) {
            $error = "⚠️ Username " . htmlspecialchars($username) . " is already taken.";
        } else {
            // OR overlap check (excluding current user)
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
                    $conflicts[] = htmlspecialchars($c['username']) . " (" . $c['or_start'] . "–" . $c['or_end'] . ")";
                }
                $error = "⚠️ OR range {$or_start}–{$or_end} overlaps with:<br>" . implode("<br>", $conflicts);
            } else {
                if ($password) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, username=?, password=?, role=?, or_start=?, or_end=? WHERE id=?");
                    $stmt->bind_param("ssssiiii", $fname, $lname, $username, $hash, $role, $or_start, $or_end, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, username=?, role=?, or_start=?, or_end=? WHERE id=?");
                    $stmt->bind_param("sssssii", $fname, $lname, $username, $role, $or_start, $or_end, $id);
                }
                if ($stmt->execute()) {
                    $success = "✅ User <strong>" . htmlspecialchars($username) . "</strong> updated successfully!";

                    // Log activity
                    if (isset($_SESSION['user_id'])) {
                        $details = "Edited user: $username (Role: $role, OR Range: $or_start–$or_end)";
                        logActivity($conn, $_SESSION['user_id'], $_SESSION['username'], "EDIT_USER", $details);
                    }
                } else {
                    $error = "Database error: " . $stmt->error;
                }
                $stmt->close();
            }
            $overlap->close();
        }
    }
}

// ========== Fetch users and calculate OR usage ==========
$users = [];
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $row['total_or'] = $row['or_end'] - $row['or_start'] + 1;

        // Count used ORs
        $q = $conn->prepare("SELECT COUNT(*) FROM payments WHERE reference_no BETWEEN ? AND ?");
        $q->bind_param("ii", $row['or_start'], $row['or_end']);
        $q->execute();
        $q->bind_result($used);
        $q->fetch();
        $q->close();
        $row['used_or'] = $used;

        // Last OR used
        $q = $conn->prepare("SELECT MAX(reference_no) FROM payments WHERE reference_no BETWEEN ? AND ?");
        $q->bind_param("ii", $row['or_start'], $row['or_end']);
        $q->execute();
        $q->bind_result($last);
        $q->fetch();
        $q->close();
        $row['last_used'] = $last;

        // Next available
        $row['next_available'] = null;
        for ($i = $row['or_start']; $i <= $row['or_end']; $i++) {
            $q = $conn->prepare("SELECT COUNT(*) FROM payments WHERE reference_no=?");
            $q->bind_param("i", $i);
            $q->execute();
            $q->bind_result($cnt);
            $q->fetch();
            $q->close();
            if ($cnt == 0) {
                $row['next_available'] = $i;
                break;
            }
        }

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
    .small-muted { font-size:.85rem; color:#6b7280; }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-4">
  <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-lg-11">

      <!-- Create User -->
      <div class="card p-4 mb-4">
        <div class="d-flex align-items-center mb-3">
          <span class="material-icons bg-light rounded-circle p-2 me-3 accent">person_add</span>
          <h4 class="mb-0">Create User</h4>
        </div>

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
            <div class="col-md-4">
              <select name="role" class="form-select" required>
                <option value="">-- Select Role --</option>
                <option value="Admin">Admin</option>
                <option value="Cashier">Cashier</option>
                <option value="Encoder">Encoder</option>
              </select>
            </div>
            <div class="col-md-4"><input type="number" name="or_start" class="form-control" placeholder="OR Start" required></div>
            <div class="col-md-4"><input type="number" name="or_end" class="form-control" placeholder="OR End" required></div>
          </div>

          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-success"><span class="material-icons">save</span> Save</button>
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
                <th>#</th><th>Name</th><th>Username</th><th>Role</th><th>OR Range</th>
                <th style="width:240px;">Usage</th><th>Next Available</th><th>Last OR Used</th><th>Created</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!$users): ?>
              <tr><td colspan="10" class="text-center small-muted">No users yet.</td></tr>
            <?php else: ?>
              <?php foreach ($users as $u):
                $used  = $u['used_or'];
                $total = $u['total_or'];
                $left  = $total - $used;
                $pct   = $total > 0 ? round(($used / $total) * 100) : 0;

                // detect overlap visually
                $conflicts = [];
                foreach ($users as $other) {
                    if ($u['id'] != $other['id']) {
                        if (!($u['or_end'] < $other['or_start'] || $u['or_start'] > $other['or_end'])) {
                            $conflicts[] = htmlspecialchars($other['username']) . " (" . $other['or_start'] . "–" . $other['or_end'] . ")";
                        }
                    }
                }
                $hasConflict = count($conflicts) > 0;
              ?>
              <tr class="<?= $hasConflict ? 'table-danger' : '' ?>">
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td>
                  <?= str_pad($u['or_start'],5,'0',STR_PAD_LEFT) ?> – <?= str_pad($u['or_end'],5,'0',STR_PAD_LEFT) ?>
                  <?php if ($hasConflict): ?>
                    <br><span class="badge bg-danger" data-bs-toggle="tooltip" title="<?= implode(', ', $conflicts) ?>">⚠ Overlap</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="small mb-1"><strong><?= $used ?></strong> used / <strong><?= $left ?></strong> left</div>
                  <div class="progress"><div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div></div>
                </td>
                <td>
                  <?php if ($u['next_available']): ?>
                    <?= str_pad($u['next_available'],5,'0',STR_PAD_LEFT) ?>
                  <?php else: ?>
                    <span class="small-muted">N/A</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($u['last_used']): ?>
                    <?= str_pad($u['last_used'],5,'0',STR_PAD_LEFT) ?>
                  <?php else: ?>
                    <span class="small-muted">None yet</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($u['created_at']) ?></td>
                <td><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $u['id'] ?>">Edit</button></td>
              </tr>

              <!-- Edit Modal -->
              <div class="modal fade" id="editModal<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <form method="post">
                      <input type="hidden" name="edit_user" value="1">
                      <input type="hidden" name="id" value="<?= $u['id'] ?>">
                      <div class="modal-header">
                        <h5 class="modal-title">Edit User — <?= htmlspecialchars($u['username']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-2"><input type="text" name="fname" class="form-control" value="<?= htmlspecialchars($u['first_name']) ?>" required></div>
                        <div class="mb-2"><input type="text" name="lname" class="form-control" value="<?= htmlspecialchars($u['last_name']) ?>" required></div>
                        <div class="mb-2"><input type="text" name="username" class="form-control" value="<?= htmlspecialchars($u['username']) ?>" required></div>
                        <div class="mb-2"><input type="password" name="password" class="form-control" placeholder="Leave blank to keep password"></div>
                        <div class="mb-2">
                          <select name="role" class="form-select" required>
                            <option value="Admin" <?= $u['role']=="Admin"?"selected":"" ?>>Admin</option>
                            <option value="Cashier" <?= $u['role']=="Cashier"?"selected":"" ?>>Cashier</option>
                            <option value="Encoder" <?= $u['role']=="Encoder"?"selected":"" ?>>Encoder</option>
                          </select>
                        </div>
                        <div class="row g-2">
                          <div class="col-md-6"><input type="number" name="or_start" class="form-control" value="<?= $u['or_start'] ?>" required></div>
                          <div class="col-md-6"><input type="number" name="or_end" class="form-control" value="<?= $u['or_end'] ?>" required></div>
                        </div>
                      </div>
                      <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Changes</button></div>
                    </form>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
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
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });
});
</script>
</body>
</html>
