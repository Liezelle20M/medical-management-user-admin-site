<?php
session_start();
include 'includes/db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo "Access denied.";
    exit();
}

// Validate input parameters
if (!isset($_GET['medico_id']) || !is_numeric($_GET['medico_id'])) {
    http_response_code(400);
    echo "Invalid Medico ID.";
    exit();
}
$medico_id = intval($_GET['medico_id']);

if (!isset($_GET['file_type']) || !in_array($_GET['file_type'], ['investigative', 'final', 'additional', 'invoice', 'receipt'])) {
    http_response_code(400);
    echo "Invalid file type.";
    exit();
}
$file_type = $_GET['file_type'];

// Fetch file path from database based on file_type
$sql = "SELECT investigative_report, final_report, additional_documents FROM med_reports WHERE medico_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $medico_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $reportData = $result->fetch_assoc();
    } else {
        http_response_code(404);
        echo "Report not found.";
        exit();
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo "Database error.";
    exit();
}

// Determine the file path based on file_type
switch ($file_type) {
    case 'investigative':
        $filePath = $reportData['investigative_report'];
        break;
    case 'final':
        $filePath = $reportData['final_report'];
        break;
    case 'additional':
        $filePath = $reportData['additional_documents'];
        break;
    case 'invoice':
        // Fetch invoice file path
        $sqlInvoice = "SELECT invoice_file FROM invoices_med WHERE in_med_id = ?";
        $stmtInvoice = $conn->prepare($sqlInvoice);
        if ($stmtInvoice) {
            $stmtInvoice->bind_param("i", $medico_id);
            $stmtInvoice->execute();
            $resultInvoice = $stmtInvoice->get_result();
            if ($resultInvoice->num_rows > 0) {
                $invoiceData = $resultInvoice->fetch_assoc();
                $filePath = $invoiceData['invoice_file'];
            } else {
                http_response_code(404);
                echo "Invoice not found.";
                exit();
            }
            $stmtInvoice->close();
        } else {
            http_response_code(500);
            echo "Database error.";
            exit();
        }
        break;
    case 'receipt':
        // Fetch receipt file path
        $sqlReceipt = "SELECT receipt_file1 FROM invoices_med WHERE in_med_id = ?";
        $stmtReceipt = $conn->prepare($sqlReceipt);
        if ($stmtReceipt) {
            $stmtReceipt->bind_param("i", $medico_id);
            $stmtReceipt->execute();
            $resultReceipt = $stmtReceipt->get_result();
            if ($resultReceipt->num_rows > 0) {
                $receiptData = $resultReceipt->fetch_assoc();
                $filePath = $receiptData['receipt_file1'];
            } else {
                http_response_code(404);
                echo "Receipt not found.";
                exit();
            }
            $stmtReceipt->close();
        } else {
            http_response_code(500);
            echo "Database error.";
            exit();
        }
        break;
    default:
        http_response_code(400);
        echo "Invalid file type.";
        exit();
}

// Prepend '../' to access the file outside Admin directory
$filePath = '../' . $filePath;

// Check if the file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    echo "File not found.";
    exit();
}

// Serve the file with appropriate headers
$mimeType = mime_content_type($filePath);
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));

// Force download for certain file types
$downloadableTypes = ['application/octet-stream', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
if (in_array($mimeType, $downloadableTypes)) {
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
} else {
    // Inline display for viewable types like PDF and images
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
}

readfile($filePath);
exit();
?>
