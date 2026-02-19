<?php
// login.php - ระบบเข้าสู่ระบบฉบับปรับปรุงความปลอดภัยและดีไซน์ระดับองค์กร
require_once 'config/db.php';

// หากมีการล็อกอินค้างไว้แล้ว ให้ส่งไปหน้าแรกทันที
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 1. ใช้ Prepared Statement ตรวจสอบชื่อผู้ใช้งานและสถานะบัญชี (Security First)
    $stmt = $conn->prepare("SELECT id, username, password, full_name, role, status FROM users WHERE username = ? AND status = 'active'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // 2. ตรวจสอบรหัสผ่านที่เข้ารหัสไว้
        if (password_verify($password, $user['password'])) {
            // ตั้งค่า Session สำหรับใช้งานในระบบ
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // 3. บันทึกประวัติเวลาเข้าใช้งานล่าสุด
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            
            header("Location: index.php");
            exit;
        } else {
            $error_msg = "รหัสผ่านไม่ถูกต้อง กรุณาตรวจสอบและลองใหม่อีกครั้ง";
        }
    } else {
        $error_msg = "ไม่พบชื่อผู้ใช้งานนี้ หรือบัญชีของคุณอาจถูกระงับการใช้งาน";
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
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
        .animate-shake {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
    </style>
</head>
<body class="bg-slate-900 flex items-center justify-center min-h-screen p-4 overflow-hidden">
    <!-- แสงพื้นหลังตกแต่ง (Professional Glow) -->
    <div class="fixed top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-orange-600/20 blur-[120px] rounded-full"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-blue-600/10 blur-[120px] rounded-full"></div>
    </div>

    <div class="max-w-md w-full animate-in fade-in zoom-in duration-700">
        <!-- Login Card -->
        <div class="bg-white rounded-[3.5rem] shadow-2xl overflow-hidden border border-white/20 relative">
            <!-- Header Section -->
            <div class="bg-orange-600 p-10 text-center text-white relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
                    <svg width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <path d="M0 100 L100 0 L100 100 Z" fill="white"></path>
                    </svg>
                </div>
                
                <div class="bg-white/20 w-20 h-20 rounded-[2rem] flex items-center justify-center mx-auto mb-4 backdrop-blur-md shadow-inner relative z-10 border border-white/30">
                    <i data-lucide="shield-check" size="40"></i>
                </div>
                <h1 class="text-2xl font-black relative z-10 tracking-tight">ระบบ GIS สำนักช่าง</h1>
                <p class="text-orange-100 text-[10px] mt-1 uppercase font-black tracking-[0.2em] opacity-80 relative z-10">Si Sa Ket PAO Engineering</p>
            </div>

            <!-- Login Form -->
            <form method="POST" class="p-8 space-y-6">
                <?php if($error_msg): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-2xl text-xs font-bold border border-red-100 flex items-center gap-3 animate-shake">
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
                               class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all shadow-sm font-bold text-slate-700"
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
                               class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-slate-50 rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all shadow-sm font-bold text-slate-700"
                               placeholder="กรอกรหัสผ่าน">
                    </div>
                </div>

                <button type="submit" class="w-full bg-slate-900 hover:bg-orange-600 text-white font-black py-4 rounded-2xl shadow-xl transition-all active:scale-[0.98] text-lg flex items-center justify-center gap-2 group mt-4 uppercase tracking-widest">
                    <span>เข้าสู่ระบบใช้งาน</span>
                    <i data-lucide="arrow-right" size="20" class="group-hover:translate-x-1 transition-transform"></i>
                </button>

                <div class="pt-6 border-t border-slate-50 text-center">
                    <p class="text-slate-400 text-[9px] font-black uppercase tracking-widest leading-relaxed">
                        กองช่าง องค์การบริหารส่วนจังหวัดศรีสะเกษ<br>
                        &copy; 2026 Infrastructure GIS System
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // เริ่มต้นไอคอน Lucide
        lucide.createIcons();
    </script>
</body>
</html>