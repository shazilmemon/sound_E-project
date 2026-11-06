<?php
// =====================================================
// SOUND GROUP - Music Detail + Reviews
// pages/music-detail.php
// =====================================================
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/pages/music.php'); exit; }

// Fetch music
$stmt = $pdo->prepare("
    SELECT m.*, a.name AS artist_name, a.id AS artist_id_val, al.title AS album_title, al.id AS album_id_val
    FROM music m
    LEFT JOIN artists a  ON m.artist_id = a.id
    LEFT JOIN albums  al ON m.album_id  = al.id
    WHERE m.id = ? AND m.status = 'active'
");
$stmt->execute([$id]);
$song = $stmt->fetch();
if (!$song) { header('Location: ' . SITE_URL . '/pages/music.php'); exit; }

// Play count is now incremented via JS when audio actually plays (real-time)
// See: api/track-play.php

// Submit review
$reviewError = '';
$reviewSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        $reviewError = 'Please login to submit a review.';
    } else {
        $rating = (int)($_POST['rating'] ?? 0);
        $text   = trim($_POST['review_text'] ?? '');
        if ($rating < 1 || $rating > 5) {
            $reviewError = 'Please select a rating (1-5 stars).';
        } else {
            $st = $pdo->prepare("INSERT INTO reviews (user_id, media_type, media_id, rating, review_text, status)
                                 VALUES (?,?,?,?,?,'approved')
                                 ON DUPLICATE KEY UPDATE rating=?, review_text=?, status='approved', updated_at=NOW()");
            $st->execute([$_SESSION['user_id'], 'music', $id, $rating, $text, $rating, $text]);
            $reviewSuccess = 'Review submitted successfully!';
        }
    }
}

// Check if current logged in user already reviewed
$myReview = null;
if (isLoggedIn()) {
    $chkMy = $pdo->prepare("SELECT * FROM reviews WHERE user_id=? AND media_type='music' AND media_id=?");
    $chkMy->execute([$_SESSION['user_id'], $id]);
    $myReview = $chkMy->fetch();
}

