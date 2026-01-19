<template>
  <div class="wifi-icon-header">
    <button
      @click="toggleMenu"
      class="nav-link nav-icon position-relative"
      :title="tooltip"
    >
      <!-- أيقونة WiFi -->
      <i 
        :class="[
          'bi', 
          realOnlineStatus ? 'bi-wifi' : 'bi-wifi-off',
          realOnlineStatus ? 'text-success' : 'text-danger'
        ]"
      ></i>
      
      <!-- Badge لعدد الملفات المعلقة -->
      <span 
        v-if="pendingCount > 0" 
        class="badge bg-warning badge-number position-absolute top-0 start-100 translate-middle"
      >
        {{ pendingCount > 99 ? '99+' : pendingCount }}
      </span>
    </button>

    <!-- القائمة المنسدلة -->
    <div v-if="showMenu" class="wifi-dropdown-menu dropdown-menu-end" @click.stop>
      <div class="dropdown-header">
        <span class="fw-bold">⚙️ إعدادات المزامنة</span>
        <button @click="closeMenu" class="btn-close btn-sm"></button>
      </div>
      
      <div class="dropdown-divider"></div>
      
      <div class="dropdown-item-text">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <small class="text-muted">الحالة:</small>
          <span :class="['badge', realOnlineStatus ? 'bg-success' : 'bg-danger']">
            {{ realOnlineStatus ? '🟢 متصل' : '🔴 غير متصل' }}
          </span>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <small class="text-muted">ملفات معلقة:</small>
          <span class="badge bg-warning">{{ pendingCount }}</span>
        </div>
        <div v-if="isLocal" class="d-flex justify-content-between align-items-center mb-2">
          <small class="text-muted">المزامنة التلقائية:</small>
          <span :class="['badge', autoSyncEnabled ? 'bg-success' : 'bg-secondary']">
            <i :class="['bi', autoSyncEnabled ? 'bi-check-circle' : 'bi-x-circle', 'me-1']"></i>
            {{ autoSyncEnabled ? 'تعمل' : 'متوقفة' }}
          </span>
        </div>
        <div v-if="isLocal && autoSyncEnabled" class="d-flex justify-content-between align-items-center">
          <small class="text-muted">المزامنة القادمة:</small>
          <span class="badge bg-info">
            <i class="bi bi-clock me-1"></i>
            {{ countdown }}
          </span>
        </div>
      </div>
      
      <div class="dropdown-divider"></div>

      <button
        v-if="isLocal && realOnlineStatus"
        @click="quickSync"
        class="dropdown-item"
        :disabled="isQuickSyncing"
      >
        <i class="bi bi-lightning-charge-fill text-warning me-2"></i>
        <span v-if="!isQuickSyncing">مزامنة سريعة</span>
        <span v-else>⏳ جاري المزامنة...</span>
      </button>
      
      <button
        @click="checkConnection"
        class="dropdown-item"
      >
        <i class="bi bi-arrow-clockwise text-primary me-2"></i>
        فحص الاتصال
      </button>
      
      <div class="dropdown-divider"></div>
      
      <!-- أزرار التبديل بين Local و Online -->
      <button
        v-if="isLocal"
        @click="switchToOnline"
        class="dropdown-item"
      >
        <i class="bi bi-cloud-fill text-info me-2"></i>
        الانتقال إلى Online
      </button>
      
      <button
        v-if="!isLocal"
        @click="switchToLocal"
        class="dropdown-item"
      >
        <i class="bi bi-laptop-fill text-success me-2"></i>
        الانتقال إلى Local
      </button>
      
      <div class="dropdown-divider"></div>
      
      <Link
        :href="route('sync-monitor.index')"
        class="dropdown-item"
        @click="closeMenu"
      >
        <i class="bi bi-gear-fill text-secondary me-2"></i>
        صفحة المزامنة
      </Link>
    </div>

    <!-- Overlay -->
    <div v-if="showMenu" class="menu-overlay-header" @click="closeMenu"></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import axios from 'axios';

