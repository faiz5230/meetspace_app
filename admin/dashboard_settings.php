<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();

$uploadDir = __DIR__ . '/../uploads/settings/';
$uploadUrl = BASE_URL . '/uploads/settings/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

function get_setting($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key=?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

function save_setting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO app_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)
    ");
    $stmt->execute([$key, $value]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $meetingStartSound = isset($_POST['meeting_start_sound']) ? '1' : '0';
    $meetingEndSound   = isset($_POST['meeting_end_sound']) ? '1' : '0';

    save_setting('meeting_start_sound', $meetingStartSound);
    save_setting('meeting_end_sound', $meetingEndSound);
    save_setting('app_name', $_POST['app_name']);

    if (!empty($_FILES['public_logo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['public_logo']['name'], PATHINFO_EXTENSION));
        $file = 'logo_' . time() . '.' . $ext;

        move_uploaded_file(
            $_FILES['public_logo']['tmp_name'],
            $uploadDir . $file
        );

        save_setting('public_logo', $file);
    }

    if (!empty($_FILES['public_background']['name'])) {
        $ext = strtolower(pathinfo($_FILES['public_background']['name'], PATHINFO_EXTENSION));
        $file = 'background_' . time() . '.' . $ext;

        move_uploaded_file(
            $_FILES['public_background']['tmp_name'],
            $uploadDir . $file
        );

        save_setting('public_background', $file);
    }

    if (!empty($_FILES['book_now_image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['book_now_image']['name'], PATHINFO_EXTENSION));
        $file = 'book_now_' . time() . '.' . $ext;

        move_uploaded_file(
            $_FILES['book_now_image']['tmp_name'],
            $uploadDir . $file
        );

        save_setting('book_now_image', $file);
    }

    if (!empty($_FILES['meeting_sound_file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['meeting_sound_file']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, ['mp3', 'wav', 'ogg'])) {
            $file = 'meeting_sound_' . time() . '.' . $ext;

            move_uploaded_file(
                $_FILES['meeting_sound_file']['tmp_name'],
                $uploadDir . $file
            );

            save_setting('meeting_sound_file', $file);
        }
    }
	if (!empty($_FILES['meeting_icon']['name'])) {
		$ext = strtolower(pathinfo($_FILES['meeting_icon']['name'], PATHINFO_EXTENSION));

    if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) {
        $file = 'meeting_icon_' . time() . '.' . $ext;

        move_uploaded_file(
            $_FILES['meeting_icon']['tmp_name'],
            $uploadDir . $file
        );

        save_setting('meeting_icon', $file);
		}
	}

    audit_log('UPDATE_DASHBOARD_SETTING', 'Update setting dashboard public');

    redirect('dashboard_settings.php');
}

$appName = get_setting('app_name', 'MeetSpace');
$logo = get_setting('public_logo');
$background = get_setting('public_background');
$bookNowImage = get_setting('book_now_image');
$meetingSoundFile = get_setting('meeting_sound_file');

$rooms = $pdo->query("SELECT * FROM rooms ORDER BY name")->fetchAll();
?>

<h3 class="fw-bold mb-3">Setting Dashboard Public</h3>

<div class="card card-soft mb-4">
    <div class="card-body">

        <form method="post" enctype="multipart/form-data" class="row g-3">

            <div class="col-md-6">
                <label class="form-label">Nama Aplikasi</label>
                <input class="form-control" name="app_name" value="<?= e($appName) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Logo</label>
                <input class="form-control" type="file" name="public_logo" accept="image/*">

                <?php if ($logo): ?>
                    <div class="mt-2">
                        <img src="<?= $uploadUrl . e($logo) ?>" style="max-height:70px">
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-12">
                <label class="form-label">Background Dashboard</label>
                <input class="form-control" type="file" name="public_background" accept="image/*">

                <?php if ($background): ?>
                    <div class="mt-2">
                        <img src="<?= $uploadUrl . e($background) ?>" style="max-height:140px;border-radius:12px">
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-12">
                <div class="card mt-2">
                    <div class="card-body">

                        <h5 class="mb-3">
                            Pengaturan Suara Meeting
                        </h5>

                        <div class="form-check form-switch mb-3">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="meeting_start_sound"
                                value="1"
                                <?= get_setting('meeting_start_sound', '1') ? 'checked' : '' ?>>

                            <label class="form-check-label">
                                Bunyi saat meeting dimulai
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="meeting_end_sound"
                                value="1"
                                <?= get_setting('meeting_end_sound', '1') ? 'checked' : '' ?>>

                            <label class="form-check-label">
                                Bunyi saat meeting selesai
                            </label>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">
                                Upload Suara Meeting
                            </label>

                            <input
                                class="form-control"
                                type="file"
                                name="meeting_sound_file"
                                accept="audio/mpeg,audio/wav,audio/ogg">

                            <?php if ($meetingSoundFile): ?>
                                <div class="mt-2">
                                    <audio controls>
                                        <source src="<?= $uploadUrl . e($meetingSoundFile) ?>">
                                        Browser tidak mendukung audio.
                                    </audio>
                                </div>
                            <?php endif; ?>
                        </div>
						<div class="mt-3">
							<label class="form-label">
							Upload Icon In Meeting
							</label>

							<input
							class="form-control"
							type="file"
							name="meeting_icon"
							accept="image/*">

							<?php
							$meetingIcon = get_setting('meeting_icon');
							?>

							<?php if ($meetingIcon): ?>
						<div class="mt-2">
							<img
							src="<?= $uploadUrl . e($meetingIcon) ?>"
							style="max-height:50px;border-radius:8px">
						</div>
						<?php endif; ?>
					</div>

                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <label class="form-label">Gambar / Logo Book Now</label>
                <input class="form-control" type="file" name="book_now_image" accept="image/*">

                <?php if ($bookNowImage): ?>
                    <div class="mt-2">
                        <img src="<?= $uploadUrl . e($bookNowImage) ?>" style="max-height:100px;border-radius:12px">
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-12">
                <button class="btn btn-success">Simpan Setting</button>
            </div>

        </form>

    </div>
</div>

<div class="card card-soft">
    <div class="card-body">

        <h5 class="fw-bold mb-3">Link Dashboard Per Ruangan</h5>

        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Ruangan</th>
                    <th>Status</th>
                    <th>Link Dashboard</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($rooms as $r): ?>
                    <?php $link = BASE_URL . '/public_dashboard.php?room_id=' . $r['id']; ?>
                    <tr>
                        <td class="fw-semibold"><?= e($r['name']) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td>
                            <input class="form-control" value="<?= e($link) ?>" readonly>
                        </td>
                        <td>
                            <a href="<?= e($link) ?>" target="_blank" class="btn btn-sm btn-success">
                                Buka
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
