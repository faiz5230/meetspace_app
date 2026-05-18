<?php
require_once __DIR__ . '/includes/header.php';

$rooms = $pdo->query("
    SELECT *
    FROM rooms
    WHERE status = 'active'
    ORDER BY name
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $roomId      = $_POST['room_id'];
    $bookingDate = $_POST['booking_date'];
    $startTime   = $_POST['start_time'];
    $endTime     = $_POST['end_time'];
    $title       = $_POST['title'];
    $description = $_POST['description'];
    $attendees   = $_POST['attendees'];

    /*
    |--------------------------------------------------------------------------
    | VALIDASI JAM SUDAH TERLEWAT
    |--------------------------------------------------------------------------
    */

    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s');

    if (
        $bookingDate === $currentDate &&
        $startTime <= $currentTime
    ) {

        $err =
            'Pengajuan booking ditolak karena jam booking sudah terlewat.<br><br>' .
            'Jam sekarang: <b>' . date('H:i') . '</b>';

    }

    /*
    |--------------------------------------------------------------------------
    | VALIDASI JAM
    |--------------------------------------------------------------------------
    */
    elseif ($startTime >= $endTime) {

        $err = 'Jam selesai harus lebih besar dari jam mulai.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | VALIDASI BENTROK + BUFFER 30 MENIT
        |--------------------------------------------------------------------------
        */

        $confStmt = $pdo->prepare("
            SELECT
                b.*,
                r.name AS room_name
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            WHERE b.room_id = ?
            AND b.booking_date = ?
            AND b.status IN ('pending', 'approved')
            AND (
                TIME(?) < ADDTIME(b.end_time, '00:30:00')
                AND
                TIME(?) > b.start_time
            )
            LIMIT 1
        ");

        $confStmt->execute([
            $roomId,
            $bookingDate,
            $startTime,
            $endTime
        ]);

        $conf = $confStmt->fetch();

        /*
        |--------------------------------------------------------------------------
        | JIKA BENTROK
        |--------------------------------------------------------------------------
        */
        if ($conf) {

            $err =
                'Booking ditolak karena jadwal bentrok atau masih dalam jeda 30 menit setelah pemakaian ruangan.<br><br>' .
                '<b>Booking aktif:</b><br>' .
                e($conf['room_name']) . '<br>' .
                substr($conf['start_time'], 0, 5) .
                ' - ' .
                substr($conf['end_time'], 0, 5);

        } else {

            /*
            |--------------------------------------------------------------------------
            | SIMPAN BOOKING
            |--------------------------------------------------------------------------
            */
            $stmt = $pdo->prepare("
                INSERT INTO bookings (
                    user_id,
                    room_id,
                    title,
                    description,
                    booking_date,
                    start_time,
                    end_time,
                    attendees
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $me['id'],
                $roomId,
                $title,
                $description,
                $bookingDate,
                $startTime,
                $endTime,
                $attendees
            ]);

            $bookingId = $pdo->lastInsertId();

            /*
            |--------------------------------------------------------------------------
            | NOTIFIKASI ADMIN
            |--------------------------------------------------------------------------
            */
            notify_admins(
                'Booking Baru',
                'Ada pengajuan booking baru dari ' . $me['name'],
                'warning'
            );

            audit_log(
                'CREATE_BOOKING',
                'User mengajukan booking ID ' . $bookingId
            );

            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            */
            redirect('my_bookings.php');
        }
    }
}
?>

<script>
const BASE_URL_JS = "<?= BASE_URL ?>";
</script>

<h3 class="fw-bold mb-3">
    Ajukan Booking Ruangan
</h3>

<?php if (!empty($err)): ?>

    <div class="alert alert-danger">
        <?= $err ?>
    </div>

<?php endif; ?>

<div class="card card-soft">

    <div class="card-body">

        <form method="post" class="row g-3">

            <div class="col-md-6">

                <label class="form-label">
                    Ruangan
                </label>

                <select
                    class="form-select"
                    name="room_id"
                    id="roomSelect"
                    required>

                    <option value="">
                        Pilih
                    </option>

                    <?php foreach ($rooms as $r): ?>

                        <option
                            value="<?= $r['id'] ?>"
                            data-facilities="<?= e($r['facilities']) ?>"
                            data-capacity="<?= $r['capacity'] ?>">

                            <?= e($r['name']) ?>
                            -
                            Kapasitas <?= $r['capacity'] ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="col-md-6">

                <label class="form-label">
                    Jumlah Peserta
                </label>

                <input
                    class="form-control"
                    type="number"
                    name="attendees"
                    min="1"
                    required>

            </div>

            <div class="col-12">

                <div
                    id="facilitiesBox"
                    class="alert alert-info d-none">
                </div>

            </div>

            <div class="col-md-4">

                <label class="form-label">
                    Tanggal
                </label>

                <input
                    class="form-control"
                    type="date"
                    name="booking_date"
                    required>

            </div>

            <div class="col-md-4">

                <label class="form-label">
                    Mulai
                </label>

                <input
                    class="form-control"
                    type="time"
                    name="start_time"
                    required>

            </div>

            <div class="col-md-4">

                <label class="form-label">
                    Selesai
                </label>

                <input
                    class="form-control"
                    type="time"
                    name="end_time"
                    required>

            </div>

            <div class="col-12">

                <label class="form-label">
                    Judul
                </label>

                <input
                    class="form-control"
                    name="title"
                    required>

            </div>

            <div class="col-12">

                <label class="form-label">
                    Deskripsi
                </label>

                <textarea
                    class="form-control"
                    name="description"></textarea>

            </div>

            <div class="col-12">

                <button class="btn btn-success">
                    Ajukan Booking
                </button>

            </div>

        </form>

    </div>

</div>

<script>
const roomSelect = document.getElementById('roomSelect');

roomSelect.addEventListener('change', function () {

    const opt = this.options[this.selectedIndex];

    const box = document.getElementById('facilitiesBox');

    if (opt && opt.dataset.facilities) {

        box.classList.remove('d-none');

        box.innerHTML =
            '<b>Fasilitas lengkap:</b> ' +
            opt.dataset.facilities +
            '<br><b>Kapasitas:</b> ' +
            opt.dataset.capacity +
            ' orang';

    } else {

        box.classList.add('d-none');
    }
});

/*
|--------------------------------------------------------------------------
| VALIDASI REALTIME JAM TERLEWAT
|--------------------------------------------------------------------------
*/

const bookingDateInput = document.querySelector('[name="booking_date"]');
const startTimeInput   = document.querySelector('[name="start_time"]');
const endTimeInput     = document.querySelector('[name="end_time"]');

function validateRealtimeBooking() {

    const bookingDate = bookingDateInput.value;
    const startTime   = startTimeInput.value;
    const endTime     = endTimeInput.value;

    if (!bookingDate || !startTime) {
        return;
    }

    const now = new Date();

    const currentDate =
        now.getFullYear() + '-' +
        String(now.getMonth() + 1).padStart(2, '0') + '-' +
        String(now.getDate()).padStart(2, '0');

    const currentTime =
        String(now.getHours()).padStart(2, '0') + ':' +
        String(now.getMinutes()).padStart(2, '0');

    /*
    |--------------------------------------------------------------------------
    | JAM SUDAH LEWAT
    |--------------------------------------------------------------------------
    */
    if (
        bookingDate === currentDate &&
        startTime <= currentTime
    ) {

        alert(
            'Booking ditolak karena jam mulai sudah terlewat.\n\n' +
            'Jam sekarang: ' + currentTime
        );

        startTimeInput.value = '';
        startTimeInput.focus();

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | JAM SELESAI LEBIH KECIL
    |--------------------------------------------------------------------------
    */
    if (
        startTime &&
        endTime &&
        startTime >= endTime
    ) {

        alert(
            'Jam selesai harus lebih besar dari jam mulai.'
        );

        endTimeInput.value = '';
        endTimeInput.focus();

        return;
    }
}

bookingDateInput.addEventListener('change', validateRealtimeBooking);

startTimeInput.addEventListener('change', validateRealtimeBooking);

endTimeInput.addEventListener('change', validateRealtimeBooking);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>