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

likeTweet($conn, $tweetId);
$conn->close();

function likeTweet($conn, $tweetId) {
    $stmt = $conn->prepare("UPDATE tweets SET likes = likes + 1 WHERE id = ?");
    $stmt->bind_param("i", $tweetId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Error liking tweet']);
    }
    $stmt->close();
}
?>
