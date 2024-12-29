<?php
session_start();

// Check if the admin is logged in by verifying the session variable
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Admin is not logged in, redirect to the admin login page
    header('Location: admin_login.php');
    exit();
}

include 'session_check.php'; // Ensures the user is logged in
require 'dompdf/autoload.inc.php'; // Include DOMPDF
use Dompdf\Dompdf;

include 'includes/db_connection.php';

// Handle AJAX requests for insert, update, delete operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['table'])) {
    $action = $_POST['action'];
    $table = $_POST['table'];

    $response = ["status" => "error", "message" => "Invalid request"];

    switch ($table) {
        case 'query_type':
            if ($action === 'insert') {
                $query_type = $conn->real_escape_string($_POST['query_type']);
                $price = floatval($_POST['price']);
                $sql = "INSERT INTO m_query (query_type, price) VALUES ('$query_type', '$price')";
                if ($conn->query($sql)) {
                    $response = ["status" => "success", "message" => "Query Type added successfully", "id" => $conn->insert_id];
                } else {
                    $response = ["status" => "error", "message" => "Error adding Query Type: " . $conn->error];
                }
            } elseif ($action === 'update') {
                $id = intval($_POST['id']);
                $query_type = $conn->real_escape_string($_POST['query_type']);
                $price = floatval($_POST['price']);
                $sql = "UPDATE m_query SET query_type = '$query_type', price = '$price' WHERE id = $id";
                if ($conn->query($sql)) {
                    $response = ["status" => "success", "message" => "Query Type updated successfully"];
                } else {
                    $response = ["status" => "error", "message" => "Error updating Query Type: " . $conn->error];
                }
            } elseif ($action === 'delete') {
                $id = intval($_POST['id']);
                $sql = "DELETE FROM m_query WHERE id = $id";
                if ($conn->query($sql)) {
                    $response = ["status" => "success", "message" => "Query Type deleted successfully"];
                } else {
                    $response = ["status" => "error", "message" => "Error deleting Query Type: " . $conn->error];
                }
            }
            break;

        case 'required_service':
            if ($action === 'insert') {
                $required_service = $conn->real_escape_string($_POST['required_service']);
                $sql = "INSERT INTO required_services (required_service) VALUES ('$required_service')";
                if ($conn->query($sql)) {
                    $response = ["status" => "success", "message" => "Required Service added successfully", "id" => $conn->insert_id];
                } else {
                    $response = ["status" => "error", "message" => "Error adding Required Service: " . $conn->error];
                }
            } elseif ($action === 'update') {
                $id = intval($_POST['id']);
                $required_service = $conn->real_escape_string($_POST['required_service']);
                $sql = "UPDATE required_services SET required_service = '$required_service' WHERE id = $id";
                if ($conn->query($sql)) {
                    $response = ["status" => "success", "message" => "Required Service updated successfully"];
                } else {
                    $response = ["status" => "error", "message" => "Error updating Required Service: " . $conn->error];
                }
            } elseif ($action === 'delete') {
                $id = intval($_POST['id']);
                $sql = "DELETE FROM required_services WHERE id = $id";
                if ($conn->query($sql)) {
                    $response = ["status" => "success", "message" => "Required Service deleted successfully"];
                } else {
                    $response = ["status" => "error", "message" => "Error deleting Required Service: " . $conn->error];
                }
            }
            break;

        case 'additional_fee':
            if ($action === 'insert') {
                $fee_name = $conn->real_escape_string($_POST['fee_name']);
                $additional_fee = floatval($_POST['additional_fee']);
                $sql = "INSERT INTO additional_fee (fee_name, additional_fee) VALUES ('$fee_name', '$additional_fee')";
                if ($conn->query($sql)) {
                    $response = ["status" => "success", "message" => "Additional Fee added successfully", "id" => $conn->insert_id];
                } else {
                    $response = ["status" => "error", "message" => "Error adding Additional Fee: " . $conn->error];
                }
            } elseif ($action === 'update') {
                $id = intval($_POST['id']);
                $fee_name = $conn->real_escape_string($_POST['fee_name']);
                $additional_fee = floatval($_POST['additional_fee']);
                $sql = "UPDATE additional_fee SET fee_name = '$fee_name', additional_fee = '$additional_fee' WHERE id = $id";
                if ($conn->query($sql)) {
                    $response = ["status" => "success", "message" => "Additional Fee updated successfully"];
                } else {
                    $response = ["status" => "error", "message" => "Error updating Additional Fee: " . $conn->error];
                }
            } elseif ($action === 'delete') {
                $id = intval($_POST['id']);
                $sql = "DELETE FROM additional_fee WHERE id = $id";
                if ($conn->query($sql)) {
                    $response = ["status" => "success", "message" => "Additional Fee deleted successfully"];
                } else {
                    $response = ["status" => "error", "message" => "Error deleting Additional Fee: " . $conn->error];
                }
            }
            break;

  case 'available_slots':
            if ($action === 'insert') {
                $date = $conn->real_escape_string($_POST['date']);
                $time_slot1 = $conn->real_escape_string($_POST['time_slot1']);
                $time_slot2 = !empty($_POST['time_slot2']) ? "'" . $conn->real_escape_string($_POST['time_slot2']) . "'" : "NULL";
                $time_slot3 = !empty($_POST['time_slot3']) ? "'" . $conn->real_escape_string($_POST['time_slot3']) . "'" : "NULL";
                $is_available = isset($_POST['is_available']) ? 1 : 0;

                $sql = "INSERT INTO available_slots (date, time_slot1, time_slot2, time_slot3, is_available) 
                        VALUES ('$date', '$time_slot1', $time_slot2, $time_slot3, $is_available)";

                if ($conn->query($sql)) {
                    $response = ["status" => "success", "message" => "Slot added successfully", "id" => $conn->insert_id];
                } else {
                    $response = ["status" => "error", "message" => "Error adding Slot: " . $conn->error];
                }
            } elseif ($action === 'update') {
                $slot_id = intval($_POST['slot_id']);
                $date = $conn->real_escape_string($_POST['date']);
                $time_slot1 = $conn->real_escape_string($_POST['time_slot1']);
                $time_slot2 = !empty($_POST['time_slot2']) ? "'" . $conn->real_escape_string($_POST['time_slot2']) . "'" : "NULL";
                $time_slot3 = !empty($_POST['time_slot3']) ? "'" . $conn->real_escape_string($_POST['time_slot3']) . "'" : "NULL";
                $is_available = isset($_POST['is_available']) ? 1 : 0;

                $sql = "UPDATE available_slots 
                        SET date = '$date', time_slot1 = '$time_slot1', time_slot2 = $time_slot2, time_slot3 = $time_slot3, is_available = $is_available 
                        WHERE slot_id = $slot_id";

                if ($conn->query($sql)) {
                    $response = ["status" => "success", "message" => "Slot updated successfully"];
                } else {
                    $response = ["status" => "error", "message" => "Error updating Slot: " . $conn->error];
                }
            } elseif ($action === 'delete') {
                $slot_id = intval($_POST['slot_id']);

                $sql = "DELETE FROM available_slots WHERE slot_id = $slot_id";

                if ($conn->query($sql)) {
                    $response = ["status" => "success", "message" => "Slot deleted successfully"];
                } else {
                    $response = ["status" => "error", "message" => "Error deleting Slot: " . $conn->error];
                }
            }
            break;

        default:
            $response = ["status" => "error", "message" => "Unknown table"];
            break;
    }

    echo json_encode($response);
    exit;
}

