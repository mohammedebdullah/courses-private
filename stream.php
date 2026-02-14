<?php
/**
 * Audio Streaming Endpoint
 * Secure token-based audio streaming
 */

require_once __DIR__ . '/includes/init.php';

// Verify user is logged in
if (!Session::isLoggedIn()) {
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
    Security::logActivity('unauthorized_audio_access', "Token user mismatch", Session::getUserId());
    http_response_code(403);
    exit('Access denied');
}

// CRITICAL: Check lesson schedule (server-side enforcement to prevent bypass)
$availability = LessonSchedule::checkAvailability($tokenData);
if (!$availability['available']) {
    Security::logActivity('audio_stream_denied_schedule', 
        "Stream denied - lesson {$tokenData['lesson_id']}: {$availability['status']}", 
        Session::getUserId());
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
    Security::logActivity('audio_file_not_found', "File not found: " . ($tokenData['file_path'] ?? 'unknown'), Session::getUserId());
    http_response_code(404);
    exit('File not found');
}

// Log access
Security::logActivity('audio_stream', "Streaming audio file", Session::getUserId());

// Stream the audio
$stream = new AudioStream($filePath, $tokenData['mime_type']);
$stream->stream();
