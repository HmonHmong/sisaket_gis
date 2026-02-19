<?php
// delete_project.php - ตรรกะการลบโครงการพร้อมแก้ไขตัวแปร Redirect เพื่อไม่ให้กระทบตัวกรอง
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: projects.php?msg=no_permission");
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $conn->begin_transaction();
    try {
        $stmt_check = $conn->prepare("SELECT project_name FROM projects WHERE id = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $project = $stmt_check->get_result()->fetch_assoc();
        
        if (!$project) { throw new Exception("ไม่พบโครงการ"); }
        $p_name = $project['project_name'];

        // ลบข้อมูลที่เกี่ยวข้อง
        $conn->query("DELETE FROM project_points WHERE project_id = $id");
        $conn->query("DELETE FROM project_status_history WHERE project_id = $id");
        
        $res_files = $conn->query("SELECT file_path FROM project_attachments WHERE project_id = $id");
        while($f = $res_files->fetch_assoc()) {
            @unlink('uploads/projects/' . $f['file_path']);
        }
        $conn->query("DELETE FROM project_attachments WHERE project_id = $id");

        $stmt_del = $conn->prepare("DELETE FROM projects WHERE id = ?");
        $stmt_del->bind_param("i", $id);
        $stmt_del->execute();

        log_activity($conn, 'DELETE', 'project', $id, "ลบโครงการ: " . $p_name);
        add_notification($conn, "ลบข้อมูลโครงการ", "โครงการ $p_name ถูกลบเรียบร้อยแล้ว", 'delete');

        $conn->commit();
        // แก้ไขจาก status=deleted เป็น msg=deleted
        header("Location: projects.php?msg=deleted");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: projects.php?msg=error");
    }
} else {
    header("Location: projects.php");
}
exit;