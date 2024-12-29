<?php
// notify_legal_two.php - PayFast IPN (Instant Payment Notification) handler

session_start();

// Include database connection file
include 'includes/db_connection.php';

// PayFast settings
$payfast_merchant_id = "10035315"; // Replace with your live merchant ID
$payfast_passphrase = "vvqrjybe8hkki"; // Replace with your live passphrase

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
    file_put_contents('logs/payfast_invalid_request.log', print_r($payfast_data, true), FILE_APPEND);
    exit('Invalid request');
}

// Step 2: Security check - verify the signature
function generate_signature($data, $passphrase = null) {
    unset($data['signature']);
    $data_string = http_build_query($data);
    if ($passphrase) {
        $data_string .= '&passphrase=' . urlencode($passphrase);
    }
    return md5($data_string);
}

$signature = generate_signature($payfast_data, $payfast_passphrase);

if ($signature !== $payfast_data['signature']) {
    file_put_contents('logs/payfast_signature_mismatch.log', print_r($payfast_data, true), FILE_APPEND);
    exit('Invalid signature');
}

// Step 3: Validate the payment with PayFast by posting back the data to PayFast's validation endpoint
$validation_data = array_merge($payfast_data, array('passphrase' => $payfast_passphrase));

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $payfast_valid_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($validation_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Ensure this is true for production
$response = curl_exec($ch);
curl_close($ch);

if ($response !== 'VALID') {
    file_put_contents('logs/payfast_invalid_validation_response.log', print_r($response, true), FILE_APPEND);
    exit('Payment validation failed');
}

// Step 4: Verify the payment details (amount, payment status, merchant ID, etc.)
$m_payment_id = $payfast_data['m_payment_id']; // Your transaction ID
$amount_gross = $payfast_data['amount_gross']; // The total amount received
$merchant_id = $payfast_data['merchant_id']; // PayFast's merchant ID

// Verify the merchant ID matches
if ($merchant_id !== $payfast_merchant_id) {
    file_put_contents('logs/payfast_merchant_mismatch.log', print_r($payfast_data, true), FILE_APPEND);
    exit('Invalid merchant ID');
}

// Fetch the expected amount from the database using m_payment_id
$stmt = $conn->prepare("SELECT expected_amount FROM transactions WHERE m_payment_id = ?");
$stmt->bind_param("s", $m_payment_id);
$stmt->execute();
$stmt->bind_result($expected_amount);
$stmt->fetch();
$stmt->close();

if (!$expected_amount) {
    file_put_contents('logs/payfast_transaction_not_found.log', print_r($payfast_data, true), FILE_APPEND);
    exit('Transaction not found');
}

// Check if the amount matches the expected amount in the database
if ($amount_gross != number_format($expected_amount, 2, '.', '')) {
    file_put_contents('logs/payfast_amount_mismatch.log', print_r($payfast_data, true), FILE_APPEND);
    exit('Payment amount mismatch');
}

// Step 5: Process the payment - update the database to mark the order as paid
$stmt_update = $conn->prepare("UPDATE transactions SET status = 'Paid', payment_date = NOW() WHERE m_payment_id = ?");
$stmt_update->bind_param("s", $m_payment_id);

if ($stmt_update->execute()) {
    // Log successful payment
    file_put_contents('logs/payfast_success.log', print_r($payfast_data, true), FILE_APPEND);
} else {
    file_put_contents('logs/payfast_update_error.log', "Error updating transaction for m_payment_id: $m_payment_id", FILE_APPEND);
    exit('Database update failed');
}

$stmt_update->close();
$conn->close();

// Step 6: Respond with "OK" to PayFast to confirm receipt of the notification
header("HTTP/1.0 200 OK");
echo "OK";
exit();
?>
