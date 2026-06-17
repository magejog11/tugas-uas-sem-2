<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "forum_diskusi";
$upload_dir = 'uploads/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$categories = [
    'Umum',
    'Teknologi',
    'Pendidikan',
    'Kegiatan',
    'Pengumuman'
];

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
