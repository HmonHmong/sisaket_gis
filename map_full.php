<?php
// map_full.php - ระบบวิเคราะห์พิกัดและโครงข่ายถนนภาพรวม (Advanced Spatial Analysis)
require_once 'auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// 1. ดึงข้อมูลโครงการทั้งหมดที่มีพิกัด พร้อมดึงรูปภาพแรกมาเป็น Thumbnail
$sql = "SELECT p.*, 
        (SELECT file_path FROM project_attachments WHERE project_id = p.id LIMIT 1) as thumbnail
        FROM projects p 
        WHERE p.start_lat IS NOT NULL AND p.start_long IS NOT NULL";
$res = $conn->query($sql);
$map_data = [];
while($row = $res->fetch_assoc()) {
    $map_data[] = $row;
}

// ข้อมูลสำหรับตัวกรอง
$years_res = $conn->query("SELECT DISTINCT fiscal_year FROM projects ORDER BY fiscal_year DESC");
$sisaket_districts = ["เมืองศรีสะเกษ", "ยางชุมน้อย", "กันทรารมย์", "กันทรลักษณ์", "ขุขันธ์", "ไพรบึง", "ปรางค์กู่", "ขุนหาญ", "ราษีไศล", "อุทุมพรพิสัย", "บึงบูรพ์", "ห้วยทับทัน", "โนนคูณ", "ศรีรัตนะ", "น้ำเกลี้ยง", "วังหิน", "ภูสิงห์", "เมืองจันทร์", "เบญจลักษณ์", "พยุห์", "โพธิ์ศรีสุวรรณ", "ศิลาลาด"];
$road_types = ["ถนนลูกรัง", "ถนนหินคลุก", "ถนนคอนกรีต", "ถนนลาดยาง (Tack Coat)", "ถนนลาดยาง (Recycling)"];

include 'includes/header.php';
?>

<div class="h-[calc(100vh-120px)] flex flex-col space-y-4 animate-in fade-in duration-500 pb-4">
    <!-- Search & Multi-Filter Bar -->
    <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-col lg:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-4 flex-1 w-full">
            <div class="w-12 h-12 bg-slate-900 rounded-2xl flex items-center justify-center text-white shrink-0 shadow-lg">
                <i data-lucide="map" size="24"></i>
            </div>
            <div class="flex-1 max-w-md relative group">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-orange-600 transition-colors" size="18"></i>
                <input type="text" id="mapSearch" oninput="updateMapFilters()" placeholder="ค้นชื่อโครงการหรือสายทาง..." 
                       class="w-full pl-12 pr-4 py-3 bg-slate-50 border-2 border-transparent rounded-2xl outline-none focus:border-orange-500 focus:bg-white transition-all font-bold text-sm shadow-inner">
            </div>
        </div>
        
        <div class="flex flex-wrap justify-center gap-2 w-full lg:w-auto">
            <select id="typeFilter" onchange="updateMapFilters()" class="p-3 bg-slate-50 border-none rounded-xl text-[10px] font-black uppercase tracking-widest outline-none focus:ring-2 focus:ring-orange-500 transition-all shadow-sm text-slate-600">
                <option value="">ทุกประเภทถนน</option>
                <?php foreach($road_types as $rt): ?>
                    <option value="<?= $rt ?>"><?= $rt ?></option>
                <?php endforeach; ?>
            </select>
            
            <select id="districtFilter" onchange="updateMapFilters()" class="p-3 bg-slate-50 border-none rounded-xl text-[10px] font-black uppercase tracking-widest outline-none focus:ring-2 focus:ring-orange-500 transition-all shadow-sm text-slate-600">
                <option value="">ทุกอำเภอ</option>
                <?php foreach($sisaket_districts as $d): ?>
                    <option value="<?= $d ?>"><?= $d ?></option>
                <?php endforeach; ?>
            </select>

            <select id="statusFilter" onchange="updateMapFilters()" class="p-3 bg-slate-50 border-none rounded-xl text-[10px] font-black uppercase tracking-widest outline-none focus:ring-2 focus:ring-orange-500 transition-all shadow-sm text-slate-600">
                <option value="">ทุกสถานะ</option>
                <option value="รอดำเนินการ">รอดำเนินการ</option>
                <option value="กำลังดำเนินการ">กำลังดำเนินการ</option>
                <option value="เสร็จสิ้น">เสร็จสิ้น</option>
                <option value="มีการเปลี่ยนแปลงหรือแก้ไข">แก้ไข/เปลี่ยนแปลง</option>
            </select>

            <button onclick="resetView()" class="p-3 bg-orange-600 text-white rounded-xl hover:bg-slate-900 transition-all shadow-md group" title="Reset View">
                <i data-lucide="maximize" size="18" class="group-hover:scale-110 transition-transform"></i>
            </button>
        </div>
    </div>

    <!-- Map Engine Container -->
    <div class="flex-1 relative rounded-[4rem] overflow-hidden border-8 border-white shadow-2xl bg-slate-200 group">
        <div id="mainMap" class="w-full h-full z-0 cursor-crosshair"></div>
        
        <!-- Legend Overlay -->
        <div class="absolute bottom-10 left-10 z-[1000] bg-white/90 backdrop-blur-md p-8 rounded-[3.5rem] border border-white shadow-2xl hidden md:block min-w-[240px] animate-in slide-in-from-left duration-500">
            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                <span class="w-4 h-px bg-slate-200"></span> ข้อมูลพิกัดโครงข่าย
            </h4>
            <div class="space-y-4">
                <div class="flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 bg-emerald-500 rounded-full shadow-[0_0_10px_rgba(16,185,129,0.5)]"></span>
                        <span class="text-[11px] font-black text-slate-600">เสร็จสิ้นโครงการ</span>
                    </div>
                    <span id="count_finish" class="text-xs font-black text-slate-400">0</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 bg-orange-500 rounded-full shadow-[0_0_10px_rgba(249,115,22,0.5)]"></span>
                        <span class="text-[11px] font-black text-slate-600">กำลังดำเนินการ</span>
                    </div>
                    <span id="count_process" class="text-xs font-black text-slate-400">0</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 bg-rose-600 rounded-full shadow-[0_0_10px_rgba(225,29,72,0.5)]"></span>
                        <span class="text-[11px] font-black text-slate-600">แก้ไข/เปลี่ยนแปลง</span>
                    </div>
                    <span id="count_change" class="text-xs font-black text-slate-400">0</span>
                </div>
                
                <div class="pt-6 mt-6 border-t border-slate-100 flex justify-between items-center">
                    <div>
                        <p class="text-[8px] font-black text-slate-400 uppercase leading-none">พบข้อมูลรวม</p>
                        <p id="totalFound" class="text-2xl font-black text-slate-900 mt-1">0</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[8px] font-black text-slate-400 uppercase leading-none">งบประมาณรวม</p>
                        <p id="totalBudget" class="text-lg font-black text-orange-600 mt-1">0 ฿</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Layer Swapper -->
        <div class="absolute top-10 right-10 z-[1000] flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            <button onclick="setMapLayer('sat')" id="layerSat" class="map-ctrl-btn active shadow-2xl"><i data-lucide="satellite"></i></button>
            <button onclick="setMapLayer('street')" id="layerStreet" class="map-ctrl-btn shadow-2xl"><i data-lucide="map-pinned"></i></button>
        </div>
    </div>
