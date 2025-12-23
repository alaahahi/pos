<template>
  <div v-if="showBar" class="sync-status-bar" :class="statusClass">
    <div class="container">
      <div class="status-content">
        <!-- الأيقونة -->
        <div class="status-icon">
          <svg v-if="isSyncing" class="animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/>
          </svg>
          <span v-else class="status-emoji">{{ statusEmoji }}</span>
        </div>

        <!-- الرسالة -->
        <div class="status-message">
          <span class="message-text">{{ statusMessage }}</span>
          <span v-if="pendingCount > 0" class="pending-badge">{{ pendingCount }}</span>
        </div>

        <!-- التقدم -->
        <div v-if="isSyncing && progress > 0" class="progress-info">
          {{ syncedCount }} / {{ totalCount }}
        </div>

        <!-- الأزرار -->
        <div class="status-actions">
          <!-- أزرار التبديل بين Online/Offline -->
          <button
            v-if="isLocal"
            @click="switchToOnline"
            class="btn-switch"
            title="التبديل إلى السيرفر"
          >
            🌐 Online
          </button>
          <button
            v-if="!isLocal"
            @click="switchToLocal"
            class="btn-switch"
            title="التبديل إلى اللوكل"
          >
            💻 Local
          </button>

          <button
            v-if="canSync"
            @click="syncNow"
            class="btn-sync"
            :disabled="isSyncing"
            title="المزامنة الذكية - مزامنة التغييرات من sync_queue إلى السيرفر"
          >
            <span v-if="!isSyncing">🔄 المزامنة الذكية</span>
            <span v-else>⏳ جاري المزامنة...</span>
          </button>

          <Link
            :href="route('sync-monitor.index')"
            class="btn-details"
          >
            📊 التفاصيل
          </Link>

          <button
            @click="dismissBar"
            class="btn-close"
          >
            ✕
          </button>
        </div>
      </div>

      <!-- شريط التقدم -->
      <div v-if="isSyncing" class="progress-bar">
        <div class="progress-fill" :style="{ width: progress + '%' }"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import axios from 'axios';

const toast = useToast();

// البيانات
const showBar = ref(false);
const isOnline = ref(navigator.onLine);
const isSyncing = ref(false);
const pendingCount = ref(0);
const syncedCount = ref(0);
const totalCount = ref(0);
const dismissed = ref(false);
const isLocal = ref(window.location.href.startsWith("http://127.0.0.1") || window.location.href.startsWith("http://localhost"));

// الحالة المحسوبة
const canSync = computed(() => {
  return isOnline.value && pendingCount.value > 0 && !isSyncing.value;
});

const progress = computed(() => {
  if (totalCount.value === 0) return 0;
  return Math.round((syncedCount.value / totalCount.value) * 100);
});

const statusClass = computed(() => {
  if (isSyncing.value) return 'syncing';
  if (!isOnline.value) return 'offline';
  if (pendingCount.value > 0) return 'pending';
  return 'online';
});

const statusEmoji = computed(() => {
  if (!isOnline.value) return '📴';
  if (pendingCount.value > 0) return '⏳';
  return '✅';
});

const statusMessage = computed(() => {
  if (isSyncing.value) return `جاري المزامنة... ${progress.value}%`;
  if (!isOnline.value) return 'غير متصل - وضع Offline مفعّل';
  if (pendingCount.value > 0) return `${pendingCount.value} عملية في انتظار المزامنة`;
  return 'كل شيء محدّث';
});

