<?php
session_start();
include 'includes/db_connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Retrieve and validate query parameters
$query_id = isset($_GET['query_id']) ? intval($_GET['query_id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';

// Validate query_id and type
if ($query_id <= 0 || !in_array($type, ['clinical', 'medico'])) {
    die("Invalid query.");
}

// Fetch report data along with status
if ($type === 'clinical') {
    $sql = "SELECT r.pre_reports, r.final_reports, r.additional_documents, r.invoice, cc.invoice_id, cc.status
            FROM reports r
            JOIN claimsqueries cc ON r.clinical_id = cc.clinical_id
            WHERE r.clinical_id = ? AND cc.user_id = ?";
} else {
    $sql = "SELECT r.pre_reports, r.final_reports, r.additional_documents, r.invoice, m.invoice_id, m.status
            FROM reports r
            JOIN medicolegal m ON r.medico_id = m.medico_id
            WHERE r.medico_id = ? AND m.user_id = ?";
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("ii", $query_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No reports found for this query.");
}

$report = $result->fetch_assoc();

// Decode JSON data for reports and additional documents
$pre_reports = json_decode($report['pre_reports'], true);
$final_reports = json_decode($report['final_reports'], true);
$additional_documents = json_decode($report['additional_documents'], true);

// Ensure all are arrays or empty arrays
$pre_reports = is_array($pre_reports) ? $pre_reports : (!empty($report['pre_reports']) ? [$report['pre_reports']] : []);
$final_reports = is_array($final_reports) ? $final_reports : (!empty($report['final_reports']) ? [$report['final_reports']] : []);
$additional_documents = is_array($additional_documents) ? $additional_documents : (!empty($report['additional_documents']) ? [$report['additional_documents']] : []);

$invoice = $report['invoice'];
$invoice_id = $report['invoice_id'];
$status = strtolower($report['status']);

$stmt->close();

// Fetch invoice details
$sql_invoice = "SELECT query_name, total_price FROM invoices WHERE invoice_id = ?";
$stmt_invoice = $conn->prepare($sql_invoice);
if (!$stmt_invoice) {
    die("Database error: " . $conn->error);
}

$stmt_invoice->bind_param("i", $invoice_id);
$stmt_invoice->execute();
$result_invoice = $stmt_invoice->get_result();

if ($result_invoice->num_rows === 0) {
    die("Invoice not found.");
}

$invoice_data = $result_invoice->fetch_assoc();
$query_name = $invoice_data['query_name'];
$total_price = $invoice_data['total_price'];

$stmt_invoice->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the user agreed to terms and refund policy
    if (!isset($_POST['agree_terms']) || !isset($_POST['agree_refund'])) {
        $error = "You must agree to the Terms and Conditions and Refund Policy before proceeding.";
    } else {
        // Update status to 'Payment Requested'
        if ($type === 'clinical') {
            $updateSql = "UPDATE claimsqueries SET status = 'Payment Requested' WHERE clinical_id = ?";
        } else {
            $updateSql = "UPDATE medicolegal SET status = 'Payment Requested' WHERE medico_id = ?";
        }
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            die("Database error: " . $conn->error);
        }
        $updateStmt->bind_param("i", $query_id);
        $updateStmt->execute();
        $updateStmt->close();

        // Store necessary data in the session
        $_SESSION['invoice_id'] = $invoice_id;
        $_SESSION['query_id'] = $query_id;
        $_SESSION['query_type'] = $type;
        $_SESSION['query_name'] = $query_name;
        $_SESSION['total_price'] = $total_price;

        // Redirect to payment page
        header("Location: payment_page.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Review Query</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Custom Styles -->
    <style>
        .document-viewer {
            width: 100%;
            height: 600px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        /* Carousel Styles */
        .carousel-item {
            text-align: center;
        }
        .carousel-item iframe {
            margin: 0 auto;
        }
        .carousel-indicators li {
            background-color: #333;
        }
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: #333;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1>Review Your Query</h1>
    <p>Please review the reports and invoice before proceeding to payment.</p>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Preliminary Report Viewer -->
    <?php if (!empty($pre_reports)): ?>
        <h3>Preliminary Report(s)</h3>
        <?php foreach ($pre_reports as $index => $pre_report): ?>
            <?php
            $fileName = basename($pre_report);
            ?>
            <div class="mb-4">
                <h5><?php echo htmlspecialchars($fileName); ?></h5>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#preReportModal<?php echo $index; ?>">
                    View Preliminary Report
                </button>
                <a href="download.php?file_type=pre_report&query_id=<?php echo urlencode($query_id); ?>&type=<?php echo urlencode($type); ?>&file_index=<?php echo $index; ?>" class="btn btn-secondary">
                    Download Preliminary Report
                </a>

                <!-- Modal for Preliminary Report -->
                <div class="modal fade" id="preReportModal<?php echo $index; ?>" tabindex="-1" aria-labelledby="preReportModalLabel<?php echo $index; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Preliminary Report: <?php echo htmlspecialchars($fileName); ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <iframe src="download.php?file_type=pre_report&query_id=<?php echo urlencode($query_id); ?>&type=<?php echo urlencode($type); ?>&file_index=<?php echo $index; ?>&view=1" class="document-viewer"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Final Report Viewer -->
    <?php if (!empty($final_reports)): ?>
        <h3>Final Report(s)</h3>
        <?php foreach ($final_reports as $index => $final_report): ?>
            <?php
            $fileName = basename($final_report);
            ?>
            <div class="mb-4">
                <h5><?php echo htmlspecialchars($fileName); ?></h5>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#finalReportModal<?php echo $index; ?>">
                    View Final Report
                </button>
                <a href="download.php?file_type=final_report&query_id=<?php echo urlencode($query_id); ?>&type=<?php echo urlencode($type); ?>&file_index=<?php echo $index; ?>" class="btn btn-secondary">
                    Download Final Report
                </a>

                <!-- Modal for Final Report -->
                <div class="modal fade" id="finalReportModal<?php echo $index; ?>" tabindex="-1" aria-labelledby="finalReportModalLabel<?php echo $index; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Final Report: <?php echo htmlspecialchars($fileName); ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <iframe src="download.php?file_type=final_report&query_id=<?php echo urlencode($query_id); ?>&type=<?php echo urlencode($type); ?>&file_index=<?php echo $index; ?>&view=1" class="document-viewer"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Additional Documents Viewer -->
    <?php if (!empty($additional_documents)): ?>
        <h3>Additional Document(s)</h3>
        <?php foreach ($additional_documents as $index => $additional_document): ?>
            <?php
            $fileName = basename($additional_document);
            ?>
            <div class="mb-4">
                <h5><?php echo htmlspecialchars($fileName); ?></h5>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#additionalDocumentModal<?php echo $index; ?>">
                    View Additional Document
                </button>
                <a href="download.php?file_type=additional_document&query_id=<?php echo urlencode($query_id); ?>&type=<?php echo urlencode($type); ?>&file_index=<?php echo $index; ?>" class="btn btn-secondary">
                    Download Additional Document
                </a>

                <!-- Modal for Additional Document -->
                <div class="modal fade" id="additionalDocumentModal<?php echo $index; ?>" tabindex="-1" aria-labelledby="additionalDocumentModalLabel<?php echo $index; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Additional Document: <?php echo htmlspecialchars($fileName); ?></h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <iframe src="download.php?file_type=additional_document&query_id=<?php echo urlencode($query_id); ?>&type=<?php echo urlencode($type); ?>&file_index=<?php echo $index; ?>&view=1" class="document-viewer"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Invoice Viewer -->
    <?php if (!empty($invoice)): ?>
        <h3>Invoice</h3>
        <?php
        $fileName = basename($invoice);
        ?>
        <div class="mb-4">
            <h5><?php echo htmlspecialchars($fileName); ?></h5>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#invoiceModal">
                View Invoice
            </button>
            <a href="download.php?file_type=invoice&query_id=<?php echo urlencode($query_id); ?>&type=<?php echo urlencode($type); ?>" class="btn btn-secondary">
                Download Invoice
            </a>

            <!-- Modal for Invoice -->
            <div class="modal fade" id="invoiceModal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Invoice: <?php echo htmlspecialchars($fileName); ?></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <iframe src="download.php?file_type=invoice&query_id=<?php echo urlencode($query_id); ?>&type=<?php echo urlencode($type); ?>&view=1" class="document-viewer"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Links to Terms and Privacy Pages -->
    <div class="mt-4">
        <a href="terms.php" class="btn btn-link">Read Terms and Conditions</a>
        <a href="privacy.php" class="btn btn-link">Read Privacy Policy</a>
    </div>

    <!-- Agreement Form and Proceed to Payment Button -->
    <?php if ($status !== 'completed'): ?>
        <form method="post">
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="agreeTerms" name="agree_terms" required>
                <label class="form-check-label" for="agreeTerms">
                    I have read and agree to the <a href="terms.php">Terms and Conditions</a>.
                </label>
            </div>
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="agreeRefund" name="agree_refund" required>
                <label class="form-check-label" for="agreeRefund">
                    I have read and agree to the <a href="privacy.php">Privacy Policy</a>.
                </label>
            </div>

            <button type="submit" class="btn btn-primary mt-3" id="proceedButton">Proceed to Payment</button>
        </form>
    <?php else: ?>
        <div class="alert alert-info mt-4">
            Your query has been completed. Payment is no longer required.
        </div>
    <?php endif; ?>
</div>

<!-- Include Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Optional Custom JavaScript -->
<script>
    // If additional JavaScript is needed, add here
</script>
</body>
</html>
