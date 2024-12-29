<?php 
// admin_dashboard.php

session_start(); 


// Check if the admin is logged in by verifying the session variable
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Admin is not logged in, redirect to the admin login page
    header('Location: admin_login.php');
    exit();
}


include 'includes/db_connection.php'; // Ensure this path is correct

// =========================
// Fetch Dashboard Metrics Separately
// =========================

// 1. Total Users
$userQuery = "SELECT COUNT(*) as total_users FROM users";
$usersResult = $conn->query($userQuery);
$totalUsers = ($usersResult && $usersResult->num_rows > 0) ? $usersResult->fetch_assoc()['total_users'] : 0;

// -------------------------
// Clinical Queries Metrics
// -------------------------

// Total Clinical Queries and Total Clinical Price from Invoices
$totalClinicalQueries = 0;
$totalClinicalPrice = 0;

// Updated Query: Join claimsqueries with invoices to fetch total_price
$queryClinicalTotal = "SELECT COUNT(*) as total_clinical_queries, SUM(i.total_price) as total_clinical_price 
                       FROM claimsqueries cc
                       LEFT JOIN invoices i ON cc.invoice_id = i.invoice_id";
$resultClinical = $conn->query($queryClinicalTotal);
if ($resultClinical && $resultClinical->num_rows > 0) {
    $row = $resultClinical->fetch_assoc();
    $totalClinicalQueries = $row['total_clinical_queries'] ? $row['total_clinical_queries'] : 0;
    $totalClinicalPrice = $row['total_clinical_price'] ? $row['total_clinical_price'] : 0;
}

// Payments Received for Clinical Queries
$paymentsReceivedClinical = 0;
$queryPaymentsClinical = "SELECT SUM(i.total_price) as payments_received 
                          FROM claimsqueries cc
                          JOIN invoices i ON cc.invoice_id = i.invoice_id
                          WHERE cc.status = 'Payment Received'";
$resultPaymentsClinical = $conn->query($queryPaymentsClinical);
if ($resultPaymentsClinical && $resultPaymentsClinical->num_rows > 0) {
    $row = $resultPaymentsClinical->fetch_assoc();
    $paymentsReceivedClinical = $row['payments_received'] ? $row['payments_received'] : 0;
}

// Pending Payments for Clinical Queries
$pendingPaymentsClinical = 0;
$queryPendingPaymentsClinical = "SELECT SUM(i.total_price) as pending_payments 
                                   FROM claimsqueries cc
                                   JOIN invoices i ON cc.invoice_id = i.invoice_id
                                   WHERE cc.status = 'Payment Requested'";
$resultPendingClinical = $conn->query($queryPendingPaymentsClinical);
if ($resultPendingClinical && $resultPendingClinical->num_rows > 0) {
    $row = $resultPendingClinical->fetch_assoc();
    $pendingPaymentsClinical = $row['pending_payments'] ? $row['pending_payments'] : 0;
}

// Completed Clinical Services
$totalCompletedClinical = 0;
$queryCompletedClinical = "SELECT COUNT(*) as completed_clinical 
                            FROM claimsqueries cc
                            WHERE cc.status = 'Completed'";
$resultCompletedClinical = $conn->query($queryCompletedClinical);
if ($resultCompletedClinical && $resultCompletedClinical->num_rows > 0) {
    $row = $resultCompletedClinical->fetch_assoc();
    $totalCompletedClinical = $row['completed_clinical'] ? $row['completed_clinical'] : 0;
}

// -------------------------
// Medico-Legal Queries Metrics
// -------------------------

// Total Medico-Legal Queries
$totalMedicoLegalQueries = 0;
$totalMedicoLegalPrice = 0;
$queryMedicoLegalTotal = "SELECT COUNT(*) as total_medico_queries, SUM(quote_amount) as total_medico_price FROM medicolegal";
$resultMedicoLegal = $conn->query($queryMedicoLegalTotal);
if ($resultMedicoLegal && $resultMedicoLegal->num_rows > 0) {
    $row = $resultMedicoLegal->fetch_assoc();
    $totalMedicoLegalQueries = $row['total_medico_queries'] ? $row['total_medico_queries'] : 0;
    $totalMedicoLegalPrice = $row['total_medico_price'] ? $row['total_medico_price'] : 0;
}

// Payments Received for Medico-Legal Queries
$paymentsReceivedMedico = 0;
$queryPaymentsMedico = "SELECT SUM(quote_amount) as payments_received FROM medicolegal WHERE status = 'Payment Received'";
$resultPaymentsMedico = $conn->query($queryPaymentsMedico);
if ($resultPaymentsMedico && $resultPaymentsMedico->num_rows > 0) {
    $row = $resultPaymentsMedico->fetch_assoc();
    $paymentsReceivedMedico = $row['payments_received'] ? $row['payments_received'] : 0;
}

