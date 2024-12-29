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
require_once 'mail-config.php'; // Include the mailer script

use Dompdf\Dompdf;

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
    // Check if required checkboxes are checked
    if (!isset($_POST['terms']) || !isset($_POST['deposit'])) {
        $_SESSION['error_message'] = "Please agree to the Terms of Service and acknowledge the deposit fee.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Sanitize and retrieve form inputs
    $query_type = $_POST['query_type'];
    $required_service_id = $_POST['required_service'];
    $case_description = htmlspecialchars(trim($_POST['case_description']));
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $meeting_type = $_POST['meeting_type'];

    // Store query_type in the session
    $_SESSION['query_type'] = $query_type;

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
        $_SESSION['error_message'] = "Price for the selected query type not found.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
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
        $_SESSION['error_message'] = "Selected required service not found.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Handle multiple file uploads
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $uploaded_files = [];
    $total_files = count($_FILES['file_upload']['name']);

    for ($i = 0; $i < $total_files; $i++) {
        $file_name = basename($_FILES["file_upload"]["name"][$i]);
        $target_file = $target_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png", "pdf", "doc", "docx"];

        // Validate file type
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message'] = "Invalid file format for file: $file_name. Only JPG, JPEG, PNG, PDF, and Word files are allowed.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        // Move the uploaded file
        if (!move_uploaded_file($_FILES["file_upload"]["tmp_name"][$i], $target_file)) {
            $_SESSION['error_message'] = "Error uploading file: $file_name.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        $uploaded_files[] = $target_file;
    }

    // Zip files if more than one
    if (count($uploaded_files) > 1) {
        $zip = new ZipArchive();
        $zip_file_name = $target_dir . "files_" . time() . ".zip";

        if ($zip->open($zip_file_name, ZipArchive::CREATE) !== TRUE) {
            $_SESSION['error_message'] = "Could not create zip file.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        foreach ($uploaded_files as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();

        // Optionally, remove individual files after zipping
        foreach ($uploaded_files as $file) {
            unlink($file);
        }

        $final_file = basename($zip_file_name);
    } else {
        $final_file = basename($uploaded_files[0]);
    }

    // Insert into `medicolegal`
    $sql_insert_query = "INSERT INTO medicolegal (user_id, query_type, case_description, required_service_id, fee, file) 
                         VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert_query);
    $stmt_insert->bind_param('isssis', $user_id, $query_type, $case_description, $required_service_id, $_SESSION['fee'], $final_file);
    $stmt_insert->execute();
    $medico_id = $stmt_insert->insert_id;

    // Store medico_id in session
    $_SESSION['medico_id'] = $medico_id;

    // Retrieve slot_id for selected date and time
    $sql_slot = "SELECT slot_id FROM available_slots WHERE date = ? AND (time_slot1 = ? OR time_slot2 = ? OR time_slot3 = ?) LIMIT 1";
    $stmt_slot = $conn->prepare($sql_slot);
    $stmt_slot->bind_param('ssss', $appointment_date, $appointment_time, $appointment_time, $appointment_time);
    $stmt_slot->execute();
    $result_slot = $stmt_slot->get_result();
    if ($result_slot->num_rows > 0) {
        $slot_data = $result_slot->fetch_assoc();
        $slot_id = $slot_data['slot_id'];
    } else {
        $_SESSION['error_message'] = "Selected appointment slot not found.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Insert into `appointments`
    $sql_insert_appointment = "INSERT INTO appointments (slot_id, user_id, meeting_type) VALUES (?, ?, ?)";
    $stmt_appointment = $conn->prepare($sql_insert_appointment);
    $stmt_appointment->bind_param('iis', $slot_id, $user_id, $meeting_type);
    $stmt_appointment->execute();

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

    // Prepare email content
    $admin_email = 'ambrosetshuma26.ka@gmail.com';
    $subject = 'New Medico-Legal Query Submission';
    $message = '
        <p>Dear Admin,</p>
        <p>A new medico-legal query has been submitted by ' . htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']) . '.</p>
        <h3>Query Details:</h3>
        <ul>
            <li><strong>Query Type:</strong> ' . htmlspecialchars($query_type) . '</li>
            <li><strong>Required Service:</strong> ' . htmlspecialchars($_SESSION['required_service']) . '</li>
            <li><strong>Case Description:</strong> ' . nl2br(htmlspecialchars($case_description)) . '</li>
        </ul>
        <p>Please log in to the admin panel to review the submission.</p>
        <p>Best regards,<br>Your Website</p>
    ';

    // Attach the PDF and uploaded files
    $attachments = [$pdf_file_path];
    if (count($uploaded_files) > 1) {
        $attachments[] = $zip_file_name;
    } else {
        $attachments[] = $uploaded_files[0];
    }

    // Send the email
    if (!sendEmail($admin_email, $subject, $message, $attachments)) {
        error_log("Failed to send email to admin.");
    }

    $_SESSION['success_message'] = "Query submitted successfully! You will be redirected to the payment page shortly.";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Existing head content -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medico-Legal Queries</title>
    <style>
        .confirmation-message {
            background-color: #d4edda; /* Light green for success */
            border: 1px solid #c3e6cb; /* Green border */
            color: #155724; /* Dark green text */
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            display: none; /* Start hidden */
            opacity: 0; /* Start invisible */
            transition: opacity 0.5s ease; /* Smooth transition */
        }

        .confirmation-message.visible {
            display: block; /* Show when visible */
            opacity: 1; /* Fully visible */
        }

        .confirmation-message.error {
            background-color: #f8d7da; /* Light red for errors */
            border-color: #f5c6cb; /* Red border */
            color: #721c24; /* Dark red text */
        }

        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            position: relative;
        }
        .close {
            color: #aaa;
            position: absolute;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        /* Additional styling as needed */
    </style>
    <link rel="stylesheet" href="assets/styles3.css">
    <link rel="stylesheet" href="assets/styles4.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <section class="form-section">
            <h1>Medico-Legal Queries</h1>
            <h2>Submit a Medico-Legal Query</h2>

            <!-- Display Success or Error Messages -->
            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="confirmation-message visible">
                    <i class="fas fa-check-circle"></i>
                    <p>
                        <?php 
                            echo $_SESSION['success_message']; 
                            unset($_SESSION['success_message']); 
                        ?>
                        You will be redirected to the <a href="payment_page_legal.php" class="payment-link">payment page</a> in <span id="countdown">3</span> seconds.
                    </p>
                </div>
                <script>
                    // Redirect after 3 seconds
                    let countdown = 3;
                    const countdownElement = document.getElementById('countdown');
                    const interval = setInterval(() => {
                        countdown--;
                        countdownElement.textContent = countdown;
                        if (countdown === 0) {
                            clearInterval(interval);
                            window.location.href = 'payment_page_legal.php';
                        }
                    }, 1000);
                </script>
            <?php endif; ?>

            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="confirmation-message error visible">
                    <i class="fas fa-exclamation-circle"></i>
                    <p><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
                </div>
            <?php endif; ?>

            <form id="medico-legal-form" method="POST" action="" enctype="multipart/form-data">
                <!-- General Details Section -->
                <div class="section">
                    <h3><img src="images/generaldetailsSection.png" alt="General Details Icon"> General Details Section</h3>

                    <!-- Query Type Field (From Database) -->
                    <div class="form-group">
                        <label for="query-type">Query Type: <span class="required">*</span></label>
                        <select name="query_type" id="query-type" required>
                            <option value="">Select query type</option>
                            <?php while ($row = $result_query_types->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($row['query_type']); ?>">
                                    <?php echo htmlspecialchars($row['query_type']); ?> (R <?php echo number_format($row['price'], 2); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Auto-populated User Information from Database -->
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($user_data['title'] ?? ''); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="name">First Name:</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="surname">Last Name:</label>
                        <input type="text" name="surname" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="bhf-number">BHF Practice No:</label>
                        <input type="text" name="bhf_number" value="<?php echo htmlspecialchars($user_data['bhf_number']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="practice-type">Medical Practice Type:</label>
                        <input type="text" name="practice_type" value="<?php echo htmlspecialchars($user_data['practice_type']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="telephone">Telephone:</label>
                        <input type="text" name="telephone" value="<?php echo htmlspecialchars($user_data['telephone']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
                    </div>
                </div>

                <!-- Case Information Section -->
                <div class="section">
                    <h3><img src="images/caseinformationSection.png" alt="Case Information Icon"> Case Information Section</h3>
                    <div class="form-group">
                        <label for="required-service">Required Service: <span class="required">*</span></label>
                        <select name="required_service" id="required-service" required>
                            <option value="">Select Required Service</option>
                            <?php while ($row = $result_required_services->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($row['id']); ?>">
                                    <?php echo htmlspecialchars($row['required_service']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="case-description">Case Description:</label>
                        <textarea name="case_description" id="case-description" placeholder="Case Description"></textarea>
                    </div>
                </div>

                <!-- Appointment Section -->
                <div class="section">
                    <h3><i class="fas fa-calendar-alt"></i> Appointment Section</h3>
                    <p><strong>Note:</strong> A consultation deposit fee of 30% of the query type price is required to confirm your appointment. The remaining balance will be invoiced after the consultation.</p>
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
                            ?>
                                <option value="<?php echo htmlspecialchars($formatted_date); ?>"><?php echo date('F d, Y', strtotime($formatted_date)); ?></option>
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
                            <option value="Physical">Physical</option>
                            <option value="Online">Online</option>
                        </select>
                    </div>
                </div>

                <!-- File Upload Section -->
                <div class="upload-section">
                    <label for="file-upload" class="upload-title">Please Upload Supporting Documents (PDF, JPG, PNG, Word)</label>
                    <!-- Modal Trigger Link -->
                    <a href="#" id="open-modal">Click here to view required documents</a>

                    <!-- Modal Structure -->
                    <div id="modal" class="modal">
                        <div class="modal-content">
                            <span id="close-modal" class="close">&times;</span>
                            <h2>Required Documents for Medico-Legal Query Submission</h2>
                            <ul>
                                <li>Motivational Letter explaining the medicolegal issue.</li>
                                <li>Relevant Medical Records and Reports.</li>
                                <li>Correspondence with patients or regulatory bodies.</li>
                                <li>Legal Documents related to the case.</li>
                                <li>Other Supporting Documentation.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="upload-box" id="upload-area">
                        <p>Upload</p>
                        <div class="upload-icon">
                            <img src="images/upload-icon.png" alt="Upload Icon">
                        </div>
                        <p>Drag & drop files or <a href="#" id="browse-files">Browse</a></p>
                        <p class="supported-formats">Supported formats: JPEG, PNG, PDF, Word</p>
                    </div>

                    <input type="file" name="file_upload[]" id="file-upload" accept=".jpg, .jpeg, .png, .pdf, .doc, .docx" hidden required multiple>
                    <progress id="upload-progress" value="0" max="100" hidden></progress>

                    <!-- Display the uploaded file names -->
                    <p id="file-name-display"></p>
                </div>

                <!-- JavaScript for Modal and File Upload -->
                <script>
                    // Modal functionality
                    document.getElementById('open-modal').addEventListener('click', function(e) {
                        e.preventDefault();
                        document.getElementById('modal').style.display = 'block';
                    });
                    document.getElementById('close-modal').addEventListener('click', function(e) {
                        document.getElementById('modal').style.display = 'none';
                    });
                    // Close modal when clicking outside of the modal content
                    window.onclick = function(event) {
                        if (event.target == document.getElementById('modal')) {
                            document.getElementById('modal').style.display = 'none';
                        }
                    };

                    // File upload functionality
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
                        const files = e.dataTransfer.files;
                        if (files.length > 0) {
                            processFiles(files);
                        }
                        uploadArea.style.borderColor = '#8ca8c3'; // Reset border color after drop
                    });

                    fileInput.addEventListener('change', function () {
                        const files = fileInput.files;
                        if (files.length > 0) {
                            processFiles(files);
                        }
                    });

                    // Function to process and display the file names and simulate progress bar
                    function processFiles(files) {
                        let fileNames = [];
                        for (let i = 0; i < files.length; i++) {
                            fileNames.push(files[i].name);
                        }
                        fileNameDisplay.innerText = fileNames.join(', '); // Display file names
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
                                progressBar.hidden = true; // Hide the progress bar after completion
                            }
                        }, 200); // Adjust the interval timing (200ms) for smooth progress bar simulation
                    }
                </script>

                
                <!-- Agreement and Deposit Confirmation Section -->
                <div class="section">
                    <h3>Agreement and Confirmation</h3>
                    <div class="form-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="terms.php" target="_blank">Terms of Service</a>.</label>
                    </div>
                    <div class="form-group">
                        <input type="checkbox" id="deposit" name="deposit" required>
                        <label for="deposit">I acknowledge that a consultation deposit fee of 30% of the query type price is required. The remaining balance will be paid after the consultation based on the investigative report.</label>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-btn">Submit Query</button>
            </form>
        </section>
    </main>
    <?php include 'includes/footer.php'; ?>

    <!-- Additional JavaScript -->
    <script>
        // Optional: Additional JavaScript if needed
    </script>
    <!-- jQuery for AJAX calls (if not already included) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- AJAX script for fetching time slots -->
    <script>
        $(document).ready(function() {
            $('#appointment_date').on('change', function() {
                var selectedDate = $(this).val();
                if(selectedDate) {
                    $.ajax({
                        url: 'fetch_time_slots.php',
                        type: 'POST',
                        data: { date: selectedDate },
                        dataType: 'json',
                        success: function(response) {
                            $('#appointment_time').empty().append('<option value="">Select a Time Slot</option>');
                            if(response.status === 'success') {
                                $.each(response.time_slots, function(index, time) {
                                    $('#appointment_time').append('<option value="'+ time +'">'+ time +'</option>');
                                });
                            } else {
                                $('#appointment_time').append('<option value="">No Available Time Slots</option>');
                            }
                        },
                        error: function() {
                            alert('An error occurred while fetching time slots.');
                        }
                    });
                } else {
                    $('#appointment_time').empty().append('<option value="">Select a Time Slot</option>');
                }
            });
        });
    </script>
</body>
</html>

