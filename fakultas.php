
<?php
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

require_login();

// Hanya Admin
$roleId = isset($_SESSION['id_role']) ? (int) $_SESSION['id_role'] : 0;
$roleNama = isset($_SESSION['nama_role'])
    ? strtolower(trim($_SESSION['nama_role']))
    : '';

if ($roleId !== 1 && !in_array($roleNama, array('admin', 'administrator'), true)) {
    http_response_code(403);
    exit('Akses ditolak. Halaman ini khusus Admin.');
}

$error = '';
$success = '';
$mode = 'tambah';
$idEdit = 0;
$idUniversitas = '';
$kodeFakultas = '';
$namaFakultas = '';

function redirectFakultas($status) {
    header('Location: fakultas.php?status=' . urlencode($status));
    exit;
}

// Pesan notifikasi
if (isset($_GET['status'])) {
    $pesan = array(
        'tambah' => 'Data fakultas berhasil ditambahkan.',
        'edit' => 'Data fakultas berhasil diperbarui.',
        'hapus' => 'Data fakultas berhasil dihapus.'
    );

    if (isset($pesan[$_GET['status']])) {
        $success = $pesan[$_GET['status']];
    }
}

// Ambil daftar universitas untuk pilihan form
$universitas = array();
$resUniv = $conn->query(
    "SELECT id_universitas, nama_universitas
     FROM universitas
     ORDER BY nama_universitas ASC"
);

if ($resUniv) {
    while ($row = $resUniv->fetch_assoc()) {
        $universitas[] = $row;
    }
}

// Ambil data untuk edit
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($id && $id > 0) {
        $stmt = $conn->prepare(
            "SELECT id_fakultas, id_universitas,
                    kode_fakultas, nama_fakultas
             FROM fakultas
             WHERE id_fakultas = ?"
        );

        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows === 1) {
                $d = $res->fetch_assoc();
                $idEdit = (int) $d['id_fakultas'];
                $idUniversitas = $d['id_universitas'];
                $kodeFakultas = $d['kode_fakultas'];
                $namaFakultas = $d['nama_fakultas'];
                $mode = 'edit';
            } else {
                $error = 'Data fakultas tidak ditemukan.';
            }
            $stmt->close();
        } else {
            $error = 'Gagal menyiapkan data edit.';
        }
    } else {
        $error = 'ID fakultas tidak valid.';
    }
}

