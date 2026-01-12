<?php
// config/db.php
// ที่อยู่ไฟล์: /config/db.php

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "sisaket_gis";

// เชื่อมต่อด้วย MySQLi
$conn = new mysqli($host, $user, $pass, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่าชุดตัวอักษรเป็น utf8mb4
$conn->set_charset("utf8mb4");

// เริ่มต้น Session สำหรับระบบสมาชิก
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>