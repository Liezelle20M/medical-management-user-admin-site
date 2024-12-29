<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Header</title>
    <style>
        /* Header Styling */
        header {
            height: 80px; /* Fixed height for consistency */
            width: 100%;
            background-color: #003f7d;
            color: white;
            display: flex;
            align-items: center; /* Center content vertically */
            justify-content: space-between; /* Distribute space between elements */
            padding: 0 20px; /* Consistent padding */
            box-sizing: border-box;
            position: relative; /* Ensures child absolute elements are positioned relative to header */
        }

        .navbar {
            display: flex;
            align-items: center;
        }

        .logo {
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .nav-links {
            list-style-type: none;
            display: flex;
            margin: 0;
            padding: 0;
        }

        .nav-links li {
            margin: 0 15px;
        }

        .nav-links a {
            text-decoration: none;
            color: white;
            font-size: 16px;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #1c658c; /* Changes color on hover for better UX */
        }

        .auth-buttons {
            display: flex;
            align-items: center;
        }

        .login {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background-color: #1c658c;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .login:hover {
            background-color: #155d86; /* Darkens on hover for better UX */
        }

        .user-icon {
            position: relative; /* Added to position the dropdown correctly */
            display: flex;
            align-items: center;
            cursor: pointer; /* Indicates interactivity */
            margin-left: 20px; /* Spacing between username and user icon */
        }

        .user-icon img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-left: 10px;
        }

        /* Profile dropdown */
        .profile-dropdown {
            display: none; /* Hidden by default */
            position: absolute;
            top: 100%; /* Positions the dropdown directly below the user icon */
            right: 0; /* Aligns the dropdown to the right edge of the user icon */
            background-color: #003f7d;
            z-index: 1000;
            padding: 10px 0; /* Added vertical padding for spacing */
            min-width: 150px; /* Ensures a consistent width */
            box-shadow: 0 8px 16px rgba(0,0,0,0.2); /* Adds a subtle shadow for depth */
            border-radius: 4px; /* Rounds the corners */
        }

        .profile-dropdown.show {
            display: block;
        }

        .profile-dropdown a {
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }

        .profile-dropdown a:hover {
            background-color: #1c658c; /* Changes background on hover for better UX */
        }

        /* Hamburger icon */
        .navbar-toggler {
             display: none;
             border: none;
             background: none;
             color: white;
             font-size: 28px;
             cursor: pointer; /* Indicates interactivity */
             z-index: 10000; /* Ensure it's above the sidebar */
        }

        .navbar-toggler.hidden {
            display: none;
        }

        /* Mobile sidebar */
        .side-menu {
            height: 100%;
            width: 0;
            position: fixed;
            top: 0;
            right: 0;
            background-color: #003f7d;
            overflow-x: hidden;
            transition: width 0.5s ease;
            padding-top: 60px;
            z-index: 9999;
        }

        .side-menu.active {
            width: 250px;
        }

        .side-menu a {
            text-decoration: none;
            color: white;
            display: block;
            font-size: 16px;
            margin: 10px 20px;
            padding: 8px 16px;
            transition: background-color 0.3s;
        }

        .side-menu a:hover {
            background-color: #1c658c; /* Changes background on hover for better UX */
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 36px;
            color: white;
            cursor: pointer; /* Indicates interactivity */
        }

        /* Media queries */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .navbar-toggler {
                display: block;
            }

            .auth-buttons {
                display: none;
            }

            .profile-dropdown {
                left: 0; /* Aligns the dropdown to the left on smaller screens */
                right: auto; /* Resets the right alignment */
                min-width: 100%; /* Makes the dropdown take full width of the user icon container */
                box-sizing: border-box; /* Ensures padding doesn't exceed container width */
            }
        }

        @media (min-width: 769px) {
            .side-menu {
                display: none;
            }

            .nav-links {
                display: flex;
            }

            .auth-buttons {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <a href="dashboard.php" style="text-decoration: none; color: inherit;">
                <div class="logo">VAHSA</div>
            </a>
        </nav>
        <!-- Hamburger menu for mobile -->
        <button class="navbar-toggler">&#9776;</button>

        <ul class="nav-links">
            <li><a href="manage_courses.php">Manage Training Courses</a></li>
            <li><a href="manageClinical.php">Manage Claims and Clinical Codes</a></li>
            <li><a href="manage_medico_legal.php">Manage Medicolegal Services</a></li>
            <li><a href="admin_newsletter.php">Manage NewsLetters</a></li>
        </ul>

        <div class="auth-buttons">
            <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                <div class="user-icon">
                    <span><?php echo $_SESSION['admin_username']; ?></span>
                    <img src="images/Picture7.png" alt="User Icon">
                    <div class="profile-dropdown">
                      <a href="dashboard.php">Dashboard</a>
                        <a href="edit_profile.php">Edit Profile</a>
                      <a href="admin_logout.php" class="logout">Log Out</a>
                     
                     
                    </div>
                </div>
            <?php else: ?>
                <a href="admin_login.php" class="login">Log In</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Sidebar for mobile -->
    <div class="side-menu">
        <a href="javascript:void(0)" class="close-btn">&times;</a>
        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="edit_profile.php" class="user-icon">Profile</a>
            <a href="admin_logout.php" class="logout">Log Out</a>
            
            
      
        <?php else: ?>
            <a href="admin_login.php" class="login">Log In</a>
        <?php endif; ?>
        <a href="manage_courses.php">Manage Training Courses</a>
        <a href="manageClinical.php">Manage Claims and Clinical Codes</a>
        <a href="manage_medico_legal.php">Manage Medicolegal Services</a>
        <a href="admin_newsletter.php">Manage NewsLetters</a>
    </div>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
       $(document).ready(function () {
            // Toggle the side menu and hamburger icon
            $(".navbar-toggler").click(function () {
                $(".side-menu").toggleClass("active");
                $(".navbar-toggler").toggleClass("hidden"); // Hide hamburger icon when sidebar opens
            });

            // Close side menu
            $(".close-btn").click(function () {
                $(".side-menu").removeClass("active");
                $(".navbar-toggler").removeClass("hidden"); // Show hamburger icon when sidebar closes
            });

            // Toggle profile dropdown on user icon click
            $(".user-icon").click(function (e) {
                e.stopPropagation(); // Prevent event bubbling
                $(".profile-dropdown").toggleClass("show");
            });

            // Close dropdown on outside click
            $(document).click(function () {
                $(".profile-dropdown").removeClass("show");
            });

            // Prevent dropdown from closing when clicking inside
            $(".profile-dropdown").click(function (e) {
                e.stopPropagation();
            });

            // Hide side menu on resize
            $(window).resize(function() {
                if ($(window).width() > 768) {
                    $(".side-menu").removeClass("active");
                    $(".navbar-toggler").removeClass("hidden"); // Ensure hamburger icon is visible on larger screens
                    $(".nav-links").css("display", "flex");
                    $(".auth-buttons").css("display", "flex");
                } else {
                    $(".nav-links").css("display", "none");
                    $(".auth-buttons").css("display", "none");
                }
            });
        });
    </script>
</body>
</html>
