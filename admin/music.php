<?php
// =====================================================
// SOUND GROUP - Admin Music Management
// admin/music.php
// =====================================================
$adminTitle = 'Music Management';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/admin-header.php';

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

// Handle Delete
if ($action === 'delete' && $id > 0) {
    $stmt = $pdo->prepare("DELETE FROM music WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Music file deleted successfully.');
    header('Location: ' . SITE_URL . '/admin/music.php');
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
    $description = trim($_POST['description'] ?? '');
    $is_new      = isset($_POST['is_new']) ? 1 : 0;
    $status      = $_POST['status'] ?? 'active';

    if (empty($title) || empty($genre) || empty($language) || empty($year)) {
        $error = 'Title, Genre, Language, and Year are mandatory fields.';
    } else {
        // Handle file uploads if present
        $cover_image = $_POST['existing_cover'] ?? null;
        if (!empty($_FILES['cover_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $cover_image = 'cover_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['cover_file']['tmp_name'], COVER_PATH . $cover_image);
            }
        } elseif (!empty($_POST['cover_url'])) {
            $cover_image = trim($_POST['cover_url']);
        }

        $file_path = $_POST['existing_file'] ?? null;
        if (!empty($_FILES['music_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['music_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['mp3', 'wav', 'ogg'])) {
                $file_path = 'audio_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['music_file']['tmp_name'], UPLOAD_PATH . 'music/' . $file_path);
            }
        } elseif (!empty($_POST['music_url'])) {
            $file_path = trim($_POST['music_url']);
        }

        if ($action === 'edit' && $id > 0) {
            if ($is_new) {
                $stmt = $pdo->prepare("UPDATE music SET title=?, artist_id=?, album_id=?, year=?, genre=?, language=?, cover_image=?, file_path=?, duration=?, description=?, is_new=?, status=?, created_at=NOW() WHERE id=?");
                $stmt->execute([$title, $artist_id, $album_id, $year, $genre, $language, $cover_image, $file_path, $duration, $description, $is_new, $status, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE music SET title=?, artist_id=?, album_id=?, year=?, genre=?, language=?, cover_image=?, file_path=?, duration=?, description=?, is_new=?, status=? WHERE id=?");
                $stmt->execute([$title, $artist_id, $album_id, $year, $genre, $language, $cover_image, $file_path, $duration, $description, $is_new, $status, $id]);
            }
            setFlash('success', 'Music track updated successfully.');
            header('Location: ' . SITE_URL . '/admin/music.php');
            exit;
        } else {
            $stmt = $pdo->prepare("INSERT INTO music (title, artist_id, album_id, year, genre, language, cover_image, file_path, duration, description, is_new, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$title, $artist_id, $album_id, $year, $genre, $language, $cover_image, $file_path, $duration, $description, $is_new, $status]);
            setFlash('success', 'New music track added successfully (Marked as NEW for 3 days).');
            header('Location: ' . SITE_URL . '/admin/music.php');
            exit;
        }
    }
}

// Options for dropdowns
$artists = $pdo->query("SELECT id, name FROM artists ORDER BY name ASC")->fetchAll();
$albums  = $pdo->query("SELECT id, title FROM albums ORDER BY title ASC")->fetchAll();
$genres  = $pdo->query("SELECT name FROM categories WHERE type='genre' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$langs   = $pdo->query("SELECT name FROM categories WHERE type='language' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch data if editing
$editItem = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM music WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<div class="admin-box">
  <div class="box-head">
    <h3><?= $action === 'edit' ? '✏️ Edit Music' : '➕ Add New Music Track' ?></h3>
    <a href="<?= SITE_URL ?>/admin/music.php" class="btn btn-outline">Back to List</a>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= clean($error) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="existing_cover" value="<?= clean($editItem['cover_image'] ?? '') ?>">
    <input type="hidden" name="existing_file" value="<?= clean($editItem['file_path'] ?? '') ?>">

    <div class="form-row">
      <div class="form-group">
        <label>Song Title *</label>
        <input type="text" name="title" value="<?= clean($editItem['title'] ?? '') ?>" required placeholder="e.g. Tere Bin">
      </div>
      <div class="form-group">
        <label>Artist *</label>
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
          <option value="">-- None / Single --</option>
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
          <?php if (!in_array($editItem['genre'] ?? '', $genres) && !empty($editItem['genre'])): ?>
            <option value="<?= clean($editItem['genre']) ?>" selected><?= clean($editItem['genre']) ?></option>
          <?php endif; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Language * (Regional / English / Hindi)</label>
        <select name="language" required>
          <option value="">-- Select Language --</option>
          <?php foreach ($langs as $l): ?>
            <option value="<?= clean($l) ?>" <?= (isset($editItem) && $editItem['language'] === $l) ? 'selected' : '' ?>><?= clean($l) ?></option>
          <?php endforeach; ?>
          <?php if (!in_array($editItem['language'] ?? '', $langs) && !empty($editItem['language'])): ?>
            <option value="<?= clean($editItem['language']) ?>" selected><?= clean($editItem['language']) ?></option>
          <?php endif; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Duration (MM:SS)</label>
        <input type="text" name="duration" value="<?= clean($editItem['duration'] ?? '3:30') ?>" placeholder="e.g. 03:45">
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <option value="active" <?= (isset($editItem) && $editItem['status'] === 'active') ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= (isset($editItem) && $editItem['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Cover Image (Upload Image)</label>
        <input type="file" name="cover_file" accept="image/*">
        <input type="text" name="cover_url" value="<?= clean($editItem['cover_image'] ?? '') ?>" placeholder="Or enter Cover URL / Filename (e.g. cover_pop.jpg or https://...)" style="margin-top:6px;">
        <?php if (!empty($editItem['cover_image'])): ?>
          <small style="color:var(--text-muted); display:block; margin-top:4px;">Current: <code><?= clean($editItem['cover_image']) ?></code></small>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label>Audio File (Upload MP3 / Audio)</label>
        <input type="file" name="music_file" accept="audio/*">
        <input type="text" name="music_url" value="<?= clean($editItem['file_path'] ?? '') ?>" placeholder="Or enter Audio URL / Filename (e.g. song1.mp3 or https://...)" style="margin-top:6px;">
        <?php if (!empty($editItem['file_path'])): ?>
          <small style="color:var(--text-muted); display:block; margin-top:4px;">Current: <code><?= clean($editItem['file_path']) ?></code></small>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-group">
      <label>
        <input type="checkbox" name="is_new" value="1" <?= (!isset($editItem) || !empty($editItem['is_new'])) ? 'checked' : '' ?>>
        Show "NEW" flashing icon on website
      </label>
    </div>

    <div class="form-group">
      <label>Description / Lyrics Information</label>
      <textarea name="description" rows="3"><?= clean($editItem['description'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Music Track</button>
  </form>
</div>

<?php else: ?>

<!-- LIST VIEW -->
<div class="admin-box">
  <div class="box-head">
    <h3><i class="fas fa-music"></i> All Music Files</h3>
    <a href="<?= SITE_URL ?>/admin/music.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Track</a>
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
      $where[] = "(m.title LIKE ? OR a.name LIKE ?)";
      $params[] = "%$q%";
      $params[] = "%$q%";
  }
  if ($filterLang) {
      $where[] = "m.language = ?";
      $params[] = $filterLang;
  }
  if ($filterGenre) {
      $where[] = "m.genre = ?";
      $params[] = $filterGenre;
  }

  // Count total matches
  $countStmt = $pdo->prepare("
      SELECT COUNT(*)
      FROM music m
      LEFT JOIN artists a ON m.artist_id = a.id
      WHERE " . implode(' AND ', $where) . "
  ");
  $countStmt->execute($params);
  $totalRecords = (int)$countStmt->fetchColumn();
  $totalPages = max(1, ceil($totalRecords / $perPage));

  // Fetch paginated records
  $stmt = $pdo->prepare("
      SELECT m.*, a.name as artist_name, al.title as album_name
      FROM music m
      LEFT JOIN artists a ON m.artist_id = a.id
      LEFT JOIN albums al ON m.album_id = al.id
      WHERE " . implode(' AND ', $where) . "
      ORDER BY m.id DESC
      LIMIT $perPage OFFSET $offset
  ");
  $stmt->execute($params);
  $allMusic = $stmt->fetchAll();
  ?>

  <div class="admin-filters">
    <form method="GET" style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:15px; align-items:center;">
      <input type="text" name="search" placeholder="Search track or artist..." value="<?= clean($q) ?>" style="padding:8px 12px; background:var(--dark-3); border:1px solid var(--border); color:#fff; border-radius:6px; min-width:200px;">
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
        <a href="<?= SITE_URL ?>/admin/music.php" class="btn btn-outline" style="padding:8px 16px;">Reset</a>
      <?php endif; ?>
      <span style="margin-left:auto; font-size:12px; color:var(--text-muted); font-weight:600;">
        Showing <?= count($allMusic) ?> of <?= $totalRecords ?> tracks
      </span>
    </form>
  </div>

  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Cover</th>
        <th>Title</th>
        <th>Artist</th>
        <th>Album</th>
        <th>Genre</th>
        <th>Lang</th>
        <th>Year</th>
        <th>Plays</th>
        <th>Badge</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($allMusic)): ?>
        <tr><td colspan="12" style="text-align:center; padding:25px; color:var(--text-muted);">No music records found matching your query.</td></tr>
      <?php else: ?>
        <?php foreach ($allMusic as $row): ?>
        <tr>
          <td>#<?= $row['id'] ?></td>
          <td>
            <img src="<?= resolveMediaUrl($row['cover_image'], 'cover') ?>" width="40" height="40" style="border-radius:6px; object-fit:cover; border:1px solid var(--border);" onerror="this.onerror=null; this.src='<?= SITE_URL ?>/assets/images/covers/cover_pop.jpg';">
          </td>
          <td><strong><?= clean($row['title']) ?></strong></td>
          <td><?= clean($row['artist_name'] ?? 'Single / Unknown') ?></td>
          <td><?= clean($row['album_name'] ?? '-') ?></td>
          <td><?= clean($row['genre']) ?></td>
          <td><span class="badge-tag"><?= clean($row['language']) ?></span></td>
          <td><?= $row['year'] ?></td>
          <td><i class="fas fa-headphones" style="font-size:11px; color:var(--text-muted);"></i> <?= number_format($row['play_count']) ?></td>
          <td><?= isMediaNew($row) ? '<span class="badge-new-sm">NEW</span>' : '-' ?></td>
          <td><span class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
          <td style="white-space:nowrap;">
            <button type="button" class="tbl-btn" style="color:var(--primary);" onclick="previewAdminSong('<?= resolveMediaUrl($row['file_path'], 'music') ?>', '<?= clean(addslashes($row['title'])) ?>')" title="Quick Audio Preview"><i class="fas fa-play"></i></button>
            <a href="<?= SITE_URL ?>/pages/music-detail.php?id=<?= $row['id'] ?>" target="_blank" class="tbl-btn" title="View Public Page"><i class="fas fa-eye"></i></a>
            <a href="<?= SITE_URL ?>/admin/music.php?action=edit&id=<?= $row['id'] ?>" class="tbl-btn" title="Edit Track"><i class="fas fa-edit"></i></a>
            <a href="<?= SITE_URL ?>/admin/music.php?action=delete&id=<?= $row['id'] ?>" class="tbl-btn danger" onclick="return confirm('Are you sure you want to delete <?= clean(addslashes($row['title'])) ?>?')" title="Delete"><i class="fas fa-trash"></i></a>
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

