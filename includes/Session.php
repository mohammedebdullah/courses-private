<?php
/**
 * Session Management Class
 * Handles secure sessions with single-session-per-user enforcement
 */

class Session {
    /**
     * Initialize secure session
     */
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.use_only_cookies', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
            ini_set('session.cookie_lifetime', SESSION_LIFETIME); // Persist cookie across browser restarts
            
            // Use secure cookie if HTTPS
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            
            session_name('AUDIO_COURSE_SESS');
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } elseif (time() - $_SESSION['created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Create user session in database
     */
    public static function createUserSession($userId) {
        $db = getDB();
        
        // Invalidate all previous sessions for this user
        self::invalidateUserSessions($userId);
        
        // Create new session
        $sessionToken = Security::generateToken(64);
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        
        $stmt = $db->prepare("
            INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, device_fingerprint, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $sessionToken,
            get_client_ip(),
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            Security::getDeviceFingerprint(),
            $expiresAt
        ]);
        
        $sessionId = $db->lastInsertId();
        
        // Store in PHP session
        $_SESSION['user_id'] = $userId;
        $_SESSION['session_token'] = $sessionToken;
        $_SESSION['session_id'] = $sessionId;
        $_SESSION['login_time'] = time();
        
        return $sessionId;
    }
    
    /**
     * Validate user session (with caching to avoid repeated DB queries)
     */
    public static function validateUserSession() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
            return false;
        }
        
        // Cache validation for 60 seconds to avoid DB query on every API call
        $cacheKey = 'session_validated_at';
        if (isset($_SESSION[$cacheKey]) && (time() - $_SESSION[$cacheKey]) < 60) {
            return true; // Session was validated recently, trust it
        }
        
        $db = getDB();
        $stmt = $db->prepare("
            SELECT us.*, u.status as user_status
            FROM user_sessions us
            JOIN users u ON us.user_id = u.id
            WHERE us.session_token = ?
            AND us.user_id = ?
            AND us.is_active = 1
            AND us.expires_at > NOW()
        ");
        
        $stmt->execute([$_SESSION['session_token'], $_SESSION['user_id']]);
        $session = $stmt->fetch();
        
            return false;
        }
        
        // Check if user is blocked
        if ($session['user_status'] !== 'active') {
            self::destroySession();
            return false;
        }
        
        // Update last activity (only every 60 seconds to reduce DB writes)
        if (!isset($_SESSION['activity_updated_at']) || (time() - $_SESSION['activity_updated_at']) >= 60) {
            self::updateActivity($session['id']);
            $_SESSION['activity_updated_at'] = time();
        }
        
        // Cache validation timestamp
        $_SESSION[$cacheKey] = time();
        
        return $session;
    }
    
    /**
     * Update session activity
     */
    public static function updateActivity($sessionId) {
        $db = getDB();
        $newExpiry = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        
        $stmt = $db->prepare("
            UPDATE user_sessions 
            SET last_activity = NOW(), expires_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$newExpiry, $sessionId]);
    }
    
    /**
     * Invalidate all sessions for a user (single session enforcement)
     */
    public static function invalidateUserSessions($userId) {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE user_sessions 
            SET is_active = 0 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
    }
    
    /**
     * Destroy current session
     */
    public static function destroySession() {
        if (isset($_SESSION['session_token'])) {
            $db = getDB();
            $stmt = $db->prepare("
                UPDATE user_sessions 
                SET is_active = 0 
                WHERE session_token = ?
            ");
            $stmt->execute([$_SESSION['session_token']]);
        }
        
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return self::validateUserSession() !== false;
    }
    
    /**
     * Get current user ID
     */
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current session ID
     */
    public static function getSessionId() {
        return $_SESSION['session_id'] ?? null;
    }
    
    /**
     * Get current session token
     */
    public static function getSessionToken() {
        return $_SESSION['session_token'] ?? null;
    }
    
    /**
     * Clean expired sessions
     */
    public static function cleanExpiredSessions() {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE user_sessions 
            SET is_active = 0 
            WHERE expires_at < NOW() AND is_active = 1
        ");
        $stmt->execute();
        
        // Also clean old audio tokens
        $stmt = $db->prepare("DELETE FROM audio_tokens WHERE expires_at < NOW()");
        $stmt->execute();
    }
}
