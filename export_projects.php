<?php
// export_projects.php - ระบบส่งออกข้อมูล Custom Export (เลือกคอลัมน์ได้)
require_once 'auth_check.php';
require_once 'config/db.php';

// ข้อมูลสำหรับตัวกรอง
$sisaket_districts = ["เมืองศรีสะเกษ", "ยางชุมน้อย", "กันทรารมย์", "กันทรลักษณ์", "ขุขันธ์", "ไพรบึง", "ปรางค์กู่", "ขุนหาญ", "ราษีไศล", "อุทุมพรพิสัย", "บึงบูรพ์", "ห้วยทับทัน", "โนนคูณ", "ศรีรัตนะ", "น้ำเกลี้ยง", "วังหิน", "ภูสิงห์", "เมืองจันทร์", "เบญจลักษณ์", "พยุห์", "โพธิ์ศรีสุวรรณ", "ศิลาลาด"];
$years_res = $conn->query("SELECT DISTINCT fiscal_year FROM projects ORDER BY fiscal_year DESC");
$years = [];
while($y = $years_res->fetch_assoc()) { $years[] = $y['fiscal_year']; }

// หากมีการกดปุ่ม "ส่งออกไฟล์ (POST)"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_action'])) {
    $f_district = $_POST['district'] ?? '';
    $f_year = $_POST['fiscal_year'] ?? '';
    $f_status = $_POST['status'] ?? '';
    
    $selected_columns = $_POST['columns'] ?? [];
    
    if (empty($selected_columns)) {
        $error_msg = "กรุณาเลือกอย่างน้อย 1 คอลัมน์ที่ต้องการส่งออก";
    } else {
        // สร้าง Query ตามตัวกรอง
        $query_parts = ["1=1"];
        $params = [];
        $types = "";

        if ($f_district) { $query_parts[] = "district_name = ?"; $params[] = $f_district; $types .= "s"; }
        if ($f_year) { $query_parts[] = "fiscal_year = ?"; $params[] = $f_year; $types .= "i"; }
        if ($f_status) { $query_parts[] = "status = ?"; $params[] = $f_status; $types .= "s"; }

        $where_sql = implode(" AND ", $query_parts);
        $sql = "SELECT * FROM projects WHERE $where_sql ORDER BY fiscal_year DESC, district_name ASC";
        
        $stmt = $conn->prepare($sql);
        if ($params) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $result = $stmt->get_result();

        // เคลียร์ Output Buffer ก่อนส่งออกไฟล์
        if (ob_get_length()) ob_clean();
        
        $filename = "SSK_PAO_Projects_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        // ใส่ UTF-8 BOM ให้ Excel อ่านภาษาไทยได้
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // แปลง Key คอลัมน์เป็นชื่อภาษาไทย
        $column_map = [
            'id' => 'รหัสโครงการ',
            'project_name' => 'ชื่อโครงการ',
            'route_name' => 'ชื่อสายทาง',
            'infrastructure_type' => 'ประเภทถนน',
            'fiscal_year' => 'ปีงบประมาณ',
            'district_name' => 'อำเภอ',
            'distance' => 'ความยาว (ม.)',
            'width' => 'ความกว้าง (ม.)',
            'area' => 'พื้นที่รวม (ตร.ม.)',
            'budget_amount' => 'งบประมาณ (บาท)',
            'budget_type' => 'ประเภทงบ',
            'supervisor_name' => 'ผู้ควบคุมงาน',
            'status' => 'สถานะ',
            'coordinates' => 'พิกัด (Lat, Lng)'
        ];

        // 1. เขียน Header
        $headers = [];
        foreach ($selected_columns as $col) {
            $headers[] = $column_map[$col] ?? $col;
        }
        fputcsv($output, $headers);

        // 2. เขียน Data
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data_row = [];
                foreach ($selected_columns as $col) {
                    if ($col === 'coordinates') {
                        $data_row[] = ($row['start_lat'] && $row['start_long']) ? $row['start_lat'].", ".$row['start_long'] : 'ไม่ระบุ';
                    } elseif (in_array($col, ['budget_amount', 'distance', 'width', 'area'])) {
                        $data_row[] = number_format($row[$col], 2, '.', '');
                    } else {
                        $data_row[] = $row[$col];
                    }
                }
                fputcsv($output, $data_row);
            }
        }
        fclose($output);
        exit;
    }
}

include 'includes/header.php';
?>

