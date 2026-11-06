<?php
// =====================================================
// SOUND GROUP - User Profile
// pages/profile.php
// =====================================================
require_once __DIR__ . '/../includes/auth.php';
requireLogin('/pages/login.php');

$pdo = getDB();
$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (empty($full_name) || empty($phone) || empty($address)) {
        $error = 'Full Name, Phone, and Address are mandatory.';
    } else {
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $error = 'New password must be at least 6 characters.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET full_name=?, phone=?, address=?, password=? WHERE id=?");
                $stmt->execute([$full_name, $phone, $address, $hash, $user['id']]);
                $success = 'Profile and password updated successfully!';
            }
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, phone=?, address=? WHERE id=?");
            $stmt->execute([$full_name, $phone, $address, $user['id']]);
            $success = 'Profile details updated successfully!';
        }
        $_SESSION['user_name'] = $full_name;
        $user = getCurrentUser();
    }
}

$pageTitle = 'My Profile';
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper" style="min-height:auto; padding:50px 20px;">
  <div class="auth-box" style="max-width:550px;">
    <h2><i class="fas fa-user-circle"></i> My Account Profile</h2>
    <p class="auth-sub">Manage your personal information</p>

    <?php if ($success): ?>
      <div class="alert alert-success">✅ <?= clean($success) ?></div>
    <?php elseif ($error): ?>
      <div class="alert alert-danger">❌ <?= clean($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="auth-form">
      <div class="form-row">
        <div class="form-group">
          <label>User ID (Immutable)</label>
          <input type="text" value="<?= clean($user['user_id']) ?>" disabled style="opacity:0.6; cursor:not-allowed;">
        </div>
        <div class="form-group">
          <label>Email (Immutable)</label>
          <input type="email" value="<?= clean($user['email']) ?>" disabled style="opacity:0.6; cursor:not-allowed;">
        </div>
      </div>

      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="full_name" value="<?= clean($user['full_name']) ?>" required>
      </div>

      <div class="form-group">
        <label>Phone Number *</label>
        <input type="text" name="phone" value="<?= clean($user['phone']) ?>" required>
      </div>

      <div class="form-group">
        <label>Mailing Address *</label>
        <input type="text" name="address" value="<?= clean($user['address']) ?>" required>
      </div>

      <div class="form-group">
        <label>Change Password (Leave blank to keep current)</label>
        <input type="password" name="password" placeholder="New Password">
      </div>

      <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-save"></i> Update Profile</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