// الوظائف
const updateStatus = async () => {
  try {
    if (!isOnline.value) {
      showBar.value = true;
      return;
    }

    // الحصول على حالة المزامنة من API
    try {
      const response = await axios.get('/api/sync-monitor/metadata');
      if (response.data.success) {
        // استخدام queue_stats للحصول على العدد الحقيقي من sync_queue
        const queueStats = response.data.queue_stats || {};
        pendingCount.value = queueStats.pending || 0;
        
        // إظهار الشريط إذا كان هناك عمليات معلقة أو offline
        if ((pendingCount.value > 0 || !isOnline.value) && !dismissed.value) {
          showBar.value = true;
        } else if (pendingCount.value === 0 && isOnline.value) {
          showBar.value = false;
        }
      }
    } catch (error) {
      // إذا فشل API، لا نعرض الشريط
      console.error('فشل تحديث حالة المزامنة:', error);
      showBar.value = false;
    }
  } catch (error) {
    console.error('فشل تحديث حالة المزامنة:', error);
  }
};

const syncNow = async () => {
  if (!canSync.value) return;
  
  isSyncing.value = true;
  syncedCount.value = 0;
  totalCount.value = pendingCount.value;
  
  try {
    // بدء المزامنة في الخلفية (Background Job)
    const response = await axios.post('/api/sync-monitor/smart-sync', {
      limit: 100
    });
    
    if (!response.data || !response.data.success) {
      throw new Error(response.data?.message || 'فشل بدء المزامنة');
    }
    
    const jobId = response.data.job_id;
    toast.info('🔄 تم بدء المزامنة في الخلفية...');
    
    // Polling: التحقق من حالة المزامنة كل ثانية
    const pollInterval = setInterval(async () => {
      try {
        const statusResponse = await axios.get('/api/sync-monitor/sync-status', {
          params: { job_id: jobId }
        });
        
        if (statusResponse.data && statusResponse.data.success) {
          const status = statusResponse.data.status;
          
          // تحديث التقدم
          if (status.synced !== undefined) {
            syncedCount.value = status.synced || 0;
          }
          if (status.total_processed !== undefined) {
            totalCount.value = status.total_processed || pendingCount.value;
          }
          
          // إذا اكتملت المزامنة
          if (status.status === 'completed') {
            clearInterval(pollInterval);
            isSyncing.value = false;
            
            const synced = status.synced || 0;
            const failed = status.failed || 0;
            
            // تحديث الحالة
            await updateStatus();
            
            if (failed === 0 && pendingCount.value === 0) {
              toast.success(`✅ تمت مزامنة ${synced} عملية بنجاح!`);
              showBar.value = false;
            } else if (synced > 0) {
              toast.warning(`⚠️ تمت مزامنة ${synced} عملية، فشل ${failed}، متبقي ${pendingCount.value}`);
            } else {
              toast.error('❌ فشلت المزامنة');
            }
          }
          
          // إذا فشلت المزامنة
          if (status.status === 'failed') {
            clearInterval(pollInterval);
            isSyncing.value = false;
            
            const errorMsg = status.error || 'فشلت المزامنة';
            toast.error('❌ ' + errorMsg);
            
            // تحديث الحالة
            await updateStatus();
          }
        } else if (statusResponse.data && statusResponse.data.status === 'not_found') {
          // Job لم يعد موجوداً (اكتمل وتم حذفه من Cache)
          clearInterval(pollInterval);
          isSyncing.value = false;
          
          // تحديث الحالة
          await updateStatus();
          
          if (pendingCount.value === 0) {
            toast.success('✅ تمت المزامنة بنجاح!');
            showBar.value = false;
          }
        }
      } catch (error) {
        console.error('خطأ في التحقق من حالة المزامنة:', error);
        // لا نوقف المزامنة، فقط نستمر في المحاولة
      }
    }, 1000); // كل ثانية
    
    // Timeout: إيقاف Polling بعد 10 دقائق
    setTimeout(() => {
      clearInterval(pollInterval);
      if (isSyncing.value) {
        isSyncing.value = false;
        toast.warning('⏱️ انتهت مهلة المزامنة. قد تكون المزامنة لا تزال جارية في الخلفية.');
        updateStatus();
      }
    }, 600000); // 10 دقائق
  } catch (error) {
    console.error('فشلت المزامنة:', error);
    const errorMessage = error.response?.data?.message || error.message || 'فشلت المزامنة';
    
    // عرض رسالة خطأ واضحة
    if (errorMessage.includes('MySQL غير متاح') || errorMessage.includes('لا يمكن الاتصال')) {
      toast.error('❌ MySQL غير متاح - لا يمكن المزامنة. يرجى التحقق من الاتصال بالسيرفر.');
    } else {
      toast.error('❌ فشلت المزامنة: ' + errorMessage);
    }
    
    // إعادة تعيين العدادات
    syncedCount.value = 0;
    totalCount.value = pendingCount.value;
  } finally {
    isSyncing.value = false;
    // تحديث الحالة مرة أخرى للتأكد من الأرقام الصحيحة
    await updateStatus();
  }
};

