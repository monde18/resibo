<?php
// reset_password.php
// One-time script to reset a user's password securely.
// ⚠️ DELETE THIS FILE after use!

include 'config.php';

// === CONFIGURE HERE ===
$usernameToReset = "admin1";   // change to the username you want to reset
$newPlainPassword = "admin123"; // the new password you want
// =======================

$hash = password_hash($newPlainPassword, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password=? WHERE username=?");
$stmt->bind_param("ss", $hash, $usernameToReset);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "<h3>✅ Password for <strong>$usernameToReset</strong> has been reset.</h3>";
        echo "<p>New password is: <strong>$newPlainPassword</strong></p>";
        echo "<p>You can now log in at <a href='login.php'>login.php</a></p>";
    } else {
        echo "<h3>⚠️ No user found with username <strong>$usernameToReset</strong>.</h3>";
    }
} else {
    echo "<h3>❌ Database error: " . $stmt->error . "</h3>";
}

$stmt->close();
$conn->close();

echo "<p style='color:red'><strong>⚠️ Reminder:</strong> Delete this file after you successfully log in.</p>";
