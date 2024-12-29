<?php
session_start(); // Start the session

// Check if the success message is set
if (isset($_SESSION['success_message'])) {
    echo "<div class='alert alert-success'>" . $_SESSION['success_message'] . "</div>";
    // Unset the message so it doesn't show again
    unset($_SESSION['success_message']);
}

// Check for error message
if (isset($_SESSION['error_message'])) {
    echo "<div class='alert alert-danger'>" . $_SESSION['error_message'] . "</div>";
    unset($_SESSION['error_message']);}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Signup</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .left-section p {
            text-align: center;
        }
        .social-icons {
            text-align: center;
            margin-top: 15px;
        }
        body, html {
            height: 100%;
            font-family: Arial, sans-serif;
            background-color: #b0c4de;  /* Light blue background for the whole page */
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            display: flex;
            height: 80vh;  /* Reduced height */
            width: 80vw;   /* Reduced width */
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);  /* Optional: Subtle shadow */
            border-radius: 20px;  /* Optional: Rounded corners */
            background-color: rgb(248, 244, 244); /* Ensure a background color for the container */
        }

        /* Left section */
        .left-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
           
            z-index: 5;  /* Make sure it stays on top of the curved effect */
        }

        .left-section h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }/* Style for the alert messages */
.alert {
    position: absolute; /* Position the alert absolutely within the left section */
    top: 10px; /* Set it close to the top of the left section */
    left: 50%; /* Center horizontally */
    transform: translateX(-50%); /* Correct centering by shifting left by 50% */
    padding: 10px;
    border-radius: 5px;
    text-align: center;
    width: 300px; /* Set a fixed width */
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
    z-index: 10; /* Make sure it stays on top of other elements */
}

.alert-success {
    background-color: #d4edda; /* Light green */
    color: #155724; /* Dark green */
}

.alert-danger {
    background-color: #f8d7da; /* Light red */
    color: #721c24; /* Dark red */
}

        form {
            display: flex;
            flex-direction: column;
            width: 80%;
            max-width: 400px;
        }
 /* Input field adjustments */
 input[type="password"], input[type="text"] {
            width: 100%; /* Full width */
            padding-right: 40px; /* Space for the icons */
            box-sizing: border-box; /* Keep size consistent */
            height: 40px;
            border: 1px solid #ccc;
            font-size: 16px;
            margin-bottom: 15px;
            border-radius: 5px;
            padding: 10px;
        }
        input[type="email"] {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            width: 100%;
        }

        /* Container for password input and the toggle icons */
        .password-container {
            position: relative;
            width: 100%; 
        }

       

        /* Common style for both icons (fixed size and position) */
        .toggle-password {
            position: absolute;
            right: 10px; /* Position to the right */
            top: 40%; /* Centered vertically */
            transform: translateY(-50%); /* Vertical alignment */
            cursor: pointer;
            width: 20px; /* Fixed width */
            height: 20px; /* Fixed height */
            z-index: 1; /* Stay above the input field */
        }

        /* Specific styling for open eye */
        #eyeOpen {
            display: none; /* Hidden by default */
        }

        .forgot-password {
            color: #007bff;
            font-size: 12px;
            margin-bottom: 15px;
            text-decoration: none;
            align-self: flex-start;
        }

        .signin-btn {
            background-color: black;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 20px;
            cursor: pointer;
            text-decoration: none;
        }

        .signin-btn:hover {
            background-color: #333;
        }

        .social-icons img {
            width: 30px;
            margin: 0 10px;
            cursor: pointer;
        }

        .social-icons img:hover {
            opacity: 0.8;
        }

        /* Styling the right section with a curved shape */
        .right-section {
            position: relative;
            background-color: #153f75;
            color: white;
            text-align: center;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-top-left-radius: 100% 100%;
            border-bottom-left-radius: 100% 100%;
            height: 100%;
            width: 50%;
            z-index: 1;
            flex: 1;
        }

        .right-section h2 {
            font-size: 24px;
            margin-bottom: 15px;
        }

        .right-section p {
            font-size: 14px;
            margin-bottom: 30px;
        }

        .signup-btn {
            background-color: black;
            color: white;
            padding: 10px 40px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
        }

        .signup-btn:hover {
            background-color: #333;
        }

        /* Mobile Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                height: 100vh; /* Ensure full height usage in mobile view */
                width: 100vw;  /* Take the full width of the screen */
            }

            .left-section, .right-section {
                width: 100%;
                height: 50%; /* Ensure each section takes half the height */
                border-radius: 0; /* Remove rounded corners in mobile view */
            }

            .left-section h1, .right-section h2 {
                font-size: 24px;
            }

            .left-section form {
                width: 100%;
            }

            .signin-btn, .signup-btn {
                width: 100%;
            }

            .forgot-password {
                text-align: left;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="left-section">
            <h1>WELCOME TO VAHSA</h1>
           
            <form action="login.php" method="POST">
            <input type="email" name="email" placeholder="Email Address" required>
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <!-- Crossed Eye (visible by default) -->
                    <img class="toggle-password" id="eyeCrossed" src="images/eye-crossed.png" alt="Hide Password">
                    <!-- Open Eye -->
                    <img class="toggle-password" id="eyeOpen" src="images/eye (1).png" alt="Show Password">
                </div>
                <a href="forgot.php" class="forgot-password">Forgot your password?</a>
                <button type="submit" class="signin-btn">SIGN IN</button>
                <p>     </p>
                <div class="social-icons">
                  
                   
                </div>
            </form>
            <div>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>
    </div>
        </div>
        <div class="right-section">
            <h2>HELLO, THERE!</h2>
            <p>Not registered yet? Register to use all our site features</p>
            <a href="create.php" class="signup-btn">SIGN UP</a>
        </div>
    </div>

    <script>
        const eyeCrossed = document.getElementById('eyeCrossed');
        const eyeOpen = document.getElementById('eyeOpen');
        const passwordInput = document.getElementById('password');

        eyeCrossed.addEventListener('click', () => {
            passwordInput.setAttribute('type', 'text');
            eyeCrossed.style.display = 'none'; // Hide crossed eye
            eyeOpen.style.display = 'block'; // Show open eye
        });

        eyeOpen.addEventListener('click', () => {
            passwordInput.setAttribute('type', 'password');
            eyeOpen.style.display = 'none'; // Hide open eye
            eyeCrossed.style.display = 'block'; // Show crossed eye
        });
    </script>
</body>
</html>
