<?php
// index.php
// ที่อยู่ไฟล์: /index.php
require_once 'auth_check.php';
require_once 'config/db.php';

// 1. ดึงสถิติรวมจากฐานข้อมูล
$stats_sql = "SELECT 
    COUNT(id) as total_projects, 
    SUM(budget_amount) as total_budget, 
    SUM(area) as total_area 
    FROM projects";
$stats_res = $conn->query($stats_sql);
$stats = $stats_res->fetch_assoc();

// 2. นับจำนวนเจ้าหน้าที่ที่เปิดใช้งานอยู่
$user_count_res = $conn->query("SELECT COUNT(id) as c FROM users WHERE status = 'active'");
$user_count = $user_count_res->fetch_assoc()['c'];

// 3. ดึงโครงการล่าสุด 5 รายการ พร้อมชื่อผู้บันทึก (Join ตาราง users)
$latest_sql = "SELECT p.*, u.full_name as creator_name 
               FROM projects p 
               LEFT JOIN users u ON p.created_by = u.id 
               ORDER BY p.id DESC LIMIT 5";
$latest_res = $conn->query($latest_sql);

include 'includes/header.php';
?>

<div class="space-y-8 animate-in fade-in duration-700">
    <!-- ส่วนหัวต้อนรับ (Welcome Banner) -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-white p-10 rounded-[3.5rem] shadow-sm border border-slate-100 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-64 h-64 bg-orange-50 rounded-full -mr-20 -mt-20 blur-3xl opacity-50 group-hover:opacity-80 transition-opacity"></div>
        <div class="relative z-10">
            <h2 class="text-3xl md:text-4xl font-black text-slate-800 tracking-tight">
                สวัสดี, <span class="text-orange-600"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
            </h2>
            <p class="text-slate-500 font-medium mt-2">ยินดีต้อนรับสู่ระบบบริหารจัดการพิกัดและโครงสร้างพื้นฐานสำนักช่าง</p>
        </div>
        <div class="flex gap-3 relative z-10">
            <div class="bg-slate-900 px-6 py-3 rounded-2xl border border-slate-800 shadow-xl">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">สิทธิ์การใช้งาน</p>
                <p class="text-white font-black uppercase text-sm"><?= $_SESSION['role'] ?></p>
            </div>
        </div>
    </div>

    <!-- บัตรสถิติ (Stat Cards) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all">
            <div class="w-14 h-14 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center mb-6 shadow-inner">
                <i data-lucide="construction" size="28"></i>
            </div>
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-1">โครงการทั้งหมด</p>
            <h3 class="text-3xl font-black text-slate-800"><?= number_format($stats['total_projects']) ?> <span class="text-xs font-bold opacity-30 uppercase ml-1">แห่ง</span></h3>
        </div>

        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all">
            <div class="w-14 h-14 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center mb-6 shadow-inner">
                <i data-lucide="wallet" size="28"></i>
            </div>
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-1">งบประมาณดำเนินการ</p>
            <h3 class="text-3xl font-black text-slate-800"><?= number_format($stats['total_budget'] ?: 0, 2) ?> <span class="text-xs font-bold opacity-30 uppercase ml-1">฿</span></h3>
        </div>

        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all">
            <div class="w-14 h-14 bg-purple-50 text-purple-500 rounded-2xl flex items-center justify-center mb-6 shadow-inner">
                <i data-lucide="maximize" size="28"></i>
            </div>
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-1">พื้นที่ดำเนินการรวม</p>
            <h3 class="text-3xl font-black text-slate-800"><?= number_format($stats['total_area'] ?: 0, 2) ?> <span class="text-xs font-bold opacity-30 uppercase ml-1">ตร.ม.</span></h3>
        </div>

        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all">
            <div class="w-14 h-14 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center mb-6 shadow-inner">
                <i data-lucide="users-round" size="28"></i>
            </div>
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] mb-1">เจ้าหน้าที่ในระบบ</p>
            <h3 class="text-3xl font-black text-slate-800"><?= number_format($user_count) ?> <span class="text-xs font-bold opacity-30 uppercase ml-1">คน</span></h3>
        </div>
    </div>

    <!-- ส่วนตารางข้อมูลล่าสุด (Recent Projects Table) -->
    <div class="bg-white p-10 rounded-[3.5rem] shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-10">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-orange-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-orange-200">
                    <i data-lucide="list-checks" size="24"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-slate-800">โครงการบันทึกล่าสุด</h3>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-0.5">Recently added infrastructure</p>
                </div>
            </div>
            <a href="projects.php" class="bg-slate-50 hover:bg-slate-100 text-slate-600 px-6 py-3 rounded-2xl text-sm font-bold transition-all flex items-center gap-2 border border-slate-100">
                ดูทั้งหมด <i data-lucide="arrow-right" size="16"></i>
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[11px] font-black text-slate-400 uppercase tracking-[0.15em] border-b border-slate-50">
                        <th class="pb-5 px-2">ชื่อโครงการ / ผู้รับผิดชอบ</th>
                        <th class="pb-5 px-2">ปีงบประมาณ</th>
                        <th class="pb-5 px-2 text-right">งบประมาณ</th>
                        <th class="pb-5 px-2 text-center">สถานะ</th>
                        <th class="pb-5 px-2 text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if ($latest_res->num_rows > 0): ?>
                        <?php while($row = $latest_res->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="py-6 px-2">
                                <div class="font-bold text-slate-800 group-hover:text-orange-600 transition-colors"><?= htmlspecialchars($row['project_name']) ?></div>
                                <div class="text-[10px] text-slate-400 font-bold mt-1 uppercase">โดย: <?= htmlspecialchars($row['creator_name'] ?: 'ระบบอัตโนมัติ') ?></div>
                            </td>
                            <td class="py-6 px-2 font-black text-slate-500"><?= $row['fiscal_year'] ?></td>
                            <td class="py-6 px-2 text-right font-black text-slate-800"><?= number_format($row['budget_amount'], 2) ?> ฿</td>
                            <td class="py-6 px-2 text-center">
                                <span class="bg-orange-100 text-orange-700 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-tight">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="py-6 px-2 text-center">
                                <a href="view_project.php?id=<?= $row['id'] ?>" class="text-slate-400 hover:text-slate-900 transition-colors inline-block p-2">
                                    <i data-lucide="eye" size="20"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-20 text-center">
                                <i data-lucide="inbox" class="mx-auto text-slate-200 mb-4" size="48"></i>
                                <p class="text-slate-400 font-bold uppercase text-xs tracking-widest">ยังไม่มีข้อมูลโครงการที่บันทึกไว้</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>