
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hapus seluruh data sesi pengguna
$_SESSION = array();

// Hapus cookie session jika digunakan
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Hancurkan sesi
session_destroy();

// Kembali ke halaman login
header("Location: login.php");
exit;
?>
