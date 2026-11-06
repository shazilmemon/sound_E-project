<?php
// =====================================================
// SOUND GROUP - Session & Auth Helper
// includes/auth.php
// =====================================================

require_once __DIR__ . '/../config/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Check if logged in ----
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// ---- Check if admin ----
function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// ---- Require login (redirect if not) ----
function requireLogin(string $redirect = '/index.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . $redirect);
        exit;
    }
}

// ---- Require admin ----
function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/index.php?msg=unauthorized');
        exit;
    }
}

// ---- Get current user ----
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// ---- Register new user ----
function registerUser(array $data): array {
    $pdo = getDB();

    // Validate required fields
    $required = ['user_id', 'full_name', 'email', 'phone', 'address', 'password'];
    foreach ($required as $field) {
        if (empty(trim($data[$field] ?? ''))) {
            return ['success' => false, 'msg' => ucfirst(str_replace('_', ' ', $field)) . ' is required.'];
        }
    }

    // Validate email
    $email = trim($data['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        return ['success' => false, 'msg' => 'Please enter a valid email address (e.g. name@example.com).'];
    }

    // Validate Pakistan phone number (exactly 11 digits, starting with 03)
    $rawPhone = trim($data['phone'] ?? '');
    $digits   = preg_replace('/[^0-9]/', '', $rawPhone);
    if (strlen($digits) === 12 && str_starts_with($digits, '923')) {
        $digits = '0' . substr($digits, 2);
    } elseif (strlen($digits) === 10 && str_starts_with($digits, '3')) {
        $digits = '0' . $digits;
    }

    if (!preg_match('/^03[0-9]{9}$/', $digits)) {
        return ['success' => false, 'msg' => 'Please enter a valid 11-digit Pakistan mobile number starting with 03 (e.g. 03001234567).'];
    }
    $savedPhone = '+92 ' . substr($digits, 1);

    // Password validation & match
    if (strlen($data['password']) < 6) {
        return ['success' => false, 'msg' => 'Password must be at least 6 characters long.'];
    }
    if (isset($data['confirm_password']) && $data['password'] !== $data['confirm_password']) {
        return ['success' => false, 'msg' => 'Password and Confirm Password do not match.'];
    }

    // Check unique user_id
    $stmt = $pdo->prepare("SELECT id FROM users WHERE user_id = ?");
    $stmt->execute([trim($data['user_id'])]);
    if ($stmt->fetch()) {
        return ['success' => false, 'msg' => 'User ID already taken. Please choose another.'];
    }

    // Check unique email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([trim($data['email'])]);
    if ($stmt->fetch()) {
        return ['success' => false, 'msg' => 'Email already registered.'];
    }

    // Hash password
    $hash = password_hash($data['password'], PASSWORD_BCRYPT);

    // Insert
    $stmt = $pdo->prepare("INSERT INTO users (user_id, full_name, email, phone, address, password)
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        trim($data['user_id']),
        trim($data['full_name']),
        trim($data['email']),
        $savedPhone,
        trim($data['address']),
        $hash
    ]);

    return ['success' => true, 'msg' => 'Account created successfully! Please login.'];
}

// ---- Login ----
function loginUser(string $email, string $password): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'msg' => 'Invalid email or password.'];
    }

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_uid']  = $user['user_id'];
    $_SESSION['role']      = $user['role'];

    return ['success' => true, 'msg' => 'Login successful!', 'role' => $user['role']];
}

// ---- Logout ----
function logoutUser(): void {
    session_destroy();
    header('Location: ' . SITE_URL . '/index.php?msg=logged_out');
    exit;
}

// ---- Helper to resolve image and media URLs (local or external) ----
function resolveMediaUrl(?string $path, string $type = 'cover'): string {
    if (empty($path)) {
        if ($type === 'music') return SITE_URL . '/assets/uploads/music/song1.mp3';
        if ($type === 'video') return SITE_URL . '/assets/uploads/videos/video1.mp4';
        return 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=500&auto=format&fit=crop&q=80';
    }
    // If it's already an absolute URL (http / https)
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    // Otherwise it's a relative path in assets
    if ($type === 'cover') {
        return SITE_URL . '/assets/images/covers/' . ltrim($path, '/');
    } elseif ($type === 'music') {
        return SITE_URL . '/assets/uploads/music/' . ltrim($path, '/');
    } elseif ($type === 'video') {
        return SITE_URL . '/assets/uploads/videos/' . ltrim($path, '/');
    } elseif ($type === 'artist') {
        return SITE_URL . '/assets/images/artists/' . ltrim($path, '/');
    }
    return SITE_URL . '/' . ltrim($path, '/');
}

// ---- Sanitize input ----
function clean(string $str): string {
    return htmlspecialchars(strip_tags(trim($str)));
}

// ---- Flash message ----
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

