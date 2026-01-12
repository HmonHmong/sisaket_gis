<?php
// view_project.php
// ที่อยู่ไฟล์: /view_project.php
require_once 'auth_check.php';
require_once 'config/db.php';

if (!isset($_GET['id'])) {
    header("Location: projects.php");
    exit;
}

$id = intval($_GET['id']);

// 1. ดึงข้อมูลโครงการ
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    echo "ไม่พบข้อมูลโครงการ";
    exit;
}

// 2. ดึงจุดพิกัด/หมู่บ้าน
$points_res = $conn->query("SELECT * FROM project_points WHERE project_id = $id ORDER BY order_index ASC");
$points = [];
while($row = $points_res->fetch_assoc()) { $points[] = $row; }

include 'includes/header.php';
?>

<div class="max-w-5xl mx-auto pb-20 space-y-6 animate-in fade-in">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <a href="projects.php" class="text-slate-500 hover:text-orange-600 flex items-center gap-2 font-bold mb-2 transition-all">
                <i data-lucide="arrow-left" size="18"></i> ย้อนกลับ
            </a>
            <h2 class="text-3xl font-black text-slate-800"><?= htmlspecialchars($project['project_name']) ?></h2>
            <p class="text-slate-500 font-medium">งบประมาณ <?= number_format($project['budget_amount'], 2) ?> ฿</p>
        </div>
        <div class="flex gap-2">
            <a href="edit_project.php?id=<?= $id ?>" class="bg-slate-900 text-white px-6 py-3 rounded-2xl font-bold flex items-center gap-2 hover:bg-orange-600 transition-all shadow-lg">
                <i data-lucide="edit-3" size="18"></i> แก้ไขข้อมูล
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-200">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i data-lucide="info" size="14"></i> ข้อมูลทางวิศวกรรม
                </h3>
                <div class="space-y-4 text-sm font-bold">
                    <div class="flex justify-between border-b pb-2"><span>ระยะทาง</span><span class="text-slate-800"><?= number_format($project['distance']) ?> ม.</span></div>
                    <div class="flex justify-between border-b pb-2"><span>ความกว้าง</span><span class="text-slate-800"><?= number_format($project['width'], 2) ?> ม.</span></div>
                    <div class="bg-orange-50 p-4 rounded-2xl">
                        <p class="text-[10px] font-black text-orange-600 uppercase mb-1">พื้นที่รวม</p>
                        <p class="text-2xl font-black text-orange-700"><?= number_format($project['area'], 2) ?> ตร.ม.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-slate-900 rounded-[3rem] p-2 shadow-2xl relative border-8 border-white h-[500px]">
                <div id="project-map" class="w-full h-full rounded-[2.5rem]"></div>
                <!-- ตัวบ่งชี้การโหลดเส้นทาง -->
                <div id="route-loader" class="absolute bottom-8 right-8 bg-white/90 backdrop-blur px-4 py-2 rounded-xl text-[10px] font-bold text-slate-800 z-[1001] flex items-center gap-2 shadow-lg">
                    <i data-lucide="loader-2" class="animate-spin text-orange-600" size="14"></i> กำลังคำนวณเส้นทางตามถนนจริง...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const startLat = <?= $project['start_lat'] ?>;
    const startLng = <?= $project['start_long'] ?>;
    const endLat = <?= $project['end_lat'] ?>;
    const endLng = <?= $project['end_long'] ?>;

    const map = L.map('project-map').setView([startLat, startLng], 14);
    
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri'
    }).addTo(map);

    // ปักหมุดเริ่มต้นและสิ้นสุด
    const startMarker = L.circleMarker([startLat, startLng], { radius: 8, fillColor: "#f97316", color: "#fff", weight: 3, fillOpacity: 1 }).addTo(map).bindPopup('จุดเริ่มต้น');
    const endMarker = L.circleMarker([endLat, endLng], { radius: 8, fillColor: "#0ea5e9", color: "#fff", weight: 3, fillOpacity: 1 }).addTo(map).bindPopup('จุดสิ้นสุด');

    // ฟังก์ชันดึงเส้นทางจาก OSRM (API วิ่งตามถนนจริง)
    async function getRoadRoute() {
        try {
            // OSRM ใช้รูปแบบ [lon,lat]
            const url = `https://router.project-osrm.org/route/v1/driving/${startLng},${startLat};${endLng},${endLat}?overview=full&geometries=geojson`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.code === 'Ok') {
                const route = data.routes[0].geometry;
                const roadLine = L.geoJSON(route, {
                    style: {
                        color: '#fbbf24',
                        weight: 8,
                        opacity: 0.85,
                        lineCap: 'round'
                    }
                }).addTo(map);
                
                // ปรับซูมให้เห็นทั้งเส้นทาง
                map.fitBounds(roadLine.getBounds(), { padding: [50, 50] });
                document.getElementById('route-loader').style.display = 'none';
            } else {
                throw new Error('OSRM Error');
            }
        } catch (error) {
            console.error('Routing failed, falling back to straight line:', error);
            // ถ้า API พัง ให้วาดเส้นตรงแทน
            const fallbackLine = L.polyline([[startLat, startLng], [endLat, endLng]], {
                color: '#ff4444', weight: 5, dashArray: '10, 10'
            }).addTo(map);
            document.getElementById('route-loader').innerHTML = '<i data-lucide="alert-triangle"></i> ไม่สามารถดึงเส้นทางถนนจริงได้ (วาดเส้นตรงแทน)';
            lucide.createIcons();
        }
    }

    getRoadRoute();
</script>

<?php include 'includes/footer.php'; ?>