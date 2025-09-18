<?php
include 'config.php';
session_start();

// Total payments
$res = $conn->query("SELECT COUNT(*) as cnt, SUM(total) as sum FROM payments");
$totalPayments = $res->fetch_assoc();

// Today
$res = $conn->query("SELECT COUNT(*) as cnt, SUM(total) as sum FROM payments WHERE DATE(date)=CURDATE()");
$today = $res->fetch_assoc();

// This month
$res = $conn->query("SELECT COUNT(*) as cnt, SUM(total) as sum FROM payments WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())");
$thisMonth = $res->fetch_assoc();

// Highest single payment
$res = $conn->query("SELECT payee,total,date FROM payments ORDER BY total DESC LIMIT 1");
$highest = $res->fetch_assoc();

// Monthly totals
$res = $conn->query("SELECT DATE_FORMAT(date, '%Y-%m') as month, SUM(total) as sum FROM payments GROUP BY month ORDER BY month");
$monthlyData = [];
while ($r=$res->fetch_assoc()){ $monthlyData[$r['month']]=$r['sum']; }

// Payments by payee
$res = $conn->query("SELECT payee, SUM(total) as sum FROM payments GROUP BY payee ORDER BY sum DESC LIMIT 6");
$payeeData = [];
while ($r=$res->fetch_assoc()){ $payeeData[$r['payee']]=$r['sum']; }

// Payments by code
$codeData = [];
$res = $conn->query("SELECT code1 as code, SUM(amount1) as sum FROM payments GROUP BY code1");
while ($r=$res->fetch_assoc()){ if($r['code']) $codeData[$r['code']]=$r['sum']; }

// Recent 5
$recent = $conn->query("SELECT date,payee,reference_no,total FROM payments ORDER BY date DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
     <?php include 'navbar.php'; ?> 
<div class="container mt-4">
  <h2 class="mb-4">📊 Payments Dashboard</h2>

  <!-- Summary Cards -->
  <div class="row mb-4">
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center">
      <h6>Total Payments</h6><h4><?= number_format($totalPayments['cnt']) ?></h4><p>₱<?= number_format($totalPayments['sum'],2) ?></p>
    </div></div></div>

    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center">
      <h6>Today</h6><h4><?= number_format($today['cnt']) ?></h4><p>₱<?= number_format($today['sum'] ?? 0,2) ?></p>
    </div></div></div>

    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center">
      <h6>This Month</h6><h4><?= number_format($thisMonth['cnt']) ?></h4><p>₱<?= number_format($thisMonth['sum'] ?? 0,2) ?></p>
    </div></div></div>

    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center">
      <h6>Highest Payment</h6>
      <h4>₱<?= number_format($highest['total'],2) ?></h4>
      <p><?= htmlspecialchars($highest['payee']) ?> (<?= $highest['date'] ?>)</p>
