<?php
session_start();

echo "<h1>Payment Canceled</h1>";
echo "<p>You have canceled the payment. You will be redirected to the home page.</p>";

// Check if the query_id exists in the session
if (isset($_SESSION['query_id'])) {
    // Get the query_id from session
    $id = $_SESSION['query_id'];

    // Database connection
    include 'includes/db_connection.php';

    // Prepare and execute the DELETE statement
    $sql = "DELETE FROM claimsqueries WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<p>Query with ID $id has been successfully deleted.</p>";

        // Optionally clear the query_id from session
        unset($_SESSION['query_id']);
    } else {
        echo "<p>Error deleting the query: " . $stmt->error . "</p>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<p>No query found to delete!</p>";
}

// Redirect to the home page after 4 seconds
header("refresh:4;url=index.php");
exit();
?>
