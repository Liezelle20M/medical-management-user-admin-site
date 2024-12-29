<?php
session_start();

// Check if the admin is logged in by verifying the session variable
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Admin is not logged in, redirect to the admin login page
    header('Location: admin_login.php');
    exit();
}

include("includes/db_connection.php");

// Fetch data from other tables
$sql = "SELECT * FROM manageclinicalcode";
$result = $conn->query($sql);


$sqlcon = "SELECT * FROM pmb_conditions";
$resultcon = $conn->query($sqlcon);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clinical Codes</title>
    <link rel="stylesheet" href="styles1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<?php include 'includes/admin_header.php'; ?>
  <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-image">
            <img src="images/med.png" alt="Manage Medicolegal">
            <div class="hero-text">
                <h1>MANAGE CLINICAL QUERIES</h1>
            </div>
        </div>
    </section>

<main>
    <!-- Section for Adding New Clinical Code and Managing Clinical Code Table -->
    <section class="query-form">
        <form action="manageClinical.php" method="POST">
            <label for="query-name">Query Name</label>
            <input type="text" id="query-name" name="query-name" placeholder="Query 9">
            <label for="price">Price</label>
            <input type="text" id="price" name="price" placeholder="R 2000.00">
            <button type="submit" name="add-new-query">Add New Query</button>
        </form>
    </section>

    <section class="query-list">
        <h2>Manage Clinical Codes</h2>
        <table>
            <thead>
                <tr>
                    <th>Query ID</th>
                    <th>Query Name</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['query_name']); ?></td>
                            <td>R <?php echo htmlspecialchars($row['price']); ?></td>
                            <td>
                                <form action="edit_condition.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                    <input type="hidden" name="table" value="manageclinicalcode">
                                    <button class="edit-btn"><i class="fa-solid fa-pen"></i></button>
                                </form>
                                <form action="delete_condition.php" method="POST" onsubmit="return confirmDelete();" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                    <input type="hidden" name="table" value="manageclinicalcode">
                                    <button class="delete-btn"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4">No queries found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <!-- Section for Adding New Reason and Managing Reasons Table -->
   

    <!-- Section for Adding New PMB Condition and Managing PMB Conditions Table -->
    <section class="query-form">
        <form action="manageClinical.php" method="POST">
            <label for="pmb-condition">PMB Conditions</label>
            <input type="text" id="pmb-conditions" name="pmb-conditions" placeholder="Enter PMB condition">
            <button type="submit" name="add-new-condition">Add New Condition</button>
        </form>
    </section>

    <section class="query-list">
        <h2>PMB Conditions</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>PMB Condition</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultcon->num_rows > 0): ?>
                    <?php while ($row = $resultcon->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['pmb_condition']); ?></td>
                            <td>
                                <form action="edit_condition.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                    <input type="hidden" name="table" value="pmb_conditions">
                                    <button class="edit-btn"><i class="fa-solid fa-pen"></i></button>
                                </form>
                                <form action="delete_condition.php" method="POST" onsubmit="return confirmDelete();" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                    <input type="hidden" name="table" value="pmb_conditions">
                                    <button class="delete-btn"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3">No PMB conditions found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>

<script>
    function confirmDelete() {
        return confirm('Are you sure you want to delete this field?');
    }
</script>

<?php include 'includes/admin_footer.php'; ?>

</body>
<style>
  /* Global Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Arial', sans-serif;
    background-color: white;
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

header nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

header .logo {
    font-size: 24px;
    font-weight: bold;
}

header nav ul {
    display: flex;
    list-style-type: none;
}

header nav ul li {
    margin-left: 20px;
}

header nav ul li a {
    color: white;
    text-decoration: none;
    font-size: 16px;
}

header .login-btn a {
    background-color: blue;
    padding: 10px 20px;
    border-radius: 5px;
    color: white;
    text-decoration: none;
}  
  /* Hero Section Styling */
        .hero-section {
            width: 100%;
            height: 60vh; /* Reduce the height to half the viewport height */
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
            object-fit: cover; /* Ensures the image covers the entire area without distortion */
        }

        .hero-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%); /* Center the text vertically and horizontally */
            color: white;
            text-align: center;
            font-size: 2.5rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6); /* Adds shadow to make text stand out */
        }



