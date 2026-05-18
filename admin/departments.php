<?php
require_once __DIR__ . '/../includes/header.php'; require_admin();
if($_SERVER['REQUEST_METHOD']==='POST'){
  $action=$_POST['action'];
  if($action==='save'){
    if(!empty($_POST['id'])){
      $pdo->prepare("UPDATE departments SET name=?, description=?, status=? WHERE id=?")->execute([$_POST['name'],$_POST['description'],$_POST['status'],$_POST['id']]);
      audit_log('UPDATE_DEPARTMENT','Edit departemen '.$_POST['name']);
    }else{
      $pdo->prepare("INSERT INTO departments (name,description,status) VALUES (?,?,?)")->execute([$_POST['name'],$_POST['description'],$_POST['status']]);
      audit_log('CREATE_DEPARTMENT','Tambah departemen '.$_POST['name']);
    }
  }
  if($action==='delete'){
    $pdo->prepare("DELETE FROM departments WHERE id=?")->execute([$_POST['id']]);
    audit_log('DELETE_DEPARTMENT','Hapus departemen ID '.$_POST['id']);
  }
  redirect('departments.php');
}
$rows=$pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
?>
<script>const BASE_URL_JS = "<?= BASE_URL ?>";</script>
<div class="d-flex justify-content-between mb-3"><h3 class="fw-bold">Master Data Departemen</h3><button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#deptModal" onclick="openDept()">Tambah</button></div>
<div class="card card-soft"><div class="card-body table-responsive"><table class="table">
<thead><tr><th>Nama</th><th>Deskripsi</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td class="fw-semibold"><?= e($r['name']) ?></td><td><?= e($r['description']) ?></td><td><?= status_badge($r['status']) ?></td><td>
<button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#deptModal" onclick='openDept(<?= json_encode($r) ?>)'>Edit</button>
<form method="post" class="d-inline" onsubmit="return confirm('Hapus departemen?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-danger">Hapus</button></form>
</td></tr><?php endforeach; ?>
</tbody></table></div></div>
<div class="modal fade" id="deptModal"><div class="modal-dialog"><form class="modal-content" method="post"><input type="hidden" name="action" value="save"><input type="hidden" name="id" id="did">
<div class="modal-header"><h5>Departemen</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><label>Nama</label><input class="form-control mb-2" name="name" id="dname" required><label>Deskripsi</label><textarea class="form-control mb-2" name="description" id="ddesc"></textarea><label>Status</label><select class="form-select" name="status" id="dstatus"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
<div class="modal-footer"><button class="btn btn-success">Simpan</button></div></form></div></div>
<script>function openDept(d=null){did.value=d?d.id:'';dname.value=d?d.name:'';ddesc.value=d?d.description:'';dstatus.value=d?d.status:'active';}</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
