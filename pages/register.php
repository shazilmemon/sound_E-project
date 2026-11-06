<?php
// =====================================================
// SOUND GROUP - Register Page
// pages/register.php
// =====================================================
require_once __DIR__ . '/../includes/auth.php';

// Already logged in
if (isLoggedIn()) { header('Location: ' . SITE_URL . '/index.php'); exit; }

$errors = [];
$old    = [];
$redirect = trim($_GET['redirect'] ?? $_POST['redirect'] ?? '');
$validRedirect = '';
if (!empty($redirect) && str_starts_with($redirect, '/') && !str_starts_with($redirect, '//')) {
    $validRedirect = $redirect;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $result = registerUser($_POST);
    if ($result['success']) {
        setFlash('success', $result['msg']);
        $loginUrl = SITE_URL . '/pages/login.php' . ($validRedirect ? '?redirect=' . urlencode($validRedirect) : '');
        header('Location: ' . $loginUrl);
        exit;
    } else {
        $errors[] = $result['msg'];
    }
}

$pageTitle = 'Register';
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-box">
    <div class="auth-logo"><em>SOUND</em> GROUP</div>
    <h2>Create Account</h2>
    <p class="auth-sub">Join SOUND Group — It's free!</p>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?><p>❌ <?= clean($e) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($validRedirect): ?>
      <div class="alert alert-info" style="background: rgba(255, 45, 85, 0.12); border: 1px solid rgba(255, 45, 85, 0.4); color: #ff85a1; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13.5px;">
        <i class="fas fa-lock"></i> Create an account to unlock and stream unlimited music and videos.
      </div>
    <?php endif; ?>

    <form action="" method="POST" class="auth-form" novalidate>
      <?php if ($validRedirect): ?>
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($validRedirect) ?>">
      <?php endif; ?>
      <div class="form-row">
        <div class="form-group">
          <label>Full Name *</label>
          <input type="text" name="full_name" value="<?= clean($old['full_name'] ?? '') ?>" placeholder="Your Full Name" required>
        </div>
        <div class="form-group">
          <label>User ID * <small>(unique)</small></label>
          <input type="text" name="user_id" value="<?= clean($old['user_id'] ?? '') ?>" placeholder="e.g. rahul_99" required pattern="[a-zA-Z0-9_]{4,20}">
          <small>4-20 chars, letters, numbers and _ only</small>
        </div>
      </div>

      <div class="form-group">
        <label>Email Address *</label>
        <input type="email" name="email" value="<?= clean($old['email'] ?? '') ?>" placeholder="your@email.com" required>
      </div>

      <div class="form-group">
        <label>Phone Number *</label>
        <input type="tel" name="phone" value="<?= clean($old['phone'] ?? '') ?>" placeholder="+91 XXXXXXXXXX" required>
      </div>

      <div class="form-group">
        <label>Address *</label>
        <input type="text" name="address" value="<?= clean($old['address'] ?? '') ?>" placeholder="Your complete address" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Password *</label>
          <div class="input-icon">
            <input type="password" name="password" id="pass" placeholder="Min 6 characters" required minlength="6">
            <span class="toggle-pass" onclick="togglePass('pass')"><i class="fas fa-eye"></i></span>
          </div>
        </div>
        <div class="form-group">
          <label>Confirm Password *</label>
          <div class="input-icon">
            <input type="password" name="confirm_password" id="cpass" placeholder="Re-enter password" required>
            <span class="toggle-pass" onclick="togglePass('cpass')"><i class="fas fa-eye"></i></span>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-user-plus"></i> Create Account</button>
    </form>

    <p class="auth-link">Already have an account? <a href="<?= SITE_URL ?>/pages/login.php<?= $validRedirect ? '?redirect=' . urlencode($validRedirect) : '' ?>">Login here</a></p>
  </div>
</div>

<script>
function togglePass(id) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}

// Client-side password match validation
document.querySelector('.auth-form').addEventListener('submit', function(e) {
  const p  = document.getElementById('pass').value;
  const cp = document.getElementById('cpass').value;
  if (p !== cp) {
    e.preventDefault();
    alert('Passwords do not match!');
  }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

