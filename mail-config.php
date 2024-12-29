<?php
// mail-config.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer autoload file
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function sendEmail($to, $subject, $message, $attachments = null) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'no-reply@valueaddedhealthcaresa.com'; // Your email address
        $mail->Password   = 'TechAlchemists@2024'; // Your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('no-reply@valueaddedhealthcaresa.com', 'VAHSA');
        $mail->addAddress($to);

        // Attachments
        if ($attachments !== null) {
            if (is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    if (file_exists($attachment)) {
                        $mail->addAttachment($attachment); // Add attachment if file exists
                    } else {
                        error_log("Attachment file not found: $attachment");
                    }
                }
            } elseif (is_string($attachments)) {
                if (file_exists($attachments)) {
                    $mail->addAttachment($attachments); // Add single attachment
                } else {
                    error_log("Attachment file not found: $attachments");
                }
            }
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error for troubleshooting
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
