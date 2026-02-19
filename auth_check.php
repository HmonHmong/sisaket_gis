<?php
// auth_check.php - ด่านตรวจความปลอดภัยสำหรับการเข้าถึงระบบ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    // หากพยายามเข้าถึงไฟล์โดยตรงโดยไม่ได้ผ่าน index.php ให้เด้งไปหน้า login
    header("Location: login.php");
    exit;
}

// 2. ตรวจสอบสถานะบัญชี (กรณีถูก Admin ระงับสิทธิ์ระหว่างการใช้งาน)
// หมายเหตุ: ในระบบขนาดใหญ่ควรมีการตรวจสอบจาก DB เป็นระยะ แต่เบื้องต้นใช้ค่าจาก Session ได้
if (isset($_SESSION['status']) && $_SESSION['status'] === 'inactive') {
    session_destroy();
    header("Location: login.php?error=account_disabled");
    exit;
}
?>