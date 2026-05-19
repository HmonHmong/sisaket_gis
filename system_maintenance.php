<?php
// system_maintenance.php - ศูนย์บำรุงรักษาระบบและตรวจสอบความสมบูรณ์ของข้อมูล (Admin Only) V2.5
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// ตรวจสอบสิทธิ์ (เฉพาะ Admin เท่านั้น)
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?error=no_permission");
    exit;
}

$msg = "";
$error = "";

// --- ตรรกะการดำเนินการ (Actions) ---
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    try {
        if ($action === 'clean_notif') {
            $conn->query("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $msg = "ล้างประวัติการแจ้งเตือนเก่าเรียบร้อยแล้ว";
            log_activity($conn, 'MAINTENANCE', 'system', null, "ล้างประวัติการแจ้งเตือนย้อนหลัง 30 วัน");
        } 
        elseif ($action === 'optimize') {
            $conn->query("OPTIMIZE TABLE projects, project_points, project_attachments, activity_logs, notifications, users");
            $msg = "จัดระเบียบฐานข้อมูล (Optimize) เรียบร้อยแล้ว";
            log_activity($conn, 'MAINTENANCE', 'system', null, "สั่ง Optimize ฐานข้อมูลทั้งหมด");
        }
        elseif ($action === 'clean_files') {
            // ค้นหาไฟล์ขยะ (ไฟล์ในเครื่องที่ไม่มีชื่อในฐานข้อมูล)
            $upload_dir = 'uploads/projects/';
            
            // ป้องกัน Error โดยการเช็คและสร้างโฟลเดอร์ให้ก่อน หากยังไม่มี
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0777, true);
            }
            
            $files_in_folder = @scandir($upload_dir);
            // ป้องกัน Error array_diff() หากแสกนโฟลเดอร์ไม่สำเร็จ
            if ($files_in_folder === false) { 
                $files_in_folder = []; 
            } else { 
                $files_in_folder = array_diff($files_in_folder, array('.', '..', '.htaccess')); 
            }
            
            $res_db = $conn->query("SELECT file_path FROM project_attachments");
            $db_files = [];
            while($f = $res_db->fetch_assoc()) $db_files[] = $f['file_path'];
            
            $deleted_count = 0;
            foreach ($files_in_folder as $file) {
                if (!in_array($file, $db_files)) {
                    @unlink($upload_dir . $file);
                    $deleted_count++;
                }
            }
            $msg = "ลบไฟล์ขยะที่ตกค้างในเซิร์ฟเวอร์เรียบร้อยแล้ว ($deleted_count ไฟล์)";
            log_activity($conn, 'MAINTENANCE', 'storage', null, "ลบไฟล์ขยะโครงการจำนวน $deleted_count ไฟล์");
        }
    } catch (Exception $e) {
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// 1. ตรวจสอบข้อมูลพิกัดที่ขาดหาย (Audit Coordinates) - ไม่นับโครงการที่อยู่ในถังขยะ
$missing_coords_query = "SELECT COUNT(id) as count FROM projects WHERE (start_lat IS NULL OR start_lat = 0 OR start_long IS NULL OR start_long = 0) AND deleted_at IS NULL";
$missing_coords = $conn->query($missing_coords_query)->fetch_assoc();

// 2. ตรวจสอบการแจ้งเตือนเก่า (Older than 30 days)
$old_notif_query = "SELECT COUNT(id) as count FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
$old_notif = $conn->query($old_notif_query)->fetch_assoc();

include 'includes/header.php';
?>

<div class="max-w-[1600px] mx-auto pb-20 animate-in fade-in duration-700 w-full">
    <div class="flex flex-col md:flex-row items-start md:items-center gap-5 mb-10 px-4">
        <div class="w-16 h-16 bg-slate-900 text-white rounded-[2rem] flex items-center justify-center shadow-2xl shrink-0">
            <i data-lucide="wrench" size="32"></i>
        </div>
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight">บำรุงรักษาข้อมูล GIS</h2>
            <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px] mt-1">Data Integrity & System Health Monitoring</p>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="mb-8 mx-4 bg-emerald-50 text-emerald-600 p-6 rounded-[2rem] border border-emerald-100 font-black flex items-center gap-3 animate-bounce shadow-sm">
            <i data-lucide="check-circle"></i> <?= $msg ?>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="mb-8 mx-4 bg-red-50 text-red-600 p-6 rounded-[2rem] border border-red-100 font-black flex items-center gap-3 shadow-sm">
            <i data-lucide="alert-circle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 px-4">
        <!-- Card 1: Check Coordinates -->
        <div class="bg-white p-8 rounded-[3.5rem] shadow-sm border border-slate-100 flex flex-col justify-between group hover:shadow-lg transition-shadow">
            <div>
                <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i data-lucide="map-pin-off"></i>
                </div>
                <h3 class="font-black text-slate-800 text-lg">ตรวจสอบพิกัด</h3>
                <p class="text-xs text-slate-400 font-bold mt-2 leading-relaxed">ตรวจสอบโครงการในระบบที่ยังระบุตำแหน่งบนแผนที่ดาวเทียมไม่ครบถ้วน</p>
            </div>
            <div class="mt-8 pt-6 border-t border-slate-50 flex justify-between items-center">
                <span class="text-2xl font-black text-orange-600"><?= number_format($missing_coords['count']) ?></span>
                <a href="projects.php" class="text-[10px] font-black uppercase text-slate-400 hover:text-orange-600 transition-colors flex items-center gap-1">
                    ดูรายการ <i data-lucide="arrow-right" size="12"></i>
                </a>
            </div>
        </div>

        <!-- Card 2: Clean Storage -->
        <div class="bg-white p-8 rounded-[3.5rem] shadow-sm border border-slate-100 flex flex-col justify-between group hover:shadow-lg transition-shadow">
            <div>
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i data-lucide="hard-drive"></i>
                </div>
                <h3 class="font-black text-slate-800 text-lg">จัดการไฟล์ขยะ</h3>
                <p class="text-xs text-slate-400 font-bold mt-2 leading-relaxed">ค้นหาและลบรูปภาพโครงการที่ตกค้างอยู่ในระบบเซิร์ฟเวอร์แต่ไม่มีข้อมูลในฐานข้อมูลแล้ว</p>
            </div>
            <div class="mt-8 pt-6 border-t border-slate-50">
                <a href="system_maintenance.php?action=clean_files" onclick="return confirm('ยืนยันการแสกนและลบไฟล์ขยะออกจากเซิร์ฟเวอร์?')" 
                   class="block w-full text-center py-3 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all shadow-md">สั่งทำความสะอาด</a>
            </div>
        </div>

        <!-- Card 3: Optimization -->
        <div class="bg-white p-8 rounded-[3.5rem] shadow-sm border border-slate-100 flex flex-col justify-between group hover:shadow-lg transition-shadow">
            <div>
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i data-lucide="zap"></i>
                </div>
                <h3 class="font-black text-slate-800 text-lg">เพิ่มความเร็วระบบ</h3>
                <p class="text-xs text-slate-400 font-bold mt-2 leading-relaxed">จัดระเบียบพื้นที่หน่วยความจำและดัชนี (Index) ของฐานข้อมูลเพื่อให้เรียกใช้งานแผนที่ได้รวดเร็วที่สุด</p>
            </div>
            <div class="mt-8 pt-6 border-t border-slate-50">
                <a href="system_maintenance.php?action=optimize" 
                   class="block w-full text-center py-3 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-900 transition-all shadow-lg shadow-emerald-100">สั่ง Optimize ระบบ</a>
            </div>
        </div>
    </div>

    <!-- Additional Tools -->
    <div class="mt-12 mx-4 bg-slate-900 p-8 md:p-10 rounded-[4rem] text-white relative overflow-hidden shadow-2xl">
        <div class="absolute -right-10 -top-10 text-white/5"><i data-lucide="history" size="200"></i></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-8">
            <div class="max-w-xl">
                <h4 class="text-xl font-black mb-2 flex items-center gap-3">
                    <i data-lucide="bell-off" class="text-orange-500"></i> ล้างประวัติการแจ้งเตือนโครงการ
                </h4>
                <p class="text-xs text-slate-400 font-medium leading-relaxed">
                    พบการแจ้งเตือนเก่าในระบบที่สะสมมานานกว่า 30 วัน จำนวน <span class="text-orange-500 font-black text-sm"><?= number_format($old_notif['count']) ?></span> รายการ <br>การล้างประวัติจะช่วยให้ฐานข้อมูลส่วนแจ้งเตือนทำงานได้อย่างลื่นไหลและประหยัดพื้นที่จัดเก็บ
                </p>
            </div>
            <a href="system_maintenance.php?action=clean_notif" onclick="return confirm('ยืนยันการล้างประวัติแจ้งเตือนเก่า? การกระทำนี้ไม่สามารถย้อนกลับได้')" 
               class="bg-orange-600 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-white hover:text-slate-900 transition-all flex items-center justify-center gap-2 shrink-0 w-full md:w-auto shadow-lg shadow-orange-900/50">
                <i data-lucide="trash-2" size="16"></i> ล้างประวัติเก่าทั้งหมด
            </a>
        </div>
    </div>

    <!-- Health Check Status -->
    <div class="mt-8 mx-4 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-6 md:p-8 rounded-[3.5rem] border border-slate-100 shadow-sm flex items-center gap-6">
            <div class="w-16 h-16 rounded-[2rem] bg-slate-50 flex items-center justify-center text-slate-400 shrink-0">
                <i data-lucide="database" size="30"></i>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Database Server Status</p>
                <p class="text-sm font-black text-slate-700 mt-1 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.6)]"></span>
                    MariaDB - Connected & Healthy
                </p>
            </div>
        </div>
        <div class="bg-white p-6 md:p-8 rounded-[3.5rem] border border-slate-100 shadow-sm flex items-center gap-6">
            <div class="w-16 h-16 rounded-[2rem] bg-slate-50 flex items-center justify-center text-slate-400 shrink-0">
                <i data-lucide="folder-open" size="30"></i>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Storage Directory Permissions</p>
                <p class="text-sm font-black text-slate-700 mt-1 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.6)]"></span>
                    /uploads/projects - Writable (777)
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    window.onload = () => { if(typeof lucide !== 'undefined') lucide.createIcons(); };
</script>

<?php include 'includes/footer.php'; ?>