// Fetch existing data from each table
$query_types = $conn->query("SELECT * FROM m_query");
$required_services = $conn->query("SELECT * FROM required_services");
$additional_fees = $conn->query("SELECT * FROM additional_fee");
$available_slots = $conn->query("SELECT * FROM available_slots");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Medicolegal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
   /* Global Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html, body {
    font-family: 'Arial', sans-serif;
    background-color: white;
    width: 100%;
    overflow-x: hidden; /* Prevent horizontal scroll */
}
/* Header Styling */
header {
    height: 80px; /* Fixed height for consistency */
    width: 100%;
    background-color: #003f7d;
    color: white;
    display: flex;
    align-items: center; /* Center content vertically */
    padding: 0 20px; /* Consistent padding */
    box-sizing: border-box;
}
/* Header Styling */
header {
    width: 100%;
    background-color: #003f7d;
    color: white;
}

/* Hero Section Styling */
.hero-section {
    width: 100%;
    height: 60vh;
    position: relative;
    overflow: hidden;
}

.hero-image {
    width: 100%;
    height: 100%;
    position: relative;
}

.hero-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    text-align: center;
    font-size: 2.5rem;
    font-weight: bold;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);
}

@media (max-width: 768px) {
    .hero-text {
        font-size: 2rem;
    }
}

