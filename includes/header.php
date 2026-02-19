<?php
// includes/header.php - ส่วนโครงสร้างหน้าจอและเมนูนำทาง (ตรวจสอบและอัปเดตเมนูบำรุงรักษาให้ครบถ้วน)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลแจ้งเตือนที่ยังไม่ได้อ่าน
$unread_count = get_unread_count($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบ GIS สำนักช่าง อบจ.ศรีสะเกษ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
        .nav-active { 
            background: #ea580c; 
            color: white !important; 
            box-shadow: 0 10px 15px -3px rgba(234, 88, 12, 0.3); 
        }
        /* Custom Scrollbar for Sidebar */
        aside::-webkit-scrollbar { width: 4px; }
        aside::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
    <!-- Top Navbar -->
    <header class="bg-slate-900 text-white shadow-lg p-4 sticky top-0 z-[1000] border-b border-white/5">
        <div class="flex justify-between items-center max-w-[1600px] mx-auto">
            <div class="flex items-center gap-4">
                <div class="bg-orange-600 p-2 rounded-xl text-white shadow-lg shadow-orange-900/20">
                    <i data-lucide="construction" size="24"></i>
                </div>
                <div>
                    <h1 class="text-sm md:text-lg font-black leading-none uppercase tracking-tight">SISAKET PAO <span class="text-orange-500">GIS</span></h1>
                    <p class="text-[9px] text-slate-400 mt-1 uppercase tracking-[0.2em] font-black opacity-70">Infrastructure Management v2.5</p>
                </div>
            </div>
            
            <div class="flex items-center gap-6">
                <div class="hidden md:flex flex-col text-right pr-4 border-r border-white/10">
                    <p class="text-xs font-black text-white"><?= e($_SESSION['full_name']) ?></p>
                    <p class="text-[9px] text-orange-500 font-black uppercase tracking-widest"><?= strtoupper($_SESSION['role']) ?></p>
                </div>
                <div class="flex items-center gap-4">
                    <a href="notifications.php" class="relative group p-2 bg-white/5 hover:bg-orange-600 rounded-xl transition-all" title="การแจ้งเตือน">
                        <i data-lucide="bell" size="20" class="text-slate-300 group-hover:text-white"></i>
                        <?php if($unread_count > 0): ?>
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-orange-500 text-white text-[9px] font-black flex items-center justify-center rounded-full border-2 border-slate-900"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="profile.php" class="p-2 bg-white/5 hover:bg-white/10 rounded-xl transition-all text-slate-300 hover:text-white" title="ข้อมูลส่วนตัว"><i data-lucide="user-circle" size="20"></i></a>
                    <a href="logout.php" class="p-2 text-slate-500 hover:text-red-500 transition-colors" title="ออกจากระบบ" onclick="return confirm('ยืนยันการออกจากระบบ?')"><i data-lucide="log-out" size="20"></i></a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex max-w-[1600px] mx-auto w-full min-h-[calc(100vh-80px)]">
        <!-- Sidebar Desktop -->
        <aside class="hidden lg:flex flex-col w-72 bg-white border-r border-slate-100 p-6 space-y-2 shrink-0 h-[calc(100vh-80px)] sticky top-[80px] overflow-y-auto">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4 mb-4 mt-2">เมนูหลัก</p>
            <a href="index.php" class="flex items-center gap-4 px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= $current_page == 'index.php' ? 'nav-active' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600' ?>">
                <i data-lucide="layout-dashboard" size="18"></i> แผงควบคุม
            </a>
            <a href="projects.php" class="flex items-center gap-4 px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= ($current_page == 'projects.php' || $current_page == 'view_project.php' || $current_page == 'edit_project.php' || $current_page == 'add_project.php') ? 'nav-active' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600' ?>">
                <i data-lucide="folder-kanban" size="18"></i> ทะเบียนโครงการ
            </a>
            <a href="map_full.php" class="flex items-center gap-4 px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= $current_page == 'map_full.php' ? 'nav-active' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600' ?>">
                <i data-lucide="map-pinned" size="18"></i> แผนที่สารสนเทศ
            </a>
            <a href="reports.php" class="flex items-center gap-4 px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= $current_page == 'reports.php' ? 'nav-active' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600' ?>">
                <i data-lucide="bar-chart-big" size="18"></i> รายงานสถิติ
            </a>

            <?php if($_SESSION['role'] === 'admin'): ?>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4 mb-4 mt-8">การจัดการระบบ</p>
            <a href="users.php" class="flex items-center gap-4 px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= ($current_page == 'users.php' || $current_page == 'edit_user.php' || $current_page == 'add_user.php') ? 'nav-active' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600' ?>">
                <i data-lucide="users-2" size="18"></i> จัดการเจ้าหน้าที่
            </a>
            <a href="activity_logs.php" class="flex items-center gap-4 px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= $current_page == 'activity_logs.php' ? 'nav-active' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600' ?>">
                <i data-lucide="scroll-text" size="18"></i> บันทึกกิจกรรม
            </a>
            <a href="backup.php" class="flex items-center gap-4 px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= $current_page == 'backup.php' ? 'nav-active' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600' ?>">
                <i data-lucide="database" size="18"></i> สำรองข้อมูล
            </a>
            <!-- เมนูบำรุงรักษาระบบ (เพิ่มกลับคืนมา) -->
            <a href="system_maintenance.php" class="flex items-center gap-4 px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= $current_page == 'system_maintenance.php' ? 'nav-active' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600' ?>">
                <i data-lucide="wrench" size="18"></i> บำรุงรักษา
            </a>
            <?php endif; ?>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 p-4 lg:p-10 overflow-x-hidden">