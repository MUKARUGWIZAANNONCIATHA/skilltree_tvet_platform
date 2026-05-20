<?php
/**
 * Email Functions
 * Path: /includes/functions/email.php
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../../vendor/autoload.php';

function sendEmail($to, $subject, $body, $isHtml = true) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        if (!$isHtml) {
            $mail->AltBody = $body;
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
        return false;
    }
}

function sendWelcomeEmail($to, $name) {
    $subject = "Welcome to SkillTree TVET Platform";
    $body = "
    <h2>Welcome to SkillTree, $name!</h2>
    <p>Thank you for joining our TVET learning platform. You can now access:</p>
    <ul>
        <li>Industry-relevant courses</li>
        <li>AI-powered learning assistance</li>
        <li>Interactive quizzes and assessments</li>
        <li>Skill tree tracking</li>
        <li>Internship opportunities</li>
    </ul>
    <p>Get started by logging into your dashboard.</p>
    <p><a href='" . APP_URL . "/includes/auth/login.php' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Login Now</a></p>
    ";
    return sendEmail($to, $subject, $body);
}

function sendPasswordResetEmail($to, $name, $resetLink) {
    $subject = "Reset Your Password - SkillTree TVET";
    $body = "
    <h2>Password Reset Request</h2>
    <p>Hello $name,</p>
    <p>We received a request to reset your password. Click the button below to create a new password:</p>
    <p><a href='$resetLink' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Reset Password</a></p>
    <p>This link will expire in 1 hour.</p>
    <p>If you did not request this, please ignore this email.</p>
    ";
    return sendEmail($to, $subject, $body);
}

function sendQuizResultEmail($to, $name, $topicName, $score, $passed) {
    $status = $passed ? "PASSED" : "NOT PASSED";
    $color = $passed ? "#4CAF50" : "#F44336";
    $subject = "Quiz Result: $topicName";
    $body = "
    <h2>Quiz Result</h2>
    <p>Hello $name,</p>
    <p>You have completed the quiz: <strong>$topicName</strong></p>
    <p>Your score: <strong style='color:$color'>$score%</strong></p>
    <p>Status: <strong style='color:$color'>$status</strong></p>
    ";
    return sendEmail($to, $subject, $body);
}
?> 
