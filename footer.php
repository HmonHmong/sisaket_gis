<!-- ... existing code ... -->
    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
        
        // ลงทะเบียน PWA Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js');
            });
        }

        // ระบบหายตัวของข้อความแจ้งเตือน (Flash Messages)
        document.addEventListener('DOMContentLoaded', () => {
<!-- ... existing code ... -->