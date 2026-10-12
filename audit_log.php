
<?php
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth.php';

require_login();

$roleId = isset($_SESSION['id_role']) ? (int) $_SESSION['id_role'] : 0;
$roleNama = isset($_SESSION['nama_role'])
    ? strtolower(trim($_SESSION['nama_role'])) : '';

if ($roleId !== 1 && !in_array($roleNama, array('admin', 'administrator'), true)) {
    http_response_code(403);
    exit('Akses ditolak. Halaman ini khusus Admin.');
}

$cari = isset($_GET['cari']) && is_string($_GET['cari'])
    ? trim($_GET['cari']) : '';

$aksiFilter = isset($_GET['aksi']) && is_string($_GET['aksi'])
    ? strtoupper(trim($_GET['aksi'])) : '';

$aksiPilihan = array('LOGIN', 'LOGOUT', 'INSERT', 'UPDATE', 'DELETE', 'VIEW');

if (!in_array($aksiFilter, $aksiPilihan, true)) {
    $aksiFilter = '';
}

$tanggalAwal = isset($_GET['tanggal_awal']) && is_string($_GET['tanggal_awal'])
    ? trim($_GET['tanggal_awal']) : '';

$tanggalAkhir = isset($_GET['tanggal_akhir']) && is_string($_GET['tanggal_akhir'])
    ? trim($_GET['tanggal_akhir']) : '';

if ($tanggalAwal !== '' &&
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalAwal)) {
    $tanggalAwal = '';
}

if ($tanggalAkhir !== '' &&
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalAkhir)) {
    $tanggalAkhir = '';
}

// Ringkasan aktivitas
$ringkasan = array(
    'total' => 0,
    'login' => 0,
    'insert' => 0,
    'update' => 0,
    'delete' => 0
);

$res = $conn->query(
    "SELECT COUNT(*) AS total,
        COALESCE(SUM(aksi = 'LOGIN'), 0) AS login,
        COALESCE(SUM(aksi = 'INSERT'), 0) AS insert_data,
        COALESCE(SUM(aksi = 'UPDATE'), 0) AS update_data,
        COALESCE(SUM(aksi = 'DELETE'), 0) AS delete_data
     FROM audit_log"
);

if ($res) {
    $row = $res->fetch_assoc();

    if ($row) {
        $ringkasan['total'] = (int) $row['total'];
        $ringkasan['login'] = (int) $row['login'];
        $ringkasan['insert'] = (int) $row['insert_data'];
        $ringkasan['update'] = (int) $row['update_data'];
        $ringkasan['delete'] = (int) $row['delete_data'];
    }
}

// Bangun filter pencarian
$where = array();
$types = '';
$params = array();

if ($cari !== '') {
    $where[] = "(
        p.username LIKE ?
        OR p.nama_lengkap LIKE ?
        OR a.tabel_nama LIKE ?
        OR a.record_id LIKE ?
        OR a.ip_address LIKE ?
    )";

    $keyword = '%' . $cari . '%';

    for ($i = 0; $i < 5; $i++) {
        $types .= 's';
        $params[] = $keyword;
    }
}

if ($aksiFilter !== '') {
    $where[] = 'a.aksi = ?';
    $types .= 's';
    $params[] = $aksiFilter;
}

if ($tanggalAwal !== '') {
    $where[] = 'a.waktu >= ?';
    $types .= 's';
    $params[] = $tanggalAwal . ' 00:00:00';
}

if ($tanggalAkhir !== '') {
    $where[] = 'a.waktu <= ?';
    $types .= 's';
    $params[] = $tanggalAkhir . ' 23:59:59';
}

$sqlWhere = count($where) > 0
    ? ' WHERE ' . implode(' AND ', $where)
    : '';

// Hitung hasil sesuai filter
$sqlCount = "SELECT COUNT(*) AS total
             FROM audit_log a
             LEFT JOIN pengguna p ON p.id_pengguna = a.id_pengguna"
             . $sqlWhere;

$totalFilter = 0;
$stmtCount = $conn->prepare($sqlCount);

if ($stmtCount) {
    if ($types !== '') {
        $bindParams = array($types);

        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }

        call_user_func_array(
            array($stmtCount, 'bind_param'),
            $bindParams
        );
    }

    if ($stmtCount->execute()) {
        $hasilCount = $stmtCount->get_result();
        $countRow = $hasilCount ? $hasilCount->fetch_assoc() : null;

        if ($countRow) {
            $totalFilter = (int) $countRow['total'];
        }
    }

    $stmtCount->close();
}

// Pagination: maksimal 50 baris per halaman
$perHalaman = 50;
$halaman = isset($_GET['halaman']) && ctype_digit((string) $_GET['halaman'])
    ? max(1, (int) $_GET['halaman']) : 1;

