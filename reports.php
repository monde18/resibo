<?php
// reports.php - Reports dashboard (with date range + collector dropdown + fixed By Collector logic)
include 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

function esc($v) { return htmlspecialchars($v); }

// Helper: validate date YYYY-MM-DD
function valid_date($d) {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
}

if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['action'];

    // common archived filter
    $archivedWhere = " (p.archived IS NULL OR p.archived = 0) ";

    // Accept date filters
    $min = isset($_GET['min']) && valid_date($_GET['min']) ? $_GET['min'] : null;
    $max = isset($_GET['max']) && valid_date($_GET['max']) ? $_GET['max'] : null;
    $collector = isset($_GET['collector']) ? intval($_GET['collector']) : 0;

    // ============================================================== //
    if ($action === 'overview') {
        // Today
        $stmt = $conn->prepare("SELECT IFNULL(SUM(total),0) FROM payments WHERE date = CURDATE() AND (archived IS NULL OR archived = 0)");
        $stmt->execute(); $stmt->bind_result($today); $stmt->fetch(); $stmt->close();

        // Month
        $stmt = $conn->prepare("SELECT IFNULL(SUM(total),0) FROM payments WHERE YEAR(date)=YEAR(CURDATE()) AND MONTH(date)=MONTH(CURDATE()) AND (archived IS NULL OR archived = 0)");
        $stmt->execute(); $stmt->bind_result($month); $stmt->fetch(); $stmt->close();

        // YTD
        $stmt = $conn->prepare("SELECT IFNULL(SUM(total),0) FROM payments WHERE YEAR(date)=YEAR(CURDATE()) AND (archived IS NULL OR archived = 0)");
        $stmt->execute(); $stmt->bind_result($ytd); $stmt->fetch(); $stmt->close();

        // Receipts this month
        $stmt = $conn->prepare("SELECT COUNT(*) FROM payments WHERE YEAR(date)=YEAR(CURDATE()) AND MONTH(date)=MONTH(CURDATE()) AND (archived IS NULL OR archived = 0)");
        $stmt->execute(); $stmt->bind_result($receipts); $stmt->fetch(); $stmt->close();

        echo json_encode(['success'=>true,'data'=>[
            'today'=> (float)$today,
            'month'=> (float)$month,
            'ytd'  => (float)$ytd,
            'receipts_month' => (int)$receipts
        ]]);
        exit;
    }

    // ============================================================== //
    // By fee (top codes) - accepts min & max
    if ($action === 'by_fee') {
        $dateCond = "";
        if ($min) $dateCond .= " AND date >= '".$conn->real_escape_string($min)."' ";
        if ($max) $dateCond .= " AND date <= '".$conn->real_escape_string($max)."' ";

        $sql = "
            SELECT code, SUM(amount) AS total_amount FROM (
                SELECT code1 AS code, amount1 AS amount, date FROM payments WHERE amount1 IS NOT NULL $dateCond
                UNION ALL
                SELECT code2 AS code, amount2 AS amount, date FROM payments WHERE amount2 IS NOT NULL $dateCond
                UNION ALL
                SELECT code3 AS code, amount3 AS amount, date FROM payments WHERE amount3 IS NOT NULL $dateCond
                UNION ALL
                SELECT code4 AS code, amount4 AS amount, date FROM payments WHERE amount4 IS NOT NULL $dateCond
                UNION ALL
                SELECT code5 AS code, amount5 AS amount, date FROM payments WHERE amount5 IS NOT NULL $dateCond
                UNION ALL
                SELECT code6 AS code, amount6 AS amount, date FROM payments WHERE amount6 IS NOT NULL $dateCond
                UNION ALL
                SELECT code7 AS code, amount7 AS amount, date FROM payments WHERE amount7 IS NOT NULL $dateCond
                UNION ALL
                SELECT code8 AS code, amount8 AS amount, date FROM payments WHERE amount8 IS NOT NULL $dateCond
            ) AS t
            WHERE code IS NOT NULL AND TRIM(code) <> ''
            GROUP BY code
            ORDER BY total_amount DESC
            LIMIT 50
        ";
        $res = $conn->query($sql);
        $out = [];
        while ($r = $res->fetch_assoc()) $out[] = ['code'=>$r['code'],'total'=>(float)$r['total_amount']];
        echo json_encode(['success'=>true,'data'=>$out]);
        exit;
    }

    // ============================================================== //
    // Daily totals
    if ($action === 'daily') {
        $where = " WHERE (archived IS NULL OR archived = 0) ";
        if ($min) $where .= " AND date >= '".$conn->real_escape_string($min)."' ";
        if ($max) $where .= " AND date <= '".$conn->real_escape_string($max)."' ";

        $sql = "SELECT date, IFNULL(SUM(total),0) as total FROM payments $where GROUP BY date ORDER BY date ASC";
        $res = $conn->query($sql);
        $labels = []; $values = [];
        while ($r = $res->fetch_assoc()) {
            $labels[] = $r['date'];
            $values[] = (float)$r['total'];
        }
        echo json_encode(['success'=>true,'labels'=>$labels,'values'=>$values]);
        exit;
    }

    // ============================================================== //
    // Monthly totals
    if ($action === 'monthly') {
        $where = " WHERE (archived IS NULL OR archived = 0) ";
        if ($min) $where .= " AND date >= '".$conn->real_escape_string($min)."' ";
        if ($max) $where .= " AND date <= '".$conn->real_escape_string($max)."' ";

        $sql = "SELECT YEAR(date) AS y, MONTH(date) AS m, IFNULL(SUM(total),0) AS total FROM payments $where GROUP BY YEAR(date), MONTH(date)";
        $res = $conn->query($sql);

        $map = [];
        while ($r = $res->fetch_assoc()) {
            $key = $r['y'].'-'.str_pad($r['m'],2,'0',STR_PAD_LEFT);
            $map[$key] = (float)$r['total'];
        }

        $labels = [];
        $values = [];
        if ($min && $max) {
            $start = new DateTime($min);
            $end = new DateTime($max);
            $end->modify('+1 day');
            $interval = new DateInterval('P1M');
            $period = new DatePeriod($start, $interval, $end);
            foreach ($period as $dt) {
                $labels[] = $dt->format('Y-M');
                $key = $dt->format('Y-m');
                $values[] = isset($map[$key]) ? $map[$key] : 0;
            }
        } else {
            $year = date('Y');
            for ($i=1;$i<=12;$i++){
                $labels[] = date('M', mktime(0,0,0,$i,1)) . " {$year}";
                $key = $year . '-' . str_pad($i,2,'0',STR_PAD_LEFT);
                $values[] = isset($map[$key]) ? $map[$key] : 0;
            }
        }

        echo json_encode(['success'=>true,'labels'=>$labels,'values'=>$values]);
        exit;
    }

    // ============================================================== //
    // Collectors list for dropdown
    if ($action === 'collectors') {
        $out = [];
        $sql = "SELECT id, CONCAT(first_name,' ',last_name) AS name 
                FROM users 
                WHERE role IN ('Encoder','Cashier')
                ORDER BY name";
        $res = $conn->query($sql);
        while ($r = $res->fetch_assoc()) {
            $out[] = ['id' => (int)$r['id'], 'name' => $r['name']];
        }
        echo json_encode(['success'=>true,'data'=>$out]);
        exit;
    }

    // ============================================================== //
    // By collector aggregation
    if ($action === 'by_collector') {
        $dateCond = "";
        if ($min) $dateCond .= " AND p.date >= '".$conn->real_escape_string($min)."' ";
        if ($max) $dateCond .= " AND p.date <= '".$conn->real_escape_string($max)."' ";

        $sql = "
          SELECT u.id AS user_id, CONCAT(u.first_name,' ',u.last_name) AS name,
                 IFNULL(SUM(p.total),0) AS total_collected, COUNT(p.id) AS receipts_count
          FROM users u
          LEFT JOIN payments p ON u.id = p.user_id
               AND (p.archived IS NULL OR p.archived = 0)
               $dateCond
          WHERE u.role IN ('Encoder','Cashier')
        ";
        if ($collector > 0) {
            $sql .= " AND u.id = " . intval($collector);
        }
        $sql .= " GROUP BY u.id ORDER BY total_collected DESC LIMIT 200";

        $res = $conn->query($sql);
        if (!$res) {
            echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
            exit;
        }
        $out = [];
        while ($r = $res->fetch_assoc()) {
            $out[] = [
                'user_id' => (int)$r['user_id'],
                'name'    => $r['name'],
                'total'   => (float)$r['total_collected'],
                'count'   => (int)$r['receipts_count']
            ];
        }
        echo json_encode(['success'=>true,'data'=>$out]);
        exit;
    }

    // ============================================================== //
    // Detailed table
    if ($action === 'table') {
        $where = " WHERE (archived IS NULL OR archived = 0) ";
        if ($min) $where .= " AND date >= '".$conn->real_escape_string($min)."' ";
        if ($max) $where .= " AND date <= '".$conn->real_escape_string($max)."' ";

        $sql = "SELECT * FROM payments $where ORDER BY date DESC LIMIT 2000";
        $res = $conn->query($sql);
        if (!$res) { echo json_encode(['success'=>false,'message'=>$conn->error]); exit; }

        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $payments = [];
            for ($i=1;$i<=8;$i++) {
                if (!empty($r["code$i"]) || !empty($r["account_name$i"])) {
                    $payments[] = ['code'=>$r["code$i"], 'account'=>$r["account_name$i"], 'amount'=>(float)$r["amount$i"]];
                }
            }
            $rows[] = [
                'id'=>(int)$r['id'],
                'date'=>$r['date'],
                'payee'=>$r['payee'],
                'reference_no'=>$r['reference_no'],
                'payments'=>$payments,
                'total'=>(float)$r['total'],
                'cash_received'=>(float)$r['cash_received'],
                'change'=>(float)$r['change_amount']
            ];
        }
        echo json_encode(['success'=>true,'data'=>$rows]);
        exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action']);
    exit;
}

