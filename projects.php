<?php
// projects.php - ทะเบียนโครงการทั้งหมด (ฉบับสมบูรณ์ V2.5 + Progress Bar + ตัวกรองปีงบประมาณ)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// 1. รับค่าจากตัวกรอง
$search = $_GET['search'] ?? '';
$f_district = $_GET['district'] ?? '';
$f_status = $_GET['status'] ?? '';
$f_budget = $_GET['budget'] ?? '';
$f_budget_type = $_GET['budget_type'] ?? '';
$f_year = $_GET['fiscal_year'] ?? ''; // เพิ่มการรับค่าปีงบประมาณ

// 2. สร้าง Query พร้อมตัวกรอง (เพิ่มเงื่อนไข p.deleted_at IS NULL เพื่อซ่อนข้อมูลในถังขยะ)
$query_parts = ["p.deleted_at IS NULL"];
$params = [];
$types = "";

if ($search) {
    $query_parts[] = "(p.project_name LIKE ? OR p.route_name LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}
if ($f_district) {
    $query_parts[] = "p.district_name = ?";
    $params[] = $f_district;
    $types .= "s";
}
if ($f_status) {
    $query_parts[] = "p.status = ?";
    $params[] = $f_status;
    $types .= "s";
}
if ($f_budget_type) {
    $query_parts[] = "p.budget_type = ?";
    $params[] = $f_budget_type;
    $types .= "s";
}
if ($f_year) {
    $query_parts[] = "p.fiscal_year = ?";
    $params[] = $f_year;
    $types .= "i";
}
if ($f_budget) {
    if ($f_budget === 'under_500k') {
        $query_parts[] = "p.budget_amount <= 500000";
    } elseif ($f_budget === '500k_1m') {
        $query_parts[] = "p.budget_amount > 500000 AND p.budget_amount <= 1000000";
    } elseif ($f_budget === '1m_5m') {
        $query_parts[] = "p.budget_amount > 1000000 AND p.budget_amount <= 5000000";
    } elseif ($f_budget === 'over_5m') {
        $query_parts[] = "p.budget_amount > 5000000";
    }
}

$where_clause = implode(" AND ", $query_parts);

// Pagination Setup
$limit = 15; // จำนวนรายการต่อหน้า
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Count total rows สำหรับการแบ่งหน้า
$count_sql = "SELECT COUNT(p.id) as total FROM projects p WHERE $where_clause";
$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch data ดึงข้อมูลมาแสดงผล
$sql = "SELECT p.*, u.full_name as creator_name 
        FROM projects p 
        LEFT JOIN users u ON p.created_by = u.id 
        WHERE $where_clause 
        ORDER BY p.created_at DESC 
        LIMIT ?, ?";

$fetch_params = $params;
$fetch_params[] = $offset;
$fetch_params[] = $limit;
$fetch_types = $types . "ii";

$stmt = $conn->prepare($sql);
if ($fetch_params) {
    $stmt->bind_param($fetch_types, ...$fetch_params);
}
$stmt->execute();
$result = $stmt->get_result();

$sisaket_districts = ["เมืองศรีสะเกษ", "ยางชุมน้อย", "กันทรารมย์", "กันทรลักษณ์", "ขุขันธ์", "ไพรบึง", "ปรางค์กู่", "ขุนหาญ", "ราษีไศล", "อุทุมพรพิสัย", "บึงบูรพ์", "ห้วยทับทัน", "โนนคูณ", "ศรีรัตนะ", "น้ำเกลี้ยง", "วังหิน", "ภูสิงห์", "เมืองจันทร์", "เบญจลักษณ์", "พยุห์", "โพธิ์ศรีสุวรรณ", "ศิลาลาด"];

// ดึงประเภทงบประมาณทั้งหมดจากฐานข้อมูลสำหรับทำตัวกรอง
$bt_sql = "SELECT DISTINCT budget_type FROM projects WHERE budget_type IS NOT NULL AND budget_type != '' AND deleted_at IS NULL ORDER BY budget_type ASC";
$bt_res = $conn->query($bt_sql);
$budget_types = [];
while($row = $bt_res->fetch_assoc()) {
    $budget_types[] = $row['budget_type'];
}

// ดึงปีงบประมาณทั้งหมดจากฐานข้อมูลสำหรับทำตัวกรอง
$year_sql = "SELECT DISTINCT fiscal_year FROM projects WHERE deleted_at IS NULL ORDER BY fiscal_year DESC";
$year_res = $conn->query($year_sql);
$fiscal_years = [];
while($row = $year_res->fetch_assoc()) {
    $fiscal_years[] = $row['fiscal_year'];
}

$msg = $_GET['msg'] ?? '';

include 'includes/header.php';
?>

<div class="max-w-[1600px] mx-auto pb-20 animate-in fade-in duration-500 w-full">
    <!-- Header & Filter -->
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-6 mb-8 px-4">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <i data-lucide="folders" class="text-orange-600"></i> ทะเบียนโครงการ
            </h2>
            <p class="text-slate-500 font-bold mt-1 uppercase tracking-widest text-xs ml-1">Project Directory</p>
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-3 w-full xl:w-auto bg-white p-3 rounded-[2rem] shadow-sm border border-slate-100">
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" size="18"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ค้นหาชื่อโครงการ, สายทาง..." 
                       class="w-full pl-12 pr-4 py-3 bg-slate-50 border-none rounded-xl text-sm font-bold outline-none focus:ring-2 focus:ring-orange-500 text-slate-700">
            </div>
            
            <select name="fiscal_year" class="p-3 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-orange-500 text-slate-700">
                <option value="">ทุกปีงบประมาณ</option>
                <?php foreach($fiscal_years as $y): ?>
                    <option value="<?= $y ?>" <?= $f_year == $y ? 'selected' : '' ?>>ปี <?= $y ?></option>
                <?php endforeach; ?>
            </select>

            <select name="district" class="p-3 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-orange-500 text-slate-700">
                <option value="">ทุกอำเภอ</option>
                <?php foreach($sisaket_districts as $d): ?>
                    <option value="<?= $d ?>" <?= $f_district == $d ? 'selected' : '' ?>><?= $d ?></option>
                <?php endforeach; ?>
            </select>

            <select name="budget" class="p-3 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-orange-500 text-slate-700 hidden sm:block">
                <option value="">ทุกช่วงงบประมาณ</option>
                <option value="under_500k" <?= $f_budget == 'under_500k' ? 'selected' : '' ?>>ไม่เกิน 500,000 ฿</option>
                <option value="500k_1m" <?= $f_budget == '500k_1m' ? 'selected' : '' ?>>500,001 - 1 ล้าน ฿</option>
                <option value="1m_5m" <?= $f_budget == '1m_5m' ? 'selected' : '' ?>>1 ล้าน - 5 ล้าน ฿</option>
                <option value="over_5m" <?= $f_budget == 'over_5m' ? 'selected' : '' ?>>มากกว่า 5 ล้าน ฿</option>
            </select>

            <select name="budget_type" class="p-3 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-orange-500 text-slate-700 hidden md:block">
                <option value="">ทุกประเภทงบ</option>
                <?php foreach($budget_types as $bt): ?>
                    <option value="<?= htmlspecialchars($bt) ?>" <?= $f_budget_type == $bt ? 'selected' : '' ?>><?= htmlspecialchars($bt) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="status" class="p-3 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-orange-500 text-slate-700">
                <option value="">ทุกสถานะ</option>
                <option value="รอดำเนินการ" <?= $f_status == 'รอดำเนินการ' ? 'selected' : '' ?>>รอดำเนินการ</option>
                <option value="กำลังดำเนินการ" <?= $f_status == 'กำลังดำเนินการ' ? 'selected' : '' ?>>กำลังดำเนินการ</option>
                <option value="เสร็จสิ้น" <?= $f_status == 'เสร็จสิ้น' ? 'selected' : '' ?>>เสร็จสิ้น</option>
                <option value="มีการเปลี่ยนแปลงหรือแก้ไข" <?= $f_status == 'มีการเปลี่ยนแปลงหรือแก้ไข' ? 'selected' : '' ?>>แก้ไข/เปลี่ยนแปลง</option>
            </select>

            <button type="submit" class="bg-slate-900 text-white px-6 py-3 rounded-xl font-black text-xs hover:bg-orange-600 transition-all shadow-md">
                กรองข้อมูล
            </button>
            <a href="projects.php" class="p-3 text-slate-400 hover:text-slate-600 transition-colors bg-slate-50 rounded-xl" title="ล้างตัวกรอง">
                <i data-lucide="refresh-cw" size="18"></i>
            </a>
            <a href="add_project.php" class="bg-emerald-500 text-white px-6 py-3 rounded-xl font-black text-xs hover:bg-emerald-600 transition-all shadow-md flex items-center gap-2">
                <i data-lucide="plus-circle" size="16"></i> เพิ่มโครงการ
            </a>
        </form>
    </div>

    <!-- แถบแจ้งเตือน -->
    <?php if($msg === 'trashed'): ?>
        <div class="mb-6 mx-4 bg-emerald-50 text-emerald-600 p-4 rounded-2xl border border-emerald-100 font-bold flex items-center gap-3 animate-in fade-in zoom-in">
            <i data-lucide="check-circle" size="20"></i> ย้ายโครงการลงถังขยะเรียบร้อยแล้ว
        </div>
    <?php elseif($msg === 'cancelled_to_trash'): ?>
        <div class="mb-6 mx-4 bg-slate-800 text-white p-5 rounded-[2rem] border border-slate-700 font-bold flex items-center gap-4 animate-in fade-in slide-in-from-top-4 shadow-2xl">
            <div class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center shrink-0">
                <i data-lucide="info" size="20" class="text-orange-400"></i>
            </div>
            <div>
                <p class="text-sm">โครงการถูกเปลี่ยนสถานะเป็น <span class="text-orange-400 uppercase tracking-widest font-black">"ยกเลิก"</span> เรียบร้อยแล้ว</p>
                <p class="text-[10px] text-slate-400 mt-0.5 font-medium">โครงการนี้ถูกตัดออกจากสถิติรวม และย้ายไปเก็บไว้ในถังขยะอัตโนมัติ</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Table Section -->
    <div class="bg-white rounded-[3.5rem] shadow-sm border border-slate-100 overflow-hidden mx-4 md:mx-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] border-b border-slate-50 bg-slate-50/50">
                        <th class="py-6 px-8 rounded-tl-[3.5rem]">ID</th>
                        <th class="py-6 px-4">ชื่อโครงการ / สายทาง</th>
                        <th class="py-6 px-4 text-center">ข้อมูลวิศวกรรม</th>
                        <th class="py-6 px-4 text-center">งบประมาณ</th>
                        <th class="py-6 px-4 text-center">สถานะ / ความก้าวหน้า</th>
                        <th class="py-6 px-8 text-center rounded-tr-[3.5rem]">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="py-5 px-8">
                                <span class="text-xs font-black text-slate-400 bg-slate-100 px-3 py-1.5 rounded-lg">#<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></span>
                            </td>
                            <td class="py-5 px-4 min-w-[250px]">
                                <p class="text-sm font-black text-slate-800 line-clamp-1" title="<?= htmlspecialchars($row['project_name']) ?>"><?= htmlspecialchars($row['project_name']) ?></p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] font-bold text-slate-500 flex items-center gap-1"><i data-lucide="map-pin" size="12" class="text-orange-500"></i> อ.<?= htmlspecialchars($row['district_name']) ?></span>
                                    <span class="text-slate-300 text-[10px]">|</span>
                                    <span class="text-[10px] font-bold text-slate-400 truncate max-w-[200px]"><?= htmlspecialchars($row['route_name'] ?: 'ไม่ระบุสายทาง') ?></span>
                                </div>
                            </td>
                            <td class="py-5 px-4 text-center">
                                <p class="text-[11px] font-black text-slate-600 bg-slate-100/80 px-2 py-1 rounded-md inline-block"><?= htmlspecialchars($row['infrastructure_type']) ?></p>
                                <p class="text-[10px] font-bold text-slate-400 mt-1">ระยะทาง <?= number_format($row['distance'], 2) ?> ม.</p>
                            </td>
                            <td class="py-5 px-4 text-center">
                                <p class="text-sm font-black text-emerald-600"><?= number_format($row['budget_amount']) ?> ฿</p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">ปี <?= $row['fiscal_year'] ?></p>
                            </td>
                            <td class="py-5 px-4 text-center min-w-[150px]">
                                <?php 
                                    $st = $row['status'];
                                    $bg = 'bg-slate-100 text-slate-600 border-slate-200';
                                    $bar = 'bg-slate-400';
                                    if($st == 'กำลังดำเนินการ') { $bg = 'bg-orange-100 text-orange-600 border-orange-200'; $bar = 'bg-orange-500'; }
                                    if($st == 'เสร็จสิ้น') { $bg = 'bg-emerald-100 text-emerald-600 border-emerald-200'; $bar = 'bg-emerald-500'; }
                                    if($st == 'มีการเปลี่ยนแปลงหรือแก้ไข') { $bg = 'bg-rose-100 text-rose-600 border-rose-200'; $bar = 'bg-rose-500'; }
                                    
                                    $pct = $row['progress_percent'] ?? 0;
                                ?>
                                <div class="flex flex-col items-center">
                                    <span class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border <?= $bg ?>">
                                        <?= htmlspecialchars($st) ?>
                                    </span>
                                    
                                    <!-- แสดง Progress Bar -->
                                    <div class="w-full max-w-[120px] mt-2 group-hover:scale-105 transition-transform">
                                        <div class="flex justify-between items-end mb-1">
                                            <span class="text-[8px] font-bold text-slate-400 uppercase">Progress</span>
                                            <span class="text-[10px] font-black text-slate-700"><?= $pct ?>%</span>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-1.5 rounded-full <?= $bar ?> transition-all duration-1000" style="width: <?= $pct ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-5 px-8">
                                <div class="flex items-center justify-center gap-2 opacity-100 lg:opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="view_project.php?id=<?= $row['id'] ?>" class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-colors" title="ดูข้อมูล">
                                        <i data-lucide="eye" size="14"></i>
                                    </a>
                                    <a href="edit_project.php?id=<?= $row['id'] ?>" class="w-8 h-8 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center hover:bg-orange-600 hover:text-white transition-colors" title="แก้ไข">
                                        <i data-lucide="edit-2" size="14"></i>
                                    </a>
                                    <?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff'): ?>
                                    <a href="delete_project.php?id=<?= $row['id'] ?>" onclick="return confirm('ต้องการย้ายโครงการนี้ลงถังขยะหรือไม่?')" class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-colors" title="ลบลงถังขยะ">
                                        <i data-lucide="trash" size="14"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-20 text-center text-slate-300">
                                <i data-lucide="folder-search" class="mx-auto mb-4 opacity-50" size="48"></i>
                                <p class="text-sm font-bold uppercase tracking-widest">ไม่พบข้อมูลโครงการในระบบ</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Component -->
        <?php if($total_pages > 1): ?>
        <div class="bg-slate-50 px-8 py-5 border-t border-slate-100 flex items-center justify-between">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">หน้า <?= $page ?> จาก <?= $total_pages ?> (รวม <?= $total_rows ?> รายการ)</p>
            <div class="flex gap-1">
                <?php if($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&fiscal_year=<?= urlencode($f_year) ?>&district=<?= urlencode($f_district) ?>&status=<?= urlencode($f_status) ?>&budget=<?= urlencode($f_budget) ?>&budget_type=<?= urlencode($f_budget_type) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:border-orange-500 hover:text-orange-500 transition-colors shadow-sm"><i data-lucide="chevron-left" size="16"></i></a>
                <?php endif; ?>
                
                <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&fiscal_year=<?= urlencode($f_year) ?>&district=<?= urlencode($f_district) ?>&status=<?= urlencode($f_status) ?>&budget=<?= urlencode($f_budget) ?>&budget_type=<?= urlencode($f_budget_type) ?>" 
                       class="w-8 h-8 flex items-center justify-center rounded-lg border text-xs font-black transition-colors shadow-sm <?= $i === $page ? 'bg-orange-600 text-white border-orange-600' : 'bg-white border-slate-200 text-slate-600 hover:border-orange-500 hover:text-orange-500' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if($page < $total_pages): ?>
                    <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&fiscal_year=<?= urlencode($f_year) ?>&district=<?= urlencode($f_district) ?>&status=<?= urlencode($f_status) ?>&budget=<?= urlencode($f_budget) ?>&budget_type=<?= urlencode($f_budget_type) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:border-orange-500 hover:text-orange-500 transition-colors shadow-sm"><i data-lucide="chevron-right" size="16"></i></a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>