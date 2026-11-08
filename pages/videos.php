<?php
// =====================================================
// SOUND GROUP - Videos Listing
// pages/videos.php
// =====================================================
require_once __DIR__ . '/../includes/auth.php';

$pdo  = getDB();
$pageTitle = 'Videos';

$search   = trim($_GET['q']    ?? '');
$genre    = trim($_GET['genre'] ?? '');
$language = trim($_GET['lang']  ?? '');
$year     = trim($_GET['year']  ?? '');
$sort     = trim($_GET['sort']  ?? 'latest');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

$where  = ["v.status = 'active'"];
$params = [];

if ($search) {
    $where[]  = "(v.title LIKE ? OR a.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($genre)    { $where[] = "v.genre    = ?"; $params[] = $genre;    }
if ($language) { $where[] = "v.language = ?"; $params[] = $language; }
if ($year)     { $where[] = "v.year     = ?"; $params[] = $year;     }

$orderBy = match($sort) {
    'popular' => 'v.view_count DESC',
    'year'    => 'v.year DESC',
    'az'      => 'v.title ASC',
    default   => 'v.created_at DESC'
};

$whereSql = implode(' AND ', $where);

$countSql  = "SELECT COUNT(*) FROM videos v LEFT JOIN artists a ON v.artist_id = a.id WHERE $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

$sql  = "SELECT v.*, a.name AS artist_name
         FROM videos v
         LEFT JOIN artists a ON v.artist_id = a.id
         WHERE $whereSql
         ORDER BY $orderBy
         LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$videos = $stmt->fetchAll();

$genres    = $pdo->query("SELECT DISTINCT genre    FROM videos WHERE status='active' ORDER BY genre")->fetchAll(PDO::FETCH_COLUMN);
$languages = $pdo->query("SELECT DISTINCT language FROM videos WHERE status='active' ORDER BY language")->fetchAll(PDO::FETCH_COLUMN);
$years     = $pdo->query("SELECT DISTINCT year     FROM videos WHERE status='active' ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1><i class="fas fa-video"></i> Video Library</h1>
    <p><?= number_format($total) ?> videos available in Regional and English</p>
  </div>
</div>

<div class="filters-bar">
  <div class="container">
    <form action="" method="GET" class="filters-form">
      <input type="text" name="q" placeholder="🔍 Search videos or cast..." value="<?= clean($search) ?>">

      <select name="genre">
        <option value="">All Genres</option>
        <?php foreach ($genres as $g): ?>
          <option value="<?= clean($g) ?>" <?= $genre===$g?'selected':'' ?>><?= clean($g) ?></option>
        <?php endforeach; ?>
      </select>

      <select name="lang">
        <option value="">All Languages</option>
        <?php foreach ($languages as $l): ?>
          <option value="<?= clean($l) ?>" <?= $language===$l?'selected':'' ?>><?= clean($l) ?></option>
        <?php endforeach; ?>
      </select>

      <select name="year">
        <option value="">All Years</option>
        <?php foreach ($years as $y): ?>
          <option value="<?= $y ?>" <?= $year==(string)$y?'selected':'' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>

      <select name="sort">
        <option value="latest"  <?= $sort==='latest' ?'selected':'' ?>>Latest</option>
        <option value="popular" <?= $sort==='popular'?'selected':'' ?>>Most Viewed</option>
        <option value="year"    <?= $sort==='year'   ?'selected':'' ?>>By Year</option>
        <option value="az"      <?= $sort==='az'     ?'selected':'' ?>>A-Z</option>
      </select>

      <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
      <?php if ($search || $genre || $language || $year): ?>
        <a href="<?= SITE_URL ?>/pages/videos.php" class="btn btn-outline">Clear</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<section class="content-section">
  <div class="container">
    <?php if (empty($videos)): ?>
      <div class="empty-state">
        <i class="fas fa-video fa-3x"></i>
        <h3>No videos found</h3>
        <p>Try modifying your filters or search keywords.</p>
      </div>
    <?php else: ?>
    <div class="media-grid">
      <?php foreach ($videos as $vid): ?>
      <div class="media-card" onclick="location.href='<?= SITE_URL ?>/pages/video-detail.php?id=<?= $vid['id'] ?>'">
        <div class="card-thumb">
          <img src="<?= resolveMediaUrl($vid['cover_image'], 'cover') ?>" alt="<?= clean($vid['title']) ?>" onerror="this.onerror=null; this.src='<?= SITE_URL ?>/assets/images/covers/cover_pop.jpg';">
          <div class="card-overlay"><div class="play-circle"><i class="fas fa-play"></i></div></div>
          <?php if (isMediaNew($vid)): ?><span class="badge-new">NEW</span><?php endif; ?>
          <span class="badge-type video">🎬 Video</span>
        </div>
        <div class="card-body">
          <h4><?= clean($vid['title']) ?></h4>
          <p class="card-sub"><?= clean($vid['artist_name'] ?? 'Unknown Cast') ?></p>
          <div class="card-footer">
            <span class="card-lang"><?= clean($vid['language']) ?></span>
            <span class="card-year"><?= $vid['year'] ?></span>
          </div>
          <div class="card-stats">
            <span><i class="fas fa-eye"></i> <?= number_format($vid['view_count']) ?> views</span>
            <span><?= clean($vid['genre']) ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php $qs = http_build_query(array_filter(['q'=>$search,'genre'=>$genre,'lang'=>$language,'year'=>$year,'sort'=>$sort])); ?>
      <?php if ($page > 1): ?>
        <a href="?<?= $qs ?>&page=<?= $page-1 ?>" class="page-btn">&laquo; Prev</a>
      <?php endif; ?>
      <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <a href="?<?= $qs ?>&page=<?= $i ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?<?= $qs ?>&page=<?= $page+1 ?>" class="page-btn">Next &raquo;</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