$totalHalaman = max(1, (int) ceil($totalFilter / $perHalaman));

if ($halaman > $totalHalaman) {
    $halaman = $totalHalaman;
}

$offset = ($halaman - 1) * $perHalaman;

// Ambil riwayat audit
$sql = "SELECT a.id_audit, a.id_pengguna, a.tabel_nama,
               a.record_id, a.aksi, a.data_lama, a.data_baru,
               a.ip_address, a.user_agent, a.waktu,
               p.username, p.nama_lengkap
        FROM audit_log a
        LEFT JOIN pengguna p ON p.id_pengguna = a.id_pengguna"
        . $sqlWhere .
        " ORDER BY a.waktu DESC, a.id_audit DESC
          LIMIT ? OFFSET ?";

$typesData = $types . 'ii';
$paramsData = $params;
$paramsData[] = $perHalaman;
$paramsData[] = $offset;

$daftar = array();
$error = '';

$stmt = $conn->prepare($sql);

if ($stmt) {
    $bindParams = array($typesData);

    foreach ($paramsData as $key => $value) {
        $bindParams[] = &$paramsData[$key];
    }

    call_user_func_array(
        array($stmt, 'bind_param'),
        $bindParams
    );

    if ($stmt->execute()) {
        $hasil = $stmt->get_result();

        if ($hasil) {
            while ($row = $hasil->fetch_assoc()) {
                $daftar[] = $row;
            }
        }
    } else {
        $error = 'Gagal mengambil riwayat aktivitas.';
    }

    $stmt->close();
} else {
    $error = 'Gagal menyiapkan query riwayat aktivitas.';
}

function auditBadge($aksi) {
    $kelas = array(
        'LOGIN' => 'login',
        'LOGOUT' => 'logout',
        'INSERT' => 'insert',
        'UPDATE' => 'update',
        'DELETE' => 'delete',
        'VIEW' => 'view'
    );

    return isset($kelas[$aksi]) ? $kelas[$aksi] : 'view';
}

