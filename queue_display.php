<?php
require_once 'config/db.php';

// กำหนดค่าเริ่มต้นให้ตัวแปร
$current_queues = [];
$waiting_queues = [];
$completed_queues = [];

try {
    // ดึงคิวที่กำลังให้บริการ (in_progress)
    $stmt = $conn->prepare("
        SELECT q.*, s.name as service_name, s.estimated_time 
        FROM queues q
        LEFT JOIN services s ON q.service_type = s.id
        WHERE q.status = 'in_progress'
        AND DATE(q.created_at) = CURDATE()
        ORDER BY q.updated_at DESC
        LIMIT 3
    ");
    $stmt->execute();
    $current_queues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ดึงคิวที่รอ (waiting)
    $stmt = $conn->prepare("
        SELECT q.*, s.name as service_name 
        FROM queues q
        LEFT JOIN services s ON q.service_type = s.id
        WHERE q.status = 'waiting'
        AND DATE(q.created_at) = CURDATE()
        ORDER BY q.created_at ASC
        LIMIT 5
    ");
    $stmt->execute();
    $waiting_queues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ดึงคิวที่เสร็จสิ้นล่าสุด (completed)
    $stmt = $conn->prepare("
        SELECT q.queue_number
        FROM queues q
        WHERE q.status = 'completed'
        AND DATE(q.created_at) = CURDATE()
        ORDER BY q.updated_at DESC
        LIMIT 3
    ");
    $stmt->execute();
    $completed_queues = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าจอแสดงคิว - ระบบจัดการการจองคิวร้านค้า</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .blink {
            animation: blink 1s infinite;
        }
        body {
            background-color: #ffffff;
        }
        .queue-display {
            font-family: 'Arial', sans-serif;
        }
    </style>
</head>
<body class="queue-display">
    <div class="container mx-auto px-4 py-8">
        <!-- ปุ่มย้อนกลับ -->
        <div class="absolute top-4 left-4">
            <a href="dashboard.php" class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-200">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับหน้าหลัก
            </a>
        </div>

        <!-- หัวข้อ -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">
                <i class="fas fa-store text-blue-500 mr-3"></i>
                ระบบจัดการการจองคิวร้านค้า
            </h1>
            <p class="text-xl text-gray-600">
                <?php echo date('d/m/Y H:i'); ?>
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- คิวที่กำลังให้บริการ -->
            <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
                <h2 class="text-2xl font-bold mb-6 text-blue-600">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    กำลังให้บริการ
                </h2>
                <?php if (!empty($current_queues)): ?>
                    <?php foreach ($current_queues as $queue): ?>
                        <div class="bg-gray-50 rounded-lg p-6 mb-4 border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-6xl font-bold text-blue-600 blink">
                                        <?php echo htmlspecialchars($queue['queue_number']); ?>
                                    </span>
                                </div>
                                <div class="text-right">
                                    <div class="text-xl font-semibold text-gray-800">
                                        <?php echo htmlspecialchars($queue['service_name']); ?>
                                    </div>
                                    <div class="text-gray-600">
                                        ประมาณ <?php echo $queue['estimated_time']; ?> นาที
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-gray-500 text-center py-8">
                        ไม่มีคิวที่กำลังให้บริการ
                    </div>
                <?php endif; ?>
            </div>

            <!-- คิวที่รอ -->
            <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
                <h2 class="text-2xl font-bold mb-6 text-green-600">
                    <i class="fas fa-clock mr-2"></i>
                    คิวถัดไป
                </h2>
                <?php if (!empty($waiting_queues)): ?>
                    <div class="grid gap-4">
                        <?php foreach ($waiting_queues as $queue): ?>
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <div class="flex items-center justify-between">
                                    <span class="text-3xl font-bold text-gray-800">
                                        <?php echo htmlspecialchars($queue['queue_number']); ?>
                                    </span>
                                    <span class="text-gray-600">
                                        <?php echo htmlspecialchars($queue['service_name']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-gray-500 text-center py-8">
                        ไม่มีคิวที่รอ
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- คิวที่เสร็จสิ้��ล่าสุด -->
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6 border border-gray-200">
            <h2 class="text-2xl font-bold mb-4 text-gray-600">
                <i class="fas fa-check-circle mr-2"></i>
                เสร็จสิ้นล่าสุด
            </h2>
            <div class="flex justify-center space-x-4">
                <?php if (!empty($completed_queues)): ?>
                    <?php foreach ($completed_queues as $queue): ?>
                        <div class="bg-gray-50 rounded-lg px-6 py-3 border border-gray-200">
                            <span class="text-2xl font-bold text-gray-600">
                                <?php echo htmlspecialchars($queue['queue_number']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-gray-500">
                        ไม่มีคิวที่เสร็จสิ้น
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // รีเฟรชหน้าทุก 30 วินาที
        setInterval(function() {
            location.reload();
        }, 30000);

        // แสดงเวลาแบบ Real-time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('th-TH');
            const dateString = now.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.querySelector('.text-gray-600').textContent = `${dateString} ${timeString}`;
        }
        setInterval(updateTime, 1000);
    </script>
</body>
</html> 