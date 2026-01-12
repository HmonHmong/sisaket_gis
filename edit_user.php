<?php
// edit_user.php
// ที่อยู่ไฟล์: /edit_user.php
require_once 'auth_check.php';
require_once 'config/db.php';

// ตรวจสอบสิทธิ์ Admin
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

// ดึงข้อมูลผู้ใช้งานเดิม
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

if (!$user_data) {
    header("Location: users.php");
    exit;
}

// ประมวลผลการแก้ไข
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $position = $conn->real_escape_string($_POST['position']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    
    // อัปเดตรหัสผ่านเฉพาะกรณีที่มีการกรอกมาใหม่
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE users SET full_name=?, position=?, role=?, status=?, password=? WHERE id=?";
        $stmt_upd = $conn->prepare($sql);
        $stmt_upd->bind_param("sssssi", $full_name, $position, $role, $status, $password, $id);
    } else {
        $sql = "UPDATE users SET full_name=?, position=?, role=?, status=? WHERE id=?";
        $stmt_upd = $conn->prepare($sql);
        $stmt_upd->bind_param("ssssi", $full_name, $position, $role, $status, $id);
    }

    if ($stmt_upd->execute()) {
        $msg = "อัปเดตข้อมูลเจ้าหน้าที่เรียบร้อยแล้ว";
        // ดึงข้อมูลใหม่มาแสดงในฟอร์ม
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

include 'includes/header.php';
?>

<div class="max-w-2xl mx-auto pb-20 animate-in fade-in duration-500">
    <div class="mb-6">
        <a href="users.php" class="text-slate-500 hover:text-orange-600 flex items-center gap-2 font-bold transition-all">
            <i data-lucide="arrow-left" size="18"></i> ย้อนกลับ
        </a>
    </div>

    <div class="bg-white rounded-[3rem] shadow-xl border border-slate-100 overflow-hidden">
        <div class="bg-orange-600 p-8 text-white flex items-center gap-4">
            <div class="w-12 h-12 bg-white text-orange-600 rounded-2xl flex items-center justify-center shadow-lg">
                <i data-lucide="user-cog" size="24"></i>
            </div>
            <div>
                <h2 class="text-xl font-black">แก้ไขข้อมูลเจ้าหน้าที่</h2>
                <p class="text-orange-100 text-xs font-bold uppercase tracking-widest mt-1">Edit Staff Information</p>
            </div>
        </div>

        <form method="POST" class="p-8 md:p-10 space-y-6">
            <?php if($msg): ?>
                <div class="bg-emerald-50 text-emerald-600 p-4 rounded-2xl border border-emerald-100 font-bold text-sm flex items-center gap-2 animate-bounce">
                    <i data-lucide="check-circle"></i> <?= $msg ?>
                </div>
            <?php endif; ?>

            <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100 mb-4">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">ชื่อผู้ใช้งาน (แก้ไขไม่ได้)</p>
                <p class="text-lg font-black text-slate-700"><?= htmlspecialchars($user_data['username']) ?></p>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อ-นามสกุล</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user_data['full_name']) ?>" required class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ตำแหน่ง</label>
                    <input type="text" name="position" value="<?= htmlspecialchars($user_data['position']) ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">สถานะบัญชี</label>
                    <select name="status" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500">
                        <option value="active" <?= $user_data['status'] == 'active' ? 'selected' : '' ?>>เปิดใช้งาน (Active)</option>
                        <option value="inactive" <?= $user_data['status'] == 'inactive' ? 'selected' : '' ?>>ระงับใช้งาน (Inactive)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">สิทธิ์การใช้งาน</label>
                    <select name="role" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500">
                        <option value="staff" <?= $user_data['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="admin" <?= $user_data['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="viewer" <?= $user_data['role'] == 'viewer' ? 'selected' : '' ?>>Viewer</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">เปลี่ยนรหัสผ่าน (เว้นว่างถ้าไม่เปลี่ยน)</label>
                    <input type="password" name="password" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all" placeholder="รหัสผ่านใหม่">
                </div>
            </div>

            <button type="submit" class="w-full bg-slate-900 hover:bg-orange-600 text-white font-black py-4 rounded-2xl shadow-xl transition-all active:scale-95 text-lg mt-4">
                บันทึกการเปลี่ยนแปลง
            </button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>