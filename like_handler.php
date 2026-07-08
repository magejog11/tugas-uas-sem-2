<?php
include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User belum login']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID Topik tidak valid']);
    exit;
}

$topic_id = intval($_GET['id']);
$user_id = intval($_SESSION['user_id']);

$check_query = "SELECT * FROM topic_likes WHERE topic_id = $topic_id AND user_id = $user_id";
$check_result = $conn->query($check_query);

if ($check_result && $check_result->num_rows > 0) {
    $delete_like = "DELETE FROM topic_likes WHERE topic_id = $topic_id AND user_id = $user_id";
    $conn->query($delete_like);
    
    $update_topic = "UPDATE topics SET likes = GREATEST(0, likes - 1) WHERE id = $topic_id";
    $conn->query($update_topic);
} else {
    $insert_like = "INSERT INTO topic_likes (topic_id, user_id) VALUES ($topic_id, $user_id)";
    $conn->query($insert_like);
    
    $update_topic = "UPDATE topics SET likes = likes + 1 WHERE id = $topic_id";
    $conn->query($update_topic);
}

$count_query = "SELECT likes FROM topics WHERE id = $topic_id";
$count_result = $conn->query($count_query);
$row = $count_result->fetch_assoc();
$new_likes = isset($row['likes']) ? intval($row['likes']) : 0;

echo json_encode([
    'success' => true,
    'new_likes' => $new_likes
]);
exit;