// Fetch all approved reviews (including the one just submitted)
$reviews = $pdo->prepare("
    SELECT r.*, u.full_name, u.user_id AS uid
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.media_type='music' AND r.media_id=? AND r.status='approved'
    ORDER BY r.created_at DESC
");
$reviews->execute([$id]);
$reviewList = $reviews->fetchAll();

$avgRating = $pdo->prepare("SELECT AVG(rating), COUNT(*) FROM reviews WHERE media_type='music' AND media_id=? AND status='approved'");
$avgRating->execute([$id]);
[$avg, $cnt] = $avgRating->fetch(PDO::FETCH_NUM);

// Related songs
$related = $pdo->prepare("
    SELECT m.*, a.name AS artist_name FROM music m
    LEFT JOIN artists a ON m.artist_id = a.id
    WHERE m.genre = ? AND m.id != ? AND m.status='active' LIMIT 4
");
$related->execute([$song['genre'], $id]);
$relatedSongs = $related->fetchAll();

$pageTitle = clean($song['title']);
include __DIR__ . '/../includes/header.php';
?>

<!-- DETAIL HERO -->
<div class="detail-hero">
  <div class="detail-bg" style="background-image: url('<?= resolveMediaUrl($song['cover_image'], 'cover') ?>')"></div>
  <div class="detail-overlay"></div>
  <div class="container detail-content">
    <div class="detail-thumb">
      <img src="<?= resolveMediaUrl($song['cover_image'], 'cover') ?>" alt="<?= clean($song['title']) ?>" onerror="this.onerror=null; this.src='<?= SITE_URL ?>/assets/images/covers/cover_pop.jpg';">
    </div>
    <div class="detail-info">
      <span class="detail-type music">🎵 Music Track</span>
      <h1><?= clean($song['title']) ?> <?= $song['is_new'] ? '<span class="badge-new">NEW</span>' : '' ?></h1>
      <p class="detail-artist">
        <?php if ($song['artist_id_val']): ?>
          <a href="<?= SITE_URL ?>/pages/artist-detail.php?id=<?= $song['artist_id_val'] ?>"><i class="fas fa-microphone-alt"></i> <?= clean($song['artist_name']) ?></a>
        <?php else: echo '<i class="fas fa-user"></i> ' . clean($song['artist_name'] ?? 'Unknown Artist'); endif; ?>
      </p>
      <div class="detail-tags">
        <?php if ($song['album_id_val']): ?><a href="<?= SITE_URL ?>/pages/album-detail.php?id=<?= $song['album_id_val'] ?>" class="tag">💿 <?= clean($song['album_title']) ?></a><?php endif; ?>
        <span class="tag"><i class="fas fa-tag"></i> <?= clean($song['genre']) ?></span>
        <span class="tag"><i class="fas fa-globe"></i> <?= clean($song['language']) ?></span>
        <span class="tag"><i class="fas fa-calendar"></i> <?= $song['year'] ?></span>
        <span class="tag" id="live-duration-tag"><i class="fas fa-clock"></i> <?= clean($song['duration']) ?></span>
      </div>
      <?php
        $displayRating = ($avg !== null && $cnt > 0) ? number_format((float)$avg, 1) : '0.0';
        $starCount = ($avg !== null && $cnt > 0) ? (int)round((float)$avg) : 0;
      ?>
      <div class="detail-rating">
        <span class="stars" id="hero-stars"><?= $starCount > 0 ? str_repeat('⭐', $starCount) : '☆☆☆☆☆' ?></span>
        <span id="hero-rating-text"><strong style="color:#f5a623;">⭐ <?= $displayRating ?></strong> (<span id="hero-review-cnt"><?= $cnt ?></span> reviews)</span>
        <span class="play-count" id="live-play-count"><i class="fas fa-headphones"></i> <?= number_format($song['play_count']) ?> plays</span>
      </div>

      <!-- AUDIO PLAYER (LOCKED IF NOT LOGGED IN) -->
      <?php if (isLoggedIn()): ?>
      <div class="audio-player-card">
        <div class="player-header">
          <i class="fas fa-volume-up text-accent"></i> <span>Now Playing: <strong><?= clean($song['title']) ?></strong> - <?= clean($song['artist_name'] ?? 'Artist') ?></span>
        </div>
        <?php
          $ext  = strtolower(pathinfo($song['file_path'], PATHINFO_EXTENSION));
          $mime = ($ext === 'm4a') ? 'audio/mp4' : 'audio/mpeg';
        ?>
        <audio id="main-audio-player" controls controlsList="nodownload" preload="metadata" style="width:100%; border-radius:30px;">
          <source src="<?= resolveMediaUrl($song['file_path'], 'music') ?>" type="<?= $mime ?>">
          Your browser does not support HTML5 audio playback.
        </audio>
      </div>
      <?php else: ?>
      <div class="audio-player-card player-locked-card">
        <div class="locked-icon-wrapper">
          <div class="locked-icon-circle"><i class="fas fa-lock"></i></div>
        </div>
        <div class="locked-content">
          <div class="locked-badge"><i class="fas fa-user-lock"></i> Members Only</div>
          <h3>Login to Play This Song</h3>
          <p>Music streaming is only available for registered members. Please login or create a free account to listen to full tracks.</p>
          <div class="locked-actions">
            <a href="<?= SITE_URL ?>/pages/login.php?redirect=<?= urlencode('/pages/music-detail.php?id=' . $song['id']) ?>" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login to Play</a>
            <a href="<?= SITE_URL ?>/pages/register.php?redirect=<?= urlencode('/pages/music-detail.php?id=' . $song['id']) ?>" class="btn btn-outline"><i class="fas fa-user-plus"></i> Register Free</a>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <p class="detail-desc"><?= nl2br(clean($song['description'] ?? 'No description available for this track.')) ?></p>
    </div>
  </div>
</div>

<!-- REVIEWS SECTION -->
<section class="content-section" id="reviews-section">
  <div class="container">
    <div class="reviews-layout">

      <!-- Review List -->
      <div class="reviews-col">
        <h2 class="section-title"><span class="accent-bar"></span><i class="fas fa-star"></i> Reviews (<span id="reviews-count-badge"><?= $cnt ?></span>)</h2>

        <div id="reviews-cards-list">
          <?php if (empty($reviewList)): ?>
            <p class="text-muted" id="no-reviews-msg">No approved reviews yet. Be the first!</p>
          <?php else: ?>
            <?php foreach ($reviewList as $rev): ?>
            <?php $isCurrentUser = isLoggedIn() && ($rev['user_id'] == $_SESSION['user_id']); ?>
            <div class="review-card" <?= $isCurrentUser ? 'id="my-user-review-card"' : '' ?>>
              <div class="review-header">
                <div class="reviewer-avatar"><?= strtoupper(substr($rev['full_name'], 0, 1)) ?></div>
                <div>
                  <strong><?= clean($rev['full_name']) ?></strong>
                  <span class="review-uid">@<?= clean($rev['uid']) ?></span>
                </div>
                <div class="review-stars"><?= str_repeat('⭐', $rev['rating']) ?></div>
                <span class="review-date"><?= date('M d, Y', strtotime($rev['created_at'])) ?></span>
              </div>
              <p class="review-text"><?= nl2br(clean($rev['review_text'])) ?></p>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Submit Review -->
      <div class="review-form-col">
        <h3 id="review-form-title"><i class="fas fa-pen"></i> <?= $myReview ? 'Modify Your Review' : 'Write a Review' ?></h3>

        <div id="ajax-review-alert">
          <?php if ($reviewSuccess): ?>
            <div class="alert alert-success" style="margin-bottom:15px;">✅ <?= clean($reviewSuccess) ?></div>
          <?php elseif ($reviewError): ?>
            <div class="alert alert-danger" style="margin-bottom:15px;">❌ <?= clean($reviewError) ?></div>
          <?php endif; ?>
        </div>

        <?php if (isLoggedIn()): ?>
        <form id="ajax-review-form" action="#reviews-section" method="POST" class="review-form">
          <input type="hidden" name="media_type" value="music">
          <input type="hidden" name="media_id" value="<?= $song['id'] ?>">
          <div class="form-group">
            <label>Rating (1 to 5 Stars) *</label>
            <div class="star-rating">
              <?php $curRating = (int)($myReview['rating'] ?? 0); ?>
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= $curRating === $i ? 'checked' : '' ?> required>
                <label for="star<?= $i ?>">★</label>
              <?php endfor; ?>
            </div>
          </div>
          <div class="form-group">
            <label>Your Review</label>
            <textarea name="review_text" rows="4" placeholder="Share your thoughts about this song..."><?= clean($myReview['review_text'] ?? '') ?></textarea>
          </div>
          <button type="submit" name="submit_review" id="review-submit-btn" class="btn btn-primary"><i class="fas fa-paper-plane"></i> <?= $myReview ? 'Update Review' : 'Submit Review' ?></button>
        </form>
        <?php else: ?>
          <div class="login-prompt">
            <p><i class="fas fa-lock"></i> Please <a href="<?= SITE_URL ?>/pages/login.php">login</a> to write a review.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- RELATED SONGS -->
<?php if (!empty($relatedSongs)): ?>
<section class="content-section bg-dark2">
  <div class="container">
    <h2 class="section-title"><span class="accent-bar"></span><i class="fas fa-music"></i> Related Songs</h2>
    <div class="media-grid">
      <?php foreach ($relatedSongs as $rel): ?>
      <div class="media-card">
        <div class="card-thumb">
          <img src="<?= resolveMediaUrl($rel['cover_image'], 'cover') ?>" alt="<?= clean($rel['title']) ?>">
          <div class="card-overlay"><a href="<?= SITE_URL ?>/pages/music-detail.php?id=<?= $rel['id'] ?>" class="play-circle"><i class="fas fa-play"></i></a></div>
        </div>
        <div class="card-body">
          <h4><a href="<?= SITE_URL ?>/pages/music-detail.php?id=<?= $rel['id'] ?>"><?= clean($rel['title']) ?></a></h4>
          <p class="card-sub"><?= clean($rel['artist_name'] ?? '') ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php if (isLoggedIn()): ?>
<script>
(function() {
  var audio        = document.getElementById('main-audio-player');
  var countEl      = document.getElementById('live-play-count');
  var durationEl   = document.getElementById('live-duration-tag');
  var songId       = <?= (int)$song['id'] ?>;
  var played       = false;

  if (!audio) return;

  // --- Real-time duration from actual audio metadata ---
  audio.addEventListener('loadedmetadata', function() {
    var totalSec = Math.floor(audio.duration);
    if (!isNaN(totalSec) && totalSec > 0) {
      var mins = Math.floor(totalSec / 60);
      var secs = totalSec % 60;
      var fmt  = mins + ':' + (secs < 10 ? '0' : '') + secs;
      if (durationEl) {
        durationEl.innerHTML = '<i class="fas fa-clock"></i> ' + fmt;
      }
      // Persist to DB silently
      fetch('<?= SITE_URL ?>/api/update-duration.php?id=' + songId + '&duration=' + encodeURIComponent(fmt));
    }
  });

  // --- Real-time play count (increment once per page visit) ---
  audio.addEventListener('play', function() {
    if (played) return;
    played = true;
    fetch('<?= SITE_URL ?>/api/track-play.php?type=music&id=' + songId)
      .then(function(r){ return r.json(); })
      .then(function(data) {
        if (data.success && countEl) {
          countEl.innerHTML = '<i class="fas fa-headphones"></i> ' + data.formatted_count + ' plays';
        }
      })
      .catch(function(){});
  });

  // --- Smooth AJAX Review Submission (No page reload, no jump to top) ---
  var reviewForm = document.getElementById('ajax-review-form');
  if (reviewForm) {
    reviewForm.addEventListener('submit', function(e) {
      e.preventDefault();
      var submitBtn = document.getElementById('review-submit-btn');
      var alertBox  = document.getElementById('ajax-review-alert');
      var formData  = new FormData(reviewForm);

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
      }

      fetch('<?= SITE_URL ?>/api/submit-review.php', {
        method: 'POST',
        body: formData
      })
      .then(function(r){ return r.json(); })
      .then(function(res) {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Update Review';
        }

        if (!res.success) {
          alertBox.innerHTML = '<div class="alert alert-danger" style="margin-bottom:15px;">❌ ' + res.error + '</div>';
          return;
        }

        alertBox.innerHTML = '<div class="alert alert-success" style="margin-bottom:15px;">✅ ' + res.message + '</div>';

        // Update form title
        var formTitle = document.getElementById('review-form-title');
        if (formTitle) formTitle.innerHTML = '<i class="fas fa-pen"></i> Modify Your Review';

        // Update hero rating
        var heroStars = document.getElementById('hero-stars');
        var heroText  = document.getElementById('hero-rating-text');
        var listBadge = document.getElementById('reviews-count-badge');

        if (heroStars) heroStars.innerHTML = res.stars_html;
        if (heroText) heroText.innerHTML = '<strong style="color:#f5a623;">⭐ ' + res.formatted_avg + '</strong> (<span id="hero-review-cnt">' + res.review_count + '</span> reviews)';
        if (listBadge) listBadge.textContent = res.review_count;

        // Update review card in list
        var noMsg = document.getElementById('no-reviews-msg');
        if (noMsg) noMsg.remove();

        var cardHtml = '<div class="review-card" id="my-user-review-card" style="border:1px solid rgba(229,9,20,0.3); transition:all 0.3s ease;">' +
          '<div class="review-header">' +
            '<div class="reviewer-avatar">' + res.review_card.avatar + '</div>' +
            '<div>' +
              '<strong>' + res.review_card.full_name + '</strong> ' +
              '<span class="review-uid">@' + res.review_card.uid + '</span>' +
            '</div>' +
            '<div class="review-stars">' + res.review_card.rating_str + '</div>' +
            '<span class="review-date">' + res.review_card.date + ' (Just now)</span>' +
          '</div>' +
          '<p class="review-text">' + res.review_card.text + '</p>' +
        '</div>';

        var existingCard = document.getElementById('my-user-review-card');
        var cardsList    = document.getElementById('reviews-cards-list');

        if (existingCard) {
          existingCard.outerHTML = cardHtml;
        } else if (cardsList) {
          cardsList.insertAdjacentHTML('afterbegin', cardHtml);
        }
      })
      .catch(function(err) {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Review';
        }
        alertBox.innerHTML = '<div class="alert alert-danger" style="margin-bottom:15px;">❌ Network error occurred. Please try again.</div>';
      });
    });
  }
})();
</script>
<?php endif; ?>

