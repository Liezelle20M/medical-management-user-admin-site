<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'mail-config.php'; // Include the mailer for sending emails

// Function to generate and send new OTP
function resendOTP($email, $conn) {
    $newOTP = rand(100000, 999999); // Generate a new OTP
    $newExpiryTime = time() + (3 * 60); // Set new OTP to expire in 3 minutes

    // Update the new OTP and expiry time in the database
    $updateOTP = $conn->prepare("UPDATE users SET OTP = ?, otp_expiry = ? WHERE email = ?");
    $updateOTP->bind_param("iis", $newOTP, $newExpiryTime, $email);
    $updateOTP->execute();
    $updateOTP->close();

    // Send the new OTP via email using PHPMailer
    $subject = "Your new OTP Code for Verification";
    $message = "Hello,\n\nYour new OTP for email verification is $newOTP. It is valid for 3 minutes.\n\nThank you!";
    sendEmail($email, $subject, $message); // sendEmail function from mailer.php

    // Store new OTP expiry time in session for countdown
    $_SESSION['otp_expiry_time'] = $newExpiryTime;
}

// Handle OTP resend request
if (isset($_POST['resend_otp'])) {
    $email = $_SESSION['otp_email']; // Retrieve the email from the session
    resendOTP($email, $conn); // Call function to resend OTP
    $_SESSION['otp_success_message'] = "A new OTP has been sent to your email.";
}

// Handle OTP verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['resend_otp'])) {
    $enteredOTP = $_POST['otp']; // User entered OTP
    $email = $_SESSION['otp_email']; // Get the email from the session

    // Retrieve OTP and expiry time from the database
    $stmt = $conn->prepare("SELECT OTP, otp_expiry FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $otpFromDB = $user['OTP'];
        $otpExpiry = $user['otp_expiry'];

        // Check if the OTP has expired
        if (time() >= $otpExpiry) {
            // OTP has expired; remove it from the database
            $stmt = $conn->prepare("UPDATE users SET OTP = NULL, otp_expiry = NULL WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->close();

            // Set an error message for expired OTP
            $_SESSION['otp_error'] = "Your OTP has expired. Please request a new one.";
        } else if ($enteredOTP == $otpFromDB) {
            // OTP is correct and hasn't expired, mark email as verified
            $update = $conn->prepare("UPDATE users SET email_verified = 1 WHERE email = ?");
            $update->bind_param("s", $email);
            $update->execute();
            $update->close();

            // Set success message
            $_SESSION['verification_success'] = "Your email has been verified successfully!";
            // Redirect to the login page after successful verification
            header("Location: success.php?verified=true");
            exit();
        } else {
            // OTP is incorrect
            $_SESSION['otp_error'] = "Invalid OTP. Please try again.";
        }
    } else {
        // No user found with the provided email
        $_SESSION['otp_error'] = "No user found for this email.";
    }

 
}

// Close database connection

?>

<!-- HTML form for OTP verification -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <script>
        // Countdown function
        function startCountdown(expiryTime) {
            var countdownElement = document.getElementById('countdown');
            var resendButton = document.getElementById('resend-btn');
            var now = Math.floor(Date.now() / 1000); // Current time in seconds
            var timeLeft = expiryTime - now;

            var countdownInterval = setInterval(function () {
                if (timeLeft > 0) {
                    var minutes = Math.floor(timeLeft / 60);
                    var seconds = timeLeft % 60;
                    countdownElement.textContent = minutes + "m " + (seconds < 10 ? '0' : '') + seconds + "s";
                    timeLeft--;
                } else {
                    clearInterval(countdownInterval);
                    countdownElement.textContent = "OTP expired!";
                    resendButton.disabled = false; // Enable the resend button
                }
            }, 1000);
        }

        // Start the countdown with the OTP expiry time passed from the server
        window.onload = function () {
            var expiryTime = <?php echo $_SESSION['otp_expiry_time']; ?>; // Time in seconds
            startCountdown(expiryTime);
        };
    </script>
</head>
<body>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #b0c4de;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .error-message {
            color: red;
            font-size: 14px;
            margin-bottom: 20px;
            background-color: #f8d7da; /* Light red background */
            border: 1px solid #f5c6cb; /* Red border */
            border-radius: 4px;
            padding: 10px;
        }

        .success-message {
            color: green;
            font-size: 14px;
            margin-bottom: 20px;
            background-color: #d4edda; /* Light green background */
            border: 1px solid #c3e6cb; /* Green border */
            border-radius: 4px;
            padding: 10px;
        }
        .otp-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 700px;
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        button[type="submit"], #resend-btn {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            background-color: black;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button[type="submit"]:hover, #resend-btn:hover {
            background-color: black;
        }

        #resend-btn:disabled {
            background-color: #cccccc;
            color: #666666;
            cursor: not-allowed;
        }

        #countdown {
            font-weight: bold;
            color: #333;
        }
        h1 {
            text-align: center;
            font-size: 18px;
        }
    </style>

    <div class="otp-container">
        <h2>Verify Your OTP</h2>
        <h1>An email containing a One-Time Password (OTP) has been sent to your email address.</h1>
        <!-- Display error message if any -->
        <?php
        if (isset($_SESSION['otp_error'])) {
            echo "<p class='error-message'>" . $_SESSION['otp_error'] . "</p>";
            unset($_SESSION['otp_error']); // Clear error message after displaying
        }

        // Display success message if any
        if (isset($_SESSION['otp_success_message'])) {
            echo "<p class='success-message'>" . $_SESSION['otp_success_message'] . "</p>";
            unset($_SESSION['otp_success_message']); // Clear success message after displaying
        }
        ?>

        <form action="verify_otp.php" method="POST">
            <label for="otp">Enter OTP:</label>
            <input type="text" name="otp" id="otp" required>
            <button type="submit">Verify</button>
        </form>

        <p>OTP is valid for: <span id="countdown"></span> minutes</p>

        <!-- Resend OTP button -->
        <form action="verify_otp.php" method="POST">
            <input type="hidden" name="resend_otp" value="true">
            <button type="submit" id="resend-btn" disabled>Resend OTP</button>
        </form>
    </div>
</body>
</html>
