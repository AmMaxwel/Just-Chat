<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include database
require('../db.php');

// Log for debugging
file_put_contents('debug_send.log', date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$username = $_SESSION['username'];
$message = trim($_POST['message'] ?? '');
$media_path = '';

file_put_contents('debug_send.log', "Username: $username, Message: $message\n", FILE_APPEND);

// Handle file upload
if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'mp3', 'ogg', 'wav', 'webm', 'mp4'];
    $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $filename = 'msg_' . time() . '_' . basename($_FILES['media']['name']);
        $target = '../uploads/' . $filename;

        if (move_uploaded_file($_FILES['media']['tmp_name'], $target)) {
            $media_path = 'uploads/' . $filename;
            file_put_contents('debug_send.log', "File uploaded: $media_path\n", FILE_APPEND);
        } else {
            file_put_contents('debug_send.log', "File move failed\n", FILE_APPEND);
        }
    } else {
        file_put_contents('debug_send.log', "File type not allowed: $ext\n", FILE_APPEND);
    }
}

// Insert into database
if (!empty($message) || !empty($media_path)) {
    $stmt = $conn->prepare("INSERT INTO messages (username, message, media_path) VALUES (?, ?, ?)");
    if (!$stmt) {
        file_put_contents('debug_send.log', "Prepare failed: " . $conn->error . "\n", FILE_APPEND);
        http_response_code(500);
        exit();
    }

    $stmt->bind_param("sss", $username, $message, $media_path);
    if ($stmt->execute()) {
        file_put_contents('log.html', "Message saved to DB\n", FILE_APPEND);
    } else {
        file_put_contents('log.html', "Execute failed: " . $stmt->error . "\n", FILE_APPEND);
    }
    $stmt->close();
} else {
    file_put_contents('debug_send.log', "No message or media to save\n", FILE_APPEND);
}

// Close connection
$conn->close();

// No output (to avoid JSON parse errors)
?>