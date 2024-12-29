<?php session_start();
 if (isset($_SESSION['user_name'])) {
    $username = htmlspecialchars($_SESSION['user_name']); // Sanitize to prevent XSS
} ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy</title>
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
          <h1>Privacy Policy</h1>
    <h2>Information We Collect</h2>
    <p>We collect personal information when you register on our site, place an order, subscribe to our newsletter, or interact with our services.</p>

    <h2>How We Use Your Information</h2>
    <p>Your information may be used to improve our website, process transactions, or send periodic emails regarding your order.</p>

    <h2>Security of Your Information</h2>
    <p>We implement a variety of security measures to maintain the safety of your personal information.</p>

    <h2>Sharing Your Information</h2>
    <p>We do not sell, trade, or otherwise transfer to outside parties your personally identifiable information without your consent.</p>

    <h2>Changes to This Privacy Policy</h2>
    <p>We may update this privacy policy periodically. We will notify you about significant changes in the way we treat personal information by sending a notice to the primary email address specified in your account or by placing a prominent notice on our site.</p>

    <h2>Contact Us</h2>
    <p>If you have any questions about this privacy policy, please contact us at <a href="mailto:vahsa_health@outlook.com">vahsa_health@outlook.com</a>.</p>  </main>
  </main>  <?php include("includes/footer.php"); ?>
</body>
</html>
