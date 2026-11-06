<?php
// =====================================================
// SOUND GROUP - Video Detail & Reviews
// pages/video-detail.php
// =====================================================
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/pages/videos.php'); exit; }

$stmt = $pdo->prepare("
    SELECT v.*, a.name AS artist_name, a.id AS artist_id_val, al.title AS album_title
    FROM videos v
    LEFT JOIN artists a ON v.artist_id = a.id
    LEFT JOIN albums al ON v.album_id = al.id
    WHERE v.id = ? AND v.status = 'active'
");
$stmt->execute([$id]);
$video = $stmt->fetch();
if (!$video) { header('Location: ' . SITE_URL . '/pages/videos.php'); exit; }

// Play count is updated via JS real-time when video actually plays
// See: api/track-play.php

// Submit review
$reviewError = '';
$reviewSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        $reviewError = 'Please login to submit your rating and review.';
    } else {
        $rating = (int)($_POST['rating'] ?? 0);
        $text   = trim($_POST['review_text'] ?? '');
        if ($rating < 1 || $rating > 5) {
            $reviewError = 'Please choose a rating between 1 and 5 stars.';
        } else {
            $st = $pdo->prepare("INSERT INTO reviews (user_id, media_type, media_id, rating, review_text, status)
                                 VALUES (?,?,?,?,?,'approved')
                                 ON DUPLICATE KEY UPDATE rating=?, review_text=?, status='approved', updated_at=NOW()");
            $st->execute([$_SESSION['user_id'], 'video', $id, $rating, $text, $rating, $text]);
            $reviewSuccess = 'Thank you! Your review has been submitted successfully.';
        }
    }
}

// Check if user already reviewed
$myReview = null;
if (isLoggedIn()) {
    $chkMy = $pdo->prepare("SELECT * FROM reviews WHERE user_id=? AND media_type='video' AND media_id=?");
    $chkMy->execute([$_SESSION['user_id'], $id]);
    $myReview = $chkMy->fetch();
}

