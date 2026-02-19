<?php
// profile.php - ระบบจัดการข้อมูลส่วนตัวและรหัสผ่านของเจ้าหน้าที่
require_once 'auth_check.php';
require_once 'config/db.php';

$id = $_SESSION['user_id'];
$msg = "";
$error = "";

// 1. ดึงข้อมูลปัจจุบันของผู้ใช้งานด้วย Prepared Statement
$stmt = $conn->prepare("SELECT username, full_name, position, role, password, status FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// 2. ประมวลผลเมื่อมีการกดบันทึกแก้ไข
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $position = $_POST['position'];
    
    // กรณีที่ 1: อัปเดตข้อมูลทั่วไปและเปลี่ยนรหัสผ่านใหม่
    if (!empty($_POST['new_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // ตรวจสอบรหัสผ่านเดิมก่อน
        if (password_verify($current_password, $user_data['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET full_name = ?, position = ?, password = ? WHERE id = ?";
                    $stmt_upd = $conn->prepare($sql);
                    $stmt_upd->bind_param("sssi", $full_name, $position, $hashed_password, $id);
                } else {
                    $error = "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
                }
            } else {
                $error = "รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน";
            }
        } else {
            $error = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
        }
    } 
    // กรณีที่ 2: อัปเดตเฉพาะชื่อและตำแหน่ง
    else {
        $sql = "UPDATE users SET full_name = ?, position = ? WHERE id = ?";
        $stmt_upd = $conn->prepare($sql);
        $stmt_upd->bind_param("ssi", $full_name, $position, $id);
    }

    // ทำการ Execute และตรวจสอบผล
    if (empty($error)) {
        if ($stmt_upd->execute()) {
            $msg = "อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว";
            $_SESSION['full_name'] = $full_name; // อัปเดตชื่อใน Session ทันที
            
            // ดึงข้อมูลใหม่มาแสดง
            $stmt->execute();
            $user_data = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล";
        }
    }
}

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto pb-20 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 bg-slate-900 text-white rounded-2xl flex items-center justify-center shadow-xl">
            <i data-lucide="user-circle" size="32"></i>
        </div>
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight">ข้อมูลส่วนตัว</h2>
            <p class="text-slate-500 font-medium">จัดการข้อมูลบัญชีและตั้งค่าความปลอดภัยของคุณ</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- ฝั่งซ้าย: บัตรประจำตัวเจ้าหน้าที่ -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 text-center space-y-4 relative overflow-hidden group">
                <div class="absolute top-0 left-0 w-full h-2 bg-orange-600"></div>
                <div class="w-24 h-24 bg-orange-50 text-orange-600 rounded-full flex items-center justify-center mx-auto text-4xl font-black border-4 border-white shadow-lg group-hover:rotate-12 transition-transform">
                    <?= mb_substr($user_data['full_name'], 0, 1, 'UTF-8') ?>
                </div>
                <div>
                    <h3 class="font-black text-slate-800 text-xl leading-tight"><?= e($user_data['full_name']) ?></h3>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1"><?= e($user_data['position'] ?: 'เจ้าหน้าที่') ?></p>
                </div>
                <div class="pt-6 border-t border-slate-50 flex flex-col gap-2">
                    <span class="bg-slate-900 text-white px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest">
                        ระดับสิทธิ์: <?= strtoupper($user_data['role']) ?>
                    </span>
                    <span class="bg-emerald-50 text-emerald-600 px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest">
                        สถานะ: <?= strtoupper($user_data['status']) ?>
                    </span>
                </div>
            </div>

            <div class="bg-slate-900 p-8 rounded-[2.5rem] text-white shadow-2xl relative overflow-hidden group">
                <div class="absolute -right-6 -bottom-6 opacity-10 group-hover:scale-125 transition-transform duration-700">
                    <i data-lucide="shield-check" size="120"></i>
                </div>
                <h4 class="font-black text-sm mb-2 uppercase tracking-tight text-orange-500">Security Tips</h4>
                <p class="text-xs text-slate-400 leading-relaxed font-medium">
                    ควรเปลี่ยนรหัสผ่านทุก 90 วัน และไม่ควรใช้รหัสผ่านที่คาดเดาง่าย เพื่อรักษาความปลอดภัยของข้อมูลพิกัดโครงสร้างพื้นฐาน
                </p>
            </div>
        </div>

        <!-- ฝั่งขวา: ฟอร์มแก้ไขข้อมูล -->
        <div class="lg:col-span-2">
            <form method="POST" class="bg-white p-8 md:p-10 rounded-[3.5rem] shadow-sm border border-slate-100 space-y-10">
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

                <!-- ส่วนข้อมูลทั่วไป -->
                <div class="space-y-6">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                        <span class="w-8 h-px bg-slate-200"></span> ข้อมูลเจ้าหน้าที่
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อผู้ใช้งาน (Username)</label>
                            <input type="text" value="<?= e($user_data['username']) ?>" disabled class="w-full p-4 bg-slate-100 border-2 border-slate-100 rounded-2xl text-slate-500 font-bold cursor-not-allowed outline-none">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อ-นามสกุล</label>
                            <input type="text" name="full_name" value="<?= e($user_data['full_name']) ?>" required class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all font-bold">
                        </div>
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ตำแหน่งงาน</label>
                            <input type="text" name="position" value="<?= e($user_data['position']) ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all font-bold">
                        </div>
                    </div>
                </div>

                <!-- ส่วนเปลี่ยนรหัสผ่าน -->
                <div class="space-y-6 pt-8 border-t border-slate-50">
                    <h3 class="text-xs font-black text-orange-600 uppercase tracking-[0.2em] flex items-center gap-2">
                        <span class="w-8 h-px bg-orange-100"></span> ความปลอดภัยบัญชี (เปลี่ยนรหัสผ่าน)
                    </h3>
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">รหัสผ่านปัจจุบัน (กรอกเพื่อยืนยันตัวตน)</label>
                            <input type="password" name="current_password" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">รหัสผ่านใหม่</label>
                                <input type="password" name="new_password" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all" placeholder="อย่างน้อย 6 ตัวอักษร">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ยืนยันรหัสผ่านใหม่</label>
                                <input type="password" name="confirm_password" class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-slate-900 hover:bg-orange-600 text-white font-black py-4 rounded-2xl shadow-xl transition-all active:scale-95 text-lg mt-4 uppercase tracking-widest">
                    บันทึกข้อมูลส่วนตัว
                </button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>