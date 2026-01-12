<?php
// map_full.php
// ที่อยู่ไฟล์: /map_full.php
require_once 'auth_check.php';
require_once 'config/db.php';

// 1. ดึงข้อมูลโครงการทั้งหมดที่มีพิกัด
$sql = "SELECT id, project_name, fiscal_year, start_lat, start_long, end_lat, end_long, budget_amount FROM projects WHERE start_lat IS NOT NULL";
$res = $conn->query($sql);
$map_data = [];
while($row = $res->fetch_assoc()) $map_data[] = $row;

// 2. ดึงปีงบประมาณที่มีทั้งหมด
$years_res = $conn->query("SELECT DISTINCT fiscal_year FROM projects ORDER BY fiscal_year DESC");

// 3. ดึงรายชื่ออำเภอทั้งหมด
$districts_res = $conn->query("SELECT DISTINCT district FROM project_points WHERE district IS NOT NULL AND district != '' ORDER BY district ASC");

include 'includes/header.php';
?>

<style>
    /* ปรับแต่งส่วน Filter ด้านบน */
    .filter-section {
        background: white;
        border-radius: 2rem;
        padding: 1.5rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    }

    .filter-input {
        width: 100%;
        background: #f8fafc;
        border: 2px solid #f1f5f9;
        border-radius: 1rem;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        outline: none;
        transition: all 0.2s;
    }
    .filter-input:focus {
        border-color: #f97316;
        background: white;
    }

    /* ปุ่มควบคุมบนแผนที่ (ย้ายไปมุมล่างซ้ายตามเดิม) */
    .map-overlay-tools {
        position: absolute;
        bottom: 30px;
        left: 20px;
        z-index: 1000;
    }
    
    .map-tool-btn {
        background: #f97316;
        color: white;
        height: 60px;
        width: 60px;
        border-radius: 30px;
        font-weight: 800;
        font-size: 15px;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        padding: 0 18px;
        box-shadow: 0 10px 25px rgba(249, 115, 22, 0.4);
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        overflow: hidden;
        white-space: nowrap;
        border: 3px solid white; 
        cursor: pointer;
    }

    .map-tool-btn .main-icon {
        min-width: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white; 
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    }

    .map-tool-btn:hover {
        width: 260px;
        background: #ea580c; 
    }

    .btn-text {
        opacity: 0;
        margin-left: 14px;
        transform: translateX(-15px);
        transition: all 0.4s ease;
        pointer-events: none;
        color: white;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .map-tool-btn:hover .btn-text {
        opacity: 1;
        transform: translateX(0);
    }

    /* แผนที่แบบยืดหยุ่น */
    #map-container {
        height: 65vh;
        min-height: 500px;
    }
</style>

