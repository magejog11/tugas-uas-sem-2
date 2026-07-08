<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$admin_result = $stmt->get_result();
$admin = $admin_result->fetch_assoc();
$stmt->close();

if (!$admin || ($admin['role'] !== 'admin' && $admin['is_admin'] != 1)) {
    header('Location: ../index.php');
    exit();
}

$error_message = '';
$success_message = '';
$has_email_column = false;
$email_check = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
if ($email_check && $email_check->num_rows > 0) {
    $has_email_column = true;
}

$user_to_edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_to_edit_id <= 0) {
    header('Location: users.php');
    exit();
}

$select_fields = ['id', 'username', 'role', 'is_admin'];
if ($has_email_column) {
    $select_fields[] = 'email';
}

$stmt = $conn->prepare('SELECT ' . implode(', ', $select_fields) . ' FROM users WHERE id = ?');
$stmt->bind_param('i', $user_to_edit_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$stmt->close();

if ($user_data && !$has_email_column) {
    $user_data['email'] = '';
}

if (!$user_data) {
    $error_message = 'User tidak ditemukan.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_data) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'user');

    if ($username === '' || ($has_email_column && $email === '')) {
        $error_message = $has_email_column ? 'Username dan email wajib diisi.' : 'Username wajib diisi.';
    } else {
        $duplicate_query = 'SELECT id FROM users WHERE username = ?';
        $duplicate_params = [$username];
        $duplicate_types = 's';

        if ($has_email_column && $email !== '') {
            $duplicate_query .= ' OR email = ?';
            $duplicate_params[] = $email;
            $duplicate_types .= 's';
        }

        $duplicate_query .= ' AND id != ? LIMIT 1';
        $duplicate_params[] = $user_to_edit_id;
        $duplicate_types .= 'i';

        $check_stmt = $conn->prepare($duplicate_query);
        $check_stmt->bind_param($duplicate_types, ...$duplicate_params);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $duplicate = $check_result->fetch_assoc();
        $check_stmt->close();

        if ($duplicate) {
            $error_message = $has_email_column ? 'Username atau email sudah dipakai user lain.' : 'Username sudah dipakai user lain.';
        } else {
            $is_admin_value = ($role === 'admin') ? 1 : 0;
            if ($has_email_column) {
                $update_stmt = $conn->prepare('UPDATE users SET username = ?, email = ?, role = ?, is_admin = ? WHERE id = ?');
                $update_stmt->bind_param('ssssi', $username, $email, $role, $is_admin_value, $user_to_edit_id);
            } else {
                $update_stmt = $conn->prepare('UPDATE users SET username = ?, role = ?, is_admin = ? WHERE id = ?');
                $update_stmt->bind_param('sssi', $username, $role, $is_admin_value, $user_to_edit_id);
            }

            if ($update_stmt->execute()) {
                $success_message = 'Data user berhasil diperbarui.';
                $user_data['username'] = $username;
                $user_data['email'] = $email;
                $user_data['role'] = $role;
                $user_data['is_admin'] = $is_admin_value;
            } else {
                $error_message = 'Gagal memperbarui data user.';
            }

            $update_stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,.08); }
        .sidebar { width: 250px; height: 100vh; position: fixed; background: #212529; }
        .sidebar a { display: block; color: white; text-decoration: none; padding: 15px 20px; }
        .sidebar a:hover { background: #0d6efd; }
        .content { margin-left: 250px; padding: 30px; }
    </style>
</head>
<body>
<div class="sidebar">
    <h3 class="text-white p-3">ADMIN</h3>
    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="users.php"><i class="bi bi-people"></i> Users</a>
    <a href="topics.php"><i class="bi bi-chat-left-text"></i> Topik</a>
    <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="content">
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Edit User</h4>
            <a href="users.php" class="btn btn-secondary btn-sm">Kembali</a>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if ($user_data): ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($user_data['username'] ?? '') ?>" required>
                </div>

                <?php if ($has_email_column): ?>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required>
                </div>
                <?php else: ?>
                <div class="alert alert-info py-2">Kolom email tidak tersedia di tabel users, jadi form ini hanya mengedit username dan role.</div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select class="form-select" name="role">
                        <option value="user" <?= ($user_data['role'] === 'admin') ? '' : 'selected' ?>>User</option>
                        <option value="admin" <?= ($user_data['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
