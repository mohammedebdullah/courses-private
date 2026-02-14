<?php
/**
 * API: Save Progress
 * Saves user's listening progress for a lesson
 * Optimized for frequent calls during audio playback
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// Verify AJAX request
if (!is_ajax()) {
    json_response(['success' => false, 'message' => 'Invalid request'], 400);
}

// Quick session check - just verify user_id exists in session
// Full validation is done by auth_check.php on page load
$userId = Session::getUserId();
if (!$userId) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!verify_csrf($input['csrf_token'] ?? '')) {
    json_response(['success' => false, 'message' => 'Invalid token'], 403);
}

$lessonId = intval($input['lesson_id'] ?? 0);
$progress = intval($input['progress'] ?? 0);
$completed = !empty($input['completed']);

if (!$lessonId) {
    json_response(['success' => false, 'message' => 'Invalid lesson ID'], 400);
}

$db = getDB();

// Insert or update progress (use $userId from quick session check above)
$stmt = $db->prepare("
    INSERT INTO user_progress (user_id, lesson_id, progress_seconds, completed)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        progress_seconds = GREATEST(progress_seconds, VALUES(progress_seconds)),
        completed = VALUES(completed) OR completed
");

$stmt->execute([$userId, $lessonId, $progress, $completed ? 1 : 0]);

json_response(['success' => true]);
