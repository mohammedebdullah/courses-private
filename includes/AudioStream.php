<?php
/**
 * Audio Streaming Class
 * Handles secure audio streaming with token-based access
 */

class AudioStream {
    private $filePath;
    private $mimeType;
    private $fileSize;
    
    public function __construct($filePath, $mimeType = 'audio/mpeg') {
        $this->filePath = $filePath;
        $this->mimeType = $mimeType;
        $this->fileSize = filesize($filePath);
    }
    
    /**
     * Stream audio file with range support
     */
    public function stream() {
        if (!file_exists($this->filePath)) {
            http_response_code(404);
            exit('File not found');
        }
        
        // Set headers to prevent caching and downloading
        header('Accept-Ranges: bytes');
        header('Content-Type: ' . $this->mimeType);
        header('Content-Disposition: inline');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Prevent embedding
        header('X-Content-Type-Options: nosniff');
        
        // Handle range requests for seeking
        $start = 0;
        $end = $this->fileSize - 1;
        
        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            
            if (preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
                $start = $matches[1] !== '' ? intval($matches[1]) : 0;
                $end = $matches[2] !== '' ? intval($matches[2]) : $this->fileSize - 1;
            }
            
            if ($start > $end || $start >= $this->fileSize) {
                http_response_code(416);
                header("Content-Range: bytes */{$this->fileSize}");
                exit;
            }
            
            http_response_code(206);
            header("Content-Range: bytes $start-$end/{$this->fileSize}");
        }
        
        $length = $end - $start + 1;
        header("Content-Length: $length");
        
