<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('location: login.php');
    exit();
}

// ตรวจสอบว่าเป็น admin หรือไม่
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
    header('location: dashboard.php');
    exit();
}

// ดึงข้อมูลการตั้งค่าจากฐานข้อมูล
try {
    $stmt = $conn->prepare("SELECT * FROM settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// จัดการการอัพเดทการตั้งค่า
if (isset($_POST['update_settings'])) {
    try {
        $site_name = $_POST['site_name'];
        $site_description = $_POST['site_description'];
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $items_per_page = $_POST['items_per_page'];
        $contact_email = $_POST['contact_email'];
        $theme_color = $_POST['theme_color'];

        // อัพเดทการตั้งค่า
        $stmt = $conn->prepare("UPDATE settings SET 
            site_name = ?, 
            site_description = ?, 
            maintenance_mode = ?,
            items_per_page = ?,
            contact_email = ?,
            theme_color = ?
            WHERE id = 1");
        
        $stmt->execute([
            $site_name,
            $site_description,
            $maintenance_mode,
            $items_per_page,
            $contact_email,
            $theme_color
        ]);

        $_SESSION['success'] = 'อัพเดทการตั้งค่าสำเร็จ';
        header('location: settings.php');
        exit();
    } catch(PDOException $e) {
        $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าระบบ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Navbar -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-arrow-left mr-2"></i>กลับหน้าแดชบอร์ด
                        </a>
                    </div>
                    <div class="text-lg font-semibold">ตั้งค่าระบบ</div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- การ์ดแสดงสถานะระบบ -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                            <i class="fas fa-server text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">สถานะระบบ</p>
                            <p class="text-lg font-semibold text-gray-800">
                                <?php echo $settings['maintenance_mode'] ? 'ปิดปรับปรุง' : 'ใช้งานปกติ'; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-500">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">จำนวนผู้ใช้ทั้งหมด</p>
                            <p class="text-lg font-semibold text-gray-800">
                                <?php 
                                    $stmt = $conn->query("SELECT COUNT(*) FROM users");
                                    echo $stmt->fetchColumn();
                                ?> คน
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                            <i class="fas fa-box text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">จำนวนรายการทั้งหมด</p>
                            <p class="text-lg font-semibold text-gray-800">
                                <?php 
                                    $stmt = $conn->query("SELECT COUNT(*) FROM items");
                                    echo $stmt->fetchColumn();
                                ?> รายการ
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ฟอร์มตั้งค่า -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <?php if (isset($_SESSION['success'])) { ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                        <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                        ?>
                    </div>
                <?php } ?>

                <?php if (isset($_SESSION['error'])) { ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                        ?>
                    </div>
                <?php } ?>

                <form method="POST" action="" class="space-y-6">
                    <!-- ชื่อเว็บไซต์ -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ชื่อเว็บไซต์
                        </label>
                        <input type="text" name="site_name" 
                               value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>"
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- คำอธิบายเว็บไซต์ -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            คำอธิบายเว็บไซต์
                        </label>
                        <textarea name="site_description" rows="3"
                                  class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                    </div>

                    <!-- โหมดปิดปรับปรุง -->
                    <div class="flex items-center">
                        <input type="checkbox" name="maintenance_mode" id="maintenance_mode"
                               <?php echo ($settings['maintenance_mode'] ?? false) ? 'checked' : ''; ?>
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="maintenance_mode" class="ml-2 block text-sm text-gray-700">
                            เปิดโหมดปิดปรับปรุง
                        </label>
                    </div>

                    <!-- จำนวนรายการต่อหน้า -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            จำนวนรายการต่อหน้า
                        </label>
                        <input type="number" name="items_per_page" min="1" max="100"
                               value="<?php echo htmlspecialchars($settings['items_per_page'] ?? '10'); ?>"
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- อีเมลติดต่อ -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            อีเมลติดต่อ
                        </label>
                        <input type="email" name="contact_email"
                               value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>"
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- ธีมสี -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            ธีมสี
                        </label>
                        <select name="theme_color"
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="blue" <?php echo ($settings['theme_color'] ?? '') === 'blue' ? 'selected' : ''; ?>>น้ำเงิน</option>
                            <option value="green" <?php echo ($settings['theme_color'] ?? '') === 'green' ? 'selected' : ''; ?>>เขียว</option>
                            <option value="purple" <?php echo ($settings['theme_color'] ?? '') === 'purple' ? 'selected' : ''; ?>>ม่วง</option>
                            <option value="red" <?php echo ($settings['theme_color'] ?? '') === 'red' ? 'selected' : ''; ?>>แดง</option>
                        </select>
                    </div>

                    <!-- ปุ่มบันทึก -->
                    <button type="submit" name="update_settings"
                            class="w-full bg-blue-500 text-white py-3 px-4 rounded-lg hover:bg-blue-600 transition duration-200">
                        <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 