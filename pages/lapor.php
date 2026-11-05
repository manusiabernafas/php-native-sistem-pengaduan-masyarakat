<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('beranda');
if (!empty($_POST['website'])) redirect('beranda'); // bot mengisi honeypot

// Batasi 5 laporan per sesi per jam untuk mencegah spam
$_SESSION['lapor_log'] = array_filter($_SESSION['lapor_log'] ?? [], fn ($t) => $t > time() - 3600);

$d = [];
foreach (['judul', 'kategori_id', 'lokasi', 'isi', 'nama', 'telepon'] as $f) $d[$f] = trim($_POST[$f] ?? '');
$d['anonim'] = !empty($_POST['anonim']) ? 1 : 0;
try {
    if (count($_SESSION['lapor_log']) >= 5) throw new RuntimeException('Terlalu banyak laporan dalam satu jam. Coba lagi nanti.');
    if (mb_strlen($d['judul']) < 8 || mb_strlen($d['isi']) < 20 || $d['lokasi'] === '') throw new RuntimeException('Judul minimal 8 karakter, isi minimal 20 karakter, dan lokasi wajib diisi.');
    if (!$d['anonim'] && ($d['nama'] === '' || !preg_match('/^[0-9+]{9,15}$/', $d['telepon']))) throw new RuntimeException('Isi nama dan nomor WhatsApp yang valid, atau centang kirim sebagai anonim.');
    $kat = q('SELECT * FROM kategori WHERE id = ?', [(int) $d['kategori_id']])->fetch();
    if (!$kat) throw new RuntimeException('Kategori tidak valid.');
    $foto = simpan_foto('foto');

    q('INSERT INTO aduan (kode, nama, telepon, anonim, kategori_id, unit_id, judul, isi, lokasi, foto) VALUES (?,?,?,?,?,?,?,?,?,?)', [
        'TMP-' . bin2hex(random_bytes(4)), $d['anonim'] ? null : $d['nama'], $d['anonim'] ? null : $d['telepon'], $d['anonim'],
        $kat['id'], $kat['unit_id'], $d['judul'], $d['isi'], $d['lokasi'], $foto,
    ]);
    $id = (int) db()->lastInsertId();
    $kode = 'ADU-' . date('ym') . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    q('UPDATE aduan SET kode = ? WHERE id = ?', [$kode, $id]);
    q("INSERT INTO tanggapan (aduan_id, status, isi) VALUES (?, 'baru', 'Laporan diterima dan menunggu verifikasi petugas.')", [$id]);
    $_SESSION['lapor_log'][] = time();
} catch (RuntimeException $ex) {
    $_SESSION['old'] = $d;
    $_SESSION['lapor_error'] = $ex->getMessage();
    redirect('beranda');
}

$title = 'Laporan Terkirim';
require __DIR__ . '/../includes/portal_header.php';
?>
<div class="panel ticket" style="max-width:560px;margin:20px auto">
    <span class="badge badge-green">Laporan terkirim</span>
    <p class="muted" style="margin:18px 0 0">Nomor tiket kamu</p>
    <div class="code"><?= e($kode) ?></div>
    <p class="muted">Simpan nomor ini untuk melacak perkembangan laporan. Target penanganan kategori <b style="color:var(--text)"><?= e($kat['nama']) ?></b>: <?= (int) $kat['sla_hari'] ?> hari.</p>
    <div class="form-actions" style="justify-content:center">
        <a class="btn btn-primary" href="<?= url('lacak', ['kode' => $kode]) ?>"><?= icon('search', 16) ?> Lacak Sekarang</a>
        <a class="btn" href="<?= url('beranda') ?>">Buat Laporan Lain</a>
    </div>
</div>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
