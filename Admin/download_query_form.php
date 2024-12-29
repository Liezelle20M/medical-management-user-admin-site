<?php
session_start();
include("includes/db_connection.php");

if (isset($_GET['query_id'])) {
    $queryId = $_GET['query_id'];

    // Fetch the query form name from the database
    $sql = "SELECT query_form FROM claimsqueries WHERE clinical_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $queryId);
    $stmt->execute();
    $stmt->bind_result($queryFormPath);
    $stmt->fetch();
    $stmt->close();

    // Define the path to the `uploads` directory, which is outside `Admin`
    $uploadsDir = '../uploads/';

    // Construct the full file path for the query form
    $filePath = $uploadsDir . basename($queryFormPath); // Ensure to use basename to prevent path traversal

    // Check if the query form file exists before attempting download
    if ($queryFormPath && file_exists($filePath)) {
        // Set headers to initiate file download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        echo "Query form not found.";
    }
} else {
    echo "No query form specified.";
}
?>
