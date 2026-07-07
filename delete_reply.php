<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$reply_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$is_admin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'master_admin'], true);
if ($reply_id <= 0 || $topic_id <= 0) {
    header("Location: index.php");
    exit();
}

$res = $conn->query("SELECT user_id, image_path FROM replies WHERE id = $reply_id");
if (!$res || $res->num_rows === 0) {
    header("Location: view_topic.php?id=" . $topic_id);
    exit();
}

$reply = $res->fetch_assoc();
if ($reply['user_id'] != $_SESSION['user_id'] && !$is_admin) {
    die('Akses ditolak. Anda tidak berhak menghapus balasan ini.');
}

if (!empty($reply['image_path']) && file_exists($reply['image_path'])) {
    @unlink($reply['image_path']);
}

$conn->query("DELETE FROM replies WHERE id = $reply_id");

header("Location: view_topic.php?id=" . $topic_id);
exit();

?>
