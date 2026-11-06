<?php
// =====================================================
// SOUND GROUP - Admin Category Management
// admin/categories.php
// Requirement: "Create Categories viz. YEAR, ARTIST, ALBUM etc."
// =====================================================
$adminTitle = 'Categories Management';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/admin-header.php';

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);
$error = '';

if ($action === 'delete' && $id > 0) {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Category deleted.');
    header('Location: ' . SITE_URL . '/admin/categories.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = trim($_POST['type'] ?? '');
    $name = trim($_POST['name'] ?? '');

    if (empty($type) || empty($name)) {
        $error = 'Category Type and Name are required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (type, name) VALUES (?, ?)");
        $stmt->execute([$type, $name]);
        setFlash('success', 'Category added successfully.');
        header('Location: ' . SITE_URL . '/admin/categories.php');
        exit;
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY type ASC, name ASC")->fetchAll();
?>

<div class="admin-two-col">
  <!-- Add Category Form -->
  <div class="admin-box">
    <div class="box-head">
      <h3>➕ Create New Category</h3>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= clean($error) ?></div>
    <?php endif; ?>
    <form method="POST" class="admin-form">
      <div class="form-group">
        <label>Category Type *</label>
        <select name="type" required>
          <option value="genre">Genre (e.g. Pop, Rock, Classical, Jazz)</option>
          <option value="language">Language (e.g. Hindi, English, Punjabi, Telugu)</option>
          <option value="year">Year / Era (e.g. 2024, 2023, 90s, 2000s)</option>
        </select>
      </div>
      <div class="form-group">
        <label>Category Value / Name *</label>
        <input type="text" name="name" required placeholder="e.g. Sufi, Spanish, 2025">
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Save Category</button>
    </form>

    <div style="margin-top:20px; font-size:12px; color:var(--text-muted); line-height:1.6;">
      <p>💡 <em>Artists</em> aur <em>Albums</em> bhi project requirements ke mutabiq dedicated sections se manage kiye jaate hain:</p>
      <div style="display:flex; gap:10px; margin-top:8px;">
        <a href="<?= SITE_URL ?>/admin/artists.php" class="btn btn-outline" style="font-size:12px;"><i class="fas fa-microphone"></i> Manage Artists</a>
        <a href="<?= SITE_URL ?>/admin/albums.php" class="btn btn-outline" style="font-size:12px;"><i class="fas fa-compact-disc"></i> Manage Albums</a>
      </div>
    </div>
  </div>

  <!-- Categories List -->
  <div class="admin-box">
    <div class="box-head">
      <h3><i class="fas fa-tags"></i> Existing Categories</h3>
    </div>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Type</th>
          <th>Name / Value</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $cat): ?>
        <tr>
          <td><span class="badge-tag"><?= strtoupper(clean($cat['type'])) ?></span></td>
          <td><strong><?= clean($cat['name']) ?></strong></td>
          <td>
            <a href="<?= SITE_URL ?>/admin/categories.php?action=delete&id=<?= $cat['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete this category?')" title="Delete"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

