<?php
// users.php - ระบบจัดการเจ้าหน้าที่ (ฉบับสมบูรณ์ ป้องกันจอขาวและ Error Undefined Key)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// เฉพาะ Admin เท่านั้น
if ($_SESSION['role'] !== 'admin') { 
    header("Location: index.php"); 
    exit; 
}

$msg = ''; 
$error = '';

// บันทึกการ เพิ่ม / แก้ไข ข้อมูลเจ้าหน้าที่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $position = isset($_POST['position']) ? trim($_POST['position']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $role = $_POST['role'];
    $status = $_POST['status'];
    
    if ($action === 'add') {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, position, phone, email, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $username, $password, $full_name, $position, $phone, $email, $role, $status);
        if ($stmt->execute()) { 
            $msg = "เพิ่มเจ้าหน้าที่ $full_name สำเร็จ"; 
            log_activity($conn, 'INSERT', 'user', $conn->insert_id, "เพิ่มเจ้าหน้าที่ใหม่: $username");
        } else { 
            $error = "ชื่อผู้ใช้งานนี้มีในระบบแล้ว กรุณาใช้ชื่ออื่น"; 
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['user_id']);
        if (!empty($_POST['password'])) {
            // กรณีเปลี่ยนรหัสผ่านด้วย
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username=?, password=?, full_name=?, position=?, phone=?, email=?, role=?, status=? WHERE id=?");
            $stmt->bind_param("ssssssssi", $username, $password, $full_name, $position, $phone, $email, $role, $status, $id);
        } else {
            // กรณีไม่เปลี่ยนรหัสผ่าน
            $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, position=?, phone=?, email=?, role=?, status=? WHERE id=?");
            $stmt->bind_param("sssssssi", $username, $full_name, $position, $phone, $email, $role, $status, $id);
        }
        if ($stmt->execute()) { 
            $msg = "อัปเดตข้อมูลเจ้าหน้าที่ $full_name สำเร็จ"; 
            log_activity($conn, 'UPDATE', 'user', $id, "แก้ไขข้อมูลเจ้าหน้าที่: $username");
        } else { 
            $error = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล"; 
        }
    }
}

// บันทึกการ ลบ เจ้าหน้าที่
if (isset($_GET['del'])) {
    $del_id = intval($_GET['del']);
    if ($del_id !== $_SESSION['user_id']) { 
        $conn->query("DELETE FROM users WHERE id = $del_id");
        $msg = "ลบข้อมูลเจ้าหน้าที่สำเร็จ";
        log_activity($conn, 'DELETE', 'user', $del_id, "ลบเจ้าหน้าที่ ID: $del_id");
    } else { 
        $error = "ไม่สามารถลบบัญชีของตัวคุณเองที่กำลังใช้งานอยู่ได้"; 
    }
}

// ดึงข้อมูลเจ้าหน้าที่ทั้งหมดมาแสดง
$users = $conn->query("SELECT * FROM users ORDER BY role ASC, created_at DESC");

include 'includes/header.php';
?>