<div class="space-y-6 animate-in fade-in pb-20">
    <!-- ส่วนหัว: สถิติและปุ่มส่งออก -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight">ระบบ GIS วิศวกรรม</h2>
            <p class="text-slate-500 font-medium">แสดงผลพิกัดและเส้นทางโครงสร้างพื้นฐานทั่วจังหวัด</p>
        </div>
        <div id="stats-badge" class="bg-orange-600 text-white px-6 py-3 rounded-2xl text-sm font-black uppercase shadow-lg flex items-center gap-2">
            <i data-lucide="map-pin" size="18"></i>
            พบทั้งหมด <span id="count-text"><?= count($map_data) ?></span> โครงการ
        </div>
    </div>

    <!-- ส่วนกรองข้อมูล (แยกออกมาไว้ด้านบนแผนที่) -->
    <div class="filter-section">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="relative md:col-span-2">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" size="18"></i>
                <input type="text" id="search-input" placeholder="ค้นหาชื่อโครงการที่ต้องการ..." class="filter-input pl-12">
            </div>
            <div>
                <select id="year-filter" class="filter-input">
                    <option value="">ทุกปีงบประมาณ</option>
                    <?php while($y = $years_res->fetch_assoc()): ?>
                        <option value="<?= $y['fiscal_year'] ?>"><?= $y['fiscal_year'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <select id="district-filter" class="filter-input flex-1">
                    <option value="">ทุกอำเภอ</option>
                    <?php while($d = $districts_res->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($d['district']) ?>"><?= htmlspecialchars($d['district']) ?></option>
                    <?php endwhile; ?>
                </select>
                <button id="clear-filters" class="p-3 bg-slate-100 text-slate-400 hover:bg-red-50 hover:text-red-500 rounded-xl transition-all" title="ล้างตัวกรอง">
                    <i data-lucide="refresh-cw" size="20"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- พื้นที่แผนที่ -->
    <div id="map-container" class="rounded-[3rem] border-8 border-white shadow-2xl relative overflow-hidden bg-slate-900">
        <!-- ปุ่มควบคุมพิกัด (ล่างซ้าย) -->
        <div class="map-overlay-tools">
            <button onclick="recenterMap()" class="map-tool-btn group">
                <div class="main-icon">
                    <i data-lucide="target" size="26"></i>
                </div>
                <div class="btn-text">
                    <span>กลับไปยังพิกัดโครงการ</span>
                    <i data-lucide="arrow-right-circle" size="18"></i>
                </div>
            </button>
        </div>
        
        <div id="map" class="w-full h-full"></div>
    </div>
</div>

<script>
    const originalProjectData = <?= json_encode($map_data) ?>;
    let filteredData = [...originalProjectData];
    let routeLayers = []; 
    let markerLayers = []; 
    let currentBounds = [];

    // เริ่มต้นแผนที่
    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Esri'
    });
    const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'OSM'
    });

    const map = L.map('map', {
        layers: [satelliteLayer],
        zoomControl: false 
    }).setView([15.1186, 104.3220], 10);

    L.control.zoom({ position: 'bottomright' }).addTo(map);
    L.control.layers({"แผนที่ดาวเทียม": satelliteLayer, "แผนที่ถนน": streetLayer}, null, { position: 'topright' }).addTo(map);

    // ฟังก์ชันวาดแผนที่
    async function renderMap(data) {
        routeLayers.forEach(l => map.removeLayer(l));
        markerLayers.forEach(l => map.removeLayer(l));
        routeLayers = [];
        markerLayers = [];
        currentBounds = [];

        document.getElementById('count-text').innerText = data.length;

        for (const p of data) {
            const start = [parseFloat(p.start_lat), parseFloat(p.start_long)];
            const end = [parseFloat(p.end_lat), parseFloat(p.end_long)];
            currentBounds.push(start, end);

            try {
                const url = `https://router.project-osrm.org/route/v1/driving/${start[1]},${start[0]};${end[1]},${end[0]}?overview=full&geometries=geojson`;
                const response = await fetch(url);
                const routeData = await response.json();

                if (routeData.code === 'Ok') {
                    const line = L.geoJSON(routeData.routes[0].geometry, {
                        style: { color: '#ffffff', weight: 4, opacity: 0.7, dashArray: '5, 10' }
                    }).addTo(map);
                    
                    line.bindPopup(`
                        <div class="p-2 font-sarabun">
                            <p class="font-black text-sm mb-1 text-slate-800">${p.project_name}</p>
                            <p class="text-[10px] text-orange-600 font-bold mb-2">ปี ${p.fiscal_year} | งบ: ${new Intl.NumberFormat().format(p.budget_amount)} ฿</p>
                            <a href="view_project.php?id=${p.id}" class="block text-center bg-slate-900 text-white py-1.5 rounded-xl text-[10px] font-bold mt-2 hover:bg-orange-600 transition-all">ดูรายละเอียด</a>
                        </div>
                    `);
                    routeLayers.push(line);
                }
            } catch (e) {
                const fallback = L.polyline([start, end], { color: '#ffffff', weight: 2, opacity: 0.5 }).addTo(map);
                routeLayers.push(fallback);
            }

            const m1 = L.circleMarker(start, { radius: 6, fillColor: "#f97316", color: "#fff", weight: 2, fillOpacity: 1 }).addTo(map);
            const m2 = L.circleMarker(end, { radius: 6, fillColor: "#0ea5e9", color: "#fff", weight: 2, fillOpacity: 1 }).addTo(map);
            markerLayers.push(m1, m2);
        }

        if (currentBounds.length > 0) {
            map.fitBounds(currentBounds, { padding: [80, 80], maxZoom: 15 });
        }
    }

    // ระบบกรองข้อมูล
    function filterData() {
        const searchTerm = document.getElementById('search-input').value.toLowerCase();
        const yearFilter = document.getElementById('year-filter').value;
        const districtFilter = document.getElementById('district-filter').value;

        filteredData = originalProjectData.filter(p => {
            const matchesSearch = p.project_name.toLowerCase().includes(searchTerm);
            const matchesYear = yearFilter === "" || p.fiscal_year.toString() === yearFilter;
            return matchesSearch && matchesYear;
        });

        renderMap(filteredData);
    }

    document.getElementById('search-input').addEventListener('input', filterData);
    document.getElementById('year-filter').addEventListener('change', filterData);
    
    document.getElementById('clear-filters').addEventListener('click', () => {
        document.getElementById('search-input').value = "";
        document.getElementById('year-filter').value = "";
        document.getElementById('district-filter').value = "";
        filterData();
    });

    function recenterMap() {
        if (currentBounds.length > 0) {
            map.fitBounds(currentBounds, { padding: [100, 100], maxZoom: 15, animate: true });
        }
    }

    renderMap(originalProjectData);
    lucide.createIcons();
</script>

<?php include 'includes/footer.php'; ?>