<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('location: login.php');
    exit();
}

// ตำหนดค่าเริ่มต้น
$services = [];
$message = '';
$error = '';

try {
    // ดึงข้อมูลบริการทั้งหมด
    $stmt = $conn->prepare("
        SELECT s.*, 
        (SELECT COUNT(*) FROM queues WHERE service_type = s.id) as total_queues
        FROM services s 
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // จัดการการเพิ่มบริการ
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $estimated_time = (int)$_POST['estimated_time'];
            $price = (float)$_POST['price'];

            $stmt = $conn->prepare("
                INSERT INTO services (name, description, estimated_time, price) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $description, $estimated_time, $price]);
            $message = 'เพิ่มบริการสำเร็จ';
            header('location: services_management.php');
            exit();
        }
        
        // จัดการการแก้ไขบริการ
        elseif ($_POST['action'] === 'edit' && isset($_POST['service_id'])) {
            $service_id = $_POST['service_id'];
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $estimated_time = (int)$_POST['estimated_time'];
            $price = (float)$_POST['price'];
            $active = isset($_POST['active']) ? 1 : 0;

            $stmt = $conn->prepare("
                UPDATE services 
                SET name = ?, description = ?, estimated_time = ?, price = ?, active = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $estimated_time, $price, $active, $service_id]);
            $message = 'แก้ไขบริการสำเร็จ';
            header('location: services_management.php');
            exit();
        }
        
        // จัดการการลบบริการ
        elseif ($_POST['action'] === 'delete' && isset($_POST['service_id'])) {
            $service_id = $_POST['service_id'];
            
            // ตรวจสอบว่ามีคิวที่ใช้บริการนี้อยู่หรือไม่
            $stmt = $conn->prepare("SELECT COUNT(*) FROM queues WHERE service_type = ?");
            $stmt->execute([$service_id]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'ไม่สามารถลบบริการนี้ได้เนื่องจากมีคิวที่ใช้บริการนี้อยู่';
            } else {
                $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
                $stmt->execute([$service_id]);
                $message = 'ลบบริการสำเร็จ';
                header('location: services_management.php');
                exit();
            }
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
    <title>จัดการบริการ - ระบบจัดการการจองคิวร้านค้า</title>
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
            <h1 class="text-3xl font-bold text-gray-800">จัดการบริการ</h1>
            <button onclick="document.getElementById('addServiceModal').classList.remove('hidden')"
                    class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                <i class="fas fa-plus mr-2"></i>
                เพิ่มบริการ
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

        <!-- ตารางบริการ -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อบริการ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">รายละเอียด</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">เวลา (นาที)</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">ราคา</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">จำนวนคิว</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($services as $service): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($service['name']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php echo htmlspecialchars($service['description']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-center text-gray-900">
                            <?php echo $service['estimated_time']; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-center text-gray-900">
                            <?php echo number_format($service['price'], 2); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $service['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $service['active'] ? 'เปิดใช้บริการ' : 'ปิดให้บริการ'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-center text-gray-900">
                            <?php echo number_format($service['total_queues']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick='editService(<?php echo json_encode($service); ?>)'
                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteService(<?php echo $service['id']; ?>)"
                                    class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal เพิ่มบริการ -->
    <div id="addServiceModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">เพิ่มบริการ</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อบริการ</label>
                        <input type="text" name="name" required
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">รายละเอียด</label>
                        <textarea name="description" rows="3"
                                  class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">เวลาที่ใช้ (นาที)</label>
                        <input type="number" name="estimated_time" required min="1"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">ราคา</label>
                        <input type="number" name="price" required min="0" step="0.01"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="document.getElementById('addServiceModal').classList.add('hidden')"
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

    <!-- Modal แก้ไขบริการ -->
    <div id="editServiceModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">แก้ไขบริการ</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="service_id" id="edit_service_id">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อบริการ</label>
                        <input type="text" name="name" id="edit_name" required
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">รายละเอียด</label>
                        <textarea name="description" id="edit_description" rows="3"
                                  class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">เวลาที่ใช้ (นาที)</label>
                        <input type="number" name="estimated_time" id="edit_estimated_time" required min="1"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">ราคา</label>
                        <input type="number" name="price" id="edit_price" required min="0" step="0.01"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="active" id="edit_active"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-600">เปิดให้บริการ</span>
                        </label>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="document.getElementById('editServiceModal').classList.add('hidden')"
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
        function editService(service) {
            document.getElementById('edit_service_id').value = service.id;
            document.getElementById('edit_name').value = service.name;
            document.getElementById('edit_description').value = service.description;
            document.getElementById('edit_estimated_time').value = service.estimated_time;
            document.getElementById('edit_price').value = service.price;
            document.getElementById('edit_active').checked = service.active == 1;
            document.getElementById('editServiceModal').classList.remove('hidden');
        }

        function deleteService(serviceId) {
            if (confirm('ต้องการลบบริการนี้หรือไม่?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="service_id" value="${serviceId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 