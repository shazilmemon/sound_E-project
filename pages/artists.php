<?php
// =====================================================
// SOUND GROUP - Artists Directory
// pages/artists.php
// =====================================================
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDB();
$pageTitle = 'Artists';

$artists = $pdo->query("
    SELECT a.*, COUNT(m.id) as songs_count
    FROM artists a
    LEFT JOIN music m ON m.artist_id = a.id
    GROUP BY a.id
    ORDER BY a.name ASC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1><i class="fas fa-microphone"></i> Top Artists</h1>
    <p>Explore your favorite singers, composers, and bands</p>
  </div>
</div>

<section class="content-section">
  <div class="container">
    <div class="artists-grid">
      <?php foreach ($artists as $art): ?>
      <a href="<?= SITE_URL ?>/pages/artist-detail.php?id=<?= $art['id'] ?>" class="artist-card">
        <div class="artist-avatar">
          <img src="<?= resolveMediaUrl($art['image'], 'artist') ?>" alt="<?= clean($art['name']) ?>">
        </div>
        <h4><?= clean($art['name']) ?></h4>
        <p><?= clean($art['language']) ?> &bull; <?= $art['songs_count'] ?> Songs</p>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

