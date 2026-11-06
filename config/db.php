<?php
// =====================================================
// SOUND GROUP - Database Configuration
// config/db.php
// =====================================================

define('DB_HOST',   'localhost');
define('DB_USER',   'root');
define('DB_PASS',   '');          // XAMPP default: empty
define('DB_NAME',   'sound_db');
define('DB_CHARSET','utf8mb4');

define('SITE_NAME',    'SOUND Group');
define('SITE_URL',     'http://localhost/sound-php');
define('UPLOAD_PATH',  __DIR__ . '/../assets/uploads/');
define('COVER_PATH',   __DIR__ . '/../assets/images/covers/');

// ---- PDO Connection ----
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:20px;background:#1a0000;color:#ff4444;border:1px solid #ff0000;">
                <h3>⛔ Database Connection Error</h3>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                <p>Please make sure XAMPP MySQL is running and database <b>sound_db</b> is imported.</p>
            </div>');
        }
    }
    return $pdo;
}

