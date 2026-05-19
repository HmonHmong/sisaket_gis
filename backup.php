<?php
// backup.php - ระบบสำรองข้อมูลฐานข้อมูล (Database Backup) สำหรับ Admin
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// เฉพาะ Admin เท่านั้นที่เข้าถึงหน้านี้ได้
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?error=no_permission");
    exit;
}

// ==========================================
// ส่วนที่ 1: ตรรกะการ Export ฐานข้อมูล (SQL Dump)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'export_db') {
    $tables = array();
    $result = $conn->query("SHOW TABLES");
    while($row = $result->fetch_row()){
        $tables[] = $row[0];
    }

    $sqlScript = "-- ---------------------------------------------------------\n";
    $sqlScript .= "-- SSK GIS System Database Backup\n";
    $sqlScript .= "-- วันที่และเวลาสำรองข้อมูล: " . date('Y-m-d H:i:s') . "\n";
    $sqlScript .= "-- ---------------------------------------------------------\n\n";
    $sqlScript .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sqlScript .= "SET AUTOCOMMIT = 0;\n";
    $sqlScript .= "START TRANSACTION;\n";
    $sqlScript .= "SET time_zone = \"+07:00\";\n\n";

    foreach($tables as $table){
        $result = $conn->query("SHOW CREATE TABLE $table");
        $row = $result->fetch_row();
        $sqlScript .= "\n-- โครงสร้างตาราง `$table`\n";
        $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
        $sqlScript .= $row[1] . ";\n\n";

        $result = $conn->query("SELECT * FROM $table");
        $columnCount = $result->field_count;
        $rowCount = $result->num_rows;

        if ($rowCount > 0) {
            $sqlScript .= "-- ข้อมูลสำหรับตาราง `$table`\n";
            while($row = $result->fetch_row()){
                $sqlScript .= "INSERT INTO `$table` VALUES(";
                for($j=0; $j<$columnCount; $j++){
                    $row[$j] = $row[$j];
                    if(isset($row[$j])){
                        $sqlScript .= "'" . $conn->real_escape_string($row[$j]) . "'";
                    }else{
                        $sqlScript .= "NULL";
                    }
                    if($j < ($columnCount-1)){
                        $sqlScript .= ",";
                    }
                }
                $sqlScript .= ");\n";
            }
            $sqlScript .= "\n";
        }
    }
    $sqlScript .= "COMMIT;\n";

    // บันทึกการกระทำลงใน Logs
    log_activity($conn, 'BACKUP', 'system', null, "ดาวน์โหลดไฟล์สำรองข้อมูลฐานข้อมูล (.sql)");

    // สั่งให้ Browser ดาวน์โหลดไฟล์
    $filename = 'SSK_GIS_Backup_' . date('Ymd_His') . '.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $sqlScript;
    exit;
}

// ==========================================
// ส่วนที่ 2: หน้าจอแสดงผล (UI)
// ==========================================
include 'includes/header.php';

// คำนวณขนาดฐานข้อมูลคร่าวๆ
$db_size_query = $conn->query("SELECT table_schema AS 'db', SUM(data_length + index_length) / 1024 / 1024 AS 'size' FROM information_schema.TABLES WHERE table_schema = 'sisaket_gis' GROUP BY table_schema");
$db_size = $db_size_query ? round($db_size_query->fetch_assoc()['size'], 2) : 0;
?>

<div class="max-w-[1600px] mx-auto pb-20 animate-in fade-in duration-500 px-4">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 bg-teal-50 text-teal-600 rounded-2xl flex items-center justify-center shadow-sm">
            <i data-lucide="database-backup" size="28"></i>
        </div>
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight">สำรองข้อมูลระบบ</h2>
            <p class="text-slate-500 font-bold mt-1 uppercase text-xs tracking-widest">System Backup & Recovery</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- เมนูสำรองฐานข้อมูล (SQL) -->
        <div class="bg-white p-8 md:p-10 rounded-[3rem] shadow-sm border border-slate-100 flex flex-col justify-between group hover:shadow-lg transition-all">
            <div>
                <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-[2rem] flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-inner">
                    <i data-lucide="database" size="32"></i>
                </div>
                <h3 class="text-xl font-black text-slate-800 mb-2">สำรองฐานข้อมูล (Database)</h3>
                <p class="text-sm font-bold text-slate-500 leading-relaxed">
                    ดาวน์โหลดข้อมูลโครงการ พิกัด ผู้ใช้งาน และประวัติทั้งหมดในรูปแบบไฟล์ <span class="font-mono text-blue-500 bg-blue-50 px-2 py-0.5 rounded">.sql</span> เพื่อเก็บไว้เป็นเครื่องมือในการกู้คืนระบบกรณีฉุกเฉิน
                </p>
                <div class="mt-6 flex items-center gap-2 text-xs font-bold text-slate-400 bg-slate-50 p-3 rounded-xl w-max">
                    <i data-lucide="hard-drive" size="14"></i> ขนาดฐานข้อมูลปัจจุบัน: <span class="text-slate-700"><?= $db_size ?> MB</span>
                </div>
            </div>
            
            <a href="backup.php?action=export_db" class="mt-8 w-full bg-slate-900 hover:bg-blue-600 text-white font-black py-5 rounded-[2rem] transition-all shadow-xl flex items-center justify-center gap-2">
                <i data-lucide="download-cloud" size="18"></i> ดาวน์โหลดไฟล์ฐานข้อมูล (.SQL)
            </a>
        </div>

        <!-- คำแนะนำ / ข้อควรระวัง -->
        <div class="bg-slate-900 p-8 md:p-10 rounded-[3rem] shadow-xl text-white relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 opacity-10"><i data-lucide="shield-alert" size="150"></i></div>
            <div class="relative z-10">
                <h3 class="text-xl font-black text-orange-500 mb-4 flex items-center gap-2">
                    <i data-lucide="info" size="20"></i> ข้อควรระวัง (คำแนะนำ)
                </h3>
                <ul class="space-y-4 text-sm font-bold text-slate-300">
                    <li class="flex items-start gap-3">
                        <i data-lucide="check-circle" class="text-emerald-500 shrink-0 mt-0.5" size="16"></i>
                        <span>ควรทำการสำรองข้อมูลอย่างน้อย <strong>สัปดาห์ละ 1 ครั้ง</strong> เพื่อป้องกันข้อมูลสูญหาย</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i data-lucide="check-circle" class="text-emerald-500 shrink-0 mt-0.5" size="16"></i>
                        <span>ไฟล์ <span class="text-white">.sql</span> ที่ดาวน์โหลดไปแล้ว ควรจัดเก็บในที่ปลอดภัยและไม่ควรส่งต่อให้บุคคลที่ไม่เกี่ยวข้อง</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i data-lucide="alert-triangle" class="text-orange-500 shrink-0 mt-0.5" size="16"></i>
                        <span>การสำรองข้อมูลนี้ <strong>ไม่รวมรูปภาพโครงการ (Images)</strong> หากต้องการสำรองรูปภาพ โปรดคัดลอกโฟลเดอร์ <span class="font-mono text-orange-200">/uploads/projects/</span> ในเครื่องเซิร์ฟเวอร์ด้วยตนเอง</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    window.onload = () => { if(typeof lucide !== 'undefined') lucide.createIcons(); };
</script>

<?php include 'includes/footer.php'; ?>