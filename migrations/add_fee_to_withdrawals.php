<?php
/**
 * Migration script to add fee column to withdrawals table
 */

require_once __DIR__ . '/../config/database.php';

try {
    // Add fee column to withdrawals table
    $stmt = $pdo->prepare("ALTER TABLE withdrawals 
                          ADD COLUMN IF NOT EXISTS fee DECIMAL(10,2) DEFAULT 0.00,
                          ADD COLUMN IF NOT EXISTS amount_after_fee DECIMAL(10,2) DEFAULT 0.00");
    $stmt->execute();
    
    echo "Migration completed successfully. Added fee columns to withdrawals table.\n";
    
} catch (PDOException $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
}
?>