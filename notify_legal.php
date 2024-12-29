<?php
// notify_legal.php - PayFast IPN (Instant Payment Notification) handler

session_start();

// PayFast settings
$payfast_merchant_id = "10000100"; // Replace with your live merchant ID
$payfast_passphrase = "your_passphrase_here"; // Replace with your live passphrase

// Determine if you're in sandbox or live mode
$payfast_sandbox = true; // Set to false for live environment

// PayFast sandbox or live URLs
$payfast_valid_url = $payfast_sandbox 
    ? 'https://sandbox.payfast.co.za/eng/query/validate'
    : 'https://www.payfast.co.za/eng/query/validate';

// Fetch PayFast POST data
$payfast_data = $_POST;

// Step 1: Validate that the POST data contains the required fields
if (!isset($payfast_data['m_payment_id']) || !isset($payfast_data['pf_payment_id'])) {
    // Log invalid request and exit
    file_put_contents('payfast_invalid_request.log', print_r($payfast_data, true), FILE_APPEND);
    exit('Invalid request');
}

// Step 2: Security check - verify the signature
function generate_signature($data, $passphrase = null) {
    // Remove the signature from the data if it exists
    unset($data['signature']);
    
    // Build query string from data
    $data_string = http_build_query($data);
    
    // Append the passphrase if one is provided
    if ($passphrase) {
        $data_string .= '&passphrase=' . urlencode($passphrase);
    }
    
    // Return the md5 hash of the string
    return md5($data_string);
}

// Generate signature using the received data and passphrase
$signature = generate_signature($payfast_data, $payfast_passphrase);

// Check if the signatures match
if ($signature !== $payfast_data['signature']) {
    // Log signature mismatch and exit
    file_put_contents('payfast_signature_mismatch.log', print_r($payfast_data, true), FILE_APPEND);
    exit('Invalid signature');
}

// Step 3: Validate the payment with PayFast by posting back the data to PayFast's validation endpoint
$validation_data = array_merge($payfast_data, array('passphrase' => $payfast_passphrase));

// cURL setup for validation request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $payfast_valid_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($validation_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Ensure this is true for production
$response = curl_exec($ch);
curl_close($ch);

// PayFast validation response must be "VALID"
if ($response !== 'VALID') {
    // Log invalid response and exit
    file_put_contents('payfast_invalid_validation_response.log', print_r($response, true), FILE_APPEND);
    exit('Payment validation failed');
}

// Step 4: Verify the payment details (amount, payment status, merchant ID, etc.)
$m_payment_id = $payfast_data['m_payment_id']; // Your transaction ID
$amount_gross = $payfast_data['amount_gross']; // The total amount received
$merchant_id = $payfast_data['merchant_id']; // PayFast's merchant ID

// Verify that the transaction belongs to you (merchant_id match)
if ($merchant_id !== $payfast_merchant_id) {
    // Log merchant ID mismatch and exit
    file_put_contents('payfast_merchant_mismatch.log', print_r($payfast_data, true), FILE_APPEND);
    exit('Invalid merchant ID');
}

// Verify the amount (match it with your original transaction amount)
$expected_amount = $_SESSION['total_price']; // Retrieve the original amount from session or database
if ($amount_gross != number_format($expected_amount, 2, '.', '')) {
    // Log amount mismatch and exit
    file_put_contents('payfast_amount_mismatch.log', print_r($payfast_data, true), FILE_APPEND);
    exit('Payment amount mismatch');
}

// Step 5: Process the payment
// Payment is valid, update the order in your database (e.g., mark as paid)

// Use your m_payment_id to reference the order in your system
$order_id = $m_payment_id; // For example purposes

// Assume you have a function to mark the order as paid
// markOrderAsPaid($order_id, $amount_gross);

// Log successful payment
file_put_contents('payfast_success.log', print_r($payfast_data, true), FILE_APPEND);

// Step 6: Respond with "OK" to PayFast to confirm receipt of the notification
header("HTTP/1.0 200 OK");
echo "OK";
exit();
?>
