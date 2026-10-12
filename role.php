
<?php
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

require_login();

// Pastikan hanya Admin yang dapat mengelola role.
$roleId = isset($_SESSION['id_role'])
    ? (int) $_SESSION['id_role']
    : (isset($_SESSION['user']['id_role']) ? (int) $_SESSION['user']['id_role'] : 0);

$roleNama = isset($_SESSION['nama_role'])
    ? strtolower(trim($_SESSION['nama_role']))
    : '';

if (
    $roleId !== 1 &&
    $roleNama !== 'admin' &&
    $roleNama !== 'administrator'
) {
    http_response_code(403);
    exit('Akses ditolak. Hanya Admin yang dapat mengelola role.');
}

$error = '';
$success = '';
$editId = 0;
$namaRole = '';
$mode = 'tambah';

// Pesan hasil operasi
if (isset($_GET['status'])) {
    $pesan = array(
        'tambah' => 'Role berhasil ditambahkan.',
        'edit' => 'Role berhasil diperbarui.',
        'hapus' => 'Role berhasil dihapus.'
    );

    if (isset($pesan[$_GET['status']])) {
        $success = $pesan[$_GET['status']];
    }
}

// Ambil data role untuk diedit
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $idEdit = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($idEdit && $idEdit > 0) {
        $stmtEdit = $conn->prepare(
            "SELECT id_role, nama_role
             FROM roles
             WHERE id_role = ?"
        );

        if ($stmtEdit) {
            $stmtEdit->bind_param('i', $idEdit);
            $stmtEdit->execute();
            $hasilEdit = $stmtEdit->get_result();

            if ($hasilEdit && $hasilEdit->num_rows > 0) {
                $rowEdit = $hasilEdit->fetch_assoc();
                $editId = (int) $rowEdit['id_role'];
                $namaRole = $rowEdit['nama_role'];
                $mode = 'edit';
            } else {
                $error = 'Role tidak ditemukan.';
            }

            $stmtEdit->close();
        } else {
            $error = 'Gagal mengambil data role.';
        }
    } else {
        $error = 'ID role tidak valid.';
    }
}

// Proses tambah, edit, dan hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'Token keamanan tidak valid. Muat ulang halaman dan coba kembali.';
    } else {
        $aksi = isset($_POST['aksi']) && is_string($_POST['aksi'])
            ? $_POST['aksi']
            : '';

        // TAMBAH ROLE
        if ($aksi === 'tambah') {
            $namaRole = isset($_POST['nama_role']) && is_string($_POST['nama_role'])
                ? trim($_POST['nama_role'])
                : '';

            if ($namaRole === '') {
                $error = 'Nama role wajib diisi.';
            } elseif (strlen($namaRole) > 100) {
                $error = 'Nama role maksimal 100 karakter.';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO roles (nama_role) VALUES (?)"
                );

                if ($stmt) {
                    $stmt->bind_param('s', $namaRole);

                    if ($stmt->execute()) {
                        $stmt->close();
                        header('Location: role.php?status=tambah');
                        exit;
                    } else {
                        $error = ($stmt->errno === 1062)
                            ? 'Nama role sudah tersedia.'
                            : 'Role gagal ditambahkan. Periksa struktur tabel.';
                    }

                    $stmt->close();
                } else {
                    $error = 'Gagal menyiapkan perintah tambah.';
                }
            }

            $mode = 'tambah';
        }

        // EDIT ROLE
        elseif ($aksi === 'edit') {
            $editId = isset($_POST['id_role'])
                ? (int) $_POST['id_role']
                : 0;

            $namaRole = isset($_POST['nama_role']) && is_string($_POST['nama_role'])
                ? trim($_POST['nama_role'])
                : '';

            if ($editId <= 0) {
                $error = 'ID role tidak valid.';
            } elseif ($namaRole === '') {
                $error = 'Nama role wajib diisi.';
            } elseif (strlen($namaRole) > 100) {
                $error = 'Nama role maksimal 100 karakter.';
            } else {
                $stmt = $conn->prepare(
                    "UPDATE roles
                     SET nama_role = ?
                     WHERE id_role = ?"
                );

                if ($stmt) {
                    $stmt->bind_param('si', $namaRole, $editId);

                    if ($stmt->execute()) {
                        $stmt->close();
                        header('Location: role.php?status=edit');
                        exit;
                    } else {
                        $error = ($stmt->errno === 1062)
                            ? 'Nama role sudah digunakan.'
                            : 'Role gagal diperbarui.';
                    }

                    $stmt->close();
                } else {
                    $error = 'Gagal menyiapkan perintah edit.';
                }
            }

            $mode = 'edit';
        }

        // HAPUS ROLE
        elseif ($aksi === 'hapus') {
            $idHapus = isset($_POST['id_role'])
                ? (int) $_POST['id_role']
                : 0;

            if ($idHapus <= 0) {
                $error = 'ID role tidak valid.';
            } elseif ($idHapus === 1) {
                $error = 'Role Admin utama tidak boleh dihapus.';
            } else {
                $stmt = $conn->prepare(
                    "DELETE FROM roles WHERE id_role = ?"
                );

                if ($stmt) {
                    $stmt->bind_param('i', $idHapus);

                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $stmt->close();
                        header('Location: role.php?status=hapus');
                        exit;
                    } else {
                        $error = 'Role tidak dapat dihapus. Role mungkin masih digunakan oleh akun pengguna.';
                    }

                    $stmt->close();
                } else {
                    $error = 'Gagal menyiapkan perintah hapus.';
                }
            }
        } else {
            $error = 'Aksi tidak dikenal.';
        }
    }
}

