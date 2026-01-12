<?php
// includes/footer.php
?>
        </main>
    </div>

    <!-- Mobile Navigation -->
    <footer class="lg:hidden bg-white border-t py-2 px-4 flex justify-between sticky bottom-0 z-[2000] shadow-lg">
        <a href="index.php" class="flex flex-col items-center p-2 text-slate-400">
            <i data-lucide="layout-dashboard" size="20"></i>
            <span class="text-[10px] mt-1 font-black uppercase">หน้าแรก</span>
        </a>
        <a href="projects.php" class="flex flex-col items-center p-2 text-slate-400">
            <i data-lucide="table" size="20"></i>
            <span class="text-[10px] mt-1 font-black uppercase">ทะเบียน</span>
        </a>
        <a href="add_project.php" class="flex flex-col items-center p-2 text-orange-600">
            <i data-lucide="plus-circle" size="20"></i>
            <span class="text-[10px] mt-1 font-black uppercase">เพิ่ม</span>
        </a>
        <a href="map.php" class="flex flex-col items-center p-2 text-slate-400">
            <i data-lucide="map" size="20"></i>
            <span class="text-[10px] mt-1 font-black uppercase">แผนที่</span>
        </a>
    </footer>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
    </script>
</body>
</html>