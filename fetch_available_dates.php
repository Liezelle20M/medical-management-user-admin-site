<?php
include 'includes/db_connection.php';

$sql_dates = "SELECT DISTINCT date FROM available_slots WHERE is_available = 1 ORDER BY date ASC";
$result_dates = $conn->query($sql_dates);

$available_dates = [];
while ($date_row = $result_dates->fetch_assoc()) {
    $available_dates[] = $date_row['date'];
}

// Return dates in JSON format
echo json_encode($available_dates);
?>
