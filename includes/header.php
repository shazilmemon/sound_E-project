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
    <a href="<?= SITE_URL ?>/index.php" class="brand-link" title="SONIXWAVE - Sound Group">
      <div class="brand-badge">
        <div class="logo-icon-glow"></div>
        <div class="logo-icon-inner">
          <i class="fas fa-headphones-simple brand-icon"></i>
          <div class="nav-eq-bars" aria-hidden="true">
            <span class="eq-bar bar-1"></span>
            <span class="eq-bar bar-2"></span>
            <span class="eq-bar bar-3"></span>
            <span class="eq-bar bar-4"></span>
          </div>
        </div>
      </div>
      <div class="brand-title-wrap">
        <span class="brand-title"><span class="brand-glow">SONIX</span><span class="brand-accent">WAVE</span></span>
        <span class="brand-tagline"><span class="pulse-dot"></span> SOUND GROUP</span>
      </div>
    </a>
  </div>

  <ul class="nav-links" id="navLinks">
    <li>
      <a href="<?= SITE_URL ?>/index.php" class="nav-item <?= $currentPage==='index' ? 'active' : '' ?>">
        <i class="fas fa-house nav-icon"></i>
        <span>Home</span>
      </a>
    </li>
    <li>
      <a href="<?= SITE_URL ?>/pages/music.php" class="nav-item <?= $currentPage==='music' ? 'active' : '' ?>">
        <i class="fas fa-music nav-icon"></i>
        <span>Music</span>
      </a>
    </li>
    <li>
      <a href="<?= SITE_URL ?>/pages/videos.php" class="nav-item <?= $currentPage==='videos' ? 'active' : '' ?>">
        <i class="fas fa-play-circle nav-icon"></i>
        <span>Videos</span>
      </a>
    </li>
    <li>
      <a href="<?= SITE_URL ?>/pages/albums.php" class="nav-item <?= $currentPage==='albums' ? 'active' : '' ?>">
        <i class="fas fa-compact-disc nav-icon disc-icon"></i>
        <span>Albums</span>
      </a>
    </li>
    <li>
      <a href="<?= SITE_URL ?>/pages/artists.php" class="nav-item <?= $currentPage==='artists' ? 'active' : '' ?>">
        <i class="fas fa-microphone-lines nav-icon"></i>
        <span>Artists</span>
      </a>
    </li>
    <li>
      <a href="<?= SITE_URL ?>/pages/search.php" class="nav-item <?= $currentPage==='search' ? 'active' : '' ?>">
        <i class="fas fa-compass nav-icon"></i>
        <span>Explore</span>
      </a>
    </li>
  </ul>

  <div class="nav-actions">
    <form action="<?= SITE_URL ?>/pages/search.php" method="GET" class="nav-search" id="navSearchForm">
      <div class="search-input-wrapper">
        <i class="fas fa-search search-icon-left"></i>
        <input type="text" name="q" placeholder="Search tracks, artists..." value="<?= isset($_GET['q']) ? clean($_GET['q']) : '' ?>" autocomplete="off">
        <button type="submit" class="search-submit-btn" title="Search"><i class="fas fa-arrow-right"></i></button>
      </div>
    </form>

    <?php if (isLoggedIn()): ?>
      <div class="user-menu" id="userMenu">
        <button class="user-pill-btn" id="userPillBtn" aria-expanded="false" type="button">
          <div class="user-avatar-ring">
            <i class="fas fa-user"></i>
          </div>
          <span class="user-greeting"><?= clean($_SESSION['user_name']) ?></span>
          <i class="fas fa-chevron-down caret-icon"></i>
        </button>
        <div class="user-dropdown">
          <div class="dropdown-header">
            <span class="dropdown-user-name"><?= clean($_SESSION['user_name']) ?></span>
            <span class="dropdown-user-role"><?= isAdmin() ? 'Administrator' : 'Sound Member' ?></span>
          </div>
          <hr>
          <?php if(isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/index.php" class="dropdown-link admin-link"><i class="fas fa-shield-halved"></i> Admin Panel</a>
          <?php endif; ?>
          <a href="<?= SITE_URL ?>/pages/profile.php" class="dropdown-link"><i class="fas fa-user-gear"></i> My Profile</a>
          <a href="<?= SITE_URL ?>/pages/my-reviews.php" class="dropdown-link"><i class="fas fa-star"></i> My Reviews</a>
          <hr>
          <a href="<?= SITE_URL ?>/pages/logout.php" class="dropdown-link logout-link"><i class="fas fa-arrow-right-from-bracket"></i> Logout</a>
        </div>
      </div>
    <?php else: ?>
      <div class="auth-buttons">
        <a href="<?= SITE_URL ?>/pages/login.php" class="btn btn-outline-glow"><i class="fas fa-arrow-right-to-bracket"></i> Login</a>
        <a href="<?= SITE_URL ?>/pages/register.php" class="btn btn-primary-glow"><i class="fas fa-wand-magic-sparkles"></i> Sign Up</a>
      </div>
    <?php endif; ?>

    <div class="hamburger" id="hamburger" aria-label="Toggle navigation" role="button" tabindex="0">
      <span></span><span></span><span></span>
    </div>
  </div>
</nav>

