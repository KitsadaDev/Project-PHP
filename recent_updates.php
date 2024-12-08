<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('location: login.php');
    exit();
}

try {
    // ดึงรายการที่มีการแก้ไขล่าสุด 10 รายการ
    $stmt = $conn->prepare("
        SELECT i.*, u.email as updated_by 
        FROM items i 
        LEFT JOIN users u ON i.user_id = u.id 
        ORDER BY i.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการการจองคิวร้านค้า</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">รายการจองคิวที่มีการแก้ไขล่าสุด</h1>
                <p class="text-gray-600 text-sm">ระบบจัดการการจองคิวร้านค้า</p>
            </div>
            <a href="dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>กลับ
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อรายการ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">แก้ไขโดย</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่แก้ไข</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($items): ?>
                        <?php foreach ($items as $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $item['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $item['title']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $item['updated_by'] ?? 'ไม่ระบุ'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    แก้ไขแล้ว
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                ไม่พบรายการที่มีการแก้ไข
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 