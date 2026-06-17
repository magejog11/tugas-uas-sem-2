<?php
include 'config.php';
session_start();

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
        $desired_width = isset($_POST['width']) ? max(0, intval($_POST['width'])) : 0;
        $desired_height = isset($_POST['height']) ? max(0, intval($_POST['height'])) : 0;

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
            } elseif ($upload['size'] > 2 * 1024 * 1024) {
                $error_message = 'Ukuran gambar maksimal 2MB.';
            } else {
                $extension = $allowed_mimes[$mime_type];
                $filename = uniqid('img_', true) . '.' . $extension;
                $destination = $upload_dir . $filename;

                if ($desired_width > 0 || $desired_height > 0) {
                    $source_image = null;
                    switch ($mime_type) {
                        case 'image/jpeg':
                        case 'image/jpg':
                            $source_image = imagecreatefromjpeg($upload['tmp_name']);
                            break;
                        case 'image/png':
                            $source_image = imagecreatefrompng($upload['tmp_name']);
                            break;
                        case 'image/gif':
                            $source_image = imagecreatefromgif($upload['tmp_name']);
                            break;
                    }

                    if (!$source_image) {
                        $error_message = 'Gagal memproses gambar.';
                    } else {
                        $orig_width = imagesx($source_image);
                        $orig_height = imagesy($source_image);

                        if ($desired_width <= 0) {
                            $desired_width = (int) round($orig_width * ($desired_height / $orig_height));
                        }
                        if ($desired_height <= 0) {
                            $desired_height = (int) round($orig_height * ($desired_width / $orig_width));
                        }

                        $desired_width = min(2000, max(1, $desired_width));
                        $desired_height = min(2000, max(1, $desired_height));

                        $resized_image = imagecreatetruecolor($desired_width, $desired_height);
                        if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
                            imagecolortransparent($resized_image, imagecolorallocatealpha($resized_image, 0, 0, 0, 127));
                            imagealphablending($resized_image, false);
                            imagesavealpha($resized_image, true);
                        }

                        imagecopyresampled(
                            $resized_image,
                            $source_image,
                            0, 0, 0, 0,
                            $desired_width,
                            $desired_height,
                            $orig_width,
                            $orig_height
                        );

                        switch ($mime_type) {
                            case 'image/jpeg':
                            case 'image/jpg':
                                $save_result = imagejpeg($resized_image, $destination, 85);
                                break;
                            case 'image/png':
                                $save_result = imagepng($resized_image, $destination);
                                break;
                            case 'image/gif':
                                $save_result = imagegif($resized_image, $destination);
                                break;
                            default:
                                $save_result = false;
                        }

                        imagedestroy($source_image);
                        imagedestroy($resized_image);

                        if (!$save_result) {
                            $error_message = 'Gagal menyimpan gambar setelah perubahan ukuran.';
                        } else {
                            $image_path = $conn->real_escape_string($destination);
                        }
                    }
                } else {
                    if (!move_uploaded_file($upload['tmp_name'], $destination)) {
                        $error_message = 'Gagal mengunggah gambar. Coba lagi.';
                    } else {
                        $image_path = $conn->real_escape_string($destination);
                    }
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
<html>
<head>
    <title>Buat Topik Baru</title>
      <!-- Bootstrap 5 CSS CDN -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <h2>Buat Topik Diskusi Baru</h2>
    <?php if ($error_message): ?>
        <div style="color:red; margin-bottom:1rem;"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    <form method="POST" action="" enctype="multipart/form-data">
        <label>Judul Topik:</label><br>
        <input type="text" name="title" required><br><br>

        <label>Kategori:</label><br>
        <select name="category" required>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Gambar (opsional):</label><br>
        <input type="file" name="image" accept="image/*"><br><br>

        <label>Atur lebar (px, kosong untuk asli):</label><br>
        <input type="number" name="width" min="1" max="2000" placeholder="Contoh: 800"><br><br>

        <label>Atur tinggi (px, kosong untuk asli):</label><br>
        <input type="number" name="height" min="1" max="2000" placeholder="Contoh: 600"><br><br>
        
        <label>Isi Diskusi:</label><br>
        <textarea name="content" rows="5" required></textarea><br><br>
        
        <button type="submit" name="submit">Publikasikan</button>
    </form>
    <br>
    <a href="index.php">Kembali ke Beranda</a>
</body>
</html>
