<?php
// =====================================================
// SOUND GROUP - Sync Duration API
// api/update-duration.php
// =====================================================
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$id       = (int)($_GET['id'] ?? 0);
$duration = clean($_GET['duration'] ?? '');

if (!$id || empty($duration)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Basic format check (e.g. 3:45 or 03:45 or 12:34)
if (preg_match('/^\d{1,3}:\d{2}$/', $duration)) {
    $pdo = getDB();
    $pdo->prepare("UPDATE music SET duration = ? WHERE id = ?")->execute([$duration, $id]);
    echo json_encode(['success' => true, 'id' => $id, 'duration' => $duration]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid duration format']);

