<?php
header('Content-Type: application/json');
require('../db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$message_id = (int)$_POST['message_id'];
$reaction = $_POST['reaction'];
$username = $_POST['username'];

// Validate emoji
if (!in_array($reaction, ['❤️', '👍', '😂'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid reaction']);
    exit();
}

// Delete if already reacted
$check = $conn->prepare("DELETE FROM reactions WHERE message_id = ? AND username = ?");
$check->bind_param("is", $message_id, $username);
$check->execute();
$check->close();

// Add new reaction
$stmt = $conn->prepare("INSERT INTO reactions (message_id, username, reaction) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $message_id, $username, $reaction);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
?>