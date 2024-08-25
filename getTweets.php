<?php
require 'db.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

getTweets($conn);
$conn->close();

function getTweets($conn) {
    $sql = "SELECT tweets.id, tweets.content, tweets.likes, users.username AS author,
        COALESCE(GROUP_CONCAT(comments.content ORDER BY comments.created_at ASC SEPARATOR '|||'), '') AS comments,
        COALESCE(GROUP_CONCAT(users.username ORDER BY comments.created_at ASC SEPARATOR '|||'), '') AS comment_authors
        FROM tweets
        LEFT JOIN comments ON tweets.id = comments.tweet_id
        LEFT JOIN users ON tweets.user_id = users.id
        GROUP BY tweets.id
        ORDER BY tweets.created_at DESC";


    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(['error' => 'Error executing query: ' . $conn->error]);
        return;
    }

    $tweets = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $comments = [];
            $commentContents = explode('|||', $row['comments']);
            $commentAuthors = explode('|||', $row['comment_authors']);
            for ($i = 0; $i < count($commentContents); $i++) {
                if (!empty($commentContents[$i])) {
                    $comments[] = [
                        'content' => $commentContents[$i],
                        'username' => $commentAuthors[$i]
                    ];
                }
            }
            $tweets[] = [
                'id' => $row['id'],
                'content' => $row['content'],
                'likes' => $row['likes'],
                'author' => $row['author'],
                'comments' => $comments
            ];
        }
    }

    echo json_encode($tweets);
}
?>
