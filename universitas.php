
<?php
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

require_login();

// Hak akses Admin
$roleId = isset($_SESSION['id_role']) ? (int) $_SESSION['id_role'] : 0;
$roleNama = isset($_SESSION['nama_role'])
    ? strtolower(trim($_SESSION['nama_role']))
    : '';

if (
    $roleId !== 1 &&
    $roleNama !== 'admin' &&
    $roleNama !== 'administrator'
) {
    http_response_code(403);
    exit('Akses ditolak. Hanya Admin yang dapat mengelola data universitas.');
}

$error = '';
$success = '';
$mode = 'tambah';

$data = array(
    'id_universitas' => '',
    'kode_universitas' => '',
    'nama_universitas' => '',
    'slogan' => '',
    'alamat' => '',
    'kota' => '',
    'provinsi' => '',
    'kode_pos' => '',
    'website' => '',
    'email' => '',
    'telepon' => ''
);

function redirectUniversitas($status) {
    header('Location: universitas.php?status=' . urlencode($status));
    exit;
}

// Pesan setelah CRUD
if (isset($_GET['status'])) {
    $pesan = array(
        'tambah' => 'Data universitas berhasil ditambahkan.',
        'edit' => 'Data universitas berhasil diperbarui.',
        'hapus' => 'Data universitas berhasil dihapus.'
    );

    if (isset($pesan[$_GET['status']])) {
        $success = $pesan[$_GET['status']];
    }
}

// Ambil data yang akan diedit
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $idEdit = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($idEdit && $idEdit > 0) {
        $stmt = $conn->prepare(
            "SELECT id_universitas, kode_universitas,
                    nama_universitas, slogan, alamat, kota,
                    provinsi, kode_pos, website, email, telepon
             FROM universitas
             WHERE id_universitas = ?"
        );

        if ($stmt) {
            $stmt->bind_param('i', $idEdit);
            $stmt->execute();
            $resultEdit = $stmt->get_result();

            if ($resultEdit && $resultEdit->num_rows === 1) {
                $data = $resultEdit->fetch_assoc();
                $mode = 'edit';
            } else {
                $error = 'Data universitas tidak ditemukan.';
            }

            $stmt->close();
        } else {
            $error = 'Gagal mengambil data universitas.';
        }
    } else {
        $error = 'ID universitas tidak valid.';
    }
}