const dismissBar = () => {
  dismissed.value = true;
  showBar.value = false;
  
  // إعادة إظهار الشريط بعد دقيقة إذا كان هناك عمليات معلقة
  setTimeout(() => {
    dismissed.value = false;
    updateStatus();
  }, 60000);
};

const handleOnline = () => {
  isOnline.value = true;
  dismissed.value = false;
  updateStatus();
  
  // مزامنة تلقائية بعد ثانيتين
  setTimeout(() => {
    if (pendingCount.value > 0) {
      syncNow();
    }
  }, 2000);
};

const handleOffline = () => {
  isOnline.value = false;
  showBar.value = true;
};

// وظائف التبديل بين Online/Offline
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

// Lifecycle
onMounted(() => {
  updateStatus();
  
  // مستمعو الأحداث
  window.addEventListener('online', handleOnline);
  window.addEventListener('offline', handleOffline);
  
  // تحديث دوري
  const interval = setInterval(updateStatus, 5000); // كل 5 ثواني
  
  onUnmounted(() => {
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);
    clearInterval(interval);
  });
});
</script>

<style scoped>
.sync-status-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 9998;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    transform: translateY(-100%);
  }
  to {
    transform: translateY(0);
  }
}

.sync-status-bar.online {
  background: linear-gradient(135deg, #00C851 0%, #007E33 100%);
  color: white;
}

.sync-status-bar.offline {
  background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
  color: white;
}

.sync-status-bar.pending {
  background: linear-gradient(135deg, #ffbb33 0%, #FF8800 100%);
  color: white;
}

.sync-status-bar.syncing {
  background: linear-gradient(135deg, #33b5e5 0%, #0099CC 100%);
  color: white;
}

.container {
  max-width: 100%;
  margin: 0 auto;
}

.status-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px;
  gap: 15px;
}

.status-icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
}

.status-icon svg {
  width: 100%;
  height: 100%;
}

.status-emoji {
  font-size: 20px;
}

.status-message {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 500;
}

.message-text {
  font-size: 14px;
}

.pending-badge {
  background: rgba(255, 255, 255, 0.3);
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: bold;
}

.progress-info {
  font-size: 13px;
  opacity: 0.9;
  font-weight: 600;
}

.status-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.btn-sync,
.btn-details,
.btn-close,
.btn-switch {
  padding: 6px 12px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.2s;
}

.btn-switch {
  background: rgba(255, 255, 255, 0.25);
  color: white;
  margin-left: 8px;
}

.btn-switch:hover {
  background: rgba(255, 255, 255, 0.35);
  transform: scale(1.05);
}

.btn-sync {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.btn-sync:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.3);
}

.btn-sync:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-details {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  text-decoration: none;
}

.btn-details:hover {
  background: rgba(255, 255, 255, 0.3);
}

.btn-close {
  background: rgba(0, 0, 0, 0.2);
  color: white;
  padding: 4px 10px;
  font-size: 18px;
  line-height: 1;
}

.btn-close:hover {
  background: rgba(0, 0, 0, 0.3);
}

.progress-bar {
  height: 3px;
  background: rgba(0, 0, 0, 0.2);
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: rgba(255, 255, 255, 0.8);
  transition: width 0.3s ease;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* RTL Support */
[dir="rtl"] .status-content {
  flex-direction: row-reverse;
}
</style>

