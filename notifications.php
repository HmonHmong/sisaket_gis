<?php
// notifications.php - ศูนย์รวมการแจ้งเตือนและประวัติกิจกรรมในระบบ
require_once 'auth_check.php';
require_once 'config/db.php';

$user_id = $_SESSION['user_id'];

// 1. ดึงรายการแจ้งเตือน (แสดงทั้งที่เป็นของส่วนตัว และที่เป็นของส่วนกลาง NULL)
// เรียงลำดับตามความใหม่ล่าสุด
$sql = "SELECT * FROM notifications 
        WHERE user_id = ? OR user_id IS NULL 
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// 2. ปรับสถานะเป็นอ่านแล้วทั้งหมดเมื่อเข้าหน้านี้ (Optional: หรือจะทำปุ่มกดแยกก็ได้)
$update_sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? OR user_id IS NULL";
$upd_stmt = $conn->prepare($update_sql);
$upd_stmt->bind_param("i", $user_id);
$upd_stmt->execute();

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto pb-20 animate-in fade-in duration-500">
    <!-- Header & Action -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight">ศูนย์แจ้งเตือน</h2>
            <p class="text-slate-500 font-medium">ติดตามประวัติกิจกรรมและความเคลื่อนไหวในระบบ GIS</p>
        </div>
        <div class="flex gap-2 w-full md:w-auto">
            <a href="clear_notifications.php" onclick="return confirm('คุณต้องการล้างประวัติการแจ้งเตือนทั้งหมดใช่หรือไม่?')" 
               class="flex-1 md:flex-none bg-white border-2 border-slate-100 text-slate-400 px-6 py-3 rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-red-50 hover:text-red-600 hover:border-red-100 transition-all">
                <i data-lucide="trash-2" size="18"></i> ล้างประวัติทั้งหมด
            </a>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="space-y-4">
        <?php if ($result->num_rows > 0): ?>
            <?php 
            $current_date = "";
            while($row = $result->fetch_assoc()): 
                $noti_date = date('Y-m-d', strtotime($row['created_at']));
                
                // แสดงตัวแบ่งวันที่ (Date Separator)
                if ($noti_date !== $current_date) {
                    $current_date = $noti_date;
                    $display_date = ($noti_date == date('Y-m-d')) ? "วันนี้" : date('d/m/Y', strtotime($noti_date));
                    echo '<div class="pt-6 pb-2"><span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] bg-slate-100 px-4 py-1.5 rounded-full">'.$display_date.'</span></div>';
                }

                // กำหนดสไตล์ตามประเภท (Type-based Styling)
                $type_config = [
                    'project' => ['icon' => 'package', 'color' => 'blue'],
                    'delete' => ['icon' => 'trash-2', 'color' => 'red'],
                    'user' => ['icon' => 'user-plus', 'color' => 'emerald'],
                    'system' => ['icon' => 'settings', 'color' => 'slate'],
                    'edit' => ['icon' => 'edit-3', 'color' => 'orange']
                ];
                $config = $type_config[$row['type']] ?? $type_config['system'];
            ?>
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex items-start gap-6 hover:shadow-md transition-all group relative overflow-hidden">
                <?php if(!$row['is_read']): ?>
                    <div class="absolute left-0 top-0 w-1 h-full bg-orange-600"></div>
                <?php endif; ?>

                <div class="w-12 h-12 bg-<?= $config['color'] ?>-50 text-<?= $config['color'] ?>-500 rounded-2xl flex items-center justify-center shrink-0 shadow-inner group-hover:scale-110 transition-transform">
                    <i data-lucide="<?= $config['icon'] ?>" size="24"></i>
                </div>
                
                <div class="flex-1">
                    <div class="flex justify-between items-start gap-2">
                        <h4 class="font-black text-slate-800 leading-tight mb-1"><?= e($row['title']) ?></h4>
                        <span class="text-[10px] font-bold text-slate-400 whitespace-nowrap"><?= date('H:i', strtotime($row['created_at'])) ?> น.</span>
                    </div>
                    <p class="text-sm text-slate-500 leading-relaxed"><?= e($row['message']) ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-32 bg-white rounded-[3rem] border-2 border-dashed border-slate-100">
                <div class="w-20 h-20 bg-slate-50 text-slate-200 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="bell-off" size="40"></i>
                </div>
                <p class="text-slate-400 font-bold uppercase tracking-widest text-sm">ไม่มีรายการแจ้งเตือนในขณะนี้</p>
                <p class="text-slate-300 text-[10px] mt-1 font-medium">ทุกกิจกรรมที่คุณทำจะปรากฏขึ้นที่นี่</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>