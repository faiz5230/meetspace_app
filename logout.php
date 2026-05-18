<?php
require_once __DIR__ . '/includes/functions.php';
audit_log('LOGOUT', 'User logout');
session_destroy();
redirect(BASE_URL . '/login.php');
?>
