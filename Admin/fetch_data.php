<?php 
include 'includes/db_connection.php'; 

// Fetch total number of users
$userQuery = "SELECT COUNT(*) as total_users FROM users";
$usersResult = $conn->query($userQuery);
$totalUsers = $usersResult->fetch_assoc()['total_users'];

// Fetch total invoice price
$invoiceQuery = "SELECT SUM(total_price) as total_invoice FROM invoices";
$invoicesResult = $conn->query($invoiceQuery);
$totalInvoicePrice = $invoicesResult->fetch_assoc()['total_invoice'];

// Fetch invoices by query type
$queryTypeQuery = "SELECT query_name, SUM(total_price) as total_price FROM invoices GROUP BY query_name";
$queryTypesResult = $conn->query($queryTypeQuery);
$queryTypes = [];
$queryPrices = [];

while ($row = $queryTypesResult->fetch_assoc()) {
    $queryTypes[] = $row['query_name'];
    $queryPrices[] = $row['total_price'];
}

// Fetch total fees for bookings (medicolegal)
$medicolegalQuery = "SELECT SUM(fee) as total_fees FROM medicolegal";
$medicolegalResult = $conn->query($medicolegalQuery);
$totalFees = $medicolegalResult->fetch_assoc()['total_fees'];

// Prepare response
$response = [
    'totalUsers' => $totalUsers,
    'totalInvoicePrice' => $totalInvoicePrice,
    'queryTypes' => $queryTypes,
    'queryPrices' => $queryPrices,
    'totalFees' => $totalFees
];

// Close database connection
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
