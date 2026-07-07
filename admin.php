<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$is_admin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'master_admin'], true);
$is_master_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'master_admin';
$message = '';
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

if ($is_master_admin && isset($_POST['change_master_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($current_password === '' || $new_password === '' || $confirm_password === '') {
        $message = 'Semua kolom password harus diisi.';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Password baru dan konfirmasi tidak sama.';
    } else {
        $user_id = intval($_SESSION['user_id']);
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            if (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->bind_param("si", $hashed_password, $user_id);
                $update->execute();
                $update->close();
                $message = 'Password master admin berhasil diubah.';
            } else {
                $message = 'Password lama tidak cocok.';
            }
        }
        $stmt->close();
    }

    header('Location: admin.php?message=' . urlencode($message));
    exit();
}

if ($is_master_admin && isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

if ($is_master_admin && isset($_POST['change_user_password'])) {
    $user_id = intval($_POST['user_id']);
    $new_password = trim($_POST['new_password']);
    if ($new_password !== '') {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();
        $stmt->close();
    }
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

    <?php if ($is_master_admin): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-3">Ganti Password Master Admin</h5>
            <?php if ($message): ?>
                <div class="alert alert-info">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Password Lama</label>
                    <div class="input-group">
                        <input type="password" name="current_password" id="currentPassword" class="form-control" placeholder="Masukkan password lama" required>
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordInput('currentPassword', this)">Tampilkan</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
                    <div class="input-group">
                        <input type="password" name="new_password" id="newPasswordMaster" class="form-control" placeholder="Masukkan password baru" required>
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordInput('newPasswordMaster', this)">Tampilkan</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirmPasswordMaster" class="form-control" placeholder="Ulangi password baru" required>
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordInput('confirmPasswordMaster', this)">Tampilkan</button>
                    </div>
                </div>
                <button type="submit" name="change_master_password" class="btn btn-success">Simpan Password</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

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
                                        <div class="d-flex gap-2 flex-wrap">
                                            <form method="POST" class="d-flex gap-2">
                                                <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                                <select name="role" class="form-select form-select-sm">
                                                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                </select>
                                                <button type="submit" name="promote_user" class="btn btn-sm btn-outline-primary">Simpan</button>
                                            </form>
                                            <?php if ($is_master_admin): ?>
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#resetPassword<?php echo (int)$user['id']; ?>" aria-expanded="false" aria-controls="resetPassword<?php echo (int)$user['id']; ?>">Reset Password</button>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($is_master_admin): ?>
                                            <div class="collapse mt-2" id="resetPassword<?php echo (int)$user['id']; ?>">
                                                <form method="POST" class="d-flex gap-2 align-items-center">
                                                    <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                                    <div class="input-group input-group-sm">
                                                        <input type="password" name="new_password" id="newPassword<?php echo (int)$user['id']; ?>" class="form-control" placeholder="Password baru" required>
                                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordInput('newPassword<?php echo (int)$user['id']; ?>', this)">Tampilkan</button>
                                                    </div>
                                                    <button type="submit" name="change_user_password" class="btn btn-sm btn-success">Ubah</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
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
<script>
    function togglePasswordInput(inputId, button) {
        var input = document.getElementById(inputId);
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
            button.textContent = 'Sembunyikan';
        } else {
            input.type = 'password';
            button.textContent = 'Tampilkan';
        }
    }
</script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
