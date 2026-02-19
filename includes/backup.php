<?php
// backup.php - ระบบสำรองข้อมูลฐานข้อมูลสำหรับผู้ดูแลระบบ (Admin Only)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// ตรวจสอบสิทธิ์ (เฉพาะ Admin เท่านั้น)
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?error=no_permission");
    exit;
}

// ตรรกะการส่งออกข้อมูล SQL
if (isset($_GET['action']) && $_GET['action'] == 'export') {
    // 1. ดึงรายชื่อตารางทั้งหมดในฐานข้อมูล
    $tables = array();
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    $sql_output = "-- SISAKET PAO GIS INFRASTRUCTURE DATABASE BACKUP\n";
    $sql_output .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    $sql_output .= "-- Backup by: " . $_SESSION['full_name'] . "\n\n";
    $sql_output .= "SET NAMES utf8mb4;\n";
    $sql_output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    foreach ($tables as $table) {
        // 2. สร้างโครงสร้างตาราง (CREATE TABLE)
        $res_create = $conn->query("SHOW CREATE TABLE $table");
        $row_create = $res_create->fetch_row();
        $sql_output .= "DROP TABLE IF EXISTS `$table`;\n" . $row_create[1] . ";\n\n";

        // 3. ดึงข้อมูลในตาราง (INSERT INTO)
        $res_data = $conn->query("SELECT * FROM $table");
        $num_fields = $res_data->field_count;

        while ($row = $res_data->fetch_row()) {
            $sql_output .= "INSERT INTO `$table` VALUES(";
            for ($j = 0; $j < $num_fields; $j++) {
                if (is_null($row[$j])) {
                    $sql_output .= "NULL";
                } else {
                    $val = addslashes($row[$j]);
                    $val = str_replace("\n", "\\n", $val);
                    $sql_output .= '"' . $val . '"';
                }
                if ($j < ($num_fields - 1)) {
                    $sql_output .= ',';
                }
            }
            $sql_output .= ");\n";
        }
        $sql_output .= "\n\n";
    }
    
    $sql_output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

    // บันทึกกิจกรรมลง Log
    log_activity($conn, 'BACKUP', 'database', null, "ส่งออกข้อมูลระบบ GIS สำนักช่าง ทั้งหมด (.sql)");

    // ส่งไฟล์ให้เบราว์เซอร์ดาวน์โหลด
    $filename = 'backup_gis_sskpao_' . date('Y-m-d_His') . '.sql';
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"" . $filename . "\"");
    echo $sql_output;
    exit;
}

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto pb-20 animate-in fade-in duration-700 px-4">
    <div class="flex items-center gap-5 mb-12">
        <div class="w-16 h-16 bg-slate-900 text-white rounded-[2rem] flex items-center justify-center shadow-2xl">
            <i data-lucide="database-backup" size="32"></i>
        </div>
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight">ศูนย์สำรองและบำรุงรักษาข้อมูล</h2>
            <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px] mt-1">Database Security & Disaster Recovery Portal</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Download Block -->
        <div class="bg-white p-10 rounded-[4rem] shadow-sm border border-slate-100 flex flex-col items-center text-center group">
            <div class="w-24 h-24 bg-emerald-50 text-emerald-600 rounded-[2.5rem] flex items-center justify-center shadow-inner mb-6 group-hover:scale-110 transition-transform">
                <i data-lucide="download-cloud" size="48"></i>
            </div>
            <h3 class="text-xl font-black text-slate-800">ส่งออกฐานข้อมูล SQL</h3>
            <p class="text-xs text-slate-400 font-bold mt-3 leading-relaxed">
                สร้างไฟล์ .sql ที่บรรจุโครงสร้างตารางและพิกัดโครงการทั้งหมด <br>เพื่อเก็บรักษาไว้ภายนอกเซิร์ฟเวอร์
            </p>
            <div class="w-full pt-8">
                <a href="backup.php?action=export" class="block w-full bg-slate-900 text-white py-4 rounded-2xl font-black uppercase tracking-[0.15em] text-xs hover:bg-emerald-600 transition-all shadow-xl shadow-slate-100 flex items-center justify-center gap-2">
                    <i data-lucide="hard-drive-download" size="18"></i>
                    ดาวน์โหลดข้อมูลทันที
                </a>
            </div>
        </div>

        <!-- Instructions Block -->
        <div class="bg-orange-600 p-10 rounded-[4rem] text-white shadow-2xl relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 text-white/10 rotate-12"><i data-lucide="shield-alert" size="180"></i></div>
            <div class="relative z-10">
                <h4 class="font-black text-sm uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i data-lucide="info" size="18"></i> มาตรฐานความปลอดภัย
                </h4>
                <div class="space-y-5">
                    <div class="flex gap-4 items-start">
                        <div class="w-6 h-6 rounded-lg bg-white/20 flex items-center justify-center shrink-0 font-black text-[10px]">1</div>
                        <p class="text-xs font-bold leading-relaxed">ควรสำรองข้อมูลทุกครั้งก่อนมีการแก้ไขโครงสร้างระบบขนาดใหญ่</p>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="w-6 h-6 rounded-lg bg-white/20 flex items-center justify-center shrink-0 font-black text-[10px]">2</div>
                        <p class="text-xs font-bold leading-relaxed">ไฟล์สำรองประกอบด้วยข้อมูลอ่อนไหว (พิกัดและชื่อเจ้าหน้าที่) กรุณาเก็บรักษาเป็นความลับ</p>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="w-6 h-6 rounded-lg bg-white/20 flex items-center justify-center shrink-0 font-black text-[10px]">3</div>
                        <p class="text-xs font-bold leading-relaxed">ในกรณีระบบขัดข้อง สามารถนำไฟล์นี้ไปกู้คืนผ่าน phpMyAdmin ได้โดยใช้คำสั่ง Import</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>