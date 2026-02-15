<?php
/**
 * Debug Specific Audio File
 */

require_once __DIR__ . '/includes/admin_auth.php';

$db = getDB();

// Get the problematic file
$stmt = $db->prepare("SELECT af.*, l.title as lesson_title 
                      FROM audio_files af 
                      JOIN lessons l ON af.lesson_id = l.id 
                      WHERE af.stored_filename = ?");
$stmt->execute(['fbb2b2c9230bd373c0ccadf88ad0f6263e18d08d51f9503bc9d0190754355645.mp3']);
$audioFile = $stmt->fetch();

if (!$audioFile) {
    die('File not found in database');
}

$filePath = APP_ROOT . '/' . $audioFile['file_path'];

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug Audio File</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .info { background: #e7f3ff; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #ffe7e7; padding: 15px; margin: 10px 0; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>

<h1>🔍 Debug Audio File</h1>
<p><a href="test-duration.php">← Back to Test</a></p>

<div class="info">
    <strong>Lesson:</strong> <?= htmlspecialchars($audioFile['lesson_title']) ?><br>
    <strong>File:</strong> <?= htmlspecialchars($audioFile['original_filename']) ?><br>
    <strong>Stored as:</strong> <?= htmlspecialchars($audioFile['stored_filename']) ?><br>
    <strong>Path:</strong> <?= htmlspecialchars($audioFile['file_path']) ?><br>
    <strong>DB Duration:</strong> <?= gmdate('H:i:s', $audioFile['duration']) ?> (<?= $audioFile['duration'] ?> seconds)<br>
    <strong>File Size:</strong> <?= number_format($audioFile['file_size'] / 1024 / 1024, 2) ?> MB
</div>

<h2>File Exists Check</h2>
<?php if (file_exists($filePath)): ?>
    <div class="info">✅ File exists at: <?= htmlspecialchars($filePath) ?></div>
<?php else: ?>
    <div class="error">❌ File NOT found at: <?= htmlspecialchars($filePath) ?></div>
    <?php exit; ?>
<?php endif; ?>

<h2>getID3 Analysis</h2>
<?php
if (class_exists('getID3')) {
    try {
        require_once(APP_ROOT . '/vendor/james-heinrich/getid3/getid3/getid3.php');
        $getID3 = new getID3();
        $getID3->option_md5_data = false;
        $getID3->option_md5_data_source = false;
        $getID3->encoding = 'UTF-8';
        
        $fileInfo = $getID3->analyze($filePath);
        
        echo '<div class="info">';
        echo '<strong>getID3 Results:</strong><br><br>';
        
        if (isset($fileInfo['error'])) {
            echo '<strong style="color: red;">Errors:</strong><br>';
            echo '<pre>' . print_r($fileInfo['error'], true) . '</pre>';
        }
        
        if (isset($fileInfo['warning'])) {
            echo '<strong style="color: orange;">Warnings:</strong><br>';
            echo '<pre>' . print_r($fileInfo['warning'], true) . '</pre>';
        }
        
        echo '<strong>Playtime:</strong><br>';
        echo '&nbsp;&nbsp;Seconds: ' . ($fileInfo['playtime_seconds'] ?? 'N/A') . '<br>';
        echo '&nbsp;&nbsp;String: ' . ($fileInfo['playtime_string'] ?? 'N/A') . '<br><br>';
        
        if (isset($fileInfo['audio'])) {
            echo '<strong>Audio Info:</strong><br>';
            echo '&nbsp;&nbsp;Dataformat: ' . ($fileInfo['audio']['dataformat'] ?? 'N/A') . '<br>';
            echo '&nbsp;&nbsp;Bitrate: ' . (isset($fileInfo['audio']['bitrate']) ? intval($fileInfo['audio']['bitrate'] / 1000) . ' kbps' : 'N/A') . '<br>';
            echo '&nbsp;&nbsp;Sample Rate: ' . ($fileInfo['audio']['sample_rate'] ?? 'N/A') . '<br>';
            echo '&nbsp;&nbsp;Channels: ' . ($fileInfo['audio']['channels'] ?? 'N/A') . '<br>';
        }
        
        echo '<br><strong>Full getID3 Data:</strong><br>';
        echo '<pre>' . print_r($fileInfo, true) . '</pre>';
        
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="error">❌ getID3 Exception: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
} else {
    echo '<div class="error">❌ getID3 class not available</div>';
}
?>

<h2>File Size Estimates</h2>
<div class="info">
<?php
$fileSize = filesize($filePath);
echo '<strong>Calculating duration based on different bitrates:</strong><br><br>';
foreach ([64, 96, 128, 160, 192, 256, 320] as $bitrate) {
    $duration = intval($fileSize / ($bitrate * 1024 / 8));
    $formatted = gmdate('H:i:s', $duration);
    echo sprintf('%3d kbps: %s (%d seconds)<br>', $bitrate, $formatted, $duration);
}
?>
</div>

<h2>Manual Fix</h2>
<div class="info">
    <p>If you know the correct duration, you can manually update it:</p>
    <form method="POST">
        <?= csrf_field() ?>
        <label>Correct duration (in seconds): </label>
        <input type="number" name="correct_duration" value="<?= $audioFile['duration'] ?>" min="0" max="86400">
        <button type="submit" name="action" value="update">Update Duration</button>
    </form>
</div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $newDuration = intval($_POST['correct_duration']);
        $stmt = $db->prepare("UPDATE audio_files SET duration = ? WHERE id = ?");
        $stmt->execute([$newDuration, $audioFile['id']]);
        echo '<div class="info">✅ Duration updated to ' . gmdate('H:i:s', $newDuration) . '</div>';
        echo '<script>setTimeout(() => window.location.reload(), 2000);</script>';
    }
}
?>

</body>
</html>
