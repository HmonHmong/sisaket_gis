<?php
// update_user_status.php
// ที่อยู่ไฟล์: /update_user_status.php
require_once 'auth_check.php';
require_once 'config/db.php';

// ตรวจสอบสิทธิ์ Admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = intval($_GET['id']);
    $status = $_GET['status'] === 'active' ? 'active' : 'inactive';

    // ป้องกัน Admin ระงับตัวเอง
    if ($id === $_SESSION['user_id']) {
        header("Location: users.php?error=self_suspend");
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    
    if ($stmt->execute()) {
        header("Location: users.php?success=status_updated");
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    header("Location: users.php");
}
?>