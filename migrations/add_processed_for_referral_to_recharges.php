<?php
/**
 * Migration script to add processed_for_referral column to recharges table
 */

require_once __DIR__ . '/../config/database.php';

try {
    // Add processed_for_referral column to recharges table
    $stmt = $pdo->prepare("ALTER TABLE recharges ADD COLUMN processed_for_referral TINYINT(1) DEFAULT 0");
    $stmt->execute();
    
    echo "Migration completed successfully. Added processed_for_referral column to recharges table.\n";
    
} catch (PDOException $e) {
    // Check if the column already exists
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column processed_for_referral already exists in recharges table.\n";
    } else {
        echo "Error running migration: " . $e->getMessage() . "\n";
    }
}
?>