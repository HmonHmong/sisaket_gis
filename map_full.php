<?php
// map_full.php - แผนที่สารสนเทศแบบเต็มจอ (แก้แผนที่เทา + เปลี่ยนใช้ Google Maps + แก้ไอคอนหาย)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// 1. ดึงประเภทงานทั้งหมดสำหรับตัวกรอง
$type_res = $conn->query("SELECT DISTINCT infrastructure_type FROM projects WHERE deleted_at IS NULL AND infrastructure_type != ''");
$types = [];
while($t = $type_res->fetch_assoc()) {
    $types[] = $t['infrastructure_type'];
}

$districts = ["เมืองศรีสะเกษ", "ยางชุมน้อย", "กันทรารมย์", "กันทรลักษณ์", "ขุขันธ์", "ไพรบึง", "ปรางค์กู่", "ขุนหาญ", "ราษีไศล", "อุทุมพรพิสัย", "บึงบูรพ์", "ห้วยทับทัน", "โนนคูณ", "ศรีรัตนะ", "น้ำเกลี้ยง", "วังหิน", "ภูสิงห์", "เมืองจันทร์", "เบญจลักษณ์", "พยุห์", "โพธิ์ศรีสุวรรณ", "ศิลาลาด"];

// 2. ดึงข้อมูลโครงการพร้อมไฟล์แนบรูปแรก (เฉพาะที่มีพิกัด)
$sql = "SELECT p.*, (SELECT file_path FROM project_attachments WHERE project_id = p.id LIMIT 1) as thumbnail 
        FROM projects p WHERE p.start_lat IS NOT NULL AND p.start_long IS NOT NULL AND p.deleted_at IS NULL";
$res = $conn->query($sql);
$projects = [];
$stats = [
    'เสร็จสิ้น' => 0, 
    'กำลังดำเนินการ' => 0, 
    'รอดำเนินการ' => 0, 
    'มีการเปลี่ยนแปลงหรือแก้ไข' => 0, 
    'total_budget' => 0
];

while($row = $res->fetch_assoc()) {
    $projects[] = $row;
    $status = $row['status'];
    if(isset($stats[$status])) {
        $stats[$status]++;
    }
    $stats['total_budget'] += $row['budget_amount'];
}

include 'includes/header.php';
?>

<!-- โหลดไลบรารี Leaflet จาก CDN หลัก (เสถียรกว่า) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    /* ปรับแต่ง Popup ให้สวยงามและเข้ากับธีม V2.5 */
    .leaflet-popup-content-wrapper { 
        background: rgba(15, 23, 42, 0.95); 
        backdrop-filter: blur(8px);
        color: white; 
        border-radius: 1.5rem; 
        padding: 0; 
        overflow: hidden; 
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); 
        border: 1px solid rgba(255,255,255,0.1);
    }
    .leaflet-popup-content { margin: 0; min-width: 260px !important; }
    .leaflet-popup-tip { background: rgba(15, 23, 42, 0.95); }
    
    /* ⭐️ บังคับความสูงของกล่องแผนที่ ป้องกันปัญหาจอยุบ/จอเทา ⭐️ */
    #map-container-wrapper {
        min-height: 600px;
        height: calc(100vh - 180px);
    }
    #gisMap {
        width: 100%;
        height: 100%;
        z-index: 10;
    }
</style>

