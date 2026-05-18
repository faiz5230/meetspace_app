<?php
require_once __DIR__ . '/includes/header.php';
$roomCount = $pdo->query("SELECT COUNT(*) c FROM rooms")->fetch()['c'];
$staffCount = $pdo->query("SELECT COUNT(*) c FROM users WHERE role='staff' AND status='active'")->fetch()['c'];
$pendingCount = $pdo->query("SELECT COUNT(*) c FROM bookings WHERE status='pending'")->fetch()['c'];
$todayCount = $pdo->query("SELECT COUNT(*) c FROM bookings WHERE booking_date=CURDATE() AND status='approved'")->fetch()['c'];

$rooms = $pdo->query("
SELECT r.*,
  GROUP_CONCAT(CONCAT(b.start_time,'-',b.end_time,' ',b.title) ORDER BY b.start_time SEPARATOR '<br>') AS today_schedule
FROM rooms r
LEFT JOIN bookings b ON b.room_id=r.id AND b.booking_date=CURDATE() AND b.status='approved'
GROUP BY r.id
ORDER BY r.name
")->fetchAll();

$latest = $pdo->query("
SELECT b.*, u.name user_name, r.name room_name
FROM bookings b
JOIN users u ON u.id=b.user_id
JOIN rooms r ON r.id=b.room_id
ORDER BY b.created_at DESC LIMIT 8
")->fetchAll();
?>
<script>const BASE_URL_JS = "<?= BASE_URL ?>";</script>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div><h3 class="fw-bold mb-0">Dashboard <?= $me['role']==='admin'?'Admin':'Staff' ?></h3><div class="text-muted">Sistem booking ruang meeting profesional</div></div>
</div>

<div class="row g-3 mb-4">
  <?php foreach([
    ['Total Ruangan',$roomCount,'bi-door-open'],
    ['Staff Aktif',$staffCount,'bi-people'],
    ['Booking Menunggu',$pendingCount,'bi-clock'],
    ['Booking Hari Ini',$todayCount,'bi-calendar-day'],
  ] as $s): ?>
  <div class="col-md-3"><div class="card card-soft"><div class="card-body"><div class="text-muted"><?= $s[0] ?></div><div class="display-6 fw-bold"><?= $s[1] ?></div></div></div></div>
  <?php endforeach; ?>
</div>

<div class="card card-soft mb-4">
  <div class="card-body">
    <h5 class="fw-bold mb-3">Status Semua Ruangan Hari Ini</h5>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead><tr><th>Ruangan</th><th>Kapasitas</th><th>Lantai</th><th>Status Ruangan</th><th>Jadwal Hari Ini</th><th>Fasilitas</th></tr></thead>
        <tbody>
        <?php foreach($rooms as $r): ?>
          <tr>
            <td class="fw-semibold"><?= e($r['name']) ?></td>
            <td><?= e($r['capacity']) ?> orang</td>
            <td><?= e($r['floor']) ?></td>
            <td><?= status_badge($r['status']) ?></td>
            <td><?= $r['today_schedule'] ? $r['today_schedule'] : '<span class="text-success">Tersedia</span>' ?></td>
            <td><?= e($r['facilities']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card card-soft">
  <div class="card-body">
    <h5 class="fw-bold mb-3">Pemesanan Terbaru</h5>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead><tr><th>Pemohon</th><th>Ruangan</th><th>Judul</th><th>Tanggal</th><th>Waktu</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach($latest as $b): ?>
          <tr>
            <td><?= e($b['user_name']) ?></td><td><?= e($b['room_name']) ?></td><td><?= e($b['title']) ?></td>
            <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
            <td><?= substr($b['start_time'],0,5) ?>-<?= substr($b['end_time'],0,5) ?></td>
            <td><?= status_badge($b['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
