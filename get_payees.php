<?php
include 'config.php'; // your DB connection

$term = $_GET['term'] ?? '';

$stmt = $conn->prepare("SELECT DISTINCT payee FROM payments WHERE payee LIKE ? LIMIT 10");
$search = "%".$term."%";
$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();

$payees = [];
while ($row = $result->fetch_assoc()) {
    $payees[] = $row['payee'];
}

echo json_encode($payees);
