<?php 
session_start();

if (isset($_SESSION['is_refreshed'])) {
    ; // Clear the chat history
} else {
    $_SESSION['is_refreshed'] = true; // First page load
}

// Function to search document for specific query
function searchDocument($query) {
    $documentPath = "document.txt";
    if (file_exists($documentPath)) {
        $content = file_get_contents($documentPath);
        
        // Perform a case-insensitive search for the query in the document
        if (stripos($content, $query) !== false) {
            $position = stripos($content, $query);
            $start = max(0, $position - 50);
            $end = min(strlen($content), $position + 150);
            $snippet = substr($content, $start, $end - $start);
            return "... " . htmlspecialchars($snippet) . " ...";
        }
        return "Sorry, I couldn't find any relevant information in the document.";
    }
    return "Document not found.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/styles3.css">
    <title>VAHSA Chatbot</title>
    <style>
        /* Scope all styles to #chatbot-container */
        #chatbot-container * {
            box-sizing: border-box;
        }

        #chatbot-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #b0c4de;
            overflow-x: hidden;
            font-family: Arial, sans-serif;
        }

        #chatbot-container footer {
            width: 100%;
            padding: 10px 20px;
            color: white;
            background-color: #0C4375;
            z-index: 1000;
            text-align: center;
        }

        #chatbot-container .chat-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 90px 20px 150px;
        }

        #chatbot-container #chat-container {
            width: 100%;
            max-width: 600px;
            padding: 20px;
            border-radius: 10px;
            background-color: #153f75;
            box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            animation: float 2s ease-in-out infinite;
        margin-left: 425px;
        }

        #chatbot-container #chat-output {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            height: 300px;
            overflow-y: auto;
            background-color: #f0f0f0;
            border-radius: 10px;
        }

        #chatbot-container .message {
            display: flex;
            align-items: center;
            margin: 5px 0;
        }

        #chatbot-container .bot-message {
            flex-direction: row;
        }

        #chatbot-container .user-message {
            flex-direction: row-reverse;
            text-align: right;
        }

        #chatbot-container .message img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin: 0 10px;
        }

        #chatbot-container .message p {
            background-color: #f1f1f1;
            padding: 8px 12px;
            border-radius: 10px;
            max-width: 70%;
        }

        #chatbot-container .user-message p {
            background-color: #d1e7dd;
            color: #333;
        }

        #chatbot-container #user-input {
            width: 100%;
            height: 30px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 10px;
            padding: 5px;
        }

        #chatbot-container button {
            background-color: black;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            font-size: 16px;
        }

        #chatbot-container button:hover {
            background-color: #333;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            #chatbot-container #chat-container {
                width: 90%;
                margin: 0 auto;
            }

            #chatbot-container .message p {
                font-size: 14px;
                max-width: 85%;
            }

            #chatbot-container button {
                font-size: 14px;
                padding: 8px;
            }

            #chatbot-container #user-input {
                font-size: 14px;
            }

            #chatbot-container footer {
                padding: 20px 10px;
            }
        }

        @media (max-width: 480px) {
            #chatbot-container #chat-container {
                width: 100%;
                padding: 15px;
            }

            #chatbot-container .message p {
                font-size: 12px;
                max-width: 90%;
            }

            #chatbot-container button {
                font-size: 12px;
                padding: 6px;
            }

            #chatbot-container #user-input {
                font-size: 12px;
                height: 28px;
            }
        }
    </style>
</head>

<body>
<?php include("includes/header.php"); ?>

