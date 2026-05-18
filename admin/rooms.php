<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 0);
        $floor = trim($_POST['floor'] ?? '');
        $facilities = trim($_POST['facilities'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if ($name === '' || $capacity < 1) {
            $_SESSION['flash_error'] = 'Nama ruangan dan kapasitas wajib diisi.';
            redirect('rooms.php');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE rooms
                SET name = ?, capacity = ?, floor = ?, facilities = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $capacity, $floor, $facilities, $status, $id]);

            audit_log('UPDATE_ROOM', 'Edit ruangan ID ' . $id . ' - ' . $name);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO rooms (name, capacity, floor, facilities, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $capacity, $floor, $facilities, $status]);

            audit_log('CREATE_ROOM', 'Tambah ruangan ' . $name);
        }

        redirect('rooms.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM bookings WHERE room_id = ?");
        $stmt->execute([$id]);
        $totalBooking = (int)$stmt->fetch()['total'];

        if ($totalBooking > 0) {
            $_SESSION['flash_error'] = 'Ruangan tidak bisa dihapus karena sudah memiliki data booking. Nonaktifkan saja ruangan tersebut.';
            redirect('rooms.php');
        }

        $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$id]);
        audit_log('DELETE_ROOM', 'Hapus ruangan ID ' . $id);

        redirect('rooms.php');
    }
}

$rooms = $pdo->query("SELECT * FROM rooms ORDER BY name ASC")->fetchAll();
?>
<script>const BASE_URL_JS = "<?= BASE_URL ?>";</script>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="fw-bold mb-0">Manajemen Ruangan</h3>
        <div class="text-muted">Admin dapat tambah, edit, nonaktifkan, dan mengubah fasilitas ruangan.</div>
    </div>

    <button class="btn btn-success"
            data-bs-toggle="modal"
            data-bs-target="#roomModal"
            onclick="openRoomModal()">
        <i class="bi bi-plus"></i> Tambah Ruangan
    </button>
</div>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger">
        <?= e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
    </div>
<?php endif; ?>

<div class="card card-soft">
    <div class="card-body table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Nama Ruangan</th>
                    <th>Kapasitas</th>
                    <th>Lantai</th>
                    <th>Fasilitas</th>
                    <th>Status</th>
                    <th width="160">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $r): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($r['name']); ?></td>
                        <td><?= e($r['capacity']); ?> orang</td>
                        <td><?= e($r['floor']); ?></td>
                        <td>
                            <?php
                            $facilities = array_filter(array_map('trim', explode(',', $r['facilities'] ?? '')));
                            if ($facilities):
                                foreach ($facilities as $f):
                            ?>
                                <span class="badge rounded-pill text-bg-light border me-1 mb-1"><?= e($f); ?></span>
                            <?php
                                endforeach;
                            else:
                            ?>
                                <span class="text-muted">Belum ada fasilitas</span>
                            <?php endif; ?>
                        </td>
                        <td><?= status_badge($r['status']); ?></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#roomModal"
                                    onclick='openRoomModal(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                <i class="bi bi-pencil"></i> Edit
                            </button>

                            <form method="post" class="d-inline"
                                  onsubmit="return confirm('Hapus ruangan ini? Ruangan yang sudah memiliki booking tidak bisa dihapus.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id']; ?>">
                                <button class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
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

<div class="modal fade" id="roomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="post">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="room_id">

            <div class="modal-header">
                <h5 class="modal-title" id="roomModalTitle">Tambah Ruangan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body row g-3">
                <div class="col-md-8">
                    <label class="form-label">Nama Ruangan</label>
                    <input type="text" class="form-control" name="name" id="room_name" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Kapasitas</label>
                    <input type="number" class="form-control" name="capacity" id="room_capacity" min="1" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Lantai</label>
                    <input type="text" class="form-control" name="floor" id="room_floor" placeholder="Contoh: 1 / 2 / Ground">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Status</label>
						<select class="form-select" name="status" id="room_status">

						<option value="active">
						Aktif
						</option>

						<option value="closed">
						Close Sementara
						</option>

						<option value="inactive">
						Nonaktif
						</option>

						</select>
                </div>

                <div class="col-12">
                    <label class="form-label">Fasilitas Ruangan</label>
                    <textarea class="form-control"
                              name="facilities"
                              id="room_facilities"
                              rows="4"
                              placeholder="Contoh: Proyektor, Whiteboard, AC, Sound System, Video Conference"></textarea>
                    <div class="form-text">
                        Pisahkan setiap fasilitas dengan koma. Contoh: Proyektor, AC, Whiteboard.
                    </div>
                </div>

                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        <strong>Preview fasilitas:</strong>
                        <div id="facilityPreview" class="mt-2 text-muted">Belum ada fasilitas.</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-success">Simpan Ruangan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRoomModal(room = null) {
    document.getElementById('roomModalTitle').innerText = room ? 'Edit Ruangan' : 'Tambah Ruangan';

    document.getElementById('room_id').value = room ? room.id : '';
    document.getElementById('room_name').value = room ? room.name : '';
    document.getElementById('room_capacity').value = room ? room.capacity : '';
    document.getElementById('room_floor').value = room ? room.floor : '';
    document.getElementById('room_facilities').value = room ? (room.facilities || '') : '';
    document.getElementById('room_status').value = room ? room.status : 'active';

    renderFacilityPreview();
}

function renderFacilityPreview() {
    const value = document.getElementById('room_facilities').value || '';
    const items = value.split(',').map(v => v.trim()).filter(Boolean);
    const preview = document.getElementById('facilityPreview');

    if (!items.length) {
        preview.innerHTML = '<span class="text-muted">Belum ada fasilitas.</span>';
        return;
    }

    preview.innerHTML = items.map(item => {
        return `<span class="badge rounded-pill text-bg-success me-1 mb-1">${escapeHtml(item)}</span>`;
    }).join('');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function () {
    const facilitiesInput = document.getElementById('room_facilities');
    if (facilitiesInput) {
        facilitiesInput.addEventListener('input', renderFacilityPreview);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
