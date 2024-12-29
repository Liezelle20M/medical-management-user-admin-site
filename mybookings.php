<?php
session_start();
include 'includes/db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get the user ID from session
$user_id = $_SESSION['user_id'];

// Pagination logic
$limit = 10; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch bookings from receipt_courses, invoices, and receipts_med
$query_courses = "
    SELECT rc.training_theme, rc.num_delegates, rc.total_price, rc.payment_status, rc.invoice_pdf 
    FROM receipt_courses rc
    JOIN users u ON rc.user_email = u.email
    WHERE u.user_id = ?
    ORDER BY rc.created_at DESC 
    LIMIT ?, ?";

$query_invoices = "
    SELECT i.query_name, i.total_price, i.payment_status, i.invoice_pdf 
    FROM invoices i
    JOIN users u ON i.user_email = u.email
    WHERE u.user_id = ?
    ORDER BY i.created_at DESC 
    LIMIT ?, ?";

$query_receipts = "
    SELECT r.query_type, r.required_service, r.payment_status, r.invoice_pdf 
    FROM receipts_med r
    JOIN users u ON r.user_email = u.email
    WHERE u.user_id = ?
    ORDER BY r.created_at DESC 
    LIMIT ?, ?";

// Prepare and execute statements
$stmt_courses = $conn->prepare($query_courses);
$stmt_courses->bind_param("iii", $user_id, $offset, $limit);
$stmt_courses->execute();
$result_courses = $stmt_courses->get_result();

$stmt_invoices = $conn->prepare($query_invoices);
$stmt_invoices->bind_param("iii", $user_id, $offset, $limit);
$stmt_invoices->execute();
$result_invoices = $stmt_invoices->get_result();

$stmt_receipts = $conn->prepare($query_receipts);
$stmt_receipts->bind_param("iii", $user_id, $offset, $limit);
$stmt_receipts->execute();
$result_receipts = $stmt_receipts->get_result();

// Count total records for pagination
$total_courses = $conn->query("SELECT COUNT(*) AS total FROM receipt_courses rc JOIN users u ON rc.user_email = u.email WHERE u.user_id = $user_id")->fetch_assoc()['total'];
$total_invoices = $conn->query("SELECT COUNT(*) AS total FROM invoices i JOIN users u ON i.user_email = u.email WHERE u.user_id = $user_id")->fetch_assoc()['total'];
$total_receipts = $conn->query("SELECT COUNT(*) AS total FROM receipts_med r JOIN users u ON r.user_email = u.email WHERE u.user_id = $user_id")->fetch_assoc()['total'];

$total_records = $total_courses + $total_invoices + $total_receipts;
$total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings</title>
    <link rel="stylesheet" href="assets/styles3.css">
    <link rel="stylesheet" href="assets/styles4.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f4f4f4;
        }
        main {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            flex: 1;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        h3 {
            color: #003f7d;
            margin: 20px 0 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px; /* Space between tables */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #003f7d; /* Header color */
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .pagination {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #007bff;
            border-radius: 5px;
            color: #007bff;
            text-decoration: none;
        }
        .pagination a:hover {
            background-color: #007bff;
            color: white;
        }
        .active {
            background-color: #007bff;
            color: white;
        }
        footer {
            text-align: center;
            padding: 10px 0;
            background: #333;
            color: white;
            margin-top: auto;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <h2>My Bookings</h2>

   <h3>Receipt Courses</h3>
<table>
    <thead>
        <tr>
            <th>Training Theme</th>
            <th>Number of Delegates</th>
            <th>Total Price</th>
            <th>Payment Status</th>
            <th>Invoice</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result_courses->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['training_theme']); ?></td>
                <td><?php echo htmlspecialchars($row['num_delegates']); ?></td>
                <td><?php echo htmlspecialchars($row['total_price']); ?></td>
                <td><?php echo htmlspecialchars($row['payment_status']); ?></td>
                <td><a href="download.php?file=<?php echo urlencode($row['invoice_pdf']); ?>">Download Invoice</a></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

       <h3>Invoices</h3>
<table>
    <thead>
        <tr>
            <th>Query Name</th>
            <th>Total Price</th>
            <th>Payment Status</th>
            <th>Invoice</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result_invoices->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['query_name']); ?></td>
                <td><?php echo htmlspecialchars($row['total_price']); ?></td>
                <td><?php echo htmlspecialchars($row['payment_status']); ?></td>
                <td><a href="download.php?file=<?php echo urlencode($row['invoice_pdf']); ?>">Download Invoice</a></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

        <h3>Receipts Medical</h3>
<table>
    <thead>
        <tr>
            <th>Query Type</th>
            <th>Required Service</th>
            <th>Payment Status</th>
            <th>Invoice</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result_receipts->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['query_type']); ?></td>
                <td><?php echo htmlspecialchars($row['required_service']); ?></td>
                <td><?php echo htmlspecialchars($row['payment_status']); ?></td>
                <td><a href="download.php?file=<?php echo urlencode($row['invoice_pdf']); ?>">Download Invoice</a></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="mybookings.php?page=<?php echo $page - 1; ?>">Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="mybookings.php?page=<?php echo $i; ?>" <?php if ($i == $page) echo 'class="active"'; ?>><?php echo $i; ?></a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="mybookings.php?page=<?php echo $page + 1; ?>">Next</a>
            <?php endif; ?>
        </div>
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Your Company. All rights reserved.</p>
    </footer>
</body>
</html>
<?php
$conn->close();
?>
