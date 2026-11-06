<?php
// =====================================================
// SOUND GROUP - Admin Reviews Management
// admin/reviews.php
// =====================================================
$adminTitle = 'Reviews & Ratings';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/admin-header.php';

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

if ($action === 'approve' && $id > 0) {
    $stmt = $pdo->prepare("UPDATE reviews SET status='approved' WHERE id=?");
    $stmt->execute([$id]);
    setFlash('success', 'Review approved and published.');
    header('Location: ' . SITE_URL . '/admin/reviews.php');
    exit;
}

if ($action === 'reject' && $id > 0) {
    $stmt = $pdo->prepare("UPDATE reviews SET status='rejected' WHERE id=?");
    $stmt->execute([$id]);
    setFlash('success', 'Review rejected.');
    header('Location: ' . SITE_URL . '/admin/reviews.php');
    exit;
}

if ($action === 'delete' && $id > 0) {
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id=?");
    $stmt->execute([$id]);
    setFlash('success', 'Review deleted.');
    header('Location: ' . SITE_URL . '/admin/reviews.php');
    exit;
}

$reviews = $pdo->query("
    SELECT r.*, u.full_name, u.user_id as username,
           CASE 
               WHEN r.media_type = 'music' THEN (SELECT title FROM music WHERE id = r.media_id)
               WHEN r.media_type = 'video' THEN (SELECT title FROM videos WHERE id = r.media_id)
           END as media_title
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.id DESC
")->fetchAll();
?>

<div class="admin-box">
  <div class="box-head">
    <h3><i class="fas fa-star"></i> Moderate Reviews &amp; Ratings</h3>
  </div>

  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>Type</th>
        <th>Item Title</th>
        <th>Rating</th>
        <th>Review Text</th>
        <th>Status</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($reviews)): ?>
        <tr><td colspan="9" style="text-align:center; padding:20px; color:var(--text-muted);">No reviews submitted yet.</td></tr>
      <?php else: ?>
        <?php foreach ($reviews as $rev): ?>
        <tr>
          <td><?= $rev['id'] ?></td>
          <td>
            <strong><?= clean($rev['full_name']) ?></strong><br>
            <small style="color:var(--text-muted);">@<?= clean($rev['username']) ?></small>
          </td>
          <td><span class="badge-tag"><?= strtoupper(clean($rev['media_type'])) ?></span></td>
          <td><strong><?= clean($rev['media_title'] ?? 'N/A') ?></strong></td>
          <td><span style="color:#f5a623;"><?= str_repeat('★', $rev['rating']) ?></span> (<?= $rev['rating'] ?>/5)</td>
          <td style="max-width:240px;"><?= nl2br(clean($rev['review_text'])) ?></td>
          <td>
            <span class="badge-status-<?= $rev['status'] ?>">
              <?= ucfirst($rev['status']) ?>
            </span>
          </td>
          <td><?= date('d M Y', strtotime($rev['created_at'])) ?></td>
          <td>
            <?php if ($rev['status'] !== 'approved'): ?>
              <a href="<?= SITE_URL ?>/admin/reviews.php?action=approve&id=<?= $rev['id'] ?>" class="tbl-btn" style="color:#00b894;" title="Approve"><i class="fas fa-check"></i></a>
            <?php endif; ?>
            <?php if ($rev['status'] !== 'rejected'): ?>
              <a href="<?= SITE_URL ?>/admin/reviews.php?action=reject&id=<?= $rev['id'] ?>" class="tbl-btn" style="color:#f5a623;" title="Reject"><i class="fas fa-times"></i></a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/admin/reviews.php?action=delete&id=<?= $rev['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete this review permanently?')" title="Delete"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

