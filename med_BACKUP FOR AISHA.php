<?php
// Start the session
session_start(); // Ensure session is started

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page
    header("Location: sign.php");
    exit; // Stop further execution of the page
}

// Database connection
include 'includes/db_connection.php';
require 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

// Initialize an array to hold error messages
$errors = [];

// Get logged-in user's data
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result_user = $stmt->get_result();

    if ($result_user->num_rows > 0) {
        $user_data = $result_user->fetch_assoc();
    } else {
        die("User not found.");
    }
} else {
    die("User not logged in.");
}

// Fetch query types
$sql_query_types = "SELECT query_type, price FROM m_query";
$result_query_types = $conn->query($sql_query_types);

// Fetch required services
$sql_required_services = "SELECT * FROM required_services";
$result_required_services = $conn->query($sql_required_services);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $query_type = $_POST['query_type'];
    $required_service_id = $_POST['required_service'];
    $case_description = $_POST['case_description'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $meeting_type = $_POST['meeting_type'];

    // Retrieve fee for query type
    $sql_fee = "SELECT price FROM m_query WHERE query_type = ? LIMIT 1";
    $stmt_fee = $conn->prepare($sql_fee);
    $stmt_fee->bind_param('s', $query_type);
    $stmt_fee->execute();
    $result_fee = $stmt_fee->get_result();

    if ($result_fee->num_rows > 0) {
        $row_fee = $result_fee->fetch_assoc();
        $_SESSION['fee'] = $row_fee['price'];
    } else {
        $errors[] = "Price for the selected query type not found.";
    }

    // Retrieve required service name
    $sql_required_service = "SELECT required_service FROM required_services WHERE id = ? LIMIT 1";
    $stmt_required_service = $conn->prepare($sql_required_service);
    $stmt_required_service->bind_param('i', $required_service_id);
    $stmt_required_service->execute();
    $result_required_service = $stmt_required_service->get_result();

    if ($result_required_service->num_rows > 0) {
        $row_service = $result_required_service->fetch_assoc();
        $_SESSION['required_service'] = $row_service['required_service'];
    } else {
        $errors[] = "Selected required service not found.";
    }

    // Handle file upload
    if (isset($_FILES["file_upload"]) && $_FILES["file_upload"]["error"] != UPLOAD_ERR_NO_FILE) {
        $target_dir = "uploads/";
        $file_name = basename($_FILES["file_upload"]["name"]);
        $target_file = $target_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png", "pdf"];

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Invalid file format. Only JPG, JPEG, PNG, and PDF files are allowed.";
        } else {
            if (!move_uploaded_file($_FILES["file_upload"]["tmp_name"], $target_file)) {
                $errors[] = "Error uploading file.";
            }
        }
    } else {
        $errors[] = "Please upload a supporting document.";
    }

    // Proceed only if there are no errors
    if (empty($errors)) {
        // Insert into `medicolegal`
        $sql_insert_query = "INSERT INTO medicolegal (user_id, query_type, case_description, required_service_id, fee, file)
                             VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert_query);
        $stmt_insert->bind_param('isssis', $user_id, $query_type, $case_description, $required_service_id, $_SESSION['fee'], $file_name);
        $stmt_insert->execute();
        $medico_id = $stmt_insert->insert_id;

        // Retrieve slot_id for selected date and time
        $sql_slot = "SELECT slot_id FROM available_slots WHERE date = ? AND (time_slot1 = ? OR time_slot2 = ? OR time_slot3 = ?) LIMIT 1";
        $stmt_slot = $conn->prepare($sql_slot);
        $stmt_slot->bind_param('ssss', $appointment_date, $appointment_time, $appointment_time, $appointment_time);
        $stmt_slot->execute();
        $result_slot = $stmt_slot->get_result();
        if ($result_slot->num_rows > 0) {
            $slot_row = $result_slot->fetch_assoc();
            $slot_id = $slot_row['slot_id'];

            // Insert into `appointments`
            $sql_insert_appointment = "INSERT INTO appointments (slot_id, user_id, meeting_type) VALUES (?, ?, ?)";
            $stmt_appointment = $conn->prepare($sql_insert_appointment);
            $stmt_appointment->bind_param('iis', $slot_id, $user_id, $meeting_type);
            $stmt_appointment->execute();
        } else {
            $errors[] = "Selected appointment slot is not available.";
        }

        // Generate PDF with all form sections
        $dompdf = new Dompdf();
        $pdf_html = "
            <style>
                h1, h3 { color: #0055A5; font-family: 'Cormorant Garamond', serif; }
                .section-title { background-color: #0055A5; color: white; padding: 10px 20px; border-radius: 5px; }
                .section { margin-bottom: 20px; }
                p { margin: 5px 0; }
            </style>
            <h1>Medico-Legal Query Form</h1>
            
            <div class='section'>
                <h3 class='section-title'>General Details</h3>
                <p><strong>Title:</strong> {$user_data['title']}</p>
                <p><strong>First Name:</strong> {$user_data['first_name']}</p>
                <p><strong>Last Name:</strong> {$user_data['last_name']}</p>
                <p><strong>BHF Practice No:</strong> {$user_data['bhf_number']}</p>
                <p><strong>Medical Practice Type:</strong> {$user_data['practice_type']}</p>
                <p><strong>Telephone:</strong> {$user_data['telephone']}</p>
                <p><strong>Email:</strong> {$user_data['email']}</p>
            </div>

            <div class='section'>
                <h3 class='section-title'>Query Information</h3>
                <p><strong>Query Type:</strong> $query_type</p>
                <p><strong>Fee:</strong> R {$_SESSION['fee']}</p>
                <p><strong>Required Service:</strong> {$_SESSION['required_service']}</p>
                <p><strong>Case Description:</strong> $case_description</p>
            </div>

            <div class='section'>
                <h3 class='section-title'>Appointment Details</h3>
                <p><strong>Date:</strong> $appointment_date</p>
                <p><strong>Time Slot:</strong> $appointment_time</p>
                <p><strong>Meeting Type:</strong> $meeting_type</p>
            </div>
        ";

        $dompdf->loadHtml($pdf_html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdf_output = $dompdf->output();
        $pdf_file_path = "pdfs/appointment_$medico_id.pdf";
        file_put_contents($pdf_file_path, $pdf_output);

        // Update `medicolegal` with PDF path
        $sql_update_query = "UPDATE medicolegal SET query_form = ? WHERE medico_id = ?";
        $stmt_update = $conn->prepare($sql_update_query);
        $stmt_update->bind_param('si', $pdf_file_path, $medico_id);
        $stmt_update->execute();

        $_SESSION['success_message'] = "Query submitted successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta Tags and Title -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medico-Legal Queries</title>

    <!-- CSS Stylesheets -->
    <link rel="stylesheet" href="assets/styles3.css">
    <link rel="stylesheet" href="assets/styles4.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <!-- Inline Styles for Error Messages and Modal -->
    <style>
    /* Existing styles */
    /* Error Message Styles */
    .error-message {
        display: none;
        color: #ff0000; /* Red color for error messages */
        font-size: 0.875rem;
        margin-top: 0.25rem;
        padding: 0.5rem;
        background-color: #ffe6e6;
        border-left: 4px solid #ff0000;
        border-radius: 0.375rem;
    }

    .error-message.show {
        display: block;
    }

    /* Modal Styles */
    .modal {
        display: none; /* Hidden by default */
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
        backdrop-filter: blur(5px);
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }

    .modal.show {
        display: flex;
        opacity: 1;
    }

    .modal-content {
        background: #ffffff;
        border-radius: 8px;
        padding: 2rem;
        max-width: 400px;
        width: 90%;
        text-align: center;
        position: relative;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 
                    0 8px 10px -6px rgba(0, 0, 0, 0.1);
        transform: scale(0.8);
        transition: all 0.3s ease-in-out;
    }

    .modal.show .modal-content {
        transform: scale(1);
    }

    .close-modal {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: transparent;
        border: none;
        font-size: 1.5rem;
        color: #6b7280;
        cursor: pointer;
        transition: color 0.2s ease-in-out;
    }

    .close-modal:hover {
        color: #111827;
    }

    .success-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 1rem;
        background: #22c55e;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        animation: popIn 0.5s ease-in-out;
    }

    @keyframes popIn {
        0% {
            transform: scale(0.5);
            opacity: 0;
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .modal-content h3 {
        font-size: 1.5rem;
        color: #111827;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .modal-content p {
        font-size: 1rem;
        color: #4b5563;
        margin-bottom: 1.5rem;
    }

    .modal-actions {
        display: flex;
        justify-content: center;
        gap: 1rem;
    }

    .btn, .btn-secondary {
        padding: 0.75rem 1.5rem;
        border-radius: 5px;
        font-size: 0.875rem;
        text-decoration: none;
        transition: background-color 0.3s ease;
        cursor: pointer;
        border: none;
        display: inline-block;
    }

    .btn {
        background-color: #22c55e;
        color: #ffffff;
    }

    .btn:hover {
        background-color: #16a34a;
    }

    .btn-secondary {
        background-color: #f3f4f6;
        color: #4b5563;
    }

    .btn-secondary:hover {
        background-color: #e5e7eb;
    }
      
     #file-name-display {
    color: #003366;
    margin-top: 0.5rem;
    font-weight: bold;
}

      
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <section class="form-section">
            <h1>Medico-Legal Queries</h1>
            <h2>Submit a Medico-Legal Query</h2>
            <form id="medico-legal-form" method="POST" action="" enctype="multipart/form-data">
                <div class="section">
                    <h3> General Details Section</h3>

                    <!-- Query Type Field (From Database) -->
                    <div class="form-group">
                        <label for="query-type">Query Type: <span class="required">*</span></label>
                        <select name="query_type" id="query-type" required>
                            <option value="">Select query type</option>
                            <?php
                            $result_query_types->data_seek(0); // Reset pointer
                            while ($row = $result_query_types->fetch_assoc()):
                                $selected = (isset($_POST['query_type']) && $_POST['query_type'] == $row['query_type']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $row['query_type']; ?>" <?php echo $selected; ?>>
                                    <?php echo $row['query_type']; ?> (R <?php echo number_format($row['price'], 2); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Auto-populated User Information from Database -->
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" name="title" value="<?php echo isset($user_data['title']) ? $user_data['title'] : ''; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="name">First Name:</label>
                        <input type="text" name="name" value="<?php echo $user_data['first_name']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="surname">Last Name:</label>
                        <input type="text" name="surname" value="<?php echo $user_data['last_name']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="bhf-number">BHF Practice No:</label>
                        <input type="text" name="bhf_number" value="<?php echo $user_data['bhf_number']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="practice-type">Medical Practice Type:</label>
                        <input type="text" name="practice_type" value="<?php echo $user_data['practice_type']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="telephone">Telephone:</label>
                        <input type="text" name="telephone" value="<?php echo $user_data['telephone']; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" name="email" value="<?php echo $user_data['email']; ?>" readonly>
                    </div>
                </div>

                <!-- Case Information Section -->
                <div class="section">
                    <h3>Case Information Section</h3>
                    <div class="form-group">
                        <label for="required-service">Required Service: <span class="required">*</span></label>
                        <select name="required_service" id="required-service" required>
                            <option value="">Select Required Service</option>
                            <?php
                            $result_required_services->data_seek(0); // Reset pointer
                            while ($row = $result_required_services->fetch_assoc()):
                                $selected = (isset($_POST['required_service']) && $_POST['required_service'] == $row['id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $row['id']; ?>" <?php echo $selected; ?>>
                                    <?php echo $row['required_service']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="case-description">Case Description:</label>
                        <textarea name="case_description" id="case-description" placeholder="Case Description"><?php echo isset($_POST['case_description']) ? htmlspecialchars($_POST['case_description']) : ''; ?></textarea>
                    </div>
                </div>

                <!-- Appointment Section -->
                <div class="section">
                    <h3><i class="fas fa-calendar-alt"></i> Appointment Section</h3>
                    <div class="form-group">
                        <label for="appointment_date">Select Date: <span class="required">*</span></label>
                        <select name="appointment_date" id="appointment_date" required>
                            <option value="">Select a Date</option>
                            <?php
                            // Fetch available dates from available_slots table
                            $sql_dates = "SELECT DISTINCT date FROM available_slots WHERE is_available = 1 ORDER BY date ASC";
                            $result_dates = $conn->query($sql_dates);
                            while ($date_row = $result_dates->fetch_assoc()):
                                $formatted_date = date('Y-m-d', strtotime($date_row['date']));
                                $selected = (isset($_POST['appointment_date']) && $_POST['appointment_date'] == $formatted_date) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $formatted_date; ?>" <?php echo $selected; ?>><?php echo date('F d, Y', strtotime($formatted_date)); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="appointment_time">Select Time Slot: <span class="required">*</span></label>
                        <select name="appointment_time" id="appointment_time" required>
                            <option value="">Select a Time Slot</option>
                            <!-- Time slots will be populated based on selected date -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="meeting_type">Meeting Type: <span class="required">*</span></label>
                        <select name="meeting_type" id="meeting_type" required>
                            <option value="">Select Meeting Type</option>
                            <option value="Physical" <?php echo (isset($_POST['meeting_type']) && $_POST['meeting_type'] == 'Physical') ? 'selected' : ''; ?>>Physical</option>
                            <option value="Online" <?php echo (isset($_POST['meeting_type']) && $_POST['meeting_type'] == 'Online') ? 'selected' : ''; ?>>Online</option>
                        </select>
                    </div>
                </div>

                <!-- File Upload Section -->
                <div class="upload-section">
                    <label for="file-upload" class="upload-title">Please Upload Supporting Documents (PDF, JPG, PNG, Word)</label>
                    <div class="upload-box" id="upload-area">
                        <p>Upload</p>
                        <div class="upload-icon">
                            <img src="images/upload-icon.png" alt="Upload Icon">
                        </div>
                        <p>Drag & drop files or <a href="#" id="browse-files">Browse</a></p>
                        <p class="supported-formats">Supported formats: JPEG, PNG, PDF, Word</p>
                    </div>

                    <input type="file" name="file_upload" id="file-upload" accept=".jpg, .jpeg, .png, .pdf" hidden>
                    <progress id="upload-progress" value="0" max="100" hidden></progress>

                    <!-- Display the uploaded file name -->
                    <p id="file-name-display"><?php echo isset($_FILES['file_upload']['name']) ? htmlspecialchars($_FILES['file_upload']['name']) : ''; ?></p>

                    <!-- Error message for file upload -->
                    <?php if (isset($errors) && !empty($errors)): ?>
                        <div class="error-message show">
                            <?php foreach ($errors as $error): ?>
                                <?php echo htmlspecialchars($error) . '<br>'; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <script>
                    const fileInput = document.getElementById('file-upload');
                    const uploadArea = document.getElementById('upload-area');
                    const progressBar = document.getElementById('upload-progress');
                    const fileNameDisplay = document.getElementById('file-name-display');
                    const browseFilesLink = document.getElementById('browse-files');

                    // Prevent default scrolling when clicking 'Browse'
                    browseFilesLink.addEventListener('click', function(event) {
                        event.preventDefault(); // Prevent default anchor behavior
                        fileInput.click(); // Trigger file input click
                    });

                    uploadArea.addEventListener('click', function () {
                        fileInput.click(); // Trigger file input click
                    });

                    // Drag and Drop functionality
                    uploadArea.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        uploadArea.style.borderColor = '#1c658c'; // Highlight the area on drag over
                    });

                    uploadArea.addEventListener('dragleave', function(e) {
                        uploadArea.style.borderColor = '#8ca8c3'; // Revert border color on drag leave
                    });

                    uploadArea.addEventListener('drop', function(e) {
                        e.preventDefault();
                        const file = e.dataTransfer.files[0]; // Handle the file drop
                        if (file) {
                            processFile(file);
                        }
                        uploadArea.style.borderColor = '#8ca8c3'; // Reset border color after drop
                    });

                    fileInput.addEventListener('change', function () {
                        const file = fileInput.files[0];
                        if (file) {
                            processFile(file);
                        }
                    });

                    // Function to process and display the file name and simulate progress bar
                    function processFile(file) {
                        const fileName = file.name; // Get the file name
                        fileNameDisplay.innerText = fileName; // Display file name
                        progressBar.hidden = false; // Show the progress bar

                        simulateProgressBar(); // Simulate file upload progress
                    }

                    // Simulate the progress bar without uploading
                    function simulateProgressBar() {
                        let progress = 0;
                        const interval = setInterval(function () {
                            progress += 10; // Increase progress by 10%
                            progressBar.value = progress;

                            if (progress >= 100) {
                                clearInterval(interval); // Stop once we reach 100%
                            }
                        }, 200); // Adjust the interval timing (200ms) for smooth progress bar simulation
                    }

                    // Populate time slots based on selected date
                    document.getElementById('appointment_date').addEventListener('change', function() {
                        var selectedDate = this.value;
                        var appointmentTimeSelect = document.getElementById('appointment_time');

                        // Clear previous options
                        appointmentTimeSelect.innerHTML = '<option value="">Select a Time Slot</option>';

                        if (selectedDate) {
                            var xhr = new XMLHttpRequest();
                            xhr.open('POST', 'fetch_time_slots.php', true);
                            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                            xhr.onreadystatechange = function() {
                                if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                                    var response = JSON.parse(this.responseText);
                                    if (response.status === 'success') {
                                        response.time_slots.forEach(function(time) {
                                            var option = document.createElement('option');
                                            option.value = time;
                                            option.textContent = time;
                                            appointmentTimeSelect.appendChild(option);
                                        });
                                    } else {
                                        var option = document.createElement('option');
                                        option.value = '';
                                        option.textContent = 'No Available Time Slots';
                                        appointmentTimeSelect.appendChild(option);
                                    }
                                }
                            };
                            xhr.send('date=' + encodeURIComponent(selectedDate));
                        }
                    });
                </script>

                <!-- Submit Button -->
                <button type="submit" class="submit-btn">Submit Query</button>
            </form>
        </section>
    </main>
    <?php include 'includes/footer.php'; ?>

    <!-- Success Modal -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div id="successModal" class="modal show">
            <div class="modal-content">
                <button class="close-modal" onclick="closeModal()" aria-label="Close modal">&times;</button>
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Success!</h3>
                <p><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
                <div class="modal-actions">
                    <a href="user_dashboard.php" class="btn">Go to Dashboard</a>
                    <a href="index.php" class="btn-secondary">Browse More Services</a>
                </div>
            </div>
        </div>
        <script>
            function closeModal() {
                const modal = document.getElementById("successModal");
                modal.classList.remove("show");
                setTimeout(() => {
                    modal.style.display = "none";
                }, 300);
            }

            // Close modal when clicking outside of it
            document.getElementById("successModal").addEventListener("click", function(event) {
                if (event.target === this) {
                    closeModal();
                }
            });

            // Close modal with Escape key
            document.addEventListener("keydown", function(event) {
                if (event.key === "Escape" && document.getElementById("successModal").classList.contains("show")) {
                    closeModal();
                }
            });

            // Automatically show the modal if success_message is set
            document.addEventListener("DOMContentLoaded", function() {
                const modal = document.getElementById("successModal");
                if (modal) {
                    modal.style.display = "flex";
                    setTimeout(() => {
                        modal.classList.add("show");
                    }, 10); // Slight delay to allow CSS transitions
                }
            });

            // Clear success message from session after showing modal
            <?php unset($_SESSION['success_message']); ?>
        </script>
    <?php endif; ?>

    <!-- Existing scripts -->
</body>
</html>
