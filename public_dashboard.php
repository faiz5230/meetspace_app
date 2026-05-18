<?php
require_once __DIR__ . '/config/database.php';

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function get_public_setting($key, $default = '')
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT setting_value
        FROM app_settings
        WHERE setting_key = ?
    ");

    $stmt->execute([$key]);

    $row = $stmt->fetch();

    return $row ? $row['setting_value'] : $default;
}

$appName = get_public_setting('app_name', 'MeetSpace');
$logo = get_public_setting('public_logo');
$background = get_public_setting('public_background');
$bookNowImage = get_public_setting('book_now_image');

$settingUploadUrl = 'uploads/settings/';

$roomId = $_GET['room_id'] ?? null;

if (!$roomId) {
    $room = $pdo->query("
        SELECT *
        FROM rooms
        WHERE status IN ('active','closed')
        ORDER BY id ASC
        LIMIT 1
    ")->fetch();
} else {
    $stmt = $pdo->prepare("
        SELECT *
        FROM rooms
        WHERE id = ?
    ");
    $stmt->execute([$roomId]);
    $room = $stmt->fetch();
}

if (!$room) {
    die('Ruangan tidak ditemukan');
}

$stmt = $pdo->prepare("
    SELECT
        b.*,
        u.name AS user_name
    FROM bookings b
    LEFT JOIN users u ON u.id = b.user_id
    WHERE b.room_id = ?
    AND b.booking_date = CURDATE()
    AND b.status IN ('pending','approved')
    ORDER BY b.start_time ASC
");

$stmt->execute([$room['id']]);
$schedules = $stmt->fetchAll();

$now = date('H:i:s');
$currentBooking = null;

foreach ($schedules as $s) {
    if (
        $s['status'] === 'approved' &&
        $now >= $s['start_time'] &&
        $now <= $s['end_time']
    ) {
        $currentBooking = $s;
        break;
    }
}

$statusText = 'Available';
$statusClass = 'available';

if ($room['status'] === 'closed') {
    $statusText = 'Closed';
    $statusClass = 'closed';
} elseif ($currentBooking) {
    $statusText = 'In a Meeting';
    $statusClass = 'meeting';
}

$qrLink =
    'http://' .
    $_SERVER['HTTP_HOST'] .
    dirname($_SERVER['PHP_SELF']) .
    '/booking_create.php?room_id=' .
    $room['id'];

$qrImage =
    'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' .
    urlencode($qrLink);

$bgImage = $background
    ? $settingUploadUrl . $background
    : 'assets/img/dashboard-bg.jpg';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>
    <?= e($appName) ?> - <?= e($room['name']) ?>
</title>

<style>
* {
    box-sizing: border-box;
}

html,
body {
    width: 100%;
    height: 100%;
}

body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    color: #fff;
    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            rgba(57, 98, 228, .18),
            rgba(122, 206, 255, .12)
        ),
        url("<?= e($bgImage) ?>");

    background-size: 100% 100%;
    background-position: center center;
    background-repeat: no-repeat;
    background-attachment: fixed;
}

body::before {
    content: "";
    position: fixed;
    inset: 0;
    background:
        linear-gradient(
            135deg,
            rgba(0, 0, 0, .03),
            rgba(0, 0, 0, .07)
        );
    z-index: 1;
    pointer-events: none;
}

.page {
    width: 100vw;
    height: 100vh;
    padding: 55px 65px;
    display: grid;
    grid-template-columns: 58% 42%;
    gap: 45px;
    position: relative;
    z-index: 2;
}

.left {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.logo {
    font-size: 44px;
    font-weight: 900;
    letter-spacing: 1px;
}

.logo img {
    max-height: 90px;
    max-width: 260px;
    object-fit: contain;
}

.room-title {
    margin-top: 70px;
}

.room-title h1 {
    margin: 0;
    font-size: 78px;
    line-height: 1;
    font-weight: 900;
    text-shadow: 0 4px 10px rgba(0,0,0,.18);
}

.room-time {
    margin-top: 12px;
    display: flex;
    align-items: center;
    gap: 25px;
    font-size: 40px;
}

.status-pill {
    padding: 10px 25px;
    border-radius: 999px;
    font-size: 28px;
    font-weight: 700;
}

.status-pill.meeting {
    background: #ff3333;
}

.status-pill.available {
    background: #16a34a;
}

.status-pill.closed {
    background: #111827;
}

.location {
    margin-top: 45px;
}

.location h2 {
    margin: 0 0 12px;
    font-size: 42px;
    font-weight: 900;
}

.location p {
    margin: 0;
    font-size: 25px;
    line-height: 1.45;
    max-width: 860px;
}

.bottom {
    display: flex;
    align-items: center;
    gap: 55px;
}

.book-btn {
    background: #ffc400;
    color: #fff;
    border-radius: 999px;
    padding: 24px 45px;
    font-size: 30px;
    font-weight: 900;
    letter-spacing: 3px;
    display: inline-flex;
    align-items: center;
    gap: 25px;
    box-shadow: 0 8px 20px rgba(0,0,0,.15);
}

.book-btn span {
    background: #fff;
    color: #ffc400;
    width: 58px;
    height: 58px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
}

.qr-box {
    background: #fff;
    padding: 14px;
    border-radius: 18px;
    color: #111;
    text-align: center;
    font-weight: 900;
}

.qr-box img {
    width: 160px;
    height: 160px;
    display: block;
}

.right {
    display: flex;
    align-items: center;
    justify-content: center;
}

.panel {
    width: 100%;
    max-width: 620px;
    background: rgba(255,255,255,.82);
    color: #111;
    min-height: 78vh;
}

.clock-box {
    background: rgba(0,132,190,.82);
    color: #fff;
    text-align: center;
    padding: 30px 20px;
}

.clock {
    font-size: 96px;
    font-weight: 300;
    line-height: 1;
}

.date {
    font-size: 28px;
    margin-top: 8px;
}

.schedule {
    padding: 22px 30px;
}

.item {
    display: grid;
    grid-template-columns: 10px 1fr;
    gap: 22px;
    padding: 14px 0;
    border-bottom: 3px solid rgba(255,255,255,.8);
}

.item.active {
    background: #fff;
    border-radius: 18px;
    padding: 18px 20px;
    margin: 8px -10px;
    box-shadow: 0 6px 18px rgba(0,0,0,.08);
}

.bar {
    width: 7px;
    border-radius: 10px;
    background: #0ea5e9;
}

.item.active .bar {
    background: #ff3333;
}

.item.done {
    opacity: .45;
}

.item-title {
    font-size: 27px;
    font-weight: 900;
}

.item-time {
    font-size: 25px;
    margin-top: 6px;
}

.item-time .running {
    color: #ef233c;
}

.item-time .waiting {
    color: #111;
}

.item-time .done-text {
    color: #555;
}

.empty {
    text-align: center;
    color: #333;
    font-size: 28px;
    padding: 70px 20px;
}

@media(max-width: 1000px) {
    body {
        overflow: auto;
        background-size: cover;
    }

    .page {
        grid-template-columns: 1fr;
        height: auto;
        min-height: 100vh;
        overflow-y: auto;
        padding: 30px;
    }

    .room-title h1 {
        font-size: 52px;
    }

    .room-time {
        font-size: 28px;
        flex-wrap: wrap;
    }

    .panel {
        max-width: 100%;
    }
}
</style>
</head>

<body>

<div class="page">

    <div class="left">

        <div>

            <div class="logo">
                <?php if ($logo): ?>
                    <img src="<?= e($settingUploadUrl . $logo) ?>" alt="Logo">
                <?php else: ?>
                    <?= e($appName) ?>
                <?php endif; ?>
            </div>

            <div class="room-title">
                <h1><?= e($room['name']) ?></h1>

                <div class="room-time">
                    <?php if ($currentBooking): ?>
                        <div>
                            <?= substr($currentBooking['start_time'],0,5) ?>
                            -
                            <?= substr($currentBooking['end_time'],0,5) ?>
                        </div>
                    <?php else: ?>
                        <div>Ready</div>
                    <?php endif; ?>

                    <div class="status-pill <?= e($statusClass) ?>">
                        <?= e($statusText) ?>
                    </div>
                </div>
            </div>

            <div class="location">
                <h2>Meeting Room</h2>
                <p>
                    Jl. Laswi No.104-108, Cibangkong, Kec. Batununggal, Kota Bandung, Jawa Barat 40273.
                </p>
            </div>

        </div>

        <div class="bottom">

            <?php if ($bookNowImage): ?>

			<img
			src="<?= e($settingUploadUrl . $bookNowImage) ?>"
			alt="Book Now"
			style="max-height:120px;max-width:380px;object-fit:contain;">

			<?php else: ?>

			<div class="book-btn">
				BOOK NOW
			<span>›</span>
			</div>

			<?php endif; ?>

            <div class="qr-box">
                <img src="<?= e($qrImage) ?>" alt="QR Booking">
                SCAN HERE
            </div>

        </div>

    </div>

    <div class="right">

        <div class="panel">

            <div class="clock-box">
                <div class="clock" id="clock">--:--</div>
                <div class="date" id="dateText"></div>
            </div>

            <div class="schedule">

                <?php if ($schedules): ?>

                    <?php foreach ($schedules as $s): ?>

                        <?php
                        $isActive =
                            $s['status'] === 'approved' &&
                            $now >= $s['start_time'] &&
                            $now <= $s['end_time'];

                        $isDone =
                            $s['status'] === 'approved' &&
                            $now > $s['end_time'];

                        $itemClass = $isActive ? 'active' : ($isDone ? 'done' : '');

                        if ($isActive) {
                            $label = '<span class="running">Berlangsung</span>';
                        } elseif ($isDone) {
                            $label = '<span class="done-text">Selesai</span>';
                        } else {
                            $label = '<span class="waiting">Menunggu</span>';
                        }
                        ?>

                        <div class="item <?= e($itemClass) ?>">
                            <div class="bar"></div>

                            <div>
                                <div class="item-title">
                                    <?= e($s['title'] ?? 'Meeting') ?>
                                </div>

                                <div class="item-time">
                                    <?= substr($s['start_time'],0,5) ?>
                                    -
                                    <?= substr($s['end_time'],0,5) ?>
                                    |
                                    <?= $label ?>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="empty">
                        Belum ada booking hari ini
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>

<script>
function updateClock() {
    const now = new Date();

    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');

    document.getElementById('clock').innerText = h + ':' + m;

    const bulan = [
        'Januari','Februari','Maret','April',
        'Mei','Juni','Juli','Agustus',
        'September','Oktober','November','Desember'
    ];

    document.getElementById('dateText').innerText =
        now.getDate() + ' ' +
        bulan[now.getMonth()] + ' ' +
        now.getFullYear();
}

updateClock();
setInterval(updateClock, 1000);

setInterval(function () {
    window.location.reload();
}, 60000);
</script>

</body>
</html>