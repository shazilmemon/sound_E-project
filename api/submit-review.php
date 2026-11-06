<?php
// =====================================================
// SOUND GROUP - AJAX Review & Rating Submission API
// api/submit-review.php
// =====================================================
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Please login to submit a review.']);
    exit;
}

$pdo = getDB();
$userId = (int)$_SESSION['user_id'];

// Can accept JSON body or POST form data
$mediaType  = clean($_POST['media_type'] ?? '');
$mediaId    = (int)($_POST['media_id'] ?? 0);
$rating     = (int)($_POST['rating'] ?? 0);
$reviewText = trim($_POST['review_text'] ?? '');

if (!in_array($mediaType, ['music', 'video']) || !$mediaId) {
    echo json_encode(['success' => false, 'error' => 'Invalid media item selected.']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Please select a rating between 1 and 5 stars.']);
    exit;
}

// Check if user already reviewed this item
$chk = $pdo->prepare("SELECT id FROM reviews WHERE user_id=? AND media_type=? AND media_id=?");
$chk->execute([$userId, $mediaType, $mediaId]);
$existingId = $chk->fetchColumn();

if ($existingId) {
    $st = $pdo->prepare("UPDATE reviews SET rating=?, review_text=?, status='approved', updated_at=NOW() WHERE id=?");
    $st->execute([$rating, $reviewText, $existingId]);
    $isUpdate = true;
} else {
    $st = $pdo->prepare("INSERT INTO reviews (user_id, media_type, media_id, rating, review_text, status) VALUES (?,?,?,?,?,'approved')");
    $st->execute([$userId, $mediaType, $mediaId, $rating, $reviewText]);
    $isUpdate = false;
}

// Recompute average rating and count
$avgStmt = $pdo->prepare("SELECT AVG(rating), COUNT(*) FROM reviews WHERE media_type=? AND media_id=? AND status='approved'");
$avgStmt->execute([$mediaType, $mediaId]);
[$avg, $cnt] = $avgStmt->fetch(PDO::FETCH_NUM);

$displayRating = ($avg !== null && $cnt > 0) ? number_format((float)$avg, 1) : '0.0';
$starCount = ($avg !== null && $cnt > 0) ? (int)round((float)$avg) : 0;

// Fetch current user details for the review card
$uStmt = $pdo->prepare("SELECT full_name, user_id AS uid FROM users WHERE id=?");
$uStmt->execute([$userId]);
$user = $uStmt->fetch();

echo json_encode([
    'success'       => true,
    'message'       => $isUpdate ? 'Your review has been updated successfully!' : 'Review submitted successfully!',
    'is_update'     => $isUpdate,
    'rating'        => $rating,
    'review_text'   => nl2br(clean($reviewText)),
    'avg_rating'    => (float)($avg ?? 0),
    'formatted_avg' => $displayRating,
    'review_count'  => (int)$cnt,
    'stars_html'    => $starCount > 0 ? str_repeat('⭐', $starCount) : '☆☆☆☆☆',
    'review_card'   => [
        'full_name'  => clean($user['full_name'] ?? 'User'),
        'uid'        => clean($user['uid'] ?? 'user'),
        'avatar'     => strtoupper(substr($user['full_name'] ?? 'U', 0, 1)),
        'rating'     => $rating,
        'rating_str' => str_repeat('⭐', $rating),
        'stars_gold' => str_repeat('★', $rating),
        'text'       => nl2br(clean($reviewText)),
        'date'       => date('M d, Y')
    ]
]);
exit;

