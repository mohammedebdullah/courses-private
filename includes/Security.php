<?php
/**
 * Security Class
 * Handles encryption, decryption, and security measures
 */

class Security {
    private static $cipher = 'aes-256-cbc';
    
    /**
     * Encrypt data
     */
    public static function encrypt($data, $key = null) {
        $key = $key ?: SECURE_KEY;
        $key = hash('sha256', $key, true);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$cipher));
        $encrypted = openssl_encrypt($data, self::$cipher, $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt data
     */
    public static function decrypt($data, $key = null) {
        $key = $key ?: SECURE_KEY;
        $key = hash('sha256', $key, true);
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length(self::$cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, self::$cipher, $key, 0, $iv);
    }
    
    /**
     * Generate secure token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Generate access code
     */
    public static function generateAccessCode() {
        $prefix = strtoupper(substr(md5(time()), 0, 4));
        $code = strtoupper(bin2hex(random_bytes(8)));
        return $prefix . '-' . substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . substr($code, 8, 4);
    }
    
    /**
     * Hash access code for storage
     */
    public static function hashCode($code) {
        return hash('sha256', strtoupper($code) . SECURE_KEY);
    }
    
    /**
     * Generate device fingerprint
     */
    public static function getDeviceFingerprint() {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''
        ];
        return hash('sha256', implode('|', $components));
    }
    
    /**
     * Validate and sanitize input
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $decayMinutes = 30) {
        $db = getDB();
        $since = date('Y-m-d H:i:s', strtotime("-{$decayMinutes} minutes"));
        
        $stmt = $db->prepare("
            SELECT COUNT(*) as attempts FROM activity_logs 
            WHERE (ip_address = ? OR description LIKE ?) 
            AND action = 'failed_login' 
            AND created_at > ?
        ");
        $stmt->execute([$identifier, "%$identifier%", $since]);
        $result = $stmt->fetch();
        
        return $result['attempts'] < $maxAttempts;
    }
    
    /**
     * Log activity
     */
    public static function logActivity($action, $description = '', $userId = null, $adminId = null, $metadata = null) {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, admin_id, action, description, ip_address, user_agent, metadata)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $adminId,
            $action,
            $description,
            get_client_ip(),
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $metadata ? json_encode($metadata) : null
        ]);
    }
    
    /**
     * Generate encrypted audio URL (optimized for speed)
     */
    public static function generateAudioToken($audioFileId, $userId, $sessionId) {
        $db = getDB();
        $token = self::generateToken(32);
        $expiresAt = date('Y-m-d H:i:s', time() + AUDIO_TOKEN_LIFETIME);
        
        // Skip the UPDATE query for performance - expired tokens are cleaned up separately
        // Just create new token directly
        $stmt = $db->prepare("
            INSERT INTO audio_tokens (token, audio_file_id, user_id, session_id, ip_address, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $token,
            $audioFileId,
            $userId,
            $sessionId,
            get_client_ip(),
            $expiresAt
        ]);
        
        return $token;
    }
    
    /**
     * Validate audio token
     * Tokens can be reused until they expire (needed for range requests/seeking)
     */
    public static function validateAudioToken($token) {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT at.*, af.file_path, af.stored_filename, af.mime_type, af.lesson_id,
                   l.start_datetime, l.end_datetime, l.status as lesson_status
            FROM audio_tokens at
            JOIN audio_files af ON at.audio_file_id = af.id
            JOIN lessons l ON af.lesson_id = l.id
            WHERE at.token = ? 
            AND at.expires_at > NOW() 
            AND at.ip_address = ?
        ");
        $stmt->execute([$token, get_client_ip()]);
        return $stmt->fetch();
    }
    
    /**
     * Mark audio token as used
     */
    public static function markTokenUsed($token) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE audio_tokens SET used = 1 WHERE token = ?");
        $stmt->execute([$token]);
    }
    
    /**
     * Add security headers
     */
    public static function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; media-src 'self' blob:; connect-src 'self';");
        header('Permissions-Policy: autoplay=(self), fullscreen=(self)');
    }
}
