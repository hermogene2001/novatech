<?php
// Database initialization script for Novatech Investment Platform
// This script will create the database and tables if they don't exist

$host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'kinginvest';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
    echo "Database '$db_name' created or already exists.\n";
    
    // Select the database
    $pdo->exec("USE `$db_name`");
    
    // Read the SQL file
    $sql = file_get_contents('kinginvest.sql');
    
    // Execute the SQL commands
    $pdo->exec($sql);
    echo "Database tables created successfully.\n";
    
    echo "Database initialization completed!\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>