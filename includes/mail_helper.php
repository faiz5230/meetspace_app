<?php

require_once __DIR__ . '/../config/mail_config.php';

function queue_email($recipient, $subject, $body, $status = 'pending', $error = null)
{
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO email_queue (
            recipient,
            subject,
            body,
            status,
            error_message
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $recipient,
        $subject,
        $body,
        $status,
        $error
    ]);

    return $pdo->lastInsertId();
}

function send_smtp_email($recipient, $subject, $body)
{
    global $pdo;

    $queueId = queue_email(
        $recipient,
        $subject,
        $body,
        'pending'
    );

    $autoload = __DIR__ . '/../vendor/autoload.php';

    /*
    |--------------------------------------------------------------------------
    | PHPMailer
    |--------------------------------------------------------------------------
    */
    if (file_exists($autoload)) {

        require_once $autoload;

        if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {

            try {

                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

                /*
                |--------------------------------------------------------------------------
                | SMTP CONFIG
                |--------------------------------------------------------------------------
                */
                $mail->isSMTP();

                $mail->Host       = MAIL_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = MAIL_USERNAME;
                $mail->Password   = MAIL_PASSWORD;
                $mail->SMTPSecure = MAIL_SECURE;
                $mail->Port       = MAIL_PORT;

                /*
                |--------------------------------------------------------------------------
                | FIX WINDOWS/XAMPP SSL
                |--------------------------------------------------------------------------
                */
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true
                    ]
                ];

                $mail->SMTPDebug = 0;

                $mail->CharSet = 'UTF-8';

                $mail->Timeout = 30;

                /*
                |--------------------------------------------------------------------------
                | EMAIL HEADER
                |--------------------------------------------------------------------------
                */
                $mail->setFrom(
                    MAIL_USERNAME,
                    MAIL_FROM_NAME
                );

                $mail->addReplyTo(
                    MAIL_USERNAME,
                    MAIL_FROM_NAME
                );

                $mail->addAddress(
                    trim($recipient)
                );

                /*
                |--------------------------------------------------------------------------
                | EMAIL CONTENT
                |--------------------------------------------------------------------------
                */
                $mail->isHTML(true);

                $mail->Subject = $subject;

                $mail->Body = $body;

                /*
                |--------------------------------------------------------------------------
                | SEND
                |--------------------------------------------------------------------------
                */
                $mail->send();

                $stmt = $pdo->prepare("
                    UPDATE email_queue
                    SET
                        status='sent',
                        sent_at=NOW()
                    WHERE id=?
                ");

                $stmt->execute([$queueId]);

                return true;

            } catch (Throwable $e) {

                $stmt = $pdo->prepare("
                    UPDATE email_queue
                    SET
                        status='failed',
                        error_message=?
                    WHERE id=?
                ");

                $stmt->execute([
                    $e->getMessage(),
                    $queueId
                ]);

                return false;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FALLBACK mail()
    |--------------------------------------------------------------------------
    */
    $headers  = "From: " . MAIL_FROM_NAME . " <" . MAIL_USERNAME . ">\r\n";
    $headers .= "Reply-To: " . MAIL_USERNAME . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $sent = @mail(
        $recipient,
        $subject,
        $body,
        $headers
    );

    if ($sent) {

        $stmt = $pdo->prepare("
            UPDATE email_queue
            SET
                status='sent',
                sent_at=NOW()
            WHERE id=?
        ");

        $stmt->execute([$queueId]);

        return true;
    }

    $stmt = $pdo->prepare("
        UPDATE email_queue
        SET
            status='failed',
            error_message=?
        WHERE id=?
    ");

    $stmt->execute([
        'PHPMailer gagal dan mail() gagal.',
        $queueId
    ]);

    return false;
}

/*
|--------------------------------------------------------------------------
| TEMPLATE EMAIL
|--------------------------------------------------------------------------
*/
function email_template(
    $title,
    $message,
    $buttonText = null,
    $buttonUrl = null
) {

    $button = '';

    if ($buttonText && $buttonUrl) {

        $button = '
            <p style="margin:28px 0;">
                <a href="' . htmlspecialchars($buttonUrl) . '"
                   style="
                        background:#198754;
                        color:#ffffff;
                        text-decoration:none;
                        padding:13px 22px;
                        border-radius:10px;
                        font-weight:bold;
                        display:inline-block;
                   ">
                    ' . htmlspecialchars($buttonText) . '
                </a>
            </p>
        ';
    }

    return '
    <!doctype html>
    <html>
    <body style="
        margin:0;
        background:#f1f5f9;
        font-family:Arial,sans-serif;
        color:#1f2937;
    ">

        <div style="
            max-width:640px;
            margin:0 auto;
            padding:32px;
        ">

            <div style="
                background:#ffffff;
                border-radius:18px;
                padding:32px;
                border:1px solid #e5e7eb;
            ">

                <div style="
                    font-size:24px;
                    font-weight:800;
                    color:#198754;
                    margin-bottom:8px;
                ">
                    MeetSpace
                </div>

                <h2 style="
                    margin:0 0 16px 0;
                    color:#111827;
                ">
                    ' . htmlspecialchars($title) . '
                </h2>

                <div style="
                    font-size:15px;
                    line-height:1.7;
                    color:#374151;
                ">
                    ' . $message . '
                </div>

                ' . $button . '

                <hr style="
                    border:none;
                    border-top:1px solid #e5e7eb;
                    margin:28px 0;
                ">

                <p style="
                    font-size:12px;
                    color:#6b7280;
                ">
                    Email otomatis dari sistem MeetSpace.
                    Abaikan email ini jika bukan Anda.
                </p>

            </div>

        </div>

    </body>
    </html>';
}

/*
|--------------------------------------------------------------------------
| VERIFICATION EMAIL
|--------------------------------------------------------------------------
*/
function send_verification_email(
    $userId,
    $email,
    $name,
    $token
) {

    $link = BASE_URL .
        '/verify_email.php?token=' .
        urlencode($token);

    $body = email_template(
        'Verifikasi Email Anda',
        '
        <p>
            Halo <strong>' . htmlspecialchars($name) . '</strong>,
        </p>

        <p>
            Klik tombol berikut untuk verifikasi email Anda.
        </p>

        <p>
            Setelah verifikasi email,
            akun tetap menunggu approval admin.
        </p>
        ',
        'Verifikasi Email',
        $link
    );

    return send_smtp_email(
        $email,
        'Verifikasi Email MeetSpace',
        $body
    );
}

/*
|--------------------------------------------------------------------------
| FORGOT PASSWORD EMAIL
|--------------------------------------------------------------------------
*/
function send_forgot_password_email(
    $email,
    $name,
    $token
) {

    $link = BASE_URL .
        '/reset_password.php?token=' .
        urlencode($token);

    $body = email_template(
        'Reset Password MeetSpace',
        '
        <p>
            Halo <strong>' . htmlspecialchars($name) . '</strong>,
        </p>

        <p>
            Link reset password berlaku selama 1 jam.
        </p>
        ',
        'Reset Password',
        $link
    );

    return send_smtp_email(
        $email,
        'Reset Password MeetSpace',
        $body
    );
}

/*
|--------------------------------------------------------------------------
| OTP EMAIL
|--------------------------------------------------------------------------
*/
function send_otp_email(
    $email,
    $name,
    $otp
) {

    $body = email_template(
        'Kode OTP Login',
        '
        <p>
            Halo <strong>' . htmlspecialchars($name) . '</strong>,
        </p>

        <p>
            Kode OTP login Anda:
        </p>

        <div style="
            font-size:32px;
            font-weight:800;
            letter-spacing:6px;
            color:#198754;
            margin:18px 0;
        ">
            ' . htmlspecialchars($otp) . '
        </div>

        <p>
            Kode berlaku selama 5 menit.
        </p>
        '
    );

    return send_smtp_email(
        $email,
        'Kode OTP Login MeetSpace',
        $body
    );
}

/*
|--------------------------------------------------------------------------
| ACTIVATION EMAIL
|--------------------------------------------------------------------------
*/
function send_activation_email(
    $email,
    $name
) {

    $body = email_template(
        'Akun MeetSpace Anda Aktif',
        '
        <p>
            Halo <strong>' . htmlspecialchars($name) . '</strong>,
        </p>

        <p>
            Akun Anda sudah disetujui admin
            dan sekarang bisa login.
        </p>
        ',
        'Login MeetSpace',
        BASE_URL . '/login.php'
    );

    return send_smtp_email(
        $email,
        'Akun MeetSpace Anda Aktif',
        $body
    );
}
?>