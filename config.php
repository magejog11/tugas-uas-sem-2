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

$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$role_col = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($role_col && $role_col->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN role VARCHAR(30) NOT NULL DEFAULT 'user'");
}

$master_admin_username = 'masteradmin';
$master_admin_password = 'Admin123!';
$check_admin = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check_admin->bind_param('s', $master_admin_username);
$check_admin->execute();
$admin_result = $check_admin->get_result();
if ($admin_result->num_rows === 0) {
    $admin_hash = password_hash($master_admin_password, PASSWORD_BCRYPT);
    $admin_role = 'master_admin';
    $insert_admin = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $insert_admin->bind_param('sss', $master_admin_username, $admin_hash, $admin_role);
    $insert_admin->execute();
    $insert_admin->close();
}
$check_admin->close();
?>
