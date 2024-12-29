<?php
session_start();
include 'includes/db_connection.php';
include 'mail-config.php';
require 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

// Redirect to user_dashboard.php after 5 seconds
header("refresh:5;url=user_dashboard.php");

// Check if session variables exist
$required_session_vars = ['email', 'first_name', 'last_name', 'total_price', 'query_name', 'query_id'];
foreach ($required_session_vars as $var) {
    if (!isset($_SESSION[$var])) {
        die("Missing session information: $var!");
    }
}

// Retrieve session data
$email = htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8');
$first_name = htmlspecialchars($_SESSION['first_name'], ENT_QUOTES, 'UTF-8');
$last_name = htmlspecialchars($_SESSION['last_name'], ENT_QUOTES, 'UTF-8');
$address = htmlspecialchars($_SESSION['street_address'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
$city = htmlspecialchars($_SESSION['city'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
$postal_code = htmlspecialchars($_SESSION['postal_code'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
$province = htmlspecialchars($_SESSION['province'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
$country = htmlspecialchars($_SESSION['country'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
$query_name = htmlspecialchars($_SESSION['query_name'], ENT_QUOTES, 'UTF-8');
$total_price = number_format((float)$_SESSION['total_price'], 2, '.', '');
$query_id = intval($_SESSION['query_id']); // Assuming this corresponds to clinical_id

// Initialize DOMPDF
$dompdf = new Dompdf();
$logo_path = realpath('images/logo.jpg'); // Ensure this path is correct and the file exists

if (!$logo_path || !file_exists($logo_path)) {
    error_log("Logo file not found at path: " . realpath('images/logo.jpg'));
    // Optionally, proceed without the logo
    $logo_path = ''; // Or set to a default image
}

// Create HTML for the receipt
$html = "
    <style>
        @page {
            margin: 20mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .receipt-container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #eee;
            background-color: #fff;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header img {
            max-width: 150px;
            height: auto;
        }
        .company-details {
            text-align: right;
        }
        .company-details h2 {
            margin: 0;
            font-size: 24px;
            color: #4CAF50;
        }
        .company-details p {
            margin: 5px 0;
            font-size: 14px;
            color: #777;
        }
        .title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 30px;
        }
        .billing-info {
            width: 100%;
            margin-bottom: 20px;
        }
        .billing-info td {
            padding: 8px;
            vertical-align: top;
        }
        .billing-info td.left {
            width: 50%;
        }
        .billing-info td.right {
            width: 50%;
        }
        .receipt-details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .receipt-details th, .receipt-details td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        .receipt-details th {
            background-color: #f2f2f2;
            color: #333;
        }
        .totals {
            width: 100%;
            margin-bottom: 20px;
        }
        .totals td {
            padding: 8px;
            font-size: 16px;
        }
        .totals td.left {
            text-align: right;
            font-weight: bold;
        }
        .totals td.right {
            text-align: right;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
    <div class='receipt-container'>
        <div class='header'>
            <div class='logo'>
                " . ($logo_path ? "<img src='file://$logo_path' alt='VAHSA Logo'>" : "") . "
            </div>
            <div class='company-details'>
                <h2>Value Added Healthcare Solutions</h2>
                <p>123 Health Street, Wellness City, WC 45678</p>
                <p>Email: support@valueaddedhealthcaresa.com | Phone: +1 (234) 567-890</p>
            </div>
        </div>
        <div class='title'>RECEIPT</div>
        <table class='billing-info'>
            <tr>
                <td class='left'>
                    <strong>Sold To:</strong><br>
                    $first_name $last_name<br>
                    $address<br>
                    $city, $province $postal_code<br>
                    $country
                </td>
                <td class='right'>
                    <strong>Receipt No:</strong> RCPT-" . date('Ymd') . "-" . rand(1000, 9999) . "<br>
                    <strong>Date:</strong> " . date('F j, Y') . "
                </td>
            </tr>
        </table>
        <table class='receipt-details'>
            <thead>
                <tr>
                    <th>Qty</th>
                    <th>Item Description</th>
                    <th>Price/Unit</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>$query_name</td>
                    <td>R " . number_format($total_price, 2) . "</td>
                    <td>R " . number_format($total_price, 2) . "</td>
                </tr>
            </tbody>
        </table>
        <table class='totals'>
            <tr>
                <td class='left'>Subtotal:</td>
                <td class='right'>R " . number_format($total_price, 2) . "</td>
            </tr>
            <tr>
                <td class='left'>Tax (0%):</td>
                <td class='right'>R 0.00</td>
            </tr>
            <tr>
                <td class='left'><strong>Total:</strong></td>
                <td class='right'><strong>R " . number_format($total_price, 2) . "</strong></td>
            </tr>
        </table>
        <p>Thank you for your payment! We appreciate your business.</p>
        <div class='footer'>
            <p>&copy; " . date('Y') . " Value Added Healthcare Solutions. All rights reserved.</p>
            <p>Visit us at: www.valueaddedhealthcaresa.com</p>
        </div>
    </div>
";

// Load the HTML into DOMPDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');

try {
    $dompdf->render();
} catch (Exception $e) {
    error_log("Dompdf rendering failed: " . $e->getMessage());
    die("Failed to generate PDF receipt.");
}

// Save the PDF to the 'receipts' folder
$receipts_dir = __DIR__ . '/receipts'; // Absolute path
if (!file_exists($receipts_dir)) {
    if (!mkdir($receipts_dir, 0755, true)) {
        die("Failed to create receipts directory.");
    }
}

$pdf_filename = 'receipt_' . time() . '.pdf';
$pdf_output = $receipts_dir . '/' . $pdf_filename;
$pdf_content = $dompdf->output();

if (file_put_contents($pdf_output, $pdf_content) === false) {
    die("Failed to save the receipt PDF.");
}

// Begin Database Transaction
$conn->begin_transaction();

try {
    // 1. Update the payment status in the claimsqueries table
    $updateStmt = $conn->prepare("UPDATE claimsqueries SET status = 'Completed' WHERE clinical_id = ?");
    if (!$updateStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $updateStmt->bind_param('i', $clinical_id);
    if (!$updateStmt->execute()) {
        throw new Exception("Execute failed: " . $updateStmt->error);
    }

    // 2. Insert the receipt into the receipt_claims table
    $stmt = $conn->prepare("
        INSERT INTO receipt_claims 
        (user_email, billing_first_name, billing_last_name, billing_address, billing_city, billing_postal_code, billing_province, billing_country, query_name, total_price, payment_status, receipt_pdf)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'complete', ?)
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        'sssssssssss',
        $email,
        $first_name,
        $last_name,
        $address,
        $city,
        $postal_code,
        $province,
        $country,
        $query_name,
        $total_price,
        $pdf_output
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // 3. Commit Transaction
    $conn->commit();

    // Send the receipt via email
    $subject = "Receipt for Your Payment";
    $message = "
        <p>Dear $first_name $last_name,</p>
        <p>Thank you for your payment for the query: <strong>$query_name</strong>.</p>
        <p>Please find your receipt attached.</p>
        <p>Best regards,<br>Value Added Healthcare Solutions</p>
    ";

    // Initialize email status message
    $emailStatus = "";

    // Ensure the mail-config.php's sendEmail function supports attachments
    // Pass the attachment as an array
    if (sendEmail($email, $subject, $message, [$pdf_output])) {
        $emailStatus = "<p>Receipt sent to your email.</p>";
    } else {
        $emailStatus = "<p>Failed to send receipt email.</p>";
        error_log("Failed to send email to {$email} with attachment {$pdf_output}");
    }

    // Display Success Message with Logo and Countdown
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Payment Successful</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                padding-top: 50px;
                background-color: #f9f9f9;
            }
            .logo {
                width: 200px;
                margin-bottom: 20px;
            }
            .message {
                font-size: 24px;
                color: #4CAF50;
                margin-bottom: 20px;
            }
            .countdown {
                font-size: 18px;
                color: #555;
            }
            .email-status {
                margin-top: 20px;
                font-size: 16px;
                color: #333;
            }
        </style>
        <script>
            let countdown = 5;
            function updateCountdown() {
                if (countdown > 0) {
                    document.getElementById('countdown').innerText = countdown;
                    countdown--;
                    setTimeout(updateCountdown, 1000);
                } else {
                    // Redirect happens automatically via header
                }
            }
            window.onload = updateCountdown;
        </script>
    </head>
    <body>
        <img src="images/logo.png" alt="Company Logo" class="logo">
        <div class="message">Payment Successful!</div>
        <div class="countdown">Redirecting to your dashboard in <span id="countdown">5</span> seconds...</div>
        <div class="email-status">
            <?php echo $emailStatus; ?>
        </div>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    // Rollback Transaction
    $conn->rollback();
    echo "<p>Error processing payment: " . htmlspecialchars($e->getMessage()) . "</p>";
    error_log("Payment processing error: " . $e->getMessage());
    exit();
}

// Close the statement and connection
$stmt->close();
$updateStmt->close();
$conn->close();
?>
