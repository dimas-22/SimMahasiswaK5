
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

$statusFilter = isset($_GET['status']) && is_string($_GET['status'])
    ? trim($_GET['status']) : '';

$fakultasFilter = isset($_GET['fakultas']) && is_string($_GET['fakultas'])
    ? trim($_GET['fakultas']) : '';

$prodiFilter = isset($_GET['prodi']) && is_string($_GET['prodi'])
    ? trim($_GET['prodi']) : '';

$statusPilihan = array(
    'Aktif', 'Cuti', 'Lulus', 'Mengundurkan Diri', 'Drop Out', 'Tidak Aktif'
);

if (!in_array($statusFilter, $statusPilihan, true)) {
    $statusFilter = '';
}

// Data fakultas
$fakultasList = array();
$res = $conn->query(
    "SELECT id_fakultas, nama_fakultas
     FROM fakultas ORDER BY nama_fakultas"
);

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $fakultasList[] = $row;
    }
}

// Data program studi
$prodiList = array();
$res = $conn->query(
    "SELECT p.id_program_studi, p.nama_program_studi, p.id_fakultas,
            p.jenjang, f.nama_fakultas
     FROM program_studi p
     INNER JOIN fakultas f ON f.id_fakultas = p.id_fakultas
     ORDER BY f.nama_fakultas, p.nama_program_studi"
);

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $prodiList[] = $row;
    }
}

// Ringkasan total mahasiswa
$ringkasan = array(
    'total' => 0,
    'aktif' => 0,
    'cuti' => 0,
    'lulus' => 0,
    'lainnya' => 0
);

$res = $conn->query(
    "SELECT COUNT(*) AS total,
        COALESCE(SUM(status_mahasiswa = 'Aktif'), 0) AS aktif,
        COALESCE(SUM(status_mahasiswa = 'Cuti'), 0) AS cuti,
        COALESCE(SUM(status_mahasiswa = 'Lulus'), 0) AS lulus,
        COALESCE(SUM(status_mahasiswa NOT IN ('Aktif','Cuti','Lulus')), 0) AS lainnya
     FROM mahasiswa"
);

if ($res) {
    $dataRingkasan = $res->fetch_assoc();
    if ($dataRingkasan) {
        foreach ($ringkasan as $key => $value) {
            $ringkasan[$key] = isset($dataRingkasan[$key])
                ? (int) $dataRingkasan[$key] : 0;
        }
    }
}

// Statistik berdasarkan status
$statStatus = array();
$res = $conn->query(
    "SELECT status_mahasiswa, COUNT(*) AS jumlah
     FROM mahasiswa
     GROUP BY status_mahasiswa
     ORDER BY jumlah DESC"
);

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $statStatus[] = $row;
    }
}

// Statistik per program studi
$statProdi = array();
$res = $conn->query(
    "SELECT f.nama_fakultas, p.nama_program_studi, p.jenjang,
            COUNT(m.id_mahasiswa) AS jumlah
     FROM program_studi p
     INNER JOIN fakultas f ON f.id_fakultas = p.id_fakultas
     LEFT JOIN mahasiswa m ON m.id_program_studi = p.id_program_studi
     GROUP BY p.id_program_studi, f.nama_fakultas,
              p.nama_program_studi, p.jenjang
     ORDER BY f.nama_fakultas, p.nama_program_studi"
);

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $statProdi[] = $row;
    }
}

// Data mahasiswa sesuai filter
$sql = "SELECT m.id_mahasiswa, m.npm, m.nama_mahasiswa,
               m.jenis_kelamin, m.tanggal_masuk, m.status_mahasiswa,
               p.nama_program_studi, p.jenjang,
               f.nama_fakultas
        FROM mahasiswa m
        INNER JOIN program_studi p ON p.id_program_studi = m.id_program_studi
        INNER JOIN fakultas f ON f.id_fakultas = p.id_fakultas
        WHERE 1 = 1";

$types = '';
$params = array();

if ($cari !== '') {
    $sql .= " AND (m.npm LIKE ? OR m.nama_mahasiswa LIKE ?)";
    $keyword = '%' . $cari . '%';
    $types .= 'ss';
    $params[] = $keyword;
    $params[] = $keyword;
}

