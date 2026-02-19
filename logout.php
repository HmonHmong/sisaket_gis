<?php
// logout.php - ระบบออกจากระบบ
// เริ่มต้น Session เพื่อเข้าถึงข้อมูลปัจจุบัน
session_start();

// ล้างค่าตัวแปร Session ทั้งหมด
$_SESSION = array();

// หากมีการใช้ Cookie ในการเก็บ Session ให้ทำการลบ Cookie ทิ้งด้วย
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ทำลาย Session บน Server
session_destroy();

// ส่งผู้ใช้งานกลับไปยังหน้า Login
header("Location: login.php");
exit;
?>