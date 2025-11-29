<?php
/**
 * Cron job for calculating and distributing referral commissions
 * This script should be run daily via cron
 */

require_once '../config/database.php';

try {
    // Get all new investments that haven't been processed for referrals
    $stmt = $pdo->prepare("SELECT i.*, u.invitation_code 
                          FROM investments i 
                          JOIN users u ON i.user_id = u.id 
                          WHERE i.last_profit_update IS NULL OR i.last_profit_update = i.invested_at");
    $stmt->execute();
    $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $processed_count = 0;
    $error_count = 0;
    
    foreach ($investments as $investment) {
        try {
            $investment_id = $investment['id'];
            $user_id = $investment['user_id'];
            $investment_amount = $investment['amount'];
            $invitation_code = $investment['invitation_code'];
            
            // Process referral commissions if user was referred
            if (!empty($invitation_code)) {
                // Find the referrer
                $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
                $stmt->execute([$invitation_code]);
                $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($referrer) {
                    $referrer_id = $referrer['id'];
                    
                    // Calculate level 1 commission (3%)
                    $level1_commission = $investment_amount * 0.03;
                    
                    // Add to referrer's referral bonus
                    $stmt = $pdo->prepare("UPDATE users SET referral_bonus = referral_bonus + ? WHERE id = ?");
                    $stmt->execute([$level1_commission, $referrer_id]);
                    
                    // Record referral earning
                    $stmt = $pdo->prepare("INSERT INTO referral_earnings (referrer_id, referred_id, amount, level) 
                                          VALUES (?, ?, ?, 1)");
                    $stmt->execute([$referrer_id, $user_id, $level1_commission]);
                    
                    // Record transaction
                    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, transaction_type, amount, status) 
                                          VALUES (?, 'referral_commission', ?, 'approved')");
                    $stmt->execute([$referrer_id, $level1_commission]);
                    
                    // Check for level 2 referrals (referrer's referrer)
                    $stmt = $pdo->prepare("SELECT u.invitation_code FROM users u WHERE u.id = ?");
                    $stmt->execute([$referrer_id]);
                    $referrer_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($referrer_data && !empty($referrer_data['invitation_code'])) {
                        $referrer_invitation_code = $referrer_data['invitation_code'];
                        
                        // Find the level 2 referrer
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
                        $stmt->execute([$referrer_invitation_code]);
                        $level2_referrer = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($level2_referrer) {
                            $level2_referrer_id = $level2_referrer['id'];
                            
                            // Calculate level 2 commission (1%)
                            $level2_commission = $investment_amount * 0.01;
                            
                            // Add to level 2 referrer's referral bonus
                            $stmt = $pdo->prepare("UPDATE users SET referral_bonus = referral_bonus + ? WHERE id = ?");
                            $stmt->execute([$level2_commission, $level2_referrer_id]);
                            
                            // Record referral earning
                            $stmt = $pdo->prepare("INSERT INTO referral_earnings (referrer_id, referred_id, amount, level) 
                                                  VALUES (?, ?, ?, 2)");
                            $stmt->execute([$level2_referrer_id, $user_id, $level2_commission]);
                            
                            // Record transaction
                            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, transaction_type, amount, status) 
                                                  VALUES (?, 'referral_commission', ?, 'approved')");
                            $stmt->execute([$level2_referrer_id, $level2_commission]);
                        }
                    }
                }
            }
            
            // Update investment to mark it as processed
            $stmt = $pdo->prepare("UPDATE investments SET last_profit_update = NOW() WHERE id = ?");
            $stmt->execute([$investment_id]);
            
            $processed_count++;
        } catch (PDOException $e) {
            $error_count++;
            error_log("Error processing referral commission for investment ID {$investment['id']}: " . $e->getMessage());
        }
    }
    
    // Log results
    error_log("Referral commissions cron job completed. Processed: $processed_count, Errors: $error_count");
    
    echo "Referral commissions calculation completed.\n";
    echo "Processed: $processed_count investments\n";
    echo "Errors: $error_count\n";
    
} catch (PDOException $e) {
    error_log("Fatal error in referral commissions cron job: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
?>