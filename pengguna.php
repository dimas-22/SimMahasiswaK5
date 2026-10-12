
<?php
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

require_login();

$roleIdLogin = isset($_SESSION['id_role']) ? (int) $_SESSION['id_role'] : 0;
$roleNamaLogin = isset($_SESSION['nama_role']) ? strtolower(trim($_SESSION['nama_role'])) : '';

if ($roleIdLogin !== 1 && !in_array($roleNamaLogin, array('admin', 'administrator'), true)) {
    http_response_code(403);
    exit('Akses ditolak. Halaman ini khusus Admin.');
}

$error = '';
$success = '';
$mode = 'tambah';
$idEdit = 0;

$idRole = '';
$idUniversitas = '1';
$idFakultas = '';
$idProdi = '';
$username = '';
$nama = '';
$email = '';
$noHp = '';
$status = 'Aktif';

$statusPilihan = array('Aktif', 'Tidak Aktif');

function penggunaStatusValid($status, $pilihan) {
    return in_array($status, $pilihan, true);
}

function penggunaAmbil($key) {
    return isset($_POST[$key]) && is_string($_POST[$key])
        ? trim($_POST[$key]) : '';
}

if (isset($_GET['status'])) {
    $pesan = array(
        'tambah' => 'Pengguna berhasil ditambahkan.',
        'edit' => 'Data pengguna berhasil diperbarui.',
        'hapus' => 'Pengguna berhasil dihapus.'
    );

    if (isset($pesan[$_GET['status']])) {
        $success = $pesan[$_GET['status']];
    }
}

// Ambil role
$roles = array();
$res = $conn->query("SELECT id_role, kode_role, nama_role FROM roles ORDER BY id_role");

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $roles[] = $r;
    }
}

// Ambil universitas
$universitas = array();
$res = $conn->query(
    "SELECT id_universitas, nama_universitas FROM universitas ORDER BY nama_universitas"
);

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $universitas[] = $r;
    }
}

// Ambil fakultas
$fakultas = array();
$res = $conn->query(
    "SELECT id_fakultas, id_universitas, nama_fakultas
     FROM fakultas ORDER BY nama_fakultas"
);

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $fakultas[] = $r;
    }
}

// Ambil program studi
$programStudi = array();
$res = $conn->query(
    "SELECT id_program_studi, id_fakultas, nama_program_studi, jenjang
     FROM program_studi ORDER BY nama_program_studi"
);

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $programStudi[] = $r;
    }
}

// Ambil data untuk diedit
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($id && $id > 0) {
        $stmt = $conn->prepare(
            "SELECT id_pengguna, id_role, id_universitas, id_fakultas,
                    id_program_studi, username, nama_lengkap, email,
                    no_hp, status_aktif
             FROM pengguna WHERE id_pengguna = ? LIMIT 1"
        );

        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $hasil = $stmt->get_result();
            $d = $hasil ? $hasil->fetch_assoc() : null;

            if ($d) {
                $idEdit = (int) $d['id_pengguna'];
                $idRole = (string) $d['id_role'];
                $idUniversitas = (string) $d['id_universitas'];
                $idFakultas = (string) $d['id_fakultas'];
                $idProdi = (string) $d['id_program_studi'];
                $username = $d['username'];
                $nama = $d['nama_lengkap'];
                $email = $d['email'];
                $noHp = $d['no_hp'];
                $status = $d['status_aktif'];
                $mode = 'edit';
            } else {
                $error = 'Pengguna tidak ditemukan.';
            }

            $stmt->close();
        } else {
            $error = 'Gagal mengambil data pengguna.';
        }
    } else {
        $error = 'ID pengguna tidak valid.';
    }
}

