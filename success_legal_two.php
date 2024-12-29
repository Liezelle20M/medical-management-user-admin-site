<?php
session_start();
// Check if 'medico_id' is set in the session
if (!isset($_SESSION['medico_id'])) {
    die("Missing session information: medico_id");
}


$medico_id = $_SESSION['medico_id'];

// Include necessary files
include 'includes/db_connection.php'; // Adjust the path as necessary
include 'mail-config.php';           // Your existing mail configuration with sendEmail function
require 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

// Check if required session variables exist
$required_sessions = [
    'email', 'first_name', 'last_name', 'street_address',
    'city', 'postal_code', 'province', 'country',
    'query_type', 'fee', 'required_service', 'payment_amount'
];

foreach ($required_sessions as $key) {
    if (!isset($_SESSION[$key])) {
        die("Missing session information: $key");
    }
}

// Retrieve session data
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
$payment_amount = floatval($_SESSION['payment_amount']);

// Calculate deposit (70%)
$deposit_percentage = 0.70;
$deposit_amount = round($query_price * $deposit_percentage, 2);

// Generate Receipt PDF (receipt_file2)
$receipt_dir = 'receipts/';
if (!is_dir($receipt_dir)) {
    mkdir($receipt_dir, 0755, true);
}
$receipt_file2 = $receipt_dir . 'REC-' . strtoupper(uniqid()) . '.pdf';

$dompdf = new Dompdf();
$receipt_html = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .receipt-box {
            max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; font-size: 16px; line-height: 24px; color: #555;
        }
        .receipt-box table { width: 100%; line-height: inherit; text-align: left; }
        .receipt-box table td { padding: 5px; vertical-align: top; }
        .receipt-box table tr.top table td { padding-bottom: 20px; }
        .receipt-box table tr.information table td { padding-bottom: 40px; }
        .receipt-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
        .receipt-box table tr.item td { border-bottom: 1px solid #eee; }
        .receipt-box table tr.total td { font-weight: bold; border-top: 2px solid #eee; }
        .align-right { text-align: right; }
    </style>
</head>
<body>
<div class='receipt-box'>
    <table cellpadding='0' cellspacing='0'>
        <tr class='top'>
            <td colspan='2'>
                <table>
                    <tr>
                        <td class='title'>
                            <img src='images/VAHSA LOGO 3.png' style='width:100%; max-width:200px;'>
                        </td>
                        <td>
                            Receipt #: REC-" . strtoupper(uniqid()) . "<br>
                            Date: " . date('Y-m-d') . "<br>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr class='information'>
            <td colspan='2'>
                <table>
                    <tr>
                        <td>VAHSA Pty Ltd.<br>Address Line 1<br>Address Line 2</td>
                        <td>
                            {$first_name} {$last_name}<br>{$email}<br>{$address}, {$city}, {$postal_code}, {$province}, {$country}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr class='heading'>
            <td>Description</td><td>Amount (R)</td>
        </tr>
        <tr class='item'>
            <td>{$query_type} - Payment (70%)</td><td class='align-right'>" . number_format($deposit_amount, 2) . "</td>
        </tr>
        <tr class='total'><td></td><td>Total Paid: R " . number_format($deposit_amount, 2) . "</td></tr>
    </table>
    <p>Thank you for your payment! Please call <strong>0614837013</strong> to follow up on your query: <strong>{$query_type}</strong> and required service: <strong>{$required_service}</strong>.</p>
</div>
</body>
</html>
";

$dompdf->loadHtml($receipt_html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
file_put_contents($receipt_file2, $dompdf->output());

// Update 'invoices_med' table with receipt_file2
$stmt_update_receipt = $conn->prepare("UPDATE invoices_med SET receipt_file2 = ? WHERE in_med_id = ?");
$stmt_update_receipt->bind_param("si", $receipt_file2, $medico_id);
$stmt_update_receipt->execute();
$stmt_update_receipt->close();

// Update 'medicolegal' table to mark status as 'completed'
$stmt_update_status = $conn->prepare("UPDATE medicolegal SET status = 'completed' WHERE medico_id = ?");
$stmt_update_status->bind_param("i", $medico_id);
$stmt_update_status->execute();
$stmt_update_status->close();

// Fetch reports from 'med_reports' table using the code provided
$reportData = null;
$sqlReports = "SELECT * FROM med_reports WHERE medico_id = ?";
$stmtReports = $conn->prepare($sqlReports);
if ($stmtReports) {
    $stmtReports->bind_param("i", $medico_id);
    $stmtReports->execute();
    $resultReports = $stmtReports->get_result();
    if ($resultReports->num_rows > 0) {
        $reportData = $resultReports->fetch_assoc();
    }
    $stmtReports->close();
}

// Prepare attachments
$attachments = [$receipt_file2];
if ($reportData && !empty($reportData['final_report'])) {
    $attachments[] = 'Admin/' . $reportData['final_report'];
}
if ($reportData && !empty($reportData['additional_documents'])) {
    $attachments[] = 'Admin/' . $reportData['additional_documents'];
}

// Email message
$subject = 'Your Payment Receipt and Reports from VAHSA';
$message = "
    <p>Dear {$first_name} {$last_name},</p>
    <p>Thank you for your payment. Please find attached your receipt and relevant reports.</p>
    <p>Please call <strong>0614837013</strong> to follow up on your query: <strong>{$query_type}</strong> and required service: <strong>{$required_service}</strong>.</p>
    <p>Best regards,<br>VAHSA Pty Ltd.</p>
";

// Send email with attachments using sendEmail function
if (sendEmail($email, $subject, $message, $attachments)) {
    echo "<div style='text-align: center; padding: 50px;'>
            <img src='images/VAHSA LOGO 3.png' alt='VAHSA Logo' style='width:150px;'>
            <h1>Payment Successful!</h1>
            <p>Thank you, <strong>{$first_name} {$last_name}</strong>, for your payment.</p>
            <p>Your receipt and reports have been sent to your email.</p>
            <p>You will be redirected shortly. If not, <a href='user_dashboard.php'>click here</a>.</p>
          </div>";
    header("refresh:5;url=user_dashboard.php");
} else {
    echo "<div style='text-align: center; padding: 50px; color: red;'>
            <h1>Payment Successful, But Failed to Send Email.</h1>
            <p>Please contact support.</p>
          </div>";
}

$conn->close();
?>
