<?php
// settings.php - ระบบจัดการตั้งค่าหลังบ้าน (ประเภทงาน, โครงสร้าง ฯลฯ) V2.5
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// เฉพาะ Admin เท่านั้นที่เข้าหน้านี้ได้
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?error=no_permission");
    exit;
}

$msg = '';
$error = '';

// จัดการลบประเภทงาน
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $del_id = intval($_GET['id']);
    if ($conn->query("DELETE FROM infrastructure_types WHERE id = $del_id")) {
        $msg = "ลบประเภทงานเรียบร้อยแล้ว";
        log_activity($conn, 'DELETE', 'system', $del_id, "ลบประเภทงาน (ID: $del_id)");
    }
}

// จัดการเพิ่มประเภทงานใหม่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_type'])) {
    $type_name = trim($_POST['type_name']);
    $category = trim($_POST['category']);
    
    if (!empty($type_name) && !empty($category)) {
        $stmt = $conn->prepare("INSERT INTO infrastructure_types (type_name, category) VALUES (?, ?)");
        $stmt->bind_param("ss", $type_name, $category);
        if ($stmt->execute()) {
            $msg = "เพิ่มประเภทงานใหม่ '$type_name' เรียบร้อยแล้ว";
            log_activity($conn, 'INSERT', 'system', $conn->insert_id, "เพิ่มประเภทงานใหม่: $type_name");
        } else {
            $error = "ไม่สามารถเพิ่มได้ (ชื่อประเภทงานนี้อาจมีอยู่แล้วในระบบ)";
        }
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}

// ⭐️ 1. เพิ่มระบบจัดการการแก้ไขประเภทงาน (Edit) ⭐️
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_type'])) {
    $edit_id = intval($_POST['edit_id']);
    $type_name = trim($_POST['type_name']);
    $category = trim($_POST['category']);
    
    if (!empty($type_name) && !empty($category) && $edit_id > 0) {
        $stmt = $conn->prepare("UPDATE infrastructure_types SET type_name = ?, category = ? WHERE id = ?");
        $stmt->bind_param("ssi", $type_name, $category, $edit_id);
        if ($stmt->execute()) {
            $msg = "แก้ไขประเภทงานเรียบร้อยแล้ว";
            log_activity($conn, 'UPDATE', 'system', $edit_id, "แก้ไขประเภทงาน (ID: $edit_id) เป็น: $type_name");
        } else {
            $error = "ไม่สามารถแก้ไขได้ (ชื่อประเภทงานนี้อาจมีอยู่แล้วในระบบ)";
        }
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}

// ดึงข้อมูลประเภทงานทั้งหมดมาแสดง จัดกลุ่มตามหมวดหมู่
$res = $conn->query("SELECT * FROM infrastructure_types ORDER BY category ASC, type_name ASC");
$types_grouped = [];
if ($res) {
    while($row = $res->fetch_assoc()){
        $types_grouped[$row['category']][] = $row;
    }
}

include 'includes/header.php';
?>

