<?php
// =====================================================
// SOUND GROUP - Real-Time Live Play & View Counter API
// api/track-play.php
// =====================================================
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$type = clean($_GET['type'] ?? 'music');
$id   = (int)($_GET['id'] ?? 0);

if (!$id || !in_array($type, ['music', 'video'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$pdo = getDB();

if ($type === 'music') {
    // Increment play_count
    $pdo->prepare("UPDATE music SET play_count = play_count + 1 WHERE id = ?")->execute([$id]);
    $stmt = $pdo->prepare("SELECT play_count FROM music WHERE id = ?");
    $stmt->execute([$id]);
    $count = (int)$stmt->fetchColumn();

    echo json_encode([
        'success'         => true,
        'type'            => 'music',
        'id'              => $id,
        'raw_count'       => $count,
        'formatted_count' => number_format($count),
        'label'           => number_format($count) . ' plays'
    ]);
    exit;
} else {
    // Increment view_count
    $pdo->prepare("UPDATE videos SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);
    $stmt = $pdo->prepare("SELECT view_count FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $count = (int)$stmt->fetchColumn();

    echo json_encode([
        'success'         => true,
        'type'            => 'video',
        'id'              => $id,
        'raw_count'       => $count,
        'formatted_count' => number_format($count),
        'label'           => number_format($count) . ' views'
    ]);
    exit;
}

