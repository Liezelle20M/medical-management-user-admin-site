<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'mail-config.php';

// Function to resend reset code
function resendResetCode($email, $conn) {
    $newResetCode = rand(100000, 999999);
    $newExpiryTime = time() + (13 * 60); // 13 minutes from now

    $stmt = $conn->prepare("UPDATE admins SET reset_code = ?, reset_expiry = ? WHERE email = ?");
    $stmt->bind_param("iis", $newResetCode, $newExpiryTime, $email);
    $stmt->execute();
    $stmt->close();

    // Send the new reset code via email
    $subject = "Your New Admin Password Reset Code";
    $message = "Your new password reset code is: $newResetCode\n\nPlease use this code within 13 minutes.";
    sendEmail($email, $subject, $message);

    // Update session expiry for countdown display
    $_SESSION['reset_expiry_time'] = $newExpiryTime;
    $_SESSION['success_message'] = "A new reset code has been sent to your email.";
}

// Handle reset code verification and resend
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if the admin requested a resend
    if (isset($_POST['resend_code'])) {
        resendResetCode($email, $conn);
    } else {
        $enteredCode = mysqli_real_escape_string($conn, $_POST['reset_code']);

        // Fetch reset code and expiry time from the database for admins
        $stmt = $conn->prepare("SELECT reset_code, reset_expiry FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            $resetCodeFromDB = $admin['reset_code'];
            $resetExpiry = $admin['reset_expiry'];

            // Verify the reset code and expiry time
            if ($enteredCode == $resetCodeFromDB && time() < $resetExpiry) {
                // Redirect to change password page
                header("Location: security_questions.php?email=$email");
                exit();
            } else {
                // Handle incorrect or expired code
                if (time() >= $resetExpiry) {
                    $stmt = $conn->prepare("UPDATE admins SET reset_code = NULL, reset_expiry = NULL WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->close();
                    $_SESSION['error_message'] = "The reset code has expired. Please request a new one.";
                } else {
                    $_SESSION['error_message'] = "The reset code is incorrect. Please try again.";
                }
            }
        } else {
            $_SESSION['error_message'] = "No account found with this email.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Reset Code</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #b0c4de; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .form-container { background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); width: 700px; text-align: center; }
        h2 { color: #333; margin-bottom: 20px; }
        input[type="text"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: black; margin-bottom: 15px; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #333; }
        .error-message { color: red; margin-top: 10px; }
        .success-message { color: green; margin-top: 10px; }
        h1 { text-align: center; font-size: 20px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Verify Reset Code</h2>
        <h1>An email containing a Reset Code has been sent to your email address.</h1>

        <?php
        if (isset($_SESSION['error_message'])) {
            echo "<p class='error-message'>" . $_SESSION['error_message'] . "</p>";
            unset($_SESSION['error_message']);
        }
        if (isset($_SESSION['success_message'])) {
            echo "<p class='success-message'>" . $_SESSION['success_message'] . "</p>";
            unset($_SESSION['success_message']);
        }
        ?>

        <!-- Reset code verification form -->
        <form action="" method="POST">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email']); ?>">
            <input type="text" name="reset_code" placeholder="Enter Reset Code" required>
            <button type="submit">Verify Code</button>
        </form>

        <!-- Resend reset code button -->
        <form action="" method="POST">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email']); ?>">
            <input type="hidden" name="resend_code" value="true">
            <button type="submit">Resend Reset Code</button>
        </form>
    </div>
</body>
</html>
