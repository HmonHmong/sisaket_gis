<?php
// includes/functions.php
// ที่อยู่ไฟล์: /includes/functions.php

/**
 * ฟังก์ชันสำหรับเพิ่มการแจ้งเตือนลงในฐานข้อมูล
 */
if (!function_exists('add_notification')) {
    function add_notification($conn, $title, $message, $type = 'system', $target_user_id = null) {
        $title = $conn->real_escape_string($title);
        $message = $conn->real_escape_string($message);
        
        $sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        // i = integer, s = string
        $stmt->bind_param("isss", $target_user_id, $title, $message, $type);
        
        return $stmt->execute();
    }
}

/**
 * ฟังก์ชันดึงจำนวนแจ้งเตือนที่ยังไม่ได้อ่าน
 */
if (!function_exists('get_unread_count')) {
    function get_unread_count($conn, $user_id) {
        $sql = "SELECT COUNT(*) as unread FROM notifications 
                WHERE (user_id = ? OR user_id IS NULL) 
                AND is_read = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res['unread'] ?? 0;
    }
}
?>