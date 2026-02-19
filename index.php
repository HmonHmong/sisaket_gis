<?php
// index.php - แผงควบคุมสถิติวิศวกรรมและภาพรวมโครงการ (Dashboard) เวอร์ชันสมบูรณ์
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// 1. ดึงสถิติหลัก (Key Performance Indicators) 
// ตรวจสอบว่าดึงทั้ง Area (พื้นที่รวม) และ Distance (ระยะทางรวม)
$stats_query = "SELECT 
    COUNT(id) as total_projects, 
    SUM(budget_amount) as total_budget,
    SUM(area) as total_area,
    SUM(distance) as total_distance
    FROM projects";
$stats = $conn->query($stats_query)->fetch_assoc();

// 2. ข้อมูลสัดส่วนประเภทถนน (รองรับ 5 ประเภทถนนภาษาไทยที่อัปเดตล่าสุด)
$type_query = "SELECT infrastructure_type, COUNT(id) as count, SUM(budget_amount) as budget 
               FROM projects 
               GROUP BY infrastructure_type 
               ORDER BY budget DESC";
$type_res = $conn->query($type_query);
$types_data = [];
while($t = $type_res->fetch_assoc()) $types_data[] = $t;

// 3. รายการโครงการล่าสุด (แสดงสถานะ 'มีการเปลี่ยนแปลงหรือแก้ไข' ได้ถูกต้อง)
$recent_query = "SELECT p.*, u.full_name as creator_name 
                 FROM projects p 
                 LEFT JOIN users u ON p.created_by = u.id 
                 ORDER BY p.created_at DESC LIMIT 5";
$recent_res = $conn->query($recent_query);

include 'includes/header.php';
?>

