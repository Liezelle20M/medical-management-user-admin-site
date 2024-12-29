<?php
session_start();

// Get the raw POST data
$raw_post_data = file_get_contents('php://input');

// Log the notification (for debugging purposes)
file_put_contents('payfast_notify.log', $raw_post_data . PHP_EOL, FILE_APPEND);

// Parse the notification data
$notification_data = [];
parse_str($raw_post_data, $notification_data);

// Validate the notification
if (isset($notification_data['payment_status']) && $notification_data['payment_status'] === 'COMPLETE') {
    // Process the payment
    // Save to the database, update order status, etc.
    // Send confirmation emails, etc.
}

// Respond to PayFast with a 200 OK
http_response_code(200);
?>
