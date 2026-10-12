
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

if ($roleId !== 1 && !in_array($roleNama, array('admin', 'administrator'), true)) {
    http_response_code(403);
    exit('Akses ditolak. Halaman ini khusus Admin.');
}

$error = '';
$success = '';
$mode = 'tambah';
$idEdit = 0;
$idFakultas = '';
$kodeProdi = '';
$namaProdi = '';
$jenjang = 'S1';
$statusAktif = 'Aktif';

// Notifikasi
if (isset($_GET['status'])) {
    $pesan = array(
        'tambah' => 'Program studi berhasil ditambahkan.',
        'edit' => 'Program studi berhasil diperbarui.',
        'hapus' => 'Program studi berhasil dihapus.'
    );

    if (isset($pesan[$_GET['status']])) {
        $success = $pesan[$_GET['status']];
    }
}

// Daftar fakultas
$daftarFakultas = array();
$resFakultas = $conn->query(
    "SELECT id_fakultas, kode_fakultas, nama_fakultas
     FROM fakultas
     ORDER BY nama_fakultas ASC"
);

if ($resFakultas) {
    while ($row = $resFakultas->fetch_assoc()) {
        $daftarFakultas[] = $row;
    }
}

// Ambil data untuk diedit
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($id && $id > 0) {
        $stmt = $conn->prepare(
            "SELECT id_program_studi, id_fakultas,
                    kode_program_studi, nama_program_studi,
                    jenjang, status_aktif
             FROM program_studi
             WHERE id_program_studi = ?"
        );

        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $hasil = $stmt->get_result();

            if ($hasil && $hasil->num_rows > 0) {
                $data = $hasil->fetch_assoc();
                $idEdit = (int) $data['id_program_studi'];
                $idFakultas = $data['id_fakultas'];
                $kodeProdi = $data['kode_program_studi'];
                $namaProdi = $data['nama_program_studi'];
                $jenjang = $data['jenjang'];
                $statusAktif = $data['status_aktif'];
                $mode = 'edit';
            } else {
                $error = 'Program studi tidak ditemukan.';
            }

            $stmt->close();
        } else {
            $error = 'Gagal mengambil data program studi.';
        }
    } else {
        $error = 'ID program studi tidak valid.';
    }
}

