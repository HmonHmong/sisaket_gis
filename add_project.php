<?php
// add_project.php - ระบบเพิ่มโครงการ (อัปเดตตำบลอำเภอราษีไศลให้ถูกต้อง 100% ตามคำขอ)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$success_msg = "";
$error_msg = "";

// ข้อมูลอำเภอและตำบลของจังหวัดศรีสะเกษ อัปเดตล่าสุดครบทั้ง 22 อำเภอ
$sisaket_full_data = [
    "เมืองศรีสะเกษ" => ["เมืองเหนือ", "เมืองใต้", "คูซอด", "ซำ", "จาน", "ตะดอบ", "หนองครก", "โพนข่า", "โพธิ์", "หญ้าปล้อง", "ทุ่ม", "หนองไฮ", "หนองแก้ว", "น้ำคำ", "หน้าเมือง", "หนองไผ่", "โพนค้อ", "โพนเขวา"],
    "ยางชุมน้อย" => ["ยางชุมน้อย", "ลิ้นฟ้า", "คอนกาม", "โนนคูณ", "กุดเมืองฮาม", "บึงบอน", "ยางชุมใหญ่"],
    "กันทรารมย์" => ["ดูน", "โนนสัง", "หนองหัวช้าง", "ยาง", "ละทาย", "คำเนียม", "จาน", "อีปาด", "บัวน้อย", "หนองบัว", "ดู่", "ผักแพว", "จานใหญ่", "คำน้อย", "เมืองน้อย", "พะเนา"],
    "กันทรลักษณ์" => ["ดูน", "สังเม็ก", "น้ำอ้อม", "ละลาย", "เมือง", "ขนุน", "สวนกล้วย", "ตระกาจ", "วิรักษ์", "กุดเสลา", "จานใหญ่", "บึงมะลู", "รุง", "ทุ่งใหญ่", "ภูเงิน", "กระแชง", "หนองหญ้าลาด", "เสาธงชัย", "โพนทอง", "ภูผาหมอก"],
    "ขุขันธ์" => ["ห้วยเหนือ", "ห้วยใต้", "สะเดาใหญ่", "ตาอุด", "นิคมพัฒนา", "ศรีสะอาด", "ลมศักดิ์", "หนองฉลอง", "ปราสาท", "จะกง", "ดินแดง", "ดองกำเม็ด", "โสน", "ปรือใหญ่", "ตะเคียน", "กันทรารมย์", "โคกเพชร", "โนนสำราญ", "กฤษณา", "สำโรงตาเจ็น", "ห้วยสำราญ", "นาเหม้า"],
    "ไพรบึง" => ["ไพรบึง", "ดินแดง", "ปราสาท", "สำโรงพลัน", "สุขสวัสดิ์", "โนนปูน"],
    "ปรางค์กู่" => ["พิมาย", "กู่", "หนองเชียงทูน", "ตูม", "สมอ", "โพธิ์ศรี", "สำโรงระวี", "ดู่", "ระแบง", "สวาย"],
    "ขุนหาญ" => ["สิ", "บักดอง", "พราน", "โพธิ์วงศ์", "ไพร", "กระหวัน", "ขุนหาญ", "โนนสูง", "ห้วยจันทร์", "กันทรอม", "ภูผาหมอก", "พอกพูน"],
    "ราษีไศล" => ["เมืองคง", "บัวหุ่ง", "ส้มป่อย", "สร้างปี่", "เมืองแคน", "หว้านคำ", "หนองแค", "หนองหมี", "จิกสังข์ทอง", "ไผ่", "ด่าน", "หนองอึ่ง", "ดู่"],
    "อุทุมพรพิสัย" => ["กำแพง", "อุทุมพร", "ขะยูง", "โคกจาน", "โพธิ์ชัย", "สำโรง", "แขม", "หนองห้าง", "รังแร้ง", "แต้", "ปะอาว", "หนองไฮ", "สระกำแพงใหญ่", "ทุ่งไชย", "ตาเกษ", "หัวช้าง", "โคกหล่อ", "ปราสาทกู่", "หนองอึ่ง"],
    "บึงบูรพ์" => ["บึงบูรพ์", "เป๊าะ"],
    "ห้วยทับทัน" => ["ห้วยทับทัน", "เมืองหลวง", "กล้วยกว้าง", "ผักไหม", "ปราสาท", "จานแสนไชย"],
    "โนนคูณ" => ["โนนคูณ", "บก", "โพธิ์", "หนองกุง", "เหล่ากวาง"],
    "ศรีรัตนะ" => ["ศรีแก้ว", "พิงพวย", "สระเยาว์", "ตูม", "สะพุง", "ศรีโนนงาม", "พรหมสวัสดิ์"],
    "น้ำเกลี้ยง" => ["น้ำเกลี้ยง", "ละเอาะ", "ตองปิด", "เขิน", "รุ่งระวี", "คูบ"],
    "วังหิน" => ["บุสูง", "ธาตุ", "ดวนใหญ่", "บ่อแก้ว", "ศรีสำราญ", "โพนยาง", "ทุ่งสว่าง", "วังหว้า"],
    "ภูสิงห์" => ["โคกตาล", "ห้วยตึ๊กชู", "ห้วยตามอญ", "ตะเคียนราม", "ดงรัก", "ไพรพัฒนา", "ละลม"],
    "เมืองจันทร์" => ["เมืองจันทร์", "ตาโกน", "หนองใหญ่"],
    "เบญจลักษณ์" => ["เสียว", "หนองฮาง", "หนองงูเหลือม", "เมืองน้อย", "ท่าคล้อ"],
    "พยุห์" => ["พยุห์", "พรหมสวัสดิ์", "ตำแย", "โนนเพ็ก", "หนองค้า"],
    "โพธิ์ศรีสุวรรณ" => ["โดด", "เสียว", "หนองม้า", "ผือใหญ่", "อีเซ"],
    "ศิลาลาด" => ["กุง", "หนองอึ่ง", "คลีกลิ้ง", "โจดม่วง"]
];