// ============================================================== //
// No action -> render HTML page
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Reports — Collections Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables -->
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    body { background:#f9fafb; }
    .card-quick { border-radius:12px; }
    .small-muted { font-size:.85rem; color:#6b7280; }
    .chart-container { min-height:320px; }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4><span class="material-icons">bar_chart</span> Reports & Collections</h4>
    <div>
      <button id="refreshBtn" class="btn btn-outline-secondary btn-sm"><span class="material-icons">refresh</span> Refresh</button>
    </div>
  </div>

  <!-- Filters: Date Range + Collector -->
  <div class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label">Date From</label>
        <input type="date" id="minDate" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Date To</label>
        <input type="date" id="maxDate" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">Collector</label>
        <select id="collectorSelect" class="form-select">
          <option value="">— All Collectors —</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button id="applyFilters" class="btn btn-primary w-100">Apply</button>
        <button id="clearFilters" class="btn btn-outline-secondary w-100">Clear</button>
      </div>
    </div>
  </div>

  <!-- Overview cards -->
  <div class="row g-3 mb-4" id="overviewCards">
    <div class="col-md-3">
      <div class="card card-quick p-3">
        <div class="small-muted">Today</div>
        <div class="h5" id="cardToday">₱0.00</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-quick p-3">
        <div class="small-muted">This Month</div>
        <div class="h5" id="cardMonth">₱0.00</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-quick p-3">
        <div class="small-muted">Year to Date</div>
        <div class="h5" id="cardYTD">₱0.00</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-quick p-3">
        <div class="small-muted">Receipts (This month)</div>
        <div class="h5" id="cardReceipts">0</div>
      </div>
    </div>
  </div>

  <!-- Charts row -->
  <div class="row mb-4">
    <div class="col-lg-6 mb-3">
      <div class="card p-3">
        <h6>Daily Collections</h6>
        <div class="chart-container">
          <canvas id="dailyChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-6 mb-3">
      <div class="card p-3 mb-3">
        <h6>Monthly Collections</h6>
        <div class="chart-container">
          <canvas id="monthlyChart"></canvas>
        </div>
      </div>

      <div class="card p-3">
        <h6>Collection by Fee / Code (Top items)</h6>
        <div class="chart-container">
          <canvas id="feeChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- By Collector -->
  <div class="card mb-4 p-3">
    <h6>By Collector</h6>
    <div class="table-responsive">
      <table class="table table-sm" id="collectorTable">
        <thead><tr><th>Collector</th><th>Receipts</th><th>Total Collected</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <!-- Detailed table -->
  <div class="card p-3">
    <div class="d-flex justify-content-between mb-2">
      <h6>Detailed Payments</h6>
      <div>
        <button id="exportCsv" class="btn btn-sm btn-outline-secondary">Export CSV</button>
      </div>
    </div>
    <div class="table-responsive">
      <table id="paymentsTable" class="table table-striped nowrap" style="width:100%">
        <thead>
          <tr>
            <th>Date</th><th>Payee</th><th>Reference</th><th>Payments</th><th>Total</th><th>Cash</th><th>Change</th><th>Action</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
function fmtMoney(n){
  return "₱ " + (Number(n) || 0).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2});
}

