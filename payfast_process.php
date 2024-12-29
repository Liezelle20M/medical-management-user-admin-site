<?php
session_start();

// PayFast sandbox credentials
$payfast_merchant_id = '10035315'; // Sandbox merchant ID
$payfast_merchant_key = '4boy9jg0fjjs1'; // Sandbox merchant key
$payfast_passphrase = 'vvqrjybe8hkki'; // Replace with your actual passphrase

// Retrieve data from session
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$email_address = $_SESSION['email'] ?? '';
$amount = $_SESSION['total_price'] ?? 0;
$item_name = $_SESSION['query_name'] ?? 'Payment';
$invoice_id = $_SESSION['invoice_id'] ?? '';

// Prepare data for PayFast
$payfast_data = array(
    // Merchant details
    'merchant_id' => $payfast_merchant_id,
    'merchant_key' => $payfast_merchant_key,
    // Transaction details
    'return_url' => 'https://valueaddedhealthcaresa.com/success_claims.php',
    'cancel_url' => 'https://valueaddedhealthcaresa.com/cancel.php',
    'notify_url' => 'https://valueaddedhealthcaresa.com/notify.php',
    // Buyer details
    'name_first' => $first_name,
    'name_last'  => $last_name,
    'email_address'=> $email_address,
    // Transaction details
    'm_payment_id' => 'INV_' . $invoice_id, // Unique payment ID
    'amount' => number_format(sprintf("%.2f", $amount), 2, '.', ''),
    'item_name' => $item_name,
);

// Generate signature
function generateSignature($data, $passPhrase = null) {
    // Create parameter string
    $pfOutput = '';
    foreach ($data as $key => $val) {
        if (!empty($val)) {
            $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
        }
    }
    // Remove last ampersand
    $getString = substr($pfOutput, 0, -1);
    if ($passPhrase !== null) {
        $getString .= '&passphrase=' . urlencode(trim($passPhrase));
    }
    return md5($getString);
}

// Generate the signature using your passphrase
$payfast_data['signature'] = generateSignature($payfast_data, $payfast_passphrase);

// Build the query string
$pfHost = 'sandbox.payfast.co.za';
$htmlForm = '<form action="https://'.$pfHost.'/eng/process" method="post" id="payfast_payment_form">';
foreach ($payfast_data as $name => $value) {
    $htmlForm .= '<input name="'.htmlspecialchars($name).'" type="hidden" value="'.htmlspecialchars($value).'" />';
}
$htmlForm .= '</form>';

// Auto-submit the form
$htmlForm .= '<script type="text/javascript">document.getElementById("payfast_payment_form").submit();</script>';

// Display the form
echo $htmlForm;
?>
