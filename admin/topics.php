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

$message = '';
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}

$error = '';
if (isset($_SESSION['admin_error'])) {
    $error = $_SESSION['admin_error'];
    unset($_SESSION['admin_error']);
}

$topics = mysqli_query($conn, "
    SELECT topics.*, users.username
    FROM topics
    LEFT JOIN users ON users.id = topics.user_id
    ORDER BY topics.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Topik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fb;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            background: #212529;
        }

        .sidebar h3 {
            color: white;
            padding: 20px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 15px 20px;
        }

        .sidebar a:hover {
            background: #0d6efd;
        }

        .content {
            margin-left: 250px;
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,.08);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3>ADMIN</h3>
        <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="users.php"><i class="bi bi-people"></i> Users</a>
        <a href="topics.php"><i class="bi bi-chat-left-text"></i> Topik</a>
        <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Manajemen Topik</h2>
                <p class="text-muted mb-0">Kelola topik, foto postingan, dan hapus konten dari satu panel.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Daftar Topik</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Judul</th>
                                <th>Penulis</th>
                                <th>Kategori</th>
                                <th>Foto</th>
                                <th>Tanggal</th>
                                <th width="220">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($topics && mysqli_num_rows($topics) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($topics)): ?>
                                    <tr>
                                        <td><?= (int)$row['id'] ?></td>
                                        <td><?= htmlspecialchars($row['title']) ?></td>
                                        <td><?= htmlspecialchars($row['username'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['category'] ?? '-') ?></td>
                                        <td>
                                            <?php if (!empty($row['image_path'])): ?>
                                                <img src="../<?= htmlspecialchars($row['image_path']) ?>" alt="Foto topik" style="width:60px;height:60px;object-fit:cover;border-radius:8px;">
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Tidak ada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                                        <td>
                                            <form method="post" action="delete_topic.php" class="d-inline" onsubmit="return confirm('Hapus topik ini?')">
                                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                                <input type="hidden" name="action" value="delete_topic">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-trash"></i> Hapus Topic
                                                </button>
                                            </form>

                                            <?php if (!empty($row['image_path'])): ?>
                                                <form method="post" action="delete_topic.php" class="d-inline ms-1" onsubmit="return confirm('Hapus foto dari topik ini?')">
                                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                                    <input type="hidden" name="action" value="delete_photo">
                                                    <button type="submit" class="btn btn-outline-warning btn-sm">
                                                        <i class="bi bi-image"></i> Hapus Foto
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada topik yang dibuat.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
