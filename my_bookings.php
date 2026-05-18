<?php
require_once __DIR__ . '/includes/header.php';
$stmt=$pdo->prepare("SELECT b.*, r.name room_name FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.user_id=? ORDER BY b.created_at DESC");
$stmt->execute([$me['id']]); $rows=$stmt->fetchAll();
?>
<script>const BASE_URL_JS = "<?= BASE_URL ?>";</script>
<h3 class="fw-bold mb-3">Booking Saya</h3>
<div class="card card-soft"><div class="card-body table-responsive"><table class="table">
<thead><tr><th>Ruangan</th><th>Judul</th><th>Tanggal</th><th>Waktu</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>
<?php foreach($rows as $b): ?><tr><td><?= e($b['room_name']) ?></td><td><?= e($b['title']) ?></td><td><?= e($b['booking_date']) ?></td><td><?= substr($b['start_time'],0,5) ?>-<?= substr($b['end_time'],0,5) ?></td><td><?= status_badge($b['status']) ?></td><td><?= e($b['rejection_reason'] ?: $b['cancel_reason'] ?: $b['reschedule_note'] ?: '-') ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
