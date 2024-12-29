<?php
session_start();

// Enable error reporting for development (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to the login page
    header("Location: sign.php"); // Adjust the path if necessary
    exit();
}

// Include the database connection
include 'includes/db_connection.php'; // Ensure the correct path

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Handle any session-based messages (e.g., after submitting a query)
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$success_message = htmlspecialchars($success_message); // Sanitize output
unset($_SESSION['success_message']);

// ----------------------
// Medico-Legal Queries Section with Pagination and Search
// ----------------------

// Define records per page
$records_per_page_medico = 10;

// Get the current page from GET parameter (default to 1)
$current_page_medico = isset($_GET['page_medico']) && is_numeric($_GET['page_medico']) ? (int)$_GET['page_medico'] : 1;
if ($current_page_medico < 1) {
    $current_page_medico = 1;
}

// Calculate the OFFSET for SQL
$offset_medico = ($current_page_medico - 1) * $records_per_page_medico;

// Capture search input
$search_medico_id = isset($_GET['search_medico_id']) ? trim($_GET['search_medico_id']) : '';

// Fetch total number of Medico-Legal Queries for the user
if (!empty($search_medico_id)) {
    $sql_total_medico = "SELECT COUNT(*) AS total FROM medicolegal m WHERE m.user_id = ? AND m.medico_id = ?";
    $stmt_total_medico = $conn->prepare($sql_total_medico);
    if (!$stmt_total_medico) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    $stmt_total_medico->bind_param("ii", $user_id, $search_medico_id);
} else {
    $sql_total_medico = "SELECT COUNT(*) AS total FROM medicolegal m WHERE m.user_id = ?";
    $stmt_total_medico = $conn->prepare($sql_total_medico);
    if (!$stmt_total_medico) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    $stmt_total_medico->bind_param("i", $user_id);
}
$stmt_total_medico->execute();
$result_total_medico = $stmt_total_medico->get_result();
$total_medico = $result_total_medico->fetch_assoc()['total'];

// Calculate total pages
$total_pages_medico = ceil($total_medico / $records_per_page_medico);

// Fetch Medico-Legal Queries submitted by the user with pagination and search
if (!empty($search_medico_id)) {
    $sql_medico_queries = "SELECT 
                                m.medico_id AS id, 
                                m.query_type, 
                                m.case_description, 
                                m.status, 
                                m.submitted_on AS created_at, 
                                m.quote_amount AS total_price, 
                                m.invoice_med 
                           FROM medicolegal m
                           WHERE m.user_id = ? AND m.medico_id = ?
                           ORDER BY m.submitted_on DESC 
                           LIMIT ? OFFSET ?";
    $stmt_medico = $conn->prepare($sql_medico_queries);
    if (!$stmt_medico) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    $stmt_medico->bind_param("iiii", $user_id, $search_medico_id, $records_per_page_medico, $offset_medico);
} else {
    $sql_medico_queries = "SELECT 
                                m.medico_id AS id, 
                                m.query_type, 
                                m.case_description, 
                                m.status, 
                                m.submitted_on AS created_at, 
                                m.quote_amount AS total_price, 
                                m.invoice_med 
                           FROM medicolegal m
                           WHERE m.user_id = ? 
                           ORDER BY m.submitted_on DESC 
                           LIMIT ? OFFSET ?";
    $stmt_medico = $conn->prepare($sql_medico_queries);
    if (!$stmt_medico) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    $stmt_medico->bind_param("iii", $user_id, $records_per_page_medico, $offset_medico);
}
$stmt_medico->execute();
$result_medico = $stmt_medico->get_result();

// ----------------------
// Clinical Queries Section with Pagination and Search
// ----------------------

// Define records per page
$records_per_page_clinical = 10;

// Get the current page from GET parameter (default to 1)
$current_page_clinical = isset($_GET['page_clinical']) && is_numeric($_GET['page_clinical']) ? (int)$_GET['page_clinical'] : 1;
if ($current_page_clinical < 1) {
    $current_page_clinical = 1;
}

// Calculate the OFFSET for SQL
$offset_clinical = ($current_page_clinical - 1) * $records_per_page_clinical;

