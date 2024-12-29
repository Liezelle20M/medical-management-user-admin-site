<?php
// med_review.php

session_start();
include 'includes/db_connection.php'; // Ensure the path is correct
include 'includes/header.php'; // If applicable

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to send JSON response and exit (for AJAX, not used here but kept for consistency)
function send_json_response($success, $message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: sign.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get medico_id from GET parameters and validate
if (isset($_GET['medico_id']) && is_numeric($_GET['medico_id'])) {
    $medico_id = intval($_GET['medico_id']);
} else {
    // Invalid medico_id
    echo "<script>
            alert('Invalid Query ID.');
            window.location.href = 'user_dashboard.php';
          </script>";
    exit();
}

// Fetch medico-legal query details
$sqlMedico = "SELECT * FROM medicolegal WHERE medico_id = ? AND user_id = ?";
$stmtMedico = $conn->prepare($sqlMedico);
if ($stmtMedico) {
    $stmtMedico->bind_param("ii", $medico_id, $user_id);
    $stmtMedico->execute();
    $resultMedico = $stmtMedico->get_result();
    if ($resultMedico->num_rows === 0) {
        // No such query found for this user
        echo "<script>
                alert('No such query found.');
                window.location.href = 'user_dashboard.php';
              </script>";
        exit();
    }
    $medicoData = $resultMedico->fetch_assoc();
    $stmtMedico->close();
} else {
    // Query preparation failed
    echo "<script>
            alert('Database error.');
            window.location.href = 'user_dashboard.php';
          </script>";
    exit();
}

// Fetch invoice details from invoices_med using invoice_med
$invoiceData = null;
if (!empty($medicoData['invoice_med'])) {
    $sqlInvoice = "SELECT * FROM invoices_med WHERE in_med_id = ?";
    $stmtInvoice = $conn->prepare($sqlInvoice);
    if ($stmtInvoice) {
        $stmtInvoice->bind_param("i", $medicoData['invoice_med']);
        $stmtInvoice->execute();
        $resultInvoice = $stmtInvoice->get_result();
        if ($resultInvoice->num_rows > 0) {
            $invoiceData = $resultInvoice->fetch_assoc();
        }
        $stmtInvoice->close();
    }
}

// Fetch reports from med_reports using medico_id
$reportData = null;
$sqlReports = "SELECT * FROM med_reports WHERE medico_id = ?";
$stmtReports = $conn->prepare($sqlReports);
if ($stmtReports) {
    $stmtReports->bind_param("i", $medico_id);
    $stmtReports->execute();
    $resultReports = $stmtReports->get_result();
    if ($resultReports->num_rows > 0) {
        $reportData = $resultReports->fetch_assoc();
    }
    $stmtReports->close();
}

// Determine payment status based on 'status' field
$paymentStatus = in_array($medicoData['status'], ['Payment Received', 'Completed']);

// Calculate 70% deposit based on invoices_med table
$deposit_percentage = 0.70; // Changed from 0.30 to 0.70
$deposit_amount = 0;
if ($invoiceData && isset($invoiceData['fee'])) {
    $fee = floatval($invoiceData['fee']);
    $deposit_amount = round($fee * $deposit_percentage, 2);
} else {
    // Handle cases where fee is not set
    $fee = 0;
    $deposit_amount = 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medico-Legal Review</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (for eye icon) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/styles22.css">
    <!-- Custom Styles -->
    <style>
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
        /* Vertical Listing */
        .vertical-list p {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Medico-Legal Review</h1>
        <hr>
        
        <!-- Query Details -->
        <h3>Query Details</h3>
        <div class="vertical-list">
            <p><strong>Query ID:</strong> <?php echo htmlspecialchars($medicoData['medico_id']); ?></p>
            <p><strong>Query Type:</strong> <?php echo htmlspecialchars($medicoData['query_type']); ?></p>
            <p><strong>Case Description:</strong> <?php echo nl2br(htmlspecialchars($medicoData['case_description'])); ?></p>
            <p><strong>Fee:</strong> R<?php echo number_format($medicoData['fee'], 2) ; ?></p>
            <p><strong>Submitted On:</strong> <?php echo htmlspecialchars($medicoData['submitted_on']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($medicoData['status']); ?></p>
        </div>
        
        <hr>
        
        <!-- Invoice and Investigative Report -->
        <h3>Documents</h3>
        <div class="vertical-list">
            <?php if ($invoiceData && !empty($invoiceData['invoice_file'])): ?>
                <div class="mb-3">
                    <h4>Invoice</h4>
                    <p><strong>Invoice ID:</strong> <?php echo htmlspecialchars($invoiceData['in_med_id']); ?></p>
                    <!-- Trigger Modal for Invoice File -->
                    <p><strong>Invoice File:</strong> 
                        <a href="#" 
                           class="view-file-link" 
                           data-bs-toggle="modal" 
                           data-bs-target="#fileModal" 
                           data-file="<?php echo htmlspecialchars($invoiceData['invoice_file']); ?>">
                           <i class="bi bi-eye"></i> View Invoice
                        </a>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if ($reportData && !empty($reportData['investigative_report'])): ?>
                <div class="mb-3">
                    <h4>Investigative Report</h4>
                    <!-- Trigger Modal for Investigative Report -->
                    <p><strong>Report File:</strong> 
                        <a href="#" 
                           class="view-file-link" 
                           data-bs-toggle="modal" 
                           data-bs-target="#fileModal" 
                           data-file="<?php echo htmlspecialchars('Admin/' . $reportData['investigative_report']); ?>">
                           <i class="bi bi-eye"></i> View Investigative Report
                        </a>
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Final Report - Locked until status is 'Completed' -->
            <?php if ($medicoData['status'] === 'Completed' && $reportData && !empty($reportData['final_report'])): ?>
                <div class="mb-3">
                    <h4>Final Report</h4>
                    <!-- Trigger Modal for Final Report -->
                    <p><strong>Final Report File:</strong> 
                        <a href="#" 
                           class="view-file-link" 
                           data-bs-toggle="modal" 
                           data-bs-target="#fileModal" 
                           data-file="<?php echo htmlspecialchars('Admin/' . $reportData['final_report']); ?>">
                           <i class="bi bi-eye"></i> View Final Report
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <div class="mb-3">
                    <h4>Final Report</h4>
                    <p class="text-muted">Final Report will be available once the status is <strong>Completed</strong>.</p>
                </div>
            <?php endif; ?>
            
            <!-- Additional Documents - Locked until status is 'Completed' -->
            <?php if ($medicoData['status'] === 'Completed' && $reportData && !empty($reportData['additional_documents'])): ?>
                <div class="mb-3">
                    <h4>Additional Documents</h4>
                    <!-- Trigger Modal for Additional Documents -->
                    <p><strong>Additional Documents:</strong> 
                        <a href="#" 
                           class="view-file-link" 
                           data-bs-toggle="modal" 
                           data-bs-target="#fileModal" 
                           data-file="<?php echo htmlspecialchars('Admin/' . $reportData['additional_documents']); ?>">
                           <i class="bi bi-eye"></i> View Additional Documents
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <div class="mb-3">
                    <h4>Additional Documents</h4>
                    <p class="text-muted">Additional Documents will be available once the status is <strong>Completed</strong>.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <hr>
        
        <!-- Terms of Service and Refund Policy -->
        <h3>Terms of Service & Refund Policy</h3>
        <div class="mb-3">
            <p>Please read our <a href="terms_of_service.php" target="_blank">Terms of Service</a> and <a href="refund_policy.php" target="_blank">Refund Policy</a> before proceeding with the payment.</p>
        </div>
        
        <!-- Agreement Checkboxes -->
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="" id="agreeTOS">
            <label class="form-check-label" for="agreeTOS">
                I agree to the <a href="terms_of_service.php" target="_blank">Terms of Service</a>.
            </label>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="" id="agreeRefund">
            <label class="form-check-label" for="agreeRefund">
                I have read and understood the <a href="refund_policy.php" target="_blank">Refund Policy</a>.
            </label>
        </div>
        
        <hr>
        
        <!-- Proceed to Payment Button -->
        <?php if (!$paymentStatus): ?>
            <button id="proceedPaymentBtn" class="btn btn-success" disabled>Proceed to Payment</button>
        <?php else: ?>
            <p class="text-success">Payment has been received. You can now access your final reports and collaborate further.</p>
        <?php endif; ?>
        
        <hr>
        
        <!-- Further Collaboration Instructions (After Payment) -->
        <?php if ($paymentStatus): ?>
            <h3>Further Collaboration</h3>
            <div class="vertical-list">
                <p>Thank you for your payment. You can now access your final reports and additional documents.</p>
                <p>If you require further assistance, such as Advice, Defense Response, or Referral, please contact us:</p>
                <ul>
                    <li><strong>Phone:</strong> +27 73 922 1860</li>
                    <li><strong>Email:</strong> vahsa_health@outlook.com</li>
                </ul>
                <p>Please follow up with us to implement the required services.</p>
            </div>
        <?php endif; ?>
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
            // Handle File Viewer Modal
            var fileModal = document.getElementById('fileModal');
            fileModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var fileSrc = button.getAttribute('data-file');
                var fileViewer = document.getElementById('fileViewer');
                var downloadBtn = document.getElementById('downloadBtn');
                
                // Optionally, you can adjust the fileSrc to be a full URL if necessary
                // For security, ensure that the file paths are validated and sanitized
                
                fileViewer.src = fileSrc;
                downloadBtn.href = fileSrc;
            });
            fileModal.addEventListener('hidden.bs.modal', function () {
                var fileViewer = document.getElementById('fileViewer');
                fileViewer.src = '';
                var downloadBtn = document.getElementById('downloadBtn');
                downloadBtn.href = '#';
            });
            
            // Handle Proceed to Payment Button Enablement
            var agreeTOS = document.getElementById('agreeTOS');
            var agreeRefund = document.getElementById('agreeRefund');
            var proceedPaymentBtn = document.getElementById('proceedPaymentBtn');

            if (proceedPaymentBtn) { // Check if the button exists
                function toggleProceedButton() {
                    if (agreeTOS.checked && agreeRefund.checked) {
                        proceedPaymentBtn.disabled = false;
                    } else {
                        proceedPaymentBtn.disabled = true;
                    }
                }

                // Initial check
                toggleProceedButton();

                // Add event listeners
                agreeTOS.addEventListener('change', toggleProceedButton);
                agreeRefund.addEventListener('change', toggleProceedButton);

                // Add click event to redirect to payment only if enabled
                proceedPaymentBtn.addEventListener('click', function() {
                    if (!proceedPaymentBtn.disabled) {
                        // Optionally, you can add a confirmation dialog
                        window.location.href = 'payment_process_legal_two.php?medico_id=<?php echo urlencode($medico_id); ?>';
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php
include 'includes/footer.php'; // If applicable
$conn->close();
?>