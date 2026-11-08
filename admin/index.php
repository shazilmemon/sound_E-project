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

$recentVideos = $pdo->query("
    SELECT v.*, a.name AS artist_name FROM videos v
    LEFT JOIN artists a ON v.artist_id = a.id
    ORDER BY v.created_at DESC LIMIT 5
")->fetchAll();

// Recent reviews
$recentReviews = $pdo->query("
    SELECT r.*, u.full_name,
           CASE 
               WHEN r.media_type = 'music' THEN (SELECT title FROM music WHERE id = r.media_id)
               WHEN r.media_type = 'video' THEN (SELECT title FROM videos WHERE id = r.media_id)
           END as media_title
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC LIMIT 5
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
  <a href="<?= SITE_URL ?>/admin/music.php?action=add"      class="qa-btn"><i class="fas fa-plus"></i> Add Music</a>
  <a href="<?= SITE_URL ?>/admin/videos.php?action=add"     class="qa-btn"><i class="fas fa-plus"></i> Add Video</a>
  <a href="<?= SITE_URL ?>/admin/artists.php?action=add"    class="qa-btn"><i class="fas fa-plus"></i> Add Artist</a>
  <a href="<?= SITE_URL ?>/admin/albums.php?action=add"     class="qa-btn"><i class="fas fa-plus"></i> Add Album</a>
  <a href="<?= SITE_URL ?>/admin/categories.php"            class="qa-btn"><i class="fas fa-tags"></i> Add Category</a>
  <a href="<?= SITE_URL ?>/admin/users.php?action=add"      class="qa-btn"><i class="fas fa-user-plus"></i> Add User</a>
</div>

<div class="admin-two-col">
  <!-- Recent Music -->
  <div class="admin-box">
    <div class="box-head">
      <h3><i class="fas fa-music"></i> Recent Music Tracks</h3>
      <a href="<?= SITE_URL ?>/admin/music.php" class="tbl-btn">View All</a>
    </div>
    <table class="admin-table">
      <thead><tr><th>Title</th><th>Artist</th><th>Lang</th><th>Plays</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($recentMusic as $m): ?>
        <tr>
          <td><strong><?= clean($m['title']) ?></strong> <?= isMediaNew($m) ? '<span class="badge-new-sm">NEW</span>' : '' ?></td>
          <td><?= clean($m['artist_name'] ?? '-') ?></td>
          <td><span class="badge-tag"><?= clean($m['language']) ?></span></td>
          <td><?= number_format($m['play_count']) ?></td>
          <td style="white-space:nowrap;">
            <button type="button" class="tbl-btn" style="color:var(--primary);" onclick="previewAdminSong('<?= resolveMediaUrl($m['file_path'], 'music') ?>', '<?= clean(addslashes($m['title'])) ?>')" title="Play Preview"><i class="fas fa-play"></i></button>
            <a href="<?= SITE_URL ?>/admin/music.php?action=edit&id=<?= $m['id'] ?>" class="tbl-btn" title="Edit"><i class="fas fa-edit"></i></a>
            <a href="<?= SITE_URL ?>/admin/music.php?action=delete&id=<?= $m['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete this song?')" title="Delete"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Recent Videos -->
  <div class="admin-box">
    <div class="box-head">
      <h3><i class="fas fa-video"></i> Recent Video Tracks</h3>
      <a href="<?= SITE_URL ?>/admin/videos.php" class="tbl-btn">View All</a>
    </div>
    <table class="admin-table">
      <thead><tr><th>Title</th><th>Artist</th><th>Lang</th><th>Views</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($recentVideos as $v): ?>
        <tr>
          <td><strong><?= clean($v['title']) ?></strong> <?= isMediaNew($v) ? '<span class="badge-new-sm">NEW</span>' : '' ?></td>
          <td><?= clean($v['artist_name'] ?? '-') ?></td>
          <td><span class="badge-tag"><?= clean($v['language']) ?></span></td>
          <td><?= number_format($v['view_count']) ?></td>
          <td style="white-space:nowrap;">
            <button type="button" class="tbl-btn" style="color:var(--primary);" onclick="previewAdminVideo('<?= resolveMediaUrl($v['file_path'], 'video') ?>', '<?= clean(addslashes($v['title'])) ?>')" title="Play Video Preview"><i class="fas fa-play"></i></button>
            <a href="<?= SITE_URL ?>/admin/videos.php?action=edit&id=<?= $v['id'] ?>" class="tbl-btn" title="Edit"><i class="fas fa-edit"></i></a>
            <a href="<?= SITE_URL ?>/admin/videos.php?action=delete&id=<?= $v['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete this video?')" title="Delete"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="admin-two-col" style="margin-top:24px;">
  <!-- Recent Reviews / Moderation -->
  <div class="admin-box">
    <div class="box-head">
      <h3><i class="fas fa-star"></i> Moderation Queue <?php if ($pendingRev > 0): ?><span class="sb-badge"><?= $pendingRev ?> Pending</span><?php endif; ?></h3>
      <a href="<?= SITE_URL ?>/admin/reviews.php" class="tbl-btn">View All</a>
    </div>
    <table class="admin-table">
      <thead><tr><th>User</th><th>Item</th><th>Rating</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        <?php if (empty($recentReviews)): ?>
          <tr><td colspan="5" style="text-align:center; padding:15px; color:var(--text-muted);">No reviews yet.</td></tr>
        <?php else: ?>
          <?php foreach ($recentReviews as $r): ?>
          <tr>
            <td><strong><?= clean($r['full_name']) ?></strong></td>
            <td><?= clean($r['media_title'] ?? 'N/A') ?></td>
            <td><span style="color:#f5a623;"><?= str_repeat('★', $r['rating']) ?></span></td>
            <td><span class="badge-status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
            <td style="white-space:nowrap;">
              <?php if ($r['status'] !== 'approved'): ?>
                <a href="<?= SITE_URL ?>/admin/reviews.php?action=approve&id=<?= $r['id'] ?>" class="tbl-btn" style="color:#00b894;" title="Approve"><i class="fas fa-check"></i></a>
              <?php endif; ?>
              <a href="<?= SITE_URL ?>/admin/reviews.php" class="tbl-btn" title="Manage"><i class="fas fa-external-link-alt"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Recent Users Directory -->
  <div class="admin-box">
    <div class="box-head">
      <h3><i class="fas fa-users"></i> Recent Users</h3>
      <a href="<?= SITE_URL ?>/admin/users.php" class="tbl-btn">Manage All</a>
    </div>
    <table class="admin-table">
      <thead><tr><th>User</th><th>Email</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($recentUsers as $u): ?>
        <tr>
          <td>
            <strong><?= clean($u['full_name']) ?></strong><br>
            <small style="color:var(--text-muted);">@<?= clean($u['user_id']) ?></small>
          </td>
          <td><small><?= clean($u['email']) ?></small></td>
          <td>
            <a href="<?= SITE_URL ?>/admin/users.php?action=toggle&id=<?= $u['id'] ?>" class="status-<?= $u['status'] ?>" style="font-weight:600; text-decoration:none;">
              ● <?= ucfirst($u['status']) ?>
            </a>
          </td>
          <td>
            <a href="<?= SITE_URL ?>/admin/users.php?action=edit&id=<?= $u['id'] ?>" class="tbl-btn"><i class="fas fa-edit"></i> Edit</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

