<?php
require_once __DIR__ . '/includes/header.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $photo=upload_photo('photo',$me['photo'] ?? null);
  if(!empty($_POST['password'])){
    $pdo->prepare("UPDATE users SET name=?, password=?, photo=?, updated_at=NOW() WHERE id=?")->execute([$_POST['name'],password_hash($_POST['password'],PASSWORD_DEFAULT),$photo,$me['id']]);
  } else {
    $pdo->prepare("UPDATE users SET name=?, photo=?, updated_at=NOW() WHERE id=?")->execute([$_POST['name'],$photo,$me['id']]);
  }
  audit_log('UPDATE_PROFILE','Update profil sendiri');
  refresh_user_session();
  redirect('profile.php');
}
?>
<script>const BASE_URL_JS = "<?= BASE_URL ?>";</script>
<h3 class="fw-bold mb-3">Profil Saya</h3>
<div class="card card-soft"><div class="card-body"><form method="post" enctype="multipart/form-data" class="row g-3">
<div class="col-md-6"><label>Nama</label><input class="form-control" name="name" value="<?= e($me['name']) ?>"></div>
<div class="col-md-6"><label>Foto Profil</label><input class="form-control" type="file" name="photo" accept=".jpg,.jpeg,.png,.webp"></div>
<div class="col-md-6"><label>Password Baru</label><input class="form-control" type="password" name="password"></div>
<div class="col-12"><button class="btn btn-success">Simpan</button></div>
</form></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