@media (max-width: 480px) {
    .hero-text {
        font-size: 1.5rem;
    }
}

/* Content Sections */
.query-form, .query-list, .available-slots-section {
    padding: 20px;
    max-width: 100%; /* Use full width */
    background-color: white;
    border-radius: 10px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
}

/* Remove additional margins and center forms */
.query-form form, .available-slots-section form {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 10px 20px;
    align-items: center;
}

.query-form label, .available-slots-section label {
    color: black;
}

.query-form input, .query-form select, .available-slots-section input[type="date"], .available-slots-section input[type="time"] {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    width: 100%;
    background-color: #F5F5F5;
    font-size: 14px;
}

.query-form button, .available-slots-section button {
    grid-column: 1 / -1;
    background-color: black;
    color: white;
    padding: 10px 70px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

/* Query List */
.query-list {
    margin: 0 auto;
    width: 100%;
}

.query-list table {
    width: 100%;
    border-collapse: collapse;
}

.query-list td, .query-list th {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    font-size: 14px;
}

.query-list th {
    background-color: white;
    color: black;
    border-bottom: 5px solid #002a5b;
    font-weight: bold;
}

/* Admin Table Styling */
.admin-table, .available-slots-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 0;
    font-size: 14px;
}

.admin-table th, .admin-table td, .available-slots-table th, .available-slots-table td {
    padding: 10px;
    border: 1px solid #ccc;
    text-align: left;
}

.admin-table th, .available-slots-table th {
    background-color: #f2f2f2;
    color: #002d62;
    font-weight: bold;
    border-bottom: 5px solid #002a5b;
}

