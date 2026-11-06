<?php
// =====================================================
// SOUND GROUP - Search Page
// pages/search.php
// =====================================================
require_once __DIR__ . '/../includes/auth.php';

$pdo   = getDB();
$q     = trim($_GET['q']    ?? '');
$type  = trim($_GET['type'] ?? 'all');   // all | music | video
$lang  = trim($_GET['lang'] ?? '');
$genre = trim($_GET['genre'] ?? '');

$musicResults = [];
$videoResults = [];

if ($q) {
    $like = "%$q%";

    if ($type === 'all' || $type === 'music') {
        $where  = ["m.status='active'", "(m.title LIKE ? OR a.name LIKE ? OR m.genre LIKE ?)"];
        $params = [$like, $like, $like];
        if ($lang)  { $where[] = "m.language = ?"; $params[] = $lang; }
        if ($genre) { $where[] = "m.genre = ?";    $params[] = $genre; }
        $sql = "SELECT m.*, a.name AS artist_name, 'music' AS media_type
                FROM music m LEFT JOIN artists a ON m.artist_id = a.id
                WHERE " . implode(' AND ', $where) . " ORDER BY m.play_count DESC LIMIT 20";
        $st = $pdo->prepare($sql); $st->execute($params);
        $musicResults = $st->fetchAll();
    }

    if ($type === 'all' || $type === 'video') {
        $where  = ["v.status='active'", "(v.title LIKE ? OR a.name LIKE ? OR v.genre LIKE ?)"];
        $params = [$like, $like, $like];
        if ($lang)  { $where[] = "v.language = ?"; $params[] = $lang; }
        if ($genre) { $where[] = "v.genre = ?";    $params[] = $genre; }
        $sql = "SELECT v.*, a.name AS artist_name, 'video' AS media_type
                FROM videos v LEFT JOIN artists a ON v.artist_id = a.id
                WHERE " . implode(' AND ', $where) . " ORDER BY v.view_count DESC LIMIT 20";
        $st = $pdo->prepare($sql); $st->execute($params);
        $videoResults = $st->fetchAll();
    }
}

$totalResults = count($musicResults) + count($videoResults);
$pageTitle = $q ? "Search: $q" : "Search";
include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1><i class="fas fa-search"></i> Search</h1>
  </div>
</div>

<section class="content-section">
  <div class="container">

    <!-- SEARCH BAR -->
    <div class="search-hero-bar">
      <form action="" method="GET" class="search-big-form">
        <div class="search-input-wrap">
          <i class="fas fa-search"></i>
          <input type="text" name="q" placeholder="Search songs, videos, artists, albums..." value="<?= clean($q) ?>" autofocus>
        </div>
        <select name="type">
          <option value="all"   <?= $type==='all'  ?'selected':'' ?>>All</option>
          <option value="music" <?= $type==='music'?'selected':'' ?>>Music</option>
          <option value="video" <?= $type==='video'?'selected':'' ?>>Video</option>
        </select>
        <select name="lang">
          <option value="">All Languages</option>
          <?php
          $langs = $pdo->query("SELECT DISTINCT language FROM music UNION SELECT DISTINCT language FROM videos ORDER BY language")->fetchAll(PDO::FETCH_COLUMN);
          foreach ($langs as $l): ?>
            <option value="<?= clean($l) ?>" <?= $lang===$l?'selected':'' ?>><?= clean($l) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
      </form>
    </div>

    <?php if (!$q): ?>
      <!-- POPULAR SEARCHES -->
      <div class="popular-tags">
        <p>Popular:</p>
        <?php foreach (['Arijit', 'Atif', 'Bollywood', 'Hindi', 'English', 'Pop', 'Regional'] as $tag): ?>
          <a href="?q=<?= urlencode($tag) ?>" class="pop-tag"><?= $tag ?></a>
        <?php endforeach; ?>
      </div>

    <?php else: ?>

      <div class="search-meta">
        <p>Found <strong><?= $totalResults ?></strong> results for "<em><?= clean($q) ?></em>"</p>
      </div>

      <?php if (!empty($musicResults)): ?>
        <h2 class="section-title mt-40"><span class="accent-bar"></span><i class="fas fa-music"></i> Songs (<?= count($musicResults) ?>)</h2>
        <div class="media-grid">
          <?php foreach ($musicResults as $s): ?>
          <div class="media-card">
            <div class="card-thumb">
              <img src="<?= resolveMediaUrl($s['cover_image'], 'cover') ?>" alt="<?= clean($s['title']) ?>" onerror="this.onerror=null; this.src='<?= SITE_URL ?>/assets/images/covers/cover_pop.jpg';">
              <div class="card-overlay"><a href="<?= SITE_URL ?>/pages/music-detail.php?id=<?= $s['id'] ?>" class="play-circle"><i class="fas fa-play"></i></a></div>
            </div>
            <div class="card-body">
              <h4><a href="<?= SITE_URL ?>/pages/music-detail.php?id=<?= $s['id'] ?>"><?= clean($s['title']) ?></a></h4>
              <p class="card-sub"><?= clean($s['artist_name'] ?? '') ?></p>
              <div class="card-footer">
                <span class="card-lang"><?= clean($s['language']) ?></span>
                <span><?= $s['year'] ?></span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($videoResults)): ?>
        <h2 class="section-title mt-40"><span class="accent-bar"></span><i class="fas fa-video"></i> Videos (<?= count($videoResults) ?>)</h2>
        <div class="media-grid">
          <?php foreach ($videoResults as $v): ?>
          <div class="media-card">
            <div class="card-thumb">
              <img src="<?= resolveMediaUrl($v['cover_image'], 'cover') ?>" alt="<?= clean($v['title']) ?>" onerror="this.onerror=null; this.src='<?= SITE_URL ?>/assets/images/covers/cover_pop.jpg';">
              <div class="card-overlay"><a href="<?= SITE_URL ?>/pages/video-detail.php?id=<?= $v['id'] ?>" class="play-circle"><i class="fas fa-play"></i></a></div>
              <span class="badge-type video">🎬</span>
            </div>
            <div class="card-body">
              <h4><a href="<?= SITE_URL ?>/pages/video-detail.php?id=<?= $v['id'] ?>"><?= clean($v['title']) ?></a></h4>
              <p class="card-sub"><?= clean($v['artist_name'] ?? '') ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($totalResults === 0): ?>
        <div class="empty-state">
          <i class="fas fa-search fa-3x"></i>
          <h3>No results found</h3>
          <p>Try different keywords or remove filters.</p>
        </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