// Pending Payments for Medico-Legal Queries
$pendingPaymentsMedico = 0;
$queryPendingPaymentsMedico = "SELECT SUM(quote_amount) as pending_payments FROM medicolegal WHERE status = 'Payment Requested'";
$resultPendingMedico = $conn->query($queryPendingPaymentsMedico);
if ($resultPendingMedico && $resultPendingMedico->num_rows > 0) {
    $row = $resultPendingMedico->fetch_assoc();
    $pendingPaymentsMedico = $row['pending_payments'] ? $row['pending_payments'] : 0;
}

// Completed Medico-Legal Services
$totalCompletedMedico = 0;
$queryCompletedMedico = "SELECT COUNT(*) as completed_medico FROM medicolegal WHERE status = 'Completed'";
$resultCompletedMedico = $conn->query($queryCompletedMedico);
if ($resultCompletedMedico && $resultCompletedMedico->num_rows > 0) {
    $row = $resultCompletedMedico->fetch_assoc();
    $totalCompletedMedico = $row['completed_medico'] ? $row['completed_medico'] : 0;
}

// =========================
// Fetch All Clinical Queries with Pagination and Search
// =========================

// Define Pagination Settings
$limit = 10; // Number of queries per page

// Determine Current Page for Clinical Queries
$pageClinical = isset($_GET['page_clinical']) && is_numeric($_GET['page_clinical']) ? intval($_GET['page_clinical']) : 1;
$pageClinical = max($pageClinical, 1);
$offsetClinical = ($pageClinical - 1) * $limit;

// Determine Current Page for Medico-Legal Queries
$pageMedico = isset($_GET['page_medico']) && is_numeric($_GET['page_medico']) ? intval($_GET['page_medico']) : 1;
$pageMedico = max($pageMedico, 1);
$offsetMedico = ($pageMedico - 1) * $limit;

// Handle Search Queries
$searchClinical = isset($_GET['search_clinical']) ? trim($_GET['search_clinical']) : '';
$searchMedico = isset($_GET['search_medico']) ? trim($_GET['search_medico']) : '';

// =========================
// Fetch All Clinical Queries (Including Completed) Ordered by Status
// =========================

// Define Clinical Statuses for Ordering
$clinical_status_order = ['Pending Review', 'Approved', 'Flagged', 'Replacement Requested', 'Payment Requested', 'Payment Received', 'Completed'];

// Convert the status order array to a comma-separated string for SQL
$clinical_status_order_sql = "'" . implode("','", $clinical_status_order) . "'";

// Count Total Clinical Queries (for Pagination)
$searchParamClinical = '%' . $searchClinical . '%';
$sqlCountClinical = "SELECT COUNT(*) as total FROM claimsqueries cc
                     JOIN users u ON cc.user_id = u.user_id
                     WHERE (cc.clinical_id LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR cc.query_type LIKE ?)";
$stmtCountClinical = $conn->prepare($sqlCountClinical);
if ($stmtCountClinical) {
    $stmtCountClinical->bind_param("sss", $searchParamClinical, $searchParamClinical, $searchParamClinical);
    $stmtCountClinical->execute();
    $resultCountClinical = $stmtCountClinical->get_result();
    $totalClinical = ($resultCountClinical && $resultCountClinical->num_rows > 0) ? $resultCountClinical->fetch_assoc()['total'] : 0;
    $stmtCountClinical->close();
} else {
    // Handle query preparation error
    error_log("dashboard.php: Failed to prepare clinical count query - " . $conn->error);
    $totalClinical = 0;
}

// Fetch All Clinical Queries Ordered by Status and Submitted On
$sqlClinical = "SELECT cc.clinical_id, u.first_name, u.last_name, cc.query_type, cc.submitted_on, cc.status, cc.file, cc.query_form
                FROM claimsqueries cc
                JOIN users u ON cc.user_id = u.user_id
                WHERE (cc.clinical_id LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR cc.query_type LIKE ?)
                ORDER BY 
                    FIELD(cc.status, $clinical_status_order_sql) ASC,
                    cc.submitted_on DESC
                LIMIT ? OFFSET ?";
$stmtClinical = $conn->prepare($sqlClinical);
if ($stmtClinical) {
    $stmtClinical->bind_param("sssii", $searchParamClinical, $searchParamClinical, $searchParamClinical, $limit, $offsetClinical);
    $stmtClinical->execute();
    $resultClinical = $stmtClinical->get_result();
    $stmtClinical->close();
} else {
    // Handle query preparation error
    error_log("dashboard.php: Failed to prepare clinical query - " . $conn->error);
    $resultClinical = null;
}

