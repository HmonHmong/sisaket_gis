<?php
// view_project.php - ระบบแสดงผลข้อมูลโครงการวิศวกรรมฉบับสมบูรณ์ (แผนที่, แกลเลอรี่, ไทม์ไลน์)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) { header("Location: projects.php"); exit; }
$id = intval($_GET['id']);

// 1. ดึงข้อมูลโครงการหลัก
$sql = "SELECT p.*, u.full_name as creator_name 
        FROM projects p 
        LEFT JOIN users u ON p.created_by = u.id 
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) { die("ไม่พบข้อมูลโครงการ"); }

// 2. ดึงพิกัดจุดผ่าน/หมู่บ้าน
$points_res = $conn->query("SELECT * FROM project_points WHERE project_id = $id ORDER BY order_index ASC");
$waypoints = [];
while($wp = $points_res->fetch_assoc()) $waypoints[] = $wp;

// 3. ดึงไฟล์แนบ (แยกประเภทรูปภาพและเอกสาร)
$files_res = $conn->query("SELECT * FROM project_attachments WHERE project_id = $id ORDER BY uploaded_at DESC");
$images = [];
$documents = [];
while($f = $files_res->fetch_assoc()) {
    $ext = strtolower(pathinfo($f['file_path'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $images[] = $f;
    } else {
        $documents[] = $f;
    }
}

// 4. ดึงประวัติสถานะ (Timeline)
$history_res = $conn->query("SELECT h.*, u.full_name as changer_name 
                             FROM project_status_history h 
                             LEFT JOIN users u ON h.changed_by = u.id 
                             WHERE h.project_id = $id 
                             ORDER BY h.created_at DESC");

include 'includes/header.php';
?>

<!-- เพิ่ม CSS สำหรับ Lightbox แบบง่าย -->
<style>
    .lightbox { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.95); z-index: 2000; align-items: center; justify-content: center; padding: 2rem; cursor: zoom-out; }
    .lightbox.active { display: flex; }
    .lightbox img { max-width: 90%; max-height: 90%; border-radius: 1rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
</style>

<div class="max-w-[1600px] mx-auto pb-20 animate-in fade-in duration-700">
    <!-- Header & Actions -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-10 px-4">
        <div>
            <div class="flex items-center gap-3 mb-3">
                <?php 
                    $status_colors = [
                        'รอดำเนินการ' => 'bg-slate-500',
                        'กำลังดำเนินการ' => 'bg-orange-500',
                        'เสร็จสิ้น' => 'bg-emerald-500',
                        'มีการเปลี่ยนแปลงหรือแก้ไข' => 'bg-rose-600'
                    ];
                    $s_color = $status_colors[$project['status']] ?? 'bg-slate-500';
                ?>
                <span class="<?= $s_color ?> text-white px-5 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest shadow-lg animate-pulse"><?= e($project['status']) ?></span>
                <span class="text-slate-400 font-bold text-[10px] uppercase tracking-widest border-l pl-3 border-slate-200">PROJECT ID: #<?= str_pad($project['id'], 5, '0', STR_PAD_LEFT) ?></span>
            </div>
            <h2 class="text-4xl font-black text-slate-900 tracking-tight leading-tight"><?= e($project['project_name']) ?></h2>
            <div class="flex flex-wrap items-center gap-4 mt-2 text-slate-500 font-bold text-sm">
                <span class="flex items-center gap-1.5 text-orange-600"><i data-lucide="map-pin" size="16"></i> อ.<?= e($project['district_name']) ?></span>
                <span class="text-slate-200">|</span>
                <span>สายทาง: <?= e($project['route_name'] ?: 'ไม่ระบุสายทาง') ?></span>
            </div>
        </div>
        <div class="flex gap-3 w-full lg:w-auto">
            <a href="edit_project.php?id=<?= $id ?>" class="flex-1 lg:flex-none bg-white border-2 border-slate-200 text-slate-700 px-8 py-4 rounded-[2rem] font-black text-xs hover:border-orange-500 hover:text-orange-600 transition-all flex items-center justify-center gap-2 shadow-sm">
                <i data-lucide="edit-3" size="18"></i> แก้ไขข้อมูลโครงการ
            </a>
            <button onclick="window.print()" class="flex-1 lg:flex-none bg-slate-900 text-white px-10 py-4 rounded-[2rem] font-black text-xs hover:bg-orange-600 transition-all flex items-center justify-center gap-2 shadow-xl">
                <i data-lucide="printer" size="18"></i> ออกรายงาน PDF
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-8 px-4">
        <!-- Sidebar: ข้อมูลเทคนิคและไทม์ไลน์ -->
        <div class="xl:col-span-1 space-y-8">
            <!-- สรุปตัวเลขวิศวกรรม -->
            <div class="bg-white p-8 rounded-[3.5rem] shadow-sm border border-slate-100">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i data-lucide="ruler" size="14" class="text-orange-600"></i> Engineering Metrics
                </h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-4 bg-slate-50 rounded-3xl">
                        <span class="text-xs font-bold text-slate-500">ความกว้าง</span>
                        <span class="text-lg font-black text-slate-800"><?= number_format($project['width'], 2) ?> ม.</span>
                    </div>
                    <div class="flex justify-between items-center p-4 bg-slate-50 rounded-3xl">
                        <span class="text-xs font-bold text-slate-500">ความยาว</span>
                        <span class="text-lg font-black text-slate-800"><?= number_format($project['distance'], 2) ?> ม.</span>
                    </div>
                    <div class="p-6 bg-emerald-600 rounded-[2.5rem] text-white shadow-lg shadow-emerald-100 text-center">
                        <p class="text-[10px] font-black uppercase opacity-70 mb-1">พื้นที่รวมสุทธิ</p>
                        <p class="text-3xl font-black"><?= number_format($project['area'], 2) ?> <span class="text-xs">ตร.ม.</span></p>
                    </div>
                </div>
            </div>

            <!-- งบประมาณ -->
            <div class="bg-slate-900 p-8 rounded-[3.5rem] text-white shadow-2xl relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 text-white/5 rotate-12 group-hover:scale-110 transition-transform">
                    <i data-lucide="banknote" size="150"></i>
                </div>
                <div class="relative z-10 space-y-6">
                    <div>
                        <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest mb-1">งบประมาณดำเนินโครงการ</p>
                        <h4 class="text-3xl font-black"><?= number_format($project['budget_amount'], 2) ?> ฿</h4>
                        <p class="text-[10px] text-slate-400 font-bold mt-2 uppercase italic"><?= e($project['budget_type']) ?></p>
                    </div>
                    <div class="pt-6 border-t border-white/10 flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center">
                            <i data-lucide="hard-hat" class="text-orange-500"></i>
                        </div>
                        <div>
                            <p class="text-[8px] font-black text-slate-500 uppercase">ผู้ควบคุมงาน</p>
                            <p class="text-xs font-black text-slate-200"><?= e($project['supervisor_name'] ?: 'ไม่ระบุ') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ไทม์ไลน์สถานะ -->
            <div class="bg-white p-8 rounded-[3.5rem] shadow-sm border border-slate-100">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-8 flex items-center gap-2">
                    <i data-lucide="history" size="14"></i> Project Timeline
                </h3>
                <div class="space-y-8 relative before:absolute before:left-[11px] before:top-2 before:bottom-0 before:w-0.5 before:bg-slate-100">
                    <?php if($history_res->num_rows > 0): ?>
                        <?php while($h = $history_res->fetch_assoc()): ?>
                        <div class="relative pl-10">
                            <div class="absolute left-0 top-1.5 w-6 h-6 rounded-full bg-white border-4 border-slate-100 flex items-center justify-center z-10">
                                <div class="w-2 h-2 rounded-full <?= $h['status']=='เสร็จสิ้น'?'bg-emerald-500':'bg-slate-300' ?>"></div>
                            </div>
                            <div>
                                <div class="flex justify-between items-start">
                                    <p class="text-[11px] font-black text-slate-800 uppercase leading-none"><?= e($h['status']) ?></p>
                                    <p class="text-[8px] font-bold text-slate-400"><?= date('d/m/y', strtotime($h['created_at'])) ?></p>
                                </div>
                                <?php if($h['remark']): ?>
                                    <p class="text-[10px] text-slate-500 font-medium italic mt-2 border-l-2 border-slate-100 pl-3">"<?= e($h['remark']) ?>"</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center text-xs text-slate-300 italic py-4">ยังไม่มีประวัติสถานะ</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Content: แผนที่และสื่อประกอบ -->
        <div class="xl:col-span-3 space-y-8">
            <!-- แผนที่แบบ Full-width -->
            <div class="bg-white rounded-[4rem] p-2 shadow-2xl border border-slate-100 relative h-[600px] overflow-hidden group">
                <div id="map" class="w-full h-full rounded-[3.8rem] z-0"></div>
                <div class="absolute top-8 right-8 z-[1000] flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="toggleLayer('sat')" id="btn-sat" class="w-12 h-12 bg-white rounded-2xl shadow-xl flex items-center justify-center hover:bg-orange-600 hover:text-white transition-all active"><i data-lucide="satellite"></i></button>
                    <button onclick="toggleLayer('street')" id="btn-street" class="w-12 h-12 bg-white rounded-2xl shadow-xl flex items-center justify-center hover:bg-orange-600 hover:text-white transition-all"><i data-lucide="map"></i></button>
                </div>
            </div>

            <!-- แกลเลอรี่รูปภาพและเอกสาร -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- แกลเลอรี่รูปภาพ -->
                <div class="bg-white p-10 rounded-[4rem] shadow-sm border border-slate-100">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-8 flex items-center gap-3">
                        <i data-lucide="image" class="text-blue-500"></i> ภาพถ่ายหน้างานจริง
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php if(count($images) > 0): ?>
                            <?php foreach($images as $img): ?>
                            <div class="aspect-square rounded-3xl overflow-hidden cursor-zoom-in group relative" onclick="openLightbox('uploads/projects/<?= $img['file_path'] ?>')">
                                <img src="uploads/projects/<?= $img['file_path'] ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                <div class="absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-full py-20 border-4 border-dashed border-slate-50 rounded-[3rem] text-center text-slate-300">
                                <i data-lucide="camera-off" size="48" class="mx-auto mb-4 opacity-10"></i>
                                <p class="font-bold text-sm uppercase tracking-widest">ยังไม่มีรูปภาพประกอบ</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- รายการเอกสาร -->
                <div class="bg-white p-10 rounded-[4rem] shadow-sm border border-slate-100">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-8 flex items-center gap-3">
                        <i data-lucide="file-text" class="text-orange-500"></i> เอกสารประกอบทางเทคนิค
                    </h3>
                    <div class="space-y-3">
                        <?php if(count($documents) > 0): ?>
                            <?php foreach($documents as $doc): ?>
                            <a href="uploads/projects/<?= $doc['file_path'] ?>" target="_blank" class="flex items-center justify-between p-5 bg-slate-50 rounded-[2rem] hover:bg-orange-50 transition-all group">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-slate-400 group-hover:text-orange-600 shadow-sm">
                                        <i data-lucide="file"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-black text-slate-700 truncate max-w-[200px]"><?= e($doc['file_name']) ?></p>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Technical Document</p>
                                    </div>
                                </div>
                                <i data-lucide="download" size="18" class="text-slate-300 group-hover:text-orange-600 transition-colors"></i>
                            </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="py-20 border-4 border-dashed border-slate-50 rounded-[3rem] text-center text-slate-300">
                                <i data-lucide="file-x" size="48" class="mx-auto mb-4 opacity-10"></i>
                                <p class="font-bold text-sm uppercase tracking-widest">ยังไม่มีไฟล์เอกสาร</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox Component -->
<div id="lightbox" class="lightbox" onclick="this.classList.remove('active')">
    <img id="lightbox-img" src="" alt="Zoom">
</div>

<script>
    const project = <?= json_encode($project) ?>;
    let map, satLayer, streetLayer;

    function initMap() {
        const startPos = [parseFloat(project.start_lat || 15.1186), parseFloat(project.start_long || 104.3220)];
        const endPos = [parseFloat(project.end_lat), parseFloat(project.end_long)];

        const satBase = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}');
        const labels = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_only_labels/{z}/{x}/{y}{r}.png');
        satLayer = L.layerGroup([satBase, labels]);
        streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');

        map = L.map('map', { zoomControl: false, layers: [satLayer] }).setView(startPos, 14);

        if (project.start_lat) {
            L.circleMarker(startPos, { radius: 10, fillColor: '#f97316', color: '#fff', weight: 4, fillOpacity: 1 }).addTo(map).bindPopup("จุดเริ่มต้นโครงการ");
        }
        if (project.end_lat) {
            const ep = [parseFloat(project.end_lat), parseFloat(project.end_long)];
            L.circleMarker(ep, { radius: 10, fillColor: '#0ea5e9', color: '#fff', weight: 4, fillOpacity: 1 }).addTo(map).bindPopup("จุดสิ้นสุดโครงการ");
            
            // วาดเส้นแนวถนนโครงการ
            const line = L.polyline([startPos, ep], { color: '#fbbf24', weight: 6, opacity: 0.8, dashArray: '10, 10' }).addTo(map);
            map.fitBounds(line.getBounds(), { padding: [100, 100] });
        }
    }

    function toggleLayer(type) {
        if(type === 'sat') { map.addLayer(satLayer); map.removeLayer(streetLayer); }
        else { map.addLayer(streetLayer); map.removeLayer(satLayer); }
        document.getElementById('btn-sat').classList.toggle('active', type==='sat');
        document.getElementById('btn-street').classList.toggle('active', type==='street');
    }

    function openLightbox(src) {
        const lb = document.getElementById('lightbox');
        document.getElementById('lightbox-img').src = src;
        lb.classList.add('active');
    }

    window.onload = () => {
        initMap();
        lucide.createIcons();
    };
</script>

<?php include 'includes/footer.php'; ?>