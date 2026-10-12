
<?php
/**
 * login.php
 * Halaman login SIM Mahasiswa UMB.
 * Kompatibel dengan PHP 7.2.
 */

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

// Jika sudah login, langsung menuju dashboard
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$username = '';

// Memproses formulir login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) && is_string($_POST['username'])
        ? trim($_POST['username'])
        : '';

    $password = isset($_POST['password']) && is_string($_POST['password'])
        ? $_POST['password']
        : '';

    // Validasi CSRF
    if (!csrf_validate()) {
        $error = 'Sesi formulir tidak valid. Silakan muat ulang halaman.';
    } elseif ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        // Mengambil data pengguna dan role dari database
        $sql = "SELECT
                    p.id_pengguna,
                    p.id_role,
                    p.id_universitas,
                    p.id_fakultas,
                    p.id_program_studi,
                    p.username,
                    p.password_hash,
                    p.nama_lengkap,
                    p.status_aktif,
                    r.nama_role
                FROM pengguna AS p
                LEFT JOIN roles AS r
                    ON p.id_role = r.id_role
                WHERE p.username = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;

            $stmt->close();

            // Memeriksa password dan status akun
            $passwordValid = false;

            if (
                $user &&
                isset($user['password_hash']) &&
                is_string($user['password_hash']) &&
                $user['password_hash'] !== '' &&
                strpos($user['password_hash'], 'REPLACE_WITH_PASSWORD_HASH') === false
            ) {
                $passwordValid = password_verify(
                    $password,
                    $user['password_hash']
                );
            }

            if (!$user || !$passwordValid) {
                $error = 'Username atau password salah.';
            } else {
                // Mendukung status aktif berupa angka atau teks
                $status = strtolower(trim((string) $user['status_aktif']));

                $akunAktif = in_array(
                    $status,
                    array('1', 'aktif', 'active', 'ya', 'yes'),
                    true
                );

                if (!$akunAktif) {
                    $error = 'Akun Anda tidak aktif. Silakan hubungi administrator.';
                } else {
                    // Mencegah session fixation
                    session_regenerate_id(true);

                    // Menyimpan identitas pengguna ke session
                    $_SESSION['id_pengguna'] = (int) $user['id_pengguna'];
                    $_SESSION['user_id'] = (int) $user['id_pengguna'];
                    $_SESSION['id_role'] = (int) $user['id_role'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['nama_role'] = isset($user['nama_role'])
                        ? $user['nama_role']
                        : '';
                    $_SESSION['role'] = $_SESSION['nama_role'];

                    $_SESSION['id_universitas'] = $user['id_universitas'];
                    $_SESSION['id_fakultas'] = $user['id_fakultas'];
                    $_SESSION['id_program_studi'] = $user['id_program_studi'];

                    // Memperbarui waktu login terakhir
                    $update = $conn->prepare(
                        "UPDATE pengguna
                         SET last_login = NOW()
                         WHERE id_pengguna = ?"
                    );

                    if ($update) {
                        $idPengguna = (int) $user['id_pengguna'];
                        $update->bind_param('i', $idPengguna);
                        $update->execute();
                        $update->close();
                    }

                    // Mengganti token CSRF setelah login berhasil
                    csrf_regenerate();

                    header('Location: dashboard.php');
                    exit;
                }
            }
        } else {
            // Detail kesalahan disimpan di log server
            error_log('SIM Mahasiswa UMB: query login gagal disiapkan.');
            $error = 'Terjadi kesalahan pada sistem. Silakan coba kembali.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIM Mahasiswa UMB</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(135deg, #eaf2ff, #f7f9fc);
            color: #1f2937;
        }

        .login-container {
            width: 100%;
            max-width: 430px;
            background: #fff;
            padding: 36px;
            border-radius: 18px;
            box-shadow: 0 12px 40px rgba(15, 42, 80, 0.12);
        }

        .brand {
            text-align: center;
            margin-bottom: 28px;
        }

        .brand img {
            width: 78px;
            height: 78px;
            object-fit: contain;
            margin-bottom: 12px;
        }

        .brand h1 {
            font-size: 22px;
            color: #123d78;
            margin-bottom: 8px;
        }

        .brand p {
            font-size: 13px;
            line-height: 1.6;
            color: #64748b;
        }

        .form-title {
            font-size: 20px;
            margin-bottom: 8px;
        }

        .form-description {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            border-color: #1d4ed8;
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: #164a91;
            color: white;
            border: none;
            border-radius: 9px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 4px;
        }

        .btn-login:hover {
            background: #10386f;
        }

        .error-message {
            padding: 12px 14px;
            border-radius: 8px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .back-link {
            text-align: center;
            margin-top: 22px;
            font-size: 14px;
        }

        .back-link a {
            color: #164a91;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .footer {
            margin-top: 28px;
            padding-top: 18px;
            border-top: 1px solid #e5e7eb;
            color: #94a3b8;
            font-size: 12px;
            text-align: center;
            line-height: 1.6;
        }

        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            .login-container {
                padding: 26px 22px;
            }

            .brand h1 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <main class="login-container">

        <div class="brand">
            <img
                src="gedung.png"
                alt="Logo SIM Mahasiswa UMB"
                onerror="this.style.display='none';"
            >

            <h1>SIM Mahasiswa UMB</h1>

            <p>
                Universitas Muhammadiyah Bengkulu<br>
                Sistem Informasi Manajemen Mahasiswa
            </p>
        </div>

        <h2 class="form-title">Masuk ke Akun Anda</h2>

        <p class="form-description">
            Silakan masukkan username dan password untuk melanjutkan.
        </p>

        <?php if ($error !== ''): ?>
            <div class="error-message" role="alert">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?php echo e($username); ?>"
                    placeholder="Masukkan username"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Masukkan password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="btn-login">
                Login
            </button>
        </form>

        <div class="back-link">
            <a href="index.php">Kembali ke Halaman Utama</a>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?>
            SIM Mahasiswa UMB.<br>
            Universitas Muhammadiyah Bengkulu
        </div>

    </main>
</body>
</html>