<div class="max-w-[1600px] mx-auto pb-10 animate-in fade-in duration-500 w-full px-4 md:px-0">
    
    <!-- Top Filter Bar -->
    <div class="bg-white p-3 rounded-[2rem] shadow-sm border border-slate-100 flex flex-col lg:flex-row gap-3 items-center justify-between mb-4 w-full">
        <div class="flex items-center gap-3 w-full lg:w-1/3 px-3">
            <div class="w-10 h-10 bg-orange-600 text-white rounded-xl flex items-center justify-center shrink-0 shadow-sm"><i data-lucide="map-pinned" size="20"></i></div>
            <input type="text" id="searchMap" placeholder="ค้นหาชื่อโครงการ หรือสายทาง..." class="w-full bg-transparent border-none font-bold text-sm outline-none text-slate-700">
        </div>
        <div class="flex flex-wrap md:flex-nowrap gap-2 w-full lg:w-auto">
            <select id="filterType" class="p-3 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none text-slate-600 flex-1 lg:flex-none cursor-pointer focus:ring-2 focus:ring-orange-500">
                <option value="">ทุกประเภทงาน</option>
                <?php foreach($types as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filterDistrict" class="p-3 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none text-slate-600 flex-1 lg:flex-none cursor-pointer focus:ring-2 focus:ring-orange-500">
                <option value="">ทุกอำเภอ</option>
                <?php foreach($districts as $d): ?>
                    <option value="<?= $d ?>">อ.<?= $d ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filterStatus" class="p-3 bg-slate-50 border-none rounded-xl text-xs font-bold outline-none text-slate-600 flex-1 lg:flex-none cursor-pointer focus:ring-2 focus:ring-orange-500">
                <option value="">ทุกสถานะ</option>
                <option value="เสร็จสิ้น">เสร็จสิ้น</option>
                <option value="กำลังดำเนินการ">กำลังดำเนินการ</option>
                <option value="รอดำเนินการ">รอดำเนินการ</option>
                <option value="มีการเปลี่ยนแปลงหรือแก้ไข">มีการแก้ไข</option>
            </select>
            <button onclick="resetFilters()" class="p-3 bg-slate-100 text-slate-500 rounded-xl hover:bg-orange-100 hover:text-orange-600 transition-colors" title="รีเซ็ตตัวกรอง">
                <i data-lucide="refresh-cw" size="16"></i>
            </button>
        </div>
    </div>

    <!-- Map Area -->
    <div id="map-container-wrapper" class="w-full relative rounded-[2.5rem] md:rounded-[3rem] overflow-hidden border-4 border-white shadow-2xl bg-slate-200">
        <!-- ⭐️ ตัวแสดงแผนที่ ⭐️ -->
        <div id="gisMap"></div>
        
        <!-- กล่องแสดงสถิติ (ลอยอยู่บนแผนที่มุมซ้ายล่าง) -->
        <div class="absolute bottom-6 left-6 z-[1000] bg-white/95 backdrop-blur-md p-6 rounded-[2rem] shadow-2xl border border-white max-w-sm pointer-events-none hidden md:block">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2"><i data-lucide="pie-chart" size="14"></i> ข้อมูลพิกัดโครงข่าย</p>
            <div class="space-y-3 mb-5">
                <div class="flex justify-between items-center text-xs font-bold">
                    <span><span class="w-2.5 h-2.5 inline-block rounded-full bg-emerald-500 mr-2 shadow-sm"></span> เสร็จสิ้น</span>
                    <span id="stat-completed" class="text-slate-700"><?= $stats['เสร็จสิ้น'] ?></span>
                </div>
                <div class="flex justify-between items-center text-xs font-bold">
                    <span><span class="w-2.5 h-2.5 inline-block rounded-full bg-orange-500 mr-2 shadow-sm animate-pulse"></span> กำลังดำเนินการ</span>
                    <span id="stat-ongoing" class="text-slate-700"><?= $stats['กำลังดำเนินการ'] ?></span>
                </div>
                <div class="flex justify-between items-center text-xs font-bold">
                    <span><span class="w-2.5 h-2.5 inline-block rounded-full bg-slate-400 mr-2 shadow-sm"></span> รอดำเนินการ</span>
                    <span id="stat-pending" class="text-slate-700"><?= $stats['รอดำเนินการ'] ?></span>
                </div>
                <div class="flex justify-between items-center text-xs font-bold">
                    <span><span class="w-2.5 h-2.5 inline-block rounded-full bg-rose-500 mr-2 shadow-sm"></span> เปลี่ยนแปลง/แก้ไข</span>
                    <span id="stat-error" class="text-slate-700"><?= $stats['มีการเปลี่ยนแปลงหรือแก้ไข'] ?></span>
                </div>
            </div>
            <div class="pt-4 border-t border-slate-100 flex justify-between items-end">
                <div>
                    <p class="text-[8px] font-black text-slate-400 uppercase">พบข้อมูลรวม</p>
                    <p class="text-2xl font-black text-slate-900 mt-1" id="stat-total"><?= count($projects) ?></p>
                </div>
                <div class="text-right">
                    <p class="text-[8px] font-black text-slate-400 uppercase">งบประมาณรวม</p>
                    <p class="text-lg font-black text-emerald-600 mt-1" id="stat-budget"><?= number_format($stats['total_budget']) ?> ฿</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const rawProjects = <?= json_encode($projects) ?>;
    let map, markersLayer;
    let currentData = [...rawProjects];

    // สีของหมุด
    const getStatusColor = (status) => {
        switch(status) {
            case 'เสร็จสิ้น': return '#10b981'; // Emerald
            case 'กำลังดำเนินการ': return '#f97316'; // Orange
            case 'มีการเปลี่ยนแปลงหรือแก้ไข': return '#f43f5e'; // Rose
            default: return '#94a3b8'; // Slate
        }
    };

    function initMap() {
        // สร้างแผนที่ เล็งไปที่ศรีสะเกษ
        map = L.map('gisMap', { zoomControl: false }).setView([15.1186, 104.3220], 10);
        L.control.zoom({ position: 'topright' }).addTo(map);
        
        // ⭐️ เปลี่ยนมาใช้เซิร์ฟเวอร์ Google Maps (Hybrid) เพื่อความเสถียรและเร็ว ⭐️
        L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: '© Google Maps'
        }).addTo(map);

        markersLayer = L.featureGroup().addTo(map);
        
        // วาดข้อมูล
        renderMapData(currentData);

        // ⭐️ แก้ปัญหาจอเทา: บังคับให้แผนที่รีเฟรชขนาดตัวเอง ⭐️
        setTimeout(() => { map.invalidateSize(); }, 500);
        setTimeout(() => { map.invalidateSize(); }, 1500);
    }

    function renderMapData(data) {
        markersLayer.clearLayers();
        let totalBudget = 0; 
        let counts = { 'เสร็จสิ้น': 0, 'กำลังดำเนินการ': 0, 'รอดำเนินการ': 0, 'มีการเปลี่ยนแปลงหรือแก้ไข': 0 };
        const bounds = [];

        // SVG Icons ฝังตรงๆ เพื่อแก้ปัญหาไอคอนไม่โหลดใน Popup
        const iconArrow = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>`;
        const iconHat = `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v2z"></path><path d="M10 10V5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5"></path><path d="M4 15v-3a6 6 0 0 1 6-6h0"></path><path d="M14 6h0a6 6 0 0 1 6 6v3"></path></svg>`;

        data.forEach(p => {
            const lat = parseFloat(p.start_lat); 
            const lng = parseFloat(p.start_long);
            if (isNaN(lat) || isNaN(lng)) return;
            
            // อัปเดตสถิติ
            totalBudget += parseFloat(p.budget_amount) || 0;
            if(counts[p.status] !== undefined) counts[p.status]++;
            
            const color = getStatusColor(p.status);
            
            // วาดหมุดจุดเริ่มต้น
            const marker = L.circleMarker([lat, lng], { 
                radius: 8, fillColor: color, color: '#ffffff', weight: 2, opacity: 1, fillOpacity: 1 
            }).addTo(markersLayer);

            // วาดเส้นและจุดสิ้นสุด (ถ้ามี)
            if (p.end_lat && p.end_long) {
                const endLat = parseFloat(p.end_lat); const endLng = parseFloat(p.end_long);
                if (!isNaN(endLat) && !isNaN(endLng)) {
                    L.polyline([[lat, lng], [endLat, endLng]], { 
                        color: color, weight: 4, opacity: 0.8, dashArray: '6,6' 
                    }).addTo(markersLayer);
                    bounds.push([endLat, endLng]);
                }
            }
            bounds.push([lat, lng]);

            const thumbUrl = p.thumbnail ? `uploads/projects/${p.thumbnail}` : 'https://placehold.co/400x200/1e293b/white?text=No+Image';
            const sup_name = p.supervisor_name && p.supervisor_name.trim() !== '' ? p.supervisor_name : '<span class="text-slate-500 italic font-normal">ไม่ได้ระบุชื่อ</span>';
            const route_html = p.route_name ? `<p class="text-[9px] font-bold text-orange-400 mt-1 line-clamp-1">สายทาง: ${p.route_name}</p>` : '';
            
            // HTML สำหรับ Popup (แก้ไอคอนหาย + เพิ่มผู้ควบคุมงาน)
            const popupContent = `
                <div class="flex flex-col">
                    <img src="${thumbUrl}" class="w-full h-32 object-cover">
                    <div class="p-5 relative">
                        <a href="view_project.php?id=${p.id}" class="absolute -top-5 right-4 w-10 h-10 bg-orange-600 hover:bg-orange-500 text-white flex items-center justify-center rounded-full shadow-lg transition-colors border-2 border-slate-900 z-10">
                            ${iconArrow}
                        </a>
                        
                        <div class="flex items-center gap-2 mb-2 pr-8">
                            <span class="text-[8px] font-black uppercase tracking-tighter bg-white/10 px-2 py-0.5 rounded text-orange-400">${p.infrastructure_type}</span>
                            <span class="text-[8px] font-black text-slate-400">อ.${p.district_name}</span>
                        </div>
                        
                        <h5 class="text-xs font-black text-white leading-tight pr-2">${p.project_name}</h5>
                        ${route_html}
                        
                        <div class="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-white/10">
                            <div>
                                <p class="text-[8px] text-slate-400 uppercase">งบประมาณ</p>
                                <p class="text-[11px] font-black text-emerald-400">${new Intl.NumberFormat().format(p.budget_amount)} ฿</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[8px] text-slate-400 uppercase">สถานะ</p>
                                <p class="text-[9px] font-bold mt-1" style="color: ${color}">${p.status}</p>
                            </div>
                        </div>
                        
                        <div class="mt-2 pt-2 border-t border-white/10">
                            <p class="text-[8px] text-slate-400 uppercase mb-1">ช่างผู้ควบคุมงาน</p>
                            <div class="flex items-center gap-1.5">
                                <div class="w-5 h-5 rounded-full bg-orange-500/20 flex items-center justify-center text-orange-400 shrink-0">
                                    ${iconHat}
                                </div>
                                <p class="text-[10px] font-bold text-white truncate">${sup_name}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            marker.bindPopup(popupContent);
        });

        // เลื่อนกล้องให้เห็นหมุดทั้งหมด
        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
        }
        
        // อัปเดตตัวเลขสถิติบนหน้าจอ
        document.getElementById('stat-completed').innerText = counts['เสร็จสิ้น'];
        document.getElementById('stat-ongoing').innerText = counts['กำลังดำเนินการ'];
        document.getElementById('stat-pending').innerText = counts['รอดำเนินการ'];
        document.getElementById('stat-error').innerText = counts['มีการเปลี่ยนแปลงหรือแก้ไข'];
        document.getElementById('stat-total').innerText = data.length;
        document.getElementById('stat-budget').innerText = new Intl.NumberFormat().format(totalBudget) + ' ฿';
    }

    // ระบบตัวกรองข้อมูล
    function applyFilters() {
        const searchText = document.getElementById('searchMap').value.toLowerCase();
        const type = document.getElementById('filterType').value;
        const district = document.getElementById('filterDistrict').value;
        const status = document.getElementById('filterStatus').value;

        currentData = rawProjects.filter(p => {
            const matchSearch = p.project_name.toLowerCase().includes(searchText) || (p.route_name && p.route_name.toLowerCase().includes(searchText));
            const matchType = type === '' || p.infrastructure_type === type;
            const matchDistrict = district === '' || p.district_name === district;
            const matchStatus = status === '' || p.status === status;
            return matchSearch && matchType && matchDistrict && matchStatus;
        });
        renderMapData(currentData);
    }

    function resetFilters() {
        document.getElementById('searchMap').value = ''; 
        document.getElementById('filterType').value = '';
        document.getElementById('filterDistrict').value = ''; 
        document.getElementById('filterStatus').value = '';
        applyFilters();
    }

    // ผูก Event Listener
    document.getElementById('searchMap').addEventListener('input', applyFilters);
    document.getElementById('filterType').addEventListener('change', applyFilters);
    document.getElementById('filterDistrict').addEventListener('change', applyFilters);
    document.getElementById('filterStatus').addEventListener('change', applyFilters);
    
    // เริ่มทำงานเมื่อเอกสารโหลดเสร็จ
    document.addEventListener('DOMContentLoaded', () => { 
        if(typeof lucide !== 'undefined') lucide.createIcons(); 
        initMap(); 
    });
</script>

<?php include 'includes/footer.php'; ?>