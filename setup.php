<?php
// =====================================================
// SOUND GROUP - 1-Click Database & Tables Installer
// setup.php
// =====================================================

$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$dbName = 'sound_db';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SOUND Group - Database Setup</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f111a; color: #e2e8f0; padding: 40px; margin: 0; }
        .card { max-width: 800px; margin: 0 auto; background: #1a1e2e; border: 1px solid #2d3748; border-radius: 12px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h1 { color: #38bdf8; margin-top: 0; font-size: 26px; border-bottom: 2px solid #2d3748; padding-bottom: 12px; }
        .step { display: flex; align-items: center; padding: 10px 15px; margin: 8px 0; border-radius: 6px; font-family: monospace; font-size: 14px; }
        .step.success { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid #22c55e; }
        .step.error { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid #ef4444; }
        .step.info { background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid #0284c7; }
        .btn { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 20px; }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
<div class="card">
    <h1>🚀 SOUND Group - Database & Tables Setup</h1>
<?php

try {
    // Step 1: Connect to MySQL server (without selecting DB)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo '<div class="step success">✔ Step 1: Connected to MySQL Server successfully!</div>';

    // Step 2: Create Database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo '<div class="step success">✔ Step 2: Database <b>' . $dbName . '</b> verified / created!</div>';

    // Step 3: Select database
    $pdo->exec("USE `$dbName`;");
    echo '<div class="step info">📌 Step 3: Switched to database <b>' . $dbName . '</b></div>';

    // Step 4: Tables Array in Foreign-Key Dependency Order
    $tables = [
        "users" => "CREATE TABLE IF NOT EXISTS users (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            user_id     VARCHAR(50)  NOT NULL UNIQUE,
            full_name   VARCHAR(100) NOT NULL,
            email       VARCHAR(150) NOT NULL UNIQUE,
            phone       VARCHAR(20)  NOT NULL,
            address     TEXT         NOT NULL,
            password    VARCHAR(255) NOT NULL,
            role        ENUM('admin','user') DEFAULT 'user',
            status      ENUM('active','inactive') DEFAULT 'active',
            avatar      VARCHAR(255) DEFAULT NULL,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;",

        "artists" => "CREATE TABLE IF NOT EXISTS artists (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(100) NOT NULL,
            bio         TEXT,
            image       VARCHAR(255),
            language    VARCHAR(50),
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;",

        "albums" => "CREATE TABLE IF NOT EXISTS albums (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            title       VARCHAR(150) NOT NULL,
            artist_id   INT,
            year        YEAR NOT NULL,
            cover_image VARCHAR(255),
            description TEXT,
            language    VARCHAR(50),
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE SET NULL
        ) ENGINE=InnoDB;",

        "music" => "CREATE TABLE IF NOT EXISTS music (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            title       VARCHAR(200) NOT NULL,
            artist_id   INT,
            album_id    INT,
            year        YEAR NOT NULL,
            genre       VARCHAR(50)  NOT NULL,
            language    VARCHAR(50)  NOT NULL,
            cover_image VARCHAR(255),
            file_path   VARCHAR(255),
            duration    VARCHAR(10) DEFAULT '0:00',
            description TEXT,
            play_count  INT DEFAULT 0,
            is_new      TINYINT(1) DEFAULT 1,
            status      ENUM('active','inactive') DEFAULT 'active',
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE SET NULL,
            FOREIGN KEY (album_id)  REFERENCES albums(id)  ON DELETE SET NULL
        ) ENGINE=InnoDB;",

        "videos" => "CREATE TABLE IF NOT EXISTS videos (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            title       VARCHAR(200) NOT NULL,
            artist_id   INT,
            album_id    INT,
            year        YEAR NOT NULL,
            genre       VARCHAR(50)  NOT NULL,
            language    VARCHAR(50)  NOT NULL,
            cover_image VARCHAR(255),
            file_path   VARCHAR(255),
            duration    VARCHAR(10) DEFAULT '0:00',
            description TEXT,
            view_count  INT DEFAULT 0,
            is_new      TINYINT(1) DEFAULT 1,
            status      ENUM('active','inactive') DEFAULT 'active',
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE SET NULL,
            FOREIGN KEY (album_id)  REFERENCES albums(id)  ON DELETE SET NULL
        ) ENGINE=InnoDB;",

        "reviews" => "CREATE TABLE IF NOT EXISTS reviews (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            user_id     INT NOT NULL,
            media_type  ENUM('music','video') NOT NULL,
            media_id    INT NOT NULL,
            rating      TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
            review_text TEXT,
            status      ENUM('pending','approved','rejected') DEFAULT 'approved',
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_review (user_id, media_type, media_id)
        ) ENGINE=InnoDB;",

        "categories" => "CREATE TABLE IF NOT EXISTS categories (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            type        ENUM('genre','language','year') NOT NULL,
            name        VARCHAR(100) NOT NULL,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;"
    ];

    echo '<h3 style="color:#f1f5f9;margin-top:20px;">🔨 Creating Tables:</h3>';
    foreach ($tables as $name => $query) {
        $pdo->exec($query);
        echo "<div class='step success'>✔ Table [<b>$name</b>] created or already exists.</div>";
    }

    // Step 5: Check and insert sample data if tables are empty
    $chk = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($chk == 0) {
        // Insert Admin & User
        $hashed = password_hash('Admin@123', PASSWORD_BCRYPT);
        $userHash = password_hash('User@123', PASSWORD_BCRYPT);

        $pdo->prepare("INSERT INTO users (user_id, full_name, email, phone, address, password, role) VALUES 
            ('admin_sound', 'Admin User', 'admin@soundgroup.com', '+91-9876543210', 'Sound Group HQ', ?, 'admin'),
            ('rahul_99', 'Rahul Sharma', 'rahul@gmail.com', '+91-9123456789', 'Delhi, India', ?, 'user')
        ")->execute([$hashed, $userHash]);

        // Insert Artists
        $pdo->exec("INSERT INTO artists (name, bio, language) VALUES
            ('Arijit Singh', 'Bollywood playback singer', 'Hindi'),
            ('Atif Aslam', 'Pakistani singer and actor', 'Hindi/Urdu'),
            ('Ed Sheeran', 'English singer-songwriter', 'English'),
            ('The Weeknd', 'Canadian R&B artist', 'English');");

        // Insert Categories
        $pdo->exec("INSERT INTO categories (type, name) VALUES
            ('genre', 'Pop'), ('genre', 'Bollywood'), ('genre', 'Rock'), ('genre', 'Regional'),
            ('language', 'Hindi'), ('language', 'English'), ('language', 'Punjabi'),
            ('year', '2024'), ('year', '2023'), ('year', '2022');");

        echo "<div class='step info'>✔ Default Admin, Demo User, Categories & Artists seeded!</div>";
    }

    echo '<p style="color:#4ade80;font-weight:bold;margin-top:25px;font-size:18px;">🎉 Setup complete! All database tables & connection are ready.</p>';
    echo '<a class="btn" href="index.php">Go to Homepage →</a> ';
    echo '<a class="btn" style="background:#475569;margin-left:10px;" href="admin/index.php">Go to Admin Panel →</a>';

} catch (PDOException $e) {
    echo '<div class="step error">❌ Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<p style="color:#cbd5e1;">Please make sure MySQL is running in your XAMPP/WAMP Control Panel.</p>';
}
?>
</div>
</body>
</html>

