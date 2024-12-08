<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config/config.php';

class Mailer {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        
        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = $_ENV['SMTP_HOST'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $_ENV['SMTP_USER'];
        $this->mailer->Password = $_ENV['SMTP_PASS'];
        $this->mailer->Port = $_ENV['SMTP_PORT'];
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->CharSet = 'UTF-8';
    }
    
    public function sendBookingConfirmation($userEmail, $bookingDetails) {
        try {
            $this->mailer->setFrom('your-email@gmail.com', 'ระบบจองคิว');
            $this->mailer->addAddress($userEmail);
            $this->mailer->Subject = 'ยืนยันการจองคิว';
            $this->mailer->Body = "การจองของคุณได้รับการยืนยันแล้ว\n" . 
                                 "รายละเอียด:\n" . $bookingDetails;
            
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail Error: " . $e->getMessage());
            return false;
        }
    }
}
?> 