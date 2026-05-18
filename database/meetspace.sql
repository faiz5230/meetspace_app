CREATE DATABASE IF NOT EXISTS meetspace_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE meetspace_db;

DROP TABLE IF EXISTS email_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;

CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description TEXT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department_id INT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','staff') DEFAULT 'staff',
  status ENUM('active','pending','rejected') DEFAULT 'pending',
  photo VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

CREATE TABLE rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  capacity INT NOT NULL DEFAULT 1,
  floor VARCHAR(50) NULL,
  facilities TEXT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  image VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  room_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  booking_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  attendees INT NOT NULL DEFAULT 1,
  status ENUM('pending','approved','rejected','cancelled') DEFAULT 'pending',
  rejection_reason TEXT NULL,
  cancel_reason TEXT NULL,
  approved_by INT NULL,
  approved_at DATETIME NULL,
  cancelled_by INT NULL,
  cancelled_at DATETIME NULL,
  rescheduled_by INT NULL,
  rescheduled_at DATETIME NULL,
  reschedule_note TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (rescheduled_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(120) NOT NULL,
  detail TEXT NULL,
  ip_address VARCHAR(60) NULL,
  user_agent TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  type ENUM('info','success','warning','danger') DEFAULT 'info',
  is_read TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE email_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  recipient VARCHAR(150) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  status ENUM('queued','sent','failed') DEFAULT 'queued',
  error_message TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO departments (name, description, status) VALUES
('Management', 'Manajemen dan direksi', 'active'),
('Finance', 'Keuangan dan akuntansi', 'active'),
('Human Resource', 'HR dan GA', 'active'),
('IT', 'Information Technology', 'active'),
('Marketing', 'Marketing dan komunikasi', 'active');

INSERT INTO users (department_id, name, email, password, role, status, photo, created_at) VALUES
(1, 'Administrator', 'admin@meetspace.id', '$2y$10$8eNfM6G0Zg31Yz8eCaIbn.YeSlvCYLxjeWM2TQW3eGPJjQK/4BrB6', 'admin', 'active', NULL, NOW()),
(5, 'Rina Wati', 'rina@company.id', '$2y$10$K6It3VkGk0qSGBFEoQKJiO4NgkMLUcZPy5.TpMzLJv6eCPVp1LDom', 'staff', 'active', NULL, NOW()),
(4, 'Ahmad Fauzi', 'ahmad@company.id', '$2y$10$cJ36a8dFauFAuorN3YkAoe39zR1ZzQBpZ3UtzNtXLR9BYj5Y00f06', 'staff', 'active', NULL, NOW());

INSERT INTO rooms (name, capacity, floor, facilities, status, image) VALUES
('Ruang Serbaguna Alpha', 20, '1', 'Proyektor, Whiteboard, AC, Sound System', 'active', NULL),
('Ruang Meeting Beta', 10, '2', 'TV LED, Whiteboard, AC', 'active', NULL),
('Ruang Konferensi Gamma', 50, '3', 'Proyektor, Microphone, AC, Sound System, Recording', 'active', NULL),
('Ruang Brainstorming Delta', 8, '1', 'Whiteboard, AC, Sticky Notes', 'active', NULL),
('Ruang Boardroom Epsilon', 15, '5', 'TV LED 75 inch, Proyektor, AC, Sound System, Video Conference', 'active', NULL),
('Ruang Training Zeta', 30, '4', 'Proyektor, Microphone, AC, Sound System, Whiteboard', 'active', NULL);

INSERT INTO bookings (user_id, room_id, title, description, booking_date, start_time, end_time, attendees, status, approved_by, approved_at)
VALUES
(2, 5, 'Rekonsiliasi R/L', 'Meeting bulanan finance dan management', CURDATE(), '09:00:00', '11:00:00', 8, 'approved', 1, NOW()),
(3, 3, 'Presentasi Proyek Baru', 'Presentasi internal proyek baru', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00', '15:00:00', 20, 'pending', NULL, NULL);
