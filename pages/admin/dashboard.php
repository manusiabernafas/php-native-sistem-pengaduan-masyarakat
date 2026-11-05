<?php
$u = user();
$scope = is_admin() ? '1=1' : 'a.unit_id = ' . (int) $u['unit_id'];
$k = q("SELECT SUM(status = 'baru') baru, SUM(status IN ('diverifikasi','diproses')) proses,
        SUM(status = 'selesai' AND selesai_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) selesai_bulan,
        ROUND(AVG(CASE WHEN status = 'selesai' AND created_at >= CURDATE() - INTERVAL 90 DAY THEN TIMESTAMPDIFF(HOUR, created_at, selesai_at) / 24 END), 1) rata,
        ROUND(AVG(CASE WHEN rating IS NOT NULL THEN rating END), 1) rating FROM aduan a WHERE $scope")->fetch();
$aktif = q("SELECT a.*, k.nama kategori, k.sla_hari FROM aduan a LEFT JOIN kategori k ON k.id = a.kategori_id WHERE $scope AND a.status IN ('baru','diverifikasi','diproses')")->fetchAll();
$telat = array_values(array_filter($aktif, 'lewat_sla'));
usort($telat, fn ($x, $y) => umur_hari($y) <=> umur_hari($x));

// 8 minggu terakhir: masuk vs selesai
$minggu = [];
for ($i = 7; $i >= 0; $i--) {
    $mulai = date('Y-m-d', strtotime("monday this week -$i week"));
    $akhir = date('Y-m-d', strtotime("$mulai +7 day"));
    $minggu[] = ['label' => date('d/m', strtotime($mulai)),
        'masuk' => (int) q("SELECT COUNT(*) FROM aduan a WHERE $scope AND created_at >= ? AND created_at < ?", [$mulai, $akhir])->fetchColumn(),
        'selesai' => (int) q("SELECT COUNT(*) FROM aduan a WHERE $scope AND selesai_at >= ? AND selesai_at < ? AND status = 'selesai'", [$mulai, $akhir])->fetchColumn()];
}
$maxW = max(1, ...array_map(fn ($w) => $w['masuk'] + $w['selesai'], $minggu));
$perKat = q("SELECT k.nama, COUNT(a.id) n FROM kategori k JOIN aduan a ON a.kategori_id = k.id WHERE $scope AND a.created_at >= CURDATE() - INTERVAL 30 DAY GROUP BY k.id ORDER BY n DESC")->fetchAll();
$maxK = max(1, ...array_column($perKat ?: [['n' => 1]], 'n'));

$title = 'Ringkasan';
require __DIR__ . '/../../includes/header.php';
?>
<div class="kpis">
    <div class="kpi"><div class="bar-top" style="background:var(--blue)"></div><small><?= icon('inbox', 14) ?> Aduan baru</small><strong><?= (int) $k['baru'] ?></strong><span>menunggu verifikasi</span></div>
    <div class="kpi"><div class="bar-top" style="background:var(--amber)"></div><small><?= icon('clock', 14) ?> Dalam proses</small><strong><?= (int) $k['proses'] ?></strong><span><?= count($telat) ?> lewat SLA</span></div>
    <div class="kpi"><div class="bar-top" style="background:var(--green)"></div><small><?= icon('check', 14) ?> Selesai bulan ini</small><strong><?= (int) $k['selesai_bulan'] ?></strong></div>
    <div class="kpi"><div class="bar-top" style="background:var(--teal)"></div><small>Rata-rata penyelesaian</small><strong><?= e($k['rata'] ?? '-') ?></strong><span>hari (90 hari)</span></div>
    <div class="kpi"><div class="bar-top" style="background:var(--purple)"></div><small>Kepuasan pelapor</small><strong><?= e($k['rating'] ?? '-') ?></strong><span>dari 5</span></div>
</div>
<div class="grid-2">
    <div class="panel">
        <div class="panel-head"><h2>Masuk vs selesai, 8 minggu</h2><div class="legend"><span><i style="background:var(--blue)"></i>Masuk</span><span><i style="background:var(--teal)"></i>Selesai</span></div></div>
        <div class="panel-body"><div class="bars">
            <?php foreach ($minggu as $w): ?>
                <div class="bar-col" title="<?= $w['masuk'] ?> masuk, <?= $w['selesai'] ?> selesai">
                    <div class="stack" style="height:<?= round(($w['masuk'] + $w['selesai']) / $maxW * 100) ?>%">
                        <span style="flex:<?= $w['masuk'] ?>;background:var(--blue)"></span><span style="flex:<?= $w['selesai'] ?>;background:var(--teal)"></span>
                    </div>
                    <small><?= $w['label'] ?></small>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>Kategori terbanyak</h2><span class="muted">30 hari</span></div>
        <div class="panel-body">
            <?php foreach ($perKat as $c): ?>
                <div class="hrow"><span><?= e($c['nama']) ?></span><div class="htrack"><span style="width:<?= round($c['n'] / $maxK * 100) ?>%"></span></div><span class="mono text-right"><?= $c['n'] ?></span></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<div class="panel mt">
    <div class="panel-head"><h2>Aduan aktif</h2><a href="<?= url('aduan') ?>">Buka kotak aduan</a></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Tiket</th><th>Laporan</th><th>Prioritas</th><th>Umur</th><th>Status</th></tr></thead>
        <tbody>
        <?php usort($aktif, fn ($x, $y) => [lewat_sla($y), umur_hari($y)] <=> [lewat_sla($x), umur_hari($x)]);
        foreach (array_slice($aktif, 0, 8) as $a): $late = lewat_sla($a); ?>
            <tr onclick="location.href='<?= url('aduan.detail', ['id' => $a['id']]) ?>'" style="cursor:pointer">
                <td class="mono"><?= e($a['kode']) ?></td><td><strong><?= e($a['judul']) ?></strong><small><?= e($a['kategori']) ?> · <?= e($a['lokasi']) ?></small></td>
                <td><span class="pri pri-<?= $a['prioritas'] ?>"><?= e($a['prioritas']) ?></span></td>
                <td class="<?= $late ? 'sla-late' : '' ?>"><?= umur_hari($a) ?> hari<small><?= $late ? 'lewat SLA' : 'SLA ' . $a['sla_hari'] . ' hari' ?></small></td>
                <td><?= status_badge($a['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$aktif): ?><tr><td colspan="5" class="empty">Semua aduan sudah ditangani.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