if ($statusFilter !== '') {
    $sql .= " AND m.status_mahasiswa = ?";
    $types .= 's';
    $params[] = $statusFilter;
}

if ($fakultasFilter !== '' && ctype_digit($fakultasFilter)) {
    $sql .= " AND f.id_fakultas = ?";
    $types .= 'i';
    $params[] = (int) $fakultasFilter;
} else {
    $fakultasFilter = '';
}

if ($prodiFilter !== '' && ctype_digit($prodiFilter)) {
    $sql .= " AND p.id_program_studi = ?";
    $types .= 'i';
    $params[] = (int) $prodiFilter;
} else {
    $prodiFilter = '';
}

$sql .= " ORDER BY f.nama_fakultas, p.nama_program_studi, m.nama_mahasiswa";

$mahasiswa = array();
$stmt = $conn->prepare($sql);

if ($stmt) {
    if ($types !== '') {
        $bindParams = array($types);
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }
        call_user_func_array(array($stmt, 'bind_param'), $bindParams);
    }

    if ($stmt->execute()) {
        $hasil = $stmt->get_result();
        if ($hasil) {
            while ($row = $hasil->fetch_assoc()) {
                $mahasiswa[] = $row;
            }
        }
    }
    $stmt->close();
}

// Ekspor CSV berdasarkan filter aktif
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $namaFile = 'rekap_mahasiswa_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $namaFile . '"');

    $output = fopen('php://output', 'w');
    // BOM agar karakter Indonesia terbaca dengan baik di Excel
    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, array(
        'No', 'NPM', 'Nama Mahasiswa', 'Jenis Kelamin',
        'Fakultas', 'Program Studi', 'Jenjang',
        'Tanggal Masuk', 'Status Mahasiswa'
    ), ';');

    foreach ($mahasiswa as $i => $m) {
        fputcsv($output, array(
            $i + 1,
            $m['npm'],
            $m['nama_mahasiswa'],
            $m['jenis_kelamin'],
            $m['nama_fakultas'],
            $m['nama_program_studi'],
            $m['jenjang'],
            $m['tanggal_masuk'],
            $m['status_mahasiswa']
        ), ';');
    }

    fclose($output);
    exit;
}

$totalTampil = count($mahasiswa);