let dailyChart, monthlyChart, feeChart, paymentsTable;

function initCharts(){
  const dctx = document.getElementById('dailyChart');
  const mctx = document.getElementById('monthlyChart');
  const fctx = document.getElementById('feeChart');

  dailyChart = new Chart(dctx, {
    type: 'bar',
    data: { labels: [], datasets: [{ label: 'Daily', data: [], backgroundColor: 'rgba(13,110,253,0.7)' }] },
    options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
  });

  monthlyChart = new Chart(mctx, {
    type: 'line',
    data: { labels: [], datasets: [{ label: 'Monthly', data: [], fill:true, tension:0.3 }] },
    options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
  });

  feeChart = new Chart(fctx, {
    type: 'pie',
    data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
    options: { responsive:true, plugins:{legend:{position:'right'}} }
  });
}

function getFiltersQuery() {
  const min = $('#minDate').val();
  const max = $('#maxDate').val();
  const collector = $('#collectorSelect').val() || '';
  let q = '';
  if (min) q += '&min=' + encodeURIComponent(min);
  if (max) q += '&max=' + encodeURIComponent(max);
  if (collector) q += '&collector=' + encodeURIComponent(collector);
  return q ? q : '';
}

function loadOverview(){
  $.getJSON('reports.php?action=overview', function(res){
    if (res.success) {
      $('#cardToday').text(fmtMoney(res.data.today));
      $('#cardMonth').text(fmtMoney(res.data.month));
      $('#cardYTD').text(fmtMoney(res.data.ytd));
      $('#cardReceipts').text(res.data.receipts_month);
    }
  });
}

