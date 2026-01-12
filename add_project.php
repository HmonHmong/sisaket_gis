<?php
// add_project.php
// ที่อยู่ไฟล์: /add_project.php
require_once 'auth_check.php';
require_once 'config/db.php';
// ตรวจสอบว่าไฟล์ includes/functions.php มีอยู่จริงก่อนเรียกใช้
if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
} else {
    // กรณีไม่มีไฟล์ ให้สร้างฟังก์ชันจำลองเพื่อป้องกัน Error
    function add_notification($c, $t, $m, $type) { return true; }
}

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();
    try {
        $project_name = $conn->real_escape_string($_POST['project_name']);
        $route_name = $conn->real_escape_string($_POST['route_name']);
        $fiscal_year = intval($_POST['fiscal_year']);
        $distance = floatval($_POST['distance']);
        $width = floatval($_POST['width']);
        $has_shoulder = isset($_POST['has_shoulder']) ? 1 : 0;
        $shoulder_width = floatval($_POST['shoulder_width']);
        $budget_amount = floatval($_POST['budget_amount']);
        $budget_type = $conn->real_escape_string($_POST['budget_type']);
        $start_lat = $_POST['start_lat'] !== "" ? floatval($_POST['start_lat']) : "NULL";
        $start_long = $_POST['start_long'] !== "" ? floatval($_POST['start_long']) : "NULL";
        $end_lat = $_POST['end_lat'] !== "" ? floatval($_POST['end_lat']) : "NULL";
        $end_long = $_POST['end_long'] !== "" ? floatval($_POST['end_long']) : "NULL";
        $created_by = $_SESSION['user_id'];

        $total_width = $width + ($has_shoulder ? $shoulder_width : 0);
        $area = $distance * $total_width;

        // 1. บันทึกลงตาราง projects
        $sql_project = "INSERT INTO projects (
            project_name, route_name, fiscal_year, distance, width, has_shoulder, 
            shoulder_width, area, budget_amount, budget_type, 
            start_lat, start_long, end_lat, end_long, created_by
        ) VALUES (
            '$project_name', '$route_name', $fiscal_year, $distance, $width, $has_shoulder, 
            $shoulder_width, $area, $budget_amount, '$budget_type', 
            $start_lat, $start_long, $end_lat, $end_long, $created_by
        )";

        if (!$conn->query($sql_project)) throw new Exception($conn->error);
        $project_id = $conn->insert_id;

        // 2. บันทึกสถานที่ก่อสร้าง (project_points)
        if (isset($_POST['villages'])) {
            foreach ($_POST['villages'] as $index => $village) {
                if (empty($village)) continue;
                $v_name = $conn->real_escape_string($village);
                $v_moo = $conn->real_escape_string($_POST['moos'][$index]);
                $v_sub = $conn->real_escape_string($_POST['sub_districts'][$index]);
                $v_dist = $conn->real_escape_string($_POST['districts'][$index]);
                $v_prov = $conn->real_escape_string($_POST['provinces'][$index]);
                
                $conn->query("INSERT INTO project_points (project_id, village, moo, sub_district, district, province, order_index) 
                              VALUES ($project_id, '$v_name', '$v_moo', '$v_sub', '$v_dist', '$v_prov', $index)");
            }
        }

        $conn->commit();

        // 3. แจ้งเตือนระบบอัตโนมัติ
        add_notification($conn, "เพิ่มโครงการใหม่", "โครงการ: $project_name สายทาง $route_name โดย " . $_SESSION['full_name'], 'project');

        $success_msg = "บันทึกข้อมูลโครงการและพิกัดเรียบร้อยแล้ว";
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="max-w-6xl mx-auto pb-20 animate-in slide-in-from-bottom-4 duration-500">
    <div class="bg-white rounded-[3.5rem] shadow-2xl border border-slate-100 overflow-hidden">
        <!-- Header Panel -->
        <div class="bg-slate-900 p-10 text-white flex items-center justify-between relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-orange-600/20 blur-3xl -mr-20 -mt-20"></div>
            <div class="flex items-center gap-5 relative z-10">
                <div class="w-16 h-16 bg-orange-600 rounded-[1.5rem] flex items-center justify-center shadow-2xl">
                    <i data-lucide="map-pin" size="32"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-white">เพิ่มโครงการและพิกัด GIS</h2>
                    <p class="text-slate-400 text-sm font-medium">ระบบบริหารจัดการข้อมูลโครงสร้างพื้นฐานสำนักช่าง</p>
                </div>
            </div>
        </div>

        <form method="POST" id="projectForm" class="p-8 md:p-12 space-y-12">
            <?php if($success_msg): ?>
                <div class="bg-emerald-50 text-emerald-600 p-6 rounded-[2rem] border border-emerald-100 flex items-center gap-3 font-bold animate-bounce shadow-sm">
                    <i data-lucide="check-circle" size="24"></i> <?= $success_msg ?>
                </div>
            <?php endif; ?>

            <!-- Section 1: Project Info -->
            <section class="space-y-8">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-8 h-px bg-slate-200"></span> 1. ข้อมูลโครงการหลัก
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อโครงการ</label>
                        <input type="text" name="project_name" required placeholder="เช่น งานก่อสร้างถนนคอนกรีต..."
                               class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl focus:border-orange-500 focus:bg-white outline-none transition-all font-bold">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อสายทาง (ดึงอัตโนมัติจากหมู่บ้าน)</label>
                        <input type="text" name="route_name" id="route_name" required readonly tabindex="-1"
                               class="w-full p-4 bg-orange-50 border-2 border-orange-100 rounded-2xl outline-none font-bold text-orange-700 cursor-not-allowed" placeholder="กรอกสถานที่ก่อสร้างเพื่อสรุปสายทาง...">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">งบประมาณดำเนินการ (บาท)</label>
                        <input type="number" step="0.01" name="budget_amount" required class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 font-bold text-orange-600">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ปีงบประมาณ</label>
                        <select name="fiscal_year" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 font-bold">
                            <?php for($y=2567; $y<=2570; $y++) echo "<option value='$y' ".($y==2568?'selected':'').">$y</option>"; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ประเภทงบประมาณ</label>
                        <select name="budget_type" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 font-bold">
                            <option>งบตามข้อบัญญัติ</option><option>งบกลาง</option><option>งบอุดหนุนเฉพาะกิจ</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Section 2: Construction Site -->
            <section class="space-y-6">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-8 h-px bg-slate-200"></span> 2. สถานที่ก่อสร้าง (หมู่บ้าน > ตำบล > อำเภอ > จังหวัด)
                </h3>
                
                <div id="points-list" class="space-y-6">
                    <!-- แถวเริ่มต้น: แก้ไขคลาสเพื่อให้ Smart Lookup ทำงานได้ทันที -->
                    <div class="point-row bg-white p-8 rounded-[2.5rem] relative border-2 border-slate-100 hover:border-orange-300 transition-all group shadow-sm">
                        <div class="absolute -left-3 top-10 w-8 h-8 bg-slate-900 text-white text-xs flex items-center justify-center rounded-xl font-black shadow-xl z-20">1</div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div class="space-y-2">
                                <label class="text-[9px] font-black text-orange-600 uppercase tracking-widest ml-1">หมู่บ้าน/สถานที่</label>
                                <input type="text" name="villages[]" oninput="updateRouteName()" required placeholder="ชื่อหมู่บ้าน" class="village-input w-full p-3 bg-slate-50 rounded-xl border-none text-sm font-bold outline-none focus:ring-2 focus:ring-orange-500">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">หมู่ที่</label>
                                <input type="text" name="moos[]" placeholder="ม." class="w-full p-3 bg-slate-50 rounded-xl border-none text-sm font-bold outline-none focus:ring-2 focus:ring-orange-500">
                            </div>
                            <div class="space-y-2 relative">
                                <label class="text-[9px] font-black text-blue-600 uppercase tracking-widest ml-1">ตำบล (ค้นหา)</label>
                                <input type="text" name="sub_districts[]" oninput="smartLookup(this)" placeholder="พิมพ์ชื่อตำบล..." class="sub-district-input w-full p-3 bg-slate-50 rounded-xl border-none text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500" list="tambon-list">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">อำเภอ</label>
                                <input type="text" name="districts[]" placeholder="ระบุอำเภอ" class="district-input w-full p-3 bg-slate-50 rounded-xl border-none text-sm font-bold outline-none focus:ring-2 focus:ring-orange-500" list="district-list">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">จังหวัด</label>
                                <input type="text" name="provinces[]" placeholder="ระบุจังหวัด" class="province-input w-full p-3 bg-slate-50 rounded-xl border-none text-sm font-bold outline-none focus:ring-2 focus:ring-orange-500" list="province-list">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="addPoint()" class="w-full py-5 border-2 border-dashed border-slate-200 rounded-[2rem] text-slate-400 font-black text-sm hover:border-orange-400 hover:text-orange-600 transition-all flex items-center justify-center gap-2 group">
                    <i data-lucide="plus-circle" class="group-hover:scale-110 transition-transform"></i> เพิ่มจุดสถานที่ก่อสร้างในสายทาง
                </button>
            </section>

            <!-- Section 3: GIS Points -->
            <section class="space-y-6">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-8 h-px bg-slate-200"></span> 3. พิกัดที่ตั้งโครงการ (Lat/Long)
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 bg-slate-900 p-10 rounded-[3.5rem] shadow-2xl relative">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3"><div class="w-3 h-3 bg-orange-500 rounded-full animate-pulse"></div><p class="text-white text-[10px] font-black uppercase">START POINT</p></div>
                        <div class="grid grid-cols-2 gap-4">
                            <input type="text" name="start_lat" placeholder="Latitude" class="bg-white/10 border-none rounded-2xl p-4 text-white font-mono text-sm outline-none focus:bg-white/20 transition-all">
                            <input type="text" name="start_long" placeholder="Longitude" class="bg-white/10 border-none rounded-2xl p-4 text-white font-mono text-sm outline-none focus:bg-white/20 transition-all">
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center gap-3"><div class="w-3 h-3 bg-blue-400 rounded-full"></div><p class="text-white text-[10px] font-black uppercase">END POINT</p></div>
                        <div class="grid grid-cols-2 gap-4">
                            <input type="text" name="end_lat" placeholder="Latitude" class="bg-white/10 border-none rounded-2xl p-4 text-white font-mono text-sm outline-none focus:bg-white/20 transition-all">
                            <input type="text" name="end_long" placeholder="Longitude" class="bg-white/10 border-none rounded-2xl p-4 text-white font-mono text-sm outline-none focus:bg-white/20 transition-all">
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section 4: Engineering Detail -->
            <section class="space-y-6">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-8 h-px bg-slate-200"></span> 4. รายละเอียดถนน
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ระยะทาง (ม.)</label>
                        <input type="number" step="0.01" name="distance" required class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 font-black">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ความกว้างผิวทาง (ม.)</label>
                        <input type="number" step="0.01" name="width" required class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 font-black">
                    </div>
                    <div class="flex flex-col justify-center items-center">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">มีไหล่ทาง</label>
                        <input type="checkbox" name="has_shoulder" class="w-8 h-8 rounded-xl text-orange-600 border-slate-200">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">กว้างไหล่ทาง (ม.)</label>
                        <input type="number" step="0.01" name="shoulder_width" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 font-black">
                    </div>
                </div>
            </section>

            <!-- Actions -->
            <div class="pt-10 flex flex-col md:flex-row gap-4 border-t border-slate-50">
                <button type="submit" class="flex-1 bg-orange-600 hover:bg-slate-900 text-white font-black py-6 rounded-[2.5rem] shadow-2xl transition-all active:scale-95 text-xl uppercase tracking-widest flex items-center justify-center gap-3">
                    <i data-lucide="save" size="24"></i> บันทึกข้อมูล
                </button>
                <a href="projects.php" class="px-12 py-6 bg-slate-100 text-slate-500 font-black rounded-[2.5rem] hover:bg-slate-200 transition-all text-center flex items-center justify-center">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

<!-- Datalists for Smart Lookup -->
<datalist id="tambon-list"></datalist>
<datalist id="district-list">
    <option value="เมืองศรีสะเกษ"><option value="กันทรลักษณ์"><option value="ขุขันธ์"><option value="กันทรารมย์">
    <option value="ขุนหาญ"><option value="ราษีไศล"><option value="อุทุมพรพิสัย">
</datalist>
<datalist id="province-list">
    <option value="ศรีสะเกษ"><option value="สุรินทร์"><option value="อุบลราชธานี"><option value="ยโสธร">
</datalist>

<script>
    // ฐานข้อมูลพื้นที่อ้างอิง
    const masterLocations = [
        { t: "หนองครก", a: "เมืองศรีสะเกษ", p: "ศรีสะเกษ" },
        { t: "เมืองใต้", a: "เมืองศรีสะเกษ", p: "ศรีสะเกษ" },
        { t: "โพธิ์", a: "เมืองศรีสะเกษ", p: "ศรีสะเกษ" },
        { t: "หญ้าปล้อง", a: "เมืองศรีสะเกษ", p: "ศรีสะเกษ" },
        { t: "น้ำอ้อม", a: "กันทรลักษณ์", p: "ศรีสะเกษ" },
        { t: "กระแชง", a: "กันทรลักษณ์", p: "ศรีสะเกษ" },
        { t: "ห้วยเหนือ", a: "ขุขันธ์", p: "ศรีสะเกษ" },
        { t: "สะพุง", a: "ศรีรัตนะ", p: "ศรีสะเกษ" },
        { t: "ดูน", a: "กันทรารมย์", p: "ศรีสะเกษ" },
        { t: "โนนคูณ", a: "โนนคูณ", p: "ศรีสะเกษ" },
        { t: "ห้วยทับทัน", a: "ห้วยทับทัน", p: "ศรีสะเกษ" },
        { t: "กำแพง", a: "อุทุมพรพิสัย", p: "ศรีสะเกษ" },
        { t: "พะทาย", a: "ภูมิซรอล", p: "ศรีสะเกษ" }
    ];

    window.onload = () => {
        const list = document.getElementById('tambon-list');
        masterLocations.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.t;
            opt.textContent = `อำเภอ${item.a}, จังหวัด${item.p}`;
            list.appendChild(opt);
        });
    };

    // ค้นหาตำบลและเติมอำเภอ/จังหวัดอัตโนมัติ
    function smartLookup(input) {
        const tambonName = input.value.trim();
        const row = input.closest('.point-row');
        const distInput = row.querySelector('.district-input');
        const provInput = row.querySelector('.province-input');
        
        const match = masterLocations.find(loc => loc.t === tambonName);
        if (match) {
            distInput.value = match.a;
            provInput.value = match.p;
        }
    }

    // สร้างชื่อสายทางอัตโนมัติ
    function updateRouteName() {
        const inputs = document.querySelectorAll('.village-input');
        const names = Array.from(inputs).map(i => i.value.trim()).filter(n => n !== "");
        document.getElementById('route_name').value = names.join(" - ");
    }

    // เพิ่มจุดสถานที่ก่อสร้างใหม่
    function addPoint() {
        const list = document.getElementById('points-list');
        const count = list.children.length + 1;
        const newRow = document.createElement('div');
        newRow.className = "point-row bg-white p-8 rounded-[2.5rem] relative border-2 border-slate-100 hover:border-orange-300 transition-all group shadow-sm animate-in slide-in-from-left-4";
        newRow.innerHTML = `
            <div class="absolute -left-3 top-10 w-8 h-8 bg-slate-900 text-white text-xs flex items-center justify-center rounded-xl font-black shadow-xl z-20">${count}</div>
            <button type="button" onclick="this.parentElement.remove(); updateRouteName();" class="absolute -right-2 -top-2 bg-red-100 text-red-600 p-2 rounded-full hover:bg-red-600 hover:text-white transition-colors opacity-0 group-hover:opacity-100 shadow-lg z-20"><i data-lucide="x" size="14"></i></button>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="space-y-2"><label class="text-[9px] font-black text-orange-600 uppercase tracking-widest ml-1">หมู่บ้าน/สถานที่</label><input type="text" name="villages[]" oninput="updateRouteName()" required placeholder="ชื่อหมู่บ้าน" class="village-input w-full p-3 bg-slate-50 rounded-xl border-none text-sm font-bold outline-none focus:ring-2 focus:ring-orange-500"></div>
                <div class="space-y-2"><label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">หมู่ที่</label><input type="text" name="moos[]" placeholder="ม." class="w-full p-3 bg-slate-50 rounded-xl border-none text-sm font-bold outline-none focus:ring-2 focus:ring-orange-500"></div>
                <div class="space-y-2 relative"><label class="text-[9px] font-black text-blue-600 uppercase tracking-widest ml-1">ตำบล (ค้นหา)</label><input type="text" name="sub_districts[]" oninput="smartLookup(this)" placeholder="พิมพ์ตำบล..." class="sub-district-input w-full p-3 bg-slate-50 rounded-xl border-none text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500" list="tambon-list"></div>
                <div class="space-y-2"><label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">อำเภอ</label><input type="text" name="districts[]" placeholder="ระบุอำเภอ" class="district-input w-full p-3 bg-slate-50 rounded-xl border-none text-sm font-bold outline-none focus:ring-2 focus:ring-orange-500" list="district-list"></div>
                <div class="space-y-2"><label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">จังหวัด</label><input type="text" name="provinces[]" placeholder="ระบุจังหวัด" class="province-input w-full p-3 bg-slate-50 rounded-xl border-none text-sm font-bold outline-none focus:ring-2 focus:ring-orange-500" list="province-list"></div>
            </div>
        `;
        list.appendChild(newRow);
        lucide.createIcons();
    }
</script>

<?php include 'includes/footer.php'; ?>