<?php
require 'vendor/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/src/SMTP.php';
require 'vendor/phpmailer/src/Exception.php';

$mail = new PHPMailer\PHPMailer\PHPMailer();
$mail->setFrom('test@academai.xscpry.com');
$mail->addAddress('your-email@gmail.com');
$mail->Subject = 'Test';
$mail->Body = 'PHPMailer is working!';

if ($mail->send()) {
    echo "Email sent!";
} else {
    echo "Error: " . $mail->ErrorInfo;
}