/* Query Form */
.query-form {

    padding: 20px;
    margin: 20px;
    
    
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
}

/* Using Flexbox to place label and input side by side */
.query-form form {
    display: grid;
    grid-template-columns: 1fr 2fr; /* Label 1/3 and Input 2/3 */
    gap: 10px 20px; /* Space between items */
    align-items: center; /* Align vertically */
}

.query-form label {
    color: black;
    display: block;
    margin-bottom: 30px; /* Prevent margin below */
}

.query-form input {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    width: 100%;
    background-color: #F5F5F5;
    margin-bottom: 30px;
}

/* Center the submit button and stretch it across the full form width */
.query-form button {
    grid-column: 1 / -1; /* Span button across the form */
    background-color: black;
    color: white;
    padding: 10px 70px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    justify-self: flex-end;
    
}


/* Query List */
.query-list {
    margin: 20px auto;
    max-width: 1200px;
    padding: 20px;
    background-color: white;
    border-radius: 10px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
}

.query-list table {
    width: 100%;
    border-collapse: collapse;
}


.query-list td, .query-list th {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    
}

.query-list th {
    
    border-bottom:  5px solid #002a5b;
}

.query-list th {
    background-color: white;
    color: black;
}

.query-list .edit-btn, .query-list .delete-btn {
    background: none;
    border: none;
    cursor: pointer;
    margin-right: 10px;
}

/* Query Claims */

h1 {
    padding: 20px;
}
.query-claims {
    background-color: white;
    padding: 20px;
    margin: 20px;
    border-radius: 10px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    
}

.query-claims form {
    display: flex;
    flex-direction: column;
}


.query-claims input {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    margin-bottom: 20px;
}

.query-claims button {
    background-color: black;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-right: 15px;
     
}





.claim-details {
    padding: 20px;
    background-color: #e0f0ff;
    border-radius: 10px;
    color: black;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Add some shadow for better depth */
    max-width: 800px; /* Ensure it doesn't stretch too much */
    margin: 20px; /* Center it and give it spacing */
    
}

.claim-details p {
    margin-bottom: 10px;
    font-size: 16px; /* Ensure readability */
    line-height: 1.5; /* Better spacing between lines */
}

.claim-details strong {
    color: black; /* Darker color for the strong (label) text */
}



 
</style>
</html>
<?php
// Check if the form is submitted
if (isset($_POST['add-new-query'])) {
    //echo "Form submitted for query.";

    $query_name = $_POST['query-name'];
    $price = $_POST['price'];

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO manageclinicalcode (query_name, price) VALUES (?, ?)");
    $stmt->bind_param("sd", $query_name, $price); // "sdss" = string, decimal, string, string

    // Execute the statement
    if ($stmt->execute()) {
        echo "New query added successfully!";
    } else {
        echo "Error: Can't insert into table" . $stmt->error;
    }

    // Close statement and connection
    $stmt->close();
    header("Location: manageClinical.php");
    exit;
    
}


    // Execute the statement
    if ($stmt->execute()) {
        echo "New query added successfully!";
    } else {
        echo "Error: Can't insert into table" . $stmt->error;
    }

    // Close statement and connection
    $stmt->close();
    header("Location: manageClinical.php");
    exit;
    


if (isset($_POST['add-new-condition'])) {
    //echo "Form submitted for pmb.";
    $pmb_conditions = $_POST['pmb-conditions'];

    // Prepare and bind
    //$stmt = $conn->prepare("INSERT INTO pmb_conditions (pmb_condition) VALUES (?)");
    if ($stmt = $conn->prepare("INSERT INTO pmb_conditions (pmb_condition) VALUES (?)")) {
        $stmt->bind_param("s", $pmb_conditions); 

        if ($stmt->execute() === TRUE) {
            echo "New condition added successfully!";
            $stmt->close();
            header("Location: EditProfile.php");
            exit;
        } else {
            echo "Error: Can't insert into table" . $stmt->error;
            $stmt->close();
        }
    }else {
        echo "Error : ". $conn->error;
    }
   
}


$conn->close();
?>
