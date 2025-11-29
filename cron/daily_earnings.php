<?php
/**
 * Cron job for calculating daily earnings for active investments
 * This script should be run once daily via cron
 */

require_once '../config/database.php';

try {
    // Get all active investments
    $stmt = $pdo->prepare("SELECT i.*, p.daily_earning, p.name as product_name, u.email, u.first_name 
                          FROM investments i 
                          JOIN products p ON i.product_id = p.id 
                          JOIN users u ON i.user_id = u.id 
                          WHERE i.status = 'active' AND p.status = 'active'");
    $stmt->execute();
    $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $processed_count = 0;
    $error_count = 0;
    
    foreach ($investments as $investment) {
        try {
            $investment_id = $investment['id'];
            $user_id = $investment['user_id'];
            $daily_earning = $investment['daily_earning'];
            $last_update = $investment['last_profit_update'];
            
            // Check if we already processed today's earnings
            $today = date('Y-m-d');
            $last_update_date = date('Y-m-d', strtotime($last_update));
            
            if ($last_update_date < $today) {
                // Add daily earning to user's balance
                $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$daily_earning, $user_id]);
                
                // Update investment last profit update timestamp
                $stmt = $pdo->prepare("UPDATE investments SET last_profit_update = NOW() WHERE id = ?");
                $stmt->execute([$investment_id]);
                
                // Record transaction
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, transaction_type, amount, status) 
                                      VALUES (?, 'daily_earning', ?, 'approved')");
                $stmt->execute([$user_id, $daily_earning]);
                
                // Send notification
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, is_read) 
                                      VALUES (?, ?, 0)");
                $message = "You received $" . number_format($daily_earning, 2) . " from your investment in " . $investment['product_name'];
                $stmt->execute([$user_id, $message]);
                
                $processed_count++;
            }
        } catch (PDOException $e) {
            $error_count++;
            error_log("Error processing daily earnings for investment ID {$investment['id']}: " . $e->getMessage());
        }
    }
    
    // Log results
    error_log("Daily earnings cron job completed. Processed: $processed_count, Errors: $error_count");
    
    echo "Daily earnings calculation completed.\n";
    echo "Processed: $processed_count investments\n";
    echo "Errors: $error_count\n";
    
} catch (PDOException $e) {
    error_log("Fatal error in daily earnings cron job: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
?>