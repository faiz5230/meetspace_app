<?php
require_once __DIR__ . '/../includes/header.php'; require_admin();
$rooms=$pdo->query("SELECT * FROM rooms ORDER BY name")->fetchAll();
?>
<script>const BASE_URL_JS = "<?= BASE_URL ?>";</script>
<h3 class="fw-bold mb-3">Manajemen Ruangan</h3>
<div class="card card-soft"><div class="card-body table-responsive"><table class="table">
<thead><tr><th>Ruangan</th><th>Kapasitas</th><th>Lantai</th><th>Fasilitas</th><th>Status</th></tr></thead><tbody>
<?php foreach($rooms as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['capacity']) ?></td><td><?= e($r['floor']) ?></td><td><?= e($r['facilities']) ?></td><td><?= status_badge($r['status']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
