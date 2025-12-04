<?php
/**
 * Fixed database initialization script for Novatech Investment Platform
 */

require_once 'config/database.php';

try {
    // Read the SQL file
    $sql = file_get_contents('kinginvest.sql');
    
    // Split the SQL file into individual statements
    $statements = explode(';', $sql);
    
    // Execute each statement
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "Database initialized successfully!\n";
    
} catch(PDOException $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
}
?>