/* Responsive Adjustments */
@media (max-width: 1100px) {
    .query-form form, .available-slots-section form {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .query-form label, .query-form input, .query-form button, .query-list th, .query-list td {
        font-size: 13px;
    }
}

    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <?php include 'includes/admin_header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-image">
            <img src="images/med.png" alt="Manage Medicolegal">
            <div class="hero-text">
                <h1>MANAGE MEDICOLEGAL</h1>
            </div>
        </div>
    </section>

    <!-- Query Types Management -->
    <section class="query-form">
        <h2>Add New Query</h2>
        <form id="add-query-type-form">
            <label for="query_type">Query Type:</label>
            <input type="text" name="query_type" id="query_type" required>
            <label for="price">Price (R):</label>
            <input type="number" step="0.01" name="price" id="price" required>
            <button type="submit">Add Query Type</button>
        </form>
    </section>

    <section class="query-list">
        <h1>Manage Query Types and Prices</h1>
        <table class="admin-table" id="query-types-table">
            <thead>
                <tr>
                    <th>Query ID</th>
                    <th>Query Type</th>
                    <th>Price (R)</th>
                    <th>Edit</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $query_types->fetch_assoc()): ?>
                    <tr data-id="<?php echo $row['id']; ?>">
                        <td><?php echo $row['id']; ?></td>
                        <td class="query_type"><?php echo htmlspecialchars($row['query_type']); ?></td>
                        <td class="price"><?php echo number_format($row['price'], 2); ?></td>
                        <td>
                            <button type="button" class="edit-btn" data-action="edit"><i class="fas fa-edit"></i></button>
                        </td>
                        <td>
                            <button type="button" class="delete-btn" data-action="delete"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>

    <!-- Required Services Management -->
    <section class="query-form">
        <h2>Manage Required Services</h2>
        <form id="add-required-service-form">
            <label for="required_service">Required Service:</label>
            <input type="text" name="required_service" id="required_service" required>
            <button type="submit">Add Required Service</button>
        </form>
    </section>

    <section class="query-list">
        <table class="admin-table" id="required-services-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Required Service</th>
                    <th>Edit</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $required_services->fetch_assoc()): ?>
                    <tr data-id="<?php echo $row['id']; ?>">
                        <td><?php echo $row['id']; ?></td>
                        <td class="required_service"><?php echo htmlspecialchars($row['required_service']); ?></td>
                        <td>
                            <button type="button" class="edit-btn" data-action="edit"><i class="fas fa-edit"></i></button>
                        </td>
                        <td>
                            <button type="button" class="delete-btn" data-action="delete"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>

    <!-- Additional Fees Management -->
    <section class="query-form">
        <h2>Manage Additional Fees</h2>
        <form id="add-additional-fee-form">
            <label for="fee_name">Fee Name:</label>
            <input type="text" name="fee_name" id="fee_name" required>
            <label for="additional_fee">Additional Fee (R):</label>
            <input type="number" step="0.01" name="additional_fee" id="additional_fee" required>
            <button type="submit">Add Additional Fee</button>
        </form>
    </section>

    <section class="query-list">
        <table class="admin-table" id="additional-fees-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fee Name</th>
                    <th>Additional Fee (R)</th>
                    <th>Edit</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $additional_fees->fetch_assoc()): ?>
                    <tr data-id="<?php echo $row['id']; ?>">
                        <td><?php echo $row['id']; ?></td>
                        <td class="fee_name"><?php echo htmlspecialchars($row['fee_name']); ?></td>
                        <td class="additional_fee"><?php echo number_format($row['additional_fee'], 2); ?></td>
                        <td>
                            <button type="button" class="edit-btn" data-action="edit"><i class="fas fa-edit"></i></button>
                        </td>
                        <td>
                            <button type="button" class="delete-btn" data-action="delete"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>



  <section class="available-slots-section">
    <h2>Manage Available Slots</h2>
    <form id="add-available-slot-form">
        <label for="date">Date:</label>
        <input type="date" name="date" id="date" min="<?php echo date('Y-m-d'); ?>" required>
        
        <label for="time_slot1">Time Slot 1:</label>
        <input type="time" name="time_slot1" id="time_slot1" required>
        
        <label for="time_slot2">Time Slot 2:</label>
        <input type="time" name="time_slot2" id="time_slot2">
        
        <label for="time_slot3">Time Slot 3:</label>
        <input type="time" name="time_slot3" id="time_slot3">
        
        <label for="is_available">Is Available:</label>
        <input type="checkbox" name="is_available" id="is_available" value="1" checked>
        
        <button type="submit">Add Slot</button>
    </form>
</section>

<!-- Available Slots Table -->
<section class="query-list">
    <h1>Available Slots</h1>
    <table class="available-slots-table" id="available-slots-table">
        <thead>
            <tr>
                <th>Slot ID</th>
                <th>Date</th>
                <th>Time Slot 1</th>
                <th>Time Slot 2</th>
                <th>Time Slot 3</th>
                <th>Is Available</th>
                <th>Edit</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($slot = $available_slots->fetch_assoc()): ?>
                <tr data-id="<?php echo $slot['slot_id']; ?>">
                    <td><?php echo $slot['slot_id']; ?></td>
                    <td class="date"><?php echo htmlspecialchars($slot['date']); ?></td>
                    <td class="time_slot1"><?php echo htmlspecialchars($slot['time_slot1']); ?></td>
                    <td class="time_slot2"><?php echo htmlspecialchars($slot['time_slot2']); ?></td>
                    <td class="time_slot3"><?php echo htmlspecialchars($slot['time_slot3']); ?></td>
                    <td class="is_available"><?php echo $slot['is_available'] ? 'Yes' : 'No'; ?></td>
                    <td>
                        <button type="button" class="edit-btn" data-action="edit"><i class="fas fa-edit"></i></button>
                    </td>
                    <td>
                        <button type="button" class="delete-btn" data-action="delete"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</section>

    <?php include 'includes/admin_footer.php'; ?>
