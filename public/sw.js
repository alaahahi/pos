// Service Worker - PWA محسّن - دعم Offline سريع
// الهدف: تسريع التطبيق + دعم offline بدون تخزين IndexedDB

const CACHE_NAME = 'pos-v4.0.0'; // ⬆️ تحديث الإصدار - PWA محسّن مع Cache First
const RUNTIME_CACHE = 'pos-runtime-v4.0.0';
const PAGES_CACHE = 'pos-pages-v4.0.0'; // كاش منفصل للصفحات
const ASSETS_TO_CACHE = [
  '/',
  '/offline.html',
  '/app-shell.html'
];

// تثبيت Service Worker
self.addEventListener('install', (event) => {
  console.log('✅ SW v2.0: Installing...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('📦 Caching essential assets...');
        return cache.addAll(ASSETS_TO_CACHE).catch(err => {
          console.log('⚠️ Some assets failed to cache, continuing anyway');
        });
      })
      .then(() => {
        console.log('✅ SW installed!');
        return self.skipWaiting(); // تفعيل فوري
      })
  );
});

// تفعيل Service Worker
self.addEventListener('activate', (event) => {
  console.log('✅ SW v2.0: Activating...');
  
  event.waitUntil(
    // مسح الـ caches القديمة
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames
          .filter(name => name !== CACHE_NAME && name !== PAGES_CACHE && name !== RUNTIME_CACHE)
          .map(name => {
            console.log('🗑️ Deleting old cache:', name);
            return caches.delete(name);
          })
      );
    }).then(() => {
      console.log('✅ SW v4.0 activated and claimed all clients!');
      return self.clients.claim(); // التحكم الفوري بجميع الصفحات
    })
  );
});

// 🎯 التعامل مع الطلبات - الحل الصحيح
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);
  
  // ❌ تجاهل تماماً: الطلبات الخارجية
  if (url.origin !== location.origin) {
    return;
  }
  
  // ❌ تجاهل تماماً: طلبات POST/PUT/DELETE
  if (request.method !== 'GET') {
    return;
  }
  
  // ✅ دعم طلبات Inertia (زيارة الصفحات داخل التطبيق) - Cache First Strategy
  const isInertiaRequest = request.headers.get('X-Inertia') || request.headers.get('X-Inertia-Version');
  if (isInertiaRequest) {
    event.respondWith(
      caches.open(PAGES_CACHE).then(cache => {
        return cache.match(request).then(cached => {
          // إذا كان موجود في الكاش، أرجعها فوراً (أسرع)
          if (cached) {
            // تحديث الكاش في الخلفية (Stale-While-Revalidate)
            fetch(request)
              .then(response => {
                if (response && response.ok) {
                  const clone = response.clone();
                  cache.put(request, clone);
                }
              })
              .catch(() => {}); // تجاهل الأخطاء في التحديث
            return cached;
          }
          
          // إذا لم يكن في الكاش، جلب من الشبكة
          return fetch(request)
            .then(response => {
              if (response && response.ok) {
                const clone = response.clone();
                cache.put(request, clone);
              }
              return response;
            })
            .catch(async () => {
              const shell = await caches.match('/app-shell.html');
              if (shell) {
                return shell;
              }
              return caches.match('/offline.html');
            });
        });
      })
    );
    return;
  }
  
  // ❌ تجاهل تماماً: API endpoints
  if (url.pathname.startsWith('/api/') || 
      url.pathname.includes('logout') || 
      url.pathname.includes('login')) {
    return;
  }
  
  // ✅ تعامل مع طلبات التصفّح (navigate) لتوفير fallback
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then(response => {
          const copy = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(request, copy));
          return response;
        })
        .catch(async () => {
          const cachedPage = await caches.match(request);
          if (cachedPage) {
            return cachedPage;
          }
          const shell = await caches.match('/app-shell.html');
          if (shell) {
            return shell;
          }
          return caches.match('/offline.html');
        })
    );
    return;
  }
  
  // ✅ فقط للملفات الثابتة: استخدم Network First strategy
  if (
    url.pathname.startsWith('/build/') ||
    url.pathname.startsWith('/icons/') ||
    url.pathname.startsWith('/img/') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.jpg') ||
    url.pathname.endsWith('.svg') ||
    url.pathname.endsWith('.woff') ||
    url.pathname.endsWith('.woff2')
  ) {
    event.respondWith(
      fetch(request)
        .then(response => {
          // حفظ في cache فقط إذا نجح
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(request, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          // fallback للـ cache
          return caches.match(request);
        })
    );
  }
  // لكل شيء آخر: دعه يمر بشكل طبيعي (no caching للصفحات الرئيسية)
});

// رسالة لتحديث Service Worker
self.addEventListener('message', (event) => {
  if (event.data === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

// 🚀 Background Sync - مزامنة SQLite تلقائياً عند عودة الإنترنت
self.addEventListener('sync', (event) => {
  console.log('🔄 Background Sync event:', event.tag);
  
  if (event.tag === 'sync-database') {
    event.waitUntil(syncDatabase());
  }
});

// دالة المزامنة في الخلفية - SQLite -> MySQL ثم MySQL -> SQLite
async function syncDatabase() {
  try {
    console.log('🔄 بدء مزامنة قاعدة البيانات في الخلفية...');
    
    // 1. أولاً: مزامنة من SQLite إلى MySQL (نقل البيانات المحلية للسيرفر)
    console.log('📤 مزامنة من SQLite إلى MySQL...');
    const responseUp = await fetch('/api/sync-monitor/sync', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        direction: 'up', // من SQLite إلى MySQL
        safe_mode: true, // Safe Mode: إضافة فقط، لا تحديث
        create_backup: true // إنشاء نسخة احتياطية
      })
    });
    
    if (!responseUp.ok) {
      console.error('❌ فشلت المزامنة من SQLite إلى MySQL:', responseUp.status);
      throw new Error('فشلت المزامنة من SQLite إلى MySQL');
    }
    
    const resultUp = await responseUp.json();
    console.log('✅ تمت المزامنة من SQLite إلى MySQL:', resultUp);
    
    // 2. ثانياً: مزامنة من MySQL إلى SQLite (للتأكد من التحديثات)
    console.log('📥 مزامنة من MySQL إلى SQLite...');
    const responseDown = await fetch('/api/sync-monitor/sync', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        direction: 'down' // من MySQL إلى SQLite
      })
    });
    
    if (!responseDown.ok) {
      console.error('❌ فشلت المزامنة من MySQL إلى SQLite:', responseDown.status);
      // لا نرمي خطأ هنا لأن المزامنة الأولى نجحت
    } else {
      const resultDown = await responseDown.json();
      console.log('✅ تمت المزامنة من MySQL إلى SQLite:', resultDown);
    }
    
    // إرسال رسالة للتطبيق
    const clients = await self.clients.matchAll();
    clients.forEach(client => {
      client.postMessage({
        type: 'SYNC_COMPLETE',
        success: true,
        data: {
          up: resultUp,
          down: resultDown
        }
      });
    });
    
  } catch (error) {
    console.error('❌ خطأ في Background Sync:', error);
    throw error; // لإعادة المحاولة تلقائياً
  }
}

console.log('🚀 Service Worker loaded!');

