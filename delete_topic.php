<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$topic_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_admin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'master_admin'], true);
if ($topic_id <= 0) {
    header("Location: index.php");
    exit();
}

$res = $conn->query("SELECT user_id, image_path FROM topics WHERE id = $topic_id");
if (!$res || $res->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$topic = $res->fetch_assoc();
if ($topic['user_id'] != $_SESSION['user_id'] && !$is_admin) {
    die('Akses ditolak. Anda tidak berhak menghapus topik ini.');
}

// Hapus file gambar topik jika ada
if (!empty($topic['image_path']) && file_exists($topic['image_path'])) {
    @unlink($topic['image_path']);
}

// Hapus gambar balasan terkait
$replies = $conn->query("SELECT image_path FROM replies WHERE topic_id = $topic_id AND image_path IS NOT NULL");
if ($replies) {
    while ($r = $replies->fetch_assoc()) {
        if (!empty($r['image_path']) && file_exists($r['image_path'])) {
            @unlink($r['image_path']);
        }
    }
}

// Hapus balasan, reaksi, dan topik
$conn->query("DELETE FROM topic_likes WHERE topic_id = $topic_id");
$conn->query("DELETE FROM replies WHERE topic_id = $topic_id");
$conn->query("DELETE FROM topics WHERE id = $topic_id");

header("Location: index.php");
exit();

?>
