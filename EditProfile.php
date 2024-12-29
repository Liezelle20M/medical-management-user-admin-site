<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("includes/db_connection.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user data from the database
$query = "SELECT first_name, last_name, email, address FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $first_name = htmlspecialchars($user['first_name']);
    $last_name = htmlspecialchars($user['last_name']);
    $email = htmlspecialchars($user['email']);
    $address = htmlspecialchars($user['address']);
} else {
    echo "User not found.";
    exit;
}

$success_message = ''; // Success message
$error_message = ''; // Error message

// Function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(trim($data));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save-changes'])) {
    // Sanitize inputs
    $first_name = sanitize_input($_POST['first-name'] ?? '');
    $last_name = sanitize_input($_POST['last-name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $current_password = $_POST['current-password'] ?? '';
    $new_password = $_POST['new-password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';

    $errors = [];

    // Initialize an array to hold fields to update
    $fields_to_update = [];
    $params = [];
    $types = '';

    // Validate and prepare fields to update
    if (!empty($first_name)) {
        if (!preg_match("/^[a-zA-Z]{3,}$/", $first_name)) {
            $errors[] = "First name must contain only letters and be at least 3 characters long.";
        } else {
            $fields_to_update['first_name'] = $first_name;
            $types .= 's';
            $params[] = $first_name;
        }
    }

    if (!empty($last_name)) {
        if (!preg_match("/^[a-zA-Z]{3,}$/", $last_name)) {
            $errors[] = "Last name must contain only letters and be at least 3 characters long.";
        } else {
            $fields_to_update['last_name'] = $last_name;
            $types .= 's';
            $params[] = $last_name;
        }
    }

    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        } else {
            $fields_to_update['email'] = $email;
            $types .= 's';
            $params[] = $email;
        }
    }

    if (!empty($address)) {
        $fields_to_update['address'] = $address;
        $types .= 's';
        $params[] = $address;
    }

    // Check if user is attempting to change the password
    $password_change = false;
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        $password_change = true;

        // All password fields must be filled
        if (empty($current_password)) {
            $errors[] = "Current password is required to change your password.";
        }

        if (empty($new_password)) {
            $errors[] = "New password is required.";
        }

        if (empty($confirm_password)) {
            $errors[] = "Please confirm your new password.";
        }

        // If new passwords are provided, check if they match
        if ($new_password !== $confirm_password) {
            $errors[] = "New password and confirm password do not match.";
        }

        // Enforce password strength
        if (!empty($new_password)) {
            if (strlen($new_password) < 8) {
                $errors[] = "New password must be at least 8 characters long.";
            }
            if (!preg_match('/[A-Z]/', $new_password)) {
                $errors[] = "New password must contain at least one uppercase letter.";
            }
            if (!preg_match('/[a-z]/', $new_password)) {
                $errors[] = "New password must contain at least one lowercase letter.";
            }
            if (!preg_match('/[0-9]/', $new_password)) {
                $errors[] = "New password must contain at least one number.";
            }
            if (!preg_match('/[!@#$%^&*]/', $new_password)) {
                $errors[] = "New password must contain at least one special character (!@#$%^&*).";
            }
        }
    }

    if (empty($errors)) {
        // Begin transaction
        $conn->begin_transaction();

        try {
            // Handle password change first
            if ($password_change) {
                // Fetch current hashed password from the database
                $query = "SELECT password_hash FROM users WHERE user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_password = $result->fetch_assoc();
                $stmt->close();

                // Verify the current password
                if (password_verify($current_password, $user_password['password_hash'])) {
                    // Hash the new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $fields_to_update['password_hash'] = $hashed_password;
                    $types .= 's';
                    $params[] = $hashed_password;
                } else {
                    throw new Exception("Current password is incorrect.");
                }
            }

            // If there are fields to update
            if (!empty($fields_to_update)) {
                // Build the SET part of the query dynamically
                $set_clause = implode(", ", array_map(function($field) {
                    return "$field = ?";
                }, array_keys($fields_to_update)));

                $update_query = "UPDATE users SET $set_clause WHERE user_id = ?";
                $stmt = $conn->prepare($update_query);

                // Add the user ID to the parameters
                $types .= 'i';
                $params[] = $userId;

                // Bind parameters dynamically
                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    // Commit transaction
                    $conn->commit();

                    if ($password_change) {
                        // Password updated, log the user out
                        session_unset(); // Clear session variables
                        session_destroy(); // Destroy the session
                        $success_message = "Password updated successfully. Please log in again.";
                        header("Location: sign.php"); // Redirect to the sign-in page
                        exit;
                    } else {
                        $success_message = "Profile updated successfully.";
                    }
                } else {
                    throw new Exception("Error updating profile.");
                }

                $stmt->close();
            } else {
                if ($password_change) {
                    // If only password was changed and everything is handled above
                    // Do nothing here
                } else {
                    // No fields to update
                    $error_message = "No changes detected.";
                }
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    } else {
        // Concatenate all error messages
        $error_message = implode("<br>", $errors);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="assets/styles3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
       <style>
        /* Existing Styles */
        .client-feedback {
            color: red;
            margin-bottom: 15px;
            display: none;
        }
        .feedback-message {
            /* Remove the existing color */
            margin-bottom: 15px;
        }
        .success-message {
            color: green;
            margin-bottom: 15px;
        }
        .error-message {
            color: red;
            margin-bottom: 15px;
        }
        .guidelines {
            font-size: 14px;
            color: #001f3f;
            margin-bottom: 10px;
        }
        .strength {
            font-weight: bold;
            margin-top: 5px;
        }
        .weak {
            color: red;
        }
        .medium {
            color: orange;
        }
        .strong {
            color: green;
        }
        .requirement {
            color: #000;
        }
        .requirement.valid {
            color: green;
        }
        .requirement.invalid {
            color: red;
        }
        .toggle-password {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
        }
        .form-group {
            position: relative; /* For positioning the toggle icon */
        }

        /* New Styles for Specific Error Messages */
        .password-match-message {
            color: red;
        }
    </style>
    <script>
        // JavaScript function to validate the form
        function validateForm(event) {
            const firstName = document.getElementById('first-name').value.trim();
            const lastName = document.getElementById('last-name').value.trim();
            const email = document.getElementById('email').value.trim();
            const address = document.getElementById('address').value.trim();
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            let errors = [];

            // Validate First Name
            const nameRegex = /^[a-zA-Z]{3,}$/;
            if (firstName === '') {
                errors.push("First name is required.");
            } else if (!nameRegex.test(firstName)) {
                errors.push("First name must contain only letters and be at least 3 characters long.");
            }

            // Validate Last Name
            if (lastName === '') {
                errors.push("Last name is required.");
            } else if (!nameRegex.test(lastName)) {
                errors.push("Last name must contain only letters and be at least 3 characters long.");
            }

            // Validate Email
            if (email === '') {
                errors.push("Email is required.");
            } else {
                // Simple email regex
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    errors.push("Invalid email format.");
                }
            }

            // Validate Address
            if (address === '') {
                errors.push("Address is required.");
            }

            // Check if user is attempting to change the password
            const password_change = currentPassword !== '' || newPassword !== '' || confirmPassword !== '';

            if (password_change) {
                // Validate Current Password
                if (currentPassword === '') {
                    errors.push("Current password is required to change your password.");
                }

                // Validate New Password
                if (newPassword === '') {
                    errors.push("New password is required.");
                }

                // Validate Confirm Password
                if (confirmPassword === '') {
                    errors.push("Please confirm your new password.");
                }

                // Check if New Password and Confirm Password match
                if (newPassword !== '' && confirmPassword !== '' && newPassword !== confirmPassword) {
                    errors.push("New password and confirm password do not match.");
                }

                // Enforce password strength
                if (newPassword.length > 0) {
                    if (newPassword.length < 8) {
                        errors.push("New password must be at least 8 characters long.");
                    }
                    if (!/[A-Z]/.test(newPassword)) {
                        errors.push("New password must contain at least one uppercase letter.");
                    }
                    if (!/[a-z]/.test(newPassword)) {
                        errors.push("New password must contain at least one lowercase letter.");
                    }
                    if (!/[0-9]/.test(newPassword)) {
                        errors.push("New password must contain at least one number.");
                    }
                    if (!/[!@#$%^&*]/.test(newPassword)) {
                        errors.push("New password must contain at least one special character (!@#$%^&*).");
                    }
                }
            }

            if (errors.length > 0) {
                event.preventDefault(); // Prevent form submission
                // Display errors
                const feedback = document.getElementById('client-feedback');
                feedback.innerHTML = errors.join("<br>");
                feedback.style.display = 'block';
                window.scrollTo(0, 0); // Scroll to top to see errors
            }
        }

        // Real-time password strength validation
        function validatePasswords() {
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;

            // Password strength criteria
            const lengthValid = newPassword.length >= 8;
            const uppercaseValid = /[A-Z]/.test(newPassword);
            const lowercaseValid = /[a-z]/.test(newPassword);
            const numberValid = /[0-9]/.test(newPassword);
            const specialValid = /[!@#$%^&*]/.test(newPassword);

            // Update requirement classes
            document.getElementById('length').className = lengthValid ? 'requirement valid' : 'requirement invalid';
            document.getElementById('uppercase').className = uppercaseValid ? 'requirement valid' : 'requirement invalid';
            document.getElementById('lowercase').className = lowercaseValid ? 'requirement valid' : 'requirement invalid';
            document.getElementById('number').className = numberValid ? 'requirement valid' : 'requirement invalid';
            document.getElementById('special').className = specialValid ? 'requirement valid' : 'requirement invalid';

            // Update strength message
            const strengthMessage = document.getElementById('strengthMessage');
            if (lengthValid && uppercaseValid && lowercaseValid && numberValid && specialValid) {
                strengthMessage.textContent = "Password strength: Strong";
                strengthMessage.className = "strength strong";
            } else if (
                (lengthValid && uppercaseValid && lowercaseValid && numberValid) ||
                (lengthValid && uppercaseValid && lowercaseValid && specialValid) ||
                (lengthValid && uppercaseValid && numberValid && specialValid) ||
                (lengthValid && lowercaseValid && numberValid && specialValid)
            ) {
                strengthMessage.textContent = "Password strength: Medium";
                strengthMessage.className = "strength medium";
            } else {
                strengthMessage.textContent = "Password strength: Weak";
                strengthMessage.className = "strength weak";
            }

            // Password match validation
            const passwordMatchMessage = document.getElementById('passwordMatchMessage');
            if (newPassword !== confirmPassword) {
                passwordMatchMessage.textContent = "Passwords do not match.";
                passwordMatchMessage.style.color = "red";
            } else {
                passwordMatchMessage.textContent = "";
            }
        }

        window.onload = function() {
            // Attach the validateForm function to the form's submit event
            const form = document.getElementById('profile-form');
            form.addEventListener('submit', validateForm);

            // Attach real-time validation to password fields
            const newPasswordInput = document.getElementById('new-password');
            const confirmPasswordInput = document.getElementById('confirm-password');
            newPasswordInput.addEventListener('input', validatePasswords);
            confirmPasswordInput.addEventListener('input', validatePasswords);
        }
    </script>
</head>
<body>
    <?php include("includes/header.php"); ?>
    
    <main>
       
        <section class="profile-edit">
            <div class="heading">
                <h2>Edit Your Profile</h2>
            </div>
            <?php if ($success_message): ?>
                <p class="success-message"><?php echo $success_message; ?></p>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <div id="client-feedback" class="client-feedback"></div>
            <form id="profile-form" action="EditProfile.php" method="POST">
                <div class="name-fields">
                    <div class="form-group">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="first-name" value="<?php echo $first_name; ?>">
                    </div>
                    <div class="form-group">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="last-name" value="<?php echo $last_name; ?>">
                    </div>
                </div>
                <div class="name-fields">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $email; ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" value="<?php echo $address; ?>">
                    </div>
                </div>

                <!-- Password Fields (Always Visible) -->
                <div class="form-group">
                    <label for="current-password">Current Password</label>
                    <input type="password" id="current-password" name="current-password" placeholder="Current Password">
                </div>
                <div class="form-group">
                    <label for="new-password">New Password</label>
                    <input type="password" id="new-password" name="new-password" placeholder="New Password">
                </div>
                <div class="guidelines">
                    Your password must contain:
                    <ul>
                        <li id="length" class="requirement">At least 8 characters</li>
                        <li id="uppercase" class="requirement">At least one uppercase letter (A-Z)</li>
                        <li id="lowercase" class="requirement">At least one lowercase letter (a-z)</li>
                        <li id="number" class="requirement">At least one number (0-9)</li>
                        <li id="special" class="requirement">At least one special character (!@#$%^&*)</li>
                    </ul>
                </div>
                <p id="strengthMessage" class="strength"></p>
                <div class="form-group">
                    <label for="confirm-password">Confirm New Password</label>
                    <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm New Password">
                </div>
                <p id="passwordMatchMessage" class="password-match-message"></p>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="window.location.href='profile.php';">Cancel</button>
                    <button type="submit" class="btn-save" name="save-changes">Save Changes</button>
                </div>
            </form>
        </section>
    </main>
    <?php include("includes/footer.php"); ?>
</body>
</html>
