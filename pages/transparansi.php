<?php
$stat = q("SELECT COUNT(*) total, SUM(status = 'selesai') selesai, SUM(status IN ('baru','diverifikasi','diproses')) proses,
           ROUND(AVG(CASE WHEN status = 'selesai' THEN TIMESTAMPDIFF(HOUR, created_at, selesai_at) / 24 END), 1) rata, ROUND(AVG(rating), 1) rating
           FROM aduan WHERE created_at >= CURDATE() - INTERVAL 90 DAY")->fetch();
$perKat = q("SELECT k.nama, COUNT(a.id) n, SUM(a.status = 'selesai') s FROM kategori k LEFT JOIN aduan a ON a.kategori_id = k.id
             AND a.created_at >= CURDATE() - INTERVAL 90 DAY GROUP BY k.id ORDER BY n DESC")->fetchAll();
$maxK = max(1, ...array_column($perKat, 'n'));
$terbaru = q("SELECT a.kode, a.judul, a.lokasi, a.created_at, a.selesai_at, k.nama kategori FROM aduan a LEFT JOIN kategori k ON k.id = a.kategori_id
              WHERE a.status = 'selesai' ORDER BY a.selesai_at DESC LIMIT 10")->fetchAll();
$title = 'Transparansi';
require __DIR__ . '/../includes/portal_header.php';
?>
<h1 style="font-size:30px">Transparansi penanganan aduan</h1>
<p class="muted" style="margin:6px 0 24px">Data 90 hari terakhir, diperbarui otomatis.</p>
<div class="kpis">
    <div class="kpi"><div class="bar-top" style="background:var(--blue)"></div><small>Laporan masuk</small><strong><?= (int) $stat['total'] ?></strong></div>
    <div class="kpi"><div class="bar-top" style="background:var(--green)"></div><small>Selesai</small><strong><?= (int) $stat['selesai'] ?></strong><span><?= $stat['total'] ? round($stat['selesai'] / $stat['total'] * 100) : 0 ?>% dari total</span></div>
    <div class="kpi"><div class="bar-top" style="background:var(--amber)"></div><small>Sedang diproses</small><strong><?= (int) $stat['proses'] ?></strong></div>
    <div class="kpi"><div class="bar-top" style="background:var(--teal)"></div><small>Rata-rata selesai</small><strong><?= e($stat['rata'] ?? '-') ?></strong><span>hari</span></div>
    <div class="kpi"><div class="bar-top" style="background:var(--purple)"></div><small>Kepuasan pelapor</small><strong><?= e($stat['rating'] ?? '-') ?></strong><span>dari 5</span></div>
</div>
<div class="grid-2">
    <div class="panel"><div class="panel-head"><h2>Baru saja diselesaikan</h2></div>
        <div class="table-wrap"><table><tbody>
            <?php foreach ($terbaru as $t): ?>
                <tr><td><strong><?= e($t['judul']) ?></strong><small><?= e($t['kategori']) ?> · <?= e($t['lokasi']) ?></small></td>
                    <td class="text-right"><span class="mono muted"><?= e($t['kode']) ?></span><small>selesai <?= tgl($t['selesai_at']) ?></small></td></tr>
            <?php endforeach; ?>
        </tbody></table></div></div>
    <div class="panel" style="align-self:start"><div class="panel-head"><h2>Per kategori</h2></div><div class="panel-body">
        <?php foreach ($perKat as $k): ?>
            <div class="hrow"><span><?= e($k['nama']) ?></span><div class="htrack"><span style="width:<?= round($k['n'] / $maxK * 100) ?>%"></span></div><span class="mono text-right"><?= (int) $k['n'] ?></span></div>
        <?php endforeach; ?>
    </div></div>
</div>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
