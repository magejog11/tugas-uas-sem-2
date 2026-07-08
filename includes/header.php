<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = $page_title ?? "Forum Diskusi";
$page_css = $page_css ?? "";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($page_title) ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <?php if (!empty($page_css)): ?>
        <link rel="stylesheet" href="assets/css/<?= htmlspecialchars($page_css) ?>">
    <?php endif; ?>

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">
</head>

<body>