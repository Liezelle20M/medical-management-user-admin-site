<?php
session_start();
include 'session_check.php'; // Ensures the user is logged in
require_once 'includes/db_connection.php';

// Assuming the admin's id is stored in session after they login
$admin_id = $_SESSION['admin_id'];
$message = '';

// Fetch admin details from the database
$sql = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
} else {
    $message = "<div class='error'>Admin not found.</div>";
}

// Handle form submission for updating the profile
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Trim and sanitize inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $question_1 = trim($_POST['question_1']);
    $question_2 = trim($_POST['question_2']);
    $question_3 = trim($_POST['question_3']);
    $answer_1 = trim($_POST['answer_1']);
    $answer_2 = trim($_POST['answer_2']);
    $answer_3 = trim($_POST['answer_3']);

    $errors = array();

    // Validate username
    if (!empty($username)) {
        if (!preg_match("/^[a-zA-Z ]*$/", $username)) {
            $errors[] = "Username can only contain letters and spaces.";
        }
    }

    // Validate email
    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
    }

    // Validate phone number
    if (!empty($phone_number)) {
        if (!preg_match("/^\d{10}$/", $phone_number)) {
            $errors[] = "Phone number must be 10 digits.";
        }
    }

    // Further validations can be added for security questions and answers if required

    if (empty($errors)) {
        // Proceed with updating the admin profile

        // Build the update query dynamically based on provided fields
        $updateFields = array();
        $params = array();
        $types = '';

        if (!empty($username)) {
            $updateFields[] = 'username = ?';
            $params[] = $username;
            $types .= 's';
        }
        if (!empty($email)) {
            $updateFields[] = 'email = ?';
            $params[] = $email;
            $types .= 's';
        }
        if (!empty($phone_number)) {
            $updateFields[] = 'phone_number = ?';
            $params[] = $phone_number;
            $types .= 's';
        }
        if (!empty($question_1)) {
            $updateFields[] = 'question_1 = ?';
            $params[] = $question_1;
            $types .= 's';
        }
        if (!empty($answer_1)) {
            $updateFields[] = 'answer_1 = ?';
            $params[] = $answer_1;
            $types .= 's';
        }
        if (!empty($question_2)) {
            $updateFields[] = 'question_2 = ?';
            $params[] = $question_2;
            $types .= 's';
        }
        if (!empty($answer_2)) {
            $updateFields[] = 'answer_2 = ?';
            $params[] = $answer_2;
            $types .= 's';
        }
        if (!empty($question_3)) {
            $updateFields[] = 'question_3 = ?';
            $params[] = $question_3;
            $types .= 's';
        }
        if (!empty($answer_3)) {
            $updateFields[] = 'answer_3 = ?';
            $params[] = $answer_3;
            $types .= 's';
        }

        if (!empty($updateFields)) {
            $updateSql = "UPDATE admins SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $params[] = $admin_id;
            $types .= 'i';

            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $message = "<div class='success'>Profile updated successfully.</div>";
                // Refresh admin data
                $sql = "SELECT * FROM admins WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $admin_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $admin = $result->fetch_assoc();
            } else {
                $message = "<div class='error'>Error updating profile: " . $stmt->error . "</div>";
            }

            $stmt->close();
        } else {
            $message = "<div class='error'>No fields to update.</div>";
        }
    } else {
        // Display errors
        $message = "<div class='error'>" . implode("<br>", $errors) . "</div>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- (Same as your existing head content) -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <style>
        /* (Your existing styles) */
        /* General styling for body and container */
        body {
            font-family: Arial, sans-serif;
            background-color: #b0c4de;
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* Allows footer to stick at the bottom */
            margin: 0;
        }

        /* Header Styling */
        header {
            height: 80px; /* Fixed height for consistency */
            width: 100%;
            background-color: #003f7d;
            color: white;
            display: flex;
            align-items: center; /* Center content vertically */
            padding: 0 20px; /* Consistent padding */
            box-sizing: border-box;
        }
        
        .hamburger-menu {
            margin-left: auto; /* Moves the icon to the far right */
        }

        /* Scoped styles for profile form container */
        .profile-form-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            flex: 1;
        }

        .profile-form-container form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 600px;
            max-width: 100%;
        }

        .profile-form-container input[type="text"],
        .profile-form-container input[type="email"],
        .profile-form-container input[type="tel"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
.profile-form-container .form-btn {
    width: 100%;
    padding: 10px;
    background-color: #0C4375;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    text-align: center;
    margin-top: 10px;
}

.profile-form-container .form-btn:hover {
    background-color: #0b3a67;
}
        .profile-form-container button:hover,
        .profile-form-container .change-password-btn:hover {
            background-color: #0b3a67;
        }

        /* Message styling */
        .profile-form-container .message-container {
            margin-bottom: 15px;
        }

        .profile-form-container .success {
            color: green;
        }

        .profile-form-container .error {
            color: red;
        }

        .profile-form-container .section-title {
            margin-top: 20px;
            font-weight: bold;
            color: #333;
        }

        /* Footer styling */
        footer {
            background-color: #002a5b;
            color: white;
            padding: 20px 0;
            text-align: center;
            width: 100%;
        }

        .footer-bottom p {
            margin: 0;
            font-size: 14px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-form-container form {
                width: 90%;
                padding: 15px;
            }

            .footer-bottom p {
                font-size: 12px;
            }

            header,
            footer {
                padding: 10px;
            }
        }
    </style>
</head>
<body>

    <!-- Include header -->
    <?php include 'includes/admin_header.php'; ?>

    <main>
        <div class="profile-form-container">
            <!-- Profile update form -->
            <form action="" method="POST">
                <h2>Edit Profile</h2>

                <div class="message-container">
                    <?php echo $message; ?>
                </div>

                <label for="username">Username:</label>
                <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($admin['username']); ?>" placeholder="Enter your name">

                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($admin['email']); ?>" placeholder="Enter your email">

                <label for="phone_number">Phone Number:</label>
                <input type="tel" name="phone_number" id="phone_number" value="<?php echo htmlspecialchars($admin['phone_number']); ?>" placeholder="Enter your 10-digit phone number">

                <h3 class="section-title">Security Questions</h3>

                <label for="question_1">Question 1:</label>
                <input type="text" name="question_1" id="question_1" value="<?php echo htmlspecialchars($admin['question_1']); ?>" placeholder="Enter security question 1">

                <label for="answer_1">Answer 1:</label>
                <input type="text" name="answer_1" id="answer_1" value="<?php echo htmlspecialchars($admin['answer_1']); ?>" placeholder="Enter answer for question 1">

                <label for="question_2">Question 2:</label>
                <input type="text" name="question_2" id="question_2" value="<?php echo htmlspecialchars($admin['question_2']); ?>" placeholder="Enter security question 2">

                <label for="answer_2">Answer 2:</label>
                <input type="text" name="answer_2" id="answer_2" value="<?php echo htmlspecialchars($admin['answer_2']); ?>" placeholder="Enter answer for question 2">

                <label for="question_3">Question 3:</label>
                <input type="text" name="question_3" id="question_3" value="<?php echo htmlspecialchars($admin['question_3']); ?>" placeholder="Enter security question 3">

                <label for="answer_3">Answer 3:</label>
                <input type="text" name="answer_3" id="answer_3" value="<?php echo htmlspecialchars($admin['answer_3']); ?>" placeholder="Enter answer for question 3">

                <!-- buttons -->
<button type="submit" class="form-btn">Update Profile</button>
<button type="button" class="form-btn" onclick="window.location.href='update_password.php'">Change Password</button>
            </form>
        </div>
    </main>

    <!-- Include footer -->
    <?php include 'includes/admin_footer.php'; ?>

</body>
</html>
