<?php
session_start();
include("includes/db_connection.php");

if (isset($_GET['file'])) {
    $fileId = $_GET['file'];

    // Define the directory for query forms
    $pdfsDir = '../pdfs/';

    // Fetch the query_form name from the `medicolegal` table
    $sql = "SELECT query_form FROM medicolegal WHERE medico_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $stmt->bind_result($queryForm);
    $stmt->fetch();
    $stmt->close();

    // Remove any directory path (e.g., "pdfs/") from the query_form filename if it exists
    $fileName = basename($queryForm);  // This will strip any path and get only the filename, e.g., "appointment_89.pdf"
    $filePath = $pdfsDir . $fileName;  // Construct the path within the "pdfs" directory

    // Check if file exists and initiate download
    if ($queryForm && file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        echo "File not found.";
    }
} else {
    echo "No file specified.";
}
?>
