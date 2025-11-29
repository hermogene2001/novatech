<?php
/**
 * Migration script to add referral earnings table
 */

require_once __DIR__ . '/../config/database.php';

try {
    // Create referral_earnings table
    $stmt = $pdo->prepare("CREATE TABLE IF NOT EXISTS referral_earnings (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        referrer_id INT(11) NOT NULL,
        referred_id INT(11) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        level INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (referrer_id) REFERENCES users(id),
        FOREIGN KEY (referred_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    $stmt->execute();
    
    echo "Migration completed successfully. Created referral_earnings table.\n";
    
} catch (PDOException $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
}
?>