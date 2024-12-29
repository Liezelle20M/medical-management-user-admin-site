<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

/**
 * Custom function to send an email with optional attachments.
 * 
 * @param string $to       Recipient email address.
 * @param string $subject  Email subject.
 * @param string $message  HTML content of the email.
 * @param array|string|null $attachments Optional attachments - file paths or $_FILES data.
 * 
 * @return bool Returns true on success, false on failure.
 */
function sendEmail($to, $subject, $message, $attachments = null) {
    $mail = new PHPMailer(true);

    try {
        // SMTP server configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'no-reply@valueaddedhealthcaresa.com';
        $mail->Password = 'TechAlchemists@2024';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Set email headers
        $mail->setFrom('no-reply@valueaddedhealthcaresa.com', 'VAHSA');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        // Handle attachments
        if ($attachments !== null) {
            if (is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    if (file_exists($attachment)) {
                        $mail->addAttachment($attachment);
                    } else {
                        error_log("Attachment file not found: $attachment");
                    }
                }
            } elseif (is_string($attachments) && file_exists($attachments)) {
                $mail->addAttachment($attachments);
            } else {
                error_log("Attachment not found or invalid format: $attachments");
            }
        }

        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
