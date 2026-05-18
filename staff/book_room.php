<?php
require_once __DIR__ . '/../includes/functions.php';
require_staff();
$pageTitle = 'Pesan Ruangan';
$active = 'book';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = current_user()['id'];
    $rid = (int)$_POST['room_id'];
    $date = $_POST['booking_date'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $title = trim($_POST['title']);
    $desc = trim($_POST['description'] ?? '');
    $att = (int)$_POST['attendees'];

    $roomSt = db()->prepare('SELECT * FROM rooms WHERE id=? AND status="active"');
    $roomSt->execute([$rid]);
    $room = $roomSt->fetch();

    if (!$room) {
        flash('error', 'Ruangan tidak valid.');
    } elseif ($att > $room['capacity']) {
        flash('error', 'Jumlah peserta melebihi kapasitas ruangan.');
    } elseif ($date < date('Y-m-d')) {
        flash('error', 'Tanggal tidak boleh di masa lalu.');
    } elseif ($start >= $end) {
        flash('error', 'Jam selesai harus setelah jam mulai.');
    } elseif (check_booking_conflict($rid, $date, $start, $end)) {
        flash('error', 'Jadwal bentrok dengan booking lain.');
    } else {
        $st = db()->prepare('INSERT INTO bookings(user_id,room_id,booking_date,start_time,end_time,title,description,attendees,status) VALUES(?,?,?,?,?,?,?,?,"pending")');
        $st->execute([$uid, $rid, $date, $start, $end, $title, $desc, $att]);
        flash('success', 'Pemesanan berhasil diajukan dan menunggu persetujuan admin.');
        redirect('/meetspace_app/staff/my_bookings.php');
    }
}

$rooms = db()->query("SELECT * FROM rooms WHERE status='active' ORDER BY name")->fetchAll();
$roomJson = [];
foreach ($rooms as $r) {
    $roomJson[$r['id']] = [
        'name' => $r['name'],
        'capacity' => (int)$r['capacity'],
        'floor' => (int)$r['floor'],
        'facilities' => array_values(array_filter(array_map('trim', explode(',', $r['facilities'])))),
        'image_url' => $r['image_url'] ?: 'https://picsum.photos/seed/room/900/500',
    ];
}
include __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:820px">
    <h3>Formulir Pemesanan Ruangan</h3>
    <p class="muted small" style="margin-top:-6px;margin-bottom:18px">Pilih ruangan untuk melihat kapasitas, lantai, dan semua fasilitas yang tersedia.</p>

    <form method="post">
        <div class="form-group">
            <label>Ruangan</label>
            <select class="select" name="room_id" id="room_id" required onchange="showRoomFacilities()">
                <option value="">Pilih ruangan...</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?> - Kapasitas <?= (int)$r['capacity'] ?>, Lantai <?= (int)$r['floor'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="room-facility-box" class="card" style="display:none;background:#f8fafc;border-style:dashed;margin-bottom:18px;padding:18px"></div>

        <div class="form-group">
            <label>Tanggal</label>
            <input class="input" type="date" name="booking_date" min="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="grid grid-2">
            <div class="form-group">
                <label>Jam Mulai</label>
                <input class="input" type="time" name="start_time" value="09:00" required>
            </div>
            <div class="form-group">
                <label>Jam Selesai</label>
                <input class="input" type="time" name="end_time" value="11:00" required>
            </div>
        </div>
        <div class="form-group">
            <label>Judul Meeting</label>
            <input class="input" name="title" required>
        </div>
        <div class="form-group">
            <label>Deskripsi</label>
            <textarea class="textarea" name="description"></textarea>
        </div>
        <div class="form-group">
            <label>Jumlah Peserta</label>
            <input class="input" type="number" min="1" name="attendees" id="attendees" required>
            <div id="capacity-help" class="muted small" style="margin-top:6px"></div>
        </div>
        <button class="btn btn-primary">Ajukan Pemesanan</button>
    </form>
</div>

<script>
const rooms = <?= json_encode($roomJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function escapeHtml(value) {
    return String(value).replace(/[&<>'"]/g, function (char) {
        return ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'})[char];
    });
}

function showRoomFacilities() {
    const select = document.getElementById('room_id');
    const box = document.getElementById('room-facility-box');
    const help = document.getElementById('capacity-help');
    const attendees = document.getElementById('attendees');
    const room = rooms[select.value];

    if (!room) {
        box.style.display = 'none';
        box.innerHTML = '';
        help.textContent = '';
        attendees.removeAttribute('max');
        return;
    }

    attendees.max = room.capacity;
    help.textContent = 'Kapasitas maksimal ruangan ini: ' + room.capacity + ' orang.';

    const facilities = room.facilities.length
        ? room.facilities.map(f => `<span class="chip">${escapeHtml(f)}</span>`).join('')
        : '<span class="muted small">Belum ada fasilitas yang dicatat.</span>';

    box.innerHTML = `
        <div class="section-head" style="margin-bottom:12px">
            <div>
                <h3 style="margin:0 0 4px">${escapeHtml(room.name)}</h3>
                <div class="muted small">Kapasitas ${room.capacity} orang · Lantai ${room.floor}</div>
            </div>
            <span class="badge success">Tersedia</span>
        </div>
        <div class="small" style="font-weight:800;margin-bottom:8px;color:#334155">Fasilitas Ruangan</div>
        <div class="chips">${facilities}</div>
    `;
    box.style.display = 'block';
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
