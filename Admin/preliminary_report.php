<?php 
session_start();
include("includes/db_connection.php");

// Include Dompdf for PDF generation
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

// Include the mailer configuration
require 'mail-config.php'; // Ensure this path is correct

// Include Admin Header
include("includes/admin_header.php");

// Retrieve the clinical query ID from the GET parameter
$clinical_id = $_GET['query_id'] ?? null;
if (!$clinical_id) {
    die("Clinical query ID is required.");
}

// Retrieve necessary data, including invoices
$sql = "SELECT cc.*, u.*, i.*
        FROM claimsqueries cc
        JOIN users u ON cc.user_id = u.user_id
        JOIN invoices i ON cc.invoice_id = i.invoice_id
        WHERE cc.clinical_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}
$stmt->bind_param("i", $clinical_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Invalid clinical query ID.");
}
$queryData = $result->fetch_assoc();
$user_id = $queryData['user_id'];

// Initialize message variables
$successMessage = '';
$errorMessage = '';
$errors = [];

// Function to handle file uploads
function uploadFiles($inputName, $uploadDir, $existingFiles = []) {
    global $errors;
    $uploadedFiles = [];

    // Ensure the upload directory exists
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            $errors[] = "Failed to create directory $uploadDir.";
            return [];
        }
    }

    if (isset($_FILES[$inputName]) && is_array($_FILES[$inputName]['name'])) {
        foreach ($_FILES[$inputName]['tmp_name'] as $index => $tmpName) {
            if ($_FILES[$inputName]['error'][$index] === UPLOAD_ERR_OK) {
                $originalName = basename($_FILES[$inputName]['name'][$index]);
                $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
                $baseName = pathinfo($originalName, PATHINFO_FILENAME);

                // Sanitize file name to prevent security issues
                $baseName = preg_replace("/[^a-zA-Z0-9_-]/", "_", $baseName);
                $fileName = $baseName . '.' . $fileExtension;
                $targetPath = $uploadDir . $fileName;

                // Check if file already exists
                if (in_array($targetPath, $existingFiles)) {
                    // Overwrite existing file
                    if (!unlink($targetPath)) {
                        $errors[] = "Failed to overwrite existing file $originalName.";
                        continue;
                    }
                }

                // Validate file type and size
                $allowedExtensions = ['pdf', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
                if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
                    $errors[] = "Invalid file type for $originalName. Allowed types: " . implode(', ', $allowedExtensions) . ".";
                    continue;
                }

                // Check file size (e.g., max 5MB)
                if ($_FILES[$inputName]['size'][$index] > 5 * 1024 * 1024) {
                    $errors[] = "File $originalName exceeds the maximum allowed size of 5MB.";
                    continue;
                }

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = $targetPath;
                } else {
                    $errors[] = "Failed to move uploaded file $originalName.";
                }
            } elseif ($_FILES[$inputName]['error'][$index] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "File upload error: " . $_FILES[$inputName]['error'][$index];
            }
        }

        // Return the array of uploaded file paths
        return $uploadedFiles;

    } else {
        // No files uploaded
        return [];
    }
}

