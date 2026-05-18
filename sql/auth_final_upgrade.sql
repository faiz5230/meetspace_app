ALTER TABLE users
ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS email_verify_token VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS email_verified_at DATETIME NULL,
ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS reset_expired_at DATETIME NULL,
ADD COLUMN IF NOT EXISTS otp_code VARCHAR(10) NULL,
ADD COLUMN IF NOT EXISTS otp_expired_at DATETIME NULL,
ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL;

CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body LONGTEXT NOT NULL,
    status ENUM('pending','sent','failed') DEFAULT 'pending',
    error_message TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS user_approval_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    admin_id INT NULL,
    action ENUM('approved','rejected') NOT NULL,
    note TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

UPDATE users SET email_verified = 1, email_verified_at = NOW()
WHERE role = 'admin' AND email_verified = 0;
