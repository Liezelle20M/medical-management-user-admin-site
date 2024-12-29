<?php
session_start();
include 'includes/db_connection.php';

// Fetch data from the session
$invoice_id = $_SESSION['invoice_id'] ?? 0;
$query_name = $_SESSION['query_name'] ?? 'N/A';
$total_price = $_SESSION['total_price'] ?? 0;

if ($invoice_id == 0) {
    die("Invalid invoice.");
}

// Fetch billing details from the invoices table
$sql_invoice = "SELECT user_email, billing_first_name, billing_last_name, billing_address, billing_city, billing_postal_code, billing_province, billing_country FROM invoices WHERE invoice_id = ?";
$stmt_invoice = $conn->prepare($sql_invoice);
$stmt_invoice->bind_param("i", $invoice_id);
$stmt_invoice->execute();
$result_invoice = $stmt_invoice->get_result();

if ($result_invoice->num_rows === 0) {
    die("Invoice not found.");
}

$invoice_data = $result_invoice->fetch_assoc();
$stmt_invoice->close();

// Extract billing details
$email = $invoice_data['user_email'] ?? '';
$first_name = $invoice_data['billing_first_name'] ?? '';
$last_name = $invoice_data['billing_last_name'] ?? '';
$street_address = $invoice_data['billing_address'] ?? '';
$city = $invoice_data['billing_city'] ?? '';
$postal_code = $invoice_data['billing_postal_code'] ?? '';
$province = $invoice_data['billing_province'] ?? '';
$country = $invoice_data['billing_country'] ?? 'South Africa';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        // Store valid email in the session
        $_SESSION['email'] = $_POST['email'];
    } else {
        echo "Invalid email format!";
        exit();
    }

    // Store other fields in the session
    $_SESSION['first_name'] = $_POST['first-name'];
    $_SESSION['last_name'] = $_POST['last-name'];
    $_SESSION['street_address'] = $_POST['street-address'];
    $_SESSION['city'] = $_POST['city'];
    $_SESSION['postal_code'] = $_POST['postal-code'];
    $_SESSION['province'] = $_POST['province'];
    $_SESSION['country'] = $_POST['country'];
	
    // Redirect to the payment processing page
    header("Location: payfast_process.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>VAHSA Billing</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/styles3.css">
    <style>
        .pay-now-btn {
            background-color: #0C4375;
            color: white;
            width: 176px;
            height: 56px;
            border: none;
            font-size: 18px;
            border-radius: 5px;
        }
        .pay-now-btn:hover {
            background-color: #09496B;
        }
        .medico-legal-table {
            width: 100%;
            border-collapse: collapse;
        }
        .medico-legal-table td {
            padding: 10px;
            text-align: right;
            color: #000000;
        }
        .medico-legal-table td:first-child {
            text-align: left;
        }
        .medico-legal-table tr {
            border-bottom: 1px solid #d3d3d3;
        }
        .payfast-image {
            width: 200px; /* Adjust the size as needed */
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <form action="" method="POST">
        <div class="container-fluid py-5" style="background-color: #D3DCE7;">
            <div class="row justify-content-center">
                <!-- Billing Details Column (Left Side) -->
                <div class="col-md-6">
                    <h2 class="mb-4" style="color: #000000;">Billing Details</h2>
                    <div class="mb-3">
                        <label for="first-name" class="form-label" style="color: #000000;">First Name*</label>
                        <input type="text" class="form-control" id="first-name" name="first-name" required style="background-color: #F5F5F5;" value="<?php echo htmlspecialchars($first_name); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="last-name" class="form-label" style="color: #000000;">Last Name*</label>
                        <input type="text" class="form-control" id="last-name" name="last-name" required style="background-color: #F5F5F5;" value="<?php echo htmlspecialchars($last_name); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="street-address" class="form-label" style="color: #000000;">Street Address*</label>
                        <input type="text" class="form-control" id="street-address" name="street-address" required style="background-color: #F5F5F5;" value="<?php echo htmlspecialchars($street_address); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="city" class="form-label" style="color: #000000;">City*</label>
                        <input type="text" class="form-control" id="city" name="city" required style="background-color: #F5F5F5;" value="<?php echo htmlspecialchars($city); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="postal-code" class="form-label" style="color: #000000;">Postal Code*</label>
                        <input type="text" class="form-control" id="postal-code" name="postal-code" required style="background-color: #F5F5F5;" value="<?php echo htmlspecialchars($postal_code); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="province" class="form-label" style="color: #000000;">Province*</label>
                        <input type="text" class="form-control" id="province" name="province" required style="background-color: #F5F5F5;" value="<?php echo htmlspecialchars($province); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="country" class="form-label" style="color: #000000;">Country*</label>
                        <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($country); ?>" readonly style="background-color: #F5F5F5;">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label" style="color: #000000;">Email*</label>
                        <input type="email" class="form-control" id="email" name="email" required style="background-color: #F5F5F5;" value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                </div>

                <!-- Service Details and Pay Now Button (Right Side) -->
                <div class="col-md-4 medico-legal-section">
                    <!-- Service Details Table -->
                    <table class="medico-legal-table">
                        <tr>
                            <td>Query Type:</td>
                            <td><?php echo htmlspecialchars($query_name); ?></td>
                        </tr>
                        <tr>
                            <td>Total:</td>
                            <td><strong>R <?php echo number_format($total_price, 2); ?></strong></td>
                        </tr>
                    </table>

                    <!-- Pay Now Button -->
                    <input type="hidden" name="total_price" value="<?php echo number_format($total_price, 2); ?>">
                    
<!-- Payment Method Section with PayFast Image -->
                    <div class="payment-method mt-4">
                        <!-- PayFast Payment Gateway Image -->
                        <img src="images/payfast-logo.png" alt="PayFast Payment Gateway" class="payfast-image">
                        
                        <!-- Submit Payment Button -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="pay-now-btn">Pay Now</button>
                        </div>
            </div>
        </div>
    </form>

    <?php include 'includes/footer.php'; ?>
</body>

</html>
