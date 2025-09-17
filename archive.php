<?php
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
    $action = $_POST['action'];
    
    // Validate and sanitize input
    $id = filter_var($id, FILTER_VALIDATE_INT);
    $reason = filter_var($reason, FILTER_SANITIZE_STRING);
    
    if ($id === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
    
    try {
        // Check if the archived column exists, if not alter the table
        $checkColumn = $conn->query("SHOW COLUMNS FROM payments LIKE 'archived'");
        if ($checkColumn->num_rows == 0) {
            $conn->query("ALTER TABLE payments ADD COLUMN archived TINYINT(1) DEFAULT 0");
        }
        
        if ($action === 'archive') {
            // Validate reason
            if (empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'Archive reason is required']);
                exit;
            }
            
            // Check if the archive_reason column exists, if not alter the table
            $checkReasonColumn = $conn->query("SHOW COLUMNS FROM payments LIKE 'archive_reason'");
            if ($checkReasonColumn->num_rows == 0) {
                $conn->query("ALTER TABLE payments ADD COLUMN archive_reason TEXT NULL");
            }
            
            // Check if the archived_date column exists, if not alter the table
            $checkDateColumn = $conn->query("SHOW COLUMNS FROM payments LIKE 'archived_date'");
            if ($checkDateColumn->num_rows == 0) {
                $conn->query("ALTER TABLE payments ADD COLUMN archived_date TIMESTAMP NULL");
            }
            
            // Archive the record
            $stmt = $conn->prepare("UPDATE payments SET archived = 1, archive_reason = ?, archived_date = NOW() WHERE id = ?");
            $stmt->bind_param('si', $reason, $id);
        } 
        elseif ($action === 'restore') {
            // Restore the record - only reset archived flag if other columns don't exist
            $checkReasonColumn = $conn->query("SHOW COLUMNS FROM payments LIKE 'archive_reason'");
            $checkDateColumn = $conn->query("SHOW COLUMNS FROM payments LIKE 'archived_date'");
            
            if ($checkReasonColumn->num_rows > 0 && $checkDateColumn->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE payments SET archived = 0, archive_reason = NULL, archived_date = NULL WHERE id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE payments SET archived = 0 WHERE id = ?");
            }
            $stmt->bind_param('i', $id);
        }
        else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Operation completed successfully']);
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