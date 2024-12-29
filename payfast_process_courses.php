<?php
session_start();

// Fetch required data from the session
$first_name = $_SESSION['first_name'] ?? 'N/A';
$last_name = $_SESSION['last_name'] ?? 'N/A';
$email = $_SESSION['email'] ?? 'N/A';
$training_theme = $_SESSION['training_theme'] ?? 'N/A';
$total_price = $_SESSION['total_price_courses'] ?? 0; // Assume this is already in Rands
$payable_amount = $_SESSION['payableAmount'];

// PayFast Sandbox integration details
$merchant_id = "10035315"; // PayFast Sandbox Merchant ID
$merchant_key = "4boy9jg0fjjs1"; // PayFast Merchant Key
$passphrase = "vvqrjybe8hkki"; // Passphrase from PayFast settings

// PayFast URLs
$return_url = "https://valueaddedhealthcaresa.com/success_courses.php"; // URL after successful payment
$cancel_url = "https://valueaddedhealthcaresa.com/cancel_courses.php"; // URL after canceled payment
$notify_url = "https://valueaddedhealthcaresa.com/notify_courses.php"; // PayFast notification URL (IPN)
// Prepare data for PayFast
$payfast_data = [
    'merchant_id' => $merchant_id,
    'merchant_key' => $merchant_key,
    'return_url' => $return_url,
    'cancel_url' => $cancel_url,
    'notify_url' => $notify_url,
    'name_first' => $first_name,
    'name_last' => $last_name,
    'email_address' => $email,
    'amount' => number_format($payable_amount, 2, '.', ''), // Keep the amount in Rands
    'item_name' => $training_theme,
];

// Generate the PayFast signature
$pfOutput = '';
foreach ($payfast_data as $key => $val) {
    $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
}
$pfOutput = substr($pfOutput, 0, -1);  // Remove last '&'

if (!empty($passphrase)) {
    $pfOutput .= '&passphrase=' . urlencode(trim($passphrase));
}

$signature = md5($pfOutput);  // Generate MD5 hash
$payfast_data['signature'] = $signature;  // Add signature to data

// PayFast Sandbox URL
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
