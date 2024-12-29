<?php
session_start(); // Start the session
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login and Sign Up</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background-color: #b0c4de;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
}
.toggle-password {
    cursor: pointer;
    position: absolute;
    right: 10px; /* Adjust position from the right */
    top: 40%;
    transform: translateY(-50%); /* Center vertically */
    width: 24px; /* Set the width for your icons */
    height: 24px; /* Set the height for your icons */
}
.guidelines {
    font-size: 14px;
    color: #333;
    margin-bottom: 10px;
}
.strength {
    font-weight: bold;
    margin-top: 5px;
}
.weak {
    color: red;
}
.medium {
    color: orange;
}
.strong {
    color: green;
}
.requirement {
    color: #999;
}
.requirement.valid {
    color: green;
}
.requirement.invalid {
    color: red;
}
.signup-section input, select {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.container {
    display: flex;
    height: 80vh;
    width: 80vw;
    background-color: #fff;
    border-radius: 15px;
    overflow: hidden;
}

.signin-section {
    flex: 1;
    background-color: #153f75;
    color: white;
    padding: 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    border-radius: 15px 0 0 15px;
    border-top-right-radius: 100% 100%;
    border-bottom-right-radius: 100% 100%;
    min-height: 100%; /* Ensure full height */
}

.signin-section h2 {
    margin-bottom: 20px;
    font-size: 28px;
}

.signin-section p {
    margin-bottom: 30px;
    font-size: 16px;
}

.signin-btn {
    background-color: black;
    color: white;
    padding: 10px 20px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    text-decoration: none;
    transition: background-color 0.3s ease;
}

.signin-btn:hover {
    background-color: #333;
}

.signup-section {
    flex: 1;
    background-color: white;
    padding: 50px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: flex-start;
    overflow: hidden;
}

.signup-section h2 {
    margin-bottom: 20px;
}

.form-container {
    flex: 1;
    width: 100%;
    overflow-y: auto; /* Scrollable form container */
}

.signup-section form {
    display: flex;
    flex-direction: column;
    width: 100%;
}

.signup-section input {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
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
    margin-top: 20px;
    width: 100%;
}

.signup-btn:hover {
    background-color: #333;
}

/* Mobile Responsive Design */
@media (max-width: 768px) {
    .container {
        flex-direction: column;
        height: auto;
        width: 100%;
    }

    .signin-section {
        padding: 20px;
        height: auto; /* Ensure height is flexible */
        min-height: unset;
        order: 2; /* Move to the bottom in mobile view */
        border-radius: 0 0 15px 15px;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .signup-section {
        padding: 20px;
        order: 1; /* Move to the top in mobile view */
    }

    .signin-section h2 {
        font-size: 24px;
    }

    .signup-section h2 {
        font-size: 24px;
    }

    .signin-section p {
        font-size: 16px;
        margin-bottom: 15px;
    }

    .signin-btn {
        padding: 10px 20px;
        font-size: 16px;
    }

    body {
        justify-content: flex-start;
        padding-top: 20px;
    }
}
</style>
    <div class="container">
        <!-- Sign In Section -->
        <div class="signin-section">
            <h2>WELCOME BACK!</h2>
            <p>To keep connected with us please login with your previous details.</p>
            <a href="sign.php" class="signin-btn">SIGN IN</a>
        </div>

        <!-- Sign Up Section -->
        <div class="signup-section">
            <h2>CREATE ACCOUNT</h2>
            <?php
    // Check for error message
    if (isset($_SESSION['error'])) {
        echo '<p style="color: red;">' . $_SESSION['error'] . '</p>';
        unset($_SESSION['error']); // Clear the message after displaying it
    }
    ?>
            <div class="form-container">
           
           <form id="registrationForm" action="signup.php" method="POST" onsubmit="return validatePasswords();">
    <!-- Title Dropdown -->
    <select name="title" required>
        <option value="" disabled selected>Select Title</option>
        <option value="Dr">Dr.</option>
        <option value="Mr">Mr.</option>
        <option value="Mrs">Mrs.</option>
        <option value="Miss">Miss</option>
        <option value="Prof">Prof.</option>
    </select>

    <!-- First Name (Letters Only) -->
    <input type="text" name="firstName" placeholder="First Name" required pattern="[A-Za-z]+" title="Only letters are allowed.">

    <!-- Last Name (Letters Only) -->
    <input type="text" name="lastName" placeholder="Last Name" required pattern="[A-Za-z]+" title="Only letters are allowed.">

    <!-- BHF Number -->
    <input type="text" name="BHF_number" placeholder="BHF Number" required oninput="this.value = this.value.replace(/[^0-9]/g, '');">


    <!-- Practice Type (Letters Only) -->
  
<select name="practice_type" id="practice_type" required>
    <option value="" disabled selected>Select your Medical Practice Type</option>
    <option value="Doctor">Doctor</option>
    <option value="Radiologist">Radiologist</option>
    <option value="Cardiologist">Cardiologist</option>
    <option value="Dermatologist">Dermatologist</option>
    <option value="Pediatrician">Pediatrician</option>
    <option value="Surgeon">Surgeon</option>
    <option value="Oncologist">Oncologist</option>
  <option value="Oncologist">Other</option>
  
    <!-- Add more options as needed -->
</select>
    <!-- South African Phone Number -->
    <input type="tel" name="telephone" placeholder="Phone Number" required pattern="0[0-9]{9}" title="Enter a valid 10-digit South African number starting with 0.">

    <!-- Email Address -->
    <input type="email" name="email" placeholder="Email Address" required>
    
    <!-- Password Fields and Additional Validation -->
    <div style="position: relative;">
        <input type="password" id="password" name="password" placeholder="Create Password" required>
        <img class="toggle-password" id="togglePassword" src="images/eye-crossed.png" alt="Toggle Password Visibility">
    </div>      

    <div class="guidelines">
        Your password must contain:
        <ul>
            <li id="length" class="requirement">At least 8 characters</li>
            <li id="uppercase" class="requirement">At least one uppercase letter (A-Z)</li>
            <li id="lowercase" class="requirement">At least one lowercase letter (a-z)</li>
            <li id="number" class="requirement">At least one number (0-9)</li>
            <li id="special" class="requirement">At least one special character (!@#$%^&*)</li>
        </ul>
    </div>
    
    <p id="strengthMessage" class="strength"></p>
    <div style="position: relative;">
        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required>
        <img class="toggle-password" id="toggleConfirmPassword" src="images/eye-crossed.png" alt="Toggle Password Visibility">
    </div>
    <p id="passwordMatchMessage" class="password-match-message"></p>
  
    <!-- Address Field -->
    <input type="text" name="full_address" id="full_address" placeholder="Address (Street, City, Province, Postal Code)" required>
<span id="error-message" style="color: red; display: none;">Please enter a complete address in the format: Street, City, Province, Postal Code.</span>



    <!-- Submit Button -->
    <button type="submit" class="signup-btn">SIGN UP</button>
</form>

            </div>
        </div>
    </div>
       <script>document.getElementById('full_address').addEventListener('input', function() {
    const fullAddress = this.value;
    const addressPattern = /^[a-zA-Z0-9\s,]+\s*,\s*[a-zA-Z\s]+\s*,\s*[a-zA-Z\s]+\s*,\s*[A-Za-z0-9\s-]+$/;
    const errorMessage = document.getElementById('error-message');
    
    if (addressPattern.test(fullAddress)) {
        errorMessage.style.display = 'none';
    } else {
        errorMessage.style.display = 'block';
    }
});

document.getElementById('registrationForm').addEventListener('submit', function(event) {
    const fullAddress = document.getElementById('full_address').value;
    const addressPattern = /^[a-zA-Z0-9\s,]+\s*,\s*[a-zA-Z\s]+\s*,\s*[a-zA-Z\s]+\s*,\s*[A-Za-z0-9\s-]+$/;
    const errorMessage = document.getElementById('error-message');
    
    // Prevent form submission if address is not in the correct format
    if (!addressPattern.test(fullAddress)) {
        errorMessage.style.display = 'block';
        event.preventDefault(); // Stops the form from submitting
    }
});</script>       
    <script>
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const strengthMessage = document.getElementById('strengthMessage');
    const lengthRequirement = document.getElementById('length');
    const uppercaseRequirement = document.getElementById('uppercase');
    const lowercaseRequirement = document.getElementById('lowercase');
    const numberRequirement = document.getElementById('number');
    const specialRequirement = document.getElementById('special');
    const passwordMatchMessage = document.getElementById('passwordMatchMessage');
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');

    function validatePasswords() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        // Validate password match
        if (password !== confirmPassword) {
            passwordMatchMessage.textContent = "Passwords do not match.";
            passwordMatchMessage.style.color = "red";
            return false; // Prevent form submission
        } else {
            passwordMatchMessage.textContent = "";
        }

        // Validate password strength
        const lengthValid = password.length >= 8;
        const uppercaseValid = /[A-Z]/.test(password);
        const lowercaseValid = /[a-z]/.test(password);
        const numberValid = /[0-9]/.test(password);
        const specialValid = /[!@#$%^&*]/.test(password);

        lengthRequirement.className = lengthValid ? 'requirement valid' : 'requirement invalid';
        uppercaseRequirement.className = uppercaseValid ? 'requirement valid' : 'requirement invalid';
        lowercaseRequirement.className = lowercaseValid ? 'requirement valid' : 'requirement invalid';
        numberRequirement.className = numberValid ? 'requirement valid' : 'requirement invalid';
        specialRequirement.className = specialValid ? 'requirement valid' : 'requirement invalid';

        if (lengthValid && uppercaseValid && lowercaseValid && numberValid && specialValid) {
            strengthMessage.textContent = "Password strength: Strong";
            strengthMessage.className = "strength strong";
            return true; // Allow form submission
        } else {
            strengthMessage.textContent = "Password is weak. Please strengthen it.";
            strengthMessage.className = "strength weak";
            return false; // Prevent form submission
        }
    }

    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        togglePassword.src = type === 'password' ? 'images/eye-crossed.png' : 'images/eye (1).png'; // Change image path
    });

    toggleConfirmPassword.addEventListener('click', function () {
        const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPasswordInput.setAttribute('type', type);
        toggleConfirmPassword.src = type === 'password' ? 'images/eye-crossed.png' : 'images/eye (1).png'; // Change image path
    });

    // Add input event listener to validate password strength in real-time
    passwordInput.addEventListener('input', validatePasswords);
    confirmPasswordInput.addEventListener('input', validatePasswords);
    </script>
</body>
</html>
