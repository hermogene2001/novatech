<?php
/**
 * Mail configuration for Novatech Investment Platform
 * Using PHPMailer for sending emails
 */

// For now, we'll use a simple mail function
// In production, you would integrate with PHPMailer or a service like SendGrid

function sendEmail($to, $subject, $message) {
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: noreply@novatech.com' . "\r\n";
    
    // In a real application, you would use a proper email library
    // For now, we'll just log the email
    error_log("EMAIL TO: $to, SUBJECT: $subject, MESSAGE: $message");
    
    // Uncomment the line below to actually send emails in production
    // return mail($to, $subject, $message, $headers);
    
    return true; // Simulate successful send
}

function sendTransactionNotification($user, $transaction_type, $amount) {
    $subject = "Transaction Notification - Novatech";
    
    $message = "
    <html>
    <head>
        <title>Transaction Notification</title>
    </head>
    <body>
        <h2>Transaction Notification</h2>
        <p>Dear " . htmlspecialchars($user['first_name']) . ",</p>
        <p>We're writing to inform you that a transaction has been processed on your account:</p>
        <ul>
            <li><strong>Transaction Type:</strong> " . ucfirst(str_replace('_', ' ', $transaction_type)) . "</li>
            <li><strong>Amount:</strong> $" . number_format($amount, 2) . "</li>
            <li><strong>Date:</strong> " . date('F j, Y, g:i a') . "</li>
        </ul>
        <p>If you did not authorize this transaction, please contact our support team immediately.</p>
        <p>Thank you for using Novatech Investment Platform.</p>
        <br>
        <p>Best regards,<br>The Novatech Team</p>
    </body>
    </html>
    ";
    
    return sendEmail($user['email'], $subject, $message);
}

function sendWithdrawalNotification($user, $amount, $status) {
    $subject = "Withdrawal " . ucfirst($status) . " - Novatech";
    
    $message = "
    <html>
    <head>
        <title>Withdrawal Notification</title>
    </head>
    <body>
        <h2>Withdrawal Notification</h2>
        <p>Dear " . htmlspecialchars($user['first_name']) . ",</p>
        <p>Your withdrawal request has been <strong>" . strtolower($status) . "</strong>:</p>
        <ul>
            <li><strong>Amount:</strong> $" . number_format($amount, 2) . "</li>
            <li><strong>Status:</strong> " . ucfirst($status) . "</li>
            <li><strong>Date:</strong> " . date('F j, Y, g:i a') . "</li>
        </ul>
        " . ($status == 'approved' ? "<p>Your funds will be transferred to your bank account within 24 hours.</p>" : "") . "
        <p>Thank you for using Novatech Investment Platform.</p>
        <br>
        <p>Best regards,<br>The Novatech Team</p>
    </body>
    </html>
    ";
    
    return sendEmail($user['email'], $subject, $message);
}

function sendInvestmentNotification($user, $product_name, $amount) {
    $subject = "New Investment Confirmation - Novatech";
    
    $message = "
    <html>
    <head>
        <title>Investment Confirmation</title>
    </head>
    <body>
        <h2>Investment Confirmation</h2>
        <p>Dear " . htmlspecialchars($user['first_name']) . ",</p>
        <p>Thank you for your investment in Novatech:</p>
        <ul>
            <li><strong>Product:</strong> " . htmlspecialchars($product_name) . "</li>
            <li><strong>Amount:</strong> $" . number_format($amount, 2) . "</li>
            <li><strong>Date:</strong> " . date('F j, Y, g:i a') . "</li>
        </ul>
        <p>You will start receiving daily earnings from this investment.</p>
        <p>Thank you for choosing Novatech Investment Platform.</p>
        <br>
        <p>Best regards,<br>The Novatech Team</p>
    </body>
    </html>
    ";
    
    return sendEmail($user['email'], $subject, $message);
}
?>