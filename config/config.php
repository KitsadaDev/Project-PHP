<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// โหลดไฟล์ .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// ตรวจสอบว่ามีการกำหนดค่าที่จำเป็นครบหรือไม่
$dotenv->required([
    'DB_HOST', 'DB_NAME', 'DB_USER',
    'SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS',
    'TWILIO_SID', 'TWILIO_TOKEN', 'TWILIO_PHONE'
]); 