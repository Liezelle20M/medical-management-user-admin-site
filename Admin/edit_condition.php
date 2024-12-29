<?php
include '../includes/db_connection.php';

if (isset($_POST['id']) && isset($_POST['table'])) {
    $id = intval($_POST['id']);
    $table = $_POST['table'];

    $allowedTables = ['manageclinicalcode', 'pmb_conditions', 'reasons_for_partial_or_nonpayment', 'claimsqueries', 'users'];
    if (!in_array($table, $allowedTables)) {
        exit("Invalid table.");
    }

    $stmt = $conn->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        exit("Record not found.");
    }
} else {
    exit("ID or Table not specified.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Condition</title>
    <link rel="stylesheet" href="styles1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<?php include 'includes/admin_header.php'; ?>

<section class="banner"></section>

<h1>Edit Condition</h1>

<form action="edit_condition.php" method="POST">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
    <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>">

    <?php
    if ($table === 'manageclinicalcode') {
        ?>
        <label for="query_name">Query Name:</label>
        <input type="text" name="query_name" value="<?php echo htmlspecialchars($row['query_name']); ?>" required><br>

        <label for="price">Price:</label>
        <input type="text" name="price" value="<?php echo htmlspecialchars($row['price']); ?>" required><br>
        <?php
    } elseif ($table === 'reasons_for_partial_or_nonpayment') {
        ?>
        <label for="reason">Reason for Partial Payment/Non-Payment:</label>
        <input type="text" name="reason" value="<?php echo htmlspecialchars($row['reason']); ?>" required><br>
        <?php
    } elseif ($table === 'pmb_conditions') {
        ?>
        <label for="pmb_condition">PMB Condition:</label>
        <input type="text" name="pmb_condition" value="<?php echo htmlspecialchars($row['pmb_condition']); ?>" required><br>
        <?php
    }
    ?>

    <button type="submit">Update</button>
</form>

<?php include 'includes/admin_footer.php'; ?>
</body>
<style>
/* Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Arial', sans-serif;
    background-color: white;
}

header {
    background-color: #002a5b;
    color: white;
    padding: 20px;
    margin-bottom: 70px;
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

.banner {
    margin-bottom: 70px;
    background-image: url('images/banner.jpeg');
    height: 400px;
    background-size: cover;
    background-position: center;
    display: flex;
    justify-content: center;
    align-items: center;
}
   
h1 {
    text-align: center;
    padding: 20px;
    color: #002a5b;
}

form {
    width: 50%;
    margin: 0 auto;
    background-color: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
}

form label {
    font-size: 1.1rem;
    color: #333;
    display: block;
    margin-bottom: 10px;
}

form input[type="text"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 1rem;
    box-sizing: border-box;
    transition: border 0.3s ease;
}

form input[type="text"]:focus {
    border-color: #002a5b;
    outline: none;
}

button[type="submit"] {
    background-color: #002a5b;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    cursor: pointer;
    width: 100%;
}

button[type="submit"]:hover {
    background-color: #004080;
}

@media (max-width: 768px) {
    form {
        width: 90%;
    }
}

@media (max-width: 480px) {
    h1 {
        font-size: 1.5rem;
        padding: 15px;
    }
    
    form {
        padding: 20px;
    }
    
    form label {
        font-size: 1rem;
    }
    
    button[type="submit"] {
        padding: 10px;
    }
}
</style>
</html>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $table = $_POST['table'];

    $allowedTables = ['manageclinicalcode', 'pmb_conditions', 'reasons_for_partial_or_nonpayment'];
    if (!in_array($table, $allowedTables)) {
        exit("Invalid table.");
    }

    if ($table === 'manageclinicalcode') {
        $query_name = $_POST['query_name'] ?? null;
        $price = $_POST['price'] ?? null;

        if ($query_name && $price) {
            $stmt = $conn->prepare("UPDATE manageclinicalcode SET query_name = ?, price = ? WHERE id = ?");
            $stmt->bind_param("ssi", $query_name, $price, $id);
        } else {
            exit("Please provide both query name and price.");
        }

    } elseif ($table === 'reasons_for_partial_or_nonpayment') {
        $reason = $_POST['reason'] ?? null;

        if ($reason) {
            $stmt = $conn->prepare("UPDATE reasons_for_partial_or_nonpayment SET reason = ? WHERE id = ?");
            $stmt->bind_param("si", $reason, $id);
        } else {
            exit("Please provide a reason.");
        }

    } elseif ($table === 'pmb_conditions') {
        $pmb_condition = $_POST['pmb_condition'] ?? null;

        if ($pmb_condition) {
            $stmt = $conn->prepare("UPDATE pmb_conditions SET pmb_condition = ? WHERE id = ?");
            $stmt->bind_param("si", $pmb_condition, $id);
        } else {
            exit("Please provide a PMB condition.");
        }
    }

    if ($stmt && $stmt->execute()) {
        echo "Record updated successfully!";
    } else {
        echo "Error updating record: " . $conn->error;
    }

    $stmt->close();
}
?>
