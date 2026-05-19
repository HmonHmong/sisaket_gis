<?php
// index.php - แผงควบคุม (Dashboard) V2.5 - Full Width Layout
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// 1. ดึงสถิติหลัก (ไม่รวมโครงการที่ถูกลบลงถังขยะ)
$stats_query = "SELECT 
    COUNT(id) as total_projects, 
    SUM(budget_amount) as total_budget,
    SUM(area) as total_area,
    SUM(distance) as total_distance
    FROM projects WHERE deleted_at IS NULL";
$stats = $conn->query($stats_query)->fetch_assoc();

// 2. ข้อมูลสัดส่วนประเภทถนน (สำหรับทำ Progress bar)
$type_query = "SELECT infrastructure_type, COUNT(id) as count, SUM(budget_amount) as budget 
               FROM projects WHERE deleted_at IS NULL
               GROUP BY infrastructure_type 
               ORDER BY budget DESC";
$type_res = $conn->query($type_query);
$types_data = [];
$total_budget_for_percent = $stats['total_budget'] > 0 ? $stats['total_budget'] : 1; // กัน Error หารด้วย 0

while($t = $type_res->fetch_assoc()) {
    $t['percent'] = ($t['budget'] / $total_budget_for_percent) * 100;
    $types_data[] = $t;
}

// 3. รายการโครงการล่าสุด 5 รายการ
$recent_query = "SELECT p.*, u.full_name as creator_name 
                 FROM projects p 
                 LEFT JOIN users u ON p.created_by = u.id 
                 WHERE p.deleted_at IS NULL
                 ORDER BY p.created_at DESC LIMIT 5";
$recent_res = $conn->query($recent_query);

include 'includes/header.php';
?>

