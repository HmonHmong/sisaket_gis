<?php
// add_user.php - ระบบเพิ่มเจ้าหน้าที่ใหม่ พร้อมช่องกรอกเบอร์โทรศัพท์
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// ตรวจสอบสิทธิ์ (เฉพาะ Admin เท่านั้นที่เข้าหน้านี้ได้)
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
    $phone = $conn->real_escape_string($_POST['phone']); // รับค่าเบอร์โทรศัพท์
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // ตรวจสอบชื่อผู้ใช้งานซ้ำ
    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if($check->num_rows > 0) {
        $error = "ชื่อผู้ใช้งานนี้มีอยู่ในระบบแล้ว กรุณาใช้ชื่ออื่น";
    } else {
        $sql = "INSERT INTO users (username, password, full_name, position, phone, role, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'active')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $username, $password, $full_name, $position, $phone, $role);
        
        if($stmt->execute()) {
            $msg = "ลงทะเบียนเจ้าหน้าที่เรียบร้อยแล้ว";
            // บันทึกกิจกรรมลงใน Notification
            add_notification($conn, "เพิ่มเจ้าหน้าที่ใหม่", "เพิ่ม $full_name เข้าสู่ระบบโดย " . $_SESSION['full_name'], 'user');
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

include 'includes/header.php';
?>

<div class="max-w-3xl mx-auto pb-20 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="users.php" class="text-slate-500 hover:text-orange-600 flex items-center gap-2 font-bold transition-all group">
            <i data-lucide="arrow-left" size="18" class="group-hover:-translate-x-1 transition-transform"></i> 
            ย้อนกลับไปหน้าจัดการเจ้าหน้าที่
        </a>
    </div>

    <div class="bg-white rounded-[3.5rem] shadow-2xl border border-slate-100 overflow-hidden">
        <!-- Header Section -->
        <div class="bg-slate-900 p-10 text-center text-white relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-10 -mr-10 -mt-10"><i data-lucide="user-plus" size="180"></i></div>
            <h2 class="text-3xl font-black tracking-tight relative z-10">เพิ่มเจ้าหน้าที่ใหม่</h2>
            <p class="text-orange-500 text-xs font-black uppercase tracking-[0.2em] mt-1 relative z-10">Create New Authorized Account</p>
        </div>

        <form method="POST" class="p-8 md:p-12 space-y-10">
            <?php if($msg): ?>
                <div class="bg-emerald-50 text-emerald-600 p-5 rounded-2xl border border-emerald-100 font-bold flex items-center gap-3 animate-bounce">
                    <i data-lucide="check-circle" size="24"></i> <?= $msg ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="bg-red-50 text-red-600 p-5 rounded-2xl border border-red-100 font-bold flex items-center gap-3">
                    <i data-lucide="alert-circle" size="24"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- ส่วนที่ 1: ข้อมูลบัญชี -->
            <div class="space-y-6">
                <div class="flex items-center gap-3 mb-2">
                    <span class="w-8 h-px bg-slate-200"></span>
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">ข้อมูลบัญชีผู้ใช้งาน</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] ml-2">ชื่อผู้ใช้งาน (Username)</label>
                        <input type="text" name="username" required placeholder="เช่น somchai_s" 
                               class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all font-bold text-slate-700">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] ml-2">รหัสผ่านเริ่มต้น (Password)</label>
                        <input type="password" name="password" required placeholder="อย่างน้อย 6 ตัวอักษร" 
                               class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all font-bold text-slate-700">
                    </div>
                </div>
            </div>

            <!-- ส่วนที่ 2: ข้อมูลส่วนตัว -->
            <div class="space-y-6 pt-4">
                <div class="flex items-center gap-3 mb-2">
                    <span class="w-8 h-px bg-slate-200"></span>
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">ข้อมูลส่วนตัวและช่องทางติดต่อ</h3>
                </div>
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] ml-2">ชื่อ-นามสกุล</label>
                        <input type="text" name="full_name" required placeholder="ระบุชื่อจริง-นามสกุล" 
                               class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all font-bold text-slate-700">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] ml-2">ตำแหน่งงาน</label>
                            <input type="text" name="position" placeholder="เช่น วิศวกรโยธาชำนาญการ" 
                                   class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all font-bold text-slate-700">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-orange-600 uppercase tracking-[0.15em] ml-2">เบอร์โทรศัพท์ (ติดต่อ)</label>
                            <div class="relative group">
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-orange-500">
                                    <i data-lucide="phone" size="18"></i>
                                </div>
                                <input type="text" name="phone" placeholder="0XX-XXX-XXXX" 
                                       class="w-full pl-12 pr-4 py-4 bg-orange-50/30 border-2 border-orange-100 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all font-black text-slate-700 shadow-sm">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ส่วนที่ 3: ระดับสิทธิ์ -->
            <div class="space-y-6 pt-4">
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-8 h-px bg-slate-200"></span>
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">ระดับสิทธิ์การใช้งานระบบ</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="role" value="admin" class="peer sr-only">
                        <div class="p-6 bg-slate-50 border-2 border-slate-100 rounded-[2rem] text-center peer-checked:border-orange-500 peer-checked:bg-white peer-checked:shadow-xl transition-all h-full">
                            <div class="w-10 h-10 bg-slate-200 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                                <i data-lucide="shield-check" size="20" class="text-slate-600"></i>
                            </div>
                            <p class="text-xs font-black text-slate-800 uppercase">Administrator</p>
                            <p class="text-[9px] text-slate-400 mt-1 font-bold">จัดการทุกอย่างในระบบได้</p>
                        </div>
                    </label>

                    <label class="relative cursor-pointer group">
                        <input type="radio" name="role" value="staff" checked class="peer sr-only">
                        <div class="p-6 bg-slate-50 border-2 border-slate-100 rounded-[2rem] text-center peer-checked:border-orange-500 peer-checked:bg-white peer-checked:shadow-xl transition-all h-full">
                            <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                                <i data-lucide="edit-3" size="20" class="text-orange-600"></i>
                            </div>
                            <p class="text-xs font-black text-slate-800 uppercase">Staff (เจ้าหน้าที่)</p>
                            <p class="text-[9px] text-slate-400 mt-1 font-bold">จัดการข้อมูลโครงสร้างพื้นฐาน</p>
                        </div>
                    </label>

                    <label class="relative cursor-pointer group">
                        <input type="radio" name="role" value="viewer" class="peer sr-only">
                        <div class="p-6 bg-slate-50 border-2 border-slate-100 rounded-[2rem] text-center peer-checked:border-orange-500 peer-checked:bg-white peer-checked:shadow-xl transition-all h-full">
                            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                                <i data-lucide="eye" size="20" class="text-blue-600"></i>
                            </div>
                            <p class="text-xs font-black text-slate-800 uppercase">Viewer (ผู้ดูข้อมูล)</p>
                            <p class="text-[9px] text-slate-400 mt-1 font-bold">ดูรายงานและแผนที่ได้เท่านั้น</p>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" class="w-full bg-slate-900 hover:bg-orange-600 text-white font-black py-6 rounded-[2.5rem] shadow-2xl transition-all active:scale-[0.98] text-xl flex items-center justify-center gap-3 group mt-10 uppercase tracking-widest">
                <span>ลงทะเบียนเจ้าหน้าที่ใหม่</span>
                <i data-lucide="user-plus" size="24" class="group-hover:translate-x-1 transition-transform"></i>
            </button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>