// sw.js - Service Worker สำหรับระบบ PWA (SSK GIS V2.5)

// ตั้งชื่อและเวอร์ชันของ Cache (หากมีการอัปเดตระบบใหญ่ๆ ให้มาเปลี่ยนตัวเลขตรงนี้)
const CACHE_NAME = 'ssk-gis-cache-v2.5.0';

// ระบุไฟล์หรือลิงก์พื้นฐานที่ต้องการให้ระบบโหลดเก็บไว้ในเครื่องมือถือทันที (Pre-cache)
const ASSETS_TO_CACHE = [
    './',
    './index.php',
    './manifest.json',
    'https://cdn.tailwindcss.com',
    'https://unpkg.com/lucide@latest',
    'https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap'
];

// 1. Install Event: เมื่อมือถือของผู้ใช้รู้จัก Service Worker นี้ครั้งแรก ให้โหลดไฟล์ที่จำเป็นเก็บไว้
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[Service Worker] Caching App Shell...');
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
    // บังคับให้ Service Worker ตัวใหม่ทำงานทันที โดยไม่ต้องรอปิดเบราว์เซอร์
    self.skipWaiting();
});

// 2. Activate Event: ลบ Cache เวอร์ชั่นเก่าๆ ทิ้ง เพื่อให้พื้นที่มือถือไม่เต็มและได้หน้าเว็บอัปเดตล่าสุด
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        console.log('[Service Worker] Clearing Old Cache:', cache);
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// 3. Fetch Event: ตัวจัดการการดึงข้อมูล (Network First Strategy)
self.addEventListener('fetch', (event) => {
    // ข้ามการ Cache หากเป็นคำสั่ง POST/PUT (เช่น ตอนบันทึกฟอร์มเพิ่มโครงการ)
    if (event.request.method !== 'GET') {
        return;
    }

    // ข้ามการ Cache ข้อมูลแผนที่ Leaflet (เพราะมันเยอะมาก จะทำให้เครื่องมือถือช้า)
    if (event.request.url.includes('arcgisonline') || event.request.url.includes('cartocdn')) {
        return;
    }

    event.respondWith(
        // พยายามดึงข้อมูลจากอินเทอร์เน็ตของจริงก่อน (Network First)
        fetch(event.request)
            .then((response) => {
                // ถ้ามีเน็ต: เอาข้อมูลที่ได้ ไปโคลนเก็บลง Cache ไว้ใช้ยามฉุกเฉิน แล้วส่งข้อมูลให้เบราว์เซอร์
                const resClone = response.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(event.request, resClone);
                });
                return response;
            })
            .catch(() => {
                // ถ้าไม่มีเน็ต (Offline): ให้ดึงข้อมูลหน้าเว็บที่เคยเซฟไว้ใน Cache มาแสดงผลแทนหน้าไดโนเสาร์
                return caches.match(event.request);
            })
    );
});