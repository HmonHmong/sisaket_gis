<?php
// activity_logs.php - หน้าตรวจสอบประวัติกิจกรรมการใช้งานระบบ (เฉพาะ Admin)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?error=no_permission");
    exit;
}

// 1. รับค่าตัวกรอง
$f_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : '';
$f_action = isset($_GET['action']) ? $_GET['action'] : '';

// 2. สร้าง Query พร้อม Filter
$where = "WHERE 1=1";
$params = [];
$types = "";

if ($f_user) { $where .= " AND l.user_id = ?"; $params[] = $f_user; $types .= "i"; }
if ($f_action) { $where .= " AND l.action = ?"; $params[] = $f_action; $types .= "s"; }

$sql = "SELECT l.*, u.full_name, u.username, u.role 
        FROM activity_logs l 
        JOIN users u ON l.user_id = u.id 
        $where 
        ORDER BY l.created_at DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// ดึงรายชื่อ User เพื่อทำ Dropdown กรองข้อมูล
$users_list = $conn->query("SELECT id, full_name FROM users ORDER BY full_name ASC");

include 'includes/header.php';
?>

<div class="max-w-[1600px] mx-auto pb-20 animate-in fade-in duration-700 px-4">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-10">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <i data-lucide="shield-check" class="text-orange-600"></i> บันทึกกิจกรรมระบบ
            </h2>
            <p class="text-slate-500 font-bold mt-1 uppercase tracking-widest text-xs ml-1">System Security & Audit Trail</p>
        </div>
        
        <!-- Filter Bar -->
        <form method="GET" class="flex flex-wrap gap-3 w-full lg:w-auto bg-white p-3 rounded-[2rem] shadow-sm border border-slate-100">
            <select name="user_id" class="p-2.5 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-orange-500">
                <option value="">เจ้าหน้าที่ทั้งหมด</option>
                <?php while($u = $users_list->fetch_assoc()): ?>
                    <option value="<?= $u['id'] ?>" <?= $f_user == $u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option>
                <?php endwhile; ?>
            </select>
            <select name="action" class="p-2.5 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-orange-500">
                <option value="">ทุกกิจกรรม</option>
                <option value="INSERT" <?= $f_action == 'INSERT' ? 'selected' : '' ?>>เพิ่มข้อมูล</option>
                <option value="UPDATE" <?= $f_action == 'UPDATE' ? 'selected' : '' ?>>แก้ไขข้อมูล</option>
                <option value="DELETE" <?= $f_action == 'DELETE' ? 'selected' : '' ?>>ลบข้อมูล</option>
                <option value="LOGIN" <?= $f_action == 'LOGIN' ? 'selected' : '' ?>>เข้าสู่ระบบ</option>
            </select>
            <button type="submit" class="bg-slate-900 text-white px-6 py-2.5 rounded-xl font-black text-xs hover:bg-orange-600 transition-all">กรองข้อมูล</button>
            <a href="activity_logs.php" class="p-2.5 text-slate-400 hover:text-slate-600 transition-colors"><i data-lucide="refresh-cw" size="18"></i></a>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-[3.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                        <th class="py-7 px-10">วัน-เวลา</th>
                        <th class="py-7 px-4">เจ้าหน้าที่</th>
                        <th class="py-7 px-4 text-center">กิจกรรม</th>
                        <th class="py-7 px-4">เป้าหมาย</th>
                        <th class="py-7 px-4">รายละเอียดกิจกรรม</th>
                        <th class="py-7 px-10 text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $action_colors = [
                                'INSERT' => 'bg-emerald-50 text-emerald-600',
                                'UPDATE' => 'bg-blue-50 text-blue-600',
                                'DELETE' => 'bg-rose-50 text-rose-600',
                                'LOGIN' => 'bg-orange-50 text-orange-600'
                            ];
                            $a_color = $action_colors[$row['action']] ?? 'bg-slate-50 text-slate-600';
                        ?>
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="py-6 px-10">
                                <p class="text-xs font-black text-slate-800"><?= date('d/m/Y', strtotime($row['created_at'])) ?></p>
                                <p class="text-[10px] font-bold text-slate-400"><?= date('H:i:s', strtotime($row['created_at'])) ?> น.</p>
                            </td>
                            <td class="py-6 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-500">
                                        <?= mb_substr($row['full_name'], 0, 1, 'UTF-8') ?>
                                    </div>
                                    <div>
                                        <p class="text-xs font-black text-slate-700"><?= e($row['full_name']) ?></p>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">@<?= e($row['username']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-6 px-4 text-center">
                                <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest <?= $a_color ?>">
                                    <?= $row['action'] ?>
                                </span>
                            </td>
                            <td class="py-6 px-4">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest border border-slate-100 px-2 py-0.5 rounded">
                                    <?= e($row['target_type'] ?: '-') ?>
                                </span>
                            </td>
                            <td class="py-6 px-4">
                                <p class="text-xs font-bold text-slate-600 leading-relaxed max-w-xs truncate" title="<?= e($row['details']) ?>">
                                    <?= e($row['details']) ?>
                                </p>
                            </td>
                            <td class="py-6 px-10 text-right">
                                <span class="text-[10px] font-mono font-bold text-slate-300"><?= $row['ip_address'] ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-20 text-center text-slate-300 italic text-sm">ไม่พบประวัติกิจกรรมในช่วงเวลานี้</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>