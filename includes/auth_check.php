<?php
/**
 * User Authentication Middleware
 * Optimized to minimize database queries
 */

require_once __DIR__ . '/init.php';

// Validate session (this already checks user status in one query)
$sessionData = Session::validateUserSession();
if (!$sessionData) {
    // Store intended URL for redirect after login
    $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
    redirect('index.php');
}

// Get current user from cache (Auth::getUser uses session caching)
$currentUser = Auth::getUser();

if (!$currentUser || $currentUser['status'] !== 'active') {
    Session::destroySession();
    redirect('index.php?error=blocked');
}

// Set security headers for all protected pages
Security::setSecurityHeaders();

// Clean expired sessions and tokens periodically (1% chance - reduced from 100% to minimize overhead)
if (rand(1, 100) === 1) {
    Session::cleanExpiredSessions();
    AccessCode::cleanExpired();
    
    // Clean expired audio tokens (performance: only 1% of requests)
    $db = getDB();
    $db->exec("DELETE FROM audio_tokens WHERE expires_at < NOW() OR used = 1");
}
