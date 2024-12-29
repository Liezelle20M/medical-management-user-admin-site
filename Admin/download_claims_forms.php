<?php
session_start();
include("includes/db_connection.php");

if (isset($_GET['file'])) {
    $fileId = $_GET['file'];

    // Fetch the file name from the database based on the `file` ID
    $sql = "SELECT file FROM claimsqueries WHERE clinical_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $stmt->bind_result($fileName);
    $stmt->fetch();
    $stmt->close();

    // Define the directories for files and forms
    $uploadsDir = '../uploads/';
    $formsDir = '../forms/';

    // Determine the full path based on file prefix or extension (modify based on your naming convention)
    if (strpos($fileName, 'form_') === 0) { // Example: if form files are prefixed with 'form_'
        $filePath = $formsDir . $fileName;
    } else {
        $filePath = $uploadsDir . $fileName;
    }

    // Check if file exists before attempting download
    if ($fileName && file_exists($filePath)) {
        // Set headers to initiate file download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
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
