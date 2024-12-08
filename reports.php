<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('location: login.php');
    exit();
}

// กำหนดค่าเริ่มต้น
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// กำหนดค่าเริ่มต้นสำหรับสถิติ
$stats = [
    'total_queues' => 0,
    'completed_queues' => 0,
    'cancelled_queues' => 0,
    'avg_service_time' => 0
];
$service_stats = [];
$daily_stats = [];

try {
    // สถิติภาพรวม
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_queues,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_queues,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_queues,
            COALESCE(AVG(CASE 
                WHEN status = 'completed' 
                THEN TIMESTAMPDIFF(MINUTE, created_at, updated_at)
                END), 0) as avg_service_time
        FROM queues
        WHERE DATE(created_at) BETWEEN :start_date AND :end_date
    ");
    $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $stats = $result;
    }

    // สถิติแยกตามบริการ
    $stmt = $conn->prepare("
        SELECT 
            s.name as service_name,
            COUNT(*) as total,
            COUNT(CASE WHEN q.status = 'completed' THEN 1 END) as completed,
            COUNT(CASE WHEN q.status = 'cancelled' THEN 1 END) as cancelled,
            COALESCE(AVG(CASE 
                WHEN q.status = 'completed' 
                THEN TIMESTAMPDIFF(MINUTE, q.created_at, q.updated_at)
                END), 0) as avg_time
        FROM queues q
        JOIN services s ON q.service_type = s.id
        WHERE DATE(q.created_at) BETWEEN :start_date AND :end_date
        GROUP BY s.id, s.name
        ORDER BY total DESC
    ");
    $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
    $service_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // สถิติรายวัน
    $stmt = $conn->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled
        FROM queues
        WHERE DATE(created_at) BETWEEN :start_date AND :end_date
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
    $daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน - ระบบจัดการก���รจองคิวร้านค้า</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- ปุ่มย้อนกลับ -->
        <div class="mb-6">
            <a href="dashboard.php" class="inline-flex items-center bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับหน้าหลัก
            </a>
        </div>

        <!-- หัวข้อ -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">รายงานและสถิติ</h1>
            <p class="text-gray-600">ข้อมูลการให้บริการ</p>
        </div>

        <!-- ตัวกรองวันที่ -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form class="flex gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">วันที่เริ่มต้น</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>"
                           class="rounded-lg border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">วันที่สิ้นสุด</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>"
                           class="rounded-lg border-gray-300">
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-search mr-2"></i>
                    ค้นหา
                </button>
            </form>
        </div>

        <!-- สถิติภาพรวม -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm font-medium text-gray-500 mb-2">จำนวนคิวทั้งหมด</div>
                <div class="text-3xl font-bold text-gray-800">
                    <?php echo number_format($stats['total_queues']); ?>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm font-medium text-gray-500 mb-2">ให้บริการสำเร็จ</div>
                <div class="text-3xl font-bold text-green-600">
                    <?php echo number_format($stats['completed_queues']); ?>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm font-medium text-gray-500 mb-2">ยกเลิก</div>
                <div class="text-3xl font-bold text-red-600">
                    <?php echo number_format($stats['cancelled_queues']); ?>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-sm font-medium text-gray-500 mb-2">เวลาเฉลี่ยต่อคิว</div>
                <div class="text-3xl font-bold text-blue-600">
                    <?php echo number_format($stats['avg_service_time'], 1); ?> นาที
                </div>
            </div>
        </div>

        <!-- กราฟและตาราง -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- สถิติแยกตามบริการ -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">สถิติแยกตามบริการ</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">บริการ</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">ทั้งหมด</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">สำเร็จ</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">ยกเลิก</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">เวลาเฉลี่ย</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($service_stats as $stat): ?>
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-800"><?php echo htmlspecialchars($stat['service_name']); ?></td>
                                <td class="px-6 py-4 text-sm text-center"><?php echo number_format($stat['total']); ?></td>
                                <td class="px-6 py-4 text-sm text-center text-green-600"><?php echo number_format($stat['completed']); ?></td>
                                <td class="px-6 py-4 text-sm text-center text-red-600"><?php echo number_format($stat['cancelled']); ?></td>
                                <td class="px-6 py-4 text-sm text-center"><?php echo number_format($stat['avg_time'] ?? 0, 1); ?> นาที</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- กราฟสถิติรายวัน -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">สถิติรายวัน</h2>
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // สร้างกราฟสถิติรายวัน
        const ctx = document.getElementById('dailyChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column(array_reverse($daily_stats), 'date')); ?>,
                datasets: [{
                    label: 'จำนวนคิวทั้งหมด',
                    data: <?php echo json_encode(array_column(array_reverse($daily_stats), 'total')); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    tension: 0.1
                }, {
                    label: 'ให้บริการสำเร็จ',
                    data: <?php echo json_encode(array_column(array_reverse($daily_stats), 'completed')); ?>,
                    borderColor: 'rgb(34, 197, 94)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html> 