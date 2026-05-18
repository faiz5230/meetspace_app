ALTER TABLE users
ADD email_verified TINYINT(1) DEFAULT 0,
ADD email_verify_token VARCHAR(255) NULL,
ADD reset_token VARCHAR(255) NULL,
ADD otp_code VARCHAR(10) NULL;

CREATE TABLE IF NOT EXISTS email_queue (
id INT AUTO_INCREMENT PRIMARY KEY,
recipient VARCHAR(255),
subject VARCHAR(255),
body LONGTEXT,
status VARCHAR(50) DEFAULT 'pending'
);