// Proses formulir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'Token keamanan tidak valid. Muat ulang halaman dan coba kembali.';
    } else {
        $aksi = isset($_POST['aksi']) && is_string($_POST['aksi'])
            ? $_POST['aksi']
            : '';

        // TAMBAH DAN EDIT
        if ($aksi === 'tambah' || $aksi === 'edit') {
            $field = array(
                'kode_universitas',
                'nama_universitas',
                'slogan',
                'alamat',
                'kota',
                'provinsi',
                'kode_pos',
                'website',
                'email',
                'telepon'
            );

            foreach ($field as $key) {
                $data[$key] = isset($_POST[$key]) && is_string($_POST[$key])
                    ? trim($_POST[$key])
                    : '';
            }

            $id = isset($_POST['id_universitas'])
                ? (int) $_POST['id_universitas']
                : 0;

            if (
                $data['kode_universitas'] === '' ||
                $data['nama_universitas'] === ''
            ) {
                $error = 'Kode dan nama universitas wajib diisi.';
            } elseif (strlen($data['kode_universitas']) > 20) {
                $error = 'Kode universitas maksimal 20 karakter.';
            } elseif (strlen($data['nama_universitas']) > 200) {
                $error = 'Nama universitas maksimal 200 karakter.';
            } elseif (
                strlen($data['slogan']) > 255 ||
                strlen($data['kota']) > 100 ||
                strlen($data['provinsi']) > 100 ||
                strlen($data['kode_pos']) > 10 ||
                strlen($data['website']) > 255 ||
                strlen($data['email']) > 150 ||
                strlen($data['telepon']) > 50
            ) {
                $error = 'Ada isian yang melebihi batas panjang kolom.';
            } elseif (
                $data['email'] !== '' &&
                !filter_var($data['email'], FILTER_VALIDATE_EMAIL)
            ) {
                $error = 'Format email tidak valid.';
            } elseif (
                $data['website'] !== '' &&
                !filter_var($data['website'], FILTER_VALIDATE_URL)
            ) {
                $error = 'Website harus menggunakan URL lengkap, misalnya https://contoh.ac.id.';
            } elseif ($aksi === 'edit' && $id <= 0) {
                $error = 'ID universitas tidak valid.';
            } else {
                if ($aksi === 'tambah') {
                    $stmt = $conn->prepare(
                        "INSERT INTO universitas
                        (kode_universitas, nama_universitas, slogan,
                         alamat, kota, provinsi, kode_pos, website,
                         email, telepon)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    if ($stmt) {
                        $stmt->bind_param(
                            'ssssssssss',
                            $data['kode_universitas'],
                            $data['nama_universitas'],
                            $data['slogan'],
                            $data['alamat'],
                            $data['kota'],
                            $data['provinsi'],
                            $data['kode_pos'],
                            $data['website'],
                            $data['email'],
                            $data['telepon']
                        );

                        if ($stmt->execute()) {
                            $stmt->close();
                            redirectUniversitas('tambah');
                        } else {
                            $error = ($stmt->errno === 1062)
                                ? 'Kode universitas sudah digunakan.'
                                : 'Data gagal ditambahkan. Periksa struktur tabel dan data.';
                        }

                        $stmt->close();
                    } else {
                        $error = 'Gagal menyiapkan perintah tambah.';
                    }
                } else {
                    $stmt = $conn->prepare(
                        "UPDATE universitas SET
                         kode_universitas = ?,
                         nama_universitas = ?,
                         slogan = ?,
                         alamat = ?,
                         kota = ?,
                         provinsi = ?,
                         kode_pos = ?,
                         website = ?,
                         email = ?,
                         telepon = ?
                         WHERE id_universitas = ?"
                    );

                    if ($stmt) {
                        $stmt->bind_param(
                            'ssssssssssi',
                            $data['kode_universitas'],
                            $data['nama_universitas'],
                            $data['slogan'],
                            $data['alamat'],
                            $data['kota'],
                            $data['provinsi'],
                            $data['kode_pos'],
                            $data['website'],
                            $data['email'],
                            $data['telepon'],
                            $id
                        );

                        if ($stmt->execute()) {
                            $stmt->close();
                            redirectUniversitas('edit');
                        } else {
                            $error = ($stmt->errno === 1062)
                                ? 'Kode universitas sudah digunakan.'
                                : 'Data gagal diperbarui. Periksa kembali struktur tabel.';
                        }

                        $stmt->close();
                    } else {
                        $error = 'Gagal menyiapkan perintah edit.';
                    }
                }
            }

            $mode = ($aksi === 'edit') ? 'edit' : 'tambah';
            $data['id_universitas'] = isset($_POST['id_universitas'])
                ? (int) $_POST['id_universitas']
                : '';
        }

        // HAPUS
        elseif ($aksi === 'hapus') {
            $idHapus = isset($_POST['id_universitas'])
                ? (int) $_POST['id_universitas']
                : 0;

            if ($idHapus <= 0) {
                $error = 'ID universitas tidak valid.';
            } else {
                $stmt = $conn->prepare(
                    "DELETE FROM universitas WHERE id_universitas = ?"
                );

                if ($stmt) {
                    $stmt->bind_param('i', $idHapus);

                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $stmt->close();
                        redirectUniversitas('hapus');
                    } else {
                        $error = 'Data tidak dapat dihapus. Pastikan universitas tidak sedang digunakan oleh data fakultas.';
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

// PENCARIAN DAN DAFTAR DATA
$cari = isset($_GET['cari']) && is_string($_GET['cari'])
    ? trim($_GET['cari'])
    : '';

if ($cari !== '') {
    $keyword = '%' . $cari . '%';

    $stmt = $conn->prepare(
        "SELECT id_universitas, kode_universitas,
                nama_universitas, kota, provinsi, email, telepon
         FROM universitas
         WHERE kode_universitas LIKE ?
            OR nama_universitas LIKE ?
            OR kota LIKE ?
         ORDER BY id_universitas DESC"
    );

    if ($stmt) {
        $stmt->bind_param('sss', $keyword, $keyword, $keyword);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = false;
        $error = 'Gagal melakukan pencarian data.';
    }
} else {
    $stmt = $conn->prepare(
        "SELECT id_universitas, kode_universitas,
                nama_universitas, kota, provinsi, email, telepon
         FROM universitas
         ORDER BY id_universitas DESC"
    );

    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = false;
        $error = 'Gagal mengambil daftar universitas.';
    }
}

$universitas = array();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $universitas[] = $row;
    }
}

if (isset($stmt) && $stmt) {
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Universitas - SIM Mahasiswa UMB</title>

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
    --blue-light: #e8f2ff;
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
    background: white;
    border-bottom: 1px solid var(--border);
}

.brand {
    font-size: 15px;
    font-weight: bold;
}

.brand small {
    display: block;
    color: var(--muted);
    font-size: 10px;
    font-weight: normal;
    margin-top: 4px;
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
    color: #b7cbe6;
    font-size: 10px;
    font-weight: bold;
    letter-spacing: 1px;
    margin: 10px 8px;
}

.nav-link {
    display: block;
    color: #edf4ff;
    padding: 12px 10px;
    border-radius: 6px;
    text-decoration: none;
    margin-bottom: 4px;
}

.nav-link:hover,
.nav-link.active {
    background: var(--blue-hover);
}

.main {
    margin-left: 220px;
    padding: 88px 25px 30px;
    min-height: 100vh;
}

.page-label {
    color: var(--blue);
    font-size: 10px;
    font-weight: bold;
    letter-spacing: 1px;
    margin-bottom: 8px;
}

h1 {
    font-size: 23px;
    margin-bottom: 7px;
}

.subtitle {
    color: var(--muted);
    margin-bottom: 22px;
    line-height: 1.5;
}

.panel {
    background: white;
    border: 1px solid var(--border);
    border-radius: 11px;
    margin-bottom: 20px;
    overflow: hidden;
}

.panel-heading {
    padding: 17px 20px;
    font-weight: bold;
    border-bottom: 1px solid var(--border);
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
    background: #e9f9ef;
    color: #167342;
    border: 1px solid #bde9cb;
}

.alert-error {
    background: #fff0f0;
    color: #a12626;
    border: 1px solid #f4c5c5;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.form-group.full {
    grid-column: 1 / -1;
}

label {
    font-size: 12px;
    font-weight: bold;
}

.required {
    color: #dc3545;
}

input, textarea {
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #d5deea;
    border-radius: 7px;
    font: inherit;
    background: white;
    color: var(--text);
}

textarea {
    min-height: 75px;
    resize: vertical;
}

input:focus, textarea:focus {
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
    border: 0;
    border-radius: 7px;
    padding: 10px 14px;
    text-decoration: none;
    cursor: pointer;
    font-size: 12px;
    font-weight: bold;
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
    gap: 12px;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.search-form {
    display: flex;
    gap: 8px;
    max-width: 480px;
    flex: 1;
}

.search-form input {
    min-width: 120px;
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
    text-align: left;
    padding: 13px 15px;
    border-bottom: 1px solid var(--border);
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

.empty {
    text-align: center;
    color: var(--muted);
    padding: 30px;
}

.count {
    color: var(--muted);
    font-size: 12px;
}

@media (max-width: 850px) {
    .sidebar {
        width: 175px;
    }

    .main {
        margin-left: 175px;
        padding-left: 15px;
        padding-right: 15px;
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

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
        grid-column: auto;
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
        <span><?php echo e(isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Admin'); ?></span>
        <a href="logout.php">Keluar</a>
    </div>
</header>

<aside class="sidebar">
    <div class="menu-heading">MENU UTAMA</div>
    <a class="nav-link" href="dashboard.php">▦ &nbsp; Dashboard</a>

    <div class="menu-heading">DATA AKADEMIK</div>
    <a class="nav-link" href="mahasiswa.php">🎓 &nbsp; Data Mahasiswa</a>
    <a class="nav-link active" href="universitas.php">🏫 &nbsp; Universitas</a>
    <a class="nav-link" href="fakultas.php">🏢 &nbsp; Fakultas</a>
    <a class="nav-link" href="program_studi.php">📚 &nbsp; Program Studi</a>
    <a class="nav-link" href="pengguna.php">👥 &nbsp; Pengguna</a>
    <a class="nav-link" href="role.php">🔐 &nbsp; Role</a>

    <div class="menu-heading">LAPORAN</div>
    <a class="nav-link" href="rekap_mahasiswa.php">📊 &nbsp; Rekap Mahasiswa</a>

    <div class="menu-heading">SISTEM</div>
    <a class="nav-link" href="audit_log.php">📝 &nbsp; Audit Log</a>
</aside>

<main class="main">
    <div class="page-label">DATA MASTER</div>
    <h1>Data Universitas</h1>
    <div class="subtitle">
        Kelola informasi universitas dalam sistem SIM Mahasiswa.
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo e($success); ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="panel">
        <div class="panel-heading">
            <?php echo $mode === 'edit' ? 'Edit Universitas' : 'Tambah Universitas'; ?>
        </div>

        <div class="panel-body">
            <form method="post" action="universitas.php">
                <?php echo csrf_field(); ?>

                <input type="hidden" name="aksi"
                    value="<?php echo $mode === 'edit' ? 'edit' : 'tambah'; ?>">

                <input type="hidden" name="id_universitas"
                    value="<?php echo e($data['id_universitas']); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="kode_universitas">
                            Kode Universitas <span class="required">*</span>
                        </label>
                        <input id="kode_universitas" name="kode_universitas"
                            maxlength="20" required
                            value="<?php echo e($data['kode_universitas']); ?>"
                            placeholder="Contoh: UMB">
                    </div>

                    <div class="form-group">
                        <label for="nama_universitas">
                            Nama Universitas <span class="required">*</span>
                        </label>
                        <input id="nama_universitas" name="nama_universitas"
                            maxlength="200" required
                            value="<?php echo e($data['nama_universitas']); ?>"
                            placeholder="Nama universitas">
                    </div>

                    <div class="form-group full">
                        <label for="slogan">Slogan</label>
                        <input id="slogan" name="slogan" maxlength="255"
                            value="<?php echo e($data['slogan']); ?>">
                    </div>

                    <div class="form-group full">
                        <label for="alamat">Alamat</label>
                        <textarea id="alamat" name="alamat"><?php echo e($data['alamat']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="kota">Kota</label>
                        <input id="kota" name="kota" maxlength="100"
                            value="<?php echo e($data['kota']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="provinsi">Provinsi</label>
                        <input id="provinsi" name="provinsi" maxlength="100"
                            value="<?php echo e($data['provinsi']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="kode_pos">Kode Pos</label>
                        <input id="kode_pos" name="kode_pos" maxlength="10"
                            value="<?php echo e($data['kode_pos']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="telepon">Telepon</label>
                        <input id="telepon" name="telepon" maxlength="50"
                            value="<?php echo e($data['telepon']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" maxlength="150"
                            value="<?php echo e($data['email']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="website">Website</label>
                        <input id="website" type="url" name="website" maxlength="255"
                            value="<?php echo e($data['website']); ?>"
                            placeholder="https://contoh.ac.id">
                    </div>
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">
                        <?php echo $mode === 'edit' ? 'Simpan Perubahan' : 'Tambah Universitas'; ?>
                    </button>

                    <?php if ($mode === 'edit'): ?>
                        <a class="btn btn-light" href="universitas.php">Batal</a>
                    <?php else: ?>
                        <button class="btn btn-light" type="reset">Reset Form</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">Daftar Universitas</div>

        <div class="panel-body">
            <div class="toolbar">
                <div class="count">
                    Total data: <strong><?php echo count($universitas); ?></strong>
                </div>

                <form class="search-form" method="get" action="universitas.php">
                    <input type="search" name="cari"
                        placeholder="Cari kode, nama, atau kota..."
                        value="<?php echo e($cari); ?>">
                    <button class="btn btn-primary" type="submit">Cari</button>

                    <?php if ($cari !== ''): ?>
                        <a class="btn btn-light" href="universitas.php">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Kode</th>
                            <th>Nama Universitas</th>
                            <th>Kota</th>
                            <th>Provinsi</th>
                            <th>Email</th>
                            <th>Telepon</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($universitas) > 0): ?>
                        <?php foreach ($universitas as $i => $u): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo e($u['kode_universitas']); ?></td>
                                <td><?php echo e($u['nama_universitas']); ?></td>
                                <td><?php echo e($u['kota']); ?></td>
                                <td><?php echo e($u['provinsi']); ?></td>
                                <td><?php echo e($u['email']); ?></td>
                                <td><?php echo e($u['telepon']); ?></td>
                                <td>
                                    <a class="btn btn-edit"
                                       href="universitas.php?edit=<?php echo (int) $u['id_universitas']; ?>">
                                        Edit
                                    </a>

                                    <form class="inline-form" method="post"
                                        action="universitas.php"
                                        onsubmit="return confirm('Yakin ingin menghapus data universitas ini?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id_universitas"
                                            value="<?php echo (int) $u['id_universitas']; ?>">
                                        <button type="submit" class="btn btn-delete">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="empty">
                                Belum ada data atau hasil pencarian tidak ditemukan.
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
