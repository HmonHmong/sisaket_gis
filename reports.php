<?php
// reports.php - ระบบวิเคราะห์ข้อมูลเชิงบริหารและรายงานสรุปงบประมาณพร้อมกราฟ
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// 1. ดึงข้อมูลสรุปตามประเภทโครงสร้าง (สำหรับกราฟวงกลม)
$type_sql = "SELECT infrastructure_type, COUNT(id) as count, SUM(budget_amount) as total_budget 
             FROM projects GROUP BY infrastructure_type";
$type_res = $conn->query($type_sql);
$type_labels = []; $type_budgets = [];
while($r = $type_res->fetch_assoc()) {
    $type_labels[] = $r['infrastructure_type'] ?: 'ไม่ระบุ';
    $type_budgets[] = (float)$r['total_budget'];
}

// 2. ดึงข้อมูลสรุปตามอำเภอ (สำหรับกราฟแท่งและตาราง)
$district_sql = "SELECT 
    district_name, 
    COUNT(id) as proj_count, 
    SUM(budget_amount) as total_budget,
    SUM(area) as total_area,
    (SUM(budget_amount) / NULLIF(SUM(area), 0)) as cost_per_unit
    FROM projects 
    GROUP BY district_name 
    ORDER BY total_budget DESC";
$district_res = $conn->query($district_sql);
$dist_labels = []; $dist_budgets = [];
$table_data = [];
while($r = $district_res->fetch_assoc()) {
    $dist_labels[] = $r['district_name'];
    $dist_budgets[] = (float)$r['total_budget'];
    $table_data[] = $r;
}

include 'includes/header.php';
?>

<!-- เพิ่ม Chart.js สำหรับการแสดงผลกราฟ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="max-w-[1600px] mx-auto pb-20 animate-in fade-in duration-700 px-4">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-10 no-print">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <i data-lucide="bar-chart-3" class="text-orange-600"></i> รายงานวิเคราะห์เชิงบริหาร
            </h2>
            <p class="text-slate-500 font-bold mt-1 uppercase tracking-widest text-xs ml-1">Infrastructure Budget & Spatial Analysis Report</p>
        </div>
        <div class="flex gap-3 w-full lg:w-auto">
            <a href="export_projects.php" class="bg-emerald-50 text-emerald-700 border-2 border-emerald-100 px-6 py-3 rounded-2xl font-black text-xs hover:bg-emerald-600 hover:text-white transition-all flex items-center gap-2">
                <i data-lucide="file-spreadsheet"></i> ส่งออก EXCEL
            </a>
            <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-3 rounded-2xl font-black text-xs hover:bg-orange-600 transition-all flex items-center gap-2 shadow-xl">
                <i data-lucide="printer"></i> พิมพ์รายงานสรุป
            </button>
        </div>
    </div>

    <!-- แถบสรุปภาพรวม (Summary Cards) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-white p-8 rounded-[3.5rem] shadow-sm border border-slate-100 border-b-4 border-b-orange-500">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">งบประมาณดำเนินการรวม</p>
            <h4 class="text-3xl font-black text-slate-900">฿ <?= number_format(array_sum($dist_budgets), 2) ?></h4>
        </div>
        <div class="bg-white p-8 rounded-[3.5rem] shadow-sm border border-slate-100 border-b-4 border-b-blue-500">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">พื้นที่ก่อสร้างสะสม</p>
            <h4 class="text-3xl font-black text-slate-900"><?= number_format(array_sum(array_column($table_data, 'total_area')), 2) ?> <span class="text-xs uppercase">ตร.ม.</span></h4>
        </div>
        <div class="bg-white p-8 rounded-[3.5rem] shadow-sm border border-slate-100 border-b-4 border-b-emerald-500">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">ค่าเฉลี่ยงบประมาณต่อพื้นที่</p>
            <?php 
                $avg_cost = array_sum($dist_budgets) / (array_sum(array_column($table_data, 'total_area')) ?: 1);
            ?>
            <h4 class="text-3xl font-black text-slate-900"><?= number_format($avg_cost, 2) ?> <span class="text-xs uppercase">฿/ตร.ม.</span></h4>
        </div>
    </div>

    <!-- ส่วนของกราฟ (Visual Analytics) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
        <!-- กราฟสัดส่วนงบประมาณตามประเภท -->
        <div class="bg-white p-10 rounded-[4rem] shadow-sm border border-slate-100">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-8">สัดส่วนงบประมาณแยกตามประเภทถนน</h3>
            <div class="h-[300px] flex items-center justify-center">
                <canvas id="typeChart"></canvas>
            </div>
        </div>

        <!-- กราฟแท่งเปรียบเทียบอำเภอ -->
        <div class="bg-white p-10 rounded-[4rem] shadow-sm border border-slate-100">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-8">งบประมาณสะสมรายอำเภอ (Top 10)</h3>
            <div class="h-[300px]">
                <canvas id="districtChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ตารางวิเคราะห์ข้อมูลเชิงลึก (Detailed Analysis Table) -->
    <div class="bg-white rounded-[4rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">ตารางวิเคราะห์ความคุ้มค่าเชิงวิศวกรรมรายอำเภอ</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100">
                        <th class="py-6 px-10">อำเภอ</th>
                        <th class="py-6 px-4 text-center">จำนวนโครงการ</th>
                        <th class="py-6 px-4 text-right">งบประมาณรวม (บาท)</th>
                        <th class="py-6 px-4 text-right">พื้นที่รวม (ตร.ม.)</th>
                        <th class="py-6 px-10 text-right">ต้นทุนเฉลี่ย (บาท/ตร.ม.)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach($table_data as $row): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <td class="py-6 px-10 font-black text-slate-700 group-hover:text-orange-600 transition-colors">อ.<?= e($row['district_name']) ?></td>
                        <td class="py-6 px-4 text-center font-bold text-slate-500"><?= number_format($row['proj_count']) ?></td>
                        <td class="py-6 px-4 text-right font-black text-slate-900"><?= number_format($row['total_budget'], 2) ?></td>
                        <td class="py-6 px-4 text-right font-bold text-blue-600"><?= number_format($row['total_area'], 2) ?></td>
                        <td class="py-6 px-10 text-right font-black text-orange-600">
                            <span class="bg-orange-50 px-3 py-1 rounded-lg">
                                <?= number_format($row['cost_per_unit'], 2) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Print Format Styles -->
