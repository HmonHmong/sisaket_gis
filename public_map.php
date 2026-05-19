<?php
// public_map.php - ระบบเปิดเผยข้อมูล (Open Data) สำหรับประชาชนทั่วไป
require_once 'config/db.php';

// ดึงเฉพาะโครงการที่มีพิกัดและไม่ถูกลบลงถังขยะ
$sql = "SELECT p.*, 
        (SELECT file_path FROM project_attachments WHERE project_id = p.id LIMIT 1) as thumbnail
        FROM projects p 
        WHERE p.start_lat IS NOT NULL AND p.start_long IS NOT NULL AND p.deleted_at IS NULL";
$res = $conn->query($sql);
$map_data = [];
$total_budget = 0;
while($row = $res->fetch_assoc()) {
    $map_data[] = $row;
    $total_budget += $row['budget_amount'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบแผนที่สาธารณะ | อบจ.ศรีสะเกษ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#ea580c">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
        .gis-popup .leaflet-popup-content-wrapper {
            background: rgba(15, 23, 42, 0.98); color: white; border-radius: 2rem; padding: 0; overflow: hidden;
        }
        .gis-popup .leaflet-popup-content { margin: 0; width: 260px !important; }
        .gis-popup .leaflet-popup-tip { background: rgba(15, 23, 42, 0.98); }
    </style>
</head>
<body class="bg-slate-100 flex flex-col h-screen overflow-hidden">
    <!-- Header -->
    <header class="bg-slate-900 text-white shadow-xl p-4 shrink-0 flex justify-between items-center z-50">
        <div class="flex items-center gap-3">
            <div class="bg-orange-600 p-2 rounded-xl text-white shadow-lg"><i data-lucide="map" size="20"></i></div>
            <div>
                <h1 class="text-sm font-black uppercase">ระบบแผนที่สาธารณะ (Open Data)</h1>
                <p class="text-[9px] text-slate-400 uppercase tracking-widest font-bold mt-0.5">องค์การบริหารส่วนจังหวัดศรีสะเกษ</p>
            </div>
        </div>
        <a href="login.php" class="bg-white/10 hover:bg-orange-600 text-white px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
            <i data-lucide="lock" size="14"></i> จนท. เข้าสู่ระบบ
        </a>
    </header>

    <!-- Map Area -->
    <div class="flex-1 relative z-0">
        <div id="publicMap" class="w-full h-full"></div>
        
        <!-- Floating Stats -->
        <div class="absolute bottom-6 left-6 z-[1000] bg-white/90 backdrop-blur-md p-6 rounded-[2.5rem] shadow-2xl border border-white">
            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">ข้อมูลภาพรวมโครงการ</h4>
            <div class="flex items-end gap-6">
                <div>
                    <p class="text-[9px] font-bold text-slate-500 uppercase">จำนวนโครงการ</p>
                    <p class="text-2xl font-black text-slate-900"><?= count($map_data) ?></p>
                </div>
                <div class="w-px h-8 bg-slate-200"></div>
                <div>
                    <p class="text-[9px] font-bold text-slate-500 uppercase">งบประมาณรวม (บาท)</p>
                    <p class="text-xl font-black text-orange-600"><?= number_format($total_budget) ?></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const projects = <?= json_encode($map_data) ?>;
        
        // Init Map
        const map = L.map('publicMap', { zoomControl: false }).setView([15.1186, 104.3220], 10);
        L.control.zoom({ position: 'topright' }).addTo(map);
        
        // ⭐️ เปลี่ยนมาใช้เซิร์ฟเวอร์แผนที่ของ Google Maps ⭐️
        L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: '© Google Maps'
        }).addTo(map);

        const bounds = [];
        
        projects.forEach(p => {
            const start = [parseFloat(p.start_lat), parseFloat(p.start_long)];
            const color = p.status === 'เสร็จสิ้น' ? '#10b981' : (p.status === 'กำลังดำเนินการ' ? '#f97316' : '#64748b');
            
            const m = L.circleMarker(start, { radius: 8, fillColor: color, color: '#fff', weight: 2, fillOpacity: 1 }).addTo(map);

            if (p.end_lat && p.end_long) {
                const end = [parseFloat(p.end_lat), parseFloat(p.end_long)];
                L.polyline([start, end], { color: color, weight: 4, opacity: 0.8, dashArray: '6,6' }).addTo(map);
                bounds.push(start, end);
            } else { bounds.push(start); }

            const thumbUrl = p.thumbnail ? `uploads/projects/${p.thumbnail}` : 'https://placehold.co/400x200/1e293b/white?text=No+Image';
            const displayRouteName = p.route_name ? ` <span class="text-orange-400 font-bold ml-1 text-[10px]">สายทาง</span> ${p.route_name}` : '';
            
            const popupContent = `
                <div class="flex flex-col">
                    <img src="${thumbUrl}" class="w-full h-28 object-cover">
                    <div class="p-5 space-y-2">
                        <span class="text-[8px] font-black uppercase bg-white/10 px-2 py-0.5 rounded text-orange-400">${p.infrastructure_type} | ปี ${p.fiscal_year}</span>
                        <h5 class="text-xs font-black text-white leading-tight">${p.project_name}${displayRouteName}</h5>
                        <div class="pt-2 mt-2 border-t border-white/10">
                            <p class="text-[8px] text-slate-400 uppercase">งบประมาณ</p>
                            <p class="text-sm font-black text-emerald-400">${new Intl.NumberFormat().format(p.budget_amount)} ฿</p>
                        </div>
                    </div>
                </div>
            `;
            m.bindPopup(popupContent, { className: 'gis-popup' });
        });

        if (bounds.length > 0) map.fitBounds(bounds, { padding: [50, 50], maxZoom: 14 });
        lucide.createIcons();
    </script>
</body>
</html>