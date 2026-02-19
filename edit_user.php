<?php
// edit_user.php - ระบบแก้ไขข้อมูลเจ้าหน้าที่พร้อมแถบค้นหาโครงการอัจฉริยะ
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$id = intval($_GET['id']);
$msg = "";
$error = "";

// ดึงข้อมูลเจ้าหน้าที่เดิม
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

if (!$user_data) {
    header("Location: users.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $position = $conn->real_escape_string($_POST['position']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE users SET full_name=?, position=?, phone=?, role=?, status=?, password=? WHERE id=?";
        $stmt_upd = $conn->prepare($sql);
        $stmt_upd->bind_param("ssssssi", $full_name, $position, $phone, $role, $status, $password, $id);
    } else {
        $sql = "UPDATE users SET full_name=?, position=?, phone=?, role=?, status=? WHERE id=?";
        $stmt_upd = $conn->prepare($sql);
        $stmt_upd->bind_param("sssssi", $full_name, $position, $phone, $role, $status, $id);
    }

    if ($stmt_upd->execute()) {
        $msg = "อัปเดตข้อมูลเจ้าหน้าที่เรียบร้อยแล้ว";
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

include 'includes/header.php';
?>

<div class="max-w-5xl mx-auto pb-20 animate-in fade-in duration-500">
    <!-- Action Bar: ปรับปรุงให้มีปุ่มค้นหาโครงการที่ชัดเจน -->
    <div class="flex flex-col lg:flex-row justify-between items-center mb-8 gap-4 px-2">
        <a href="users.php" class="text-slate-500 hover:text-orange-600 flex items-center gap-2 font-bold transition-all group shrink-0">
            <i data-lucide="arrow-left" size="18" class="group-hover:-translate-x-1 transition-transform"></i> 
            <span>ย้อนกลับไปจัดการเจ้าหน้าที่</span>
        </a>
        
        <div class="flex items-center gap-3 w-full lg:w-auto">
            <!-- ปุ่มค้นหาโครงการ (Link ไปหน้ารวมพร้อม Focus ช่องค้นหา) -->
            <a href="projects.php" class="bg-white border-2 border-slate-100 text-slate-700 px-5 py-3 rounded-2xl font-black text-xs shadow-sm hover:border-orange-500 hover:text-orange-600 transition-all flex items-center gap-2 group whitespace-nowrap">
                <i data-lucide="search" size="16" class="group-hover:scale-110 transition-transform"></i>
                <span>ค้นหาโครงการ</span>
            </a>

            <!-- แถบค้นหาด่วน -->
            <form action="projects.php" method="GET" class="relative flex-1 lg:w-72 group">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-orange-600 transition-colors">
                    <i data-lucide="file-search" size="18"></i>
                </div>
                <input type="text" name="search" placeholder="ระบุชื่อโครงการ..." 
                       class="w-full pl-11 pr-4 py-3 bg-slate-50 border-2 border-transparent rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all font-bold text-sm shadow-inner">
            </form>
        </div>
    </div>

    <div class="bg-white rounded-[3.5rem] shadow-2xl border border-slate-100 overflow-hidden">
        <!-- Header Section -->
        <div class="bg-orange-600 p-10 text-white relative overflow-hidden text-center">
            <div class="absolute right-0 top-0 opacity-10 -mr-10 -mt-10"><i data-lucide="user-cog" size="180"></i></div>
            <h2 class="text-3xl font-black relative z-10 uppercase tracking-tight">แก้ไขข้อมูลเจ้าหน้าที่</h2>
            <p class="text-orange-100 text-xs font-black uppercase tracking-[0.2em] mt-1 relative z-10">Account Configuration</p>
        </div>

        <form method="POST" class="p-8 md:p-12 space-y-8">
            <?php if($msg): ?>
                <div class="bg-emerald-50 text-emerald-600 p-5 rounded-2xl border border-emerald-100 font-bold flex items-center gap-3 animate-bounce">
                    <i data-lucide="check-circle" size="24"></i> <?= $msg ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">ชื่อผู้ใช้งาน</label>
                    <input type="text" value="<?= e($user_data['username']) ?>" disabled 
                           class="w-full p-4 bg-slate-100 border-2 border-slate-100 rounded-2xl font-bold text-slate-500 cursor-not-allowed outline-none">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">ชื่อ-นามสกุล</label>
                    <input type="text" name="full_name" value="<?= e($user_data['full_name']) ?>" required 
                           class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all font-bold text-slate-700">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">ตำแหน่งงาน</label>
                    <input type="text" name="position" value="<?= e($user_data['position']) ?>" 
                           class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all font-bold text-slate-700">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-orange-600 uppercase tracking-widest ml-2">เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" value="<?= e($user_data['phone']) ?>" placeholder="0XX-XXX-XXXX" 
                           class="w-full p-4 bg-orange-50/30 border-2 border-orange-100 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all font-black text-slate-700">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">ระดับสิทธิ์</label>
                    <select name="role" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-bold outline-none focus:border-orange-500">
                        <option value="staff" <?= $user_data['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="admin" <?= $user_data['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="viewer" <?= $user_data['role'] == 'viewer' ? 'selected' : '' ?>>Viewer</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">สถานะ</label>
                    <select name="status" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl font-bold outline-none focus:border-orange-500">
                        <option value="active" <?= $user_data['status'] == 'active' ? 'selected' : '' ?>>ใช้งานปกติ</option>
                        <option value="inactive" <?= $user_data['status'] == 'inactive' ? 'selected' : '' ?>>ระงับใช้งาน</option>
                    </select>
                </div>
            </div>

            <div class="pt-6 border-t border-slate-50">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">เปลี่ยนรหัสผ่าน (หากต้องการ)</label>
                <input type="password" name="password" placeholder="ระบุรหัสผ่านใหม่" 
                       class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 transition-all font-bold">
            </div>

            <button type="submit" class="w-full bg-slate-900 hover:bg-orange-600 text-white font-black py-6 rounded-[2.5rem] shadow-2xl transition-all active:scale-95 text-xl mt-4 uppercase tracking-widest">
                บันทึกการเปลี่ยนแปลง
            </button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>