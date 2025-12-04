<?php
// Migration to create social_links table
// This ensures the social_links table exists with proper structure

require_once __DIR__ . '/../config/database.php';

try {
    // Check if table already exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'social_links'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the social_links table
        $sql = "CREATE TABLE `social_links` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `whatsapp` varchar(255) DEFAULT NULL,
            `telegram` varchar(255) DEFAULT NULL,
            `facebook` varchar(255) DEFAULT NULL,
            `twitter` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $pdo->exec($sql);
        echo "Table social_links created successfully.\n";
    } else {
        echo "Table social_links already exists.\n";
    }
    
    // Check if there's at least one row in the table
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM social_links");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Insert a default row
        $sql = "INSERT INTO social_links (whatsapp, telegram, facebook, twitter) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['', '', '', '']);
        echo "Default row inserted into social_links table.\n";
    } else {
        echo "social_links table already has data.\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

echo "Migration completed.\n";
?>