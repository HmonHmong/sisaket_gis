<?php
// edit_project.php - ระบบแก้ไขโครงการพร้อมตรรกะวิศวกรรมแยกประเภทถนน (Strict Exact Match Logic)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) { header("Location: projects.php"); exit; }
$id = intval($_GET['id']);

$success_msg = "";
$error_msg = "";

// 1. ดึงข้อมูลโครงการเดิม
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) { header("Location: projects.php"); exit; }

// ดึงไฟล์แนบเดิม
$attachments_res = $conn->query("SELECT * FROM project_attachments WHERE project_id = $id");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();
    try {
        $p_name = $_POST['project_name'];
        $p_type = $_POST['infrastructure_type'];
        $new_status = $_POST['status'];
        $new_remark = ($new_status === 'มีการเปลี่ยนแปลงหรือแก้ไข') ? $_POST['status_remark'] : ($_POST['status_remark'] ?? null);
        
        $width = floatval($_POST['width_m']);
        $distance = floatval($_POST['distance_m']);
        $shoulder = floatval($_POST['shoulder_m'] ?? 0);

        // --- ตรรกะการคำนวณพื้นที่ที่ถูกต้องแบบเคร่งครัด (Strict Engineering Logic) ---
        $asphalt_types = ['ถนนลาดยาง (Tack Coat)', 'ถนนลาดยาง (Recycling)'];
        
        if (in_array($p_type, $asphalt_types)) {
            // 1. เฉพาะถนนลาดยาง: คิดรวมไหล่ทาง 2 ข้าง
            $area = ($width + ($shoulder * 2)) * $distance;
        } else {
            // 2. ถนนประเภทอื่นๆ (คอนกรีต, ลูกรัง, หินคลุก): คิดเฉพาะผิวจราจรหลัก (กว้าง x ยาว)
            $area = $width * $distance;
        }

        // --- ระบบอัปโหลดไฟล์ ---
        if (!empty($_FILES['files']['name'][0])) {
            $upload_dir = 'uploads/projects/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['files']['error'][$key] === 0) {
                    $original_name = $_FILES['files']['name'][$key];
                    $ext = pathinfo($original_name, PATHINFO_EXTENSION);
                    $file_name = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
                    $file_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $ins_file = $conn->prepare("INSERT INTO project_attachments (project_id, file_path, file_name) VALUES (?, ?, ?)");
                        $ins_file->bind_param("iss", $id, $file_name, $original_name);
                        $ins_file->execute();
                    }
                }
            }
        }

        // --- บันทึกประวัติสถานะ ---
        if ($new_status !== $project['status'] || !empty($_POST['status_remark'])) {
            $log_sql = "INSERT INTO project_status_history (project_id, status, remark, changed_by) VALUES (?, ?, ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("issi", $id, $new_status, $new_remark, $_SESSION['user_id']);
            $log_stmt->execute();
        }

        // --- อัปเดตตารางหลัก ---
        $sql_upd = "UPDATE projects SET 
                    project_name=?, infrastructure_type=?, fiscal_year=?, 
                    budget_amount=?, status=?, status_remark=?, 
                    width=?, distance=?, shoulder_width=?, area=?,
                    start_lat=?, start_long=?, end_lat=?, end_long=? 
                    WHERE id=?";
        $stmt_upd = $conn->prepare($sql_upd);
        $stmt_upd->bind_param("ssisssddddddddi", 
            $p_name, $p_type, $_POST['fiscal_year'],
            $_POST['budget_amount'], $new_status, $new_remark,
            $width, $distance, $shoulder, $area,
            $_POST['start_lat'], $_POST['start_long'], $_POST['end_lat'], $_POST['end_long'], $id
        );
        $stmt_upd->execute();

        log_activity($conn, 'UPDATE', 'project', $id, "แก้ไขโครงการ: " . $p_name . " (พื้นที่คำนวณใหม่: " . number_format($area, 2) . " ตร.ม.)");

        $conn->commit();
        $success_msg = "อัปเดตข้อมูลโครงการและคำนวณพื้นที่ใหม่เรียบร้อยแล้ว";
        
        // รีเฟรชข้อมูล
        $stmt->execute();
        $project = $stmt->get_result()->fetch_assoc();
        $attachments_res = $conn->query("SELECT * FROM project_attachments WHERE project_id = $id");
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "ข้อผิดพลาด: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="max-w-6xl mx-auto pb-20 animate-in fade-in duration-500">
    <div class="flex items-center justify-between mb-8 px-4">
        <a href="projects.php" class="text-slate-500 hover:text-orange-600 flex items-center gap-2 font-bold transition-all group">
            <i data-lucide="arrow-left" size="18" class="group-hover:-translate-x-1 transition-transform"></i> ย้อนกลับ
        </a>
        <a href="view_project.php?id=<?= $id ?>" class="bg-blue-50 text-blue-600 px-6 py-2.5 rounded-2xl text-xs font-black uppercase hover:bg-blue-600 hover:text-white transition-all shadow-sm">
            ดูรายละเอียดหน้างาน
        </a>
    </div>

    <div class="bg-white rounded-[3.5rem] shadow-2xl border border-slate-100 overflow-hidden">
        <div class="bg-slate-900 p-10 text-white relative">
            <div class="absolute right-0 top-0 opacity-10 -mr-4 -mt-4"><i data-lucide="edit-3" size="180"></i></div>
            <h2 class="text-2xl font-black uppercase tracking-tight relative z-10">จัดการข้อมูลและรูปภาพโครงการ</h2>
            <p class="text-orange-500 text-xs font-bold uppercase tracking-widest mt-1 relative z-10">Data & Engineering Maintenance</p>
        </div>

        <form method="POST" enctype="multipart/form-data" class="p-8 md:p-12 space-y-12">
            <?php if($success_msg): ?>
                <div class="bg-emerald-50 text-emerald-600 p-6 rounded-2xl border border-emerald-100 font-bold flex items-center gap-3 animate-in slide-in-from-top-2">
                    <i data-lucide="check-circle"></i> <?= $success_msg ?>
                </div>
            <?php endif; ?>

            <!-- ส่วนที่ 1: ข้อมูลทางวิศวกรรม -->
            <div class="space-y-6">
                <div class="flex justify-between items-end px-1">
                    <h3 class="text-lg font-black text-slate-800 border-l-4 border-emerald-600 pl-4 uppercase">1. ข้อมูลทางวิศวกรรม</h3>
                    <div id="formula_badge" class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-wider border transition-all duration-500">
                        Calculating...
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อโครงการ</label>
                        <input type="text" name="project_name" value="<?= e($project['project_name']) ?>" required class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-bold outline-none focus:border-orange-500 transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ประเภทถนน</label>
                        <select name="infrastructure_type" id="p_type" onchange="calculateArea()" class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold text-slate-700 outline-none focus:border-orange-500">
                            <?php 
                            $types = ["ถนนลูกรัง", "ถนนหินคลุก", "ถนนคอนกรีต", "ถนนลาดยาง (Tack Coat)", "ถนนลาดยาง (Recycling)"];
                            foreach($types as $t):
                            ?>
                            <option value="<?= $t ?>" <?= $project['infrastructure_type'] == $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-emerald-50/50 p-6 rounded-[2.5rem] border border-emerald-100 text-slate-700">
                    <div class="space-y-1 text-center">
                        <label class="text-[10px] font-black text-slate-400 uppercase">กว้าง (ม.)</label>
                        <input type="number" step="0.01" name="width_m" id="width_m" value="<?= $project['width'] ?>" oninput="calculateArea()" class="w-full p-4 rounded-2xl border-none font-bold text-center shadow-sm">
                    </div>
                    <div class="space-y-1 text-center">
                        <label class="text-[10px] font-black text-slate-400 uppercase">ยาว (ม.)</label>
                        <input type="number" step="0.01" name="distance_m" id="distance_m" value="<?= $project['distance'] ?>" oninput="calculateArea()" class="w-full p-4 rounded-2xl border-none font-bold text-center shadow-sm">
                    </div>
                    <div class="space-y-1 text-center">
                        <label class="text-[10px] font-black text-slate-400 uppercase">ไหล่ทางข้างละ (ม.)</label>
                        <input type="number" step="0.01" name="shoulder_m" id="shoulder_m" value="<?= $project['shoulder_width'] ?>" oninput="calculateArea()" class="w-full p-4 rounded-2xl border-none font-bold text-center shadow-sm transition-all duration-300 outline-none">
                    </div>
                    <div class="bg-emerald-600 rounded-[2.2rem] p-4 text-white text-center flex flex-col justify-center shadow-lg shadow-emerald-100">
                        <p class="text-[9px] font-black uppercase opacity-70">พื้นที่ดำเนินการรวม</p>
                        <input type="text" id="total_area" readonly class="w-full bg-transparent border-none text-xl font-black text-center text-white outline-none">
                    </div>
                </div>
                <p id="formula_desc" class="text-[10px] font-bold text-slate-400 italic px-2 flex items-center gap-2"></p>
            </div>

            <!-- ส่วนที่ 2: ระบบจัดการรูปภาพและไฟล์แนบ -->
            <div class="space-y-6 pt-10 border-t border-slate-50">
                <h3 class="text-lg font-black text-slate-800 border-l-4 border-blue-600 pl-4 uppercase">2. แกลเลอรีและไฟล์แนบ</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-4">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 block">เพิ่มรูปภาพหรือเอกสารใหม่</label>
                        <div class="relative group">
                            <input type="file" name="files[]" multiple accept="image/*,.pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewFiles(this)">
                            <div class="p-12 border-4 border-dashed border-slate-100 rounded-[3rem] bg-slate-50 flex flex-col items-center justify-center text-center group-hover:border-blue-200 group-hover:bg-blue-50/30 transition-all">
                                <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-sm mb-4 text-slate-300 group-hover:text-blue-500 group-hover:scale-110 transition-all">
                                    <i data-lucide="upload-cloud" size="32"></i>
                                </div>
                                <p class="text-sm font-black text-slate-500">คลิกหรือลากไฟล์วาง</p>
                                <p class="text-[10px] text-slate-400 mt-1 font-bold">รองรับ JPG, PNG, PDF</p>
                            </div>
                        </div>
                        <div id="file_list_preview" class="flex flex-wrap gap-2"></div>
                    </div>

                    <div class="space-y-4">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 block">ไฟล์ในระบบปัจจุบัน</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <?php if($attachments_res->num_rows > 0): ?>
                                <?php while($f = $attachments_res->fetch_assoc()): 
                                    $ext = strtolower(pathinfo($f['file_path'], PATHINFO_EXTENSION));
                                    $is_img = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                                ?>
                                <div class="relative group aspect-square rounded-2xl overflow-hidden border border-slate-100 bg-slate-50 shadow-sm" id="file-row-<?= $f['id'] ?>">
                                    <?php if($is_img): ?>
                                        <img src="uploads/projects/<?= $f['file_path'] ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                    <?php else: ?>
                                        <div class="w-full h-full flex flex-col items-center justify-center p-2 text-center">
                                            <i data-lucide="file-text" class="text-slate-300" size="28"></i>
                                            <p class="text-[8px] font-black text-slate-400 mt-2 truncate w-full uppercase"><?= $ext ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <button type="button" onclick="deleteFile(<?= $f['id'] ?>)" class="p-2.5 bg-rose-600 text-white rounded-xl hover:bg-rose-700 shadow-xl transform scale-75 group-hover:scale-100 transition-transform">
                                            <i data-lucide="trash-2" size="16"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-span-full py-12 border-2 border-dashed border-slate-50 rounded-[2rem] text-center text-slate-300 italic text-xs uppercase tracking-widest">No attachments found</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ส่วนที่ 3: สถานะโครงการ -->
            <div class="space-y-6 pt-10 border-t border-slate-50">
                <h3 class="text-lg font-black text-slate-800 border-l-4 border-orange-600 pl-4 uppercase">3. การเปลี่ยนแปลงสถานะ</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php 
                    $st_list = ['รอดำเนินการ', 'กำลังดำเนินการ', 'เสร็จสิ้น', 'มีการเปลี่ยนแปลงหรือแก้ไข'];
                    foreach($st_list as $st): 
                    ?>
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="status" value="<?= $st ?>" <?= $project['status'] == $st ? 'checked' : '' ?> onclick="toggleRemark(<?= $st === 'มีการเปลี่ยนแปลงหรือแก้ไข' ? 'true' : 'false' ?>)" class="peer sr-only">
                        <div class="p-5 bg-white border-2 border-slate-100 rounded-2xl text-center peer-checked:border-<?= $st=='มีการเปลี่ยนแปลงหรือแก้ไข' ? 'rose' : ($st=='เสร็จสิ้น' ? 'emerald' : 'slate') ?>-600 peer-checked:bg-<?= $st=='มีการเปลี่ยนแปลงหรือแก้ไข' ? 'rose' : ($st=='เสร็จสิ้น' ? 'emerald' : 'slate') ?>-600 peer-checked:text-white transition-all shadow-sm">
                            <p class="text-[10px] font-black uppercase"><?= $st ?></p>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div id="remarkContainer" class="<?= $project['status'] === 'มีการเปลี่ยนแปลงหรือแก้ไข' ? '' : 'hidden' ?> animate-in slide-in-from-top-2">
                    <textarea name="status_remark" placeholder="ระบุรายละเอียดเหตุผลการแก้ไขสถานะ..." class="w-full p-6 bg-rose-50 border-2 border-rose-100 rounded-[2.5rem] font-bold text-slate-700 outline-none focus:border-rose-500 min-h-[140px] shadow-sm"><?= e($project['status_remark']) ?></textarea>
                </div>
            </div>

            <!-- ข้อมูลพิกัดและงบประมาณ -->
            <div class="space-y-4 pt-10 border-t border-slate-50">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ปีงบประมาณ</label>
                        <input type="number" name="fiscal_year" value="<?= $project['fiscal_year'] ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-bold">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">งบประมาณดำเนินการ</label>
                        <input type="number" step="0.01" name="budget_amount" value="<?= $project['budget_amount'] ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-bold text-orange-600 text-lg">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="start_lat" value="<?= $project['start_lat'] ?>" placeholder="Lat Start" class="p-3 bg-slate-50 rounded-xl text-xs font-bold border-2 border-slate-50">
                    <input type="text" name="start_long" value="<?= $project['start_long'] ?>" placeholder="Lng Start" class="p-3 bg-slate-50 rounded-xl text-xs font-bold border-2 border-slate-50">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="end_lat" value="<?= $project['end_lat'] ?>" placeholder="Lat End" class="p-3 bg-slate-50 rounded-xl text-xs font-bold border-2 border-slate-50">
                    <input type="text" name="end_long" value="<?= $project['end_long'] ?>" placeholder="Lng End" class="p-3 bg-slate-50 rounded-xl text-xs font-bold border-2 border-slate-50">
                </div>
            </div>

            <button type="submit" class="w-full bg-slate-900 hover:bg-orange-600 text-white font-black py-8 rounded-[3rem] shadow-2xl transition-all active:scale-95 text-xl mt-10 uppercase tracking-[0.2em]">
                บันทึกการแก้ไขโครงการ
            </button>
        </form>
    </div>
