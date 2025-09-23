<?php
// delete.php — delete a payment by ID (with safety check + redirect + activity log)
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['msg'] = "⚠️ You must be logged in to delete records.";
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // First check if record exists
    $check = $conn->prepare("SELECT reference_no, payee, total FROM payments WHERE id=?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->bind_result($refno, $payee, $total);
        $check->fetch();
        $check->close();

        // Delete securely
        $stmt = $conn->prepare("DELETE FROM payments WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['msg'] = "✅ Payment with Reference No. <strong>" . htmlspecialchars($refno) . "</strong> deleted successfully.";

            // ✅ Log the deletion
            $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
            $agent  = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $action = "DELETE_PAYMENT";
            $details = "Deleted payment ID #$id (Ref No: $refno, Payee: $payee, Total: ₱$total)";

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

        } else {
            $_SESSION['msg'] = "❌ Error deleting record: " . $conn->error;
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "⚠️ Record not found.";
    }
} else {
    $_SESSION['msg'] = "⚠️ Invalid ID.";
}

header("Location: records.php"); // redirect back to list
exit;
?>
