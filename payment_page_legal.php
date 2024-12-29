<?php
session_start();

// Fetch data from the session
$query_type = $_SESSION['query_type'] ?? 'N/A';
$query_price = $_SESSION['fee'] ?? 0;
$required_service = $_SESSION['required_service'] ?? 'N/A';

// Calculate 30% of the query price for the deposit
$deposit_percentage = 0.30;
$deposit_amount = round($query_price * $deposit_percentage, 2);

// Store deposit amount in the session
$_SESSION['deposit_amount'] = $deposit_amount;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        // Store valid email in the session
        $_SESSION['email'] = $_POST['email'];
    } else {
        $_SESSION['error_message'] = "Invalid email format!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Store other fields in the session
    $_SESSION['first_name'] = htmlspecialchars(trim($_POST['first-name']));
    $_SESSION['last_name'] = htmlspecialchars(trim($_POST['last-name']));
    $_SESSION['medical_practice_type'] = htmlspecialchars(trim($_POST['medical-practice-type']));
    $_SESSION['street_address'] = htmlspecialchars(trim($_POST['street-address']));
    $_SESSION['city'] = htmlspecialchars(trim($_POST['city']));
    $_SESSION['postal_code'] = htmlspecialchars(trim($_POST['postal-code']));
    $_SESSION['province'] = htmlspecialchars(trim($_POST['province']));
    $_SESSION['country'] = htmlspecialchars(trim($_POST['country']));
    
    // Redirect to the payment processing page
    header("Location: payfast_process_legal.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medico-Legal Billing</title>
    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery CDN -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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
            cursor: pointer;
        }

        .pay-now-btn:hover {
            background-color: #09496B;
        }

        .payment-method {
            margin-top: 20px;
            text-align: center;
        }

        .payfast-image {
            width: 200px; /* Adjust the size as needed */
            margin-bottom: 20px;
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
            font-weight: bold;
        }

        .medico-legal-table tr {
            border-bottom: 1px solid #d3d3d3;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .medico-legal-table td {
                display: block;
                text-align: left;
            }

            .medico-legal-table tr {
                margin-bottom: 15px;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <?php if(isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger text-center" role="alert">
            <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']); 
            ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="container-fluid py-5" style="background-color: #D3DCE7;">
            <div class="row justify-content-center">
                <!-- Billing Details Column (Left Side) -->
                <div class="col-md-6">
                    <h2 class="mb-4" style="color: #000000;">Billing Details</h2>
                    <div class="mb-3">
                        <label for="first-name" class="form-label" style="color: #000000;">First Name*</label>
                        <input type="text" class="form-control" id="first-name" name="first-name" required style="background-color: #F5F5F5;" value="<?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="last-name" class="form-label" style="color: #000000;">Last Name*</label>
                        <input type="text" class="form-control" id="last-name" name="last-name" required style="background-color: #F5F5F5;" value="<?php echo htmlspecialchars($_SESSION['last_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="medical-practice-type" class="form-label" style="color: #000000;">Medical Practice Type</label>
                        <input type="text" class="form-control" id="medical-practice-type" name="medical-practice-type" style="background-color: #F5F5F5;" value="<?php echo htmlspecialchars($_SESSION['medical_practice_type'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="street-address" class="form-label" style="color: #000000;">Street Address*</label>
                        <input type="text" class="form-control" id="street-address" name="street-address" required style="background-color: #F5F5F5;" value="<?php echo htmlspecialchars($_SESSION['street_address'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="city" class="form-label" style="color: #000000;">City*</label>
                        <input type="text" class="form-control" id="city" name="city" required style="background-color: #F5F5F5;" value="<?php echo htmlspecialchars($_SESSION['city'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="postal-code" class="form-label" style="color: #000000;">Postal Code*</label>
                        <input type="text" class="form-control" id="postal-code" name="postal-code" required style="background-color: #F5F5F5;" value="<?php echo htmlspecialchars($_SESSION['postal_code'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="province" class="form-label" style="color: #000000;">Province*</label>
                        <input type="text" class="form-control" id="province" name="province" required style="background-color: #F5F5F5;" value="<?php echo htmlspecialchars($_SESSION['province'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="country" class="form-label" style="color: #000000;">Country*</label>
                        <input type="text" class="form-control" id="country" name="country" value="South Africa" readonly style="background-color: #F5F5F5;">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label" style="color: #000000;">Email*</label>
                        <input type="email" class="form-control" id="email" name="email" required style="background-color: #F5F5F5;" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Service Details, Payment, and Submit Button (Right Side) -->
                <div class="col-md-4 medico-legal-section">
                    <!-- Service Details Table -->
                    <table class="medico-legal-table">
                        <tr>
                            <td>Query Type:</td>
                            <td><?php echo htmlspecialchars($query_type); ?></td>
                        </tr>
                        <tr>
                            <td>Price:</td>
                            <td>R <?php echo number_format($query_price, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Required Service:</td>
                            <td><?php echo htmlspecialchars($required_service); ?></td>
                        </tr>
                        <tr>
                            <td>Deposit (30%):</td>
                            <td><strong>R <?php echo number_format($deposit_amount, 2); ?></strong></td>
                        </tr>
                    </table>

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
            </div>
        </div>
    </form>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