// Proses formulir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'Token keamanan tidak valid. Muat ulang halaman dan coba kembali.';
    } else {
        $aksi = penggunaAmbil('aksi');

        if ($aksi === 'tambah' || $aksi === 'edit') {
            $idEdit = (int) penggunaAmbil('id_pengguna');
            $idRole = penggunaAmbil('id_role');
            $idUniversitas = penggunaAmbil('id_universitas');
            $idFakultas = penggunaAmbil('id_fakultas');
            $idProdi = penggunaAmbil('id_program_studi');
            $username = penggunaAmbil('username');
            $nama = penggunaAmbil('nama_lengkap');
            $email = penggunaAmbil('email');
            $noHp = penggunaAmbil('no_hp');
            $status = penggunaAmbil('status_aktif');
            $password = isset($_POST['password']) && is_string($_POST['password'])
                ? $_POST['password'] : '';

            $roleInt = (int) $idRole;
            $univInt = (int) $idUniversitas;
            $fakultasInt = $idFakultas === '' ? 0 : (int) $idFakultas;
            $prodiInt = $idProdi === '' ? 0 : (int) $idProdi;

            if ($roleInt <= 0 || $univInt <= 0) {
                $error = 'Role dan universitas wajib dipilih.';
            } elseif ($username === '' || $nama === '') {
                $error = 'Username dan nama lengkap wajib diisi.';
            } elseif (strlen($username) > 100 || strlen($nama) > 200) {
                $error = 'Username maksimal 100 karakter dan nama maksimal 200 karakter.';
            } elseif ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150)) {
                $error = 'Format email tidak valid atau terlalu panjang.';
            } elseif (strlen($noHp) > 30) {
                $error = 'Nomor HP maksimal 30 karakter.';
            } elseif (!penggunaStatusValid($status, $statusPilihan)) {
                $error = 'Status akun tidak valid.';
            } elseif ($aksi === 'tambah' && strlen($password) < 8) {
                $error = 'Password baru wajib diisi minimal 8 karakter.';
            } elseif ($aksi === 'edit' && $password !== '' && strlen($password) < 8) {
                $error = 'Password baru harus minimal 8 karakter.';
            } else {
                // Validasi role dan universitas
                $cek = $conn->prepare(
                    "SELECT COUNT(*) AS total FROM roles WHERE id_role = ?"
                );
                $cek->bind_param('i', $roleInt);
                $cek->execute();
                $roleAda = (int) $cek->get_result()->fetch_assoc()['total'] > 0;
                $cek->close();

                $cek = $conn->prepare(
                    "SELECT COUNT(*) AS total FROM universitas WHERE id_universitas = ?"
                );
                $cek->bind_param('i', $univInt);
                $cek->execute();
                $univAda = (int) $cek->get_result()->fetch_assoc()['total'] > 0;
                $cek->close();

                // Validasi fakultas jika diisi
                $fakultasValid = true;
                if ($fakultasInt > 0) {
                    $cek = $conn->prepare(
                        "SELECT COUNT(*) AS total FROM fakultas
                         WHERE id_fakultas = ? AND id_universitas = ?"
                    );
                    $cek->bind_param('ii', $fakultasInt, $univInt);
                    $cek->execute();
                    $fakultasValid = (int) $cek->get_result()->fetch_assoc()['total'] > 0;
                    $cek->close();
                }

                // Validasi program studi jika diisi
                $prodiValid = true;
                if ($prodiInt > 0) {
                    $cek = $conn->prepare(
                        "SELECT COUNT(*) AS total FROM program_studi p
                         INNER JOIN fakultas f ON f.id_fakultas = p.id_fakultas
                         WHERE p.id_program_studi = ?
                         AND f.id_universitas = ?
                         AND (? = 0 OR p.id_fakultas = ?)"
                    );
                    $cek->bind_param('iiii', $prodiInt, $univInt, $fakultasInt, $fakultasInt);
                    $cek->execute();
                    $prodiValid = (int) $cek->get_result()->fetch_assoc()['total'] > 0;
                    $cek->close();
                }

                if (!$roleAda || !$univAda || !$fakultasValid || !$prodiValid) {
                    $error = 'Role, universitas, fakultas, atau program studi tidak sesuai.';
                } else {
                    // Periksa username duplikat
                    $cekSql = "SELECT COUNT(*) AS total FROM pengguna WHERE username = ?";
                    if ($aksi === 'edit') {
                        $cekSql .= " AND id_pengguna <> ?";
                    }

                    $cek = $conn->prepare($cekSql);

                    if ($aksi === 'edit') {
                        $cek->bind_param('si', $username, $idEdit);
                    } else {
                        $cek->bind_param('s', $username);
                    }

                    $cek->execute();
                    $usernameAda = (int) $cek->get_result()->fetch_assoc()['total'] > 0;
                    $cek->close();

                    if ($usernameAda) {
                        $error = 'Username sudah digunakan.';
                    } else {
                        $emailDb = $email === '' ? null : $email;
                        $noHpDb = $noHp === '' ? null : $noHp;
                        $fakultasDb = $fakultasInt > 0 ? $fakultasInt : null;
                        $prodiDb = $prodiInt > 0 ? $prodiInt : null;

                        if ($aksi === 'tambah') {
                            $hash = password_hash($password, PASSWORD_DEFAULT);

                            $stmt = $conn->prepare(
                                "INSERT INTO pengguna
                                 (id_role, id_universitas, id_fakultas, id_program_studi,
                                  username, password_hash, nama_lengkap, email, no_hp, status_aktif)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                            );

                            if ($stmt) {
                                $stmt->bind_param(
                                    'iiiissssss',
                                    $roleInt, $univInt, $fakultasDb, $prodiDb,
                                    $username, $hash, $nama, $emailDb, $noHpDb, $status
                                );

                                if ($stmt->execute()) {
                                    $stmt->close();
                                    header('Location: pengguna.php?status=tambah');
                                    exit;
                                }

                                $error = 'Gagal menambahkan pengguna. Periksa kembali data yang diisi.';
                                $stmt->close();
                            } else {
                                $error = 'Gagal menyiapkan perintah tambah pengguna.';
                            }
                        } else {
                            if ($idEdit <= 0) {
                                $error = 'ID pengguna tidak valid.';
                            } elseif ($idEdit === (int) $_SESSION['id_pengguna'] && $status !== 'Aktif') {
                                $error = 'Anda tidak dapat menonaktifkan akun yang sedang digunakan.';
                            } else {
                                if ($password !== '') {
                                    $hash = password_hash($password, PASSWORD_DEFAULT);

                                    $stmt = $conn->prepare(
                                        "UPDATE pengguna SET
                                         id_role = ?, id_universitas = ?, id_fakultas = ?,
                                         id_program_studi = ?, username = ?, password_hash = ?,
                                         nama_lengkap = ?, email = ?, no_hp = ?, status_aktif = ?
                                         WHERE id_pengguna = ?"
                                    );

                                    if ($stmt) {
                                        $stmt->bind_param(
                                            'iiiissssssi',
                                            $roleInt, $univInt, $fakultasDb, $prodiDb,
                                            $username, $hash, $nama, $emailDb, $noHpDb,
                                            $status, $idEdit
                                        );
                                    }
                                } else {
                                    $stmt = $conn->prepare(
                                        "UPDATE pengguna SET
                                         id_role = ?, id_universitas = ?, id_fakultas = ?,
                                         id_program_studi = ?, username = ?, nama_lengkap = ?,
                                         email = ?, no_hp = ?, status_aktif = ?
                                         WHERE id_pengguna = ?"
                                    );

                                    if ($stmt) {
                                        $stmt->bind_param(
                                            'iiiisssssi',
                                            $roleInt, $univInt, $fakultasDb, $prodiDb,
                                            $username, $nama, $emailDb, $noHpDb, $status, $idEdit
                                        );
                                    }
                                }

                                if (isset($stmt) && $stmt) {
                                    if ($stmt->execute()) {
                                        $stmt->close();
                                        header('Location: pengguna.php?status=edit');
                                        exit;
                                    }

                                    $error = 'Gagal memperbarui pengguna.';
                                    $stmt->close();
                                } else {
                                    $error = 'Gagal menyiapkan perintah edit pengguna.';
                                }
                            }
                        }
                    }
                }
            }

            $mode = $aksi === 'edit' ? 'edit' : 'tambah';
        } elseif ($aksi === 'hapus') {
            $idHapus = (int) penggunaAmbil('id_pengguna');

            if ($idHapus <= 0) {
                $error = 'ID pengguna tidak valid.';
            } elseif ($idHapus === (int) $_SESSION['id_pengguna']) {
                $error = 'Anda tidak dapat menghapus akun yang sedang digunakan.';
            } else {
                $stmt = $conn->prepare(
                    "DELETE FROM pengguna WHERE id_pengguna = ?"
                );

                if ($stmt) {
                    $stmt->bind_param('i', $idHapus);

                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $stmt->close();
                        header('Location: pengguna.php?status=hapus');
                        exit;
                    }

                    $error = 'Pengguna gagal dihapus. Akun mungkin sudah tidak ada atau masih terhubung dengan data lain.';
                    $stmt->close();
                } else {
                    $error = 'Gagal menyiapkan perintah hapus pengguna.';
                }
            }
        } else {
            $error = 'Aksi tidak dikenal.';
        }
    }
}

