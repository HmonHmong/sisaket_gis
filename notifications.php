<?php
// notifications.php
require_once 'auth_check.php';
require_once 'config/db.php';

$user_id = $_SESSION['user_id'];

// ทำการอัปเดตเป็น "อ่านแล้ว" เมื่อเปิดหน้านี้
$conn->query("UPDATE notifications SET is_read = 1 WHERE (user_id = $user_id OR user_id IS NULL) AND is_read = 0");

// ดึงข้อมูลแจ้งเตือนทั้งหมด
$sql = "SELECT * FROM notifications 
        WHERE (user_id = ? OR user_id IS NULL) 
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto pb-20 animate-in fade-in duration-700">
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-orange-600 text-white rounded-[1.5rem] flex items-center justify-center shadow-xl">
                <i data-lucide="bell-ring" size="28"></i>
            </div>
            <div>
                <h2 class="text-3xl font-black text-slate-800 tracking-tight">ศูนย์แจ้งเตือน</h2>
                <p class="text-slate-500 font-medium">ความเคลื่อนไหวทั้งหมดในระบบ GIS สำนักช่าง</p>
            </div>
        </div>
        <a href="clear_notifications.php" onclick="return confirm('ลบประวัติการแจ้งเตือนทั้งหมด?')" class="text-xs font-bold text-slate-400 hover:text-red-500 flex items-center gap-2 transition-all">
            <i data-lucide="trash-2" size="14"></i> ล้างข้อมูล
        </a>
    </div>

    <div class="space-y-4">
        <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $icon = 'info'; $color = 'blue';
                if($row['type'] == 'project') { $icon = 'construction'; $color = 'orange'; }
                if($row['type'] == 'alert') { $icon = 'alert-triangle'; $color = 'red'; }
            ?>
            <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex items-start gap-6 hover:shadow-md transition-all">
                <div class="w-12 h-12 bg-<?= $color ?>-50 text-<?= $color ?>-600 rounded-2xl flex items-center justify-center shrink-0">
                    <i data-lucide="<?= $icon ?>" size="24"></i>
                </div>
                <div class="flex-1">
                    <div class="flex justify-between items-start mb-1">
                        <h3 class="font-black text-slate-800"><?= htmlspecialchars($row['title']) ?></h3>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?= date('d M Y | H:i', strtotime($row['created_at'])) ?></span>
                    </div>
                    <p class="text-sm text-slate-500 leading-relaxed"><?= htmlspecialchars($row['message']) ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-24 bg-white rounded-[3.5rem] border-2 border-dashed border-slate-100">
                <i data-lucide="bell-off" class="mx-auto text-slate-200 mb-4" size="64"></i>
                <p class="text-slate-400 font-bold uppercase tracking-widest">ยังไม่มีการแจ้งเตือนในขณะนี้</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>