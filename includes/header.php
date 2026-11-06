<?php
// =====================================================
// SOUND GROUP - Header Template
// includes/header.php
// =====================================================
require_once __DIR__ . '/../includes/auth.php';
$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? clean($pageTitle) . ' | ' : '' ?>SOUND Group</title>
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php if ($flash): ?>
<div class="flash-msg flash-<?= $flash['type'] ?>">
  <?= ($flash['type'] === 'success') ? '✅' : '⚠️' ?> <?= clean($flash['msg']) ?>
  <button onclick="this.parentElement.remove()">✕</button>
</div>
<?php endif; ?>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
  <div class="nav-logo">
    <a href="<?= SITE_URL ?>/index.php">
      <div class="logo-icon">S</div>
      <span class="logo-text"><em>SOUND</em> GROUP</span>
    </a>
  </div>

  <ul class="nav-links">
    <li><a href="<?= SITE_URL ?>/index.php"       class="<?= $currentPage==='index'   ?'active':'' ?>">Home</a></li>
    <li><a href="<?= SITE_URL ?>/pages/music.php"  class="<?= $currentPage==='music'   ?'active':'' ?>">Music</a></li>
    <li><a href="<?= SITE_URL ?>/pages/videos.php" class="<?= $currentPage==='videos'  ?'active':'' ?>">Videos</a></li>
    <li><a href="<?= SITE_URL ?>/pages/albums.php" class="<?= $currentPage==='albums'  ?'active':'' ?>">Albums</a></li>
    <li><a href="<?= SITE_URL ?>/pages/artists.php"class="<?= $currentPage==='artists' ?'active':'' ?>">Artists</a></li>
    <li><a href="<?= SITE_URL ?>/pages/search.php" class="<?= $currentPage==='search'  ?'active':'' ?>">Search</a></li>
  </ul>

  <div class="nav-actions">
    <form action="<?= SITE_URL ?>/pages/search.php" method="GET" class="nav-search">
      <input type="text" name="q" placeholder="Search music, artists..." value="<?= isset($_GET['q']) ? clean($_GET['q']) : '' ?>">
      <button type="submit"><i class="fas fa-search"></i></button>
    </form>

    <?php if (isLoggedIn()): ?>
      <div class="user-menu">
        <span class="user-greeting"><i class="fas fa-user-circle"></i> <?= clean($_SESSION['user_name']) ?></span>
        <div class="user-dropdown">
          <?php if(isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/index.php"><i class="fas fa-cog"></i> Admin Panel</a>
          <?php endif; ?>
          <a href="<?= SITE_URL ?>/pages/profile.php"><i class="fas fa-user"></i> My Profile</a>
          <a href="<?= SITE_URL ?>/pages/my-reviews.php"><i class="fas fa-star"></i> My Reviews</a>
          <hr>
          <a href="<?= SITE_URL ?>/pages/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
      </div>
    <?php else: ?>
      <a href="<?= SITE_URL ?>/pages/login.php"    class="btn btn-outline">Login</a>
      <a href="<?= SITE_URL ?>/pages/register.php" class="btn btn-primary">Sign Up</a>
    <?php endif; ?>
  </div>

  <div class="hamburger" id="hamburger">
    <span></span><span></span><span></span>
  </div>
</nav>

