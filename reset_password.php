<?php
require_once __DIR__ . '/includes/functions.php';
$token=trim($_GET['token'] ?? $_POST['token'] ?? ''); $error=''; $success='';
$stmt=$pdo->prepare("SELECT * FROM users WHERE reset_token=? LIMIT 1"); $stmt->execute([$token]); $user=$stmt->fetch();
if(!$user || strtotime($user['reset_expired_at']) < time()) $error='Token reset tidak valid atau sudah expired.';
if($_SERVER['REQUEST_METHOD']==='POST' && !$error){
  $password=$_POST['password']??''; $password2=$_POST['password2']??'';
  if(strlen($password)<6) $error='Password minimal 6 karakter.';
  elseif($password!==$password2) $error='Konfirmasi password tidak cocok.';
  else { $pdo->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expired_at=NULL, updated_at=NOW() WHERE id=?")->execute([password_hash($password,PASSWORD_DEFAULT),$user['id']]); audit_log('RESET_PASSWORD_SUCCESS','Password reset: '.$user['email']); $success='Password berhasil direset. Silakan login.'; }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reset Password</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><div class="container py-5"><div class="col-md-5 mx-auto"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><h4 class="fw-bold">Reset Password</h4><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if($success): ?><div class="alert alert-success"><?= e($success) ?></div><a href="login.php" class="btn btn-success">Login</a><?php elseif(!$error): ?><form method="post"><input type="hidden" name="token" value="<?= e($token) ?>"><div class="mb-3"><label>Password Baru</label><input class="form-control" type="password" name="password" required minlength="6"></div><div class="mb-3"><label>Konfirmasi Password</label><input class="form-control" type="password" name="password2" required minlength="6"></div><button class="btn btn-success w-100">Reset Password</button></form><?php endif; ?></div></div></div></div></body></html>
