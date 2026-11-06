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
$editItem = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM albums WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$albums = $pdo->query("
    SELECT al.*, a.name as artist_name, COUNT(m.id) as track_count
    FROM albums al
    LEFT JOIN artists a ON al.artist_id = a.id
    LEFT JOIN music m ON m.album_id = al.id
    GROUP BY al.id
    ORDER BY al.year DESC
")->fetchAll();
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
          <input type="text" name="language" value="<?= clean($editItem['language'] ?? 'Hindi') ?>">
        </div>
      </div>
      <div class="form-group">
        <label>Album Artwork</label>
        <input type="file" name="cover_file" accept="image/*">
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
      <h3><i class="fas fa-compact-disc"></i> Albums List</h3>
    </div>
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
        <?php foreach ($albums as $al): ?>
        <tr>
          <td>
            <img src="<?= $al['cover_image'] ? SITE_URL.'/assets/images/covers/'.clean($al['cover_image']) : 'https://picsum.photos/seed/al'.$al['id'].'/40/40' ?>" width="35" height="35" style="border-radius:4px; object-fit:cover;">
          </td>
          <td><strong><?= clean($al['title']) ?></strong></td>
          <td><?= clean($al['artist_name'] ?? 'Soundtrack') ?></td>
          <td><?= $al['year'] ?></td>
          <td><?= $al['track_count'] ?> tracks</td>
          <td>
            <a href="<?= SITE_URL ?>/admin/albums.php?action=edit&id=<?= $al['id'] ?>" class="tbl-btn" title="Edit"><i class="fas fa-edit"></i></a>
            <a href="<?= SITE_URL ?>/admin/albums.php?action=delete&id=<?= $al['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete this album?')" title="Delete"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

