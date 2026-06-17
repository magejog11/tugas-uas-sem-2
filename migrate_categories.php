<?php
include 'config.php';

$sql = "ALTER TABLE topics ADD COLUMN category VARCHAR(100) NOT NULL DEFAULT 'Umum'";

if ($conn->query($sql) === TRUE) {
    echo "Migrasi berhasil: kolom 'category' ditambahkan ke tabel topics.";
} else {
    echo "Migrasi gagal: " . $conn->error;
}
