<?php
// delete_project.php - ตรรกะการลบโครงการ (Soft Delete & Hard Delete) ฉบับสมบูรณ์
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// ตรวจสอบสิทธิ์ (Staff และ Admin เข้าถึงได้ แต่ Staff ลบถาวรไม่ได้)
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: projects.php?msg=no_permission");
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // รับค่าว่าเป็นการสั่งลบถาวรหรือไม่ (ถ้าไม่ได้ส่งมาให้ถือว่าเป็น 0 คือแค่ย้ายลงถังขยะ)
    $force = isset($_GET['force']) ? intval($_GET['force']) : 0;
    
    $conn->begin_transaction();
    try {
        // 1. เช็คว่ามีโครงการนี้อยู่จริงหรือไม่
        $stmt_check = $conn->prepare("SELECT project_name FROM projects WHERE id = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $project = $stmt_check->get_result()->fetch_assoc();
        
        if (!$project) { throw new Exception("ไม่พบโครงการในระบบ"); }
        $p_name = $project['project_name'];

        if ($force === 1 && $_SESSION['role'] === 'admin') {
            // ==========================================
            // แบบที่ 1: HARD DELETE (ลบถาวร - เฉพาะ Admin)
            // ==========================================
            
            // 1.1 ลบข้อมูลพิกัดย่อย (ดักจับ Error ไว้เผื่อไม่มีตาราง)
            try { $conn->query("DELETE FROM project_points WHERE project_id = $id"); } catch (Exception $e) {}
            
            // 1.2 ลบประวัติสถานะ
            try { $conn->query("DELETE FROM project_status_history WHERE project_id = $id"); } catch (Exception $e) {}
            
            // 1.3 ลบไฟล์แนบและลบไฟล์รูปภาพออกจากเครื่อง Server
            try {
                $res_files = $conn->query("SELECT file_path FROM project_attachments WHERE project_id = $id");
                while($f = $res_files->fetch_assoc()) { 
                    @unlink('uploads/projects/' . $f['file_path']); 
                }
                $conn->query("DELETE FROM project_attachments WHERE project_id = $id");
            } catch (Exception $e) {}

            // 1.4 ลบข้อมูลโครงการตัวหลัก (เป้าหมายสำคัญ)
            $stmt_del = $conn->prepare("DELETE FROM projects WHERE id = ?");
            $stmt_del->bind_param("i", $id);
            if (!$stmt_del->execute()) { throw new Exception("ไม่สามารถลบข้อมูลหลักได้"); }

            log_activity($conn, 'DELETE', 'project', $id, "ลบโครงการถาวร: " . $p_name);
            add_notification($conn, "ลบข้อมูลโครงการถาวร", "โครงการ $p_name ถูกลบถาวรเรียบร้อยแล้ว", 'delete');
            
            $conn->commit();
            // ส่งกลับไปหน้าถังขยะ
            header("Location: trash.php?msg=deleted_permanently");
            
        } else {
            // ==========================================
            // แบบที่ 2: SOFT DELETE (ย้ายลงถังขยะ - ค่าเริ่มต้น)
            // ==========================================
            
            $stmt_soft = $conn->prepare("UPDATE projects SET deleted_at = NOW() WHERE id = ?");
            $stmt_soft->bind_param("i", $id);
            if (!$stmt_soft->execute()) { throw new Exception("ไม่สามารถย้ายลงถังขยะได้"); }

            log_activity($conn, 'UPDATE', 'project', $id, "ย้ายโครงการลงถังขยะ: " . $p_name);
            add_notification($conn, "ย้ายข้อมูลลงถังขยะ", "โครงการ $p_name ถูกย้ายลงถังขยะ", 'delete');
            
            $conn->commit();
            // ส่งกลับไปหน้าทะเบียนโครงการ
            header("Location: projects.php?msg=trashed");
        }
        
    } catch (Exception $e) {
        $conn->rollback(); // ยกเลิกหากพัง
        header("Location: projects.php?msg=error&err=" . urlencode($e->getMessage()));
    }
} else {
    header("Location: projects.php");
}
exit;