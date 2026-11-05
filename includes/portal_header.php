<?php $set = setting(); $current = $_GET['page'] ?? 'beranda'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Lapor') ?> · <?= e($set['nama_layanan']) ?> <?= e($set['nama_instansi']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="portal-top">
    <a class="logo" href="<?= url('beranda') ?>"><?= icon('megaphone', 20) ?></a>
    <div><strong><?= e($set['nama_layanan']) ?></strong><small><?= e($set['nama_instansi']) ?></small></div>
    <nav>
        <a href="<?= url('beranda') ?>" class="<?= $current === 'beranda' ? 'on' : '' ?>">Buat Laporan</a>
        <a href="<?= url('lacak') ?>" class="<?= $current === 'lacak' ? 'on' : '' ?>">Lacak Tiket</a>
        <a href="<?= url('transparansi') ?>" class="<?= $current === 'transparansi' ? 'on' : '' ?>">Transparansi</a>
        <a href="<?= url('login') ?>">Petugas</a>
    </nav>
</header>
<div class="portal">
