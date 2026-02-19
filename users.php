<?php
// users.php - ระบบบริหารจัดการเจ้าหน้าที่ฉบับปรับปรุง (แสดงเบอร์โทรศัพท์)
require_once 'auth_check.php';
require_once 'config/db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?error=no_permission");
    exit;
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$where = "";
$params = [];
$types = "";

if ($search) {
    $where = "WHERE username LIKE ? OR full_name LIKE ? OR position LIKE ? OR phone LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param];
    $types = "ssss";
}

$sql = "SELECT id, username, full_name, position, phone, role, status, last_login FROM users $where ORDER BY role ASC, full_name ASC";
$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

include 'includes/header.php';
?>

<div class="space-y-6 animate-in fade-in duration-500 pb-20">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight">จัดการเจ้าหน้าที่</h2>
            <p class="text-slate-500 font-medium">บริหารจัดการสิทธิ์และข้อมูลติดต่อเจ้าหน้าที่สำนักช่าง</p>
        </div>
        <div class="flex gap-3 w-full md:w-auto">
            <form method="GET" class="relative flex-1 md:w-64">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" size="18"></i>
                <input type="text" name="search" value="<?= e($search) ?>" 
                       class="w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-2xl outline-none focus:border-orange-500 transition-all shadow-sm" 
                       placeholder="ค้นหาชื่อ/เบอร์โทร...">
            </form>
            <a href="add_user.php" class="bg-slate-900 text-white px-6 py-3 rounded-2xl font-bold flex items-center gap-2 hover:bg-orange-600 transition-all shadow-lg whitespace-nowrap">
                <i data-lucide="user-plus" size="18"></i> เพิ่มเจ้าหน้าที่
            </a>
        </div>
    </div>

    <div class="bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[11px] font-black text-slate-400 uppercase tracking-[0.15em] border-b border-slate-50 text-center">
                        <th class="py-6 px-8 text-left">เจ้าหน้าที่ / ตำแหน่ง</th>
                        <th class="py-6 px-4">เบอร์โทรศัพท์</th>
                        <th class="py-6 px-4">ระดับสิทธิ์</th>
                        <th class="py-6 px-4">สถานะ</th>
                        <th class="py-6 px-8">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php while($user = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="py-6 px-8">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-2xl bg-orange-50 flex items-center justify-center text-orange-600 font-black shadow-inner">
                                    <?= mb_substr($user['full_name'], 0, 1, 'UTF-8') ?>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800"><?= e($user['full_name']) ?></p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tight"><?= e($user['position'] ?: 'ไม่ได้ระบุตำแหน่ง') ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="py-6 px-4 text-center">
                            <?php if($user['phone']): ?>
                                <a href="tel:<?= $user['phone'] ?>" class="text-xs font-bold text-slate-600 hover:text-orange-600 flex items-center justify-center gap-1">
                                    <i data-lucide="phone" size="12"></i> <?= e($user['phone']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-[10px] text-slate-300 font-bold uppercase">ไม่ได้ระบุ</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-6 px-4 text-center">
                            <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-lg <?= $user['role'] == 'admin' ? 'text-orange-600 bg-orange-50' : 'text-blue-600 bg-blue-50' ?>">
                                <?= $user['role'] ?>
                            </span>
                        </td>
                        <td class="py-6 px-4 text-center">
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase <?= $user['status'] == 'active' ? 'text-emerald-600' : 'text-red-400' ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $user['status'] == 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-red-400' ?>"></span>
                                <?= $user['status'] == 'active' ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="py-6 px-8 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="p-2 text-slate-300 hover:text-orange-600 transition-colors">
                                    <i data-lucide="user-cog" size="18"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>