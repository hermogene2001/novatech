<?php
/**
 * Two Factor Authentication Helper Class
 * Provides methods for generating secrets and verifying codes
 */

class TwoFactorAuth {
    
    /**
     * Generate a random secret for 2FA
     * @return string
     */
    public static function generateSecret() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $secret;
    }
    
    /**
     * Generate QR code URL for Google Authenticator
     * @param string $name Account name (usually email or username)
     * @param string $secret The secret key
     * @param string $title Application title
     * @return string
     */
    public static function getQRCodeUrl($name, $secret, $title = 'Novatech') {
        $url = 'otpauth://totp/' . urlencode($title) . ':' . urlencode($name) . '?secret=' . $secret . '&issuer=' . urlencode($title);
        return 'https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($url) . '&size=200x200';
    }
    
    /**
     * Verify a 2FA code
     * @param string $secret The secret key
     * @param string $code The code to verify
     * @return bool
     */
    public static function verifyCode($secret, $code) {
        // In a real implementation, you would use a proper TOTP library
        // For this example, we'll simulate verification
        // In production, use something like PHPGangsta/GoogleAuthenticator
        
        // Simple simulation - in reality, you would calculate the TOTP
        // based on the current time and compare with the provided code
        return !empty($secret) && !empty($code) && strlen($code) == 6 && is_numeric($code);
    }
    
    /**
     * Generate a 6-digit code based on secret and time
     * This is a simplified version - in production use a proper library
     * @param string $secret
     * @param int $timeSlice
     * @return string
     */
    public static function getCode($secret, $timeSlice = null) {
        // This is a simplified implementation
        // In production, use a proper TOTP library
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }
        
        // Generate a simple 6-digit code for demonstration
        return str_pad(($timeSlice % 1000000), 6, '0', STR_PAD_LEFT);
    }
}
?>