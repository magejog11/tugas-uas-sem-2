<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$res = mysqli_query($conn, "SELECT * FROM users WHERE id={$user_id} LIMIT 1");
$admin = $res ? mysqli_fetch_assoc($res) : null;

if (!$admin || ($admin['role'] !== 'admin' && $admin['is_admin'] != 1)) {
    header('Location: ../index.php');
    exit();
}

// Stats
$totalUsers = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"))['total'];
$totalTopics = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM topics"))['total'];
$totalComments = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM comments"))['total'];
$likeQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM topic_likes");
$totalLikes = $likeQuery ? (int) mysqli_fetch_assoc($likeQuery)['total'] : 0;

$latestTopics = mysqli_query($conn, "SELECT topics.*, users.username FROM topics LEFT JOIN users ON users.id = topics.user_id ORDER BY topics.created_at DESC LIMIT 8");
?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
      :root{--bg:#f6f9fb}
      body{background:var(--bg);font-family:Inter, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial}
      .sidebar{width:230px;position:fixed;inset:0 auto 0 0;background:#08292b;color:#fff;padding:24px}
      .content{margin-left:230px;padding:28px}
      .stat-card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 6px 18px rgba(8,16,24,0.06)}
      .topic-thumb{width:64px;height:48px;object-fit:cover;border-radius:8px}
      @media (max-width:900px){.sidebar{position:relative;width:100%;height:auto}.content{margin-left:0}}
    </style>
  </head>
  <body>

    <aside class="sidebar">
      <h4 class="mb-1">Panel Admin</h4>
      <div class="text-muted small mb-3">Selamat datang, <?= htmlspecialchars($admin['username']) ?></div>
      <nav class="d-block">
        <a href="dashboard.php" class="d-block text-white py-2"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        <a href="users.php" class="d-block text-white py-2"><i class="bi bi-people me-2"></i>Users</a>
        <a href="topics.php" class="d-block text-white py-2"><i class="bi bi-chat-left-text me-2"></i>Topics</a>
        <a href="delete_topic.php" class="d-block text-white py-2"><i class="bi bi-trash me-2"></i>Hapus Topik</a>
        <a href="../logout.php" class="d-block text-white py-2 mt-3"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
      </nav>
    </aside>

    <main class="content">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-0">Dashboard</h2>
          <small class="text-muted">Ringkasan aktivitas forum</small>
        </div>
        <div>
          <a href="../index.php" class="btn btn-outline-secondary btn-sm">Lihat situs</a>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <small class="text-muted">Pengguna</small>
            <h3 class="mt-1"><?= $totalUsers ?></h3>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <small class="text-muted">Topik</small>
            <h3 class="mt-1"><?= $totalTopics ?></h3>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <small class="text-muted">Komentar</small>
            <h3 class="mt-1"><?= $totalComments ?></h3>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <small class="text-muted">Like</small>
            <h3 class="mt-1"><?= $totalLikes ?></h3>
          </div>
        </div>
      </div>

      <div class="card p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Topik Terbaru</h5>
          <a href="topics.php" class="btn btn-sm btn-outline-primary">Lihat semua</a>
        </div>

        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr class="text-muted small">
                <th>#</th>
                <th>Judul</th>
                <th>Penulis</th>
                <th>Tanggal</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php $i=1; while($row = mysqli_fetch_assoc($latestTopics)): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td>
                    <div class="d-flex align-items-center">
                      <?php if (!empty($row['image_path'])): ?>
                        <img src="../<?= htmlspecialchars($row['image_path']) ?>" class="topic-thumb me-3" alt="thumb">
                      <?php endif; ?>
                      <div>
                        <div class="fw-bold"><?= htmlspecialchars($row['title']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars(substr($row['content'] ?? '', 0, 80)) ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($row['username'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($row['created_at']) ?></td>
                  <td class="text-end">
                    <a href="../view_topic.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-secondary">Buka</a>
                    <a href="topics.php" class="btn btn-sm btn-outline-danger ms-1">Hapus</a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <footer class="text-center text-muted small mt-4">Panel Admin — FORKOM UIMY</footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
