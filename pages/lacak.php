<?php
$kode = strtoupper(trim($_GET['kode'] ?? $_POST['kode'] ?? ''));
$a = $kode !== '' ? q('SELECT a.*, k.nama kategori, k.sla_hari, u.nama unit FROM aduan a LEFT JOIN kategori k ON k.id = a.kategori_id
                       LEFT JOIN unit u ON u.id = a.unit_id WHERE a.kode = ?', [$kode])->fetch() : null;

// Pelapor memberi penilaian setelah aduan selesai (sekali saja)
if ($a && $_SERVER['REQUEST_METHOD'] === 'POST' && $a['status'] === 'selesai' && !$a['rating']) {
    $r = (int) ($_POST['rating'] ?? 0);
    if ($r >= 1 && $r <= 5) {
        q('UPDATE aduan SET rating = ? WHERE id = ? AND rating IS NULL', [$r, $a['id']]);
        $a['rating'] = $r;
    }
}
$timeline = $a ? q('SELECT t.*, u.nama petugas FROM tanggapan t LEFT JOIN users u ON u.id = t.user_id WHERE t.aduan_id = ? AND t.internal = 0 ORDER BY t.created_at, t.id', [$a['id']])->fetchAll() : [];

$title = 'Lacak Tiket';
require __DIR__ . '/../includes/portal_header.php';
?>
<form method="get" class="panel" style="max-width:720px;margin:0 auto 22px"><div class="panel-body filters">
    <input type="hidden" name="page" value="lacak">
    <input type="text" name="kode" value="<?= e($kode) ?>" placeholder="Masukkan nomor tiket, contoh ADU-2610-0123" style="flex:1;font-family:var(--mono)" required>
    <button class="btn btn-primary"><?= icon('search', 16) ?> Lacak</button>
</div></form>
<?php if ($kode !== '' && !$a): ?>
    <div class="alert alert-error" style="max-width:720px;margin:0 auto">Tiket <?= e($kode) ?> tidak ditemukan.</div>
<?php elseif ($a): ?>
    <div class="grid-2" style="max-width:1000px;margin:0 auto">
        <div class="panel">
            <div class="panel-head"><div><span class="mono muted"><?= e($a['kode']) ?></span><h2 style="font-size:18px;margin-top:4px"><?= e($a['judul']) ?></h2></div><?= status_badge($a['status']) ?></div>
            <div class="panel-body">
                <ul class="timeline">
                    <?php foreach ($timeline as $t): ?>
                        <li><span class="node"></span><div><strong><?= e(STATUS[$t['status']][0]) ?></strong> <span class="meta"><?= tgl($t['created_at']) ?> <?= date('H.i', strtotime($t['created_at'])) ?><?= $t['petugas'] ? ' · ' . e($t['petugas']) : '' ?></span><p><?= e($t['isi']) ?></p></div></li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($a['status'] === 'selesai'): ?>
                    <?php if ($a['rating']): ?>
                        <p class="muted">Penilaian kamu: <b style="color:var(--amber)"><?= str_repeat('★', $a['rating']) ?></b><?= str_repeat('☆', 5 - $a['rating']) ?></p>
                    <?php else: ?>
                        <form method="post" class="filters" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="kode" value="<?= e($a['kode']) ?>">
                            <span class="muted">Puas dengan penanganannya?</span>
                            <div class="stars"><?php for ($i = 1; $i <= 5; $i++): ?><label><input type="radio" name="rating" value="<?= $i ?>" required><span><?= $i ?> ★</span></label><?php endfor; ?></div>
                            <button class="btn btn-sm btn-primary">Kirim Nilai</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="panel" style="align-self:start">
            <div class="panel-head"><h2>Detail</h2></div>
            <div class="panel-body">
                <dl class="kv">
                    <dt>Kategori</dt><dd><?= e($a['kategori']) ?></dd>
                    <dt>Ditangani</dt><dd><?= e($a['unit'] ?? '-') ?></dd>
                    <dt>Lokasi</dt><dd><?= e($a['lokasi']) ?></dd>
                    <dt>Dilaporkan</dt><dd><?= tgl($a['created_at']) ?></dd>
                    <dt>Lama proses</dt><dd><?= umur_hari($a) ?> hari <span class="muted">/ target <?= (int) $a['sla_hari'] ?> hari</span></dd>
                </dl>
                <p style="margin:16px 0 0;white-space:pre-line"><?= e($a['isi']) ?></p>
                <?php if ($a['foto']): ?><img class="photo mt" src="<?= url('foto', ['f' => $a['foto']]) ?>" alt="Foto laporan"><?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
