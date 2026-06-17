<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$userId = $_SESSION['user_id'] ?? null;

$stmtUser = $pdo->prepare("
    SELECT *
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmtUser->execute([$userId]);
$me = $stmtUser->fetch();

$role = $me['role'] ?? 'staff';

$rows = $pdo->query("
    SELECT 
        b.*, 
        r.name AS room_name, 
        u.name AS user_name 
    FROM bookings b 
    JOIN rooms r ON r.id = b.room_id 
    JOIN users u ON u.id = b.user_id 
    WHERE b.status IN ('approved','pending')
")->fetchAll();

$out = [];

foreach ($rows as $b) {

    $organizerStatus = strtolower(trim($b['organizer_status'] ?? ''));
    $organizerName   = trim($b['organizer_name'] ?? '');
    $bookingTitle    = trim($b['title'] ?? '');

    if ($role === 'admin') {

        $title = $b['room_name'];

        if ($bookingTitle !== '') {
            $title .= ' - ' . $bookingTitle;
        }

        if ($organizerName !== '') {
            $title .= ' - ' . $organizerName;
        }

        if ($organizerStatus !== '') {
            $title .= ' [' . ucfirst($organizerStatus) . ']';
        }

    } else {

        $isOwnBooking = ((int)$b['user_id'] === (int)$userId);

        $title = $b['room_name'];

        if ($organizerStatus === 'internal') {

            if ($isOwnBooking && $bookingTitle !== '') {
                $title .= ' - ' . $bookingTitle;
            }

            if ($organizerName !== '') {
                $title .= ' - ' . $organizerName;
            }

            $title .= ' [Internal]';

        } else {

            if ($bookingTitle !== '') {
                $title .= ' - ' . $bookingTitle;
            }

            if ($organizerName !== '') {
                $title .= ' - ' . $organizerName;
            }

            $title .= ' [External]';
        }
    }

    $out[] = [
        'id'    => $b['id'],
        'title' => $title,
        'start' => $b['booking_date'] . 'T' . $b['start_time'],
        'end'   => $b['booking_date'] . 'T' . $b['end_time'],
        'color' => $b['status'] === 'approved' ? '#198754' : '#f59f00',
        'description' => $role === 'admin' ? ($b['description'] ?? '') : ''
    ];
}

header('Content-Type: application/json');
echo json_encode($out);
?>