<?php
require_once __DIR__ . '/../includes/header.php';

require_admin();

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/
$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end'] ?? date('Y-m-t');
$room  = $_GET['room'] ?? '';

$where = [
    "b.status = 'approved'",
    "DATE(b.booking_date) BETWEEN ? AND ?"
];

$params = [$start, $end];

if ($room !== '') {
    $where[] = "b.room_id = ?";
    $params[] = $room;
}

$whereSql = implode(' AND ', $where);

/*
|--------------------------------------------------------------------------
| DATA REPORT
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        b.*,
        r.name AS room_name,
        r.capacity,
        u.name AS user_name,
        u.email,
        d.name AS department_name
    FROM bookings b
    LEFT JOIN rooms r
        ON r.id = b.room_id
    LEFT JOIN users u
        ON u.id = b.user_id
    LEFT JOIN departments d
        ON d.id = u.department_id
    WHERE $whereSql
    ORDER BY b.booking_date DESC, b.start_time DESC
");

$stmt->execute($params);

$reports = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/
$totalBooking = count($reports);

$totalHours = 0;

foreach ($reports as $r) {

    $startTime = strtotime($r['start_time']);
    $endTime   = strtotime($r['end_time']);

    $diff = ($endTime - $startTime) / 3600;

    $totalHours += $diff;
}

/*
|--------------------------------------------------------------------------
| ROOM LIST
|--------------------------------------------------------------------------
*/
$rooms = $pdo->query("
    SELECT *
    FROM rooms
    ORDER BY name
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h3 class="fw-bold mb-1">
            Laporan Pemakaian Ruangan
        </h3>

        <div class="text-muted">
            Monitoring penggunaan ruangan meeting
        </div>
    </div>

</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">

    <div class="card-body">

        <form class="row g-3">

            <div class="col-md-3">
                <label class="form-label">
                    Tanggal Awal
                </label>

                <input
                    type="date"
                    class="form-control"
                    name="start"
                    value="<?= e($start) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">
                    Tanggal Akhir
                </label>

                <input
                    type="date"
                    class="form-control"
                    name="end"
                    value="<?= e($end) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">
                    Ruangan
                </label>

                <select
                    class="form-select"
                    name="room">

                    <option value="">
                        Semua Ruangan
                    </option>

                    <?php foreach ($rooms as $rm): ?>

                        <option
                            value="<?= $rm['id'] ?>"
                            <?= $room == $rm['id'] ? 'selected' : '' ?>>

                            <?= e($rm['name']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end">

                <button class="btn btn-success w-100">
                    Filter Laporan
                </button>

            </div>

        </form>

    </div>

</div>

<div class="row g-3 mb-4">

    <div class="col-md-4">

        <div class="card border-0 shadow-sm rounded-4">

            <div class="card-body">

                <div class="text-muted mb-2">
                    Total Booking
                </div>

                <h2 class="fw-bold text-success">
                    <?= $totalBooking ?>
                </h2>

            </div>

        </div>

    </div>

    <div class="col-md-4">

        <div class="card border-0 shadow-sm rounded-4">

            <div class="card-body">

                <div class="text-muted mb-2">
                    Total Jam Pemakaian
                </div>

                <h2 class="fw-bold text-primary">
                    <?= number_format($totalHours, 1) ?> Jam
                </h2>

            </div>

        </div>

    </div>

    <div class="col-md-4">

        <div class="card border-0 shadow-sm rounded-4">

            <div class="card-body">

                <div class="text-muted mb-2">
                    Total Ruangan Digunakan
                </div>

                <h2 class="fw-bold text-dark">
                    <?= count(array_unique(array_column($reports, 'room_id'))) ?>
                </h2>

            </div>

        </div>

    </div>

</div>

<div class="card border-0 shadow-sm rounded-4">

    <div class="card-body table-responsive">

        <div class="d-flex justify-content-between align-items-center mb-3">

    <h5 class="fw-bold mb-0">
        Detail Pemakaian Ruangan
    </h5>

    <div class="d-flex gap-2">

        <a
            href="<?= BASE_URL ?>/admin/export_reports_excel.php?start=<?= e($start) ?>&end=<?= e($end) ?>&room=<?= e($room) ?>"
            class="btn btn-success btn-sm">

            <i class="bi bi-file-earmark-excel"></i>
            Export Excel

        </a>

        <a
            href="<?= BASE_URL ?>/admin/export_reports_pdf.php?start=<?= e($start) ?>&end=<?= e($end) ?>&room=<?= e($room) ?>"
            target="_blank"
            class="btn btn-danger btn-sm">

            <i class="bi bi-file-earmark-pdf"></i>
            Export PDF

        </a>

        <button
            onclick="window.print()"
            class="btn btn-outline-dark btn-sm">

            Print Laporan

        </button>

    </div>

</div>

        <table class="table table-bordered align-middle">

            <thead class="table-light">

                <tr>

                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Ruangan</th>
                    <th>User</th>
                    <th>Departemen</th>
                    <th>Judul Meeting</th>
                    <th>Jam</th>
                    <th>Durasi</th>
                    <th>Status</th>

                </tr>

            </thead>

            <tbody>

                <?php if ($reports): ?>

                    <?php foreach ($reports as $i => $r): ?>

                        <?php
                        $duration =
                            (
                                strtotime($r['end_time']) -
                                strtotime($r['start_time'])
                            ) / 3600;
                        ?>

                        <tr>

                            <td>
                                <?= $i + 1 ?>
                            </td>

                            <td>
                                <?= date('d M Y', strtotime($r['booking_date'])) ?>
                            </td>

                            <td>

                                <div class="fw-semibold">
                                    <?= e($r['room_name']) ?>
                                </div>

                                <div class="small text-muted">
                                    Kapasitas:
                                    <?= e($r['capacity']) ?> orang
                                </div>

                            </td>

                            <td>

                                <div class="fw-semibold">
                                    <?= e($r['user_name']) ?>
                                </div>

                                <div class="small text-muted">
                                    <?= e($r['email']) ?>
                                </div>

                            </td>

                            <td>
                                <?= e($r['department_name'] ?? '-') ?>
                            </td>

                            <td>
                                <?= e($r['title']) ?>
                            </td>

                            <td>

                                <?= substr($r['start_time'],0,5) ?>
                                -
                                <?= substr($r['end_time'],0,5) ?>

                            </td>

                            <td>
                                <?= number_format($duration,1) ?> Jam
                            </td>

                            <td>

                                <span class="badge text-bg-success">
                                    Approved
                                </span>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="9"
                            class="text-center py-4 text-muted">

                            Tidak ada data laporan.

                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>