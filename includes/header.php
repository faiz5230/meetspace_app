<?php require_once __DIR__ . '/functions.php'; require_login(); refresh_user_session(); $me=current_user(); ?>
<!doctype html>
<html lang="id" data-bs-theme="<?= $_COOKIE['theme'] ?? 'light' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom bg-body sticky-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/index.php"><i class="bi bi-building-check text-success"></i> MeetSpace</a>
    <button class="btn btn-outline-secondary btn-sm me-2" id="themeToggle"><i class="bi bi-moon-stars"></i> Dark Mode</button>
    <div class="dropdown ms-auto">
      <button class="btn btn-light border dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
        <?php if (!empty($me['photo'])): ?>
          <img src="<?= UPLOAD_URL . e($me['photo']) ?>" class="avatar-sm">
        <?php else: ?>
          <span class="avatar-sm bg-success text-white d-inline-flex align-items-center justify-content-center"><?= strtoupper(substr($me['name'],0,1)) ?></span>
        <?php endif; ?>
        <span><?= e($me['name']) ?></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="<?= BASE_URL ?>/profile.php">Profil</a></li>
        <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php">Keluar</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="d-flex">
  <aside class="sidebar border-end bg-body">
    <div class="p-3">
      <a class="nav-link mb-2" href="<?= BASE_URL ?>/index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <?php if ($me['role']==='admin'): ?>

	  <a class="nav-link mb-2" href="<?= BASE_URL ?>/admin/users.php">
        <i class="bi bi-people"></i> Pengguna
      </a>

      <a class="nav-link mb-2" href="<?= BASE_URL ?>/admin/departments.php">
        <i class="bi bi-diagram-3"></i> Departemen
      </a>

      <a class="nav-link mb-2" href="<?= BASE_URL ?>/admin/rooms.php">
        <i class="bi bi-door-open"></i> Ruangan
      </a>

      <a class="nav-link mb-2" href="<?= BASE_URL ?>/admin/bookings.php">
        <i class="bi bi-calendar-check"></i> Booking
      </a>
	  
	  <a class="nav-link mb-2" href="<?= BASE_URL ?>/booking_create.php">
		<i class="bi bi-calendar-plus"></i> Booking Room
	  </a>

      <a class="nav-link mb-2" href="<?= BASE_URL ?>/admin/calendar.php">
        <i class="bi bi-calendar3"></i> Kalender
      </a>
	  <a class="nav-link mb-2" href="<?= BASE_URL ?>/admin/dashboard_settings.php">
		<i class="bi bi-display"></i> Setting Dashboard
	  </a>

       <!-- TAMBAHAN MENU LAPORAN -->
      <a class="nav-link mb-2" href="<?= BASE_URL ?>/admin/reports.php">
        <i class="bi bi-bar-chart"></i> Laporan Ruangan
      </a>

      <a class="nav-link mb-2" href="<?= BASE_URL ?>/admin/audit_logs.php">
        <i class="bi bi-clock-history"></i> Audit Log
      </a>

      <?php else: ?>

      <a class="nav-link mb-2" href="<?= BASE_URL ?>/booking_create.php">
        <i class="bi bi-calendar-plus"></i> Ajukan Booking
      </a>

      <a class="nav-link mb-2" href="<?= BASE_URL ?>/my_bookings.php">
        <i class="bi bi-list-check"></i> Booking Saya
      </a>

      <a class="nav-link mb-2" href="<?= BASE_URL ?>/calendar.php">
        <i class="bi bi-calendar3"></i> Jadwal Ruangan
      </a>

      <?php endif; ?>
    </div>
  </aside>
  <main class="content flex-fill p-4">
    <div id="liveToastArea" class="position-fixed top-0 end-0 p-3" style="z-index:1080"></div>
