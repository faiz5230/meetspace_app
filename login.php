<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mail_helper.php';

if (current_user()) redirect(BASE_URL . '/index.php');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = $_POST['step'] ?? 'password';

    if ($step === 'password') {
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $stmt = $pdo->prepare("SELECT u.*, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id=u.department_id WHERE u.email=? LIMIT 1");
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        $storedPassword = trim($u['password'] ?? '');

        if ($u && (password_verify($pass, $storedPassword) || $pass === $storedPassword)) {
            if ((int)($u['email_verified'] ?? 0) !== 1) {
                $error = 'Email belum diverifikasi. Silakan cek email atau kirim ulang verifikasi.';
            } elseif ($u['status'] !== 'active') {
                $error = 'Akun belum di-approve admin atau sedang ditolak.';
            } else {
                if (defined('ENABLE_LOGIN_OTP') && ENABLE_LOGIN_OTP) {
                    $otp = (string)random_int(100000, 999999);
                    $expired = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    $pdo->prepare("UPDATE users SET otp_code=?, otp_expired_at=? WHERE id=?")->execute([$otp, $expired, $u['id']]);
                    send_otp_email($u['email'], $u['name'], $otp);
                    $_SESSION['otp_user_id'] = $u['id'];
                    $showOtp = true;
                } else {
                    $_SESSION['user'] = $u;
                    $pdo->prepare("UPDATE users SET last_login_at=NOW() WHERE id=?")->execute([$u['id']]);
                    audit_log('LOGIN', 'User login');
                    redirect(BASE_URL . '/index.php');
                }
            }
        } else {
            $error = 'Email atau password salah.';
        }
    }

    if ($step === 'otp') {
        $otp = trim($_POST['otp'] ?? '');
        $uid = $_SESSION['otp_user_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT u.*, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id=u.department_id WHERE u.id=? LIMIT 1");
        $stmt->execute([$uid]);
        $u = $stmt->fetch();
        if ($u && $u['otp_code'] === $otp && strtotime($u['otp_expired_at']) >= time()) {
            $pdo->prepare("UPDATE users SET otp_code=NULL, otp_expired_at=NULL, last_login_at=NOW() WHERE id=?")->execute([$u['id']]);
            unset($_SESSION['otp_user_id']);
            $_SESSION['user'] = $u;
            audit_log('LOGIN_OTP_SUCCESS', 'User login OTP');
            redirect(BASE_URL . '/index.php');
        } else {
            $error = 'Kode OTP salah atau expired.';
            $showOtp = true;
        }
    }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login MeetSpace</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><div class="container py-5"><div class="row justify-content-center"><div class="col-md-5"><div class="card shadow-sm border-0 rounded-4"><div class="card-body p-4"><h3 class="fw-bold text-success">MeetSpace</h3><p class="text-muted">Masuk ke sistem booking ruang meeting.</p><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if(!empty($showOtp)): ?><form method="post"><input type="hidden" name="step" value="otp"><div class="mb-3"><label class="form-label">Kode OTP</label><input class="form-control" name="otp" maxlength="6" required></div><button class="btn btn-success w-100">Verifikasi OTP</button></form><?php else: ?><form method="post"><input type="hidden" name="step" value="password"><div class="mb-3"><label>Email</label><input class="form-control" type="email" name="email" required></div><div class="mb-3"><label>Password</label><input class="form-control" type="password" name="password" required></div><button class="btn btn-success w-100">Masuk</button></form><div class="d-flex justify-content-between mt-3 small"><a href="register.php">Daftar akun</a><a href="forgot_password.php">Lupa password?</a></div><div class="mt-2 small"><a href="resend_verification.php">Kirim ulang verifikasi email</a></div><hr><div class="d-grid gap-2"><a class="btn btn-outline-dark disabled" href="#">Login Google - perlu Client ID</a><a class="btn btn-outline-primary disabled" href="#">Login Microsoft - perlu Client ID</a></div><?php endif; ?></div></div></div></div></div></body></html>
