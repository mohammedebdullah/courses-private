<?php
/**
 * Fix Audio Durations - Update all audio files with correct duration
 */

require_once __DIR__ . '/includes/admin_auth.php';

$db = getDB();
$updated = 0;
$failed = 0;

// Get all audio files
$stmt = $db->query("SELECT id, file_path, stored_filename, duration FROM audio_files");
$audioFiles = $stmt->fetchAll();

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Fix Audio Durations</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;max-width:800px;margin:0 auto;}h2{color:#333;}.success{color:green;}.error{color:red;}.info{color:#666;}</style>";
echo "</head><body>";
echo "<h2>تازەکرنا درێژایا دەنگێ</h2>";
echo "<p>هاتنە دیتن " . count($audioFiles) . " پەڕگێن دەنگی...</p>";
echo "<hr>";

foreach ($audioFiles as $audio) {
    $filePath = APP_ROOT . '/' . $audio['file_path'];
    
    if (!file_exists($filePath)) {
        echo "<p class='error'>❌ پەڕگ نەهاتە دیتن: {$audio['stored_filename']}</p>";
        $failed++;
        continue;
    }
    
    // Get correct duration using getID3
    $duration = AudioStream::getDuration($filePath);
    
    if ($duration > 0 && $duration != $audio['duration']) {
        // Update database
        $stmt = $db->prepare("UPDATE audio_files SET duration = ? WHERE id = ?");
        $stmt->execute([$duration, $audio['id']]);
        
        $oldDuration = gmdate('H:i:s', $audio['duration']);
        $newDuration = gmdate('H:i:s', $duration);
        
        echo "<p class='success'>✅ هاتە نوێکرن: {$audio['stored_filename']} - {$oldDuration} → {$newDuration}</p>";
        $updated++;
    } else if ($duration > 0) {
        $durationFormatted = gmdate('H:i:s', $duration);
        echo "<p class='info'>✓ راست: {$audio['stored_filename']} - {$durationFormatted}</p>";
    } else {
        echo "<p class='error'>⚠ نەشیا درێژایی بهێتە دیتن: {$audio['stored_filename']}</p>";
        $failed++;
    }
    
    // Flush output to show progress
    flush();
    if (ob_get_level() > 0) {
        ob_flush();
    }
}

echo "<hr>";
echo "<h3>کورتی:</h3>";
echo "<p><strong>هاتنە نوێکرن:</strong> $updated پەڕگ</p>";
echo "<p><strong>شکستی:</strong> $failed پەڕگ</p>";
echo "<p><a href='lessons.php'>← ڤەگەڕان بو وانان</a></p>";
echo "</body></html>";
