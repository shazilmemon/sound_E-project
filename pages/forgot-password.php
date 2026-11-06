<?php
// =====================================================
// SOUND GROUP - Forgot / Reset Password Page
// pages/forgot-password.php
// =====================================================
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email           = trim($_POST['email'] ?? '');
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($email)) {
        $errors[] = 'Please enter your registered email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($newPassword)) {
        $errors[] = 'Please enter a new password.';
    } elseif (strlen($newPassword) < 6) {
        $errors[] = 'New password must be at least 6 characters long.';
    }

    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New password and Confirm password do not match.';
    }

    // Process reset if no validation errors
    if (empty($errors)) {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT id, full_name, email, status FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $errors[] = 'No account found with this email address. Please make sure you entered your registered email.';
            } elseif (isset($user['status']) && $user['status'] !== 'active') {
                $errors[] = 'This account is deactivated. Please contact support.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$newHash, $user['id']]);

                setFlash('success', 'Your password has been reset successfully! Please log in with your new password.');
                header('Location: ' . SITE_URL . '/pages/login.php?msg=pass_reset');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'A database error occurred. Please try again.';
        }
    }
}

$pageTitle = 'Forgot Password';
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-box">
    <div class="auth-logo"><em>SOUND</em> GROUP</div>
    <h2>Reset Password</h2>
    <p class="auth-sub">Enter your registered email and choose a new password</p>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?><p>❌ <?= clean($e) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form action="" method="POST" class="auth-form" id="forgotForm" novalidate>
      <div class="form-group">
        <label>Registered Email Address *</label>
        <input 
          type="email" 
          name="email" 
          id="email" 
          value="<?= isset($_POST['email']) ? clean($_POST['email']) : '' ?>" 
          placeholder="your@email.com" 
          required 
          autofocus
        >
        <div class="field-error-msg" id="emailError">Please enter a valid registered email address.</div>
      </div>

      <div class="form-group">
        <label>New Password * (Min 6 chars)</label>
        <div class="input-icon">
          <input 
            type="password" 
            name="new_password" 
            id="new_pass" 
            placeholder="Enter your new password" 
            required 
            minlength="6"
            autocomplete="new-password"
          >
          <span class="toggle-pass" onclick="togglePass('new_pass', this)" title="Show / Hide Password" aria-label="Toggle password visibility">
            <i class="fas fa-eye"></i>
          </span>
        </div>
        <div class="field-error-msg" id="passError">Password must be at least 6 characters long.</div>
      </div>

      <div class="form-group">
        <label>Confirm New Password *</label>
        <div class="input-icon">
          <input 
            type="password" 
            name="confirm_password" 
            id="confirm_pass" 
            placeholder="Confirm your new password" 
            required 
            minlength="6"
            autocomplete="new-password"
          >
          <span class="toggle-pass" onclick="togglePass('confirm_pass', this)" title="Show / Hide Password" aria-label="Toggle password visibility">
            <i class="fas fa-eye"></i>
          </span>
        </div>
        <div class="field-error-msg" id="confirmError">Passwords do not match.</div>
      </div>

      <button type="submit" class="btn btn-primary btn-full" style="margin-top: 10px;">
        <i class="fas fa-key"></i> Reset Password
      </button>

      <div class="auth-link" style="margin-top: 20px;">
        Remember your password? <a href="<?= SITE_URL ?>/pages/login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
      </div>
    </form>
  </div>
</div>

<script>
function togglePass(id, btn) {
  const input = document.getElementById(id);
  if (!input) return;
  const icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    if (icon) {
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
    }
    btn.setAttribute('title', 'Hide Password');
  } else {
    input.type = 'password';
    if (icon) {
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
    btn.setAttribute('title', 'Show Password');
  }
}

// Client-side instant validation
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('forgotForm');
  const emailInput = document.getElementById('email');
  const emailError = document.getElementById('emailError');
  const passInput = document.getElementById('new_pass');
  const passError = document.getElementById('passError');
  const confirmInput = document.getElementById('confirm_pass');
  const confirmError = document.getElementById('confirmError');

  const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

  function validateEmail() {
    const val = emailInput.value.trim();
    if (!val || !emailRegex.test(val)) {
      emailInput.classList.add('is-invalid');
      emailInput.classList.remove('is-valid');
      emailError.classList.add('visible');
      return false;
    } else {
      emailInput.classList.remove('is-invalid');
      emailInput.classList.add('is-valid');
      emailError.classList.remove('visible');
      return true;
    }
  }

  function validatePass() {
    const val = passInput.value;
    if (val.length < 6) {
      passInput.classList.add('is-invalid');
      passInput.classList.remove('is-valid');
      passError.classList.add('visible');
      return false;
    } else {
      passInput.classList.remove('is-invalid');
      passInput.classList.add('is-valid');
      passError.classList.remove('visible');
      return true;
    }
  }

  function validateConfirm() {
    const p1 = passInput.value;
    const p2 = confirmInput.value;
    if (!p2 || p1 !== p2) {
      confirmInput.classList.add('is-invalid');
      confirmInput.classList.remove('is-valid');
      confirmError.classList.add('visible');
      return false;
    } else {
      confirmInput.classList.remove('is-invalid');
      confirmInput.classList.add('is-valid');
      confirmError.classList.remove('visible');
      return true;
    }
  }

  if (emailInput) {
    emailInput.addEventListener('input', validateEmail);
    emailInput.addEventListener('blur', validateEmail);
  }

  if (passInput) {
    passInput.addEventListener('input', function() {
      validatePass();
      if (confirmInput.value.length > 0) validateConfirm();
    });
  }

  if (confirmInput) {
    confirmInput.addEventListener('input', validateConfirm);
  }

  if (form) {
    form.addEventListener('submit', function(e) {
      const vEmail = validateEmail();
      const vPass = validatePass();
      const vConfirm = validateConfirm();
      if (!vEmail || !vPass || !vConfirm) {
        e.preventDefault();
      }
    });
  }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
