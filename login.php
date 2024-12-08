<?php
session_start();
require_once 'config/db.php';

if (isset($_POST['signin'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $check_data = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $check_data->bindParam(":email", $email);
        $check_data->execute();
        $row = $check_data->fetch(PDO::FETCH_ASSOC);

        if ($check_data->rowCount() > 0) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['role'] = $row['role'];
                header("location: dashboard.php");
                exit();
            } else {
                $_SESSION['error'] = 'รหัสผ่านไม่ถูกต้อง';
                header("location: login.php");
                exit();
            }
        } else {
            $_SESSION['error'] = 'ไม่พบอีเมลนี้ในระบบ';
            header("location: login.php");
            exit();
        }
    } catch(PDOException $e) {
        echo $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo และหัวข้อ -->
            <div class="text-center">
                <i class="fas fa-store text-blue-500 text-6xl mb-4"></i>
                <h2 class="text-center text-3xl font-extrabold text-gray-900">
                    ระบบจัดการการจองคิวร้านค้า
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    กรุณาเข้าสู่ระบบเพื่อดำเนินการ
                </p>
            </div>

            <!-- แสดงข้อความ error -->
            <?php if(isset($_SESSION['error'])) { ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">
                        <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                        ?>
                    </span>
                </div>
            <?php } ?>

            <!-- ฟอร์มล็อกอิน -->
            <form class="mt-8 space-y-6" action="" method="POST">
                <div class="space-y-4">
                    <!-- อีเมล -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input id="email" name="email" type="email" required 
                                   class="appearance-none relative block w-full px-3 py-3 pl-10
                                          border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg
                                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                   placeholder="กรอกอีเมลของคุณ">
                        </div>
                    </div>

                    <!-- รหัสผ่าน -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input id="password" name="password" type="password" required
                                   class="appearance-none relative block w-full px-3 py-3 pl-10
                                          border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg
                                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                   placeholder="กรอกรหัสผ่านของคุณ">
                        </div>
                    </div>
                </div>

                <!-- ปุ่มเข้าสู่ระบบ -->
                <div class="mt-6">
                    <button type="submit" name="signin"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium
                                   rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2
                                   focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-sign-in-alt text-blue-500 group-hover:text-blue-400"></i>
                        </span>
                        เข้าสู่ระบบ
                    </button>
                </div>
            </form>

            <!-- ลิงก์เพิ่มเติม -->
            <div class="text-center text-sm">
                <a href="#" class="font-medium text-blue-600 hover:text-blue-500">
                    ลืมรหัสผ่าน?
                </a>
            </div>

            <!-- เพิ่มส่วนนี้ไว้ใต้ปุ่ม "เข้าสู่ระบบ" ในไฟล์ login.php -->
            <div class="text-sm text-center mt-4">
                <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">
                    ยังไม่มีบัญชี? สร้างบัญชีใหม่
                </a>
            </div>
        </div>
    </div>
</body>
</html> 