// Pencarian role
$cari = isset($_GET['cari']) && is_string($_GET['cari'])
    ? trim($_GET['cari'])
    : '';

if ($cari !== '') {
    $keyword = '%' . $cari . '%';

    $stmtList = $conn->prepare(
        "SELECT r.id_role, r.nama_role,
                COUNT(p.id_pengguna) AS jumlah_pengguna
         FROM roles r
         LEFT JOIN pengguna p ON p.id_role = r.id_role
         WHERE r.nama_role LIKE ?
         GROUP BY r.id_role, r.nama_role
         ORDER BY r.id_role ASC"
    );

    if ($stmtList) {
        $stmtList->bind_param('s', $keyword);
        $stmtList->execute();
        $hasilList = $stmtList->get_result();
    } else {
        $hasilList = false;
        $error = 'Gagal mencari data role.';
    }
} else {
    $stmtList = $conn->prepare(
        "SELECT r.id_role, r.nama_role,
                COUNT(p.id_pengguna) AS jumlah_pengguna
         FROM roles r
         LEFT JOIN pengguna p ON p.id_role = r.id_role
         GROUP BY r.id_role, r.nama_role
         ORDER BY r.id_role ASC"
    );

    if ($stmtList) {
        $stmtList->execute();
        $hasilList = $stmtList->get_result();
    } else {
        $hasilList = false;
        $error = 'Gagal mengambil daftar role.';
    }
}

$daftarRole = array();

if ($hasilList) {
    while ($row = $hasilList->fetch_assoc()) {
        $daftarRole[] = $row;
    }
}

