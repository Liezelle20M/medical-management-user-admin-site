<?php
session_start();

// PayFast Merchant Details
$merchant_id = "25372399";
$merchant_key = "vvqrjybe8hkki";

// Notification handling variables
$pfError = false;
$pfErrMsg = '';
$pfDone = false;
$pfData = [];
$pfHost = 'www.payfast.co.za';

// Security check to validate the source
if (!$pfError && !$pfDone) {
    // Read the incoming POST data from PayFast
    $pfData = $_POST;

    // Verify signature (ensure the data was not tampered with)
    $pfParamString = '';
    foreach ($pfData as $key => $val) {
        if ($key !== 'signature') {
            $pfParamString .= $key .'='. urlencode($val) .'&';
        }
    }
    $pfParamString = rtrim($pfParamString, '&');
    $signature = md5($pfParamString);
    if ($signature !== $pfData['signature']) {
        $pfError = true;
        $pfErrMsg = 'Invalid signature';
    }
}

// Validate the data by sending it back to PayFast for verification
if (!$pfError && !$pfDone) {
    $url = 'https://'.$pfHost.'/eng/query/validate';
    
    // cURL POST request to PayFast
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $pfParamString);
    $response = curl_exec($ch);
    curl_close($ch);

    // Validate PayFast's response
    if ($response !== 'VALID') {
        $pfError = true;
        $pfErrMsg = 'Data validation failed';
    }
}

// Check payment status and update database if valid
if (!$pfError) {
    // Process payment notification (success or failure)
    $payment_status = $pfData['payment_status'];
    $transaction_id = $pfData['pf_payment_id'];
    $amount_paid = $pfData['amount_gross'];
    $email_address = $pfData['email_address'];  // Could use for referencing the user

    // Payment success
    if ($payment_status == "COMPLETE") {
        // Example: Update the database with payment success details (e.g., mark the order as paid)
        // You should also reference the user and billing information if necessary
        
        // Assuming you have a DB connection, update payment status (simplified query)
        /*
        $sql = "UPDATE payments SET status = 'paid', amount_paid = '$amount_paid', transaction_id = '$transaction_id' WHERE email = '$email_address'";
        mysqli_query($db_conn, $sql);
        */
        
        // Store a log or other related actions
    } elseif ($payment_status == "FAILED") {
        // Handle failed payments (log it, update your system, etc.)
        /*
        $sql = "UPDATE payments SET status = 'failed' WHERE email = '$email_address'";
        mysqli_query($db_conn, $sql);
        */
    }
}

// Return a valid response to PayFast
header("HTTP/1.0 200 OK");
flush();
