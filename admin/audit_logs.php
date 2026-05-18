<?php
require_once __DIR__ . '/../includes/header.php'; require_admin();
$logs=$pdo->query("SELECT a.*, u.name user_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC LIMIT 200")->fetchAll();
?>
<script>const BASE_URL_JS = "<?= BASE_URL ?>";</script>
<h3 class="fw-bold mb-3">Audit Log Aktivitas</h3>
<div class="card card-soft"><div class="card-body table-responsive"><table class="table align-middle">
<thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Detail</th><th>IP</th></tr></thead><tbody>
<?php foreach($logs as $l): ?><tr><td><?= e($l['created_at']) ?></td><td><?= e($l['user_name'] ?? '-') ?></td><td><span class="badge text-bg-dark"><?= e($l['action']) ?></span></td><td><?= e($l['detail']) ?></td><td><?= e($l['ip_address']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
