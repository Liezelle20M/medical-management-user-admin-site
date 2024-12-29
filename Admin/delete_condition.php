<?php
session_start();

include 'includes/db_connection.php'; 

// Check if ID and table are set
if (isset($_POST['id']) && isset($_POST['table'])) {
    $id = intval($_POST['id']); 
    $table = $_POST['table']; 


    $allowed_tables = ['manageclinicalcode', 'pmb_conditions', 'reasons_for_partial_or_nonpayment', 'claimsqueries'];

    if (in_array($table, $allowed_tables)) {

        $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            // Record deleted successfully, redirect back with success message
            echo "Record deleted successfully";
            header("Location: manageClinical.php?message=Record deleted successfully");
            exit();
        } else {
            // Error handling
            echo "Error deleting record: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Invalid table specified.";
    }
} else {
    echo "ID or table not specified.";
}

$conn->close();
exit;
?>
