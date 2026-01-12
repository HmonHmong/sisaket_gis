<?php
// login.php
// ที่อยู่ไฟล์: /login.php
require_once 'config/db.php';

// หากมีการล็อกอินค้างไว้แล้ว ให้ส่งไปหน้าแรกทันที
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // ตรวจสอบชื่อผู้ใช้งานและสถานะบัญชี
    $sql = "SELECT * FROM users WHERE username = '$username' AND status = 'active'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // ตรวจสอบรหัสผ่านที่เข้ารหัสไว้ในฐานข้อมูล
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // บันทึกเวลาที่เข้าใช้งานล่าสุด
            $conn->query("UPDATE users SET last_login = NOW() WHERE id = " . $user['id']);
            
            header("Location: index.php");
            exit;
        } else {
            $error_msg = "รหัสผ่านไม่ถูกต้อง กรุณาลองใหม่";
        }
    } else {
        $error_msg = "ไม่พบชื่อผู้ใช้งานนี้ หรือบัญชีของคุณถูกระงับ";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | GIS สำนักช่าง อบจ.ศรีสะเกษ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700;800&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 flex items-center justify-center min-h-screen p-4 overflow-hidden">
    <!-- แสงพื้นหลังตกแต่ง -->
    <div class="fixed top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-orange-600/20 blur-[120px] rounded-full"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-blue-600/10 blur-[120px] rounded-full"></div>
    </div>

    <div class="max-w-md w-full bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-100 relative">
        <div class="bg-orange-600 p-10 text-center text-white relative overflow-hidden">
            <!-- ตกแต่งส่วนหัว -->
            <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
                <svg width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M0 100 L100 0 L100 100 Z" fill="white"></path>
                </svg>
            </div>
            
            <div class="bg-white/20 w-20 h-20 rounded-3xl flex items-center justify-center mx-auto mb-4 backdrop-blur-md shadow-inner relative z-10">
                <i data-lucide="shield-check" size="40"></i>
            </div>
            <h1 class="text-2xl font-black relative z-10 tracking-tight">ระบบ GIS สำนักช่าง</h1>
            <p class="text-orange-100 text-xs mt-1 uppercase font-bold tracking-[0.2em] opacity-80 relative z-10">Si Sa Ket PAO Engineering</p>
        </div>

        <form method="POST" class="p-8 space-y-6">
            <?php if($error_msg): ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-2xl text-sm font-bold border border-red-100 flex items-center gap-3 animate-pulse">
                    <i data-lucide="circle-alert" size="20"></i> <?= $error_msg ?>
                </div>
            <?php endif; ?>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] ml-1">ชื่อผู้ใช้งาน (Username)</label>
                <div class="relative group">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-orange-600 transition-colors">
                        <i data-lucide="user" size="20"></i>
                    </div>
                    <input type="text" name="username" required 
                           class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all shadow-sm"
                           placeholder="กรอกชื่อผู้ใช้งาน">
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] ml-1">รหัสผ่าน (Password)</label>
                <div class="relative group">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-orange-600 transition-colors">
                        <i data-lucide="lock-keyhole" size="20"></i>
                    </div>
                    <input type="password" name="password" required 
                           class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all shadow-sm"
                           placeholder="กรอกรหัสผ่าน">
                </div>
            </div>

            <button type="submit" class="w-full bg-slate-900 hover:bg-orange-600 text-white font-black py-4 rounded-2xl shadow-xl transition-all active:scale-[0.98] text-lg flex items-center justify-center gap-2 group">
                <span>เข้าสู่ระบบใช้งาน</span>
                <i data-lucide="arrow-right" size="20" class="group-hover:translate-x-1 transition-transform"></i>
            </button>

            <div class="pt-6 border-t border-slate-50 text-center">
                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest leading-relaxed">
                    กองช่าง องค์การบริหารส่วนจังหวัดศรีสะเกษ<br>
                    &copy; 2024 Infrastructure GIS Dashboard
                </p>
            </div>
        </form>
    </div>

    <script>
        // เริ่มต้นการใช้งาน Lucide Icons
        lucide.createIcons();
    </script>
</body>
</html>