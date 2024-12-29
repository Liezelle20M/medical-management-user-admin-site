<?php
session_start();
include 'includes/db_connection.php';
require 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get receipt ID from the URL
$receipt_id = isset($_GET['receipt_id']) ? $_GET['receipt_id'] : null;

if ($receipt_id) {
    // Fetch receipt details from the database
    $query = "SELECT * FROM receipt_courses WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $receipt_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $receipt = $result->fetch_assoc();

    if ($receipt) {
        // Create the PDF
        $dompdf = new Dompdf();
        $html = '
        <h1>Receipt</h1>
        <p><strong>Billing Name:</strong> ' . $receipt['billing_first_name'] . ' ' . $receipt['billing_last_name'] . '</p>
        <p><strong>Training Theme:</strong> ' . $receipt['training_theme'] . '</p>
        <p><strong>Total Price:</strong> ' . $receipt['total_price'] . '</p>
        <p><strong>Payment Status:</strong> ' . $receipt['payment_status'] . '</p>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Output the generated PDF
        $dompdf->stream('receipt_' . $receipt_id . '.pdf', array("Attachment" => 1));
    } else {
        echo "Receipt not found.";
    }
} else {
    echo "Invalid request.";
}
?>
