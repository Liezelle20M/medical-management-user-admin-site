<?php
session_start();
include 'includes/db_connection.php';
include 'mail-config.php';
require 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

// Required session data
$required_session_keys = [
    'user_id', 
    'training_theme', 
    'num_delegates', 
    'fee', 
    'payableAmount'
];

$missing_keys = array_filter($required_session_keys, function($key) {
    return !isset($_SESSION[$key]);
});

if (!empty($missing_keys)) {
    die("Missing session information for: " . implode(', ', $missing_keys));
}

// Fetch the session data for the course details
$user_id = $_SESSION['user_id'];
$training_theme = $_SESSION['training_theme'] ?? 'N/A';
$num_delegates = $_SESSION['num_delegates'] ?? 0;
$fee = $_SESSION['fee'] ?? 0;
$total_price = $_SESSION['total_price_courses'] ?? ($fee * $num_delegates);
$deposit_amount = $_SESSION['payableAmount'] ?? 0; // 30% deposit amount
$remaining_amount = $total_price - $deposit_amount;

// Billing information from session
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$medical_practice_type = $_SESSION['medical_practice_type'] ?? '';
$street_address = $_SESSION['street_address'] ?? '';
$city = $_SESSION['city'] ?? '';
$postal_code = $_SESSION['postal_code'] ?? '';
$province = $_SESSION['province'] ?? '';
$country = $_SESSION['country'] ?? 'South Africa';
$email = $_SESSION['email'] ?? '';

// Fetch user information from the users table to ensure data integrity
$stmt_user = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$stmt_user->bind_param('i', $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows === 0) {
    die("User not found.");
}

$user = $result_user->fetch_assoc();
$first_name = htmlspecialchars($user['first_name']);
$last_name = htmlspecialchars($user['last_name']);

$stmt_user->close();

// Generate the PDF receipt
$dompdf = new Dompdf();

// Updated logo path to 'logo.png'
$logo_path = $_SERVER['DOCUMENT_ROOT'] . '/images/logo.png';

// Initialize logo HTML
$logo_html = "";

// Verify that the logo file exists
if (file_exists($logo_path)) {
    // Use a relative path for the image in the PDF
    $logo_relative_path = 'images/logo.png';
    $logo_html = "<img src='{$logo_relative_path}' alt='VAHSA Logo'>";
} else {
    // Log the missing logo file instead of terminating the script
    error_log("Logo file not found at path: $logo_path");
}

// Create HTML for the receipt with optional logo
$html = "
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            padding: 20px;
            border-bottom: 2px solid #0C4375;
        }
        .header img {
            width: 150px; /* Adjust logo size as needed */
        }
        .receipt-title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            margin-top: 20px;
            color: #0C4375;
        }
        .details, .table, .totals {
            width: 100%;
            margin: 20px 0;
        }
        .details td, .table th, .table td, .totals td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .table {
            border-collapse: collapse;
            background-color: #f9f9f9;
        }
        .table th {
            background-color: #0C4375;
            color: white;
        }
        .totals {
            border: none;
        }
        .totals td {
            border: none;
            text-align: right;
            padding: 8px;
        }
        .totals td:first-child {
            text-align: left;
        }
        .footer {
            margin-top: 30px;
            font-size: 14px;
            text-align: center;
            color: #555;
        }
    </style>
    <div class='header'>
        {$logo_html}
    </div>
    <h1 class='receipt-title'>RECEIPT</h1>
    <table class='details'>
        <tr>
            <td><strong>Sold To:</strong> $first_name $last_name</td>
            <td><strong>Date:</strong> " . date('Y-m-d') . "</td>
        </tr>
    </table>
    <table class='table'>
        <thead>
            <tr>
                <th>Qty</th>
                <th>Item</th>
                <th>Price/Unit</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>$num_delegates</td>
                <td>$training_theme</td>
                <td>R " . number_format($fee, 2) . "</td>
                <td>R " . number_format($total_price, 2) . "</td>
            </tr>
        </tbody>
    </table>
    <table class='totals'>
        <tr>
            <td><strong>Subtotal</strong></td>
            <td>R " . number_format($total_price, 2) . "</td>
        </tr>
        <tr>
            <td><strong>Deposit (30%)</strong></td>
            <td>R " . number_format($deposit_amount, 2) . "</td>
        </tr>
        <tr>
            <td><strong>Remaining Balance</strong></td>
            <td>R " . number_format($remaining_amount, 2) . "</td>
        </tr>
        <tr>
            <td><strong>Tax</strong></td>
            <td>R 0.00</td>
        </tr>
        <tr>
            <td><strong>Shipping</strong></td>
            <td>R 0.00</td>
        </tr>
        <tr>
            <td><strong>Total Paid</strong></td>
            <td><strong>R " . number_format($deposit_amount, 2) . "</strong></td>
        </tr>
    </table>
    <p class='footer'>Thank you for your application for the training theme: <strong>$training_theme</strong>. You will receive more details via email within 48 hours.</p>
