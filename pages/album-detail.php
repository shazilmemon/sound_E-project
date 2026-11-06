<?php
// =====================================================
// SOUND GROUP - Album Detail & Tracklist
// pages/album-detail.php
// =====================================================
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/pages/albums.php'); exit; }

$stmt = $pdo->prepare("
    SELECT al.*, a.name as artist_name, a.id as artist_id_val
    FROM albums al
    LEFT JOIN artists a ON al.artist_id = a.id
    WHERE al.id = ?
");
$stmt->execute([$id]);
$album = $stmt->fetch();
if (!$album) { header('Location: ' . SITE_URL . '/pages/albums.php'); exit; }

// Fetch tracks belonging to this album
$tracks = $pdo->prepare("
    SELECT m.*, a.name as track_artist
    FROM music m
    LEFT JOIN artists a ON m.artist_id = a.id
    WHERE m.album_id = ? AND m.status='active'
    ORDER BY m.id ASC
");
$tracks->execute([$id]);
$trackList = $tracks->fetchAll();

$pageTitle = clean($album['title']);
include __DIR__ . '/../includes/header.php';
?>

<div class="detail-hero">
  <div class="detail-bg" style="background-image: url('<?= resolveMediaUrl($album['cover_image'], 'cover') ?>')"></div>
  <div class="detail-overlay"></div>
  <div class="container detail-content">
    <div class="detail-thumb">
      <img src="<?= resolveMediaUrl($album['cover_image'], 'cover') ?>" alt="<?= clean($album['title']) ?>" onerror="this.onerror=null; this.src='<?= SITE_URL ?>/assets/images/covers/cover_pop.jpg';">
    </div>
    <div class="detail-info">
      <span class="detail-type album">💿 Album</span>
      <h1><?= clean($album['title']) ?></h1>
      <p class="detail-artist">
        By <?= clean($album['artist_name'] ?? 'Various Artists') ?>
      </p>
      <div class="detail-tags">
        <span class="tag"><?= clean($album['language']) ?></span>
        <span class="tag">Year <?= $album['year'] ?></span>
        <span class="tag"><?= count($trackList) ?> Songs</span>
      </div>
      <p class="detail-desc"><?= nl2br(clean($album['description'] ?? '')) ?></p>
    </div>
  </div>
</div>

<section class="content-section">
  <div class="container">
    <h2 class="section-title"><span class="accent-bar"></span><i class="fas fa-list-ol"></i> Album Tracklist</h2>

    <?php if (empty($trackList)): ?>
      <p class="text-muted">No individual song files linked to this album yet.</p>
    <?php else: ?>
      <div class="trending-list">
        <?php foreach ($trackList as $idx => $t): ?>
        <a href="<?= SITE_URL ?>/pages/music-detail.php?id=<?= $t['id'] ?>" class="trending-item">
          <span class="t-num"><?= str_pad($idx+1, 2, '0', STR_PAD_LEFT) ?></span>
          <div class="t-thumb">
            <img src="<?= resolveMediaUrl($t['cover_image'], 'cover') ?>" alt="<?= clean($t['title']) ?>" onerror="this.onerror=null; this.src='<?= SITE_URL ?>/assets/images/covers/cover_pop.jpg';">
          </div>
          <div class="t-info">
            <h4><?= clean($t['title']) ?></h4>
            <p><?= clean($t['track_artist'] ?? $album['artist_name']) ?> &bull; <?= clean($t['genre']) ?></p>
          </div>
          <div class="t-meta">
            <span class="t-plays"><?= clean($t['duration']) ?></span>
            <span class="t-type music">▶</span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

