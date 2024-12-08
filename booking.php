<?php
require_once 'includes/csrf.php';
require_once 'includes/Validator.php';
require_once 'includes/Mailer.php';
require_once 'includes/SMS.php';

session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$validator = new Validator();
$mailer = new Mailer();
$sms = new SMS();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ CSRF token
    validateCSRFToken($_POST['csrf_token']);
    
    $booking_date = $_POST['booking_date'];
    $store_id = $_POST['store_id'];
    
    // ตรวจสอบความถูกต้องของข้อมูล
    if ($validator->validateBookingDate($booking_date)) {
        // บันทึกการจอง
        $sql = "INSERT INTO bookings (user_id, store_id, booking_date) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute([$_SESSION['user_id'], $store_id, $booking_date])) {
            // ส่งอีเมลยืนยัน
            $bookingDetails = "วันที่จอง: " . $booking_date;
            $mailer->sendBookingConfirmation($_SESSION['user_email'], $bookingDetails);
            
            // ส่ง SMS แจ้งเตือน (ถ้ามีเบอร์โทรศัพท์)
            if (isset($_SESSION['user_phone'])) {
                $sms->sendBookingNotification(
                    $_SESSION['user_phone'],
                    "การจองของคุณได้รับการยืนยันแล้ว สำหรับวันที่ " . $booking_date
                );
            }
            
            header('Location: booking-success.php');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองคิว</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <form method="POST" class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-6">จองคิว</h2>
            
            <!-- แสดงข้อผิดพลาด -->
            <?php if ($validator->getErrors()): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php foreach ($validator->getErrors() as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">วันที่จอง</label>
                <input type="date" name="booking_date" required 
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">เลือกร้านค้า</label>
                <select name="store_id" required 
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                    <!-- ดึงข้อมูลร้านค้าจากฐานข้อมูล -->
                </select>
            </div>
            
            <button type="submit" 
                    class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600">
                ยืนยันการจอง
            </button>
        </form>
    </div>
</body>
</html> 