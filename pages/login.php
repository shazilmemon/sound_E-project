<?php
// =====================================================
// SOUND GROUP - Login Page
// pages/login.php
// =====================================================
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) { header('Location: ' . SITE_URL . '/index.php'); exit; }

$errors = [];
$redirect = trim($_GET['redirect'] ?? $_POST['redirect'] ?? '');
$validRedirect = '';
if (!empty($redirect) && str_starts_with($redirect, '/') && !str_starts_with($redirect, '//')) {
    $validRedirect = $redirect;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $errors[] = 'Email and password are required.';
    } else {
        $result = loginUser($email, $password);
        if ($result['success']) {
            setFlash('success', 'Welcome back, ' . $_SESSION['user_name'] . '!');
            if ($result['role'] === 'admin') {
                $target = SITE_URL . '/admin/index.php';
            } elseif ($validRedirect) {
                $target = SITE_URL . $validRedirect;
            } else {
                $target = SITE_URL . '/index.php';
            }
            header('Location: ' . $target);
            exit;
        } else {
            $errors[] = $result['msg'];
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-box">
    <div class="auth-logo"><em>SOUND</em> GROUP</div>
    <h2>Welcome Back!</h2>
    <p class="auth-sub">Sign in to your account</p>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out'): ?>
      <div class="alert alert-success">✅ Logged out successfully.</div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?><p>❌ <?= clean($e) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($validRedirect): ?>
      <div class="alert alert-info" style="background: rgba(255, 45, 85, 0.12); border: 1px solid rgba(255, 45, 85, 0.4); color: #ff85a1; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13.5px;">
        <i class="fas fa-lock"></i> Please login or register to play and enjoy your selected media.
      </div>
    <?php endif; ?>

    <form action="" method="POST" class="auth-form">
      <?php if ($validRedirect): ?>
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($validRedirect) ?>">
      <?php endif; ?>
      <div class="form-group">
        <label>Email Address *</label>
        <input type="email" name="email" value="<?= isset($_POST['email']) ? clean($_POST['email']) : '' ?>" placeholder="your@email.com" required autofocus>
      </div>

      <div class="form-group">
        <label>Password *</label>
        <div class="input-icon">
          <input type="password" name="password" id="pass" placeholder="Enter your password" required>
          <span class="toggle-pass" onclick="togglePass('pass')"><i class="fas fa-eye"></i></span>
        </div>
      </div>

      <div class="form-check-row">
        <label><input type="checkbox" name="remember"> Remember me</label>
        <a href="#" class="forgot-link">Forgot Password?</a>
      </div>

      <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-sign-in-alt"></i> Login</button>
    </form>

    <div class="demo-creds">
      <p><strong>Demo Admin:</strong> admin@soundgroup.com / password</p>
      <p><strong>Demo User:</strong>  rahul@gmail.com / password</p>
    </div>

    <p class="auth-link">Don't have an account? <a href="<?= SITE_URL ?>/pages/register.php<?= $validRedirect ? '?redirect=' . urlencode($validRedirect) : '' ?>">Register here</a></p>
  </div>
</div>

<script>
function togglePass(id) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

