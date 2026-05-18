<?php
require_once __DIR__ . '/includes/header.php';

$roomCount = $pdo->query("SELECT COUNT(*) c FROM rooms")->fetch()['c'];
$staffCount = $pdo->query("SELECT COUNT(*) c FROM users WHERE role='staff' AND status='active'")->fetch()['c'];
$pendingCount = $pdo->query("SELECT COUNT(*) c FROM bookings WHERE status='pending'")->fetch()['c'];
$todayCount = $pdo->query("SELECT COUNT(*) c FROM bookings WHERE booking_date=CURDATE() AND status='approved'")->fetch()['c'];

$rooms = $pdo->query("
SELECT 
    r.*,
    GROUP_CONCAT(
        CONCAT(
            DATE_FORMAT(b.booking_date, '%d-%m-%Y'),
            ' | ',
            CASE DAYOFWEEK(b.booking_date)
                WHEN 1 THEN 'Minggu'
                WHEN 2 THEN 'Senin'
                WHEN 3 THEN 'Selasa'
                WHEN 4 THEN 'Rabu'
                WHEN 5 THEN 'Kamis'
                WHEN 6 THEN 'Jumat'
                WHEN 7 THEN 'Sabtu'
            END,
            ' | ',
            TIME_FORMAT(b.start_time, '%H:%i'),
            '-',
            TIME_FORMAT(b.end_time, '%H:%i'),
            ' | ',
            b.title
        )
        ORDER BY b.booking_date, b.start_time
        SEPARATOR '<br>'
    ) AS approved_schedule
FROM rooms r
LEFT JOIN bookings b 
    ON b.room_id = r.id
    AND b.status = 'approved'
    AND b.booking_date >= CURDATE()
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
        <h5 class="fw-bold mb-3">Status Semua Ruangan</h5>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Ruangan</th>
                        <th>Kapasitas</th>
                        <th>Lantai</th>
                        <th>Status Ruangan</th>
                        <th>Jadwal Approved</th>
                        <th>Fasilitas</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($rooms as $r): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($r['name']); ?></td>
                            <td><?= e($r['capacity']); ?> orang</td>
                            <td><?= e($r['floor']); ?></td>
                            <td><?= status_badge($r['status']); ?></td>
                            <td>
                                <?php if (!empty($r['approved_schedule'])): ?>
                                    <div class="small"><?= $r['approved_schedule']; ?></div>
                                <?php else: ?>
                                    <span class="text-success">Belum ada booking approved</span>
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
                            <td colspan="6" class="text-center text-muted py-4">
                                Belum ada data ruangan.
                            </td>
                        </tr>
                    <?php endif; ?>
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
