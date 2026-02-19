<?php
// projects.php - ทะเบียนโครงการพร้อมช่องค้นหาเดิมและเพิ่มระบบลบโครงการ
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// รายชื่ออำเภอสำหรับตัวกรอง
$sisaket_districts = [
    "เมืองศรีสะเกษ", "ยางชุมน้อย", "กันทรารมย์", "กันทรลักษณ์", "ขุขันธ์", "ไพรบึง", "ปรางค์กู่", "ขุนหาญ", 
    "ราษีไศล", "อุทุมพรพิสัย", "บึงบูรพ์", "ห้วยทับทัน", "โนนคูณ", "ศรีรัตนะ", "น้ำเกลี้ยง", "วังหิน", 
    "ภูสิงห์", "เมืองจันทร์", "เบญจลักษณ์", "พยุห์", "โพธิ์ศรีสุวรรณ", "ศิลาลาด"
];

// 1. รับค่าจากตัวกรอง
$search = $_GET['search'] ?? '';
$f_district = $_GET['district'] ?? '';
$f_status = $_GET['status'] ?? '';

// 2. สร้าง Query พร้อมตัวกรอง
$query_parts = ["1=1"];
$params = [];
$types = "";

if ($search) {
    $query_parts[] = "(p.project_name LIKE ? OR p.route_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param; $params[] = $search_param;
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

$where_sql = implode(" AND ", $query_parts);
$sql = "SELECT p.*, u.full_name as creator_name 
        FROM projects p 
        LEFT JOIN users u ON p.created_by = u.id 
        WHERE $where_sql 
        ORDER BY p.fiscal_year DESC, p.id DESC";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// คำนวณสรุปผล
$total_budget = 0;
$total_area = 0;
$project_count = $result->num_rows;
$projects_list = [];
while($row = $result->fetch_assoc()) {
    $total_budget += $row['budget_amount'];
    $total_area += $row['area'];
    $projects_list[] = $row;
}

include 'includes/header.php';
?>

<div class="space-y-6 animate-in fade-in duration-500 pb-20">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight uppercase">ทะเบียนโครงการพัฒนา</h2>
            <p class="text-slate-500 font-medium text-sm italic">จัดการฐานข้อมูลโครงสร้างพื้นฐาน อบจ.ศรีสะเกษ</p>
        </div>
        <div class="flex gap-2 w-full lg:w-auto">
            <a href="add_project.php" class="flex-1 lg:flex-none bg-orange-600 text-white px-8 py-3 rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-slate-900 transition-all shadow-xl">
                <i data-lucide="plus-circle" size="18"></i> เพิ่มโครงการ
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-slate-900 p-6 rounded-[2rem] text-white shadow-lg">
            <p class="text-[9px] font-black text-orange-500 uppercase tracking-widest mb-1">ผลการค้นหา</p>
            <h4 class="text-2xl font-black"><?= number_format($project_count) ?> <span class="text-xs">โครงการ</span></h4>
        </div>
        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">งบประมาณรวม (ที่แสดง)</p>
            <h4 class="text-2xl font-black text-slate-800"><?= number_format($total_budget, 2) ?> <span class="text-xs">฿</span></h4>
        </div>
        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">พื้นที่ดำเนินการรวม</p>
            <h4 class="text-2xl font-black text-blue-600"><?= number_format($total_area, 2) ?> <span class="text-xs">ตร.ม.</span></h4>
        </div>
    </div>

    <!-- Filter Bar (คืนค่าเดิม) -->
    <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="ค้นชื่อโครงการหรือสายทาง..." 
                   class="w-full p-3.5 bg-slate-50 border-2 border-transparent rounded-xl outline-none focus:border-orange-500 transition-all font-bold text-sm">
            
            <select name="district" class="p-3.5 bg-slate-50 border-2 border-transparent rounded-xl outline-none focus:border-orange-500 font-bold text-slate-600 text-sm">
                <option value="">ทุกอำเภอ</option>
                <?php foreach($sisaket_districts as $d): ?>
                    <option value="<?= $d ?>" <?= $f_district == $d ? 'selected' : '' ?>><?= $d ?></option>
                <?php endforeach; ?>
            </select>

            <select name="status" class="p-3.5 bg-slate-50 border-2 border-transparent rounded-xl outline-none focus:border-orange-500 font-bold text-slate-600 text-sm">
                <option value="">ทุกสถานะ</option>
                <option value="รอดำเนินการ" <?= $f_status == 'รอดำเนินการ' ? 'selected' : '' ?>>รอดำเนินการ</option>
                <option value="กำลังดำเนินการ" <?= $f_status == 'กำลังดำเนินการ' ? 'selected' : '' ?>>กำลังดำเนินการ</option>
                <option value="เสร็จสิ้น" <?= $f_status == 'เสร็จสิ้น' ? 'selected' : '' ?>>เสร็จสิ้น</option>
                <option value="มีการเปลี่ยนแปลงหรือแก้ไข" <?= $f_status == 'มีการเปลี่ยนแปลงหรือแก้ไข' ? 'selected' : '' ?>>แก้ไข/เปลี่ยนแปลง</option>
            </select>

            <button type="submit" class="bg-slate-900 text-white rounded-xl font-black hover:bg-orange-600 transition-all text-sm uppercase tracking-widest">
                ค้นหาข้อมูล
            </button>
        </form>
    </div>

    <!-- Project List -->
    <div class="space-y-4">
        <?php if ($project_count > 0): ?>
            <?php foreach($projects_list as $row): 
                $status_config = [
                    'รอดำเนินการ' => ['color' => 'slate', 'icon' => 'clock'],
                    'กำลังดำเนินการ' => ['color' => 'orange', 'icon' => 'play-circle'],
                    'เสร็จสิ้น' => ['color' => 'emerald', 'icon' => 'check-circle-2'],
                    'มีการเปลี่ยนแปลงหรือแก้ไข' => ['color' => 'rose', 'icon' => 'alert-circle']
                ];
                $cfg = $status_config[$row['status']] ?? $status_config['รอดำเนินการ'];
            ?>
            <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-xl transition-all group overflow-hidden relative">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="flex items-start gap-5 flex-1 overflow-hidden">
                        <div class="w-14 h-14 rounded-2xl bg-<?= $cfg['color'] ?>-50 text-<?= $cfg['color'] ?>-600 flex items-center justify-center shrink-0 border border-<?= $cfg['color'] ?>-100">
                            <i data-lucide="<?= $cfg['icon'] ?>" size="28"></i>
                        </div>
                        <div class="overflow-hidden">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[9px] font-black uppercase bg-slate-100 px-2 py-0.5 rounded text-slate-500">ปี <?= $row['fiscal_year'] ?></span>
                                <span class="text-[9px] font-black uppercase text-<?= $cfg['color'] ?>-600 bg-<?= $cfg['color'] ?>-50 px-2 py-0.5 rounded"><?= e($row['status']) ?></span>
                            </div>
                            <h3 class="text-lg font-black text-slate-800 truncate group-hover:text-orange-600 transition-colors"><?= e($row['project_name']) ?></h3>
                            <p class="text-xs text-slate-400 font-bold mt-0.5 flex items-center gap-2">
                                <i data-lucide="map-pin" size="12"></i> 
                                <span>อ.<?= e($row['district_name']) ?></span>
                                <span class="text-slate-200">|</span>
                                <span class="truncate"><?= e($row['route_name'] ?: 'ไม่ระบุสายทาง') ?></span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex flex-row md:flex-col items-center md:items-end justify-between w-full md:w-auto gap-4 pt-4 md:pt-0 border-t md:border-t-0 border-slate-50">
                        <div class="text-left md:text-right">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">งบประมาณโครงการ</p>
                            <p class="text-xl font-black text-slate-900"><?= number_format($row['budget_amount'], 2) ?> ฿</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="view_project.php?id=<?= $row['id'] ?>" class="p-3 bg-slate-50 text-slate-400 hover:bg-blue-600 hover:text-white rounded-xl transition-all shadow-sm">
                                <i data-lucide="eye" size="18"></i>
                            </a>
                            <a href="edit_project.php?id=<?= $row['id'] ?>" class="p-3 bg-slate-50 text-slate-400 hover:bg-orange-600 hover:text-white rounded-xl transition-all shadow-sm">
                                <i data-lucide="edit-3" size="18"></i>
                            </a>
                            <?php if($_SESSION['role'] === 'admin'): ?>
                            <a href="delete_project.php?id=<?= $row['id'] ?>" 
                               onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบโครงการนี้? ข้อมูลพิกัดและไฟล์แนบจะถูกลบออกถาวร')"
                               class="p-3 bg-slate-50 text-slate-400 hover:bg-rose-600 hover:text-white rounded-xl transition-all shadow-sm">
                                <i data-lucide="trash-2" size="18"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-20 bg-white rounded-[3rem] border-2 border-dashed border-slate-100">
                <i data-lucide="folder-search" class="mx-auto text-slate-200 mb-4" size="64"></i>
                <p class="text-slate-400 font-bold uppercase tracking-widest text-sm">ไม่พบข้อมูลโครงการตามเงื่อนไขที่ระบุ</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>