<?php
include 'config.php';
include 'fees.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = intval($_POST['id']);
    $date  = $_POST['date'] ?? '';
    $payee = $_POST['payee'] ?? '';
    $refno = $_POST['refno'] ?? '';

    $codes  = $_POST['code'] ?? [];
    $accts  = $_POST['acctname'] ?? [];
    $consts = $_POST['constval'] ?? [];
    $amts   = $_POST['amount'] ?? [];

    $codesArr = $acctsArr = $constArr = $amtsArr = [];
    for ($i=0; $i<8; $i++) {
        $codesArr[$i] = $codes[$i] ?? '';
        $acctsArr[$i] = $accts[$i] ?? '';
        $constArr[$i] = $consts[$i] ?? '';
        $amtsArr[$i]  = is_numeric($amts[$i] ?? '') ? (float)$amts[$i] : 0.00;
    }

    $total = array_sum($amtsArr);

    $sql = "
    UPDATE payments SET
      date=?, payee=?, reference_no=?,
      code1=?, account_name1=?, const1=?, amount1=?,
      code2=?, account_name2=?, const2=?, amount2=?,
      code3=?, account_name3=?, const3=?, amount3=?,
      code4=?, account_name4=?, const4=?, amount4=?,
      code5=?, account_name5=?, const5=?, amount5=?,
      code6=?, account_name6=?, const6=?, amount6=?,
      code7=?, account_name7=?, const7=?, amount7=?,
      code8=?, account_name8=?, const8=?, amount8=?,
      total=?
    WHERE id=?";

    $stmt = $conn->prepare($sql);

    $types = str_repeat("s", 3) . str_repeat("sssd", 8) . "di";

    $stmt->bind_param(
        $types,
        $date, $payee, $refno,
        $codesArr[0], $acctsArr[0], $constArr[0], $amtsArr[0],
        $codesArr[1], $acctsArr[1], $constArr[1], $amtsArr[1],
        $codesArr[2], $acctsArr[2], $constArr[2], $amtsArr[2],
        $codesArr[3], $acctsArr[3], $constArr[3], $amtsArr[3],
        $codesArr[4], $acctsArr[4], $constArr[4], $amtsArr[4],
        $codesArr[5], $acctsArr[5], $constArr[5], $amtsArr[5],
        $codesArr[6], $acctsArr[6], $constArr[6], $amtsArr[6],
        $codesArr[7], $acctsArr[7], $constArr[7], $amtsArr[7],
        $total, $id
    );

    if ($stmt->execute()) {
        header("Location: records.php?status=success");
    } else {
        header("Location: records.php?status=error");
    }
    exit;
}
