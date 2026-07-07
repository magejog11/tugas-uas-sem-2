<?php
include 'config.php';
session_start();

if (!isset($categories)) {
    $categories = [];
}

$selected_category = '';
$category_filter = '';
if (isset($_GET['category']) && in_array($_GET['category'], $categories, true)) {
    $selected_category = $_GET['category'];
    $category_filter = "WHERE topics.category = '" . $conn->real_escape_string($selected_category) . "'";
}

$query = "SELECT topics.*, users.username FROM topics JOIN users ON topics.user_id = users.id $category_filter ORDER BY topics.created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FORKOM UIM</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <!-- Bootstrap 5 CSS CDN -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar Atas -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="logo1.png" alt="Logo" height="70" class="me-2">FORKOM UIMYogyakarta
            </a>
            <div class="d-flex align-items-center">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="text-white me-3">Halo, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Pengguna'); ?></strong></span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Keluar</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light btn-sm me-2">Masuk</a>
                    <a href="register.php" class="btn btn-primary btn-sm">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Konten Utama -->
    <div class="container">
        <div class="row">
            <div class="col-md-8 mx-auto">
                
                <div class="d-flex justify-content-between align-items-center mb-4 flex-column flex-md-row gap-3">
                    <div>
                        <h2 class="h4 mb-0 text-secondary">Daftar Diskusi Terbaru</h2>
                        <?php if ($selected_category): ?>
                            <div class="small text-muted">Kategori: <strong><?php echo htmlspecialchars($selected_category); ?></strong></div>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="create_topic.php" class="btn btn-success">+ Tambah Topik Baru</a>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <div class="btn-group" role="group">
                        <a href="index.php" class="btn btn-sm <?php echo $selected_category === '' ? 'btn-secondary' : 'btn-outline-secondary'; ?>">Semua</a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="index.php?category=<?php echo urlencode($cat); ?>" class="btn btn-sm <?php echo $selected_category === $cat ? 'btn-secondary' : 'btn-outline-secondary'; ?>"><?php echo htmlspecialchars($cat); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- List Topik Diskusi -->
                <div class="list-group shadow-sm">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <a href="view_topic.php?id=<?php echo $row['id']; ?>" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex gap-3">
                                    <?php if (!empty($row['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="Thumbnail" class="rounded" style="width:100px; height:100px; object-fit:cover;">
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <div class="d-flex w-100 justify-content-between align-items-start gap-2">
                                            <div>
                                                <h5 class="mb-1 text-primary"><?php echo htmlspecialchars($row['title']); ?></h5>
                                                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['category']); ?></span>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted d-block"><?php echo date('d M Y', strtotime($row['created_at'])); ?></small>
                                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id']): ?>
                                                    <a href="delete_topic.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger mt-1" onclick="return confirm('Hapus topik ini? Semua balasan juga akan dihapus.')">Hapus</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <p class="mb-1 text-muted text-truncate"><?php echo htmlspecialchars(substr($row['content'], 0, 100)); ?>...</p>
                                        <small class="text-secondary">Oleh: <b><?php echo htmlspecialchars($row['username']); ?></b></small>
                                    </div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="list-group-item text-center py-4 text-muted">Belum ada diskusi terbuka.</div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
