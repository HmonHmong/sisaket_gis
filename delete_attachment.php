<?php
// delete_attachment.php - ลบไฟล์โครงการอย่างปลอดภัย (รวมถึงลบไฟล์จริงในเครื่อง)
require_once 'auth_check.php';
require_once 'config/db.php';

header('Content-Type: application/json');

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'error' => 'No permission']);
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // 1. ค้นหาข้อมูลไฟล์ก่อนลบ
    $stmt = $conn->prepare("SELECT file_path FROM project_attachments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $file = $stmt->get_result()->fetch_assoc();

    if ($file) {
        $full_path = 'uploads/projects/' . $file['file_path'];
        
        // 2. ลบไฟล์จริงในเครื่อง (ถ้ามี)
        if (file_exists($full_path)) {
            unlink($full_path);
        }

        // 3. ลบข้อมูลในฐานข้อมูล
        $del = $conn->prepare("DELETE FROM project_attachments WHERE id = ?");
        $del->bind_param("i", $id);
        
        if ($del->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'File not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
}
exit;