<?php session_start();
 if (isset($_SESSION['user_name'])) {
    $username = htmlspecialchars($_SESSION['user_name']); // Sanitize to prevent XSS
} ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions</title>
    <link rel="stylesheet" href="assets/styles4.css">
    <link rel="stylesheet" href="assets/styles3.css">
    <style>
     main {
    max-width: 800px; /* Limits the width for better readability */
    margin: 0 auto; /* Centers the content */
    padding: 20px;
    font-family: Arial, sans-serif;
    line-height: 1.6;
    color: #333;
}

h1 {
    text-align: center;
    font-size: 2em;
    margin-bottom: 20px;
    color: #007BFF; /* Optional: Change color to fit your theme */
}

h2 {
    font-size: 1.5em;
    color: #333;
    margin-top: 20px;
    margin-bottom: 10px;
}


    </style>
</head>
<body>
    <?php include("includes/header.php"); ?>
    
    <main>
       <h1>Terms and Conditions</h1>
        <h2>Introduction</h2>
        <p>Welcome to VAHSA. By accessing our website, you agree to comply with and be bound by the following terms and conditions.</p>

        <h2>Intellectual Property</h2>
        <p>All content, trademarks, and other intellectual property on this website are owned by VAHSA or its licensors.</p>

        <h2>User Responsibilities</h2>
        <p>You agree to use the website for lawful purposes and not to engage in any conduct that restricts or inhibits anyone's use or enjoyment of the site.</p>

        <h2>Limitation of Liability</h2>
        <p>VAHSA is not liable for any indirect or consequential loss arising from your use of the website.</p>

        <h2>Changes to Terms</h2>
        <p>We may update these terms from time to time. We will notify you of any changes by posting the new terms on this page.</p>

        <h2>Contact Us</h2>
        <p>If you have any questions about these terms, please contact us at <a href="mailto:vahsa_health@outlook.com">vahsa_health@outlook.com</a>.</p>
    </main>
    <?php include("includes/footer.php"); ?>
</body>
</html>
