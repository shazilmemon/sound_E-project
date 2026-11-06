<?php
// =====================================================
// SOUND GROUP - Admin User & Logins Management
// admin/users.php
// Requirement: "Create/Manage Users/Logins. USERID should be unique.
// Name, Address, Phone Numbers, Email-ids should be mandatory"
// =====================================================
$adminTitle = 'User Management';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/admin-header.php';

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);
$error = '';

// Toggle status or Delete
if ($action === 'delete' && $id > 0) {
    if ($id == $_SESSION['user_id']) {
        setFlash('error', 'You cannot delete yourself!');
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'User removed successfully.');
    }
    header('Location: ' . SITE_URL . '/admin/users.php');
    exit;
}

if ($action === 'toggle' && $id > 0) {
    $stmt = $pdo->prepare("UPDATE users SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'User status updated.');
    header('Location: ' . SITE_URL . '/admin/users.php');
    exit;
}

// Add or Edit User POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id   = trim($_POST['user_id'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $role      = $_POST['role'] ?? 'user';
    $status    = $_POST['status'] ?? 'active';
    $password  = $_POST['password'] ?? '';

    // Validations (All mandatory as per doc specs)
    if (empty($user_id) || empty($full_name) || empty($email) || empty($phone) || empty($address)) {
        $error = 'All fields (User ID, Name, Email, Phone, Address) are mandatory.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        if ($action === 'edit' && $id > 0) {
            // Check unique user_id & email excluding current user
            $chk = $pdo->prepare("SELECT id FROM users WHERE (user_id = ? OR email = ?) AND id != ?");
            $chk->execute([$user_id, $email, $id]);
            if ($chk->fetch()) {
                $error = 'User ID or Email already belongs to another account.';
            } else {
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET user_id=?, full_name=?, email=?, phone=?, address=?, role=?, status=?, password=? WHERE id=?");
                    $stmt->execute([$user_id, $full_name, $email, $phone, $address, $role, $status, $hash, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET user_id=?, full_name=?, email=?, phone=?, address=?, role=?, status=? WHERE id=?");
                    $stmt->execute([$user_id, $full_name, $email, $phone, $address, $role, $status, $id]);
                }
                setFlash('success', 'User updated successfully.');
                header('Location: ' . SITE_URL . '/admin/users.php');
                exit;
            }
        } else {
            // New user registration
            if (empty($password) || strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } else {
                $chk = $pdo->prepare("SELECT id FROM users WHERE user_id = ? OR email = ?");
                $chk->execute([$user_id, $email]);
                if ($chk->fetch()) {
                    $error = 'User ID or Email already exists. User ID must be unique.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (user_id, full_name, email, phone, address, password, role, status) VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->execute([$user_id, $full_name, $email, $phone, $address, $hash, $role, $status]);
                    setFlash('success', 'New user account created.');
                    header('Location: ' . SITE_URL . '/admin/users.php');
                    exit;
                }
            }
        }
    }
}

$editItem = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<div class="admin-box">
  <div class="box-head">
    <h3><?= $action === 'edit' ? '✏️ Edit User' : '➕ Create New User Account' ?></h3>
    <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-outline">Back to Users</a>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= clean($error) ?></div>
  <?php endif; ?>

  <form method="POST" class="admin-form">
    <div class="form-row">
      <div class="form-group">
        <label>Unique User ID *</label>
        <input type="text" name="user_id" value="<?= clean($editItem['user_id'] ?? '') ?>" required placeholder="e.g. rahul_99">
      </div>
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="full_name" value="<?= clean($editItem['full_name'] ?? '') ?>" required placeholder="User Full Name">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Email Address *</label>
        <input type="email" name="email" value="<?= clean($editItem['email'] ?? '') ?>" required placeholder="user@example.com">
      </div>
      <div class="form-group">
        <label>Phone Number *</label>
        <input type="text" name="phone" value="<?= clean($editItem['phone'] ?? '') ?>" required placeholder="+91 XXXXXXXXXX">
      </div>
    </div>

    <div class="form-group">
      <label>Complete Address *</label>
      <input type="text" name="address" value="<?= clean($editItem['address'] ?? '') ?>" required placeholder="Street address, City">
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Password <?= $action === 'edit' ? '(Leave blank to keep unchanged)' : '*' ?></label>
        <input type="password" name="password" <?= $action === 'add' ? 'required' : '' ?> placeholder="Min 6 characters">
      </div>
      <div class="form-group">
        <label>Role</label>
        <select name="role">
          <option value="user" <?= (isset($editItem) && $editItem['role'] === 'user') ? 'selected' : '' ?>>Normal User</option>
          <option value="admin" <?= (isset($editItem) && $editItem['role'] === 'admin') ? 'selected' : '' ?>>Administrator</option>
        </select>
      </div>
      <div class="form-group">
        <label>Account Status</label>
        <select name="status">
          <option value="active" <?= (isset($editItem) && $editItem['status'] === 'active') ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= (isset($editItem) && $editItem['status'] === 'inactive') ? 'selected' : '' ?>>Inactive / Blocked</option>
        </select>
      </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $action === 'edit' ? 'Update User' : 'Create User' ?></button>
  </form>
</div>

<?php else: ?>

<!-- Users List -->
<div class="admin-box">
  <div class="box-head">
    <h3><i class="fas fa-users"></i> Users &amp; Logins Directory</h3>
    <a href="<?= SITE_URL ?>/admin/users.php?action=add" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add New User</a>
  </div>

  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>User ID</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Address</th>
        <th>Role</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><?= $u['id'] ?></td>
        <td><strong>@<?= clean($u['user_id']) ?></strong></td>
        <td><?= clean($u['full_name']) ?></td>
        <td><?= clean($u['email']) ?></td>
        <td><?= clean($u['phone']) ?></td>
        <td style="max-width:140px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;"><?= clean($u['address']) ?></td>
        <td><span class="user-role role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
        <td>
          <a href="<?= SITE_URL ?>/admin/users.php?action=toggle&id=<?= $u['id'] ?>" title="Click to toggle status" class="status-<?= $u['status'] ?>">
            ● <?= ucfirst($u['status']) ?>
          </a>
        </td>
        <td>
          <a href="<?= SITE_URL ?>/admin/users.php?action=edit&id=<?= $u['id'] ?>" class="tbl-btn" title="Edit"><i class="fas fa-edit"></i></a>
          <?php if ($u['id'] != $_SESSION['user_id']): ?>
            <a href="<?= SITE_URL ?>/admin/users.php?action=delete&id=<?= $u['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete this user?')" title="Delete"><i class="fas fa-trash"></i></a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

