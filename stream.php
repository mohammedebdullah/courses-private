<?php
/**
 * Audio Streaming Endpoint
 * Secure token-based audio streaming
 * OPTIMIZED: Minimal session checks - token validation handles auth
 */

require_once __DIR__ . '/includes/init.php';

// Quick session check - just verify user_id exists in PHP session
// Full validation is already done by the audio token system
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Get token
$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    exit('Invalid request');
}

// Validate token
$tokenData = Security::validateAudioToken($token);

if (!$tokenData) {
    http_response_code(403);
    exit('Access denied or token expired');
}

// Verify token belongs to current user
if ($tokenData['user_id'] != Session::getUserId()) {
    // Don't log here - too frequent with audio range requests
    http_response_code(403);
    exit('Access denied');
}

// CRITICAL: Check lesson schedule (server-side enforcement to prevent bypass)
$availability = LessonSchedule::checkAvailability($tokenData);
if (!$availability['available']) {
    // Don't log here - checked on token generation already
    http_response_code(403);
    exit('ئەڤ وانە ئێدی یا بەردەست نینە');
}

// Get file path - handle both absolute and relative paths
$filePath = $tokenData['file_path'];

// If it's a relative path, prepend the base directory
if (!preg_match('/^[a-zA-Z]:[\\\\\\/]/', $filePath) && $filePath[0] !== '/') {
    $filePath = __DIR__ . '/' . $filePath;
}

// Normalize path
$filePath = realpath($filePath);

if (!$filePath || !file_exists($filePath)) {
    // Don't log here - too frequent, file issues are rare
    http_response_code(404);
    exit('File not found');
}

// Don't log streaming - audio players make 10-50+ requests per playback (range, buffer, seek)
// Logging every request causes massive performance issues

// Stream the audio
$stream = new AudioStream($filePath, $tokenData['mime_type']);
$stream->stream();