// Capture search input
$search_clinical_id = isset($_GET['search_clinical_id']) ? trim($_GET['search_clinical_id']) : '';

// Fetch total number of Clinical Queries for the user
if (!empty($search_clinical_id)) {
    $sql_total_clinical = "SELECT COUNT(*) AS total FROM claimsqueries c WHERE c.user_id = ? AND c.clinical_id = ?";
    $stmt_total_clinical = $conn->prepare($sql_total_clinical);
    if (!$stmt_total_clinical) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    $stmt_total_clinical->bind_param("ii", $user_id, $search_clinical_id);
} else {
    $sql_total_clinical = "SELECT COUNT(*) AS total FROM claimsqueries c WHERE c.user_id = ?";
    $stmt_total_clinical = $conn->prepare($sql_total_clinical);
    if (!$stmt_total_clinical) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    $stmt_total_clinical->bind_param("i", $user_id);
}
$stmt_total_clinical->execute();
$result_total_clinical = $stmt_total_clinical->get_result();
$total_clinical = $result_total_clinical->fetch_assoc()['total'];

// Calculate total pages
$total_pages_clinical = ceil($total_clinical / $records_per_page_clinical);

// Fetch Clinical Queries submitted by the user with pagination and search
if (!empty($search_clinical_id)) {
    $sql_clinical_queries = "SELECT 
                                c.clinical_id AS id, 
                                c.query_type, 
                                c.query_description AS case_description, 
                                c.status, 
                                c.created_at AS created_at, 
                                c.invoice_id 
                             FROM claimsqueries c 
                             WHERE c.user_id = ? AND c.clinical_id = ?
                             ORDER BY c.created_at DESC 
                             LIMIT ? OFFSET ?";
    $stmt_clinical = $conn->prepare($sql_clinical_queries);
    if (!$stmt_clinical) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    $stmt_clinical->bind_param("iiii", $user_id, $search_clinical_id, $records_per_page_clinical, $offset_clinical);
} else {
    $sql_clinical_queries = "SELECT 
                                c.clinical_id AS id, 
                                c.query_type, 
                                c.query_description AS case_description, 
                                c.status, 
                                c.created_at AS created_at, 
                                c.invoice_id 
                             FROM claimsqueries c 
                             WHERE c.user_id = ? 
                             ORDER BY c.created_at DESC 
                             LIMIT ? OFFSET ?";
    $stmt_clinical = $conn->prepare($sql_clinical_queries);
    if (!$stmt_clinical) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    $stmt_clinical->bind_param("iii", $user_id, $records_per_page_clinical, $offset_clinical);
}
$stmt_clinical->execute();
$result_clinical = $stmt_clinical->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Medico-Legal & Clinical Queries</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/styles3.css"> <!-- Ensure this is loaded after Bootstrap -->
</head>
<body>
    <?php include 'includes/header.php'; ?> 

    <div id="user-dashboard" class="ud-container mt-5">
        <!-- Dashboard Header -->
        <h1 class="dashboard-header">My Queries Dashboard</h1>

        <!-- Display Success Message -->
        <div class="alert-position">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Dashboard Grid Layout -->
        <div class="dashboard-grid">
            <!-- Medico-Legal Queries Section -->
            <div class="dashboard-medico-legal">
                <div class="section-header">Medico-Legal Queries</div>
                
                <!-- Search Form -->
                <form method="GET" class="form-inline mb-3">
                    <div class="form-group mr-2">
                        <input type="text" name="search_medico_id" class="form-control" placeholder="Search by Query ID" value="<?php echo isset($_GET['search_medico_id']) ? htmlspecialchars($_GET['search_medico_id']) : ''; ?>">
                    </div>
                    <button type="submit" class="btn btn-outline-primary mr-2">Search</button>
                    <a href="user_dashboard.php" class="btn btn-outline-secondary">Reset</a>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Query ID</th>
                                <th>Query Type</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Submitted On</th>
                                <th>Progress</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_medico->num_rows > 0): ?>
                                <?php while ($query = $result_medico->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="Query ID"><?php echo htmlspecialchars($query['id'] ?? 'N/A'); ?></td>
                                        <td data-label="Query Type"><?php echo htmlspecialchars($query['query_type'] ?? 'N/A'); ?></td>
                                        <td data-label="Description"><?php echo htmlspecialchars(substr($query['case_description'] ?? '', 0, 50)) . '...'; ?></td>
                                        <td data-label="Status">
                                            <?php
                                                $status = $query['status'] ?? 'N/A';
                                                $badge_class = 'secondary';
                                                switch ($status) {
                                                    case 'Pending Review':
                                                        $badge_class = 'warning';
                                                        break;
                                                    case 'Clarification Needed':
                                                        $badge_class = 'danger';
                                                        break;
                                                    case 'Awaiting User Approval':
                                                        $badge_class = 'info';
                                                        break;
                                                    case 'Payment Requested':
                                                        $badge_class = 'primary';
                                                        break;
                                                    case 'Paid':
                                                        $badge_class = 'success';
                                                        break;
                                                    case 'Completed':
                                                        $badge_class = 'dark';
                                                        break;
                                                    case 'Refund Requested':
                                                        $badge_class = 'warning';
                                                        break;
                                                    case 'Refunded':
                                                        $badge_class = 'danger';
                                                        break;
                                                    case 'Unpaid':
                                                        $badge_class = 'secondary';
                                                        break;
                                                    default:
                                                        $badge_class = 'secondary';
                                                }
                                            ?>
                                            <span class="badge badge-<?php echo $badge_class; ?> status-badge"><?php echo htmlspecialchars($status); ?></span>
                                        </td>
                                        <td data-label="Submitted On"><?php echo htmlspecialchars(isset($query['created_at']) ? date("Y-m-d H:i", strtotime($query['created_at'])) : 'N/A'); ?></td>
                                        <td data-label="Progress">
                                            <?php
                                                // Determine progress based on status
                                                switch ($status) {
                                                    case 'Pending Review':
                                                        $progress = 20;
                                                        break;
                                                    case 'Clarification Needed':
                                                        $progress = 40;
                                                        break;
                                                    case 'Awaiting User Approval':
                                                        $progress = 60;
                                                        break;
                                                    case 'Payment Requested':
                                                        $progress = 70;
                                                        break;
                                                    case 'Payment Received':
                                                        $progress = 80;
                                                        break;
                                                    case 'In Progress':
                                                        $progress = 90;
                                                        break;
                                                    case 'Completed':
                                                    case 'Refund Requested':
                                                    case 'Refunded':
                                                        $progress = 100;
                                                        break;
                                                    case 'Unpaid':
                                                        $progress = 0;
                                                        break;
                                                    default:
                                                        $progress = 0;
                                                }
                                            ?>
                                            <div class="progress">
                                                <div 
                                                    class="progress-bar" 
                                                    role="progressbar" 
                                                    style="width: <?php echo $progress; ?>%;" 
                                                    aria-valuenow="<?php echo $progress; ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100">
                                                    <?php echo $progress; ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="Actions">
                                            <a href="med_review.php?medico_id=<?php echo urlencode($query['id']); ?>" class="btn btn-sm btn-primary action-btn" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($query['status'] === 'Partial Solution Provided'): ?>
                                                <a href="preliminary_solution.php?id=<?php echo urlencode($query['id']); ?>" class="btn btn-sm btn-success action-btn" title="Review Solution">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php 
                                                // Add Proceed to Payment button if status warrants payment
                                                if ($query['status'] === 'Payment Requested' || $query['status'] === 'Awaiting Payment'): ?>
                                                    <a href="proceed_payment.php?query_id=<?php echo urlencode($query['id']); ?>&type=medico" class="btn btn-sm btn-success action-btn" title="Proceed to Payment">
                                                        <i class="fas fa-dollar-sign"></i>
                                                    </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">You have not submitted any Medico-Legal queries yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls for Medico-Legal Queries -->
                <?php if ($total_pages_medico > 1): ?>
                    <nav aria-label="Medico-Legal Queries Pagination">
                        <ul class="pagination">
                            <!-- Previous Page Link -->
                            <li class="page-item <?php if ($current_page_medico <= 1) echo 'disabled'; ?>">
                                <a class="page-link" href="?page_medico=<?php echo max($current_page_medico - 1, 1); ?>&page_clinical=<?php echo $current_page_clinical; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <!-- Page Number Links -->
                            <?php
                                // Determine the range of pages to show
                                $max_links = 5; // Maximum number of page links to show
                                $start = max(1, $current_page_medico - floor($max_links / 2));
                                $end = min($start + $max_links - 1, $total_pages_medico);
                                
                                // Adjust start if we're near the end
                                if ($end - $start + 1 < $max_links) {
                                    $start = max(1, $end - $max_links + 1);
                                }

                                for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?php if ($i == $current_page_medico) echo 'active'; ?>">
                                        <a class="page-link" href="?page_medico=<?php echo $i; ?>&page_clinical=<?php echo $current_page_clinical; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            <!-- Next Page Link -->
                            <li class="page-item <?php if ($current_page_medico >= $total_pages_medico) echo 'disabled'; ?>">
                                <a class="page-link" href="?page_medico=<?php echo min($current_page_medico + 1, $total_pages_medico); ?>&page_clinical=<?php echo $current_page_clinical; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

                <!-- Submit Button for Medico-Legal Queries -->
                <div class="submit-button">
                    <a href="med.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Submit Medico-Legal Query</a>
                </div>
            </div> <!-- End of Medico-Legal Queries Section -->

            <!-- Clinical Queries Section -->
            <div class="dashboard-clinical">
                <div class="section-header">Clinical Queries</div>
                
                <!-- Search Form -->
                <form method="GET" class="form-inline mb-3">
                    <div class="form-group mr-2">
                        <input type="text" name="search_clinical_id" class="form-control" placeholder="Search by Query ID" value="<?php echo isset($_GET['search_clinical_id']) ? htmlspecialchars($_GET['search_clinical_id']) : ''; ?>">
                    </div>
                    <button type="submit" class="btn btn-outline-primary mr-2">Search</button>
                    <a href="user_dashboard.php" class="btn btn-outline-secondary">Reset</a>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Query ID</th>
                                <th>Query Type</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Submitted On</th>
                                <th>Progress</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_clinical->num_rows > 0): ?>
                                <?php while ($query = $result_clinical->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="Query ID"><?php echo htmlspecialchars($query['id'] ?? 'N/A'); ?></td>
                                        <td data-label="Query Type"><?php echo htmlspecialchars($query['query_type'] ?? 'N/A'); ?></td>
                                        <td data-label="Description"><?php echo htmlspecialchars(substr($query['case_description'] ?? '', 0, 50)) . '...'; ?></td>
                                        <td data-label="Status">
                                            <?php
                                                $status = $query['status'] ?? 'N/A';
                                                $badge_class = 'secondary';
                                                switch ($status) {
                                                    case 'Pending Review':
                                                        $badge_class = 'warning';
                                                        break;
                                                    case 'Clarification Needed':
                                                        $badge_class = 'danger';
                                                        break;
                                                    case 'Awaiting User Approval':
                                                        $badge_class = 'info';
                                                        break;
                                                    case 'Payment Requested':
                                                        $badge_class = 'primary';
                                                        break;
                                                    case 'Paid':
                                                        $badge_class = 'success';
                                                        break;
                                                    case 'Completed':
                                                        $badge_class = 'dark';
                                                        break;
                                                    case 'Refund Requested':
                                                        $badge_class = 'warning';
                                                        break;
                                                    case 'Refunded':
                                                        $badge_class = 'danger';
                                                        break;
                                                    case 'Unpaid':
                                                        $badge_class = 'secondary';
                                                        break;
                                                    default:
                                                        $badge_class = 'secondary';
                                                }
                                            ?>
                                            <span class="badge badge-<?php echo $badge_class; ?> status-badge"><?php echo htmlspecialchars($status); ?></span>
                                        </td>
                                        <td data-label="Submitted On"><?php echo htmlspecialchars(isset($query['created_at']) ? date("Y-m-d H:i", strtotime($query['created_at'])) : 'N/A'); ?></td>
                                        <td data-label="Progress">
                                            <?php
                                                // Determine progress based on status
                                                switch ($status) {
                                                    case 'Pending Review':
                                                        $progress = 20;
                                                        break;
                                                    case 'Clarification Needed':
                                                        $progress = 40;
                                                        break;
                                                    case 'Awaiting User Approval':
                                                        $progress = 60;
                                                        break;
                                                    case 'Payment Requested':
                                                        $progress = 70;
                                                        break;
                                                    case 'Payment Received':
                                                        $progress = 100;
                                                        break;
                                                    case 'In Progress':
                                                        $progress = 90;
                                                        break;
                                                    case 'Completed':
                                                    case 'Refund Requested':
                                                    case 'Refunded':
                                                        $progress = 100;
                                                        break;
                                                    case 'Unpaid':
                                                        $progress = 0;
                                                        break;
                                                    default:
                                                        $progress = 0;
                                                }
                                            ?>
                                            <div class="progress">
                                                <div 
                                                    class="progress-bar" 
                                                    role="progressbar" 
                                                    style="width: <?php echo $progress; ?>%;" 
                                                    aria-valuenow="<?php echo $progress; ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100">
                                                    <?php echo $progress; ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="Actions">
                                            <?php
                                            // Check if there are uploaded documents for this query
                                            $query_id = $query['id'];
                                            $type = 'clinical'; // Adjust based on your logic

                                            // Fetch report data to check for uploaded documents
                                            if ($type == 'clinical') {
                                                $sql_reports = "SELECT pre_reports FROM reports WHERE clinical_id = ?";
                                            } else {
                                                $sql_reports = "SELECT pre_reports FROM reports WHERE medico_id = ?";
                                            }
                                            $stmt_reports = $conn->prepare($sql_reports);
                                            $stmt_reports->bind_param("i", $query_id);
                                            $stmt_reports->execute();
                                            $result_reports = $stmt_reports->get_result();
                                            $stmt_reports->close();

                                            if ($result_reports->num_rows > 0) {
                                                // There are uploaded documents; display the eye icon
                                                ?>
                                                <a href="review_query.php?query_id=<?php echo urlencode($query_id); ?>&type=clinical" class="btn btn-sm btn-primary action-btn" title="View Documents">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <?php
                                            } else {
                                                echo '<span class="text-muted">N/A</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">You have not submitted any Clinical queries yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls for Clinical Queries -->
                <?php if ($total_pages_clinical > 1): ?>
                    <nav aria-label="Clinical Queries Pagination">
                        <ul class="pagination">
                            <!-- Previous Page Link -->
                            <li class="page-item <?php if ($current_page_clinical <= 1) echo 'disabled'; ?>">
                                <a class="page-link" href="?page_medico=<?php echo $current_page_medico; ?>&page_clinical=<?php echo max($current_page_clinical - 1, 1); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <!-- Page Number Links -->
                            <?php
                                // Determine the range of pages to show
                                $max_links = 5; // Maximum number of page links to show
                                $start = max(1, $current_page_clinical - floor($max_links / 2));
                                $end = min($start + $max_links - 1, $total_pages_clinical);
                                
                                // Adjust start if we're near the end
                                if ($end - $start + 1 < $max_links) {
                                    $start = max(1, $end - $max_links + 1);
                                }

                                for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?php if ($i == $current_page_clinical) echo 'active'; ?>">
                                        <a class="page-link" href="?page_medico=<?php echo $current_page_medico; ?>&page_clinical=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            <!-- Next Page Link -->
                            <li class="page-item <?php if ($current_page_clinical >= $total_pages_clinical) echo 'disabled'; ?>">
                                <a class="page-link" href="?page_medico=<?php echo $current_page_medico; ?>&page_clinical=<?php echo min($current_page_clinical + 1, $total_pages_clinical); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

                <!-- Submit Button for Clinical Queries -->
                <div class="submit-button">
                    <a href="clinical.php" class="btn btn-secondary"><i class="fas fa-plus-circle"></i> Submit Clinical Query</a>
                </div>
            </div> <!-- End of Clinical Queries Section -->
        </div> <!-- End of Dashboard Grid -->
    </div> <!-- End of #user-dashboard -->

    <!-- Include Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/footer.php'; ?>

    <!-- Toggle Navigation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.querySelector('.toggle-button');
            const navLinks = document.querySelector('.nav-links');

            toggleButton.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        });
    </script>
</body>
</html>        