<style>
  .message {
    display: inline-block;
    margin-left: 10px;
    font-weight: bold;
  }
  .message.success {
    color: green;
  }
  .message.error {
    color: red;
  }
</style>

<script>
    $(document).ready(function () {
        // Initialize form handling for other tables only
        handleFormSubmission('#add-query-type-form', '#query-types-table', 'query_type');
        handleFormSubmission('#add-required-service-form', '#required-services-table', 'required_service');
        handleFormSubmission('#add-additional-fee-form', '#additional-fees-table', 'additional_fee');

        // Function to handle form submissions for other tables
        function handleFormSubmission(formId, tableId, tableType) {
            $(formId).on('submit', function (e) {
                e.preventDefault();
                const form = $(this);
                const submitButton = form.find('button[type="submit"]');
                submitButton.prop('disabled', true);

                // Remove any existing messages
                form.find('.message').remove();

                const formData = form.serializeArray();
                const data = {};
                formData.forEach(item => {
                    data[item.name] = item.value;
                });
                data.action = 'insert';
                data.table = tableType;

                $.ajax({
                    url: '', // Current page
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            const successMsg = $('<span class="message success">✔ ' + response.message + '</span>');
                            form.append(successMsg);
                            successMsg.fadeIn().delay(3000).fadeOut(function () {
                                $(this).remove();
                            });

                            // Create new row based on table type
                            let newRow = '';
                            if (tableType === 'query_type') {
                                newRow = `<tr data-id="${response.id}">
                                    <td>${response.id}</td>
                                    <td class="query_type">${data.query_type}</td>
                                    <td class="price">${parseFloat(data.price).toFixed(2)}</td>
                                    <td>
                                        <button type="button" class="edit-btn" data-action="edit"><i class="fas fa-edit"></i></button>
                                    </td>
                                    <td>
                                        <button type="button" class="delete-btn" data-action="delete"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>`;
                            } else if (tableType === 'required_service') {
                                newRow = `<tr data-id="${response.id}">
                                    <td>${response.id}</td>
                                    <td class="required_service">${data.required_service}</td>
                                    <td>
                                        <button type="button" class="edit-btn" data-action="edit"><i class="fas fa-edit"></i></button>
                                    </td>
                                    <td>
                                        <button type="button" class="delete-btn" data-action="delete"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>`;
                            } else if (tableType === 'additional_fee') {
                                newRow = `<tr data-id="${response.id}">
                                    <td>${response.id}</td>
                                    <td class="fee_name">${data.fee_name}</td>
                                    <td class="additional_fee">${parseFloat(data.additional_fee).toFixed(2)}</td>
                                    <td>
                                        <button type="button" class="edit-btn" data-action="edit"><i class="fas fa-edit"></i></button>
                                    </td>
                                    <td>
                                        <button type="button" class="delete-btn" data-action="delete"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>`;
                            }

                            $(tableId + ' tbody').append(newRow);
                            form[0].reset();
                        } else {
                            const errorMsg = $('<span class="message error">✖ ' + response.message + '</span>');
                            form.append(errorMsg);
                            errorMsg.fadeIn().delay(3000).fadeOut(function () {
                                $(this).remove();
                            });
                        }
                        submitButton.prop('disabled', false);
                    },
                    error: function () {
                        const errorMsg = $('<span class="message error">✖ An error occurred. Please try again.</span>');
                        form.append(errorMsg);
                        errorMsg.fadeIn().delay(3000).fadeOut(function () {
                            $(this).remove();
                        });
                        submitButton.prop('disabled', false);
                    }
                });
            });
        }

        // Function to handle edit and delete buttons for other tables
        function handleTableActions(tableId, tableType) {
            $(tableId).on('click', '.edit-btn', function () {
                const btn = $(this);
                const row = btn.closest('tr');
                const id = row.data('id');

                if (btn.data('action') === 'edit') {
                    btn.data('action', 'save');
                    btn.html('<i class="fas fa-save"></i>');

                    // Make cells editable based on table type
                    if (tableType === 'query_type') {
                        row.find('.query_type').html(`<input type="text" value="${row.find('.query_type').text()}" />`);
                        row.find('.price').html(`<input type="number" step="0.01" value="${row.find('.price').text()}" />`);
                    } else if (tableType === 'required_service') {
                        row.find('.required_service').html(`<input type="text" value="${row.find('.required_service').text()}" />`);
                    } else if (tableType === 'additional_fee') {
                        row.find('.fee_name').html(`<input type="text" value="${row.find('.fee_name').text()}" />`);
                        row.find('.additional_fee').html(`<input type="number" step="0.01" value="${row.find('.additional_fee').text()}" />`);
                    }
                } else if (btn.data('action') === 'save') {
                    // Gather updated data based on table type
                    let updatedData = {};
                    if (tableType === 'query_type') {
                        updatedData.query_type = row.find('.query_type input').val();
                        updatedData.price = row.find('.price input').val();
                    } else if (tableType === 'required_service') {
                        updatedData.required_service = row.find('.required_service input').val();
                    } else if (tableType === 'additional_fee') {
                        updatedData.fee_name = row.find('.fee_name input').val();
                        updatedData.additional_fee = row.find('.additional_fee input').val();
                    }
                    updatedData.action = 'update';
                    updatedData.table = tableType;
                    updatedData.id = id;

                    btn.prop('disabled', true);

                    // Send AJAX request to update
                    $.ajax({
                        url: '', // Current page URL
                        type: 'POST',
                        data: updatedData,
                        dataType: 'json',
                        success: function (response) {
                            if (response.status === 'success') {
                                // Show success message
                                const successMsg = $('<span class="message success">✔ ' + response.message + '</span>');
                                row.find('td').first().append(successMsg);
                                successMsg.fadeIn().delay(3000).fadeOut(function () {
                                    $(this).remove();
                                });

                                // Revert back to view mode
                                btn.data('action', 'edit');
                                btn.html('<i class="fas fa-edit"></i>');

                                // Update table cells with new values
                                if (tableType === 'query_type') {
                                    row.find('.query_type').text(updatedData.query_type);
                                    row.find('.price').text(parseFloat(updatedData.price).toFixed(2));
                                } else if (tableType === 'required_service') {
                                    row.find('.required_service').text(updatedData.required_service);
                                } else if (tableType === 'additional_fee') {
                                    row.find('.fee_name').text(updatedData.fee_name);
                                    row.find('.additional_fee').text(parseFloat(updatedData.additional_fee).toFixed(2));
                                }
                            } else {
                                // Show error message
                                const errorMsg = $('<span class="message error">✖ ' + response.message + '</span>');
                                row.find('td').first().append(errorMsg);
                                errorMsg.fadeIn().delay(3000).fadeOut(function () {
                                    $(this).remove();
                                });
                            }
                            btn.prop('disabled', false);
                        },
                        error: function () {
                            // Show generic error message
                            const errorMsg = $('<span class="message error">✖ An error occurred while updating.</span>');
                            row.find('td').first().append(errorMsg);
                            errorMsg.fadeIn().delay(3000).fadeOut(function () {
                                $(this).remove();
                            });
                            btn.prop('disabled', false);
                        }
                    });
                }
            });

            // Handle delete button click for other tables
            $(tableId).on('click', '.delete-btn', function () {
                if (!confirm('Are you sure you want to delete this record?')) {
                    return;
                }

                const btn = $(this);
                const row = btn.closest('tr');
                const id = row.data('id');

                let data = {
                    action: 'delete',
                    table: tableType,
                    id: id
                };

                $.ajax({
                    url: '', // Current page URL
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            // Show success message
                            const successMsg = $('<span class="message success">✔ ' + response.message + '</span>');
                            row.find('td').first().append(successMsg);
                            successMsg.fadeIn().delay(3000).fadeOut(function () {
                                $(this).remove();
                                row.remove();
                            });
                        } else {
                            // Show error message
                            const errorMsg = $('<span class="message error">✖ ' + response.message + '</span>');
                            row.find('td').first().append(errorMsg);
                            errorMsg.fadeIn().delay(3000).fadeOut(function () {
                                $(this).remove();
                            });
                        }
                    },
                    error: function () {
                        // Show generic error message
                        const errorMsg = $('<span class="message error">✖ An error occurred while deleting.</span>');
                        row.find('td').first().append(errorMsg);
                        errorMsg.fadeIn().delay(3000).fadeOut(function () {
                            $(this).remove();
                        });
                    }
                });
            });
        }

        // Initialize table actions for other tables only
        handleTableActions('#query-types-table', 'query_type');
        handleTableActions('#required-services-table', 'required_service');
        handleTableActions('#additional-fees-table', 'additional_fee');
    });