if (isset($stmtList) && $stmtList) {
    $stmtList->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Data Role - SIM Mahasiswa UMB</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

:root {
    --blue-dark: #123b72;
    --blue: #1769aa;
    --blue-hover: #245493;
    --background: #f4f7fb;
    --border: #e1e8f0;
    --text: #263449;
    --muted: #7c8797;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: var(--background);
    color: var(--text);
    font-size: 13px;
}

.topbar {
    height: 58px;
    position: fixed;
    z-index: 100;
    top: 0;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 22px;
    background: #fff;
    border-bottom: 1px solid var(--border);
}

.brand {
    font-size: 15px;
    font-weight: bold;
}

.brand small {
    display: block;
    margin-top: 4px;
    color: var(--muted);
    font-size: 10px;
    font-weight: normal;
}

.top-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.top-right a {
    color: #dc3545;
    text-decoration: none;
}

.sidebar {
    width: 220px;
    position: fixed;
    top: 58px;
    bottom: 0;
    left: 0;
    overflow-y: auto;
    background: var(--blue-dark);
    padding: 22px 12px;
}

.menu-heading {
    margin: 10px 8px;
    color: #b7cbe6;
    font-size: 10px;
    font-weight: bold;
    letter-spacing: 1px;
}

.nav-link {
    display: block;
    padding: 12px 10px;
    margin-bottom: 4px;
    border-radius: 6px;
    color: #edf4ff;
    text-decoration: none;
}

.nav-link:hover,
.nav-link.active {
    background: var(--blue-hover);
}

.main {
    min-height: 100vh;
    margin-left: 220px;
    padding: 88px 25px 30px;
}

.page-label {
    margin-bottom: 8px;
    color: var(--blue);
    font-size: 10px;
    font-weight: bold;
    letter-spacing: 1px;
}

h1 {
    margin-bottom: 7px;
    font-size: 23px;
}

.subtitle {
    margin-bottom: 22px;
    color: var(--muted);
    line-height: 1.5;
}

.panel {
    margin-bottom: 20px;
    overflow: hidden;
    border: 1px solid var(--border);
    border-radius: 11px;
    background: white;
}

.panel-heading {
    padding: 17px 20px;
    border-bottom: 1px solid var(--border);
    font-weight: bold;
}

.panel-body {
    padding: 20px;
}

.alert {
    padding: 12px 15px;
    margin-bottom: 18px;
    border-radius: 7px;
    line-height: 1.5;
}

.alert-success {
    border: 1px solid #bde9cb;
    background: #e9f9ef;
    color: #167342;
}

.alert-error {
    border: 1px solid #f4c5c5;
    background: #fff0f0;
    color: #a12626;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
    max-width: 600px;
}

label {
    font-size: 12px;
    font-weight: bold;
}

input {
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #d5deea;
    border-radius: 7px;
    font: inherit;
}

input:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(23,105,170,.1);
}

.actions {
    display: flex;
    gap: 8px;
    margin-top: 18px;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    padding: 10px 14px;
    border: 0;
    border-radius: 7px;
    cursor: pointer;
    font-size: 12px;
    font-weight: bold;
    text-decoration: none;
}

.btn-primary {
    background: var(--blue);
    color: white;
}

.btn-primary:hover {
    background: var(--blue-dark);
}

.btn-light {
    background: #edf1f6;
    color: #344054;
}

.btn-edit {
    background: #e7f1ff;
    color: #1759a5;
}

.btn-delete {
    background: #fff0f0;
    color: #bd3030;
}

.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.count {
    color: var(--muted);
}

.search-form {
    display: flex;
    gap: 8px;
    max-width: 450px;
    flex: 1;
}

.search-form input {
    min-width: 100px;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap;
}

th, td {
    padding: 13px 15px;
    border-bottom: 1px solid var(--border);
    text-align: left;
}

th {
    background: #f7f9fc;
    color: #596579;
    font-size: 11px;
}

td {
    font-size: 12px;
}

tbody tr:hover {
    background: #f8fbff;
}

td .btn {
    padding: 7px 9px;
    margin: 2px;
}

.inline-form {
    display: inline;
}

.badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    background: #e7f1ff;
    color: #1759a5;
    font-size: 11px;
    font-weight: bold;
}

.empty {
    padding: 30px;
    color: var(--muted);
    text-align: center;
}

@media (max-width: 850px) {
    .sidebar {
        width: 175px;
    }

    .main {
        margin-left: 175px;
        padding-right: 15px;
        padding-left: 15px;
    }
}

@media (max-width: 650px) {
    .topbar {
        padding: 0 12px;
    }

    .sidebar {
        position: static;
        width: 100%;
        margin-top: 58px;
        padding: 10px;
    }

    .sidebar .menu-heading {
        display: none;
    }

    .nav-link {
        display: inline-block;
        padding: 9px;
    }

    .main {
        margin-left: 0;
        padding: 25px 12px;
    }

    h1 {
        font-size: 20px;
    }
}
</style>
</head>

<body>

<header class="topbar">
    <div class="brand">
        SIM Mahasiswa
        <small>Universitas Muhammadiyah Bengkulu</small>
    </div>

    <div class="top-right">
        <span><?php
            echo e(isset($_SESSION['nama_lengkap'])
                ? $_SESSION['nama_lengkap']
                : 'Admin');
        ?></span>
        <a href="logout.php">Keluar</a>
    </div>
</header>

