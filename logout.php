<?php
session_start();
include 'config.php';

if (isset($_SESSION['user_id'])) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt = $conn->prepare("INSERT INTO activity_logs 
        (user_id, username, action, details, ip_address, user_agent) 
        VALUES (?,?,?,?,?,?)");
    $action = "LOGOUT";
    $details = "User logged out";
    $stmt->bind_param("isssss", $_SESSION['user_id'], $_SESSION['username'], $action, $details, $ip, $agent);
    $stmt->execute();
    $stmt->close();
}

session_destroy();
header("Location: login.php");
exit;
