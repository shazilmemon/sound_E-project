<?php
// =====================================================
// SOUND GROUP - Footer Template
// includes/footer.php
// =====================================================
?>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-container">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="brand-link" style="margin-bottom: 14px;">
          <div class="brand-badge">
            <div class="logo-icon-glow"></div>
            <div class="logo-icon-inner">
              <i class="fas fa-headphones-simple brand-icon"></i>
            </div>
          </div>
          <div class="brand-title-wrap">
            <span class="brand-title"><span class="brand-glow">SONIX</span><span class="brand-accent">WAVE</span></span>
            <span class="brand-tagline"><span class="pulse-dot"></span> SOUND GROUP</span>
          </div>
        </div>
        <p><?= clean(getSiteSetting('about_text', 'Your ultimate destination for music and video entertainment. Explore songs, albums, artists in Regional & English languages.')) ?></p>
        <div class="social-links">
          <?php if ($fb = getSiteSetting('social_facebook')): ?><a href="<?= clean($fb) ?>" target="_blank" rel="noopener" class="social-link"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
          <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
          <?php if ($ig = getSiteSetting('social_instagram')): ?><a href="<?= clean($ig) ?>" target="_blank" rel="noopener" class="social-link"><i class="fab fa-instagram"></i></a><?php endif; ?>
          <?php if ($yt = getSiteSetting('social_youtube')): ?><a href="<?= clean($yt) ?>" target="_blank" rel="noopener" class="social-link"><i class="fab fa-youtube"></i></a><?php endif; ?>
        </div>
      </div>

      <div class="footer-col">
        <h4>Music</h4>
        <ul>
          <li><a href="<?= SITE_URL ?>/pages/music.php">Latest Songs</a></li>
          <li><a href="<?= SITE_URL ?>/pages/music.php?sort=popular">Top Charts</a></li>
          <li><a href="<?= SITE_URL ?>/pages/albums.php">Albums</a></li>
          <li><a href="<?= SITE_URL ?>/pages/artists.php">Artists</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Browse By</h4>
        <ul>
          <li><a href="<?= SITE_URL ?>/pages/music.php?lang=Hindi">Hindi</a></li>
          <li><a href="<?= SITE_URL ?>/pages/music.php?lang=English">English</a></li>
          <li><a href="<?= SITE_URL ?>/pages/music.php?lang=Regional">Regional</a></li>
          <li><a href="<?= SITE_URL ?>/pages/search.php">Search</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Account</h4>
        <ul>
          <?php if(isLoggedIn()): ?>
            <li><a href="<?= SITE_URL ?>/pages/profile.php">My Profile</a></li>
            <li><a href="<?= SITE_URL ?>/pages/my-reviews.php">My Reviews</a></li>
            <li><a href="<?= SITE_URL ?>/pages/logout.php">Logout</a></li>
          <?php else: ?>
            <li><a href="<?= SITE_URL ?>/pages/login.php">Login</a></li>
            <li><a href="<?= SITE_URL ?>/pages/register.php">Register</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> SOUND Group. All rights reserved.</p>
      <div>
        <a href="#">Privacy Policy</a>
        <a href="#">Terms of Service</a>
        <a href="#">Contact Us</a>
      </div>
    </div>
  </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js?v=<?= time() ?>"></script>
</body>
</html>

