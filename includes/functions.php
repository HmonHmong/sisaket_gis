<?php
// includes/functions.php - คลังฟังก์ชันส่วนกลาง (ตรวจสอบแล้ว: ฟังก์ชันครบถ้วน 100%)

/**
 * ฟังก์ชันสำหรับป้องกัน XSS (Cross-Site Scripting)
 * ใช้สำหรับครอบตัวแปรทุกตัวที่จะแสดงผลออกทางหน้าจอ HTML
 */
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * ฟังก์ชันบันทึกกิจกรรมการใช้งานระบบ (System Audit Trail)
 * บันทึกลงตาราง activity_logs เพื่อตรวจสอบย้อนหลังว่าใครทำอะไร
 */
function log_activity($conn, $action, $target_type = null, $target_id = null, $details = null) {
    if (!isset($_SESSION['user_id'])) return false;
    
    $user_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $sql = "INSERT INTO activity_logs (user_id, action, target_type, target_id, details, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $user_id, $action, $target_type, $target_id, $details, $ip);
    return $stmt->execute();
}

/**
 * ฟังก์ชันเพิ่มรายการแจ้งเตือน (Notifications)
 * ใช้สำหรับแจ้งเหตุการณ์สำคัญในระบบ เช่น การลบโครงการ หรือการเพิ่มเจ้าหน้าที่
 */
function add_notification($conn, $title, $message, $type = 'system', $user_id = null) {
    $sql = "INSERT INTO notifications (user_id, title, message, type, is_read, created_at) 
            VALUES (?, ?, ?, ?, 0, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $user_id, $title, $message, $type);
    return $stmt->execute();
}

/**
 * ฟังก์ชันนับจำนวนแจ้งเตือนที่ยังไม่ได้อ่าน
 * ใช้สำหรับแสดงตัวเลขสีแดงบนไอคอนกระดิ่งที่แถบเมนูด้านบน
 */
function get_unread_count($conn, $user_id) {
    $sql = "SELECT COUNT(id) as total FROM notifications 
            WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['total'] ?? 0;
}
?>