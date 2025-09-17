<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
      <span class="material-icons me-1 text-primary">account_balance</span> Payment System
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto nav-tabs">
                <li class="nav-item">
          <a class="nav-link <?php if($current_page=='dashboard.php') echo 'active'; ?>" href="dashboard.php">
            <span class="material-icons">home</span> Home
          </a>
        </li>
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
            <span class="material-icons">table_view</span> Add fees
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
      </ul>
    </div>
  </div>
</nav>

<style>
/* Bootstrap nav-tabs already gives the cut-out look */
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