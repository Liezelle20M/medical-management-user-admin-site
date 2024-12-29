<?php
session_start();
if (isset($_SESSION['medico_id'])) {
    $medico_id = $_SESSION['medico_id'];
} else {
    die("Missing session information: medico_id");
}

// Include necessary files
include 'includes/db_connection.php';
include 'mail-config.php';
require 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

// Redirect to user_dashboard.php after 5 seconds
header("refresh:5;url=user_dashboard.php");

// Check if required session variables exist
$required_sessions = [
    'email', 'first_name', 'last_name', 'street_address',
    'city', 'postal_code', 'province', 'country',
    'query_type', 'fee', 'required_service'
];

foreach ($required_sessions as $key) {
    if (!isset($_SESSION[$key])) {
        die("Missing session information: $key");
    }
}

// Retrieve and sanitize session data
$email = filter_var($_SESSION['email'], FILTER_SANITIZE_EMAIL);
$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name = htmlspecialchars($_SESSION['last_name']);
$address = htmlspecialchars($_SESSION['street_address']);
$city = htmlspecialchars($_SESSION['city']);
$postal_code = htmlspecialchars($_SESSION['postal_code']);
$province = htmlspecialchars($_SESSION['province']);
$country = htmlspecialchars($_SESSION['country']);
$query_type = htmlspecialchars($_SESSION['query_type']);
$query_price = floatval($_SESSION['fee']);
$required_service = htmlspecialchars($_SESSION['required_service']);

// Calculate deposit and remaining balance
$deposit_percentage = 0.30;
$deposit_amount = round($query_price * $deposit_percentage, 2); // 30% of fee
$remaining_balance = round($query_price - $deposit_amount, 2); // Remaining balance

// Initialize Dompdf
$dompdf = new Dompdf();

// Function to generate PDF
function generatePDF($html, $filename, $dompdf_instance) {
    $dompdf_instance->loadHtml($html);
    $dompdf_instance->setPaper('A4', 'portrait');
    $dompdf_instance->render();
    file_put_contents($filename, $dompdf_instance->output());
}

// --- Generate Invoice PDF ---
$invoice_number = 'INV-' . strtoupper(uniqid());
$invoice_date = date('Y-m-d');

