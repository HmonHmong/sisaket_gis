</main> <!-- ปิด main tag จาก header -->
    </div> <!-- ปิด flex container จาก header -->

    <!-- Footer Section -->
    <footer class="bg-white border-t border-slate-100 py-8 px-10">
        <div class="max-w-[1600px] mx-auto flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-400">
                    <i data-lucide="info" size="20"></i>
                </div>
                <div>
                    <p class="text-xs font-black text-slate-800 uppercase tracking-tight">ระบบจัดเก็บข้อมูลพิกัดโครงสร้างพื้นฐาน</p>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">องค์การบริหารส่วนจังหวัดศรีสะเกษ &copy; 2026</p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="text-right hidden sm:block">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Server Status</p>
                    <p class="text-[10px] text-emerald-500 font-black uppercase mt-0.5 flex items-center gap-1 justify-end">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Online
                    </p>
                </div>
                <div class="h-8 w-px bg-slate-100"></div>
                <div class="flex gap-2">
                    <span class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-300 hover:text-orange-500 transition-colors cursor-help" title="ระบบรองรับ PHP 8.2+">
                        <i data-lucide="code-2" size="14"></i>
                    </span>
                    <span class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-300 hover:text-blue-500 transition-colors cursor-help" title="GIS Powered by Leaflet & Esri">
                        <i data-lucide="map-pin" size="14"></i>
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
        
        // ระบบหายตัวของข้อความแจ้งเตือน (Flash Messages)
        document.addEventListener('DOMContentLoaded', () => {
            const alerts = document.querySelectorAll('.animate-bounce, .bg-emerald-50, .bg-red-50');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 1s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 1000);
                }, 5000);
            });
        });
    </script>
</body>
</html>