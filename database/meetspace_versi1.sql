CREATE DATABASE IF NOT EXISTS meetspace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE meetspace;

DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;

CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  code VARCHAR(20) NOT NULL UNIQUE,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  status ENUM('pending','active','rejected') NOT NULL DEFAULT 'pending',
  department_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  capacity INT NOT NULL,
  floor INT NOT NULL,
  facilities TEXT NOT NULL,
  image_url VARCHAR(500) DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  room_id INT NOT NULL,
  booking_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT,
  attendees INT NOT NULL,
  status ENUM('pending','approved','rejected','canceled') NOT NULL DEFAULT 'pending',
  rejection_reason TEXT,
  cancellation_reason TEXT,
  rescheduled_by INT DEFAULT NULL,
  rescheduled_at DATETIME DEFAULT NULL,
  processed_by INT DEFAULT NULL,
  processed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_bookings_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  CONSTRAINT fk_bookings_processor FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_bookings_rescheduler FOREIGN KEY (rescheduled_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_room_date_time (room_id, booking_date, start_time, end_time),
  INDEX idx_status (status)
) ENGINE=InnoDB;

INSERT INTO departments (name,code,status) VALUES
('Human Resource','HRD','active'),
('Marketing','MKT','active'),
('Finance','FIN','active'),
('Information Technology','IT','active'),
('Operations','OPS','active');

INSERT INTO users (name,email,password_hash,role,status,department_id) VALUES
('Administrator','admin@meetspace.id','240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9','admin','active',NULL),
('Rina Wati','rina@company.id','041e4e852c36528e050e1d979f30b59870a2353ae6a06a9606f7b353d8a0e8d5','staff','active',2),
('Ahmad Fauzi','ahmad@company.id','306098fa01257f8e4809cbdfca258d8c22c7fb12937cc2616ef06aa20fd8008e','staff','active',4);

INSERT INTO rooms (name,capacity,floor,facilities,image_url,status) VALUES
('Ruang Serbaguna Alpha',20,1,'Proyektor, Whiteboard, AC, Sound System','https://picsum.photos/seed/rm1/900/500','active'),
('Ruang Meeting Beta',10,2,'TV LED, Whiteboard, AC','https://picsum.photos/seed/rm2/900/500','active'),
('Ruang Konferensi Gamma',50,3,'Proyektor, Microphone, AC, Sound System, Recording','https://picsum.photos/seed/rm3/900/500','active'),
('Ruang Brainstorming Delta',8,1,'Whiteboard, AC, Sticky Notes','https://picsum.photos/seed/rm4/900/500','active'),
('Ruang Boardroom Epsilon',15,5,'TV LED 75 inch, Proyektor, AC, Sound System, Video Conference','https://picsum.photos/seed/rm5/900/500','active'),
('Ruang Training Zeta',30,4,'Proyektor, Microphone, AC, Sound System, Whiteboard','https://picsum.photos/seed/rm6/900/500','active');
