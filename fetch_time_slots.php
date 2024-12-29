<?php
session_start();
include 'includes/db_connection.php';

if(isset($_POST['date'])) {
    $date = $conn->real_escape_string($_POST['date']);

    $sql_time_slots = "SELECT time_slot1, time_slot2, time_slot3 FROM available_slots WHERE date = '$date' AND is_available = 1 LIMIT 1";
    $result = $conn->query($sql_time_slots);

    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $time_slots = array_filter([$row['time_slot1'], $row['time_slot2'], $row['time_slot3']]);
        echo json_encode(['status' => 'success', 'time_slots' => array_values($time_slots)]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No available time slots for this date.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