<div class="max-w-[1600px] mx-auto pb-20 animate-in fade-in duration-500">
    
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-orange-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-orange-500/30 shrink-0">
                <i data-lucide="layout-dashboard" size="28"></i>
            </div>
            <div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight">แผงควบคุมระบบ GIS</h2>
                <p class="text-slate-500 font-bold mt-1 uppercase tracking-widest text-[10px]">PAO Infrastructure Engineering Analysis</p>
            </div>
        </div>
        <div class="flex gap-3 w-full md:w-auto">
            <a href="export_projects.php" class="flex-1 md:flex-none bg-white text-slate-700 px-6 py-3 rounded-xl font-bold text-xs hover:bg-slate-50 border border-slate-200 transition-all flex items-center justify-center gap-2 shadow-sm">
                <i data-lucide="file-spreadsheet" size="16"></i> ส่งออก Excel
            </a>
            <a href="add_project.php" class="flex-1 md:flex-none bg-orange-600 text-white px-6 py-3 rounded-xl font-black text-xs hover:bg-slate-900 transition-all flex items-center justify-center gap-2 shadow-lg shadow-orange-600/20">
                <i data-lucide="plus-circle" size="16"></i> เพิ่มโครงการใหม่
            </a>
        </div>
    </div>

    <!-- Stats Cards (Grid แบบ 4 คอลัมน์ ขยายเต็มจอ) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card 1 -->
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-col justify-center relative overflow-hidden group hover:shadow-md transition-all">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 z-10">โครงการทั้งหมด</p>
            <div class="flex items-baseline gap-2 z-10">
                <h3 class="text-5xl font-black text-slate-800"><?= number_format($stats['total_projects']) ?></h3>
                <span class="text-sm font-bold text-slate-500">แห่ง</span>
            </div>
        </div>
        
        <!-- Card 2 -->
        <div class="bg-slate-900 p-8 rounded-[2.5rem] shadow-xl shadow-slate-900/20 border border-slate-800 flex flex-col justify-center relative overflow-hidden group hover:bg-slate-800 transition-all">
            <div class="absolute -right-6 -top-6 text-white/5 transform group-hover:rotate-12 transition-transform duration-500"><i data-lucide="coins" size="140"></i></div>
            <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest mb-2 z-10">งบประมาณรวม</p>
            <div class="flex items-baseline gap-2 z-10">
                <h3 class="text-5xl font-black text-white"><?= number_format($stats['total_budget'] / 1000000, 2) ?></h3>
                <span class="text-sm font-bold text-slate-400">ล้านบาท</span>
            </div>
        </div>
        
        <!-- Card 3 -->
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-col justify-center relative overflow-hidden group hover:shadow-md transition-all">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 z-10">โครงข่ายถนนรวม</p>
            <div class="flex items-baseline gap-2 z-10">
                <h3 class="text-5xl font-black text-slate-800"><?= number_format($stats['total_distance'] / 1000, 2) ?></h3>
                <span class="text-sm font-bold text-blue-500">KM</span>
            </div>
            <div class="w-1/2 h-2 bg-blue-500 rounded-full mt-4 opacity-50 group-hover:w-3/4 transition-all duration-700"></div>
        </div>
        
        <!-- Card 4 -->
        <div class="bg-orange-600 p-8 rounded-[2.5rem] shadow-lg shadow-orange-600/20 border border-orange-500 flex flex-col justify-center relative overflow-hidden group hover:bg-orange-500 transition-all">
            <div class="absolute -right-6 -bottom-6 text-black/10 transform group-hover:scale-110 transition-transform duration-500"><i data-lucide="map" size="140"></i></div>
            <p class="text-[10px] font-black text-orange-100 uppercase tracking-widest mb-2 z-10">พื้นที่ดำเนินการจริง</p>
            <div class="flex items-baseline gap-2 z-10">
                <h3 class="text-5xl font-black text-white"><?= number_format($stats['total_area'], 2) ?></h3>
                <span class="text-sm font-bold text-orange-200">ตร.ม.</span>
            </div>
        </div>
    </div>

    <!-- Content Area (Charts & Recent Projects) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- สัดส่วนประเภทถนน -->
        <div class="bg-white p-8 lg:p-10 rounded-[3.5rem] shadow-sm border border-slate-100">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-8 flex items-center gap-3 border-l-4 border-orange-500 pl-3">
                <i data-lucide="pie-chart" class="text-orange-500"></i> สัดส่วนประเภทถนน
            </h3>
            <div class="space-y-6">
                <?php foreach($types_data as $idx => $t): 
                    $colors = ['orange', 'blue', 'emerald', 'purple', 'slate', 'rose'];
                    $c = $colors[$idx % count($colors)];
                ?>
                <div class="group">
                    <div class="flex justify-between items-end mb-2">
                        <p class="text-xs font-black text-slate-700 group-hover:text-<?= $c ?>-600 transition-colors"><?= htmlspecialchars($t['infrastructure_type']) ?></p>
                        <p class="text-[10px] font-black text-<?= $c ?>-500"><?= number_format($t['percent'], 1) ?>%</p>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-<?= $c ?>-500 h-2.5 rounded-full transform origin-left transition-transform duration-1000" style="width: <?= $t['percent'] ?>%"></div>
                    </div>
                    <p class="text-[9px] font-bold text-slate-400 mt-1.5 uppercase tracking-widest">งบประมาณ: <?= number_format($t['budget']) ?> ฿</p>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($types_data)): ?>
                    <div class="py-10 text-center text-slate-300">
                        <i data-lucide="bar-chart-3" size="48" class="mx-auto mb-3 opacity-50"></i>
                        <p class="text-xs font-bold uppercase tracking-widest">ยังไม่มีข้อมูลสำหรับการประเมินสัดส่วน</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- โครงการล่าสุด -->
        <div class="lg:col-span-2 bg-white p-8 lg:p-10 rounded-[3.5rem] shadow-sm border border-slate-100 flex flex-col">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-3 border-l-4 border-blue-500 pl-3">
                    <i data-lucide="clock" class="text-blue-500"></i> โครงการที่อัปเดตล่าสุด
                </h3>
                <a href="projects.php" class="text-[9px] font-black uppercase bg-slate-100 text-slate-500 px-4 py-2 rounded-xl hover:bg-slate-900 hover:text-white transition-colors tracking-widest">
                    ดูทะเบียนโครงการทั้งหมด
                </a>
            </div>
            
            <div class="flex-1 space-y-4">
                <?php if ($recent_res->num_rows > 0): ?>
                    <?php while($row = $recent_res->fetch_assoc()): ?>
                    <a href="view_project.php?id=<?= $row['id'] ?>" class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 p-5 rounded-[2rem] border border-slate-100 bg-slate-50/50 hover:bg-white hover:border-orange-300 hover:shadow-md transition-all group">
                        <div class="w-14 h-14 rounded-2xl bg-white border border-slate-100 flex items-center justify-center text-slate-400 group-hover:text-orange-500 group-hover:bg-orange-50 group-hover:border-orange-100 transition-colors shrink-0 shadow-sm">
                            <i data-lucide="map-pin"></i>
                        </div>
                        <div class="flex-1 min-w-0 w-full">
                            <h4 class="text-sm font-black text-slate-800 truncate group-hover:text-orange-600 transition-colors"><?= htmlspecialchars($row['project_name']) ?></h4>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span class="text-[10px] font-bold text-slate-500 bg-slate-200/50 px-2 py-0.5 rounded-lg">อ.<?= htmlspecialchars($row['district_name']) ?></span>
                                <?php 
                                    $st_color = 'slate';
                                    if($row['status'] == 'เสร็จสิ้น') $st_color = 'emerald';
                                    if($row['status'] == 'กำลังดำเนินการ') $st_color = 'orange';
                                    if($row['status'] == 'มีการเปลี่ยนแปลงหรือแก้ไข') $st_color = 'rose';
                                ?>
                                <span class="text-[9px] font-black uppercase text-<?= $st_color ?>-600 tracking-widest border border-<?= $st_color ?>-200 bg-<?= $st_color ?>-50 px-2 py-0.5 rounded-lg"><?= htmlspecialchars($row['status']) ?></span>
                            </div>
                        </div>
                        <div class="text-left sm:text-right shrink-0 w-full sm:w-auto mt-2 sm:mt-0 pt-3 sm:pt-0 border-t sm:border-none border-slate-200">
                            <p class="text-sm font-black text-slate-800"><?= number_format($row['budget_amount']) ?> ฿</p>
                            <p class="text-[10px] font-bold text-slate-400 mt-0.5 uppercase tracking-widest"><?= date('d/m/Y', strtotime($row['created_at'])) ?></p>
                        </div>
                    </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="h-full flex flex-col items-center justify-center text-slate-300 py-16 bg-slate-50 rounded-[2rem] border border-dashed border-slate-200">
                        <i data-lucide="folder-open" size="64" class="mb-4 opacity-50"></i>
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400">ยังไม่มีโครงการในระบบ</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
    window.onload = () => { if(typeof lucide !== 'undefined') lucide.createIcons(); };
</script>

<?php 
// หากคุณมีไฟล์ includes/footer.php อยู่แล้ว ให้ปิดคอมเมนต์บรรทัดล่างนี้เพื่อใช้งาน
// include 'includes/footer.php'; 

// แต่ถ้ายังไม่มีไฟล์ footer.php ให้ระบบสร้างแท็กปิดอัตโนมัติดังนี้:
?>
    </main>
</body>
</html>