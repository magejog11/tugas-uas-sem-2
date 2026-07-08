<?php
require_once __DIR__ . '/config.php';

/**
 * Escape HTML
 */
function e($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Cek Login
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Redirect jika belum login
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Format waktu
 */
function timeAgo($datetime)
{
    $time = time() - strtotime($datetime);

    if ($time < 60) {
        return "Baru saja";
    }

    if ($time < 3600) {
        return floor($time / 60) . " menit yang lalu";
    }

    if ($time < 86400) {
        return floor($time / 3600) . " jam yang lalu";
    }

    if ($time < 604800) {
        return floor($time / 86400) . " hari yang lalu";
    }

    return date("d M Y H:i", strtotime($datetime));
}

/**
 * Upload Gambar
 */
function uploadImage($file, $folder)
{
    if ($file['error'] !== 0) {
        return null;
    }

    $allowed = ['jpg','jpeg','png','gif','webp'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        return null;
    }

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $filename = uniqid() . "." . $ext;

    move_uploaded_file($file['tmp_name'], $folder . $filename);

    return $filename;
}

/**
 * Avatar User
 */
function getAvatar($photo = null)
{
    if (empty($photo)) {
        return "assets/img/default-avatar.png";
    }

    return "uploads/profile/" . $photo;
}

/**
 * HTML Avatar
 */
function getAvatarHTML($photo = null, $size = 45)
{
    $src = getAvatar($photo);

    return '<img src="' . $src . '" class="avatar" style="
        width:' . $size . 'px;
        height:' . $size . 'px;
        border-radius:50%;
        object-fit:cover;
    ">';
}

/**
 * Foto Postingan
 */
function getTopicImage($image = null)
{
    if (empty($image)) {
        return "";
    }

    return "uploads/topics/" . $image;
}