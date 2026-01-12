<?php
// reports.php
// ที่อยู่ไฟล์: /reports.php
require_once 'auth_check.php';
require_once 'config/db.php';

// 1. ดึงงบประมาณและจำนวนโครงการแยกตามอำเภอ
$district_sql = "SELECT 
                    pp.district, 
                    COUNT(DISTINCT p.id) as project_count, 
                    SUM(p.budget_amount) as total_budget,
                    SUM(p.area) as total_area
                 FROM project_points pp
                 JOIN projects p ON pp.project_id = p.id
                 GROUP BY pp.district
                 ORDER BY total_budget DESC";
$district_res = $conn->query($district_sql);

// 2. ดึงสถิติแยกตามปีงบประมาณ
$year_sql = "SELECT 
                fiscal_year, 
                COUNT(id) as count, 
                SUM(budget_amount) as budget 
             FROM projects 
             GROUP BY fiscal_year 
             ORDER BY fiscal_year DESC";
$year_res = $conn->query($year_sql);

include 'includes/header.php';
?>

<div class="space-y-8 animate-in fade-in duration-700 pb-20">
    <!-- ส่วนหัวหน้าจอ -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-800">รายงานสรุปเชิงบริหาร</h2>
            <p class="text-slate-500 font-medium">สถิติงบประมาณและผลการดำเนินงานสำนักช่าง อบจ.ศรีสะเกษ</p>
        </div>
        <button onclick="window.print()" class="bg-slate-900 text-white px-6 py-3 rounded-2xl font-bold flex items-center gap-2 hover:bg-slate-800 transition-all shadow-lg">
            <i data-lucide="printer" size="18"></i> พิมพ์รายงาน PDF
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- ฝั่งซ้าย: รายงานรายอำเภอ (Progress Bar Style) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-8 rounded-[3.5rem] shadow-sm border border-slate-100">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center">
                        <i data-lucide="map-pin" size="24"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-800">งบประมาณดำเนินการรายอำเภอ</h3>
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Budget Distribution by District</p>
                    </div>
                </div>

                <div class="space-y-8">
                    <?php 
                    $max_budget = 0;
                    $districts = [];
                    while($row = $district_res->fetch_assoc()) {
                        $districts[] = $row;
                        if($row['total_budget'] > $max_budget) $max_budget = $row['total_budget'];
                    }

                    foreach($districts as $item): 
                        $percent = ($max_budget > 0) ? ($item['total_budget'] / $max_budget) * 100 : 0;
                    ?>
                    <div class="group">
                        <div class="flex justify-between items-end mb-3">
                            <div>
                                <span class="text-lg font-black text-slate-800">อำเภอ<?= htmlspecialchars($item['district']) ?></span>
                                <span class="text-xs font-bold text-slate-400 ml-2 uppercase tracking-tight"><?= $item['project_count'] ?> โครงการ</span>
                            </div>
                            <div class="text-right">
                                <span class="text-xl font-black text-orange-600"><?= number_format($item['total_budget']) ?></span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase ml-1">บาท</span>
                            </div>
                        </div>
                        <!-- Custom Progress Bar -->
                        <div class="w-full bg-slate-100 rounded-full h-4 overflow-hidden shadow-inner p-1">
                            <div class="bg-gradient-to-r from-orange-400 to-orange-600 h-full rounded-full transition-all duration-1000 group-hover:from-blue-500 group-hover:to-blue-600" 
                                 style="width: <?= $percent ?>%"></div>
                        </div>
                        <div class="flex justify-between mt-2 px-1">
                            <span class="text-[10px] text-slate-400 font-bold uppercase">พื้นที่รวม: <?= number_format($item['total_area'], 2) ?> ตร.ม.</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ฝั่งขวา: สรุปปีงบประมาณและข้อมูลรวม -->
        <div class="lg:col-span-1 space-y-6">
            <!-- ปีงบประมาณ Card -->
            <div class="bg-slate-900 p-8 rounded-[3rem] text-white shadow-2xl relative overflow-hidden">
                <div class="absolute right-0 top-0 p-8 opacity-10">
                    <i data-lucide="calendar" size="80"></i>
                </div>
                <h3 class="text-xs font-black text-orange-500 uppercase tracking-[0.2em] mb-6 relative z-10">สรุปรายปีงบประมาณ</h3>
                <div class="space-y-6 relative z-10">
                    <?php while($y = $year_res->fetch_assoc()): ?>
                    <div class="flex justify-between items-center border-b border-white/10 pb-4">
                        <div>
                            <p class="text-lg font-black">ปี <?= $y['fiscal_year'] ?></p>
                            <p class="text-[10px] text-white/50 font-bold uppercase"><?= $y['count'] ?> โครงการ</p>
                        </div>
                        <p class="text-xl font-black text-orange-400"><?= number_format($y['budget'] / 1000000, 2) ?> <span class="text-[10px] font-normal opacity-50">ล้าน</span></p>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- กล่องคำแนะนำ/หมายเหตุ -->
            <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center">
                        <i data-lucide="info" size="20"></i>
                    </div>
                    <h4 class="font-black text-slate-800 uppercase text-xs tracking-widest">หมายเหตุระบบ</h4>
                </div>
                <p class="text-sm text-slate-500 leading-relaxed font-medium">
                    ข้อมูลนี้ประมวลผลจากฐานข้อมูล GIS ล่าสุด 
                    ใช้สำหรับติดตามสถานะงบประมาณและการกระจายตัวของโครงสร้างพื้นฐานในเขตพื้นที่รับผิดชอบของ อบจ.ศรีสะเกษ
                </p>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    header, aside, footer, button { display: none !important; }
    main { padding: 0 !important; width: 100% !important; }
    .bg-white { border: none !important; shadow: none !important; }
    .bg-slate-900 { background: #1e293b !important; -webkit-print-color-adjust: exact; }
}
</style>

<?php include 'includes/footer.php'; ?>