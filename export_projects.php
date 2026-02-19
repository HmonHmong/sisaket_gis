<?php
// export_projects.php - ระบบส่งออกข้อมูลโครงสร้างพื้นฐานสำหรับวิเคราะห์งบประมาณ
require_once 'auth_check.php';
require_once 'config/db.php';

// 1. รับค่าตัวกรอง (Filters) เพื่อให้ส่งออกเฉพาะข้อมูลที่ต้องการ
$f_district = isset($_GET['district']) ? $_GET['district'] : '';
$f_year = isset($_GET['year']) ? $_GET['year'] : '';
$f_type = isset($_GET['type']) ? $_GET['type'] : '';

// 2. สร้าง Query พร้อมตัวกรอง
$query_parts = ["1=1"];
$params = [];
$types = "";

if ($f_district) {
    $query_parts[] = "district_name = ?";
    $params[] = $f_district;
    $types .= "s";
}
if ($f_year) {
    $query_parts[] = "fiscal_year = ?";
    $params[] = $f_year;
    $types .= "i";
}
if ($f_type) {
    $query_parts[] = "infrastructure_type = ?";
    $params[] = $f_type;
    $types .= "s";
}

$where_sql = implode(" AND ", $query_parts);
$sql = "SELECT project_name, infrastructure_type, fiscal_year, district_name, 
               distance, width, area, budget_amount, budget_type, 
               start_lat, start_long, end_lat, end_long, status, created_at 
        FROM projects 
        WHERE $where_sql 
        ORDER BY fiscal_year DESC, district_name ASC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// 3. ตั้งค่า Header สำหรับการดาวน์โหลดไฟล์ CSV
$filename = "sisaket_gis_export_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 4. สร้างตัวจัดการไฟล์ Output
$output = fopen('php://output', 'w');

// ใส่ UTF-8 BOM เพื่อให้ Excel แสดงผลภาษาไทยได้ถูกต้อง (สำคัญมาก)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 5. ส่วนหัวของตาราง (Header Row)
fputcsv($output, [
    'ชื่อโครงการ/สายทาง',
    'ประเภท',
    'ปีงบประมาณ',
    'อำเภอ',
    'ระยะทาง (ม.)',
    'ความกว้าง (ม.)',
    'พื้นที่ดำเนินการ (ตร.ม.)',
    'งบประมาณ (บาท)',
    'ประเภทงบ',
    'พิกัดเริ่ม (Lat)',
    'พิกัดเริ่ม (Long)',
    'พิกัดสิ้นสุด (Lat)',
    'พิกัดสิ้นสุด (Long)',
    'สถานะ',
    'วันที่บันทึก'
]);

// 6. ส่วนเนื้อหาข้อมูล (Data Rows)
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // แปลงประเภทโครงสร้างเป็นภาษาไทย
        $type_th = '';
        switch($row['infrastructure_type']) {
            case 'road': $type_th = 'งานทาง/ถนน'; break;
            case 'bridge': $type_th = 'สะพาน/ท่อเหลี่ยม'; break;
            case 'building': $type_th = 'อาคาร/โรงเรือน'; break;
            case 'water': $type_th = 'แหล่งน้ำ'; break;
            default: $type_th = $row['infrastructure_type'];
        }

        fputcsv($output, [
            $row['project_name'],
            $type_th,
            $row['fiscal_year'],
            $row['district_name'],
            number_format($row['distance'], 2, '.', ''),
            number_format($row['width'], 2, '.', ''),
            number_format($row['area'], 2, '.', ''),
            number_format($row['budget_amount'], 2, '.', ''),
            $row['budget_type'],
            $row['start_lat'],
            $row['start_long'],
            $row['end_lat'],
            $row['end_long'],
            $row['status'],
            $row['created_at']
        ]);
    }
}

fclose($output);
exit;
?>