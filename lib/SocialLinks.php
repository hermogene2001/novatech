<?php
/**
 * Social Links Utility Class
 * Provides methods to fetch and display social media links
 */

class SocialLinks {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Fetch all social links from the database
     * @return array Associative array of social links
     */
    public function getAllLinks() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM social_links WHERE id = 1");
            $stmt->execute();
            $links = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($links) {
                return $links;
            }
            
            return [
                'whatsapp' => '',
                'telegram' => '',
                'facebook' => '',
                'twitter' => ''
            ];
        } catch(PDOException $e) {
            return [
                'whatsapp' => '',
                'telegram' => '',
                'facebook' => '',
                'twitter' => ''
            ];
        }
    }
    
    /**
     * Check if any social links are configured
     * @return bool True if at least one link is configured
     */
    public function hasAnyLinks() {
        $links = $this->getAllLinks();
        
        return !empty($links['whatsapp']) || 
               !empty($links['telegram']) || 
               !empty($links['facebook']) || 
               !empty($links['twitter']);
    }
    
    /**
     * Generate HTML for social links icons
     * @param array $links Social links array
     * @param bool $showLabels Whether to show labels with icons
     * @return string HTML for social links
     */
    public function generateSocialLinksHTML($links, $showLabels = false) {
        $html = '';
        
        if (!empty($links['whatsapp'])) {
            $html .= '<a href="' . htmlspecialchars($links['whatsapp']) . '" target="_blank" class="btn btn-success me-2 mb-2">' .
                     '<i class="bi bi-whatsapp"></i>' . ($showLabels ? ' WhatsApp' : '') . '</a>';
        }
        
        if (!empty($links['telegram'])) {
            $html .= '<a href="' . htmlspecialchars($links['telegram']) . '" target="_blank" class="btn btn-info me-2 mb-2">' .
                     '<i class="bi bi-telegram"></i>' . ($showLabels ? ' Telegram' : '') . '</a>';
        }
        
        if (!empty($links['facebook'])) {
            $html .= '<a href="' . htmlspecialchars($links['facebook']) . '" target="_blank" class="btn btn-primary me-2 mb-2">' .
                     '<i class="bi bi-facebook"></i>' . ($showLabels ? ' Facebook' : '') . '</a>';
        }
        
        if (!empty($links['twitter'])) {
            $html .= '<a href="' . htmlspecialchars($links['twitter']) . '" target="_blank" class="btn btn-dark me-2 mb-2">' .
                     '<i class="bi bi-twitter"></i>' . ($showLabels ? ' Twitter' : '') . '</a>';
        }
        
        return $html;
    }
}
?>