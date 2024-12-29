<?php
// Include the database connection file and mailer
require_once 'includes/db_connection.php';
require_once 'mail-config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if the email exists in the database
    $checkEmailSql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($checkEmailSql);

    if ($result->num_rows > 0) {
        // Email exists, generate a reset code and set an expiry time (13 minutes from now)
        $resetCode = rand(100000, 999999); // Generate a 6-digit reset code
        $expiryTime = time() + (13 * 60);  // 13 minutes from current time

        // Store the reset code and expiry time in the database
        $sql = "UPDATE users SET reset_code = ?, reset_expiry = ? WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $resetCode, $expiryTime, $email);
        
        if ($stmt->execute()) {
            // Send the reset code to the user's email
            $subject = "Your Password Reset Code";
            $message = "Your password reset code is: $resetCode\n\nPlease use this code within 13 minutes to reset your password.";
            sendEmail($email, $subject, $message); // Use your email sending function

            // Redirect to the reset code verification page
            header("Location: verify_reset_code.php?email=$email");
            exit();
        } else {
            echo "Error updating reset code: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "No account found with that email address.";
    }

    // Close the connection
    $conn->close();
}
?>
