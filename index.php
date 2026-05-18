<?php
require_once __DIR__ . '/includes/header.php';

$roomCount = $pdo->query("SELECT COUNT(*) c FROM rooms")->fetch()['c'];
$staffCount = $pdo->query("SELECT COUNT(*) c FROM users WHERE role='staff' AND status='active'")->fetch()['c'];
$pendingCount = $pdo->query("SELECT COUNT(*) c FROM bookings WHERE status='pending'")->fetch()['c'];
$todayCount = $pdo->query("SELECT COUNT(*) c FROM bookings WHERE booking_date=CURDATE() AND status='approved'")->fetch()['c'];

/*
|--------------------------------------------------------------------------
| STATUS SEMUA RUANGAN
|--------------------------------------------------------------------------
| Status:
| - Close     : ruangan dinonaktifkan admin
| - Dipakai   : ada booking approved hari ini dan jam sekarang sedang berjalan
| - Tersedia  : tidak sedang dipakai
|
| Antrian booking:
| - Menampilkan semua booking approved di hari yang sama, walaupun jam berbeda.
*/
$rooms = $pdo->query("
SELECT 
    r.*,

    MAX(
        CASE 
            WHEN b_now.id IS NOT NULL THEN 1 
            ELSE 0 
        END
    ) AS is_used_now,

    GROUP_CONCAT(
        DISTINCT CONCAT(
            TIME_FORMAT(b_today.start_time, '%H:%i'),
            '-',
            TIME_FORMAT(b_today.end_time, '%H:%i'),
            ' | ',
            b_today.title,
            ' | ',
            u_today.name
        )
        ORDER BY b_today.start_time
        SEPARATOR '<br>'
    ) AS today_queue,

    GROUP_CONCAT(
        DISTINCT CONCAT(
            DATE_FORMAT(b_future.booking_date, '%d-%m-%Y'),
            ' | ',
            CASE DAYOFWEEK(b_future.booking_date)
                WHEN 1 THEN 'Minggu'
                WHEN 2 THEN 'Senin'
                WHEN 3 THEN 'Selasa'
                WHEN 4 THEN 'Rabu'
                WHEN 5 THEN 'Kamis'
                WHEN 6 THEN 'Jumat'
                WHEN 7 THEN 'Sabtu'
            END,
            ' | ',
            TIME_FORMAT(b_future.start_time, '%H:%i'),
            '-',
            TIME_FORMAT(b_future.end_time, '%H:%i'),
            ' | ',
            b_future.title
        )
        ORDER BY b_future.booking_date, b_future.start_time
        SEPARATOR '<br>'
    ) AS approved_schedule

FROM rooms r

LEFT JOIN bookings b_now
    ON b_now.room_id = r.id
    AND b_now.status = 'approved'
    AND b_now.booking_date = CURDATE()
    AND CURTIME() BETWEEN b_now.start_time AND b_now.end_time

LEFT JOIN bookings b_today
    ON b_today.room_id = r.id
    AND b_today.status = 'approved'
    AND b_today.booking_date = CURDATE()

LEFT JOIN users u_today
    ON u_today.id = b_today.user_id

LEFT JOIN bookings b_future
    ON b_future.room_id = r.id
    AND b_future.status = 'approved'
    AND b_future.booking_date >= CURDATE()

GROUP BY r.id
ORDER BY r.name
")->fetchAll();

$latest = $pdo->query("
SELECT b.*, u.name user_name, r.name room_name
FROM bookings b
JOIN users u ON u.id = b.user_id
JOIN rooms r ON r.id = b.room_id
WHERE 
    b.booking_date >= CURDATE()
ORDER BY b.created_at DESC
LIMIT 8
")->fetchAll();
?>

<script>
const BASE_URL_JS = "<?= BASE_URL ?>";
</script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-0">Dashboard <?= $me['role'] === 'admin' ? 'Admin' : 'Staff' ?></h3>
        <div class="text-muted">Sistem booking ruang meeting profesional</div>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['Total Ruangan', $roomCount],
        ['Staff Aktif', $staffCount],
        ['Booking Menunggu', $pendingCount],
        ['Booking Hari Ini', $todayCount],
    ] as $s): ?>
        <div class="col-md-3">
            <div class="card card-soft">
                <div class="card-body">
                    <div class="text-muted"><?= e($s[0]); ?></div>
                    <div class="display-6 fw-bold"><?= e($s[1]); ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card card-soft mb-4">
    <div class="card-body">
        <h5 class="fw-bold mb-3">Status Semua Ruangan Hari Ini</h5>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Ruangan</th>
                        <th>Kapasitas</th>
                        <th>Lantai</th>
                        <th>Status Saat Ini</th>
                        <th>Antrian Booking Hari Ini</th>
                        <th>Jadwal Approved Berikutnya</th>
                        <th>Fasilitas</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($rooms as $r): ?>
                        <?php
                            if ($r['status'] === 'closed') {

								$roomStatus = '
								<span class="badge text-bg-danger">
								Close
							</span>
							<div class="small text-muted mt-1">
								Ditutup sementara admin
							</div>
							';

							} elseif ($r['status'] === 'inactive') {

								$roomStatus = '
								<span class="badge text-bg-dark">
								Nonaktif
								</span>
							<div class="small text-muted mt-1">
								Maintenance / Tidak digunakan
							</div>
							';

							} elseif ((int)$r['is_used_now'] === 1) {

								$roomStatus = '
								<span class="badge text-bg-warning">
								Dipakai
								</span>
							<div class="small text-muted mt-1">
								Sedang digunakan
							</div>
							';

							} else {

								$roomStatus = '
								<span class="badge text-bg-success">
								Tersedia
								</span>
								<div class="small text-muted mt-1">
								Tidak sedang dipakai
								</div>
								';
							}
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= e($r['name']); ?></td>
                            <td><?= e($r['capacity']); ?> orang</td>
                            <td><?= e($r['floor']); ?></td>
                            <td><?= $roomStatus; ?></td>

                            <td>
                                <?php if (!empty($r['today_queue'])): ?>
                                    <div class="small"><?= $r['today_queue']; ?></div>
                                <?php else: ?>
                                    <span class="text-success">Tidak ada antrian hari ini</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (!empty($r['approved_schedule'])): ?>
                                    <div class="small"><?= $r['approved_schedule']; ?></div>
                                <?php else: ?>
                                    <span class="text-muted">Belum ada jadwal approved</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php
                                $facilities = array_filter(array_map('trim', explode(',', $r['facilities'] ?? '')));
                                if ($facilities):
                                    foreach ($facilities as $facility):
                                ?>
                                    <span class="badge rounded-pill text-bg-light border me-1 mb-1">
                                        <?= e($facility); ?>
                                    </span>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                    <span class="text-muted">Belum ada fasilitas</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$rooms): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Belum ada data ruangan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="small text-muted mt-2">
            Keterangan: <strong>Dipakai</strong> muncul jika jam sekarang berada di antara jam booking approved hari ini. 
            <strong>Close</strong> muncul jika admin menonaktifkan ruangan.
        </div>
    </div>
</div>

<div class="card card-soft">
    <div class="card-body">
        <h5 class="fw-bold mb-3">Pemesanan Terbaru</h5>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Pemohon</th>
                        <th>Ruangan</th>
                        <th>Judul</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($latest as $b): ?>
                        <tr>
                            <td><?= e($b['user_name']); ?></td>
                            <td><?= e($b['room_name']); ?></td>
                            <td><?= e($b['title']); ?></td>
                            <td><?= date('d M Y', strtotime($b['booking_date'])); ?></td>
                            <td><?= substr($b['start_time'], 0, 5); ?>-<?= substr($b['end_time'], 0, 5); ?></td>
                            <td><?= status_badge($b['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$latest): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Belum ada data pemesanan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