<!-- Chatbot Container -->
<div id="chatbot-container">
    <div id="chat-container">
        <div id="chat-output">
            <?php
  
  
            if (!isset($_SESSION['chat_history'])) {
                $_SESSION['chat_history'] = ["<div class='message bot-message'><img src='https://via.placeholder.com/30?text=B' alt='Bot'><p>Hello! I'm here to help you with VAHSA. How can I assist you?<br></p></div>"];
            }

       // Define response patterns
        $pairs = [
            ["My name is (.*)", "Hello %1, how can I assist you regarding VAHSA?"],
            ["Hi|hey|hello", "Hello! How can I help you with VAHSA?"],
            ["Thank you", "You're welcome! Feel free to reach out if you need any more assistance with VAHSA."],
            ["How are you|how are you doing", "I'm doing great, thanks for asking! How can I help you regarding VAHSA?"],
            ["what is vahsa", "VAHSA (VALUE ADDED HEALTHCARE SOUTH AFRICA) supports healthcare professionals with clinical code management, medico-legal support, and professional training."],
            ["Clinical code assistance", "We offer expert assistance in managing clinical code queries. Please visit our Clinical Code Queries page, complete the required information, and our team will respond promptly."],
            ["Medico-legal support", "We provide comprehensive medico-legal support. Please visit our Medico-Legal page for more information and assistance."],
            ["Mraining courses", "VAHSA offers professional training to enhance your skills. Please register on our website to get started."],
            ["Support", "You can contact VAHSA's support team via our website or phone for assistance with your account or services."],
            ["Core values", "VAHSA values efficiency, security, integrity, and professional growth in all our interactions."],
            ["Where is vahsa located", "VAHSA is based in Midrand, South Africa, serving healthcare professionals nationwide."],
            ["How do i contact vahsa", "You can find our contact details on the Contacts page, where you can email or call us directly."],
            ["How can i register for training|how to register", "To register for training, create an account on our website and visit the Training Courses page."],
            ["Is my data safe", "Yes, VAHSA ensures data safety with encryption and two-factor authentication."],
            ["How do i book services|how to make payments", "You can book services or make payments securely through your VAHSA account dashboard."],
            ["How do i claim", "You can raise claims directly through the claims section in your account dashboard."],
            ["Goodbye|Bye", "Goodbye! Feel free to reach out if you need any more assistance with VAHSA."]
        ];

        // Keyword responses
        $keyword_responses = [
              "register" =>  "You can register by creating an account on the VAHSA platform. Go to the Create Accout Page to register and follow the steps to verify your email.",
    "location" =>  "VAHSA is located in South Africa, serving healthcare professionals nationwide.",
    "services" => "VAHSA offers clinical code assistance, medico-legal support, and professional training.",
   
    "contact" =>  "You can find our conatact details on the Contacts page and email/call us directly.",
    "query" => "You can raise claims queries directly through the claims section in your account dashboard.",
    "claims" => "You can raise claims queries directly through the claims section in your account dashboard.",
    "claim" => "You can raise claims queries directly through the claims section in your account dashboard.",
    "claiming" => "You can raise claims queries directly through the claims section in your account dashboard.",
    "claimed" => "You can raise claims queries directly through the claims section in your account dashboard.",
    "book" => "You can book services or make payments securely through your VAHSA account dashboard.",
    "payments" => "You can book services or make payments securely through your VAHSA account dashboard.",
    
    "safe" => "Yes, VAHSA ensures the safety of your data with encryption and two-factor authentication.",
    "Data" => "Yes, VAHSA ensures the safety of your data with encryption and two-factor authentication.",
    "code" => "We offer expert assistance in managing clinical code queries to ensure accurate claims and documentation. For support with clinical coding issues, please visit our Clinical Code Queries page, complete the required information, and our team will promptly respond.",
"Medico" => "We offer comprehensive medico-legal support to protect healthcare professionals from legal disputes.For support with Medico-Legal issues, please visit our Medico-Legal page, complete the required information, and our team will promptly respond.",
"legal" => "We offer comprehensive medico-legal support to protect healthcare professionals from legal disputes.For support with Medico-Legal issues, please visit our Medico-Legal page, complete the required information, and our team will promptly respond.",
"Training" => "VAHSA provides professional training designed to enhance your skills. To get started, please register if do not have an account with us and then login to able to book on our website through the Training Courses page.",
"courses" => "VAHSA provides professional training designed to enhance your skills. To get started, please register  please register if do not have an account with us and then login to able to book on our website through the Training Courses page.",
"support" => "You can contact VAHSA's support team for help with your account or services through our email or phone number which you can find on the Contacts page.",
"values" =>  "VAHSA values efficiency, security, integrity, and professional growth in all interactions with healthcare professionals.",
"located" => "VAHSA is based in Midrand, South Africa and serves healthcare professionals nationwide.",
"based" => "VAHSA is based in Midrand, South Africa and serves healthcare professionals nationwide.",
"call" => "You can find our conatact details on the Contacts page and email/call us directly.",
"email" => "You can find our conatact details on the Contacts page and email/call us directly..",
"hold" => "You can find our conatact details on the Contacts page and email/call us directly.",
"calls" => "You can contact us via our support page or call/email us directly.",
"emails" => "You can find our conatact details on the Contacts page and email/call us directly.",
"calling" => "You can find our conatact details on the Contacts page and email/call us directly.",
"GOODBYE" => "Goodbye! Feel free to reach out if you need any more assistance with VAHSA.",
"BYE" => "Goodbye! Feel free to reach out if you need any more assistance with VAHSA.",
"thanks" => "You're Welcome! Feel free to reach out if you need any more assistance with VAHSA.",
"Clinical" => "We offer expert assistance in managing clinical code queries to ensure accurate claims and documentation. For support with clinical coding issues, please visit our Clinical Code Queries page, complete the required information, and our team will promptly respond.",
"clinical"=>"We offer expert assistance in managing clinical code queries to ensure accurate claims and documentation. For support with clinical coding issues, please visit our Clinical Code Queries page, complete the required information, and our team will promptly respond.",

      "vahsa" => "VAHSA (VALUE ADDED HEALTHCARE SOUTH AFRICA) supports healthcare professionals with clinical code management, medico-legal support, and professional training.",
  ];

        // Default responses
        $default_responses = [
           
            "You can check our website or contact us directly for further information. Check our Contacts page for more details.",
            "Feel free to reach out to our support team for more details."
        ];

        // Helper function to match patterns
        function matchPattern($input, $patterns) {
            foreach ($patterns as $pattern => $response) {
                if (preg_match("/^" . $pattern . "$/i", $input, $matches)) {
                    return preg_replace('/%1/', $matches[1] ?? "", $response);
                }
            }
            return false;
        }

        // Handle user input
        if (isset($_POST['user_input'])) {
            $userInput = strtolower(trim($_POST['user_input']));
            $_SESSION['chat_history'][] = "<div class='message user-message'><img src='https://via.placeholder.com/30?text=U' alt='User'><p>" . htmlspecialchars($userInput) . "</p></div>";

            // Check for a pattern match
            $botResponse = matchPattern($userInput, array_column($pairs, 1, 0));

            // If no match in patterns, check keywords
            if (!$botResponse) {
                foreach ($keyword_responses as $keyword => $response) {
                    if (strpos($userInput, $keyword) !== false) {
                        $botResponse = $response;
                        break;
                    }
                }
            }
// If no match in keywords, check for document search trigger
            if (!$botResponse && strpos($userInput, 'search') !== false) {
                // Extract the query by removing "search" keyword
                $query = trim(str_replace("search", "", $userInput));
                $botResponse = searchDocument($query);
            }

            // If no match in keywords, use default response
            if (!$botResponse) {
                $botResponse = $default_responses[array_rand($default_responses)];
            }

            // Add bot response to chat history
            $_SESSION['chat_history'][] = "<div class='message bot-message'><img src='https://via.placeholder.com/30?text=B' alt='Bot'><p>" . $botResponse . "</p></div>";
        }
            // Display chat history
            foreach ($_SESSION['chat_history'] as $message) {
                echo $message;
            }
            ?>
        </div>
        <form method="post">
            <input type="text" name="user_input" id="user-input" placeholder="Type a message..." required>
            <button type="submit">Send</button>
        </form>
    </div>
    <?php include("includes/footer.php"); ?>
</div>

</body>
</html>
