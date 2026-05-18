<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
require_once __DIR__ . '/../includes/mail_helper.php';

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$verified = $_GET['verified'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 10;
$off = ($page - 1) * $per;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'approve') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if ($user) {
            if ((int)$user['email_verified'] !== 1) {
                $_SESSION['flash_error'] = 'User belum verifikasi email, belum bisa di-approve.';
            } else {
                $pdo->prepare("UPDATE users SET status='active', updated_at=NOW() WHERE id=?")->execute([$id]);
                $pdo->prepare("INSERT INTO user_approval_logs (user_id, admin_id, action, note) VALUES (?, ?, 'approved', ?)")->execute([$id, $me['id'], 'Approved dari admin panel']);
                audit_log('APPROVE_USER', 'Approve user: ' . $user['email']);
                send_activation_email($user['email'], $user['name']);
                notify_user($id, 'Akun Disetujui', 'Akun Anda sudah disetujui admin.', 'success');
                $_SESSION['flash_success'] = 'User berhasil di-approve.';
            }
        }
        redirect('users.php');
    }

    if ($action === 'reject') {
        $note = trim($_POST['note'] ?? 'Ditolak admin');
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if ($user) {
            $pdo->prepare("UPDATE users SET status='rejected', updated_at=NOW() WHERE id=?")->execute([$id]);
            $pdo->prepare("INSERT INTO user_approval_logs (user_id, admin_id, action, note) VALUES (?, ?, 'rejected', ?)")->execute([$id, $me['id'], $note]);
            audit_log('REJECT_USER', 'Reject user: ' . $user['email'] . ' - ' . $note);
            notify_user($id, 'Akun Ditolak', 'Akun Anda ditolak admin. Alasan: ' . $note, 'danger');
            $_SESSION['flash_success'] = 'User berhasil ditolak.';
        }
        redirect('users.php');
    }

    if ($action === 'resend_verification_admin') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if ($user && (int)$user['email_verified'] !== 1) {
            $token = bin2hex(random_bytes(32));
            $pdo->prepare("UPDATE users SET email_verify_token=? WHERE id=?")->execute([$token, $id]);
            send_verification_email($id, $user['email'], $user['name'], $token);
            audit_log('ADMIN_RESEND_VERIFICATION', 'Admin resend verification: ' . $user['email']);
            $_SESSION['flash_success'] = 'Email verifikasi dikirim ulang.';
        }
        redirect('users.php');
    }

    if ($action === 'delete') {
        if ($id === 1) {
            $_SESSION['flash_error'] = 'Admin utama tidak bisa dihapus.';
            redirect('users.php');
        }
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        audit_log('DELETE_USER', 'Delete user ID ' . $id);
        $_SESSION['flash_success'] = 'User berhasil dihapus.';
        redirect('users.php');
    }
}

$where = ["1=1"];
$params = [];
if ($q !== '') { $where[] = "(u.name LIKE ? OR u.email LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($status !== '') { $where[] = "u.status = ?"; $params[] = $status; }
if ($verified !== '') { $where[] = "u.email_verified = ?"; $params[] = (int)$verified; }
$whereSql = implode(" AND ", $where);

$count = $pdo->prepare("SELECT COUNT(*) c FROM users u WHERE $whereSql");
$count->execute($params);
$total = (int)$count->fetch()['c'];
$pages = max(1, ceil($total / $per));

$stmt = $pdo->prepare("SELECT u.*, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id=u.department_id WHERE $whereSql ORDER BY u.created_at DESC LIMIT $per OFFSET $off");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<script>const BASE_URL_JS = "<?= BASE_URL ?>";</script>
<div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="fw-bold mb-0">Manajemen Pengguna</h3><div class="text-muted">Approval user, verifikasi email, dan audit approval.</div></div></div>
<?php if(!empty($_SESSION['flash_success'])): ?><div class="alert alert-success"><?= e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div><?php endif; ?>
<?php if(!empty($_SESSION['flash_error'])): ?><div class="alert alert-danger"><?= e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div><?php endif; ?>
<form class="card card-soft mb-3"><div class="card-body row g-2 align-items-end"><div class="col-md-4"><label class="form-label">Cari</label><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Nama atau email"></div><div class="col-md-3"><label class="form-label">Status Approval</label><select class="form-select" name="status"><option value="">Semua</option><option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending</option><option value="active" <?= $status==='active'?'selected':'' ?>>Active</option><option value="rejected" <?= $status==='rejected'?'selected':'' ?>>Rejected</option></select></div><div class="col-md-3"><label class="form-label">Email Verified</label><select class="form-select" name="verified"><option value="">Semua</option><option value="1" <?= $verified==='1'?'selected':'' ?>>Verified</option><option value="0" <?= $verified==='0'?'selected':'' ?>>Belum Verified</option></select></div><div class="col-md-2 d-grid"><button class="btn btn-primary">Filter</button></div></div></form>
<div class="card card-soft"><div class="card-body table-responsive"><table class="table align-middle"><thead><tr><th>Nama</th><th>Email</th><th>Departemen</th><th>Email Verified</th><th>Status</th><th>Role</th><th width="280">Aksi</th></tr></thead><tbody>
<?php foreach($users as $u): ?><tr><td class="fw-semibold"><?= e($u['name']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['department_name'] ?? '-') ?></td><td><?= (int)$u['email_verified']===1 ? '<span class="badge text-bg-success">Verified</span>' : '<span class="badge text-bg-warning">Belum Verified</span>' ?></td><td><?= status_badge($u['status']) ?></td><td><?= e($u['role']) ?></td><td class="text-nowrap"><?php if($u['status']==='pending' && (int)$u['email_verified']===1): ?><form method="post" class="d-inline"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn btn-sm btn-success">Approve</button></form><?php endif; ?> <?php if($u['status']==='pending'): ?><button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal" onclick="document.getElementById('reject_id').value='<?= $u['id'] ?>'">Reject</button><?php endif; ?> <?php if((int)$u['email_verified']!==1): ?><form method="post" class="d-inline"><input type="hidden" name="action" value="resend_verification_admin"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn btn-sm btn-warning">Resend Verif</button></form><?php endif; ?> <form method="post" class="d-inline" onsubmit="return confirm('Hapus user ini?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr><?php endforeach; ?>
<?php if(!$users): ?><tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data user.</td></tr><?php endif; ?>
</tbody></table><nav><ul class="pagination mb-0"><?php for($i=1;$i<=$pages;$i++): ?><li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="?q=<?= urlencode($q) ?>&status=<?= urlencode($status) ?>&verified=<?= urlencode($verified) ?>&page=<?= $i ?>"><?= $i ?></a></li><?php endfor; ?></ul></nav></div></div>
<div class="modal fade" id="rejectModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post"><input type="hidden" name="action" value="reject"><input type="hidden" name="id" id="reject_id"><div class="modal-header"><h5 class="modal-title">Reject User</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Alasan Reject</label><textarea class="form-control" name="note" rows="3" required></textarea></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger">Reject</button></div></form></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
