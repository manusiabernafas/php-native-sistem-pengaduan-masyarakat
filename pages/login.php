<?php
if (user()) redirect('dashboard');
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = q('SELECT u.*, n.nama unit_nama FROM users u LEFT JOIN unit n ON n.id = u.unit_id WHERE u.username = ?', [trim($_POST['username'] ?? '')])->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        unset($u['password']);
        $_SESSION['user'] = $u;
        redirect('dashboard');
    }
    $error = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk Petugas · <?= e(setting()['nama_layanan']) ?></title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<div class="login-page">
    <form method="post" class="panel login-box">
        <?= csrf_field() ?>
        <div class="logo"><?= icon('shield', 24) ?></div>
        <h1 style="font-size:22px">Panel Petugas</h1>
        <p class="muted" style="margin:6px 0 22px"><?= e(setting()['nama_layanan']) ?> · <?= e(setting()['nama_instansi']) ?></p>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <div class="field"><label>Username</label><input type="text" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>"></div>
        <div class="field"><label>Password</label><input type="password" name="password" required></div>
        <button class="btn btn-primary btn-block">Masuk</button>
        <p style="text-align:center;margin:16px 0 0"><a href="<?= url('beranda') ?>">Kembali ke portal warga</a></p>
        <div class="note">Akun demo (password <b>admin123</b>): <b>admin</b>, <b>dpu</b>, <b>dlh</b>, <b>dishub</b></div>
    </form>
</div>
</body>
</html>
