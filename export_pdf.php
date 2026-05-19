<?php
// export_pdf.php - ระบบสร้างรายงานสรุปโครงการแบบ Native A4 (ไม่ต้องติดตั้ง mPDF)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) { die("กรุณาระบุ ID โครงการ"); }
$id = intval($_GET['id']);

// ดึงข้อมูลโครงการ
$sql = "SELECT p.*, u.full_name as creator_name 
        FROM projects p 
        LEFT JOIN users u ON p.created_by = u.id 
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) { die("ไม่พบข้อมูลโครงการ"); }

// ดึงรูปภาพประกอบ (จำกัด 2 รูป)
$img_res = $conn->query("SELECT file_path FROM project_attachments WHERE project_id = $id AND (file_path LIKE '%.jpg%' OR file_path LIKE '%.png%' OR file_path LIKE '%.jpeg%' OR file_path LIKE '%.webp%') LIMIT 2");
$images = [];
while($img = $img_res->fetch_assoc()) {
    $images[] = 'uploads/projects/' . $img['file_path'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานสรุป_โครงการ_<?= str_pad($project['id'], 5, '0', STR_PAD_LEFT) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap');
        body { 
            font-family: 'Sarabun', sans-serif; 
            background: #cbd5e1; 
        }
        
        /* สไตล์จำลองกระดาษ A4 แนวตั้ง */
        .a4-page {
            width: 210mm;
            min-height: 297mm;
            background: white;
            margin: 20px auto;
            padding: 20mm; /* ระยะขอบกระดาษ */
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
        }
        
        /* CSS เฉพาะตอนกดพิมพ์ (Print / Save as PDF) */
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .a4-page { box-shadow: none; margin: 0; padding: 15mm; width: 100%; height: auto; page-break-after: auto; }
            .no-print { display: none !important; }
            /* บังคับให้เบราว์เซอร์พิมพ์สีพื้นหลังด้วย */
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
        
        /* สไตล์ตารางในเอกสาร */
        .doc-table th { background-color: #f1f5f9; width: 35%; color: #334155; font-weight: 700; border: 1px solid #cbd5e1; padding: 10px 14px; text-align: left;}
        .doc-table td { color: #0f172a; font-weight: 500; border: 1px solid #cbd5e1; padding: 10px 14px; text-align: left;}
    </style>
</head>
<body>
    <!-- แถบเครื่องมือด้านบน (จะถูกซ่อนเวลาสั่งพิมพ์) -->
    <div class="no-print sticky top-0 z-50 bg-slate-900 text-white p-4 shadow-xl flex justify-between items-center px-8">
        <div class="flex items-center gap-3">
            <i data-lucide="file-text" class="text-orange-500"></i>
            <div>
                <p class="text-xs font-bold text-slate-400 leading-none">ระบบสร้างเอกสาร</p>
                <p class="text-sm font-black">ตัวอย่างก่อนพิมพ์ (Print Preview)</p>
            </div>
        </div>
        <div class="flex gap-3">
            <button onclick="window.close()" class="px-5 py-2 rounded-xl font-bold bg-slate-800 hover:bg-slate-700 transition-colors flex items-center gap-2 text-sm">
                <i data-lucide="x" size="16"></i> ปิดหน้าต่าง
            </button>
            <button onclick="window.print()" class="px-6 py-2 rounded-xl font-black bg-orange-600 hover:bg-orange-500 transition-colors flex items-center gap-2 shadow-lg text-sm">
                <i data-lucide="printer" size="16"></i> พิมพ์ / บันทึกเป็น PDF
            </button>
        </div>
    </div>

    <!-- กระดาษ A4 -->
    <div class="a4-page">
        <!-- หัวเอกสาร (Header) -->
        <div class="text-center mb-8 border-b-2 border-slate-900 pb-6 relative">
            <h1 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">สรุปย่อโครงการ (Project Brief)</h1>
            <h2 class="text-xl font-bold text-orange-600">องค์การบริหารส่วนจังหวัดศรีสะเกษ</h2>
            <p class="text-xs font-bold text-slate-500 absolute right-0 bottom-6">วัน/เวลาที่พิมพ์: <?= date('d/m/Y H:i') ?></p>
        </div>

        <!-- Section 1 -->
        <div class="mb-8">
            <h3 class="text-lg font-black text-slate-800 mb-3 flex items-center gap-2 border-l-[5px] border-orange-600 pl-3 leading-none">
                1. ข้อมูลทั่วไป
            </h3>
            <table class="w-full doc-table border-collapse">
                <tr><th>ชื่อโครงการ</th><td><?= e($project['project_name']) ?></td></tr>
                <tr><th>ชื่อสายทาง</th><td><?= e($project['route_name'] ?: '-') ?></td></tr>
                <tr><th>อำเภอ / ปีงบประมาณ</th><td>อ.<?= e($project['district_name']) ?> / ปีงบประมาณ <?= $project['fiscal_year'] ?></td></tr>
                <tr><th>ประเภทโครงสร้าง</th><td><?= e($project['infrastructure_type']) ?></td></tr>
                <tr><th>สถานะปัจจุบัน</th><td class="font-bold <?= $project['status']=='เสร็จสิ้น' ? 'text-emerald-600' : ($project['status']=='มีการเปลี่ยนแปลงหรือแก้ไข' ? 'text-red-600' : 'text-orange-600') ?>"><?= e($project['status']) ?></td></tr>
            </table>
        </div>

        <!-- Section 2 -->
        <div class="mb-8">
            <h3 class="text-lg font-black text-slate-800 mb-3 flex items-center gap-2 border-l-[5px] border-blue-600 pl-3 leading-none">
                2. ข้อมูลวิศวกรรมและพิกัด
            </h3>
            <table class="w-full doc-table border-collapse">
                <tr><th>ความกว้างผิวจราจร</th><td><?= number_format($project['width'], 2) ?> เมตร</td></tr>
                <tr><th>ความยาวโครงการ</th><td><?= number_format($project['distance'], 2) ?> เมตร</td></tr>
                <tr><th>พื้นที่ดำเนินการสุทธิ</th><td class="text-orange-600 font-black text-lg"><?= number_format($project['area'], 2) ?> ตร.ม.</td></tr>
                <tr><th>พิกัดเริ่มต้น (Start)</th><td class="font-mono text-sm"><?= ($project['start_lat'] ? $project['start_lat'] . ', ' . $project['start_long'] : 'ไม่ระบุ') ?></td></tr>
                <tr><th>พิกัดสิ้นสุด (End)</th><td class="font-mono text-sm"><?= ($project['end_lat'] ? $project['end_lat'] . ', ' . $project['end_long'] : 'ไม่ระบุ') ?></td></tr>
            </table>
        </div>

        <!-- Section 3 -->
        <div class="mb-8">
            <h3 class="text-lg font-black text-slate-800 mb-3 flex items-center gap-2 border-l-[5px] border-emerald-600 pl-3 leading-none">
                3. ข้อมูลงบประมาณดำเนินการ
            </h3>
            <table class="w-full doc-table border-collapse">
                <tr><th>งบประมาณที่ได้รับ</th><td class="text-xl font-black text-slate-900"><?= number_format($project['budget_amount'], 2) ?> บาท</td></tr>
                <tr><th>ประเภทงบประมาณ</th><td><?= e($project['budget_type']) ?></td></tr>
                <tr><th>เจ้าหน้าที่ผู้ควบคุมงาน</th><td><?= e($project['supervisor_name'] ?: 'ไม่ระบุ') ?></td></tr>
            </table>
        </div>

        <!-- Section 4 (รูปภาพ ถ้ามี) -->
        <?php if (count($images) > 0): ?>
        <div class="mb-8" style="page-break-inside: avoid;">
            <h3 class="text-lg font-black text-slate-800 mb-4 flex items-center gap-2 border-l-[5px] border-slate-600 pl-3 leading-none">
                4. ภาพถ่ายประกอบหน้างาน
            </h3>
            <div class="grid grid-cols-2 gap-6">
                <?php foreach ($images as $img_path): if(file_exists($img_path)): ?>
                    <div class="rounded-xl overflow-hidden border-2 border-slate-200 h-64 bg-slate-50 flex items-center justify-center p-1">
                        <img src="<?= $img_path ?>" class="w-full h-full object-cover rounded-lg">
                    </div>
                <?php endif; endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ท้ายกระดาษ (Footer) -->
        <div class="absolute bottom-10 right-10 text-right mt-10">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest leading-relaxed">
                ส่งออกจากระบบฐานข้อมูลสารสนเทศ (GIS)<br>
                สำนักช่าง องค์การบริหารส่วนจังหวัดศรีสะเกษ
            </p>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        // สั่งเปิดหน้าจอ Print (Save as PDF) อัตโนมัติเมื่อโหลดเว็บเสร็จ
        window.onload = () => {
            setTimeout(() => {
                window.print();
            }, 800);
        };
    </script>
</body>
</html>