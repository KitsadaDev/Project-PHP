<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('location: login.php');
    exit();
}

try {
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY id DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายชื่อผู้ใช้</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">รายชื่อผู้ใช้ระบบ</h1>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">อีเมล</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่สมัคร</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $user['id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $user['email']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $user['role'] === 'admin' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo $user['role']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>