// Calculate Total Pages for Clinical Queries
$totalPagesClinical = ceil($totalClinical / $limit);

// =========================
// Fetch Pending Medico-Legal Queries
// =========================

// Define Medico-Legal Statuses to Display
$medico_statuses = ['Pending Review', 'Clarification Needed', 'Awaiting User Approval', 'Payment Requested', 'Payment Received', 'Completed', 'Refund Requested', 'Refunded', 'Unpaid'];

// Count Total Pending Medico-Legal Queries
$searchParamMedico = '%' . $searchMedico . '%';
$sqlCountMedico = "SELECT COUNT(*) as total FROM medicolegal ml
                   JOIN users u ON ml.user_id = u.user_id
                   WHERE ml.status IN ('" . implode("','", $medico_statuses) . "')
                   AND (ml.medico_id LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR ml.query_type LIKE ?)";
$stmtCountMedico = $conn->prepare($sqlCountMedico);
if ($stmtCountMedico) {
    $stmtCountMedico->bind_param("sss", $searchParamMedico, $searchParamMedico, $searchParamMedico);
    $stmtCountMedico->execute();
    $resultCountMedico = $stmtCountMedico->get_result();
    $totalMedicoPending = ($resultCountMedico && $resultCountMedico->num_rows > 0) ? $resultCountMedico->fetch_assoc()['total'] : 0;
    $stmtCountMedico->close();
} else {
    // Handle query preparation error
    error_log("dashboard.php: Failed to prepare medico-legal count query - " . $conn->error);
    $totalMedicoPending = 0;
}

// Fetch Pending Medico-Legal Queries
$sqlPendingMedico = "SELECT ml.medico_id, u.first_name, u.last_name, ml.query_type, ml.submitted_on, ml.status, ml.file, ml.query_form
                     FROM medicolegal ml
                     JOIN users u ON ml.user_id = u.user_id
                     WHERE ml.status IN ('" . implode("','", $medico_statuses) . "')
                     AND (ml.medico_id LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR ml.query_type LIKE ?)
                     ORDER BY ml.submitted_on DESC
                     LIMIT ? OFFSET ?";
$stmtPendingMedico = $conn->prepare($sqlPendingMedico);
if ($stmtPendingMedico) {
    $stmtPendingMedico->bind_param("sssii", $searchParamMedico, $searchParamMedico, $searchParamMedico, $limit, $offsetMedico);
    $stmtPendingMedico->execute();
    $resultPendingMedico = $stmtPendingMedico->get_result();
    $stmtPendingMedico->close();
} else {
    // Handle query preparation error
    error_log("dashboard.php: Failed to prepare medico-legal pending queries - " . $conn->error);
    $resultPendingMedico = null;
}

// Calculate Total Pages for Medico-Legal Queries
$totalPagesMedico = ceil($totalMedicoPending / $limit);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VAHSA Admin Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Custom Styles --><link rel="stylesheet" href="admin_styles.css">
    
