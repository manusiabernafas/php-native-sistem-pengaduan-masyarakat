<?php
$u = user();
$a = q('SELECT a.*, k.nama kategori, k.sla_hari, n.nama unit FROM aduan a LEFT JOIN kategori k ON k.id = a.kategori_id LEFT JOIN unit n ON n.id = a.unit_id WHERE a.id = ?', [(int) ($_GET['id'] ?? 0)])->fetch();
if (!$a || (!is_admin() && $a['unit_id'] != $u['unit_id'])) { flash('Aduan tidak ditemukan atau bukan wewenang unit Anda.', 'error'); redirect('aduan'); }
$timeline = q('SELECT t.*, us.nama petugas FROM tanggapan t LEFT JOIN users us ON us.id = t.user_id WHERE t.aduan_id = ? ORDER BY t.created_at, t.id', [$a['id']])->fetchAll();
$unit = q('SELECT * FROM unit ORDER BY nama')->fetchAll();
$late = lewat_sla($a);
$terbuka = !in_array($a['status'], ['selesai', 'ditolak'], true);

$title = $a['kode'];
$actions = $a['telepon'] && !$a['anonim'] ? '<a class="btn" target="_blank" href="https://wa.me/' . preg_replace('/^0/', '62', preg_replace('/\D/', '', $a['telepon'])) . '?text=' . rawurlencode("Halo {$a['nama']}, terkait laporan {$a['kode']} ({$a['judul']}): ") . '">Hubungi Pelapor</a>' : '';
require __DIR__ . '/../../includes/header.php';
?>
<div class="grid-2">
    <div>
        <div class="panel">
            <div class="panel-head"><div><h2 style="font-size:19px"><?= e($a['judul']) ?></h2><span class="muted"><?= e($a['kategori']) ?> · <?= e($a['lokasi']) ?></span></div><?= status_badge($a['status']) ?></div>
            <div class="panel-body">
                <p style="margin:0;white-space:pre-line"><?= e($a['isi']) ?></p>
                <?php if ($a['foto']): ?><img class="photo mt" src="<?= url('foto', ['f' => $a['foto']]) ?>" alt="Foto"><?php endif; ?>
            </div>
        </div>
        <div class="panel mt">
            <div class="panel-head"><h2>Riwayat Penanganan</h2><span class="legend"><span><i style="background:var(--teal)"></i>Terlihat pelapor</span><span><i style="background:var(--amber)"></i>Catatan internal</span></span></div>
            <div class="panel-body"><ul class="timeline">
                <?php foreach ($timeline as $t): ?>
                    <li><span class="node <?= $t['internal'] ? 'internal' : '' ?>"></span><div>
                        <strong><?= $t['internal'] ? 'Catatan internal' : e(STATUS[$t['status']][0]) ?></strong>
                        <span class="meta"><?= tgl($t['created_at']) ?> <?= date('H.i', strtotime($t['created_at'])) ?> · <?= e($t['petugas'] ?? 'Sistem') ?></span>
                        <p><?= e($t['isi']) ?></p></div></li>
                <?php endforeach; ?>
            </ul></div>
        </div>
    </div>
    <div>
        <div class="panel">
            <div class="panel-head"><h2>Info Aduan</h2></div>
            <div class="panel-body"><dl class="kv">
                <dt>Pelapor</dt><dd><?= $a['anonim'] ? '<span class="muted">Anonim</span>' : e($a['nama']) . '<br><span class="mono muted">' . e($a['telepon']) . '</span>' ?></dd>
                <dt>Masuk</dt><dd><?= tgl($a['created_at']) ?> <?= date('H.i', strtotime($a['created_at'])) ?></dd>
                <dt>Unit</dt><dd><?= e($a['unit'] ?? '-') ?></dd>
                <dt>Prioritas</dt><dd><span class="pri pri-<?= $a['prioritas'] ?>"><?= e($a['prioritas']) ?></span></dd>
                <dt>Umur / SLA</dt><dd class="<?= $late ? 'sla-late' : '' ?>"><?= umur_hari($a) ?> dari <?= (int) $a['sla_hari'] ?> hari<?= $late ? ' · terlambat' : '' ?></dd>
                <?php if ($a['rating']): ?><dt>Penilaian</dt><dd style="color:var(--amber)"><?= str_repeat('★', $a['rating']) ?><span class="muted"><?= str_repeat('☆', 5 - $a['rating']) ?></span></dd><?php endif; ?>
            </dl></div>
        </div>
        <?php if ($terbuka): ?>
            <form method="post" action="<?= url('aduan.proses') ?>" class="panel mt">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $a['id'] ?>">
                <div class="panel-head"><h2>Tindak Lanjut</h2></div>
                <div class="panel-body">
                    <div class="form-grid">
                        <div><label>Ubah status</label><select name="status">
                            <?php foreach (['diverifikasi', 'diproses', 'selesai', 'ditolak'] as $s): if (!is_admin() && in_array($s, ['diverifikasi', 'ditolak'], true)) continue; ?>
                                <option value="<?= $s ?>" <?= $a['status'] === 'baru' && $s === 'diverifikasi' || $a['status'] === 'diverifikasi' && $s === 'diproses' || $a['status'] === 'diproses' && $s === 'selesai' ? 'selected' : '' ?>><?= STATUS[$s][0] ?></option>
                            <?php endforeach; ?>
                            <option value="">(tetap) hanya catatan</option>
                        </select></div>
                        <div><label>Prioritas</label><select name="prioritas"><?php foreach (['rendah', 'sedang', 'tinggi'] as $p): ?><option <?= $a['prioritas'] === $p ? 'selected' : '' ?>><?= $p ?></option><?php endforeach; ?></select></div>
                        <?php if (is_admin()): ?>
                            <div class="full"><label>Teruskan ke unit</label><select name="unit_id"><?php foreach ($unit as $n): ?><option value="<?= $n['id'] ?>" <?= $a['unit_id'] == $n['id'] ? 'selected' : '' ?>><?= e($n['nama']) ?></option><?php endforeach; ?></select></div>
                        <?php endif; ?>
                        <div class="full"><label>Tanggapan</label><textarea name="isi" rows="4" required placeholder="Tulis tanggapan atau hasil penanganan"></textarea></div>
                        <div class="full"><label class="check"><input type="checkbox" name="internal" value="1"> Catatan internal (tidak terlihat pelapor)</label></div>
                    </div>
                    <button class="btn btn-primary btn-block mt"><?= icon('check', 16) ?> Simpan Tindak Lanjut</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
