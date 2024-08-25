<?php
require 'db.php';
session_start(); // Start the session to manage user login

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'getTweets':
        getTweets($conn);
        break;
    case 'postTweet':
        postTweet($conn);
        break;
    case 'likeTweet':
        likeTweet($conn);
        break;
    case 'commentTweet':
        commentTweet($conn);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

$conn->close();

function getTweets($conn) {
  $sql = "SELECT tweets.id, tweets.content, tweets.likes, users.username AS author,
          COALESCE(GROUP_CONCAT(comments.content ORDER BY comments.created_at ASC SEPARATOR '|||'), '') AS comments,
          COALESCE(GROUP_CONCAT(users.username ORDER BY comments.created_at ASC SEPARATOR '|||'), '') AS comment_authors
          FROM tweets
          LEFT JOIN comments ON tweets.id = comments.tweet_id
          LEFT JOIN users ON comments.user_id = users.id
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
              $comments[] = [
                  'content' => $commentContents[$i],
                  'username' => $commentAuthors[$i]
              ];
          }
          $tweets[] = [
              'id' => $row['id'],
              'content' => $row['content'],
              'likes' => $row['likes'],
              'comments' => $comments,
              'author' => $row['author']
          ];
      }
  }

  echo json_encode($tweets);
}

function postTweet($conn) {
  $data = json_decode(file_get_contents('php://input'), true);
  $content = trim($data['content']);
  $user_id = $_SESSION['user_id']; // Get the logged-in user's ID from the session

  if (!empty($content)) {
      $stmt = $conn->prepare("INSERT INTO tweets (content, user_id) VALUES (?, ?)");
      $stmt->bind_param("si", $content, $user_id);

      if ($stmt->execute()) {
          $tweet = [
              'id' => $stmt->insert_id,
              'content' => $content,
              'likes' => 0,
              'comments' => [],
              'author' => $_SESSION['username'] // Get the username from the session
          ];
          echo json_encode($tweet);
      } else {
          echo json_encode(['error' => 'Error posting tweet: ' . $stmt->error]);
      }
      $stmt->close();
  } else {
      echo json_encode(['error' => 'Content cannot be empty']);
  }
}

function likeTweet($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $tweetId = $data['tweetId'];

    $stmt = $conn->prepare("UPDATE tweets SET likes = likes + 1 WHERE id = ?");
    $stmt->bind_param("i", $tweetId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Error liking tweet']);
    }
    $stmt->close();
}

function commentTweet($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $tweetId = $data['tweetId'];
    $comment = trim($data['comment']);
    $user_id = $_SESSION['user_id']; // Get the logged-in user's ID from the session

    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (tweet_id, content, user_id) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $tweetId, $comment, $user_id);

        if ($stmt->execute()) {
            $newComment = [
                'content' => $comment,
                'username' => $_SESSION['username'] // Get the username from the session
            ];
            echo json_encode($newComment);
        } else {
            echo json_encode(['error' => 'Error commenting on tweet']);
        }
        $stmt->close();
    } else {
        echo json_encode(['error' => 'Comment cannot be empty']);
    }
}
?>