function loadDaily(){
  const q = getFiltersQuery();
  $.getJSON('reports.php?action=daily' + q, function(res){
    if (res.success){
      dailyChart.data.labels = res.labels;
      dailyChart.data.datasets[0].data = res.values;
      dailyChart.update();
    }
  });
}

function loadMonthly(){
  const q = getFiltersQuery();
  $.getJSON('reports.php?action=monthly' + q, function(res){
    if (res.success){
      monthlyChart.data.labels = res.labels;
      monthlyChart.data.datasets[0].data = res.values;
      monthlyChart.update();
    }
  });
}

function loadFee(){
  const q = getFiltersQuery();
  $.getJSON('reports.php?action=by_fee' + q, function(res){
    if (res.success){
      const labels = res.data.map(r => r.code);
      const values = res.data.map(r => r.total);
      feeChart.data.labels = labels;
      feeChart.data.datasets[0].data = values;
      feeChart.data.datasets[0].backgroundColor = labels.map(() => randomColor(0.8));
      feeChart.update();
    }
  });
}

function randomColor(alpha=0.8){
  return 'rgba(' + Math.floor(Math.random()*200) + ',' + Math.floor(Math.random()*200) + ',' + Math.floor(Math.random()*200) + ',' + alpha + ')';
}

function loadCollectorsDropdown(){
  $.getJSON('reports.php?action=collectors', function(res){
    if (res.success){
      const sel = $('#collectorSelect');
      sel.empty();
      sel.append('<option value="">— All Collectors —</option>');
      res.data.forEach(c => {
        sel.append(`<option value="${c.id}">${escapeHtml(c.name)}</option>`);
      });
    }
  });
}

