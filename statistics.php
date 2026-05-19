<?php
// statistics.php - หน้ารายงานสถิติและกราฟวิเคราะห์ข้อมูล V2.5
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// 1. ภาพรวมสถิติ (Overall Stats) - ไม่รวมโครงการในถังขยะ
$stats_query = "SELECT 
    COUNT(id) as total_projects, 
    SUM(budget_amount) as total_budget,
    SUM(area) as total_area,
    SUM(distance) as total_distance
    FROM projects WHERE deleted_at IS NULL";
$stats = $conn->query($stats_query)->fetch_assoc();
$total_budget_ref = $stats['total_budget'] > 0 ? $stats['total_budget'] : 1;

// 2. สถิติแยกตามสถานะ (Status Distribution)
$status_query = "SELECT status, COUNT(id) as count FROM projects WHERE deleted_at IS NULL GROUP BY status";
$status_res = $conn->query($status_query);
$status_labels = [];
$status_counts = [];
$status_colors = [];
while($row = $status_res->fetch_assoc()) {
    $status_labels[] = $row['status'];
    $status_counts[] = $row['count'];
    // กำหนดสีตามสถานะ
    if($row['status'] == 'เสร็จสิ้น') $status_colors[] = '#10b981'; // Emerald
    elseif($row['status'] == 'กำลังดำเนินการ') $status_colors[] = '#f97316'; // Orange
    elseif($row['status'] == 'มีการเปลี่ยนแปลงหรือแก้ไข') $status_colors[] = '#f43f5e'; // Rose
    else $status_colors[] = '#94a3b8'; // Slate (รอดำเนินการ)
}

// 3. สถิติแยกตามประเภทถนน (Infrastructure Type)
$type_query = "SELECT infrastructure_type, COUNT(id) as count, SUM(budget_amount) as budget FROM projects WHERE deleted_at IS NULL AND infrastructure_type != '' GROUP BY infrastructure_type";
$type_res = $conn->query($type_query);
$type_labels = [];
$type_budgets = [];
while($row = $type_res->fetch_assoc()) {
    $type_labels[] = $row['infrastructure_type'];
    $type_budgets[] = $row['budget'];
}

// 4. สถิติแยกตามปีงบประมาณ (Fiscal Year Trend)
$year_query = "SELECT fiscal_year, COUNT(id) as count, SUM(budget_amount) as budget FROM projects WHERE deleted_at IS NULL GROUP BY fiscal_year ORDER BY fiscal_year ASC";
$year_res = $conn->query($year_query);
$year_labels = [];
$year_budgets = [];
while($row = $year_res->fetch_assoc()) {
    $year_labels[] = 'ปี ' . $row['fiscal_year'];
    $year_budgets[] = $row['budget'];
}

// 5. สถิติแยกตามอำเภอ (District Distribution - สำหรับตารางและกราฟแท่ง)
$district_query = "SELECT district_name, COUNT(id) as count, SUM(budget_amount) as budget, SUM(distance) as distance 
                   FROM projects WHERE deleted_at IS NULL AND district_name != '' 
                   GROUP BY district_name ORDER BY budget DESC";
$district_res = $conn->query($district_query);
$district_data = [];
$dist_top_labels = [];
$dist_top_budgets = [];
$counter = 0;
while($row = $district_res->fetch_assoc()) {
    $district_data[] = $row;
    // ดึง 10 อันดับแรกไปแสดงในกราฟแท่ง
    if($counter < 10) {
        $dist_top_labels[] = 'อ.' . $row['district_name'];
        $dist_top_budgets[] = $row['budget'];
        $counter++;
    }
}

include 'includes/header.php';
?>

