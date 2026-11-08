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
        if ($action === 'edit' && $id > 0) {
            $stmt = $pdo->prepare("UPDATE categories SET type = ?, name = ? WHERE id = ?");
            $stmt->execute([$type, $name, $id]);
            setFlash('success', 'Category updated successfully.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (type, name) VALUES (?, ?)");
            $stmt->execute([$type, $name]);
            setFlash('success', 'Category added successfully.');
        }
        header('Location: ' . SITE_URL . '/admin/categories.php');
        exit;
    }
}

$editItem = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$filterType = trim($_GET['type'] ?? '');
$where = '1=1';
$params = [];
if ($filterType) {
    $where = 'type = ?';
    $params[] = $filterType;
}
$stmt = $pdo->prepare("SELECT * FROM categories WHERE $where ORDER BY type ASC, name ASC");
$stmt->execute($params);
$categories = $stmt->fetchAll();
?>

<div class="admin-two-col">
  <!-- Add / Edit Category Form -->
  <div class="admin-box">
    <div class="box-head">
      <h3><?= $action === 'edit' ? '✏️ Edit Category' : '➕ Create New Category' ?></h3>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= clean($error) ?></div>
    <?php endif; ?>
    <form method="POST" class="admin-form">
      <div class="form-group">
        <label>Category Type *</label>
        <select name="type" required>
          <option value="genre" <?= (isset($editItem) && $editItem['type'] === 'genre') ? 'selected' : '' ?>>Genre (e.g. Pop, Rock, Classical, Jazz)</option>
          <option value="language" <?= (isset($editItem) && $editItem['type'] === 'language') ? 'selected' : '' ?>>Language (e.g. Hindi, English, Punjabi, Telugu)</option>
          <option value="year" <?= (isset($editItem) && $editItem['type'] === 'year') ? 'selected' : '' ?>>Year / Era (e.g. 2024, 2023, 90s, 2000s)</option>
        </select>
      </div>
      <div class="form-group">
        <label>Category Value / Name *</label>
        <input type="text" name="name" value="<?= clean($editItem['name'] ?? '') ?>" required placeholder="e.g. Sufi, Spanish, 2025">
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $action === 'edit' ? 'Update Category' : 'Save Category' ?></button>
      <?php if ($action === 'edit'): ?>
        <a href="<?= SITE_URL ?>/admin/categories.php" class="btn btn-outline">Cancel</a>
      <?php endif; ?>
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
      <h3><i class="fas fa-tags"></i> Existing Categories (<?= count($categories) ?>)</h3>
    </div>

    <!-- Type filter tabs -->
    <div style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap;">
      <a href="<?= SITE_URL ?>/admin/categories.php" class="tbl-btn <?= empty($filterType) ? 'active' : '' ?>">All</a>
      <a href="<?= SITE_URL ?>/admin/categories.php?type=genre" class="tbl-btn <?= $filterType === 'genre' ? 'active' : '' ?>">Genres</a>
      <a href="<?= SITE_URL ?>/admin/categories.php?type=language" class="tbl-btn <?= $filterType === 'language' ? 'active' : '' ?>">Languages</a>
      <a href="<?= SITE_URL ?>/admin/categories.php?type=year" class="tbl-btn <?= $filterType === 'year' ? 'active' : '' ?>">Years</a>
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
        <?php if (empty($categories)): ?>
          <tr><td colspan="3" style="text-align:center; padding:20px; color:var(--text-muted);">No categories found.</td></tr>
        <?php else: ?>
          <?php foreach ($categories as $cat): ?>
          <tr>
            <td><span class="badge-tag"><?= strtoupper(clean($cat['type'])) ?></span></td>
            <td><strong><?= clean($cat['name']) ?></strong></td>
            <td style="white-space:nowrap;">
              <a href="<?= SITE_URL ?>/admin/categories.php?action=edit&id=<?= $cat['id'] ?>" class="tbl-btn" title="Edit"><i class="fas fa-edit"></i></a>
              <a href="<?= SITE_URL ?>/admin/categories.php?action=delete&id=<?= $cat['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete this category?')" title="Delete"><i class="fas fa-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

