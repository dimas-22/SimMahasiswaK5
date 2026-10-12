
<?php
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

require_login();

$roleId = isset($_SESSION['id_role']) ? (int) $_SESSION['id_role'] : 0;
$roleNama = isset($_SESSION['nama_role']) ? strtolower(trim($_SESSION['nama_role'])) : '';

if ($roleId !== 1 && !in_array($roleNama, array('admin', 'administrator'), true)) {
    http_response_code(403);
    exit('Akses ditolak. Halaman ini khusus Admin.');
}

$error = '';
$success = '';
$mode = 'tambah';

$idEdit = 0;
$idProdi = '';
$npm = '';
$nama = '';
$jk = 'L';
$tempatLahir = '';
$tanggalLahir = '';
$tanggalMasuk = date('Y-m-d');
$alamat = '';
$statusMhs = 'Aktif';

$statusPilihan = array(
    'Aktif',
    'Cuti',
    'Lulus',
    'Mengundurkan Diri',
    'Drop Out',
    'Tidak Aktif'
);

function tanggalValid($tanggal, $wajib = false) {
    if ($tanggal === '') {
        return !$wajib;
    }

    $d = DateTime::createFromFormat('!Y-m-d', $tanggal);
    return $d && $d->format('Y-m-d') === $tanggal;
}

if (isset($_GET['status'])) {
    $pesan = array(
        'tambah' => 'Data mahasiswa berhasil ditambahkan.',
        'edit' => 'Data mahasiswa berhasil diperbarui.',
        'hapus' => 'Data mahasiswa berhasil dihapus.'
    );

    if (isset($pesan[$_GET['status']])) {
        $success = $pesan[$_GET['status']];
    }
}

// Daftar program studi
$daftarProdi = array();
$resProdi = $conn->query(
    "SELECT p.id_program_studi, p.kode_program_studi,
            p.nama_program_studi, p.jenjang, f.nama_fakultas
     FROM program_studi p
     INNER JOIN fakultas f ON f.id_fakultas = p.id_fakultas
     WHERE p.status_aktif = 'Aktif'
     ORDER BY f.nama_fakultas, p.nama_program_studi"
);

if ($resProdi) {
    while ($r = $resProdi->fetch_assoc()) {
        $daftarProdi[] = $r;
    }
}

// Ambil data untuk diedit
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($id && $id > 0) {
        $stmt = $conn->prepare(
            "SELECT * FROM mahasiswa WHERE id_mahasiswa = ?"
        );

        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $hasil = $stmt->get_result();

            if ($hasil && $hasil->num_rows > 0) {
                $d = $hasil->fetch_assoc();

                $idEdit = (int) $d['id_mahasiswa'];
                $idProdi = $d['id_program_studi'];
                $npm = $d['npm'];
                $nama = $d['nama_mahasiswa'];
                $jk = $d['jenis_kelamin'];
                $tempatLahir = $d['tempat_lahir'];
                $tanggalLahir = $d['tanggal_lahir'];
                $tanggalMasuk = $d['tanggal_masuk'];
                $alamat = $d['alamat'];
                $statusMhs = $d['status_mahasiswa'];
                $mode = 'edit';
            } else {
                $error = 'Data mahasiswa tidak ditemukan.';
            }

            $stmt->close();
        } else {
            $error = 'Gagal mengambil data mahasiswa.';
        }
    } else {
        $error = 'ID mahasiswa tidak valid.';
    }
}

