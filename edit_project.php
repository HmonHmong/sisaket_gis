<?php
// edit_project.php - ระบบแก้ไขโครงการ (เพิ่ม Map Picker ปักหมุดพิกัด และแก้บั๊ก $sd)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) { header("Location: projects.php"); exit; }
$id = intval($_GET['id']);

$success_msg = "";
$error_msg = "";

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

// แก้บั๊ก $sd: สร้างตัวแปรล่วงหน้าให้พร้อมใช้ทุกเงื่อนไข
$sorted_districts = array_keys($sisaket_full_data);
sort($sorted_districts);

$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
if (!$project) { header("Location: projects.php"); exit; }

$attachments_res = $conn->query("SELECT * FROM project_attachments WHERE project_id = $id");
$points_res = $conn->query("SELECT * FROM project_points WHERE project_id = $id ORDER BY order_index ASC");
$existing_points = [];
while($pt = $points_res->fetch_assoc()) { $existing_points[] = $pt; }

// --- ⭐️ เพิ่มการดึงประเภทงานจากฐานข้อมูล (Dynamic Infrastructure Types) ⭐️ ---
$types_db_res = $conn->query("SELECT type_name FROM infrastructure_types ORDER BY category ASC, type_name ASC");
$dynamic_types = [];
while($tr = $types_db_res->fetch_assoc()) { $dynamic_types[] = $tr['type_name']; }
// หากข้อมูลเดิมไม่มีในฐานข้อมูล (โปรเจกต์เก่ามาก) ให้แทรกเข้าไปชั่วคราวเพื่อไม่ให้แสดงผลผิดพลาด
if ($project['infrastructure_type'] && !in_array($project['infrastructure_type'], $dynamic_types)) {
    array_unshift($dynamic_types, $project['infrastructure_type']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();
    try {
        $p_name = $_POST['project_name'];
        $r_name = $_POST['route_name'] ?? '';
        $p_type = $_POST['infrastructure_type'];
        $new_status = $_POST['status'];
        
        $b_type = $_POST['budget_type'] ?? '';
        $supervisor = $_POST['supervisor_name'] ?? '';
        $progress = $_POST['progress_percent'] ?? '0'; 
        $fiscal_year = $_POST['fiscal_year'] ?? '0';
        $budget_amount = $_POST['budget_amount'] ?? '0';
        
        $district_main = $_POST['districts'][0] ?? $project['district_name'];
        $needs_remark = in_array($new_status, ['มีการเปลี่ยนแปลงหรือแก้ไข', 'ยกเลิกโครงการ']);
        $new_remark = $needs_remark ? $_POST['status_remark'] : '';
        
        $width = $_POST['width_m'] ?? '0';
        $distance = $_POST['distance_m'] ?? '0';
        $shoulder = $_POST['shoulder_m'] ?? '0';

        $w = floatval($width); $d = floatval($distance); $s = floatval($shoulder);
        if (in_array($p_type, ['ถนนลาดยาง (Tack Coat)', 'ถนนลาดยาง (Recycling)'])) {
            $area = strval(($w + ($s * 2)) * $d);
        } else {
            $area = strval($w * $d);
        }

        $s_lat = $_POST['start_lat'] ?? '';
        $s_lng = $_POST['start_long'] ?? '';
        $e_lat = $_POST['end_lat'] ?? '';
        $e_lng = $_POST['end_long'] ?? '';

        // --- ระบบอัปโหลดไฟล์ (เพิ่มความปลอดภัยและบีบอัดรูปภาพ) ---
        if (!empty($_FILES['files']['name'][0])) {
            $upload_dir = 'uploads/projects/';
            if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];

            foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['files']['error'][$key] === 0) {
                    $original_name = $_FILES['files']['name'][$key];
                    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                    $mime = mime_content_type($tmp_name);

                    if (in_array($ext, $allowed_exts) && in_array($mime, $allowed_types)) {
                        $file_name = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
                        $file_path = $upload_dir . $file_name;
                        
                        if ($ext === 'pdf') {
                            if (move_uploaded_file($tmp_name, $file_path)) {
                                $ins_file = $conn->prepare("INSERT INTO project_attachments (project_id, file_path, file_name) VALUES (?, ?, ?)");
                                $ins_file->bind_param("iss", $id, $file_name, $original_name);
                                $ins_file->execute();
                            }
                        } else {
                            $info = getimagesize($tmp_name);
                            if ($info['mime'] == 'image/jpeg') $image = imagecreatefromjpeg($tmp_name);
                            elseif ($info['mime'] == 'image/gif') $image = imagecreatefromgif($tmp_name);
                            elseif ($info['mime'] == 'image/png') $image = imagecreatefrompng($tmp_name);
                            elseif ($info['mime'] == 'image/webp') $image = imagecreatefromwebp($tmp_name);
                            else continue;

                            $img_width = imagesx($image);
                            $img_height = imagesy($image);
                            $max_dim = 1600;
                            if ($img_width > $max_dim || $img_height > $max_dim) {
                                $ratio = $img_width / $img_height;
                                if ($ratio > 1) { $new_width = $max_dim; $new_height = $max_dim / $ratio; } 
                                else { $new_height = $max_dim; $new_width = $max_dim * $ratio; }
                                
                                $new_image = imagecreatetruecolor((int)$new_width, (int)$new_height);
                                if ($info['mime'] == 'image/png') {
                                    imagealphablending($new_image, false); imagesavealpha($new_image, true);
                                    $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
                                    imagefilledrectangle($new_image, 0, 0, (int)$new_width, (int)$new_height, $transparent);
                                }
                                imagecopyresampled($new_image, $image, 0, 0, 0, 0, (int)$new_width, (int)$new_height, $img_width, $img_height);
                                imagedestroy($image);
                                $image = $new_image;
                            }

                            $saved = false;
                            if ($info['mime'] == 'image/png') { $saved = imagepng($image, $file_path, 8); } 
                            elseif ($info['mime'] == 'image/webp') { $saved = imagewebp($image, $file_path, 80); } 
                            else { $saved = imagejpeg($image, $file_path, 80); }
                            imagedestroy($image);

                            if ($saved) {
                                $ins_file = $conn->prepare("INSERT INTO project_attachments (project_id, file_path, file_name) VALUES (?, ?, ?)");
                                $ins_file->bind_param("iss", $id, $file_name, $original_name);
                                $ins_file->execute();
                            }
                        }
                    }
                }
            }
        }

        if ($new_status !== $project['status'] || !empty($_POST['status_remark'])) {
            $log_sql = "INSERT INTO project_status_history (project_id, status, remark, changed_by) VALUES (?, ?, ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("issi", $id, $new_status, $new_remark, $_SESSION['user_id']);
            $log_stmt->execute();
        }

        if ($new_status === 'ยกเลิกโครงการ') {
            $sql_upd = "UPDATE projects SET project_name=?, route_name=?, infrastructure_type=?, fiscal_year=?, district_name=?, budget_amount=?, budget_type=?, supervisor_name=?, status=?, status_remark=?, progress_percent=?, width=?, distance=?, shoulder_width=?, area=?, start_lat=?, start_long=?, end_lat=?, end_long=?, deleted_at=NOW() WHERE id=?";
        } else {
            $sql_upd = "UPDATE projects SET project_name=?, route_name=?, infrastructure_type=?, fiscal_year=?, district_name=?, budget_amount=?, budget_type=?, supervisor_name=?, status=?, status_remark=?, progress_percent=?, width=?, distance=?, shoulder_width=?, area=?, start_lat=?, start_long=?, end_lat=?, end_long=? WHERE id=?";
        }
        
        $stmt_upd = $conn->prepare($sql_upd);
        $stmt_upd->bind_param("sssssssssssssssssssi", 
            $p_name, $r_name, $p_type, $fiscal_year, $district_main,
            $budget_amount, $b_type, $supervisor, $new_status, $new_remark, $progress,
            $width, $distance, $shoulder, $area,
            $s_lat, $s_lng, $e_lat, $e_lng, $id
        );
        $stmt_upd->execute();

        $conn->query("DELETE FROM project_points WHERE project_id = $id");
        if (isset($_POST['villages'])) {
            $stmt_pt = $conn->prepare("INSERT INTO project_points (project_id, village, moo, sub_district, district, province, order_index) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($_POST['villages'] as $idx => $v_name) {
                if (empty($v_name) && empty($_POST['sub_districts'][$idx])) continue;
                $v_name_clean = trim($v_name);
                $stmt_pt->bind_param("isssssi", $id, $v_name_clean, $_POST['moos'][$idx], $_POST['sub_districts'][$idx], $_POST['districts'][$idx], $_POST['provinces'][$idx], $idx);
                $stmt_pt->execute();
            }
        }

        log_activity($conn, 'UPDATE', 'project', $id, "แก้ไข/เปลี่ยนสถานะโครงการ: " . $p_name . " ($new_status $progress%)");
        $conn->commit();
        
        if ($new_status === 'ยกเลิกโครงการ') { header("Location: projects.php?msg=cancelled_to_trash"); exit; }
        
        $success_msg = "อัปเดตข้อมูลและเปอร์เซ็นต์ความก้าวหน้าเรียบร้อยแล้ว";
        $stmt->execute();
        $project = $stmt->get_result()->fetch_assoc();
        $attachments_res = $conn->query("SELECT * FROM project_attachments WHERE project_id = $id");
        $points_res = $conn->query("SELECT * FROM project_points WHERE project_id = $id ORDER BY order_index ASC");
        $existing_points = [];
        while($pt = $points_res->fetch_assoc()) { $existing_points[] = $pt; }
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "ข้อผิดพลาด: " . $e->getMessage();
    }
}
include 'includes/header.php';
?>

