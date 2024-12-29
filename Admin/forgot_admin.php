<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="styles.css">
    
</head>
<body>
    <div class="forgot-password-container">
        <h2>Forgot your password Admin?</h2>
        <p>Enter your email address and we'll help you reset your password</p>
        <form action="#">
            <input type="text" placeholder="Email Address" required>
            <button type="submit" class="continue-btn">CONTINUE</button>
        </form>
    </div>
</body>
<style>
    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background-color: #b0c4de;
    padding: 0 20px;
}

/* Forgot password page styling */
.forgot-password-container {
    background-color: #fff;
    width: 100%;
    max-width: 600px;
    padding: 40px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

h2 {
    font-size: 24px;
    margin-bottom: 20px;
}

p {
    margin-bottom: 20px;
    font-size: 14px;
    color: #666;
}

form {
    display: flex;
    flex-direction: column;
}

input {
    padding: 10px;
    font-size: 14px;
    border-radius: 5px;
    border: 1px solid #ccc;
    margin-bottom: 20px;
}

input:focus {
    outline: none;
    border-color: #999;
}

button {
    padding: 10px;
    background-color: black;
    color: white;
    font-size: 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

button:hover {
    opacity: 0.9;
}

/* Responsive Design */
@media screen and (max-width: 768px) {
    .container {
        flex-direction: column;
        height: auto;
    }

    .login-box, .signup-box {
        width: 100%;
        padding: 30px;
    }

    .forgot-password-container {
        width: 100%;
        padding: 20px;
    }
}

@media screen and (max-width: 480px) {
    h2 {
        font-size: 20px;
    }

    p {
        font-size: 12px;
    }

    input, button {
        font-size: 14px;
    }
}

</style>
</html>