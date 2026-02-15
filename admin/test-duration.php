<?php
/**
 * Test Audio Duration Detection
 * This page helps diagnose duration detection issues
 */

require_once __DIR__ . '/includes/admin_auth.php';

$db = getDB();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Duration Detection</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .warning { background: #fff3cd; color: #856404; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>

<h1>🔍 Audio Duration Detection Test</h1>
<p><a href="lessons.php">← Back to Lessons</a></p>

<h2>System Information</h2>
<div class="result info">
    <strong>PHP Version:</strong> <?= PHP_VERSION ?><br>
    <strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?><br>
    <strong>App Root:</strong> <?= APP_ROOT ?>
</div>

<h2>Library Check</h2>
<?php
// Check getID3
if (class_exists('getID3')) {
    echo '<div class="result success">✅ getID3 library is loaded</div>';
} else {
    echo '<div class="result error">❌ getID3 library is NOT loaded</div>';
    if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
        echo '<div class="result warning">⚠ Composer autoload exists but getID3 class not found</div>';
    } else {
        echo '<div class="result error">❌ Composer vendor/autoload.php not found</div>';
    }
}

// Check ffprobe
exec('ffprobe -version 2>&1', $output, $returnCode);
if ($returnCode === 0) {
    echo '<div class="result success">✅ ffprobe is available</div>';
} else {
    echo '<div class="result warning">⚠ ffprobe is NOT available</div>';
}
?>

<h2>Audio Files Test</h2>
<?php
// Get all audio files
$stmt = $db->query("SELECT af.*, l.title as lesson_title 
                    FROM audio_files af 
                    JOIN lessons l ON af.lesson_id = l.id 
                    ORDER BY af.id DESC 
                    LIMIT 10");
$audioFiles = $stmt->fetchAll();

if (empty($audioFiles)) {
    echo '<div class="result warning">⚠ No audio files found in database</div>';
} else {
    echo '<table>';
    echo '<tr>
            <th>ID</th>
            <th>Lesson</th>
            <th>File</th>
            <th>DB Duration</th>
            <th>Actual Duration</th>
            <th>File Size</th>
            <th>Status</th>
          </tr>';
    
    foreach ($audioFiles as $audio) {
        $filePath = APP_ROOT . '/' . $audio['file_path'];
        $fileExists = file_exists($filePath);
        
        if ($fileExists) {
            $actualDuration = AudioStream::getDuration($filePath);
            $dbDuration = $audio['duration'];
            $match = abs($actualDuration - $dbDuration) < 5; // Within 5 seconds
            
            echo '<tr>';
            echo '<td>' . $audio['id'] . '</td>';
            echo '<td>' . htmlspecialchars($audio['lesson_title']) . '</td>';
            echo '<td>' . htmlspecialchars($audio['original_filename']) . '</td>';
            echo '<td>' . gmdate('H:i:s', $dbDuration) . ' (' . $dbDuration . 's)</td>';
            echo '<td>' . gmdate('H:i:s', $actualDuration) . ' (' . $actualDuration . 's)</td>';
            echo '<td>' . number_format($audio['file_size'] / 1024 / 1024, 2) . ' MB</td>';
            echo '<td>' . ($match ? '<span style="color: green;">✅ OK</span>' : '<span style="color: red;">❌ Mismatch</span>') . '</td>';
            echo '</tr>';
        } else {
            echo '<tr>';
            echo '<td>' . $audio['id'] . '</td>';
            echo '<td>' . htmlspecialchars($audio['lesson_title']) . '</td>';
            echo '<td colspan="5"><span style="color: red;">❌ File not found: ' . htmlspecialchars($audio['file_path']) . '</span></td>';
            echo '</tr>';
        }
    }
    
    echo '</table>';
}
?>

<h2>Detection Method Used</h2>
<?php
if (!empty($audioFiles) && file_exists(APP_ROOT . '/' . $audioFiles[0]['file_path'])) {
    $testFile = APP_ROOT . '/' . $audioFiles[0]['file_path'];
    
    echo '<div class="result info">';
    echo '<strong>Testing with:</strong> ' . htmlspecialchars($audioFiles[0]['original_filename']) . '<br><br>';
    
    // Test getID3
    if (class_exists('getID3')) {
        try {
            $getID3 = new \getID3();
            $fileInfo = $getID3->analyze($testFile);
            if (isset($fileInfo['playtime_seconds'])) {
                echo '✅ getID3: ' . gmdate('H:i:s', intval($fileInfo['playtime_seconds'])) . '<br>';
            } else {
                echo '❌ getID3: Could not detect duration<br>';
            }
        } catch (Exception $e) {
            echo '❌ getID3 Error: ' . htmlspecialchars($e->getMessage()) . '<br>';
        }
    }
    
    // Test ffprobe
    $output = [];
    $cmd = 'ffprobe -i ' . escapeshellarg($testFile) . ' -show_entries format=duration -v quiet -of csv="p=0" 2>&1';
    exec($cmd, $output, $returnCode);
    if ($returnCode === 0 && !empty($output[0]) && is_numeric($output[0])) {
        echo '✅ ffprobe: ' . gmdate('H:i:s', intval(floatval($output[0]))) . '<br>';
    } else {
        echo '❌ ffprobe: Not available or failed<br>';
    }
    
    // Test native PHP
    $fileSize = filesize($testFile);
    $estimatedDuration = intval($fileSize / (128 * 1000 / 8));
    echo '⚠ Fallback (file size estimate): ' . gmdate('H:i:s', $estimatedDuration) . '<br>';
    
    echo '</div>';
}
?>

<h2>Recommendations</h2>
<div class="result info">
    <?php if (!class_exists('getID3')): ?>
    <p>❌ <strong>getID3 library is not loaded.</strong></p>
    <p>On your production server, you need to:</p>
    <ol>
        <li>Upload the <code>vendor/</code> folder from your local project</li>
        <li>Or run <code>composer install</code> on the server</li>
        <li>Make sure <code>vendor/autoload.php</code> is included in <code>includes/init.php</code></li>
    </ol>
    <?php else: ?>
    <p>✅ getID3 is loaded correctly</p>
    <?php endif; ?>
</div>

</body>
</html>