// Proses formulir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'Token keamanan tidak valid. Muat ulang halaman.';
    } else {
        $aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';

        if ($aksi === 'tambah' || $aksi === 'edit') {
            $idEdit = isset($_POST['id_fakultas'])
                ? (int) $_POST['id_fakultas'] : 0;

            $idUniversitas = isset($_POST['id_universitas'])
                ? (int) $_POST['id_universitas'] : 0;

            $kodeFakultas = isset($_POST['kode_fakultas'])
                ? trim($_POST['kode_fakultas']) : '';

            $namaFakultas = isset($_POST['nama_fakultas'])
                ? trim($_POST['nama_fakultas']) : '';

            if ($idUniversitas <= 0) {
                $error = 'Pilih universitas terlebih dahulu.';
            } elseif ($kodeFakultas === '' || $namaFakultas === '') {
                $error = 'Kode dan nama fakultas wajib diisi.';
            } elseif (strlen($kodeFakultas) > 20) {
                $error = 'Kode fakultas maksimal 20 karakter.';
            } elseif (strlen($namaFakultas) > 200) {
                $error = 'Nama fakultas maksimal 200 karakter.';
            } else {
                // Pastikan universitas benar-benar ada
                $cek = $conn->prepare(
                    "SELECT id_universitas FROM universitas
                     WHERE id_universitas = ?"
                );
                $cek->bind_param('i', $idUniversitas);
                $cek->execute();
                $validUniv = $cek->get_result()->num_rows > 0;
                $cek->close();

                if (!$validUniv) {
                    $error = 'Universitas yang dipilih tidak ditemukan.';
                } elseif ($aksi === 'tambah') {
                    $stmt = $conn->prepare(
                        "INSERT INTO fakultas
                         (id_universitas, kode_fakultas, nama_fakultas)
                         VALUES (?, ?, ?)"
                    );

                    if ($stmt) {
                        $stmt->bind_param(
                            'iss',
                            $idUniversitas,
                            $kodeFakultas,
                            $namaFakultas
                        );

                        if ($stmt->execute()) {
                            $stmt->close();
                            redirectFakultas('tambah');
                        } else {
                            $error = ($stmt->errno === 1062)
                                ? 'Data duplikat. Kode fakultas mungkin sudah digunakan.'
                                : 'Gagal menambahkan fakultas.';
                        }
                        $stmt->close();
                    } else {
                        $error = 'Gagal menyiapkan perintah tambah.';
                    }
                } else {
                    if ($idEdit <= 0) {
                        $error = 'ID fakultas tidak valid.';
                    } else {
                        $stmt = $conn->prepare(
                            "UPDATE fakultas
                             SET id_universitas = ?,
                                 kode_fakultas = ?,
                                 nama_fakultas = ?
                             WHERE id_fakultas = ?"
                        );

                        if ($stmt) {
                            $stmt->bind_param(
                                'issi',
                                $idUniversitas,
                                $kodeFakultas,
                                $namaFakultas,
                                $idEdit
                            );

                            if ($stmt->execute()) {
                                $stmt->close();
                                redirectFakultas('edit');
                            } else {
                                $error = ($stmt->errno === 1062)
                                    ? 'Kode fakultas sudah digunakan.'
                                    : 'Gagal memperbarui fakultas.';
                            }
                            $stmt->close();
                        } else {
                            $error = 'Gagal menyiapkan perintah edit.';
                        }
                    }
                }
            }

            $mode = ($aksi === 'edit') ? 'edit' : 'tambah';
        } elseif ($aksi === 'hapus') {
            $idHapus = isset($_POST['id_fakultas'])
                ? (int) $_POST['id_fakultas'] : 0;

            if ($idHapus <= 0) {
                $error = 'ID fakultas tidak valid.';
            } else {
                $stmt = $conn->prepare(
                    "DELETE FROM fakultas WHERE id_fakultas = ?"
                );

                if ($stmt) {
                    $stmt->bind_param('i', $idHapus);

                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $stmt->close();
                        redirectFakultas('hapus');
                    } else {
                        $error = 'Fakultas tidak dapat dihapus. Pastikan tidak digunakan oleh program studi atau pengguna.';
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

// Pencarian dan daftar fakultas
$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';

if ($cari !== '') {
    $keyword = '%' . $cari . '%';
    $stmtList = $conn->prepare(
        "SELECT f.id_fakultas, f.id_universitas,
                f.kode_fakultas, f.nama_fakultas,
                u.nama_universitas
         FROM fakultas f
         INNER JOIN universitas u
             ON u.id_universitas = f.id_universitas
         WHERE f.kode_fakultas LIKE ?
            OR f.nama_fakultas LIKE ?
            OR u.nama_universitas LIKE ?
         ORDER BY f.id_fakultas DESC"
    );
    $stmtList->bind_param('sss', $keyword, $keyword, $keyword);
} else {
    $stmtList = $conn->prepare(
        "SELECT f.id_fakultas, f.id_universitas,
                f.kode_fakultas, f.nama_fakultas,
                u.nama_universitas
         FROM fakultas f
         INNER JOIN universitas u
             ON u.id_universitas = f.id_universitas
         ORDER BY f.id_fakultas DESC"
    );
}

$daftarFakultas = array();

if ($stmtList) {
    $stmtList->execute();
    $hasil = $stmtList->get_result();

    while ($row = $hasil->fetch_assoc()) {
        $daftarFakultas[] = $row;
    }

    $stmtList->close();
} else {
    $error = 'Gagal mengambil daftar fakultas.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Fakultas - SIM Mahasiswa UMB</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--dark:#123b72;--blue:#1769aa;--hover:#245493;--bg:#f4f7fb;--border:#e1e8f0;--text:#263449;--muted:#7c8797}
body{font-family:Arial,Helvetica,sans-serif;background:var(--bg);color:var(--text);font-size:13px}
.topbar{height:58px;position:fixed;z-index:10;top:0;left:0;right:0;display:flex;align-items:center;justify-content:space-between;padding:0 22px;background:white;border-bottom:1px solid var(--border)}
.brand{font-size:15px;font-weight:bold}.brand small{display:block;margin-top:4px;color:var(--muted);font-size:10px;font-weight:normal}
.top-right{display:flex;gap:12px;align-items:center}.top-right a{color:#dc3545;text-decoration:none}
.sidebar{position:fixed;top:58px;bottom:0;left:0;width:220px;overflow-y:auto;background:var(--dark);padding:22px 12px}
.menu-heading{margin:10px 8px;color:#b7cbe6;font-size:10px;font-weight:bold;letter-spacing:1px}
.nav-link{display:block;padding:12px 10px;margin-bottom:4px;border-radius:6px;color:#edf4ff;text-decoration:none}
.nav-link:hover,.nav-link.active{background:var(--hover)}
.main{min-height:100vh;margin-left:220px;padding:88px 25px 30px}
.page-label{margin-bottom:8px;color:var(--blue);font-size:10px;font-weight:bold;letter-spacing:1px}
h1{font-size:23px;margin-bottom:7px}.subtitle{margin-bottom:22px;color:var(--muted);line-height:1.5}
.panel{margin-bottom:20px;overflow:hidden;background:white;border:1px solid var(--border);border-radius:11px}
.panel-heading{padding:17px 20px;border-bottom:1px solid var(--border);font-weight:bold}.panel-body{padding:20px}
.alert{padding:12px 15px;margin-bottom:18px;border-radius:7px;line-height:1.5}
.ok{background:#e9f9ef;color:#167342;border:1px solid #bde9cb}.err{background:#fff0f0;color:#a12626;border:1px solid #f4c5c5}
.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.form-group{display:flex;flex-direction:column;gap:7px}label{font-weight:bold}
input,select{width:100%;padding:11px 12px;border:1px solid #d5deea;border-radius:7px;background:white;font:inherit;color:var(--text)}
input:focus,select:focus{outline:none;border-color:var(--blue);box-shadow:0 0 0 3px #1769aa18}
.actions{display:flex;gap:8px;margin-top:18px;flex-wrap:wrap}
.btn{display:inline-block;padding:10px 14px;border:0;border-radius:7px;cursor:pointer;font-size:12px;font-weight:bold;text-decoration:none}
.primary{background:var(--blue);color:white}.light{background:#edf1f6;color:#344054}.edit{background:#e7f1ff;color:#1759a5}.delete{background:#fff0f0;color:#bd3030}
.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap}
.search{display:flex;gap:8px;max-width:450px;flex:1}.search input{min-width:100px}
.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;white-space:nowrap}
th,td{padding:13px 15px;border-bottom:1px solid var(--border);text-align:left}
th{background:#f7f9fc;color:#596579;font-size:11px}td{font-size:12px}tbody tr:hover{background:#f8fbff}
.inline{display:inline}.empty{text-align:center;padding:30px;color:var(--muted)}
@media(max-width:850px){.sidebar{width:175px}.main{margin-left:175px;padding-left:15px;padding-right:15px}}
@media(max-width:650px){.sidebar{position:static;width:100%;margin-top:58px;padding:10px}.sidebar .menu-heading{display:none}.nav-link{display:inline-block;padding:9px}.main{margin:0;padding:25px 12px}.form-grid{grid-template-columns:1fr}.topbar{padding:0 12px}}
</style>
</head>
<body>
<header class="topbar">
    <div class="brand">SIM Mahasiswa
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
    <a class="nav-link" href="universitas.php">🏫 &nbsp; Universitas</a>
    <a class="nav-link active" href="fakultas.php">🏢 &nbsp; Fakultas</a>
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
    <h1>Data Fakultas</h1>
    <div class="subtitle">Kelola data fakultas dan universitas yang menaunginya.</div>

    <?php if ($success !== ''): ?>
        <div class="alert ok"><?php echo e($success); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert err"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="panel">
        <div class="panel-heading"><?php echo $mode === 'edit' ? 'Edit Fakultas' : 'Tambah Fakultas'; ?></div>
        <div class="panel-body">
            <form method="post" action="fakultas.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="aksi" value="<?php echo e($mode); ?>">
                <input type="hidden" name="id_fakultas" value="<?php echo (int) $idEdit; ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="id_universitas">Universitas *</label>
                        <select id="id_universitas" name="id_universitas" required>
                            <option value="">-- Pilih Universitas --</option>
                            <?php foreach ($universitas as $u): ?>
                                <option value="<?php echo (int) $u['id_universitas']; ?>"
                                    <?php echo ((string) $idUniversitas === (string) $u['id_universitas']) ? 'selected' : ''; ?>>
                                    <?php echo e($u['nama_universitas']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="kode_fakultas">Kode Fakultas *</label>
                        <input id="kode_fakultas" name="kode_fakultas" maxlength="20"
                            required placeholder="Contoh: FT"
                            value="<?php echo e($kodeFakultas); ?>">
                    </div>

                    <div class="form-group">
                        <label for="nama_fakultas">Nama Fakultas *</label>
                        <input id="nama_fakultas" name="nama_fakultas" maxlength="200"
                            required placeholder="Contoh: Fakultas Teknik"
                            value="<?php echo e($namaFakultas); ?>">
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn primary">
                        <?php echo $mode === 'edit' ? 'Simpan Perubahan' : 'Tambah Fakultas'; ?>
                    </button>
                    <?php if ($mode === 'edit'): ?>
                        <a class="btn light" href="fakultas.php">Batal</a>
                    <?php else: ?>
                        <button type="reset" class="btn light">Reset</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">Daftar Fakultas</div>
        <div class="panel-body">
            <div class="toolbar">
                <span>Total data: <strong><?php echo count($daftarFakultas); ?></strong></span>
                <form class="search" method="get" action="fakultas.php">
                    <input type="search" name="cari" placeholder="Cari kode, fakultas, universitas..."
                        value="<?php echo e($cari); ?>">
                    <button class="btn primary" type="submit">Cari</button>
                    <?php if ($cari !== ''): ?>
                        <a class="btn light" href="fakultas.php">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>No.</th>
                        <th>Kode Fakultas</th>
                        <th>Nama Fakultas</th>
                        <th>Universitas</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($daftarFakultas) > 0): ?>
                        <?php foreach ($daftarFakultas as $i => $f): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo e($f['kode_fakultas']); ?></td>
                                <td><?php echo e($f['nama_fakultas']); ?></td>
                                <td><?php echo e($f['nama_universitas']); ?></td>
                                <td>
                                    <a class="btn edit"
                                       href="fakultas.php?edit=<?php echo (int) $f['id_fakultas']; ?>">Edit</a>

                                    <form class="inline" method="post" action="fakultas.php"
                                        onsubmit="return confirm('Yakin ingin menghapus fakultas ini?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id_fakultas"
                                            value="<?php echo (int) $f['id_fakultas']; ?>">
                                        <button class="btn delete" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="empty">Belum ada data fakultas atau data tidak ditemukan.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
</body>
</html>
