<?php
// users.php
// ที่อยู่ไฟล์: /users.php
require_once 'auth_check.php';
require_once 'config/db.php';

// ตรวจสอบสิทธิ์: เฉพาะ admin เท่านั้นที่เข้าหน้านี้ได้
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?error=no_permission");
    exit;
}

// ระบบค้นหาผู้ใช้งาน
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where = $search ? "WHERE username LIKE '%$search%' OR full_name LIKE '%$search%'" : "";

$sql = "SELECT * FROM users $where ORDER BY role ASC, full_name ASC";
$result = $conn->query($sql);

include 'includes/header.php';
?>

<div class="space-y-6 animate-in fade-in duration-500 pb-20">
    <!-- ส่วนหัวและปุ่มเพิ่มผู้ใช้ -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-800">จัดการผู้ใช้งานระบบ</h2>
            <p class="text-slate-500 font-medium">บริหารจัดการสิทธิ์และบัญชีเจ้าหน้าที่สำนักช่าง</p>
        </div>
        <div class="flex gap-3 w-full md:w-auto">
            <form method="GET" class="relative flex-1 md:w-64">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" size="18"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       class="w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-2xl outline-none focus:border-orange-500 transition-all shadow-sm" 
                       placeholder="ค้นชื่อ/Username...">
            </form>
            <a href="add_user.php" class="bg-slate-900 text-white px-6 py-3 rounded-2xl font-bold flex items-center gap-2 hover:bg-orange-600 transition-all shadow-lg whitespace-nowrap">
                <i data-lucide="user-plus" size="18"></i> เพิ่มเจ้าหน้าที่
            </a>
        </div>
    </div>

    <!-- ตารางรายชื่อผู้ใช้งาน -->
    <div class="bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[11px] font-black text-slate-400 uppercase tracking-[0.15em] border-b border-slate-50">
                        <th class="py-6 px-8">เจ้าหน้าที่ / ตำแหน่ง</th>
                        <th class="py-6 px-4">ชื่อผู้ใช้งาน</th>
                        <th class="py-6 px-4">ระดับสิทธิ์</th>
                        <th class="py-6 px-4">สถานะ</th>
                        <th class="py-6 px-4">เข้าใช้งานล่าสุด</th>
                        <th class="py-6 px-8 text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php while($user = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="py-6 px-8">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 font-bold uppercase">
                                    <?= substr($user['username'], 0, 1) ?>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800"><?= htmlspecialchars($user['full_name']) ?></p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tight"><?= htmlspecialchars($user['position'] ?: 'ไม่ระบุตำแหน่ง') ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="py-6 px-4">
                            <code class="text-xs bg-slate-100 px-2 py-1 rounded text-slate-600"><?= htmlspecialchars($user['username']) ?></code>
                        </td>
                        <td class="py-6 px-4">
                            <span class="text-xs font-black uppercase tracking-widest <?= $user['role'] == 'admin' ? 'text-orange-600' : 'text-blue-600' ?>">
                                <?= $user['role'] ?>
                            </span>
                        </td>
                        <td class="py-6 px-4">
                            <?php if($user['status'] == 'active'): ?>
                                <span class="flex items-center gap-1.5 text-emerald-600 text-[10px] font-black uppercase">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span> ใช้งานปกติ
                                </span>
                            <?php else: ?>
                                <span class="flex items-center gap-1.5 text-red-400 text-[10px] font-black uppercase">
                                    <span class="w-1.5 h-1.5 bg-red-400 rounded-full"></span> ระงับใช้งาน
                                </span>
                            <?php Bird: ?>
                            <?php endif; ?>
                        </td>
                        <td class="py-6 px-4 text-xs font-bold text-slate-500">
                            <?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'ไม่เคยเข้าใช้งาน' ?>
                        </td>
                        <td class="py-6 px-8 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="p-2 text-slate-400 hover:text-orange-600 transition-colors" title="แก้ไข">
                                    <i data-lucide="edit-3" size="18"></i>
                                </a>
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <button onclick="toggleStatus(<?= $user['id'] ?>, '<?= $user['status'] ?>')" class="p-2 <?= $user['status'] == 'active' ? 'text-slate-400 hover:text-red-500' : 'text-emerald-500 hover:text-emerald-600' ?> transition-colors" title="<?= $user['status'] == 'active' ? 'ระงับการใช้งาน' : 'คืนสถานะการใช้งาน' ?>">
                                    <i data-lucide="<?= $user['status'] == 'active' ? 'user-x' : 'user-check' ?>" size="18"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleStatus(id, currentStatus) {
    const action = currentStatus === 'active' ? 'ระงับการใช้งาน' : 'เปิดใช้งาน';
    if(confirm(`คุณต้องการ ${action} เจ้าหน้าที่ท่านนี้ใช่หรือไม่?`)) {
        window.location.href = `update_user_status.php?id=${id}&status=${currentStatus === 'active' ? 'inactive' : 'active'}`;
    }
}
</script>

<?php include 'includes/footer.php'; ?>