<div class="max-w-[1600px] mx-auto pb-20 animate-in fade-in duration-500 w-full px-4">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 bg-slate-900 text-white rounded-2xl flex items-center justify-center shadow-lg shrink-0">
            <i data-lucide="settings" size="28"></i>
        </div>
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">ตั้งค่าระบบ</h2>
            <p class="text-slate-500 font-bold mt-1 uppercase tracking-widest text-[10px]">System Configurations</p>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="mb-8 bg-emerald-50 text-emerald-600 p-5 rounded-2xl border border-emerald-100 font-bold flex items-center gap-3 animate-in slide-in-from-top-2 shadow-sm">
            <i data-lucide="check-circle"></i> <?= $msg ?>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="mb-8 bg-red-50 text-red-600 p-5 rounded-2xl border border-red-100 font-bold flex items-center gap-3 animate-in slide-in-from-top-2 shadow-sm">
            <i data-lucide="alert-circle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- ฝั่งซ้าย: ฟอร์มเพิ่มประเภทงาน -->
        <div class="lg:col-span-1">
            <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 sticky top-24">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-2 border-l-4 border-orange-500 pl-3">
                    เพิ่มประเภทงาน/โครงสร้าง
                </h3>
                <form method="POST" class="space-y-5">
                    <input type="hidden" name="add_type" value="1">
                    
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">หมวดหมู่งาน</label>
                        <select name="category" required class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 outline-none focus:ring-2 focus:ring-orange-500 transition-all cursor-pointer shadow-sm">
                            <option value="งานถนน">งานถนน</option>
                            <option value="งานระบายน้ำ">งานระบายน้ำ (ท่อเหลี่ยม/ท่อกลม)</option>
                            <option value="งานไฟฟ้า">งานไฟฟ้าและแสงสว่าง</option>
                            <option value="งานอาคาร">งานอาคารและสิ่งก่อสร้าง</option>
                            <option value="งานแหล่งน้ำ">งานแหล่งน้ำ (ขุดลอกคลอง/สระ)</option>
                            <option value="อื่นๆ">อื่นๆ</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อประเภทงาน (เช่น ท่อลอดเหลี่ยม)</label>
                        <input type="text" name="type_name" required placeholder="ระบุชื่อประเภทงาน..." class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 outline-none focus:ring-2 focus:ring-orange-500 transition-all shadow-sm">
                    </div>

                    <button type="submit" class="w-full bg-slate-900 hover:bg-orange-600 text-white font-black py-4 rounded-2xl shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2 mt-4">
                        <i data-lucide="plus" size="18"></i> บันทึกประเภทงาน
                    </button>
                </form>
            </div>
        </div>

        <!-- ฝั่งขวา: รายการประเภทงานที่มีในระบบ -->
        <div class="lg:col-span-2 space-y-6">
            <?php if(empty($types_grouped)): ?>
                <div class="bg-white p-12 rounded-[3rem] shadow-sm border border-slate-100 text-center text-slate-400">
                    <i data-lucide="database" size="48" class="mx-auto mb-4 opacity-50"></i>
                    <p class="font-bold">ยังไม่มีข้อมูลประเภทงานในระบบ</p>
                    <p class="text-xs mt-2">โปรดเพิ่มประเภทงานทางเมนูด้านซ้าย เพื่อให้สามารถเลือกได้ในหน้าเพิ่มโครงการ</p>
                </div>
            <?php else: ?>
                <?php foreach($types_grouped as $cat_name => $items): ?>
                <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 bg-slate-50 px-4 py-2 rounded-xl inline-block">
                        หมวดหมู่: <?= htmlspecialchars($cat_name) ?>
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
                        <?php foreach($items as $item): ?>
                        <div class="flex items-center justify-between p-4 rounded-2xl border border-slate-100 hover:border-orange-200 hover:shadow-md transition-all group">
                            <span class="font-bold text-slate-700 text-sm flex items-center gap-2">
                                <i data-lucide="chevron-right" size="14" class="text-orange-500"></i>
                                <?= htmlspecialchars($item['type_name']) ?>
                            </span>
                            
                            <!-- ⭐️ 2. เพิ่มปุ่ม "แก้ไข" และจัดกลุ่มปุ่มจัดการ ⭐️ -->
                            <div class="flex gap-1">
                                <button type="button" onclick="openEditModal(<?= $item['id'] ?>, '<?= htmlspecialchars($item['type_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($item['category'], ENT_QUOTES) ?>')" class="w-8 h-8 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:bg-blue-500 hover:text-white" title="แก้ไข">
                                    <i data-lucide="edit-2" size="14"></i>
                                </button>
                                <a href="settings.php?action=delete&id=<?= $item['id'] ?>" onclick="return confirm('ยืนยันการลบประเภทงานนี้? หากลบไปแล้ว โครงการเดิมที่เคยใช้ประเภทนี้จะยังแสดงผลชื่อเดิมอยู่ แต่จะไม่สามารถเลือกใหม่ได้')" class="w-8 h-8 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:bg-rose-500 hover:text-white" title="ลบ">
                                    <i data-lucide="trash-2" size="14"></i>
                                </a>
                            </div>

                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- ⭐️ 3. เพิ่ม HTML สำหรับหน้าต่าง Modal แก้ไขข้อมูล ⭐️ -->
<div id="editModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] hidden items-center justify-center p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-[3rem] shadow-2xl w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300" id="editModalContent">
        <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                <i data-lucide="edit-3" class="text-blue-500"></i> แก้ไขประเภทงาน
            </h3>
            <button onclick="closeEditModal()" class="text-slate-400 hover:text-rose-500 transition-colors"><i data-lucide="x"></i></button>
        </div>
        <form method="POST" class="p-8 space-y-5">
            <input type="hidden" name="edit_type" value="1">
            <input type="hidden" id="edit_id" name="edit_id" value="">
            
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">หมวดหมู่งาน</label>
                <select id="edit_category" name="category" required class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500 transition-all cursor-pointer shadow-sm">
                    <option value="งานถนน">งานถนน</option>
                    <option value="งานระบายน้ำ">งานระบายน้ำ (ท่อเหลี่ยม/ท่อกลม)</option>
                    <option value="งานไฟฟ้า">งานไฟฟ้าและแสงสว่าง</option>
                    <option value="งานอาคาร">งานอาคารและสิ่งก่อสร้าง</option>
                    <option value="งานแหล่งน้ำ">งานแหล่งน้ำ (ขุดลอกคลอง/สระ)</option>
                    <option value="อื่นๆ">อื่นๆ</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อประเภทงาน</label>
                <input type="text" id="edit_type_name" name="type_name" required class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500 transition-all shadow-sm">
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-slate-900 text-white font-black py-4 rounded-2xl shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2 mt-4">
                <i data-lucide="save" size="18"></i> บันทึกการแก้ไข
            </button>
        </form>
    </div>
</div>

<script>
    // ⭐️ 4. เพิ่ม Script สำหรับเปิด/ปิด Modal ⭐️
    function openEditModal(id, typeName, category) {
        const modal = document.getElementById('editModal');
        const modalContent = document.getElementById('editModalContent');
        
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_type_name').value = typeName;
        document.getElementById('edit_category').value = category;
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Timeout needed for transition to work properly
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
        }, 10);
    }

    function closeEditModal() {
        const modal = document.getElementById('editModal');
        const modalContent = document.getElementById('editModalContent');
        
        modal.classList.add('opacity-0');
        modalContent.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

    window.onload = () => { if(typeof lucide !== 'undefined') lucide.createIcons(); };
</script>

<?php include 'includes/footer.php'; ?>