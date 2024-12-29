<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Refund Request - VAHSA</title>
    <style>
        :root {
            --navy-dark: #0B1437;
            --navy-main: #1E3A8A;
            --navy-light: #2563EB;
            --navy-accent: #3B82F6;
            --success-green: #10B981;
            --error-red: #EF4444;
            --gray-light: #F8FAFC;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
        }

        body {
            background: var(--gray-light);
            color: var(--navy-dark);
            line-height: 1.6;
        }

        .refund-page {
            max-width: 800px;
            margin: 40px auto;
            padding: 40px;
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(14, 20, 55, 0.1);
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            color: var(--navy-main);
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .policy-notice {
            text-align: center;
            background: var(--navy-main);
            color: var(--white);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .policy-link {
            color: var(--white);
            font-weight: 600;
            text-decoration: underline;
            transition: opacity 0.3s ease;
        }

        .policy-link:hover {
            opacity: 0.8;
        }

        section {
            margin-bottom: 40px;
            padding: 25px;
            background: var(--gray-light);
            border-radius: 12px;
            border-left: 4px solid var(--navy-accent);
        }

        h2 {
            color: var(--navy-main);
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .refund-eligibility ul {
            list-style: none;
            margin-left: 0;
        }

        .refund-eligibility li {
            margin-bottom: 12px;
            padding-left: 25px;
            position: relative;
        }

        .refund-eligibility li::before {
            content: "•";
            color: var(--navy-accent);
            font-size: 1.5em;
            position: absolute;
            left: 0;
            top: -5px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--navy-main);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #E2E8F0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--navy-accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input[type="file"] {
            padding: 10px;
            background: var(--white);
        }

        .required-doc {
            font-size: 0.9rem;
            color: var(--navy-main);
            margin-top: 10px;
            padding: 10px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 6px;
        }

        .submit-button {
            background: linear-gradient(to right, var(--navy-main), var(--navy-light));
            color: var(--white);
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            transition: transform 0.2s ease;
        }

        .submit-button:hover {
            transform: translateY(-2px);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(11, 20, 55, 0.5);
            backdrop-filter: blur(5px);
            animation: modalFadeIn 0.3s ease;
        }

        .modal-content {
            background: var(--white);
            margin: 10% auto;
            padding: 40px;
            width: 90%;
            max-width: 500px;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(14, 20, 55, 0.25);
            position: relative;
            animation: modalSlideIn 0.3s ease;
        }

        .modal-icon {
            width: 60px;
            height: 60px;
            background: var(--success-green);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-icon svg {
            width: 30px;
            height: 30px;
            color: white;
        }

        .modal-content h2 {
            text-align: center;
            margin-bottom: 15px;
        }

        .modal-content p {
            text-align: center;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .modal-content ul {
            list-style: none;
            margin-bottom: 25px;
            padding: 20px;
            background: var(--gray-light);
            border-radius: 8px;
        }

        .modal-content li {
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
        }

        .modal-content li::before {
            content: "✓";
            color: var(--success-green);
            position: absolute;
            left: 0;
        }

        .modal-button {
            background: var(--navy-main);
            color: var(--white);
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .modal-button:hover {
            background: var(--navy-light);
        }

        .close-button {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            color: var(--navy-main);
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .close-button:hover {
            background: var(--gray-light);
        }

        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 768px) {
            .refund-page {
                margin: 20px;
                padding: 25px;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            section {
                padding: 20px;
            }
        }

        /* Error message styles */
        .error-message {
            color: var(--error-red);
            font-size: 0.9rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="refund-page">
        <header class="page-header">
            <h1>Refund Request</h1>
        </header>
        
        <section class="policy-notice">
            <p>Please read the <a href="refund_policy.html" class="policy-link">VAHSA Refund Request Policy</a> before submitting your request.</p>
        </section>
        
        <section class="refund-eligibility">
            <h2>Eligibility for Refunds</h2>
            <p>You are eligible for a refund if:</p>
            <ul>
                <li>You initiated the cancellation within 30 days of purchase</li>
                <li>There was an error in billing</li>
                <li>There was a duplication of payment or error on VAHSA's part</li>
                <li>You have provided all required documentation</li>
            </ul>
        </section>
        
        <section class="refund-form-section">
            <h2>Submit Your Refund Request</h2>
            <form id="refund-form" action="submit-refund.php" method="POST" enctype="multipart/form-data" novalidate>
                <div class="form-group">
                    <label for="name">Name (as on registration)</label>
                    <input type="text" id="name" name="name" required minlength="3" pattern="^[A-Za-z\s]{3,}$" title="Name must contain at least 3 letters and no numbers or special characters.">
                    <div class="error-message" id="name-error"></div>
                </div>
                
                <div class="form-group">
                    <label for="invoice">Invoice Number</label>
                    <input type="text" id="invoice" name="invoice" required minlength="3" pattern="^[A-Za-z0-9]+$" title="Invoice number must be alphanumeric without special characters.">
                    <div class="error-message" id="invoice-error"></div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                    <div class="error-message" id="email-error"></div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description of Refund Request</label>
                    <textarea id="description" name="description" rows="4" required minlength="10" placeholder="Please provide a detailed description of your refund request."></textarea>
                    <div class="error-message" id="description-error"></div>
                </div>
                
                <div class="form-group">
                    <label for="proof">Attach Proof of Payment</label>
                    <input type="file" id="proof" name="proof" accept="image/*,application/pdf" required>
                </div>
                
                <div class="form-group">
                    <label for="supporting_docs">Supporting Documentation</label>
                    <input type="file" id="supporting_docs" name="supporting_docs[]" accept="image/*,application/pdf" multiple>
                    <p class="required-doc">Please attach all necessary supporting documents (if applicable)</p>
                </div>
                
                <button type="submit" class="submit-button">Submit Request</button>
            </form>
        </section>
        
        <section class="processing-time">
            <p>Your refund request will be processed within <strong>14 business days</strong>.</p>
        </section>

        <section>
            <h2>Need Help?</h2>
            <p>If you have any questions regarding your refund, please contact us at <a href="mailto:vahsa_health@outlook.com">vahsa_health@outlook.com</a> or call us at <strong>+27 73 922 1860</strong>.</p>
        </section>
        
    </div>
    

    <!-- Success Modal -->
    <div id="confirmation-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <div class="modal-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2>Request Submitted Successfully!</h2>
            <p>Thank you for submitting your refund request. Here's what happens next:</p>
            <ul>
                <li>You will receive a confirmation email shortly</li>
                <li>Your request will be processed within 14 business days</li>
                <li>We will communicate updates via email</li>
                <li>Additional documentation may be requested if needed</li>
            </ul>
            <p>Reference Number: <strong id="reference-number"></strong></p>
            <button class="modal-button" onclick="closeModal()">Close</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get DOM elements
            const modal = document.getElementById('confirmation-modal');
            const closeBtn = document.querySelector('.close-button');
            const form = document.getElementById('refund-form');
            const refNumberElement = document.getElementById('reference-number');

            // Error message elements
            const nameError = document.getElementById('name-error');
            const invoiceError = document.getElementById('invoice-error');
            const emailError = document.getElementById('email-error');
            const descriptionError = document.getElementById('description-error');

            // Generate reference number
            function generateReferenceNumber() {
                const date = new Date();
                const year = date.getFullYear().toString().slice(-2);
                const month = (date.getMonth() + 1).toString().padStart(2, '0');
                const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
                return `REF-${year}${month}-${random}`;
            }

            // Show modal function
            function showModal() {
                if (modal) {
                    modal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                    // Generate and set reference number
                    refNumberElement.textContent = generateReferenceNumber();
                }
            }

            // Close modal function
            function closeModal() {
                if (modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    // Optional: Reset form
                    if (form) form.reset();
                    // Clear error messages
                    clearErrors();
                }
            }

            // Clear all error messages
            function clearErrors() {
                nameError.textContent = '';
                invoiceError.textContent = '';
                emailError.textContent = '';
                descriptionError.textContent = '';
            }

            // Validate form fields
            function validateForm() {
                let isValid = true;
                clearErrors();

                // Validate Name
                const name = form.name.value.trim();
                const namePattern = /^[A-Za-z\s]{3,}$/;
                if (!namePattern.test(name)) {
                    nameError.textContent = "Please enter a valid name (at least 3 letters, no numbers or special characters).";
                    isValid = false;
                }

                // Validate Invoice Number
                const invoice = form.invoice.value.trim();
                const invoicePattern = /^[A-Za-z0-9]{3,}$/;
                if (!invoicePattern.test(invoice)) {
                    invoiceError.textContent = "Invoice number must be at least 3 characters and contain only letters and numbers.";
                    isValid = false;
                }

                // Validate Email
                const email = form.email.value.trim();
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(email)) {
                    emailError.textContent = "Please enter a valid email address.";
                    isValid = false;
                }

                // Validate Description
                const description = form.description.value.trim();
                if (description.length < 10) {
                    descriptionError.textContent = "Description must be at least 10 characters long.";
                    isValid = false;
                }

                return isValid;
            }

            // Add event listeners
            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }

            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (validateForm()) {
                        showModal();
                    } else {
                        // Scroll to first error
                        const firstError = document.querySelector('.error-message:not(:empty)');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                });
            }

            // Close modal if clicking outside
            window.onclick = function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            }

            // Make closeModal function globally available
            window.closeModal = closeModal;
        });
    </script>
</body>
</html>