</script>

<script>
    $(document).ready(function () {
        // Handle insert for available_slots
        $('#add-available-slot-form').on('submit', function (e) {
            e.preventDefault();
            const form = $(this);
            const submitButton = form.find('button[type="submit"]');
            submitButton.prop('disabled', true);

            // Remove any existing messages
            form.find('.message').remove();

            // Serialize the form data
            const formData = form.serializeArray();
            const data = {};
            formData.forEach(item => {
                data[item.name] = item.value;
            });
            data.action = 'insert';
            data.table = 'available_slots';

            $.ajax({
                url: '', // Current page URL
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        // Show success message
                        const successMsg = $('<span class="message success">✔ ' + response.message + '</span>');
                        form.append(successMsg);
                        successMsg.fadeIn().delay(3000).fadeOut(function () {
                            $(this).remove();
                        });

                        // Add new row to available_slots table
                        const isAvailable = data.is_available ? 'Yes' : 'No';
                        const newRow = `<tr data-id="${response.id}">
                            <td>${response.id}</td>
                            <td class="date">${data.date}</td>
                            <td class="time_slot1">${data.time_slot1}</td>
                            <td class="time_slot2">${data.time_slot2 || ''}</td>
                            <td class="time_slot3">${data.time_slot3 || ''}</td>
                            <td class="is_available">${isAvailable}</td>
                            <td>
                                <button type="button" class="edit-btn" data-action="edit"><i class="fas fa-edit"></i></button>
                            </td>
                            <td>
                                <button type="button" class="delete-btn" data-action="delete"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>`;
                        $('#available-slots-table tbody').append(newRow);
                        form[0].reset();
                    } else {
                        // Show error message
                        const errorMsg = $('<span class="message error">✖ ' + response.message + '</span>');
                        form.append(errorMsg);
                        errorMsg.fadeIn().delay(3000).fadeOut(function () {
                            $(this).remove();
                        });
                    }
                    submitButton.prop('disabled', false);
                },
                error: function () {
                    // Show generic error message
                    const errorMsg = $('<span class="message error">✖ An error occurred. Please try again.</span>');
                    form.append(errorMsg);
                    errorMsg.fadeIn().delay(3000).fadeOut(function () {
                        $(this).remove();
                    });
                    submitButton.prop('disabled', false);
                }
            });
        });

        // Handle edit for available_slots
        $('#available-slots-table').on('click', '.edit-btn', function () {
            const btn = $(this);
            const row = btn.closest('tr');
            const id = row.data('id');

            if (btn.data('action') === 'edit') {
                btn.data('action', 'save');
                btn.html('<i class="fas fa-save"></i>');

                // Make cells editable
                row.find('.date').html(`<input type="date" value="${row.find('.date').text()}" min="<?php echo date('Y-m-d'); ?>" />`);
                row.find('.time_slot1').html(`<input type="time" value="${row.find('.time_slot1').text()}" />`);
                row.find('.time_slot2').html(`<input type="time" value="${row.find('.time_slot2').text()}" />`);
                row.find('.time_slot3').html(`<input type="time" value="${row.find('.time_slot3').text()}" />`);
                row.find('.is_available').html(`<input type="checkbox" ${row.find('.is_available').text() === 'Yes' ? 'checked' : ''} />`);
            } else if (btn.data('action') === 'save') {
                // Gather updated data
                const updatedData = {
                    date: row.find('.date input').val(),
                    time_slot1: row.find('.time_slot1 input').val(),
                    time_slot2: row.find('.time_slot2 input').val(),
                    time_slot3: row.find('.time_slot3 input').val(),
                    is_available: row.find('.is_available input').is(':checked') ? 1 : 0,
                    action: 'update',
                    table: 'available_slots',
                    slot_id: id
                };

                btn.prop('disabled', true);

                // Send AJAX request to update
                $.ajax({
                    url: '', // Current page URL
                    type: 'POST',
                    data: updatedData,
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            // Show success message
                            const successMsg = $('<span class="message success">✔ ' + response.message + '</span>');
                            row.find('td').first().append(successMsg);
                            successMsg.fadeIn().delay(3000).fadeOut(function () {
                                $(this).remove();
                            });

                            // Revert back to view mode
                            btn.data('action', 'edit');
                            btn.html('<i class="fas fa-edit"></i>');

                            row.find('.date').text(updatedData.date);
                            row.find('.time_slot1').text(updatedData.time_slot1);
                            row.find('.time_slot2').text(updatedData.time_slot2);
                            row.find('.time_slot3').text(updatedData.time_slot3);
                            row.find('.is_available').text(updatedData.is_available ? 'Yes' : 'No');
                        } else {
                            // Show error message
                            const errorMsg = $('<span class="message error">✖ ' + response.message + '</span>');
                            row.find('td').first().append(errorMsg);
                            errorMsg.fadeIn().delay(3000).fadeOut(function () {
                                $(this).remove();
                            });
                        }
                        btn.prop('disabled', false);
                    },
                    error: function () {
                        // Show generic error message
                        const errorMsg = $('<span class="message error">✖ An error occurred while updating.</span>');
                        row.find('td').first().append(errorMsg);
                        errorMsg.fadeIn().delay(3000).fadeOut(function () {
                            $(this).remove();
                        });
                        btn.prop('disabled', false);
                    }
                });
            }
        });

        // Handle delete for available_slots
        $('#available-slots-table').on('click', '.delete-btn', function () {
            if (!confirm('Are you sure you want to delete this slot?')) return;

            const btn = $(this);
            const row = btn.closest('tr');
            const id = row.data('id');

            $.ajax({
                url: '', // Current page URL
                type: 'POST',
                data: { action: 'delete', table: 'available_slots', slot_id: id },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        // Show success message
                        const successMsg = $('<span class="message success">✔ ' + response.message + '</span>');
                        row.find('td').first().append(successMsg);
                        successMsg.fadeIn().delay(3000).fadeOut(function () {
                            $(this).remove();
                            row.remove();
                        });
                    } else {
                        // Show error message
                        const errorMsg = $('<span class="message error">✖ ' + response.message + '</span>');
                        row.find('td').first().append(errorMsg);
                        errorMsg.fadeIn().delay(3000).fadeOut(function () {
                            $(this).remove();
                        });
                    }
                },
                error: function () {
                    // Show generic error message
                    const errorMsg = $('<span class="message error">✖ An error occurred while deleting.</span>');
                    row.find('td').first().append(errorMsg);
                    errorMsg.fadeIn().delay(3000).fadeOut(function () {
                        $(this).remove();
                    });
                }
            });
        });
    });
</script>


</body>

</html>