<div class="max-w-[1600px] mx-auto pb-20 animate-in fade-in duration-700">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6 px-4">
        <div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <div class="w-12 h-12 bg-orange-600 rounded-2xl flex items-center justify-center text-white shadow-lg">
                    <i data-lucide="layout-dashboard"></i>
                </div>
                แผงควบคุมระบบ GIS
            </h1>
            <p class="text-slate-500 font-bold mt-1 uppercase tracking-widest text-xs ml-1">PAO Infrastructure Engineering Analysis</p>
        </div>
        <div class="flex gap-3 w-full lg:w-auto">
            <!-- ฟังก์ชัน Export ข้อมูลที่คงไว้ -->
            <a href="export_projects.php" class="bg-white border-2 border-slate-100 text-slate-700 px-6 py-4 rounded-[2rem] font-black text-sm shadow-sm hover:border-emerald-500 hover:text-emerald-600 transition-all flex items-center gap-2 group">
                <i data-lucide="file-spreadsheet" size="18" class="group-hover:scale-110 transition-transform"></i> ส่งออก Excel
            </a>
            <a href="add_project.php" class="bg-orange-600 text-white px-8 py-4 rounded-[2rem] font-black text-sm shadow-xl shadow-orange-100 hover:bg-slate-900 transition-all flex items-center gap-2 group">
                <i data-lucide="plus-circle" size="18" class="group-hover:rotate-90 transition-transform"></i> เพิ่มโครงการใหม่
            </a>
        </div>
    </div>

    <!-- Engineering KPIs: แสดงผลตัวเลขจากการคำนวณสูตรใหม่ -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12 px-4">
        <div class="bg-white p-8 rounded-[3.5rem] shadow-sm border border-slate-100 relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 text-slate-50 opacity-50 group-hover:scale-110 transition-transform"><i data-lucide="package" size="120"></i></div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">โครงการทั้งหมด</p>
            <h3 class="text-4xl font-black text-slate-900"><?= number_format($stats['total_projects'] ?? 0) ?> <span class="text-xs">แห่ง</span></h3>
        </div>

        <div class="bg-slate-900 p-8 rounded-[3.5rem] shadow-xl relative overflow-hidden text-white">
            <div class="absolute -right-4 -top-4 text-white/5"><i data-lucide="wallet" size="120"></i></div>
            <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest mb-2">งบประมาณรวม</p>
            <h3 class="text-3xl font-black"><?= number_format(($stats['total_budget'] ?? 0) / 1000000, 2) ?> <span class="text-xs">ล้านบาท</span></h3>
        </div>

        <div class="bg-white p-8 rounded-[3.5rem] shadow-sm border border-slate-100 relative overflow-hidden group">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">โครงข่ายถนนรวม</p>
            <h3 class="text-4xl font-black text-slate-900"><?= number_format(($stats['total_distance'] ?? 0) / 1000, 2) ?> <span class="text-sm font-bold text-blue-500 ml-1 uppercase">km</span></h3>
            <div class="w-full bg-slate-100 h-2 rounded-full mt-4 overflow-hidden border border-slate-50">
                <div class="bg-blue-500 h-full w-full animate-in slide-in-from-left duration-1000"></div>
            </div>
        </div>

        <!-- พื้นที่รวมสุทธิ (Net Area) ที่มาจากสูตรคำนวณล่าสุด -->
        <div class="bg-orange-600 p-8 rounded-[3.5rem] shadow-xl shadow-orange-100 text-white relative overflow-hidden">
            <div class="absolute -right-4 -top-4 text-white/10 rotate-12"><i data-lucide="maximize" size="120"></i></div>
            <p class="text-[10px] font-black text-orange-200 uppercase tracking-widest mb-2">พื้นที่ดำเนินการจริง</p>
            <h3 class="text-3xl font-black"><?= number_format($stats['total_area'] ?? 0, 2) ?> <span class="text-xs">ตร.ม.</span></h3>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 px-4">
        <!-- 5 Road Types Distribution -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white p-10 rounded-[4rem] shadow-sm border border-slate-100 h-full">
                <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-8 flex items-center gap-2">
                    <i data-lucide="pie-chart" class="text-orange-600"></i> สัดส่วนประเภทถนน
                </h4>
                <div class="space-y-7">
                    <?php 
                    if(count($types_data) > 0):
                        foreach($types_data as $type): 
                            $percent = $stats['total_budget'] > 0 ? ($type['budget'] / $stats['total_budget']) * 100 : 0;
                    ?>
                    <div class="group">
                        <div class="flex justify-between items-end mb-2.5 px-1">
                            <span class="text-xs font-black text-slate-600 truncate max-w-[200px]"><?= e($type['infrastructure_type'] ?: 'ยังไม่ระบุประเภท') ?></span>
                            <span class="text-[10px] font-black text-orange-600 bg-orange-50 px-2 py-0.5 rounded-lg"><?= number_format($percent, 1) ?>%</span>
                        </div>
                        <div class="w-full bg-slate-50 h-3 rounded-full overflow-hidden border border-slate-100">
                            <div class="bg-slate-900 h-full transition-all duration-1000 group-hover:bg-orange-600 shadow-[0_0_10px_rgba(234,88,12,0.2)]" style="width: <?= $percent ?>%"></div>
                        </div>
                        <div class="flex justify-between mt-1.5 px-1">
                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">Budget Allocation</p>
                            <p class="text-[9px] text-slate-900 font-black tracking-tight"><?= number_format($type['budget']) ?> ฿</p>
                        </div>
                    </div>
                    <?php 
                        endforeach; 
                    else:
                    ?>
                        <p class="text-center py-10 text-slate-300 italic text-xs">ไม่มีข้อมูลประเภทถนน</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity: ตรวจสอบสถานะ 'แก้ไข/เปลี่ยนแปลง' -->
        <div class="lg:col-span-2">
            <div class="bg-white p-10 rounded-[4rem] shadow-sm border border-slate-100 h-full">
                <div class="flex justify-between items-center mb-8">
                    <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                        <i data-lucide="refresh-cw" class="text-blue-500"></i> โครงการที่อัปเดตล่าสุด
                    </h4>
                    <a href="projects.php" class="bg-slate-100 text-[9px] font-black text-slate-500 uppercase px-4 py-2 rounded-full hover:bg-slate-900 hover:text-white transition-all">ดูทะเบียนทั้งหมด</a>
                </div>

                <div class="space-y-4">
                    <?php while($proj = $recent_res->fetch_assoc()): ?>
                    <a href="view_project.php?id=<?= $proj['id'] ?>" class="flex items-center justify-between p-6 rounded-[2.8rem] bg-slate-50 hover:bg-white hover:shadow-2xl hover:shadow-slate-200/50 hover:scale-[1.01] transition-all border border-transparent hover:border-slate-100 group">
                        <div class="flex items-center gap-6 overflow-hidden">
                            <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-slate-400 group-hover:bg-orange-600 group-hover:text-white transition-all shadow-sm shrink-0 border border-slate-100">
                                <i data-lucide="map-pin" size="20"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h5 class="text-sm font-black text-slate-800 truncate leading-tight"><?= e($proj['project_name']) ?></h5>
                                <div class="flex items-center gap-3 mt-1.5">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">อ.<?= e($proj['district_name']) ?></span>
                                    <span class="w-1 h-1 bg-slate-200 rounded-full"></span>
                                    <span class="text-[9px] font-black <?= $proj['status']=='มีการเปลี่ยนแปลงหรือแก้ไข' ? 'text-rose-600' : 'text-orange-600' ?> uppercase tracking-widest"><?= e($proj['status']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs font-black text-slate-900"><?= number_format($proj['budget_amount']) ?> ฿</p>
                            <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest mt-0.5 italic"><?= date('d/m/Y', strtotime($proj['created_at'])) ?></p>
                        </div>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>