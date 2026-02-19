<?php
// update_user_status.php - ตรรกะการเปลี่ยนสถานะเจ้าหน้าที่ (Admin Only)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// 1. ตรวจสอบสิทธิ์ Admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// 2. รับค่าและตรวจสอบความถูกต้อง
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$new_status = isset($_GET['status']) ? $_GET['status'] : '';

// ป้องกัน Admin ระงับสิทธิ์ตัวเอง
if ($id === $_SESSION['user_id']) {
    header("Location: users.php?status=error&message=cannot_disable_self");
    exit;
}

if ($id > 0 && ($new_status === 'active' || $new_status === 'inactive')) {
    try {
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);
        
        if ($stmt->execute()) {
            $log_msg = "เปลี่ยนสถานะ User ID #$id เป็น " . strtoupper($new_status);
            add_notification($conn, "เปลี่ยนสถานะผู้ใช้งาน", $log_msg, 'system');
            
            header("Location: users.php?status=updated");
        } else {
            throw new Exception("Update failed");
        }
    } catch (Exception $e) {
        header("Location: users.php?status=error");
    }
} else {
    header("Location: users.php");
}
exit;