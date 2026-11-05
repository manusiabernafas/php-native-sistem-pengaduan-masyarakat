<?php
$kategori = q('SELECT * FROM kategori ORDER BY nama')->fetchAll();
$stat = q("SELECT COUNT(*) total, SUM(status = 'selesai') selesai FROM aduan")->fetch();
$old = $_SESSION['old'] ?? [];
$err = $_SESSION['lapor_error'] ?? null;
unset($_SESSION['old'], $_SESSION['lapor_error']);
$title = 'Buat Laporan';
require __DIR__ . '/../includes/portal_header.php';
?>
<div class="hero">
    <div>
        <span class="badge badge-green">Layanan aduan warga 24 jam</span>
        <h1 style="margin-top:16px">Laporkan masalah di sekitarmu, <em>kami tindak lanjuti</em>.</h1>
        <p class="lead">Jalan rusak, sampah menumpuk, lampu jalan mati, atau pelayanan publik yang lambat. Setiap laporan mendapat nomor tiket yang bisa kamu lacak sampai selesai.</p>
        <div class="steps">
            <div class="step"><b>1</b><div><strong>Tulis laporan</strong><div class="muted">Isi judul, lokasi, dan foto pendukung. Boleh anonim.</div></div></div>
            <div class="step"><b>2</b><div><strong>Simpan nomor tiket</strong><div class="muted">Dipakai untuk melacak perkembangan laporan.</div></div></div>
            <div class="step"><b>3</b><div><strong>Pantau & beri penilaian</strong><div class="muted">Petugas memverifikasi, meneruskan ke dinas terkait, lalu menyelesaikan.</div></div></div>
        </div>
        <p class="muted" style="margin-top:18px"><b style="color:var(--text)"><?= number_format($stat['total'], 0, ',', '.') ?></b> laporan diterima · <b style="color:var(--text)"><?= number_format($stat['selesai'], 0, ',', '.') ?></b> sudah selesai ditangani</p>
    </div>
    <form method="post" action="<?= url('lapor') ?>" enctype="multipart/form-data" class="panel">
        <?= csrf_field() ?>
        <div class="panel-head"><h2>Formulir Laporan</h2></div>
        <div class="panel-body">
            <?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
            <div class="form-grid">
                <div class="full"><label>Judul laporan</label><input type="text" name="judul" maxlength="150" required value="<?= e($old['judul'] ?? '') ?>" placeholder="Contoh: Jalan berlubang di depan SDN 2"></div>
                <div><label>Kategori</label><select name="kategori_id" required><?php foreach ($kategori as $k): ?><option value="<?= $k['id'] ?>" <?= ($old['kategori_id'] ?? '') == $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option><?php endforeach; ?></select></div>
                <div><label>Lokasi</label><input type="text" name="lokasi" required value="<?= e($old['lokasi'] ?? '') ?>" placeholder="Nama jalan / kelurahan"></div>
                <div class="full"><label>Isi laporan</label><textarea name="isi" rows="4" required minlength="20" placeholder="Jelaskan kejadian, sejak kapan, dan dampaknya"><?= e($old['isi'] ?? '') ?></textarea></div>
                <div class="full"><label>Foto pendukung (opsional, maks 3 MB)</label><input type="file" name="foto" accept="image/*"></div>
                <div><label>Nama</label><input type="text" name="nama" value="<?= e($old['nama'] ?? '') ?>"></div>
                <div><label>No. WhatsApp</label><input type="text" name="telepon" value="<?= e($old['telepon'] ?? '') ?>"></div>
                <div class="full"><label class="check"><input type="checkbox" name="anonim" value="1" <?= !empty($old['anonim']) ? 'checked' : '' ?>> Kirim sebagai anonim (nama & nomor tidak ditampilkan ke petugas)</label></div>
                <!-- Honeypot anti spam bot: harus dibiarkan kosong -->
                <div style="position:absolute;left:-9999px"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
            </div>
            <button class="btn btn-primary btn-block mt"><?= icon('send', 16) ?> Kirim Laporan</button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