</div>

<style>
    .map-ctrl-btn {
        background: white; color: #94a3b8; width: 56px; height: 56px; border-radius: 1.5rem;
        display: flex; align-items: center; justify-content: center; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 3px solid transparent;
    }
    .map-ctrl-btn.active { background: #ea580c; color: white; border-color: white; transform: scale(1.1); }
    
    /* สไตล์ Tooltip ขั้นสูง */
    .gis-popup .leaflet-popup-content-wrapper {
        background: rgba(15, 23, 42, 0.98); color: white; border-radius: 2rem; padding: 0; overflow: hidden;
        border: 1px solid rgba(255,255,255,0.1); backdrop-blur: 10px;
    }
    .gis-popup .leaflet-popup-content { margin: 0; width: 280px !important; }
    .gis-popup .leaflet-popup-tip { background: rgba(15, 23, 42, 0.98); }
</style>

<script>
    const allProjects = <?= json_encode($map_data) ?>;
    let map, markers = [], polylineGroup, satLayer, streetLayer;

    const statusColors = {
        'รอดำเนินการ': '#64748b',
        'กำลังดำเนินการ': '#f97316',
        'เสร็จสิ้น': '#10b981',
        'มีการเปลี่ยนแปลงหรือแก้ไข': '#e11d48'
    };

    function initMap() {
        const satBase = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}');
        const labels = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_only_labels/{z}/{x}/{y}{r}.png');
        satLayer = L.layerGroup([satBase, labels]);
        streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');

        map = L.map('mainMap', { zoomControl: false, layers: [satLayer] }).setView([15.1186, 104.3220], 10);
        polylineGroup = L.featureGroup().addTo(map);
        
        renderMarkers(allProjects);
    }

    function renderMarkers(data) {
        // ล้างข้อมูลเดิม
        markers.forEach(m => map.removeLayer(m));
        polylineGroup.clearLayers();
        markers = [];
        
        let stats = { finish: 0, process: 0, change: 0, totalBudget: 0 };
        const bounds = [];

        data.forEach(p => {
            const start = [parseFloat(p.start_lat), parseFloat(p.start_long)];
            const color = statusColors[p.status] || '#64748b';
            
            // 1. สร้างหมุดวงกลม
            const m = L.circleMarker(start, {
                radius: 9, fillColor: color, color: '#fff', weight: 3, fillOpacity: 1
            }).addTo(map);

            // 2. วาดเส้นโครงข่ายถ้ามีพิกัดสิ้นสุด
            if (p.end_lat && p.end_long) {
                const end = [parseFloat(p.end_lat), parseFloat(p.end_long)];
                const line = L.polyline([start, end], {
                    color: color, weight: 5, opacity: 0.6, dashArray: '8, 8'
                }).addTo(polylineGroup);
                bounds.push(start, end);
            } else {
                bounds.push(start);
            }

            // 3. สร้าง Popup เนื้อหาเข้มข้น
            const thumbUrl = p.thumbnail ? `uploads/projects/${p.thumbnail}` : 'https://placehold.co/400x250/1e293b/white?text=GIS+Survey';
            const popupContent = `
                <div class="flex flex-col">
                    <img src="${thumbUrl}" class="w-full h-32 object-cover">
                    <div class="p-5 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-[8px] font-black uppercase tracking-tighter bg-white/10 px-2 py-0.5 rounded text-orange-400">${p.infrastructure_type}</span>
                            <span class="text-[9px] font-bold text-slate-400">ปี ${p.fiscal_year}</span>
                        </div>
                        <h5 class="text-xs font-black text-white leading-tight">${p.project_name}</h5>
                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-white/5">
                            <div>
                                <p class="text-[8px] text-slate-500 uppercase font-black">งบประมาณ</p>
                                <p class="text-[10px] font-black text-orange-500">${new Intl.NumberFormat().format(p.budget_amount)} ฿</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[8px] text-slate-500 uppercase font-black">พื้นที่รวม</p>
                                <p class="text-[10px] font-black text-emerald-500">${p.area} ตร.ม.</p>
                            </div>
                        </div>
                        <a href="view_project.php?id=${p.id}" class="block w-full text-center py-2 bg-orange-600 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-white hover:text-slate-900 transition-all mt-2">ดูพิกัดวิศวกรรม</a>
                    </div>
                </div>
            `;

            m.bindPopup(popupContent, { className: 'gis-popup', maxWidth: 280 });
            markers.push(m);

            // อัปเดตสถิติ
            if(p.status === 'เสร็จสิ้น') stats.finish++;
            else if(p.status === 'กำลังดำเนินการ') stats.process++;
            else if(p.status === 'มีการเปลี่ยนแปลงหรือแก้ไข') stats.change++;
            stats.totalBudget += parseFloat(p.budget_amount);
        });

        // อัปเดต UI Legend
        document.getElementById('count_finish').innerText = stats.finish;
        document.getElementById('count_process').innerText = stats.process;
        document.getElementById('count_change').innerText = stats.change;
        document.getElementById('totalFound').innerText = data.length;
        document.getElementById('totalBudget').innerText = new Intl.NumberFormat().format(stats.totalBudget);

        if (bounds.length > 0) map.fitBounds(bounds, { padding: [100, 100], maxZoom: 15 });
    }

    function updateMapFilters() {
        const query = document.getElementById('mapSearch').value.toLowerCase();
        const type = document.getElementById('typeFilter').value;
        const district = document.getElementById('districtFilter').value;
        const status = document.getElementById('statusFilter').value;

        const filtered = allProjects.filter(p => {
            const matchSearch = p.project_name.toLowerCase().includes(query) || (p.route_name && p.route_name.toLowerCase().includes(query));
            const matchType = type === "" || p.infrastructure_type === type;
            const matchDist = district === "" || p.district_name === district;
            const matchStatus = status === "" || p.status === status;
            return matchSearch && matchType && matchDist && matchStatus;
        });
        
        renderMarkers(filtered);
    }

    function setMapLayer(type) {
        if(type === 'sat') { map.addLayer(satLayer); map.removeLayer(streetLayer); }
        else { map.addLayer(streetLayer); map.removeLayer(satLayer); }
        document.getElementById('layerSat').classList.toggle('active', type==='sat');
        document.getElementById('layerStreet').classList.toggle('active', type==='street');
    }

    function resetView() {
        map.setView([15.1186, 104.3220], 10);
    }

    window.onload = () => {
        initMap();
        lucide.createIcons();
    };
</script>

<?php include 'includes/footer.php'; ?>