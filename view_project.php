<?php
// view_project.php - แก้ไขปัญหาแผนที่จอขาวด้วย ResizeObserver
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) { header("Location: projects.php"); exit; }
$id = intval($_GET['id']);

$sql = "SELECT p.*, u.full_name as creator_name FROM projects p LEFT JOIN users u ON p.created_by = u.id WHERE p.id = ?";
$stmt = $conn->prepare($sql); $stmt->bind_param("i", $id); $stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
if (!$project) { header("Location: projects.php"); exit; }

$points_res = $conn->query("SELECT * FROM project_points WHERE project_id = $id ORDER BY order_index ASC");
$locations = [];
while($pt = $points_res->fetch_assoc()) {
    $loc_str = "อ." . $pt['district'];
    if($pt['sub_district']) $loc_str = "ต." . $pt['sub_district'] . " " . $loc_str;
    if($pt['village']) { $v = trim($pt['village']); $v = str_starts_with($v, 'บ.') ? $v : 'บ.' . $v; if($pt['moo']) $v .= " ม." . $pt['moo']; $loc_str = $v . " " . $loc_str; }
    $locations[] = $loc_str;
}

$attachments_res = $conn->query("SELECT * FROM project_attachments WHERE project_id = $id");
$images = []; $docs = [];
while($f = $attachments_res->fetch_assoc()) {
    if(in_array(strtolower(pathinfo($f['file_path'], PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif','webp'])) { $images[] = $f; } else { $docs[] = $f; }
}

$history_res = $conn->query("SELECT h.*, u.full_name FROM project_status_history h LEFT JOIN users u ON h.changed_by = u.id WHERE h.project_id = $id ORDER BY h.created_at DESC");
$timeline = []; while($h = $history_res->fetch_assoc()) { $timeline[] = $h; }

include 'includes/header.php';

$st = $project['status'];
$st_color = 'slate'; $st_bg = 'bg-slate-100'; $st_text = 'text-slate-600'; $st_icon = 'clock';
if($st == 'กำลังดำเนินการ') { $st_color = 'orange'; $st_bg = 'bg-orange-100'; $st_text = 'text-orange-600'; $st_icon = 'activity'; }
if($st == 'เสร็จสิ้น') { $st_color = 'emerald'; $st_bg = 'bg-emerald-100'; $st_text = 'text-emerald-600'; $st_icon = 'check-circle'; }
if($st == 'มีการเปลี่ยนแปลงหรือแก้ไข') { $st_color = 'rose'; $st_bg = 'bg-rose-100'; $st_text = 'text-rose-600'; $st_icon = 'alert-triangle'; }
if($st == 'ยกเลิกโครงการ') { $st_color = 'slate-800'; $st_bg = 'bg-slate-800'; $st_text = 'text-white'; $st_icon = 'x-circle'; }
?>

<!-- ใช้ cdnjs เพื่อความเสถียรของการโหลดแผนที่ -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<div class="max-w-[1600px] mx-auto pb-20 animate-in fade-in duration-500 w-full px-4 md:px-0">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest <?= $st_bg . ' ' . $st_text ?> flex items-center gap-1.5"><i data-lucide="<?= $st_icon ?>" size="12"></i> <?= htmlspecialchars($st) ?></span>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-200/50 px-3 py-1 rounded-full">Project ID: #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></span>
            </div>
            <h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-tight"><?= htmlspecialchars($project['project_name']) ?></h2>
            <div class="flex flex-wrap items-center gap-2 mt-2"><span class="text-xs font-bold text-orange-600 flex items-center gap-1 bg-orange-50 px-2.5 py-1 rounded-md"><i data-lucide="map-pin" size="14"></i> อ.<?= htmlspecialchars($project['district_name']) ?></span>
                <span class="text-slate-300">|</span>
                <span class="text-xs font-bold text-slate-500">สายทาง: <?= htmlspecialchars($project['route_name'] ?: 'ไม่ระบุ') ?></span>
            </div>
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto">
            <?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff'): ?>
            <a href="edit_project.php?id=<?= $id ?>" class="flex-1 md:flex-none bg-white border border-slate-200 text-slate-700 px-5 py-3 rounded-xl text-xs font-black hover:bg-slate-50 shadow-sm flex items-center justify-center gap-2"><i data-lucide="edit-3" size="16"></i> แก้ไขข้อมูลโครงการ</a>
            <?php endif; ?>
            <a href="export_pdf.php?id=<?= $id ?>" target="_blank" class="flex-1 md:flex-none bg-slate-900 text-white px-5 py-3 rounded-xl text-xs font-black hover:bg-orange-600 shadow-lg flex items-center justify-center gap-2"><i data-lucide="printer" size="16"></i> ออกรายงาน PDF</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
        <div class="space-y-6 md:space-y-8">
            <div class="bg-white p-6 md:p-8 rounded-[3rem] shadow-sm border border-slate-100">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2"><i data-lucide="ruler" class="text-orange-500" size="16"></i> Engineering Metrics</p>
                <div class="space-y-4">
                    <div class="flex justify-between items-end border-b border-slate-50 pb-3"><span class="text-xs font-bold text-slate-500">ประเภทผิวทาง</span><span class="text-sm font-black text-slate-800"><?= htmlspecialchars($project['infrastructure_type']) ?></span></div>
                    <div class="flex justify-between items-end border-b border-slate-50 pb-3"><span class="text-xs font-bold text-slate-500">ความกว้าง</span><span class="text-sm font-black text-slate-800"><?= number_format($project['width'], 2) ?> ม.</span></div>
                    <div class="flex justify-between items-end border-b border-slate-50 pb-3"><span class="text-xs font-bold text-slate-500">ความยาว</span><span class="text-sm font-black text-slate-800"><?= number_format($project['distance'], 2) ?> ม.</span></div>
                </div>
                <div class="mt-6 bg-emerald-500 text-white p-5 rounded-[2rem] text-center shadow-lg"><p class="text-[10px] font-black uppercase opacity-80 mb-1">พื้นที่รวมสุทธิ</p><h3 class="text-3xl font-black"><?= number_format($project['area'], 2) ?> <span class="text-sm font-bold">ตร.ม.</span></h3></div>
            </div>
            
            <div class="bg-slate-900 text-white p-6 md:p-8 rounded-[3rem] shadow-xl relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 opacity-10"><i data-lucide="coins" size="150"></i></div>
                <div class="relative z-10">
                    <p class="text-[10px] font-black text-orange-400 uppercase tracking-widest mb-1">งบประมาณดำเนินการโครงการ</p><h3 class="text-4xl font-black text-white"><?= number_format($project['budget_amount']) ?> ฿</h3>
                    <p class="text-[10px] font-bold text-slate-400 mt-2"><?= htmlspecialchars($project['budget_type'] ?: 'ไม่ระบุ') ?> (ปี <?= $project['fiscal_year'] ?>)</p>
                    <div class="mt-6 pt-5 border-t border-slate-800 flex items-center gap-4 bg-slate-800/50 p-4 rounded-2xl"><div class="w-10 h-10 rounded-full bg-orange-600/20 text-orange-500 flex items-center justify-center shrink-0"><i data-lucide="hard-hat" size="20"></i></div><div><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">ผู้ควบคุมงาน</p><p class="text-sm font-black mt-0.5"><?= htmlspecialchars($project['supervisor_name'] ?: 'ยังไม่ระบุ') ?></p></div></div>
                </div>
            </div>

            <div class="bg-white p-6 md:p-8 rounded-[3rem] shadow-sm border border-slate-100">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2"><i data-lucide="history" class="text-slate-400" size="16"></i> Project Timeline</p>
                <?php if(empty($timeline)): ?>
                    <div class="py-6 text-center text-slate-300 border-2 border-dashed border-slate-100 rounded-2xl"><p class="text-[10px] font-bold uppercase">ยังไม่มีประวัติสถานะ</p></div>
                <?php else: ?>
                    <div class="space-y-6 relative before:absolute before:inset-0 before:ml-2 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-200 before:to-transparent">
                        <?php foreach($timeline as $i => $log): 
                            $l_st = $log['status']; $l_color = 'slate'; 
                            if($l_st == 'เสร็จสิ้น') $l_color = 'emerald'; if($l_st == 'กำลังดำเนินการ') $l_color = 'orange'; if($l_st == 'มีการเปลี่ยนแปลงหรือแก้ไข') $l_color = 'rose'; 
                        ?>
                        <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                            <div class="flex items-center justify-center w-5 h-5 rounded-full border-4 border-white bg-<?= $l_color ?>-500 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10"></div>
                            <div class="w-[calc(100%-2.5rem)] md:w-[calc(50%-1.5rem)] p-4 rounded-2xl bg-slate-50 border border-slate-100 shadow-sm">
                                <div class="flex items-center justify-between mb-1"><span class="text-[9px] font-black uppercase text-<?= $l_color ?>-600 tracking-widest"><?= $l_st ?></span><time class="text-[9px] font-bold text-slate-400"><?= date('d/m/Y', strtotime($log['created_at'])) ?></time></div>
                                <?php if($log['remark']): ?><p class="text-[11px] text-slate-600 font-medium leading-tight mt-2 bg-white p-2 rounded-lg border border-slate-100"><?= htmlspecialchars($log['remark']) ?></p><?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6 md:space-y-8 flex flex-col h-full">
            <div class="bg-white p-2 rounded-[3.5rem] shadow-sm border border-slate-100 flex-1 min-h-[400px] lg:min-h-[500px] relative overflow-hidden group">
                <div class="absolute top-6 right-6 z-10 bg-white/90 backdrop-blur px-4 py-2 rounded-2xl shadow-lg border border-white flex items-center gap-2 pointer-events-none">
                    <span class="w-2.5 h-2.5 rounded-full bg-orange-500 animate-pulse"></span><span class="text-[10px] font-black text-slate-800 uppercase tracking-widest">แผนที่โครงข่าย (GIS)</span>
                </div>
                
                <div class="absolute bottom-6 left-6 z-10 bg-white/90 backdrop-blur p-5 rounded-[2rem] shadow-2xl border border-white text-xs pointer-events-none max-w-[80%] md:max-w-xs">
                    <p class="font-black text-slate-800 mb-3 border-b border-slate-200 pb-3 flex items-center gap-2"><i data-lucide="map-pin" class="text-orange-500" size="16"></i> พื้นที่ดำเนินงาน:</p>
                    <div class="space-y-2 max-h-32 overflow-y-auto no-scrollbar">
                        <?php foreach($locations as $loc): ?><p class="text-slate-600 font-medium flex items-start gap-2 leading-tight"><i data-lucide="check" class="text-emerald-500 shrink-0 mt-0.5" size="12"></i> <?= htmlspecialchars($loc) ?></p><?php endforeach; ?>
                        <?php if(empty($locations)): ?><p class="text-slate-400 italic">ไม่ได้ระบุพิกัดหมู่บ้าน</p><?php endif; ?>
                    </div>
                </div>

                <!-- ⭐️ ปรับ CSS กล่องแผนที่เพื่อให้ Leaflet อ่านขนาดได้ 100% ⭐️ -->
                <div id="projectMap" class="w-full h-full rounded-[3rem] z-0" style="min-height: 400px;"></div>
                
                <!-- ตัวบังแผนที่ หากไม่มีพิกัด -->
                <div id="noMapOverlay" class="absolute inset-0 bg-slate-100/90 backdrop-blur-sm z-20 rounded-[3rem] flex flex-col items-center justify-center text-slate-400 hidden"><i data-lucide="map-pin-off" size="48" class="mb-3"></i><p class="font-black text-sm uppercase tracking-widest">ไม่พบข้อมูลพิกัดในระบบ</p></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                <div class="bg-white p-6 md:p-8 rounded-[3rem] shadow-sm border border-slate-100">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-6 flex items-center gap-2 border-l-4 border-blue-500 pl-3"><i data-lucide="image" class="text-blue-500" size="16"></i> ภาพถ่ายหน้างานจริง</p>
                    <?php if(empty($images)): ?><div class="h-32 border-2 border-dashed border-slate-100 flex items-center justify-center text-slate-300 rounded-[2rem]"><p class="text-[9px] font-black uppercase">ไม่มีรูปภาพ</p></div>
                    <?php else: ?><div class="grid grid-cols-2 gap-3"><?php foreach($images as $img): ?><a href="uploads/projects/<?= $img['file_path'] ?>" target="_blank" class="block aspect-square rounded-2xl overflow-hidden border border-slate-100 hover:shadow-md hover:scale-105 transition-all"><img src="uploads/projects/<?= $img['file_path'] ?>" class="w-full h-full object-cover"></a><?php endforeach; ?></div><?php endif; ?>
                </div>

                <div class="bg-white p-6 md:p-8 rounded-[3rem] shadow-sm border border-slate-100">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2 border-l-4 border-orange-500 pl-3"><i data-lucide="file-text" class="text-orange-500" size="16"></i> เอกสารประกอบทางเทคนิค</p>
                    <?php if(empty($docs)): ?><div class="h-32 rounded-[2rem] border-2 border-dashed border-slate-100 flex flex-col items-center justify-center text-slate-300"><i data-lucide="file-x" size="24" class="mb-2 opacity-50"></i><p class="text-[9px] font-black uppercase">ไม่มีเอกสาร</p></div>
                    <?php else: ?><div class="space-y-3"><?php foreach($docs as $doc): ?><a href="uploads/projects/<?= $doc['file_path'] ?>" target="_blank" class="flex items-center gap-3 p-4 rounded-2xl bg-slate-50 hover:bg-orange-50 hover:border-orange-200 transition-colors border border-slate-100 group"><div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-rose-500 shadow-sm group-hover:scale-110 transition-transform shrink-0"><i data-lucide="file-pdf" size="18"></i></div><div class="flex-1 min-w-0"><p class="text-xs font-black text-slate-700 truncate group-hover:text-orange-600 transition-colors"><?= htmlspecialchars($doc['file_name']) ?></p><p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">PDF Document</p></div></a><?php endforeach; ?></div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if(typeof lucide !== 'undefined') lucide.createIcons();

        const lat = parseFloat(<?= json_encode($project['start_lat']) ?>) || 0; 
        const lng = parseFloat(<?= json_encode($project['start_long']) ?>) || 0;
        const eLat = parseFloat(<?= json_encode($project['end_lat']) ?>) || 0; 
        const eLng = parseFloat(<?= json_encode($project['end_long']) ?>) || 0;
        
        if(lat !== 0 && lng !== 0) {
            const mapContainer = document.getElementById('projectMap');
            const map = L.map(mapContainer, { zoomControl: false }).setView([lat, lng], 15);
            L.control.zoom({ position: 'topright' }).addTo(map);
            
            // ⭐️ เปลี่ยนมาใช้เซิร์ฟเวอร์แผนที่ของ Google Maps (เสถียร 100% และไม่โดนบล็อก) ⭐️
            L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', { 
                maxZoom: 20,
                attribution: '© Google Maps'
            }).addTo(map);

            let color = '#94a3b8'; if("<?= $project['status'] ?>" === 'เสร็จสิ้น') color = '#10b981'; else if("<?= $project['status'] ?>" === 'กำลังดำเนินการ') color = '#f97316';
            L.circleMarker([lat, lng], { radius: 10, fillColor: color, color: '#ffffff', weight: 3, fillOpacity: 1 }).addTo(map).bindPopup('<b style="font-family:Sarabun">📍 จุดเริ่มต้น (Start)</b>').openPopup();

            if(eLat !== 0 && eLng !== 0) {
                L.circleMarker([eLat, eLng], { radius: 10, fillColor: '#3b82f6', color: '#ffffff', weight: 3, fillOpacity: 1 }).addTo(map).bindPopup('<b style="font-family:Sarabun">🏁 จุดสิ้นสุด (End)</b>');
                L.polyline([[lat, lng], [eLat, eLng]], { color: color, weight: 6, opacity: 0.8, dashArray: '8,8' }).addTo(map);
                map.fitBounds([[lat, lng], [eLat, eLng]], { padding: [50, 50] });
            }

            // ⭐️ บังคับให้แผนที่คำนวณขนาดตัวเองใหม่ ป้องกันจอสีเทา ⭐️
            const resizeObserver = new ResizeObserver(() => {
                map.invalidateSize();
            });
            resizeObserver.observe(mapContainer);
            setTimeout(() => { map.invalidateSize(); }, 500);
        } else {
            document.getElementById('noMapOverlay').classList.remove('hidden');
        }
    });
</script>

<?php include 'includes/footer.php'; ?>