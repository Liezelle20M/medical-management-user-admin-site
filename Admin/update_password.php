<?php
session_start();
require_once 'includes/db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$message = ''; // Variable to hold success/error messages

// Function to check password strength
function isStrongPassword($password) {
    // Check if the password meets the criteria
    return preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])(?=.{8,})/", $password);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newPassword = isset($_POST['new_password']) ? mysqli_real_escape_string($conn, $_POST['new_password']) : '';
    $confirmPassword = isset($_POST['confirm_password']) ? mysqli_real_escape_string($conn, $_POST['confirm_password']) : '';

    // Check if passwords match
    if ($newPassword !== $confirmPassword) {
        $message = "<div class='error'>Passwords do not match!</div>";
    } elseif (!isStrongPassword($newPassword)) {
        $message = "<div class='error'>Password is weak! It must be at least 8 characters long, contain uppercase and lowercase letters, a number, and a special character.</div>";
    } else {
        // Hash the new password
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the password in the database
        $sql = "UPDATE admins SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $passwordHash, $admin_id);

        if ($stmt->execute()) {
            $message = "<div class='success'>Password updated successfully! You will be logged out.</div>";
            
            // Logout the admin after updating password
            session_destroy();
            
            // Redirect to login page after 3 seconds
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'admin_login.php';
                }, 3000);
            </script>";
        } else {
            $message = "<div class='error'>Error updating password: " . $stmt->error . "</div>";
        }
        
        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
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

        form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 600px;
        }

        .password-container {
            position: relative; 
            width: 100%; 
        }

        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 10px 40px 10px 10px; 
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        img.toggle-password {
            position: absolute;
            right: 10px; 
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            width: 24px;
            height: 24px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #0C4375; /* Blue color */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #0b3a67; /* Darker blue on hover */
        }

        .error {
            color: red;
            margin: 10px 0;
        }

        .success {
            color: green;
            margin: 10px 0;
        }

        .strength {
            margin: 10px 0;
            font-weight: bold;
        }

        .guidelines {
            font-size: 0.9em;
            margin-top: 10px;
            color: #555;
        }

        .match-status {
            font-weight: bold;
            margin: 10px 0;
        }

        .message-container {
            margin-bottom: 15px; /* Space between messages and form */
        }
    </style>
</head>
<body>
    <form action="" method="POST">
        <h2>Update Password</h2>

        <div class="message-container">
            <?php echo $message; ?>
        </div>

        <div class="password-container">
            <input type="password" name="new_password" id="new_password" placeholder="Enter New Password" required>
            <img src="images/eye-crossed.png" id="toggleNewPassword" class="toggle-password" onclick="togglePasswordVisibility('new_password', this)" alt="Toggle Password Visibility">
        </div>

        <div class="password-container">
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm New Password" required>
            <img src="images/eye-crossed.png" id="toggleConfirmPassword" class="toggle-password" onclick="togglePasswordVisibility('confirm_password', this)" alt="Toggle Password Visibility">
        </div>

        <div class="strength" id="password_strength"></div>
        <div class="match-status" id="match_status"></div>

        <button type="submit">Update Password</button>

        <div class="guidelines">
            <strong>Password Guidelines:</strong><br>
            - At least 8 characters long<br>
            - Contains uppercase and lowercase letters<br>
            - Includes at least one number<br>
            - Contains at least one special character (e.g., @, #, $, %, &)<br>
        </div>
    </form>

    <script>
        function checkPasswordStrength(password) {
            let strengthText = '';
            const strengthElement = document.getElementById('password_strength');
            const strongPasswordRegex = new RegExp("^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])(?=.{8,})");
            const weakPasswordRegex = new RegExp("^(?=.*[a-z])(?=.{6,})");

            if (strongPasswordRegex.test(password)) {
                strengthText = 'Strong Password';
                strengthElement.style.color = 'green';
            } else if (weakPasswordRegex.test(password)) {
                strengthText = 'Weak Password';
                strengthElement.style.color = 'orange';
            } else {
                strengthText = 'Very Weak Password';
                strengthElement.style.color = 'red';
            }

            strengthElement.innerText = strengthText;
        }

        function checkPasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchStatusElement = document.getElementById('match_status');

            if (password === confirmPassword) {
                matchStatusElement.innerText = 'Passwords match!';
                matchStatusElement.style.color = 'green';
            } else {
                matchStatusElement.innerText = 'Passwords do not match!';
                matchStatusElement.style.color = 'red';
            }
        }

        function togglePasswordVisibility(passwordFieldId, toggleIcon) {
            const passwordField = document.getElementById(passwordFieldId);
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.src = 'images/eye (1).png'; // Use the icon for "password visible"
            } else {
                passwordField.type = 'password';
                toggleIcon.src = 'images/eye-crossed.png'; // Use the icon for "password hidden"
            }
        }

        document.getElementById('new_password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });

        document.getElementById('confirm_password').addEventListener('input', function() {
            checkPasswordMatch();
        });
    </script>
</body>
</html>
