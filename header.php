<!-- ... existing code ... -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบ GIS สำนักช่าง อบจ.ศรีสะเกษ</title>
    
    <!-- เพิ่ม PWA Tags -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#ea580c">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/854/854878.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
<!-- ... existing code ... -->
            <a href="system_maintenance.php" class="flex items-center gap-4 px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= $current_page == 'system_maintenance.php' ? 'nav-active' : 'text-slate-500 hover:bg-orange-50 hover:text-orange-600' ?>">
                <i data-lucide="wrench" size="18"></i> บำรุงรักษา
            </a>
            
            <!-- เพิ่มเมนูถังขยะ -->
            <a href="trash.php" class="flex items-center gap-4 px-5 py-4 rounded-2xl font-bold text-sm transition-all <?= $current_page == 'trash.php' ? 'nav-active' : 'text-rose-500 hover:bg-rose-50 hover:text-rose-600' ?>">
                <i data-lucide="trash-2" size="18"></i> ถังขยะโครงการ
            </a>
            <?php endif; ?>
        </aside>

        <!-- Main Content Area -->
<!-- ... existing code ... -->