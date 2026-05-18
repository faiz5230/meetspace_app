<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mail_helper.php';

if (current_user()) {
    redirect(BASE_URL . '/index.php');
}

$error = '';
$success = '';

if (!isset($_SESSION['captcha_a'])) {
    $_SESSION['captcha_a'] = random_int(1, 9);
    $_SESSION['captcha_b'] = random_int(1, 9);
}

$departments = $pdo->query("
    SELECT *
    FROM departments
    WHERE status = 'active'
    ORDER BY name
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name         = trim($_POST['name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $departmentId = $_POST['department_id'] ?: null;
    $captcha      = (int)($_POST['captcha'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | CAPTCHA
    |--------------------------------------------------------------------------
    */
    if (
        defined('ENABLE_SIMPLE_CAPTCHA') &&
        ENABLE_SIMPLE_CAPTCHA
    ) {

        $captchaAnswer = (int)(
            $_SESSION['captcha_a'] +
            $_SESSION['captcha_b']
        );

        if ($captcha !== $captchaAnswer) {
            $error = 'Captcha salah.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */
    if (
        !$error &&
        (
            $name === '' ||
            $email === '' ||
            strlen($password) < 6
        )
    ) {
        $error = 'Nama, email, dan password minimal 6 karakter wajib diisi.';
    }

    /*
    |--------------------------------------------------------------------------
    | CEK EMAIL SUDAH ADA
    |--------------------------------------------------------------------------
    */
    if (!$error) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = ?
        ");

        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SIMPAN USER
    |--------------------------------------------------------------------------
    */
    if (!$error) {

        $token = bin2hex(random_bytes(32));

        /*
        |--------------------------------------------------------------------------
        | Semua email diperbolehkan
        |--------------------------------------------------------------------------
        */
        $status = 'pending';

        $stmt = $pdo->prepare("
            INSERT INTO users (
                department_id,
                name,
                email,
                password,
                role,
                status,
                email_verified,
                email_verify_token,
                created_at
            )
            VALUES (
                ?,
                ?,
                ?,
                ?,
                'staff',
                ?,
                0,
                ?,
                NOW()
            )
        ");

        $stmt->execute([
            $departmentId,
            $name,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $status,
            $token
        ]);

        $userId = $pdo->lastInsertId();

        /*
        |--------------------------------------------------------------------------
        | KIRIM EMAIL VERIFIKASI
        |--------------------------------------------------------------------------
        */
        send_verification_email(
            $userId,
            $email,
            $name,
            $token
        );

        /*
        |--------------------------------------------------------------------------
        | NOTIFIKASI ADMIN
        |--------------------------------------------------------------------------
        */
        notify_admins(
            'Pendaftaran User Baru',
            'User ' . $name . ' mendaftar dan menunggu verifikasi email/approval admin.',
            'warning'
        );

        audit_log(
            'REGISTER_USER',
            'User daftar mandiri: ' . $email
        );

        unset(
            $_SESSION['captcha_a'],
            $_SESSION['captcha_b']
        );

        $success = '
            Registrasi berhasil.
            Silakan cek email untuk verifikasi.
            Setelah verifikasi, admin perlu approve akun Anda.
        ';
    }

    /*
    |--------------------------------------------------------------------------
    | RESET CAPTCHA
    |--------------------------------------------------------------------------
    */
    $_SESSION['captcha_a'] = random_int(1, 9);
    $_SESSION['captcha_b'] = random_int(1, 9);
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register MeetSpace</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6">

            <div class="card shadow-sm border-0 rounded-4">

                <div class="card-body p-4">

                    <h3 class="fw-bold text-success">
                        Daftar MeetSpace
                    </h3>

                    <p class="text-muted">
                        Registrasi akun booking ruang meeting.
                    </p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?= e($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>

                        <div class="alert alert-success">
                            <?= e($success) ?>
                        </div>

                        <a href="login.php" class="btn btn-success">
                            Kembali ke Login
                        </a>

                    <?php else: ?>

                        <form method="post">

                            <div class="mb-3">
                                <label class="form-label">
                                    Nama Lengkap
                                </label>

                                <input
                                    class="form-control"
                                    name="name"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Email
                                </label>

                                <input
                                    class="form-control"
                                    type="email"
                                    name="email"
                                    placeholder="contoh@gmail.com"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Departemen
                                </label>

                                <select
                                    class="form-select"
                                    name="department_id">

                                    <option value="">
                                        Pilih departemen
                                    </option>

                                    <?php foreach ($departments as $d): ?>

                                        <option value="<?= $d['id'] ?>">
                                            <?= e($d['name']) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Password
                                </label>

                                <input
                                    class="form-control"
                                    type="password"
                                    name="password"
                                    minlength="6"
                                    required>
                            </div>

                            <?php if (
                                defined('ENABLE_SIMPLE_CAPTCHA') &&
                                ENABLE_SIMPLE_CAPTCHA
                            ): ?>

                                <div class="mb-3">

                                    <label class="form-label">
                                        Captcha:
                                        <?= (int)$_SESSION['captcha_a'] ?>
                                        +
                                        <?= (int)$_SESSION['captcha_b'] ?>
                                        =
                                    </label>

                                    <input
                                        class="form-control"
                                        type="number"
                                        name="captcha"
                                        required>

                                </div>

                            <?php endif; ?>

                            <button class="btn btn-success w-100">
                                Daftar
                            </button>

                        </form>

                        <div class="mt-3 small">
                            Sudah punya akun?
                            <a href="login.php">
                                Login
                            </a>
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>