<?php
// restore_project.php - ระบบกู้คืนโครงการ
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE projects SET deleted_at = NULL WHERE id = ?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()) {
        log_activity($conn, 'UPDATE', 'project', $id, "กู้คืนโครงการจากถังขยะ (ID: $id)");
        header("Location: trash.php?msg=restored");
        exit;
    }
}
header("Location: trash.php");
?>