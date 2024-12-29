<?php
// Database connection
include("includes/db_connection.php");

// Start session if it hasn't been started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT title, first_name, last_name, bhf_number, telephone, email FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_result->num_rows > 0) {
        $user = $user_result->fetch_assoc();
    } else {
        die("User not found.");
    }
} else {
    die("User not logged in.");
}

// Retrieve the training theme and course information
$training_theme = $_SESSION['training_theme'] ?? '';
if (empty($training_theme)) die("Training theme not set in session.");

$booking_query = "SELECT id, fee FROM booking WHERE training_theme = ?";
$booking_stmt = $conn->prepare($booking_query);
$booking_stmt->bind_param('s', $training_theme);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();
$booking = $booking_result->fetch_assoc();

$fee = $booking['fee'] ?? 0;
$course_id = $booking['id'] ?? '';

if (!$course_id) die("No course found for the selected training theme.");

$date_query = "
    SELECT date, time
    FROM course_dates
    WHERE course_id = ?
    AND date != '1970-01-01'
    AND date != '0000-00-00'
";
$date_stmt = $conn->prepare($date_query);
$date_stmt->bind_param('i', $course_id);
$date_stmt->execute();
$date_result = $date_stmt->get_result();

$available_dates = [];
$times_for_dates = [];
while ($row = $date_result->fetch_assoc()) {
    $available_dates[] = $row['date'];
    $times_for_dates[$row['date']][] = $row['time'];
}

// After calculating the total price and setting number of delegates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $num_delegates = $_POST['delegates'];
    $total_amount = $fee * $num_delegates;
    $payable_amount = $total_amount * 0.30;

    // Storing required values in the session
    $_SESSION['training_theme'] = $_POST['training_theme'];
    $_SESSION['num_delegates'] = $num_delegates;
    $_SESSION['training_date'] = $_POST['date'];
    $_SESSION['fee'] = $fee; // Store course fee in session
    $_SESSION['payableAmount'] = $payable_amount; // Store the calculated payable amount

    // Redirect to the courses payment page
    header("Location: payment_page_courses.php");
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VAHSA Booking</title>

    <!-- Include Flatpickr CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <link rel="stylesheet" href="assets/styles7.css">
    <link rel="stylesheet" href="assets/styles8.css">
    <link rel="stylesheet" href="assets/styles3.css">
    <link rel="stylesheet" href="assets/styles4.css">
    <style>
    /* General body styling */
body {
    font-family: Arial, sans-serif;
    background-color: #fff;
    margin: 0;
    padding: 0;
}

/* Scoped styles for the form */
.form-container {
    width: 90%;
    max-width: 1000px;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    margin: 50px auto;
    text-align: center;
}

/* Scoped h1 styling within .form-container to avoid header conflicts */
.form-container h1 {
    font-size: 2.2em;
    font-weight: bold;
    color: black;
    margin-bottom: 15px;
}

.info-message {
    text-align: center;
    margin-bottom: 30px;
    padding: 15px;
    background-color: #e9f7ef;
    border: 1px solid #c3e6cb;
    border-radius: 5px;
    color: #155724;
    font-size: 0.9em;
}

form {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

label {
    margin-bottom: 5px;
    font-weight: bold;
    align-self: flex-start;
}

input, select {
    margin-bottom: 15px;
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 4px;
    width: 100%;
    max-width: 100%;
}

input[readonly] {
    background-color: #f1f1f1;
}

button {
    padding: 10px;
    background-color: #007bff;
    color: white;
    font-size: 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    width: 100%;
    max-width: 200px;
    margin-top: 20px;
}

button:hover {
    background-color: #0056b3;
}

/* Media query for smaller screens */
@media (max-width: 600px) {
    .form-container h1 {
        font-size: 1.8em;
    }

    .info-message {
        font-size: 0.8em;
        padding: 10px;
    }

    input, select {
        font-size: 14px;
        padding: 8px;
    }

    button {
        font-size: 14px;
        padding: 8px;
    }
}
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<main>
    <h1>Book Your Training</h1>
    <div class="form-container">
      
        <div class="info-message">
            <strong>Price Calculation:</strong><br>
            The total amount is based on the course fee multiplied by the number of delegates you wish to enroll.<br>
            Upon registration, a deposit of 30% of the total amount will be required to secure your booking. The remaining balance will be payable on the day of the course.
        </div>
        <form action="" method="POST">
            <label for="training-theme">Training Theme</label>
            <input type="text" id="training-theme" name="training_theme" value="<?php echo htmlspecialchars($training_theme); ?>" readonly>

            <label for="name">Names</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['first_name']); ?>" readonly required>

            <label for="surname">Surname</label>
            <input type="text" id="surname" name="surname" value="<?php echo htmlspecialchars($user['last_name']); ?>" readonly required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly required>

            <label for="telephone">Telephone</label>
            <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone']); ?>" readonly required>

            <label for="delegates">No. of Delegates for training</label>
            <input type="number" id="delegates" name="delegates" value="1" required>

            <label for="date">Choose Date for Training</label>
            <input type="text" id="date" name="date" required readonly>

            <label for="time">Time for Selected Date</label>
            <select id="time" name="time" required>
                <option value="">Select a date first</option>
            </select>

            <label for="amount">Total Amount</label>
            <input type="text" id="amount" name="amount" value="R 0.00" readonly>

            <label for="payablern">Amount to pay: 30% of Total Amount</label>
            <input type="text" id="payablern" name="payablern" value="R 0.00" readonly>

            <button type="submit">Proceed</button>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const availableDates = <?php echo json_encode($available_dates); ?>;
        const timesForDates = <?php echo json_encode($times_for_dates); ?>;

        flatpickr("#date", {
            dateFormat: "Y-m-d",
            minDate: "today",
            enable: availableDates,
            disableMobile: true,
            onChange: function (selectedDates, dateStr) {
                const timeSelect = document.getElementById("time");
                timeSelect.innerHTML = "";

                const times = timesForDates[dateStr] || [];
                if (times.length > 0) {
                    times.forEach(function (time) {
                        const option = document.createElement("option");
                        option.value = time;
                        option.textContent = time;
                        timeSelect.appendChild(option);
                    });
                } else {
                    const option = document.createElement("option");
                    option.value = "";
                    option.textContent = "No times available";
                    timeSelect.appendChild(option);
                }
            }
        });

        const delegatesInput = document.getElementById("delegates");
        delegatesInput.addEventListener("input", calculateTotal);

        function calculateTotal() {
            var fee = <?php echo $fee; ?>;
            var numDelegates = Math.max(document.getElementById("delegates").value || 1, 1);
            var totalAmount = fee * numDelegates;
            var thirtyPercent = totalAmount * 0.30;

            document.getElementById("amount").value = "R " + totalAmount.toFixed(2);
            document.getElementById("payablern").value = "R " + thirtyPercent.toFixed(2);
        }

        calculateTotal();
    });
</script>
</body>
</html>
