<?php
require_once __DIR__ . '/../includes/functions.php';
require_staff();
$pageTitle = 'Dashboard Staff';
$active = 'dashboard';
$uid = current_user()['id'];

$pending = count_table("SELECT COUNT(*) FROM bookings WHERE user_id=? AND status='pending'", [$uid]);
$approved = count_table("SELECT COUNT(*) FROM bookings WHERE user_id=? AND status='approved'", [$uid]);
$rejected = count_table("SELECT COUNT(*) FROM bookings WHERE user_id=? AND status='rejected'", [$uid]);

$roomStatusRows = db()->query("\n    SELECT\n        r.*,\n        (SELECT COUNT(*) FROM bookings b WHERE b.room_id = r.id AND b.booking_date = CURDATE() AND b.status = 'approved') AS approved_today,\n        (SELECT COUNT(*) FROM bookings b WHERE b.room_id = r.id AND b.booking_date = CURDATE() AND b.status = 'pending') AS pending_today,\n        (SELECT COUNT(*) FROM bookings b WHERE b.room_id = r.id AND b.booking_date = CURDATE() AND b.status = 'approved' AND CURTIME() >= b.start_time AND CURTIME() < b.end_time) AS occupied_now,\n        (SELECT CONCAT(TIME_FORMAT(b.start_time, '%H:%i'), '-', TIME_FORMAT(b.end_time, '%H:%i'), ' - ', b.title)\n           FROM bookings b\n          WHERE b.room_id = r.id AND b.booking_date = CURDATE() AND b.status = 'approved' AND b.end_time >= CURTIME()\n          ORDER BY b.start_time ASC\n          LIMIT 1) AS next_schedule\n    FROM rooms r\n    ORDER BY r.name ASC\n")->fetchAll();

$st = db()->prepare("SELECT b.*,r.name room_name FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.user_id=? AND b.status='approved' AND b.booking_date>=CURDATE() ORDER BY b.booking_date,b.start_time LIMIT 5");
$st->execute([$uid]);
$upcoming = $st->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<div class="card" style="background:linear-gradient(135deg,#059669,#047857);color:#fff">
  <h2>Selamat datang, <?= e(current_user()['name']) ?></h2>
  <p style="color:#d1fae5">Pantau status pemesanan ruangan Anda.</p>
</div>

<div class="grid grid-3" style="margin-top:20px">
  <div class="card stat"><div class="muted">Menunggu</div><div class="num"><?= $pending ?></div></div>
  <div class="card stat"><div class="muted">Disetujui</div><div class="num"><?= $approved ?></div></div>
  <div class="card stat"><div class="muted">Ditolak</div><div class="num"><?= $rejected ?></div></div>
</div>

<div class="card" style="margin-top:20px">
  <div class="section-head">
    <div>
      <h3>Status Semua Ruangan</h3>
      <p class="muted small">Lihat ketersediaan semua ruangan hari ini sebelum mengajukan booking.</p>
    </div>
    <span class="badge muted"><?= date('H:i') ?> WIB</span>
  </div>
  <table class="table">
    <thead>
      <tr><th>Nama Ruangan</th><th>Kapasitas</th><th>Lantai</th><th>Status Ruangan</th><th>Status Hari Ini</th><th>Jam Booking</th><th>Keterangan</th></tr>
    </thead>
    <tbody>
      <?php foreach ($roomStatusRows as $room):
        $todayBadge = '<span class="badge success">Tersedia</span>';
        $scheduleText = $room['next_schedule'] ?: '-';
        $note = 'Belum ada jadwal approved berikutnya hari ini.';
        if ($room['status'] === 'inactive') {
            $todayBadge = '<span class="badge muted">Nonaktif</span>';
            $note = 'Ruangan tidak dapat digunakan.';
        } elseif ((int)$room['occupied_now'] > 0) {
            $todayBadge = '<span class="badge danger">Sedang Dipakai</span>';
            $note = 'Sedang ada meeting berlangsung.';
        } elseif ((int)$room['approved_today'] > 0) {
            $todayBadge = '<span class="badge warning">Dipesan Hari Ini</span>';
            $note = 'Ada jadwal approved hari ini.';
        } elseif ((int)$room['pending_today'] > 0) {
            $todayBadge = '<span class="badge warning">Menunggu Approval</span>';
            $note = 'Ada request booking yang belum diproses.';
        }
      ?>
        <tr>
          <td><strong><?= e($room['name']) ?></strong></td>
          <td><?= (int)$room['capacity'] ?> orang</td>
          <td><?= (int)$room['floor'] ?></td>
          <td><?= badge($room['status']) ?></td>
          <td><?= $todayBadge ?></td>
          <td><?= e($scheduleText) ?></td>
          <td class="muted"><?= e($note) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card" style="margin-top:20px">
  <h3>Booking Mendatang</h3>
  <table class="table">
    <thead><tr><th>Ruangan</th><th>Judul</th><th>Tanggal</th><th>Waktu</th></tr></thead>
    <tbody>
      <?php foreach($upcoming as $b): ?>
        <tr>
          <td><?= e($b['room_name']) ?></td>
          <td><?= e($b['title']) ?></td>
          <td><?= format_date($b['booking_date']) ?></td>
          <td><?= format_time($b['start_time']) ?>-<?= format_time($b['end_time']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
