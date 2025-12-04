<?php
/**
 * Cron job for calculating and distributing referral commissions
 * This script should be run when a referred user makes a recharge
 */

require_once '../config/database.php';

try {
    // Get all new recharges that haven't been processed for referrals
    $stmt = $pdo->prepare("SELECT r.*, u.invitation_code
                          FROM recharges r
                          JOIN users u ON r.client_id = u.id
                          WHERE r.status = 'confirmed' AND r.processed_for_referral = 0");
    $stmt->execute();
    $recharges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $processed_count = 0;
    $error_count = 0;
    
    foreach ($recharges as $recharge) {
        try {
            $recharge_id = $recharge['id'];
            $user_id = $recharge['client_id'];
            $recharge_amount = $recharge['amount'];
            $invitation_code = $recharge['invitation_code'];
            
            // Process referral commissions if user was referred
            if (!empty($invitation_code)) {
                // Find the referrer
                $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
                $stmt->execute([$invitation_code]);
                $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($referrer) {
                    $referrer_id = $referrer['id'];
                    
                    // Calculate level 1 commission (30%)
                    $level1_commission = $recharge_amount * 0.30;
                    
                    // Add to referrer's referral bonus
                    $stmt = $pdo->prepare("UPDATE users SET referral_bonus = referral_bonus + ? WHERE id = ?");
                    $stmt->execute([$level1_commission, $referrer_id]);
                    
                    // Record transaction
                    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, transaction_type, amount, status) 
                                              VALUES (?, 'referral_commission', ?, 'approved')");
                    $stmt->execute([$referrer_id, $level1_commission]);
                    
                    // Record referral earning
                    $stmt = $pdo->prepare("INSERT INTO referral_earnings (referrer_id, referred_id, amount, level) 
                                              VALUES (?, ?, ?, 1)");
                    $stmt->execute([$referrer_id, $user_id, $level1_commission]);
                    
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
                            
                            // Calculate level 2 commission (4%)
                            $level2_commission = $recharge_amount * 0.04;
                            
                            // Add to level 2 referrer's referral bonus
                            $stmt = $pdo->prepare("UPDATE users SET referral_bonus = referral_bonus + ? WHERE id = ?");
                            $stmt->execute([$level2_commission, $level2_referrer_id]);
                            
                            // Record referral earning
                            $stmt = $pdo->prepare("INSERT INTO referral_earnings (referrer_id, referred_id, amount, level) 
                                                      VALUES (?, ?, ?, 2)");
                            $stmt->execute([$level2_referrer_id, $user_id, $level2_commission]);
                            
                            // Record transaction
                            $stmt->execute([$level2_referrer_id, $level2_commission]);
                            
                            // Check for level 3 referrals (level 2 referrer's referrer)
                            $stmt = $pdo->prepare("SELECT u.invitation_code FROM users u WHERE u.id = ?");
                            $stmt->execute([$level2_referrer_id]);
                            $level2_referrer_data = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($level2_referrer_data && !empty($level2_referrer_data['invitation_code'])) {
                                $level3_referrer_invitation_code = $level2_referrer_data['invitation_code'];
                                
                                // Find the level 3 referrer
                                $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
                                $stmt->execute([$level3_referrer_invitation_code]);
                                $level3_referrer = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($level3_referrer) {
                                    $level3_referrer_id = $level3_referrer['id'];
                                    
                                    // Calculate level 3 commission (1%)
                                    $level3_commission = $recharge_amount * 0.01;
                                    
                                    // Add to level 3 referrer's referral bonus
                                    $stmt = $pdo->prepare("UPDATE users SET referral_bonus = referral_bonus + ? WHERE id = ?");
                                    $stmt->execute([$level3_commission, $level3_referrer_id]);
                                    
                                    // Record referral earning
                                    $stmt = $pdo->prepare("INSERT INTO referral_earnings (referrer_id, referred_id, amount, level) 
                                                              VALUES (?, ?, ?, 3)");
                                    $stmt->execute([$level3_referrer_id, $user_id, $level3_commission]);
                                    
                                    // Record transaction
                                    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, transaction_type, amount, status) 
                                                              VALUES (?, 'referral_commission', ?, 'approved')");
                                    $stmt->execute([$level3_referrer_id, $level3_commission]);
                                }
                            }
                        }
                    }
                }
            }
            
            // Mark recharge as processed for referrals
            $stmt = $pdo->prepare("UPDATE recharges SET processed_for_referral = 1 WHERE id = ?");
            $stmt->execute([$recharge_id]);
            
            $processed_count++;
        } catch (PDOException $e) {
            $error_count++;
            error_log("Error processing referral commission for recharge ID {$recharge['id']}: " . $e->getMessage());
        }
    }
    
    // Log results
    error_log("Referral commissions cron job completed. Processed: $processed_count, Errors: $error_count");
    
    echo "Referral commissions calculation completed.\n";
    echo "Processed: $processed_count recharges\n";
    echo "Errors: $error_count\n";
    
} catch (PDOException $e) {
    error_log("Fatal error in referral commissions cron job: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
?>