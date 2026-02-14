<?php
/**
 * User Authentication Middleware
 */

require_once __DIR__ . '/init.php';

// Check if user is authenticated
if (!Session::isLoggedIn()) {
    // Store intended URL for redirect after login
    $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
    redirect('index.php');
}

// Get current user
$currentUser = Auth::getUser();

if (!$currentUser || $currentUser['status'] !== 'active') {
    Session::destroySession();
    redirect('index.php?error=blocked');
}

// Set security headers for all protected pages
Security::setSecurityHeaders();

// Clean expired sessions periodically (1% chance)
if (rand(1, 100) === 1) {
    Session::cleanExpiredSessions();
    AccessCode::cleanExpired();
}
