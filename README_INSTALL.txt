MEETSPACE AUTH FINAL INTEGRATED PATCH
====================================

File yang disertakan:
- login.php
- register.php
- verify_email.php
- resend_verification.php
- forgot_password.php
- reset_password.php
- admin/users.php
- config/mail_config.php
- includes/mail_helper.php
- sql/auth_final_upgrade.sql

Fitur:
- Register mandiri
- Verifikasi email
- Resend verification
- Approval admin
- Forgot/reset password via email
- OTP login email optional
- Block domain luar kantor: hanya @bprhaldenprime.com
- SMTP Hostinger faiz@bprhaldenprime.com
- Email queue/log
- Audit approval user
- Captcha matematika sederhana
- Placeholder Google/Microsoft login

Cara install:
1. Backup folder C:\xampp\htdocs\meetspace_app
2. Extract ZIP ini
3. Copy semua file/folder ke C:\xampp\htdocs\meetspace_app
4. Replace file yang sama
5. Import sql/auth_final_upgrade.sql ke database meetspace_db
6. Edit config/mail_config.php
7. Ganti GANTI_PASSWORD_EMAIL_HOSTINGER dengan password email Hostinger
8. Buka http://localhost/meetspace_app/register.php

Catatan SMTP:
Hostinger SMTP: smtp.hostinger.com
Port SSL: 465
Email: faiz@bprhaldenprime.com

Jika PHPMailer belum ada, email tetap masuk ke tabel email_queue.
Untuk SMTP real disarankan install PHPMailer:
composer require phpmailer/phpmailer
