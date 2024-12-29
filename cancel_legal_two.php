<?php
session_start();

// Check if 'medico_id' is set in the session
if (!isset($_SESSION['medico_id'])) {
    die("Missing session information: medico_id");
}

$medico_id = $_SESSION['medico_id'];

// Include database connection file
include 'includes/db_connection.php';

// Update the status in the 'medicolegal' table
$stmt = $conn->prepare("UPDATE medicolegal SET status = 'Payment Requested' WHERE medico_id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $medico_id);

if ($stmt->execute()) {
    // Display cancellation message and logo, then redirect to user dashboard
    echo "
        <html>
        <head>
            <title>Payment Canceled</title>
            <meta http-equiv='refresh' content='5;url=user_dashboard.php'>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    text-align: center;
                    padding-top: 50px;
                }
                .message-container {
                    max-width: 600px;
                    margin: 0 auto;
                }
                .logo {
                    width: 150px;
                    height: auto;
                }
                .message {
                    font-size: 18px;
                    color: #555;
                }
                .redirect-message {
                    font-size: 16px;
                    color: #777;
                    margin-top: 20px;
                }
                .button {
                    display: inline-block;
                    padding: 10px 20px;
                    font-size: 16px;
                    color: white;
                    background-color: #d9534f;
                    text-decoration: none;
                    border-radius: 5px;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class='message-container'>
                <img src='images/logo.jpg' alt='Payment Canceled Logo' class='logo'>
                <h1>Payment Canceled</h1>
                <p class='message'>Your payment has been canceled, and the status of your request has been updated to 'Payment Requested'.</p>
                <p class='redirect-message'>You will be redirected to your dashboard in a few seconds.</p>
                <a href='user_dashboard.php' class='button'>Go to Dashboard</a>
            </div>
        </body>
        </html>
    ";
} else {
    // Log an error message or handle the error accordingly
    error_log("Failed to update medicolegal status for medico_id: $medico_id");
    echo "Failed to cancel the payment. Please try again later.";
}

// Close the statement and database connection
$stmt->close();
$conn->close();
?>
