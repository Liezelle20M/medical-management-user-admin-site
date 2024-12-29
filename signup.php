<?php
// Include the database connection file
require_once 'includes/db_connection.php';
require_once 'mail-config.php'; // Assuming you have a mailer file or use PHPMailer for sending emails
session_start(); // Start the session

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $phoneNumber = mysqli_real_escape_string($conn, $_POST['telephone']);
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
    $bhfNumber = mysqli_real_escape_string($conn, $_POST['BHF_number']);
    $practiceType = mysqli_real_escape_string($conn, $_POST['practice_type']);
    $fullAddress = mysqli_real_escape_string($conn, $_POST['full_address']);

    // Check if the email already exists
    $checkEmailSql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($checkEmailSql);

    if ($result->num_rows > 0) {
        // Email already exists, set the session error message
        $_SESSION['error'] = "An account with this email already exists. Please use a different email or sign in if you already have an account.";
        
        // Redirect to create.php
        header("Location: create.php");
        exit(); // Always call exit after header redirection
    } else {
        // Hash the password before saving it
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Generate a random OTP
        $otp = rand(100000, 999999);

        // Set OTP expiry time (e.g., 3 minutes from now)
        $otpExpiry = time() + (3 * 60); // Current time + 3 minutes

        // Insert into the database (including OTP and expiry time)
        $sql = "INSERT INTO users (title, first_name, last_name, email, password_hash, telephone, specialization, bhf_number, practice_type, address, OTP, otp_expiry)
                VALUES ('$title', '$firstName', '$lastName', '$email', '$passwordHash', '$phoneNumber', '$specialization', '$bhfNumber', '$practiceType', '$fullAddress', '$otp', $otpExpiry)";

        if ($conn->query($sql) === TRUE) {
        // Send OTP to user's email
        $subject = "Your OTP Code for Verification";
        $message = "Hello $firstName,\n\nYour OTP for email verification is $otp. The OTP is valid for 3 minutes.\n\nThank you!";
        
        // Send the email (use PHPMailer or mail() function)
        // Example:
        // mail($email, $subject, $message);
        sendEmail($email, $subject, $message); // Using a hypothetical sendMail function from mailer.php

        // Store the email in session to use in OTP verification page
        session_start();
        $_SESSION['otp_email'] = $email;
        $_SESSION['otp_expiry_time'] = $otpExpiry; // Storing OTP expiry in session for countdown

        // Redirect to the OTP verification page
        header("Location: verify_otp.php");
        exit(); // Always call exit after header redirection
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

        // Close the connection
        $conn->close();
    }
}
?>
