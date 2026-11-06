<?php
// =====================================================
// SOUND GROUP - User Reviews Management
// pages/my-reviews.php
// Requirement: "USER should be able to: ADD/Modify Reviews, ADD/Modify Ratings"
// =====================================================
require_once __DIR__ . '/../includes/auth.php';
requireLogin('/pages/login.php');

$pdo = getDB();
$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$revId = (int)($_GET['id'] ?? 0);
$success = '';
$error = '';

// Delete user's own review
if ($action === 'delete' && $revId > 0) {
    $st = $pdo->prepare("DELETE FROM reviews WHERE id=? AND user_id=?");
    $st->execute([$revId, $userId]);
    setFlash('success', 'Review deleted successfully.');
    header('Location: ' . SITE_URL . '/pages/my-reviews.php');
    exit;
}

// Modify existing review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_review'])) {
    $editId = (int)($_POST['review_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $text   = trim($_POST['review_text'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Rating must be between 1 and 5 stars.';
    } else {
        $st = $pdo->prepare("UPDATE reviews SET rating=?, review_text=?, status='approved', updated_at=NOW() WHERE id=? AND user_id=?");
        $st->execute([$rating, $text, $editId, $userId]);
        setFlash('success', 'Your review and rating have been updated successfully.');
        header('Location: ' . SITE_URL . '/pages/my-reviews.php');
        exit;
    }
}

// Fetch all reviews of this user
$stmt = $pdo->prepare("
    SELECT r.*,
           CASE 
               WHEN r.media_type = 'music' THEN (SELECT title FROM music WHERE id = r.media_id)
               WHEN r.media_type = 'video' THEN (SELECT title FROM videos WHERE id = r.media_id)
           END as item_title
    FROM reviews r
    WHERE r.user_id = ?
    ORDER BY r.updated_at DESC
");
$stmt->execute([$userId]);
$myReviews = $stmt->fetchAll();

$pageTitle = 'My Reviews & Ratings';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1><i class="fas fa-star"></i> My Reviews &amp; Ratings</h1>
    <p>Manage and modify all your submitted feedback</p>
  </div>
</div>

<section class="content-section">
  <div class="container" style="max-width:850px;">
    <?php if (empty($myReviews)): ?>
      <div class="empty-state">
        <i class="fas fa-comment-alt fa-3x"></i>
        <h3>You have not submitted any reviews yet</h3>
        <p>Explore our <a href="<?= SITE_URL ?>/pages/music.php">music</a> or <a href="<?= SITE_URL ?>/pages/videos.php">videos</a> to leave your ratings!</p>
      </div>
    <?php else: ?>
      <div style="display:flex; flex-direction:column; gap:20px;">
        <?php foreach ($myReviews as $rev): ?>
        <div class="review-card" style="padding:22px; background:var(--dark-card); border-radius:12px; border:1px solid var(--border);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <div>
              <span class="badge-tag"><?= strtoupper(clean($rev['media_type'])) ?></span>
              <strong style="font-size:16px; margin-left:8px;"><?= clean($rev['item_title'] ?? 'Item') ?></strong>
            </div>
            <div>
              <span class="badge-status-<?= $rev['status'] ?>"><?= ucfirst($rev['status']) ?></span>
            </div>
          </div>

          <!-- Edit inline form -->
          <form method="POST" class="auth-form" style="margin-top:10px;">
            <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
            <div class="form-group" style="margin-bottom:10px;">
              <label style="font-size:12px;">Modify Rating (1-5 Stars):</label>
              <div class="star-rating">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                  <input type="radio" name="rating" id="mr_<?= $rev['id'] ?>_<?= $i ?>" value="<?= $i ?>" <?= $rev['rating'] == $i ? 'checked' : '' ?> required>
                  <label for="mr_<?= $rev['id'] ?>_<?= $i ?>">★</label>
                <?php endfor; ?>
              </div>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
              <label style="font-size:12px;">Modify Review Comments:</label>
              <textarea name="review_text" rows="2" style="width:100%; background:var(--dark-3); border:1px solid var(--border); color:#fff; border-radius:6px; padding:10px;"><?= clean($rev['review_text']) ?></textarea>
            </div>

            <div style="display:flex; gap:10px;">
              <button type="submit" name="update_review" class="btn btn-primary" style="padding:6px 16px; font-size:12px;"><i class="fas fa-edit"></i> Update Review &amp; Rating</button>
              <a href="<?= SITE_URL ?>/pages/my-reviews.php?action=delete&id=<?= $rev['id'] ?>" class="btn btn-outline" style="padding:6px 16px; font-size:12px; color:#e50914;" onclick="return confirm('Are you sure you want to remove this review?');"><i class="fas fa-trash"></i> Delete</a>
            </div>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

