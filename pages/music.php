<?php
// =====================================================
// SOUND GROUP - Music Listing
// pages/music.php
// =====================================================
require_once __DIR__ . '/../includes/auth.php';

$pdo  = getDB();
$pageTitle = 'Music';

// Filters
$search   = trim($_GET['q']    ?? '');
$genre    = trim($_GET['genre'] ?? '');
$language = trim($_GET['lang']  ?? '');
$year     = trim($_GET['year']  ?? '');
$sort     = trim($_GET['sort']  ?? 'latest');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 10;
$offset   = ($page - 1) * $perPage;

// Build query
$where  = ["m.status = 'active'"];
$params = [];

if ($search) {
    $where[]  = "(m.title LIKE ? OR a.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($genre)    { $where[] = "m.genre    = ?"; $params[] = $genre;    }
if ($language) { $where[] = "m.language = ?"; $params[] = $language; }
if ($year)     { $where[] = "m.year     = ?"; $params[] = $year;     }

$orderBy = match($sort) {
    'popular' => 'm.play_count DESC',
    'year'    => 'm.year DESC',
    'az'      => 'm.title ASC',
    default   => 'm.created_at DESC'
};

$whereSql = implode(' AND ', $where);

// Count total
$countSql  = "SELECT COUNT(*) FROM music m LEFT JOIN artists a ON m.artist_id = a.id WHERE $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

// Fetch
$sql  = "SELECT m.*, a.name AS artist_name
         FROM music m
         LEFT JOIN artists a ON m.artist_id = a.id
         WHERE $whereSql
         ORDER BY $orderBy
         LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$songs = $stmt->fetchAll();

// Genres & Languages for filter dropdowns
$genres    = $pdo->query("SELECT DISTINCT genre    FROM music WHERE status='active' ORDER BY genre")->fetchAll(PDO::FETCH_COLUMN);
$languages = $pdo->query("SELECT DISTINCT language FROM music WHERE status='active' ORDER BY language")->fetchAll(PDO::FETCH_COLUMN);
$years     = $pdo->query("SELECT DISTINCT year     FROM music WHERE status='active' ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../includes/header.php';
?>

<!-- PAGE BANNER -->
<div class="page-banner">
  <div class="container">
    <h1><i class="fas fa-music"></i> Music Library</h1>
    <p><?= number_format($total) ?> songs available</p>
  </div>
</div>

<!-- FILTERS -->
<div class="filters-bar">
  <div class="container">
    <form action="" method="GET" class="filters-form">
      <input type="text" name="q" placeholder="🔍 Search songs, artists..." value="<?= clean($search) ?>">

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
        <option value="popular" <?= $sort==='popular'?'selected':'' ?>>Most Popular</option>
        <option value="year"    <?= $sort==='year'   ?'selected':'' ?>>By Year</option>
        <option value="az"      <?= $sort==='az'     ?'selected':'' ?>>A-Z</option>
      </select>

      <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
      <?php if ($search || $genre || $language || $year): ?>
        <a href="<?= SITE_URL ?>/pages/music.php" class="btn btn-outline">Clear</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- MUSIC GRID -->
<section class="content-section">
  <div class="container">
    <?php if (empty($songs)): ?>
      <div class="empty-state">
        <i class="fas fa-music fa-3x"></i>
        <h3>No songs found</h3>
        <p>Try changing your filters or search term.</p>
      </div>
    <?php else: ?>
    <div class="media-grid">
      <?php foreach ($songs as $song): ?>
      <div class="media-card" onclick="location.href='<?= SITE_URL ?>/pages/music-detail.php?id=<?= $song['id'] ?>'">
        <div class="card-thumb">
          <img src="<?= resolveMediaUrl($song['cover_image'], 'cover') ?>" alt="<?= clean($song['title']) ?>" onerror="this.onerror=null; this.src='<?= SITE_URL ?>/assets/images/covers/cover_pop.jpg';">
          <div class="card-overlay">
            <div class="play-circle"><i class="fas fa-play"></i></div>
          </div>
          <?php if (isMediaNew($song)): ?><span class="badge-new">NEW</span><?php endif; ?>
          <span class="badge-type music">🎵</span>
        </div>
        <div class="card-body">
          <h4><?= clean($song['title']) ?></h4>
          <p class="card-sub"><?= clean($song['artist_name'] ?? 'Unknown') ?></p>
          <div class="card-footer">
            <span class="card-genre"><?= clean($song['genre']) ?></span>
            <span class="card-lang"><?= clean($song['language']) ?></span>
          </div>
          <div class="card-stats">
            <span><i class="fas fa-headphones"></i> <?= number_format($song['play_count']) ?></span>
            <span><?= $song['year'] ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php
        $qs = http_build_query(array_filter(['q'=>$search,'genre'=>$genre,'lang'=>$language,'year'=>$year,'sort'=>$sort]));
      ?>
      <?php if ($page > 1): ?>
        <a href="?<?= $qs ?>&page=<?= $page-1 ?>" class="page-btn">&laquo; Prev</a>
      <?php endif; ?>
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
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

