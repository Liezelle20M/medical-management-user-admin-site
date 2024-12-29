<?php
session_start();
include 'includes/db_connection.php';
include 'mail-config.php'; // Include PHPMailer configuration

// Handle AJAX form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    // Initialize response array
    $response = array('success' => false, 'message' => '');
    
    // Check if admin is logged in
    if (!isset($_SESSION['admin_id'])) {
        $response['message'] = 'Admin not logged in.';
        echo json_encode($response);
        exit();
    }

    // Get medico_id from POST data
    if (isset($_POST['medico_id']) && is_numeric($_POST['medico_id'])) {
        $medico_id = intval($_POST['medico_id']);
    } else {
        $response['message'] = 'Invalid Query ID.';
        echo json_encode($response);
        exit();
    }

    // Fetch query details from medicolegal table
    $sqlQuery = "SELECT ml.*, u.first_name, u.last_name, u.email, u.user_id, ml.query_type
                 FROM medicolegal ml
                 JOIN users u ON ml.user_id = u.user_id
                 WHERE ml.medico_id = ?";
    $stmt = $conn->prepare($sqlQuery);
    if ($stmt) {
        $stmt->bind_param("i", $medico_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $queryData = $result->fetch_assoc();
        } else {
            $response['message'] = "Medico-Legal Query not found.";
            echo json_encode($response);
            exit();
        }
        $stmt->close();
    } else {
        $response['message'] = "Database query failed.";
        echo json_encode($response);
        exit();
    }

    // Fetch existing report data if needed
    $sqlReports = "SELECT * FROM med_reports WHERE medico_id = ?";
    $stmtReports = $conn->prepare($sqlReports);
    if ($stmtReports) {
        $stmtReports->bind_param("i", $medico_id);
        $stmtReports->execute();
        $resultReports = $stmtReports->get_result();
        if ($resultReports->num_rows > 0) {
            $reportData = $resultReports->fetch_assoc();
        } else {
            $reportData = null; // No reports uploaded yet
        }
        $stmtReports->close();
    } else {
        $reportData = null;
    }

    $action = $_POST['action'];

    if ($action == 'upload_documents') {
        // Process uploaded files
        $uploadDir = 'med_reports/';
        $allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png'];

        // Create upload directories if they don't exist
        $investigativeDir = $uploadDir . 'investigative_reports/';
        $finalDir = $uploadDir . 'final_reports/';
        $additionalDir = $uploadDir . 'additional_documents/';

        if (!file_exists($investigativeDir)) {
            mkdir($investigativeDir, 0755, true);
        }
        if (!file_exists($finalDir)) {
            mkdir($finalDir, 0755, true);
        }
        if (!file_exists($additionalDir)) {
            mkdir($additionalDir, 0755, true);
        }

        // Initialize variables for file paths
        $investigativeReportPath = $reportData ? $reportData['investigative_report'] : null;
        $finalReportPath = $reportData ? $reportData['final_report'] : null;
        $additionalDocumentsPath = $reportData ? $reportData['additional_documents'] : null;

        $investigativeReportUploaded = false;

        // Function to handle individual file uploads
        function handleFileUpload($fileInputName, $currentPath, $uploadDirectory, $prefix, $medico_id, $allowedTypes, &$response) {
            if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] == 0) {
                $fileInfo = pathinfo($_FILES[$fileInputName]['name']);
                $ext = strtolower($fileInfo['extension']);
                if (in_array($ext, $allowedTypes)) {
                    // Delete existing file if it exists
                    if (!empty($currentPath) && file_exists($currentPath)) {
                        unlink($currentPath);
                    }

                    $uniqueName = $prefix . '_' . $medico_id . '.' . $ext; // Removed timestamp for consistent filenames
                    $filePath = $uploadDirectory . $uniqueName;
                    if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $filePath)) {
                        return $filePath;
                    } else {
                        $response['message'] .= "Failed to upload " . ucfirst(str_replace('_', ' ', $fileInputName)) . ".<br>";
                    }
                } else {
                    $response['message'] .= "Invalid file type for " . ucfirst(str_replace('_', ' ', $fileInputName)) . ".<br>";
                }
            }
            return $currentPath;
        }

        // Process Investigative Report
        $investigativeReportPath = handleFileUpload(
            'investigative_report',
            $investigativeReportPath,
            $investigativeDir,
            'investigative_report',
            $medico_id,
            $allowedTypes,
            $response
        );

        // Check if Investigative Report was uploaded
        if ($investigativeReportPath != ($reportData ? $reportData['investigative_report'] : null)) {
            $investigativeReportUploaded = true;
        }

        // Process Final Report
        $finalReportPath = handleFileUpload(
            'final_report',
            $finalReportPath,
            $finalDir,
            'final_report',
            $medico_id,
            $allowedTypes,
            $response
        );

        // Process Additional Document
        $additionalDocumentsPath = handleFileUpload(
            'additional_documents',
            $additionalDocumentsPath,
            $additionalDir,
            'additional_doc',
            $medico_id,
            $allowedTypes,
            $response
        );

        // Check if at least one file was uploaded
        if ($investigativeReportPath || $finalReportPath || $additionalDocumentsPath) {
            // Insert or update med_reports
            if ($reportData) {
                // Update existing record
                $sqlUpdateReport = "UPDATE med_reports 
                                    SET investigative_report = ?, final_report = ?, additional_documents = ?, uploaded_on = NOW()
                                    WHERE medico_id = ?";
                $stmtUpdateReport = $conn->prepare($sqlUpdateReport);
                if ($stmtUpdateReport) {
                    $stmtUpdateReport->bind_param(
                        "sssi",
                        $investigativeReportPath,
                        $finalReportPath,
                        $additionalDocumentsPath,
                        $medico_id
                    );
                    if ($stmtUpdateReport->execute()) {
                        $response['success'] = true;
                        $response['message'] .= "Documents uploaded successfully.<br>";
                    } else {
                        $response['message'] .= "Failed to update documents.<br>";
                    }
                    $stmtUpdateReport->close();
                } else {
                    $response['message'] .= "Failed to prepare update statement.<br>";
                }
            } else {
                // Insert new record
                $sqlInsertReport = "INSERT INTO med_reports (medico_id, user_id, investigative_report, final_report, additional_documents, uploaded_on)
                                    VALUES (?, ?, ?, ?, ?, NOW())";
                $stmtInsertReport = $conn->prepare($sqlInsertReport);
                if ($stmtInsertReport) {
                    $stmtInsertReport->bind_param(
                        "iisss",
                        $medico_id,
                        $queryData['user_id'],
                        $investigativeReportPath,
                        $finalReportPath,
                        $additionalDocumentsPath
                    );
                    if ($stmtInsertReport->execute()) {
                        $response['success'] = true;
                        $response['message'] .= "Documents uploaded successfully.<br>";
                    } else {
                        $response['message'] .= "Failed to upload documents.<br>";
                    }
                    $stmtInsertReport->close();
                } else {
                    $response['message'] .= "Failed to prepare insert statement.<br>";
                }
            }

            // If Investigative Report was uploaded, send email and update status
            if ($investigativeReportUploaded) {
                // Update status to 'Awaiting User Approval'
                $newStatus = 'Awaiting User Approval';
                $sqlUpdateStatus = "UPDATE medicolegal SET status = ? WHERE medico_id = ?";
                $stmtUpdateStatus = $conn->prepare($sqlUpdateStatus);
                if ($stmtUpdateStatus) {
                    $stmtUpdateStatus->bind_param("si", $newStatus, $medico_id);
                    if ($stmtUpdateStatus->execute()) {
                        $response['message'] .= "Status updated to 'Awaiting User Approval'.<br>";
                        // Prepare email to user
                        $to = $queryData['email'];
                        $subject = "Status Update: Your Medicolegal Query";
                        $message = "
                        <html>
                        <head>
                            <title>Status Update</title>
                        </head>
                        <body>
                            <p>Dear " . htmlspecialchars($queryData['first_name']) . ",</p>
                            <p>The status of your Medicolegal query (" . htmlspecialchars($queryData['query_type']) . ") has been updated to <strong>Awaiting User Approval</strong>.</p>
                            <p>Please <a href='https://valueaddedhealthcaresa.com/user_dashboard.php'>click here</a> to view the details and take any necessary actions.</p>
                            <p>Best regards,<br>Value Added Healthcare SA Team</p>
                        </body>
                        </html>
                        ";

                        // Use PHPMailer to send email
                        if (sendEmail($to, $subject, $message)) {
                            $response['message'] .= "Email sent to user regarding status update.<br>";
                        } else {
                            $response['message'] .= "Failed to send email to user.<br>";
                        }

                    } else {
                        $response['message'] .= "Failed to update status.<br>";
                    }
                    $stmtUpdateStatus->close();
                } else {
                    $response['message'] .= "Failed to prepare status update statement.<br>";
                }
            }

        } else {
            $response['message'] .= "No files were uploaded.<br>";
        }

        echo json_encode($response);
        exit();
    }

    if ($action == 'send_email') {
        $email_subject = trim($_POST['email_subject']);
        $email_message = trim($_POST['email_message']);

        if (!empty($email_subject) && !empty($email_message)) {
            $to = $queryData['email']; // Recipient's email from user data
            $subject = $email_subject;
            $message = "
            <html>
            <head>
                <title>" . htmlspecialchars($subject) . "</title>
            </head>
            <body>
                <p>Dear " . htmlspecialchars($queryData['first_name']) . ",</p>
                <p>" . nl2br(htmlspecialchars($email_message)) . "</p>
                <p>Best regards,<br>Value Added Healthcare SA Team</p>
            </body>
            </html>
            ";

            // Use PHPMailer to send email
            if (sendEmail($to, $subject, $message)) {
                $response['success'] = true;
                $response['message'] .= "Email sent successfully.<br>";
            } else {
                $response['message'] .= "Failed to send email.<br>";
            }
        } else {
            $response['message'] .= "Email subject and message cannot be empty.<br>";
        }

        echo json_encode($response);
        exit();
    }

}

