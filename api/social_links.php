<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../lib/SocialLinks.php';

try {
    $socialLinks = new SocialLinks($pdo);
    $links = $socialLinks->getAllLinks();
    
    echo json_encode([
        'success' => true,
        'links' => $links
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>