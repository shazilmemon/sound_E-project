<?php
// =====================================================
// SOUND GROUP - Admin Panel Header
// admin/includes/admin-header.php
// =====================================================
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();  // Only admins can access
$currentAdmin = basename($_SERVER['PHP_SELF'], '.php');
$flash = getFlash();

$pendingRevCount = 0;
try {
    $pendingRevCount = (int)getDB()->query("SELECT COUNT(*) FROM reviews WHERE status='pending'")->fetchColumn();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($adminTitle) ? clean($adminTitle).' | ' : '' ?>Admin Portal | SONIXWAVE</title>
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
</head>
<body class="admin-body">

<?php if ($flash): ?>
<div class="flash-msg flash-<?= $flash['type'] ?>">
  <?= $flash['type']==='success' ? '✅' : '⚠️' ?> <?= clean($flash['msg']) ?>
  <button onclick="this.parentElement.remove()">✕</button>
</div>
<?php endif; ?>

<div class="admin-layout">
<!-- SIDEBAR -->
<aside class="admin-sidebar">
  <div class="sidebar-logo">
    <div class="sb-icon"><i class="fas fa-headphones-simple"></i></div>
    <div class="sb-brand-text">
      <span class="sb-title"><em>SONIX</em>WAVE</span>
      <span class="sb-subtitle">ADMIN CONTROL</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-group">
      <div class="nav-label">Main</div>
      <a href="<?= SITE_URL ?>/admin/index.php"    class="nav-link <?= $currentAdmin==='index'      ?'active':'' ?>"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
      <a href="<?= SITE_URL ?>/admin/music.php"    class="nav-link <?= $currentAdmin==='music'      ?'active':'' ?>"><i class="fas fa-music"></i> <span>Music Tracks</span></a>
      <a href="<?= SITE_URL ?>/admin/videos.php"   class="nav-link <?= $currentAdmin==='videos'     ?'active':'' ?>"><i class="fas fa-video"></i> <span>Video Tracks</span></a>
    </div>
    <div class="nav-group">
      <div class="nav-label">Catalog</div>
      <a href="<?= SITE_URL ?>/admin/artists.php"  class="nav-link <?= $currentAdmin==='artists'    ?'active':'' ?>"><i class="fas fa-microphone-lines"></i> <span>Artists</span></a>
      <a href="<?= SITE_URL ?>/admin/albums.php"   class="nav-link <?= $currentAdmin==='albums'     ?'active':'' ?>"><i class="fas fa-compact-disc"></i> <span>Albums</span></a>
      <a href="<?= SITE_URL ?>/admin/categories.php" class="nav-link <?= $currentAdmin==='categories'?'active':'' ?>"><i class="fas fa-tags"></i> <span>Categories</span></a>
      <a href="<?= SITE_URL ?>/admin/users.php"    class="nav-link <?= $currentAdmin==='users'      ?'active':'' ?>"><i class="fas fa-users"></i> <span>Users &amp; Logins</span></a>
      <a href="<?= SITE_URL ?>/admin/reviews.php"  class="nav-link <?= $currentAdmin==='reviews'    ?'active':'' ?>">
        <i class="fas fa-star"></i> <span>Reviews</span>
        <?php if ($pendingRevCount > 0): ?>
          <span class="sb-badge"><?= $pendingRevCount ?></span>
        <?php endif; ?>
      </a>
    </div>
    <div class="nav-group">
      <div class="nav-label">System</div>
      <a href="<?= SITE_URL ?>/admin/settings.php" class="nav-link <?= $currentAdmin==='settings'   ?'active':'' ?>"><i class="fas fa-sliders-h"></i> <span>Portal Settings</span></a>
      <a href="<?= SITE_URL ?>/index.php" target="_blank" class="nav-link"><i class="fas fa-arrow-up-right-from-square"></i> <span>View Live Site</span></a>
      <a href="<?= SITE_URL ?>/pages/logout.php"   class="nav-link danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
  </nav>
</aside>

<!-- MAIN AREA -->
<div class="admin-main">
  <div class="admin-topbar">
    <div class="topbar-left">
      <h1 class="admin-page-title"><?= isset($adminTitle) ? clean($adminTitle) : 'Dashboard' ?></h1>
    </div>
    <div class="topbar-right">
      <a href="<?= SITE_URL ?>/index.php" target="_blank" class="topbar-view-btn" title="Open Public Website"><i class="fas fa-globe"></i> View Website</a>
      <div class="admin-user-pill">
        <div class="admin-avatar"><i class="fas fa-shield-halved"></i></div>
        <div class="admin-user-info">
          <span class="admin-name"><?= clean($_SESSION['user_name']) ?></span>
          <span class="admin-badge-role">Super Admin</span>
        </div>
      </div>
    </div>
  </div>
  <div class="admin-content">

