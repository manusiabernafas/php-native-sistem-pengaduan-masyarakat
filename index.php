<?php
require __DIR__ . '/includes/bootstrap.php';

$routes = [
    // Portal publik (tanpa login)
    'beranda'      => 'pages/beranda.php',
    'lapor'        => 'pages/lapor.php',
    'lacak'        => 'pages/lacak.php',
    'transparansi' => 'pages/transparansi.php',
    'foto'         => 'pages/foto.php',
    // Panel petugas
    'login'        => 'pages/login.php',
    'logout'       => 'pages/logout.php',
    'dashboard'    => 'pages/admin/dashboard.php',
    'aduan'        => 'pages/admin/aduan.php',
    'aduan.detail' => 'pages/admin/detail.php',
    'aduan.proses' => 'pages/admin/proses.php',
    'laporan'      => 'pages/admin/laporan.php',
    'master'       => 'pages/admin/master.php',
];
$publik = ['beranda', 'lapor', 'lacak', 'transparansi', 'foto', 'login'];

$page = $_GET['page'] ?? 'beranda';
if (!isset($routes[$page])) {
    http_response_code(404);
    $page = 'beranda';
}
if (!in_array($page, $publik, true)) require_login();
verify_csrf();

require __DIR__ . '/' . $routes[$page];