// Function to send admin email to user
function sendAdminEmail($conn, $clinical_id, &$successMessage, &$errors) {
    // Retrieve user data
    $sql = "SELECT cc.*, u.*, i.*
            FROM claimsqueries cc
            JOIN users u ON cc.user_id = u.user_id
            JOIN invoices i ON cc.invoice_id = i.invoice_id
            WHERE cc.clinical_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $errors[] = "SQL Error: " . $conn->error;
        return;
    }
    $stmt->bind_param("i", $clinical_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $errors[] = "Invalid clinical query ID.";
        return;
    }
    $queryData = $result->fetch_assoc();

    // Extract necessary fields
    $to = $queryData['user_email']; // From invoices table
    $subject = trim($_POST['email_subject'] ?? '');
    $cc = trim($_POST['email_cc'] ?? '');
    $bcc = trim($_POST['email_bcc'] ?? '');

    // Sanitize admin input
    $adminMessage = trim($_POST['admin_message'] ?? '');
    if (empty($subject)) {
        $errors[] = "Email subject cannot be empty.";
        return;
    }
    if (empty($adminMessage)) {
        $errors[] = "Email message cannot be empty.";
        return;
    }

    // Prepare the email message in HTML format
    $message = "
        <html>
        <head>
            <title>" . htmlspecialchars($subject) . "</title>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .email-container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .email-header { background-color: #f4f4f4; padding: 20px; text-align: center; }
                .email-content { padding: 20px; }
                .email-footer { background-color: #f4f4f4; padding: 20px; text-align: center; font-size: 12px; color: #777; }
                a { color: #3498db; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h2>Value Added Healthcare SA</h2>
                </div>
                <div class='email-content'>
                    <p>Dear " . htmlspecialchars($queryData['billing_first_name']) . " " . htmlspecialchars($queryData['billing_last_name']) . ",</p>
                    <p>" . nl2br(htmlspecialchars($adminMessage)) . "</p>
                    <p>Please provide the additional information at your earliest convenience.</p>
                    <p><a href='https://valueaddedhealthcaresa.com/user_dashboard.php'>VAHSA Dashboard</a></p>
                    <p>Best regards,<br>Value Added Healthcare SA Team</p>
                </div>
                <div class='email-footer'>
                    &copy; " . date('Y') . " Value Added Healthcare SA. All rights reserved.
                </div>
            </div>
        </body>
        </html>
    ";

    // Handle attachments if any
    $attachments = [];
    if (isset($_FILES['email_attachments'])) {
        foreach ($_FILES['email_attachments']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['email_attachments']['error'][$index] === UPLOAD_ERR_OK) {
                $originalName = basename($_FILES['email_attachments']['name'][$index]);
                $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
                $baseName = pathinfo($originalName, PATHINFO_FILENAME);

                // Sanitize file name
                $baseName = preg_replace("/[^a-zA-Z0-9_-]/", "_", $baseName);
                $fileName = $baseName . '.' . $fileExtension;
                $uploadDir = 'uploads/email_attachments/';
                $targetPath = $uploadDir . $fileName;

                // Ensure upload directory exists
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0777, true)) {
                        $errors[] = "Failed to create directory $uploadDir.";
                        continue;
                    }
                }

                // Validate file type and size
                $allowedExtensions = ['pdf', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
                if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
                    $errors[] = "Invalid file type for $originalName. Allowed types: " . implode(', ', $allowedExtensions) . ".";
                    continue;
                }

                // Check file size (e.g., max 5MB)
                if ($_FILES['email_attachments']['size'][$index] > 5 * 1024 * 1024) {
                    $errors[] = "File $originalName exceeds the maximum allowed size of 5MB.";
                    continue;
                }

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $attachments[] = $targetPath;
                } else {
                    $errors[] = "Failed to move email attachment $originalName.";
                }
            }
        }
    }

    // Send the email using the sendEmail function from mailer.php
    // Assuming sendEmail accepts parameters: to, subject, message, attachments, cc, bcc
    $mailSent = sendEmail($to, $subject, $message, $attachments, $cc, $bcc);

    if ($mailSent) {
        $successMessage = "Email notification sent successfully.";
    } else {
        $errors[] = "Failed to send email notification.";
    }
}

// Function to retrieve all uploaded files categorized
function getAllUploadedFiles($reportsData) {
    $allFiles = [];

    // Decode JSON paths
    $preReports = json_decode($reportsData['pre_reports'] ?? '[]', true);
    $finalReports = json_decode($reportsData['final_reports'] ?? '[]', true);
    $additionalDocuments = json_decode($reportsData['additional_documents'] ?? '[]', true);
    $invoice = $reportsData['invoice'] ?? '';

    $allFiles['pre_reports'] = $preReports;
    $allFiles['final_reports'] = $finalReports;
    $allFiles['additional_documents'] = $additionalDocuments;
    if (!empty($invoice)) {
        $allFiles['invoice'] = [$invoice];
    }

    return $allFiles;
}

