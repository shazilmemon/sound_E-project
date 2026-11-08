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
    } elseif (strlen(preg_replace('/[^0-9]/', '', $phone)) < 10) {
        $error = 'Please enter a valid phone number with dial code (at least 10-11 digits).';
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

$q = trim($_GET['search'] ?? '');
$filterRole = trim($_GET['role'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
$params = [];

if ($q) {
    $where[] = "(user_id LIKE ? OR full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($filterRole && in_array($filterRole, ['user', 'admin'])) {
    $where[] = "role = ?";
    $params[] = $filterRole;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE " . implode(' AND ', $where));
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

$stmt = $pdo->prepare("SELECT * FROM users WHERE " . implode(' AND ', $where) . " ORDER BY id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();
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
        <input type="text" name="phone" value="<?= clean($editItem['phone'] ?? '') ?>" required placeholder="+92 3XXXXXXXXX">
      </div>
    </div>

    <div class="form-group">
      <label>Complete Address *</label>
      <input type="text" name="address" value="<?= clean($editItem['address'] ?? '') ?>" required placeholder="Street address, City">
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Password <?= $action === 'edit' ? '(Leave blank to keep unchanged)' : '*' ?></label>
        <div class="input-icon" style="position:relative;">
          <input type="password" name="password" id="adminUserPass" <?= $action === 'add' ? 'required' : '' ?> placeholder="Min 6 characters">
          <span class="toggle-pass" onclick="toggleAdminPass('adminUserPass', this)" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; color:#888;">
            <i class="fas fa-eye"></i>
          </span>
        </div>
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

<script>
function toggleAdminPass(id, btn) {
  const input = document.getElementById(id);
  if (!input) return;
  const icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}
</script>

<?php else: ?>

<!-- Users List -->
<div class="admin-box">
  <div class="box-head">
    <h3><i class="fas fa-users"></i> Users &amp; Logins Directory (<?= count($users) ?>)</h3>
    <a href="<?= SITE_URL ?>/admin/users.php?action=add" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add New User</a>
  </div>

  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
    <div style="display:flex; gap:8px;">
      <a href="<?= SITE_URL ?>/admin/users.php" class="tbl-btn <?= empty($filterRole) ? 'active' : '' ?>">All Users</a>
      <a href="<?= SITE_URL ?>/admin/users.php?role=user" class="tbl-btn <?= $filterRole === 'user' ? 'active' : '' ?>">Standard Users</a>
      <a href="<?= SITE_URL ?>/admin/users.php?role=admin" class="tbl-btn <?= $filterRole === 'admin' ? 'active' : '' ?>">Admins</a>
    </div>

    <form method="GET" style="display:flex; gap:8px;">
      <?php if ($filterRole): ?><input type="hidden" name="role" value="<?= clean($filterRole) ?>"><?php endif; ?>
      <input type="text" name="search" placeholder="Search name, ID, email..." value="<?= clean($q) ?>" style="padding:6px 12px; background:var(--dark-3); border:1px solid var(--border); color:#fff; border-radius:6px; min-width:200px;">
      <button type="submit" class="btn btn-outline" style="padding:6px 12px;">Search</button>
      <?php if ($q): ?><a href="<?= SITE_URL ?>/admin/users.php<?= $filterRole ? '?role='.urlencode($filterRole) : '' ?>" class="btn btn-outline" style="padding:6px 12px;">Reset</a><?php endif; ?>
    </form>
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
      <?php if (empty($users)): ?>
        <tr><td colspan="9" style="text-align:center; padding:25px; color:var(--text-muted);">No users found.</td></tr>
      <?php else: ?>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>#<?= $u['id'] ?></td>
          <td><strong>@<?= clean($u['user_id']) ?></strong></td>
          <td><?= clean($u['full_name']) ?></td>
          <td><?= clean($u['email']) ?></td>
          <td><?= clean($u['phone']) ?></td>
          <td style="max-width:140px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;" title="<?= clean($u['address']) ?>"><?= clean($u['address']) ?></td>
          <td><span class="user-role role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
          <td>
            <a href="<?= SITE_URL ?>/admin/users.php?action=toggle&id=<?= $u['id'] ?>" title="Click to toggle status (Active/Inactive)" class="status-<?= $u['status'] ?>" style="text-decoration:none; font-weight:600;">
              ● <?= ucfirst($u['status']) ?>
            </a>
          </td>
          <td style="white-space:nowrap;">
            <a href="<?= SITE_URL ?>/admin/users.php?action=edit&id=<?= $u['id'] ?>" class="tbl-btn" title="Edit User"><i class="fas fa-edit"></i></a>
            <?php if ($u['id'] != $_SESSION['user_id']): ?>
              <a href="<?= SITE_URL ?>/admin/users.php?action=delete&id=<?= $u['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete user @<?= clean(addslashes($u['user_id'])) ?> permanently?')" title="Delete"><i class="fas fa-trash"></i></a>
            <?php endif; ?>
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
      <a href="?search=<?= urlencode($q) ?>&role=<?= urlencode($filterRole) ?>&p=<?= $page - 1 ?>" class="tbl-btn">&laquo; Prev</a>
    <?php endif; ?>
    
    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
      <a href="?search=<?= urlencode($q) ?>&role=<?= urlencode($filterRole) ?>&p=<?= $i ?>" class="tbl-btn <?= $i === $page ? 'active' : '' ?>" style="<?= $i === $page ? 'background:var(--primary); color:#fff; border-color:var(--primary);' : '' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
      <a href="?search=<?= urlencode($q) ?>&role=<?= urlencode($filterRole) ?>&p=<?= $page + 1 ?>" class="tbl-btn">Next &raquo;</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

