<?php
session_start();
require_once 'includes/db_connection.php'; // Adjust the path as necessary

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $answer1 = trim(mysqli_real_escape_string($conn, $_POST['answer1']));
    $answer2 = trim(mysqli_real_escape_string($conn, $_POST['answer2']));
    $answer3 = trim(mysqli_real_escape_string($conn, $_POST['answer3']));

    // Fetch security questions and answers for the admin based on their email
    $stmt = $conn->prepare("SELECT question_1, question_2, question_3, answer_1, answer_2, answer_3 FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();

        // Validate the provided answers exactly as they are (case-sensitive)
        if ($answer1 === trim($admin['answer_1']) &&
            $answer2 === trim($admin['answer_2']) &&
            $answer3 === trim($admin['answer_3'])) {
            
            // Answers are correct, redirect to change password page
            header("Location: admin_change_password.php?email=$email");
            exit();
        } else {
            $_SESSION['error_message'] = "One or more answers are incorrect. Please try again.";
        }
    } else {
        $_SESSION['error_message'] = "No account found with this email.";
    }
    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Questions</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #b0c4de; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .form-container { background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); width: 700px; text-align: center; }
        h2 { color: #333; margin-bottom: 20px; }
        input[type="text"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: black; margin-bottom: 15px; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #333; }
        .error-message { color: red; margin-top: 10px; }
        h1 { text-align: center; font-size: 20px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Security Questions</h2>
        <h1>Please answer your security questions to proceed.</h1>

        <?php
        if (isset($_SESSION['error_message'])) {
            echo "<p class='error-message'>" . $_SESSION['error_message'] . "</p>";
            unset($_SESSION['error_message']);
        }
        ?>

        <!-- Security questions form -->
        <form action="" method="POST">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email']); ?>">

            <?php
            // Fetch the questions for display
            $stmt = $conn->prepare("SELECT question_1, question_2, question_3 FROM admins WHERE email = ?");
            $stmt->bind_param("s", $_GET['email']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                echo '<p>' . htmlspecialchars($admin['question_1']) . '</p>';
                echo '<input type="text" name="answer1" placeholder="Answer to Question 1" required>';

                echo '<p>' . htmlspecialchars($admin['question_2']) . '</p>';
                echo '<input type="text" name="answer2" placeholder="Answer to Question 2" required>';

                echo '<p>' . htmlspecialchars($admin['question_3']) . '</p>';
                echo '<input type="text" name="answer3" placeholder="Answer to Question 3" required>';
            } else {
                echo '<p>No security questions set for this account.</p>';
            }
            $stmt->close();
            ?>

            <button type="submit">Submit Answers</button>
        </form>
    </div>
</body>
</html>