// Function to send reports email with invoice
function sendReportsEmail($conn, $queryData, $invoicePath, &$successMessage, &$errors) {
    $to = $queryData['user_email']; // From invoices table
    $subject = "VAHSA - Your Query Status Updated to Awaiting User Approval";

    // Prepare the email message in HTML format
    $message = "
        <html>
        <head>
            <title>VAHSA Query Status Update</title>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .email-container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .email-header { background-color: #f4f4f4; padding: 20px; text-align: center; }
                .email-content { padding: 20px; }
                .email-footer { background-color: #f4f4f4; padding: 20px; text-align: center; font-size: 12px; color: #777; }
                a { color: #3498db; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h2>Value Added Healthcare SA</h2>
                </div>
                <div class='email-content'>
                    <p>Dear " . htmlspecialchars($queryData['billing_first_name']) . " " . htmlspecialchars($queryData['billing_last_name']) . ",</p>
                    <p>Your query status has been updated to <strong>Awaiting User Approval</strong>.</p>
                    <p>Please log in to your dashboard to review and proceed.</p>
                    <p><a href='https://valueaddedhealthcaresa.com/user_dashboard.php'>VAHSA Dashboard</a></p>
                    <p>Best regards,<br>Value Added Healthcare SA Team</p>
                </div>
                <div class='email-footer'>
                    &copy; " . date('Y') . " Value Added Healthcare SA. All rights reserved.
                </div>
            </div>
        </body>
        </html>
    ";

    // Attach the invoice PDF
    $attachments = [$invoicePath];

    // Send the email using the sendEmail function from mailer.php
    $mailSent = sendEmail($to, $subject, $message, $attachments);

    if ($mailSent) {
        $successMessage .= " Email notification sent successfully.";
    } else {
        $errors[] = "Failed to send email notification.";
    }
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine which form was submitted
    if (isset($_POST['submit_reports'])) {
        // Handle Reports Submission

        // Fetch existing reports
        $reportsSql = "SELECT * FROM reports WHERE user_id = ? AND clinical_id = ?";
        $reportsStmt = $conn->prepare($reportsSql);
        if ($reportsStmt) {
            $reportsStmt->bind_param("ii", $user_id, $clinical_id);
            $reportsStmt->execute();
            $reportsResult = $reportsStmt->get_result();
            $reportsData = $reportsResult->fetch_assoc();
        } else {
            $reportsData = null;
            $errors[] = "Error fetching existing reports: " . $conn->error;
        }

        // Retrieve existing files to handle overwriting
        if ($reportsData) {
            $existingFiles = getAllUploadedFiles($reportsData);
        } else {
            $existingFiles = [];
        }

        // Upload pre-reports, final reports, and additional documents
        $preReportFiles = uploadFiles('pre_reports', 'uploads/pre_reports/', $existingFiles['pre_reports'] ?? []);
        $finalReportFiles = uploadFiles('final_reports', 'uploads/final_reports/', $existingFiles['final_reports'] ?? []);
        $additionalDocumentsFiles = uploadFiles('additional_documents', 'uploads/documents/', $existingFiles['additional_documents'] ?? []);

        // Proceed if there are no upload errors
        if (empty($errors)) {
            // Generate invoice using Dompdf
            $dompdf = new Dompdf();

            // Prepare HTML content for the invoice using data from the invoices table
            $invoiceHtml = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Professional Invoice</title>
                <style>
                    body { font-family: DejaVu Sans, sans-serif; }
                    .invoice-box {
                        max-width: 800px;
                        margin: auto;
                        padding: 30px;
                        border: 1px solid #eee;
                        box-shadow: 0 0 10px rgba(0, 0, 0, .15);
                        font-size: 16px;
                        line-height: 24px;
                        color: #555;
                    }
                    .invoice-box table {
                        width: 100%;
                        line-height: inherit;
                        text-align: left;
                    }
                    .invoice-box table td {
                        padding: 5px;
                        vertical-align: top;
                    }
                    .invoice-box table tr td:nth-child(2) {
                        text-align: right;
                    }
                    .invoice-box table tr.top table td {
                        padding-bottom: 20px;
                    }
                    .invoice-box table tr.information table td {
                        padding-bottom: 40px;
                    }
                    .invoice-box table tr.heading td {
                        background: #eee;
                        border-bottom: 1px solid #ddd;
                        font-weight: bold;
                    }
                    .invoice-box table tr.details td {
                        padding-bottom: 20px;
                    }
                    .invoice-box table tr.item td{
                        border-bottom: 1px solid #eee;
                    }
                    .invoice-box table tr.item.last td {
                        border-bottom: none;
                    }
                    .invoice-box table tr.total td:nth-child(2) {
                        border-top: 2px solid #eee;
                        font-weight: bold;
                    }
                </style>
            </head>
            <body>
                <div class="invoice-box">
                    <table cellpadding="0" cellspacing="0">
                        <tr class="top">
                            <td colspan="2">
                                <table>
                                    <tr>
                                        <td class="title">
                                            <h1>VAHSA Invoice</h1>
                                        </td>
                                        <td>
                                            Invoice #: ' . htmlspecialchars($queryData['invoice_id']) . '<br>
                                            Created: ' . date('F d, Y') . '<br>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr class="information">
                            <td colspan="2">
                                <table>
                                    <tr>
                                        <td>
                                            VAHSA<br>
                                            36b West Road<br>
                                            Glen Austin AH, Midrand, South Africa
                                        </td>

                                        <td>
                                            ' . htmlspecialchars($queryData['billing_first_name'] . ' ' . $queryData['billing_last_name']) . '<br>
                                            ' . htmlspecialchars($queryData['user_email']) . '<br>
                                            ' . nl2br(htmlspecialchars($queryData['billing_address'])) . '<br>
                                            ' . htmlspecialchars($queryData['billing_city']) . ', ' . htmlspecialchars($queryData['billing_postal_code']) . '<br>
                                            ' . htmlspecialchars($queryData['billing_province']) . ', ' . htmlspecialchars($queryData['billing_country']) . '
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr class="heading">
                            <td>Query Details</td>
                            <td></td>
                        </tr>

                        <tr class="item">
                            <td>Query Type</td>
                            <td>' . htmlspecialchars($queryData['query_type']) . '</td>
                        </tr>
                        <tr class="item">
                            <td>Query Description</td>
                            <td>' . htmlspecialchars($queryData['query_description']) . '</td>
                        </tr>
                        <tr class="item">
                            <td>Diagnosis Code</td>
                            <td>' . htmlspecialchars($queryData['diagnosis_code']) . '</td>
                        </tr>
                        <tr class="item last">
                            <td>Procedure Code</td>
                            <td>' . htmlspecialchars($queryData['procedure_code']) . '</td>
                        </tr>

                        <tr class="heading">
                            <td>Payment Details</td>
                            <td></td>
                        </tr>

                        <tr class="item">
                            <td>Query Price</td>
                            <td>R' . number_format($queryData['query_price'], 2) . '</td>
                        </tr>

                        <tr class="total">
                            <td></td>
                            <td>Total: R' . number_format($queryData['total_price'], 2) . '</td>
                        </tr>
                    </table>
                </div>
            </body>
            </html>';

            $dompdf->loadHtml($invoiceHtml);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Save the PDF to a file
            $invoiceDir = 'uploads/invoices/';
            if (!is_dir($invoiceDir)) {
                if (!mkdir($invoiceDir, 0777, true)) {
                    $errors[] = "Failed to create directory $invoiceDir.";
                }
            }
            $invoiceFileName = 'invoice_' . $queryData['invoice_id'] . '_' . time() . '.pdf';
            $invoicePath = $invoiceDir . $invoiceFileName;
            if (file_put_contents($invoicePath, $dompdf->output()) === false) {
                $errors[] = "Failed to save invoice PDF.";
            }

            if (empty($errors)) {
                // Merge existing file paths with newly uploaded files
                $existingPreReports = json_decode($reportsData['pre_reports'] ?? '[]', true);
                $existingFinalReports = json_decode($reportsData['final_reports'] ?? '[]', true);
                $existingAdditionalDocs = json_decode($reportsData['additional_documents'] ?? '[]', true);

                $preReportPaths = json_encode(array_merge($existingPreReports, $preReportFiles));
                $finalReportPaths = json_encode(array_merge($existingFinalReports, $finalReportFiles));
                $additionalDocumentsPaths = json_encode(array_merge($existingAdditionalDocs, $additionalDocumentsFiles));

                // Insert or Update into reports table
                if ($reportsData) {
                    // Update existing report
                    $updateSql = "
                        UPDATE reports 
                        SET pre_reports = ?, final_reports = ?, additional_documents = ?, invoice = ?, created_at = NOW()
                        WHERE user_id = ? AND clinical_id = ?
                    ";
                    $updateStmt = $conn->prepare($updateSql);
                    if (!$updateStmt) {
                        $errors[] = "Update Reports SQL Error: " . $conn->error;
                    } else {
                        $updateStmt->bind_param(
                            "ssssii",
                            $preReportPaths,
                            $finalReportPaths,
                            $additionalDocumentsPaths,
                            $invoicePath,
                            $user_id,
                            $clinical_id
                        );

                        if ($updateStmt->execute()) {
                            $successMessage = "Reports uploaded and invoice generated successfully.";
                        } else {
                            $errors[] = "Error: Unable to update reports. " . $updateStmt->error;
                        }
                    }
                } else {
                    // Insert new report
                    $insertSql = "
                        INSERT INTO reports (user_id, clinical_id, pre_reports, final_reports, additional_documents, invoice, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ";

                    $insertStmt = $conn->prepare($insertSql);
                    if (!$insertStmt) {
                        $errors[] = "Insert Reports SQL Error: " . $conn->error;
                    } else {
                        $insertStmt->bind_param(
                            "iissss",
                            $user_id,
                            $clinical_id,
                            $preReportPaths,
                            $finalReportPaths,
                            $additionalDocumentsPaths,
                            $invoicePath
                        );

                        if ($insertStmt->execute()) {
                            $successMessage = "Reports uploaded and invoice generated successfully.";
                        } else {
                            $errors[] = "Error: Unable to upload reports. " . $insertStmt->error;
                        }
                    }
                }
            }

            // Update claimsqueries status
            if (empty($errors)) {
                $updateStatusSql = "UPDATE claimsqueries SET status = 'Awaiting User Approval' WHERE clinical_id = ?";
                $updateStatusStmt = $conn->prepare($updateStatusSql);
                if (!$updateStatusStmt) {
                    $errors[] = "Update Status SQL Error: " . $conn->error;
                } else {
                    $updateStatusStmt->bind_param("i", $clinical_id);
                    if (!$updateStatusStmt->execute()) {
                        $errors[] = "Error updating status: " . $updateStatusStmt->error;
                    }
                }
            }

            // Send Automated Email to User with the Invoice Attached
            if (empty($errors)) {
                sendReportsEmail($conn, $queryData, $invoicePath, $successMessage, $errors);
            }

            if (!empty($errors)) {
                $errorMessage = implode("<br>", $errors);
            }
        }
    } elseif (isset($_POST['send_email'])) {
        // Handle Admin Email Submission
        sendAdminEmail($conn, $clinical_id, $successMessage, $errors);
        if (!empty($errors)) {
            $errorMessage = implode("<br>", $errors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Clinical Query</title>
    <!-- Include Bootstrap CSS for styling (optional) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Manage Clinical Query</h1>

        <!-- Upload Reports Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h2>Upload Preliminary and Final Reports</h2>
            </div>
            <div class="card-body">
                <form action="preliminary_report.php?query_id=<?php echo htmlspecialchars($clinical_id); ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="pre_reports" class="form-label">Upload Pre-Report(s):</label>
                        <input type="file" name="pre_reports[]" multiple class="form-control">
                        <small class="form-text text-muted">Optional. Upload one or more pre-reports.</small>
                    </div>
                    <div class="mb-3">
                        <label for="final_reports" class="form-label">Upload Final Report(s):</label>
                        <input type="file" name="final_reports[]" multiple class="form-control">
                        <small class="form-text text-muted">Optional. Upload one or more final reports.</small>
                    </div>
                    <div class="mb-3">
                        <label for="additional_documents" class="form-label">Upload Additional Document(s):</label>
                        <input type="file" name="additional_documents[]" multiple class="form-control">
                        <small class="form-text text-muted">Optional. Upload any additional documents.</small>
                    </div>
                    <button type="submit" name="submit_reports" class="btn btn-primary">Submit Reports</button>
                </form>
            </div>
        </div>

        <!-- Existing Uploaded Files -->
        <div class="card mb-4">
            <div class="card-header">
                <h2>Recently Uploaded Files</h2>
            </div>
            <div class="card-body">
                <?php
                // Fetch existing reports
                $reportsSql = "SELECT * FROM reports WHERE user_id = ? AND clinical_id = ?";
                $reportsStmt = $conn->prepare($reportsSql);
                if ($reportsStmt) {
                    $reportsStmt->bind_param("ii", $user_id, $clinical_id);
                    $reportsStmt->execute();
                    $reportsResult = $reportsStmt->get_result();
                    if ($reportsResult->num_rows > 0) {
                        $reportsData = $reportsResult->fetch_assoc();
                        $allFiles = getAllUploadedFiles($reportsData);

                        // Function to display file list
                        function displayFileList($files, $category) {
                            if (!empty($files)) {
                                echo '<h4>' . ucfirst(str_replace('_', ' ', $category)) . '</h4>';
                                echo '<ul class="list-group mb-3">';
                                foreach ($files as $filePath) {
                                    $fileName = basename($filePath);
                                    echo '
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            ' . htmlspecialchars($fileName) . '
                                            <div>
                                                <button type="button" class="btn btn-sm btn-info me-2 view-file-btn" data-file="' . htmlspecialchars($filePath) . '" data-bs-toggle="modal" data-bs-target="#viewFileModal">View</button>
                                                <a href="preliminary_report.php?query_id=' . htmlspecialchars($clinical_id) . '&delete_file=' . urlencode($filePath) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure you want to delete this file?\')">Delete</a>
                                            </div>
                                        </li>
                                    ';
                                }
                                echo '</ul>';
                            } else {
                                echo '<p>No ' . htmlspecialchars(str_replace('_', ' ', $category)) . ' uploaded.</p>';
                            }
                        }

                        // Display each category
                        displayFileList($allFiles['pre_reports'], 'pre_reports');
                        displayFileList($allFiles['final_reports'], 'final_reports');
                        displayFileList($allFiles['additional_documents'], 'additional_documents');

                        // Invoice
                        echo '<h4>Invoice</h4>';
                        if (!empty($allFiles['invoice'])) {
                            echo '<ul class="list-group mb-3">';
                            foreach ($allFiles['invoice'] as $invoicePath) {
                                $invoiceName = basename($invoicePath);
                                echo '
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        ' . htmlspecialchars($invoiceName) . '
                                        <div>
                                            <button type="button" class="btn btn-sm btn-info me-2 view-file-btn" data-file="' . htmlspecialchars($invoicePath) . '" data-bs-toggle="modal" data-bs-target="#viewFileModal">View</button>
                                            <a href="preliminary_report.php?query_id=' . htmlspecialchars($clinical_id) . '&delete_file=' . urlencode($invoicePath) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure you want to delete this invoice?\')">Delete</a>
                                        </div>
                                    </li>
                                ';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p>No invoice generated.</p>';
                        }
                    } else {
                        echo '<p>No reports found.</p>';
                    }
                } else {
                    echo '<p>Error fetching reports.</p>';
                }
                ?>
            </div>
        </div>

        <!-- Send Email Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h2>Send Email to User</h2>
            </div>
            <div class="card-body">
                <form action="preliminary_report.php?query_id=<?php echo htmlspecialchars($clinical_id); ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="email_subject" class="form-label">Subject:</label>
                        <input type="text" name="email_subject" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_message" class="form-label">Message:</label>
                        <textarea name="admin_message" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="email_cc" class="form-label">CC:</label>
                        <input type="email" name="email_cc" class="form-control" placeholder="optional@example.com">
                    </div>
                    <div class="mb-3">
                        <label for="email_bcc" class="form-label">BCC:</label>
                        <input type="email" name="email_bcc" class="form-control" placeholder="optional@example.com">
                    </div>
                    <div class="mb-3">
                        <label for="email_attachments" class="form-label">Attachments:</label>
                        <input type="file" name="email_attachments[]" multiple class="form-control">
                        <small class="form-text text-muted">Optional. Attach files to include with the email.</small>
                    </div>
                    <button type="submit" name="send_email" class="btn btn-secondary">Send Email</button>
                </form>
            </div>
        </div>
    </div>

    <!-- View File Modal -->
    <div class="modal fade" id="viewFileModal" tabindex="-1" aria-labelledby="viewFileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl"> <!-- Increased size for better viewing -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Dynamically set the iframe src -->
                    <iframe id="fileViewer" src="" width="100%" height="600px" frameborder="0"></iframe>
                </div>
                <div class="modal-footer">
                    <a href="#" id="downloadFile" class="btn btn-primary" target="_blank">Download</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Include SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($successMessage)) { ?>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                html: '<?php echo addslashes($successMessage); ?>'
            });
        <?php } elseif (!empty($errorMessage)) { ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: '<?php echo addslashes($errorMessage); ?>'
            });
        <?php } ?>
    });

    // Handle file viewing in modal
    var viewFileModal = document.getElementById('viewFileModal');
    viewFileModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var filePath = button.getAttribute('data-file');
        var fileViewer = document.getElementById('fileViewer');
        var downloadLink = document.getElementById('downloadFile');

        // Determine file type
        var fileExtension = filePath.split('.').pop().toLowerCase();
        var embedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        if (embedTypes.includes(fileExtension)) {
            // For embeddable types, use iframe
            fileViewer.src = filePath;
            fileViewer.style.display = 'block';
        } else {
            // For non-embeddable types, hide iframe and provide download link
            fileViewer.style.display = 'none';
        }

        // Set the download link href
        downloadLink.href = filePath;
    });
    </script>
