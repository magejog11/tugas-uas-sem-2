<?php
include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error_message = '';

if (isset($_POST['submit'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $category = in_array($_POST['category'] ?? '', $categories, true)
        ? $conn->real_escape_string($_POST['category'])
        : $conn->real_escape_string($categories[0]);
    $user_id = $_SESSION['user_id'];
    $image_path = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = $_FILES['image'];

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
                $error_message = 'Format gambar tidak didukung. Gunakan JPG, PNG, atau GIF.';
            } elseif ($upload['size'] > 5 * 1024 * 1024) {
                $error_message = 'Ukuran gambar maksimal 5MB.';
            } else {
                $extension = $allowed_mimes[$mime_type];
                $filename = uniqid('img_', true) . '.' . $extension;
                $destination = $upload_dir . $filename;

                if (!move_uploaded_file($upload['tmp_name'], $destination)) {
                    $error_message = 'Gagal mengunggah gambar. Coba lagi.';
                } else {
                    $image_path = $conn->real_escape_string($destination);
                }
            }
        } else {
            $error_message = 'Terjadi kesalahan saat mengunggah gambar.';
        }
    }

    if ($error_message === '') {
        $sql = "INSERT INTO topics (user_id, title, content, category" . ($image_path !== '' ? ', image_path' : '') . ") VALUES ('$user_id', '$title', '$content', '$category'" . ($image_path !== '' ? ", '$image_path'" : '') . ")";
        if ($conn->query($sql)) {
            header("Location: index.php");
            exit();
        } else {
            $error_message = "Gagal menyimpan topik: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Postingan - FORKOM</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(circle at top, #f5fdff 0%, #eef6fc 40%, #f8fbfd 100%);
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', 'Segoe UI', Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 24px 16px;
        }

        .fb-form-container {
            width: 100%;
            max-width: 720px;
            background-color: #ffffff;
            border-radius: 32px;
            box-shadow: 0 32px 80px rgba(15, 23, 42, 0.12);
            padding: 0;
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, 0.06);
        }

        .fb-header {
            padding: 30px 32px 26px;
            background: linear-gradient(135deg, #0a959c 0%, #0c8b90 48%, #146c86 100%);
            color: #ffffff;
            position: relative;
        }

        .fb-title {
            font-size: 30px;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 6px;
            letter-spacing: -0.04em;
        }

        .fb-subtitle {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.82);
            margin: 0;
            max-width: 680px;
            line-height: 1.6;
        }

        .fb-close-btn {
            position: absolute;
            right: 24px;
            top: 24px;
            background-color: rgba(255,255,255,0.18);
            color: #f8fafc;
            border-radius: 50%;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 22px;
            font-weight: 700;
            transition: background-color 0.2s ease;
        }

        .fb-close-btn:hover {
            background-color: rgba(255,255,255,0.3);
        }

        .fb-body {
            padding: 32px 34px 34px;
        }

        .fb-group {
            margin-bottom: 22px;
        }

        .fb-label {
            font-size: 12px;
            font-weight: 800;
            color: #475569;
            margin-bottom: 10px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.16em;
        }

        .fb-input,
        .fb-select,
        .fb-textarea {
            width: 100%;
            border-radius: 20px;
            border: 1px solid #d2dae8;
            background: #f8fafc;
            padding: 16px 18px;
            font-size: 15px;
            color: #102a43;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            outline: none;
        }

        .fb-input:focus,
        .fb-select:focus,
        .fb-textarea:focus {
            border-color: #0a959c;
            box-shadow: 0 0 0 4px rgba(10, 149, 156, 0.12);
        }

        .fb-textarea {
            min-height: 170px;
            resize: vertical;
            line-height: 1.8;
        }

        .fb-add-to-post {
            border: 1px solid #d2dae8;
            border-radius: 22px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            background: #f8fbff;
            margin-bottom: 24px;
        }

        .fb-add-text {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }

        .file-input-wrapper {
            position: relative;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            padding: 12px 16px;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            height: 48px;
            min-width: 160px;
            color: #0f172a;
            font-weight: 600;
        }

        .file-input-wrapper:hover {
            background-color: #eef6ff;
            border-color: #93c5fd;
        }

        .file-input-real {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-input-placeholder {
            font-size: 14px;
            color: #0a4591;
            pointer-events: none;
        }

        .btn-fb-submit {
            background: linear-gradient(135deg, #0A959C 0%, #087d83 100%);
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            padding: 16px 20px;
            border-radius: 24px;
            border: none;
            width: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }

        .btn-fb-submit:hover {
            background: linear-gradient(135deg, #087d83 0%, #066468 100%);
            transform: translateY(-2px);
            box-shadow: 0 18px 36px rgba(8, 125, 131, 0.22);
        }

        .alert-fb-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border-radius: 18px;
            font-size: 14px;
            padding: 14px 16px;
            margin-bottom: 20px;
            font-weight: 700;
            text-align: center;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>

    <div class="fb-form-container">
        
        <div class="fb-header">
            <h2 class="fb-title">Buat Postingan</h2>
            <a href="index.php" class="fb-close-btn" title="Batalkan">×</a>
        </div>

        <div class="fb-body">
            <?php if ($error_message): ?>
                <div class="alert-fb-danger">
                    ⚠️ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                
                <div class="fb-group">
                    <label class="fb-label">Judul Postingan</label>
                    <input type="text" name="title" class="fb-input" placeholder="Masukkan judul utama Anda..." required autocomplete="off">
                </div>

                <div class="fb-group">
                    <label class="fb-label">Pilih Kategori</label>
                    <select name="category" class="fb-select" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Kolom Isi Mengadopsi Pola Status Facebook -->
                <div class="fb-group mb-1">
                    <textarea name="content" class="fb-textarea" placeholder="Apa yang Anda pikirkan?" required></textarea>
                </div>

                <!-- Kotak Aksi Penambahan Media Foto ala Facebook Toolbar -->
                <div class="fb-add-to-post">
                    <span class="fb-add-text">Tambahkan ke postingan Anda</span>
                    <div class="file-input-wrapper">
                        <span class="file-input-placeholder" id="file-label">Foto</span>
                        <input type="file" name="image" id="image-input" class="file-input-real" accept="image/*" onchange="updateFileName()">
                    </div>
                </div>
                
                <button type="submit" name="submit" class="btn-fb-submit">Kirim</button>
            </form>
        </div>
        
    </div>

    <script>
        function updateFileName() {
            const input = document.getElementById('image-input');
            const label = document.getElementById('file-label');
            if (input.files && input.files.length > 0) {
                label.innerText = '✓ Terpilih';
            } else {
                label.innerText = ' Foto';
            }
        }
    </script>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>