<?php
// =====================================================
// SOUND GROUP - Admin Albums Management
// admin/albums.php
// =====================================================
$adminTitle = 'Albums Management';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/admin-header.php';

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);
$error = '';

if ($action === 'delete' && $id > 0) {
    $stmt = $pdo->prepare("DELETE FROM albums WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Album deleted.');
    header('Location: ' . SITE_URL . '/admin/albums.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim($_POST['title'] ?? '');
    $artist_id = !empty($_POST['artist_id']) ? (int)$_POST['artist_id'] : null;
    $year      = (int)($_POST['year'] ?? date('Y'));
    $language  = trim($_POST['language'] ?? 'Hindi');
    $description = trim($_POST['description'] ?? '');

    if (empty($title)) {
        $error = 'Album title is required.';
    } else {
        $cover_image = $_POST['existing_cover'] ?? null;
        if (!empty($_FILES['cover_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $cover_image = 'alb_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['cover_file']['tmp_name'], COVER_PATH . $cover_image);
            }
        } elseif (!empty($_POST['cover_url'])) {
            $cover_image = trim($_POST['cover_url']);
        }

        if ($action === 'edit' && $id > 0) {
            $stmt = $pdo->prepare("UPDATE albums SET title=?, artist_id=?, year=?, language=?, cover_image=?, description=? WHERE id=?");
            $stmt->execute([$title, $artist_id, $year, $language, $cover_image, $description, $id]);
            setFlash('success', 'Album updated.');
            header('Location: ' . SITE_URL . '/admin/albums.php');
            exit;
        } else {
            $stmt = $pdo->prepare("INSERT INTO albums (title, artist_id, year, language, cover_image, description) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$title, $artist_id, $year, $language, $cover_image, $description]);
            setFlash('success', 'Album created.');
            header('Location: ' . SITE_URL . '/admin/albums.php');
            exit;
        }
    }
}

$artists = $pdo->query("SELECT id, name FROM artists ORDER BY name ASC")->fetchAll();
$langs   = $pdo->query("SELECT name FROM categories WHERE type='language' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$editItem = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM albums WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$q = trim($_GET['search'] ?? '');
$where = ['1=1'];
$params = [];
if ($q) {
    $where[] = "(al.title LIKE ? OR a.name LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$stmt = $pdo->prepare("
    SELECT al.*, a.name as artist_name, COUNT(m.id) as track_count
    FROM albums al
    LEFT JOIN artists a ON al.artist_id = a.id
    LEFT JOIN music m ON m.album_id = al.id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY al.id
    ORDER BY al.year DESC
");
$stmt->execute($params);
$albums = $stmt->fetchAll();
?>

<div class="admin-two-col">
  <!-- Add/Edit Album -->
  <div class="admin-box">
    <div class="box-head">
      <h3><?= $action === 'edit' ? '✏️ Edit Album' : '➕ Create New Album' ?></h3>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= clean($error) ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="admin-form">
      <input type="hidden" name="existing_cover" value="<?= clean($editItem['cover_image'] ?? '') ?>">
      <div class="form-group">
        <label>Album Title *</label>
        <input type="text" name="title" value="<?= clean($editItem['title'] ?? '') ?>" required placeholder="e.g. Aashiqui 2">
      </div>
      <div class="form-group">
        <label>Primary Artist</label>
        <select name="artist_id">
          <option value="">-- Multiple / Soundtrack --</option>
          <?php foreach ($artists as $a): ?>
            <option value="<?= $a['id'] ?>" <?= (isset($editItem) && $editItem['artist_id'] == $a['id']) ? 'selected' : '' ?>>
              <?= clean($a['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Release Year *</label>
          <input type="number" name="year" min="1900" max="2099" value="<?= clean($editItem['year'] ?? date('Y')) ?>" required>
        </div>
        <div class="form-group">
          <label>Language</label>
          <select name="language">
            <?php foreach ($langs as $l): ?>
              <option value="<?= clean($l) ?>" <?= (isset($editItem) && $editItem['language'] === $l) ? 'selected' : '' ?>><?= clean($l) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Album Artwork (Upload or URL)</label>
        <input type="file" name="cover_file" accept="image/*">
        <input type="text" name="cover_url" value="<?= clean($editItem['cover_image'] ?? '') ?>" placeholder="Or enter Artwork URL / Filename..." style="margin-top:6px;">
        <?php if (!empty($editItem['cover_image'])): ?>
          <small style="color:var(--text-muted); display:block; margin-top:4px;">Current: <code><?= clean($editItem['cover_image']) ?></code></small>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="2"><?= clean($editItem['description'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary"><?= $action === 'edit' ? 'Update Album' : 'Create Album' ?></button>
      <?php if ($action === 'edit'): ?>
        <a href="<?= SITE_URL ?>/admin/albums.php" class="btn btn-outline">Cancel</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Albums List -->
  <div class="admin-box">
    <div class="box-head">
      <h3><i class="fas fa-compact-disc"></i> Albums List (<?= count($albums) ?>)</h3>
    </div>
    
    <form method="GET" style="display:flex; gap:8px; margin-bottom:15px;">
      <input type="text" name="search" placeholder="Search album or artist..." value="<?= clean($q) ?>" style="padding:6px 12px; background:var(--dark-3); border:1px solid var(--border); color:#fff; border-radius:6px; flex:1;">
      <button type="submit" class="btn btn-outline" style="padding:6px 12px;">Search</button>
      <?php if ($q): ?><a href="<?= SITE_URL ?>/admin/albums.php" class="btn btn-outline" style="padding:6px 12px;">Reset</a><?php endif; ?>
    </form>

    <table class="admin-table">
      <thead>
        <tr>
          <th>Cover</th>
          <th>Title</th>
          <th>Artist</th>
          <th>Year</th>
          <th>Tracks</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($albums)): ?>
          <tr><td colspan="6" style="text-align:center; padding:20px; color:var(--text-muted);">No albums found.</td></tr>
        <?php else: ?>
          <?php foreach ($albums as $al): ?>
          <tr>
            <td>
              <img src="<?= resolveMediaUrl($al['cover_image'], 'cover') ?>" width="38" height="38" style="border-radius:6px; object-fit:cover; border:1px solid var(--border);" onerror="this.onerror=null; this.src='<?= SITE_URL ?>/assets/images/covers/cover_pop.jpg';">
            </td>
            <td><strong><?= clean($al['title']) ?></strong></td>
            <td><?= clean($al['artist_name'] ?? 'Soundtrack') ?></td>
            <td><?= $al['year'] ?></td>
            <td><span class="badge-tag"><?= $al['track_count'] ?> tracks</span></td>
            <td style="white-space:nowrap;">
              <a href="<?= SITE_URL ?>/pages/album-detail.php?id=<?= $al['id'] ?>" target="_blank" class="tbl-btn" title="View Public Album"><i class="fas fa-eye"></i></a>
              <a href="<?= SITE_URL ?>/admin/albums.php?action=edit&id=<?= $al['id'] ?>" class="tbl-btn" title="Edit"><i class="fas fa-edit"></i></a>
              <a href="<?= SITE_URL ?>/admin/albums.php?action=delete&id=<?= $al['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete this album?')" title="Delete"><i class="fas fa-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

