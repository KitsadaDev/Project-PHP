<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('location: login.php');
    exit();
}

// ดึงข้อมูลผู้ใช้
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// จัดการการเปลี่ยนรหัสผ่าน
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // ตรวจสอบว่ากรอกข้อมูลครบ
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = 'กรุณากรอกข้อมูลให้ครบ';
    } 
    // ตรวจสอบรหัสผ่านใหม่ต้อง���รงกัน
    elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'รหัสผ่านใหม่ไม่ตรงกัน';
    }
    // ตรวจสอบความยาวรหัสผ่านใหม่
    elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
    }
    else {
        try {
            // ตรวจสอบรหัสผ่านปัจจุบัน
            if (password_verify($current_password, $user['password'])) {
                // เข้ารหัสรหัสผ่านใหม่
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // อัพเดทรหัสผ่านในฐานข้อมูล
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$password_hash, $_SESSION['user_id']]);
                
                $_SESSION['success'] = 'เปลี่ยนรหัสผ่านสำเร็จ';
                header('location: profile.php');
                exit();
            } else {
                $_SESSION['error'] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เปลี่ยนรหัสผ่าน</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-96">
            <div class="flex items-center mb-6">
                <a href="profile.php" class="text-gray-600 hover:text-gray-800 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h2 class="text-2xl font-bold">เปลี่ยนรหัสผ่าน</h2>
            </div>

            <?php if (isset($_SESSION['error'])) { ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php } ?>

            <form method="POST" action="">
                <div class="space-y-4">
                    <!-- รหัสผ่านปัจจุบัน -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">รหัสผ่านปัจจุบัน</label>
                        <input type="password" name="current_password" required
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <!-- รหัสผ่านใหม่ -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">รหัสผ่านใหม่</label>
                        <input type="password" name="new_password" required
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                        <p class="text-sm text-gray-500 mt-1">รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร</p>
                    </div>

                    <!-- ยื���ยันรหัสผ่านใหม่ -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" name="confirm_password" required
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <!-- ปุ่มบันทึก -->
                    <button type="submit" name="change_password"
                            class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600">
                        <i class="fas fa-key mr-2"></i>เปลี่ยนรหัสผ่าน
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 