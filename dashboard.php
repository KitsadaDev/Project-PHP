<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบวารล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('location: login.php');
    exit();
}

// กำหนดค่าเริ่มต้นสำหรับ queue_stats
$queue_stats = [
    'waiting_count' => 0,
    'in_progress_count' => 0,
    'completed_count' => 0,
    'cancelled_count' => 0
];

try {
    // นับจำนวนคิวแต่ละสถานะของวันนี้
    $stmt = $conn->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'waiting' THEN 1 END) as waiting_count,
            COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_count,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count
        FROM queues 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $queue_stats = $result;
    }

    // ดึงข้อมูลผู้ใช้
    $stmt = $conn->prepare("SELECT email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
    }

} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
}

// กำหนดค่าเริ่มต้นสำหรับ session ที่ไม่มี
$_SESSION['email'] = $_SESSION['email'] ?? 'ผู้ใช้งาน';
$_SESSION['role'] = $_SESSION['role'] ?? 'user';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ระบบจัดการการจองคิวร้านค้า</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-gray-800 text-white w-64 py-6 flex flex-col">
            <div class="px-6 mb-8">
                <h1 class="text-2xl font-bold">ระบบจองคิว</h1>
                <p class="text-gray-400 text-sm">ร้านค้าของคุณ</p>
            </div>
            <nav class="flex-1">
                <a href="dashboard.php" class="flex items-center px-6 py-3 bg-gray-900">
                    <i class="fas fa-home mr-3"></i> หน้าหลัก
                </a>
                <a href="queue_management.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <i class="fas fa-list-ol mr-3"></i> จัดการคิว
                </a>
                <a href="queue_display.php" target="_blank" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <i class="fas fa-tv mr-3"></i> หน้าจอแสดงคิว
                </a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="services_management.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <i class="fas fa-cog mr-3"></i> จัดการบริการ
                </a>
                <?php endif; ?>
                <a href="reports.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <i class="fas fa-chart-bar mr-3"></i> รายงาน
                </a>
            </nav>
            <div class="px-6 py-4 border-t border-gray-700">
                <div class="flex items-center mb-4">
                    <i class="fas fa-user-circle text-2xl mr-3"></i>
                    <div>
                        <div class="text-sm"><?php echo $_SESSION['email']; ?></div>
                        <div class="text-xs text-gray-400"><?php echo $_SESSION['role']; ?></div>
                    </div>
                </div>
                <a href="logout.php" class="flex items-center text-red-400 hover:text-red-300">
                    <i class="fas fa-sign-out-alt mr-3"></i> ออกจากระบบ
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-x-hidden overflow-y-auto">
            <div class="container mx-auto px-6 py-8">
                <h3 class="text-gray-700 text-3xl font-medium mb-8">แดชบอร์ด</h3>

                <!-- สถิติคิววันนี้ -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100">
                                <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-gray-600 text-sm">รอดำเนินการ</h4>
                                <h3 class="text-2xl font-bold text-gray-700"><?php echo $queue_stats['waiting_count']; ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100">
                                <i class="fas fa-spinner text-blue-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-gray-600 text-sm">กำลังดำเนินการ</h4>
                                <h3 class="text-2xl font-bold text-gray-700"><?php echo $queue_stats['in_progress_count']; ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100">
                                <i class="fas fa-check text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-gray-600 text-sm">เสร็จสิ้น</h4>
                                <h3 class="text-2xl font-bold text-gray-700"><?php echo $queue_stats['completed_count']; ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100">
                                <i class="fas fa-times text-red-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-gray-600 text-sm">ยกเลิก</h4>
                                <h3 class="text-2xl font-bold text-gray-700"><?php echo $queue_stats['cancelled_count']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- เมนูลัด -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <a href="queue_management.php" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-200">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100">
                                <i class="fas fa-plus text-blue-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-xl font-semibold text-gray-700">เพิ่มคิวใหม่</h4>
                                <p class="text-gray-600 text-sm">จัดการคิวลูกค้า</p>
                            </div>
                            <i class="fas fa-chevron-right ml-auto text-gray-400"></i>
                        </div>
                    </a>

                    <a href="queue_display.php" target="_blank" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-200">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100">
                                <i class="fas fa-tv text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-xl font-semibold text-gray-700">หน้าจอแสดงคิว</h4>
                                <p class="text-gray-600 text-sm">แสดงสถานะคิวปัจจุบัน</p>
                            </div>
                            <i class="fas fa-chevron-right ml-auto text-gray-400"></i>
                        </div>
                    </a>

                    <a href="reports.php" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-200">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100">
                                <i class="fas fa-chart-bar text-purple-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-xl font-semibold text-gray-700">รายงาน</h4>
                                <p class="text-gray-600 text-sm">ดูสถิติและรายงาน</p>
                            </div>
                            <i class="fas fa-chevron-right ml-auto text-gray-400"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 