$invoice_html = '
<html>
<head>
<style>
    body { font-family: DejaVu Sans, sans-serif; }
    .invoice-box {
        max-width: 800px;
        margin: auto;
        padding: 30px;
        border: 1px solid #eee;
        font-size: 16px;
        line-height: 24px;
        color: #555;
    }
    .invoice-box table { width: 100%; line-height: inherit; text-align: left; }
    .invoice-box table td { padding: 5px; vertical-align: top; }
    .invoice-box table tr td:nth-child(2) { text-align: right; }
    .invoice-box table tr.top table td { padding-bottom: 20px; }
    .invoice-box table tr.top table td.title { font-size: 45px; line-height: 45px; color: #333; }
    .invoice-box table tr.information table td { padding-bottom: 40px; }
    .invoice-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
    .invoice-box table tr.item td{ border-bottom: 1px solid #eee; }
    .invoice-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; }
    .align-right { text-align: right; }
</style>
</head>
<body>
<div class="invoice-box">
    <table cellpadding="0" cellspacing="0">
        <tr class="top">
            <td colspan="2">
                <table>
                    <tr>
                        <td class="title">
                            <!-- Company Logo -->
                            <img src="images/VAHSA LOGO 3.png" style="width:100%; max-width:200px;">
                        </td>
                        <td>
                            Invoice #: '.$invoice_number.'<br>
                            Date: '.$invoice_date.'<br>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr class="information">
            <td colspan="2">
                <table>
                    <tr>
                        <td>
                            VAHSA Pty Ltd.<br>
                            Company Address Line 1<br>
                            Company Address Line 2
                        </td>
                        <td>
                            '.$first_name.' '.$last_name.'<br>
                            '.$email.'<br>
                            '.$address.', '.$city.', '.$postal_code.', '.$province.', '.$country.'
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr class="heading">
            <td>Description</td>
            <td>Amount (R)</td>
        </tr>
        
        <tr class="item">
            <td>'.$query_type.'</td>
            <td class="align-right">'.number_format($query_price, 2).'</td>
        </tr>
        
        <tr class="item">
            <td>Deposit Paid (30%)</td>
            <td class="align-right">-'.number_format($deposit_amount, 2).'</td>
        </tr>
        
        <tr class="item last">
            <td><strong>Remaining Balance Due</strong></td>
            <td class="align-right"><strong>'.number_format($remaining_balance, 2).'</strong></td>
        </tr>
        
        <tr class="total">
            <td></td>
            <td>Total Due: R '.number_format($remaining_balance, 2).'</td>
        </tr>
    </table>
    <p>Thank you for your business!</p>
</div>
</body>
</html>
';

$invoice_dir = 'invoices/';
if (!is_dir($invoice_dir)) {
    mkdir($invoice_dir, 0755, true);
}
$invoice_file = $invoice_dir . $invoice_number . '.pdf';
generatePDF($invoice_html, $invoice_file, $dompdf);

// --- Generate Receipt PDF ---
$receipt_number = 'REC-' . strtoupper(uniqid());
$receipt_date = date('Y-m-d');

$receipt_html = '
<html>
<head>
<style>
    body { font-family: DejaVu Sans, sans-serif; }
    .receipt-box {
        max-width: 800px;
        margin: auto;
        padding: 30px;
        border: 1px solid #eee;
        font-size: 16px;
        line-height: 24px;
        color: #555;
    }
    .receipt-box table { width: 100%; line-height: inherit; text-align: left; }
    .receipt-box table td { padding: 5px; vertical-align: top; }
    .receipt-box table tr td:nth-child(2) { text-align: right; }
    .receipt-box table tr.top table td { padding-bottom: 20px; }
    .receipt-box table tr.top table td.title { font-size: 45px; line-height: 45px; color: #333; }
    .receipt-box table tr.information table td { padding-bottom: 40px; }
    .receipt-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
    .receipt-box table tr.item td{ border-bottom: 1px solid #eee; }
    .receipt-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; }
    .align-right { text-align: right; }
</style>
</head>
<body>
<div class="receipt-box">
    <table cellpadding="0" cellspacing="0">
        <tr class="top">
            <td colspan="2">
                <table>
                    <tr>
                        <td class="title">
                            <!-- Company Logo -->
                            <img src="images/VAHSA LOGO 3.png" style="width:100%; max-width:200px;">
                        </td>
                        <td>
                            Receipt #: '.$receipt_number.'<br>
                            Date: '.$receipt_date.'<br>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr class="information">
            <td colspan="2">
                <table>
                    <tr>
                        <td>
                            VAHSA Pty Ltd.<br>
                            Company Address Line 1<br>
                            Company Address Line 2
                        </td>
                        <td>
                            '.$first_name.' '.$last_name.'<br>
                            '.$email.'<br>
                            '.$address.', '.$city.', '.$postal_code.', '.$province.', '.$country.'
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr class="heading">
            <td>Description</td>
            <td>Amount Paid (R)</td>
        </tr>
        
        <tr class="item">
            <td>'.$query_type.' - Deposit (30%)</td>
            <td class="align-right">'.number_format($deposit_amount, 2).'</td>
        </tr>
        
        <tr class="total">
            <td></td>
            <td>Total Paid: R '.number_format($deposit_amount, 2).'</td>
        </tr>
    </table>
    <p>Thank you for your payment!</p>
</div>
</body>
</html>
';

$receipt_dir = 'receipts/';
if (!is_dir($receipt_dir)) {
    mkdir($receipt_dir, 0755, true);
}
$receipt_file1 = $receipt_dir . $receipt_number . '.pdf';
generatePDF($receipt_html, $receipt_file1, $dompdf);

// --- Insert Data into Database ---
$stmt = $conn->prepare("
    INSERT INTO invoices_med (
        user_email, billing_first_name, billing_last_name, billing_address,
        billing_city, billing_postal_code, billing_province, billing_country,
        query_type, fee, deposit_amount, required_service,
        invoice_file, receipt_file1, payment_status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Paid')
");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Bind parameters (types: s - string, d - double)
$stmt->bind_param(
    'sssssssssddsss', // 14 type specifiers
    $email,
    $first_name,
    $last_name,
    $address,
    $city,
    $postal_code,
    $province,
    $country,
    $query_type,
    $query_price,      // fee
    $deposit_amount,
    $required_service,
    $invoice_file,
    $receipt_file1
);

// Execute the statement
if ($stmt->execute()) {
    // Get the `in_med_id` of the newly inserted invoice
    $in_med_id = $stmt->insert_id;

    // Update `medicolegal` with `in_med_id`
    $sql_update_medico = "UPDATE medicolegal SET invoice_med = ? WHERE medico_id = ?";
    $stmt_update_medico = $conn->prepare($sql_update_medico);
    if (!$stmt_update_medico) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt_update_medico->bind_param('ii', $in_med_id, $medico_id);
    if (!$stmt_update_medico->execute()) {
        // Handle error
        error_log("Failed to update medicolegal: " . $stmt_update_medico->error);
    }
    $stmt_update_medico->close();

    // Success message
    echo "<div style='text-align: center; padding: 50px;'>
            <img src='images/VAHSA LOGO 3.png' alt='VAHSA Logo' style='width:150px;'>
            <h1>Payment Successful!</h1>
            <p>Thank you, <strong>$first_name $last_name</strong>, for your payment.</p>
            <p>Your invoice and receipt have been generated.</p>
            <p>You will be redirected shortly. If not, <a href='user_dashboard.php'>click here</a>.</p>
          </div>";
    
    // --- Send Emails with Both Attachments ---
    $subject = "Your Invoice and Receipt from VAHSA";
    $message = "<p>Dear $first_name $last_name,</p>
                <p>Thank you for your payment. Please find attached your invoice and receipt.</p>
                <p>If you have any questions, feel free to contact us.</p>
                <p>Best regards,<br>VAHSA Team</p>";

    // Attach both PDFs
    $attachments = [$invoice_file, $receipt_file1];

    $email_sent = sendEmail($email, $subject, $message, $attachments);

    if ($email_sent) {
        echo "<p style='text-align: center;'>An email with your invoice and receipt has been sent to $email.</p>";
    } else {
        echo "<p style='text-align: center; color: red;'>Failed to send email. Please contact support.</p>";
    }

} else {
    // Error handling
    echo "<div style='text-align: center; padding: 50px; color: red;'>
            <h1>Payment Failed!</h1>
            <p>We're sorry, but there was an issue processing your payment.</p>
            <p>Please try again later or contact support.</p>
          </div>";
    error_log("Database Insert Error: " . $stmt->error);
}

// Close the statements and connection
$stmt->close();
$conn->close();

?>
