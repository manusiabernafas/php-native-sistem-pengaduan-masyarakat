<?php
// Foto laporan disimpan di uploads/ (tidak bisa diakses langsung) dan disajikan lewat skrip ini
$f = basename($_GET['f'] ?? '');
$path = __DIR__ . '/../uploads/' . $f;
if ($f === '' || !q('SELECT 1 FROM aduan WHERE foto = ?', [$f])->fetchColumn() || !is_file($path)) {
    http_response_code(404);
    exit;
}
header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($path));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($path);
