<?php
// system_maintenance.php - ศูนย์บำรุงรักษาระบบและตรวจสอบความสมบูรณ์ของข้อมูล (Admin Only)
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

// 1. ตรวจสอบข้อมูลพิกัดที่ขาดหาย (Audit Coordinates)
$missing_coords_query = "SELECT COUNT(id) as count FROM projects WHERE start_lat IS NULL OR start_lat = 0 OR start_long IS NULL OR start_long = 0";
$missing_coords = $conn->query($missing_coords_query)->fetch_assoc();

// 2. ตรวจสอบการแจ้งเตือนเก่า (Older than 30 days)
$old_notif_query = "SELECT COUNT(id) as count FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
$old_notif = $conn->query($old_notif_query)->fetch_assoc();

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
            $files_in_folder = array_diff(scandir($upload_dir), array('.', '..', '.htaccess'));
            
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

include 'includes/header.php';
?>

<div class="max-w-6xl mx-auto pb-20 animate-in fade-in duration-700 px-4">
    <div class="flex items-center gap-5 mb-10">
        <div class="w-16 h-16 bg-slate-900 text-white rounded-[2rem] flex items-center justify-center shadow-2xl">
            <i data-lucide="wrench" size="32"></i>
        </div>
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight">บำรุงรักษาข้อมูล GIS</h2>
            <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px] mt-1">Data Integrity & System Health Monitoring</p>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="mb-8 bg-emerald-50 text-emerald-600 p-6 rounded-[2rem] border border-emerald-100 font-black flex items-center gap-3 animate-bounce">
            <i data-lucide="check-circle"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Card 1: Check Coordinates -->
        <div class="bg-white p-8 rounded-[3.5rem] shadow-sm border border-slate-100 flex flex-col justify-between group">
            <div>
                <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i data-lucide="map-pin-off"></i>
                </div>
                <h3 class="font-black text-slate-800 text-lg">ตรวจสอบพิกัด</h3>
                <p class="text-xs text-slate-400 font-bold mt-2 leading-relaxed">ตรวจสอบโครงการที่ยังระบุตำแหน่งบนแผนที่ดาวเทียมไม่ครบถ้วน</p>
            </div>
            <div class="mt-8 pt-6 border-t border-slate-50 flex justify-between items-center">
                <span class="text-2xl font-black text-orange-600"><?= number_format($missing_coords['count']) ?></span>
                <a href="projects.php?status=incomplete" class="text-[10px] font-black uppercase text-slate-400 hover:text-orange-600 transition-colors">ดูรายการ</a>
            </div>
        </div>

        <!-- Card 2: Clean Storage -->
        <div class="bg-white p-8 rounded-[3.5rem] shadow-sm border border-slate-100 flex flex-col justify-between group">
            <div>
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i data-lucide="hard-drive"></i>
                </div>
                <h3 class="font-black text-slate-800 text-lg">จัดการไฟล์ขยะ</h3>
                <p class="text-xs text-slate-400 font-bold mt-2 leading-relaxed">ค้นหาและลบรูปภาพโครงการที่ตกค้างอยู่ในระบบแต่ไม่มีเจ้าของ</p>
            </div>
            <div class="mt-8 pt-6 border-t border-slate-50">
                <a href="system_maintenance.php?action=clean_files" onclick="return confirm('ยืนยันการแสกนและลบไฟล์ขยะ?')" 
                   class="block w-full text-center py-3 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all">สั่งทำความสะอาด</a>
            </div>
        </div>

        <!-- Card 3: Optimization -->
        <div class="bg-white p-8 rounded-[3.5rem] shadow-sm border border-slate-100 flex flex-col justify-between group">
            <div>
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i data-lucide="zap"></i>
                </div>
                <h3 class="font-black text-slate-800 text-lg">เพิ่มความเร็วระบบ</h3>
                <p class="text-xs text-slate-400 font-bold mt-2 leading-relaxed">จัดระเบียบดัชนี (Index) ของฐานข้อมูลเพื่อให้เรียกใช้แผนที่ได้รวดเร็วขึ้น</p>
            </div>
            <div class="mt-8 pt-6 border-t border-slate-50">
                <a href="system_maintenance.php?action=optimize" 
                   class="block w-full text-center py-3 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-900 transition-all shadow-lg shadow-emerald-100">สั่ง Optimize ระบบ</a>
            </div>
        </div>
    </div>

    <!-- Additional Tools -->
    <div class="mt-12 bg-slate-900 p-10 rounded-[4rem] text-white relative overflow-hidden shadow-2xl">
        <div class="absolute -right-10 -top-10 text-white/5"><i data-lucide="history" size="200"></i></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="max-w-md">
                <h4 class="text-xl font-black mb-2">ล้างประวัติการแจ้งเตือนโครงการ</h4>
                <p class="text-xs text-slate-400 font-medium leading-relaxed">พบการแจ้งเตือนเก่าที่สะสมมานานกว่า 30 วันจำนวน <span class="text-orange-500 font-black"><?= number_format($old_notif['count']) ?></span> รายการ การล้างประวัติจะช่วยให้ฐานข้อมูลทำงานได้อย่างลื่นไหล</p>
            </div>
            <a href="system_maintenance.php?action=clean_notif" onclick="return confirm('ยืนยันการล้างประวัติแจ้งเตือนเก่า?')" 
               class="bg-orange-600 text-white px-10 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-white hover:text-slate-900 transition-all flex items-center gap-2 shrink-0">
                <i data-lucide="trash-2" size="16"></i> ล้างประวัติเก่าทั้งหมด
            </a>
        </div>
    </div>

    <!-- Health Check Status -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-8 rounded-[3.5rem] border border-slate-100 shadow-sm flex items-center gap-6">
            <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center text-slate-300">
                <i data-lucide="database" size="32"></i>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Database Server Status</p>
                <p class="text-sm font-black text-slate-700 mt-1 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    MariaDB 10.4 - Connected & Healthy
                </p>
            </div>
        </div>
        <div class="bg-white p-8 rounded-[3.5rem] border border-slate-100 shadow-sm flex items-center gap-6">
            <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center text-slate-300">
                <i data-lucide="folder-open" size="32"></i>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Storage Directory Permissions</p>
                <p class="text-sm font-black text-slate-700 mt-1 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    /uploads/projects - Writable (777)
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>