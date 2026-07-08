<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../config.php");

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Ambil data admin
$user_id = $_SESSION['user_id'];

$result = mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'");
$admin = mysqli_fetch_assoc($result);

if (!$admin || ($admin['role'] != 'admin' && $admin['is_admin'] != 1)) {
    header("Location: ../index.php");
    exit();
}

// Pencarian
$search = "";

if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);

    $users = mysqli_query($conn,"
        SELECT *
        FROM users
        WHERE username LIKE '%$search%'
        OR email LIKE '%$search%'
        ORDER BY id DESC
    ");
} else {

    $users = mysqli_query($conn,"
        SELECT *
        FROM users
        ORDER BY id DESC
    ");

}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Manajemen User</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>

body{
    background:#f5f7fb;
}

.sidebar{
    width:250px;
    height:100vh;
    position:fixed;
    background:#212529;
}

.sidebar h3{
    color:white;
    padding:20px;
}

.sidebar a{
    display:block;
    color:white;
    text-decoration:none;
    padding:15px 20px;
}

.sidebar a:hover{
    background:#0d6efd;
}

.content{
    margin-left:250px;
    padding:30px;
}

.card{
    border:none;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,.08);
}

</style>

</head>

<body>

<div class="sidebar">

<h3>ADMIN</h3>

<a href="dashboard.php">
<i class="bi bi-speedometer2"></i>
Dashboard
</a>

<a href="users.php">
<i class="bi bi-people"></i>
Users
</a>

<a href="topics.php">
<i class="bi bi-chat-left-text"></i>
Topik
</a>

<a href="../logout.php">
<i class="bi bi-box-arrow-right"></i>
Logout
</a>

</div>

<div class="content">

<div class="card">

<div class="card-header d-flex justify-content-between">

<h4>Manajemen User</h4>

<form method="GET">

<div class="input-group">

<input
type="text"
name="search"
class="form-control"
placeholder="Cari user..."
value="<?= htmlspecialchars($search) ?>">

<button class="btn btn-primary">

<i class="bi bi-search"></i>

</button>

</div>

</form>

</div>

<div class="card-body">

<table class="table table-hover table-bordered align-middle">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>Username</th>
<th>Email</th>
<th>Role</th>
<th>Tanggal</th>
<th width="180">Aksi</th>

</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($users)){ ?>

<tr>

<td><?= $row['id']; ?></td>

<td><?= htmlspecialchars($row['username']); ?></td>

<td><?= htmlspecialchars($row['email']); ?></td>

<td>

<?php

if($row['role']=="admin"){
    echo '<span class="badge bg-success">Admin</span>';
}else{
    echo '<span class="badge bg-secondary">User</span>';
}

?>

</td>

<td><?= $row['created_at']; ?></td>

<td>

<a
href="edit_user.php?id=<?= $row['id']; ?>"
class="btn btn-warning btn-sm">

<i class="bi bi-pencil"></i>

</a>

<a
href="delete_user.php?id=<?= $row['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Hapus user ini?')">

<i class="bi bi-trash"></i>

</a>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

</body>

</html>