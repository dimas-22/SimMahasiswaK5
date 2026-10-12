
<?php
/**
 * koneksi.php
 * Koneksi database SIM Mahasiswa UMB.
 * Kompatibel dengan PHP 7.2.
 */

mysqli_report(MYSQLI_REPORT_OFF);

// Konfigurasi database
$host = "sql213.infinityfree.com";
$username = "if0_43046753";
$password = "tXNhawLmsmS6";
$database = "if0_43046753_sim_mahasiswa";

// Membuat koneksi
$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

// Memeriksa koneksi
if ($conn->connect_error) {
    error_log(
        "Koneksi database gagal: " . $conn->connect_error
    );

    die("Koneksi database gagal. Silakan coba kembali nanti.");
}

// Mengatur karakter UTF-8
if (!$conn->set_charset("utf8mb4")) {
    error_log(
        "Gagal mengatur charset database: " . $conn->error
    );
}
?>
