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
        } elseif (!empty($_POST['cover_url'])) {
            $cover_image = trim($_POST['cover_url']);
        }

        $video_url = trim($_POST['file_path'] ?? '');
        if (!empty($_FILES['video_file']['name'])) {
            $vExt = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            if (in_array($vExt, ['mp4', 'webm', 'ogg'])) {
                $video_url = 'vid_' . time() . '.' . $vExt;
                move_uploaded_file($_FILES['video_file']['tmp_name'], UPLOAD_PATH . 'videos/' . $video_url);
            }
        }

        if ($action === 'edit' && $id > 0) {
            if ($is_new) {
                $stmt = $pdo->prepare("UPDATE videos SET title=?, artist_id=?, album_id=?, year=?, genre=?, language=?, cover_image=?, file_path=?, duration=?, description=?, is_new=?, status=?, created_at=NOW() WHERE id=?");
                $stmt->execute([$title, $artist_id, $album_id, $year, $genre, $language, $cover_image, $video_url, $duration, $description, $is_new, $status, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE videos SET title=?, artist_id=?, album_id=?, year=?, genre=?, language=?, cover_image=?, file_path=?, duration=?, description=?, is_new=?, status=? WHERE id=?");
                $stmt->execute([$title, $artist_id, $album_id, $year, $genre, $language, $cover_image, $video_url, $duration, $description, $is_new, $status, $id]);
            }
            setFlash('success', 'Video updated successfully.');
            header('Location: ' . SITE_URL . '/admin/videos.php');
            exit;
        } else {
            $stmt = $pdo->prepare("INSERT INTO videos (title, artist_id, album_id, year, genre, language, cover_image, file_path, duration, description, is_new, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$title, $artist_id, $album_id, $year, $genre, $language, $cover_image, $video_url, $duration, $description, $is_new, $status]);
            setFlash('success', 'Video added successfully (Marked as NEW for 3 days).');
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
        <label>Upload Video (.mp4 file)</label>
        <input type="file" name="video_file" accept="video/mp4,video/webm,video/ogg">
        <?php if (!empty($editItem['file_path'])): ?>
          <small style="color:var(--text-muted); display:block; margin-top:4px;">Current file: <code><?= clean($editItem['file_path']) ?></code></small>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label>Or Video Filename / URL</label>
        <input type="text" name="file_path" value="<?= clean($editItem['file_path'] ?? '') ?>" placeholder="e.g. video1.mp4 or https://...">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Duration (MM:SS)</label>
        <input type="text" name="duration" value="<?= clean($editItem['duration'] ?? '4:00') ?>">
      </div>
      <div class="form-group">
        <label>Video Poster / Thumbnail</label>
        <input type="file" name="cover_file" accept="image/*">
        <input type="text" name="cover_url" value="<?= clean($editItem['cover_image'] ?? '') ?>" placeholder="Or enter Thumbnail URL / Filename..." style="margin-top:6px;">
        <?php if (!empty($editItem['cover_image'])): ?>
          <small style="color:var(--text-muted); display:block; margin-top:4px;">Current: <code><?= clean($editItem['cover_image']) ?></code></small>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <option value="active" <?= (isset($editItem) && $editItem['status'] === 'active') ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= (isset($editItem) && $editItem['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div class="form-group" style="display:flex; align-items:center; padding-top:24px;">
        <label style="cursor:pointer; display:flex; align-items:center; gap:8px;">
          <input type="checkbox" name="is_new" value="1" <?= (!isset($editItem) || !empty($editItem['is_new'])) ? 'checked' : '' ?>>
          Show "NEW" badge on website (Valid for 3 days)
        </label>
      </div>
    </div>

    <div class="form-group">
      <label>Video Description / Information</label>
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
  $q = trim($_GET['search'] ?? '');
  $filterLang = trim($_GET['lang'] ?? '');
  $filterGenre = trim($_GET['genre'] ?? '');
  $page = max(1, (int)($_GET['p'] ?? 1));
  $perPage = 15;
  $offset = ($page - 1) * $perPage;

  $where = ['1=1'];
  $params = [];

  if ($q) {
      $where[] = "(v.title LIKE ? OR a.name LIKE ?)";
      $params[] = "%$q%";
      $params[] = "%$q%";
  }
  if ($filterLang) {
      $where[] = "v.language = ?";
      $params[] = $filterLang;
  }
  if ($filterGenre) {
      $where[] = "v.genre = ?";
      $params[] = $filterGenre;
  }

  // Count total matches
  $countStmt = $pdo->prepare("
      SELECT COUNT(*)
      FROM videos v
      LEFT JOIN artists a ON v.artist_id = a.id
      WHERE " . implode(' AND ', $where) . "
  ");
  $countStmt->execute($params);
  $totalRecords = (int)$countStmt->fetchColumn();
  $totalPages = max(1, ceil($totalRecords / $perPage));

  $stmt = $pdo->prepare("
      SELECT v.*, a.name as artist_name, al.title as album_name
      FROM videos v
      LEFT JOIN artists a ON v.artist_id = a.id
      LEFT JOIN albums al ON v.album_id = al.id
      WHERE " . implode(' AND ', $where) . "
      ORDER BY v.id DESC
      LIMIT $perPage OFFSET $offset
  ");
  $stmt->execute($params);
  $allVideos = $stmt->fetchAll();
  ?>

  <div class="admin-filters">
    <form method="GET" style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:15px; align-items:center;">
      <input type="text" name="search" placeholder="Search title or artist..." value="<?= clean($q) ?>" style="padding:8px 12px; background:var(--dark-3); border:1px solid var(--border); color:#fff; border-radius:6px; min-width:200px;">
      <select name="lang" style="padding:8px 12px; background:var(--dark-3); border:1px solid var(--border); color:#fff; border-radius:6px;">
        <option value="">All Languages</option>
        <?php foreach ($langs as $l): ?>
          <option value="<?= clean($l) ?>" <?= $filterLang === $l ? 'selected' : '' ?>><?= clean($l) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="genre" style="padding:8px 12px; background:var(--dark-3); border:1px solid var(--border); color:#fff; border-radius:6px;">
        <option value="">All Genres</option>
        <?php foreach ($genres as $g): ?>
          <option value="<?= clean($g) ?>" <?= $filterGenre === $g ? 'selected' : '' ?>><?= clean($g) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-outline" style="padding:8px 16px;"><i class="fas fa-filter"></i> Filter</button>
      <?php if ($q || $filterLang || $filterGenre): ?>
        <a href="<?= SITE_URL ?>/admin/videos.php" class="btn btn-outline" style="padding:8px 16px;">Reset</a>
      <?php endif; ?>
      <span style="margin-left:auto; font-size:12px; color:var(--text-muted); font-weight:600;">
        Showing <?= count($allVideos) ?> of <?= $totalRecords ?> videos
      </span>
    </form>
  </div>

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
        <tr><td colspan="11" style="text-align:center; padding:25px; color:var(--text-muted);">No videos found matching your query.</td></tr>
      <?php else: ?>
        <?php foreach ($allVideos as $row): ?>
        <tr>
          <td>#<?= $row['id'] ?></td>
          <td>
            <img src="<?= resolveMediaUrl($row['cover_image'], 'cover') ?>" width="42" height="42" style="border-radius:6px; object-fit:cover; border:1px solid var(--border);" onerror="this.onerror=null; this.src='<?= SITE_URL ?>/assets/images/covers/cover_pop.jpg';">
          </td>
          <td><strong><?= clean($row['title']) ?></strong></td>
          <td><?= clean($row['artist_name'] ?? 'Various') ?></td>
          <td><?= clean($row['genre']) ?></td>
          <td><span class="badge-tag"><?= clean($row['language']) ?></span></td>
          <td><?= $row['year'] ?></td>
          <td><i class="fas fa-eye" style="font-size:11px; color:var(--text-muted);"></i> <?= number_format($row['view_count']) ?></td>
          <td><?= isMediaNew($row) ? '<span class="badge-new-sm">NEW</span>' : '-' ?></td>
          <td><span class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
          <td style="white-space:nowrap;">
            <button type="button" class="tbl-btn" style="color:var(--primary);" onclick="previewAdminVideo('<?= resolveMediaUrl($row['file_path'], 'video') ?>', '<?= clean(addslashes($row['title'])) ?>')" title="Quick Video Preview"><i class="fas fa-play"></i></button>
            <a href="<?= SITE_URL ?>/pages/video-detail.php?id=<?= $row['id'] ?>" target="_blank" class="tbl-btn" title="View Public Video Page"><i class="fas fa-eye"></i></a>
            <a href="<?= SITE_URL ?>/admin/videos.php?action=edit&id=<?= $row['id'] ?>" class="tbl-btn" title="Edit Video"><i class="fas fa-edit"></i></a>
            <a href="<?= SITE_URL ?>/admin/videos.php?action=delete&id=<?= $row['id'] ?>" class="tbl-btn danger" onclick="return confirm('Are you sure you want to delete <?= clean(addslashes($row['title'])) ?>?')" title="Delete"><i class="fas fa-trash"></i></a>
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
      <a href="?search=<?= urlencode($q) ?>&lang=<?= urlencode($filterLang) ?>&genre=<?= urlencode($filterGenre) ?>&p=<?= $page - 1 ?>" class="tbl-btn">&laquo; Prev</a>
    <?php endif; ?>
    
    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
      <a href="?search=<?= urlencode($q) ?>&lang=<?= urlencode($filterLang) ?>&genre=<?= urlencode($filterGenre) ?>&p=<?= $i ?>" class="tbl-btn <?= $i === $page ? 'active' : '' ?>" style="<?= $i === $page ? 'background:var(--primary); color:#fff; border-color:var(--primary);' : '' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
      <a href="?search=<?= urlencode($q) ?>&lang=<?= urlencode($filterLang) ?>&genre=<?= urlencode($filterGenre) ?>&p=<?= $page + 1 ?>" class="tbl-btn">Next &raquo;</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

