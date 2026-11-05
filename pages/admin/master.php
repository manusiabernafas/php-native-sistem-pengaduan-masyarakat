<?php
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($_POST['aksi'] ?? '') {
            case 'pengaturan':
                q('UPDATE pengaturan SET nama_instansi=?, nama_layanan=?, alamat=?, telepon=? WHERE id=1', [trim($_POST['nama_instansi']), trim($_POST['nama_layanan']), trim($_POST['alamat']), trim($_POST['telepon'])]);
                break;
            case 'kategori':
                if (trim($_POST['nama']) === '') throw new RuntimeException('Nama kategori wajib diisi.');
                q('INSERT INTO kategori (nama, unit_id, sla_hari) VALUES (?,?,?)', [trim($_POST['nama']), (int) $_POST['unit_id'] ?: null, max(1, (int) $_POST['sla_hari'])]);
                break;
            case 'sla':
                foreach ($_POST['sla'] ?? [] as $id => $hari) q('UPDATE kategori SET sla_hari = ?, unit_id = ? WHERE id = ?', [max(1, (int) $hari), (int) ($_POST['unit'][$id] ?? 0) ?: null, (int) $id]);
                break;
            case 'unit':
                if (trim($_POST['nama']) === '') throw new RuntimeException('Nama unit wajib diisi.');
                q('INSERT INTO unit (nama) VALUES (?)', [trim($_POST['nama'])]);
                break;
            case 'user':
                if (strlen($_POST['password']) < 6) throw new RuntimeException('Password minimal 6 karakter.');
                $role = $_POST['role'] === 'admin' ? 'admin' : 'petugas';
                q('INSERT INTO users (nama, username, password, role, unit_id) VALUES (?,?,?,?,?)', [trim($_POST['nama']), trim($_POST['username']),
                    password_hash($_POST['password'], PASSWORD_DEFAULT), $role, $role === 'petugas' ? ((int) $_POST['unit_id'] ?: null) : null]);
                break;
        }
        flash('Perubahan disimpan.');
    } catch (PDOException $e) {
        flash('Gagal menyimpan: data sudah ada.', 'error');
    } catch (RuntimeException $e) {
        flash($e->getMessage(), 'error');
    }
    redirect('master');
}
$set = setting();
$unit = q('SELECT * FROM unit ORDER BY nama')->fetchAll();
$kategori = q('SELECT * FROM kategori ORDER BY nama')->fetchAll();
$users = q('SELECT u.*, n.nama unit FROM users u LEFT JOIN unit n ON n.id = u.unit_id ORDER BY u.role, u.nama')->fetchAll();
$title = 'Data Master';
require __DIR__ . '/../../includes/header.php';
?>
<div class="grid-2">
    <form method="post" class="panel">
        <?= csrf_field() ?><input type="hidden" name="aksi" value="sla">
        <div class="panel-head"><h2>Kategori, unit penanggung jawab & SLA</h2></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Kategori</th><th>Unit</th><th>SLA (hari)</th></tr></thead>
            <tbody><?php foreach ($kategori as $k): ?>
                <tr><td><strong><?= e($k['nama']) ?></strong></td>
                    <td><select name="unit[<?= $k['id'] ?>]"><option value="0">-</option><?php foreach ($unit as $n): ?><option value="<?= $n['id'] ?>" <?= $k['unit_id'] == $n['id'] ? 'selected' : '' ?>><?= e($n['nama']) ?></option><?php endforeach; ?></select></td>
                    <td><input type="number" name="sla[<?= $k['id'] ?>]" value="<?= (int) $k['sla_hari'] ?>" min="1" max="90" style="width:90px"></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <div class="panel-body"><button class="btn btn-primary">Simpan SLA</button></div>
    </form>
    <div>
        <form method="post" class="panel">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="pengaturan">
            <div class="panel-head"><h2>Identitas layanan</h2></div>
            <div class="panel-body"><div class="form-grid">
                <div><label>Nama layanan</label><input type="text" name="nama_layanan" value="<?= e($set['nama_layanan']) ?>"></div>
                <div><label>Telepon</label><input type="text" name="telepon" value="<?= e($set['telepon']) ?>"></div>
                <div class="full"><label>Nama instansi</label><input type="text" name="nama_instansi" value="<?= e($set['nama_instansi']) ?>"></div>
                <div class="full"><label>Alamat</label><input type="text" name="alamat" value="<?= e($set['alamat']) ?>"></div>
            </div><div class="form-actions"><button class="btn btn-primary">Simpan</button></div></div>
        </form>
        <div class="panel mt"><div class="panel-head"><h2>Tambah data</h2></div><div class="panel-body">
            <form method="post" class="filters" style="margin-bottom:12px"><?= csrf_field() ?><input type="hidden" name="aksi" value="kategori">
                <input type="text" name="nama" placeholder="Kategori baru" required><input type="number" name="sla_hari" value="7" style="width:80px">
                <select name="unit_id"><?php foreach ($unit as $n): ?><option value="<?= $n['id'] ?>"><?= e($n['nama']) ?></option><?php endforeach; ?></select><button class="btn"><?= icon('plus', 16) ?></button></form>
            <form method="post" class="filters"><?= csrf_field() ?><input type="hidden" name="aksi" value="unit"><input type="text" name="nama" placeholder="Unit / dinas baru" required><button class="btn"><?= icon('plus', 16) ?></button></form>
        </div></div>
    </div>
</div>
<div class="panel mt">
    <div class="panel-head"><h2>Pengguna</h2></div>
    <div class="table-wrap"><table><thead><tr><th>Nama</th><th>Username</th><th>Peran</th><th>Unit</th></tr></thead>
        <tbody><?php foreach ($users as $x): ?><tr><td><?= e($x['nama']) ?></td><td class="mono"><?= e($x['username']) ?></td><td><?= e(ucfirst($x['role'])) ?></td><td><?= e($x['unit'] ?? '-') ?></td></tr><?php endforeach; ?></tbody></table></div>
    <form method="post" class="panel-body" style="border-top:1px solid var(--line)"><?= csrf_field() ?><input type="hidden" name="aksi" value="user">
        <div class="form-grid" style="grid-template-columns:repeat(5,1fr)">
            <div><label>Nama</label><input type="text" name="nama" required></div><div><label>Username</label><input type="text" name="username" required></div>
            <div><label>Password</label><input type="password" name="password" minlength="6" required></div>
            <div><label>Peran</label><select name="role"><option value="petugas">Petugas</option><option value="admin">Admin</option></select></div>
            <div><label>Unit</label><select name="unit_id"><?php foreach ($unit as $n): ?><option value="<?= $n['id'] ?>"><?= e($n['nama']) ?></option><?php endforeach; ?></select></div>
        </div><div class="form-actions"><button class="btn btn-primary"><?= icon('plus', 16) ?> Tambah</button></div></form>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