<style>
    @media print {
        header, aside, .no-print, .shadow-xl, .animate-in { display: none !important; }
        body { background: white !important; }
        main { padding: 0 !important; width: 100% !important; margin: 0 !important; }
        .max-w-\[1600px\] { max-width: 100% !important; padding: 0 !important; }
        .rounded-\[3\.5rem\], .rounded-\[4rem\] { border-radius: 0 !important; border: 1px solid #eee !important; box-shadow: none !important; }
        .grid { display: block !important; }
        .bg-slate-900 { background: #1e293b !important; color: white !important; }
        .mb-10, .mb-12 { margin-bottom: 2rem !important; }
        canvas { max-width: 500px !important; margin: 0 auto !important; }
    }
</style>

<script>
    // 1. กราฟวงกลม: ประเภทโครงสร้าง
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($type_labels) ?>,
            datasets: [{
                data: <?= json_encode($type_budgets) ?>,
                backgroundColor: ['#f97316', '#3b82f6', '#10b981', '#6366f1', '#ec4899'],
                borderWidth: 5,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { weight: 'bold', family: 'Sarabun' }, padding: 20 } }
            },
            cutout: '70%'
        }
    });

    // 2. กราฟแท่ง: งบประมาณรายอำเภอ
    const distCtx = document.getElementById('districtChart').getContext('2d');
    new Chart(distCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_slice($dist_labels, 0, 10)) ?>,
            datasets: [{
                label: 'งบประมาณดำเนินการ (บาท)',
                data: <?= json_encode(array_slice($dist_budgets, 0, 10)) ?>,
                backgroundColor: '#1e293b',
                borderRadius: 12,
                hoverBackgroundColor: '#f97316'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { font: { family: 'Sarabun' } } },
                x: { ticks: { font: { weight: 'bold', family: 'Sarabun' } } }
            },
            plugins: { legend: { display: false } }
        }
    });

    window.onload = () => { if(typeof lucide !== 'undefined') lucide.createIcons(); };
</script>

<?php include 'includes/footer.php'; ?>