// Fetch all approved reviews (including the one just submitted)
$reviews = $pdo->prepare("
    SELECT r.*, u.full_name, u.user_id AS uid
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.media_type='video' AND r.media_id=? AND r.status='approved'
    ORDER BY r.created_at DESC
");
$reviews->execute([$id]);
$reviewList = $reviews->fetchAll();

$avgRating = $pdo->prepare("SELECT AVG(rating), COUNT(*) FROM reviews WHERE media_type='video' AND media_id=? AND status='approved'");
$avgRating->execute([$id]);
[$avg, $cnt] = $avgRating->fetch(PDO::FETCH_NUM);


$pageTitle = clean($video['title']);
include __DIR__ . '/../includes/header.php';
?>

<div class="video-container-page">
  <div class="container">
    <div class="video-player-wrapper">
      <?php if (isLoggedIn()): ?>
        <div class="video-responsive">
          <video id="main-video-player" controls autoplay playsinline poster="<?= resolveMediaUrl($video['cover_image'], 'cover') ?>" style="width:100%; height:100%; object-fit:contain; background:#000;">
            <source src="<?= resolveMediaUrl($video['file_path'], 'video') ?>" type="video/mp4">
            Your browser does not support the video tag.
          </video>
        </div>
      <?php else: ?>
        <div class="video-responsive video-locked-responsive" style="background-image: linear-gradient(rgba(10,12,20,0.85), rgba(10,12,20,0.92)), url('<?= resolveMediaUrl($video['cover_image'], 'cover') ?>');">
          <div class="video-locked-content">
            <div class="locked-icon-circle-lg">
              <i class="fas fa-lock"></i>
            </div>
            <div class="locked-badge"><i class="fas fa-user-lock"></i> Members Only</div>
            <h2>Video Playback is Locked</h2>
            <p>You must be registered and logged in to watch <strong><?= clean($video['title']) ?></strong>.</p>
            <div class="locked-actions">
              <a href="<?= SITE_URL ?>/pages/login.php?redirect=<?= urlencode('/pages/video-detail.php?id=' . $video['id']) ?>" class="btn btn-primary btn-lg"><i class="fas fa-sign-in-alt"></i> Login to Watch</a>
              <a href="<?= SITE_URL ?>/pages/register.php?redirect=<?= urlencode('/pages/video-detail.php?id=' . $video['id']) ?>" class="btn btn-outline btn-lg"><i class="fas fa-user-plus"></i> Register Free</a>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="video-info-box">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px;">
        <div>
          <h1><?= clean($video['title']) ?> <?= $video['is_new'] ? '<span class="badge-new">NEW</span>' : '' ?></h1>
          <p class="detail-artist">
            Cast / Artist: <strong><?= clean($video['artist_name'] ?? 'Various Artists') ?></strong>
            &bull; <?= clean($video['language']) ?>
            &bull; <?= clean($video['genre']) ?>
            &bull; <?= $video['year'] ?>
          </p>
        </div>
        <?php
          $displayRating = ($avg !== null && $cnt > 0) ? number_format((float)$avg, 1) : '0.0';
          $starCount = ($avg !== null && $cnt > 0) ? (int)round((float)$avg) : 0;
        ?>
        <div style="text-align:right;">
          <span id="video-hero-stars" style="font-size:18px; color:#f5a623;"><?= $starCount > 0 ? str_repeat('★', $starCount) : '☆☆☆☆☆' ?></span>
          <p style="font-size:13px; color:var(--text-muted);"><span id="video-hero-rating-text"><strong style="color:#f5a623;">★ <?= $displayRating ?></strong> (<span id="video-hero-review-cnt"><?= $cnt ?></span> ratings)</span> &bull; <span id="live-view-count"><?= number_format($video['view_count']) ?> views</span></p>
        </div>
      </div>
      <p style="margin-top:14px; color:var(--text-secondary); line-height:1.7;"><?= nl2br(clean($video['description'] ?? '')) ?></p>
    </div>
  </div>
</div>

<!-- REVIEWS SECTION -->
<section class="content-section" id="reviews-section">
  <div class="container">
    <div class="reviews-layout">
      <!-- Reviews List -->
      <div class="reviews-col">
        <h2 class="section-title"><span class="accent-bar"></span><i class="fas fa-star"></i> Reviews &amp; Ratings (<span id="video-reviews-count-badge"><?= $cnt ?></span>)</h2>
        <div id="video-reviews-cards-list">
          <?php if (empty($reviewList)): ?>
            <p class="text-muted" id="video-no-reviews-msg">No public reviews yet. Share your experience!</p>
          <?php else: ?>
            <?php foreach ($reviewList as $rev): ?>
            <?php $isCurrentUser = isLoggedIn() && ($rev['user_id'] == $_SESSION['user_id']); ?>
            <div class="review-card" <?= $isCurrentUser ? 'id="my-video-review-card"' : '' ?>>
              <div class="review-header">
                <div class="reviewer-avatar"><?= strtoupper(substr($rev['full_name'], 0, 1)) ?></div>
                <div>
                  <strong><?= clean($rev['full_name']) ?></strong>
                  <span class="review-uid">@<?= clean($rev['uid']) ?></span>
                </div>
                <div class="review-stars" style="color:#f5a623;"><?= str_repeat('★', $rev['rating']) ?></div>
                <span class="review-date"><?= date('M d, Y', strtotime($rev['created_at'])) ?></span>
              </div>
              <p class="review-text"><?= nl2br(clean($rev['review_text'])) ?></p>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Submit Review Form -->
      <div class="review-form-col">
        <h3 id="video-review-form-title"><i class="fas fa-pen"></i> <?= $myReview ? 'Modify Your Review' : 'Add Rating & Review' ?></h3>

        <div id="ajax-video-review-alert">
          <?php if ($reviewSuccess): ?>
            <div class="alert alert-success" style="margin-bottom:15px;"><?= clean($reviewSuccess) ?></div>
          <?php elseif ($reviewError): ?>
            <div class="alert alert-danger" style="margin-bottom:15px;"><?= clean($reviewError) ?></div>
          <?php endif; ?>
        </div>

        <?php if (isLoggedIn()): ?>
        <form id="ajax-video-review-form" action="#reviews-section" method="POST" class="review-form">
          <input type="hidden" name="media_type" value="video">
          <input type="hidden" name="media_id" value="<?= $video['id'] ?>">
          <div class="form-group">
            <label>Select Rating (1 to 5 Stars) *</label>
            <div class="star-rating">
              <?php $curRating = (int)($myReview['rating'] ?? 0); ?>
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" id="vstar<?= $i ?>" value="<?= $i ?>" <?= $curRating === $i ? 'checked' : '' ?> required>
                <label for="vstar<?= $i ?>">★</label>
              <?php endfor; ?>
            </div>
          </div>
          <div class="form-group">
            <label>Your Review Comments</label>
            <textarea name="review_text" rows="4" placeholder="How did you like this video?"><?= clean($myReview['review_text'] ?? '') ?></textarea>
          </div>
          <button type="submit" name="submit_review" id="video-review-submit-btn" class="btn btn-primary"><i class="fas fa-paper-plane"></i> <?= $myReview ? 'Update Review' : 'Submit Review' ?></button>
        </form>
        <?php else: ?>
          <div class="login-prompt">
            <p><i class="fas fa-lock"></i> Please <a href="<?= SITE_URL ?>/pages/login.php">login</a> to rate and review this video.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php if (isLoggedIn()): ?>
<script>
(function() {
  var video   = document.getElementById('main-video-player');
  var countEl = document.getElementById('live-view-count');
  var videoId = <?= (int)$video['id'] ?>;
  var played  = false;

  if (!video) return;

  video.addEventListener('play', function() {
    if (played) return;
    played = true;
    fetch('<?= SITE_URL ?>/api/track-play.php?type=video&id=' + videoId)
      .then(function(r){ return r.json(); })
      .then(function(data) {
        if (data.success && countEl) {
          countEl.textContent = data.formatted_count + ' views';
        }
      })
      .catch(function(){});
  });

  // --- Smooth AJAX Video Review Submission (No page reload, no jump to top) ---
  var videoReviewForm = document.getElementById('ajax-video-review-form');
  if (videoReviewForm) {
    videoReviewForm.addEventListener('submit', function(e) {
      e.preventDefault();
      var submitBtn = document.getElementById('video-review-submit-btn');
      var alertBox  = document.getElementById('ajax-video-review-alert');
      var formData  = new FormData(videoReviewForm);

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
        var formTitle = document.getElementById('video-review-form-title');
        if (formTitle) formTitle.innerHTML = '<i class="fas fa-pen"></i> Modify Your Review';

        // Update hero rating & stars
        var heroStars = document.getElementById('video-hero-stars');
        var heroText  = document.getElementById('video-hero-rating-text');
        var listBadge = document.getElementById('video-reviews-count-badge');

        if (heroStars) heroStars.innerHTML = res.stars_html;
        if (heroText) heroText.innerHTML = '<strong style="color:#f5a623;">★ ' + res.formatted_avg + '</strong> (<span id="video-hero-review-cnt">' + res.review_count + '</span> ratings)';
        if (listBadge) listBadge.textContent = res.review_count;

        // Update review card in list
        var noMsg = document.getElementById('video-no-reviews-msg');
        if (noMsg) noMsg.remove();

        var cardHtml = '<div class="review-card" id="my-video-review-card" style="border:1px solid rgba(229,9,20,0.3); transition:all 0.3s ease;">' +
          '<div class="review-header">' +
            '<div class="reviewer-avatar">' + res.review_card.avatar + '</div>' +
            '<div>' +
              '<strong>' + res.review_card.full_name + '</strong> ' +
              '<span class="review-uid">@' + res.review_card.uid + '</span>' +
            '</div>' +
            '<div class="review-stars" style="color:#f5a623;">' + res.review_card.stars_gold + '</div>' +
            '<span class="review-date">' + res.review_card.date + ' (Just now)</span>' +
          '</div>' +
          '<p class="review-text">' + res.review_card.text + '</p>' +
        '</div>';

        var existingCard = document.getElementById('my-video-review-card');
        var cardsList    = document.getElementById('video-reviews-cards-list');

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

