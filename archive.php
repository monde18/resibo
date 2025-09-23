<?php
// archive.php — Archive or Restore a payment (with activity logs)
include 'config.php';
session_start();

header('Content-Type: application/json');

// ✅ Require login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = $_POST['id'] ?? null;
    $reason = $_POST['reason'] ?? '';
    $action = $_POST['action'] ?? '';

    // Validate inputs
    $id = filter_var($id, FILTER_VALIDATE_INT);
    $reason = trim($reason);

    if ($id === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }

    try {
        // Ensure archive-related columns exist
        $conn->query("ALTER TABLE payments 
            ADD COLUMN IF NOT EXISTS archived TINYINT(1) DEFAULT 0,
            ADD COLUMN IF NOT EXISTS archive_reason TEXT NULL,
            ADD COLUMN IF NOT EXISTS archived_date TIMESTAMP NULL
        ");

        if ($action === 'archive') {
            if (empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'Archive reason is required']);
                exit;
            }

            $stmt = $conn->prepare("UPDATE payments 
                SET archived = 1, archive_reason = ?, archived_date = NOW() 
                WHERE id = ?");
            $stmt->bind_param('si', $reason, $id);
            $actionType = "ARCHIVE_PAYMENT";
            $details = "Archived payment ID #$id. Reason: $reason";

        } elseif ($action === 'restore') {
            $stmt = $conn->prepare("UPDATE payments 
                SET archived = 0, archive_reason = NULL, archived_date = NULL 
                WHERE id = ?");
            $stmt->bind_param('i', $id);
            $actionType = "RESTORE_PAYMENT";
            $details = "Restored payment ID #$id";

        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Operation completed successfully']);

            // ✅ Log activity
            $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
            $agent  = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $log = $conn->prepare("INSERT INTO activity_logs 
                (user_id, username, action, details, ip_address, user_agent) 
                VALUES (?,?,?,?,?,?)");
            $log->bind_param(
                "isssss",
                $_SESSION['user_id'],
                $_SESSION['username'],
                $actionType,
                $details,
                $ip,
                $agent
            );
            $log->execute();
            $log->close();

        } else {
            throw new Exception("Database error: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
