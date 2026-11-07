<?php
// =====================================================
// SOUND GROUP - Admin Dashboard
// admin/index.php
// =====================================================
$adminTitle = 'Dashboard';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/admin-header.php';

$pdo = getDB();

$totalMusic   = $pdo->query("SELECT COUNT(*) FROM music  WHERE status='active'")->fetchColumn();
$totalVideos  = $pdo->query("SELECT COUNT(*) FROM videos WHERE status='active'")->fetchColumn();
$totalUsers   = $pdo->query("SELECT COUNT(*) FROM users  WHERE role='user'")->fetchColumn();
$totalReviews = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$pendingRev   = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status='pending'")->fetchColumn();
$totalArtists = $pdo->query("SELECT COUNT(*) FROM artists")->fetchColumn();
$totalAlbums  = $pdo->query("SELECT COUNT(*) FROM albums")->fetchColumn();

// Recent uploads
$recentMusic = $pdo->query("
    SELECT m.*, a.name AS artist_name FROM music m
    LEFT JOIN artists a ON m.artist_id = a.id
    ORDER BY m.created_at DESC LIMIT 5
")->fetchAll();

// Recent users
$recentUsers = $pdo->query("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>

<!-- STAT CARDS -->
<div class="stats-grid">
  <div class="stat-card-admin">
    <div class="stat-icon red"><i class="fas fa-music"></i></div>
    <div class="stat-data">
      <span class="stat-num"><?= number_format($totalMusic) ?></span>
      <span class="stat-label">Total Songs</span>
    </div>
    <a href="<?= SITE_URL ?>/admin/music.php" class="stat-link">Manage &rarr;</a>
  </div>
  <div class="stat-card-admin">
    <div class="stat-icon green"><i class="fas fa-video"></i></div>
    <div class="stat-data">
      <span class="stat-num"><?= number_format($totalVideos) ?></span>
      <span class="stat-label">Total Videos</span>
    </div>
    <a href="<?= SITE_URL ?>/admin/videos.php" class="stat-link">Manage &rarr;</a>
  </div>
  <div class="stat-card-admin">
    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
    <div class="stat-data">
      <span class="stat-num"><?= number_format($totalUsers) ?></span>
      <span class="stat-label">Registered Users</span>
    </div>
    <a href="<?= SITE_URL ?>/admin/users.php" class="stat-link">Manage &rarr;</a>
  </div>
  <div class="stat-card-admin">
    <div class="stat-icon yellow"><i class="fas fa-star"></i></div>
    <div class="stat-data">
      <span class="stat-num"><?= number_format($totalReviews) ?></span>
      <span class="stat-label">Reviews <?php if ($pendingRev > 0): ?><span class="badge-pending"><?= $pendingRev ?> pending</span><?php endif; ?></span>
    </div>
    <a href="<?= SITE_URL ?>/admin/reviews.php" class="stat-link">Manage &rarr;</a>
  </div>
  <div class="stat-card-admin">
    <div class="stat-icon purple"><i class="fas fa-microphone"></i></div>
    <div class="stat-data">
      <span class="stat-num"><?= $totalArtists ?></span>
      <span class="stat-label">Artists</span>
    </div>
    <a href="<?= SITE_URL ?>/admin/artists.php" class="stat-link">Manage &rarr;</a>
  </div>
  <div class="stat-card-admin">
    <div class="stat-icon orange"><i class="fas fa-compact-disc"></i></div>
    <div class="stat-data">
      <span class="stat-num"><?= $totalAlbums ?></span>
      <span class="stat-label">Albums</span>
    </div>
    <a href="<?= SITE_URL ?>/admin/albums.php" class="stat-link">Manage &rarr;</a>
  </div>
</div>

<!-- QUICK ACTIONS -->
<div class="quick-actions">
  <a href="<?= SITE_URL ?>/admin/music.php?action=add"   class="qa-btn"><i class="fas fa-plus"></i> Add Music</a>
  <a href="<?= SITE_URL ?>/admin/videos.php?action=add"  class="qa-btn"><i class="fas fa-plus"></i> Add Video</a>
  <a href="<?= SITE_URL ?>/admin/artists.php?action=add" class="qa-btn"><i class="fas fa-plus"></i> Add Artist</a>
  <a href="<?= SITE_URL ?>/admin/albums.php?action=add"  class="qa-btn"><i class="fas fa-plus"></i> Add Album</a>
</div>

<div class="admin-two-col">
  <!-- Recent Music -->
  <div class="admin-box">
    <div class="box-head">
      <h3><i class="fas fa-music"></i> Recent Music</h3>
      <a href="<?= SITE_URL ?>/admin/music.php">View All</a>
    </div>
    <table class="admin-table">
      <thead><tr><th>Title</th><th>Artist</th><th>Lang</th><th>Plays</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($recentMusic as $m): ?>
        <tr>
          <td><?= clean($m['title']) ?> <?= isMediaNew($m) ? '<span class="badge-new-sm">NEW</span>' : '' ?></td>
          <td><?= clean($m['artist_name'] ?? '-') ?></td>
          <td><?= clean($m['language']) ?></td>
          <td><?= number_format($m['play_count']) ?></td>
          <td>
            <a href="<?= SITE_URL ?>/admin/music.php?action=edit&id=<?= $m['id'] ?>" class="tbl-btn">Edit</a>
            <a href="<?= SITE_URL ?>/admin/music.php?action=delete&id=<?= $m['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete this song?')">Del</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Recent Users -->
  <div class="admin-box">
    <div class="box-head">
      <h3><i class="fas fa-users"></i> Recent Users</h3>
      <a href="<?= SITE_URL ?>/admin/users.php">View All</a>
    </div>
    <table class="admin-table">
      <thead><tr><th>Name</th><th>User ID</th><th>Email</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($recentUsers as $u): ?>
        <tr>
          <td><?= clean($u['full_name']) ?></td>
          <td>@<?= clean($u['user_id']) ?></td>
          <td><?= clean($u['email']) ?></td>
          <td><span class="status-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

