<?php
// trash.php - ระบบจัดการถังขยะ (กู้คืน / ลบถาวร)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }

$sql = "SELECT p.*, u.full_name as creator_name 
        FROM projects p 
        LEFT JOIN users u ON p.created_by = u.id 
        WHERE p.deleted_at IS NOT NULL 
        ORDER BY p.deleted_at DESC";
$res = $conn->query($sql);

include 'includes/header.php';
?>

<div class="max-w-[1600px] mx-auto pb-20 animate-in fade-in duration-500 px-4">
    <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center shadow-sm">
            <i data-lucide="trash-2" size="28"></i>
        </div>
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight">ถังขยะโครงการ</h2>
            <p class="text-slate-500 font-bold mt-1 uppercase text-xs">Recycle Bin & Data Recovery</p>
        </div>
    </div>

    <div class="space-y-4">
        <?php if($res->num_rows > 0): while($row = $res->fetch_assoc()): ?>
            <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-col md:flex-row justify-between items-center gap-6">
                <div>
                    <h3 class="text-lg font-black text-slate-800 line-through opacity-70"><?= e($row['project_name']) ?></h3>
                    <p class="text-xs text-rose-500 font-bold mt-1">ถูกลบเมื่อ: <?= date('d/m/Y H:i', strtotime($row['deleted_at'])) ?></p>
                </div>
                <div class="flex gap-3">
                    <a href="restore_project.php?id=<?= $row['id'] ?>" onclick="return confirm('ยืนยันการกู้คืนโครงการนี้?')" class="px-6 py-3 bg-emerald-50 text-emerald-600 rounded-xl font-black text-xs hover:bg-emerald-600 hover:text-white transition-all flex items-center gap-2">
                        <i data-lucide="refresh-cw" size="16"></i> กู้คืนข้อมูล
                    </a>
                    <a href="delete_project.php?id=<?= $row['id'] ?>&force=1" onclick="return confirm('คำเตือน: การลบถาวรจะไม่สามารถกู้คืนได้อีก ยืนยัน?')" class="px-6 py-3 bg-slate-900 text-white rounded-xl font-black text-xs hover:bg-rose-600 transition-all flex items-center gap-2 shadow-lg">
                        <i data-lucide="x-circle" size="16"></i> ลบถาวร
                    </a>
                </div>
            </div>
        <?php endwhile; else: ?>
            <div class="text-center py-20 bg-white rounded-[3rem] border-2 border-dashed border-slate-100">
                <i data-lucide="inbox" class="mx-auto text-slate-200 mb-4" size="64"></i>
                <p class="text-slate-400 font-bold uppercase tracking-widest text-sm">ถังขยะว่างเปล่า</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>