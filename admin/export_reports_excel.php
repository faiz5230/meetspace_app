<?php

require_once __DIR__ . '/../includes/functions.php';

require_admin();

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=laporan_ruangan.xls");

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

$stmt = $pdo->prepare("
    SELECT
        b.*,
        r.name AS room_name,
        u.name AS user_name,
        d.name AS department_name
    FROM bookings b
    LEFT JOIN rooms r
        ON r.id = b.room_id
    LEFT JOIN users u
        ON u.id = b.user_id
    LEFT JOIN departments d
        ON d.id = u.department_id
    WHERE $whereSql
    ORDER BY b.booking_date DESC
");

$stmt->execute($params);

$data = $stmt->fetchAll();

?>

<table border="1">

    <tr style="background:#198754;color:#fff;">

        <th>No</th>
        <th>Tanggal</th>
        <th>Ruangan</th>
        <th>User</th>
        <th>Departemen</th>
        <th>Judul Meeting</th>
        <th>Jam</th>
        <th>Status</th>

    </tr>

    <?php foreach ($data as $i => $r): ?>

        <tr>

            <td><?= $i + 1 ?></td>

            <td>
                <?= date('d M Y', strtotime($r['booking_date'])) ?>
            </td>

            <td>
                <?= $r['room_name'] ?>
            </td>

            <td>
                <?= $r['user_name'] ?>
            </td>

            <td>
                <?= $r['department_name'] ?>
            </td>

            <td>
                <?= $r['title'] ?>
            </td>

            <td>
                <?= substr($r['start_time'],0,5) ?>
                -
                <?= substr($r['end_time'],0,5) ?>
            </td>

            <td>
                Approved
            </td>

        </tr>

    <?php endforeach; ?>

</table>