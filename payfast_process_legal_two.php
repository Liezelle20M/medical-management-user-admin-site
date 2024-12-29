<?php
session_start();

// Fetch all required data from the session (from payment_page_legal.php)
$first_name = $_SESSION['first_name'] ?? 'N/A';
$last_name = $_SESSION['last_name'] ?? 'N/A';
$email = $_SESSION['email'] ?? 'N/A';
$medical_practice_type = $_SESSION['medical_practice_type'] ?? 'N/A';
$street_address = $_SESSION['street_address'] ?? 'N/A';
$city = $_SESSION['city'] ?? 'N/A';
$postal_code = $_SESSION['postal_code'] ?? 'N/A';
$province = $_SESSION['province'] ?? 'N/A';
$country = $_SESSION['country'] ?? 'South Africa';
$query_type = $_SESSION['query_type'] ?? 'N/A';
$required_service = $_SESSION['required_service'] ?? 'N/A';
$payment_amount = $_SESSION['payment_amount'];
$payment_method = $_SESSION['payment_method'] ?? 'N/A';

// PayFast Sandbox integration details
$merchant_id = "10035315"; // PayFast Sandbox Merchant ID
$merchant_key = "4boy9jg0fjjs1"; // PayFast Merchant Key
$passphrase = "vvqrjybe8hkki"; // Optional if enabled in PayFast settings

// PayFast URLs
$return_url = "https://valueaddedhealthcaresa.com/success_legal_two.php"; // URL after successful payment
$cancel_url = "https://valueaddedhealthcaresa.com/cancel_legal_two.php"; // URL after canceled payment
$notify_url = "https://valueaddedhealthcaresa.com/notify_legal_two.php"; // PayFast notification URL (IPN)

// Payment data to be sent to PayFast
$payfast_data = [
    'merchant_id' => $merchant_id,
    'merchant_key' => $merchant_key,
    'return_url' => $return_url,
    'cancel_url' => $cancel_url,
    'notify_url' => $notify_url,
    'name_first' => $first_name,
    'name_last' => $last_name,
    'email_address' => $email,
    'amount' =>number_format($payment_amount,2, '.', ''), // Total price formatted to 2 decimals
    'item_name' => $query_type, // Name of the query type
    'item_description' => $required_service, // Description of the required service
];

// Generate PayFast signature
$pfOutput = '';
foreach ($payfast_data as $key => $val) {
    $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
}

// Remove the last '&' and generate the signature
$pfOutput = substr($pfOutput, 0, -1);
if (!empty($passphrase)) {
    $pfOutput .= '&passphrase=' . urlencode($passphrase);
}
$signature = md5($pfOutput);

// Add the signature to the data
$payfast_data['signature'] = $signature;

// PayFast sandbox URL for testing
$payfast_url = "https://sandbox.payfast.co.za/eng/process";

// Redirect to PayFast for payment processing
?>
<html>
<head>
    <title>Processing Payment...</title>
</head>
<body onload="document.forms['payfast_form'].submit();">
    <form id="payfast_form" action="<?php echo $payfast_url; ?>" method="post">
        <?php
        // Automatically create hidden inputs for each value in $payfast_data
        foreach ($payfast_data as $key => $value) {
            echo "<input type='hidden' name='$key' value='$value' />";
        }
        ?>
        <input type="submit" value="Click here if you are not redirected automatically" />
    </form>
</body>
</html>
