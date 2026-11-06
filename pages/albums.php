<?php
// =====================================================
// SOUND GROUP - Albums Catalog
// pages/albums.php
// =====================================================
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDB();
$pageTitle = 'Albums';

$lang = trim($_GET['lang'] ?? '');
$year = trim($_GET['year'] ?? '');

$where = ['1=1'];
$params = [];

if ($lang) {
    $where[] = "al.language = ?";
    $params[] = $lang;
}
if ($year) {
    $where[] = "al.year = ?";
    $params[] = $year;
}

$stmt = $pdo->prepare("
    SELECT al.*, a.name AS artist_name, COUNT(m.id) as track_count
    FROM albums al
    LEFT JOIN artists a ON al.artist_id = a.id
    LEFT JOIN music m ON m.album_id = al.id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY al.id
    ORDER BY al.year DESC
");
$stmt->execute($params);
$albums = $stmt->fetchAll();

$languages = $pdo->query("SELECT DISTINCT language FROM albums WHERE language IS NOT NULL ORDER BY language")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1><i class="fas fa-compact-disc"></i> Albums</h1>
    <p>Discover complete albums and collections in English and Regional languages</p>
  </div>
</div>

<div class="filters-bar">
  <div class="container">
    <form action="" method="GET" class="filters-form">
      <select name="lang">
        <option value="">All Languages</option>
        <?php foreach ($languages as $l): ?>
          <option value="<?= clean($l) ?>" <?= $lang === $l ? 'selected' : '' ?>><?= clean($l) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="number" name="year" placeholder="Year (e.g. 2022)" value="<?= clean($year) ?>" style="width:140px;">
      <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
      <?php if ($lang || $year): ?>
        <a href="<?= SITE_URL ?>/pages/albums.php" class="btn btn-outline">Clear</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<section class="content-section">
  <div class="container">
    <div class="albums-grid">
      <?php foreach ($albums as $alb): ?>
      <a href="<?= SITE_URL ?>/pages/album-detail.php?id=<?= $alb['id'] ?>" class="album-card">
        <div class="album-thumb">
          <img src="<?= resolveMediaUrl($alb['cover_image'], 'cover') ?>" alt="<?= clean($alb['title']) ?>">
          <div class="album-overlay"><i class="fas fa-play"></i></div>
        </div>
        <div class="album-body">
          <h4><?= clean($alb['title']) ?></h4>
          <p><?= clean($alb['artist_name'] ?? 'Soundtrack') ?> &bull; <?= clean($alb['language']) ?></p>
          <div class="album-meta">
            <span><?= $alb['year'] ?></span>
            <span class="track-count"><?= $alb['track_count'] ?> Tracks</span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

