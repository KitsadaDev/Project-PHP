<?php
require_once __DIR__ . '/../config/config.php';
use Twilio\Rest\Client;

class SMS {
    private $client;
    private $fromNumber;
    
    public function __construct() {
        $this->client = new Client(
            $_ENV['TWILIO_SID'],
            $_ENV['TWILIO_TOKEN']
        );
        $this->fromNumber = $_ENV['TWILIO_PHONE'];
    }
    
    public function sendBookingNotification($toNumber, $message) {
        try {
            $this->client->messages->create(
                $toNumber,
                [
                    'from' => $this->fromNumber,
                    'body' => $message
                ]
            );
            return true;
        } catch (Exception $e) {
            error_log("SMS Error: " . $e->getMessage());
            return false;
        }
    }
}
?> 