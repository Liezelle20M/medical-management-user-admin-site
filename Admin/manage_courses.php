<?php
  session_start();
  // Check if the admin is logged in by verifying the session variable
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Admin is not logged in, redirect to the admin login page
    header('Location: admin_login.php');
    exit();
}

// Database connection
include 'includes/db_connection.php';
// Initialize variables
$course_id = '';
$training_theme = '';
$description = '';
$fee = '';
$image = '';

// Handle form submission to add or update a course
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle Add or Update Course
    if (isset($_POST['action']) && $_POST['action'] == 'add_update_course') {
        $training_theme = $_POST['training_theme'];
        $description = $_POST['description'];
        $fee = $_POST['fee'];
        $course_id = $_POST['course_id'];

        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "images/";
            $target_file = $target_dir . basename($_FILES["image"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image = $_FILES['image']['name'];
            }
        } else {
            // Retrieve existing image if no new image is uploaded
            if (!empty($course_id)) {
                $result = $conn->query("SELECT image FROM booking WHERE id=$course_id");
                $existing_image = $result->fetch_assoc();
                $image = $existing_image['image'];
            }
        }

        if (!empty($course_id)) {
            // Update existing course
            $sql = "UPDATE booking SET training_theme='$training_theme', description='$description', fee='$fee', image='$image' WHERE id=$course_id";
        } else {
            // Insert new course
            $sql = "INSERT INTO booking (training_theme, description, fee, image) 
                    VALUES ('$training_theme', '$description', '$fee', '$image')";
        }

        if ($conn->query($sql) === TRUE) {
            echo json_encode(['status' => 'success', 'message' => 'Course saved successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Error: " . $sql . "<br>" . $conn->error]);
        }
        exit; // End the script after handling the AJAX request
    }

    // Handle Add Date/Time to Course
    if (isset($_POST['action']) && $_POST['action'] == 'add_course_date_time') {
        $selected_course_id = $_POST['selected_course_id'];
        $selected_date = $_POST['selected_date'];
        $selected_time = $_POST['selected_time'];

        // Insert new date and time into course_dates
        $formatted_date = date('Y-m-d', strtotime($selected_date));
        $formatted_time = date('H:i:s', strtotime($selected_time));
        $sql = "INSERT INTO course_dates (course_id, date, time) VALUES ($selected_course_id, '$formatted_date', '$formatted_time')";

        if ($conn->query($sql) === TRUE) {
            echo json_encode(['status' => 'success', 'message' => 'Date and time added successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Error: " . $sql . "<br>" . $conn->error]);
        }
        exit;
    }
}

// Handle delete operation for courses
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM booking WHERE id=$id");
    header('Location: manage_courses.php');
    exit;
}

// Handle delete operation for course dates
if (isset($_GET['delete_date'])) {
    $date_id = $_GET['delete_date'];
    $conn->query("DELETE FROM course_dates WHERE id=$date_id");
    header('Location: manage_courses.php');
    exit;
}

// Fetch courses for display
$query = "SELECT * FROM booking";
$result = $conn->query($query);

// Fetch course dates for display
$date_query = "SELECT cd.id as date_id, b.training_theme, cd.date, cd.time 
               FROM course_dates cd 
               JOIN booking b ON cd.course_id = b.id 
               ORDER BY b.training_theme, cd.date, cd.time";
$date_result = $conn->query($date_query);
?>

<!DOCTYPE html>
<html lang="en">
 
<head>
    <title>Manage Training</title>
    <style>
      /* Body styling for centering content */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
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
/* Content styling */
.content {
    max-width: 1200px;
    width: 90%;
    margin: 20px auto; /* Centers the content */
}

/* Hero Section Styling */
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

/* Form styling */
.course-form,
.date-time-form {
    background-color: white;
    padding: 20px;
    max-width: 800px; /* Limit width for readability */
    margin: 20px auto;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.course-form h2,
.date-time-form h2 {
    font-size: 24px;
    color: #333;
    text-align: center;
    margin-bottom: 20px;
}

/* Input, textarea, and button styling */
.course-form input,
.course-form textarea,
.date-time-form select,
.date-time-form input {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
    border-radius: 4px;
    border: 1px solid #ddd;
}

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

/* Table styling */
.course-table,
.course-dates-table {
    width: 100%;
    margin-top: 20px;
    border-collapse: collapse;
        
}

.course-table th,
.course-table td,
.course-dates-table th,
.course-dates-table td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: center;
}

.course-table th,
.course-dates-table th {
    background-color: #003366;
    color: white;
    font-weight: bold;
}

/* Button styling in tables */
.editButton,
.deleteButton,
.course-dates-table td a {
    background-color: black;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
    font-weight: bold;
    text-transform: uppercase;
}

.editButton:hover,
.deleteButton:hover,
.course-dates-table td a:hover {
    background-color: #333333;
}

/* Footer styling */
footer {
    background-color: #002a5b;
    color: white;
    padding: 20px 0;
    text-align: center;
    margin-top: auto; /* Pushes footer to the bottom */
}

.footer-bottom p {
    margin: 0;
    font-size: 14px;
}

@media (max-width: 768px) {
    /* Responsive adjustments for smaller screens */
    .hero-text {
        font-size: 1.5rem;
    }

    .content,
    .course-form,
    .date-time-form {
        width: 95%;
        margin: 10px auto;
    }

    .footer-bottom p {
        font-size: 12px;
    }
}/* Responsive styling for smaller screens */
@media (max-width: 768px) {
    /* Make tables scrollable on small screens */
    .course-table,
    .course-dates-table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }

    /* Adjust font size and padding for table cells on mobile */
    .course-table td,
    .course-table th,
    .course-dates-table td,
    .course-dates-table th {
        padding: 8px;
        font-size: 14px;
    }

    /* Center-align headers for readability */
    .course-table th,
    .course-dates-table th {
        text-align: center;
    }
}

    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"> 
  <link rel="stylesheet" href="styles1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
 
<body>
    <?php include 'includes/admin_header.php'; ?>
  
   <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-image">
            <img src="images/med.png" alt="Manage Medicolegal">
            <div class="hero-text">
                <h1>MANAGE TRAINING COURSES</h1>
            </div>
        </div>
    </section>


    <div class="content">
        <div id="successMessage" style="display: none; color: green; font-weight: bold; text-align: center; margin-bottom: 20px;"></div>

        <!-- Add/Edit Course Form -->
        <form class="course-form" id="courseForm" enctype="multipart/form-data">
            <h2>Add New Courses</h2>
            <input type="hidden" name="course_id" id="course_id" value="">
            <input type="text" name="training_theme" id="training_theme" placeholder="Course Name" required>
            <textarea name="description" id="description" placeholder="Course Description" required></textarea>
            <input type="text" name="fee" id="fee" placeholder="Course Fee (e.g., 2000.00)" required>
            <input type="file" name="image" id="image" required>
            <button type="submit" id="submitButton"><?php echo $course_id ? 'Update Course' : 'Add Course'; ?></button>
        </form>

        <!-- Second Form: Add Date and Time to Course -->
        <form class="date-time-form" id="dateTimeForm">
            <h2>Add Available Dates and Times</h2>
            <label for="course_select">Select Course:</label>
            <select id="course_select" name="selected_course_id" required>
                <option value="">-- Select Course --</option>
                <?php
                $course_options = $conn->query("SELECT id, training_theme FROM booking");
                while ($course = $course_options->fetch_assoc()) {
                    echo "<option value='{$course['id']}'>{$course['training_theme']}</option>";
                }
                ?>
            </select>

            <label for="selected_date">Select Date:</label>
            <input type="text" id="selected_date" name="selected_date" placeholder="Select date" required>

            <label for="selected_time">Select Time:</label>
            <input type="text" id="selected_time" name="selected_time" placeholder="Select time" required>

            <button type="submit">Add Date and Time</button>
        </form>

        <!-- Display Existing Courses -->
        <h2>Existing Courses</h2>
        <table class="course-table">
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Description</th>
                    <th>Fee</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="courseTableBody">
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr data-id="<?php echo $row['id']; ?>">
                    <td><?php echo htmlspecialchars($row['training_theme']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td>R<?php echo number_format($row['fee'], 2); ?></td>
                    <td><img src="images/<?php echo htmlspecialchars($row['image']); ?>" alt="Course Image" style="max-width:80px;"></td>
                    <td>
                        <button class="editButton">Edit</button>
                        <button class="deleteButton">Delete</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Display Available Dates and Times -->
        <h2>Available Events</h2>
        <table class="course-dates-table">
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($date_row = $date_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($date_row['training_theme']); ?></td>
                    <td><?php echo htmlspecialchars($date_row['date']); ?></td>
                    <td><?php echo htmlspecialchars($date_row['time']); ?></td>
                    <td><a href="?delete_date=<?php echo $date_row['date_id']; ?>" onclick="return confirm('Are you sure you want to delete this date?');">Delete</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php include 'includes/admin_footer.php'; ?>
    <script>
        // JavaScript for date and time picker, AJAX handling, and form submissions
        $(document).ready(function() {
            // Configure flatpickr for date and time inputs
            flatpickr("#selected_date", { dateFormat: "Y-m-d" });
            flatpickr("#selected_time", { enableTime: true, noCalendar: true, dateFormat: "H:i" });

            // AJAX handling for course form submission
            $('#courseForm').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append('action', 'add_update_course');
                
                $.ajax({
                    type: 'POST',
                    url: '',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        var data = JSON.parse(response);
                        $('#successMessage').text(data.message).show();
                        if (data.status === 'success') {
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    }
                });
            });

            // AJAX handling for adding date and time slots
            $('#dateTimeForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    type: 'POST',
                    url: '',
                    data: $(this).serialize() + '&action=add_course_date_time',
                    success: function(response) {
                        var data = JSON.parse(response);
                        $('#successMessage').text(data.message).show();
                        if (data.status === 'success') {
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    }
                });
            });
            $('.editButton').click(function() {
        // Get the course data from the corresponding row
        var row = $(this).closest('tr');
        var courseId = row.data('id');
        var trainingTheme = row.children('td').eq(0).text();
        var description = row.children('td').eq(1).text();
        var fee = row.children('td').eq(2).text().replace('R', '').replace(',', ''); // Remove currency symbol and commas
        var imageSrc = row.find('img').attr('src'); // Get image source

        // Populate the form fields with the course data
        $('#course_id').val(courseId);
        $('#training_theme').val(trainingTheme);
        $('#description').val(description);
        $('#fee').val(fee);
        $('#image').val(''); // Reset the file input (this won't show the filename, but it will allow a new upload)

        // Scroll to the course form
        $('html, body').animate({
            scrollTop: $('#courseForm').offset().top
        }, 500); // 500ms duration for smooth scrolling
    });


            // Handle delete button click for each course
            $('.deleteButton').click(function() {
                if (confirm('Are you sure you want to delete this course?')) {
                    var courseId = $(this).closest('tr').data('id');
                    window.location.href = '?delete=' + courseId;
                }
            });
        });
    </script>
</body>

</html>