function persenRekap($jumlah, $total) {
    if ((int) $total <= 0) {
        return 0;
    }
    return round(((int) $jumlah / (int) $total) * 100, 1);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Rekap Mahasiswa - SIM Mahasiswa UMB</title>
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
.cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin-bottom:20px}
.card{background:#fff;border:1px solid #e1e8f0;border-radius:11px;padding:18px}
.card-label{color:#7c8797;font-size:12px;margin-bottom:12px}
.card-value{font-size:27px;font-weight:bold;color:#123b72}
.card-note{margin-top:8px;color:#7c8797;font-size:11px}
.panel{background:#fff;border:1px solid #e1e8f0;border-radius:11px;margin-bottom:20px;overflow:hidden}
.panel-heading{padding:17px 20px;border-bottom:1px solid #e1e8f0;font-weight:bold}
.panel-body{padding:20px}
.grid-two{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.stat-row{margin-bottom:16px}
.stat-top{display:flex;justify-content:space-between;gap:10px;margin-bottom:7px}
.stat-top span:last-child{font-weight:bold}
.bar{height:9px;background:#e9eef5;border-radius:10px;overflow:hidden}
.bar-inner{height:100%;background:#2474c6;border-radius:10px}
.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.filters{display:flex;gap:8px;flex-wrap:wrap;align-items:center;width:100%}
input,select{padding:10px 11px;border:1px solid #d5deea;border-radius:7px;background:white;font:inherit;color:#263449;max-width:100%}
.filters input{flex:1;min-width:170px}
.btn{display:inline-block;padding:10px 13px;border:0;border-radius:7px;cursor:pointer;font-size:12px;font-weight:bold;text-decoration:none;white-space:nowrap}
.primary{background:#1769aa;color:#fff}.light{background:#edf1f6;color:#344054}.export{background:#e5f7ed;color:#167342}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;white-space:nowrap}
th,td{padding:12px 13px;border-bottom:1px solid #e1e8f0;text-align:left}
th{background:#f7f9fc;color:#596579;font-size:11px}
td{font-size:12px}
tbody tr:hover{background:#f8fbff}
.badge{display:inline-block;padding:5px 9px;border-radius:20px;background:#e7f1ff;color:#1759a5;font-size:11px;font-weight:bold}
.status-aktif{background:#e5f7ed;color:#167342}
.status-cuti{background:#fff4d9;color:#8a5b00}
.status-lulus{background:#e7f1ff;color:#1759a5}
.status-lain{background:#f1f2f4;color:#586273}
.empty{text-align:center;padding:25px;color:#7c8797}
.note{font-size:11px;color:#7c8797;margin-top:12px;line-height:1.5}
@media(max-width:1000px){.cards{grid-template-columns:repeat(2,minmax(0,1fr))}.grid-two{grid-template-columns:1fr}}
@media(max-width:850px){.sidebar{width:175px}.main{margin-left:175px;padding-left:15px;padding-right:15px}}
@media(max-width:650px){.sidebar{position:static;width:100%;margin-top:58px;padding:10px}.sidebar .menu-heading{display:none}.nav-link{display:inline-block;padding:9px}.main{margin:0;padding:25px 12px}.cards{grid-template-columns:repeat(2,minmax(0,1fr))}.card{padding:14px}.card-value{font-size:23px}}
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
    <a class="nav-link active" href="rekap_mahasiswa.php">📊 &nbsp; Rekap Mahasiswa</a>
    <div class="menu-heading">SISTEM</div>
    <a class="nav-link" href="audit_log.php">📝 &nbsp; Audit Log</a>
</aside>

<main class="main">
    <div class="page-label">LAPORAN AKADEMIK</div>
    <h1>Rekap Mahasiswa</h1>
    <div class="subtitle">Ringkasan dan laporan data mahasiswa Universitas Muhammadiyah Bengkulu.</div>

    <section class="cards">
        <div class="card">
            <div class="card-label">Total Mahasiswa</div>
            <div class="card-value"><?php echo number_format($ringkasan['total'], 0, ',', '.'); ?></div>
            <div class="card-note">Seluruh data mahasiswa</div>
        </div>
        <div class="card">
            <div class="card-label">Mahasiswa Aktif</div>
            <div class="card-value"><?php echo number_format($ringkasan['aktif'], 0, ',', '.'); ?></div>
            <div class="card-note"><?php echo persenRekap($ringkasan['aktif'], $ringkasan['total']); ?>% dari total</div>
        </div>
        <div class="card">
            <div class="card-label">Mahasiswa Cuti</div>
            <div class="card-value"><?php echo number_format($ringkasan['cuti'], 0, ',', '.'); ?></div>
            <div class="card-note"><?php echo persenRekap($ringkasan['cuti'], $ringkasan['total']); ?>% dari total</div>
        </div>
        <div class="card">
            <div class="card-label">Mahasiswa Lulus</div>
            <div class="card-value"><?php echo number_format($ringkasan['lulus'], 0, ',', '.'); ?></div>
            <div class="card-note"><?php echo persenRekap($ringkasan['lulus'], $ringkasan['total']); ?>% dari total</div>
        </div>
    </section>

    <section class="grid-two">
        <div class="panel">
            <div class="panel-heading">Rekap Berdasarkan Status</div>
            <div class="panel-body">
                <?php if (count($statStatus)): ?>
                    <?php foreach ($statStatus as $s): ?>
                        <div class="stat-row">
                            <div class="stat-top">
                                <span><?php echo e($s['status_mahasiswa']); ?></span>
                                <span><?php echo (int) $s['jumlah']; ?> (<?php echo persenRekap($s['jumlah'], $ringkasan['total']); ?>%)</span>
                            </div>
                            <div class="bar">
                                <div class="bar-inner" style="width:<?php echo persenRekap($s['jumlah'], $ringkasan['total']); ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">Belum ada data mahasiswa.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel">
            <div class="panel-heading">Rekap Berdasarkan Program Studi</div>
            <div class="panel-body">
                <?php if (count($statProdi)): ?>
                    <?php foreach ($statProdi as $s): ?>
                        <div class="stat-row">
                            <div class="stat-top">
                                <span>
                                    <?php echo e($s['nama_program_studi']); ?>
                                    <small>(<?php echo e($s['nama_fakultas']); ?>)</small>
                                </span>
                                <span><?php echo (int) $s['jumlah']; ?></span>
                            </div>
                            <div class="bar">
                                <div class="bar-inner" style="width:<?php echo persenRekap($s['jumlah'], $ringkasan['total']); ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">Belum ada program studi.</div>
                <?php endif; ?>
                <div class="note">Grafik menunjukkan jumlah mahasiswa pada setiap program studi.</div>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">Daftar Mahasiswa</div>
        <div class="panel-body">
            <div class="toolbar">
                <div>Jumlah data sesuai filter: <strong><?php echo number_format($totalTampil, 0, ',', '.'); ?></strong></div>
                <a class="btn export" href="?<?php
                    $exportParams = array(
                        'cari' => $cari,
                        'status' => $statusFilter,
                        'fakultas' => $fakultasFilter,
                        'prodi' => $prodiFilter,
                        'export' => 'csv'
                    );
                    echo e(http_build_query($exportParams));
                ?>">Ekspor CSV / Excel</a>
            </div>

            <form method="get" action="rekap_mahasiswa.php" class="filters">
                <input type="search" name="cari" placeholder="Cari NPM atau nama mahasiswa"
                       value="<?php echo e($cari); ?>">

                <select name="status">
                    <option value="">Semua Status</option>
                    <?php foreach ($statusPilihan as $s): ?>
                        <option value="<?php echo e($s); ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>>
                            <?php echo e($s); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="fakultas">
                    <option value="">Semua Fakultas</option>
                    <?php foreach ($fakultasList as $f): ?>
                        <option value="<?php echo (int) $f['id_fakultas']; ?>"
                            <?php echo $fakultasFilter === (string) $f['id_fakultas'] ? 'selected' : ''; ?>>
                            <?php echo e($f['nama_fakultas']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="prodi">
                    <option value="">Semua Program Studi</option>
                    <?php foreach ($prodiList as $p): ?>
                        <option value="<?php echo (int) $p['id_program_studi']; ?>"
                            <?php echo $prodiFilter === (string) $p['id_program_studi'] ? 'selected' : ''; ?>>
                            <?php echo e($p['nama_program_studi'] . ' - ' . $p['nama_fakultas']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button class="btn primary" type="submit">Terapkan Filter</button>
                <a class="btn light" href="rekap_mahasiswa.php">Reset</a>
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>No.</th>
                    <th>NPM</th>
                    <th>Nama Mahasiswa</th>
                    <th>Jenis Kelamin</th>
                    <th>Fakultas</th>
                    <th>Program Studi</th>
                    <th>Tanggal Masuk</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($totalTampil > 0): ?>
                    <?php foreach ($mahasiswa as $i => $m): ?>
                        <?php
                        $kelasStatus = 'status-lain';
                        if ($m['status_mahasiswa'] === 'Aktif') {
                            $kelasStatus = 'status-aktif';
                        } elseif ($m['status_mahasiswa'] === 'Cuti') {
                            $kelasStatus = 'status-cuti';
                        } elseif ($m['status_mahasiswa'] === 'Lulus') {
                            $kelasStatus = 'status-lulus';
                        }
                        ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo e($m['npm']); ?></td>
                            <td><?php echo e($m['nama_mahasiswa']); ?></td>
                            <td><?php echo e($m['jenis_kelamin']); ?></td>
                            <td><?php echo e($m['nama_fakultas']); ?></td>
                            <td><?php echo e($m['nama_program_studi'] . ' (' . $m['jenjang'] . ')'); ?></td>
                            <td><?php echo e($m['tanggal_masuk']); ?></td>
                            <td><span class="badge <?php echo $kelasStatus; ?>"><?php echo e($m['status_mahasiswa']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="empty">Data mahasiswa tidak ditemukan.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
