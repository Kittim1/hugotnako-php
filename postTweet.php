<?php
require 'db.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$content = trim($data['content']);
$user_id = $data['user_id']; // Get user ID from request
$username = $data['username']; // Get username from request

if (!empty($content) && !empty($user_id) && !empty($username)) {
    postTweet($conn, $content, $user_id, $username);
} else {
    echo json_encode(['error' => 'Content, user ID, and username cannot be empty']);
}

$conn->close();

function postTweet($conn, $content, $user_id, $username) {
    $stmt = $conn->prepare("INSERT INTO tweets (content, user_id) VALUES (?, ?)");
    $stmt->bind_param("si", $content, $user_id);

    if ($stmt->execute()) {
        $tweet = [
            'id' => $stmt->insert_id,
            'content' => $content,
            'likes' => 0,
            'comments' => [],
            'author' => $username // Use the actual username
        ];
        echo json_encode($tweet);
    } else {
        echo json_encode(['error' => 'Error posting tweet: ' . $stmt->error]);
    }
    $stmt->close();
}
?>