// Include header and check if admin is logged in
include 'includes/admin_header.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Get medico_id from the URL
if (isset($_GET['query_id']) && is_numeric($_GET['query_id'])) {
    $medico_id = intval($_GET['query_id']);
} else {
    echo "Invalid Query ID.";
    exit();
}

// Fetch query details from medicolegal table
$sqlQuery = "SELECT ml.*, u.first_name, u.last_name, u.email, u.user_id, ml.query_type
             FROM medicolegal ml
             JOIN users u ON ml.user_id = u.user_id
             WHERE ml.medico_id = ?";
$stmt = $conn->prepare($sqlQuery);
if ($stmt) {
    $stmt->bind_param("i", $medico_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $queryData = $result->fetch_assoc();
    } else {
        echo "Medico-Legal Query not found.";
        exit();
    }
    $stmt->close();
} else {
    echo "Database query failed.";
    exit();
}

// Fetch invoice_med_id from queryData
$invoice_med_id = $queryData['invoice_med'];

if ($invoice_med_id) {
    // Fetch invoices_med details using in_med_id
    $sqlInvoice = "SELECT * FROM invoices_med WHERE in_med_id = ?";
    $stmtInvoice = $conn->prepare($sqlInvoice);
    if ($stmtInvoice) {
        $stmtInvoice->bind_param("i", $invoice_med_id);
        $stmtInvoice->execute();
        $resultInvoice = $stmtInvoice->get_result();
        if ($resultInvoice->num_rows > 0) {
            $invoiceData = $resultInvoice->fetch_assoc();
        } else {
            $invoiceData = null; // No invoice data yet
        }
        $stmtInvoice->close();
    } else {
        $invoiceData = null;
    }
} else {
    $invoiceData = null; // No invoice associated
}

