<?php
// projects.php
// ที่อยู่ไฟล์: /projects.php
require_once 'auth_check.php';
require_once 'config/db.php';

// ระบบค้นหาข้อมูล
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where = "";
if ($search) {
    $where = "WHERE p.project_name LIKE '%$search%' OR p.fiscal_year LIKE '%$search%'";
}

// ดึงข้อมูลโครงการพร้อมชื่อผู้บันทึก
$sql = "SELECT p.*, u.full_name as creator_name 
        FROM projects p 
        LEFT JOIN users u ON p.created_by = u.id 
        $where 
        ORDER BY p.fiscal_year DESC, p.id DESC";
$result = $conn->query($sql);

include 'includes/header.php';
?>

<div class="space-y-6 animate-in fade-in pb-20">
    <!-- ส่วนหัวหน้าจอ: ชื่อหน้าและกลุ่มปุ่มดำเนินการ -->
    <div class="flex flex-col xl:flex-row gap-4 items-start xl:items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-slate-800">ทะเบียนโครงการทั้งหมด</h2>
            <p class="text-slate-500 text-sm font-medium">จัดการและตรวจสอบฐานข้อมูลโครงสร้างพื้นฐาน</p>
        </div>
        
        <div class="flex flex-wrap gap-3 w-full xl:w-auto">
            <!-- ปุ่มเพิ่มโครงการใหม่ (เพิ่มเข้ามาเพื่อให้เข้าถึงง่าย) -->
            <a href="add_project.php" class="bg-orange-600 text-white px-6 py-3 rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-orange-700 transition-all shadow-lg shadow-orange-100">
                <i data-lucide="plus-circle" size="18"></i>
                <span class="whitespace-nowrap">เพิ่มโครงการใหม่</span>
            </a>

            <!-- ปุ่มส่งออก Excel (CSV) -->
            <a href="export_projects.php" class="bg-emerald-600 text-white px-6 py-3 rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-100">
                <i data-lucide="file-spreadsheet" size="18"></i>
                <span class="whitespace-nowrap">ส่งออก Excel</span>
            </a>
            
            <!-- ฟอร์มค้นหา -->
            <form method="GET" class="relative flex-1 md:w-64">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" size="18"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       class="w-full pl-12 pr-4 py-3 bg-white border-2 border-slate-100 rounded-2xl outline-none focus:border-orange-500 transition-all shadow-sm" 
                       placeholder="ค้นหาโครงการ...">
            </form>
        </div>
    </div>

    <!-- รายการโครงการ (Cards) -->
    <div class="grid grid-cols-1 gap-6">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $p_id = $row['id'];
                $pts_res = $conn->query("SELECT village FROM project_points WHERE project_id = $p_id ORDER BY order_index ASC");
                $villages = [];
                while($pt = $pts_res->fetch_assoc()) $villages[] = htmlspecialchars($pt['village']);
                $route_name = !empty($villages) ? implode(" - ", $villages) : "ไม่ระบุสายทาง";
            ?>
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden hover:border-orange-400 transition-all group">
                <div class="flex flex-col md:flex-row">
                    <div class="bg-slate-900 text-white p-8 md:w-64 flex flex-col justify-between shrink-0">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">ปีงบประมาณ</p>
                            <p class="text-3xl font-black"><?= $row['fiscal_year'] ?></p>
                        </div>
                        <div class="mt-6">
                            <p class="text-xs font-bold text-slate-400 uppercase">พื้นที่รวม</p>
                            <p class="text-xl font-bold"><?= number_format($row['area'], 2) ?> ตร.ม.</p>
                        </div>
                    </div>
                    
                    <div class="p-8 flex-1 bg-white">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h3 class="text-xl font-black text-slate-800 group-hover:text-orange-600 transition-colors"><?= htmlspecialchars($row['project_name']) ?></h3>
                                <div class="flex items-center gap-2 text-slate-400 font-bold mt-1 text-xs">
                                    <i data-lucide="map-pinned" size="14" class="text-blue-500"></i>
                                    สายทาง: <span class="text-slate-600"><?= $route_name ?></span>
                                </div>
                            </div>
                            <span class="bg-orange-50 text-orange-700 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-50">
                                <p class="text-[10px] text-slate-400 font-black uppercase mb-1">ระยะทาง</p>
                                <p class="font-black text-slate-700"><?= number_format($row['distance']) ?> ม.</p>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-50">
                                <p class="text-[10px] text-slate-400 font-black uppercase mb-1">งบประมาณ</p>
                                <p class="font-black text-orange-600"><?= number_format($row['budget_amount'], 2) ?> ฿</p>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-50">
                                <p class="text-[10px] text-slate-400 font-black uppercase mb-1">ความกว้าง</p>
                                <p class="font-black text-slate-700"><?= $row['width'] ?> ม.</p>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-50">
                                <p class="text-[10px] text-slate-400 font-black uppercase mb-1">ผู้บันทึก</p>
                                <p class="font-bold text-slate-700 truncate"><?= htmlspecialchars($row['creator_name'] ?: 'System') ?></p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 pt-4 border-t border-slate-50">
                            <a href="view_project.php?id=<?= $row['id'] ?>" class="bg-orange-600 text-white px-6 py-2.5 rounded-xl text-xs font-black flex items-center gap-2 hover:bg-orange-700 shadow-lg transition-all">
                                <i data-lucide="eye" size="14"></i> ดูข้อมูล
                            </a>
                            <a href="edit_project.php?id=<?= $row['id'] ?>" class="bg-white border-2 border-slate-100 text-slate-700 px-6 py-2.5 rounded-xl text-xs font-black flex items-center gap-2 hover:bg-slate-50 transition-all">
                                <i data-lucide="edit-3" size="14"></i> แก้ไข
                            </a>
                            <button onclick="confirmDelete(<?= $row['id'] ?>)" class="ml-auto bg-red-50 text-red-600 p-2.5 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm">
                                <i data-lucide="trash-2" size="18"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-24 bg-white rounded-[3rem] border-2 border-dashed border-slate-100">
                <i data-lucide="database-zap" class="mx-auto text-slate-200 mb-4" size="64"></i>
                <p class="text-slate-400 font-bold uppercase tracking-widest">ไม่พบข้อมูลโครงการในระบบ</p>
                <a href="add_project.php" class="inline-flex items-center gap-2 mt-6 bg-orange-600 text-white px-8 py-4 rounded-[2rem] font-bold hover:shadow-xl transition-all">
                    <i data-lucide="plus-circle"></i> คลิกเพื่อเพิ่มโครงการใหม่
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if(confirm('คุณต้องการลบโครงการนี้และข้อมูลพิกัดทั้งหมดใช่หรือไม่?')) {
        window.location.href = 'delete_project.php?id=' + id;
    }
}
</script>

<?php include 'includes/footer.php'; ?>