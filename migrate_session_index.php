<?php
/**
 * One-time migration to add session_token index
 * DELETE THIS FILE AFTER RUNNING!
 */

require_once 'database/config.php';

try {
    $db = getDB();
    
    echo "<h2>Adding session_token index...</h2>";
    
    // Check if index already exists
    $stmt = $db->prepare("
        SELECT COUNT(*) as idx_count
        FROM information_schema.statistics 
        WHERE table_schema = DATABASE()
        AND table_name = 'user_sessions'
        AND index_name = 'idx_user_sessions_token'
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['idx_count'] > 0) {
        echo "<p style='color: orange;'>Index idx_user_sessions_token already exists. Skipping...</p>";
    } else {
        echo "<p>Creating index idx_user_sessions_token...</p>";
        $db->exec("ALTER TABLE user_sessions ADD INDEX idx_user_sessions_token (session_token)");
        echo "<p style='color: green;'>✓ Index created successfully!</p>";
    }
    
    // Show all indexes on user_sessions
    echo "<h3>Current indexes on user_sessions:</h3>";
    $stmt = $db->query("SHOW INDEX FROM user_sessions");
    $indexes = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Key Name</th><th>Column</th><th>Unique</th></tr>";
    foreach ($indexes as $idx) {
        $unique = $idx['Non_unique'] == 0 ? 'Yes' : 'No';
        echo "<tr>";
        echo "<td>{$idx['Key_name']}</td>";
        echo "<td>{$idx['Column_name']}</td>";
        echo "<td>{$unique}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p style='color: red; font-weight: bold;'>⚠️ DELETE THIS FILE (migrate_session_index.php) IMMEDIATELY FOR SECURITY!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
