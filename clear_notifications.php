<?php
// clear_notifications.php - ตรรกะการล้างประวัติการแจ้งเตือนอย่างปลอดภัย
require_once 'auth_check.php';
require_once 'config/db.php';

$user_id = $_SESSION['user_id'];

try {
    // ใช้ Prepared Statement เพื่อความปลอดภัย
    // ลบการแจ้งเตือนที่เป็นของ User นั้นๆ หรือที่เป็นของระบบทั่วไป (user_id IS NULL)
    $sql = "DELETE FROM notifications WHERE user_id = ? OR user_id IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // กลับไปยังหน้าแจ้งเตือนพร้อมสถานะสำเร็จ
        header("Location: notifications.php?status=cleared");
    } else {
        throw new Exception("ไม่สามารถลบข้อมูลได้");
    }
} catch (Exception $e) {
    header("Location: notifications.php?status=error&message=" . urlencode($e->getMessage()));
}
exit;