<template>
  <div class="wifi-icon-container">
    <button
      @click="toggleMenu"
      class="wifi-icon"
      :class="{ 'online': isOnline, 'offline': !isOnline, 'local': isLocal }"
      :title="tooltip"
    >
      <svg v-if="isOnline && !isLocal" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
        <path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.07 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/>
      </svg>
      <svg v-else-if="!isOnline || isLocal" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
        <path d="M23.64 7c-.45-.34-4.93-4-11.64-4C5.28 3 .81 6.66.36 7L12 21.5 23.64 7zM3.42 2.36L2.01 3.78 4.1 5.87C2.79 7.57 1.74 9.42 1.11 11.3l1.78 1.78c.88-1.9 2.02-3.6 3.33-5.01L8.9 9.98c-1.09 1.29-1.98 2.78-2.66 4.4l1.78 1.78c.84-1.75 2.01-3.31 3.38-4.64l2.01 2.01c-1.37 1.37-2.54 2.89-3.38 4.64l1.78 1.78c.68-1.62 1.57-3.11 2.66-4.4l2.13 2.13c1.31 1.41 2.45 3.11 3.33 5.01l1.78-1.78c-.63-1.88-1.68-3.73-2.99-5.43l2.12 2.12 1.41-1.41L3.42 2.36z"/>
      </svg>
    </button>

    <!-- القائمة المنسدلة -->
    <div v-if="showMenu" class="wifi-menu" @click.stop>
      <div class="menu-header">
        <span class="menu-title">⚙️ إعدادات الاتصال</span>
        <button @click="closeMenu" class="menu-close">✕</button>
      </div>
      
      <div class="menu-status">
        <div class="status-item">
          <span class="status-label">الحالة:</span>
          <span :class="['status-value', isOnline ? 'online' : 'offline']">
            {{ isOnline ? '🟢 متصل' : '🔴 غير متصل' }}
          </span>
        </div>
        <div class="status-item">
          <span class="status-label">الموقع:</span>
          <span :class="['status-value', isLocal ? 'local' : 'online']">
            {{ isLocal ? '💻 Local' : '🌐 Online' }}
          </span>
        </div>
      </div>

      <div class="menu-actions">
        <button
          @click="switchToLocal"
          class="menu-btn"
          :class="{ 'active': isLocal }"
          :disabled="isLocal"
        >
          💻 التبديل إلى Local
        </button>
        <button
          @click="switchToOnline"
          class="menu-btn"
          :class="{ 'active': !isLocal }"
          :disabled="!isLocal"
        >
          🌐 التبديل إلى Online
        </button>
        <button
          @click="checkConnection"
          class="menu-btn check-btn"
        >
          🔄 فحص الاتصال
        </button>
      </div>

      <div class="menu-footer">
        <Link
          :href="route('sync.monitor')"
          class="menu-link"
        >
          📊 صفحة المزامنة
        </Link>
      </div>
    </div>

    <!-- Overlay لإغلاق القائمة -->
    <div v-if="showMenu" class="menu-overlay" @click="closeMenu"></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/inertia-vue3';

const showMenu = ref(false);
const isOnline = ref(navigator.onLine);
const isLocal = ref(
  window.location.href.startsWith("http://127.0.0.1") || 
  window.location.href.startsWith("http://localhost")
);

const tooltip = computed(() => {
  if (!isOnline.value) return 'غير متصل - اضغط للتبديل';
  return isLocal.value ? 'Local - اضغط للتبديل' : 'Online - اضغط للتبديل';
});

const toggleMenu = () => {
  showMenu.value = !showMenu.value;
};

const closeMenu = () => {
  showMenu.value = false;
};

const switchToLocal = () => {
  if (window.switchToLocal) {
    window.switchToLocal();
  } else {
    // استخدام URL من connectionInfo إذا كان متاحاً
    const localUrl = window.connectionInfo?.local_url || "http://127.0.0.1:8000/";
    window.location.href = localUrl;
  }
};

const switchToOnline = () => {
  if (window.switchToOnline) {
    window.switchToOnline();
  } else {
    // استخدام URL من connectionInfo إذا كان متاحاً
    const onlineUrl = window.connectionInfo?.online_url || "https://system.intellijapp.com/dashboard";
    window.location.href = onlineUrl;
  }
};

const checkConnection = () => {
  if (window.checkConnectionManually) {
    window.checkConnectionManually();
  }
  closeMenu();
};

const handleOnline = () => {
  isOnline.value = true;
};

const handleOffline = () => {
  isOnline.value = false;
};

onMounted(() => {
  window.addEventListener('online', handleOnline);
  window.addEventListener('offline', handleOffline);
  
  // تحديث حالة Local عند تغيير الصفحة
  const updateLocation = () => {
    isLocal.value = window.location.href.startsWith("http://127.0.0.1") || 
                    window.location.href.startsWith("http://localhost");
  };
  
  window.addEventListener('focus', updateLocation);
  
  onUnmounted(() => {
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);
    window.removeEventListener('focus', updateLocation);
  });
});
</script>

<style scoped>
.wifi-icon-container {
  position: fixed;
  bottom: 20px;
  left: 20px;
  z-index: 9999;
  font-family: Arial, sans-serif;
}

.wifi-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transition: all 0.3s ease;
  position: relative;
  border: none;
  padding: 0;
}

.wifi-icon:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
}

.wifi-icon svg {
  width: 24px;
  height: 24px;
  color: white;
}

.wifi-icon.online {
  background: linear-gradient(135deg, #00C851 0%, #007E33 100%);
}

.wifi-icon.offline,
.wifi-icon.local {
  background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

/* القائمة المنسدلة */
.wifi-menu {
  position: absolute;
  bottom: 60px;
  left: 0;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
  min-width: 280px;
  max-width: 320px;
  overflow: hidden;
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.menu-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.menu-title {
  font-weight: 600;
  font-size: 14px;
}

.menu-close {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  line-height: 1;
  transition: background 0.2s;
}

.menu-close:hover {
  background: rgba(255, 255, 255, 0.3);
}

.menu-status {
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.status-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.status-item:last-child {
  margin-bottom: 0;
}

.status-label {
  font-size: 13px;
  color: #6b7280;
}

.status-value {
  font-weight: 600;
  font-size: 13px;
}

.status-value.online {
  color: #10b981;
}

.status-value.offline {
  color: #ef4444;
}

.status-value.local {
  color: #f59e0b;
}

.menu-actions {
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.menu-btn {
  padding: 10px 16px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.2s;
  text-align: right;
  background: #f3f4f6;
  color: #374151;
}

.menu-btn:hover:not(:disabled) {
  background: #e5e7eb;
  transform: translateX(-2px);
}

.menu-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.menu-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.check-btn {
  background: linear-gradient(135deg, #33b5e5 0%, #0099CC 100%);
  color: white;
}

.check-btn:hover {
  background: linear-gradient(135deg, #0099CC 0%, #0077b3 100%);
}

.menu-footer {
  padding: 12px 16px;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
}

.menu-link {
  display: block;
  text-align: center;
  color: #667eea;
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  transition: color 0.2s;
}

.menu-link:hover {
  color: #764ba2;
}

.menu-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.1);
  z-index: -1;
}

/* RTL Support */
[dir="rtl"] .wifi-icon-container {
  left: auto;
  right: 20px;
}

[dir="rtl"] .wifi-menu {
  left: auto;
  right: 0;
}

[dir="rtl"] .menu-btn:hover:not(:disabled) {
  transform: translateX(2px);
}
</style>

