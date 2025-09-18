<?php
include 'config.php';
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
if(!$id){ echo json_encode(["success"=>false,"message"=>"Invalid ID"]); exit; }

$res = $conn->query("SELECT * FROM payments WHERE id=$id");
if(!$res || $res->num_rows==0){ echo json_encode(["success"=>false,"message"=>"Record not found"]); exit; }

$row = $res->fetch_assoc();

// Build payments array
$payments = [];
for($i=1;$i<=8;$i++){
  if(!empty($row["code$i"]) || !empty($row["account_name$i"])){
    $payments[] = [
      "code" => $row["code$i"] ?? '',
      "account" => $row["account_name$i"] ?? '',
      "amount" => $row["amount$i"] ?? 0
    ];
  }
}

echo json_encode([
  "success"=>true,
  "data"=>[
    "payee"=>$row['payee'],
    "total"=>$row['total'],
    "cash_received"=>$row['cash_received'] ?? 0,
    "change_amount"=>$row['change_amount'] ?? 0,
    "payments"=>$payments
  ]
]);