</body>
</html>
<?php
// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_file'])) {
    $fileToDelete = $_GET['delete_file'];

    // Security: Validate the file path to prevent directory traversal
    $realBase = realpath('uploads/');
    $realUserPath = realpath($fileToDelete);

    if ($realUserPath && strpos($realUserPath, $realBase) === 0 && file_exists($realUserPath)) {
        if (unlink($realUserPath)) {
            // Update the database to remove the file path
            $reportsSql = "SELECT * FROM reports WHERE user_id = ? AND clinical_id = ?";
            $reportsStmt = $conn->prepare($reportsSql);
            if ($reportsStmt) {
                $reportsStmt->bind_param("ii", $user_id, $clinical_id);
                $reportsStmt->execute();
                $reportsResult = $reportsStmt->get_result();
                if ($reportsResult->num_rows > 0) {
                    $reportsData = $reportsResult->fetch_assoc();
                    $allFiles = getAllUploadedFiles($reportsData);
                    $updated = false;

                    foreach ($allFiles as $category => $files) {
                        if (($key = array_search($fileToDelete, $files)) !== false) {
                            unset($allFiles[$category][$key]);
                            // Reindex the array
                            $allFiles[$category] = array_values($allFiles[$category]);
                            $updated = true;
                        }
                    }

                    if ($updated) {
                        // Update the reports table with the new file paths
                        $preReportPaths = json_encode($allFiles['pre_reports'] ?? []);
                        $finalReportPaths = json_encode($allFiles['final_reports'] ?? []);
                        $additionalDocumentsPaths = json_encode($allFiles['additional_documents'] ?? []);
                        $invoicePath = $allFiles['invoice'][0] ?? '';

                        $updateSql = "UPDATE reports SET pre_reports = ?, final_reports = ?, additional_documents = ?, invoice = ? WHERE user_id = ? AND clinical_id = ?";
                        $updateStmt = $conn->prepare($updateSql);
                        if ($updateStmt) {
                            $updateStmt->bind_param(
                                "ssssii",
                                $preReportPaths,
                                $finalReportPaths,
                                $additionalDocumentsPaths,
                                $invoicePath,
                                $user_id,
                                $clinical_id
                            );

                            if ($updateStmt->execute()) {
                                // Redirect to avoid resubmission
                                header("Location: preliminary_report.php?query_id=" . urlencode($clinical_id));
                                exit();
                            } else {
                                echo "Error updating reports after deletion: " . $updateStmt->error;
                            }
                        } else {
                            echo "Error preparing update statement after deletion: " . $conn->error;
                        }
                    }
                }
            }
        } else {
            echo "Failed to delete the file.";
        }
    } else {
        echo "Invalid file path.";
    }
}

// Include Admin Footer
include("includes/admin_footer.php");
?>
