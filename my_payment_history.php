<?php
session_start();
include 'includes/db_connection.php';
require 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: sign.php");
    exit;
}

// Get the user email from the users table using user_id
$user_id = $_SESSION['user_id'];

// Fetch the email corresponding to the user_id
$query_email = "SELECT email FROM users WHERE user_id = ?";
$stmt_email = $conn->prepare($query_email);
$stmt_email->bind_param("i", $user_id);
$stmt_email->execute();
$result_email = $stmt_email->get_result();

if ($result_email->num_rows === 0) {
    echo "User email not found.";
    exit;
}

$user_email_row = $result_email->fetch_assoc();
$user_email = $user_email_row['email'];

// Fetch payment data from all three tables using the retrieved user_email
$query = "
    SELECT rc.id AS record_id, rc.created_at, rc.training_theme AS description, rc.total_price 
    FROM receipt_courses AS rc
    WHERE rc.user_email = ?
    
    UNION ALL
    
    SELECT rm.receipt_id AS record_id, rm.created_at, rm.query_type AS description, rm.total_price 
    FROM receipts_med AS rm
    WHERE rm.user_email = ?
    
    UNION ALL
    
    SELECT i.invoice_id AS record_id, i.created_at, i.query_name AS description, i.total_price 
    FROM invoices AS i
    WHERE i.user_email = ?
    
    ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $user_email, $user_email, $user_email);
$stmt->execute();
$result = $stmt->get_result();

// Generate payment history in HTML
$html = '<h2>Payment History Statement</h2>
<table border="1" cellpadding="10" cellspacing="0">
    <thead>
        <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Total Price</th>
            <th>Record ID</th>
        </tr>
    </thead>
    <tbody>';

while ($row = $result->fetch_assoc()) {
    $html .= '
        <tr>
            <td>' . date('Y-m-d', strtotime($row['created_at'])) . '</td>
            <td>' . htmlspecialchars($row['description']) . '</td>
            <td>' . number_format($row['total_price'], 2) . '</td>
            <td>' . htmlspecialchars($row['record_id']) . '</td>
        </tr>';
}

$html .= '</tbody></table>';

// Check if the user wants to download the PDF
if (isset($_GET['download_pdf'])) {
    // Create a PDF using Dompdf
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Output the generated PDF
    $dompdf->stream('Payment_History_Statement.pdf', array("Attachment" => true));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History</title>
    <link rel="stylesheet" href="assets/styles01.css">
	<link rel="stylesheet" href="assets/styles3.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
    <main>
        <h2>Payment History</h2>
        
        <!-- Display the payment history table -->
        <?php echo $html; ?>

        <!-- Button to download PDF -->
        <form method="GET" action="my_payment_history.php">
            <input type="hidden" name="download_pdf" value="1">
            <button type="submit">Download Payment Statement as PDF</button>
        </form>
    </main>
	<?php include 'includes/footer.php'; ?>
</body>
</html>
<?php
$conn->close();
?>
