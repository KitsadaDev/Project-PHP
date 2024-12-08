<?php
session_start();
require_once 'config/db.php';

// ถ้าล็อกอินแล้วให้ไปหน้า dashboard
if (isset($_SESSION['user_id'])) {
    header('location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // เพิ่ม debug
    error_log("Attempting to register: " . $username . " | " . $email);

    try {
        // ตรวจสอบว่า username ซ้ำหรือไม่
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $username_exists = $stmt->fetchColumn();

        // ตรวจสอบว่า email ซ้ำหรือไม่
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $email_exists = $stmt->fetchColumn();

        if ($username_exists > 0) {
            $error = 'Username นี้มีในระบบแล้ว';
        }
        elseif ($email_exists > 0) {
            $error = 'Email นี้มีในระบบแล้ว';
        }
        // ตรวจสอบว่ารหัสผ่านตรงกัน
        elseif ($password !== $confirm_password) {
            $error = 'รหัสผ่านไม่ตรงกัน';
        }
        // ตรวจสอบความยาวรหัสผ่าน
        elseif (strlen($password) < 6) {
            $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
        }
        else {
            // เพิ่มผู้ใช้ใหม่
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            if ($stmt->execute([$username, $email, $hashed_password])) {
                $success = 'สร้างบัญชีสำเร็จ กรุณาเข้าสู่ระบบ';
                // รอ 2 วินาทีแล้วไปหน้า login
                header('refresh:2;url=login.php');
            } else {
                $error = 'เกิดข้อผิดพลาดในการสร้างบัญชี';
            }
        }
    } catch(PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สม้างบัญชี - ระบบจัดการคิว</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow">
            <div>
                <h2 class="text-center text-3xl font-extrabold text-gray-900">
                    สร้างบัญชีใหม่
                </h2>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" method="POST">
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">
                            Username
                        </label>
                        <input id="username" name="username" type="text" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="ชื่อผู้ใช้">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Email
                        </label>
                        <input id="email" name="email" type="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="อีเมล">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <input id="password" name="password" type="password" required 
                               class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="รหัสผ่าน">
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                            Confirm Password
                        </label>
                        <input id="confirm_password" name="confirm_password" type="password" required 
                               class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="ยืนยันรหัสผ่าน">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        สร้างบัญชี
                    </button>
                </div>

                <div class="text-sm text-center">
                    <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                        มีบัญชีอยู่แล้ว? เข้าสู่ระบบ
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 