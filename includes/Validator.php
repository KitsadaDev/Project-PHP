<?php
class Validator {
    private $errors = [];
    
    public function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "รูปแบบอีเมลไม่ถูกต้อง";
            return false;
        }
        return true;
    }
    
    public function validatePassword($password) {
        if (strlen($password) < 8) {
            $this->errors[] = "รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร";
            return false;
        }
        return true;
    }
    
    public function validateBookingDate($date) {
        $dateTime = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateTime || $dateTime->format('Y-m-d') !== $date) {
            $this->errors[] = "รูปแบบวันที่ไม่ถูกต้อง";
            return false;
        }
        return true;
    }
    
    public function getErrors() {
        return $this->errors;
    }
}
?> 