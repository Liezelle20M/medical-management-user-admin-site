<?php
// Includes
require 'includes/db_connection.php';
require 'mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = $_POST['name'];
    $invoice = $_POST['invoice'];
    $email = $_POST['email'];
    $description = $_POST['description'];

    // File upload handling
    $uploadDir = 'uploads/';
    $proofFilePath = $uploadDir . basename($_FILES['proof']['name']);
    $supportingDocs = [];
    $errors = [];

    // Move the proof of payment file to the uploads directory
    if (move_uploaded_file($_FILES['proof']['tmp_name'], $proofFilePath)) {
        // Upload supporting documents (if any)
        foreach ($_FILES['supporting_docs']['tmp_name'] as $key => $tmpName) {
            $filePath = $uploadDir . basename($_FILES['supporting_docs']['name'][$key]);
            if (move_uploaded_file($tmpName, $filePath)) {
                $supportingDocs[] = $filePath;
            }
        }

        // Insert into database
        $stmt = $db->prepare("INSERT INTO refund_requests (name, invoice, email, description, proof_path, supporting_docs) VALUES (?, ?, ?, ?, ?, ?)");
        $supportingDocsSerialized = serialize($supportingDocs);
        $stmt->bind_param("ssssss", $name, $invoice, $email, $description, $proofFilePath, $supportingDocsSerialized);

        if ($stmt->execute()) {
            // Send email notification
            $subject = "New Refund Request Submitted";
            $message = "<p>A new refund request has been submitted.</p>
                        <p><strong>Name:</strong> $name</p>
                        <p><strong>Invoice:</strong> $invoice</p>
                        <p><strong>Email:</strong> $email</p>
                        <p><strong>Description:</strong> $description</p>";

            $attachments = array_merge([$proofFilePath], $supportingDocs);

            if (sendEmail("ambrosetshuma26.ka@gmail.com", $subject, $message, $attachments)) {
                echo "<script>alert('Refund request submitted successfully.'); window.location.href = 'confirmation-page.html';</script>";
            } else {
                $errors[] = "Refund submitted, but email could not be sent.";
            }
        } else {
            $errors[] = "Database insertion failed.";
        }
        $stmt->close();
    } else {
        $errors[] = "File upload failed.";
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
?>
