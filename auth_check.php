<?php
// auth_check.php
// ที่อยู่ไฟล์: /auth_check.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// หากไม่มี user_id ใน session แสดงว่ายังไม่ได้ล็อกอิน ให้ส่งกลับไปหน้า login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>