<?php
session_start();
// Redirect to login page after 5 seconds
header("refresh:5;url=sign.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verified</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #b0c4de; 
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #333;
        }

        .success-container {
            text-align: center;
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 600%;
        }

        .success-container h1 {
            font-size: 24px;
            color: black;
            margin-bottom: 20px;
        }

        .success-container p {
            font-size: 16px;
            margin-bottom: 20px;
        }

        .countdown {
            font-size: 18px;
            font-weight: bold;
            color: #555;
        }
    </style>
    <script>
        // Countdown function
        function startCountdown(seconds) {
            var countdownElement = document.getElementById('countdown');
            var timeLeft = seconds;

            var countdownInterval = setInterval(function () {
                if (timeLeft > 0) {
                    countdownElement.textContent = timeLeft + " seconds";
                    timeLeft--;
                } else {
                    clearInterval(countdownInterval);
                }
            }, 1000);
        }

        // Start the countdown on page load
        window.onload = function () {
            startCountdown(5); // Set to 5 seconds
        };
    </script>
</head>
<body>
    <div class="success-container">
        <h1>Email Verified!</h1>
        <p>Your email has been successfully verified. You can now log in to your account.</p>
        <p class="countdown">Redirecting in <span id="countdown">5 seconds</span>...</p>
    </div>
</body>
</html>
