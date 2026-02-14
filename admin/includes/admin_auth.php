<?php
/**
 * Admin Authentication Check
 */

require_once __DIR__ . '/../../includes/init.php';

// Check if admin is logged in
if (!Auth::isAdminLoggedIn()) {
    redirect('login.php');
}

// Get current admin
$currentAdmin = Auth::getAdmin();

if (!$currentAdmin) {
    Auth::adminLogout();
    redirect('login.php');
}

// Set security headers
Security::setSecurityHeaders();
