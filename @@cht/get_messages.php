<?php
// Set content type to JSON
header('Content-Type: application/json');

// Start session to get username
session_start();

// Include database connection
require('../db.php');

// Check if user is logged in
$current_user = $_SESSION['username'] ?? null;

if (!$current_user) {
    // Return empty array if not logged in
    echo json_encode([]);
    exit();
}

// Updated query: Avoid reserved keywords like `like`
$query = "
    SELECT 
        m.id,
        m.username,
        m.message,
        m.media_path,
        m.created_at,
        u.fullname,
        
        -- Reaction counts (using safe aliases)
        (SELECT COUNT(*) FROM reactions WHERE message_id = m.id AND reaction = '❤️') AS heart_count,
        (SELECT COUNT(*) FROM reactions WHERE message_id = m.id AND reaction = '👍') AS like_count,
        (SELECT COUNT(*) FROM reactions WHERE message_id = m.id AND reaction = '😂') AS laugh_count,
        
        -- Check if current user reacted
        (SELECT reaction FROM reactions WHERE message_id = m.id AND username = ?) AS my_reaction
        
    FROM messages m
    JOIN users u ON m.username = u.username
    ORDER BY m.created_at DESC
    LIMIT 100
";

// Prepare and execute
$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    echo json_encode(['error' => 'Database prepare failed']);
    exit();
}

$stmt->bind_param("s", $current_user);

if (!$stmt->execute()) {
    error_log("Execute failed: " . $stmt->error);
    echo json_encode(['error' => 'Query execution failed']);
    exit();
}

$result = $stmt->get_result();
$messages = [];

while ($row = $result->fetch_assoc()) {
    // Clean output
    $messages[] = [
        'id' => (int)$row['id'],
        'username' => htmlspecialchars($row['username']),
        'fullname' => htmlspecialchars($row['fullname']),
        'message' => $row['message'] ? htmlspecialchars($row['message']) : '',
        'media_path' => $row['media_path'],
        'created_at' => $row['created_at'],
        'heart_count' => (int)$row['heart_count'],
        'like_count' => (int)$row['like_count'],
        'laugh_count' => (int)$row['laugh_count'],
        'my_reaction' => $row['my_reaction']
    ];
}

// Output JSON
echo json_encode($messages);

// Close statement
$stmt->close();
?>