const props = defineProps({
  pendingCount: {
    type: Number,
    default: 0
  }
});

const toast = useToast();
const showMenu = ref(false);
const isOnline = ref(navigator.onLine);
const apiAvailable = ref(true);
const isLocal = ref(
  window.location.href.startsWith("http://127.0.0.1") || 
  window.location.href.startsWith("http://localhost")
);
const isQuickSyncing = ref(false);

// حالة المزامنة التلقائية
const autoSyncEnabled = ref(false);
const autoSyncRunning = ref(false);
const nextSyncIn = ref(null); // بالثواني
const lastSyncAt = ref(null);
const countdown = ref('--:--');

const realOnlineStatus = computed(() => {
  return isOnline.value && apiAvailable.value;
});

const tooltip = computed(() => {
  if (!realOnlineStatus.value) return 'غير متصل';
  if (props.pendingCount > 0) return `${props.pendingCount} ملف معلق`;
  return 'متصل - جميع الملفات متزامنة';
});

const toggleMenu = () => {
  showMenu.value = !showMenu.value;
};

const closeMenu = () => {
  showMenu.value = false;
};

const checkApiStatus = async (showToast = false) => {
  try {
    const response = await axios.get('/api/sync-monitor/check-health', { 
      timeout: 5000,
      withCredentials: true 
    });
    apiAvailable.value = response.data?.success !== false;
    
    if (showToast) {
      if (apiAvailable.value) {
        toast.success('✅ الاتصال متاح');
      } else {
        toast.warning('⚠️ السيرفر غير متاح');
      }
    }
    return apiAvailable.value;
  } catch (error) {
    apiAvailable.value = false;
    if (showToast) {
      toast.error('❌ لا يمكن الاتصال بالسيرفر');
    }
    return false;
  }
};

// جلب حالة المزامنة التلقائية
const fetchAutoSyncStatus = async () => {
  if (!isLocal.value) return;
  
  try {
    const response = await axios.get('/api/sync-monitor/auto-sync-status', {
      timeout: 3000,
      withCredentials: true
    });
    
    if (response.data?.success && response.data?.status) {
      const status = response.data.status;
      autoSyncEnabled.value = status.enabled;
      autoSyncRunning.value = status.is_running;
      nextSyncIn.value = status.next_sync_in;
      lastSyncAt.value = status.last_sync_at;
    }
  } catch (error) {
    // Ignore errors silently
    autoSyncEnabled.value = false;
  }
};

