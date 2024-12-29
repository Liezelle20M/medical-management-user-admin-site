<?php
session_start();
// echo '<pre>';
// print_r($_SESSION);
// echo '</pre>';
// Fetch the session data for the course details
$training_theme = $_SESSION['training_theme'] ?? 'N/A';
$num_delegates = $_SESSION['num_delegates'] ?? 0;
$fee = $_SESSION['fee'] ?? 0;
// Calculate the total price and payable amount
$total_price = $fee * $num_delegates;
$application_fee = 100.00;
$payable_amount = ($total_price * 0.30) + $application_fee; // 30% of total + application fee
// Billing information
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$medical_practice_type = $_SESSION['medical_practice_type'] ?? '';
$street_address = $_SESSION['street_address'] ?? '';
$city = $_SESSION['city'] ?? '';
$postal_code = $_SESSION['postal_code'] ?? '';
$province = $_SESSION['province'] ?? '';
$country = $_SESSION['country'] ?? 'South Africa';
$email = $_SESSION['email'] ?? '';
// When the form is submitted, validate the billing information and proceed to payment processing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        // Store the updated billing details in the session
        $_SESSION['first_name'] = $_POST['first-name'];
        $_SESSION['last_name'] = $_POST['last-name'];
        $_SESSION['medical_practice_type'] = $_POST['medical-practice-type'];
        $_SESSION['street_address'] = $_POST['street-address'];
        $_SESSION['city'] = $_POST['city'];
        $_SESSION['postal_code'] = $_POST['postal-code'];
        $_SESSION['province'] = $_POST['province'];
        $_SESSION['country'] = $_POST['country'];
        $_SESSION['email'] = $_POST['email'];
        
        // Store the payable amount in session for the payment page
        $_SESSION['payableAmount'] = $payable_amount;
        
        // Redirect to payment processing page
        header("Location: payfast_process_courses.php");
        exit();
    } else {
        echo "Invalid email format!";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Payment</title>
    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery CDN -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/styles3.css">
    <style>
        /* Styles are the same as payment_page.php */
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
        .payment-method {
            margin-top: 20px;
            text-align: left;
        }
        .payment-options {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
        }
        .payment-label {
            cursor: pointer;
            margin: 10px;
        }
        .payment-img {
            width: 50px;
            height: 50px;
            border-radius: 5px;
            transition: border 0.3s ease;
        }
        .payment-radio {
            display: none;
        }
        .payment-radio:checked + .payment-label img {
            border: 2px solid #0C4375;
        }
        .course-payment-table {
            width: 100%;
            border-collapse: collapse;
        }
        .course-payment-table td {
            padding: 10px;
            text-align: right;
            color: #000000;
        }
        .course-payment-table td:first-child {
            text-align: left;
        }
        .course-payment-table tr {
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
                        <input type="text" class="form-control" id="first-name" name="first-name" value="<?php echo htmlspecialchars($first_name); ?>" required style="background-color: #F5F5F5;">
                    </div>
                    <div class="mb-3">
                        <label for="last-name" class="form-label" style="color: #000000;">Last Name*</label>
                        <input type="text" class="form-control" id="last-name" name="last-name" value="<?php echo htmlspecialchars($last_name); ?>" required style="background-color: #F5F5F5;">
                    </div>
                    <div class="mb-3">
                        <label for="medical-practice-type" class="form-label" style="color: #000000;">Medical Practice Type</label>
                        <input type="text" class="form-control" id="medical-practice-type" name="medical-practice-type" value="<?php echo htmlspecialchars($medical_practice_type); ?>" style="background-color: #F5F5F5;">
                    </div>
                    <div class="mb-3">
                        <label for="street-address" class="form-label" style="color: #000000;">Street Address*</label>
                        <input type="text" class="form-control" id="street-address" name="street-address" value="<?php echo htmlspecialchars($street_address); ?>" required style="background-color: #F5F5F5;">
                    </div>
                    <div class="mb-3">
                        <label for="city" class="form-label" style="color: #000000;">City*</label>
                        <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>" required style="background-color: #F5F5F5;">
                    </div>
                    <div class="mb-3">
                        <label for="postal-code" class="form-label" style="color: #000000;">Postal Code*</label>
                        <input type="text" class="form-control" id="postal-code" name="postal-code" value="<?php echo htmlspecialchars($postal_code); ?>" required style="background-color: #F5F5F5;">
                    </div>
                    <div class="mb-3">
                        <label for="province" class="form-label" style="color: #000000;">Province*</label>
                        <input type="text" class="form-control" id="province" name="province" value="<?php echo htmlspecialchars($province); ?>" required style="background-color: #F5F5F5;">
                    </div>
                    <div class="mb-3">
                        <label for="country" class="form-label" style="color: #000000;">Country*</label>
                        <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($country); ?>" readonly style="background-color: #F5F5F5;">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label" style="color: #000000;">Email*</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required style="background-color: #F5F5F5;">
                    </div>
                </div>
                <!-- Service Details, Payment, and Submit Button (Right Side) -->
                <div class="col-md-4 course-section">
                    <!-- Service Details Table -->
                    <table class="course-payment-table">
                        <tr>
                            <td>Training Theme:</td>
                            <td><?php echo htmlspecialchars($training_theme); ?></td>
                        </tr>
                        <tr>
                            <td>Fee (Per Delegate):</td>
                            <td>R <?php echo number_format($fee, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Number of Delegates:</td>
                            <td><?php echo htmlspecialchars($num_delegates); ?></td>
                        </tr>
                        <tr>
                            <td>Total Fee:</td>
                            <td>R <?php echo number_format($total_price, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Amount Payable:</td>
                            <td><strong>R <?php echo number_format($payable_amount, 2); ?></strong></td>
                        </tr>
                    </table>
                    <!-- Payment Options -->
                     <div class="payment-method mt-4">
                        <!-- PayFast Payment Gateway Image -->
                        <img src="images/payfast-logo.png" alt="PayFast Payment Gateway" class="payfast-image">
                         <div class="text-start mt-4">
                        <button type="submit" class="pay-now-btn mt-4">Pay Now</button>
                    </div>
    
                   
                </div>
            </div>
        </div>
    </form>
    <?php include 'includes/footer.php'; ?>
</body>
</html>