        // Stream the file
        $this->streamChunks($start, $end);
    }
    
    /**
     * Stream file in chunks
     */
    private function streamChunks($start, $end) {
        $bufferSize = 8192; // 8KB chunks
        
        $fp = fopen($this->filePath, 'rb');
        if (!$fp) {
            http_response_code(500);
            exit('Cannot read file');
        }
        
        fseek($fp, $start);
        
        $remaining = $end - $start + 1;
        
        while ($remaining > 0 && !feof($fp) && connection_status() === CONNECTION_NORMAL) {
            $readSize = min($bufferSize, $remaining);
            $data = fread($fp, $readSize);
            
            if ($data === false) {
                break;
            }
            
            echo $data;
            flush();
            
            $remaining -= strlen($data);
        }
        
        fclose($fp);
    }
    
    /**
     * Get audio file duration using getID3 or ffprobe
     */
    public static function getDuration($filePath) {
        // Try using getID3 library first (most accurate)
        if (class_exists('getID3')) {
            try {
                require_once(APP_ROOT . '/vendor/james-heinrich/getid3/getid3/getid3.php');
                $getID3 = new getID3();
                $fileInfo = $getID3->analyze($filePath);
                
                // Check for errors
                if (isset($fileInfo['error'])) {
                    error_log("getID3 file error: " . implode(', ', $fileInfo['error']));
                }
                
                if (isset($fileInfo['playtime_seconds']) && $fileInfo['playtime_seconds'] > 0) {
                    return intval(round($fileInfo['playtime_seconds']));
                }
            } catch (Exception $e) {
                // Log error for debugging
                error_log("getID3 exception: " . $e->getMessage());
            }
        }
        
        // Try using ffprobe if available
        $output = [];
        $cmd = 'ffprobe -i ' . escapeshellarg($filePath) . ' -show_entries format=duration -v quiet -of csv="p=0" 2>&1';
        exec($cmd, $output, $returnCode);
        
        if ($returnCode === 0 && !empty($output[0]) && is_numeric($output[0])) {
            return intval(round(floatval($output[0])));
        }
        
        // Try reading MP3 duration using native PHP (for MP3 files)
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'mp3') {
            $duration = self::getMp3DurationNative($filePath);
            if ($duration > 0 && $duration < 86400) { // Sanity check: less than 24 hours
                return $duration;
            }
        }
        
        // Fallback: estimate based on file size (assuming 128kbps MP3)
        $fileSize = filesize($filePath);
        $estimatedBitrate = 128; // 128 kbps
        $duration = intval($fileSize / ($estimatedBitrate * 1024 / 8)); // Fixed: 1024 not 1000
        
        // Sanity check
        if ($duration > 86400) { // More than 24 hours is probably wrong
            $duration = intval($fileSize / (192 * 1024 / 8)); // Try higher bitrate
        }
        
        return $duration;
    }
    
    /**
     * Get MP3 duration using native PHP (fallback when getID3 not available)
     */
    private static function getMp3DurationNative($filePath) {
        $fd = @fopen($filePath, 'rb');
        if (!$fd) {
            return 0;
        }
        
        $duration = 0;
        $bitrate = 128000; // Default bitrate
        
        // Read first 10KB to find bitrate
        $data = fread($fd, 10240);
        fclose($fd);
        
        // Look for MP3 frame header
        for ($i = 0; $i < strlen($data) - 4; $i++) {
            if (ord($data[$i]) == 0xFF && (ord($data[$i + 1]) & 0xE0) == 0xE0) {
                $header = (ord($data[$i + 1]) << 16) | (ord($data[$i + 2]) << 8) | ord($data[$i + 3]);
                
                // Extract bitrate index
                $bitrateIndex = ($header >> 12) & 0x0F;
                $sampleRateIndex = ($header >> 10) & 0x03;
                
                // Bitrate table for MPEG1 Layer 3
                $bitrates = [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 0];
                $sampleRates = [44100, 48000, 32000];
                
                if ($bitrateIndex > 0 && $bitrateIndex < 15 && $sampleRateIndex < 3) {
                    $bitrate = $bitrates[$bitrateIndex] * 1000;
                    break;
                }
            }
        }
        
        // Calculate duration based on file size and bitrate
        $fileSize = filesize($filePath);
        if ($bitrate > 0) {
            $duration = intval(($fileSize * 8) / $bitrate);
        }
        
        return $duration;
    }
    
    /**
     * Generate encrypted filename for storage
     */
    public static function generateStoredFilename($originalFilename) {
        $ext = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $hash = hash('sha256', $originalFilename . time() . random_bytes(16));
        return $hash . '.' . strtolower($ext);
    }
    
    /**
     * Upload and store audio file
     */
    public static function upload($file, $lessonId) {
        $db = getDB();
        
        // Validate file
        $allowedTypes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/m4a', 'audio/x-m4a'];
        $maxSize = 200 * 1024 * 1024; // 200MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Allowed: MP3, WAV, OGG, M4A'];
        }
        
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File too large. Maximum size: 200MB'];
        }
        
        // Generate secure filename
        $storedFilename = self::generateStoredFilename($file['name']);
        $uploadPath = AUDIO_PATH . $storedFilename;
        
        // Create directory if not exists
        if (!is_dir(AUDIO_PATH)) {
            mkdir(AUDIO_PATH, 0755, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => false, 'message' => 'Failed to save file'];
        }
        
        // Get duration
        $duration = self::getDuration($uploadPath);
        
        // Store relative path instead of absolute path for cross-platform compatibility
        $relativePath = 'uploads/audio/' . $storedFilename;
        
        // Store in database
        $stmt = $db->prepare("
            INSERT INTO audio_files (lesson_id, original_filename, stored_filename, file_path, file_size, mime_type, duration)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $lessonId,
            $file['name'],
            $storedFilename,
            $relativePath,
            $file['size'],
            $file['type'],
            $duration
        ]);
        
        return [
            'success' => true,
            'id' => $db->lastInsertId(),
            'filename' => $storedFilename,
            'duration' => $duration
        ];
    }
    
    /**
     * Delete audio file
     */
    public static function delete($audioFileId) {
        $db = getDB();
        
        // Get file info
        $stmt = $db->prepare("SELECT * FROM audio_files WHERE id = ?");
        $stmt->execute([$audioFileId]);
        $file = $stmt->fetch();
        
        if (!$file) {
            return false;
        }
        
        // Handle both absolute and relative paths
        $filePath = $file['file_path'];
        if (!preg_match('/^[a-zA-Z]:[\\\\\\/]/', $filePath) && $filePath[0] !== '/') {
            // Relative path, prepend base directory
            $filePath = APP_ROOT . '/' . $filePath;
        }
        
        // Delete physical file
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete database record
        $stmt = $db->prepare("DELETE FROM audio_files WHERE id = ?");
        $stmt->execute([$audioFileId]);
        
        return true;
    }
}
