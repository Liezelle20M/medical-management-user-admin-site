<?php
$servername = "localhost";
$username = "u853445682_vahsa";
$password = "TechAlchemists@2024";
$dbname = "u853445682_vahsa";

//connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Checking
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
