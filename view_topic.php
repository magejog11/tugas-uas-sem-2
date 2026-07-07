<?php
include 'config.php';
session_start();

$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$is_admin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'master_admin'], true);
$topic_id = intval($_GET['id']);
// Inisialisasi variabel error agar tidak muncul warning jika tidak ada error
$error_message = '';

$topic_id_to_react = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$reaction_to_apply = '';
if (isset($_GET['reaction_topic']) && in_array($_GET['reaction_topic'], ['like', 'dislike'], true)) {
    $reaction_to_apply = $_GET['reaction_topic'];
}

if ($topic_id_to_react > 0 && $reaction_to_apply !== '' && $current_user_id > 0 && $topic_id > 0) {
    $reaction_value = $conn->real_escape_string($reaction_to_apply);
    $existing_reaction = $conn->query("SELECT id, reaction FROM topic_likes WHERE topic_id = $topic_id AND user_id = $current_user_id");
    if ($existing_reaction && $existing_reaction->num_rows > 0) {
        $existing_row = $existing_reaction->fetch_assoc();
        if ($existing_row['reaction'] === $reaction_value) {
            $conn->query("DELETE FROM topic_likes WHERE topic_id = $topic_id AND user_id = $current_user_id");
        } else {
            $conn->query("UPDATE topic_likes SET reaction = '$reaction_value' WHERE topic_id = $topic_id AND user_id = $current_user_id");
        }
    } else {
        $conn->query("INSERT INTO topic_likes (topic_id, user_id, reaction) VALUES ($topic_id, $current_user_id, '$reaction_value')");
    }
    header("Location: view_topic.php?id=" . $topic_id);
    exit();
}

// 1. Ambil Data Topik
$topic_query = "SELECT topics.*, users.username, (SELECT COUNT(*) FROM topic_likes tl WHERE tl.topic_id = topics.id AND tl.reaction = 'like') AS like_count, (SELECT COUNT(*) FROM topic_likes tl WHERE tl.topic_id = topics.id AND tl.reaction = 'dislike') AS dislike_count, (SELECT tl.reaction FROM topic_likes tl WHERE tl.topic_id = topics.id AND tl.user_id = $current_user_id) AS user_reaction FROM topics JOIN users ON topics.user_id = users.id WHERE topics.id = $topic_id";
$topic_result = $conn->query($topic_query);
$topic = $topic_result->fetch_assoc();
$user_reaction = '';
if ($current_user_id > 0 && $topic) {
    $user_reaction = $topic['user_reaction'] ?? '';
}

if (!$topic) {
    die("Topik tidak ditemukan.");
}

// 2. Proses Simpan Balasan Baru Jika Form Dikirim (dengan dukungan gambar)
if (isset($_POST['submit_reply'])) {
    $reply_content = $conn->real_escape_string($_POST['reply_content']);
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
    $reply_image_path = '';

    if (isset($_FILES['reply_image']) && $_FILES['reply_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = $_FILES['reply_image'];
        if ($upload['error'] === UPLOAD_ERR_OK) {
            $allowed_mimes = [
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
            ];
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $upload['tmp_name']);
            finfo_close($file_info);

            if (!isset($allowed_mimes[$mime_type])) {
                $error_message = 'Format gambar balasan tidak didukung.';
            } elseif ($upload['size'] > 2 * 1024 * 1024) {
                $error_message = 'Ukuran gambar balasan maksimal 2MB.';
            } else {
                $extension = $allowed_mimes[$mime_type];
                $filename = uniqid('reply_', true) . '.' . $extension;
                $destination = $upload_dir . $filename;

                if (!move_uploaded_file($upload['tmp_name'], $destination)) {
                    $error_message = 'Gagal mengunggah gambar balasan.';
                } else {
                    $reply_image_path = $conn->real_escape_string($destination);
                }
            }
        } else {
            $error_message = 'Terjadi kesalahan saat mengunggah gambar balasan.';
        }
    }

    if ($error_message === '') {
        $insert_reply = "INSERT INTO replies (topic_id, user_id, content" . ($reply_image_path !== '' ? ', image_path' : '') . ") VALUES ('$topic_id', '$user_id', '$reply_content'" . ($reply_image_path !== '' ? ", '$reply_image_path'" : '') . ")";
        $conn->query($insert_reply);
        header("Location: view_topic.php?id=" . $topic_id);
        exit();
    }
}

