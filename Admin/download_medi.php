<?php
session_start();
include("includes/db_connection.php");

if (isset($_GET['file']) && isset($_GET['type'])) {
    $fileId = $_GET['file'];
    $type = $_GET['type'];

    // Verify that the type is specific to medicolegal files
    if ($type === 'medico') {
       $sql = "SELECT file, query_form FROM medicolegal WHERE medico_id = ?";
        $uploadsDir = '../uploads/';
        $pdfsDir = '../pdfs/';

        // Prepare and execute the statement
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $fileId);
        $stmt->execute();
        $stmt->bind_result($fileName, $queryForm);
        $stmt->fetch();
        $stmt->close();

        // Determine file path based on the type requested
        if ($fileName && $type === 'medico' && isset($_GET['column']) && $_GET['column'] === 'file') {
            $filePath = $uploadsDir . $fileName;
        } elseif ($queryForm && $type === 'medico' && isset($_GET['column']) && $_GET['column'] === 'query_form') {
            $filePath = $formsDir . $queryForm;
        } else {
            echo "File not found.";
            exit;
        }

        // Check if file exists and initiate download
        if (file_exists($filePath)) {
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
        echo "Invalid file type.";
    }
} else {
    echo "No file specified.";
}
?>
