<?php
/**
 * =====================================================
 * CONFIG DATABASE
 * =====================================================
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$host = "localhost";
$user = "root";
$pass = "";
$db   = "forum_diskusi";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi database gagal : " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
*/

define("BASE_URL", "http://localhost/tugas-uas-sem-2");

/*
|--------------------------------------------------------------------------
| FOLDER UPLOAD
|--------------------------------------------------------------------------
*/

define("UPLOAD_PROFILE", "uploads/profile/");
define("UPLOAD_TOPIC", "uploads/topics/");
define("UPLOAD_COMMENT", "uploads/comments/");
define("UPLOAD_REPLY", "uploads/replies/");

/*
|--------------------------------------------------------------------------
| MEMBUAT FOLDER JIKA BELUM ADA
|--------------------------------------------------------------------------
*/

$folders = [
    UPLOAD_PROFILE,
    UPLOAD_TOPIC,
    UPLOAD_COMMENT,
    UPLOAD_REPLY
];

foreach ($folders as $folder) {
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }
}

/*
|--------------------------------------------------------------------------
| KATEGORI
|--------------------------------------------------------------------------
*/

$categories = [
    "Umum",
    "Teknologi",
    "Pendidikan",
    "Bisnis",
    "Game",
    "Olahraga",
    "Musik",
    "Film",
    "Kesehatan"
];

/*
|--------------------------------------------------------------------------
| ESCAPE STRING
|--------------------------------------------------------------------------
*/

function clean($text)
{
    global $conn;
    return mysqli_real_escape_string($conn, trim($text));
}