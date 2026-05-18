<?php

if (!defined('MAIL_HOST')) {
    define('MAIL_HOST', 'smtp.hostinger.com');
}

if (!defined('MAIL_PORT')) {
    define('MAIL_PORT', 465);
}

if (!defined('MAIL_SECURE')) {
    define('MAIL_SECURE', 'ssl');
}

if (!defined('MAIL_USERNAME')) {
    define('MAIL_USERNAME', 'faiz@bprhaldenprime.com');
}

if (!defined('MAIL_PASSWORD')) {
    define('MAIL_PASSWORD', 'Faiz@5230');
}

if (!defined('MAIL_FROM')) {
    define('MAIL_FROM', 'faiz@bprhaldenprime.com');
}

if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', 'MeetSpace System');
}

if (!defined('COMPANY_DOMAIN')) {
    define('COMPANY_DOMAIN', '');
}

if (!defined('BLOCK_EXTERNAL_EMAIL')) {
    define('BLOCK_EXTERNAL_EMAIL', false);
}

if (!defined('AUTO_APPROVE_COMPANY_DOMAIN')) {
    define('AUTO_APPROVE_COMPANY_DOMAIN', false);
}

if (!defined('ENABLE_LOGIN_OTP')) {
    define('ENABLE_LOGIN_OTP', false);
}

if (!defined('ENABLE_SIMPLE_CAPTCHA')) {
    define('ENABLE_SIMPLE_CAPTCHA', true);
}