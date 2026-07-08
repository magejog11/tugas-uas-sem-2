<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$result = mysqli_query($conn, "SELECT * FROM users WHERE id = " . (int)$user_id);
$admin = mysqli_fetch_assoc($result);

if (!$admin || ($admin['role'] !== 'admin' && $admin['is_admin'] != 1)) {
    header("Location: ../index.php");
    exit();
}

function removeTopicImageFile($imagePath)
{
    if (empty($imagePath)) {
        return;
    }

    $projectRoot = dirname(__DIR__);
    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . ltrim($imagePath, '/\\');

    if (file_exists($fullPath) && is_file($fullPath)) {
        unlink($fullPath);
    }
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = $_POST['action'] ?? 'delete_topic';

if ($id <= 0) {
    $_SESSION['admin_error'] = 'ID topik tidak valid.';
    header("Location: topics.php");
    exit();
}

$stmt = $conn->prepare("SELECT image_path FROM topics WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$topic = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$topic) {
    $_SESSION['admin_error'] = 'Topik tidak ditemukan.';
    header("Location: topics.php");
    exit();
}

if ($action === 'delete_photo') {
    if (!empty($topic['image_path'])) {
        removeTopicImageFile($topic['image_path']);
    }

    $stmt = $conn->prepare("UPDATE topics SET image_path = '' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['admin_message'] = 'Foto topik berhasil dihapus.';
} else {
    if (!empty($topic['image_path'])) {
        removeTopicImageFile($topic['image_path']);
    }

    $conn->query("DELETE FROM comments WHERE topic_id = $id");

    $stmt = $conn->prepare("DELETE FROM topics WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['admin_message'] = 'Topik berhasil dihapus.';
}

header("Location: topics.php");
exit();
