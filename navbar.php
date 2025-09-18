<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);

// Get user info from session
$user_first = $_SESSION['first_name'] ?? 'Guest';
$user_last = $_SESSION['last_name'] ?? '';
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
        <img src="assests/logo.png" alt="Logo" style="height:52px; width:52px; margin-right:8px;">
     E-RECEIPTS
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <!-- Left Nav -->
      <ul class="navbar-nav ms-auto nav-tabs">
        <li class="nav-item">
          <a class="nav-link <?php if($current_page=='dashboard.php') echo 'active'; ?>" href="dashboard.php">
            <span class="material-icons">home</span> Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if($current_page=='index.php') echo 'active'; ?>" href="index.php">
            <span class="material-icons">add</span> New
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if($current_page=='records.php') echo 'active'; ?>" href="records.php">
            <span class="material-icons">table_view</span> Records
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if($current_page=='fees1.php') echo 'active'; ?>" href="fees1.php">
            <span class="material-icons">money</span> Fees
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if($current_page=='archived_records.php') echo 'active'; ?>" href="archived_records.php">
            <span class="material-icons">archive</span> Archive
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if($current_page=='reports.php') echo 'active'; ?>" href="reports.php">
            <span class="material-icons">bar_chart</span> Reports
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php if($current_page=='users.php') echo 'active'; ?>" href="users.php">
            <span class="material-icons">group</span> Users
          </a>
        </li>
      </ul>

      <!-- Right User Dropdown -->
      <ul class="navbar-nav ms-3">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown"
             role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                 style="width:35px; height:35px;">
              <?= strtoupper(substr($user_first,0,1)) ?>
            </div>
            <span class="ms-2"><?= htmlspecialchars($user_first . ' ' . $user_last) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="change_password.php">Change Password</a></li>
            <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<style>
/* Retain your tab look */
.nav-tabs .nav-link {
  border: none;
  border-top-left-radius: .5rem;
  border-top-right-radius: .5rem;
  color: #555;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 4px;
}
.nav-tabs .nav-link:hover {
  color: #0d6efd;
  background: rgba(13,110,253,.05);
}
.nav-tabs .nav-link.active {
  color: #0d6efd;
  background: #fff;
  border: 1px solid #dee2e6;
  border-bottom: 2px solid #fff; /* creates the cut-out effect */
  box-shadow: 0 -2px 6px rgba(0,0,0,.05);
}
</style>