";

// Load the HTML into DOMPDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Ensure the 'images' directory exists and is writable
$pdf_directory = 'images/';
if (!is_dir($pdf_directory)) {
    mkdir($pdf_directory, 0755, true);
}

// Save the PDF to the 'images' folder
$pdf_filename = 'receipt_courses_' . time() . '.pdf';
$pdf_output = $pdf_directory . $pdf_filename;
file_put_contents($pdf_output, $dompdf->output());

// Insert the receipt into the receipt_courses table
$stmt_courses = $conn->prepare("
    INSERT INTO receipt_courses 
    (user_id, training_theme, num_delegates, fee, total_price, deposit_amount, remaining_amount, payment_status, invoice_pdf)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'Paid', ?)
");

$stmt_courses->bind_param(
    'isiiidds',
    $user_id,
    $training_theme,
    $num_delegates,
    $fee,
    $total_price,
    $deposit_amount,
    $remaining_amount,
    $pdf_output
);

if ($stmt_courses->execute()) {
    // Prepare the email content for the user
    $venue_address = "123 Training Venue Street, Midrand, Johannesburg, 1685, South Africa"; // Update as needed
    $course_date = "2024-12-15"; // Example date, replace with actual data if available
    $course_time = "09:00 AM - 05:00 PM"; // Example time, replace with actual data if available
    
    $subject = "Receipt for Your Training Application";
    $message = "
        <p>Dear $first_name $last_name,</p>
        <p>Thank you for your application for the training theme: <strong>$training_theme</strong>.</p>
        <p>Please find your receipt attached.</p>
        <p><strong>Remaining Balance:</strong> R " . number_format($remaining_amount, 2) . "</p>
        <p>The remaining balance can be paid before or on the day of the course. Please note that if you decide to withdraw from the course, the deposit is non-refundable.</p>
        <h3>Training Course Details:</h3>
        <ul>
            <li><strong>Date:</strong> $course_date</li>
            <li><strong>Time:</strong> $course_time</li>
            <li><strong>Venue Address:</strong> $venue_address</li>
        </ul>
        <p>Please arrive at the training location one hour before your scheduled timeslot. You will need to bring your debit or credit card to pay the remaining balance.</p>
        <p>We look forward to seeing you!</p>
    ";
    
    // Send the receipt via email
    // Assuming sendEmail accepts attachments as an array of file paths
    $email_sent = sendEmail($email, $subject, $message, [$pdf_output]); // Pass as array
    
    if ($email_sent) {
        // ------------------------- NEW CODE START -------------------------
        // Define Admin Email Address
        $admin_email = 'ambrosetshuma26.ka@gmail.com'; // Replace with actual admin email address
        
        // Prepare Admin Email Subject and Message
        $admin_subject = "New Training Course Payment Received";
        $admin_message = "
            <p>Dear Admin,</p>
            <p>The following user has made a payment for the training course:</p>
            <table>
                <tr>
                    <td><strong>User ID:</strong></td>
                    <td>{$user_id}</td>
                </tr>
                <tr>
                    <td><strong>Name:</strong></td>
                    <td>{$first_name} {$last_name}</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>{$email}</td>
                </tr>
                <tr>
                    <td><strong>Training Theme:</strong></td>
                    <td>{$training_theme}</td>
                </tr>
                <tr>
                    <td><strong>Number of Delegates:</strong></td>
                    <td>{$num_delegates}</td>
                </tr>
                <tr>
                    <td><strong>Total Paid:</strong></td>
                    <td>R " . number_format($deposit_amount, 2) . "</td>
                </tr>
                <tr>
                    <td><strong>Remaining Balance:</strong></td>
                    <td>R " . number_format($remaining_amount, 2) . "</td>
                </tr>
            </table>
            <p>Please find the attached receipt for your records.</p>
            <p>Please prepare for the final payment or use the attached invoice to confirm the remaining amount the user needs to pay.</p>
            <p>Best regards,<br>Your Website Team</p>
        ";
        
        // Send the email to Admin with the PDF attachment
        $admin_email_sent = sendEmail($admin_email, $admin_subject, $admin_message, [$pdf_output]);
        
        if (!$admin_email_sent) {
            // Log the failure to send email to admin
            error_log("Failed to send admin notification email to {$admin_email} for user ID {$user_id}.");
            // Optionally, you can notify the user about this issue
        }
        // -------------------------- NEW CODE END --------------------------
        
        // Initialize logo HTML for the success message
        $success_logo_html = "";
        $success_logo_path = 'images/logo.png';
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $success_logo_path)) {
            $success_logo_html = "<img src='{$success_logo_path}' alt='VAHSA Logo'>";
        } else {
            // Optionally log the missing logo for the success message
            error_log("Success message logo file not found at path: " . $_SERVER['DOCUMENT_ROOT'] . '/' . $success_logo_path);
        }
        
        // Display success message with countdown and redirect
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>Payment Successful</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f0f8ff;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                }
                .container {
                    text-align: center;
                    background-color: #fff;
                    padding: 40px;
                    border-radius: 10px;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                }
                .container img {
                    width: 150px;
                    margin-bottom: 20px;
                }
                .container h2 {
                    color: #0C4375;
                }
                .countdown {
                    font-size: 18px;
                    margin-top: 20px;
                    color: #555;
                }
            </style>
            <script>
                let countdown = 5;
                function updateCountdown() {
                    document.getElementById('countdown').innerText = countdown;
                    if (countdown <= 0) {
                        window.location.href = 'https://valueaddedhealthcaresa.com/user_dashboard.php';
                    }
                    countdown--;
                }
                setInterval(updateCountdown, 1000);
            </script>
        </head>
        <body>
            <div class='container'>
                {$success_logo_html}
                <h2>Payment Successful!</h2>
                <p>Your receipt has been generated and sent to your email address.</p>
                <p>You will be redirected to your dashboard in <span id='countdown'>5</span> seconds.</p>
            </div>
        </body>
        </html>
        ";
    } else {
        // Email failed to send, notify the user but still acknowledge the payment
        // Initialize logo HTML for the success message with email issue
        $email_issue_logo_html = "";
        $email_issue_logo_path = 'images/logo.png';
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $email_issue_logo_path)) {
            $email_issue_logo_html = "<img src='{$email_issue_logo_path}' alt='VAHSA Logo'>";
        } else {
            // Optionally log the missing logo for the success message
            error_log("Email issue success message logo file not found at path: " . $_SERVER['DOCUMENT_ROOT'] . '/' . $email_issue_logo_path);
        }
        
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>Payment Successful with Email Issue</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f0f8ff;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                }
                .container {
                    text-align: center;
                    background-color: #fff;
                    padding: 40px;
                    border-radius: 10px;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                }
                .container img {
                    width: 150px;
                    margin-bottom: 20px;
                }
                .container h2 {
                    color: #0C4375;
                }
                .warning {
                    color: #cc0000;
                    margin-top: 20px;
                }
                .countdown {
                    font-size: 18px;
                    margin-top: 20px;
                    color: #555;
                }
            </style>
            <script>
                let countdown = 5;
                function updateCountdown() {
                    document.getElementById('countdown').innerText = countdown;
                    if (countdown <= 0) {
                        window.location.href = 'https://valueaddedhealthcaresa.com/user_dashboard.php';
                    }
                    countdown--;
                }
                setInterval(updateCountdown, 1000);
            </script>
        </head>
        <body>
            <div class='container'>
                {$email_issue_logo_html}
                <h2>Payment Successful!</h2>
                <p>Your receipt has been generated.</p>
                <p class='warning'>However, we encountered an issue sending the email. Please check your spam folder or contact support if you do not receive the invoice within the next few minutes.</p>
                <p>You will be redirected to your dashboard in <span id='countdown'>5</span> seconds.</p>
            </div>
        </body>
        </html>
        ";
        
        // Optionally, log the email sending failure for further investigation
        error_log("Failed to send email to {$email} for user ID {$user_id}.");
    }
} else {
    // Display error message in a styled div
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>Payment Failed</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #ffe6e6;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .container {
                text-align: center;
                background-color: #fff;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            .container h2 {
                color: #cc0000;
            }
            .container p {
                color: #555;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Payment Failed!</h2>
            <p>There was an error processing your payment. Please try again later.</p>
            <p>Error Details: " . htmlspecialchars($stmt_courses->error) . "</p>
        </div>
    </body>
    </html>
    ";
    // Log the error for troubleshooting
    error_log("Database Insert Error: " . $stmt_courses->error);
    // Halt further execution
    exit();
}

// Close the statement and connection
$stmt_courses->close();
$conn->close();
?>