// Proses tambah, edit, dan hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    } else {
        $aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';

        if ($aksi === 'tambah' || $aksi === 'edit') {
            $idEdit = isset($_POST['id_mahasiswa']) ? (int) $_POST['id_mahasiswa'] : 0;
            $idProdi = isset($_POST['id_program_studi']) ? (int) $_POST['id_program_studi'] : 0;
            $npm = isset($_POST['npm']) ? trim($_POST['npm']) : '';
            $nama = isset($_POST['nama_mahasiswa']) ? trim($_POST['nama_mahasiswa']) : '';
            $jk = isset($_POST['jenis_kelamin']) ? $_POST['jenis_kelamin'] : '';
            $tempatLahir = isset($_POST['tempat_lahir']) ? trim($_POST['tempat_lahir']) : '';
            $tanggalLahir = isset($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : '';
            $tanggalMasuk = isset($_POST['tanggal_masuk']) ? $_POST['tanggal_masuk'] : '';
            $alamat = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';
            $statusMhs = isset($_POST['status_mahasiswa']) ? $_POST['status_mahasiswa'] : '';

            if ($idProdi <= 0) {
                $error = 'Silakan pilih program studi.';
            } elseif ($npm === '' || $nama === '') {
                $error = 'NPM dan nama mahasiswa wajib diisi.';
            } elseif (strlen($npm) > 30 || strlen($nama) > 200) {
                $error = 'NPM maksimal 30 karakter dan nama maksimal 200 karakter.';
            } elseif (!in_array($jk, array('L', 'P'), true)) {
                $error = 'Jenis kelamin tidak valid.';
            } elseif (strlen($tempatLahir) > 100) {
                $error = 'Tempat lahir maksimal 100 karakter.';
            } elseif (!tanggalValid($tanggalLahir) || !tanggalValid($tanggalMasuk, true)) {
                $error = 'Format tanggal tidak valid.';
            } elseif (!in_array($statusMhs, $statusPilihan, true)) {
                $error = 'Status mahasiswa tidak valid.';
            } else {
                // Pastikan prodi tersedia
                $cek = $conn->prepare(
                    "SELECT id_program_studi FROM program_studi
                     WHERE id_program_studi = ? AND status_aktif = 'Aktif'"
                );

                $cek->bind_param('i', $idProdi);
                $cek->execute();
                $prodiAda = $cek->get_result()->num_rows > 0;
                $cek->close();

                if (!$prodiAda) {
                    $error = 'Program studi tidak ditemukan atau tidak aktif.';
                } elseif ($aksi === 'tambah') {
                    // NPM harus unik
                    $stmt = $conn->prepare(
                        "INSERT INTO mahasiswa
                         (id_program_studi, npm, nama_mahasiswa,
                          jenis_kelamin, tempat_lahir, tanggal_lahir,
                          tanggal_masuk, alamat, status_mahasiswa)
                         VALUES (?, ?, ?, ?, ?, NULLIF(?, ''),
                                 ?, NULLIF(?, ''), ?)"
                    );

                    if ($stmt) {
                        $stmt->bind_param(
                            'issssssss',
                            $idProdi,
                            $npm,
                            $nama,
                            $jk,
                            $tempatLahir,
                            $tanggalLahir,
                            $tanggalMasuk,
                            $alamat,
                            $statusMhs
                        );

                        if ($stmt->execute()) {
                            $stmt->close();
                            header('Location: mahasiswa.php?status=tambah');
                            exit;
                        }

                        $error = ($stmt->errno === 1062)
                            ? 'NPM sudah terdaftar.'
                            : 'Gagal menambahkan data mahasiswa.';
                        $stmt->close();
                    } else {
                        $error = 'Gagal menyiapkan perintah tambah.';
                    }
                } else {
                    if ($idEdit <= 0) {
                        $error = 'ID mahasiswa tidak valid.';
                    } else {
                        $stmt = $conn->prepare(
                            "UPDATE mahasiswa
                             SET id_program_studi = ?,
                                 npm = ?,
                                 nama_mahasiswa = ?,
                                 jenis_kelamin = ?,
                                 tempat_lahir = NULLIF(?, ''),
                                 tanggal_lahir = NULLIF(?, ''),
                                 tanggal_masuk = ?,
                                 alamat = NULLIF(?, ''),
                                 status_mahasiswa = ?
                             WHERE id_mahasiswa = ?"
                        );

                        if ($stmt) {
                            $stmt->bind_param(
                                'issssssssi',
                                $idProdi,
                                $npm,
                                $nama,
                                $jk,
                                $tempatLahir,
                                $tanggalLahir,
                                $tanggalMasuk,
                                $alamat,
                                $statusMhs,
                                $idEdit
                            );

                            if ($stmt->execute()) {
                                $stmt->close();
                                header('Location: mahasiswa.php?status=edit');
                                exit;
                            }

                            $error = ($stmt->errno === 1062)
                                ? 'NPM sudah digunakan mahasiswa lain.'
                                : 'Gagal memperbarui data mahasiswa.';
                            $stmt->close();
                        } else {
                            $error = 'Gagal menyiapkan perintah edit.';
                        }
                    }
                }
            }

            $mode = ($aksi === 'edit') ? 'edit' : 'tambah';
        } elseif ($aksi === 'hapus') {
            $idHapus = isset($_POST['id_mahasiswa']) ? (int) $_POST['id_mahasiswa'] : 0;

            if ($idHapus <= 0) {
                $error = 'ID mahasiswa tidak valid.';
            } else {
                $stmt = $conn->prepare(
                    "DELETE FROM mahasiswa WHERE id_mahasiswa = ?"
                );

                if ($stmt) {
                    $stmt->bind_param('i', $idHapus);

                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $stmt->close();
                        header('Location: mahasiswa.php?status=hapus');
                        exit;
                    }

                    $error = 'Data mahasiswa gagal dihapus.';
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
    ? trim($_GET['cari']) : '';

$sql = "SELECT m.id_mahasiswa, m.npm, m.nama_mahasiswa,
               m.jenis_kelamin, m.tempat_lahir, m.tanggal_lahir,
               m.tanggal_masuk, m.alamat, m.status_mahasiswa,
               m.id_program_studi, p.kode_program_studi,
               p.nama_program_studi, p.jenjang, f.nama_fakultas
        FROM mahasiswa m
        INNER JOIN program_studi p ON p.id_program_studi = m.id_program_studi
        INNER JOIN fakultas f ON f.id_fakultas = p.id_fakultas";

if ($cari !== '') {
    $sql .= " WHERE m.npm LIKE ?
              OR m.nama_mahasiswa LIKE ?
              OR p.nama_program_studi LIKE ?
              OR f.nama_fakultas LIKE ?";
}

$sql .= " ORDER BY m.id_mahasiswa DESC";

$daftarMhs = array();
$stmtList = $conn->prepare($sql);

if ($stmtList) {
    if ($cari !== '') {
        $keyword = '%' . $cari . '%';
        $stmtList->bind_param('ssss', $keyword, $keyword, $keyword, $keyword);
    }

    $stmtList->execute();
    $hasilList = $stmtList->get_result();

    while ($row = $hasilList->fetch_assoc()) {
        $daftarMhs[] = $row;
    }

    $stmtList->close();
} else {
    $error = 'Gagal mengambil daftar mahasiswa.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Mahasiswa - SIM Mahasiswa UMB</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--dark:#123b72;--blue:#1769aa;--hover:#245493;--bg:#f4f7fb;--border:#e1e8f0;--text:#263449;--muted:#7c8797}
body{font-family:Arial,Helvetica,sans-serif;background:var(--bg);color:var(--text);font-size:13px}
.topbar{height:58px;position:fixed;z-index:10;top:0;left:0;right:0;display:flex;align-items:center;justify-content:space-between;padding:0 22px;background:#fff;border-bottom:1px solid var(--border)}
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
input,select,textarea{width:100%;padding:11px 12px;border:1px solid #d5deea;border-radius:7px;background:#fff;font:inherit;color:var(--text)}
textarea{resize:vertical;min-height:76px}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--blue);box-shadow:0 0 0 3px #1769aa18}
.actions{display:flex;gap:8px;margin-top:18px;flex-wrap:wrap}
.btn{display:inline-block;padding:10px 13px;border:0;border-radius:7px;cursor:pointer;font-size:12px;font-weight:bold;text-decoration:none}
.primary{background:var(--blue);color:white}.light{background:#edf1f6;color:#344054}.edit{background:#e7f1ff;color:#1759a5}.delete{background:#fff0f0;color:#bd3030}
.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap}
.search{display:flex;gap:8px;max-width:460px;flex:1}.search input{min-width:100px}
.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;white-space:nowrap}
th,td{padding:13px 14px;border-bottom:1px solid var(--border);text-align:left}
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
    <a class="nav-link active" href="mahasiswa.php">🎓 &nbsp; Data Mahasiswa</a>
    <a class="nav-link" href="universitas.php">🏫 &nbsp; Universitas</a>
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
    <h1>Data Mahasiswa</h1>
    <div class="subtitle">Kelola identitas dan status akademik mahasiswa UMB.</div>

    <?php if ($success !== ''): ?>
        <div class="alert ok"><?php echo e($success); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert err"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="panel">
        <div class="panel-heading">
            <?php echo $mode === 'edit' ? 'Edit Data Mahasiswa' : 'Tambah Mahasiswa'; ?>
        </div>
        <div class="panel-body">
            <form method="post" action="mahasiswa.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="aksi" value="<?php echo e($mode); ?>">
                <input type="hidden" name="id_mahasiswa" value="<?php echo (int) $idEdit; ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="npm">NPM *</label>
                        <input id="npm" name="npm" maxlength="30" required
                               value="<?php echo e($npm); ?>" placeholder="Masukkan NPM">
                    </div>

                    <div class="form-group">
                        <label for="nama_mahasiswa">Nama Lengkap *</label>
                        <input id="nama_mahasiswa" name="nama_mahasiswa" maxlength="200" required
                               value="<?php echo e($nama); ?>" placeholder="Nama mahasiswa">
                    </div>

                    <div class="form-group">
                        <label for="id_program_studi">Program Studi *</label>
                        <select id="id_program_studi" name="id_program_studi" required>
                            <option value="">-- Pilih Program Studi --</option>
                            <?php foreach ($daftarProdi as $p): ?>
                                <option value="<?php echo (int) $p['id_program_studi']; ?>"
                                    <?php echo ((string) $idProdi === (string) $p['id_program_studi']) ? 'selected' : ''; ?>>
                                    <?php echo e($p['kode_program_studi'] . ' - ' . $p['nama_program_studi'] . ' (' . $p['jenjang'] . ') / ' . $p['nama_fakultas']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="jenis_kelamin">Jenis Kelamin *</label>
                        <select id="jenis_kelamin" name="jenis_kelamin" required>
                            <option value="L" <?php echo $jk === 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="P" <?php echo $jk === 'P' ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tempat_lahir">Tempat Lahir</label>
                        <input id="tempat_lahir" name="tempat_lahir" maxlength="100"
                               value="<?php echo e($tempatLahir); ?>" placeholder="Contoh: Bengkulu">
                    </div>

                    <div class="form-group">
                        <label for="tanggal_lahir">Tanggal Lahir</label>
                        <input type="date" id="tanggal_lahir" name="tanggal_lahir"
                               value="<?php echo e($tanggalLahir); ?>">
                    </div>

                    <div class="form-group">
                        <label for="tanggal_masuk">Tanggal Masuk *</label>
                        <input type="date" id="tanggal_masuk" name="tanggal_masuk" required
                               value="<?php echo e($tanggalMasuk); ?>">
                    </div>

                    <div class="form-group">
                        <label for="status_mahasiswa">Status Mahasiswa *</label>
                        <select id="status_mahasiswa" name="status_mahasiswa" required>
                            <?php foreach ($statusPilihan as $s): ?>
                                <option value="<?php echo e($s); ?>"
                                    <?php echo $statusMhs === $s ? 'selected' : ''; ?>>
                                    <?php echo e($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="grid-column:1/-1">
                        <label for="alamat">Alamat</label>
                        <textarea id="alamat" name="alamat" placeholder="Masukkan alamat mahasiswa"><?php echo e($alamat); ?></textarea>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn primary">
                        <?php echo $mode === 'edit' ? 'Simpan Perubahan' : 'Tambah Mahasiswa'; ?>
                    </button>
                    <?php if ($mode === 'edit'): ?>
                        <a href="mahasiswa.php" class="btn light">Batal</a>
                    <?php else: ?>
                        <button type="reset" class="btn light">Reset</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">Daftar Mahasiswa</div>
        <div class="panel-body">
            <div class="toolbar">
                <span>Total data: <strong><?php echo count($daftarMhs); ?></strong></span>
                <form class="search" method="get" action="mahasiswa.php">
                    <input type="search" name="cari"
                           placeholder="Cari NPM, nama, prodi, fakultas..."
                           value="<?php echo e($cari); ?>">
                    <button class="btn primary" type="submit">Cari</button>
                    <?php if ($cari !== ''): ?>
                        <a href="mahasiswa.php" class="btn light">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>No.</th>
                        <th>NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Program Studi</th>
                        <th>Fakultas</th>
                        <th>JK</th>
                        <th>Tanggal Masuk</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($daftarMhs) > 0): ?>
                        <?php foreach ($daftarMhs as $i => $m): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo e($m['npm']); ?></td>
                                <td><?php echo e($m['nama_mahasiswa']); ?></td>
                                <td><?php echo e($m['nama_program_studi'] . ' (' . $m['jenjang'] . ')'); ?></td>
                                <td><?php echo e($m['nama_fakultas']); ?></td>
                                <td><?php echo $m['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                                <td><?php echo e($m['tanggal_masuk']); ?></td>
                                <td>
                                    <span class="badge <?php echo $m['status_mahasiswa'] === 'Aktif' ? '' : 'badge-off'; ?>">
                                        <?php echo e($m['status_mahasiswa']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a class="btn edit" href="mahasiswa.php?edit=<?php echo (int) $m['id_mahasiswa']; ?>">Edit</a>
                                    <form class="inline" method="post" action="mahasiswa.php"
                                          onsubmit="return confirm('Yakin ingin menghapus data mahasiswa ini?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id_mahasiswa" value="<?php echo (int) $m['id_mahasiswa']; ?>">
                                        <button type="submit" class="btn delete">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="empty">Belum ada data mahasiswa atau data tidak ditemukan.</td>
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