</head>
<body>
  <?php include 'includes/admin_header.php'; ?>
    <h1 class="dashboard-header">VAHSA Admin Dashboard</h1>

    
    <div class="container">
        <!-- Dashboard Metrics Cards -->
        <div class="row mb-4">
            <!-- Total Users Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-white bg-info">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <h5 class="card-title">Total Users</h5>
                        <p class="card-text"><?php echo htmlspecialchars($totalUsers); ?></p>
                    </div>
                </div>
            </div>
            <!-- Total Clinical Queries Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-white bg-success">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <h5 class="card-title">Clinical Queries</h5>
                        <p class="card-text"><?php echo htmlspecialchars($totalClinicalQueries); ?></p>
                    </div>
                </div>
            </div>
            <!-- Total Medico-Legal Queries Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-white bg-warning">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <h5 class="card-title">Medico-Legal Queries</h5>
                        <p class="card-text"><?php echo htmlspecialchars($totalMedicoLegalQueries); ?></p>
                    </div>
                </div>
            </div>
            <!-- Total Invoice Price Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-white bg-danger">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <h5 class="card-title">Total Invoice Price</h5>
                        <p class="card-text">R<?php echo number_format($totalClinicalPrice + $totalMedicoLegalPrice, 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Metrics Cards -->
        <div class="row mb-4">
            <!-- Payments Received Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-white bg-secondary">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <h5 class="card-title">Payments Received</h5>
                        <p class="card-text">R<?php echo number_format($paymentsReceivedClinical + $paymentsReceivedMedico, 2); ?></p>
                    </div>
                </div>
            </div>
            <!-- Pending Payments Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-white bg-dark">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <h5 class="card-title">Pending Payments</h5>
                        <p class="card-text">R<?php echo number_format($pendingPaymentsClinical + $pendingPaymentsMedico, 2); ?></p>
                    </div>
                </div>
            </div>
            <!-- Total Completed Services Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-white bg-success">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <h5 class="card-title">Completed Services</h5>
                        <p class="card-text"><?php echo htmlspecialchars($totalCompletedClinical + $totalCompletedMedico); ?></p>
                    </div>
                </div>
            </div>
            <!-- Empty Placeholder Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-white bg-light">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <h5 class="card-title">—</h5>
                        <p class="card-text">—</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clinical Queries Section -->
        <div class="row table-section">
            <div class="col-12">
                <h2>Clinical Queries</h2>
                
                <!-- Search Form for Clinical Queries -->
                <form method="GET" action="admin_dashboard.php" class="row g-3 mb-3 search-form">
                    <div class="col-md-6">
                        <input type="text" name="search_clinical" class="form-control" placeholder="Search Clinical Queries..." value="<?php echo htmlspecialchars($searchClinical); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                    <div class="col-md-2">
                        <a href="admin_dashboard.php" class="btn btn-secondary w-100">Reset</a>
                    </div>
                </form>

                <?php if ($totalClinical > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Query ID</th>
                                    <th>User Name</th>
                                    <th>Query Type</th>
                                    <th>Submitted On</th>
                                    <th>Status</th>
                                    <th>File</th>
                                    <th>Query Form</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($resultClinical && $resultClinical->num_rows > 0): ?>
                                    <?php while($row = $resultClinical->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['clinical_id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['query_type']); ?></td>
                                            <td><?php echo htmlspecialchars(date("d M Y H:i", strtotime($row['submitted_on']))); ?></td>
                                            <td>
                                                <?php 
                                                    $status = $row['status'];
                                                    $badge_class = '';
                                                    switch($status) {
                                                        case 'Pending Review':
                                                            $badge_class = 'badge bg-warning';
                                                            break;
                                                        case 'Approved':
                                                            $badge_class = 'badge bg-success';
                                                            break;
                                                        case 'Flagged':
                                                            $badge_class = 'badge bg-danger';
                                                            break;
                                                        case 'Replacement Requested':
                                                            $badge_class = 'badge bg-info';
                                                            break;
                                                        case 'Payment Requested':
                                                            $badge_class = 'badge bg-primary';
                                                            break;
                                                        case 'Payment Received':
                                                            $badge_class = 'badge bg-primary';
                                                            break;
                                                        case 'Completed':
                                                            $badge_class = 'badge bg-success'; // Different color for completed
                                                            break;
                                                        default:
                                                            $badge_class = 'badge bg-secondary';
                                                    }
                                                ?>
                                                <span class="<?php echo $badge_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                                            </td>
                                          <!-- File Column (First Button) -->
<td>
    <?php if (!empty($row['file'])): ?>
        <a href="download_claims_forms.php?file=<?php echo urlencode($row['clinical_id']); ?>&type=file" class="btn btn-dark btn-sm" title="Download File">
            <i class="fas fa-download"></i>
        </a>
    <?php else: ?>
        N/A
    <?php endif; ?>
</td>

<td>
    <?php if (!empty($row['query_form'])): ?>
        <a href="download_query_form.php?query_id=<?php echo urlencode($row['clinical_id']); ?>" class="btn btn-dark btn-sm" title="Download Query Form">
            <i class="fas fa-download"></i>
        </a>
    <?php else: ?>
        N/A
    <?php endif; ?>
</td>

                                            <td>
                                                <!-- Existing action button -->
                                                <a href="preliminary_report.php?query_id=<?php echo urlencode($row['clinical_id']); ?>" class="btn btn-primary btn-sm" title="View & Submit Solution">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Links for Clinical Queries -->
                    <?php if ($totalPagesClinical > 1): ?>
                        <nav aria-label="Clinical Queries Page navigation">
                            <ul class="pagination justify-content-center">
                                <!-- Previous Button -->
                                <?php if ($pageClinical > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page_clinical=<?php echo $pageClinical - 1; ?>&search_clinical=<?php echo urlencode($searchClinical); ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <!-- Page Numbers -->
                                <?php 
                                // Determine the range of pages to display
                                $start = max($pageClinical - 2, 1);
                                $end = min($start + 4, $totalPagesClinical);
                                $start = max($end - 4, 1); // Adjust start if end is at the last page

                                for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?php echo ($i == $pageClinical) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page_clinical=<?php echo $i; ?>&search_clinical=<?php echo urlencode($searchClinical); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <!-- Next Button -->
                                <?php if ($pageClinical < $totalPagesClinical): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page_clinical=<?php echo $pageClinical + 1; ?>&search_clinical=<?php echo urlencode($searchClinical); ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No clinical queries found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Medico-Legal Queries Section -->
        <div class="row table-section">
            <div class="col-12">
                <h2>Medico-Legal Pending Queries</h2>

                <!-- Search Form for Medico-Legal Queries -->
                <form method="GET" action="admin_dashboard.php" class="row g-3 mb-3 search-form">
                    <div class="col-md-6">
                        <input type="text" name="search_medico" class="form-control" placeholder="Search Medico-Legal Queries..." value="<?php echo htmlspecialchars($searchMedico); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                    <div class="col-md-2">
                        <a href="admin_dashboard.php" class="btn btn-secondary w-100">Reset</a>
                    </div>
                </form>

                <?php if ($totalMedicoPending > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Query ID</th>
                                    <th>User Name</th>
                                    <th>Query Type</th>
                                    <th>Submitted On</th>
                                    <th>Status</th>
                                    <th>File</th>
                                    <th>Query Form</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($resultPendingMedico && $resultPendingMedico->num_rows > 0): ?>
                                    <?php while($row = $resultPendingMedico->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['medico_id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['query_type']); ?></td>
                                            <td><?php echo htmlspecialchars(date("d M Y H:i", strtotime($row['submitted_on']))); ?></td>
                                            <td>
                                                <?php 
                                                    $status = $row['status'];
                                                    $badge_class = '';
                                                    switch($status) {
                                                        case 'Pending Review':
                                                            $badge_class = 'badge bg-warning';
                                                            break;
                                                        case 'Clarification Needed':
                                                            $badge_class = 'badge bg-secondary';
                                                            break;
                                                        case 'Awaiting User Approval':
                                                            $badge_class = 'badge bg-info';
                                                            break;
                                                        case 'Payment Requested':
                                                            $badge_class = 'badge bg-primary';
                                                            break;
                                                        case 'Payment Received':
                                                        case 'Completed':
                                                            $badge_class = 'badge bg-success';
                                                            break;
                                                        case 'Refund Requested':
                                                        case 'Refunded':
                                                        case 'Unpaid':
                                                            $badge_class = 'badge bg-danger';
                                                            break;
                                                        default:
                                                            $badge_class = 'badge bg-secondary';
                                                    }
                                                ?>
                                                <span class="<?php echo $badge_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                                            </td>

                                            <!-- File Column -->
                                            <td>
                                                <?php if (!empty($row['file'])): ?>
                                                    <a href="download_medi.php?file=<?php echo urlencode($row['medico_id']); ?>&type=medico&column=file" class="btn btn-dark btn-sm" title="Download File">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>

                                            <!-- Query Form Column -->
                                            <td>
                                                <?php if (!empty($row['query_form'])): ?>
                                                    <a href="download_medi_query_form.php?file=<?php echo urlencode($row['medico_id']); ?>" class="btn btn-dark btn-sm" title="Download Query Form">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <a href="med_processing.php?type=medico&query_id=<?php echo urlencode($row['medico_id']); ?>" class="btn btn-primary btn-sm" title="View & Submit Solution">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Links for Medico-Legal Queries -->
                    <?php if ($totalPagesMedico > 1): ?>
                        <nav aria-label="Medico-Legal Queries Page navigation">
                            <ul class="pagination justify-content-center">
                                <!-- Previous Button -->
                                <?php if ($pageMedico > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page_medico=<?php echo $pageMedico - 1; ?>&search_medico=<?php echo urlencode($searchMedico); ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <!-- Page Numbers -->
                                <?php 
                                $start = max($pageMedico - 2, 1);
                                $end = min($start + 4, $totalPagesMedico);
                                $start = max($end - 4, 1);

                                for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?php echo ($i == $pageMedico) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page_medico=<?php echo $i; ?>&search_medico=<?php echo urlencode($searchMedico); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <!-- Next Button -->
                                <?php if ($pageMedico < $totalPagesMedico): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page_medico=<?php echo $pageMedico + 1; ?>&search_medico=<?php echo urlencode($searchMedico); ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No pending medico-legal queries at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include 'includes/admin_footer.php'; ?>

<!-- Bootstrap 5 JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
      
</body>
</html>
<?php
$conn->close();
?>
