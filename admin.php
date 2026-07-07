<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$is_admin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'master_admin'], true);
if (!$is_admin) {
    die('Akses ditolak. Hanya admin yang bisa membuka halaman ini.');
}

if (isset($_POST['promote_user'])) {
    $user_id = intval($_POST['user_id']);
    $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
    $conn->query("UPDATE users SET role = '$role' WHERE id = $user_id");
    header('Location: admin.php');
    exit();
}

$stats = [];
$stats['topics'] = $conn->query("SELECT COUNT(*) AS total FROM topics")->fetch_assoc()['total'];
$stats['replies'] = $conn->query("SELECT COUNT(*) AS total FROM replies")->fetch_assoc()['total'];
$stats['users'] = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$users_result = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
$topics_result = $conn->query("SELECT topics.id, topics.title, users.username FROM topics JOIN users ON topics.user_id = users.id ORDER BY topics.created_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Panel Admin</h2>
            <p class="text-muted mb-0">Kelola pengguna dan konten forum.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">← Ke Beranda</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total Topik</h6>
                    <h3 class="mb-0"><?php echo (int)$stats['topics']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total Balasan</h6>
                    <h3 class="mb-0"><?php echo (int)$stats['replies']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total Pengguna</h6>
                    <h3 class="mb-0"><?php echo (int)$stats['users']; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-3">Daftar Pengguna</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['role'] !== 'master_admin'): ?>
                                        <form method="POST" class="d-flex gap-2">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                            <select name="role" class="form-select form-select-sm">
                                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                            <button type="submit" name="promote_user" class="btn btn-sm btn-outline-primary">Simpan</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Master</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Topik Terbaru</h5>
            <ul class="list-group list-group-flush">
                <?php while ($topic = $topics_result->fetch_assoc()): ?>
                    <li class="list-group-item px-0">
                        <a href="view_topic.php?id=<?php echo (int)$topic['id']; ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($topic['title']); ?>
                        </a>
                        <div class="small text-muted">Oleh <?php echo htmlspecialchars($topic['username']); ?></div>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
