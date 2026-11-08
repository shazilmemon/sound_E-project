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

$filterStatus = trim($_GET['status'] ?? '');
$q = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
$params = [];

if ($filterStatus && in_array($filterStatus, ['pending', 'approved', 'rejected'])) {
    $where[] = "r.status = ?";
    $params[] = $filterStatus;
}
if ($q) {
    $where[] = "(u.full_name LIKE ? OR u.user_id LIKE ? OR r.review_text LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE " . implode(' AND ', $where) . "
");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

$stmt = $pdo->prepare("
    SELECT r.*, u.full_name, u.user_id as username,
           CASE 
               WHEN r.media_type = 'music' THEN (SELECT title FROM music WHERE id = r.media_id)
               WHEN r.media_type = 'video' THEN (SELECT title FROM videos WHERE id = r.media_id)
           END as media_title
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY r.id DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Count badges
$cntPending  = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE status='pending'")->fetchColumn();
$cntApproved = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE status='approved'")->fetchColumn();
$cntRejected = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE status='rejected'")->fetchColumn();
?>

<div class="admin-box">
  <div class="box-head">
    <h3><i class="fas fa-star"></i> Moderate Reviews &amp; Ratings</h3>
  </div>

  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
    <!-- Status Filter Pills -->
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <a href="<?= SITE_URL ?>/admin/reviews.php" class="tbl-btn <?= empty($filterStatus) ? 'active' : '' ?>">All (<?= $cntPending + $cntApproved + $cntRejected ?>)</a>
      <a href="<?= SITE_URL ?>/admin/reviews.php?status=pending" class="tbl-btn <?= $filterStatus === 'pending' ? 'active' : '' ?>" style="<?= $cntPending > 0 ? 'border-color:#f5a623; color:#f5a623;' : '' ?>">
        Pending <?php if ($cntPending > 0): ?><span class="sb-badge" style="margin-left:4px;"><?= $cntPending ?></span><?php endif; ?>
      </a>
      <a href="<?= SITE_URL ?>/admin/reviews.php?status=approved" class="tbl-btn <?= $filterStatus === 'approved' ? 'active' : '' ?>">Approved (<?= $cntApproved ?>)</a>
      <a href="<?= SITE_URL ?>/admin/reviews.php?status=rejected" class="tbl-btn <?= $filterStatus === 'rejected' ? 'active' : '' ?>">Rejected (<?= $cntRejected ?>)</a>
    </div>

    <!-- Search Form -->
    <form method="GET" style="display:flex; gap:8px;">
      <?php if ($filterStatus): ?><input type="hidden" name="status" value="<?= clean($filterStatus) ?>"><?php endif; ?>
      <input type="text" name="search" placeholder="Search user or text..." value="<?= clean($q) ?>" style="padding:6px 12px; background:var(--dark-3); border:1px solid var(--border); color:#fff; border-radius:6px;">
      <button type="submit" class="btn btn-outline" style="padding:6px 12px;">Search</button>
      <?php if ($q): ?><a href="<?= SITE_URL ?>/admin/reviews.php<?= $filterStatus ? '?status='.urlencode($filterStatus) : '' ?>" class="btn btn-outline" style="padding:6px 12px;">Reset</a><?php endif; ?>
    </form>
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
        <tr><td colspan="9" style="text-align:center; padding:25px; color:var(--text-muted);">No reviews found.</td></tr>
      <?php else: ?>
        <?php foreach ($reviews as $rev): ?>
        <?php 
          $itemUrl = ($rev['media_type'] === 'video') 
            ? SITE_URL . '/pages/video-detail.php?id=' . $rev['media_id']
            : SITE_URL . '/pages/music-detail.php?id=' . $rev['media_id'];
        ?>
        <tr>
          <td>#<?= $rev['id'] ?></td>
          <td>
            <strong><?= clean($rev['full_name']) ?></strong><br>
            <small style="color:var(--text-muted);">@<?= clean($rev['username']) ?></small>
          </td>
          <td><span class="badge-tag"><?= strtoupper(clean($rev['media_type'])) ?></span></td>
          <td>
            <a href="<?= $itemUrl ?>" target="_blank" style="color:#fff; font-weight:600; text-decoration:none;" title="View item on website">
              <?= clean($rev['media_title'] ?? 'N/A') ?> <i class="fas fa-external-link-alt" style="font-size:10px; color:var(--primary);"></i>
            </a>
          </td>
          <td><span style="color:#f5a623;"><?= str_repeat('★', $rev['rating']) ?></span> <small>(<?= $rev['rating'] ?>/5)</small></td>
          <td style="max-width:260px; font-size:12.5px; line-height:1.5;"><?= nl2br(clean($rev['review_text'])) ?></td>
          <td>
            <span class="badge-status-<?= $rev['status'] ?>">
              <?= ucfirst($rev['status']) ?>
            </span>
          </td>
          <td style="white-space:nowrap;"><?= date('d M Y', strtotime($rev['created_at'])) ?></td>
          <td style="white-space:nowrap;">
            <?php if ($rev['status'] !== 'approved'): ?>
              <a href="<?= SITE_URL ?>/admin/reviews.php?action=approve&id=<?= $rev['id'] ?>" class="tbl-btn" style="color:#00b894;" title="Approve & Publish"><i class="fas fa-check"></i></a>
            <?php endif; ?>
            <?php if ($rev['status'] !== 'rejected'): ?>
              <a href="<?= SITE_URL ?>/admin/reviews.php?action=reject&id=<?= $rev['id'] ?>" class="tbl-btn" style="color:#f5a623;" title="Reject"><i class="fas fa-ban"></i></a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/admin/reviews.php?action=delete&id=<?= $rev['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete this review permanently?')" title="Delete"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="admin-pagination" style="display:flex; justify-content:center; align-items:center; gap:8px; margin-top:20px;">
    <?php if ($page > 1): ?>
      <a href="?search=<?= urlencode($q) ?>&status=<?= urlencode($filterStatus) ?>&p=<?= $page - 1 ?>" class="tbl-btn">&laquo; Prev</a>
    <?php endif; ?>
    
    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
      <a href="?search=<?= urlencode($q) ?>&status=<?= urlencode($filterStatus) ?>&p=<?= $i ?>" class="tbl-btn <?= $i === $page ? 'active' : '' ?>" style="<?= $i === $page ? 'background:var(--primary); color:#fff; border-color:var(--primary);' : '' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
      <a href="?search=<?= urlencode($q) ?>&status=<?= urlencode($filterStatus) ?>&p=<?= $page + 1 ?>" class="tbl-btn">Next &raquo;</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

