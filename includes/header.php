<?php
// includes/header.php (เวอร์ชันอัปเดตระบบแจ้งเตือน)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลแจ้งเตือนสำหรับ Dropdown
$unread_count = get_unread_count($conn, $user_id);
$noti_sql = "SELECT * FROM notifications 
             WHERE (user_id = ? OR user_id IS NULL) 
             ORDER BY created_at DESC LIMIT 5";
$noti_stmt = $conn->prepare($noti_sql);
$noti_stmt->bind_param("i", $user_id);
$noti_stmt->execute();
$noti_preview = $noti_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบ GIS สำนักช่าง อบจ.ศรีสะเกษ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
        .nav-active { background: #ea580c; color: white; box-shadow: 0 10px 15px -3px rgba(234, 88, 12, 0.3); }
        .noti-dropdown { display: none; }
        .noti-dropdown.active { display: block; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
    <header class="bg-orange-600 text-white shadow-lg p-4 sticky top-0 z-[1000]">
        <div class="flex justify-between items-center max-w-7xl mx-auto">
            <div class="flex items-center gap-3">
                <div class="bg-white p-2 rounded-lg text-orange-600 shadow-inner">
                    <i data-lucide="construction"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold leading-none">สำนักช่าง อบจ.ศรีสะเกษ</h1>
                    <p class="text-[10px] text-orange-100 mt-1 uppercase tracking-widest font-black">GIS Management</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2 md:gap-4">
                <!-- ปุ่มแจ้งเตือน -->
                <div class="relative">
                    <button onclick="toggleNoti()" class="p-2 hover:bg-orange-700 rounded-xl transition-all relative">
                        <i data-lucide="bell" size="22"></i>
                        <?php if($unread_count > 0): ?>
                        <span class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold flex items-center justify-center rounded-full border-2 border-orange-600">
                            <?= $unread_count > 9 ? '9+' : $unread_count ?>
                        </span>
                        <?php endif; ?>
                    </button>

                    <!-- Dropdown แจ้งเตือนย่อ -->
                    <div id="notiDropdown" class="noti-dropdown absolute right-0 mt-3 w-80 bg-white rounded-[2rem] shadow-2xl border border-slate-100 overflow-hidden text-slate-800 animate-in fade-in zoom-in duration-200">
                        <div class="p-5 border-b border-slate-50 flex justify-between items-center">
                            <h3 class="font-black text-sm uppercase">แจ้งเตือนล่าสุด</h3>
                            <a href="notifications.php" class="text-[10px] font-bold text-orange-600 hover:underline">ดูทั้งหมด</a>
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            <?php if($noti_preview->num_rows > 0): ?>
                                <?php while($n = $noti_preview->fetch_assoc()): ?>
                                <a href="notifications.php" class="block p-4 hover:bg-slate-50 border-b border-slate-50 <?= $n['is_read'] ? 'opacity-50' : 'bg-orange-50/30' ?>">
                                    <p class="font-bold text-xs mb-1"><?= htmlspecialchars($n['title']) ?></p>
                                    <p class="text-[10px] text-slate-500 line-clamp-1"><?= htmlspecialchars($n['message']) ?></p>
                                    <p class="text-[8px] text-slate-400 mt-1 font-bold"><?= date('H:i', strtotime($n['created_at'])) ?></p>
                                </a>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="p-8 text-center text-slate-400 text-xs italic">ไม่มีการแจ้งเตือนใหม่</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <a href="profile.php" class="hidden md:flex flex-col items-end leading-none border-r border-orange-500 pr-4 hover:opacity-80 transition-opacity">
                    <span class="text-sm font-bold"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                    <span class="text-[9px] uppercase font-black opacity-70 mt-1"><?= $_SESSION['role'] ?></span>
                </a>
                <a href="logout.php" class="bg-orange-700 hover:bg-red-600 p-2.5 rounded-xl transition-all">
                    <i data-lucide="log-out" size="18"></i>
                </a>
            </div>
        </div>
    </header>

    <div class="flex flex-1 max-w-7xl mx-auto w-full min-h-screen">
        <aside class="hidden lg:flex flex-col w-72 bg-white border-r p-6 space-y-2 shrink-0 h-[calc(100vh-72px)] sticky top-[72px]">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 mb-2">เมนูหลัก</p>
            <a href="index.php" class="flex items-center gap-4 px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= $current_page == 'index.php' ? 'nav-active' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600' ?>">
                <i data-lucide="layout-dashboard" size="20"></i> แผงควบคุม
            </a>
            <a href="projects.php" class="flex items-center gap-4 px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= $current_page == 'projects.php' ? 'nav-active' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600' ?>">
                <i data-lucide="table" size="20"></i> ทะเบียนโครงการ
            </a>
            <a href="notifications.php" class="flex items-center gap-4 px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= $current_page == 'notifications.php' ? 'nav-active' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600' ?>">
                <i data-lucide="bell-ring" size="20"></i> แจ้งเตือนระบบ
                <?php if($unread_count > 0): ?>
                <span class="ml-auto bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-lg"><?= $unread_count ?></span>
                <?php endif; ?>
            </a>
        </aside>
        <main class="flex-1 p-4 lg:p-10 overflow-x-hidden">
    
    <script>
        function toggleNoti() {
            document.getElementById('notiDropdown').classList.toggle('active');
        }
        // ปิด dropdown เมื่อคลิกที่อื่น
        window.onclick = function(event) {
            if (!event.target.closest('.relative')) {
                document.getElementById('notiDropdown').classList.remove('active');
            }
        }
    </script>