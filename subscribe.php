<?php
require_once 'includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = $_POST['first_name'];
    $email = $_POST['email'];

    // Insert the subscriber into the database
    $stmt = $conn->prepare("INSERT INTO newsletter_subscribers (first_name, email) VALUES (?, ?)");
    $stmt->bind_param("ss", $firstName, $email);

    if ($stmt->execute()) {
        // Display a success message
        echo "<script>alert('Thank you for subscribing to our newsletter!');</script>";
        echo "<script>window.location.href='index.php';</script>"; // Redirect back to the home page
    } else {
        // Display an error message if the email is already subscribed
        echo "<script>alert('This email is already subscribed to our newsletter.');</script>";
        echo "<script>window.location.href='index.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
