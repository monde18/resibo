<?php
include 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } else {
        // ✅ fetch first_name and last_name as well
        $stmt = $conn->prepare("SELECT id, first_name, last_name, username, password, role, or_start, or_end 
                        FROM users WHERE username=? LIMIT 1");

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // ✅ Save everything into session
                $_SESSION['user_id']    = (int)$user['id'];
                $_SESSION['username']   = $user['username'];
                $_SESSION['role']       = $user['role'];
                $_SESSION['or_start']   = (int)$user['or_start'];
                $_SESSION['or_end']     = (int)$user['or_end'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name']  = $user['last_name'];

                // Redirect to homepage
                header("Location: index.php");
                exit;
            } else {
                $error = '❌ Invalid password. Try again.';
            }
        } else {
            $error = '❌ Username not found.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f6f9fc; }
    .card { border-radius: 12px; box-shadow: 0 6px 18px rgba(16,24,40,0.06); }
  </style>
</head>
<body>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-4">
      <div class="card p-4">
        <h4 class="mb-3 text-center">🔐 User Login</h4>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-success w-100">Login</button>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
