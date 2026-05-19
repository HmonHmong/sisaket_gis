<?php
// delete_attachment.php - ระบบ API สำหรับลบไฟล์รูปภาพ/PDF (ทำงานเบื้องหลัง)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// บังคับให้ตอบกลับเป็นรูปแบบ JSON
header('Content-Type: application/json');

// เช็คสิทธิ์ว่ามีสิทธิ์ลบหรือไม่ (เฉพาะ Admin และ Staff)
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์ในการดำเนินการ']);
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // ค้นหาข้อมูลไฟล์จากฐานข้อมูล
    $stmt = $conn->prepare("SELECT file_path, project_id, file_name FROM project_attachments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $file = $res->fetch_assoc();
        $path = 'uploads/projects/' . $file['file_path'];
        
        // 1. ลบไฟล์จริงออกจากโฟลเดอร์เซิร์ฟเวอร์
        if (file_exists($path)) {
            @unlink($path);
        }
        
        // 2. ลบข้อมูลออกจากฐานข้อมูล
        $del_stmt = $conn->prepare("DELETE FROM project_attachments WHERE id = ?");
        $del_stmt->bind_param("i", $id);
        $del_stmt->execute();
        
        // 3. บันทึกประวัติกิจกรรม
        log_activity($conn, 'DELETE', 'project', $file['project_id'], "ลบไฟล์แนบ: " . $file['file_name']);
        
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'ไม่พบไฟล์ที่ต้องการลบ']);
?>