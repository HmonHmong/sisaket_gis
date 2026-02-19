<?php
// config/db.php
// ปรับปรุงการเชื่อมต่อฐานข้อมูลโดยนำฟังก์ชัน e() ออกเพื่อป้องกันการประกาศซ้ำ
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "sisaket_gis";

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("ขออภัย: ไม่สามารถเชื่อมต่อฐานข้อมูลได้ในขณะนี้");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ลบฟังก์ชัน e() ออกจากที่นี่แล้ว เพราะไปเก็บไว้ใน includes/functions.php แทน
?>