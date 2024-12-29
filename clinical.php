<?php
// Start the session and enable error reporting for debugging
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get today's date for date validations
$today = date('Y-m-d');

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page
    header("Location: sign.php");
    exit(); // Stop further execution of the page
}

// Database connection
include 'includes/db_connection.php';

// Include DOMPDF for PDF generation
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

// Get logged-in user's data
$user_id = $_SESSION['user_id'];

// Fetch user data using prepared statements
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Preparation failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result_user = $stmt->get_result();

if ($result_user->num_rows > 0) {
    $user_data = $result_user->fetch_assoc(); // Fetch user data
} else {
    die("User not found.");
}
$stmt->close();

// Fetch query types from manageclinicalcode table
$sql_query_types = "SELECT id, query_name, price FROM manageclinicalcode";
$result_query_types = $conn->query($sql_query_types);
if (!$result_query_types) {
    die("Query failed: (" . $conn->errno . ") " . $conn->error);
}

// Fetch PMB conditions (if applicable)
$sql_pmb = "SELECT DISTINCT pmb_condition FROM pmb_conditions";
$result_pmb = $conn->query($sql_pmb);
if (!$result_pmb) {
    die("PMB Conditions Query failed: (" . $conn->errno . ") " . $conn->error);
}

// Generate CSRF Token early to ensure it's available for the form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function to validate date
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    // The date is valid if the DateTime object was created and matches the format
    return $d && $d->format($format) === $date;
}