<aside class="sidebar">
    <div class="menu-heading">MENU UTAMA</div>
    <a class="nav-link" href="dashboard.php">▦ &nbsp; Dashboard</a>

    <div class="menu-heading">DATA AKADEMIK</div>
    <a class="nav-link" href="mahasiswa.php">🎓 &nbsp; Data Mahasiswa</a>
    <a class="nav-link" href="universitas.php">🏫 &nbsp; Universitas</a>
    <a class="nav-link" href="fakultas.php">🏢 &nbsp; Fakultas</a>
    <a class="nav-link" href="program_studi.php">📚 &nbsp; Program Studi</a>
    <a class="nav-link" href="pengguna.php">👥 &nbsp; Pengguna</a>
    <a class="nav-link active" href="role.php">🔐 &nbsp; Role</a>

    <div class="menu-heading">LAPORAN</div>
    <a class="nav-link" href="rekap_mahasiswa.php">📊 &nbsp; Rekap Mahasiswa</a>

    <div class="menu-heading">SISTEM</div>
    <a class="nav-link" href="audit_log.php">📝 &nbsp; Audit Log</a>
</aside>

<main class="main">
    <div class="page-label">DATA MASTER</div>
    <h1>Manajemen Role</h1>
    <div class="subtitle">
        Kelola role dan hak akses pengguna SIM Mahasiswa UMB.
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo e($success); ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="panel">
        <div class="panel-heading">
            <?php echo $mode === 'edit' ? 'Edit Role' : 'Tambah Role'; ?>
        </div>

        <div class="panel-body">
            <form method="post" action="role.php">
                <?php echo csrf_field(); ?>

                <input type="hidden" name="aksi"
                    value="<?php echo $mode === 'edit' ? 'edit' : 'tambah'; ?>">

                <?php if ($mode === 'edit'): ?>
                    <input type="hidden" name="id_role"
                        value="<?php echo (int) $editId; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nama_role">Nama Role</label>
                    <input type="text" id="nama_role" name="nama_role"
                        maxlength="100" required
                        placeholder="Contoh: Operator Prodi"
                        value="<?php echo e($namaRole); ?>">
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $mode === 'edit' ? 'Simpan Perubahan' : 'Tambah Role'; ?>
                    </button>

                    <?php if ($mode === 'edit'): ?>
                        <a href="role.php" class="btn btn-light">Batal</a>
                    <?php else: ?>
                        <button type="reset" class="btn btn-light">Reset</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">Daftar Role Pengguna</div>

        <div class="panel-body">
            <div class="toolbar">
                <div class="count">
                    Total role: <strong><?php echo count($daftarRole); ?></strong>
                </div>

                <form class="search-form" method="get" action="role.php">
                    <input type="search" name="cari"
                        placeholder="Cari nama role..."
                        value="<?php echo e($cari); ?>">

                    <button class="btn btn-primary" type="submit">Cari</button>

                    <?php if ($cari !== ''): ?>
                        <a class="btn btn-light" href="role.php">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>ID Role</th>
                            <th>Nama Role</th>
                            <th>Jumlah Pengguna</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($daftarRole) > 0): ?>
                        <?php foreach ($daftarRole as $i => $r): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo (int) $r['id_role']; ?></td>
                                <td>
                                    <span class="badge">
                                        <?php echo e($r['nama_role']); ?>
                                    </span>
                                </td>
                                <td><?php echo (int) $r['jumlah_pengguna']; ?></td>
                                <td>
                                    <a class="btn btn-edit"
                                        href="role.php?edit=<?php echo (int) $r['id_role']; ?>">
                                        Edit
                                    </a>

                                    <?php if ((int) $r['id_role'] !== 1): ?>
                                        <form class="inline-form" method="post"
                                            action="role.php"
                                            onsubmit="return confirm('Yakin ingin menghapus role ini? Pastikan role tidak digunakan oleh pengguna.');">
                                            <?php echo csrf_field(); ?>

                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id_role"
                                                value="<?php echo (int) $r['id_role']; ?>">

                                            <button class="btn btn-delete" type="submit">
                                                Hapus
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="count">Role utama</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty">
                                Belum ada data role atau hasil pencarian tidak ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

</body>
</html>
