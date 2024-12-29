<?php session_start();
 if (isset($_SESSION['user_name'])) {
    $username = htmlspecialchars($_SESSION['user_name']); // Sanitize to prevent XSS
} ?>
 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VAHSA Website</title>
  <style>
   #chat-float-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            text-align: center;
            background-color: #fff;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        #chat-icon {
            width: 60px;
            height: 60px;
            cursor: pointer;
        }

     
#close-chat {
    position: absolute;
    top: -10px; /* Adjust as needed */
    right: -10px; /* Adjust as needed */
    background-color: #ff0000; /* Red background for visibility */
    color: white; /* White color for the 'X' */
    border: none; /* No border */
    border-radius: 50%; /* Circular button */
    font-size: 20px; /* Font size of the 'X' */
    cursor: pointer; /* Pointer cursor on hover */
    width: 30px; /* Width of the button */
    height: 30px; /* Height of the button */
    display: block; /* Always display the button */
}
        #chat-message {
            font-size: 14px;
            color: #333;
            margin-top: 5px;
        }

        #chat-float-container.active #close-chat {
            display: block;
        }

        #chat-float-container.removed {
            display: none;
        }
  </style>
    <link rel="stylesheet" href="assets/styles3.css">
</head>
<body>
   <?php include 'includes/header.php'; ?>
  
   <main>
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
        <section class="welcome-section">
               <h1>Welcome to VAHSA, <?php echo $username; ?>!</h1>
            <p>Empowering Healthcare Professionals with Seamless Service Management</p>
          
        </section>
    <?php endif; ?>
</main>
  
 <main>
    <?php if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] != true): ?>
        <section class="welcome-section">
            <h1>Welcome to VAHSA!</h1>
            <p>Empowering Healthcare Professionals with Seamless Service Management</p>
            <a href="create.php" class="btn-register">Register Now</a>
        </section>
    <?php endif; ?>
</main>
    
  <section class="services-section">
    <style>
        .services-section .service-card h3 a {
            color: white;
            text-decoration: none;
        }
        
        .services-section .service-card h3 a:hover {
            text-decoration: underline;
        }
    </style>
    
    <h2>Explore Our Comprehensive Services</h2>
    <div class="services">
        <div class="service-card">
            <div class="service-header">
                <img src="images/Picture2.png" alt="Clinical Code Assistance Icon">
                <h3><a href="clinical.php">Clinical Code Assistance</a></h3>
            </div>
            <p>We provide expert support to healthcare professionals in managing clinical code queries. Ensure proper claims and documentation to avoid costly errors.</p>
        </div>
        <div class="service-card">
            <div class="service-header">
                <img src="images/Picture3.png" alt="Medico-Legal Support Icon">
                <h3><a href="med.php">Medico-Legal Support</a></h3>
            </div>
            <p>Get comprehensive support for medico-legal cases, ensuring compliance and protection against legal disputes related to clinical services and payments.</p>
        </div>
        <div class="service-card">
            <div class="service-header">
                <img src="images/Picture4.png" alt="Professional Training Courses Icon">
                <h3><a href="booking.php">Professional Training Courses</a></h3>
            </div>
            <p>Access training courses tailored to healthcare professionals, manage course registration, and enhance your skills in the latest industry standards.</p>
        </div>
        
    </div>
</section>


 <div class="container">
    <h1 class="about-h1">About VAHSA</h1>
</div>

<section id="about-vahsa">
 
    <div class="container">
      
        <div class="about-content">
        <section id="about-vahsa">
    
        
        <div class="about-content">
            <!-- About Description -->
            <div class="about-description">
                <img src="images/Picture6.png" alt="Thinking Icon">
                <h2>ABOUT US: VAHSA</h2>
                <p>Value Added Healthcare South Africa (Pty) Ltd (VAHSA) is a dedicated private company committed to
                supporting healthcare practitioners in navigating the intricate healthcare funding landscape in South
                Africa. Our expertise enables doctors, nurses, physiotherapists, radiographers, specialists, and other
                healthcare professionals to optimize their businesses.</p> <br> <br>
              
              <h2>Why Choose VAHSA</h2>
                <p>By partnering with VAHSA, healthcare practitioners gain:</p>
                <ul class="core-values-list">
                    <li>Expert knowledge and guidance</li>
                    <li>Increased revenue and profitability</li>
                    <li>Reduced administrative burdens</li>
                    <li>Enhanced practice efficiency</li>
                </ul>
            <br><br><br>
          
          
          
          </div>

            
    
