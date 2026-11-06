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
        <input type="email" name="email" id="email" value="<?= clean($old['email'] ?? '') ?>" placeholder="name@example.com" required autocomplete="email">
        <div class="field-error-msg" id="email-error">Please enter a valid email address (e.g. name@example.com).</div>
      </div>

      <div class="form-group">
        <label>Pakistan Mobile Number * <small>(11 Digits)</small></label>
        <div class="phone-input-wrapper" id="phone-wrapper">
          <div class="phone-prefix">
            <span class="flag-icon">🇵🇰</span> +92
          </div>
          <input type="tel" name="phone" id="phone" value="<?= clean($old['phone'] ?? '') ?>" placeholder="03001234567" maxlength="11" required autocomplete="tel" inputmode="numeric">
        </div>
        <div class="field-error-msg" id="phone-error">Please enter a valid 11-digit Pakistan mobile number starting with 03 (e.g. 03001234567).</div>
        <small style="color:var(--text-muted); font-size:11px; display:block; margin-top:4px;">Format: exactly 11 digits starting with 03 (e.g. 03001234567, 03451234567)</small>
      </div>

      <div class="form-group">
        <label>Address *</label>
        <input type="text" name="address" value="<?= clean($old['address'] ?? '') ?>" placeholder="Your complete address" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Password *</label>
          <div class="input-icon">
            <input type="password" name="password" id="pass" placeholder="Min 6 characters" required minlength="6" autocomplete="new-password">
            <span class="toggle-pass" onclick="togglePass('pass', this)" title="Show / Hide Password" aria-label="Toggle password visibility">
              <i class="fas fa-eye"></i>
            </span>
          </div>
          <div class="field-error-msg" id="pass-error">Password must be at least 6 characters long.</div>
        </div>
        <div class="form-group">
          <label>Confirm Password *</label>
          <div class="input-icon">
            <input type="password" name="confirm_password" id="cpass" placeholder="Re-enter password" required autocomplete="new-password">
            <span class="toggle-pass" onclick="togglePass('cpass', this)" title="Show / Hide Password" aria-label="Toggle password visibility">
              <i class="fas fa-eye"></i>
            </span>
          </div>
          <div class="field-error-msg" id="cpass-error">Passwords do not match.</div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-user-plus"></i> Create Account</button>
    </form>

    <p class="auth-link">Already have an account? <a href="<?= SITE_URL ?>/pages/login.php<?= $validRedirect ? '?redirect=' . urlencode($validRedirect) : '' ?>">Login here</a></p>
  </div>
</div>

<script>
// --- Eye Toggle for Password & Confirm Password (inside input box) ---
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

// --- Live Client-Side Validations ---
const emailInput   = document.getElementById('email');
const emailError   = document.getElementById('email-error');
const phoneInput   = document.getElementById('phone');
const phoneWrapper = document.getElementById('phone-wrapper');
const phoneError   = document.getElementById('phone-error');
const passInput    = document.getElementById('pass');
const passError    = document.getElementById('pass-error');
const cpassInput   = document.getElementById('cpass');
const cpassError   = document.getElementById('cpass-error');
const form         = document.querySelector('.auth-form');

// Email regex validation
function validateEmail() {
  const val = emailInput.value.trim();
  const re  = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  if (!val || !re.test(val)) {
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

// Pakistan 11-digit phone validation (e.g. 03001234567)
function validatePhone() {
  const val = phoneInput.value.trim();
  const re  = /^03[0-9]{9}$/;
  if (!val || !re.test(val)) {
    phoneWrapper.classList.add('is-invalid');
    phoneWrapper.classList.remove('is-valid');
    phoneError.classList.add('visible');
    return false;
  } else {
    phoneWrapper.classList.remove('is-invalid');
    phoneWrapper.classList.add('is-valid');
    phoneError.classList.remove('visible');
    return true;
  }
}

// Password validation (min 6 chars)
function validatePassword() {
  const val = passInput.value;
  if (!val || val.length < 6) {
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

// Confirm password match
function validateConfirmPassword() {
  const p  = passInput.value;
  const cp = cpassInput.value;
  if (!cp || p !== cp) {
    cpassInput.classList.add('is-invalid');
    cpassInput.classList.remove('is-valid');
    cpassError.classList.add('visible');
    return false;
  } else {
    cpassInput.classList.remove('is-invalid');
    cpassInput.classList.add('is-valid');
    cpassError.classList.remove('visible');
    return true;
  }
}

// Event Listeners for live feedback
emailInput.addEventListener('input', validateEmail);
emailInput.addEventListener('blur', validateEmail);

phoneInput.addEventListener('input', function() {
  // Only allow numbers, max 11 digits
  this.value = this.value.replace(/[^0-9]/g, '');
  if (this.value.length > 11) {
    this.value = this.value.slice(0, 11);
  }
  validatePhone();
});
phoneInput.addEventListener('blur', validatePhone);

passInput.addEventListener('input', function() {
  validatePassword();
  if (cpassInput.value.length > 0) validateConfirmPassword();
});

cpassInput.addEventListener('input', validateConfirmPassword);
cpassInput.addEventListener('blur', validateConfirmPassword);

// Form submit validation check
form.addEventListener('submit', function(e) {
  const isEmailOk = validateEmail();
  const isPhoneOk = validatePhone();
  const isPassOk  = validatePassword();
  const isCpassOk = validateConfirmPassword();

  if (!isEmailOk || !isPhoneOk || !isPassOk || !isCpassOk) {
    e.preventDefault();
    if (!isEmailOk) {
      emailInput.focus();
    } else if (!isPhoneOk) {
      phoneInput.focus();
    } else if (!isPassOk) {
      passInput.focus();
    } else if (!isCpassOk) {
      cpassInput.focus();
    }
  }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

