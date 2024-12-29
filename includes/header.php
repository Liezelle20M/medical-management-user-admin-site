

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Header</title>
    <style>
      .navbar {
    position: relative; /* Set position to relative to position child elements */
        height: 100px;
}
       .navbar-toggler {
    display: none; /* Hide by default */
    border: none;
    background: none;
    color: white;
    font-size: 28px; /* Size of the hamburger icon */
    position: absolute; /* Position it absolutely within the navbar */
    top: 25px; /* Adjust as needed */
    right: 20px; /* Adjust as needed */
}

       /* Side Menu Styles */
.side-menu {
    height: 100%;
    width: 0; /* Start collapsed */
    position: fixed;
    top: 0;
    right: 0; /* Align to the right */
    background-color: #003f7d; /* Consistent with your header color */
    overflow-x: hidden;
    transition: width 0.5s ease; /* Smooth transition for width */
    padding-top: 60px;
    z-index: 9999; /* Ensure it covers everything */
}

.side-menu.active {
    width: 250px; /* Width when active */
}

/* Normal links in the side menu */
.side-menu a {
    text-decoration: none;
    color: white;
    display: block;
    font-size: 16px; /* Match font size from the navbar */
    margin: 10px 20px; /* Margin for spacing */
    padding: 8px 16px; /* Consistent padding */
}


        /* Authentication button styles */
        .register, .login, .logout {
            padding: 8px 16px; /* Consistent padding */
            margin: 10px 20px; /* Margin for spacing */
        }

        /* Hover effects for buttons */
        .register:hover, .login:hover, .logout:hover {
            background-color: #14557b; /* Darker color on hover */
        }

        /* Normal links without button styling */
        .side-menu a:not(.register):not(.login):not(.logout):hover {
            background-color: #1c658c; /* Highlight effect on hover */
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 36px;
            color: white;
        }

        /* Dropdown styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #003f7d; /* Same as side menu */
            min-width: 160px;
            z-index: 1;
            transition: opacity 0.3s ease, visibility 0.3s ease; /* Smooth transition */
            opacity: 0; /* Start hidden */
            visibility: hidden; /* Prevent interaction when hidden */
        }

        .dropdown:hover .dropdown-content {
            display: block; /* Show on hover */
            opacity: 1; /* Fade in */
            visibility: visible; /* Make it interactive */
        }

        .dropdown-content a {
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #1c658c; /* Highlight effect on hover */
        }

        .user-icon {
            position: relative; /* This ensures the dropdown aligns to the user icon */
            cursor: pointer;
        }

        .profile-dropdown {
            display: none;
            position: absolute;
            background-color: #003f7d; /* Same as navbar */
            z-index: 1000;
            padding: 10px;
            right: 0; /* Align it to the right of the user icon */
            top: 100%; /* Ensure it opens downwards, right below the user icon */
            transition: opacity 0.3s ease, visibility 0.3s ease; /* Smooth transition */
            opacity: 0; /* Start hidden */
            visibility: hidden; /* Prevent interaction when hidden */
        }

        .profile-dropdown.show {
            display: block; /* Show when activated */
            opacity: 1; /* Fade in */
            visibility: visible; /* Make it interactive */
        }

        /* Style each dropdown item to stay in one line */
        .profile-dropdown a {
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            display: inline-block; /* Ensure they remain in a single row */
            white-space: nowrap; /* Prevent text from breaking to the next line */
        }

        .profile-dropdown a:hover {
            background-color: #14557b; /* Darker background on hover */
        }

        /* Media Queries for responsiveness */
        @media (max-width: 768px) {
            .nav-links {
                display: none; /* Hide nav links in mobile mode */
            }

            .navbar-toggler {
                display: block; /* Show hamburger icon */
            }

            .auth-buttons {
                display: none; /* Hide auth buttons on mobile */
            }
        }

        @media (min-width: 769px) {
            .side-menu {
                display: none; /* Hide side menu on larger screens */
            }

            .nav-links {
                display: flex; /* Show nav links in desktop mode */
         margin-left: 40px;
            }

            .auth-buttons {
                display: flex; /* Show auth buttons on desktop */
            }
        }

    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            // Toggle full-screen menu on mobile
            $(".navbar-toggler").click(function () {
                $(".side-menu").toggleClass("active");
            });

            // Close side menu
            $(".close-btn").click(function () {
                $(".side-menu").removeClass("active");
            });

            // Toggle profile dropdown on user icon click
            $(".user-icon").click(function (e) {
                e.stopPropagation(); // Prevent click from bubbling
                $(".profile-dropdown").toggleClass("show");
            });

            // Hide profile dropdown when clicking outside
            $(document).click(function () {
                $(".profile-dropdown").removeClass("show");
            });

            // Detect window resize
            $(window).resize(function() {
                if ($(window).width() > 768) {
                    // Close the side menu if window is resized to desktop size
                    $(".side-menu").removeClass("active");
                    $(".nav-links").css("display", "flex"); // Ensure nav links are displayed
                    $(".auth-buttons").css("display", "flex"); // Ensure auth buttons are displayed
                    $(".profile-dropdown").removeClass("show"); // Hide profile dropdown on desktop
                } else {
                    $(".nav-links").css("display", "none"); // Hide nav links in mobile
                    $(".auth-buttons").css("display", "none"); // Hide auth buttons in mobile
                }
            });
        });
    </script>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">VAHSA</div>

            <!-- Toggle button for mobile devices -->
            <button class="navbar-toggler">&#9776;</button>

            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
               <li><a href="/index.php#about-vahsa">About</a></li>
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                    <li class="dropdown">
                        <a href="#">Services</a>
                        <div class="dropdown-content">
                            <a href="clinical.php">Claims and Clinical Codes</a>
                            <a href="med.php">Medicolegal Services</a>
                        </div>
                    </li>
                    <li><a href="booking.php">Courses</a></li>
                <?php endif; ?>
                <li><a href="contactUs.php">Contact</a></li>
              
            </ul>
          
          
            <div class="auth-buttons">
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                    <div class="user-icon" style="display: flex; align-items: center;">
                        <img src="images/Picture7.png" alt="User Icon" style="cursor: pointer;">
                        <div class="profile-dropdown">
                         <a href="user_dashboard.php" style="color: white; padding: 8px 16px; display: block; text-decoration: none;">Dashboard</a>

                            <a href="EditProfile.php" style="color: white; padding: 8px 16px; display: block; text-decoration: none;">Edit Profile</a>
                             <a href="refund_policy.html"   style="color: white; padding: 8px 16px; display: block; text-decoration: none;"   >Refund Request </a>

                            <a href="logout.php" class="logout" style="color: white; padding: 8px 16px; display: block; text-decoration: none;">Log Out</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="sign.php" class="login">Log In</a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Full-screen menu for mobile -->
        <div class="side-menu">
            <a href="javascript:void(0)" class="close-btn">&times;</a>
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <a href="EditProfile.php" class="user-icon">Profile</a> <!-- Redirect to Edit Profile -->
                <a href="dashboard.php">Dashboard</a>
                <a href="refund_policy.html">Refund Request</a>


                <a href="logout.php" class="logout">Log Out</a> <!-- Show logout button if logged in -->
                <li class="dropdown">
                    <a href="#">Services</a>
                    <div class="dropdown-content">
                        <a href="clinical.php">Claims and Clinical Codes</a>
                        <a href="med.php">Medicolegal Services</a>
                    </div>
                </li>
                <a href="booking.php">Courses</a>
            <?php else: ?>
                <a href="sign.php" class="login">Log In</a> <!-- Show login button if not logged in -->
            <?php endif; ?>
            <a href="index.php">Home</a>
            <a href="/index.php#about-vahsa">About</a>
            <a href="contactUs.php">Contact</a>
        </div>
    </header>
</body>
</html>