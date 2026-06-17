<?php
date_default_timezone_set('Asia/Jakarta');

define('APP_NAME', 'MeetSpace');
define('BASE_URL', 'http://192.168.0.28/meetspace_app');

define('DB_HOST', 'localhost');
define('DB_NAME', 'meetspace_db');
define('DB_USER', 'root');
define('DB_PASS', '');

define('UPLOAD_DIR', __DIR__ . '/../uploads/profile/');
define('UPLOAD_URL', BASE_URL . '/uploads/profile/');

define('MAIL_FROM', 'noreply@meetspace.local');
define('MAIL_FROM_NAME', 'MeetSpace Notification');
?>
