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

// จัดการการอัพเดทข้อมูล
if (isset($_POST['update_profile'])) {
    $email = $_POST['email'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    try {
        // อัพโหลดรูปภาพ
        if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_img']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                // สร้างชื่อไฟล์ใหม่ด้วยเวลาปัจจุบัน
                $newname = time() . '.' . $filetype;
                $target = 'uploads/' . $newname;
                
                if (move_uploaded_file($_FILES['profile_img']['tmp_name'], $target)) {
                    // ลบรูปเก่า (ถ้ามี)
                    if ($user['profile_img'] && file_exists('uploads/' . $user['profile_img'])) {
                        unlink('uploads/' . $user['profile_img']);
                    }
                    
                    // อัพเดทข้อมูลพร้อมรูปภาพ
                    $stmt = $conn->prepare("UPDATE users SET email=?, firstname=?, lastname=?, phone=?, address=?, profile_img=? WHERE id=?");
                    $stmt->execute([$email, $firstname, $lastname, $phone, $address, $newname, $_SESSION['user_id']]);
                } else {
                    $_SESSION['error'] = 'เกิดข้อผิดพลาดในการอัพโหลดรูปภาพ';
                }
            } else {
                $_SESSION['error'] = 'อนุญาตเฉพาะไฟล์ภาพเท่านั้น (jpg, jpeg, png, gif)';
            }
        } else {
            // อัพเดทข้อมูลโดยไม่เปลี่ยนรูปภาพ
            $stmt = $conn->prepare("UPDATE users SET email=?, firstname=?, lastname=?, phone=?, address=? WHERE id=?");
            $stmt->execute([$email, $firstname, $lastname, $phone, $address, $_SESSION['user_id']]);
        }

        $_SESSION['success'] = 'อัพเดทข้อมูลสำเร็จ';
        header('location: profile.php');
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
    <title>โปรไฟล์ของฉัน</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-xl font-semibold">
                        <i class="fas fa-arrow-left mr-2"></i>กลับหน้าแดชบอร์ด
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto mt-8 px-4 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold">โปรไฟล์ของฉัน</h2>
                <a href="change_password.php" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-key mr-2"></i>เปลี่ยนรหัสผ่าน
                </a>
            </div>

            <?php if (isset($_SESSION['error'])) { ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php } ?>

            <?php if (isset($_SESSION['success'])) { ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php } ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <!-- รูปโปรไฟล์ -->
                <div class="mb-6 text-center">
                    <div class="mb-4">
                        <?php if ($user['profile_img']) { ?>
                            <img src="uploads/<?php echo $user['profile_img']; ?>" 
                                 alt="Profile" 
                                 class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-blue-500">
                        <?php } else { ?>
                            <div class="w-32 h-32 rounded-full mx-auto bg-gray-200 flex items-center justify-center">
                                <i class="fas fa-user text-4xl text-gray-500"></i>
                            </div>
                        <?php } ?>
                    </div>
                    <input type="file" name="profile_img" 
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- อีเมล -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">อีเมล</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <!-- เบอร์โทรศัพท์ -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">เบอร์โทรศัพท์</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <!-- ชื่อ -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อ</label>
                        <input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname'] ?? ''); ?>"
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <!-- นามสกุล -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">นามสกุล</label>
                        <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname'] ?? ''); ?>"
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                </div>

                <!-- ที่อยู่ -->
                <div class="mt-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">ที่อยู่</label>
                    <textarea name="address" rows="3" 
                              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>

                <!-- ปุ่มบันทึก -->
                <button type="submit" name="update_profile"
                        class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 mt-6">
                    <i class="fas fa-save mr-2"></i>บันทึกการเปลี่ยนแปลง
                </button>
            </form>
        </div>
    </div>
</body>
</html> 