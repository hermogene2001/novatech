<?php
/**
 * Migration script to add 2FA fields to users table
 */

require_once __DIR__ . '/../config/database.php';

try {
    // Add 2FA fields to users table
    $stmt = $pdo->prepare("ALTER TABLE users 
                          ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(255) NULL,
                          ADD COLUMN IF NOT EXISTS two_factor_enabled TINYINT(1) DEFAULT 0");
    $stmt->execute();
    
    echo "Migration completed successfully. Added 2FA fields to users table.\n";
    
} catch (PDOException $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
}
?>