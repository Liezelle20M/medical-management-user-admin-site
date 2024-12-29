<?php
// Include necessary files
require 'includes/db_connection.php';
require 'mailer.php';

$errors = []; // Array to collect errors
$uploadDir = 'uploads/'; // Directory to store uploaded files

// Check if form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p>Form submission received.</p>";

    // Retrieve and sanitize input data
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $invoice = filter_input(INPUT_POST, 'invoice', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

    // Display the sanitized inputs for debugging
    echo "<p>Name: $name</p>";
    echo "<p>Invoice: $invoice</p>";
    echo "<p>Email: $email</p>";
    echo "<p>Description: $description</p>";

    // Validate required fields and email format
    if (!$name || !$invoice || !$email || !$description) {
        $errors[] = "All fields are required, and email must be valid.";
    }

    // Handle file upload for proof of payment
    $proofFilePath = '';
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
        $proofFilePath = $uploadDir . basename($_FILES['proof']['name']);
        if (!move_uploaded_file($_FILES['proof']['tmp_name'], $proofFilePath)) {
            $errors[] = "Error uploading proof of payment file.";
        }
    } else {
        $errors[] = "Proof of payment file is required.";
    }

    // Handle multiple supporting documents upload (if any)
    $supportingDocs = [];
    if (isset($_FILES['supporting_docs']) && count($_FILES['supporting_docs']['tmp_name']) > 0) {
        foreach ($_FILES['supporting_docs']['tmp_name'] as $key => $tmpName) {
            if (!empty($tmpName)) {
                $filePath = $uploadDir . basename($_FILES['supporting_docs']['name'][$key]);
                if (move_uploaded_file($tmpName, $filePath)) {
                    $supportingDocs[] = $filePath;
                } else {
                    $errors[] = "Error uploading file: " . $_FILES['supporting_docs']['name'][$key];
                }
            }
        }
    }

    // Proceed if no errors
    if (empty($errors)) {
        echo "<p>No errors, proceeding with database insertion.</p>";
        
        // Prepare data for database insertion
        $stmt = $db->prepare("INSERT INTO refund_requests (name, invoice, email, description, proof_path, supporting_docs) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $supportingDocsSerialized = serialize($supportingDocs);
            $stmt->bind_param("ssssss", $name, $invoice, $email, $description, $proofFilePath, $supportingDocsSerialized);

            if ($stmt->execute()) {
                echo "<p>Database insertion successful.</p>";

                // Email details
                $subject = "New Refund Request Submitted";
                $message = "<p>A new refund request has been submitted.</p>
                            <p><strong>Name:</strong> $name</p>
                            <p><strong>Invoice:</strong> $invoice</p>
                            <p><strong>Email:</strong> $email</p>
                            <p><strong>Description:</strong> $description</p>";

                $attachments = array_merge([$proofFilePath], $supportingDocs);

                // Attempt to send email
                if (sendEmail("ambrosetshuma26.ka@gmail.com", $subject, $message, $attachments)) {
                    echo "<script>alert('Refund request submitted successfully.'); window.location.href = 'confirmation-page.html';</script>";
                } else {
                    $errors[] = "Refund submitted, but email could not be sent.";
                }
            } else {
                $errors[] = "Database insertion failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Failed to prepare the database statement: " . $db->error;
        }
    }

    // Display errors if any
    if (!empty($errors)) {
        echo "<p>Errors:</p><ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p>No POST data received.</p>";
}
?>