function loadCollectorsTable(){
  const q = getFiltersQuery();
  $.getJSON('reports.php?action=by_collector' + q, function(res){
    if (res.success){
      const tbody = $('#collectorTable tbody').empty();
      res.data.forEach(row => {
        tbody.append(`<tr>
          <td>${escapeHtml(row.name)}</td>
          <td>${row.count}</td>
          <td>${fmtMoney(row.total)}</td>
        </tr>`);
      });
    }
  });
}

function loadPaymentsTable(){
  const q = getFiltersQuery();
  $.getJSON('reports.php?action=table' + q, function(res){
    if (res.success){
      if (paymentsTable && $.fn.DataTable.isDataTable('#paymentsTable')) {
        paymentsTable.clear().rows.add(res.data).draw();
        return;
      }
      paymentsTable = $('#paymentsTable').DataTable({
        data: res.data,
        columns: [
          { data: 'date' },
          { data: 'payee', render: d => escapeHtml(d) },
          { data: 'reference_no' },
          { data: 'payments', render: function(p){
              if (!p || !p.length) return '';
              return p.map(x => `<div style="white-space:nowrap">${escapeHtml(x.code)} — ${escapeHtml(x.account)} (₱${Number(x.amount).toFixed(2)})</div>`).join('');
            }
          },
          { data: 'total', render: d => fmtMoney(d) },
          { data: 'cash_received', render: d => fmtMoney(d) },
          { data: 'change', render: d => fmtMoney(d) },
          { data: 'id', orderable:false, render: function(id){
              return `<a class="btn btn-sm btn-outline-primary" href="edit.php?id=${id}">Edit</a> <a class="btn btn-sm btn-outline-danger" href="delete.php?id=${id}" onclick="return confirm('Delete?')">Delete</a>`;
          } }
        ],
        order: [[0,'desc']],
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
          { extend: 'excel', text: 'Excel' },
          { extend: 'csv', text: 'CSV' },
          { extend: 'print', text: 'Print' }
        ],
        pageLength: 25
      });
    } else {
      alert('Error loading payments: ' + res.message);
    }
  });
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/[&<>"'`=\/]/g, function(s){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'})[s]; });
}

$(function(){
  initCharts();
  loadCollectorsDropdown();
  loadOverview();
  loadDaily();
  loadMonthly();
  loadFee();
  loadCollectorsTable();
  loadPaymentsTable();

  $('#applyFilters').on('click', function(){
    loadOverview();
    loadDaily();
    loadMonthly();
    loadFee();
    loadCollectorsTable();
    loadPaymentsTable();
  });

  $('#clearFilters').on('click', function(){
    $('#minDate,#maxDate').val('');
    $('#collectorSelect').val('');
    $('#applyFilters').trigger('click');
  });

  $('#refreshBtn').on('click', function(){
    $('#applyFilters').trigger('click');
  });

  $('#exportCsv').on('click', function(){
    if (paymentsTable) paymentsTable.button('.buttons-csv').trigger();
  });
});
</script>
</body>
</html>
