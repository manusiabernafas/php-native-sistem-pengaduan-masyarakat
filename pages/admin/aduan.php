<?php
$u = user();
$status = $_GET['status'] ?? 'aktif';
$cari = trim($_GET['cari'] ?? '');
$kat = (int) ($_GET['kategori'] ?? 0);

$base = ['1=1'];
$bp = [];
if (!is_admin()) { $base[] = 'a.unit_id = ?'; $bp[] = (int) $u['unit_id']; }   // petugas hanya melihat aduan unitnya
if ($cari !== '') { $base[] = '(a.kode LIKE ? OR a.judul LIKE ? OR a.lokasi LIKE ?)'; array_push($bp, "%$cari%", "%$cari%", "%$cari%"); }
if ($kat) { $base[] = 'a.kategori_id = ?'; $bp[] = $kat; }

$hitung = q('SELECT a.status, COUNT(*) n FROM aduan a WHERE ' . implode(' AND ', $base) . ' GROUP BY a.status', $bp)->fetchAll(PDO::FETCH_KEY_PAIR);
$where = $base;
$params = $bp;
if ($status === 'aktif') $where[] = "a.status IN ('baru','diverifikasi','diproses')";
elseif (isset(STATUS[$status])) { $where[] = 'a.status = ?'; $params[] = $status; }

$rows = q('SELECT a.*, k.nama kategori, k.sla_hari, n.nama unit FROM aduan a LEFT JOIN kategori k ON k.id = a.kategori_id LEFT JOIN unit n ON n.id = a.unit_id
           WHERE ' . implode(' AND ', $where) . " ORDER BY FIELD(a.prioritas,'tinggi','sedang','rendah'), a.created_at DESC LIMIT 200", $params)->fetchAll();
$kategori = q('SELECT * FROM kategori ORDER BY nama')->fetchAll();

$tabs = ['aktif' => ['Perlu tindakan', ($hitung['baru'] ?? 0) + ($hitung['diverifikasi'] ?? 0) + ($hitung['diproses'] ?? 0)], 'baru' => ['Baru', $hitung['baru'] ?? 0],
    'diproses' => ['Diproses', $hitung['diproses'] ?? 0], 'selesai' => ['Selesai', $hitung['selesai'] ?? 0], 'ditolak' => ['Ditolak', $hitung['ditolak'] ?? 0], 'semua' => ['Semua', array_sum($hitung)]];
$title = 'Kotak Aduan';
require __DIR__ . '/../../includes/header.php';
?>
<div class="panel">
    <div class="panel-head">
        <div class="tabs"><?php foreach ($tabs as $k => [$l, $n]): ?><a class="<?= $status === $k ? 'on' : '' ?>" href="<?= url('aduan', ['status' => $k, 'cari' => $cari, 'kategori' => $kat]) ?>"><?= $l ?><span><?= $n ?></span></a><?php endforeach; ?></div>
        <form class="filters" method="get">
            <input type="hidden" name="page" value="aduan"><input type="hidden" name="status" value="<?= e($status) ?>">
            <select name="kategori" onchange="this.form.submit()"><option value="0">Semua kategori</option><?php foreach ($kategori as $k): ?><option value="<?= $k['id'] ?>" <?= $kat === (int) $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option><?php endforeach; ?></select>
            <input type="search" name="cari" value="<?= e($cari) ?>" placeholder="Tiket, judul, lokasi">
        </form>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>Tiket</th><th>Laporan</th><th>Unit</th><th>Prioritas</th><th>Umur</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $a): $late = lewat_sla($a); ?>
            <tr onclick="location.href='<?= url('aduan.detail', ['id' => $a['id']]) ?>'" style="cursor:pointer">
                <td class="mono"><?= e($a['kode']) ?></td>
                <td><strong><?= e($a['judul']) ?></strong><small><?= e($a['kategori']) ?> · <?= e($a['lokasi']) ?><?= $a['foto'] ? ' · ada foto' : '' ?></small></td>
                <td><?= e($a['unit'] ?? '-') ?></td>
                <td><span class="pri pri-<?= $a['prioritas'] ?>"><?= e($a['prioritas']) ?></span></td>
                <td class="<?= $late ? 'sla-late' : '' ?>"><?= umur_hari($a) ?> hari<small><?= $late ? 'lewat SLA ' . $a['sla_hari'] . ' hari' : 'SLA ' . $a['sla_hari'] . ' hari' ?></small></td>
                <td><?= status_badge($a['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="empty">Tidak ada aduan.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