$nearby_provinces = ["ศรีสะเกษ", "สุรินทร์", "ร้อยเอ็ด", "ยโสธร", "อุบลราชธานี"];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();
    try {
        $p_name = $_POST['project_name'];
        $r_name = $_POST['route_name'] ?? '';
        $p_type = $_POST['infrastructure_type'];
        $p_year = intval($_POST['fiscal_year']);
        $district_main = $_POST['districts'][0] ?? 'ไม่ระบุ';
        $budget = floatval($_POST['budget_amount']);
        $b_type = $_POST['budget_type'];
        $supervisor = $_POST['supervisor_name'];
        $status = $_POST['status'];
        $status_remark = ($status === 'มีการเปลี่ยนแปลงหรือแก้ไข') ? $_POST['status_remark'] : null;
        
        $width = floatval($_POST['width_m'] ?? 0);
        $distance = floatval($_POST['distance_m'] ?? 0);
        $shoulder = floatval($_POST['shoulder_m'] ?? 0);
        // สูตรคำนวณพื้นที่งานทาง: (ความกว้าง + (ไหล่ทางข้างละ * 2)) * ความยาว
        $area = ($width + ($shoulder * 2)) * $distance; 

        $s_lat = !empty($_POST['start_lat']) ? floatval($_POST['start_lat']) : null;
        $s_lng = !empty($_POST['start_long']) ? floatval($_POST['start_long']) : null;
        $e_lat = !empty($_POST['end_lat']) ? floatval($_POST['end_lat']) : null;
        $e_lng = !empty($_POST['end_long']) ? floatval($_POST['end_long']) : null;

        $sql = "INSERT INTO projects (project_name, route_name, infrastructure_type, fiscal_year, district_name, budget_amount, budget_type, supervisor_name, status, status_remark, width, distance, shoulder_width, area, start_lat, start_long, end_lat, end_long, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssisdssssddddddddi", $p_name, $r_name, $p_type, $p_year, $district_main, $budget, $b_type, $supervisor, $status, $status_remark, $width, $distance, $shoulder, $area, $s_lat, $s_lng, $e_lat, $e_lng, $_SESSION['user_id']);
        
        if (!$stmt->execute()) throw new Exception($conn->error);
        $project_id = $conn->insert_id;

        if (isset($_POST['villages'])) {
            $stmt_pt = $conn->prepare("INSERT INTO project_points (project_id, village, moo, sub_district, district, province, order_index) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($_POST['villages'] as $idx => $v_name) {
                if (empty($v_name)) continue;
                $stmt_pt->bind_param("isssssi", $project_id, $v_name, $_POST['moos'][$idx], $_POST['sub_districts'][$idx], $_POST['districts'][$idx], $_POST['provinces'][$idx], $idx);
                $stmt_pt->execute();
            }
        }

        $conn->commit();
        $success_msg = "บันทึกโครงการเรียบร้อยแล้ว";
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="max-w-6xl mx-auto pb-20 animate-in fade-in duration-500">
    <div class="bg-white rounded-[3.5rem] shadow-2xl border border-slate-100 overflow-hidden">
        <!-- Header Section -->
        <div class="bg-slate-900 p-10 text-white text-center relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-10 -mr-10 -mt-10"><i data-lucide="map-pin" size="200"></i></div>
            <h2 class="text-3xl font-black tracking-tight uppercase relative z-10">เพิ่มโครงการและพิกัดที่ตั้ง</h2>
            <p class="text-orange-500 text-sm font-bold uppercase tracking-widest mt-1 relative z-10">Si Sa Ket PAO Infrastructure Entry</p>
        </div>

        <form method="POST" id="projectForm" class="p-8 md:p-12 space-y-12">
            <?php if($success_msg): ?>
                <div class="bg-emerald-50 text-emerald-600 p-6 rounded-2xl border border-emerald-100 font-bold animate-bounce flex items-center gap-3">
                    <i data-lucide="check-circle" size="24"></i> <?= $success_msg ?>
                </div>
            <?php endif; ?>
            
            <!-- ส่วนที่ 1: ข้อมูลโครงการ -->
            <div class="space-y-6">
                <h3 class="text-lg font-black text-slate-800 flex items-center gap-3 border-l-4 border-slate-900 pl-4 uppercase">1. ข้อมูลโครงการ</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="text" name="project_name" required placeholder="ชื่อโครงการ" class="p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-bold outline-none focus:border-orange-500 transition-all">
                        <input type="text" name="route_name" id="route_name_display" readonly class="p-4 bg-slate-100 border-2 border-slate-100 rounded-2xl font-black text-orange-600 outline-none" placeholder="ชื่อสายทาง (อัตโนมัติ)">
                    </div>
                    <div class="w-full md:w-1/2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 text-slate-700">ประเภทโครงสร้าง</label>
                        <select name="infrastructure_type" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-bold mt-1 outline-none focus:border-orange-500 transition-all text-slate-700">
                            <option value="ถนนลูกรัง">ถนนลูกรัง</option>
                            <option value="ถนนหินคลุก">ถนนหินคลุก</option>
                            <option value="ถนนคอนกรีต">ถนนคอนกรีต</option>
                            <option value="ถนนลาดยาง (Tack Coat)">ถนนลาดยาง (Tack Coat)</option>
                            <option value="ถนนลาดยาง (Recycling)">ถนนลาดยาง (Recycling)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ส่วนที่ 2: สถานที่ก่อสร้าง -->
            <div class="space-y-6 pt-10 border-t border-slate-50">
                <h3 class="text-lg font-black text-slate-800 flex items-center gap-3 border-l-4 border-blue-600 pl-4 uppercase">2. สถานที่ก่อสร้าง</h3>
                <div id="pointsContainer" class="space-y-4">
                    <div class="point-row bg-slate-50 p-6 rounded-[2rem] border border-slate-100 relative group text-slate-700">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                            <select name="provinces[]" class="province-select p-3 rounded-xl border-none font-bold text-sm shadow-sm bg-white">
                                <?php foreach($nearby_provinces as $p): ?>
                                    <option value="<?= $p ?>"><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="districts[]" onchange="handleDistrictChange(this)" class="district-select p-3 rounded-xl border-none font-bold text-sm shadow-sm bg-white">
                                <option value="">เลือกอำเภอ</option>
                                <?php 
                                    $sorted_districts = array_keys($sisaket_full_data);
                                    sort($sorted_districts);
                                    foreach($sorted_districts as $d): 
                                ?>
                                    <option value="<?= $d ?>"><?= $d ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="sub_districts[]" class="sub-district-select p-3 rounded-xl border-none font-bold text-sm shadow-sm bg-white">
                                <option value="">เลือกตำบล</option>
                            </select>
                            <input type="text" name="moos[]" class="p-3 rounded-xl border-none text-sm text-center shadow-sm bg-white" placeholder="ม.">
                            <input type="text" name="villages[]" oninput="updateRouteName()" placeholder="หมู่บ้าน" class="v-input p-3 rounded-xl border-none font-bold text-sm shadow-sm bg-white">
                        </div>
                    </div>
                </div>
                <div class="flex justify-center">
                    <button type="button" onclick="addPointRow()" class="bg-slate-900 text-white px-8 py-3 rounded-2xl text-xs font-black hover:bg-blue-600 transition-all shadow-xl flex items-center gap-2 group">
                        <i data-lucide="plus-circle" size="16" class="group-hover:rotate-90 transition-transform"></i> เพิ่มจุดพิกัด/หมู่บ้าน ต่อไป
                    </button>
                </div>
            </div>

            <!-- ส่วนที่ 3: ข้อมูลถนน -->
            <div class="space-y-6 pt-10 border-t border-slate-50">
                <h3 class="text-lg font-black text-slate-800 flex items-center gap-3 border-l-4 border-emerald-600 pl-4 uppercase">3. ข้อมูลถนน</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-emerald-50/50 p-6 rounded-[2.5rem] border border-emerald-100 text-slate-700">
                    <div class="space-y-1 text-center">
                        <label class="text-[10px] font-black text-slate-400 uppercase">ความกว้างถนน (ม.)</label>
                        <input type="number" step="0.01" name="width_m" id="width_m" oninput="calculateArea()" 
                               onfocus="clearInitialZero(this)" onblur="restoreInitialZero(this)" value="0"
                               class="w-full p-4 rounded-2xl border-none font-bold text-center shadow-sm text-slate-700">
                    </div>
                    <div class="space-y-1 text-center">
                        <label class="text-[10px] font-black text-slate-400 uppercase">ความยาวถนน (ม.)</label>
                        <input type="number" step="0.01" name="distance_m" id="distance_m" oninput="calculateArea()" 
                               onfocus="clearInitialZero(this)" onblur="restoreInitialZero(this)" value="0"
                               class="w-full p-4 rounded-2xl border-none font-bold text-center shadow-sm text-slate-700">
                    </div>
                    <div class="space-y-1 text-center">
                        <label class="text-[10px] font-black text-slate-400 uppercase">ไหล่ทางข้างละ (ม.)</label>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="has_shoulder" onchange="toggleShoulder()" class="w-6 h-6 accent-emerald-600">
                            <input type="number" step="0.01" name="shoulder_m" id="shoulder_m" disabled oninput="calculateArea()" 
                                   onfocus="clearInitialZero(this)" onblur="restoreInitialZero(this)" value="0"
                                   class="w-full p-4 rounded-2xl border-none font-bold text-center opacity-50 shadow-sm transition-all text-slate-700">
                        </div>
                    </div>
                    <div class="bg-emerald-600 rounded-[2rem] p-4 text-white text-center flex flex-col justify-center shadow-lg">
                        <label class="text-[9px] font-black uppercase opacity-70">พื้นที่รวม (ตร.ม.)</label>
                        <input type="text" id="total_area" readonly value="0.00" class="w-full bg-transparent border-none text-2xl font-black text-center text-white outline-none">
                    </div>
                </div>
            </div>

            <!-- ส่วนที่ 4: รายละเอียดงบประมาณ -->
            <div class="space-y-6 pt-10 border-t border-slate-50">
                <h3 class="text-lg font-black text-slate-800 flex items-center gap-3 border-l-4 border-orange-600 pl-4 uppercase">4. รายละเอียดงบประมาณ</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 text-slate-700">งบประมาณดำเนินการ (บาท)</label>
                        <input type="number" step="0.01" name="budget_amount" required value="0"
                               onfocus="clearInitialZero(this)" onblur="restoreInitialZero(this)"
                               class="p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-black text-orange-600 text-xl w-full outline-none focus:border-orange-500 shadow-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 text-slate-700">ประเภทงบประมาณ</label>
                        <select name="budget_type" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-bold outline-none focus:border-orange-500 text-slate-700 text-sm">
                            <option value="งบตามข้อบัญญัติ">งบตามข้อบัญญัติ</option>
                            <option value="งบเงินอุดหนุนเฉพาะกิจ">งบเงินอุดหนุนเฉพาะกิจ</option>
                            <option value="งบกลาง">งบกลาง</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 text-center block text-slate-700">ปีงบประมาณ</label>
                        <input type="number" name="fiscal_year" value="<?= date('Y')+543 ?>" class="p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-bold w-full text-center outline-none focus:border-orange-500 shadow-sm text-slate-700">
                    </div>
                </div>
            </div>

            <!-- ส่วนที่ 5: GIS & สถานะ -->
            <div class="space-y-6 pt-10 border-t border-slate-50">
                <h3 class="text-lg font-black text-slate-800 flex items-center gap-3 border-l-4 border-slate-400 pl-4 uppercase">5. พิกัด GIS และสถานะโครงการ</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div class="bg-slate-900 p-8 rounded-[3rem] text-white space-y-4 shadow-xl">
                        <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest">จุดเริ่มต้น - สิ้นสุด (Lat/Lng)</p>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" name="start_lat" placeholder="Lat Start" class="p-3 bg-white/10 rounded-xl text-white border-none text-xs">
                            <input type="text" name="start_long" placeholder="Lng Start" class="p-3 bg-white/10 rounded-xl text-white border-none text-xs">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" name="end_lat" placeholder="Lat End" class="p-3 bg-white/10 rounded-xl text-white border-none text-xs">
                            <input type="text" name="end_long" placeholder="Lng End" class="p-3 bg-white/10 rounded-xl text-white border-none text-xs">
                        </div>
                    </div>
                    <div class="bg-slate-50 p-8 rounded-[3rem] border-4 border-dashed border-slate-200 flex flex-col items-center justify-center text-center group hover:border-orange-200 transition-all cursor-pointer shadow-inner">
                        <i data-lucide="upload-cloud" class="text-slate-300 mb-2 group-hover:text-orange-400 transition-colors" size="32"></i>
                        <p class="text-[10px] font-black text-slate-400 uppercase group-hover:text-slate-600">แนบไฟล์รูปภาพ/เอกสารโครงการ</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 text-slate-700">ช่างผู้ควบคุมงาน</label>
                    <input type="text" name="supervisor_name" placeholder="ระบุชื่อช่างผู้ควบคุมงาน" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-bold outline-none focus:border-orange-500 shadow-sm text-slate-700">
                </div>

                <div class="pt-6 space-y-4 text-slate-700">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">สถานะโครงการปัจจุบัน</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <?php 
                        $st_list = ['รอดำเนินการ', 'กำลังดำเนินการ', 'เสร็จสิ้น', 'มีการเปลี่ยนแปลงหรือแก้ไข'];
                        foreach($st_list as $st): 
                        ?>
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="status" value="<?= $st ?>" <?= $st == 'รอดำเนินการ' ? 'checked' : '' ?> onclick="toggleRemark(<?= $st === 'มีการเปลี่ยนแปลงหรือแก้ไข' ? 'true' : 'false' ?>)" class="peer sr-only">
                            <div class="p-4 bg-white border-2 border-slate-100 rounded-2xl text-center peer-checked:border-<?= $st=='มีการเปลี่ยนแปลงหรือแก้ไข' ? 'rose' : ($st=='เสร็จสิ้น' ? 'emerald' : 'slate') ?>-600 peer-checked:bg-<?= $st=='มีการเปลี่ยนแปลงหรือแก้ไข' ? 'rose' : ($st=='เสร็จสิ้น' ? 'emerald' : 'slate') ?>-600 peer-checked:text-white transition-all shadow-sm">
                                <p class="text-[10px] font-black uppercase"><?= $st ?></p>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div id="remarkContainer" class="hidden animate-in slide-in-from-top-2">
                        <textarea name="status_remark" placeholder="กรุณาระบุหมายเหตุการเปลี่ยนแปลงหรือแก้ไขโครงการ..." class="w-full p-5 bg-rose-50 border-2 border-rose-100 rounded-[2rem] font-bold text-slate-700 outline-none focus:border-rose-500 min-h-[120px] shadow-sm"></textarea>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-orange-600 hover:bg-slate-900 text-white font-black py-7 rounded-[2.5rem] shadow-2xl transition-all active:scale-95 text-xl mt-10 uppercase tracking-widest">
                ยืนยันการบันทึกข้อมูลโครงการ
            </button>
        </form>
    </div>
