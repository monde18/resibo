<?php
// activity_logs.php
// Requires: config.php which provides $conn (mysqli) and session handling as in your original file.
// Place this file alongside your existing config.php.

include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Restrict to admin only (keeps your original logic)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo "<div style='padding:20px;font-family:sans-serif;color:red;'>
            ❌ Access Denied. Admins only.
          </div>";
    exit;
}

// ---------- Helper functions ----------
function esc($v) { return htmlspecialchars($v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

// Validate YYYY-MM-DD
function valid_date($d) {
    if (!$d) return false;
    $parts = explode('-', $d);
    if (count($parts) !== 3) return false;
    return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
}

// Allowed sortable columns mapping (frontend label => actual db column)
$allowedSort = [
    'created_at' => 'created_at',
    'username'   => 'username',
    'action'     => 'action',
    'ip_address' => 'ip_address'
];

// Read & sanitize GET params
$page      = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page  = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
$per_page  = in_array($per_page, [10,20,50,100]) ? $per_page : 20;

$sort_by   = isset($_GET['sort_by']) && array_key_exists($_GET['sort_by'], $allowedSort) ? $_GET['sort_by'] : 'created_at';
$sort_dir  = (isset($_GET['sort_dir']) && strtoupper($_GET['sort_dir']) === 'ASC') ? 'ASC' : 'DESC';

$filter_user   = isset($_GET['user']) ? trim($_GET['user']) : '';
$filter_action = isset($_GET['action']) ? trim($_GET['action']) : '';
$from_date     = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date       = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$export_csv    = isset($_GET['export']) && $_GET['export'] === 'csv';

// Build WHERE clauses (escaped)
$where = [];
if ($filter_user !== '') {
    $where[] = "username = '" . $conn->real_escape_string($filter_user) . "'";
}
if ($filter_action !== '') {
    $where[] = "action = '" . $conn->real_escape_string($filter_action) . "'";
}
if (valid_date($from_date)) {
    $where[] = "DATE(created_at) >= '" . $conn->real_escape_string($from_date) . "'";
}
if (valid_date($to_date)) {
    $where[] = "DATE(created_at) <= '" . $conn->real_escape_string($to_date) . "'";
}
if ($search !== '') {
    $s = $conn->real_escape_string($search);
    $where[] = "(username LIKE '%$s%' OR action LIKE '%$s%' OR details LIKE '%$s%')";
}

$where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ---------- Pagination - get total ----------
$count_sql = "SELECT COUNT(*) AS total FROM activity_logs $where_sql";
$count_res = $conn->query($count_sql);
$total_rows = 0;
if ($count_res) {
    $r = $count_res->fetch_assoc();
    $total_rows = (int)$r['total'];
}
$total_pages = max(1, (int)ceil($total_rows / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

// ---------- Export to CSV (if requested) ----------
if ($export_csv) {
    // fetch all matching rows (no pagination) for CSV
    $sql = "SELECT created_at, username, action, details, ip_address, user_agent
            FROM activity_logs
            $where_sql
            ORDER BY " . $allowedSort[$sort_by] . " $sort_dir";
    $res = $conn->query($sql);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="activity_logs_export_' . date('Ymd_His') . '.csv"');

    $out = fopen('php://output', 'w');
    // BOM for Excel UTF-8
    echo "\xEF\xBB\xBF";
    fputcsv($out, ['Date','Username','Action','Details','IP Address','User Agent']);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            fputcsv($out, [
                $row['created_at'],
                $row['username'],
                $row['action'],
                $row['details'],
                $row['ip_address'],
                $row['user_agent']
            ]);
        }
    }
    fclose($out);
    exit;
}

// ---------- Fetch unique users & actions for filter dropdowns (small lists) ----------
$users = [];
$actions = [];
$uRes = $conn->query("SELECT DISTINCT username FROM activity_logs ORDER BY username LIMIT 200");
if ($uRes) {
    while ($r = $uRes->fetch_assoc()) $users[] = $r['username'];
}
$aRes = $conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action LIMIT 200");
if ($aRes) {
    while ($r = $aRes->fetch_assoc()) $actions[] = $r['action'];
}

// ---------- Main fetch with sorting & pagination ----------
$sql = "SELECT id, created_at, username, action, details, ip_address, user_agent
        FROM activity_logs
        $where_sql
        ORDER BY " . $allowedSort[$sort_by] . " $sort_dir
        LIMIT $offset, $per_page";

$result = $conn->query($sql);
if (!$result) {
    // graceful fallback
    $result = null;
}

// Utility to build query string preserving filters but changing some params
function qs(array $overrides = []) {
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) unset($params[$k]);
        else $params[$k] = $v;
    }
    return http_build_query($params);
}

