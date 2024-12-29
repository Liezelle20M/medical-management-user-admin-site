<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact</title>
    <link rel="stylesheet" href="assets/styles.css">
  <link rel="stylesheet" href="assets/styles3.css">
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script type="text/javascript"
    src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js">
</script>
<script type="text/javascript">
    (function(){
        emailjs.init({
        publicKey: "bCqMB-sl_CD7WfJ0v",
    });
    })();
</script>
  <style>
    .error { color: red; font-size: 0.9em; }
</style>
  </head>
<body>
   <?php include 'includes/header.php'; ?>

    <section id="section-wrapper">
        <div class="box-wrapper">
            <div class="info-wrap">
                <ul class="info-details">
                    <li>
                        <i class="fas fa-phone-alt"></i>
                        <span>Call Us</span>
                    </li>
                </ul>
                <h3 class="info-sub-title">We are available 5 days a week.</h3>
                <h3 class="info-sub-title">Phone: +27 73 922 1860</h3>

                <hr class="heading-line">

                <ul class="info-details">
                    <li>
                        <i class="fas fa-paper-plane"></i>
                        <span>Write To Us</span>
                    </li>
                </ul>
                <h3 class="info-sub-title">Fill up the form and our Team will get back to you within 24 hours</h3>
                <h3 class="info-sub-title">Email: vahsa_health@outlook.com</h3>

            </div>
            <div class="form-wrap">
                <form action="#" method="POST" id="contactForm">
                    <h2 class="form-titlee">Send us a message</h2>
                    <div class="form-fieldss">
                        <div class="form-groupp">
                            <input type="text" class="fname" id="namee" placeholder="First Name">
                            <div class="error" id="nameError"></div>
                        </div>
                        <div class="form-groupp">
                            <input type="text" id="lnamee" class="lname" placeholder="Last Name">
                            <div class="error" id="lnameError"></div>
                        </div>
                        <div class="form-groupp">
                            <input type="email" class="email" id="email_id" placeholder="Mail">
                            <div class="error" id="emailError"></div>
                        </div>
                        <div class="form-groupp">
                            <input type="number" id="phone" class="phone" placeholder="Phone">
                            <div class="error" id="phoneError"></div>
                        </div>
                        <div class="form-groupp">
                            <textarea name="message" id="message" placeholder="Write your message"></textarea>
                            <div class="error" id="messageError"></div>
                        </div>
                    </div>
                    <button type="button" onclick="SendMessage()" class="submit-button">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    

  <?php include 'includes/footer.php'; ?>

        <script>
            function SendMessage() {
                event.preventDefault(); // Prevent the form from submitting the traditional way
    
                

        document.getElementById("nameError").textContent = "";
        document.getElementById("lnameError").textContent = "";
        document.getElementById("emailError").textContent = "";
        document.getElementById("phoneError").textContent = "";
        document.getElementById("messageError").textContent = "";

        // Get form values
        const name = document.getElementById("namee").value.trim();
        const lname = document.getElementById("lnamee").value.trim();
        const email = document.getElementById("email_id").value.trim();
        const phone = document.getElementById("phone").value.trim();
        const message = document.getElementById("message").value.trim();

        let isValid = true;

        // Validate first name (letters only)
        const namePattern = /^[a-zA-Z]+$/;
        if (name === "" || !namePattern.test(name)) {
            document.getElementById("nameError").textContent = "First name is required and must contain only letters.";
            isValid = false;
        }

        // Validate last name (letters only)
        if (lname === "" || !namePattern.test(lname)) {
            document.getElementById("lnameError").textContent = "Last name is required and must contain only letters.";
            isValid = false;
        }
        
        // Validate email
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (email === "" || !emailPattern.test(email)) {
            document.getElementById("emailError").textContent = "Please enter a valid email address.";
            isValid = false;
        }

        // Validate phone number
        const phonePattern = /^[0-9]{10}$/;
        if (phone === "" || !phonePattern.test(phone)) {
            document.getElementById("phoneError").textContent = "Please enter a valid 10-digit phone number.";
            isValid = false;
        }

        // Validate message
        if (message === "") {
            document.getElementById("messageError").textContent = "Message cannot be empty.";
            isValid = false;
        }

        // If form is valid, submit the form
        if (isValid) {
            alert("Form submitted successfully!");
            // Here, you can add code to actually submit the form using AJAX or other methods
        }
    
        // Collect form data
        if(isValid){
        var params = {
                    from_name: document.getElementById('namee').value,
                    surname: document.getElementById('lnamee').value,
                    number: document.getElementById('phone').value,
                    message: document.getElementById('message').value,
                email_id: document.getElementById('email_id').value
                //to_name: "Vahsa",
    
                }
                console.log(params);
                // EmailJS send method
                emailjs.send('service_iu9hy2h', 'template_1li1eje', params).then(function(response) {
                    console.log("done");//for me to see my errors
                    alert('Message sent successfully!');
                }, function(error) {
                    alert('Failed to send message: ' + JSON.stringify(error));
                    console.log("not done");
                });

            }
            
    }

        </script>
    
</body>
</html>