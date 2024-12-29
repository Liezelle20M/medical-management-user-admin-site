<?php
session_start();

echo "<h1>Payment Failed</h1>";
echo "<p>Your payment was unsuccessful. Please try again.</p>";

// Check if the user is logged in and query_id exists in the session
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page
    header("Location: sign.php");
    exit;
}

if (isset($_SESSION['query_id'])) {
    // Get the query_id from session
    $id = $_SESSION['query_id'];

    // Database connection
    include 'includes/db_connection.php';

    // Prepare and execute the UPDATE statement to set payment_status to 'unpaid'
    $sql = "UPDATE claimsqueries SET payment_status = 'unpaid' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<p>Payment status for query with ID $id has been updated to 'unpaid'.</p>";

        // Optionally keep query_id in session for retrying the payment later
    } else {
        echo "<p>Error updating payment status: " . $stmt->error . "</p>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<p>No query found to update!</p>";
}

// Redirect to payment page after 4 seconds
header("refresh:4;url=payment_page.php");
exit();
?>
