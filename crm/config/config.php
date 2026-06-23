<?php
// ============================================================
// HK Builders CRM â€” Configuration
// LOCAL:      http://localhost/CRM/public  (development)
// PRODUCTION: https://hkbuildersanddevelopers.com/crm (production)
// ============================================================

// ---- Environment -------------------------------------------
// Change to 'production' before uploading to Hostinger
define('APP_ENV', 'production');

// ---- Database -----------------------------------------------
// PRODUCTION: replace with your Hostinger MySQL credentials
if (APP_ENV === 'production') {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'u813506845_u123456_crm');
    define('DB_USER', 'u813506845_user');
    define('DB_PASS', 'Hunain@1212');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'crm_system');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}
define('DB_PORT',    3306);
define('DB_CHARSET', 'utf8mb4');

// ---- Application URL ----------------------------------------
define('APP_NAME', 'HK Builders CRM');
define('APP_URL',  APP_ENV === 'production'
    ? 'https://hkbuildersanddevelopers.com/crm'
    : 'http://localhost/CRM/public'
);

// ---- Security -----------------------------------------------
define('SESSION_NAME',       'hkb_crm_session');
define('SESSION_LIFETIME',   7200);
define('CSRF_TOKEN_LENGTH',  32);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES',    15);
define('HTTPS_ENABLED',      APP_ENV === 'production');

// ---- File Uploads -------------------------------------------
// Auto-detects whether public/ subfolder exists (local) or merged (production)
define('CSV_UPLOAD_DIR', is_dir(__DIR__ . '/../public')
    ? __DIR__ . '/../public/uploads/csv/'   // local XAMPP
    : __DIR__ . '/../uploads/csv/'          // production (flat structure)
);
define('CSV_MAX_SIZE_MB', 5);

// ---- Timezone -----------------------------------------------
date_default_timezone_set('Asia/Karachi');

// ---- Error Display ------------------------------------------
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
}
