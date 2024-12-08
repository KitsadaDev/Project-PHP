<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบว่าเป็น admin หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('location: dashboard.php');
    exit();
}

// กำหนดค่าเริ่มต้น
$users = [];
$message = '';
$error = '';

try {
    // ดึงข้อมูลผู้ใช้ทั้งหมด
    $stmt = $conn->prepare("
        SELECT id, username, email, role, created_at, 
        (SELECT COUNT(*) FROM queues WHERE user_id = users.id) as total_queues
        FROM users 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // จัดการการเพิ่มผู้ใช้ใหม่
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $role = $_POST['role'];

            // ตรวจสอบว่า��ี username หรือ email ซ้ำหรือไม่
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Username หรือ Email นี้มีในระบบแล้ว';
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO users (username, email, password, role) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                $message = 'เพิ่มผู้ใช้งานสำเร็จ';
                header('location: users_management.php');
                exit();
            }
        }
        
        // จัดการการลบผู้ใช้
        elseif ($_POST['action'] === 'delete' && isset($_POST['user_id'])) {
            $user_id = $_POST['user_id'];
            // ตรวจสอบว่าไม่ใช่การลบตัวเอง
            if ($user_id != $_SESSION['user_id']) {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = 'ลบผู้ใช้งานสำเร็จ';
                header('location: users_management.php');
                exit();
            } else {
                $error = 'ไม่สามารถลบบัญชีของตัวเองได้';
            }
        }
        
        // จัดการการแก้ไขผู้ใช้
        elseif ($_POST['action'] === 'edit' && isset($_POST['user_id'])) {
            $user_id = $_POST['user_id'];
            $email = trim($_POST['email']);
            $role = $_POST['role'];
            $password = trim($_POST['password']);

            if ($password) {
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET email = ?, role = ?, password = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$email, $role, password_hash($password, PASSWORD_DEFAULT), $user_id]);
            } else {
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET email = ?, role = ?
                    WHERE id = ?
                ");
                $stmt->execute([$email, $role, $user_id]);
            }
            $message = 'แก้ไขข้อมูลผู้ใช้สำเร็จ';
            header('location: users_management.php');
            exit();
        }
    }

} catch(PDOException $e) {
    $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน - ระบบจัดการการจองคิวร้านค้า</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">จัดการผู้ใช้งาน</h1>
            <button type="button" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600" 
                    onclick="document.getElementById('addUserModal').classList.remove('hidden')">
                <i class="fas fa-plus mr-2"></i>
                เพิ่มผู้ใช้งาน
            </button>
        </div>

        <!-- แสดงข้อความ -->
        <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- ตารางผู้ใช้งาน -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">บทบาท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">จำนวนคิว</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่สร้าง</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($user['username']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                <?php echo $user['role']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo number_format($user['total_queues']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button type="button" 
                                    onclick='editUser(<?php echo json_encode($user); ?>)'
                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <button type="button"
                                    onclick="deleteUser(<?php echo $user['id']; ?>)"
                                    class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal เพิ่มผู้ใช้ -->
    <div id="addUserModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">เพิ่มผู้ใช้งาน</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" name="username" required
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" required
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" name="password" required
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">บทบาท</label>
                        <select name="role" required
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="document.getElementById('addUserModal').classList.add('hidden')"
                                class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                            บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขผู้ใช้ -->
    <div id="editUserModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">แก้ไขผู้ใช้งาน</h3>
                <form method="POST" id="editUserForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" id="edit_username" disabled
                               class="w-full rounded-lg border-gray-300 bg-gray-100">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" id="edit_email" required
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password (เว้นว่างถ้าไม่ต้องการเปลี่ยน)</label>
                        <input type="password" name="password"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">บทบาท</label>
                        <select name="role" id="edit_role" required
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="document.getElementById('editUserModal').classList.add('hidden')"
                                class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                            บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // ฟังก์ชันแก้ไขผู้ใช้
        function editUser(user) {
            try {
                // ถ้า user เป็น string ให้แปลงเป็น object
                if (typeof user === 'string') {
                    user = JSON.parse(user);
                }
                
                // เซ็ตค่าให้ form
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_username').value = user.username;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_role').value = user.role;
                
                // แสดง modal
                document.getElementById('editUserModal').classList.remove('hidden');
            } catch (error) {
                console.error('Error in editUser:', error);
                alert('เกิดข้อผิดพลาดในการแก้ไขผู้ใช้');
            }
        }

        // ฟังก์ชันลบผู้ใช้
        function deleteUser(userId) {
            if (confirm('ต้องการลบผู้ใช้งานนี้หรือไม่?')) {
                try {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="${userId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                } catch (error) {
                    console.error('Error in deleteUser:', error);
                    alert('เกิดข้อผิดพลาดในการลบผู้ใช้');
                }
            }
        }

        // เพิ่มฟังก์ชันปิด modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // เพิ่ม event listener สำหรับปุ่มเพิ่มผู้ใช้
        document.addEventListener('DOMContentLoaded', function() {
            const addUserBtn = document.querySelector('[onclick*="addUserModal"]');
            if (addUserBtn) {
                addUserBtn.addEventListener('click', function() {
                    document.getElementById('addUserModal').classList.remove('hidden');
                });
            }
        });
    </script>
</body>
</html> 