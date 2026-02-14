<?php
/**
 * Access Code Management Class
 */

class AccessCode {
    /**
     * Generate new access code
     */
    public static function generate($adminId, $durationHours = null, $notes = null) {
        $db = getDB();
        
        $code = Security::generateAccessCode();
        $codeHash = Security::hashCode($code);
        $durationHours = $durationHours ?: 720; // 30 days default
        $validUntil = date('Y-m-d H:i:s', time() + ($durationHours * 3600));
        
        $stmt = $db->prepare("
            INSERT INTO access_codes (code, code_hash, duration_hours, valid_until, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $code,
            $codeHash,
            $durationHours,
            $validUntil,
            $notes,
            $adminId
        ]);
        
        Security::logActivity('code_generated', "Access code generated", null, $adminId);
        
        return [
            'id' => $db->lastInsertId(),
            'code' => $code,
            'valid_until' => $validUntil
        ];
    }
    
    /**
     * Generate multiple access codes
     */
    public static function generateBatch($adminId, $count, $durationHours = null, $notes = null) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = self::generate($adminId, $durationHours, $notes);
        }
        return $codes;
    }
    
    /**
     * Validate access code
     */
    public static function validate($code) {
        $db = getDB();
        $code = strtoupper(trim($code));
        
        $stmt = $db->prepare("
            SELECT * FROM access_codes 
            WHERE code = ? 
            AND status = 'active'
            AND valid_until > NOW()
            AND current_uses < max_uses
        ");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
    
    /**
     * Mark code as used
     */
    public static function markUsed($codeId, $userId) {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE access_codes 
            SET user_id = ?, 
                current_uses = current_uses + 1,
                status = CASE WHEN current_uses + 1 >= max_uses THEN 'used' ELSE status END,
                used_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$userId, $codeId]);
    }

    /**
     * Revoke access code
     */
    public static function revoke($codeId, $adminId = null) {
        $db = getDB();
        
        // Get the code and associated user
        $stmt = $db->prepare("SELECT * FROM access_codes WHERE id = ?");
        $stmt->execute([$codeId]);
        $code = $stmt->fetch();
        
        if (!$code) {
            return false;
        }
        
        // Update code status
        $stmt = $db->prepare("UPDATE access_codes SET status = 'revoked' WHERE id = ?");
        $stmt->execute([$codeId]);
        
        // If there's an associated user, also expire their account
        if ($code['user_id']) {
            $stmt = $db->prepare("UPDATE users SET status = 'expired' WHERE id = ?");
            $stmt->execute([$code['user_id']]);
            
            // Invalidate sessions
            Session::invalidateUserSessions($code['user_id']);
        }
        
        Security::logActivity('code_revoked', "Access code revoked: {$code['code']}", $code['user_id'], $adminId);
        
        return true;
    }
    
    /**
     * Get all codes with optional filtering
     */
    public static function getAll($status = null, $page = 1, $perPage = 20) {
        $db = getDB();
        $offset = ($page - 1) * $perPage;
        
        $sql = "
            SELECT ac.*, 
                   u.name as user_name,
                   u.email as user_email,
                   a.username as created_by_name
            FROM access_codes ac
            LEFT JOIN users u ON ac.user_id = u.id
            LEFT JOIN admins a ON ac.created_by = a.id
        ";
        
        $params = [];
        if ($status) {
            $sql .= " WHERE ac.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY ac.created_at DESC LIMIT $perPage OFFSET $offset";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get code statistics
     */
    public static function getStats() {
        $db = getDB();
        
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' AND valid_until > NOW() THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) as used,
                SUM(CASE WHEN status = 'expired' OR valid_until <= NOW() THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN status = 'revoked' THEN 1 ELSE 0 END) as revoked
            FROM access_codes
        ");
        
        return $stmt->fetch();
    }
    
    /**
     * Check and update expired codes
     */
    public static function cleanExpired() {
        $db = getDB();
        
        // Mark expired codes
        $stmt = $db->prepare("
            UPDATE access_codes 
            SET status = 'expired' 
            WHERE valid_until <= NOW() 
            AND status = 'active'
        ");
        $stmt->execute();
    }
    
    /**
     * Reactivate an access code for reuse
     */
    public static function reactivate($codeId, $adminId = null, $extendHours = null) {
        $db = getDB();
        
        // Get the code
        $stmt = $db->prepare("SELECT * FROM access_codes WHERE id = ?");
        $stmt->execute([$codeId]);
        $code = $stmt->fetch();
        
        if (!$code) {
            return false;
        }
        
        // Calculate new valid_until if extending
        if ($extendHours) {
            $validUntil = date('Y-m-d H:i:s', time() + ($extendHours * 3600));
        } else {
            // Use original duration from creation
            $validUntil = date('Y-m-d H:i:s', time() + ($code['duration_hours'] * 3600));
        }
        
        // Reset the code: set current_uses to 0, status to active, clear user association
        $stmt = $db->prepare("
            UPDATE access_codes 
            SET status = 'active',
                current_uses = 0,
                user_id = NULL,
                used_at = NULL,
                valid_until = ?
            WHERE id = ?
        ");
        $stmt->execute([$validUntil, $codeId]);
        
        // If there was an associated user, we can optionally deactivate them
        if ($code['user_id']) {
            $stmt = $db->prepare("UPDATE users SET status = 'expired' WHERE id = ?");
            $stmt->execute([$code['user_id']]);
            
            // Invalidate their sessions
            Session::invalidateUserSessions($code['user_id']);
        }
        
        Security::logActivity('code_reactivated', "Access code reactivated: {$code['code']}", null, $adminId);
        
        return true;
    }
}