</div>

<script>
    const sisaketData = <?= json_encode($sisaket_full_data) ?>;

    function handleDistrictChange(select) {
        const row = select.closest('.point-row');
        const subSelect = row.querySelector('.sub-district-select');
        const dist = select.value;
        if (sisaketData[dist]) {
            let html = '<option value="">เลือกตำบล</option>';
            // เรียงลำดับชื่อตำบลเพื่อความสะดวกในการหา (ยกเว้นราษีไศลที่ต้องการลำดับตามที่คุณแจ้ง)
            if(dist === "ราษีไศล") {
                sisaketData[dist].forEach(s => html += `<option value="${s}">${s}</option>`);
            } else {
                [...sisaketData[dist]].sort((a, b) => a.localeCompare(b, 'th')).forEach(s => {
                    html += `<option value="${s}">${s}</option>`;
                });
            }
            subSelect.innerHTML = html;
        } else {
            subSelect.innerHTML = '<option value="">เลือกตำบล</option>';
        }
    }

    function addPointRow() {
        const container = document.getElementById('pointsContainer');
        const firstRow = document.querySelector('.point-row');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('input').forEach(i => i.value = '');
        newRow.querySelector('.sub-district-select').innerHTML = '<option value="">เลือกตำบล</option>';
        
        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'absolute -right-2 -top-2 bg-red-500 text-white w-7 h-7 rounded-full flex items-center justify-center shadow-lg hover:bg-slate-800 transition-all';
        deleteBtn.innerHTML = '<i data-lucide="x" size="14"></i>';
        deleteBtn.onclick = () => { newRow.remove(); updateRouteName(); };
        newRow.appendChild(deleteBtn);
        
        container.appendChild(newRow);
        lucide.createIcons();
    }

    function updateRouteName() {
        const inputs = document.querySelectorAll('.v-input');
        const names = [];
        inputs.forEach(i => {
            if(i.value.trim()){
                let v = i.value.trim();
                names.push(v.startsWith("บ.") ? v : "บ." + v);
            }
        });
        document.getElementById('route_name_display').value = names.join(" - ");
    }

    function calculateArea() {
        const w = parseFloat(document.getElementById('width_m').value) || 0;
        const l = parseFloat(document.getElementById('distance_m').value) || 0;
        const s = parseFloat(document.getElementById('shoulder_m').value) || 0;
        // สูตรพื้นที่รวมไหล่ทาง 2 ข้าง: (ความกว้าง + (ไหล่ทางข้างละ * 2)) * ความยาว
        const area = (w + (s * 2)) * l;
        document.getElementById('total_area').value = area.toLocaleString(undefined, {minimumFractionDigits: 2});
    }

    function clearInitialZero(input) {
        if (input.value == "0") {
            input.value = "";
        }
    }

    function restoreInitialZero(input) {
        if (input.value == "") {
            input.value = "0";
            calculateArea();
        }
    }

    function toggleShoulder() {
        const check = document.getElementById('has_shoulder');
        const input = document.getElementById('shoulder_m');
        input.disabled = !check.checked;
        input.classList.toggle('opacity-50', !check.checked);
        if(!check.checked) {
            input.value = 0;
        } else if (input.value == "0") {
            input.value = "";
            input.focus();
        }
        calculateArea();
    }

    function toggleRemark(show) {
        const container = document.getElementById('remarkContainer');
        container.classList.toggle('hidden', !show);
        if(show) container.querySelector('textarea').focus();
    }

    window.onload = () => { if(typeof lucide !== 'undefined') lucide.createIcons(); };
</script>

<?php include 'includes/footer.php'; ?>