<?php
include 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("❌ Invalid request.");
}

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    die("❌ Invalid ID.");
}

$date   = htmlspecialchars(trim($_POST['date'] ?? ''));
$payee  = htmlspecialchars(trim($_POST['payee'] ?? ''));
$refno  = intval($_POST['refno'] ?? 0);

$codes     = $_POST['code'] ?? [];
$acctnames = $_POST['acctname'] ?? [];
$amounts   = $_POST['amount'] ?? [];

if (!$date || !$payee || !$refno) {
    die("❌ Date, Payee, and Reference No. are required.");
}

// ✅ load fees for auto-fill constants
$feesLookup = [];
$res = $conn->query("SELECT code, constant_value FROM fees");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        if ($row['constant_value'] !== null && $row['constant_value'] !== '') {
            $feesLookup[$row['code']] = $row['constant_value'];
        }
    }
}

$codesArr = [];
$acctsArr = [];
$amtsArr  = [];

for ($i=0; $i<8; $i++) {
    $code  = htmlspecialchars(trim($codes[$i] ?? ''));
    $acct  = htmlspecialchars(trim($acctnames[$i] ?? ''));
    $rawAmt = $amounts[$i] ?? '';
    $amt   = str_replace(',', '', $rawAmt);

    // skip empty rows
    if ($code === '' && $acct === '' && $amt === '') {
        $codesArr[$i] = null;
        $acctsArr[$i] = null;
        $amtsArr[$i]  = null;
        continue;
    }

    $amt = is_numeric($amt) ? number_format((float)$amt, 2, '.', '') : null;

    // auto-fill constant values
    if ($code && strtoupper($code) !== 'OTHER') {
        if (isset($feesLookup[$code]) && $feesLookup[$code] !== '') {
            $amt = $feesLookup[$code];
        }
    }

    $codesArr[$i] = $code ?: null;
    $acctsArr[$i] = $acct ?: null;
    $amtsArr[$i]  = $amt;
}

// ✅ compute total
$total = 0.0;
foreach ($amtsArr as $a) {
    if ($a !== null && is_numeric($a)) {
        $total += (float)$a;
    }
}
$total = number_format($total, 2, '.', '');

// ✅ cash and change
$cash_received = floatval($_POST['cash_received'] ?? 0);
$change_amount = $cash_received - floatval($total);

$sql = "
    UPDATE payments SET
        `date`=?, payee=?, reference_no=?,
        code1=?, account_name1=?, amount1=?,
        code2=?, account_name2=?, amount2=?,
        code3=?, account_name3=?, amount3=?,
        code4=?, account_name4=?, amount4=?,
        code5=?, account_name5=?, amount5=?,
        code6=?, account_name6=?, amount6=?,
        code7=?, account_name7=?, amount7=?,
        code8=?, account_name8=?, amount8=?,
        total=?, cash_received=?, change_amount=?
    WHERE id=?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$types = "ssi" . str_repeat("ssd", 8) . "dddi";

$stmt->bind_param(
    $types,
    $date, $payee, $refno,
    $codesArr[0], $acctsArr[0], $amtsArr[0],
    $codesArr[1], $acctsArr[1], $amtsArr[1],
    $codesArr[2], $acctsArr[2], $amtsArr[2],
    $codesArr[3], $acctsArr[3], $amtsArr[3],
    $codesArr[4], $acctsArr[4], $amtsArr[4],
    $codesArr[5], $acctsArr[5], $amtsArr[5],
    $codesArr[6], $acctsArr[6], $amtsArr[6],
    $codesArr[7], $acctsArr[7], $amtsArr[7],
    $total, $cash_received, $change_amount,
    $id
);

if ($stmt->execute()) {
    // ✅ Activity Log
    if (isset($_SESSION['user_id'])) {
        $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
        $agent  = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $action = "UPDATE_PAYMENT";
        $details = "Updated payment ID #$id (Ref No: $refno, Payee: $payee, Total: ₱$total)";

        $log = $conn->prepare("INSERT INTO activity_logs 
            (user_id, username, action, details, ip_address, user_agent) 
            VALUES (?,?,?,?,?,?)");
        $log->bind_param(
            "isssss",
            $_SESSION['user_id'],
            $_SESSION['username'],
            $action,
            $details,
            $ip,
            $agent
        );
        $log->execute();
        $log->close();
    }

    // ✅ redirect with success flag
    header("Location: edit.php?id=$id&updated=1");
    exit;
} else {
    die("Update failed: " . $stmt->error);
}
?>