<div class="max-w-[1600px] mx-auto pb-20 animate-in fade-in duration-500 px-4 w-full">
    <!-- Header ส่วนหัวของหน้า -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <div class="w-12 h-12 bg-orange-600 text-white rounded-2xl flex items-center justify-center shadow-lg shrink-0">
                    <i data-lucide="users" size="24"></i>
                </div>
                จัดการเจ้าหน้าที่
            </h2>
            <p class="text-slate-500 font-bold mt-2 uppercase tracking-widest text-[10px]">บริหารจัดการสิทธิ์และข้อมูลติดต่อ</p>
        </div>
        <button onclick="openModal('add')" class="bg-slate-900 text-white px-6 py-3.5 rounded-2xl font-black text-xs hover:bg-orange-600 transition-all shadow-xl flex items-center gap-2 active:scale-95 group">
            <i data-lucide="user-plus" size="16" class="group-hover:scale-110 transition-transform"></i> เพิ่มเจ้าหน้าที่ใหม่
        </button>
    </div>

    <!-- แถบแจ้งเตือนสถานะการทำงาน -->
    <?php if($msg): ?>
        <div class="mb-6 bg-emerald-50 text-emerald-600 p-5 rounded-2xl border border-emerald-100 font-bold flex items-center gap-3 shadow-sm">
            <i data-lucide="check-circle" size="20"></i> <?= $msg ?>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="mb-6 bg-red-50 text-red-600 p-5 rounded-2xl border border-red-100 font-bold flex items-center gap-3 shadow-sm">
            <i data-lucide="alert-circle" size="20"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- ตารางรายชื่อผู้ใช้งาน -->
    <div class="bg-white rounded-[3.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50 bg-slate-50/50">
                        <th class="py-6 px-8 rounded-tl-[3.5rem]">ชื่อ-นามสกุล / ตำแหน่ง</th>
                        <th class="py-6 px-4">ข้อมูลติดต่อ (Contact)</th>
                        <th class="py-6 px-4 text-center">บทบาท (Role)</th>
                        <th class="py-6 px-4 text-center">สถานะ</th>
                        <th class="py-6 px-8 text-right rounded-tr-[3.5rem]">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if($users && $users->num_rows > 0): while($row = $users->fetch_assoc()): 
                        // ⭐️ ใช้ ?? เพื่อดักจับ Error กรณีไม่มีคอลัมน์นี้ในแถวเก่าๆ ของฐานข้อมูล ⭐️
                        $pos = isset($row['position']) && $row['position'] !== '' ? $row['position'] : 'ไม่ระบุตำแหน่ง';
                        $phone = isset($row['phone']) && $row['phone'] !== '' ? $row['phone'] : '-';
                        $email = isset($row['email']) && $row['email'] !== '' ? $row['email'] : '-';
                    ?>
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="py-5 px-8">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-[1.2rem] bg-orange-100 text-orange-600 flex items-center justify-center font-black text-lg shadow-inner shrink-0">
                                    <?= mb_substr($row['full_name'], 0, 1, 'UTF-8') ?>
                                </div>
                                <div>
                                    <p class="text-sm font-black text-slate-800"><?= htmlspecialchars($row['full_name']) ?></p>
                                    <p class="text-[10px] font-bold text-slate-400 mt-0.5"><?= htmlspecialchars($pos) ?></p>
                                    <p class="text-[9px] font-bold text-blue-500 uppercase mt-1 tracking-widest">@<?= htmlspecialchars($row['username']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="py-5 px-4">
                            <div class="space-y-1.5">
                                <p class="text-[11px] font-bold text-slate-600 flex items-center gap-2">
                                    <i data-lucide="phone" size="12" class="text-slate-400"></i> 
                                    <?= htmlspecialchars($phone) ?>
                                </p>
                                <p class="text-[11px] font-bold text-slate-600 flex items-center gap-2">
                                    <i data-lucide="mail" size="12" class="text-slate-400"></i> 
                                    <?= htmlspecialchars($email) ?>
                                </p>
                            </div>
                        </td>
                        <td class="py-5 px-4 text-center">
                            <?php 
                                $r_color = $row['role'] == 'admin' ? 'bg-purple-100 text-purple-600 border-purple-200' : ($row['role'] == 'staff' ? 'bg-blue-100 text-blue-600 border-blue-200' : 'bg-slate-100 text-slate-600 border-slate-200');
                                $r_label = $row['role'] == 'admin' ? 'แอดมิน' : ($row['role'] == 'staff' ? 'ช่าง/ผู้ดูแล' : 'ผู้เยี่ยมชม');
                            ?>
                            <span class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border <?= $r_color ?>"><?= $r_label ?></span>
                        </td>
                        <td class="py-5 px-4 text-center">
                            <?php if($row['status'] == 'active'): ?>
                                <span class="flex items-center justify-center gap-1.5 text-xs font-bold text-emerald-500"><span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> ใช้งานปกติ</span>
                            <?php else: ?>
                                <span class="flex items-center justify-center gap-1.5 text-xs font-bold text-rose-500"><span class="w-2 h-2 rounded-full bg-rose-500"></span> ถูกระงับ</span>
                            <?php endif; ?>
                            <p class="text-[9px] font-bold text-slate-400 mt-2" title="<?= $row['last_login'] ?? '' ?>">
                                เข้าระบบล่าสุด: <br><?= isset($row['last_login']) && $row['last_login'] ? date('d/m/Y', strtotime($row['last_login'])) : '-' ?>
                            </p>
                        </td>
                        <td class="py-5 px-8 text-right">
                            <div class="flex justify-end gap-2 opacity-100 lg:opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick='openModal("edit", <?= json_encode($row) ?>)' class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-colors" title="แก้ไข">
                                    <i data-lucide="edit-2" size="14"></i>
                                </button>
                                <?php if($row['id'] !== $_SESSION['user_id']): ?>
                                <a href="users.php?del=<?= $row['id'] ?>" onclick="return confirm('ยืนยันการลบเจ้าหน้าที่คนนี้? ข้อมูลจะไม่สามารถกู้คืนได้')" class="w-9 h-9 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-colors" title="ลบ">
                                    <i data-lucide="trash-2" size="14"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400 font-bold text-sm">ไม่พบข้อมูลเจ้าหน้าที่ในระบบ</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal หน้าต่างลอย สำหรับเพิ่ม/แก้ไข ผู้ใช้งาน -->
<div id="userModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden items-center justify-center p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-[3rem] shadow-2xl w-full max-w-2xl overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col max-h-[90vh]" id="userModalContent">
        <!-- Header Modal -->
        <div class="p-6 md:p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/80 shrink-0">
            <h3 class="text-xl font-black text-slate-800 flex items-center gap-3" id="modalTitle">
                <i data-lucide="user-plus" class="text-orange-500"></i> เพิ่มเจ้าหน้าที่ใหม่
            </h3>
            <button type="button" onclick="closeModal()" class="w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-colors">
                <i data-lucide="x" size="20"></i>
            </button>
        </div>
        
        <!-- Form Modal -->
        <div class="p-6 md:p-8 overflow-y-auto no-scrollbar flex-1">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="user_id" id="userId" value="">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อ-นามสกุล (ผู้แสดงผลในระบบ) *</label>
                        <input type="text" name="full_name" id="fullName" required class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ตำแหน่งงาน</label>
                        <input type="text" name="position" id="position" placeholder="เช่น วิศวกรโยธา, ช่างสำรวจ..." class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">เบอร์โทรศัพท์ติดต่อ</label>
                        <input type="text" name="phone" id="phone" placeholder="08X-XXX-XXXX" class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">อีเมล (ถ้ามี)</label>
                        <input type="email" name="email" id="email" placeholder="example@email.com" class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อเข้าสู่ระบบ (USERNAME) *</label>
                            <input type="text" name="username" id="username" required class="w-full p-4 bg-slate-50 border-none rounded-2xl font-black text-blue-600 outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">รหัสผ่าน <span id="pwdHint" class="text-rose-500 font-normal"></span></label>
                            <input type="password" name="password" id="password" class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 pb-2">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">สิทธิ์การใช้งาน (ROLE)</label>
                        <select name="role" id="role" class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 outline-none focus:ring-2 focus:ring-orange-500 transition-all cursor-pointer">
                            <option value="viewer">ผู้เยี่ยมชม (Viewer)</option>
                            <option value="staff">ช่าง/ผู้ดูแล (Staff)</option>
                            <option value="admin">แอดมิน (Admin)</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">สถานะ</label>
                        <select name="status" id="status" class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 outline-none focus:ring-2 focus:ring-orange-500 transition-all cursor-pointer">
                            <option value="active">เปิดใช้งาน (Active)</option>
                            <option value="inactive">ระงับการใช้งาน (Inactive)</option>
                        </select>
                    </div>
                </div>
                
                <div class="sticky bottom-0 bg-white pt-4">
                    <button type="submit" class="w-full bg-slate-900 text-white font-black py-5 rounded-[2rem] shadow-xl hover:bg-orange-600 transition-all flex items-center justify-center gap-2 active:scale-95 uppercase tracking-widest">
                        <i data-lucide="save" size="20"></i> บันทึกข้อมูลเจ้าหน้าที่
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // ฟังก์ชันจัดการหน้าต่าง Modal (เปิด)
    function openModal(action, data = null) {
        const modal = document.getElementById('userModal');
        const modalContent = document.getElementById('userModalContent');
        
        document.getElementById('formAction').value = action;
        
        if (action === 'edit') {
            document.getElementById('modalTitle').innerHTML = '<i data-lucide="edit-3" class="text-blue-500"></i> แก้ไขข้อมูลเจ้าหน้าที่';
            document.getElementById('userId').value = data.id;
            document.getElementById('fullName').value = data.full_name;
            document.getElementById('position').value = data.position || '';
            document.getElementById('phone').value = data.phone || '';
            document.getElementById('email').value = data.email || '';
            document.getElementById('username').value = data.username;
            document.getElementById('role').value = data.role;
            document.getElementById('status').value = data.status;
            
            // กรณีแก้ไข ไม่บังคับเปลี่ยนรหัสผ่าน
            document.getElementById('password').required = false;
            document.getElementById('pwdHint').innerText = '(เว้นว่างหากไม่ต้องการเปลี่ยน)';
        } else {
            document.getElementById('modalTitle').innerHTML = '<i data-lucide="user-plus" class="text-orange-500"></i> เพิ่มเจ้าหน้าที่ใหม่';
            document.getElementById('userId').value = '';
            document.getElementById('fullName').value = '';
            document.getElementById('position').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('email').value = '';
            document.getElementById('username').value = '';
            document.getElementById('role').value = 'staff';
            document.getElementById('status').value = 'active';
            
            // กรณีเพิ่มใหม่ บังคับตั้งรหัสผ่าน
            document.getElementById('password').required = true;
            document.getElementById('pwdHint').innerText = '(บังคับตั้งรหัสผ่าน)';
        }
        
        // แสดง Modal แบบมี Animation
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
        }, 10);
        
        if(typeof lucide !== 'undefined') lucide.createIcons();
    }

    // ฟังก์ชันจัดการหน้าต่าง Modal (ปิด)
    function closeModal() {
        const modal = document.getElementById('userModal');
        const modalContent = document.getElementById('userModalContent');
        
        modal.classList.add('opacity-0');
        modalContent.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }
    
    // โหลด Icon เมื่อหน้าเว็บพร้อม
    window.onload = () => { 
        if(typeof lucide !== 'undefined') lucide.createIcons(); 
    };
</script>

<?php include 'includes/footer.php'; ?>