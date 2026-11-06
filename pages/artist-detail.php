<?php
// =====================================================
// SOUND GROUP - Artist Detail
// pages/artist-detail.php
// =====================================================
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/pages/artists.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM artists WHERE id = ?");
$stmt->execute([$id]);
$artist = $stmt->fetch();
if (!$artist) { header('Location: ' . SITE_URL . '/pages/artists.php'); exit; }

$songs = $pdo->prepare("SELECT * FROM music WHERE artist_id = ? AND status='active' ORDER BY play_count DESC");
$songs->execute([$id]);
$songList = $songs->fetchAll();

$videos = $pdo->prepare("SELECT * FROM videos WHERE artist_id = ? AND status='active' ORDER BY view_count DESC");
$videos->execute([$id]);
$videoList = $videos->fetchAll();

$pageTitle = clean($artist['name']);
include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner" style="padding:60px 0 40px;">
  <div class="container" style="display:flex; align-items:center; gap:30px;">
    <div style="width:130px; height:130px; border-radius:50%; overflow:hidden; border:4px solid var(--primary); flex-shrink:0;">
      <img src="<?= resolveMediaUrl($artist['image'], 'artist') ?>" style="width:100%; height:100%; object-fit:cover;">
    </div>
    <div>
      <span class="badge-tag"><?= clean($artist['language']) ?> Artist</span>
      <h1 style="margin:8px 0;"><?= clean($artist['name']) ?></h1>
      <p style="color:var(--text-secondary); max-width:650px;"><?= nl2br(clean($artist['bio'] ?? '')) ?></p>
    </div>
  </div>
</div>

<section class="content-section">
  <div class="container">
    <h2 class="section-title"><span class="accent-bar"></span><i class="fas fa-music"></i> Songs by <?= clean($artist['name']) ?> (<?= count($songList) ?>)</h2>
    <?php if (empty($songList)): ?>
      <p class="text-muted">No songs available for this artist yet.</p>
    <?php else: ?>
      <div class="media-grid">
        <?php foreach ($songList as $song): ?>
        <div class="media-card" onclick="location.href='<?= SITE_URL ?>/pages/music-detail.php?id=<?= $song['id'] ?>'">
          <div class="card-thumb">
            <img src="<?= resolveMediaUrl($song['cover_image'], 'cover') ?>" alt="<?= clean($song['title']) ?>" onerror="this.onerror=null; this.src='<?= SITE_URL ?>/assets/images/covers/cover_pop.jpg';">
            <div class="card-overlay"><div class="play-circle"><i class="fas fa-play"></i></div></div>
          </div>
          <div class="card-body">
            <h4><?= clean($song['title']) ?></h4>
            <p class="card-sub"><?= clean($song['genre']) ?> &bull; <?= $song['year'] ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($videoList)): ?>
      <h2 class="section-title mt-40"><span class="accent-bar"></span><i class="fas fa-video"></i> Videos (<?= count($videoList) ?>)</h2>
      <div class="media-grid">
        <?php foreach ($videoList as $vid): ?>
        <div class="media-card" onclick="location.href='<?= SITE_URL ?>/pages/video-detail.php?id=<?= $vid['id'] ?>'">
          <div class="card-thumb">
            <img src="<?= resolveMediaUrl($vid['cover_image'], 'cover') ?>" alt="<?= clean($vid['title']) ?>">
            <div class="card-overlay"><div class="play-circle"><i class="fas fa-play"></i></div></div>
          </div>
          <div class="card-body">
            <h4><?= clean($vid['title']) ?></h4>
            <p class="card-sub"><?= clean($vid['genre']) ?> &bull; <?= $vid['year'] ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

