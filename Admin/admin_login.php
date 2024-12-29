<?php
session_start();
include '../includes/db_connection.php'; // Include the database connection file

// Check if the default admin record exists in the row with id 1
$sql_check_admin = "SELECT id, username, email FROM admins WHERE id = 1";
$result_check_admin = $conn->query($sql_check_admin);
if ($result_check_admin->num_rows == 0) {
    // Default admin record does not exist in row with id 1, insert default admin details
    $hashed_default_password = password_hash('password123', PASSWORD_DEFAULT);
    $sql_insert_admin = "INSERT INTO admins (username, password, email, phone_number) VALUES ('admin', '$hashed_default_password', 'admin@example.com', '1234567890')";
    $conn->query($sql_insert_admin);
}

// Password verification process
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch admin's password from the database (only from the default admin record with id 1)
    $sql = "SELECT id, username, password, email FROM admins WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $admin_username, $hashed_password, $email);
    $stmt->fetch();
    $stmt->close();

    // Verify password
    if (empty($password) || !password_verify($password, $hashed_password)) {
        $error = "Invalid username or password"; // Set error message for incorrect credentials
    } else {
        // Set session variables
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $id;
        $_SESSION['admin_username'] = $admin_username;
        $_SESSION['admin_email'] = $email;

        header('Location: dashboard.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body {
            background-color: #ACC0DA;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 0;
        }
        .login-container {
            background-color: white;
            border-radius: 25px;
            width: 90%; /* Almost filling the whole page */
            max-width: 800px;
            padding: 40px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .login-container h1 {
            margin-bottom: 20px;
            font-size: 28px;
            color: #333;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .forgot-password {
            display: block;
            text-align: left; /* Align to the left */
            margin: 10px 0;
            font-size: 14px;
            color: blue; /* Changed to blue */
            text-decoration: none;
        }
        .forgot-password:hover {
            text-decoration: underline;
        }
        .login-btn {
            background-color: black;
            color: white;
            width: 100%; /* Full width button */
            padding: 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .login-btn:hover {
            background-color: #333;
        }

        /* Responsive Design */
        @media screen and (max-width: 600px) {
            .login-container {
                width: 100%;
                padding: 20px;
            }
            input[type="text"], input[type="password"] {
                padding: 10px;
                font-size: 14px;
            }
            .login-btn {
                padding: 12px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Admin</h1>
        <form method="POST" action="admin_login.php">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <a href="password_reset.php" class="forgot-password">Forgot password?</a>
            <button type="submit" class="login-btn">Login</button>
        </form>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
