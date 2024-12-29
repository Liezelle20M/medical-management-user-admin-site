<?php
// cancel_legal.php - Payment was canceled by the user

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page if not logged in
    header("Location: sign.php");
    exit; // Stop further execution of the page
}

// Database connection
include 'includes/db_connection.php';

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Check if medico_id exists in the session
if (isset($_SESSION['medico_id'])) {
    $medico_id = $_SESSION['medico_id'];

    // Delete the recently inserted medicolegal record using the user ID and medico_id
    $sql_delete = "DELETE FROM medicolegal WHERE user_id = ? AND medico_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param('ii', $user_id, $medico_id);

    if ($stmt_delete->execute()) {
        // Optionally, you could clear the medico_id from the session
        unset($_SESSION['medico_id']);
    } else {
        // Handle error if delete fails
        error_log("Error deleting medicolegal record: " . $stmt_delete->error);
    }

    // Close the delete statement
    $stmt_delete->close();
}

// Redirect to med.php after 5 seconds
header("Refresh: 5; URL=med.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Canceled</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }
        .cancel-message {
            color: orange;
            font-size: 24px;
        }
    </style>
</head>
<body>
    <h1 class="cancel-message">Payment Canceled</h1>
    <p>You will be redirected to your account in 5 seconds...</p>
</body>
</html>
