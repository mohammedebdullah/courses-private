<?php
/**
 * Database Migration: Add Performance Indexes
 * 
 * This script adds database indexes to improve query performance
 * especially for the lessons page which has complex joins.
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
    <title>Database Migration: Add Performance Indexes</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #0056b3; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
    </style>
</head>
<body>
    <h1>⚡ Database Migration: Add Performance Indexes</h1>
    
    <div class="info">
        <strong>Purpose:</strong> This script adds database indexes to speed up page loading,
        especially for the lessons page which has complex queries with multiple JOINs.
    </div>

    <?php
    if (isset($_POST['migrate'])) {
        try {
            $db->beginTransaction();
            
            $indexes = [
                [
                    'table' => 'user_progress',
                    'name' => 'idx_user_progress_user_lesson',
                    'columns' => 'user_id, lesson_id',
                    'sql' => 'CREATE INDEX idx_user_progress_user_lesson ON user_progress(user_id, lesson_id)'
                ],
                [
                    'table' => 'lessons',
                    'name' => 'idx_lessons_course_status',
                    'columns' => 'course_id, status, sort_order',
                    'sql' => 'CREATE INDEX idx_lessons_course_status ON lessons(course_id, status, sort_order)'
                ],
                [
                    'table' => 'audio_files',
                    'name' => 'idx_audio_files_lesson',
                    'columns' => 'lesson_id',
                    'sql' => 'CREATE INDEX idx_audio_files_lesson ON audio_files(lesson_id)'
                ],
                [
                    'table' => 'access_codes',
                    'name' => 'idx_access_codes_code_status',
                    'columns' => 'code, status',
                    'sql' => 'CREATE INDEX idx_access_codes_code_status ON access_codes(code, status)'
                ],
                [
                    'table' => 'sessions',
                    'name' => 'idx_sessions_user_active',
                    'columns' => 'user_id, is_active, expires_at',
                    'sql' => 'CREATE INDEX idx_sessions_user_active ON sessions(user_id, is_active, expires_at)'
                ],
                [
                    'table' => 'activity_logs',
                    'name' => 'idx_activity_logs_user_type_time',
                    'columns' => 'user_id, action_type, created_at',
                    'sql' => 'CREATE INDEX idx_activity_logs_user_type_time ON activity_logs(user_id, action_type, created_at)'
                ],
                [
                    'table' => 'courses',
                    'name' => 'idx_courses_status',
                    'columns' => 'status, created_at',
                    'sql' => 'CREATE INDEX idx_courses_status ON courses(status, created_at)'
                ]
            ];
            
            echo '<h2>Migration Progress:</h2>';
            echo '<table>';
            echo '<tr><th>Table</th><th>Index Name</th><th>Columns</th><th>Status</th></tr>';
            
            $created = 0;
            $skipped = 0;
            
            foreach ($indexes as $index) {
                // Check if index already exists
                $checkStmt = $db->prepare("
                    SELECT COUNT(*) as count 
                    FROM information_schema.STATISTICS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = ? 
                    AND INDEX_NAME = ?
                ");
                $checkStmt->execute([$index['table'], $index['name']]);
                $exists = $checkStmt->fetch()['count'] > 0;
                
                echo '<tr>';
                echo '<td>' . $index['table'] . '</td>';
                echo '<td>' . $index['name'] . '</td>';
                echo '<td><small>' . $index['columns'] . '</small></td>';
                
                if ($exists) {
                    echo '<td style="color: blue;">Already exists</td>';
                    $skipped++;
                } else {
                    try {
                        $db->exec($index['sql']);
                        echo '<td style="color: green;">✓ Created</td>';
                        $created++;
                    } catch (PDOException $e) {
                        echo '<td style="color: orange;">⚠️ ' . htmlspecialchars($e->getMessage()) . '</td>';
                    }
                }
                echo '</tr>';
            }
            
            echo '</table>';
            
            $db->commit();
            
            echo '<div class="success">';
            echo '<strong>✓ Migration Completed!</strong><br>';
            echo "Created: $created new index(es)<br>";
            echo "Skipped: $skipped existing index(es)<br>";
            echo "Total: " . count($indexes) . " indexes processed";
            echo '</div>';
            
            echo '<div class="info">';
            echo '<strong>Performance Impact:</strong><br>';
            echo '• Lessons page will load significantly faster<br>';
            echo '• Authentication and access code checks will be quicker<br>';
            echo '• Progress tracking queries will be optimized';
            echo '</div>';
            
            echo '<div class="warning">';
            echo '<strong>⚠️ IMPORTANT:</strong> Please delete this file (migrate_add_indexes.php) for security reasons!';
            echo '</div>';
            
        } catch (Exception $e) {
            $db->rollBack();
            
            echo '<div class="error">';
            echo '<strong>✗ Migration Failed:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
        }
    } else {
        // Show what will be created
        echo '<h2>Indexes to be Created:</h2>';
        echo '<table>';
        echo '<tr><th>Table</th><th>Index Name</th><th>Columns</th><th>Purpose</th></tr>';
        
        $indexInfo = [
            ['user_progress', 'idx_user_progress_user_lesson', 'user_id, lesson_id', 'Speed up progress lookups'],
            ['lessons', 'idx_lessons_course_status', 'course_id, status, sort_order', 'Optimize lesson list queries'],
            ['audio_files', 'idx_audio_files_lesson', 'lesson_id', 'Faster audio file lookups'],
            ['access_codes', 'idx_access_codes_code_status', 'code, status', 'Quick authentication checks'],
            ['sessions', 'idx_sessions_user_active', 'user_id, is_active, expires_at', 'Efficient session management'],
            ['activity_logs', 'idx_activity_logs_user_type_time', 'user_id, action_type, created_at', 'Fast log queries'],
            ['courses', 'idx_courses_status', 'status, created_at', 'Quick course listings']
        ];
        
        foreach ($indexInfo as $info) {
            echo '<tr>';
            echo '<td>' . $info[0] . '</td>';
            echo '<td><code>' . $info[1] . '</code></td>';
            echo '<td><small>' . $info[2] . '</small></td>';
            echo '<td>' . $info[3] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        echo '<div class="info">';
        echo '<strong>Benefits:</strong><br>';
        echo '• Significantly faster page loading (especially lessons page)<br>';
        echo '• Reduced database query time<br>';
        echo '• Better performance under high load<br>';
        echo '• No data changes - only performance improvements';
        echo '</div>';
        
        echo '<form method="POST">';
        echo '<button type="submit" name="migrate" class="btn">Create Indexes Now</button>';
        echo '</form>';
    }
    ?>
    
    <hr style="margin: 30px 0;">
    <p><small>After migration is complete, <strong>delete this file</strong> for security.</small></p>
    
</body>
</html>
