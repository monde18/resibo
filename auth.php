<?php
// auth.php — Restrict page access by role
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Helper function to check role access
function require_role($allowed_roles = []) {
    $role = strtolower($_SESSION['role'] ?? '');
    $allowed_roles = array_map('strtolower', $allowed_roles);

    if (!in_array($role, $allowed_roles)) {
        // 🚫 Redirect to dashboard instead of showing error
        header("Location: dashboard.php");
        exit;
    }
}
?>
