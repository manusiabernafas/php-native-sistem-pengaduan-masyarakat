<?php
$dari = $_GET['dari'] ?? date('Y-m-d', strtotime('-90 days'));
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$rentang = [$dari, $sampai . ' 23:59:59'];
$perUnit = q("SELECT n.nama, COUNT(a.id) total, SUM(a.status = 'selesai') selesai, SUM(a.status = 'ditolak') ditolak,
              ROUND(AVG(CASE WHEN a.status = 'selesai' THEN TIMESTAMPDIFF(HOUR, a.created_at, a.selesai_at) / 24 END), 1) rata,
              SUM(a.status = 'selesai' AND TIMESTAMPDIFF(HOUR, a.created_at, a.selesai_at) <= k.sla_hari * 24) tepat, ROUND(AVG(a.rating), 1) rating
              FROM unit n LEFT JOIN aduan a ON a.unit_id = n.id AND a.created_at BETWEEN ? AND ? LEFT JOIN kategori k ON k.id = a.kategori_id
              GROUP BY n.id ORDER BY total DESC", $rentang)->fetchAll();
$perKat = q("SELECT k.nama, k.sla_hari, COUNT(a.id) total, SUM(a.status = 'selesai') selesai,
             ROUND(AVG(CASE WHEN a.status = 'selesai' THEN TIMESTAMPDIFF(HOUR, a.created_at, a.selesai_at) / 24 END), 1) rata
             FROM kategori k LEFT JOIN aduan a ON a.kategori_id = k.id AND a.created_at BETWEEN ? AND ? GROUP BY k.id ORDER BY total DESC", $rentang)->fetchAll();

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"aduan-$dari-sd-$sampai.csv\"");
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Tiket', 'Tanggal', 'Kategori', 'Unit', 'Judul', 'Lokasi', 'Prioritas', 'Status', 'Selesai', 'Rating'], ';', '"', '');
    foreach (q('SELECT a.*, k.nama kat, n.nama unit FROM aduan a LEFT JOIN kategori k ON k.id = a.kategori_id LEFT JOIN unit n ON n.id = a.unit_id WHERE a.created_at BETWEEN ? AND ? ORDER BY a.created_at', $rentang) as $r) {
        fputcsv($out, [$r['kode'], $r['created_at'], $r['kat'], $r['unit'], $r['judul'], $r['lokasi'], $r['prioritas'], $r['status'], $r['selesai_at'], $r['rating']], ';', '"', '');
    }
    exit;
}
$title = 'Laporan Kinerja';
$actions = '<a class="btn" href="' . url('laporan', ['dari' => $dari, 'sampai' => $sampai, 'export' => 'csv']) . '">Ekspor CSV</a><button class="btn btn-primary" onclick="window.print()">' . icon('printer', 16) . ' Cetak</button>';
require __DIR__ . '/../../includes/header.php';
?>
<form class="panel" method="get" style="margin-bottom:18px"><div class="panel-body filters">
    <input type="hidden" name="page" value="laporan"><label style="margin:0">Dari</label><input type="date" name="dari" value="<?= e($dari) ?>">
    <label style="margin:0">Sampai</label><input type="date" name="sampai" value="<?= e($sampai) ?>"><button class="btn btn-primary">Tampilkan</button>
</div></form>
<div class="panel">
    <div class="panel-head"><h2>Kinerja per unit</h2><span class="muted"><?= tgl($dari) ?> – <?= tgl($sampai) ?></span></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Unit</th><th>Masuk</th><th>Selesai</th><th>Ditolak</th><th>Rata-rata</th><th>Tepat SLA</th><th>Kepuasan</th></tr></thead>
        <tbody><?php foreach ($perUnit as $r): $pct = $r['selesai'] ? round($r['tepat'] / $r['selesai'] * 100) : 0; ?>
            <tr><td><strong><?= e($r['nama']) ?></strong></td><td><?= (int) $r['total'] ?></td><td><?= (int) $r['selesai'] ?></td><td><?= (int) $r['ditolak'] ?></td>
                <td><?= $r['rata'] !== null ? e($r['rata']) . ' hari' : '-' ?></td>
                <td><span class="badge badge-<?= $pct >= 80 ? 'green' : ($pct >= 60 ? 'amber' : 'red') ?>"><?= $pct ?>%</span></td>
                <td><?= $r['rating'] ? '<span style="color:var(--amber)">★</span> ' . e($r['rating']) : '-' ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
</div>
<div class="panel mt">
    <div class="panel-head"><h2>Per kategori</h2></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Kategori</th><th>Target SLA</th><th>Masuk</th><th>Selesai</th><th>Rata-rata selesai</th></tr></thead>
        <tbody><?php foreach ($perKat as $r): ?>
            <tr><td><strong><?= e($r['nama']) ?></strong></td><td><?= (int) $r['sla_hari'] ?> hari</td><td><?= (int) $r['total'] ?></td><td><?= (int) $r['selesai'] ?></td>
                <td class="<?= $r['rata'] > $r['sla_hari'] ? 'sla-late' : '' ?>"><?= $r['rata'] !== null ? e($r['rata']) . ' hari' : '-' ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