<!-- โหลดไลบรารีแผนที่ Leaflet (สำหรับระบบปักหมุดพิกัด) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

<div class="max-w-[1600px] mx-auto pb-20 animate-in fade-in duration-500 w-full">
    <div class="flex items-center justify-between mb-8 px-4">
        <a href="projects.php" class="text-slate-500 hover:text-orange-600 flex items-center gap-2 font-bold transition-all group">
            <i data-lucide="arrow-left" size="18" class="group-hover:-translate-x-1 transition-transform"></i> ย้อนกลับ
        </a>
        <a href="view_project.php?id=<?= $id ?>" class="bg-blue-50 text-blue-600 px-6 py-2.5 rounded-2xl text-xs font-black uppercase hover:bg-blue-600 hover:text-white transition-all shadow-sm">
            ดูรายละเอียดหน้างาน
        </a>
    </div>

    <div class="bg-white rounded-[3.5rem] shadow-2xl border border-slate-100 overflow-hidden mx-4 md:mx-0">
        <div class="bg-slate-900 p-10 text-white relative">
            <div class="absolute right-0 top-0 opacity-10 -mr-4 -mt-4"><i data-lucide="edit-3" size="180"></i></div>
            <h2 class="text-2xl font-black uppercase tracking-tight relative z-10">จัดการข้อมูลและรูปภาพโครงการ</h2>
            <p class="text-orange-500 text-xs font-bold uppercase tracking-widest mt-1 relative z-10">Data & Engineering Maintenance</p>
        </div>

        <form method="POST" id="projectForm" enctype="multipart/form-data" class="p-8 md:p-12 space-y-12">
            <?php if($success_msg): ?><div class="bg-emerald-50 text-emerald-600 p-6 rounded-2xl font-bold flex items-center gap-3 animate-in slide-in-from-top-2"><i data-lucide="check-circle"></i> <?= $success_msg ?></div><?php endif; ?>
            <?php if($error_msg): ?><div class="bg-red-50 text-red-600 p-6 rounded-2xl font-bold flex items-center gap-3 animate-in slide-in-from-top-2"><i data-lucide="alert-circle"></i> <?= $error_msg ?></div><?php endif; ?>

            <!-- ข้อมูลทางวิศวกรรม -->
            <div class="space-y-6">
                <div class="flex justify-between items-end px-1">
                    <h3 class="text-lg font-black text-slate-800 border-l-4 border-emerald-600 pl-4 uppercase">1. ข้อมูลทางวิศวกรรม</h3>
                    <div id="formula_badge" class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-wider border transition-all duration-500">Calculating...</div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-4">
                    <div class="space-y-2"><label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อโครงการ</label><input type="text" name="project_name" value="<?= e($project['project_name']) ?>" required class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-bold outline-none focus:border-orange-500"></div>
                    <div class="space-y-2"><label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อสายทาง</label><input type="text" name="route_name" id="route_name_display" value="<?= e($project['route_name'] ?? '') ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-bold outline-none focus:border-orange-500"></div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ประเภทงาน / โครงสร้าง</label>
                        <select name="infrastructure_type" id="p_type" onchange="calculateArea()" class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold outline-none focus:border-orange-500">
                            <!-- ⭐️ เปลี่ยนมาใช้ข้อมูลจาก Database ⭐️ -->
                            <?php foreach($dynamic_types as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>" <?= $project['infrastructure_type'] == $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-emerald-50/50 p-6 rounded-[2.5rem] border border-emerald-100 text-slate-700">
                    <div class="space-y-1 text-center"><label class="text-[10px] font-black text-slate-400 uppercase">กว้าง (ม.)</label><input type="number" step="0.01" name="width_m" id="width_m" value="<?= $project['width'] ?>" oninput="calculateArea()" class="w-full p-4 rounded-2xl border-none font-bold text-center shadow-sm focus:ring-2 focus:ring-emerald-500 outline-none transition-all"></div>
                    <div class="space-y-1 text-center"><label class="text-[10px] font-black text-slate-400 uppercase">ยาว (ม.)</label><input type="number" step="0.01" name="distance_m" id="distance_m" value="<?= $project['distance'] ?>" oninput="calculateArea()" class="w-full p-4 rounded-2xl border-none font-bold text-center shadow-sm focus:ring-2 focus:ring-emerald-500 outline-none transition-all"></div>
                    <div class="space-y-1 text-center"><label class="text-[10px] font-black text-slate-400 uppercase">ไหล่ทาง (ม.)</label><input type="number" step="0.01" name="shoulder_m" id="shoulder_m" value="<?= $project['shoulder_width'] ?>" oninput="calculateArea()" class="w-full p-4 rounded-2xl border-none font-bold text-center shadow-sm focus:ring-2 focus:ring-emerald-500 outline-none transition-all"></div>
                    <div class="bg-emerald-600 rounded-[2.2rem] p-4 text-white text-center flex flex-col justify-center shadow-lg"><p class="text-[9px] font-black uppercase opacity-70">พื้นที่ดำเนินการ</p><input type="text" id="total_area" readonly class="w-full bg-transparent border-none text-xl font-black text-center text-white outline-none"></div>
                </div>
            </div>

            <!-- สถานที่ก่อสร้าง -->
            <div class="space-y-6 pt-10 border-t border-slate-50">
                <h3 class="text-lg font-black text-slate-800 flex items-center gap-3 border-l-4 border-blue-600 pl-4 uppercase">2. สถานที่ก่อสร้าง</h3>
                <div id="pointsContainer" class="space-y-4">
                    <?php if (count($existing_points) > 0): foreach($existing_points as $index => $pt): ?>
                        <div class="point-row bg-slate-50 p-6 rounded-[2rem] border border-slate-100 relative group">
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                                <select name="provinces[]" class="p-3 rounded-xl border-none font-bold text-sm shadow-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none"><?php foreach($nearby_provinces as $p): ?><option value="<?= $p ?>" <?= $pt['province'] == $p ? 'selected' : '' ?>><?= $p ?></option><?php endforeach; ?></select>
                                <select name="districts[]" onchange="handleDistrictChange(this)" class="district-select p-3 rounded-xl border-none font-bold text-sm shadow-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">เลือกอำเภอ</option>
                                    <?php foreach($sorted_districts as $d): ?><option value="<?= $d ?>" <?= $pt['district'] == $d ? 'selected' : '' ?>><?= $d ?></option><?php endforeach; ?>
                                </select>
                                <select name="sub_districts[]" class="sub-district-select p-3 rounded-xl border-none font-bold text-sm shadow-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">เลือกตำบล</option>
                                    <?php if($pt['district'] && isset($sisaket_full_data[$pt['district']])): $subs = $sisaket_full_data[$pt['district']]; if($pt['district'] !== "ราษีไศล") sort($subs); foreach($subs as $s): ?><option value="<?= $s ?>" <?= $pt['sub_district'] == $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; endif; ?>
                                </select>
                                <input type="text" name="moos[]" value="<?= e($pt['moo']) ?>" class="p-3 rounded-xl border-none text-sm text-center shadow-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none" placeholder="ม.">
                                <input type="text" name="villages[]" value="<?= e($pt['village']) ?>" oninput="updateRouteName()" placeholder="หมู่บ้าน" class="v-input p-3 rounded-xl border-none font-bold text-sm shadow-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                            <?php if ($index > 0): ?><button type="button" onclick="this.closest('.point-row').remove(); updateRouteName();" class="absolute -right-2 -top-2 bg-red-500 text-white w-7 h-7 rounded-full flex items-center justify-center shadow-lg"><i data-lucide="x" size="14"></i></button><?php endif; ?>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="point-row bg-slate-50 p-6 rounded-[2rem] border border-slate-100 relative group">
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                                <select name="provinces[]" class="p-3 rounded-xl border-none font-bold text-sm shadow-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none"><?php foreach($nearby_provinces as $p): ?><option value="<?= $p ?>"><?= $p ?></option><?php endforeach; ?></select>
                                <select name="districts[]" onchange="handleDistrictChange(this)" class="district-select p-3 rounded-xl border-none font-bold text-sm shadow-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none"><option value="">เลือกอำเภอ</option><?php foreach($sorted_districts as $d): ?><option value="<?= $d ?>"><?= $d ?></option><?php endforeach; ?></select>
                                <select name="sub_districts[]" class="sub-district-select p-3 rounded-xl border-none font-bold text-sm shadow-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none"><option value="">เลือกตำบล</option></select>
                                <input type="text" name="moos[]" class="p-3 rounded-xl border-none text-sm text-center shadow-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none" placeholder="ม.">
                                <input type="text" name="villages[]" oninput="updateRouteName()" placeholder="หมู่บ้าน" class="v-input p-3 rounded-xl border-none font-bold text-sm shadow-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex justify-center"><button type="button" onclick="addPointRow()" class="bg-slate-900 text-white px-8 py-3 rounded-2xl text-xs font-black hover:bg-blue-600 transition-all shadow-xl flex items-center gap-2"><i data-lucide="plus-circle" size="16"></i> เพิ่มจุดพิกัด/หมู่บ้าน ต่อไป</button></div>
            </div>

            <!-- งบประมาณ & พิกัด -->
            <div class="space-y-6 pt-10 border-t border-slate-50">
                <h3 class="text-lg font-black text-slate-800 border-l-4 border-orange-600 pl-4 uppercase">3. งบประมาณ และ พิกัด (GIS)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2"><label class="text-[10px] font-black text-slate-400 uppercase ml-1">งบประมาณ</label><input type="number" step="0.01" name="budget_amount" value="<?= $project['budget_amount'] ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-black text-orange-600 text-lg outline-none focus:border-orange-500"></div>
                    <div class="space-y-2"><label class="text-[10px] font-black text-slate-400 uppercase ml-1">ประเภทงบ</label><select name="budget_type" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-bold outline-none focus:border-orange-500 text-slate-700 text-sm"><option value="งบตามข้อบัญญัติ" <?= ($project['budget_type'] ?? '') == 'งบตามข้อบัญญัติ' ? 'selected' : '' ?>>งบตามข้อบัญญัติ</option><option value="งบเงินอุดหนุนเฉพาะกิจ" <?= ($project['budget_type'] ?? '') == 'งบเงินอุดหนุนเฉพาะกิจ' ? 'selected' : '' ?>>งบเงินอุดหนุนเฉพาะกิจ</option><option value="งบกลาง" <?= ($project['budget_type'] ?? '') == 'งบกลาง' ? 'selected' : '' ?>>งบกลาง</option></select></div>
                    <div class="space-y-2"><label class="text-[10px] font-black text-slate-400 uppercase ml-1 text-center block">ปีงบประมาณ</label><input type="number" name="fiscal_year" value="<?= $project['fiscal_year'] ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-bold text-center outline-none focus:border-orange-500"></div>
                </div>
                <div class="space-y-2"><label class="text-[10px] font-black text-slate-400 uppercase ml-1">ช่างผู้ควบคุมงาน</label><input type="text" name="supervisor_name" value="<?= e($project['supervisor_name'] ?? '') ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-bold outline-none focus:border-orange-500"></div>
                
                <!-- ⭐️ ระบบแผนที่แบบปักหมุด (Interactive Map Picker) ⭐️ -->
                <div class="pt-6 border-t border-slate-100 mt-6">
                    <div class="flex justify-between items-end mb-3 ml-1">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">พิกัดแผนที่ (Lat / Lng)</p>
                        <button type="button" onclick="getCurrentLocation()" class="text-[10px] font-black text-blue-600 bg-blue-50 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition-colors flex items-center gap-1 shadow-sm"><i data-lucide="crosshair" size="12"></i> ดึงพิกัดปัจจุบัน</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="grid grid-cols-2 gap-2 bg-slate-50 p-4 rounded-[2rem] border border-slate-100 relative group">
                            <div class="absolute -top-2.5 left-4 bg-orange-500 text-white text-[8px] font-black px-2 py-0.5 rounded-full z-10 shadow-sm">จุดเริ่มต้น (Start)</div>
                            <input type="text" name="start_lat" id="start_lat" value="<?= $project['start_lat'] ?>" placeholder="Lat" class="p-3 bg-white rounded-xl text-xs font-bold border-none shadow-sm outline-none focus:ring-2 focus:ring-orange-500 transition-all" oninput="updateMapMarker()">
                            <input type="text" name="start_long" id="start_long" value="<?= $project['start_long'] ?>" placeholder="Lng" class="p-3 bg-white rounded-xl text-xs font-bold border-none shadow-sm outline-none focus:ring-2 focus:ring-orange-500 transition-all" oninput="updateMapMarker()">
                        </div>
                        <div class="grid grid-cols-2 gap-2 bg-slate-50 p-4 rounded-[2rem] border border-slate-100 relative group">
                            <div class="absolute -top-2.5 left-4 bg-blue-500 text-white text-[8px] font-black px-2 py-0.5 rounded-full z-10 shadow-sm">จุดสิ้นสุด (End)</div>
                            <input type="text" name="end_lat" id="end_lat" value="<?= $project['end_lat'] ?>" placeholder="Lat" class="p-3 bg-white rounded-xl text-xs font-bold border-none shadow-sm outline-none focus:ring-2 focus:ring-blue-500 transition-all" oninput="updateMapMarker()">
                            <input type="text" name="end_long" id="end_long" value="<?= $project['end_long'] ?>" placeholder="Lng" class="p-3 bg-white rounded-xl text-xs font-bold border-none shadow-sm outline-none focus:ring-2 focus:ring-blue-500 transition-all" oninput="updateMapMarker()">
                        </div>
                    </div>
                    
                    <div class="h-[350px] w-full bg-slate-200 rounded-[2.5rem] overflow-hidden border-4 border-white shadow-md relative z-0" id="pickerMap"></div>
                    <p class="text-[9px] text-slate-400 mt-3 font-bold text-center flex items-center justify-center gap-1.5"><i data-lucide="info" size="12"></i> คลิกที่แผนที่เพื่อปักหมุด หรือลากหมุดเพื่อเปลี่ยนพิกัดอัตโนมัติ</p>
                </div>
            </div>

            <!-- ไฟล์แนบ -->
            <div class="space-y-6 pt-10 border-t border-slate-50">
                <h3 class="text-lg font-black text-slate-800 border-l-4 border-slate-400 pl-4 uppercase">4. แกลเลอรีและไฟล์แนบ</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-4">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block">เพิ่มไฟล์ใหม่</label>
                        <div class="relative group">
                            <input type="file" name="files[]" multiple accept="image/*,.pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewFiles(this)">
                            <div class="p-12 border-4 border-dashed border-slate-100 rounded-[3rem] bg-slate-50 flex flex-col items-center justify-center text-center"><div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-sm mb-4 group-hover:text-blue-500 transition-colors"><i data-lucide="upload-cloud" size="32"></i></div><p class="text-sm font-black text-slate-500">คลิกหรือลากไฟล์วาง</p></div>
                        </div>
                        <div id="file_list_preview" class="flex flex-wrap gap-2"></div>
                    </div>
                    <div class="space-y-4">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block">ไฟล์ปัจจุบัน</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <?php if($attachments_res->num_rows > 0): while($f = $attachments_res->fetch_assoc()): $ext = strtolower(pathinfo($f['file_path'], PATHINFO_EXTENSION)); $is_img = in_array($ext, ['jpg','jpeg','png','gif','webp']); ?>
                            <div class="relative group aspect-square rounded-2xl overflow-hidden border border-slate-100" id="file-row-<?= $f['id'] ?>">
                                <?php if($is_img): ?><img src="uploads/projects/<?= $f['file_path'] ?>" class="w-full h-full object-cover"><?php else: ?><div class="w-full h-full flex items-center justify-center bg-slate-50"><i data-lucide="file-text" class="text-slate-300"></i></div><?php endif; ?>
                                <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"><button type="button" onclick="deleteFile(<?= $f['id'] ?>)" class="p-2.5 bg-rose-600 text-white rounded-xl"><i data-lucide="trash-2" size="16"></i></button></div>
                            </div>
                            <?php endwhile; else: ?><div class="col-span-full py-12 border-2 border-dashed border-slate-50 rounded-[2rem] text-center text-slate-300 italic text-xs uppercase">No attachments</div><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- สถานะ -->
            <div class="space-y-6 pt-10 border-t border-slate-50">
                <h3 class="text-lg font-black text-slate-800 border-l-4 border-slate-800 pl-4 uppercase">5. สถานะและความก้าวหน้า</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <?php 
                    $st_list = ['รอดำเนินการ', 'กำลังดำเนินการ', 'เสร็จสิ้น', 'มีการเปลี่ยนแปลงหรือแก้ไข', 'ยกเลิกโครงการ'];
                    foreach($st_list as $st): 
                        $color = 'slate'; if($st == 'เสร็จสิ้น') $color = 'emerald'; if($st == 'กำลังดำเนินการ') $color = 'orange'; if($st == 'มีการเปลี่ยนแปลงหรือแก้ไข') $color = 'rose'; if($st == 'ยกเลิกโครงการ') $color = 'slate-800';
                        $is_remark = in_array($st, ['มีการเปลี่ยนแปลงหรือแก้ไข', 'ยกเลิกโครงการ']) ? 'true' : 'false';
                    ?>
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="status" value="<?= $st ?>" <?= $project['status'] == $st ? 'checked' : '' ?> onclick="toggleRemark(<?= $is_remark ?>, '<?= $st ?>')" class="peer sr-only">
                        <div class="p-4 bg-white border-2 border-slate-100 rounded-2xl text-center peer-checked:border-<?= $color == 'slate-800' ? 'slate-800' : $color.'-600' ?> peer-checked:bg-<?= $color == 'slate-800' ? 'slate-800' : $color.'-600' ?> peer-checked:text-white transition-all shadow-sm"><p class="text-[9px] font-black uppercase"><?= $st ?></p></div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="mt-8 p-8 bg-slate-50 border border-slate-200 rounded-[2.5rem]">
                    <div class="flex justify-between items-center mb-6">
                        <div><label class="text-sm font-black text-slate-800 uppercase flex items-center gap-2"><i data-lucide="activity" class="text-orange-600"></i> ความก้าวหน้าโครงการ</label></div>
                        <span class="text-4xl font-black text-orange-600" id="progressText"><?= $project['progress_percent'] ?? 0 ?>%</span>
                    </div>
                    <input type="range" name="progress_percent" id="progressSlider" min="0" max="100" value="<?= $project['progress_percent'] ?? 0 ?>" class="w-full h-3 bg-slate-200 rounded-lg appearance-none cursor-pointer outline-none" style="accent-color: #ea580c;" oninput="document.getElementById('progressText').innerText = this.value + '%'">
                </div>

                <div id="remarkContainer" class="<?= in_array($project['status'], ['มีการเปลี่ยนแปลงหรือแก้ไข', 'ยกเลิกโครงการ']) ? '' : 'hidden' ?>"><textarea name="status_remark" placeholder="โปรดระบุหมายเหตุ หรือ สาเหตุที่เปลี่ยนสถานะ..." class="w-full p-6 bg-slate-50 border-2 border-slate-200 rounded-[2.5rem] font-bold outline-none focus:border-slate-800 min-h-[140px]"><?= e($project['status_remark']) ?></textarea></div>
            </div>

            <button type="submit" id="submitBtn" class="w-full bg-slate-900 hover:bg-orange-600 text-white font-black py-8 rounded-[3rem] transition-all active:scale-95 text-xl mt-10 uppercase tracking-[0.2em] flex items-center justify-center gap-3">
                <i data-lucide="save"></i> บันทึกการแก้ไขโครงการ
            </button>
        </form>
    </div>
</div>

<script>
    const sisaketData = <?= json_encode($sisaket_full_data) ?>;
    
    function handleDistrictChange(select) {
        const row = select.closest('.point-row'); const subSelect = row.querySelector('.sub-district-select'); const dist = select.value;
        if (sisaketData[dist]) {
            let html = '<option value="">เลือกตำบล</option>';
            if(dist === "ราษีไศล") { sisaketData[dist].forEach(s => html += `<option value="${s}">${s}</option>`); } 
            else { [...sisaketData[dist]].sort((a, b) => a.localeCompare(b, 'th')).forEach(s => { html += `<option value="${s}">${s}</option>`; }); }
            subSelect.innerHTML = html;
        } else { subSelect.innerHTML = '<option value="">เลือกตำบล</option>'; }
    }

    function addPointRow() {
        const container = document.getElementById('pointsContainer'); const newRow = document.querySelector('.point-row').cloneNode(true);
        newRow.querySelectorAll('input').forEach(i => i.value = ''); newRow.querySelector('.sub-district-select').innerHTML = '<option value="">เลือกตำบล</option>';
        const oldBtn = newRow.querySelector('button'); if(oldBtn) oldBtn.remove();
        const deleteBtn = document.createElement('button'); deleteBtn.type = 'button'; deleteBtn.className = 'absolute -right-2 -top-2 bg-red-500 text-white w-7 h-7 rounded-full flex items-center justify-center shadow-lg';
        deleteBtn.innerHTML = '<i data-lucide="x" size="14"></i>'; deleteBtn.onclick = () => { newRow.remove(); updateRouteName(); };
        newRow.appendChild(deleteBtn); container.appendChild(newRow); lucide.createIcons();
    }

    function updateRouteName() {
        const names = []; document.querySelectorAll('.v-input').forEach(i => { if(i.value.trim()){ let v = i.value.trim(); names.push(v.startsWith("บ.") ? v : "บ." + v); } });
        document.getElementById('route_name_display').value = names.join(" - ");
    }

    function calculateArea() {
        const type = document.getElementById('p_type').value; const w = parseFloat(document.getElementById('width_m').value) || 0; const l = parseFloat(document.getElementById('distance_m').value) || 0; const s = parseFloat(document.getElementById('shoulder_m').value) || 0;
        const shoulderInput = document.getElementById('shoulder_m'); const badge = document.getElementById('formula_badge');
        if (['ถนนลาดยาง (Tack Coat)', 'ถนนลาดยาง (Recycling)'].includes(type)) { document.getElementById('total_area').value = ((w + (s * 2)) * l).toLocaleString(undefined, { minimumFractionDigits: 2 }); shoulderInput.readOnly = false; shoulderInput.classList.remove('opacity-30', 'bg-slate-50'); badge.innerText = "สูตรถนนลาดยาง"; badge.className = "px-4 py-1.5 rounded-full text-[9px] font-black uppercase border bg-emerald-50 text-emerald-600"; } 
        else { document.getElementById('total_area').value = (w * l).toLocaleString(undefined, { minimumFractionDigits: 2 }); shoulderInput.readOnly = true; shoulderInput.classList.add('opacity-30', 'bg-slate-50'); badge.innerText = "สูตรถนนทั่วไป"; badge.className = "px-4 py-1.5 rounded-full text-[9px] font-black uppercase border bg-blue-50 text-blue-600"; }
    }

    function toggleRemark(show, statusStr) {
        document.getElementById('remarkContainer').classList.toggle('hidden', !show);
        const slider = document.getElementById('progressSlider'); const text = document.getElementById('progressText');
        if(statusStr === 'เสร็จสิ้น') { slider.value = 100; } else if(statusStr === 'รอดำเนินการ' || statusStr === 'ยกเลิกโครงการ') { slider.value = 0; }
        text.innerText = slider.value + '%';
    }

    function previewFiles(input) {
        const preview = document.getElementById('file_list_preview'); preview.innerHTML = "";
        for (const file of input.files) { const span = document.createElement('span'); span.className = "bg-blue-50 text-blue-600 px-4 py-1.5 rounded-full text-[9px] font-black"; span.innerText = file.name; preview.appendChild(span); }
    }
    
    document.getElementById('projectForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');
        btn.innerHTML = '<i data-lucide="loader-2" class="animate-spin"></i> กำลังบันทึกและอัปโหลดไฟล์...';
        lucide.createIcons();
    });

    async function deleteFile(fileId) { if (!confirm('ยืนยันลบไฟล์?')) return; const res = await fetch(`delete_attachment.php?id=${fileId}`); if((await res.json()).success) { document.getElementById(`file-row-${fileId}`).remove(); } }

    // --- ⭐️ ระบบแผนที่แบบปักหมุด (Map Picker) ⭐️ ---
    let pickerMap, startMarker, endMarker;
    function initPickerMap() {
        const defaultLat = parseFloat(document.getElementById('start_lat').value) || 15.1186;
        const defaultLng = parseFloat(document.getElementById('start_long').value) || 104.3220;

        pickerMap = L.map('pickerMap', { zoomControl: false }).setView([defaultLat, defaultLng], 14);
        L.control.zoom({ position: 'topright' }).addTo(pickerMap);
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18 }).addTo(pickerMap);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_only_labels/{z}/{x}/{y}{r}.png', { maxZoom: 18 }).addTo(pickerMap);

        const startIcon = L.divIcon({ className: 'custom-icon', html: '<div class="w-5 h-5 bg-orange-500 rounded-full border-2 border-white shadow-lg animate-pulse"></div>', iconSize: [20,20], iconAnchor: [10,10] });
        const endIcon = L.divIcon({ className: 'custom-icon', html: '<div class="w-5 h-5 bg-blue-500 rounded-full border-2 border-white shadow-lg animate-pulse"></div>', iconSize: [20,20], iconAnchor: [10,10] });

        if(document.getElementById('start_lat').value) {
            startMarker = L.marker([defaultLat, defaultLng], {icon: startIcon, draggable: true}).addTo(pickerMap);
            startMarker.on('dragend', function(e) {
                document.getElementById('start_lat').value = e.target.getLatLng().lat.toFixed(6);
                document.getElementById('start_long').value = e.target.getLatLng().lng.toFixed(6);
            });
        }

        const eLat = parseFloat(document.getElementById('end_lat').value);
        const eLng = parseFloat(document.getElementById('end_long').value);
        if(eLat && eLng) {
            endMarker = L.marker([eLat, eLng], {icon: endIcon, draggable: true}).addTo(pickerMap);
            endMarker.on('dragend', function(e) {
                document.getElementById('end_lat').value = e.target.getLatLng().lat.toFixed(6);
                document.getElementById('end_long').value = e.target.getLatLng().lng.toFixed(6);
            });
        }

        pickerMap.on('click', function(e) {
            if (!startMarker) {
                document.getElementById('start_lat').value = e.latlng.lat.toFixed(6);
                document.getElementById('start_long').value = e.latlng.lng.toFixed(6);
                startMarker = L.marker(e.latlng, {icon: startIcon, draggable: true}).addTo(pickerMap);
                startMarker.on('dragend', function(ev) {
                    document.getElementById('start_lat').value = ev.target.getLatLng().lat.toFixed(6);
                    document.getElementById('start_long').value = ev.target.getLatLng().lng.toFixed(6);
                });
            } else if (!endMarker) {
                document.getElementById('end_lat').value = e.latlng.lat.toFixed(6);
                document.getElementById('end_long').value = e.latlng.lng.toFixed(6);
                endMarker = L.marker(e.latlng, {icon: endIcon, draggable: true}).addTo(pickerMap);
                endMarker.on('dragend', function(ev) {
                    document.getElementById('end_lat').value = ev.target.getLatLng().lat.toFixed(6);
                    document.getElementById('end_long').value = ev.target.getLatLng().lng.toFixed(6);
                });
            }
        });
        
        setTimeout(() => { pickerMap.invalidateSize(); }, 500);
    }

    function updateMapMarker() {
        const sLat = parseFloat(document.getElementById('start_lat').value);
        const sLng = parseFloat(document.getElementById('start_long').value);
        if(sLat && sLng && startMarker) { startMarker.setLatLng([sLat, sLng]); pickerMap.panTo([sLat, sLng]); }

        const eLat = parseFloat(document.getElementById('end_lat').value);
        const eLng = parseFloat(document.getElementById('end_long').value);
        if(eLat && eLng && endMarker) { endMarker.setLatLng([eLat, eLng]); }
    }

    function getCurrentLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(position => {
                document.getElementById('start_lat').value = position.coords.latitude.toFixed(6);
                document.getElementById('start_long').value = position.coords.longitude.toFixed(6);
                updateMapMarker();
                if(!startMarker) { 
                    const startIcon = L.divIcon({ className: 'custom-icon', html: '<div class="w-5 h-5 bg-orange-500 rounded-full border-2 border-white shadow-lg animate-pulse"></div>', iconSize: [20,20], iconAnchor: [10,10] });
                    startMarker = L.marker([position.coords.latitude, position.coords.longitude], {icon: startIcon, draggable: true}).addTo(pickerMap);
                }
                pickerMap.setView([position.coords.latitude, position.coords.longitude], 16);
            }, () => alert("ไม่สามารถดึงตำแหน่งปัจจุบันได้ (กรุณาอนุญาตการเข้าถึง Location ของเบราว์เซอร์)"));
        } else { alert("อุปกรณ์ของคุณไม่รองรับ GPS"); }
    }

    window.onload = () => { 
        calculateArea(); 
        initPickerMap(); 
        if(typeof lucide !== 'undefined') lucide.createIcons(); 
    };
</script>
<?php include 'includes/footer.php'; ?>