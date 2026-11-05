<?php
$u = user();
$baru = (int) q("SELECT COUNT(*) FROM aduan WHERE status = 'baru'")->fetchColumn();
$menu = [
    ['dashboard', 'Ringkasan', 'home', 0],
    ['aduan', 'Aduan', 'inbox', $baru],
    ['laporan', 'Laporan', 'chart', 0],
];
if (is_admin()) $menu[] = ['master', 'Master', 'settings', 0];
$current = explode('.', $_GET['page'] ?? 'dashboard')[0];
$flash = flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Ringkasan') ?> · <?= e(setting()['nama_layanan']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="layout">
    <aside class="rail" id="rail">
        <a class="logo" href="<?= url('dashboard') ?>" title="<?= e(setting()['nama_layanan']) ?>"><?= icon('megaphone', 22) ?></a>
        <?php foreach ($menu as [$key, $label, $ic, $count]): ?>
            <a href="<?= url($key) ?>" class="<?= $current === $key ? 'active' : '' ?>"><?= icon($ic, 20) ?><?= $label ?><?php if ($count): ?><span class="dot"><?= $count ?></span><?php endif; ?></a>
        <?php endforeach; ?>
        <div class="grow"></div>
        <a href="<?= url('beranda') ?>" target="_blank"><?= icon('external', 20) ?>Portal</a>
        <a href="<?= url('logout') ?>"><?= icon('logout', 20) ?>Keluar</a>
    </aside>
    <main class="main">
        <header class="topbar">
            <button class="icon-btn menu-toggle" onclick="document.getElementById('rail').classList.toggle('open')"><?= icon('menu') ?></button>
            <div class="grow"><div class="crumb"><?= e(setting()['nama_layanan']) ?> · Panel Petugas</div><h1><?= e($title ?? 'Ringkasan') ?></h1></div>
            <div class="topbar-actions"><?= $actions ?? '' ?></div>
            <div class="who"><span class="avatar"><?= e(strtoupper(substr($u['nama'], 0, 1))) ?></span>
                <div><strong><?= e($u['nama']) ?></strong><small><?= is_admin() ? 'Admin' : 'Petugas · ' . e($u['unit_nama'] ?? '') ?></small></div></div>
        </header>
        <div class="content">
            <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
