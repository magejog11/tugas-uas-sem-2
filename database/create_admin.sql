-- Buat akun admin baru
-- Ganti username dan password sesuai kebutuhan

INSERT INTO users (username, password, is_admin, role)
VALUES ('superadmin', '$2y$10$69fsa54KMDa7Vr8Me.EiMe9tcLf6gzpmoCkKjoKu98mw77z.jSm2O', 1, 'admin');

-- Jika akun sudah ada dan hanya perlu ditingkatkan ke admin:
-- UPDATE users SET is_admin = 1, role = 'admin' WHERE username = 'nama_user';