</div>

<script>
    /**
     * ฟังก์ชันคำนวณพื้นที่แบบแยกตามประเภทถนน
     */
    function calculateArea() {
        const type = document.getElementById('p_type').value;
        const w = parseFloat(document.getElementById('width_m').value) || 0;
        const l = parseFloat(document.getElementById('distance_m').value) || 0;
        const s = parseFloat(document.getElementById('shoulder_m').value) || 0;
        
        const shoulderInput = document.getElementById('shoulder_m');
        const badge = document.getElementById('formula_badge');
        const desc = document.getElementById('formula_desc');
        let area = 0;

        // อาร์เรย์เก็บชื่อถนนที่ต้องคิดไหล่ทาง
        const asphaltTypes = ['ถนนลาดยาง (Tack Coat)', 'ถนนลาดยาง (Recycling)'];

        if (asphaltTypes.includes(type)) {
            // สูตรลาดยาง: (กว้าง + ไหล่ทาง2ข้าง) x ยาว
            area = (w + (s * 2)) * l;
            shoulderInput.readOnly = false;
            shoulderInput.classList.remove('opacity-30', 'bg-slate-50', 'cursor-not-allowed');
            badge.className = "px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-wider border bg-emerald-50 text-emerald-600 border-emerald-100";
            badge.innerText = "สูตรถนนลาดยาง";
            desc.innerHTML = '<i data-lucide="info" size="14"></i> คิดพื้นที่รวมไหล่ทาง 2 ข้าง [(กว้าง + (ไหล่ทางx2)) x ยาว]';
        } else {
            // สูตรอื่นๆ (คอนกรีต, ลูกรัง ฯลฯ): กว้าง x ยาว
            area = w * l;
            shoulderInput.readOnly = true; // ล็อกช่องพิมพ์
            shoulderInput.classList.add('opacity-30', 'bg-slate-50', 'cursor-not-allowed'); // ทำสีจางให้รู้ว่าพิมพ์ไม่ได้
            badge.className = "px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-wider border bg-blue-50 text-blue-600 border-blue-100";
            badge.innerText = "สูตรถนนทั่วไป";
            desc.innerHTML = '<i data-lucide="info" size="14"></i> คำนวณเฉพาะพื้นที่ผิวทางหลัก [กว้าง x ยาว] ไม่นำไหล่ทางมาคำนวณรวม';
        }

        document.getElementById('total_area').value = area.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        
        if(typeof lucide !== 'undefined') lucide.createIcons();
    }

    function previewFiles(input) {
        const preview = document.getElementById('file_list_preview');
        preview.innerHTML = "";
        for (const file of input.files) {
            const span = document.createElement('span');
            span.className = "bg-blue-50 text-blue-600 px-4 py-1.5 rounded-full text-[9px] font-black uppercase border border-blue-100 animate-in zoom-in";
            span.innerText = file.name;
            preview.appendChild(span);
        }
    }

    async function deleteFile(fileId) {
        if (!confirm('ยืนยันการลบไฟล์นี้ถาวร?')) return;
        try {
            const response = await fetch(`delete_attachment.php?id=${fileId}`);
            const result = await response.json();
            if (result.success) {
                const element = document.getElementById(`file-row-${fileId}`);
                element.style.transition = 'all 0.5s ease';
                element.style.opacity = '0';
                element.style.transform = 'scale(0.5)';
                setTimeout(() => element.remove(), 500);
            } else {
                alert('Error: ' + result.error);
            }
        } catch (e) {
            alert('Cannot delete file at this moment.');
        }
    }

    function toggleRemark(show) {
        const container = document.getElementById('remarkContainer');
        container.classList.toggle('hidden', !show);
        if(show) container.querySelector('textarea').focus();
    }

    window.onload = () => {
        calculateArea();
        if(typeof lucide !== 'undefined') lucide.createIcons();
    };
</script>

<?php include 'includes/footer.php'; ?>