</section>

            <!-- Core Values -->
            <div class="core-values">
                <img src="images/Picture8.png" alt="Core Values Icon">
                <div>
                    <h2>CORE VALUES</h2>
                    <div class="core-values-list">
                        <div class="core-value-item">
                            <h3>Efficiency</h3>
                            <p>We optimize every process to save you time, ensuring streamlined and effective solutions for healthcare professionals.</p>
                        </div>
                        <div class="core-value-item">
                            <h3>Security</h3>
                            <p>Your data is safe with us, protected by advanced encryption and two-factor authentication, keeping your information secure at all times.</p>
                        </div>
                        <div class="core-value-item">
                            <h3>Integrity</h3>
                            <p>We value transparency and trust in all our interactions with healthcare professionals, fostering a reliable and ethical relationship.</p>
                        </div>
                        <div class="core-value-item">
                            <h3>Professional Growth</h3>
                            <p>We support your continuous development through easily accessible training programs and resources, helping you advance your skills.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
        
            <!-- Vision and Mission Section -->
    <!-- Vision and Mission Section -->
<section id="vision-mission">
    <div class="container">

        <!-- Vision card -->
        <div class="card">
            <div class="icon">
                <img src="./images/vission-icon.png" alt="Vision Icon">
            </div>
            <h2>VISION</h2>
            <p>To be the leading provider of healthcare education and clinical coding expertise,</p>
            <div class="bar bar-left-small"></div>
            <div class="bar bar-left-medium"></div>
            <div class="bar bar-left-large"></div>
            <div class="bar bar-right-middle"></div>
            <div class="bar bar-right-below"></div>
            <div class="bar-new"></div> <!-- New bar added -->
        </div>

        <!-- Mission card -->
        <div class="card">
            <div class="icon">
                <img src="./images/mission-icon.png" alt="Mission Icon">
            </div>
            <h2>MISSION</h2>
            <p>
Our mandate is to educate, assist, and support private healthcare businesses in minimizing claims
rejections, short payments, and litigation errors.
          </p>
            <div class="bar bar-left-small"></div>
            <div class="bar bar-left-medium"></div>
            <div class="bar bar-left-large"></div>
            <div class="bar bar-right-middle"></div>
            <div class="bar bar-right-below"></div>
            <div class="bar-new"></div>
        </div>
    </div>
</section>
<style>
 
/* Media Queries for smaller screens */
@media (max-width: 768px) {
    .card-container {
        flex-direction: column;
        align-items: center;
    }
    
    .card {
        width: 100%;
        max-width: 500px;
    }
    
    .card h2 {
        font-size: 1.5em;
    }
    
    .card p {
        font-size: 0.9em;
    }
}</style>
        <!-- Newsletter Subscription Section -->
   <section id="newsletter">
    <div class="newsletter-container">
        <h2>Subscribe to our <span class="highlight">Newsletter</span></h2>
        <p>Stay updated with the latest news and insights. Subscribe now to get exclusive content delivered to your inbox!</p>
        <form class="newsletter-form" action="subscribe.php" method="POST">
            <input type="text" name="first_name" placeholder="Enter Your First Name" required>
            <input type="email" name="email" placeholder="Enter Your Email Address" required>
            <button type="submit">Subscribe Now</button>
        </form>
    </div>
</section>
   
    <!-- Footer Section -->
<?php include 'includes/footer.php'; ?>
<!-- Floating Chat Button -->
  <div id="chat-float-container">
    <img src="./images/13727385.png" id="chat-icon" alt="Chat Icon" onclick="openChat()">
    <button id="close-chat" onclick="removeChat()">×</button>
    <p id="chat-message">Need help? Chat with us!</p>
</div>

<!-- Chat Script -->
<script>
    function openChat() {
        window.location.href = "chatbot.php"; // Directs to local chatbot.php file
        document.getElementById('chat-float-container').classList.add('active');
    }

    function removeChat() {
        document.getElementById('chat-float-container').classList.add('removed');
    }
</script>
  
  
  
</body>
</html>
