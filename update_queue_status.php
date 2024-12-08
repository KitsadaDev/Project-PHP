<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

// รับข้อมูล JSON
$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $conn->prepare("
        UPDATE queues 
        SET status = :status,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :queue_id
    ");

    $stmt->execute([
        'status' => $data['status'],
        'queue_id' => $data['queue_id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'อัพเดทสถานะสำเร็จ'
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
?> 