<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ';
    header('location: login.php');
    exit();
}

// จัดการการเพิ่มรายการ
if (isset($_POST['add_item'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    try {
        // อัพโหลดรูปภาพ
        $image_name = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                $image_name = time() . '.' . $filetype;
                $target = 'uploads/items/' . $image_name;
                
                // สร้างโฟลเดอร์ถ้ายังไม่มี
                if (!file_exists('uploads/items')) {
                    mkdir('uploads/items', 0777, true);
                }
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    throw new Exception('ไม่สามารถอัพโหลดรูปภาพได้');
                }
            } else {
                throw new Exception('อนุญาตเฉพาะไฟล์รูปภาพเท่านั้น (jpg, jpeg, png, gif)');
            }
        }

        // เพิ่มข้อมูลลงฐานข้อมูล
        $stmt = $conn->prepare("INSERT INTO items (user_id, title, description, price, image, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $description, $price, $image_name, $status]);

        $_SESSION['success'] = 'เพิ่มรายการสำเร็จ';
        header('location: dashboard.php');
        exit();

    } catch(Exception $e) {
        $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มรายการใหม่</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-6">
        <div class="max-w-4xl mx-auto">
            <!-- หัวข้อและปุ่มกลับ -->
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold">เพิ่มรายการใหม่</h1>
                <a href="dashboard.php" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>กลับหน้าแดชบอร์ด
                </a>
            </div>

            <!-- แสดงข้อความ error/success -->
            <?php if (isset($_SESSION['error'])) { ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php } ?>

            <!-- ฟอร์มเพิ่มรายการ -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- ชื่อรายการ -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อรายการ</label>
                        <input type="text" name="title" required
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <!-- รายละเอียด -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">รายละเอียด</label>
                        <textarea name="description" rows="4"
                                  class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"></textarea>
                    </div>

                    <!-- ราคา -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">ราคา</label>
                        <input type="number" name="price" step="0.01" required
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <!-- รูปภาพ -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">รูปภาพ</label>
                        <input type="file" name="image"
                               class="w-full text-sm text-gray-500">
                    </div>

                    <!-- ปุ่มยืนยัน -->
                    <div class="mt-6">
                        <button type="submit" name="add_item"
                                class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">
                            ยืนยัน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 