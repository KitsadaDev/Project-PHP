<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('location: login.php');
    exit();
}

// กำหนดค่าเริ่มต้น
$services = [];
$queues = [];
$error_message = '';

try {
    // ทดสอบการเชื่อมต่อฐานข้อมูล
    $conn->query("SELECT 1");
    
    // ดึงข้อมูลบริการที่เปิดใช้งาน
    $stmt = $conn->prepare("SELECT * FROM services WHERE active = 1 ORDER BY name");
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ดึงคิวทั้งหมดของวันนี้
    $stmt = $conn->prepare("
        SELECT q.*, s.name as service_name, s.estimated_time 
        FROM queues q
        LEFT JOIN services s ON q.service_type = s.id
        WHERE DATE(q.created_at) = CURDATE()
        ORDER BY q.created_at DESC
    ");
    $stmt->execute();
    $queues = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>
            <strong class='font-bold'>เกิดข้อผิดพลาด!</strong>
            <span class='block sm:inline'> ไม่สามารถดึงข้อมูลได้</span>
          </div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคิว - ระบบจดการการจองคิวร้านค้า</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- หัวข้อและปุ่มกลับ -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">จัดการคิว</h1>
                <p class="text-gray-600">วันที่ <?php echo date('d/m/Y'); ?></p>
            </div>
            <a href="dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>กลับ
            </a>
        </div>

        <!-- ฟอร์มเพิ่มคิวใหม่ -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">เพิ่มคิวใหม่</h2>
            <form id="newQueueForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ชื่ลูกค้า</label>
                    <input type="text" name="customer_name" required
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">เบอร์โทร</label>
                    <input type="tel" name="phone" required pattern="[0-9]{10}"
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">บริการ</label>
                    <select name="service_type" required
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="">เลือกบริการ</option>
                        <?php foreach ($services as $service): ?>
                        <option value="<?php echo $service['id']; ?>">
                            <?php echo $service['name']; ?> (<?php echo $service['estimated_time']; ?> นาที)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">หมายเหตุ</label>
                    <input type="text" name="notes"
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2 lg:col-span-4 flex justify-end">
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600">
                        <i class="fas fa-plus mr-2"></i>เพิ่มคิว
                    </button>
                </div>
            </form>
        </div>

        <!-- รายการคิว -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">คิว</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อลูกค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">บริการ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">เวลา</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($queues as $queue): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo $queue['queue_number']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $queue['customer_name']; ?></div>
                            <div class="text-sm text-gray-500"><?php echo $queue['phone']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $queue['service_name']; ?></div>
                            <div class="text-sm text-gray-500"><?php echo $queue['estimated_time']; ?> นาที</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php
                                switch($queue['status']) {
                                    case 'waiting':
                                        echo 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'in_progress':
                                        echo 'bg-blue-100 text-blue-800';
                                        break;
                                    case 'completed':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'cancelled':
                                        echo 'bg-red-100 text-red-800';
                                        break;
                                }
                                ?>">
                                <?php
                                switch($queue['status']) {
                                    case 'waiting':
                                        echo 'รอดำเนินการ';
                                        break;
                                    case 'in_progress':
                                        echo 'กำลังดำเนินการ';
                                        break;
                                    case 'completed':
                                        echo 'เสร็จสิ้น';
                                        break;
                                    case 'cancelled':
                                        echo 'ยกเลิก';
                                        break;
                                }
                                ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('H:i', strtotime($queue['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <?php if ($queue['status'] == 'waiting'): ?>
                            <button onclick="updateStatus(<?php echo $queue['id']; ?>, 'in_progress')" 
                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-play"></i>
                            </button>
                            <?php endif; ?>
                            
                            <?php if ($queue['status'] == 'in_progress'): ?>
                            <button onclick="updateStatus(<?php echo $queue['id']; ?>, 'completed')"
                                    class="text-green-600 hover:text-green-900 mr-3">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php endif; ?>
                            
                            <?php if ($queue['status'] == 'waiting'): ?>
                            <button onclick="updateStatus(<?php echo $queue['id']; ?>, 'cancelled')"
                                    class="text-red-600 hover:text-red-900">
                                <i class="fas fa-times"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // จัดการการส่งฟอร์มเพิ่มคิวใหม่
        document.getElementById('newQueueForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('add_queue.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('เพิ่มคิวสำเร็จ\nหมายเลขคิว: ' + data.queue_number);
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                alert('เกิดข้อผิดพลาด: ' + error);
            });
        });

        // อัพเ���ทสถานะคิว
        function updateStatus(queueId, status) {
            if (confirm('ต้องการเปลี่ยนสถานะคิวหรือไม่?')) {
                fetch('update_queue_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        queue_id: queueId,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('เกิดข้อผิดพลาด: ' + error);
                });
            }
        }
    </script>
</body>
</html> 