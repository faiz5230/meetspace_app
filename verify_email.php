<?php
require_once __DIR__ . '/includes/functions.php';
$token = trim($_GET['token'] ?? ''); $message=''; $type='danger';
if ($token==='') $message='Token tidak valid.';
else {
  $stmt=$pdo->prepare("SELECT * FROM users WHERE email_verify_token=? LIMIT 1"); $stmt->execute([$token]); $user=$stmt->fetch();
  if(!$user) $message='Token verifikasi tidak ditemukan atau sudah digunakan.';
  else {
    $pdo->prepare("UPDATE users SET email_verified=1,email_verified_at=NOW(),email_verify_token=NULL WHERE id=?")->execute([$user['id']]);
    notify_admins('Email User Terverifikasi','Email '.$user['email'].' sudah diverifikasi dan menunggu approval admin.','info');
    audit_log('VERIFY_EMAIL','Email verified: '.$user['email']);
    $type='success'; $message='Email berhasil diverifikasi. Silakan tunggu approval admin sebelum login.';
  }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Verifikasi Email</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><div class="container py-5"><div class="col-md-6 mx-auto"><div class="alert alert-<?= e($type) ?>"><?= e($message) ?></div><a href="login.php" class="btn btn-success">Ke Login</a></div></div></body></html>
