<?php
// =====================================================
// SOUND GROUP - Admin Website Details & Settings
// admin/settings.php
// Requirement: "Manage information/details on the WEBSITE"
// =====================================================
$adminTitle = 'Website Settings';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/admin-header.php';

$pdo = getDB();

// We create a settings table if not exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT
    ) ENGINE=InnoDB;
");

// Defaults
$defaults = [
    'site_title'        => 'SONIXWAVE - SOUND Group',
    'site_tagline'      => 'Stream Unlimited Music & Videos in High Definition',
    'contact_email'     => 'info@soundgroup.com',
    'contact_phone'     => '+92 300 1234567',
    'about_text'        => 'SOUND Group is the premier entertainment portal hosting new and popular Videos and Songs in both REGIONAL and ENGLISH languages with comprehensive reviews and ratings.',
    'homepage_music_cnt'=> '5',
    'homepage_video_cnt'=> '5',
    'social_facebook'   => 'https://facebook.com',
    'social_instagram'  => 'https://instagram.com',
    'social_youtube'    => 'https://youtube.com'
];

foreach ($defaults as $k => $v) {
    $st = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    $st->execute([$k, $v]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $val) {
        $st = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $st->execute([$key, trim($val), trim($val)]);
    }
    setFlash('success', 'Website information updated successfully.');
    header('Location: ' . SITE_URL . '/admin/settings.php');
    exit;
}

$rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="admin-box" style="max-width:850px;">
  <div class="box-head">
    <h3><i class="fas fa-sliders-h"></i> Manage Website Information &amp; Preferences</h3>
    <a href="<?= SITE_URL ?>/index.php" target="_blank" class="btn btn-outline" style="font-size:12px;"><i class="fas fa-eye"></i> View Live Site</a>
  </div>

  <form method="POST" class="admin-form">
    <div class="form-row">
      <div class="form-group">
        <label>Website Name / Title *</label>
        <input type="text" name="site_title" value="<?= clean($rows['site_title'] ?? 'SONIXWAVE - SOUND Group') ?>" required>
      </div>
      <div class="form-group">
        <label>Portal Tagline</label>
        <input type="text" name="site_tagline" value="<?= clean($rows['site_tagline'] ?? '') ?>" placeholder="e.g. Stream Unlimited Music & Videos">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Contact Email *</label>
        <input type="email" name="contact_email" value="<?= clean($rows['contact_email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Contact Phone *</label>
        <input type="text" name="contact_phone" value="<?= clean($rows['contact_phone'] ?? '') ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Latest Music Count on Home Page (Requirement: 5)</label>
        <input type="number" name="homepage_music_cnt" value="<?= clean($rows['homepage_music_cnt'] ?? '5') ?>" min="1" max="20" required>
      </div>
      <div class="form-group">
        <label>Latest Video Count on Home Page (Requirement: 5)</label>
        <input type="number" name="homepage_video_cnt" value="<?= clean($rows['homepage_video_cnt'] ?? '5') ?>" min="1" max="20" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label><i class="fab fa-facebook"></i> Facebook Page URL</label>
        <input type="url" name="social_facebook" value="<?= clean($rows['social_facebook'] ?? '') ?>" placeholder="https://facebook.com/soundgroup">
      </div>
      <div class="form-group">
        <label><i class="fab fa-instagram"></i> Instagram Profile URL</label>
        <input type="url" name="social_instagram" value="<?= clean($rows['social_instagram'] ?? '') ?>" placeholder="https://instagram.com/soundgroup">
      </div>
    </div>

    <div class="form-group">
      <label><i class="fab fa-youtube"></i> YouTube Channel URL</label>
      <input type="url" name="social_youtube" value="<?= clean($rows['social_youtube'] ?? '') ?>" placeholder="https://youtube.com/@soundgroup">
    </div>

    <div class="form-group">
      <label>About SOUND Group (Shown in Footer &amp; About)</label>
      <textarea name="about_text" rows="4"><?= clean($rows['about_text'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Website Configuration</button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