<!-- โหลดไลบรารี Chart.js สำหรับวาดกราฟ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="max-w-[1600px] mx-auto pb-20 animate-in fade-in duration-500 w-full px-4">
    <!-- Header -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-purple-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-purple-500/30 shrink-0">
                <i data-lucide="bar-chart-2" size="28"></i>
            </div>
            <div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight">รายงานสถิติ</h2>
                <p class="text-slate-500 font-bold mt-1 uppercase tracking-widest text-[10px]">Statistical Reports & Analytics</p>
            </div>
        </div>
        <button onclick="window.print()" class="bg-white border border-slate-200 text-slate-700 px-6 py-3 rounded-xl font-bold text-xs hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm w-full md:w-auto">
            <i data-lucide="printer" size="16"></i> พิมพ์รายงาน
        </button>
    </div>

    <!-- กราฟ Section 1: สัดส่วนและแนวโน้ม -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- กราฟโดนัท: สถานะโครงการ -->
        <div class="bg-white p-6 md:p-8 rounded-[3rem] shadow-sm border border-slate-100 flex flex-col items-center">
            <h3 class="w-full text-sm font-black text-slate-800 uppercase tracking-widest mb-6 border-l-4 border-orange-500 pl-3">สัดส่วนสถานะโครงการ</h3>
            <div class="relative w-full max-w-[250px] aspect-square">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- กราฟพาย: ประเภทถนน (งบประมาณ) -->
        <div class="bg-white p-6 md:p-8 rounded-[3rem] shadow-sm border border-slate-100 flex flex-col items-center">
            <h3 class="w-full text-sm font-black text-slate-800 uppercase tracking-widest mb-6 border-l-4 border-blue-500 pl-3">สัดส่วนงบตามประเภทถนน</h3>
            <div class="relative w-full max-w-[250px] aspect-square">
                <canvas id="typeChart"></canvas>
            </div>
        </div>

        <!-- กราฟเส้น: แนวโน้มงบประมาณรายปี -->
        <div class="bg-white p-6 md:p-8 rounded-[3rem] shadow-sm border border-slate-100">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 border-l-4 border-emerald-500 pl-3">แนวโน้มงบประมาณรายปี</h3>
            <div class="relative w-full h-[250px]">
                <canvas id="yearChart"></canvas>
            </div>
        </div>
    </div>

    <!-- กราฟ Section 2: งบประมาณรายอำเภอ (Top 10) -->
    <div class="bg-white p-6 md:p-8 rounded-[3.5rem] shadow-sm border border-slate-100 mb-8">
        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 border-l-4 border-purple-500 pl-3">10 อันดับอำเภอที่ได้รับงบประมาณสูงสุด</h3>
        <div class="relative w-full h-[350px]">
            <canvas id="districtChart"></canvas>
        </div>
    </div>

    <!-- ตารางสรุปข้อมูลรายอำเภอ -->
    <div class="bg-white rounded-[3.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 md:p-8 border-b border-slate-50 flex items-center gap-3 bg-slate-50/50">
            <i data-lucide="table-2" class="text-slate-400"></i>
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">สรุปข้อมูลการดำเนินงานรายอำเภอ</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 bg-white">
                        <th class="py-5 px-8">ลำดับ</th>
                        <th class="py-5 px-4">อำเภอ</th>
                        <th class="py-5 px-4 text-center">จำนวนโครงการ</th>
                        <th class="py-5 px-4 text-right">ระยะทางรวม (กม.)</th>
                        <th class="py-5 px-8 text-right">งบประมาณรวม (บาท)</th>
                        <th class="py-5 px-8 text-right">สัดส่วนงบประมาณ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php 
                    $rank = 1;
                    if(count($district_data) > 0): 
                        foreach($district_data as $d): 
                            $pct = ($d['budget'] / $total_budget_ref) * 100;
                    ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="py-4 px-8 text-xs font-black text-slate-400"><?= $rank++ ?></td>
                        <td class="py-4 px-4 font-black text-slate-700">อ.<?= htmlspecialchars($d['district_name']) ?></td>
                        <td class="py-4 px-4 text-center font-bold text-slate-500">
                            <span class="bg-slate-100 px-3 py-1 rounded-lg text-xs"><?= number_format($d['count']) ?></span>
                        </td>
                        <td class="py-4 px-4 text-right font-black text-blue-600"><?= number_format($d['distance'] / 1000, 2) ?></td>
                        <td class="py-4 px-8 text-right font-black text-orange-600 text-sm"><?= number_format($d['budget'], 2) ?></td>
                        <td class="py-4 px-8">
                            <div class="flex items-center justify-end gap-3">
                                <span class="text-[10px] font-black text-slate-500"><?= number_format($pct, 1) ?>%</span>
                                <div class="w-20 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-orange-500 h-1.5 rounded-full" style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-300 font-bold uppercase tracking-widest text-xs">ไม่มีข้อมูล</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="bg-slate-50/80 border-t-2 border-slate-100">
                    <tr>
                        <th colspan="2" class="py-5 px-8 text-right text-[10px] font-black text-slate-500 uppercase tracking-widest">รวมทั้งสิ้น (Total)</th>
                        <th class="py-5 px-4 text-center font-black text-slate-800 text-lg"><?= number_format($stats['total_projects']) ?></th>
                        <th class="py-5 px-4 text-right font-black text-blue-600 text-lg"><?= number_format($stats['total_distance'] / 1000, 2) ?></th>
                        <th class="py-5 px-8 text-right font-black text-orange-600 text-lg"><?= number_format($stats['total_budget'], 2) ?></th>
                        <th class="py-5 px-8 text-right text-[10px] font-black text-slate-400">100%</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
    // ตั้งค่าฟอนต์มาตรฐานให้กับกราฟ
    Chart.defaults.font.family = "'Sarabun', sans-serif";
    Chart.defaults.color = '#64748b'; // slate-500
    
    // รูปแบบการแสดงผลตัวเลขให้มีคอมม่า
    const numberFormat = new Intl.NumberFormat('th-TH');

    window.onload = () => {
        if(typeof lucide !== 'undefined') lucide.createIcons();

        // 1. กราฟสถานะโครงการ (Doughnut Chart)
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($status_labels) ?>,
                datasets: [{
                    data: <?= json_encode($status_counts) ?>,
                    backgroundColor: <?= json_encode($status_colors) ?>,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { weight: 'bold', size: 10 }, padding: 15 } }
                },
                cutout: '70%'
            }
        });

        // 2. กราฟประเภทถนน (Pie Chart)
        new Chart(document.getElementById('typeChart'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($type_labels) ?>,
                datasets: [{
                    data: <?= json_encode($type_budgets) ?>,
                    backgroundColor: ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#64748b', '#ec4899'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { weight: 'bold', size: 10 }, padding: 15 } },
                    tooltip: {
                        callbacks: {
                            label: function(context) { return ' งบประมาณ: ' + numberFormat.format(context.raw) + ' บาท'; }
                        }
                    }
                }
            }
        });

        // 3. กราฟแนวโน้มงบประมาณรายปี (Line Chart)
        new Chart(document.getElementById('yearChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($year_labels) ?>,
                datasets: [{
                    label: 'งบประมาณ (บาท)',
                    data: <?= json_encode($year_budgets) ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    tension: 0.4, // ทำให้เส้นโค้งมนสมูท
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#10b981',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f1f5f9' }, ticks: { callback: function(val) { return numberFormat.format(val/1000000) + 'M'; } } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 4. กราฟงบประมาณรายอำเภอ Top 10 (Bar Chart)
        new Chart(document.getElementById('districtChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($dist_top_labels) ?>,
                datasets: [{
                    label: 'งบประมาณ (บาท)',
                    data: <?= json_encode($dist_top_budgets) ?>,
                    backgroundColor: 'rgba(139, 92, 246, 0.8)', // Purple
                    hoverBackgroundColor: '#8b5cf6',
                    borderRadius: 8,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f1f5f9' }, ticks: { callback: function(val) { return numberFormat.format(val/1000000) + 'M'; } } },
                    x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }
                }
            }
        });
    };
</script>

<?php include 'includes/footer.php'; ?>