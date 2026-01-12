<?php
// clear_notifications.php
require_once 'auth_check.php';
require_once 'config/db.php';

$user_id = $_SESSION['user_id'];
$conn->query("DELETE FROM notifications WHERE user_id = $user_id OR user_id IS NULL");

header("Location: notifications.php");
exit;
?>