
<?php
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth.php';

require_login();

$nama = isset($_SESSION['nama_lengkap'])
    ? $_SESSION['nama_lengkap']
    : 'Admin SIM Mahasiswa UMB';

$username = isset($_SESSION['username'])
    ? $_SESSION['username']
    : 'Admin';

$role = isset($_SESSION['nama_role'])
    ? $_SESSION['nama_role']
    : 'Admin';

$status = isset($_SESSION['status_aktif'])
    ? $_SESSION['status_aktif']
    : 'Aktif';

$roleId = isset($_SESSION['id_role'])
    ? (int) $_SESSION['id_role']
    : 0;

function esc($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function hitungData($conn, $tabel) {
    $tabelDiizinkan = array(
        'mahasiswa',
        'program_studi',
        'fakultas',
        'universitas',
        'pengguna',
        'roles',
        'audit_log'
    );

    if (!in_array($tabel, $tabelDiizinkan, true)) {
        return 0;
    }

    $result = $conn->query(
        "SELECT COUNT(*) AS total FROM `" . $tabel . "`"
    );

    if ($result) {
        $row = $result->fetch_assoc();
        return (int) $row['total'];
    }

    error_log('Dashboard query error: ' . $conn->error);
    return 0;
}

$totalMahasiswa = hitungData($conn, 'mahasiswa');
$totalProdi = hitungData($conn, 'program_studi');
$totalFakultas = hitungData($conn, 'fakultas');

$isAdmin = (
    $roleId === 1 ||
    strtolower(trim($role)) === 'admin' ||
    strtolower(trim($role)) === 'administrator'
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard - SIM Mahasiswa UMB</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
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
    --white: #ffffff;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    color: var(--text);
    background: var(--background);
    font-size: 13px;
}

/* =========================
   NAVBAR ATAS
========================= */
.topbar {
    height: 52px;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--white);
    border-bottom: 1px solid var(--border);
    padding: 0 17px;
}

.brand-area {
    display: flex;
    align-items: center;
    gap: 13px;
}

.menu-toggle {
    border: 0;
    background: transparent;
    font-size: 23px;
    cursor: pointer;
    color: #344054;
}

.brand-title {
    font-weight: bold;
    font-size: 14px;
    color: #243047;
}

.brand-subtitle {
    font-size: 10px;
    color: #758093;
    margin-top: 2px;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.avatar {
    width: 27px;
    height: 27px;
    background: #dbeafe;
    color: #1d4ed8;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.profile-name {
    font-weight: bold;
    font-size: 12px;
}

.profile-role {
    font-size: 10px;
    color: var(--muted);
    margin-top: 3px;
}

.logout {
    text-decoration: none;
    color: #dc3545;
    margin-left: 5px;
    font-size: 12px;
}

.logout:hover {
    color: #a71d2a;
}

/* =========================
   SIDEBAR BIRU
========================= */
.sidebar {
    width: 170px;
    position: fixed;
    top: 52px;
    bottom: 0;
    left: 0;
    overflow-y: auto;
    background: var(--blue-dark);
    padding: 25px 9px;
    transition: transform .25s ease;
    z-index: 90;
}

.menu-heading {
    color: #b7cbe6;
    font-size: 9px;
    font-weight: bold;
    letter-spacing: 1px;
    padding: 0 7px;
    margin: 5px 0 11px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 10px 8px;
    color: #eaf2ff;
    text-decoration: none;
    border-radius: 5px;
    margin-bottom: 3px;
    font-size: 12px;
    white-space: nowrap;
    transition: background .2s ease;
}

.nav-link:hover,
.nav-link.active {
    background: var(--blue-hover);
    color: #ffffff;
}

.nav-icon {
    width: 13px;
    text-align: center;
    flex-shrink: 0;
}

.menu-section {
    margin-bottom: 24px;
}

/* =========================
   KONTEN DASHBOARD
========================= */
.main {
    margin-left: 170px;
    padding: 76px 17px 25px;
    min-height: 100vh;
    transition: margin-left .25s ease;
}

.page-label {
    color: var(--blue);
    font-weight: bold;
    font-size: 9px;
    letter-spacing: 1px;
    margin-bottom: 7px;
}

.page-title {
    font-size: 20px;
    line-height: 1.3;
    margin-bottom: 5px;
    color: #202d40;
}

.breadcrumb {
    color: var(--muted);
    font-size: 12px;
    margin-bottom: 18px;
}

/* =========================
   KARTU RINGKASAN
========================= */
.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}

.stat-card {
    min-height: 67px;
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 11px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .015);
    transition: box-shadow .2s ease;
}

.stat-card:hover {
    box-shadow: 0 4px 12px rgba(18, 59, 114, .08);
}

