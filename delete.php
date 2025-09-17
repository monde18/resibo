<?php
// delete.php — delete a payment by ID (with safety check + redirect)
include 'config.php';
session_start();

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // First check if record exists
    $check = $conn->prepare("SELECT reference_no FROM payments WHERE id=?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->bind_result($refno);
        $check->fetch();
        $check->close();

        // Delete securely
        $stmt = $conn->prepare("DELETE FROM payments WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['msg'] = "✅ Payment with Reference No. <strong>" . htmlspecialchars($refno) . "</strong> deleted successfully.";
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
