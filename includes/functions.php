<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function redirect($url) {
    header("Location: $url");
    exit;
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!current_user()) redirect(BASE_URL . '/login.php');
}

function require_admin() {
    require_login();
    if (current_user()['role'] !== 'admin') redirect(BASE_URL . '/index.php');
}

function refresh_user_session() {
    global $pdo;
    if (!isset($_SESSION['user']['id'])) return;
    $stmt = $pdo->prepare("SELECT u.*, d.name AS department_name FROM users u LEFT JOIN departments d ON d.id=u.department_id WHERE u.id=?");
    $stmt->execute([$_SESSION['user']['id']]);
    $_SESSION['user'] = $stmt->fetch();
}

function audit_log($action, $detail = '') {
    global $pdo;
    $uid = $_SESSION['user']['id'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, detail, ip_address, user_agent) VALUES (?,?,?,?,?)");
    $stmt->execute([
        $uid,
        $action,
        $detail,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

function notify_user($user_id, $title, $message, $type='info') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)");
    $stmt->execute([$user_id, $title, $message, $type]);
}

function notify_admins($title, $message, $type='info') {
    global $pdo;
    $ids = $pdo->query("SELECT id FROM users WHERE role='admin' AND status='active'")->fetchAll();
    foreach ($ids as $row) notify_user($row['id'], $title, $message, $type);
}

function send_email_log($to, $subject, $body) {
    global $pdo;
    $headers = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $sent = false;
    try {
        $sent = @mail($to, $subject, $body, $headers);
    } catch (Throwable $e) {
        $sent = false;
    }
    $stmt = $pdo->prepare("INSERT INTO email_logs (recipient, subject, body, status, error_message) VALUES (?,?,?,?,?)");
    $stmt->execute([$to, $subject, $body, $sent ? 'sent' : 'failed', $sent ? null : 'mail() tidak aktif/SMTP belum dikonfigurasi']);
    return $sent;
}

function status_badge($status) {
    $map = [
        'active' => 'success',
        'pending' => 'warning',
        'rejected' => 'danger',
        'approved' => 'success',
        'cancelled' => 'secondary',
        'inactive' => 'secondary'
    ];
    $label = [
        'active'=>'Aktif','pending'=>'Menunggu','rejected'=>'Ditolak','approved'=>'Disetujui',
        'cancelled'=>'Dibatalkan','inactive'=>'Nonaktif'
    ][$status] ?? $status;
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge text-bg-' . $class . '">' . e($label) . '</span>';
}

function conflict_booking($room_id, $date, $start, $end, $exclude_id = null) {
    global $pdo;
    $sql = "SELECT * FROM bookings
            WHERE room_id=? AND booking_date=? AND status IN ('pending','approved')
            AND NOT (? <= start_time OR ? >= end_time)";
    $params = [$room_id, $date, $end, $start];
    if ($exclude_id) {
        $sql .= " AND id<>?";
        $params[] = $exclude_id;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function upload_photo($field, $old = null) {
    if (empty($_FILES[$field]['name'])) return $old;
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'])) return $old;
    $name = 'profile_' . time() . '_' . rand(1000,9999) . '.' . $ext;
    move_uploaded_file($_FILES[$field]['tmp_name'], UPLOAD_DIR . $name);
    return $name;
}
?>
