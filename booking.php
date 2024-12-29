<?php
session_start(); // Ensure session is started

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page
    header("Location: sign.php");
    exit; // Stop further execution of the page
}
include 'includes/db_connection.php';

// Fetch the courses from the booking table
$sql = "SELECT * FROM booking";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VAHSA Website</title>
   <link rel="stylesheet" href="assets/styles7.css">
    <link rel="stylesheet" href="assets/styles8.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        
         <!-- Courses Section -->
        <section class="courses-section">
            <h1>Training Courses</h1>
            <p>Equipping Healthcare Professionals with Essential Skills in Medical Billing and Service Management.</p>
        </section>
        <section class="courses-container">
            <!-- Dynamic Course Cards -->
            <?php while ($row = $result->fetch_assoc()): ?>
            <div class="course-card">
                <a href="link-to-course.php?id=<?php echo $row['id']; ?>" class="image-link">
                    <!-- Ensure the correct path is used to retrieve the image -->
                    <img src="Admin/images/<?php echo $row['image']; ?>" alt="<?php echo $row['training_theme']; ?>">
                </a>
                <div class="course-content">
                    <h2><?php echo $row['training_theme']; ?></h2>
                    <p><?php echo $row['description']; ?></p>
                    <p><strong>Fee:</strong> R<?php echo number_format($row['fee'], 2); ?></p>
                    <button onclick="bookCourse('<?php echo $row['training_theme']; ?>', <?php echo $row['fee']; ?>)">Book</button>
                </div>
            </div>
            <?php endwhile; ?>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

<script>
    function bookCourse(courseName, courseFee) {
        // Use AJAX to send the selected course to the PHP session
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "booking.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                // Redirect to the specs page after storing session data
                window.location.href = "specs.php";
            }
        };
        xhr.send("courseName=" + courseName + "&courseFee=" + courseFee);
    }
</script>
 <style>
 .courses-section {
    background-color: #aec4e1; /* Light blue background */
    height: 300px;
    width: 100%; /* Full width within container */
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    margin-bottom: 20px;
    box-sizing: border-box; /* Ensure padding is included in width calculation */
    padding: 0 20px; /* Optional padding for spacing */
    overflow: hidden; /* Prevents any internal overflow */
}

    /* Button Styling */
    button {
        padding: 10px;
        background-color: #007bff;
        color: white;
        font-size: 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
     width:200px;
    }

    button:hover {
        background-color: #0056b3;
    }

    .courses-section h1 {
        font-size: 36px;
        color: #000;
        margin-bottom: 10px;
    }

    .courses-section p {
        font-size: 18px;
        color: #000;
    }

    /* Courses container */
    .courses-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px;
    }

    /* Course Card Styling */
    .course-card {
        background-color: #f0f0f0;
        border-radius: 15px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin: 20px;
        width: 80%;
        max-width: 600px;
        transition: transform 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .course-card:hover {
        transform: translateY(-10px);
    }

  .course-card img {
    width: 100vw; /* Fills the full viewport width */
    max-width: 100%; /* Ensures it doesn't exceed the container */
    height: 200px; /* Fixed height for consistency */
    object-fit: cover;
    border-radius: 15px 15px 0 0;
}

    /* Course content styling */
    .course-content {
        padding: 20px;
        text-align: center; /* Center-align text */
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center; /* Center-align elements inside the content */
    }

    .course-content h2 {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 10px;
        color: #000; /* Black text */
    }

    .course-content p {
        font-size: 14px;
        margin-bottom: 5px;
        color: #000; /* Black text */
    }

    .course-content .book-button {
        background-color: #1c658c;
        color: white;
        padding: 10px 15px;
        border-radius: 5px;
        text-decoration: none;
        text-align: center;
        display: inline-block;
        margin-top: 10px;
    }

    .course-content .book-button:hover {
        background-color: #145577;
    }
</style>

</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve the course details from the AJAX request
    $courseName = $_POST['courseName'];
    $courseFee = $_POST['courseFee'];

    // Store the selected course details in the session
    $_SESSION['training_theme'] = $courseName;
    $_SESSION['course_fee'] = $courseFee;
}

$conn->close();
?>