<div class="max-w-5xl mx-auto pb-20 animate-in fade-in duration-500">
    <!-- Header -->
    <div class="flex items-center gap-5 mb-10 px-4">
        <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-[2rem] flex items-center justify-center shadow-inner">
            <i data-lucide="file-spreadsheet" size="32"></i>
        </div>
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight">ส่งออกข้อมูลโครงการ (Custom Export)</h2>
            <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px] mt-1">Export Data to Excel / CSV</p>
        </div>
    </div>

    <?php if(!empty($error_msg)): ?>
        <div class="mb-8 mx-4 bg-red-50 text-red-600 p-6 rounded-[2rem] border border-red-100 font-black flex items-center gap-3 animate-bounce">
            <i data-lucide="alert-circle"></i> <?= $error_msg ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-8 px-4">
        <input type="hidden" name="export_action" value="1">
        
        <!-- ส่วนที่ 1: ตัวกรองข้อมูล -->
        <div class="bg-white p-8 md:p-10 rounded-[3.5rem] shadow-sm border border-slate-100">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-2 border-l-4 border-blue-500 pl-3">
                1. กรองข้อมูลที่ต้องการ
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">อำเภอ</label>
                    <select name="district" class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">-- ทุกอำเภอ --</option>
                        <?php foreach($sisaket_districts as $d): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ปีงบประมาณ</label>
                    <select name="fiscal_year" class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">-- ทุกปี --</option>
                        <?php foreach($years as $y): ?>
                            <option value="<?= $y ?>"><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">สถานะโครงการ</label>
                    <select name="status" class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">-- ทุกสถานะ --</option>
                        <option value="รอดำเนินการ">รอดำเนินการ</option>
                        <option value="กำลังดำเนินการ">กำลังดำเนินการ</option>
                        <option value="เสร็จสิ้น">เสร็จสิ้น</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- ส่วนที่ 2: เลือกคอลัมน์ -->
        <div class="bg-white p-8 md:p-10 rounded-[3.5rem] shadow-sm border border-slate-100">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2 border-l-4 border-orange-500 pl-3">
                    2. เลือกคอลัมน์ที่จะส่งออก
                </h3>
                <button type="button" onclick="toggleCheckboxes(true)" class="text-[10px] font-black text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-lg hover:bg-emerald-100 transition-colors">เลือกทั้งหมด</button>
            </div>
            
            <?php
            $columns_groups = [
                'ข้อมูลทั่วไป' => [
                    'id' => 'รหัสโครงการ (ID)',
                    'project_name' => 'ชื่อโครงการ',
                    'route_name' => 'ชื่อสายทาง',
                    'infrastructure_type' => 'ประเภทถนน',
                    'fiscal_year' => 'ปีงบประมาณ',
                    'district_name' => 'อำเภอ'
                ],
                'ข้อมูลวิศวกรรม' => [
                    'distance' => 'ความยาว (ม.)',
                    'width' => 'ความกว้าง (ม.)',
                    'area' => 'พื้นที่รวม (ตร.ม.)',
                    'coordinates' => 'พิกัด GPS (Lat, Lng)'
                ],
                'การบริหารโครงการ' => [
                    'budget_amount' => 'งบประมาณ (บาท)',
                    'budget_type' => 'ประเภทงบ',
                    'supervisor_name' => 'ผู้ควบคุมงาน',
                    'status' => 'สถานะปัจจุบัน'
                ]
            ];
            ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach($columns_groups as $group_name => $cols): ?>
                <div class="space-y-4">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest pb-2 border-b border-slate-100"><?= $group_name ?></h4>
                    <div class="space-y-3">
                        <?php foreach($cols as $val => $label): ?>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" name="columns[]" value="<?= $val ?>" checked class="col-checkbox w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 accent-emerald-600 cursor-pointer">
                            <span class="text-sm font-bold text-slate-700 group-hover:text-emerald-600 transition-colors"><?= $label ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="w-full bg-slate-900 hover:bg-emerald-600 text-white font-black py-7 rounded-[3rem] shadow-2xl transition-all active:scale-95 text-xl mt-4 uppercase tracking-[0.15em] flex items-center justify-center gap-3 group">
            <i data-lucide="download" class="group-hover:-translate-y-1 transition-transform"></i>
            ดาวน์โหลดไฟล์ CSV (Excel)
        </button>
    </form>
</div>

<script>
    function toggleCheckboxes(check) {
        document.querySelectorAll('.col-checkbox').forEach(cb => cb.checked = check);
    }
    window.onload = () => { if(typeof lucide !== 'undefined') lucide.createIcons(); };
</script>

<?php include 'includes/footer.php'; ?>