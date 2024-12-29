<?php
// Start the session and check for admin authentication if necessary
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

require_once 'includes/db_connection.php';
require_once 'mailer2.php';

function sendNewsletter($subject, $message, $filePath, $conn) {
    $result = $conn->query("SELECT email FROM newsletter_subscribers");

    // Construct the image URL for the email
     $imageUrl = $filePath ? 'https://srv1649-files.hstgr.io/b733efd960c2c46a/files/public_html/uploads/' . basename($filePath) : null;

    while ($subscriber = $result->fetch_assoc()) {
        $email = $subscriber['email'];

        // Include the image in the email message
        $emailMessage = $message;
        if ($imageUrl) {
            $emailMessage .= '<br><br><img src="' . $imageUrl . '" alt="Newsletter Image" style="max-width:100%; height:auto;">';
        }

        sendEmail($email, $subject, $emailMessage); // Use sendEmail from mailer.php
    }

    $result->close();
}


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $imageUrl = null;

    // Handle file upload for images
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'https://srv1649-files.hstgr.io/b733efd960c2c46a/files/public_html/uploads/'; // Update this path
        $filePath = $uploadDir . basename($_FILES['attachment']['name']);
        
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filePath)) {
            // Store the URL to the uploaded image
            $imageUrl = 'https://valueaddedhealthcaresa.com/uploads/' . basename($filePath);
        }
    }

    // Send the newsletter to all subscribers with the image URL if available
    sendNewsletter($subject, $message, $imageUrl, $conn);

    echo "<script>alert('Newsletter sent to all subscribers successfully!');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Send Newsletter</title>
</head>
<body>
    <style>
    /* Body styling for centering the newsletter section */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    display: flex;
    flex-direction: column;
    min-height: 100vh; /* Ensures footer remains at the bottom */
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
/* Newsletter container styling */
.newsletter-container {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    max-width: 800px; /* Adjusted max-width to fit nicely */
    width: 90%; /* Responsive width for smaller screens */
    margin: 20px auto; /* Centered with margin */
}/* Hero Section Styling */
.hero-section {
    width: 100%;
    height: 60vh; /* Adjusts height for visual impact */
    position: relative;
    overflow: hidden;
}

.hero-image {
    width: 100%;
    height: 100%;
    position: relative;
}

.hero-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    text-align: center;
    font-size: 2.5rem;
    font-weight: bold;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);
}

/* Content styles specific to the newsletter */
h2 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
    text-align: center;
}

form {
    display: flex;
    flex-direction: column;
}

input {
    font-size: 16px;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 4px;
    border: 1px solid #ccc;
    width: 98%;
    height:50px;
}
textarea {font-size: 16px;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 4px;
    border: 1px solid #ccc;
    width: 98%;
    height:100px;}
button {
    font-size: 16px;
    padding: 10px;
    background-color: black;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

button:hover {
    background-color: darkgray;
}
  .newsletter-container {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        max-width: 800px; /* Adjusted max-width to fit nicely */
        width: 90%; /* Responsive width for smaller screens */
        margin: 20px auto; /* Centered with margin */
        min-height: 400px; /* Increased minimum height */
        display: flex;
        flex-direction: column;
        
    }
/* Footer positioning */
footer {
    background-color: #002a5b;
    color: white;
    padding: 20px 0;
    text-align: center;
    margin-top: auto; /* Pushes footer to bottom */
}

.footer-bottom p {
    margin: 0;
    font-size: 14px;
}

/* Media query for responsive adjustments */
@media (max-width: 768px) {
    .newsletter-container {
        padding: 15px;
    }

    h2 {
        font-size: 20px;
    }

    .footer-bottom p {
        font-size: 12px;
    }
}

    </style>
    <?php include 'includes/admin_header.php'; ?>
  
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-image">
            <img src="images/med.png" alt="Manage Medicolegal">
            <div class="hero-text">
                <h1>SEND NEWSLETTERS</h1>
            </div>
        </div>
    </section>


    <section>
        <div class="newsletter-container">
            <h2>Send Newsletter</h2>
           <form id="newsletter-form" method="POST" enctype="multipart/form-data">
    <input type="text" name="subject" placeholder="Newsletter Subject" required>
    <textarea name="message" placeholder="Write your newsletter message here..." required></textarea>

   
    <button type="submit">Send Newsletter</button>
</form>
        </div>
    </section>
<?php include 'includes/admin_footer.php'; ?>

<script src="https://cdn.tiny.cloud/1/6lmv3skn1wjpj0nnveoydjx16rt0t965k5spiaiw1nacufc9/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  document.getElementById('image-upload').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Show a preview of the image
    const previewContainer = document.getElementById('preview');
    previewContainer.innerHTML = ""; // Clear previous previews
    const img = document.createElement('img');
    img.src = URL.createObjectURL(file);
    img.style.maxWidth = '200px';
    previewContainer.appendChild(img);
});
</script>


</body>
</html>