// 3. Ambil Semua Balasan Komentar
$replies_query = "SELECT replies.*, users.username FROM replies JOIN users ON replies.user_id = users.id WHERE replies.topic_id = $topic_id ORDER BY replies.created_at ASC";
$replies_result = $conn->query($replies_query);
?>
<?php
// ... [Pertahankan logika PHP view_topic Anda di bagian paling atas sini] ...
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($topic['title']); ?></title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
<body class="bg-light">

    <div class="container my-5">
        <div class="row">
                <div class="col-md-8 mx-auto">
                <div class="d-flex justify-content-between mb-4">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">← Kembali ke Daftar Topik</a>
                    <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $topic['user_id'] || $is_admin)): ?>
                        <a href="delete_topic.php?id=<?php echo $topic['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus topik ini? Semua balasan juga akan dihapus.')">Hapus Topik</a>
                    <?php endif; ?>
                </div>

                <!-- Card Postingan Utama -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h1 class="h3 card-title text-dark fw-bold"><?php echo htmlspecialchars($topic['title']); ?></h1>
                        <div class="text-muted small mb-3">
                            Diposting oleh <span class="text-primary font-weight-bold"><?php echo htmlspecialchars($topic['username']); ?></span>
                            • <?php echo date('d M Y, H:i', strtotime($topic['created_at'])); ?>
                            <?php if (!empty($topic['category'])): ?>
                                <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($topic['category']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($topic['image_path'])): ?>
                            <div class="mb-4 text-center">
                                <img src="<?php echo htmlspecialchars($topic['image_path']); ?>" class="img-fluid rounded" alt="Gambar topik">
                            </div>
                        <?php endif; ?>
                        <hr class="text-muted">
                        <p class="card-text fs-5 text-secondary" style="line-height: 1.7;">
                            <?php echo nl2br(htmlspecialchars($topic['content'])); ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <?php if ($current_user_id > 0): ?>
                                <div class="d-flex gap-2">
                                    <form method="GET" action="view_topic.php" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $topic['id']; ?>">
                                        <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                                        <input type="hidden" name="reaction_topic" value="like">
                                        <button type="submit" class="btn btn-sm <?php echo $user_reaction === 'like' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                            👍 <?php echo $user_reaction === 'like' ? 'Disukai' : 'Suka'; ?> (<?php echo (int)$topic['like_count']; ?>)
                                        </button>
                                    </form>
                                    <form method="GET" action="view_topic.php" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $topic['id']; ?>">
                                        <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                                        <input type="hidden" name="reaction_topic" value="dislike">
                                        <button type="submit" class="btn btn-sm <?php echo $user_reaction === 'dislike' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                            👎 <?php echo $user_reaction === 'dislike' ? 'Didislike' : 'Dislike'; ?> (<?php echo (int)$topic['dislike_count']; ?>)
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="d-flex gap-2">
                                    <a href="login.php" class="btn btn-sm btn-outline-primary">👍 Suka (<?php echo (int)$topic['like_count']; ?>)</a>
                                    <a href="login.php" class="btn btn-sm btn-outline-danger">👎 Dislike (<?php echo (int)$topic['dislike_count']; ?>)</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Judul Kolom Komentar -->
                <h4 class="mb-3 text-secondary">Balasan (<?php echo $replies_result->num_rows; ?>)</h4>

                <!-- Loop Komentar -->
                <div class="mb-4">
                    <?php if ($replies_result->num_rows > 0): ?>
                        <?php while($reply = $replies_result->fetch_assoc()): ?>
                            <div class="card border-0 shadow-sm mb-2">
                                <div class="card-body py-3">
                                    <div class="d-flex justify-content-between mb-1 align-items-center">
                                        <div>
                                            <strong class="text-dark"><?php echo htmlspecialchars($reply['username']); ?></strong>
                                            <div><small class="text-muted"><?php echo date('d M Y, H:i', strtotime($reply['created_at'])); ?></small></div>
                                        </div>
                                        <div>
                                            <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $reply['user_id'] || $is_admin)): ?>
                                                <a href="delete_reply.php?id=<?php echo $reply['id']; ?>&topic_id=<?php echo $topic_id; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus balasan ini?')">Hapus</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-secondary"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                    <?php if (!empty($reply['image_path'])): ?>
                                        <div class="mt-2">
                                            <img src="<?php echo htmlspecialchars($reply['image_path']); ?>" class="img-fluid rounded" alt="Gambar balasan">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-light border text-center text-muted">Belum ada balasan.</div>
                    <?php endif; ?>
                </div>

                <!-- Form Balasan -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="mb-3">Kirim Balasan Anda</h5>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <textarea name="reply_content" class="form-control" rows="4" placeholder="Tulis komentar atau opini Anda di sini..." required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Gambar (opsional):</label>
                                    <input type="file" name="reply_image" accept="image/*" class="form-control">
                                </div>
                                <button type="submit" name="submit_reply" class="btn btn-primary">Kirim Komentar</button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0" role="alert">
                                Anda harus <a href="login.php" class="alert-link">login</a> terlebih dahulu untuk membalas diskusi ini.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
