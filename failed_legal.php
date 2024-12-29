<?php
session_start();
include 'includes/db_connection.php';

// Redirect to payment_page_legal.php
header("Location: payment_page_legal.php");

// Check if session variables exist
if (!isset($_SESSION['email'], $_SESSION['first_name'], $_SESSION['last_name'], $_SESSION['query_id'], $_SESSION['user_id'])) {
    die("Missing session information!");
}

// Retrieve session data
$email = $_SESSION['email'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$user_id = $_SESSION['user_id'];
$query_id = $_SESSION['query_id'];

// Update the payment status in the receipts_med table
$updateStmt = $conn->prepare("UPDATE receipts_med SET payment_status = 'Unpaid' WHERE user_email = ? AND query_id = ?");
$updateStmt->bind_param('si', $email, $query_id);

if ($updateStmt->execute()) {
    echo "<p>Payment status updated to 'Unpaid' for query with ID $query_id.</p>";
} else {
    echo "<p>Error updating payment status: " . $updateStmt->error . "</p>";
}

// Check if query_id exists in the session
if (isset($_SESSION['query_id'])) {
    // Delete the recently inserted medicolegal record using the user ID and query_id
    $sql_delete = "DELETE FROM medicolegal WHERE user_id = ? AND query_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param('ii', $user_id, $query_id);

    if ($stmt_delete->execute()) {
        // Optionally, you could clear the query_id from the session
        unset($_SESSION['query_id']);
    } else {
        // Handle error if delete fails
        error_log("Error deleting medicolegal record: " . $stmt_delete->error);
    }

    // Close the delete statement
    $stmt_delete->close();
}

// Delete unpaid receipts that are older than 24 hours
$sql_delete_receipts = "DELETE FROM receipts_med WHERE payment_status = 'Unpaid' AND created_at < NOW() - INTERVAL 1 DAY";
$conn->query($sql_delete_receipts);

// Close the update statement and connection
$updateStmt->close();
$conn->close();
?>
