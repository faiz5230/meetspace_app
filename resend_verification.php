<?php
require_once __DIR__ . '/includes/functions.php'; require_once __DIR__ . '/includes/mail_helper.php';
$error=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $email=trim($_POST['email']??''); $stmt=$pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1"); $stmt->execute([$email]); $user=$stmt->fetch();
  if(!$user) $error='Email tidak ditemukan.';
  elseif((int)$user['email_verified']===1) $error='Email sudah terverifikasi.';
  else { $token=bin2hex(random_bytes(32)); $pdo->prepare("UPDATE users SET email_verify_token=? WHERE id=?")->execute([$token,$user['id']]); send_verification_email($user['id'],$user['email'],$user['name'],$token); audit_log('RESEND_VERIFICATION','Resend email verification: '.$email); $success='Email verifikasi sudah dikirim ulang.'; }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kirim Ulang Verifikasi</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><div class="container py-5"><div class="col-md-5 mx-auto"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><h4 class="fw-bold">Kirim Ulang Verifikasi Email</h4><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?><form method="post"><div class="mb-3"><label>Email</label><input class="form-control" type="email" name="email" required></div><button class="btn btn-success w-100">Kirim Ulang</button></form><div class="mt-3 small"><a href="login.php">Kembali ke login</a></div></div></div></div></div></body></html>
