<?php
/**
 * API: Get Audio Token
 * Generates a short-lived token for audio streaming
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// Verify AJAX request
if (!is_ajax()) {
    json_response(['success' => false, 'message' => 'Invalid request'], 400);
}

// Verify user is logged in
if (!Session::isLoggedIn()) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!verify_csrf($input['csrf_token'] ?? '')) {
    json_response(['success' => false, 'message' => 'Invalid token'], 403);
}

$lessonId = intval($input['lesson_id'] ?? 0);

if (!$lessonId) {
    json_response(['success' => false, 'message' => 'Invalid lesson ID'], 400);
}

$db = getDB();

// Get lesson and audio file
$stmt = $db->prepare("
    SELECT l.*, c.id as course_id, c.title as course_title,
           af.id as audio_id, af.file_path, af.stored_filename, af.mime_type, af.duration
    FROM lessons l
    JOIN courses c ON l.course_id = c.id
    LEFT JOIN audio_files af ON l.id = af.lesson_id
    WHERE l.id = ? AND l.status = 'active' AND c.status = 'active'
");
$stmt->execute([$lessonId]);
$lesson = $stmt->fetch();

if (!$lesson) {
    json_response(['success' => false, 'message' => 'Lesson not found'], 404);
}

// Check lesson schedule availability (server-side enforcement)
$availability = LessonSchedule::checkAvailability($lesson);
if (!$availability['available']) {
    Security::logActivity('audio_access_denied_schedule', "Access denied due to schedule: {$lesson['title']}", Session::getUserId());
    json_response(['success' => false, 'message' => $availability['message']], 403);
}

if (!$lesson['audio_id']) {
    json_response(['success' => false, 'message' => 'No audio file available'], 404);
}

// Generate audio access token
$token = Security::generateAudioToken(
    $lesson['audio_id'],
    Session::getUserId(),
    Session::getSessionId()
);

// Log access
Security::logActivity('audio_token_generated', "Token generated for lesson: {$lesson['title']}", Session::getUserId());

json_response([
    'success' => true,
    'token' => $token,
    'lesson' => [
        'id' => $lesson['id'],
        'title' => $lesson['title'],
        'duration' => $lesson['duration']
    ],
    'course' => [
        'id' => $lesson['course_id'],
        'title' => $lesson['course_title']
    ]
]);