// Initialize an associative array to hold errors
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors['csrf'] = "Invalid CSRF token.";
        // Do not perform further validation if CSRF token is invalid
    } else {
        // Retrieve and sanitize form inputs
        $query_type_id = intval($_POST['query_type'] ?? 0);
        $diagnosis_code = strtoupper(trim($_POST['diagnosis_code'] ?? ''));
        $procedure_code = trim($_POST['procedure_code'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Basic validation
        if (empty($query_type_id)) {
            $errors['query_type'] = 'Please select a query type.';
        }

        // Validate Diagnosis Code
        if (!preg_match('/^(?=.*\d)[A-Z][A-Z0-9]{2,4}$/', $diagnosis_code)) {
            $errors['diagnosis_code'] = 'Invalid diagnosis code format. Must be 3-5 alphanumeric characters, starting with a letter (uppercase) and contain at least one number.';
        }

        // Validate Procedure Code
        if (!preg_match('/^\d{4,6}$/', $procedure_code)) {
            $errors['procedure_code'] = 'Procedure code must be 4-6 digits only.';
        }

        // Validate description
        if (empty($description)) {
            $errors['description'] = 'Please provide a query description.';
        }

        // Determine selected query type name and price
        if (empty($errors)) {
            $sql_query_type = "SELECT query_name, price FROM manageclinicalcode WHERE id = ? LIMIT 1";
            $stmt_query_type = $conn->prepare($sql_query_type);
            if ($stmt_query_type) {
                $stmt_query_type->bind_param("i", $query_type_id);
                $stmt_query_type->execute();
                $result_query_type = $stmt_query_type->get_result();

                if ($result_query_type->num_rows > 0) {
                    $query_type_data = $result_query_type->fetch_assoc();
                    $query_type = $query_type_data['query_name'];
                    $query_price = $query_type_data['price'];
                } else {
                    $errors['query_type'] = "Selected query type not found.";
                }
                $stmt_query_type->close();
            } else {
                $errors['query_type'] = "Failed to prepare query type statement.";
            }
        }

        // Initialize variables for dynamic fields
        $pmb_condition = null;
        $pmb_medical_aid_scheme_name = null;
        $pmb_medical_aid_scheme_plan = null;

        $scenario_medical_aid_scheme_name = null;
        $scenario_medical_aid_scheme_plan = null;
        $scenario_claim_reference_number = null;
        $scenario_reason_of_rejection = null;

        $partial_medical_aid_scheme_name = null;
        $partial_medical_aid_scheme_plan = null;
        $claim_reference_number = null;
        $date_of_service = null;
        $amount_billed = null;
        $amount_paid = null;

        $additional_diagnosis_codes = [];
        $additional_procedure_codes = [];

        // Handle dynamic fields based on query type
        if (empty($errors)) {
            switch ($query_type) {
                case 'PMB Query':
                    $pmb_condition = trim($_POST['pmb_condition'] ?? '');
                    $pmb_medical_aid_scheme_name = trim($_POST['pmb_medical_aid_scheme_name'] ?? '');
                    $pmb_medical_aid_scheme_plan = trim($_POST['pmb_medical_aid_scheme_plan'] ?? '');

                    // Validate PMB fields
                    if (empty($pmb_condition)) {
                        $errors['pmb_condition'] = 'Please select a PMB condition.';
                    }
                    if (empty($pmb_medical_aid_scheme_name)) {
                        $errors['pmb_medical_aid_scheme_name'] = 'Please enter Medical Aid Scheme Name.';
                    } elseif (!preg_match('/^[A-Za-z\s]+$/', $pmb_medical_aid_scheme_name)) { // Only letters and spaces
                        $errors['pmb_medical_aid_scheme_name'] = 'Medical Aid Scheme Name must contain letters and spaces only.';
                    }
                    if (empty($pmb_medical_aid_scheme_plan)) {
                        $errors['pmb_medical_aid_scheme_plan'] = 'Please enter Medical Aid Scheme Plan.';
                    } elseif (!preg_match('/^[A-Za-z\s]+$/', $pmb_medical_aid_scheme_plan)) { // Only letters and spaces
                        $errors['pmb_medical_aid_scheme_plan'] = 'Medical Aid Scheme Plan must contain letters and spaces only.';
                    }
                    break;

                case 'Scenarios':
                    $scenario_medical_aid_scheme_name = trim($_POST['scenario_medical_aid_scheme_name'] ?? '');
                    $scenario_medical_aid_scheme_plan = trim($_POST['scenario_medical_aid_scheme_plan'] ?? '');
                    $scenario_claim_reference_number = trim($_POST['scenario_claim_reference_number'] ?? '');
                    $scenario_reason_of_rejection = trim($_POST['scenario_reason_of_rejection'] ?? '');

                    // Validate Scenario fields
                    if (empty($scenario_medical_aid_scheme_name)) {
                        $errors['scenario_medical_aid_scheme_name'] = 'Please enter Medical Aid Scheme Name.';
                    } elseif (!preg_match('/^[A-Za-z\s]+$/', $scenario_medical_aid_scheme_name)) { // Only letters and spaces
                        $errors['scenario_medical_aid_scheme_name'] = 'Medical Aid Scheme Name must contain letters and spaces only.';
                    }
                    if (empty($scenario_medical_aid_scheme_plan)) {
                        $errors['scenario_medical_aid_scheme_plan'] = 'Please enter Medical Aid Scheme Plan.';
                    } elseif (!preg_match('/^[A-Za-z\s]+$/', $scenario_medical_aid_scheme_plan)) { // Only letters and spaces
                        $errors['scenario_medical_aid_scheme_plan'] = 'Medical Aid Scheme Plan must contain letters and spaces only.';
                    }
                    if (empty($scenario_claim_reference_number)) {
                        $errors['scenario_claim_reference_number'] = 'Please enter Claim Reference Number.';
                    } elseif (!preg_match('/^[A-Za-z0-9]{1,10}$/', $scenario_claim_reference_number)) {
                        $errors['scenario_claim_reference_number'] = 'Scenario Claim Reference Number must be alphanumeric and contain 1-10 characters.';
                    }
                    if (empty($scenario_reason_of_rejection)) {
                        $errors['scenario_reason_of_rejection'] = 'Please enter Reason for Rejection.';
                    }
                    break;

                case 'Non Payment':
                case 'Partial Payment':
                    $claim_reference_number = trim($_POST['claim_reference_number'] ?? '');
                    $date_of_service = $_POST['date_of_service'] ?? '';
                    $amount_paid = $_POST['amount_paid'] ?? '';
                    $amount_billed = $_POST['amount_billed'] ?? '';
                    $partial_medical_aid_scheme_name = trim($_POST['partial_medical_aid_scheme_name'] ?? '');
                    $partial_medical_aid_scheme_plan = trim($_POST['partial_medical_aid_scheme_plan'] ?? '');

                    // Validate Non/Partial Payment fields
                    if (empty($claim_reference_number)) {
                        $errors['claim_reference_number'] = 'Please Enter claim reference number (only numbers and/or letters).';
                    } elseif (!preg_match('/^[A-Za-z0-9]{1,10}$/', $claim_reference_number)) {
                        $errors['claim_reference_number'] = 'Claim Reference Number must be alphanumeric and contain 1-10 characters.';
                    }

                    if (empty($date_of_service)) {
                        $errors['date_of_service'] = 'Please enter Date of Service.';
                    } elseif (!validateDate($date_of_service)) {
                        $errors['date_of_service'] = 'Please enter a valid Date of Service.';
                    } elseif (strtotime($date_of_service) > strtotime($today)) {
                        $errors['date_of_service'] = 'Date of Service cannot be in the future.';
                    }

                    if ($amount_paid === '' || !is_numeric($amount_paid)) {
                        $errors['amount_paid'] = 'Please enter a valid Amount Paid.';
                    } elseif ($amount_paid < 0) {
                        $errors['amount_paid'] = 'Amount Paid cannot be negative.';
                    }

                    if ($amount_billed === '' || !is_numeric($amount_billed) || $amount_billed < 0) {
                        $errors['amount_billed'] = 'Amount Billed must be a non-negative number.';
                    }
                    

                    // Ensure Amount Paid does not exceed Amount Billed
                    if (is_numeric($amount_paid) && is_numeric($amount_billed) && $amount_paid > $amount_billed) {
                        $errors['amount_paid'] = 'Amount Paid cannot exceed Amount Billed.';
                    }

                    if (empty($partial_medical_aid_scheme_name)) {
                        $errors['partial_medical_aid_scheme_name'] = 'Please enter Medical Aid Scheme Name.';
                    } elseif (!preg_match('/^[A-Za-z\s]+$/', $partial_medical_aid_scheme_name)) { // Only letters and spaces
                        $errors['partial_medical_aid_scheme_name'] = 'Medical Aid Scheme Name must contain letters and spaces only.';
                    }

                    if (empty($partial_medical_aid_scheme_plan)) {
                        $errors['partial_medical_aid_scheme_plan'] = 'Please enter Medical Aid Scheme Plan.';
                    } elseif (!preg_match('/^[A-Za-z\s]+$/', $partial_medical_aid_scheme_plan)) { // Only letters and spaces
                        $errors['partial_medical_aid_scheme_plan'] = 'Medical Aid Scheme Plan must contain letters and spaces only.';
                    }
                    break;

                case 'Unique Clinical Codes':
                case 'Unique Clinical Code':
                    $additional_diagnosis_codes = $_POST['additional_diagnosis_codes'] ?? [];
                    $additional_procedure_codes = $_POST['additional_procedure_codes'] ?? [];

                    // Sanitize and filter arrays
                    if (!empty($additional_diagnosis_codes)) {
                        $additional_diagnosis_codes = array_map('strtoupper', array_map('trim', $additional_diagnosis_codes));
                        $additional_diagnosis_codes = array_filter($additional_diagnosis_codes); // Remove empty values

                        // Validate each additional diagnosis code
                        foreach ($additional_diagnosis_codes as $code) {
                            if (!preg_match('/^(?=.*\d)[A-Z][A-Z0-9]{2,4}$/', $code)) {
                                $errors['additional_diagnosis_codes'][] = "Invalid additional diagnosis code format: $code. Must be 3-5 alphanumeric characters, starting with a letter (uppercase) and contain at least one number.";
                            }
                        }
                    }

                    if (!empty($additional_procedure_codes)) {
                        $additional_procedure_codes = array_map('trim', $additional_procedure_codes);
                        $additional_procedure_codes = array_filter($additional_procedure_codes); // Remove empty values

                        // Validate each additional procedure code
                        foreach ($additional_procedure_codes as $code) {
                            if (!preg_match('/^\d{4,6}$/', $code)) {
                                $errors['additional_procedure_codes'][] = "Invalid additional procedure code format: $code. Must be 4-6 digits only.";
                            }
                        }
                    }
                    break;

                default:
                    $errors['query_type'] = 'Invalid query type selected.';
                    break;
            }
        }

        // Handle File Upload - Enhanced Error Handling
        if (empty($errors)) {
            if (isset($_FILES["file_upload"])) {
                $file_error = $_FILES["file_upload"]["error"][0];
                $max_file_size = 5 * 1024 * 1024; // 5MB in bytes

                // Check if any file is uploaded
                if ($file_error === UPLOAD_ERR_NO_FILE) {
                    $errors['file_upload'] = "Please upload supporting documents.";
                } else {
                    // Process multiple files
                    $total_files = count($_FILES['file_upload']['name']);
                    $uploaded_files = [];
                    $target_dir = "uploads/";

                    // Check if uploads directory exists, if not, create it
                    if (!is_dir($target_dir)) {
                        if (!mkdir($target_dir, 0755, true)) {
                            $errors['file_upload'] = "Failed to create uploads directory.";
                        }
                    }

                    for ($i = 0; $i < $total_files; $i++) {
                        $file_name = basename($_FILES["file_upload"]["name"][$i]);
                        $file_size = $_FILES["file_upload"]["size"][$i];
                        $file_tmp = $_FILES["file_upload"]["tmp_name"][$i];
                        $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $allowed_types = ["jpg", "jpeg", "png", "pdf"];

                        if ($file_size > $max_file_size) {
                            $errors['file_upload'][] = "File {$file_name} exceeds the maximum allowed size of 5MB.";
                            continue;
                        }

                        if (!in_array($file_type, $allowed_types)) {
                            $errors['file_upload'][] = "Invalid file format for {$file_name}. Only JPG, JPEG, PNG, and PDF files are allowed.";
                            continue;
                        }

                        $unique_file_name = uniqid() . "_" . $file_name;
                        $target_file = $target_dir . $unique_file_name;

                        if (!move_uploaded_file($file_tmp, $target_file)) {
                            $errors['file_upload'][] = "Error uploading file {$file_name}.";
                            continue;
                        }

                        $uploaded_files[] = $target_file;
                    }

                    // Zip the uploaded files
                    if (!empty($uploaded_files)) {
                        $zip = new ZipArchive();
                        $zip_file_name = $target_dir . $user_data['first_name'] . "_" . $user_data['last_name'] . "_" . time() . ".zip";

                        if ($zip->open($zip_file_name, ZipArchive::CREATE) !== TRUE) {
                            $errors['file_upload'] = "Cannot create zip file.";
                        } else {
                            foreach ($uploaded_files as $file) {
                                $zip->addFile($file, basename($file));
                            }
                            $zip->close();

                            // Remove individual files after zipping
                            foreach ($uploaded_files as $file) {
                                unlink($file);
                            }

                            $file_name = basename($zip_file_name); // Update file_name to the zip file
                            $file_type = 'zip';
                            $target_file = $zip_file_name;
                        }
                    }
                }
            } else {
                $errors['file_upload'] = "Please upload supporting documents.";
            }
        }

        // Proceed if no errors
        if (empty($errors)) {
            // Generate the PDF using dompdf
            // Collect all form data into an HTML structure with styled headings

            $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Clinical Code Query</title>
                <style>
                    body { font-family: DejaVu Sans, sans-serif; }
                    .section { margin-bottom: 20px; }
                    h1, h2 { text-align: center; color: #1c658c; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { padding: 8px; border: 1px solid #000; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <h1>Clinical Code Query Form</h1>
                <div class="section">
                    <h2>Personal Details</h2>
                    <table>
                        <tr><th>Title</th><td>' . htmlspecialchars($user_data['title']) . '</td></tr>
                        <tr><th>First Name</th><td>' . htmlspecialchars($user_data['first_name']) . '</td></tr>
                        <tr><th>Last Name</th><td>' . htmlspecialchars($user_data['last_name']) . '</td></tr>
                        <tr><th>Telephone</th><td>' . htmlspecialchars($user_data['telephone']) . '</td></tr>
                        <tr><th>Email</th><td>' . htmlspecialchars($user_data['email']) . '</td></tr>
                    </table>
                </div>
                <div class="section">
                    <h2>Practice Information</h2>
                    <table>
                        <tr><th>Medical Practice Type</th><td>' . htmlspecialchars($user_data['practice_type']) . '</td></tr>
                        <tr><th>BHF Practice No</th><td>' . htmlspecialchars($user_data['bhf_number']) . '</td></tr>
                    </table>
                </div>
                <div class="section">
                    <h2>Query Information</h2>
                    <table>
                        <tr><th>Query Type</th><td>' . htmlspecialchars($query_type) . '</td></tr>
                        <tr><th>Diagnosis Code</th><td>' . htmlspecialchars($diagnosis_code) . '</td></tr>
                        <tr><th>Procedure Code</th><td>' . htmlspecialchars($procedure_code) . '</td></tr>
                        <tr><th>Query Description</th><td>' . nl2br(htmlspecialchars($description)) . '</td></tr>
                    </table>
                </div>
            ';

            // Add additional sections based on query type
            if ($query_type == 'Unique Clinical Codes' || $query_type == 'Unique Clinical Code') {
                // Additional Diagnosis Codes
                if (!empty($additional_diagnosis_codes)) {
                    $html .= '<div class="section"><h2>Additional Diagnosis Codes</h2><table>';
                    foreach ($additional_diagnosis_codes as $code) {
                        $html .= '<tr><td>' . htmlspecialchars($code) . '</td></tr>';
                    }
                    $html .= '</table></div>';
                }
                // Additional Procedure Codes
                if (!empty($additional_procedure_codes)) {
                    $html .= '<div class="section"><h2>Additional Procedure Codes</h2><table>';
                    foreach ($additional_procedure_codes as $code) {
                        $html .= '<tr><td>' . htmlspecialchars($code) . '</td></tr>';
                    }
                    $html .= '</table></div>';
                }
            } elseif ($query_type == 'Scenarios') {
                $html .= '<div class="section"><h2>Scenarios Details</h2><table>';
                $html .= '<tr><th>Medical Aid Scheme Name</th><td>' . htmlspecialchars($scenario_medical_aid_scheme_name) . '</td></tr>';
                $html .= '<tr><th>Medical Aid Scheme Plan</th><td>' . htmlspecialchars($scenario_medical_aid_scheme_plan) . '</td></tr>';
                $html .= '<tr><th>Claim Reference Number</th><td>' . htmlspecialchars($scenario_claim_reference_number) . '</td></tr>';
                $html .= '<tr><th>Reason of Rejection</th><td>' . nl2br(htmlspecialchars($scenario_reason_of_rejection)) . '</td></tr>';
                $html .= '</table></div>';
            } elseif ($query_type == 'PMB Query') {
                $html .= '<div class="section"><h2>PMB Query Details</h2><table>';
                $html .= '<tr><th>Medical Aid Scheme Name</th><td>' . htmlspecialchars($pmb_medical_aid_scheme_name) . '</td></tr>';
                $html .= '<tr><th>Medical Aid Scheme Plan</th><td>' . htmlspecialchars($pmb_medical_aid_scheme_plan) . '</td></tr>';
                $html .= '<tr><th>PMB Condition</th><td>' . htmlspecialchars($pmb_condition) . '</td></tr>';
                $html .= '</table></div>';
            } elseif ($query_type == 'Non Payment' || $query_type == 'Partial Payment') {
                $html .= '<div class="section"><h2>Non/Partial Payment Details</h2><table>';
                $html .= '<tr><th>Claim Reference Number</th><td>' . htmlspecialchars($claim_reference_number) . '</td></tr>';
                $html .= '<tr><th>Date of Service</th><td>' . htmlspecialchars($date_of_service) . '</td></tr>';
                $html .= '<tr><th>Amount Billed</th><td>' . htmlspecialchars($amount_billed) . '</td></tr>';
                $html .= '<tr><th>Amount Paid</th><td>' . htmlspecialchars($amount_paid) . '</td></tr>';
                $html .= '<tr><th>Medical Aid Scheme Name</th><td>' . htmlspecialchars($partial_medical_aid_scheme_name) . '</td></tr>';
                $html .= '<tr><th>Medical Aid Scheme Plan</th><td>' . htmlspecialchars($partial_medical_aid_scheme_plan) . '</td></tr>';
                $html .= '</table></div>';
            }

            $html .= '</body></html>';

            // Instantiate dompdf
            $dompdf = new Dompdf();

            // Load the HTML content
            $dompdf->loadHtml($html);

            // (Optional) Setup the paper size and orientation
            $dompdf->setPaper('A4', 'portrait');

            // Render the HTML as PDF
            $dompdf->render();

            // Output the generated PDF to a file in 'uploads' directory
            $pdfOutput = $dompdf->output();

            // Ensure the uploads directory exists
            $pdfDirectory = 'uploads/';
            if (!is_dir($pdfDirectory)) {
                mkdir($pdfDirectory, 0755, true);
            }

            // Create a unique filename
            $pdfFileName = $pdfDirectory . uniqid('query_form_', true) . '.pdf';

            // Save the PDF file
            file_put_contents($pdfFileName, $pdfOutput);

            // Now we have $pdfFileName as the path to the PDF
            $query_form_path = $pdfFileName;
        }

        // Proceed if no errors
        if (empty($errors)) {
            // Begin transaction
            $conn->begin_transaction();

            try {
                // 1. Insert into claimsqueries table with common fields
                $sql_insert_claims = "INSERT INTO claimsqueries (
                                    user_id,
                                    query_type,
                                    diagnosis_code,
                                    procedure_code,
                                    query_description,
                                    file,
                                    query_form,
                                    status,
                                    created_at
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending Review', NOW())";

                // Prepare and bind
                $stmt_insert_claims = $conn->prepare($sql_insert_claims);
                if (!$stmt_insert_claims) {
                    throw new Exception("Preparation failed for claimsqueries: (" . $conn->errno . ") " . $conn->error);
                }

                $stmt_insert_claims->bind_param(
                    "issssss",
                    $user_id,               // i
                    $query_type,            // s
                    $diagnosis_code,        // s
                    $procedure_code,        // s
                    $description,           // s
                    $file_name,             // s (zip file name)
                    $query_form_path        // s (path to the generated PDF)
                );

                if (!$stmt_insert_claims->execute()) {
                    throw new Exception("Error inserting into claimsqueries: " . $stmt_insert_claims->error);
                }
                $clinical_id = $stmt_insert_claims->insert_id;  // Get the inserted clinical ID
                $stmt_insert_claims->close();

                // 2. Insert into specific tables based on query type
                switch ($query_type) {
                    case 'PMB Query':
                        $sql_pmb_query = "INSERT INTO pmb_queries (clinical_id, pmb_condition, medical_aid_scheme_name, medical_aid_scheme_plan)
                                    VALUES (?, ?, ?, ?)";
                        $stmt_pmb_query = $conn->prepare($sql_pmb_query);
                        if (!$stmt_pmb_query) {
                            throw new Exception("Preparation failed for PMB Query: (" . $conn->errno . ") " . $conn->error);
                        }
                        $stmt_pmb_query->bind_param("isss", $clinical_id, $pmb_condition, $pmb_medical_aid_scheme_name, $pmb_medical_aid_scheme_plan);
                        if (!$stmt_pmb_query->execute()) {
                            throw new Exception("Error inserting into pmb_queries: " . $stmt_pmb_query->error);
                        }
                        $stmt_pmb_query->close();
                        break;

                    case 'Scenarios':
                        $sql_scenarios = "INSERT INTO scenarios (clinical_id, medical_aid_scheme_name, medical_aid_scheme_plan, claim_reference_number, reason_of_rejection)
                                          VALUES (?, ?, ?, ?, ?)";
                        $stmt_scenarios = $conn->prepare($sql_scenarios);
                        if (!$stmt_scenarios) {
                            throw new Exception("Preparation failed for Scenarios: (" . $conn->errno . ") " . $conn->error);
                        }
                        $stmt_scenarios->bind_param("issss", $clinical_id, $scenario_medical_aid_scheme_name, $scenario_medical_aid_scheme_plan, $scenario_claim_reference_number, $scenario_reason_of_rejection);
                        if (!$stmt_scenarios->execute()) {
                            throw new Exception("Error inserting into scenarios: " . $stmt_scenarios->error);
                        }
                        $stmt_scenarios->close();
                        break;

                    case 'Non Payment':
                    case 'Partial Payment':
                        $sql_non_partial = "INSERT INTO non_partial_payments (
                                                    clinical_id,
                                                    claim_reference_number,
                                                    date_of_service,
                                                    amount_paid,
                                                    amount_billed,
                                                    medical_aid_scheme_name,
                                                    medical_aid_scheme_plan
                                                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt_non_partial = $conn->prepare($sql_non_partial);
                        if (!$stmt_non_partial) {
                            throw new Exception("Preparation failed for Non/Partial Payment: (" . $conn->errno . ") " . $conn->error);
                        }
                        $stmt_non_partial->bind_param(
                            "issddss",
                            $clinical_id,
                            $claim_reference_number,
                            $date_of_service,
                            $amount_paid,
                            $amount_billed,
                            $partial_medical_aid_scheme_name,
                            $partial_medical_aid_scheme_plan
                        );
                        if (!$stmt_non_partial->execute()) {
                            throw new Exception("Error inserting into non_partial_payments: " . $stmt_non_partial->error);
                        }
                        $stmt_non_partial->close();
                        break;

                    case 'Unique Clinical Codes':
                    case 'Unique Clinical Code':
                        // Insert additional diagnosis codes
                        foreach ($additional_diagnosis_codes as $code) {
                            if (!empty($code)) {
                                $sql_unique_diagnosis = "INSERT INTO unique_clinical_code (clinical_id, additional_diagnosis_code) VALUES (?, ?)";
                                $stmt_unique_diagnosis = $conn->prepare($sql_unique_diagnosis);
                                if (!$stmt_unique_diagnosis) {
                                    throw new Exception("Preparation failed for Unique Diagnosis Code: (" . $conn->errno . ") " . $conn->error);
                                }
                                $stmt_unique_diagnosis->bind_param("is", $clinical_id, $code);
                                if (!$stmt_unique_diagnosis->execute()) {
                                    throw new Exception("Error inserting into unique_clinical_code (Diagnosis): " . $stmt_unique_diagnosis->error);
                                }
                                $stmt_unique_diagnosis->close();
                            }
                        }

                        // Insert additional procedure codes
                        foreach ($additional_procedure_codes as $code) {
                            if (!empty($code)) {
                                $sql_unique_procedure = "INSERT INTO unique_clinical_code (clinical_id, additional_procedure_code) VALUES (?, ?)";
                                $stmt_unique_procedure = $conn->prepare($sql_unique_procedure);
                                if (!$stmt_unique_procedure) {
                                    throw new Exception("Preparation failed for Unique Procedure Code: (" . $conn->errno . ") " . $conn->error);
                                }
                                $stmt_unique_procedure->bind_param("is", $clinical_id, $code);
                                if (!$stmt_unique_procedure->execute()) {
                                    throw new Exception("Error inserting into unique_clinical_code (Procedure): " . $stmt_unique_procedure->error);
                                }
                                $stmt_unique_procedure->close();
                            }
                        }
                        break;

                    default:
                        throw new Exception("Unknown query type: " . $query_type);
                        break;
                }

                // 3. Insert into file_uploads table
                $category = 'Clinical';
                $file_path = $target_file;
                $file_status = 'Pending Review';
                $sql_insert_file = "INSERT INTO file_uploads (clinical_id, user_id, category, file_path, file_type, status)
                                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_insert_file = $conn->prepare($sql_insert_file);
                if (!$stmt_insert_file) {
                    throw new Exception("Preparation failed for file_uploads: (" . $conn->errno . ") " . $conn->error);
                }
                $stmt_insert_file->bind_param("iissss", $clinical_id, $user_id, $category, $file_path, $file_type, $file_status);
                if (!$stmt_insert_file->execute()) {
                    throw new Exception("Error inserting into file_uploads: " . $stmt_insert_file->error);
                }
                $file_upload_id = $stmt_insert_file->insert_id;
                $stmt_insert_file->close();

                // 4. Insert into audit_trails table
                $action = "Submitted Clinical Query with File Upload";
                $sql_audit = "INSERT INTO audit_trails (user_id, action, clinical_id, medico_id, file_id)
                                   VALUES (?, ?, ?, NULL, ?)";
                $stmt_audit = $conn->prepare($sql_audit);
                if (!$stmt_audit) {
                    throw new Exception("Preparation failed for audit_trails: (" . $conn->errno . ") " . $conn->error);
                }
                $stmt_audit->bind_param("isii", $user_id, $action, $clinical_id, $file_upload_id);
                if (!$stmt_audit->execute()) {
                    throw new Exception("Error inserting into audit_trails: " . $stmt_audit->error);
                }
                $stmt_audit->close();

                // 5. Insert into notifications table for admin
                $admin_id = 1; // Assuming admin ID is 1; adjust as necessary
                $message = "A new Clinical query (ID: $clinical_id) has been submitted by User ID: $user_id. Please review the uploaded file.";
                $link = "#"; // Replace with actual admin link if available
                $sql_notification = "INSERT INTO notifications (user_id, message, link, is_read, created_at)
                                     VALUES (?, ?, ?, 0, NOW())";
                $stmt_notification = $conn->prepare($sql_notification);
                if (!$stmt_notification) {
                    throw new Exception("Preparation failed for notifications: (" . $conn->errno . ") " . $conn->error);
                }
                $stmt_notification->bind_param("iss", $admin_id, $message, $link);
                if (!$stmt_notification->execute()) {
                    throw new Exception("Error inserting into notifications: " . $stmt_notification->error);
                }
                $stmt_notification->close();

                // 6. Insert into invoices table

                if ($query_type == 'Unique Clinical Codes' || $query_type == 'Unique Clinical Code') {
                    // Calculate total number of codes
                    $total_number_of_codes = 2; // Main diagnosis and procedure codes

                    // Add counts of additional codes
                    $total_number_of_codes += count($additional_diagnosis_codes);
                    $total_number_of_codes += count($additional_procedure_codes);

                    // Calculate total_price
                    $total_price = 350 * $total_number_of_codes;
                } else {
                    $total_price = $query_price; // Use the default price from manageclinicalcode
                }

                // Customize billing details dynamically or set default values
                $billing_address = "36b West Road, Glen Austin AH, Midrand, South Africa";
                $billing_city = "Johannesburg";
                $billing_postal_code = "2192";
                $billing_province = "Gauteng";
                $billing_country = "South Africa";

                // Assign user data to variables
                $user_email = $user_data['email'] ?? '';
                $billing_first_name = $user_data['first_name'] ?? '';
                $billing_last_name = $user_data['last_name'] ?? '';

                // Prepare the SQL statement
                $sql_invoice = "INSERT INTO invoices (
                                        user_email,
                                        billing_first_name,
                                        billing_last_name,
                                        billing_address,
                                        billing_city,
                                        billing_postal_code,
                                        billing_province,
                                        billing_country,
                                        query_name,
                                        query_price,
                                        total_price,
                                        payment_status
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
                $stmt_invoice = $conn->prepare($sql_invoice);
                if (!$stmt_invoice) {
                    throw new Exception("Preparation failed for invoices: (" . $conn->errno . ") " . $conn->error);
                }

                // Bind parameters using variables
                $stmt_invoice->bind_param(
                    "ssssssssssd",
                    $user_email,          // s
                    $billing_first_name,  // s
                    $billing_last_name,   // s
                    $billing_address,     // s
                    $billing_city,        // s
                    $billing_postal_code, // s
                    $billing_province,    // s
                    $billing_country,     // s
                    $query_type,          // s
                    $query_price,         // d
                    $total_price          // d
                );
                
                if (!$stmt_invoice->execute()) {
                    throw new Exception("Error inserting into invoices: " . $stmt_invoice->error);
                }
                $invoice_id = $stmt_invoice->insert_id;
                $stmt_invoice->close();

                // 7. Update claimsqueries with invoice_id using clinical_id
                $sql_update_claims = "UPDATE claimsqueries SET invoice_id = ? WHERE clinical_id = ?";
                $stmt_update_claims = $conn->prepare($sql_update_claims);
                if (!$stmt_update_claims) {
                    throw new Exception("Preparation failed for updating claimsqueries: (" . $conn->errno . ") " . $conn->error);
                }
                $stmt_update_claims->bind_param("ii", $invoice_id, $clinical_id);
                if (!$stmt_update_claims->execute()) {
                    throw new Exception("Error updating claimsqueries with invoice_id: " . $stmt_update_claims->error);
                }
                $stmt_update_claims->close();

                // 8. Insert into audit_trails for invoice creation
                $action_invoice = "Created Invoice ID: $invoice_id for Clinical Query ID: $clinical_id";
                $sql_audit_invoice = "INSERT INTO audit_trails (user_id, action, clinical_id, medico_id, file_id)
                                           VALUES (?, ?, ?, NULL, NULL)";
                $stmt_audit_invoice = $conn->prepare($sql_audit_invoice);
                if (!$stmt_audit_invoice) {
                    throw new Exception("Preparation failed for audit_trails (Invoice): (" . $conn->errno . ") " . $conn->error);
                }
                $stmt_audit_invoice->bind_param("isi", $user_id, $action_invoice, $clinical_id);
                if (!$stmt_audit_invoice->execute()) {
                    throw new Exception("Error inserting into audit_trails for invoice: " . $stmt_audit_invoice->error);
                }
                $stmt_audit_invoice->close();

                // Commit the transaction
                $conn->commit();

                // 9. Send an automated email to the admin
                include 'mail-config.php';
                $admin_email = 'ambrosetshuma26.ka@gmail.com'; // Set to the admin email
                $subject = "New Clinical Query Submitted";
                $message = "User Name: {$user_data['first_name']} {$user_data['last_name']}<br>";
                $message .= "Query Type: {$query_type}<br>";
                $message .= "A new clinical query has been submitted. Please log in to the admin page to review it.<br>";
                $message .= "<a href='https://valueaddedhealthcaresa.com/Admin/admin_login.php'>Admin Login</a>";

                sendEmail($admin_email, $subject, $message);

                // 10. Store invoice_id and success message in session for redirection
                $_SESSION['invoice_id'] = $invoice_id;
                $_SESSION['success_message'] = "Your query has been successfully submitted!";

                // Redirect to avoid form resubmission
                header("Location: " . $_SERVER['PHP_SELF']);
                exit(); // Ensures the form doesn’t resubmit on reload
            } catch (Exception $e) {
                // Rollback the transaction on error
                $conn->rollback();
                // Log the error message
                // error_log($e->getMessage());
                // Add a generic error message for the user
                // $errors['general'] = "An unexpected error occurred. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta Tags and Title -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical Code Queries</title>
    <!-- CSS Stylesheets -->
    <link rel="stylesheet" href="assets/styles3.css">
    <link rel="stylesheet" href="assets/styles4.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500&family=Plus+Jakarta+Sans:wght@400;500&display=swap" rel="stylesheet">

    <!-- Inline Styles for Error Messages and Modal -->
    <style>
        /* Error Message Styles */
        .error-message {
            display: none;
            grid-column: 2 / span 2;
            width: 70%;
            color: #000000 !important;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            padding: 0.5rem;
            background-color: #fcc5c5;
            border-left: 4px solid #ff0000;
            border-radius: 0.375rem;
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
            max-width: 600px;
            width: 90%;
            text-align: center;
            position: relative;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1),
                        0 8px 10px -6px rgba(0, 0, 0, 0.1);
            transform: scale(0.8);
            transition: all 0.3s ease-in-out;
            overflow-y: auto;
            max-height: 80%;
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

       /* Styles for input with currency symbol */
    /* Styles for input with currency symbol */
    .input-with-symbol {
        display: flex;
        align-items: stretch;
        position: relative;
        width: 100%;
        max-width: 300px; /* Match this to your other input widths */
    }

    .currency-symbol {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
        font-weight: 500;
        z-index: 2;
        background: transparent;
        border: none;
        pointer-events: none;
    }

    .input-with-symbol input {
        width: 100%;
        padding: 8px 12px 8px 30px;
        border: 1px solid #ccc;
        border-radius: 4px;
        height: 40px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s ease;
        /* Ensure consistent width with other inputs */
        box-sizing: border-box;
    }

    /* Style to match other form inputs */
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="tel"],
    .form-group input[type="number"],
    .form-group input[type="date"],
    .form-group select,
    .input-with-symbol {
        width: 100%;
        max-width: 400px; /* Set consistent max-width for all inputs */
    }

    /* Ensure proper sizing of the input inside input-with-symbol */
    .input-with-symbol input[type="number"] {
        width: 100%;
        max-width: none; /* Remove max-width from nested input */
    }

    /* Optional: Add focus state */
    .input-with-symbol input:focus {
        border-color: #1c658c;
        box-shadow: 0 0 0 2px rgba(28, 101, 140, 0.1);
    }

    /* Ensure proper display on mobile */
    @media screen and (max-width: 768px) {
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group select,
        .input-with-symbol {
            max-width: 120%;
        }
    }
    /* Terms and Conditions Styles */
    /* Styles for input with currency symbol */
    .input-with-symbol {
        display: flex;
        align-items: stretch;
        position: relative;
        width: 100%;
        max-width: 400px; /* Wider input fields */
    }

    .currency-symbol {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
        font-weight: 500;
        z-index: 2;
        background: transparent;
        border: none;
        pointer-events: none;
    }

    .input-with-symbol input {
        width: 100%;
        padding: 8px 12px 8px 30px;
        border: 1px solid #ccc;
        border-radius: 4px;
        height: 40px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s ease;
        box-sizing: border-box;
    }

    /* Style to match other form inputs */
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="tel"],
    .form-group input[type="number"],
    .form-group input[type="date"],
    .form-group select,
    .input-with-symbol {
        width: 100%;
        max-width: 400px;
    }

    /* Terms and Conditions Styles - Aligned left */
    .form-group.terms {
        display: flex;
        align-items: center;
        margin: 1.5rem 0; /* Changed from auto to 0 */
        max-width: 400px; /* Match form field width */
        gap: 8px;
        justify-content: flex-start; /* Align to left */
    }

    .form-group.terms input[type="checkbox"] {
        margin: 0;
        width: 16px;
        height: 16px;
        cursor: pointer;
        flex-shrink: 0; /* Prevent checkbox from shrinking */
    }

    .form-group.terms label {
        display: inline-flex;
        align-items: center;
        font-size: 14px;
        line-height: 1.5;
        color: #4a4a4a;
    }

    .form-group.terms a {
        color: #000080; /* Navy */
        text-decoration: underline;
        font-weight: 500;
        transition: color 0.2s ease;
        margin: 0 4px;
    }

    .form-group.terms a:hover {
        color: #000066;
    }

    .form-group.terms .required {
        color: #dc3545;
        margin-left: 4px;
    }

    /* Ensure proper sizing of the input inside input-with-symbol */
    .input-with-symbol input[type="number"] {
        width: 100%;
        max-width: none;
    }

    /* Optional: Add focus state */
    .input-with-symbol input:focus {
        border-color: #1c658c;
        box-shadow: 0 0 0 2px rgba(28, 101, 140, 0.1);
    }

    /* Ensure proper display on mobile */
    @media screen and (max-width: 768px) {
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group select,
        .input-with-symbol,
        .form-group.terms {
            max-width: 100%;
        }
        
        .form-group.terms {
            margin: 1rem 0;
        }

        .form-group.terms label {
            font-size: 13px;
        }
    }

        /* Required Indicator Inside Input Boxes */
        .required-indicator {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: red;
            font-weight: bold;
            pointer-events: none;
        }

        .form-group {
            position: relative;
        }

        /* Prevent horizontal scroll for dynamic input wrappers */
        .dynamic-input-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .dynamic-input-wrapper input {
            flex: 1;
            margin-right: 0.5rem;
        }

       
    </style>

    <!-- JavaScript for Dynamic Form Handling and Modal -->
    <script>
        // Function to handle query type change and show/hide relevant sections
        function handleQueryTypeChange() {
            const queryTypeSelect = document.getElementById("query_type");
            const selectedOption = queryTypeSelect.options[queryTypeSelect.selectedIndex];
            const queryType = selectedOption.text.trim();

            console.log("Selected Query Type:", queryType);

            // Hide all sections first
            hideAndRemoveRequired('unique-codes-section');
            hideAndRemoveRequired('scenario-section');
            hideAndRemoveRequired('partial-payment-section');
            hideAndRemoveRequired('pmb-section');

            // Show and enable the relevant section based on the selected query type
            if (queryType === "Unique Clinical Codes") {
                showAndAddRequired('unique-codes-section');
            } else if (queryType === "Scenarios") {
                showAndAddRequired('scenario-section');
            } else if (queryType === "PMB Query") {
                showAndAddRequired('pmb-section');
            } else if (queryType === "Non Payment" || queryType === "Partial Payment") {
                showAndAddRequired('partial-payment-section');
            }
        }

        // Helper function to hide a section and remove 'required' attributes
        function hideAndRemoveRequired(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                section.style.display = 'none';
                const inputFields = section.querySelectorAll('input, select, textarea');
                inputFields.forEach(field => {
                    field.disabled = true;
                    if (field.hasAttribute('data-required')) {
                        field.removeAttribute('required');
                    }
                });
            }
        }

        // Helper function to show a section and add 'required' attributes
        function showAndAddRequired(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                section.style.display = 'block';
                const inputFields = section.querySelectorAll('input, select, textarea');
                inputFields.forEach(field => {
                    field.disabled = false;
                    if (field.hasAttribute('data-required')) {
                        field.required = true;
                    }
                });
            }
        }

        // Function to add more input fields dynamically
        function addMoreFields(containerId, type) {
            const container = document.getElementById(containerId);

            // Create a wrapper div for the input and remove button
            const fieldWrapper = document.createElement('div');
            fieldWrapper.className = 'dynamic-input-wrapper';

            // Create input
            const input = document.createElement('input');
            input.type = 'text';
            input.name = type === 'diagnosis' ? 'additional_diagnosis_codes[]' : 'additional_procedure_codes[]';
            input.placeholder = type === 'diagnosis' ? 'Enter diagnosis code (e.g., "A123")' : 'Enter procedure code (4-6 digits)';
            input.required = false; // Initially not required
            input.setAttribute('pattern', type === 'diagnosis' ? '^(?=.*\\d)[A-Z][A-Z0-9]{2,4}$' : '^\\d{4,6}$');
            input.setAttribute('maxlength', type === 'diagnosis' ? '5' : '6');

            // Create remove button
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'remove-field-btn';
            removeButton.innerText = 'Remove';
            removeButton.addEventListener('click', function() {
                container.removeChild(fieldWrapper);
            });

            // Create error message div
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.style.display = 'none';
            errorDiv.innerText = type === 'diagnosis' ?
                'Invalid diagnosis code format. Must be 3-5 alphanumeric characters, starting with a letter(UPPERCASE) and contain at least one number.' :
                'Invalid procedure code format. Must be 4-6 digits only.';

            // Append input and remove button to fieldWrapper
            fieldWrapper.appendChild(input);
            fieldWrapper.appendChild(removeButton);
            fieldWrapper.appendChild(errorDiv);

            // Append fieldWrapper to container
            container.appendChild(fieldWrapper);
        }

        // Function to remove field
        function removeField(button) {
            const fieldWrapper = button.parentNode;
            fieldWrapper.parentNode.removeChild(fieldWrapper);
        }

        // Function to trigger the file input when the upload box is clicked
        function triggerFileUpload() {
            document.getElementById('file-upload').click();
        }

        // Function definitions moved outside to make them globally accessible
        function openRequirementsModal() {
            const modal = document.getElementById("documentRequirementsModal");
            if (modal) {
                modal.style.display = "flex";
                setTimeout(() => {
                    modal.classList.add("show");
                }, 10); // Slight delay to allow CSS transitions
            }
        }

        function closeRequirementsModal() {
            const modal = document.getElementById("documentRequirementsModal");
            modal.classList.remove("show");
            setTimeout(() => {
                modal.style.display = "none";
            }, 300);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const fileInput = document.getElementById('file-upload');
            const uploadArea = document.getElementById('upload-area');
            const progressBar = document.getElementById('upload-progress');
            const fileNameDisplay = document.getElementById('file-name-display');
            const browseFilesLink = document.getElementById('browse-files');
            const documentRequirementsLink = document.getElementById('document-requirements-link');

            // Prevent default scrolling when clicking 'Browse'
            browseFilesLink.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent default anchor behavior
                fileInput.click(); // Trigger file input click
            });

            // Make the entire upload area clickable to trigger file upload
            uploadArea.addEventListener('click', function(event) {
                // Prevent triggering when clicking on links inside upload area
                if (event.target.tagName.toLowerCase() !== 'a') {
                    fileInput.click();
                }
            });

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
                        progressBar.hidden = true; // Hide the progress bar after completion
                    }
                }, 200); // Adjust the interval timing (200ms) for smooth progress bar simulation
            }

            // Client-side form validation before submission
            const form = document.getElementById('clinical-code-form');
            const fileInputElement = document.getElementById('file-upload');

            // Handle form submission
            if (form) {
                form.addEventListener('submit', function(event) {

                    let isValid = true;
                    clearErrorMessages();

                    // Basic Validations
                    isValid = validateBasicFields();

                    // Query Type Specific Validations
                    const selectedQueryType = document.getElementById('query_type').options[document.getElementById('query_type').selectedIndex].text.trim();

                    switch(selectedQueryType) {
                        case "PMB Query":
                            isValid = validatePMBFields() && isValid;
                            break;
                        case "Scenarios":
                            isValid = validateScenarioFields() && isValid;
                            break;
                        case "Non Payment":
                        case "Partial Payment":
                            isValid = validatePartialPaymentFields() && isValid;
                            break;
                        case "Unique Clinical Codes":
                            isValid = validateUniqueClinicalCodes() && isValid;
                            break;
                    }

                    // File upload validation
                    const maxFileSize = 5 * 1024 * 1024; // 5MB
                    if (!fileInputElement.files || fileInputElement.files.length === 0) {
                        showError('file-upload-error', 'Please upload a supporting document.');
                        isValid = false;
                    } else if (fileInputElement.files[0].size > maxFileSize) {
                        showError('file-upload-error', 'Uploaded file exceeds the maximum allowed size of 5MB.');
                        isValid = false;
                    }

                    // Check if terms are agreed
                    const agreeTerms = document.getElementById('agree_terms');
                    if (!agreeTerms.checked) {
                        showError('agree_terms_error', 'You must agree to the Terms of Service and Privacy Policy.');
                        isValid = false;
                    }

                    if (isValid) {
                        // Allow form submission
                        return true;
                    } else {
                        // Prevent form submission
                        event.preventDefault();
                        // Scroll to the first error
                        const firstError = document.querySelector('.error-message[style*="display: block"]');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        return false;
                    }
                });
            }

            // Document Requirements Modal Handling
            documentRequirementsLink.addEventListener('click', function(event) {
                event.preventDefault();
                openRequirementsModal();
            });

            // Close modal when clicking outside of it
            document.getElementById("documentRequirementsModal").addEventListener("click", function(event) {
                if (event.target === this) {
                    closeRequirementsModal();
                }
            });

            // Close modal with Escape key
            document.addEventListener("keydown", function(event) {
                if (event.key === "Escape" && document.getElementById("documentRequirementsModal").classList.contains("show")) {
                    closeRequirementsModal();
                }
            });

        });

        // Validate basic fields that are always required
        function validateBasicFields() {
            let isValid = true;

            // Query Type
            const queryType = document.getElementById('query_type').value;
            if (!queryType) {
                showError('query_type_error', 'Please select a query type.');
                isValid = false;
            }

            // Diagnosis Code
            const diagnosisCode = document.getElementById('diagnosis_code').value.trim();
            const diagnosisPattern = /^(?=.*\d)[A-Z][A-Z0-9]{2,4}$/;
            if (!diagnosisCode || !diagnosisPattern.test(diagnosisCode)) {
                showError('diagnosis_code_error', 'Invalid diagnosis code format. Must be 3-5 alphanumeric characters, starting with a letter(UPPERCASE) and contain at least one number.');
                isValid = false;
            }

            // Procedure Code
            const procedureCode = document.getElementById('procedure_code').value.trim();
            const procedurePattern = /^\d{4,6}$/;
            if (!procedureCode || !procedurePattern.test(procedureCode)) {
                showError('procedure_code_error', 'Procedure code must be 4-6 digits only.');
                isValid = false;
            }

            // Description
            const description = document.getElementById('description').value.trim();
            if (!description) {
                showError('description_error', 'Please provide a query description.');
                isValid = false;
            }

            return isValid;
        }

        // Validate PMB fields
        function validatePMBFields() {
            let isValid = true;

            // Medical Aid Scheme Name
            const schemeName = document.getElementById('pmb_medical_aid_scheme_name').value.trim();
            const namePattern = /^[A-Za-z\s]+$/;
            if (!schemeName) {
                showError('pmb_medical_aid_scheme_name_error', 'Please enter Medical Aid Scheme Name.');
                isValid = false;
            } else if (!namePattern.test(schemeName)) {
                showError('pmb_medical_aid_scheme_name_error', 'Medical Aid Scheme Name must contain letters and spaces only.');
                isValid = false;
            }

            // Medical Aid Scheme Plan
            const schemePlan = document.getElementById('pmb_medical_aid_scheme_plan').value.trim();
            if (!schemePlan) {
                showError('pmb_medical_aid_scheme_plan_error', 'Please enter Medical Aid Scheme Plan.');
                isValid = false;
            } else if (!namePattern.test(schemePlan)) {
                showError('pmb_medical_aid_scheme_plan_error', 'Medical Aid Scheme Plan must contain letters and spaces only.');
                isValid = false;
            }

            // PMB Condition
            const pmbCondition = document.getElementById('pmb_condition').value;
            if (!pmbCondition) {
                showError('pmb_condition_error', 'Please select a PMB condition.');
                isValid = false;
            }

            return isValid;
        }

        // Validate Scenario fields
        function validateScenarioFields() {
            let isValid = true;

            // Medical Aid Scheme Name
            const schemeName = document.getElementById('scenario_medical_aid_scheme_name').value.trim();
            const namePattern = /^[A-Za-z\s]+$/;
            if (!schemeName) {
                showError('scenario_medical_aid_scheme_name_error', 'Please enter Medical Aid Scheme Name.');
                isValid = false;
            } else if (!namePattern.test(schemeName)) {
                showError('scenario_medical_aid_scheme_name_error', 'Medical Aid Scheme Name must contain letters and spaces only.');
                isValid = false;
            }

            // Medical Aid Scheme Plan
            const schemePlan = document.getElementById('scenario_medical_aid_scheme_plan').value.trim();
            if (!schemePlan) {
                showError('scenario_medical_aid_scheme_plan_error', 'Please enter Medical Aid Scheme Plan.');
                isValid = false;
            } else if (!namePattern.test(schemePlan)) {
                showError('scenario_medical_aid_scheme_plan_error', 'Medical Aid Scheme Plan must contain letters and spaces only.');
                isValid = false;
            }

            // Claim Reference Number
            const claimRef = document.getElementById('scenario_claim_reference_number').value.trim();
            const claimPattern = /^[A-Za-z0-9]{1,10}$/;
            if (!claimRef) {
                showError('scenario_claim_reference_number_error', 'Please enter Claim Reference Number.');
                isValid = false;
            } else if (!claimPattern.test(claimRef)) {
                showError('scenario_claim_reference_number_error', 'Scenario Claim Reference Number must be alphanumeric and contain 1-10 characters.');
                isValid = false;
            }

            // Reason for Rejection
            const reason = document.getElementById('scenario_reason_of_rejection').value.trim();
            if (!reason) {
                showError('scenario_reason_of_rejection_error', 'Please enter the reason for rejection.');
                isValid = false;
            }

            return isValid;
        }

        // Validate Non/Partial Payment fields
        function validatePartialPaymentFields() {
            let isValid = true;

            // Claim Reference Number
            const claimRef = document.getElementById('claim_reference_number').value.trim();
            const claimPattern = /^[A-Za-z0-9]{1,10}$/;
            if (!claimRef) {
                showError('claim_reference_number_error', 'Please Enter claim reference number (only numbers and/or letters).');
                isValid = false;
            } else if (!claimPattern.test(claimRef)) {
                showError('claim_reference_number_error', 'Claim Reference Number must be alphanumeric and contain 1-10 characters.');
                isValid = false;
            }

            // Date of Service
            const dateOfService = document.getElementById('date_of_service').value;
            const today = '<?php echo $today; ?>';
            if (!dateOfService) {
                showError('date_of_service_error', 'Please enter Date of Service.');
                isValid = false;
            } else if (!validateDate(dateOfService)) {
                showError('date_of_service_error', 'Please enter a valid Date of Service.');
                isValid = false;
            } else if (new Date(dateOfService) > new Date(today)) {
                showError('date_of_service_error', 'Date of Service cannot be in the future.');
                isValid = false;
            }

            // Amount Paid
            const amountPaid = document.getElementById('amount_paid').value;
            if (amountPaid === '' || isNaN(amountPaid)) {
                showError('amount_paid_error', 'Please enter a valid Amount Paid.');
                isValid = false;
            } else if (amountPaid < 0) {
                showError('amount_paid_error', 'Amount Paid cannot be negative.');
                isValid = false;
            }

            // Amount Billed
            const amountBilled = document.getElementById('amount_billed').value;
            if (amountBilled === '' || isNaN(amountBilled) || amountBilled < 0) {
    showError('amount_billed_error', 'Amount Billed must be a non-negative number.');
    isValid = false;
}

            // Amount Paid does not exceed Amount Billed
            if (!isNaN(amountPaid) && !isNaN(amountBilled) && Number(amountPaid) > Number(amountBilled)) {
                showError('amount_paid_error', 'Amount Paid cannot exceed Amount Billed.');
                isValid = false;
            }

            // Medical Aid Scheme Name
            const schemeName = document.getElementById('partial_medical_aid_scheme_name').value.trim();
            const namePattern = /^[A-Za-z\s]+$/;
            if (!schemeName) {
                showError('partial_medical_aid_scheme_name_error', 'Please enter Medical Aid Scheme Name.');
                isValid = false;
            } else if (!namePattern.test(schemeName)) {
                showError('partial_medical_aid_scheme_name_error', 'Medical Aid Scheme Name must contain letters and spaces only.');
                isValid = false;
            }

            // Medical Aid Scheme Plan
            const schemePlan = document.getElementById('partial_medical_aid_scheme_plan').value.trim();
            if (!schemePlan) {
                showError('partial_medical_aid_scheme_plan_error', 'Please enter Medical Aid Scheme Plan.');
                isValid = false;
            } else if (!namePattern.test(schemePlan)) {
                showError('partial_medical_aid_scheme_plan_error', 'Medical Aid Scheme Plan must contain letters and spaces only.');
                isValid = false;
            }

            return isValid;
        }

        // Validate Unique Clinical Codes
        function validateUniqueClinicalCodes() {
            let isValid = true;

            // Additional Diagnosis Codes
            const diagnosisCodes = document.querySelectorAll('input[name="additional_diagnosis_codes[]"]');
            const diagnosisPattern = /^(?=.*\d)[A-Z][A-Z0-9]{2,4}$/;
            diagnosisCodes.forEach((input, index) => {
                const value = input.value.trim();
                if (value && !diagnosisPattern.test(value)) {
                    const errorDiv = input.parentElement.querySelector('.error-message');
                    if (errorDiv) {
                        errorDiv.style.display = 'block';
                    }
                    isValid = false;
                }
            });

            // Additional Procedure Codes
            const procedureCodes = document.querySelectorAll('input[name="additional_procedure_codes[]"]');
            const procedurePattern = /^\d{4,6}$/;
            procedureCodes.forEach((input, index) => {
                const value = input.value.trim();
                if (value && !procedurePattern.test(value)) {
                    const errorDiv = input.parentElement.querySelector('.error-message');
                    if (errorDiv) {
                        errorDiv.style.display = 'block';
                    }
                    isValid = false;
                }
            });

            return isValid;
        }

        // Helper function to show error messages
        function showError(elementId, message) {
            const errorElement = document.getElementById(elementId);
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
                // Ensure the color is red
                errorElement.style.color = '#000000';
            }
        }

        // Helper function to clear error messages
        function clearErrorMessages() {
            document.querySelectorAll('.error-message').forEach(msg => {
                msg.style.display = 'none';
            });
        }

        // Validate date function for client-side
        function validateDate(date) {
            const format = 'YYYY-MM-DD';
            const regex = /^\d{4}-\d{2}-\d{2}$/;
            if (!regex.test(date)) return false;
            const d = new Date(date);
            const dNum = d.getTime();
            if (!dNum && dNum !== 0) return false; // NaN value, Invalid date
            return d.toISOString().slice(0,10) === date;
        }
    </script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <section class="form-section">
            <h1>Clinical Code Queries</h1>
            <h2>Submit a Clinical Code Query</h2>
            <form id="clinical-code-form" method="POST" enctype="multipart/form-data" novalidate>
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                <!-- Personal Details Section -->
                <div class="section">
                    <h3>Personal Details</h3>

                    <!-- Title -->
                    <div class="form-group">
                        <label for="title">Title: <span class="required">*</span></label>
                        <select id="title" name="title" disabled>
                            <option value="Dr" <?php if(($user_data['title'] ?? '') == 'Dr') echo 'selected'; ?>>Dr</option>
                            <option value="Mr" <?php if(($user_data['title'] ?? '') == 'Mr') echo 'selected'; ?>>Mr</option>
                            <option value="Mrs" <?php if(($user_data['title'] ?? '') == 'Mrs') echo 'selected'; ?>>Mrs</option>
                            <option value="Miss" <?php if(($user_data['title'] ?? '') == 'Miss') echo 'selected'; ?>>Miss</option>
                            <option value="Other" <?php if(($user_data['title'] ?? '') == 'Other') echo 'selected'; ?>>Other</option>
                        </select>
                    </div>

                    <!-- First Name -->
                    <div class="form-group">
                        <label for="first_name">First Name: <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" placeholder="Enter your first name" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" readonly>
                    </div>

                    <!-- Last Name -->
                    <div class="form-group">
                        <label for="last_name">Last Name: <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" placeholder="Enter your last name" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" readonly>
                    </div>

                    <!-- Telephone -->
                    <div class="form-group">
                        <label for="telephone">Telephone: <span class="required">*</span></label>
                        <input type="tel" id="telephone" name="telephone" placeholder="Enter your phone number" value="<?php echo htmlspecialchars($user_data['telephone'] ?? ''); ?>" readonly>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email Address: <span class="required">*</span></label>
                        <input type="email" id="email" name="email" placeholder="Enter your email address" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" readonly>
                    </div>
                </div>

                <!-- Practice Information Section -->
                <div class="section">
                    <h3>Practice Information</h3>

                    <!-- Medical Practice Type (Read-Only) -->
                    <div class="form-group">
                        <label for="practice-type">Medical Practice Type: <span class="required">*</span></label>
                        <input type="text" id="practice-type" name="practice_type" value="<?php echo htmlspecialchars($user_data['practice_type'] ?? ''); ?>" readonly>
                    </div>

                    <!-- BHF Practice Number (Read-Only) -->
                    <div class="form-group">
                        <label for="bhf-number">BHF Practice No: <span class="required">*</span></label>
                        <input type="text" id="bhf-number" name="bhf_number" value="<?php echo htmlspecialchars($user_data['bhf_number'] ?? ''); ?>" readonly>
                    </div>
                </div>

                <!-- Query Information Section -->
                <div class="section">
                    <h3>Query Information</h3>
                    <div class="form-group">
                        <label for="query_type">Query Type: <span class="required">*</span></label>
                        <select name="query_type" id="query_type" required onchange="handleQueryTypeChange()">
                            <option value="">Select Query Type</option>
                            <?php
                            if ($result_query_types->num_rows > 0) {
                                while($row = $result_query_types->fetch_assoc()) {
                                    // Retain selected option after form submission
                                    $selected = (isset($_POST['query_type']) && $_POST['query_type'] == $row['id']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($row['id']) . '" ' . $selected . '>' . htmlspecialchars($row['query_name']) . '</option>';
                                }
                            } else {
                                echo '<option value="">No query types available</option>';
                            }
                            ?>
                        </select>
                        <?php if (isset($errors['query_type'])): ?>
                            <div id="query_type_error" class="error-message" style="display: block;"><?php echo htmlspecialchars($errors['query_type']); ?></div>
                        <?php else: ?>
                            <div id="query_type_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>

                    <!-- Diagnosis Code Input -->
                    <div class="form-group">
                        <label for="diagnosis_code">Diagnosis Code: <span class="required">*</span></label>
                        <input type="text" id="diagnosis_code" name="diagnosis_code" placeholder="3-5 alphanumeric, start with Letter(UPPERCASE) e.g.,'A123'" pattern="^(?=.*\d)[A-Z][A-Z0-9]{2,4}$" maxlength="5" required value="<?php echo htmlspecialchars($_POST['diagnosis_code'] ?? ''); ?>">
                        <?php if (isset($errors['diagnosis_code'])): ?>
                            <div id="diagnosis_code_error" class="error-message" style="display: block;"><?php echo htmlspecialchars($errors['diagnosis_code']); ?></div>
                        <?php else: ?>
                            <div id="diagnosis_code_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="procedure_code">Procedure Code: <span class="required">*</span></label>
                        <input type="text" id="procedure_code" name="procedure_code" placeholder="Enter a 4-6 digit procedure code" pattern="^\d{4,6}$" maxlength="6" required value="<?php echo htmlspecialchars($_POST['procedure_code'] ?? ''); ?>">
                        <?php if (isset($errors['procedure_code'])): ?>
                            <div id="procedure_code_error" class="error-message" style="display: block;"><?php echo htmlspecialchars($errors['procedure_code']); ?></div>
                        <?php else: ?>
                            <div id="procedure_code_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="description">Query Description: <span class="required">*</span></label>
                        <textarea id="description" name="description" placeholder="Enter query description" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div id="description_error" class="error-message" style="display: block;"><?php echo htmlspecialchars($errors['description']); ?></div>
                        <?php else: ?>
                            <div id="description_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- PMB Query Section -->
                <div id="pmb-section" class="section" style="display:none;">
                    <h3>PMB Query Details</h3>
                    <div class="form-group">
                        <label for="pmb_medical_aid_scheme_name">Medical Aid Scheme Name: <span class="required">*</span></label>
                        <input type="text" id="pmb_medical_aid_scheme_name" name="pmb_medical_aid_scheme_name" placeholder="Enter medical aid scheme name" data-required="true" value="<?php echo htmlspecialchars($_POST['pmb_medical_aid_scheme_name'] ?? ''); ?>">
                        <?php if (isset($errors['pmb_medical_aid_scheme_name'])): ?>
                            <div id="pmb_medical_aid_scheme_name_error" class="error-message" style="display: block;">
                                <?php echo htmlspecialchars($errors['pmb_medical_aid_scheme_name']); ?>
                            </div>
                        <?php else: ?>
                            <div id="pmb_medical_aid_scheme_name_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="pmb_medical_aid_scheme_plan">Medical Aid Scheme Plan: <span class="required">*</span></label>
                        <input type="text" id="pmb_medical_aid_scheme_plan" name="pmb_medical_aid_scheme_plan" placeholder="Enter medical aid scheme plan" data-required="true" value="<?php echo htmlspecialchars($_POST['pmb_medical_aid_scheme_plan'] ?? ''); ?>">
                        <?php if (isset($errors['pmb_medical_aid_scheme_plan'])): ?>
                            <div id="pmb_medical_aid_scheme_plan_error" class="error-message" style="display: block;">
                                <?php echo htmlspecialchars($errors['pmb_medical_aid_scheme_plan']); ?>
                            </div>
                        <?php else: ?>
                            <div id="pmb_medical_aid_scheme_plan_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="pmb_condition">PMB Condition: <span class="required">*</span></label>
                        <select id="pmb_condition" name="pmb_condition" data-required="true">
                            <option value="">Select PMB condition</option>
                            <?php
                            if ($result_pmb->num_rows > 0) {
                                while ($row = $result_pmb->fetch_assoc()) {
                                    // Retain selected option after form submission
                                    $selected = (isset($_POST['pmb_condition']) && $_POST['pmb_condition'] == $row['pmb_condition']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row['pmb_condition']) . "' " . $selected . ">" . htmlspecialchars($row['pmb_condition']) . "</option>";
                                }
                            } else {
                                echo "<option value=''>No PMB conditions found</option>";
                            }
                            ?>
                        </select>
                        <?php if (isset($errors['pmb_condition'])): ?>
                            <div id="pmb_condition_error" class="error-message" style="display: block;">
                                <?php echo htmlspecialchars($errors['pmb_condition']); ?>
                            </div>
                        <?php else: ?>
                            <div id="pmb_condition_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Non Payment & Partial Payment Section -->
                <div id="partial-payment-section" style="display:none;">
                    <h3>Non Payment or Partial Payment Details</h3>
                    <div class="form-group">
                        <label for="claim_reference_number">Claim Reference Number: <span class="required">*</span></label>
                        <input type="text" id="claim_reference_number" name="claim_reference_number" placeholder="Enter claim reference number(only numbers and or letters)" pattern="^[A-Za-z0-9]{1,10}$" maxlength="10" data-required="true" required value="<?php echo htmlspecialchars($_POST['claim_reference_number'] ?? ''); ?>">
                        <?php if (isset($errors['claim_reference_number'])): ?>
                            <div id="claim_reference_number_error" class="error-message" style="display: block;">
                                <?php echo htmlspecialchars($errors['claim_reference_number']); ?>
                            </div>
                        <?php else: ?>
                            <div id="claim_reference_number_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="date_of_service">Date of Service: <span class="required">*</span></label>
                        <input type="date" id="date_of_service" name="date_of_service" data-required="true" required max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($_POST['date_of_service'] ?? ''); ?>">
                        <?php if (isset($errors['date_of_service'])): ?>
                            <div id="date_of_service_error" class="error-message" style="display: block;">
                                <?php echo htmlspecialchars($errors['date_of_service']); ?>
                            </div>
                        <?php else: ?>
                            <div id="date_of_service_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="amount_paid">Amount Paid: <span class="required">*</span></label>
                        <div class="input-with-symbol">
                            <span class="currency-symbol">R</span>
                            <input type="number" id="amount_paid" name="amount_paid" placeholder="Enter amount paid" step="0.01" min="0" required value="<?php echo htmlspecialchars($_POST['amount_paid'] ?? ''); ?>">
                        </div>
                        <?php if (isset($errors['amount_paid'])): ?>
                            <div id="amount_paid_error" class="error-message" style="display: block;">
                                <?php echo htmlspecialchars($errors['amount_paid']); ?>
                            </div>
                        <?php else: ?>
                            <div id="amount_paid_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="amount_billed">Amount Billed: <span class="required">*</span></label>
                        <div class="input-with-symbol">
                            <span class="currency-symbol">R</span>
                            <input type="number" id="amount_billed" name="amount_billed" placeholder="Enter amount billed" step="0.01" min="0.01" required value="<?php echo htmlspecialchars($_POST['amount_billed'] ?? ''); ?>">
                        </div>
                        <?php if (isset($errors['amount_billed'])): ?>
                            <div id="amount_billed_error" class="error-message" style="display: block;">
                                <?php echo htmlspecialchars($errors['amount_billed']); ?>
                            </div>
                        <?php else: ?>
                            <div id="amount_billed_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>

                    <!-- Removed 'Reason for Partial Payment or Non-Payment' field -->

                    <div class="form-group">
                        <label for="partial_medical_aid_scheme_name">Medical Aid Scheme Name: <span class="required">*</span></label>
                        <input type="text" id="partial_medical_aid_scheme_name" name="partial_medical_aid_scheme_name" placeholder="Enter medical aid scheme name" data-required="true" value="<?php echo htmlspecialchars($_POST['partial_medical_aid_scheme_name'] ?? ''); ?>">
                        <?php if (isset($errors['partial_medical_aid_scheme_name'])): ?>
                            <div id="partial_medical_aid_scheme_name_error" class="error-message" style="display: block;">
                                <?php echo htmlspecialchars($errors['partial_medical_aid_scheme_name']); ?>
                            </div>
                        <?php else: ?>
                            <div id="partial_medical_aid_scheme_name_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="partial_medical_aid_scheme_plan">Medical Aid Scheme Plan: <span class="required">*</span></label>
                        <input type="text" id="partial_medical_aid_scheme_plan" name="partial_medical_aid_scheme_plan" placeholder="Enter medical aid scheme plan" data-required="true" value="<?php echo htmlspecialchars($_POST['partial_medical_aid_scheme_plan'] ?? ''); ?>">
                        <?php if (isset($errors['partial_medical_aid_scheme_plan'])): ?>
                            <div id="partial_medical_aid_scheme_plan_error" class="error-message" style="display: block;">
                                <?php echo htmlspecialchars($errors['partial_medical_aid_scheme_plan']); ?>
                            </div>
                        <?php else: ?>
                            <div id="partial_medical_aid_scheme_plan_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>
                </div> <!-- Closing Non Payment & Partial Payment Section -->

                


                <!-- Scenario Section -->
                <div id="scenario-section" style="display:none;">
                    <h3>Scenarios</h3>
                    <div class="form-group">
                        <label for="scenario_medical_aid_scheme_name">Medical Aid Scheme Name: <span class="required">*</span></label>
                        <input type="text" id="scenario_medical_aid_scheme_name" name="scenario_medical_aid_scheme_name" placeholder="Enter medical aid scheme name" data-required="true" required value="<?php echo htmlspecialchars($_POST['scenario_medical_aid_scheme_name'] ?? ''); ?>">
                        <?php if (isset($errors['scenario_medical_aid_scheme_name'])): ?>
                            <div id="scenario_medical_aid_scheme_name_error" class="error-message" style="display: block;">
                                <?php echo htmlspecialchars($errors['scenario_medical_aid_scheme_name']); ?>
                            </div>
                        <?php else: ?>
                            <div id="scenario_medical_aid_scheme_name_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="scenario_medical_aid_scheme_plan">Medical Aid Scheme Plan: <span class="required">*</span></label>
                        <input type="text" id="scenario_medical_aid_scheme_plan" name="scenario_medical_aid_scheme_plan" placeholder="Enter medical aid scheme plan" data-required="true" required value="<?php echo htmlspecialchars($_POST['scenario_medical_aid_scheme_plan'] ?? ''); ?>">
                        <?php if (isset($errors['scenario_medical_aid_scheme_plan'])): ?>
                            <div id="scenario_medical_aid_scheme_plan_error" class="error-message" style="display: block;">
                                <?php echo htmlspecialchars($errors['scenario_medical_aid_scheme_plan']); ?>
                            </div>
                        <?php else: ?>
                            <div id="scenario_medical_aid_scheme_plan_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="scenario_claim_reference_number">Claim Reference Number: <span class="required">*</span></label>
                        <input type="text" id="scenario_claim_reference_number" name="scenario_claim_reference_number" placeholder="Enter claim reference number(only numbers and or letters)" pattern="^[A-Za-z0-9]{1,10}$" maxlength="10" data-required="true" required value="<?php echo htmlspecialchars($_POST['scenario_claim_reference_number'] ?? ''); ?>">
                        <?php if (isset($errors['scenario_claim_reference_number'])): ?>
                            <div id="scenario_claim_reference_number_error" class="error-message" style="display: block;">
                                <?php echo htmlspecialchars($errors['scenario_claim_reference_number']); ?>
                            </div>
                        <?php else: ?>
                            <div id="scenario_claim_reference_number_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="scenario_reason_of_rejection">Reason for Rejection: <span class="required">*</span></label>
                        <textarea id="scenario_reason_of_rejection" name="scenario_reason_of_rejection" placeholder="Enter reason for rejection" data-required="true" required><?php echo htmlspecialchars($_POST['scenario_reason_of_rejection'] ?? ''); ?></textarea>
                        <?php if (isset($errors['scenario_reason_of_rejection'])): ?>
                            <div id="scenario_reason_of_rejection_error" class="error-message" style="display: block;">
                                <?php echo htmlspecialchars($errors['scenario_reason_of_rejection']); ?>
                            </div>
                        <?php else: ?>
                            <div id="scenario_reason_of_rejection_error" class="error-message"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Unique Clinical Codes Section -->
                <div id="unique-codes-section" style="display:none;">
                    <h3>Unique Clinical Codes</h3>

                    <!-- Diagnosis Codes Container -->
                    <div id="diagnosis-codes-container">
                        <!-- Initial Diagnosis Code Input -->
                        <div class="dynamic-input-wrapper">
                            <label for="additional_diagnosis_code">Additional Diagnosis Code:</label>
                            <input type="text" id="additional_diagnosis_code" name="additional_diagnosis_codes[]" placeholder="3-5 alphanumeric, start with Letter(UPPERCASE) e.g.,'A123'"  pattern="^(?=.*\\d)[A-Z][A-Z0-9]{2,4}$" maxlength="5" value="<?php echo htmlspecialchars($_POST['additional_diagnosis_codes'][0] ?? ''); ?>">
                            <button type="button" class="remove-field-btn" onclick="removeField(this)">Remove</button>
                            <?php if (isset($errors['additional_diagnosis_codes'][0])): ?>
                                <div class="error-message" style="display:block;">
                                    <?php echo htmlspecialchars($errors['additional_diagnosis_codes'][0]); ?>
                                </div>
                            <?php else: ?>
                                <div class="error-message" style="display:none;"></div>
                            <?php endif; ?>
                        </div>

                        <!-- Dynamically Added Diagnosis Codes -->
                        <div id="more-diagnosis-codes">
                            <?php
                            // Populate additional diagnosis codes if any
                            if (isset($_POST['additional_diagnosis_codes'])) {
                                foreach ($_POST['additional_diagnosis_codes'] as $index => $code) {
                                    if ($index === 0) continue; // Skip the first one as it's already rendered
                                    if (trim($code) !== '') {
                                        $errorMsg = isset($errors['additional_diagnosis_codes'][$index]) ? htmlspecialchars($errors['additional_diagnosis_codes'][$index]) : '';
                                        $displayStyle = $errorMsg ? 'block' : 'none';
                                        echo '<div class="dynamic-input-wrapper">';
                                        echo '<input type="text" name="additional_diagnosis_codes[]" placeholder="Enter diagnosis code (e.g., \'B14X\')" pattern="^(?=.*\\d)[A-Z][A-Z0-9]{2,4}$" maxlength="5" value="' . htmlspecialchars($code) . '">';
                                        echo '<button type="button" class="remove-field-btn" onclick="removeField(this)">Remove</button>';
                                        echo '<div class="error-message" style="display:' . $displayStyle . ';">' . $errorMsg . '</div>';
                                        echo '</div>';
                                    }
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <button type="button" class="add-more-btn" onclick="addMoreFields('more-diagnosis-codes', 'diagnosis')">Add more Diagnosis Codes</button>

                    <!-- Procedure Codes Container -->
                    <div id="procedure-codes-container">
                        <!-- Initial Procedure Code Input -->
                        <div class="dynamic-input-wrapper">
                            <label for="additional_procedure_code">Additional Procedure Code:</label>
                            <input type="text" id="additional_procedure_code" name="additional_procedure_codes[]" placeholder="Enter procedure code (4-6 digits)" pattern="^\\d{4,6}$" maxlength="6" value="<?php echo htmlspecialchars($_POST['additional_procedure_codes'][0] ?? ''); ?>">
                            <button type="button" class="remove-field-btn" onclick="removeField(this)">Remove</button>
                            <?php if (isset($errors['additional_procedure_codes'][0])): ?>
                                <div class="error-message" style="display:block;">
                                    <?php echo htmlspecialchars($errors['additional_procedure_codes'][0]); ?>
                                </div>
                            <?php else: ?>
                                <div class="error-message" style="display:none;"></div>
                            <?php endif; ?>
                        </div>

                        <!-- Dynamically Added Procedure Codes -->
                        <div id="more-procedure-codes">
                            <?php
                            // Populate additional procedure codes if any
                            if (isset($_POST['additional_procedure_codes'])) {
                                foreach ($_POST['additional_procedure_codes'] as $index => $code) {
                                    if ($index === 0) continue; // Skip the first one as it's already rendered
                                    if (trim($code) !== '') {
                                        $errorMsg = isset($errors['additional_procedure_codes'][$index]) ? htmlspecialchars($errors['additional_procedure_codes'][$index]) : '';
                                        $displayStyle = $errorMsg ? 'block' : 'none';
                                        echo '<div class="dynamic-input-wrapper">';
                                        echo '<input type="text" name="additional_procedure_codes[]" placeholder="Enter procedure code (4-6 digits)" pattern="^\\d{4,6}$" maxlength="6" value="' . htmlspecialchars($code) . '">';
                                        echo '<button type="button" class="remove-field-btn" onclick="removeField(this)">Remove</button>';
                                        echo '<div class="error-message" style="display:' . $displayStyle . ';">' . $errorMsg . '</div>';
                                        echo '</div>';
                                    }
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <button type="button" class="add-more-btn" onclick="addMoreFields('more-procedure-codes', 'procedure')">Add more Procedure Codes</button>
                </div>

                <!-- File Upload Section -->
                <div class="upload-section">
                    <label for="file-upload" class="upload-title">Upload Supporting Documents (JPG, JPEG, PNG, PDF) <span class="required">*</span></label>
                    <div class="upload-box" id="upload-area">
                        <p>Upload</p>
                        <div class="upload-icon">
                            <img src="images/upload-icon.png" alt="Upload Icon">
                        </div>
                        <p>Drag & drop files or <a href="#" id="browse-files">Browse</a></p>
                        <p class="supported-formats">Supported formats: JPEG, PNG, PDF <br> <a href="#" id="document-requirements-link">What documents are required?</a></p>
                    </div>

                    <input type="file" name="file_upload[]" id="file-upload" accept=".jpg, .jpeg, .png, .pdf" multiple hidden required>
                    <progress id="upload-progress" value="0" max="100" hidden></progress>

                    <!-- Display the uploaded file name -->
                    <p id="file-name-display"><?php echo htmlspecialchars($_FILES['file_upload']['name'][0] ?? ''); ?></p>

                    <!-- Error message for file upload -->
                    <?php if (isset($errors['file_upload'])): ?>
                        <div id="file-upload-error" class="error-message" style="display: block;">
                            <?php
                                if (is_array($errors['file_upload'])) {
                                    foreach ($errors['file_upload'] as $fileError) {
                                        echo htmlspecialchars($fileError) . '<br>';
                                    }
                                } else {
                                    echo htmlspecialchars($errors['file_upload']);
                                }
                            ?>
                        </div>
                    <?php else: ?>
                        <div id="file-upload-error" class="error-message"></div>
                    <?php endif; ?>
                </div>

                <!-- Terms and Conditions -->
                <div class="form-group terms">
                    <input type="checkbox" id="agree_terms" name="agree_terms">
                    <label for="agree_terms">I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a> <span class="required">*</span></label>
                </div>
                <?php if (isset($errors['agree_terms'])): ?>
                    <div id="agree_terms_error" class="error-message" style="display: block;"><?php echo htmlspecialchars($errors['agree_terms']); ?></div>
                <?php else: ?>
                    <div id="agree_terms_error" class="error-message"></div>
                <?php endif; ?>

                <!-- Submit Button -->
                <button type="submit" class="submit-btn">Submit Query</button>

                <!-- Display general server-side errors if any -->
                <?php
                if (isset($errors['general'])) {
                    echo "<div class='error-message' style='display:block;'>";
                    echo "<p>" . htmlspecialchars($errors['general']) . "</p>";
                    echo "</div>";
                }
                ?>
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

    <!-- Document Requirements Modal -->
    <div id="documentRequirementsModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeRequirementsModal()" aria-label="Close modal">&times;</button>
            <h3>Required Documents</h3>
            <p>Please upload the following documents based on your selected query type:</p>
            <ul style="text-align: left;">
                <li><strong>Unique Clinical Codes:</strong> Medical reports, clinical notes, or other documentation supporting the need for unique clinical codes.</li>
                <li><strong>Scenarios:</strong> Claim rejection letters, communication with insurers, or other relevant documentation.</li>
                <li><strong>PMB Query:</strong> Patient\'s medical records, evidence of PMB conditions, communication with medical aid schemes.</li>
                <li><strong>Non Payment / Partial Payment:</strong> Claim submissions, Explanation of Benefits (EOBs), remittance advice, correspondence with the medical aid scheme.</li>
            </ul>
            <button class="btn" onclick="closeRequirementsModal()">Close</button>
        </div>
    </div>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
