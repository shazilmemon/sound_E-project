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

$editItem = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM artists WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$artists = $pdo->query("
    SELECT a.*, COUNT(m.id) as songs_count
    FROM artists a
    LEFT JOIN music m ON m.artist_id = a.id
    GROUP BY a.id
    ORDER BY a.name ASC
")->fetchAll();
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
        <input type="text" name="language" value="<?= clean($editItem['language'] ?? 'Hindi') ?>" placeholder="e.g. Hindi, English, Punjabi">
      </div>
      <div class="form-group">
        <label>Photo / Avatar</label>
        <input type="file" name="image_file" accept="image/*">
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
      <h3><i class="fas fa-microphone"></i> Artists List</h3>
    </div>
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
        <?php foreach ($artists as $a): ?>
        <tr>
          <td>
            <img src="<?= $a['image'] ? SITE_URL.'/assets/images/artists/'.clean($a['image']) : 'https://picsum.photos/seed/art'.$a['id'].'/40/40' ?>" width="35" height="35" style="border-radius:50%; object-fit:cover;">
          </td>
          <td><strong><?= clean($a['name']) ?></strong></td>
          <td><span class="badge-tag"><?= clean($a['language']) ?></span></td>
          <td><?= $a['songs_count'] ?> songs</td>
          <td>
            <a href="<?= SITE_URL ?>/admin/artists.php?action=edit&id=<?= $a['id'] ?>" class="tbl-btn" title="Edit"><i class="fas fa-edit"></i></a>
            <a href="<?= SITE_URL ?>/admin/artists.php?action=delete&id=<?= $a['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete this artist?')" title="Delete"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

