<?php
/**
 * Global configuration for TutorHub.
 * Edit DB credentials below to match your XAMPP / MySQL setup.
 */

// ---- Database credentials ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'tutorhub');
define('DB_USER', 'root');
define('DB_PASS', '');       // default XAMPP password is empty

define('APP_NAME', 'TutorHub');
define('BASE_URL', '/tutorhub');  // change if you place the project in a different folder
define('UPLOAD_DIR', __DIR__ . '/../uploads/profiles/');
define('UPLOAD_URL', BASE_URL . '/uploads/profiles/');

// ---- Session ----
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Error reporting (disable display_errors in production) ----
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---- Timezone ----
date_default_timezone_set('Asia/Dhaka');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
