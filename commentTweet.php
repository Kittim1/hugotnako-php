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
$tweetId = $data['tweetId'];
$comment = trim($data['comment']);
$user_id = $data['user_id']; // Extract user_id from the request
$username = $data['username']; // Extract username from the request

if (!empty($comment)) {
    commentTweet($conn, $tweetId, $comment, $user_id, $username);
} else {
    echo json_encode(['error' => 'Comment cannot be empty']);
}

$conn->close();

function commentTweet($conn, $tweetId, $comment, $user_id, $username) {
    $stmt = $conn->prepare("INSERT INTO comments (tweet_id, content, user_id, username) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $tweetId, $comment, $user_id, $username);

    if ($stmt->execute()) {
        $newComment = [
            'content' => $comment,
            'username' => $username // Use the actual username from the request
        ];
        echo json_encode($newComment);
    } else {
        echo json_encode(['error' => 'Error commenting on tweet']);
    }
    $stmt->close();
}
?>
