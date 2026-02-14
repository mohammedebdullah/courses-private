<?php
/**
 * Database Migration Script: Fix Audio File Paths
 * 
 * This script converts absolute audio file paths to relative paths
 * for cross-platform compatibility.
 * 
 * IMPORTANT: Delete this file after running it successfully!
 */

// Only allow access from localhost or when specifically authorized
$allowed_ips = ['127.0.0.1', '::1'];
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed_ips) && !isset($_GET['authorize'])) {
    die('Access denied. This script can only be run from localhost or with ?authorize parameter.');
}

require_once __DIR__ . '/includes/init.php';

$db = getDB();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration: Fix Audio Paths</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 Database Migration: Fix Audio File Paths</h1>
    
    <div class="info">
        <strong>Purpose:</strong> This script converts absolute audio file paths (e.g., C:\xampp\htdocs\...) 
        to relative paths (e.g., uploads/audio/filename.mp3) for cross-platform compatibility.
    </div>

    <?php
    if (isset($_POST['migrate'])) {
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Get current paths
            $stmt = $db->query("SELECT id, file_path, stored_filename FROM audio_files");
            $files = $stmt->fetchAll();
            
            $fixed = 0;
            $errors = [];
            
            echo '<h2>Migration Progress:</h2>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Old Path</th><th>New Path</th><th>Status</th></tr>';
            
            foreach ($files as $file) {
                $oldPath = $file['file_path'];
                $newPath = 'uploads/audio/' . $file['stored_filename'];
                
                // Check if path needs fixing (is absolute)
                $needsFix = (
                    preg_match('/^[a-zA-Z]:/', $oldPath) || // Windows path
                    $oldPath[0] === '/' || // Unix absolute path
                    !str_starts_with($oldPath, 'uploads/audio/') // Not starting with relative path
                );
                
                if ($needsFix) {
                    // Update the path
                    $updateStmt = $db->prepare("UPDATE audio_files SET file_path = ? WHERE id = ?");
                    $updateStmt->execute([$newPath, $file['id']]);
                    
                    echo '<tr>';
                    echo '<td>' . $file['id'] . '</td>';
                    echo '<td><small>' . htmlspecialchars($oldPath) . '</small></td>';
                    echo '<td><small>' . htmlspecialchars($newPath) . '</small></td>';
                    echo '<td style="color: green;">✓ Fixed</td>';
                    echo '</tr>';
                    
                    $fixed++;
                } else {
                    echo '<tr style="background: #f9f9f9;">';
                    echo '<td>' . $file['id'] . '</td>';
                    echo '<td colspan="2"><small>' . htmlspecialchars($oldPath) . '</small></td>';
                    echo '<td style="color: blue;">Already OK</td>';
                    echo '</tr>';
                }
            }
            
            echo '</table>';
            
            // Commit transaction
            $db->commit();
            
            echo '<div class="success">';
            echo '<strong>✓ Migration Completed Successfully!</strong><br>';
            echo "Fixed $fixed audio file path(s).<br>";
            echo "Total files: " . count($files);
            echo '</div>';
            
            echo '<div class="warning">';
            echo '<strong>⚠️ IMPORTANT:</strong> Please delete this file (migrate_fix_audio_paths.php) for security reasons!';
            echo '</div>';
            
        } catch (Exception $e) {
            // Rollback on error
            $db->rollBack();
            
            echo '<div class="error">';
            echo '<strong>✗ Migration Failed:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
        }
    } else {
        // Show current status
        try {
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN file_path LIKE 'uploads/audio/%' THEN 1 ELSE 0 END) as correct,
                    SUM(CASE WHEN file_path NOT LIKE 'uploads/audio/%' THEN 1 ELSE 0 END) as needs_fix
                FROM audio_files
            ");
            $stats = $stmt->fetch();
            
            echo '<h2>Current Status:</h2>';
            echo '<table>';
            echo '<tr><th>Total Audio Files</th><td>' . $stats['total'] . '</td></tr>';
            echo '<tr style="background: #d4edda;"><th>Correct Paths</th><td>' . $stats['correct'] . '</td></tr>';
            echo '<tr style="background: ' . ($stats['needs_fix'] > 0 ? '#fff3cd' : '#d4edda') . ';"><th>Needs Fixing</th><td>' . $stats['needs_fix'] . '</td></tr>';
            echo '</table>';
            
            if ($stats['needs_fix'] > 0) {
                echo '<div class="warning">';
                echo '<strong>Action Required:</strong> ' . $stats['needs_fix'] . ' audio file path(s) need to be fixed.';
                echo '</div>';
                
                // Show examples of paths that need fixing
                $examplesStmt = $db->query("
                    SELECT id, file_path, stored_filename 
                    FROM audio_files 
                    WHERE file_path NOT LIKE 'uploads/audio/%' 
                    LIMIT 5
                ");
                $examples = $examplesStmt->fetchAll();
                
                if ($examples) {
                    echo '<h3>Examples of Paths That Need Fixing:</h3>';
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Current Path</th><th>Will Become</th></tr>';
                    foreach ($examples as $ex) {
                        echo '<tr>';
                        echo '<td>' . $ex['id'] . '</td>';
                        echo '<td><small>' . htmlspecialchars($ex['file_path']) . '</small></td>';
                        echo '<td><small>uploads/audio/' . htmlspecialchars($ex['stored_filename']) . '</small></td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
                
                echo '<form method="POST" onsubmit="return confirm(\'Are you sure you want to run this migration? Make sure you have a database backup!\');">';
                echo '<button type="submit" name="migrate" class="btn">Run Migration Now</button>';
                echo '</form>';
            } else {
                echo '<div class="success">';
                echo '<strong>✓ All audio file paths are already correct!</strong> No migration needed.';
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
    }
    ?>
    
    <hr style="margin: 30px 0;">
    <p><small>After migration is complete, <strong>delete this file</strong> for security.</small></p>
    
</body>
</html>
