<?php
// =====================================================
// SOUND GROUP - Admin Video Management
// admin/videos.php
// =====================================================
$adminTitle = 'Video Management';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/admin-header.php';

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);
$error = '';

// Handle Delete
if ($action === 'delete' && $id > 0) {
    $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Video deleted successfully.');
    header('Location: ' . SITE_URL . '/admin/videos.php');
    exit;
}

// Handle Add / Edit POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $artist_id   = !empty($_POST['artist_id']) ? (int)$_POST['artist_id'] : null;
    $album_id    = !empty($_POST['album_id']) ? (int)$_POST['album_id'] : null;
    $year        = (int)($_POST['year'] ?? date('Y'));
    $genre       = trim($_POST['genre'] ?? '');
    $language    = trim($_POST['language'] ?? '');
    $duration    = trim($_POST['duration'] ?? '0:00');
    $video_url   = trim($_POST['file_path'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_new      = isset($_POST['is_new']) ? 1 : 0;
    $status      = $_POST['status'] ?? 'active';

    if (empty($title) || empty($genre) || empty($language) || empty($year)) {
        $error = 'Title, Genre, Language, and Year are required.';
    } else {
        $cover_image = $_POST['existing_cover'] ?? null;
        if (!empty($_FILES['cover_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $cover_image = 'vid_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['cover_file']['tmp_name'], COVER_PATH . $cover_image);
            }
        }

        if ($action === 'edit' && $id > 0) {
            $stmt = $pdo->prepare("UPDATE videos SET title=?, artist_id=?, album_id=?, year=?, genre=?, language=?, cover_image=?, file_path=?, duration=?, description=?, is_new=?, status=? WHERE id=?");
            $stmt->execute([$title, $artist_id, $album_id, $year, $genre, $language, $cover_image, $video_url, $duration, $description, $is_new, $status, $id]);
            setFlash('success', 'Video updated successfully.');
            header('Location: ' . SITE_URL . '/admin/videos.php');
            exit;
        } else {
            $stmt = $pdo->prepare("INSERT INTO videos (title, artist_id, album_id, year, genre, language, cover_image, file_path, duration, description, is_new, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$title, $artist_id, $album_id, $year, $genre, $language, $cover_image, $video_url, $duration, $description, $is_new, $status]);
            setFlash('success', 'Video added successfully.');
            header('Location: ' . SITE_URL . '/admin/videos.php');
            exit;
        }
    }
}

$artists = $pdo->query("SELECT id, name FROM artists ORDER BY name ASC")->fetchAll();
$albums  = $pdo->query("SELECT id, title FROM albums ORDER BY title ASC")->fetchAll();
$genres  = $pdo->query("SELECT name FROM categories WHERE type='genre' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$langs   = $pdo->query("SELECT name FROM categories WHERE type='language' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

$editItem = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<div class="admin-box">
  <div class="box-head">
    <h3><?= $action === 'edit' ? '✏️ Edit Video' : '➕ Add New Video' ?></h3>
    <a href="<?= SITE_URL ?>/admin/videos.php" class="btn btn-outline">Back to List</a>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= clean($error) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="existing_cover" value="<?= clean($editItem['cover_image'] ?? '') ?>">

    <div class="form-row">
      <div class="form-group">
        <label>Video Title *</label>
        <input type="text" name="title" value="<?= clean($editItem['title'] ?? '') ?>" required placeholder="e.g. Naatu Naatu">
      </div>
      <div class="form-group">
        <label>Artist / Cast *</label>
        <select name="artist_id">
          <option value="">-- Select Artist --</option>
          <?php foreach ($artists as $a): ?>
            <option value="<?= $a['id'] ?>" <?= (isset($editItem) && $editItem['artist_id'] == $a['id']) ? 'selected' : '' ?>>
              <?= clean($a['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Album (Optional)</label>
        <select name="album_id">
          <option value="">-- None / Soundtrack --</option>
          <?php foreach ($albums as $alb): ?>
            <option value="<?= $alb['id'] ?>" <?= (isset($editItem) && $editItem['album_id'] == $alb['id']) ? 'selected' : '' ?>>
              <?= clean($alb['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Release Year *</label>
        <input type="number" name="year" min="1900" max="2099" value="<?= clean($editItem['year'] ?? date('Y')) ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Genre *</label>
        <select name="genre" required>
          <option value="">-- Select Genre --</option>
          <?php foreach ($genres as $g): ?>
            <option value="<?= clean($g) ?>" <?= (isset($editItem) && $editItem['genre'] === $g) ? 'selected' : '' ?>><?= clean($g) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Language * (Regional / English / Hindi)</label>
        <select name="language" required>
          <option value="">-- Select Language --</option>
          <?php foreach ($langs as $l): ?>
            <option value="<?= clean($l) ?>" <?= (isset($editItem) && $editItem['language'] === $l) ? 'selected' : '' ?>><?= clean($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Video File Path (.mp4 filename or video URL)</label>
        <input type="text" name="file_path" value="<?= clean($editItem['file_path'] ?? '') ?>" placeholder="e.g. video1.mp4 or MP4 URL">
      </div>
      <div class="form-group">
        <label>Duration (MM:SS)</label>
        <input type="text" name="duration" value="<?= clean($editItem['duration'] ?? '4:00') ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Video Poster / Thumbnail</label>
        <input type="file" name="cover_file" accept="image/*">
        <?php if (!empty($editItem['cover_image'])): ?>
          <small>Current: <?= clean($editItem['cover_image']) ?></small>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <option value="active" <?= (isset($editItem) && $editItem['status'] === 'active') ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= (isset($editItem) && $editItem['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label>
        <input type="checkbox" name="is_new" value="1" <?= (!isset($editItem) || !empty($editItem['is_new'])) ? 'checked' : '' ?>>
        Show "NEW" flashing icon on website
      </label>
    </div>

    <div class="form-group">
      <label>Video Description</label>
      <textarea name="description" rows="3"><?= clean($editItem['description'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Video</button>
  </form>
</div>

<?php else: ?>

<!-- LIST VIDEOS -->
<div class="admin-box">
  <div class="box-head">
    <h3><i class="fas fa-video"></i> All Video Files</h3>
    <a href="<?= SITE_URL ?>/admin/videos.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Video</a>
  </div>

  <?php
  $stmt = $pdo->query("
      SELECT v.*, a.name as artist_name, al.title as album_name
      FROM videos v
      LEFT JOIN artists a ON v.artist_id = a.id
      LEFT JOIN albums al ON v.album_id = al.id
      ORDER BY v.id DESC
  ");
  $allVideos = $stmt->fetchAll();
  ?>

  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Thumbnail</th>
        <th>Title</th>
        <th>Artist / Cast</th>
        <th>Genre</th>
        <th>Language</th>
        <th>Year</th>
        <th>Views</th>
        <th>Badge</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($allVideos)): ?>
        <tr><td colspan="11" style="text-align:center; padding:20px; color:var(--text-muted);">No videos found.</td></tr>
      <?php else: ?>
        <?php foreach ($allVideos as $row): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td>
            <img src="<?= $row['cover_image'] ? SITE_URL.'/assets/images/covers/'.clean($row['cover_image']) : 'https://picsum.photos/seed/v'.$row['id'].'/50/50' ?>" width="40" height="40" style="border-radius:6px; object-fit:cover;">
          </td>
          <td><strong><?= clean($row['title']) ?></strong></td>
          <td><?= clean($row['artist_name'] ?? 'Unknown') ?></td>
          <td><?= clean($row['genre']) ?></td>
          <td><span class="badge-tag"><?= clean($row['language']) ?></span></td>
          <td><?= $row['year'] ?></td>
          <td><?= number_format($row['view_count']) ?></td>
          <td><?= $row['is_new'] ? '<span class="badge-new-sm">NEW</span>' : '-' ?></td>
          <td><span class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
          <td>
            <a href="<?= SITE_URL ?>/admin/videos.php?action=edit&id=<?= $row['id'] ?>" class="tbl-btn" title="Edit"><i class="fas fa-edit"></i></a>
            <a href="<?= SITE_URL ?>/admin/videos.php?action=delete&id=<?= $row['id'] ?>" class="tbl-btn danger" onclick="return confirm('Are you sure to delete this video?')" title="Delete"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

