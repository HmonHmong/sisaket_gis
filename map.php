<?php
// map.php
include 'config/db.php';
include 'includes/header.php';

// ดึงข้อมูลพิกัดทั้งหมด
$sql = "SELECT id, project_name, start_lat, start_long, end_lat, end_long FROM projects WHERE start_lat IS NOT NULL";
$result = $conn->query($sql);
$map_data = [];
while($row = $result->fetch_assoc()) {
    $map_data[] = $row;
}
?>

<div class="h-[calc(100vh-12rem)] flex flex-col space-y-4">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-800">ระบบ GIS วิศวกรรมสำนักช่าง</h2>
            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">Satellite Imaging System</p>
        </div>
        <div class="flex gap-4">
            <div class="flex items-center gap-2 text-[10px] font-black uppercase"><div class="w-3 h-3 bg-orange-500 rounded-full"></div> จุดเริ่มต้น</div>
            <div class="flex items-center gap-2 text-[10px] font-black uppercase"><div class="w-3 h-3 bg-blue-500 rounded-full"></div> จุดสิ้นสุด</div>
        </div>
    </div>
    
    <div id="map" class="flex-1 border-8 border-white shadow-2xl"></div>
</div>

<script>
    const projects = <?= json_encode($map_data) ?>;
    
    const map = L.map('map').setView([15.1186, 104.3220], 10);
    
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Source: Esri'
    }).addTo(map);

    const bounds = [];

    projects.forEach(p => {
        const start = [parseFloat(p.start_lat), parseFloat(p.start_long)];
        const end = [parseFloat(p.end_lat), parseFloat(p.end_long)];

        if(!isNaN(start[0]) && !isNaN(end[0])) {
            L.polyline([start, end], {
                color: '#ffffff',
                weight: 5,
                opacity: 0.8,
                dashArray: '10, 10'
            }).addTo(map).bindPopup(`<b>${p.project_name}</b>`);

            L.circleMarker(start, { radius: 6, fillColor: "#f97316", color: "#fff", weight: 2, fillOpacity: 1 }).addTo(map);
            L.circleMarker(end, { radius: 6, fillColor: "#0ea5e9", color: "#fff", weight: 2, fillOpacity: 1 }).addTo(map);
            
            bounds.push(start, end);
        }
    });

    if(bounds.length > 0) map.fitBounds(bounds, { padding: [50, 50] });
</script>

<?php include 'includes/footer.php'; ?>