// Proses formulir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'Token keamanan tidak valid. Muat ulang halaman.';
    } else {
        $aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';

        // Tambah atau edit
        if ($aksi === 'tambah' || $aksi === 'edit') {
            $idEdit = isset($_POST['id_program_studi'])
                ? (int) $_POST['id_program_studi'] : 0;

            $idFakultas = isset($_POST['id_fakultas'])
                ? (int) $_POST['id_fakultas'] : 0;

            $kodeProdi = isset($_POST['kode_program_studi'])
                ? trim($_POST['kode_program_studi']) : '';

            $namaProdi = isset($_POST['nama_program_studi'])
                ? trim($_POST['nama_program_studi']) : '';

            $jenjang = isset($_POST['jenjang'])
                ? trim($_POST['jenjang']) : '';

            $statusAktif = isset($_POST['status_aktif'])
                ? trim($_POST['status_aktif']) : '';

            if ($idFakultas <= 0) {
                $error = 'Silakan pilih fakultas.';
            } elseif ($kodeProdi === '' || $namaProdi === '') {
                $error = 'Kode dan nama program studi wajib diisi.';
            } elseif (strlen($kodeProdi) > 20) {
                $error = 'Kode program studi maksimal 20 karakter.';
            } elseif (strlen($namaProdi) > 200) {
                $error = 'Nama program studi maksimal 200 karakter.';
            } elseif (!in_array($jenjang, array('D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3', 'Profesi'), true)) {
                $error = 'Jenjang pendidikan tidak valid.';
            } elseif (!in_array($statusAktif, array('Aktif', 'Tidak Aktif'), true)) {
                $error = 'Status program studi tidak valid.';
            } else {
                // Pastikan fakultas tersedia
                $cek = $conn->prepare(
                    "SELECT id_fakultas
                     FROM fakultas
                     WHERE id_fakultas = ?"
                );

                $cek->bind_param('i', $idFakultas);
                $cek->execute();
                $fakultasAda = $cek->get_result()->num_rows > 0;
                $cek->close();

                if (!$fakultasAda) {
                    $error = 'Fakultas yang dipilih tidak ditemukan.';
                } elseif ($aksi === 'tambah') {
                    $stmt = $conn->prepare(
                        "INSERT INTO program_studi
                         (id_fakultas, kode_program_studi,
                          nama_program_studi, jenjang, status_aktif)
                         VALUES (?, ?, ?, ?, ?)"
                    );

                    if ($stmt) {
                        $stmt->bind_param(
                            'issss',
                            $idFakultas,
                            $kodeProdi,
                            $namaProdi,
                            $jenjang,
                            $statusAktif
                        );

                        if ($stmt->execute()) {
                            $stmt->close();
                            header('Location: program_studi.php?status=tambah');
                            exit;
                        } else {
                            $error = ($stmt->errno === 1062)
                                ? 'Kode program studi tersebut sudah digunakan pada fakultas yang sama.'
                                : 'Gagal menambahkan program studi.';
                        }

                        $stmt->close();
                    } else {
                        $error = 'Gagal menyiapkan perintah tambah.';
                    }
                } else {
                    if ($idEdit <= 0) {
                        $error = 'ID program studi tidak valid.';
                    } else {
                        $stmt = $conn->prepare(
                            "UPDATE program_studi
                             SET id_fakultas = ?,
                                 kode_program_studi = ?,
                                 nama_program_studi = ?,
                                 jenjang = ?,
                                 status_aktif = ?
                             WHERE id_program_studi = ?"
                        );

                        if ($stmt) {
                            $stmt->bind_param(
                                'issssi',
                                $idFakultas,
                                $kodeProdi,
                                $namaProdi,
                                $jenjang,
                                $statusAktif,
                                $idEdit
                            );

                            if ($stmt->execute()) {
                                $stmt->close();
                                header('Location: program_studi.php?status=edit');
                                exit;
                            } else {
                                $error = ($stmt->errno === 1062)
                                    ? 'Kode program studi sudah digunakan pada fakultas tersebut.'
                                    : 'Gagal memperbarui program studi.';
                            }

                            $stmt->close();
                        } else {
                            $error = 'Gagal menyiapkan perintah edit.';
                        }
                    }
                }
            }

            $mode = ($aksi === 'edit') ? 'edit' : 'tambah';
        }

        // Hapus
        elseif ($aksi === 'hapus') {
            $idHapus = isset($_POST['id_program_studi'])
                ? (int) $_POST['id_program_studi'] : 0;

            if ($idHapus <= 0) {
                $error = 'ID program studi tidak valid.';
            } else {
                $stmt = $conn->prepare(
                    "DELETE FROM program_studi
                     WHERE id_program_studi = ?"
                );

                if ($stmt) {
                    $stmt->bind_param('i', $idHapus);

                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $stmt->close();
                        header('Location: program_studi.php?status=hapus');
                        exit;
                    } else {
                        $error = 'Program studi tidak dapat dihapus. Pastikan belum digunakan oleh data mahasiswa atau pengguna.';
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

// Pencarian
$cari = isset($_GET['cari']) && is_string($_GET['cari'])
    ? trim($_GET['cari'])
    : '';

$sql = "SELECT p.id_program_studi, p.id_fakultas,
               p.kode_program_studi, p.nama_program_studi,
               p.jenjang, p.status_aktif, f.nama_fakultas
        FROM program_studi p
        INNER JOIN fakultas f ON f.id_fakultas = p.id_fakultas";

if ($cari !== '') {
    $sql .= " WHERE p.kode_program_studi LIKE ?
              OR p.nama_program_studi LIKE ?
              OR p.jenjang LIKE ?
              OR f.nama_fakultas LIKE ?";
}

$sql .= " ORDER BY p.id_program_studi DESC";

$stmtList = $conn->prepare($sql);
$daftarProdi = array();

if ($stmtList) {
    if ($cari !== '') {
        $keyword = '%' . $cari . '%';
        $stmtList->bind_param(
            'ssss',
            $keyword,
            $keyword,
            $keyword,
            $keyword
        );
    }

    $stmtList->execute();
    $hasilList = $stmtList->get_result();

    while ($row = $hasilList->fetch_assoc()) {
        $daftarProdi[] = $row;
    }

    $stmtList->close();
} else {
    $error = 'Gagal mengambil daftar program studi.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Program Studi - SIM Mahasiswa UMB</title>

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
.search{display:flex;gap:8px;max-width:460px;flex:1}.search input{min-width:100px}
.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;white-space:nowrap}
th,td{padding:13px 15px;border-bottom:1px solid var(--border);text-align:left}
th{background:#f7f9fc;color:#596579;font-size:11px}td{font-size:12px}tbody tr:hover{background:#f8fbff}
.inline{display:inline}.empty{text-align:center;padding:30px;color:var(--muted)}
.badge{display:inline-block;padding:5px 9px;border-radius:20px;background:#e7f1ff;color:#1759a5;font-size:11px;font-weight:bold}
.badge-off{background:#fff0f0;color:#bd3030}
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
    <a class="nav-link" href="fakultas.php">🏢 &nbsp; Fakultas</a>
    <a class="nav-link active" href="program_studi.php">📚 &nbsp; Program Studi</a>
    <a class="nav-link" href="pengguna.php">👥 &nbsp; Pengguna</a>
    <a class="nav-link" href="role.php">🔐 &nbsp; Role</a>
    <div class="menu-heading">LAPORAN</div>
    <a class="nav-link" href="rekap_mahasiswa.php">📊 &nbsp; Rekap Mahasiswa</a>
    <div class="menu-heading">SISTEM</div>
    <a class="nav-link" href="audit_log.php">📝 &nbsp; Audit Log</a>
</aside>

<main class="main">
    <div class="page-label">DATA MASTER</div>
    <h1>Data Program Studi</h1>
    <div class="subtitle">Kelola program studi berdasarkan fakultas di Universitas Muhammadiyah Bengkulu.</div>

    <?php if ($success !== ''): ?>
        <div class="alert ok"><?php echo e($success); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert err"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="panel">
        <div class="panel-heading">
            <?php echo $mode === 'edit' ? 'Edit Program Studi' : 'Tambah Program Studi'; ?>
        </div>
        <div class="panel-body">
            <form method="post" action="program_studi.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="aksi" value="<?php echo e($mode); ?>">
                <input type="hidden" name="id_program_studi" value="<?php echo (int) $idEdit; ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="id_fakultas">Fakultas *</label>
                        <select name="id_fakultas" id="id_fakultas" required>
                            <option value="">-- Pilih Fakultas --</option>
                            <?php foreach ($daftarFakultas as $f): ?>
                                <option value="<?php echo (int) $f['id_fakultas']; ?>"
                                    <?php echo ((string) $idFakultas === (string) $f['id_fakultas']) ? 'selected' : ''; ?>>
                                    <?php echo e($f['kode_fakultas'] . ' - ' . $f['nama_fakultas']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="kode_program_studi">Kode Program Studi *</label>
                        <input type="text" id="kode_program_studi"
                            name="kode_program_studi" maxlength="20" required
                            placeholder="Contoh: TI"
                            value="<?php echo e($kodeProdi); ?>">
                    </div>

                    <div class="form-group">
                        <label for="nama_program_studi">Nama Program Studi *</label>
                        <input type="text" id="nama_program_studi"
                            name="nama_program_studi" maxlength="200" required
                            placeholder="Contoh: Teknik Informatika"
                            value="<?php echo e($namaProdi); ?>">
                    </div>

                    <div class="form-group">
                        <label for="jenjang">Jenjang Pendidikan *</label>
                        <select id="jenjang" name="jenjang" required>
                            <?php foreach (array('D1','D2','D3','D4','S1','S2','S3','Profesi') as $j): ?>
                                <option value="<?php echo e($j); ?>"
                                    <?php echo $jenjang === $j ? 'selected' : ''; ?>>
                                    <?php echo e($j); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status_aktif">Status *</label>
                        <select id="status_aktif" name="status_aktif" required>
                            <option value="Aktif" <?php echo $statusAktif === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="Tidak Aktif" <?php echo $statusAktif === 'Tidak Aktif' ? 'selected' : ''; ?>>Tidak Aktif</option>
                        </select>
                    </div>
                </div>

                <div class="actions">
                    <button class="btn primary" type="submit">
                        <?php echo $mode === 'edit' ? 'Simpan Perubahan' : 'Tambah Program Studi'; ?>
                    </button>
                    <?php if ($mode === 'edit'): ?>
                        <a href="program_studi.php" class="btn light">Batal</a>
                    <?php else: ?>
                        <button type="reset" class="btn light">Reset</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">Daftar Program Studi</div>
        <div class="panel-body">
            <div class="toolbar">
                <span>Total data: <strong><?php echo count($daftarProdi); ?></strong></span>
                <form class="search" method="get" action="program_studi.php">
                    <input type="search" name="cari"
                        placeholder="Cari kode, nama prodi, atau fakultas..."
                        value="<?php echo e($cari); ?>">
                    <button class="btn primary" type="submit">Cari</button>
                    <?php if ($cari !== ''): ?>
                        <a class="btn light" href="program_studi.php">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Kode</th>
                            <th>Nama Program Studi</th>
                            <th>Fakultas</th>
                            <th>Jenjang</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($daftarProdi) > 0): ?>
                        <?php foreach ($daftarProdi as $i => $p): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo e($p['kode_program_studi']); ?></td>
                                <td><?php echo e($p['nama_program_studi']); ?></td>
                                <td><?php echo e($p['nama_fakultas']); ?></td>
                                <td><?php echo e($p['jenjang']); ?></td>
                                <td>
                                    <span class="badge <?php echo $p['status_aktif'] === 'Aktif' ? '' : 'badge-off'; ?>">
                                        <?php echo e($p['status_aktif']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a class="btn edit"
                                        href="program_studi.php?edit=<?php echo (int) $p['id_program_studi']; ?>">Edit</a>

                                    <form class="inline" method="post" action="program_studi.php"
                                        onsubmit="return confirm('Yakin ingin menghapus program studi ini?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id_program_studi"
                                            value="<?php echo (int) $p['id_program_studi']; ?>">
                                        <button class="btn delete" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty">Belum ada data program studi atau data tidak ditemukan.</td>
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
