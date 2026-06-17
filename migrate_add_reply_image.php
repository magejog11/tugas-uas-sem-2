<?php
include 'config.php';

$result = $conn->query("SHOW COLUMNS FROM replies LIKE 'image_path'");
if ($result && $result->num_rows > 0) {
    echo "Kolom 'image_path' sudah ada di tabel replies.\n";
    exit;
}

$sql = "ALTER TABLE replies ADD COLUMN image_path VARCHAR(255) DEFAULT NULL";
if ($conn->query($sql) === TRUE) {
    echo "Migrasi berhasil: kolom 'image_path' ditambahkan ke tabel replies.\n";
} else {
    echo "Migrasi gagal: " . $conn->error . "\n";
}
