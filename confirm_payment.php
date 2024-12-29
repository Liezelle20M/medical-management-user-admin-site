<?php
session_start();

// Display session variables for debugging purposes
echo "<h2>Debugging Information</h2>";
echo "<p>Query Name: " . ($_SESSION['query_name'] ?? 'Not Set') . "</p>";
echo "<p>Total Price: " . ($_SESSION['total_price'] ?? 'Not Set') . "</p>";
echo "<p>Email: " . ($_SESSION['email'] ?? 'Not Set') . "</p>";
echo "<p>First Name: " . ($_SESSION['first_name'] ?? 'Not Set') . "</p>";
echo "<p>Last Name: " . ($_SESSION['last_name'] ?? 'Not Set') . "</p>";
echo "<p>Medical Practice Type: " . ($_SESSION['medical_practice_type'] ?? 'Not Set') . "</p>";
echo "<p>Street Address: " . ($_SESSION['street_address'] ?? 'Not Set') . "</p>";
echo "<p>City: " . ($_SESSION['city'] ?? 'Not Set') . "</p>";
echo "<p>Postal Code: " . ($_SESSION['postal_code'] ?? 'Not Set') . "</p>";
echo "<p>Province: " . ($_SESSION['province'] ?? 'Not Set') . "</p>";
echo "<p>Country: " . ($_SESSION['country'] ?? 'Not Set') . "</p>";

// Add a link to proceed to PayFast
echo '<a href="payfast_process.php" style="display: inline-block; padding: 10px 20px; background-color: #0C4375; color: white; text-decoration: none; border-radius: 5px;">Click here to proceed to PayFast</a>';
?>
