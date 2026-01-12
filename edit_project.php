<?php
// edit_project.php
// ที่อยู่ไฟล์: /edit_project.php
include 'config/db.php';

if (!isset($_GET['id'])) {
    header("Location: projects.php");
    exit;
}

$id = intval($_GET['id']);

// 1. ดึงข้อมูลโครงการหลัก
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    echo "ไม่พบข้อมูลโครงการ";
    exit;
}

// 2. ดึงข้อมูลจุดเชื่อมต่อ (หมู่บ้าน)
$points_res = $conn->query("SELECT * FROM project_points WHERE project_id = $id ORDER BY order_index ASC");
$points = [];
while($row = $points_res->fetch_assoc()) {
    $points[] = $row;
}

// 3. จัดการการอัปเดตข้อมูล (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $project_name = $_POST['project_name'];
    $fiscal_year = $_POST['fiscal_year'];
    $distance = $_POST['distance'];
    $width = $_POST['width'];
    $has_shoulder = isset($_POST['has_shoulder']) ? 1 : 0;
    $shoulder_width = $_POST['shoulder_width'] ?: 0;
    $budget_amount = $_POST['budget_amount'];
    $start_lat = $_POST['start_lat'];
    $start_long = $_POST['start_long'];
    $end_lat = $_POST['end_lat'];
    $end_long = $_POST['end_long'];
    
    // คำนวณพื้นที่ใหม่
    $total_width = $width + ($has_shoulder ? $shoulder_width : 0);
    $area = $distance * $total_width;

    $update_sql = "UPDATE projects SET 
                    project_name=?, fiscal_year=?, distance=?, width=?, 
                    has_shoulder=?, shoulder_width=?, area=?, budget_amount=?, 
                    start_lat=?, start_long=?, end_lat=?, end_long=? 
                   WHERE id=?";
    $stmt_upd = $conn->prepare($update_sql);
    $stmt_upd->bind_param("sidiiiddddddi", 
        $project_name, $fiscal_year, $distance, $width, 
        $has_shoulder, $shoulder_width, $area, $budget_amount, 
        $start_lat, $start_long, $end_lat, $end_long, $id
    );
    
    if ($stmt_upd->execute()) {
        // อัปเดตจุดเชื่อมต่อ (ลบของเก่าแล้วเพิ่มใหม่เป็นวิธีที่สะอาดที่สุด)
        $conn->query("DELETE FROM project_points WHERE project_id = $id");
        if(isset($_POST['villages'])) {
            foreach($_POST['villages'] as $index => $vName) {
                if(empty($vName)) continue;
                $vSub = $_POST['sub_districts'][$index];
                $vDist = $_POST['districts'][$index];
                $stmt_pt = $conn->prepare("INSERT INTO project_points (project_id, village, sub_district, district, province, order_index) VALUES (?, ?, ?, ?, 'ศรีสะเกษ', ?)");
                $stmt_pt->bind_param("isssi", $id, $vName, $vSub, $vDist, $index);
                $stmt_pt->execute();
            }
        }
        header("Location: projects.php?updated=1");
        exit;
    }
}

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto pb-20">
    <div class="mb-6 flex items-center justify-between">
        <a href="projects.php" class="text-slate-500 hover:text-orange-600 flex items-center gap-2 font-bold transition-all">
            <i data-lucide="arrow-left" size="18"></i> ย้อนกลับ
        </a>
        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">ID โครงการ: #<?= $id ?></span>
    </div>

    <div class="bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden">
        <div class="bg-orange-600 p-6 text-white flex items-center gap-3">
            <i data-lucide="edit-3" size="32"></i>
            <div>
                <h2 class="text-xl font-bold">แก้ไขข้อมูลโครงการ</h2>
                <p class="text-orange-100 text-sm">ปรับปรุงรายละเอียดทางวิศวกรรมและแผนที่</p>
            </div>
        </div>

        <form method="POST" class="p-8 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 mb-2 uppercase">ชื่อโครงการ</label>
                    <input type="text" name="project_name" value="<?= htmlspecialchars($project['project_name']) ?>" class="w-full p-3 border-2 border-slate-100 rounded-xl focus:border-orange-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-2 uppercase">ปีงบประมาณ</label>
                    <input type="number" name="fiscal_year" value="<?= $project['fiscal_year'] ?>" class="w-full p-3 border-2 border-slate-100 rounded-xl outline-none">
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="text-xs font-black text-slate-400 uppercase flex items-center gap-2"><i data-lucide="map-pin" size="14"></i> ข้อมูลสถานที่/สายทาง</h3>
                <div id="points-container" class="space-y-4">
                    <?php if(empty($points)): ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-slate-50 rounded-2xl">
                            <input type="text" name="villages[]" placeholder="หมู่บ้าน" class="p-2 border rounded-lg outline-none">
                            <input type="text" name="sub_districts[]" placeholder="ตำบล" class="p-2 border rounded-lg outline-none">
                            <select name="districts[]" class="p-2 border rounded-lg outline-none bg-white">
                                <option>เมืองศรีสะเกษ</option><option>วังหิน</option><option>ขุขันธ์</option>
                            </select>
                        </div>
                    <?php else: ?>
                        <?php foreach($points as $pt): ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <input type="text" name="villages[]" value="<?= htmlspecialchars($pt['village']) ?>" placeholder="หมู่บ้าน" class="p-2 border rounded-lg outline-none">
                            <input type="text" name="sub_districts[]" value="<?= htmlspecialchars($pt['sub_district']) ?>" placeholder="ตำบล" class="p-2 border rounded-lg outline-none">
                            <select name="districts[]" class="p-2 border rounded-lg outline-none bg-white">
                                <option <?= $pt['district'] == 'เมืองศรีสะเกษ' ? 'selected' : '' ?>>เมืองศรีสะเกษ</option>
                                <option <?= $pt['district'] == 'วังหิน' ? 'selected' : '' ?>>วังหิน</option>
                                <option <?= $pt['district'] == 'ขุขันธ์' ? 'selected' : '' ?>>ขุขันธ์</option>
                                <option <?= $pt['district'] == 'กันทรลักษ์' ? 'selected' : '' ?>>กันทรลักษ์</option>
                            </select>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-900 p-6 rounded-[2rem] shadow-inner">
                <div class="space-y-2">
                    <p class="text-orange-500 text-[10px] font-bold uppercase border-l-2 border-orange-500 pl-2">พิกัดจุดเริ่มต้น</p>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="start_lat" value="<?= $project['start_lat'] ?>" placeholder="Lat" class="bg-slate-800 text-white p-2 rounded-lg text-sm outline-none border-none focus:ring-1 focus:ring-orange-500">
                        <input type="text" name="start_long" value="<?= $project['start_long'] ?>" placeholder="Long" class="bg-slate-800 text-white p-2 rounded-lg text-sm outline-none border-none focus:ring-1 focus:ring-orange-500">
                    </div>
                </div>
                <div class="space-y-2">
                    <p class="text-blue-500 text-[10px] font-bold uppercase border-l-2 border-blue-500 pl-2">พิกัดจุดสิ้นสุด</p>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="end_lat" value="<?= $project['end_lat'] ?>" placeholder="Lat" class="bg-slate-800 text-white p-2 rounded-lg text-sm outline-none border-none focus:ring-1 focus:ring-blue-500">
                        <input type="text" name="end_long" value="<?= $project['end_long'] ?>" placeholder="Long" class="bg-slate-800 text-white p-2 rounded-lg text-sm outline-none border-none focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-2">ระยะทาง (ม.)</label>
                    <input type="number" name="distance" value="<?= $project['distance'] ?>" class="w-full p-3 border-2 border-slate-100 rounded-xl outline-none font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-2">ความกว้าง (ม.)</label>
                    <input type="number" step="0.01" name="width" value="<?= $project['width'] ?>" class="w-full p-3 border-2 border-slate-100 rounded-xl outline-none font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-2">งบประมาณ (บาท)</label>
                    <input type="number" name="budget_amount" value="<?= $project['budget_amount'] ?>" class="w-full p-3 border-2 border-slate-100 rounded-xl outline-none font-mono">
                </div>
                <div class="flex flex-col justify-end">
                    <label class="inline-flex items-center cursor-pointer mb-2">
                        <input type="checkbox" name="has_shoulder" class="sr-only peer" <?= $project['has_shoulder'] ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:bg-orange-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                        <span class="ml-2 text-xs font-bold text-slate-500">ไหล่ทาง</span>
                    </label>
                    <input type="number" step="0.01" name="shoulder_width" value="<?= $project['shoulder_width'] ?>" placeholder="กว้างไหล่ทาง" class="w-full p-2 border-2 border-slate-100 rounded-xl outline-none text-sm">
                </div>
            </div>

            <div class="pt-6 border-t border-slate-100 flex gap-4">
                <button type="submit" class="flex-1 bg-slate-900 hover:bg-orange-600 text-white font-black py-4 rounded-2xl shadow-xl transition-all text-lg uppercase">
                    บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>