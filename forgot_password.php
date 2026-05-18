<?php
require_once __DIR__ . '/includes/functions.php'; require_once __DIR__ . '/includes/mail_helper.php';
$error=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $email=trim($_POST['email']??''); $stmt=$pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1"); $stmt->execute([$email]); $user=$stmt->fetch();
  if(!$user) $error='Email tidak ditemukan.';
  else { $token=bin2hex(random_bytes(32)); $expired=date('Y-m-d H:i:s',strtotime('+1 hour')); $pdo->prepare("UPDATE users SET reset_token=?, reset_expired_at=? WHERE id=?")->execute([$token,$expired,$user['id']]); send_forgot_password_email($user['email'],$user['name'],$token); audit_log('FORGOT_PASSWORD','Request reset password: '.$email); $success='Link reset password sudah dikirim ke email.'; }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Lupa Password</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><div class="container py-5"><div class="col-md-5 mx-auto"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><h4 class="fw-bold">Lupa Password</h4><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?><form method="post"><div class="mb-3"><label>Email</label><input class="form-control" type="email" name="email" required></div><button class="btn btn-success w-100">Kirim Link Reset</button></form><div class="mt-3 small"><a href="login.php">Kembali ke login</a></div></div></div></div></div></body></html>
