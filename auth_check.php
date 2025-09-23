<?php
// auth_check.php — Include this at the top of protected scripts
session_start();
include 'config.php';

// ✅ Force login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
function require_role($allowed_roles = []) {
    $role = strtolower($_SESSION['role'] ?? '');
    if (!in_array($role, array_map('strtolower', $allowed_roles))) {
        echo "<div style='padding:20px; font-family:sans-serif; color:red;'>
                ❌ Access Denied. This page is restricted to: 
                <strong>" . implode(', ', $allowed_roles) . "</strong>.
              </div>";
        exit;
    }
}

/**
 * Log user activity
 *
 * @param mysqli $conn      Database connection
 * @param string $action    Short action code (e.g., "INSERT_PAYMENT")
 * @param string $details   Description of the activity
 */
function logActivity($conn, $action, $details) {
    if (!isset($_SESSION['user_id'])) return; // safety

    $userId   = (int)$_SESSION['user_id'];
    $username = $_SESSION['username'] ?? '';
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent    = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmt = $conn->prepare("INSERT INTO activity_logs 
        (user_id, username, action, details, ip_address, user_agent) 
        VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("isssss", $userId, $username, $action, $details, $ip, $agent);
    $stmt->execute();
    $stmt->close();
}
?>
