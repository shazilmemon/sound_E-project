<?php
// =====================================================
// SOUND GROUP - Admin Panel Header
// admin/includes/admin-header.php
// =====================================================
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();  // Only admins can access
$currentAdmin = basename($_SERVER['PHP_SELF'], '.php');
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($adminTitle) ? clean($adminTitle).' | ' : '' ?>Admin | SOUND Group</title>
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
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
    <div class="sb-icon">S</div>
    <span><em>SOUND</em> ADMIN</span>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-group">
      <div class="nav-label">Main</div>
      <a href="<?= SITE_URL ?>/admin/index.php"    class="nav-link <?= $currentAdmin==='index'      ?'active':'' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="<?= SITE_URL ?>/admin/music.php"    class="nav-link <?= $currentAdmin==='music'      ?'active':'' ?>"><i class="fas fa-music"></i> Music</a>
      <a href="<?= SITE_URL ?>/admin/videos.php"   class="nav-link <?= $currentAdmin==='videos'     ?'active':'' ?>"><i class="fas fa-video"></i> Videos</a>
    </div>
    <div class="nav-group">
      <div class="nav-label">Manage</div>
      <a href="<?= SITE_URL ?>/admin/artists.php"  class="nav-link <?= $currentAdmin==='artists'    ?'active':'' ?>"><i class="fas fa-microphone"></i> Artists</a>
      <a href="<?= SITE_URL ?>/admin/albums.php"   class="nav-link <?= $currentAdmin==='albums'     ?'active':'' ?>"><i class="fas fa-compact-disc"></i> Albums</a>
      <a href="<?= SITE_URL ?>/admin/categories.php" class="nav-link <?= $currentAdmin==='categories'?'active':'' ?>"><i class="fas fa-tags"></i> Categories</a>
      <a href="<?= SITE_URL ?>/admin/users.php"    class="nav-link <?= $currentAdmin==='users'      ?'active':'' ?>"><i class="fas fa-users"></i> Users</a>
      <a href="<?= SITE_URL ?>/admin/reviews.php"  class="nav-link <?= $currentAdmin==='reviews'    ?'active':'' ?>"><i class="fas fa-star"></i> Reviews</a>
    </div>
    <div class="nav-group">
      <div class="nav-label">System</div>
      <a href="<?= SITE_URL ?>/admin/settings.php" class="nav-link <?= $currentAdmin==='settings'   ?'active':'' ?>"><i class="fas fa-cog"></i> Settings</a>
      <a href="<?= SITE_URL ?>/index.php"          class="nav-link"><i class="fas fa-home"></i> View Site</a>
      <a href="<?= SITE_URL ?>/pages/logout.php"   class="nav-link danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </nav>
</aside>

<!-- MAIN AREA -->
<div class="admin-main">
  <div class="admin-topbar">
    <h1 class="admin-page-title"><?= isset($adminTitle) ? clean($adminTitle) : 'Dashboard' ?></h1>
    <div class="topbar-right">
      <span class="admin-user"><i class="fas fa-user-circle"></i> <?= clean($_SESSION['user_name']) ?></span>
    </div>
  </div>
  <div class="admin-content">

