<?php
require_once __DIR__ . '/../includes/functions.php';
require_staff();
$pageTitle = 'Pemesanan Saya';
$active = 'my_bookings';
$status = $_GET['status'] ?? 'all';
$where = $status === 'all' ? 'b.user_id=?' : 'b.user_id=? AND b.status=?';
$params = $status === 'all' ? [current_user()['id']] : [current_user()['id'], $status];
$st = db()->prepare("SELECT b.*, r.name room_name FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE $where ORDER BY b.created_at DESC");
$st->execute($params);
$bookings = $st->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<div class="tabs">
  <?php foreach(['all'=>'Semua','pending'=>'Menunggu','approved'=>'Disetujui','rejected'=>'Ditolak','canceled'=>'Dibatalkan'] as $k=>$v): ?>
    <a class="<?= $status===$k?'active':'' ?>" href="?status=<?= $k ?>"><?= $v ?></a>
  <?php endforeach; ?>
</div>
<div class="card">
  <table class="table">
    <thead><tr><th>Ruangan</th><th>Judul</th><th>Tanggal</th><th>Waktu</th><th>Peserta</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach($bookings as $b): ?>
        <tr>
          <td><?= e($b['room_name']) ?></td>
          <td>
            <strong><?= e($b['title']) ?></strong>
            <div class="muted"><?= nl2br(e($b['description'])) ?></div>
            <?php if($b['rescheduled_at']): ?><div class="muted">Jadwal pernah dipindahkan admin pada <?= format_date($b['rescheduled_at']) ?></div><?php endif; ?>
          </td>
          <td><?= format_date($b['booking_date']) ?></td>
          <td><?= format_time($b['start_time']) ?>-<?= format_time($b['end_time']) ?></td>
          <td><?= (int)$b['attendees'] ?></td>
          <td>
            <?= badge($b['status']) ?>
            <?php if($b['rejection_reason']): ?><div class="muted"><?= e($b['rejection_reason']) ?></div><?php endif; ?>
            <?php if($b['cancellation_reason']): ?><div class="muted"><?= e($b['cancellation_reason']) ?></div><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
