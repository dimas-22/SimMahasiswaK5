
<?php
/**
 * auth.php
 * Autentikasi dan pengaturan hak akses SIM Mahasiswa UMB.
 * Kompatibel dengan PHP 7.2.
 */

// Memulai session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Memeriksa apakah pengguna sudah login.
 */
function is_logged_in()
{
    return isset($_SESSION['id_pengguna']) ||
           isset($_SESSION['user_id']);
}

/**
 * Mengambil ID pengguna yang sedang login.
 */
function current_user_id()
{
    if (isset($_SESSION['id_pengguna'])) {
        return $_SESSION['id_pengguna'];
    }

    return isset($_SESSION['user_id'])
        ? $_SESSION['user_id']
        : null;
}

/**
 * Mengambil username pengguna.
 */
function current_username()
{
    return isset($_SESSION['username'])
        ? $_SESSION['username']
        : '';
}

/**
 * Mengambil nama lengkap pengguna.
 */
function current_user_name()
{
    if (isset($_SESSION['nama_lengkap'])) {
        return $_SESSION['nama_lengkap'];
    }

    return current_username();
}

/**
 * Mengambil ID role pengguna.
 */
function current_role_id()
{
    return isset($_SESSION['id_role'])
        ? (int) $_SESSION['id_role']
        : null;
}

/**
 * Mengambil nama role pengguna.
 */
function current_role()
{
    if (isset($_SESSION['nama_role'])) {
        return $_SESSION['nama_role'];
    }

    return isset($_SESSION['role'])
        ? $_SESSION['role']
        : '';
}

/**
 * Memastikan pengguna sudah login.
 * Jika belum, arahkan ke halaman login.
 */
function require_login()
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Memeriksa apakah pengguna memiliki salah satu
 * role yang diizinkan.
 *
 * Parameter dapat berupa ID role atau nama role.
 * Contoh: has_role(array(1, 2))
 * Contoh: has_role(array('ADMIN', 'DEKANAT'))
 */
function has_role($allowed_roles)
{
    if (!is_array($allowed_roles)) {
        $allowed_roles = array($allowed_roles);
    }

    $user_role_id = current_role_id();
    $user_role_name = strtoupper(trim(current_role()));

    foreach ($allowed_roles as $role) {
        // Memeriksa berdasarkan ID role
        if (
            is_numeric($role) &&
            $user_role_id !== null &&
            $user_role_id === (int) $role
        ) {
            return true;
        }

        // Memeriksa berdasarkan nama role
        if (is_string($role)) {
            $normalized_role = strtoupper(trim($role));

            if (
                $normalized_role !== '' &&
                $user_role_name !== '' &&
                $user_role_name === $normalized_role
            ) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Memastikan pengguna memiliki role tertentu.
 * Jika tidak memiliki izin, tampilkan HTTP 403.
 */
function require_role($allowed_roles)
{
    require_login();

    if (!has_role($allowed_roles)) {
        http_response_code(403);

        echo '<!DOCTYPE html>';
        echo '<html lang="id">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>Akses Ditolak - SIM Mahasiswa UMB</title>';
        echo '</head>';
        echo '<body style="font-family:Arial,sans-serif;text-align:center;padding:50px;">';
        echo '<h2>Akses Ditolak</h2>';
        echo '<p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>';
        echo '<p><a href="dashboard.php">Kembali ke Dashboard</a></p>';
        echo '</body>';
        echo '</html>';

        exit;
    }
}

/**
 * Memastikan pengguna adalah Admin.
 */
function require_admin()
{
    require_role(array(1, 'ADMIN'));
}

/**
 * Memastikan pengguna adalah Operator Prodi.
 */
function require_operator_prodi()
{
    require_role(array(2, 'OPERATOR_PRODI'));
}

/**
 * Memastikan pengguna adalah Dekanat.
 */
function require_dekanat()
{
    require_role(array(3, 'DEKANAT'));
}

/**
 * Memastikan pengguna adalah Rektorat.
 */
function require_rektorat()
{
    require_role(array(4, 'REKTORAT'));
}

/**
 * Memastikan pengguna adalah Mahasiswa.
 */
function require_mahasiswa()
{
    require_role(array(5, 'MAHASISWA'));
}

/**
 * Mengamankan output HTML untuk mencegah XSS.
 * Contoh: echo e($nama);
 */
function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
