<?php
// export_projects.php
// ที่อยู่ไฟล์: /export_projects.php
require_once 'auth_check.php';
require_once 'config/db.php';

// กำหนดชื่อไฟล์สำหรับการดาวน์โหลด (ชื่อระบบ_วันที่)
$filename = "GIS_Projects_Export_" . date('Y-m-d_H-i-s') . ".csv";

// ตั้งค่า Header เพื่อบังคับให้บราวเซอร์ดาวน์โหลดไฟล์
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '";');

// เปิด Output Stream
$output = fopen('php://output', 'w');

// แก้ปัญหาภาษาไทยใน Excel (เพิ่ม BOM สำหรับ UTF-8)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 1. เขียนส่วนหัวของตาราง (Column Headers)
fputcsv($output, [
    'ลำดับ', 
    'ชื่อโครงการ', 
    'ปีงบประมาณ', 
    'ระยะทาง (ม.)', 
    'ความกว้าง (ม.)', 
    'ไหล่ทาง', 
    'กว้างไหล่ทาง (ม.)', 
    'พื้นที่ดำเนินการ (ตร.ม.)', 
    'งบประมาณ (บาท)', 
    'ประเภทงบประมาณ', 
    'พิกัดเริ่มต้น (Lat,Long)', 
    'พิกัดสิ้นสุด (Lat,Long)', 
    'สถานะ',
    'วันที่บันทึก'
]);

// 2. ดึงข้อมูลจากฐานข้อมูล
$sql = "SELECT * FROM projects ORDER BY fiscal_year DESC, id DESC";
$query = $conn->query($sql);

$i = 1;
while ($row = $query->fetch_assoc()) {
    fputcsv($output, [
        $i++,
        $row['project_name'],
        $row['fiscal_year'],
        number_format($row['distance'], 2, '.', ''),
        number_format($row['width'], 2, '.', ''),
        $row['has_shoulder'] ? 'มี' : 'ไม่มี',
        number_format($row['shoulder_width'], 2, '.', ''),
        number_format($row['area'], 2, '.', ''),
        number_format($row['budget_amount'], 2, '.', ''),
        $row['budget_type'],
        $row['start_lat'] . ',' . $row['start_long'],
        $row['end_lat'] . ',' . $row['end_long'],
        $row['status'],
        $row['created_at']
    ]);
}

fclose($output);
exit;
?>