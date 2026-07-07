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

$conn->set_charset('utf8mb4');

$conn->query("CREATE TABLE IF NOT EXISTS topic_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction VARCHAR(10) NOT NULL DEFAULT 'like',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_topic_user (topic_id, user_id),
    KEY idx_topic_id (topic_id),
    KEY idx_user_id (user_id)
)");

$reaction_col = $conn->query("SHOW COLUMNS FROM topic_likes LIKE 'reaction'");
if ($reaction_col && $reaction_col->num_rows === 0) {
    $conn->query("ALTER TABLE topic_likes ADD COLUMN reaction VARCHAR(10) NOT NULL DEFAULT 'like'");
}

$conn->query("UPDATE topic_likes SET reaction = 'like' WHERE reaction IS NULL OR reaction = ''");
?>
