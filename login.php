<?php
session_start(); // Start the session
require_once 'includes/db_connection.php';
// Removed PHPMailer include as it's no longer needed

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if email and password are set
    if (isset($_POST['email']) && isset($_POST['password'])) {
        // Collect and sanitize form data
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = trim($_POST['password']);

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_message'] = "Invalid email format.";
            header("Location: sign.php");
            exit();
        }

        // Prepare the SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT user_id, first_name, password_hash, email_verified FROM users WHERE email = ?");
        
        if ($stmt) {
            $stmt->bind_param("s", $email); // Bind the email parameter
            $stmt->execute();
            $result = $stmt->get_result(); // Get the result set

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $passwordHashFromDB = $user['password_hash'];

                // Check if email is verified
                if (!$user['email_verified']) {
                    // Delete the user from the database
                    $deleteStmt = $conn->prepare("DELETE FROM users WHERE email = ?");
                    if ($deleteStmt) {
                        $deleteStmt->bind_param("s", $email);
                        $deleteStmt->execute();
                        $deleteStmt->close();

                        // Optionally, inform the user via email about account deletion
                        // Uncomment the following block if you wish to send an email notification

                        /*
                        $subject = "Your VAHSA Account Has Been Deleted";
                        $message = "Hello {$user['first_name']},\n\nYour account on VAHSA has been deleted because your email verification was not completed.\n\nPlease register again if you wish to use our services.\n\nThank you!";
                        
                        // Set the sender's email
                        $from = "no-reply@vahsa.com"; // It's recommended to use a domain-associated email

                        // Additional headers
                        $headers = "From: VAHSA <" . $from . ">\r\n";
                        $headers .= "Reply-To: " . $from . "\r\n";
                        $headers .= "MIME-Version: 1.0\r\n";
                        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                        // Send the email
                        mail($email, $subject, $message, $headers);
                        */

                        // Set session error message
                        $_SESSION['error_message'] = "Your email was not verified. Your account has been deleted. Please register again.";

                        // Redirect to the registration page or sign-in page
                        header("Location: sign.php");
                        exit();
                    } else {
                        // Handle deletion statement preparation failure
                        $_SESSION['error_message'] = "Database error: Unable to delete account.";
                        header("Location: sign.php");
                        exit();
                    }
                } else {
                    // Verify the password
                    if (password_verify($password, $passwordHashFromDB)) {
                        // Password is correct, set session variables
                        $_SESSION['user_id'] = $user['user_id']; // Store user ID in session
                        $_SESSION['user_name'] = $user['first_name']; // Optionally store the name
                        $_SESSION['logged_in'] = true; // Mark the user as logged in

                        // Remove the verification message from the session if it exists
                        unset($_SESSION['error_message']);

                        // Redirect to profile or home page after a 3-second delay
                        echo "<script>
                            alert('Login successful! Redirecting to your dashboard.');
                            setTimeout(function() {
                                window.location.href = 'index.php';
                            }, 3000); // 3-second delay
                        </script>";
                        exit();
                    } else {
                        $_SESSION['error_message'] = "Invalid email or password.";
                    }
                }
            } else {
                $_SESSION['error_message'] = "Invalid email or password.";
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Database error: Unable to prepare statement.";
        }
    } else {
        $_SESSION['error_message'] = "Please enter both email and password.";
    }
    // Redirect back to sign-in page
    header("Location: sign.php");
    exit();
}
$conn->close();
?>