// Pencarian pengguna
$cari = isset($_GET['cari']) && is_string($_GET['cari'])
    ? trim($_GET['cari']) : '';

$sql = "SELECT p.id_pengguna, p.username, p.nama_lengkap, p.email,
               p.no_hp, p.status_aktif, p.id_role,
               r.nama_role, u.nama_universitas,
               f.nama_fakultas, ps.nama_program_studi
        FROM pengguna p
        LEFT JOIN roles r ON r.id_role = p.id_role
        LEFT JOIN universitas u ON u.id_universitas = p.id_universitas
        LEFT JOIN fakultas f ON f.id_fakultas = p.id_fakultas
        LEFT JOIN program_studi ps ON ps.id_program_studi = p.id_program_studi";

if ($cari !== '') {
    $sql .= " WHERE p.username LIKE ?
              OR p.nama_lengkap LIKE ?
              OR p.email LIKE ?
              OR r.nama_role LIKE ?";
}

$sql .= " ORDER BY p.id_pengguna DESC";

$daftar = array();
$stmt = $conn->prepare($sql);

if ($stmt) {
    if ($cari !== '') {
        $keyword = '%' . $cari . '%';
        $stmt->bind_param('ssss', $keyword, $keyword, $keyword, $keyword);
    }

    $stmt->execute();
    $hasil = $stmt->get_result();

    while ($row = $hasil->fetch_assoc()) {
        $daftar[] = $row;
    }

    $stmt->close();
} else {
    $error = 'Gagal mengambil daftar pengguna.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pengguna - SIM Mahasiswa UMB</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,Helvetica,sans-serif;background:#f4f7fb;color:#263449;font-size:13px}
.topbar{height:58px;position:fixed;z-index:10;top:0;left:0;right:0;background:#fff;border-bottom:1px solid #e1e8f0;display:flex;align-items:center;justify-content:space-between;padding:0 22px}
.brand{font-size:15px;font-weight:bold}.brand small{display:block;margin-top:4px;color:#7c8797;font-size:10px;font-weight:normal}
.top-right{display:flex;gap:12px;align-items:center}.top-right a{color:#dc3545;text-decoration:none}
.sidebar{position:fixed;top:58px;bottom:0;left:0;width:220px;overflow-y:auto;background:#123b72;padding:22px 12px}
.menu-heading{margin:10px 8px;color:#b7cbe6;font-size:10px;font-weight:bold;letter-spacing:1px}
.nav-link{display:block;padding:12px 10px;margin-bottom:4px;border-radius:6px;color:#edf4ff;text-decoration:none}
.nav-link:hover,.nav-link.active{background:#245493}
.main{margin-left:220px;padding:88px 25px 30px;min-height:100vh}
.page-label{margin-bottom:8px;color:#1769aa;font-size:10px;font-weight:bold;letter-spacing:1px}
h1{font-size:23px;margin-bottom:7px}.subtitle{margin-bottom:22px;color:#7c8797;line-height:1.5}
.panel{margin-bottom:20px;overflow:hidden;background:white;border:1px solid #e1e8f0;border-radius:11px}
.panel-heading{padding:17px 20px;border-bottom:1px solid #e1e8f0;font-weight:bold}.panel-body{padding:20px}
.alert{padding:12px 15px;margin-bottom:18px;border-radius:7px;line-height:1.5}
.ok{background:#e9f9ef;color:#167342;border:1px solid #bde9cb}.err{background:#fff0f0;color:#a12626;border:1px solid #f4c5c5}
.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.form-group{display:flex;flex-direction:column;gap:7px}
label{font-weight:bold}
input,select{width:100%;padding:11px 12px;border:1px solid #d5deea;border-radius:7px;background:#fff;font:inherit;color:#263449}
input:focus,select:focus{outline:none;border-color:#1769aa;box-shadow:0 0 0 3px #1769aa18}
.hint{font-size:11px;color:#7c8797;line-height:1.5}
.actions{display:flex;gap:8px;margin-top:18px;flex-wrap:wrap}
.btn{display:inline-block;padding:10px 13px;border:0;border-radius:7px;cursor:pointer;font-size:12px;font-weight:bold;text-decoration:none}
.primary{background:#1769aa;color:white}.light{background:#edf1f6;color:#344054}.edit{background:#e7f1ff;color:#1759a5}.delete{background:#fff0f0;color:#bd3030}
.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap}
.search{display:flex;gap:8px;max-width:450px;flex:1}.search input{min-width:100px}
.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;white-space:nowrap}
th,td{padding:13px 14px;border-bottom:1px solid #e1e8f0;text-align:left}
th{background:#f7f9fc;color:#596579;font-size:11px}td{font-size:12px}tbody tr:hover{background:#f8fbff}
.badge{display:inline-block;padding:5px 9px;border-radius:20px;background:#e7f1ff;color:#1759a5;font-size:11px;font-weight:bold}
.badge-off{background:#fff0f0;color:#bd3030}
.inline{display:inline}.empty{text-align:center;padding:30px;color:#7c8797}
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
    <a class="nav-link" href="program_studi.php">📚 &nbsp; Program Studi</a>
    <a class="nav-link active" href="pengguna.php">👥 &nbsp; Pengguna</a>
    <a class="nav-link" href="role.php">🔐 &nbsp; Role</a>
    <div class="menu-heading">LAPORAN</div>
    <a class="nav-link" href="rekap_mahasiswa.php">📊 &nbsp; Rekap Mahasiswa</a>
    <div class="menu-heading">SISTEM</div>
    <a class="nav-link" href="audit_log.php">📝 &nbsp; Audit Log</a>
</aside>

<main class="main">
    <div class="page-label">MANAJEMEN AKSES</div>
    <h1>Pengguna</h1>
    <div class="subtitle">Kelola akun, role, dan status pengguna SIM Mahasiswa UMB.</div>

    <?php if ($success !== ''): ?>
        <div class="alert ok"><?php echo e($success); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert err"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="panel">
        <div class="panel-heading">
            <?php echo $mode === 'edit' ? 'Edit Pengguna' : 'Tambah Pengguna'; ?>
        </div>
        <div class="panel-body">
            <form method="post" action="pengguna.php">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="aksi" value="<?php echo e($mode); ?>">
                <input type="hidden" name="id_pengguna" value="<?php echo (int) $idEdit; ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap *</label>
                        <input id="nama_lengkap" name="nama_lengkap" maxlength="200" required
                               value="<?php echo e($nama); ?>">
                    </div>

                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input id="username" name="username" maxlength="100" required
                               value="<?php echo e($username); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" maxlength="150"
                               value="<?php echo e($email); ?>">
                    </div>

                    <div class="form-group">
                        <label for="no_hp">Nomor HP</label>
                        <input id="no_hp" name="no_hp" maxlength="30"
                               value="<?php echo e($noHp); ?>">
                    </div>

                    <div class="form-group">
                        <label for="id_role">Role Pengguna *</label>
                        <select id="id_role" name="id_role" required>
                            <option value="">-- Pilih Role --</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?php echo (int) $r['id_role']; ?>"
                                    <?php echo ((string) $idRole === (string) $r['id_role']) ? 'selected' : ''; ?>>
                                    <?php echo e($r['nama_role'] . ' (' . $r['kode_role'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status_aktif">Status Akun *</label>
                        <select id="status_aktif" name="status_aktif" required>
                            <?php foreach ($statusPilihan as $s): ?>
                                <option value="<?php echo e($s); ?>"
                                    <?php echo $status === $s ? 'selected' : ''; ?>>
                                    <?php echo e($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

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
                        <label for="id_fakultas">Fakultas</label>
                        <select id="id_fakultas" name="id_fakultas">
                            <option value="">-- Tidak dipilih --</option>
                            <?php foreach ($fakultas as $f): ?>
                                <option value="<?php echo (int) $f['id_fakultas']; ?>"
                                    data-universitas="<?php echo (int) $f['id_universitas']; ?>"
                                    <?php echo ((string) $idFakultas === (string) $f['id_fakultas']) ? 'selected' : ''; ?>>
                                    <?php echo e($f['nama_fakultas']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_program_studi">Program Studi</label>
                        <select id="id_program_studi" name="id_program_studi">
                            <option value="">-- Tidak dipilih --</option>
                            <?php foreach ($programStudi as $p): ?>
                                <option value="<?php echo (int) $p['id_program_studi']; ?>"
                                    data-fakultas="<?php echo (int) $p['id_fakultas']; ?>"
                                    <?php echo ((string) $idProdi === (string) $p['id_program_studi']) ? 'selected' : ''; ?>>
                                    <?php echo e($p['nama_program_studi'] . ' (' . $p['jenjang'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">Password <?php echo $mode === 'tambah' ? '*' : '(opsional)'; ?></label>
                        <input type="password" id="password" name="password"
                               minlength="8" autocomplete="new-password"
                               <?php echo $mode === 'tambah' ? 'required' : ''; ?>>
                        <div class="hint">
                            Minimal 8 karakter.
                            <?php if ($mode === 'edit'): ?>
                                Kosongkan jika tidak ingin mengganti password.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn primary">
                        <?php echo $mode === 'edit' ? 'Simpan Perubahan' : 'Tambah Pengguna'; ?>
                    </button>
                    <?php if ($mode === 'edit'): ?>
                        <a href="pengguna.php" class="btn light">Batal</a>
                    <?php else: ?>
                        <button type="reset" class="btn light">Reset</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">Daftar Pengguna</div>
        <div class="panel-body">
            <div class="toolbar">
                <span>Total pengguna: <strong><?php echo count($daftar); ?></strong></span>
                <form class="search" method="get" action="pengguna.php">
                    <input type="search" name="cari"
                           placeholder="Cari username, nama, email, atau role..."
                           value="<?php echo e($cari); ?>">
                    <button class="btn primary" type="submit">Cari</button>
                    <?php if ($cari !== ''): ?>
                        <a href="pengguna.php" class="btn light">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Fakultas / Prodi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($daftar) > 0): ?>
                        <?php foreach ($daftar as $i => $p): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo e($p['nama_lengkap']); ?></td>
                                <td><?php echo e($p['username']); ?></td>
                                <td><?php echo e($p['email'] ? $p['email'] : '-'); ?></td>
                                <td><?php echo e($p['nama_role'] ? $p['nama_role'] : '-'); ?></td>
                                <td>
                                    <?php
                                    $lingkup = array();
                                    if (!empty($p['nama_fakultas'])) $lingkup[] = $p['nama_fakultas'];
                                    if (!empty($p['nama_program_studi'])) $lingkup[] = $p['nama_program_studi'];
                                    echo e(count($lingkup) ? implode(' / ', $lingkup) : '-');
                                    ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $p['status_aktif'] === 'Aktif' ? '' : 'badge-off'; ?>">
                                        <?php echo e($p['status_aktif']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a class="btn edit" href="pengguna.php?edit=<?php echo (int) $p['id_pengguna']; ?>">Edit</a>
                                    <form class="inline" method="post" action="pengguna.php"
                                          onsubmit="return confirm('Yakin ingin menghapus akun ini?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id_pengguna" value="<?php echo (int) $p['id_pengguna']; ?>">
                                        <button type="submit" class="btn delete">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="empty">Belum ada pengguna atau data tidak ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<script>
(function () {
    var universitas = document.getElementById('id_universitas');
    var fakultas = document.getElementById('id_fakultas');
    var prodi = document.getElementById('id_program_studi');

    function filterFakultas() {
        var univ = universitas.value;
        Array.prototype.forEach.call(fakultas.options, function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }
            option.hidden = option.getAttribute('data-universitas') !== univ;
        });

        var selected = fakultas.options[fakultas.selectedIndex];
        if (selected && selected.hidden) fakultas.value = '';
        filterProdi();
    }

    function filterProdi() {
        var fakultasId = fakultas.value;
        Array.prototype.forEach.call(prodi.options, function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }
            option.hidden = !fakultasId ||
                option.getAttribute('data-fakultas') !== fakultasId;
        });

        var selected = prodi.options[prodi.selectedIndex];
        if (selected && selected.hidden) prodi.value = '';
    }

    universitas.addEventListener('change', filterFakultas);
    fakultas.addEventListener('change', filterProdi);
    filterFakultas();
})();
</script>
</body>
</html>
