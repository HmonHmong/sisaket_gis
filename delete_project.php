<?php
// delete_project.php
// ที่อยู่ไฟล์: /delete_project.php
include 'config/db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // ลบข้อมูลโครงการ (เนื่องจากตั้ง ON DELETE CASCADE ใน SQL ไว้แล้ว ข้อมูลใน project_points จะถูกลบอัตโนมัติ)
    $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: projects.php?deleted=1");
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    header("Location: projects.php");
}
?>