// تحويل الثواني إلى MM:SS
const formatCountdown = (seconds) => {
  if (seconds === null || seconds === undefined) return '--:--';
  if (seconds <= 0) return '⏱️ الآن';
  
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

// تحديث العداد التنازلي كل ثانية
const updateCountdown = () => {
  if (nextSyncIn.value !== null && nextSyncIn.value > 0) {
    nextSyncIn.value--;
    countdown.value = formatCountdown(nextSyncIn.value);
  } else if (nextSyncIn.value === 0) {
    countdown.value = '⏱️ الآن';
  } else {
    countdown.value = '--:--';
  }
};

const checkConnection = async () => {
  await checkApiStatus(true);
  closeMenu();
};

const switchToLocal = () => {
  const localUrl = window.connectionInfo?.local_url || "http://127.0.0.1:8000/";
  toast.info('🔄 الانتقال إلى Local...', { timeout: 2000 });
  closeMenu();
  setTimeout(() => {
    window.location.href = localUrl;
  }, 500);
};

const switchToOnline = () => {
  const onlineUrl = window.connectionInfo?.online_url || "https://system.intellijapp.com/";
  toast.info('🔄 الانتقال إلى Online...', { timeout: 2000 });
  closeMenu();
  setTimeout(() => {
    window.location.href = onlineUrl;
  }, 500);
};

const quickSync = async () => {
  if (!realOnlineStatus.value || !isLocal.value) {
    toast.warning('غير متصل بالإنترنت');
    return;
  }
  
  if (isQuickSyncing.value) return;
  
  isQuickSyncing.value = true;
  try {
    // استخدام auto-sync الفوري
    const response = await axios.post('/api/sync-monitor/auto-sync', {}, { 
      timeout: 30000,
      withCredentials: true 
    });
    
    if (response.data?.success) {
      const pushData = response.data?.data?.push || {};
      const synced = pushData.synced || 0;
      const failed = pushData.failed || 0;
      
      if (synced > 0) {
        toast.success(`✅ تم مزامنة ${synced} سجل`);
        // إعادة تحميل الصفحة لتحديث عدد الملفات المعلقة
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else if (failed > 0) {
        toast.warning(`⚠️ فشل ${failed} سجل`);
      } else {
        toast.info('ℹ️ لا توجد تغييرات للمزامنة');
      }
    } else {
      toast.error('❌ فشلت المزامنة');
    }
  } catch (error) {
    console.error('Quick sync error:', error);
    toast.error('❌ خطأ في المزامنة');
  } finally {
    isQuickSyncing.value = false;
    closeMenu();
  }
};

const handleOnline = async () => {
  isOnline.value = true;
  await checkApiStatus(false);
};

const handleOffline = () => {
  isOnline.value = false;
  apiAvailable.value = false;
};

onMounted(() => {
  window.addEventListener('online', handleOnline);
  window.addEventListener('offline', handleOffline);
  checkApiStatus(false);
  
  // جلب حالة المزامنة التلقائية عند التحميل
  if (isLocal.value) {
    fetchAutoSyncStatus();
  }
  
  const apiCheckInterval = setInterval(() => {
    if (isOnline.value) {
      checkApiStatus(false);
    }
  }, 30000);
  
  // تحديث حالة المزامنة التلقائية كل 10 ثواني
  const autoSyncCheckInterval = setInterval(() => {
    if (isLocal.value) {
      fetchAutoSyncStatus();
    }
  }, 10000);
  
  // تحديث العداد التنازلي كل ثانية
  const countdownInterval = setInterval(() => {
    if (isLocal.value && autoSyncEnabled.value) {
      updateCountdown();
    }
  }, 1000);
  
  onUnmounted(() => {
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);
    clearInterval(apiCheckInterval);
    clearInterval(autoSyncCheckInterval);
    clearInterval(countdownInterval);
  });
});
</script>

<style scoped>
.wifi-icon-header {
  position: relative;
}

.wifi-dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 0.5rem;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  min-width: 280px;
  z-index: 1050;
  display: block;
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 0.375rem 0.375rem 0 0;
}

.dropdown-header .btn-close {
  background-color: rgba(255, 255, 255, 0.8);
  opacity: 1;
}

.dropdown-item {
  padding: 0.5rem 1rem;
  cursor: pointer;
  transition: background-color 0.15s ease-in-out;
  border: none;
  width: 100%;
  text-align: right;
  background: none;
  color: #212529;
  display: flex;
  align-items: center;
}

.dropdown-item:hover:not(:disabled) {
  background-color: #f8f9fa;
}

.dropdown-item:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.dropdown-item-text {
  padding: 0.75rem 1rem;
}

.menu-overlay-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: transparent;
  z-index: 1040;
}

/* Badge styling */
.badge-number {
  font-size: 0.65rem;
  padding: 0.25em 0.4em;
  border-radius: 0.375rem;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .wifi-dropdown-menu {
    background: #2d3748;
    border-color: #4a5568;
    color: #e2e8f0;
  }
  
  .dropdown-item {
    color: #e2e8f0;
  }
  
  .dropdown-item:hover:not(:disabled) {
    background-color: #374151;
  }
  
  .dropdown-divider {
    border-color: #4a5568;
  }
}
</style>