function auditUrlHalaman($halaman) {
    $params = $_GET;
    $params['halaman'] = $halaman;

    return 'audit_log.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Audit Log - SIM Mahasiswa UMB</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,Helvetica,sans-serif;background:#f4f7fb;color:#263449;font-size:13px}
.topbar{height:58px;position:fixed;z-index:10;top:0;left:0;right:0;background:#fff;border-bottom:1px solid #e1e8f0;display:flex;align-items:center;justify-content:space-between;padding:0 22px}
.brand{font-size:15px;font-weight:bold}
.brand small{display:block;margin-top:4px;color:#7c8797;font-size:10px;font-weight:normal}
.top-right{display:flex;gap:12px;align-items:center}
.top-right a{color:#dc3545;text-decoration:none}
.sidebar{position:fixed;top:58px;bottom:0;left:0;width:220px;overflow-y:auto;background:#123b72;padding:22px 12px}
.menu-heading{margin:10px 8px;color:#b7cbe6;font-size:10px;font-weight:bold;letter-spacing:1px}
.nav-link{display:block;padding:12px 10px;margin-bottom:4px;border-radius:6px;color:#edf4ff;text-decoration:none}
.nav-link:hover,.nav-link.active{background:#245493}
.main{margin-left:220px;padding:88px 25px 30px;min-height:100vh}
.page-label{margin-bottom:8px;color:#1769aa;font-size:10px;font-weight:bold;letter-spacing:1px}
h1{font-size:23px;margin-bottom:7px}
.subtitle{margin-bottom:22px;color:#7c8797;line-height:1.5}
.cards{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:13px;margin-bottom:20px}
.card{background:white;border:1px solid #e1e8f0;border-radius:11px;padding:17px}
.card-label{color:#7c8797;font-size:12px;margin-bottom:11px}
.card-value{font-size:25px;font-weight:bold;color:#123b72}
.panel{background:#fff;border:1px solid #e1e8f0;border-radius:11px;margin-bottom:20px;overflow:hidden}
.panel-heading{padding:17px 20px;border-bottom:1px solid #e1e8f0;font-weight:bold}
.panel-body{padding:20px}
.filters{display:flex;gap:9px;align-items:center;flex-wrap:wrap}
input,select{padding:10px 11px;border:1px solid #d5deea;border-radius:7px;background:white;color:#263449;font:inherit}
.filters input[type=search]{flex:1;min-width:180px}
.btn{display:inline-block;padding:10px 13px;border:0;border-radius:7px;cursor:pointer;font-size:12px;font-weight:bold;text-decoration:none;white-space:nowrap}
.primary{background:#1769aa;color:#fff}
.light{background:#edf1f6;color:#344054}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;white-space:nowrap}
th,td{padding:12px 13px;border-bottom:1px solid #e1e8f0;text-align:left;vertical-align:top}
th{background:#f7f9fc;color:#596579;font-size:11px}
td{font-size:12px}
tbody tr:hover{background:#f8fbff}
.badge{display:inline-block;padding:5px 8px;border-radius:20px;font-size:10px;font-weight:bold}
.login{background:#e5f7ed;color:#167342}
.logout{background:#edf1f6;color:#465467}
.insert{background:#e7f1ff;color:#1759a5}
.update{background:#fff4d9;color:#8a5b00}
.delete{background:#fff0f0;color:#bd3030}
.view{background:#f1eaff;color:#6941a5}
.muted{color:#7c8797;font-size:11px}
.detail-link{border:0;background:none;color:#1769aa;font:inherit;cursor:pointer;text-decoration:underline}
.empty{text-align:center;padding:30px;color:#7c8797}
.pagination{display:flex;align-items:center;gap:8px;justify-content:space-between;flex-wrap:wrap;padding:16px 20px}
.pagination-links{display:flex;gap:7px;align-items:center;flex-wrap:wrap}
.detail-box{max-width:350px;white-space:normal;overflow-wrap:anywhere;line-height:1.5}
@media(max-width:1150px){.cards{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:850px){.sidebar{width:175px}.main{margin-left:175px;padding-left:15px;padding-right:15px}}
@media(max-width:650px){.sidebar{position:static;width:100%;margin-top:58px;padding:10px}.sidebar .menu-heading{display:none}.nav-link{display:inline-block;padding:9px}.main{margin:0;padding:25px 12px}.cards{grid-template-columns:repeat(2,minmax(0,1fr))}.card{padding:13px}.card-value{font-size:22px}}
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
    <a class="nav-link" href="pengguna.php">👥 &nbsp; Pengguna</a>
    <a class="nav-link" href="role.php">🔐 &nbsp; Role</a>

    <div class="menu-heading">LAPORAN</div>
    <a class="nav-link" href="rekap_mahasiswa.php">📊 &nbsp; Rekap Mahasiswa</a>

    <div class="menu-heading">SISTEM</div>
    <a class="nav-link active" href="audit_log.php">📝 &nbsp; Audit Log</a>
</aside>

<main class="main">
    <div class="page-label">KEAMANAN SISTEM</div>
    <h1>Audit Log</h1>
    <div class="subtitle">
        Riwayat aktivitas pengguna dalam SIM Mahasiswa Universitas Muhammadiyah Bengkulu.
    </div>

    <?php if ($error !== ''): ?>
        <div style="padding:12px;margin-bottom:16px;background:#fff0f0;color:#a12626;border-radius:7px">
            <?php echo e($error); ?>
        </div>
    <?php endif; ?>

    <section class="cards">
        <div class="card">
            <div class="card-label">Total Aktivitas</div>
            <div class="card-value"><?php echo number_format($ringkasan['total'], 0, ',', '.'); ?></div>
        </div>
        <div class="card">
            <div class="card-label">Login</div>
            <div class="card-value"><?php echo number_format($ringkasan['login'], 0, ',', '.'); ?></div>
        </div>
        <div class="card">
            <div class="card-label">Tambah Data</div>
            <div class="card-value"><?php echo number_format($ringkasan['insert'], 0, ',', '.'); ?></div>
        </div>
        <div class="card">
            <div class="card-label">Ubah Data</div>
            <div class="card-value"><?php echo number_format($ringkasan['update'], 0, ',', '.'); ?></div>
        </div>
        <div class="card">
            <div class="card-label">Hapus Data</div>
            <div class="card-value"><?php echo number_format($ringkasan['delete'], 0, ',', '.'); ?></div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">Filter Aktivitas</div>
        <div class="panel-body">
            <form method="get" action="audit_log.php" class="filters">
                <input type="search" name="cari"
                       placeholder="Cari pengguna, tabel, ID data, atau IP..."
                       value="<?php echo e($cari); ?>">

                <select name="aksi">
                    <option value="">Semua Aksi</option>
                    <?php foreach ($aksiPilihan as $a): ?>
                        <option value="<?php echo e($a); ?>"
                            <?php echo $aksiFilter === $a ? 'selected' : ''; ?>>
                            <?php echo e($a); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="tanggal_awal">Dari</label>
                <input type="date" id="tanggal_awal" name="tanggal_awal"
                       value="<?php echo e($tanggalAwal); ?>">

                <label for="tanggal_akhir">Sampai</label>
                <input type="date" id="tanggal_akhir" name="tanggal_akhir"
                       value="<?php echo e($tanggalAkhir); ?>">

                <button type="submit" class="btn primary">Terapkan</button>
                <a href="audit_log.php" class="btn light">Reset</a>
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">Riwayat Aktivitas</div>
        <div class="panel-body">
            <div class="muted">
                Menampilkan <?php echo count($daftar); ?> dari
                <?php echo number_format($totalFilter, 0, ',', '.'); ?> aktivitas
                yang sesuai filter.
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Aksi</th>
                    <th>Tabel</th>
                    <th>ID Data</th>
                    <th>Alamat IP</th>
                    <th>Detail</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($daftar) > 0): ?>
                    <?php foreach ($daftar as $a): ?>
                        <tr>
                            <td><?php echo e($a['waktu']); ?></td>
                            <td>
                                <?php
                                echo e(
                                    !empty($a['nama_lengkap'])
                                        ? $a['nama_lengkap']
                                        : (!empty($a['username'])
                                            ? $a['username']
                                            : 'Pengguna tidak tersedia')
                                );
                                ?>
                                <div class="muted">
                                    ID pengguna:
                                    <?php echo $a['id_pengguna'] === null
                                        ? '-' : (int) $a['id_pengguna']; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php echo auditBadge($a['aksi']); ?>">
                                    <?php echo e($a['aksi']); ?>
                                </span>
                            </td>
                            <td><?php echo e($a['tabel_nama']); ?></td>
                            <td><?php echo e($a['record_id'] !== null ? $a['record_id'] : '-'); ?></td>
                            <td><?php echo e($a['ip_address'] !== null ? $a['ip_address'] : '-'); ?></td>
                            <td>
                                <button type="button" class="detail-link"
                                    onclick="lihatDetail(
                                        <?php echo htmlspecialchars(json_encode(
                                            array(
                                                'aksi' => $a['aksi'],
                                                'data_lama' => $a['data_lama'],
                                                'data_baru' => $a['data_baru'],
                                                'user_agent' => $a['user_agent']
                                            )
                                        ), ENT_QUOTES, 'UTF-8'); ?>
                                    )">Lihat detail</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty">
                            Tidak ada riwayat aktivitas yang ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <div class="muted">
                Halaman <?php echo $halaman; ?> dari <?php echo $totalHalaman; ?>
            </div>
            <div class="pagination-links">
                <?php if ($halaman > 1): ?>
                    <a class="btn light" href="<?php echo e(auditUrlHalaman(1)); ?>">Pertama</a>
                    <a class="btn light" href="<?php echo e(auditUrlHalaman($halaman - 1)); ?>">Sebelumnya</a>
                <?php endif; ?>

                <?php if ($halaman < $totalHalaman): ?>
                    <a class="btn primary" href="<?php echo e(auditUrlHalaman($halaman + 1)); ?>">Berikutnya</a>
                    <a class="btn light" href="<?php echo e(auditUrlHalaman($totalHalaman)); ?>">Terakhir</a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<div id="detailModal" style="display:none;position:fixed;inset:0;z-index:50;background:rgba(10,25,45,.55);padding:20px;overflow:auto">
    <div style="max-width:700px;margin:6vh auto;background:white;border-radius:12px;overflow:hidden">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:17px 20px;border-bottom:1px solid #e1e8f0">
            <strong>Detail Aktivitas</strong>
            <button type="button" class="btn light" onclick="tutupDetail()">Tutup</button>
        </div>
        <div style="padding:20px">
            <h3 style="font-size:13px;margin-bottom:8px">Aksi</h3>
            <p id="detailAksi" style="margin-bottom:18px"></p>

            <h3 style="font-size:13px;margin-bottom:8px">Data Sebelum</h3>
            <pre id="detailLama" class="detail-box" style="padding:12px;background:#f4f7fb;border-radius:7px;margin-bottom:18px"></pre>

            <h3 style="font-size:13px;margin-bottom:8px">Data Sesudah</h3>
            <pre id="detailBaru" class="detail-box" style="padding:12px;background:#f4f7fb;border-radius:7px;margin-bottom:18px"></pre>

            <h3 style="font-size:13px;margin-bottom:8px">Browser / User Agent</h3>
            <pre id="detailAgent" class="detail-box" style="padding:12px;background:#f4f7fb;border-radius:7px"></pre>
        </div>
    </div>
</div>

<script>
function lihatDetail(data) {
    document.getElementById('detailAksi').textContent = data.aksi || '-';
    document.getElementById('detailLama').textContent =
        data.data_lama || 'Tidak ada data sebelum aktivitas.';
    document.getElementById('detailBaru').textContent =
        data.data_baru || 'Tidak ada data sesudah aktivitas.';
    document.getElementById('detailAgent').textContent =
        data.user_agent || 'Informasi browser tidak tersedia.';

    document.getElementById('detailModal').style.display = 'block';
}

function tutupDetail() {
    document.getElementById('detailModal').style.display = 'none';
}

document.getElementById('detailModal').addEventListener('click', function(event) {
    if (event.target === this) {
        tutupDetail();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        tutupDetail();
    }
});
</script>

</body>
</html>
