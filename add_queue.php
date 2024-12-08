<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

try {
    // สร้างเลขคิว (format: Q001, Q002, ...)
    $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(queue_number, 2) AS UNSIGNED)) as max_num FROM queues WHERE DATE(created_at) = CURDATE()");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $next_num = ($result['max_num'] ?? 0) + 1;
    $queue_number = 'Q' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

    // เพิ่มคิวใหม่
    $stmt = $conn->prepare("
        INSERT INTO queues (
            customer_name, 
            phone, 
            queue_number, 
            service_type, 
            notes, 
            user_id
        ) VALUES (
            :customer_name,
            :phone,
            :queue_number,
            :service_type,
            :notes,
            :user_id
        )
    ");

    $stmt->execute([
        'customer_name' => $_POST['customer_name'],
        'phone' => $_POST['phone'],
        'queue_number' => $queue_number,
        'service_type' => $_POST['service_type'],
        'notes' => $_POST['notes'],
        'user_id' => $_SESSION['user_id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'เพิ่มคิวสำเร็จ',
        'queue_number' => $queue_number
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
?> 