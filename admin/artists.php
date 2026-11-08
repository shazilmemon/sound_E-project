<?php
// =====================================================
// SOUND GROUP - Admin Artists Management
// admin/artists.php
// =====================================================
$adminTitle = 'Artists Management';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/admin-header.php';

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);
$error = '';

if ($action === 'delete' && $id > 0) {
    $stmt = $pdo->prepare("DELETE FROM artists WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Artist removed.');
    header('Location: ' . SITE_URL . '/admin/artists.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $language = trim($_POST['language'] ?? 'Hindi');
    $bio = trim($_POST['bio'] ?? '');

    if (empty($name)) {
        $error = 'Artist name is required.';
    } else {
        $image = $_POST['existing_image'] ?? null;
        if (!empty($_FILES['image_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $image = 'art_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['image_file']['tmp_name'], __DIR__ . '/../assets/images/artists/' . $image);
            }
        } elseif (!empty($_POST['image_url'])) {
            $image = trim($_POST['image_url']);
        }

        if ($action === 'edit' && $id > 0) {
            $stmt = $pdo->prepare("UPDATE artists SET name=?, language=?, bio=?, image=? WHERE id=?");
            $stmt->execute([$name, $language, $bio, $image, $id]);
            setFlash('success', 'Artist updated successfully.');
            header('Location: ' . SITE_URL . '/admin/artists.php');
            exit;
        } else {
            $stmt = $pdo->prepare("INSERT INTO artists (name, language, bio, image) VALUES (?,?,?,?)");
            $stmt->execute([$name, $language, $bio, $image]);
            setFlash('success', 'Artist added successfully.');
            header('Location: ' . SITE_URL . '/admin/artists.php');
            exit;
        }
    }
}

$langs = $pdo->query("SELECT name FROM categories WHERE type='language' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

$editItem = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM artists WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$q = trim($_GET['search'] ?? '');
$where = ['1=1'];
$params = [];
if ($q) {
    $where[] = "a.name LIKE ?";
    $params[] = "%$q%";
}

$stmt = $pdo->prepare("
    SELECT a.*, COUNT(m.id) as songs_count
    FROM artists a
    LEFT JOIN music m ON m.artist_id = a.id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY a.id
    ORDER BY a.name ASC
");
$stmt->execute($params);
$artists = $stmt->fetchAll();
?>

<div class="admin-two-col">
  <!-- Add / Edit Artist Form -->
  <div class="admin-box">
    <div class="box-head">
      <h3><?= $action === 'edit' ? '✏️ Edit Artist' : '➕ Add New Artist' ?></h3>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= clean($error) ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="admin-form">
      <input type="hidden" name="existing_image" value="<?= clean($editItem['image'] ?? '') ?>">
      <div class="form-group">
        <label>Artist Name *</label>
        <input type="text" name="name" value="<?= clean($editItem['name'] ?? '') ?>" required placeholder="e.g. Arijit Singh">
      </div>
      <div class="form-group">
        <label>Primary Language</label>
        <select name="language">
          <?php foreach ($langs as $l): ?>
            <option value="<?= clean($l) ?>" <?= (isset($editItem) && $editItem['language'] === $l) ? 'selected' : '' ?>><?= clean($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Photo / Avatar (Upload or URL)</label>
        <input type="file" name="image_file" accept="image/*">
        <input type="text" name="image_url" value="<?= clean($editItem['image'] ?? '') ?>" placeholder="Or enter Image URL / Filename..." style="margin-top:6px;">
        <?php if (!empty($editItem['image'])): ?>
          <small style="color:var(--text-muted); display:block; margin-top:4px;">Current: <code><?= clean($editItem['image']) ?></code></small>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label>Biography</label>
        <textarea name="bio" rows="3"><?= clean($editItem['bio'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary"><?= $action === 'edit' ? 'Update Artist' : 'Save Artist' ?></button>
      <?php if ($action === 'edit'): ?>
        <a href="<?= SITE_URL ?>/admin/artists.php" class="btn btn-outline">Cancel</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Artists List -->
  <div class="admin-box">
    <div class="box-head">
      <h3><i class="fas fa-microphone"></i> Artists List (<?= count($artists) ?>)</h3>
    </div>

    <form method="GET" style="display:flex; gap:8px; margin-bottom:15px;">
      <input type="text" name="search" placeholder="Search artist name..." value="<?= clean($q) ?>" style="padding:6px 12px; background:var(--dark-3); border:1px solid var(--border); color:#fff; border-radius:6px; flex:1;">
      <button type="submit" class="btn btn-outline" style="padding:6px 12px;">Search</button>
      <?php if ($q): ?><a href="<?= SITE_URL ?>/admin/artists.php" class="btn btn-outline" style="padding:6px 12px;">Reset</a><?php endif; ?>
    </form>

    <table class="admin-table">
      <thead>
        <tr>
          <th>Photo</th>
          <th>Name</th>
          <th>Language</th>
          <th>Tracks</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($artists)): ?>
          <tr><td colspan="5" style="text-align:center; padding:20px; color:var(--text-muted);">No artists found.</td></tr>
        <?php else: ?>
          <?php foreach ($artists as $a): ?>
          <tr>
            <td>
              <img src="<?= resolveMediaUrl($a['image'], 'artist') ?>" width="38" height="38" style="border-radius:50%; object-fit:cover; border:1px solid var(--border);" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=100';">
            </td>
            <td><strong><?= clean($a['name']) ?></strong></td>
            <td><span class="badge-tag"><?= clean($a['language']) ?></span></td>
            <td>
              <a href="<?= SITE_URL ?>/admin/music.php?search=<?= urlencode($a['name']) ?>" title="View tracks by this artist" style="color:var(--primary); font-weight:600;">
                <?= $a['songs_count'] ?> songs <i class="fas fa-arrow-right" style="font-size:10px;"></i>
              </a>
            </td>
            <td style="white-space:nowrap;">
              <a href="<?= SITE_URL ?>/pages/artist-detail.php?id=<?= $a['id'] ?>" target="_blank" class="tbl-btn" title="View Public Artist Page"><i class="fas fa-eye"></i></a>
              <a href="<?= SITE_URL ?>/admin/artists.php?action=edit&id=<?= $a['id'] ?>" class="tbl-btn" title="Edit"><i class="fas fa-edit"></i></a>
              <a href="<?= SITE_URL ?>/admin/artists.php?action=delete&id=<?= $a['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete this artist?')" title="Delete"><i class="fas fa-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

