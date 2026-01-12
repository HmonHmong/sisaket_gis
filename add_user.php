<?php
// add_user.php
// ที่อยู่ไฟล์: /add_user.php
require_once 'auth_check.php';
require_once 'config/db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$msg = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $position = $conn->real_escape_string($_POST['position']);
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // เข้ารหัสผ่าน

    // ตรวจสอบ Username ซ้ำ
    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if($check->num_rows > 0) {
        $error = "ชื่อผู้ใช้งานนี้มีอยู่ในระบบแล้ว";
    } else {
        $sql = "INSERT INTO users (username, password, full_name, position, role) 
                VALUES ('$username', '$password', '$full_name', '$position', '$role')";
        if($conn->query($sql)) {
            $msg = "เพิ่มเจ้าหน้าที่ใหม่เรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

include 'includes/header.php';
?>

<div class="max-w-2xl mx-auto pb-20 animate-in slide-in-from-bottom-4 duration-500">
    <div class="mb-6">
        <a href="users.php" class="text-slate-500 hover:text-orange-600 flex items-center gap-2 font-bold transition-all">
            <i data-lucide="arrow-left" size="18"></i> ย้อนกลับไปหน้าจัดการ
        </a>
    </div>

    <div class="bg-white rounded-[3rem] shadow-xl border border-slate-100 overflow-hidden">
        <div class="bg-slate-900 p-8 text-white flex items-center gap-4">
            <div class="w-12 h-12 bg-orange-600 rounded-2xl flex items-center justify-center">
                <i data-lucide="user-plus" size="24"></i>
            </div>
            <div>
                <h2 class="text-xl font-black">ลงทะเบียนเจ้าหน้าที่ใหม่</h2>
                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-1">New Staff Registration</p>
            </div>
        </div>

        <form method="POST" class="p-8 md:p-10 space-y-6">
            <?php if($msg): ?>
                <div class="bg-emerald-50 text-emerald-600 p-4 rounded-2xl border border-emerald-100 font-bold text-sm flex items-center gap-2 animate-bounce">
                    <i data-lucide="check-circle"></i> <?= $msg ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-2xl border border-red-100 font-bold text-sm flex items-center gap-2">
                    <i data-lucide="alert-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อผู้ใช้งาน (Username)</label>
                    <input type="text" name="username" required class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">รหัสผ่าน (Password)</label>
                    <input type="password" name="password" required class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all">
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อ-นามสกุล</label>
                <input type="text" name="full_name" required class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ตำแหน่ง</label>
                    <input type="text" name="position" placeholder="เช่น วิศวกรโยธา" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">สิทธิ์การใช้งาน</label>
                    <select name="role" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500">
                        <option value="staff">Staff (บันทึกข้อมูลทั่วไป)</option>
                        <option value="admin">Admin (จัดการระบบและผู้ใช้)</option>
                        <option value="viewer">Viewer (ดูข้อมูลได้อย่างเดียว)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="w-full bg-slate-900 hover:bg-orange-600 text-white font-black py-4 rounded-2xl shadow-xl transition-all active:scale-95 text-lg mt-4">
                บันทึกรายชื่อเจ้าหน้าที่
            </button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>