// map action to badge classes
function action_badge($action) {
    $a = strtolower($action);
    if (strpos($a,'login') !== false) return 'badge bg-success';
    if (strpos($a,'fail') !== false || strpos($a,'error') !== false) return 'badge bg-danger';
    if (strpos($a,'delete') !== false) return 'badge bg-warning text-dark';
    return 'badge bg-primary';
}

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
    th { background: #f1f5f9; cursor: pointer; user-select: none; }
    .small-muted { font-size: 0.85rem; color: #6b7280; }
    .nowrap { white-space: nowrap; }
    .filter-row .form-control, .filter-row .form-select { min-height: 40px; }
  </style>
</head>
<body class="p-4">

<div class="container">
  <div class="card p-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <h3 class="mb-0">📜 Activity Logs</h3>
        <div class="small-muted">Showing administrative activity history</div>
      </div>
      <div class="text-end">
        <a href="?<?php echo qs(['export'=>'csv']); ?>" class="btn btn-outline-secondary btn-sm">Export CSV</a>
      </div>
    </div>

    <!-- Filters -->
    <form method="get" class="row g-2 mb-3 filter-row">
      <div class="col-md-3">
        <label class="form-label small-muted">User</label>
        <select name="user" class="form-select form-select-sm">
          <option value="">All users</option>
<?php foreach ($users as $u): ?>
          <option value="<?php echo esc($u); ?>" <?php if ($filter_user === $u) echo 'selected'; ?>><?php echo esc($u); ?></option>
<?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small-muted">Action</label>
        <select name="action" class="form-select form-select-sm">
          <option value="">All actions</option>
<?php foreach ($actions as $a): ?>
          <option value="<?php echo esc($a); ?>" <?php if ($filter_action === $a) echo 'selected'; ?>><?php echo esc($a); ?></option>
<?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small-muted">From</label>
        <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo esc($from_date); ?>">
      </div>

      <div class="col-md-2">
        <label class="form-label small-muted">To</label>
        <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo esc($to_date); ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label small-muted">Search</label>
        <div class="input-group input-group-sm">
          <input type="text" name="search" class="form-control" placeholder="username, action, details..." value="<?php echo esc($search); ?>">
          <button class="btn btn-primary btn-sm" type="submit">Filter</button>
          <a class="btn btn-outline-secondary btn-sm" href="activity_logs.php" title="Reset">Reset</a>
        </div>
      </div>

      <div class="col-12 mt-1 d-flex justify-content-between align-items-center">
        <div class="small-muted">
          <strong><?php echo number_format($total_rows); ?></strong> results
          &nbsp;•&nbsp; Page <?php echo $page; ?> of <?php echo $total_pages; ?>
        </div>

        <div class="d-flex gap-2 align-items-center">
          <label class="small-muted mb-0 me-1">Per page</label>
          <select name="per_page" onchange="this.form.submit()" class="form-select form-select-sm" style="width:85px;">
            <option value="10" <?php if ($per_page==10) echo 'selected'; ?>>10</option>
            <option value="20" <?php if ($per_page==20) echo 'selected'; ?>>20</option>
            <option value="50" <?php if ($per_page==50) echo 'selected'; ?>>50</option>
            <option value="100" <?php if ($per_page==100) echo 'selected'; ?>>100</option>
          </select>
        </div>
      </div>
    </form>

    <!-- Table -->
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead>
          <tr>
            <?php
            // table headers with sorting links
            $cols = [
                'created_at' => 'Date',
                'username'   => 'User',
                'action'     => 'Action',
                'details'    => 'Details',
                'ip_address' => 'IP Address',
                'device'     => 'Device'
            ];
            foreach ($cols as $colKey => $label) {
                // clickable sort only for columns in allowedSort
                if ($colKey === 'details' || $colKey === 'device') {
                    echo "<th>$label</th>";
                } else {
                    // compute toggled direction
                    $dir = ($sort_by === $colKey && $sort_dir === 'ASC') ? 'DESC' : 'ASC';
                    $arrow = '';
                    if ($sort_by === $colKey) $arrow = $sort_dir === 'ASC' ? ' ▲' : ' ▼';
                    $link = '?' . qs(['sort_by'=>$colKey, 'sort_dir'=>$dir, 'page'=>1]);
                    echo "<th><a href=\"$link\" class=\"text-decoration-none text-dark\">$label $arrow</a></th>";
                }
            }
            ?>
          </tr>
        </thead>
        <tbody>
<?php if ($result && $result->num_rows): ?>
  <?php while ($log = $result->fetch_assoc()): ?>
          <tr>
            <td class="nowrap"><?php echo esc($log['created_at']); ?></td>
            <td><?php echo esc($log['username']); ?></td>
            <td><span class="<?php echo action_badge($log['action']); ?>"><?php echo esc($log['action']); ?></span></td>
            <td style="max-width:420px; overflow:hidden; text-overflow:ellipsis;"><?php echo esc($log['details']); ?></td>
            <td class="nowrap"><?php echo esc($log['ip_address']); ?></td>
            <td>
              <span title="<?php echo esc($log['user_agent']); ?>">
                <?php
                  $ua = $log['user_agent'];
                  $short = (strlen($ua) > 60) ? substr($ua,0,60) . '...' : $ua;
                  echo esc($short);
                ?>
              </span>
            </td>
          </tr>
  <?php endwhile; ?>
<?php else: ?>
          <tr>
            <td colspan="6" class="text-center small-muted py-4">No logs found for the selected filters.</td>
          </tr>
<?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav aria-label="Logs pagination" class="mt-3">
      <ul class="pagination justify-content-center mb-0">
        <?php
        $start = max(1, $page - 3);
        $end = min($total_pages, $page + 3);

        // Prev
        $prevClass = $page <= 1 ? 'disabled' : '';
        $prevLink = $page <= 1 ? '#' : '?' . qs(['page'=>$page-1]);
        echo "<li class='page-item $prevClass'><a class='page-link' href='$prevLink' tabindex='-1'>Prev</a></li>";

        if ($start > 1) {
            echo "<li class='page-item'><a class='page-link' href='?" . qs(['page'=>1]) . "'>1</a></li>";
            if ($start > 2) echo "<li class='page-item disabled'><span class='page-link'>…</span></li>";
        }

        for ($i=$start; $i<=$end; $i++) {
            $active = $i === $page ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='?" . qs(['page'=>$i]) . "'>$i</a></li>";
        }

        if ($end < $total_pages) {
            if ($end < $total_pages - 1) echo "<li class='page-item disabled'><span class='page-link'>…</span></li>";
            echo "<li class='page-item'><a class='page-link' href='?" . qs(['page'=>$total_pages]) . "'>$total_pages</a></li>";
        }

        // Next
        $nextClass = $page >= $total_pages ? 'disabled' : '';
        $nextLink = $page >= $total_pages ? '#' : '?' . qs(['page'=>$page+1]);
        echo "<li class='page-item $nextClass'><a class='page-link' href='$nextLink'>Next</a></li>";
        ?>
      </ul>
    </nav>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
