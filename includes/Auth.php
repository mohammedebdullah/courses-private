<?php
/**
 * Authentication Class
 * Handles user and admin authentication
 */

class Auth {
    /**
     * Login with access code
     */
    public static function loginWithCode($code, $name, $email = null, $phone = null) {
        $db = getDB();
        
        // Check rate limiting
        if (!Security::checkRateLimit(get_client_ip())) {
            return ['success' => false, 'message' => 'هەولدانا زۆرە . هیڤیدکەم پاشی دوبارە هەولبد.'];
        }
        
        // Find and validate access code
        $accessCode = AccessCode::validate($code);
        
        if (!$accessCode) {
            Security::logActivity('failed_login', "Invalid access code attempt: $code");
            return ['success' => false, 'message' => 'کودێ خەلەتە یان سەرڤەچوویە.'];
        }
        
        // Check if code already has a user and it's not the same device
        if ($accessCode['user_id']) {
            // Code already used by another user
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$accessCode['user_id']]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser) {
                // Log in the existing user (single session enforcement handles the rest)
                $userId = $existingUser['id'];
            }
        } else {
            // Create new user
            $stmt = $db->prepare("
                INSERT INTO users (name, email, phone, access_code_id, status)
                VALUES (?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                Security::sanitize($name),
                $email ? Security::sanitize($email) : null,
                $phone ? Security::sanitize($phone) : null,
                $accessCode['id']
            ]);
            $userId = $db->lastInsertId();
            
            // Mark access code as used
            AccessCode::markUsed($accessCode['id'], $userId);
        }
        
        // Create session
        Session::createUserSession($userId);
        
        Security::logActivity('login', "User logged in with access code", $userId);
        
        return ['success' => true, 'user_id' => $userId];
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        $userId = Session::getUserId();
        if ($userId) {
            Security::logActivity('logout', "User logged out", $userId);
        }
        Session::destroySession();
    }
    
    /**
     * Admin login
     */
    public static function adminLogin($username, $password) {
        $db = getDB();
        
        // Check rate limiting
        if (!Security::checkRateLimit(get_client_ip())) {
            return ['success' => false, 'message' => 'هەولدانا زۆرە . هیڤیدکەم پاشی دوبارە هەولبد.'];
        }
        
        $stmt = $db->prepare("
            SELECT * FROM admins 
            WHERE (username = ? OR email = ?) 
            AND status = 'active'
        ");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();
        
        if (!$admin || !password_verify($password, $admin['password'])) {
            Security::logActivity('failed_login', "Failed admin login attempt: $username");
            return ['success' => false, 'message' => 'زانیاری خەلەتێن.'];
        }
        
        // Store admin session
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_login_time'] = time();
        
        Security::logActivity('admin_login', "Admin logged in", null, $admin['id']);
        
        return ['success' => true, 'admin' => $admin];
    }
    
    /**
     * Admin logout
     */
    public static function adminLogout() {
        $adminId = $_SESSION['admin_id'] ?? null;
        if ($adminId) {
            Security::logActivity('admin_logout', "Admin logged out", null, $adminId);
        }
        
        unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_name'], $_SESSION['admin_login_time']);
    }
    
    /**
     * Check if admin is logged in
     */
    public static function isAdminLoggedIn() {
        return isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0;
    }
    
    /**
     * Get current admin
     */
    public static function getAdmin() {
        if (!self::isAdminLoggedIn()) {
            return null;
        }
        
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admins WHERE id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch();
    }
    
    /**
     * Get current user (with session caching for performance)
     */
    public static function getUser() {
        $userId = Session::getUserId();
        if (!$userId) {
            return null;
        }
        
        // Check if user data is cached in session (performance optimization)
        if (isset($_SESSION['cached_user']) && 
            isset($_SESSION['cached_user_time']) && 
            (time() - $_SESSION['cached_user_time']) < 300) { // Cache for 5 minutes
            return $_SESSION['cached_user'];
        }
        
        // Fetch from database
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        // Cache in session
        if ($user) {
            $_SESSION['cached_user'] = $user;
            $_SESSION['cached_user_time'] = time();
        }
        
        return $user;
    }
    
    /**
     * Block user
     */
    public static function blockUser($userId) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET status = 'blocked' WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Invalidate all sessions
        Session::invalidateUserSessions($userId);
        
        Security::logActivity('user_blocked', "User blocked", $userId, $_SESSION['admin_id'] ?? null);
    }
    
    /**
     * Unblock user
     */
    public static function unblockUser($userId) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->execute([$userId]);
        
        Security::logActivity('user_unblocked', "User unblocked", $userId, $_SESSION['admin_id'] ?? null);
    }
}