// Fetch reports from med_reports
$sqlReports = "SELECT * FROM med_reports WHERE medico_id = ?";
$stmtReports = $conn->prepare($sqlReports);
if ($stmtReports) {
    $stmtReports->bind_param("i", $medico_id);
    $stmtReports->execute();
    $resultReports = $stmtReports->get_result();
    if ($resultReports->num_rows > 0) {
        $reportData = $resultReports->fetch_assoc();
    } else {
        $reportData = null; // No reports uploaded yet
    }
    $stmtReports->close();
} else {
    $reportData = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Include necessary meta tags and links -->
    <title>Medico-Legal Query Processing</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Styles -->
    <style>
        /* Existing styles */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
            color: #343a40;
        }
        .container {
            padding-top: 20px;
            padding-bottom: 20px;
        }
        .document-viewer {
            width: 100%;
            height: 500px;
            border: none;
        }
        /* Modal Styles */
        .modal-dialog {
            max-width: 80%;
            margin: 1.75rem auto;
        }
    </style>
</head>
<body>
       <div class="container">
        <h1>Medico-Legal Query Processing</h1>
        <hr>
        <!-- Success/Error Messages -->
        <!-- You can remove this div if it's no longer needed -->
        <div id="responseMessage"></div>

        <!-- Display Query Details -->
        <h3>User Information</h3>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($queryData['first_name'] . ' ' . $queryData['last_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($queryData['email']); ?></p>

        <h3>Query Details</h3>
        <p><strong>Query ID:</strong> <?php echo htmlspecialchars($queryData['medico_id']); ?></p>
        <p><strong>Query Type:</strong> <?php echo htmlspecialchars($queryData['query_type']); ?></p>
        <p><strong>Submitted On:</strong> <?php echo htmlspecialchars($queryData['submitted_on']); ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($queryData['status']); ?></p>

        <!-- Display Invoice and Receipt in Modal -->
        <h3>Invoice and Receipt</h3>
        <ul>
            <?php if ($invoiceData && !empty($invoiceData['invoice_file'])): ?>
                <li>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#fileModal" data-file="<?php echo '../' . htmlspecialchars($invoiceData['invoice_file']); ?>">
                        View Invoice
                    </a>
                </li>
            <?php endif; ?>
            <?php if ($invoiceData && !empty($invoiceData['receipt_file1'])): ?>
                <li>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#fileModal" data-file="<?php echo '../' . htmlspecialchars($invoiceData['receipt_file1']); ?>">
                        View Receipt
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Display Uploaded Reports if available -->
        <?php if ($reportData): ?>
            <h3>Uploaded Reports</h3>
            <!-- Investigative Report -->
            <?php if (!empty($reportData['investigative_report'])): ?>
                <h4>Investigative Report</h4>
                <div class="mb-4">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#fileModal" data-file="<?php echo htmlspecialchars($reportData['investigative_report']); ?>">
                        <?php echo htmlspecialchars(basename($reportData['investigative_report'])); ?>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Final Report -->
            <?php if (!empty($reportData['final_report'])): ?>
                <h4>Final Report</h4>
                <div class="mb-4">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#fileModal" data-file="<?php echo htmlspecialchars($reportData['final_report']); ?>">
                        <?php echo htmlspecialchars(basename($reportData['final_report'])); ?>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Additional Document -->
            <?php if (!empty($reportData['additional_documents'])): ?>
                <h4>Additional Document</h4>
                <div class="mb-4">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#fileModal" data-file="<?php echo htmlspecialchars($reportData['additional_documents']); ?>">
                        <?php echo htmlspecialchars(basename($reportData['additional_documents'])); ?>
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Form for Admin Actions -->
        <hr>
        <h3>Upload Documents</h3>
        <form id="uploadForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_documents">
            <input type="hidden" name="medico_id" value="<?php echo $medico_id; ?>">
            <!-- Investigative Report -->
            <div class="mb-3">
                <label for="investigative_report" class="form-label">Upload Investigative Report (Optional)</label>
                <input type="file" class="form-control" name="investigative_report" id="investigative_report">
            </div>
            <!-- Final Report -->
            <div class="mb-3">
                <label for="final_report" class="form-label">Upload Final Report (Optional)</label>
                <input type="file" class="form-control" name="final_report" id="final_report">
            </div>
            <!-- Additional Document -->
            <div class="mb-3">
                <label for="additional_documents" class="form-label">Upload Additional Document (Optional)</label>
                <input type="file" class="form-control" name="additional_documents" id="additional_documents">
            </div>
            <button type="submit" class="btn btn-primary">Upload Documents</button>
        </form>

        <hr>
        <!-- Send Email -->
        <h3>Send Email to User</h3>
        <form id="emailForm">
            <input type="hidden" name="action" value="send_email">
            <input type="hidden" name="medico_id" value="<?php echo $medico_id; ?>">
            <div class="mb-3">
                <label for="email_subject" class="form-label">Email Subject</label>
                <input type="text" class="form-control" name="email_subject" id="email_subject" required>
            </div>
            <div class="mb-3">
                <label for="email_message" class="form-label">Email Message</label>
                <textarea class="form-control" name="email_message" id="email_message" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-success">Send Email</button>
        </form>

    </div>

    <!-- File Viewer Modal -->
    <div class="modal fade" id="fileModal" tabindex="-1" aria-labelledby="fileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">File Viewer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe id="fileViewer" src="" class="document-viewer"></iframe>
                </div>
                <div class="modal-footer">
                    <a href="#" id="downloadBtn" class="btn btn-secondary" download>Download</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Include SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to handle AJAX form submissions
            function handleFormSubmit(formId) {
                const form = document.getElementById(formId);
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevent page refresh

                    const formData = new FormData(form);

                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(text => {
                        try {
                            const data = JSON.parse(text);
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    html: data.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    // Reload the page to update displayed data
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    html: data.message
                                });
                            }
                        } catch (err) {
                            console.error('JSON parsing error:', err);
                            console.error('Response text:', text);
                            Swal.fire({
                                icon: 'error',
                                title: 'An error occurred',
                                text: 'Invalid response from server.'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'An error occurred',
                            text: error.message
                        });
                    });
                });
            }

            // Initialize AJAX handlers for all forms
            handleFormSubmit('uploadForm');
            handleFormSubmit('emailForm');
            // Removed handleFormSubmit('statusForm');

            // Handle File Viewer Modal
            var fileModal = document.getElementById('fileModal');
            fileModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var fileSrc = button.getAttribute('data-file');
                var fileViewer = document.getElementById('fileViewer');
                var downloadBtn = document.getElementById('downloadBtn');
                fileViewer.src = fileSrc;
                downloadBtn.href = fileSrc;
            });
            fileModal.addEventListener('hidden.bs.modal', function () {
                var fileViewer = document.getElementById('fileViewer');
                fileViewer.src = '';
                var downloadBtn = document.getElementById('downloadBtn');
                downloadBtn.href = '#';
            });
        });
    </script>
</body>
</html>
<?php
include 'includes/admin_footer.php';
$conn->close();
?>