.stat-icon {
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    border-radius: 9px;
    background: var(--blue-light);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.stat-label {
    color: #7b8493;
    font-size: 10px;
    margin-bottom: 3px;
}

.stat-number {
    font-weight: bold;
    font-size: 17px;
    color: var(--blue-dark);
}

.stat-text {
    font-weight: bold;
    font-size: 12px;
}

/* =========================
   INFORMASI SISTEM
========================= */
.info-panel {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 11px;
    overflow: hidden;
}

.panel-heading {
    padding: 15px 16px;
    font-size: 13px;
    font-weight: bold;
    border-bottom: 1px solid var(--border);
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
}

.info-item {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    min-width: 0;
}

.info-item:nth-last-child(-n+2) {
    border-bottom: none;
}

.info-label {
    color: #8992a0;
    font-size: 10px;
    margin-bottom: 4px;
}

.info-value {
    font-size: 12px;
    font-weight: bold;
    overflow-wrap: anywhere;
}

.status-active {
    color: #16804a;
}

/* =========================
   FOOTER
========================= */
.footer {
    padding: 20px 0 5px;
    font-size: 10px;
    color: #929baa;
}

/* =========================
   RESPONSIVE SIDEBAR
========================= */
.sidebar-hidden .sidebar {
    transform: translateX(-100%);
}

.sidebar-hidden .main {
    margin-left: 0;
}

@media (max-width: 760px) {
    .sidebar {
        transform: translateX(-100%);
        box-shadow: 4px 0 14px rgba(0, 0, 0, .12);
    }

    body.mobile-sidebar-open .sidebar {
        transform: translateX(0);
    }

    .main {
        margin-left: 0;
        padding: 72px 13px 20px;
    }

    .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .page-title {
        font-size: 18px;
    }

    .profile-name {
        max-width: 130px;
        overflow-wrap: anywhere;
    }
}

@media (max-width: 430px) {
    .stats {
        grid-template-columns: 1fr;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .info-item:nth-last-child(-n+2) {
        border-bottom: 1px solid var(--border);
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .topbar {
        padding: 0 10px;
    }
}
</style>
</head>

<body>

<!-- NAVBAR -->
<header class="topbar">
    <div class="brand-area">
        <button
            class="menu-toggle"
            id="menuToggle"
            type="button"
            aria-label="Buka atau tutup menu"
        >☰</button>

        <div>
            <div class="brand-title">SIM Mahasiswa</div>
            <div class="brand-subtitle">
                Universitas Muhammadiyah Bengkulu
            </div>
        </div>
    </div>

    <div class="topbar-right">
        <div class="avatar">
            <?php echo esc(strtoupper(substr(trim($nama), 0, 1))); ?>
        </div>

        <div>
            <div class="profile-name"><?php echo esc($nama); ?></div>
            <div class="profile-role"><?php echo esc($role); ?></div>
        </div>

        <a href="logout.php" class="logout">Keluar</a>
    </div>
</header>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

    <div class="menu-section">
        <div class="menu-heading">MENU UTAMA</div>

        <a href="dashboard.php" class="nav-link active">
            <span class="nav-icon">▦</span>
            Dashboard
        </a>
    </div>

    <div class="menu-section">
        <div class="menu-heading">DATA AKADEMIK</div>

        <a href="mahasiswa.php" class="nav-link">
            <span class="nav-icon">🎓</span>
            Data Mahasiswa
        </a>

        <a href="universitas.php" class="nav-link">
            <span class="nav-icon">🏫</span>
            Universitas
        </a>

        <a href="fakultas.php" class="nav-link">
            <span class="nav-icon">🏢</span>
            Fakultas
        </a>

        <a href="program_studi.php" class="nav-link">
            <span class="nav-icon">📚</span>
            Program Studi
        </a>

        <a href="pengguna.php" class="nav-link">
            <span class="nav-icon">👥</span>
            Pengguna
        </a>

        <a href="role.php" class="nav-link">
            <span class="nav-icon">🔐</span>
            Role
        </a>
    </div>

    <div class="menu-section">
        <div class="menu-heading">LAPORAN</div>

        <a href="rekap_mahasiswa.php" class="nav-link">
            <span class="nav-icon">📊</span>
            Rekap Mahasiswa
        </a>
    </div>

    <div class="menu-section">
        <div class="menu-heading">SISTEM</div>

        <a href="audit_log.php" class="nav-link">
            <span class="nav-icon">📝</span>
            Audit Log
        </a>
    </div>

</aside>

<!-- KONTEN UTAMA -->
<main class="main">

    <div class="page-label">DASHBOARD</div>

    <h1 class="page-title">
        Selamat datang, <?php echo esc($nama); ?>
    </h1>

    <div class="breadcrumb">
        <?php echo esc($role); ?> · Seluruh Program Studi
    </div>

    <!-- KARTU STATISTIK -->
    <section class="stats">

        <div class="stat-card">
            <div class="stat-icon">🎓</div>
            <div>
                <div class="stat-label">Total Mahasiswa</div>
                <div class="stat-number">
                    <?php echo $totalMahasiswa; ?>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div>
                <div class="stat-label">Program Studi</div>
                <div class="stat-number">
                    <?php echo $totalProdi; ?>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🏢</div>
            <div>
                <div class="stat-label">Fakultas</div>
                <div class="stat-number">
                    <?php echo $totalFakultas; ?>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🔐</div>
            <div>
                <div class="stat-label">Hak Akses</div>
                <div class="stat-text"><?php echo esc($role); ?></div>
            </div>
        </div>

    </section>

    <!-- INFORMASI SISTEM -->
    <section class="info-panel">

        <div class="panel-heading">Informasi Sistem</div>

        <div class="info-grid">

            <div class="info-item">
                <div class="info-label">Universitas</div>
                <div class="info-value">
                    Universitas Muhammadiyah Bengkulu
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Role</div>
                <div class="info-value">
                    <?php echo esc($role); ?>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Username</div>
                <div class="info-value">
                    <?php echo esc($username); ?>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value status-active">
                    <?php echo esc($status); ?>
                </div>
            </div>

        </div>
    </section>

    <footer class="footer">
        &copy; <?php echo date('Y'); ?>
        SIM Mahasiswa - Universitas Muhammadiyah Bengkulu
    </footer>

</main>

<script>
document.getElementById('menuToggle').addEventListener('click', function () {
    if (window.innerWidth <= 760) {
        document.body.classList.toggle('mobile-sidebar-open');
    } else {
        document.body.classList.toggle('sidebar-hidden');
    }
});
</script>

</body>
</html>
