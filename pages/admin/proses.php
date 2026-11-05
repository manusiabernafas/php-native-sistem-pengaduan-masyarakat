<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('aduan');
$u = user();
$a = q('SELECT * FROM aduan WHERE id = ?', [(int) ($_POST['id'] ?? 0)])->fetch();
if (!$a || (!is_admin() && $a['unit_id'] != $u['unit_id']) || in_array($a['status'], ['selesai', 'ditolak'], true)) redirect('aduan');

$isi = trim($_POST['isi'] ?? '');
$status = $_POST['status'] ?? '';
$internal = !empty($_POST['internal']) ? 1 : 0;
if ($isi === '') { flash('Tanggapan wajib diisi.', 'error'); redirect('aduan.detail', ['id' => $a['id']]); }
if ($status !== '' && (!isset(STATUS[$status]) || (!is_admin() && in_array($status, ['diverifikasi', 'ditolak'], true)))) $status = '';
if ($internal) $status = ''; // catatan internal tidak mengubah status
$prioritas = in_array($_POST['prioritas'] ?? '', ['rendah', 'sedang', 'tinggi'], true) ? $_POST['prioritas'] : $a['prioritas'];
$unitId = is_admin() ? ((int) ($_POST['unit_id'] ?? 0) ?: $a['unit_id']) : $a['unit_id'];

db()->beginTransaction();
q('INSERT INTO tanggapan (aduan_id, user_id, status, isi, internal) VALUES (?,?,?,?,?)', [$a['id'], $u['id'], $status ?: $a['status'], $isi, $internal]);
q('UPDATE aduan SET status = ?, prioritas = ?, unit_id = ?, selesai_at = ? WHERE id = ?', [
    $status ?: $a['status'], $prioritas, $unitId, in_array($status, ['selesai', 'ditolak'], true) ? date('Y-m-d H:i:s') : null, $a['id'],
]);
db()->commit();
flash('Tindak lanjut tersimpan' . ($status ? ', status menjadi ' . STATUS[$status][0] : '') . '.');
redirect('aduan.detail', ['id' => $a['id']]);
