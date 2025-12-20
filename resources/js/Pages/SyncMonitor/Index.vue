<template>
  <AuthenticatedLayout :translations="translations">
    <Head title="مراقبة المزامنة" />

    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl dark:text-gray-200 text-gray-800">
          🔄 مراقبة المزامنة والـ Offline Mode
        </h2>
        <div class="flex gap-2">
          <button
            @click="refreshData"
            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
            :disabled="isRefreshing"
          >
            <span v-if="!isRefreshing">🔄 تحديث</span>
            <span v-else>⏳ جاري...</span>
          </button>
          <button
            v-if="syncStatus.pendingCount > 0 && connectionStatus.online"
            @click="syncAll"
            class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
            :disabled="isSyncing"
          >
            <span v-if="!isSyncing">✅ مزامنة الكل</span>
            <span v-else>⏳ جاري المزامنة...</span>
          </button>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- معلومات قاعدة البيانات -->
        <div class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold dark:text-gray-200">🗄️ قاعدة البيانات والمزامنة</h3>
            <button
              @click="loadDatabaseInfo"
              :disabled="loadingDatabaseInfo"
              class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50"
            >
              <span v-if="!loadingDatabaseInfo">🔄 تحديث</span>
              <span v-else>⏳ جاري...</span>
            </button>
          </div>
          <div v-if="loadingDatabaseInfo" class="text-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
          </div>
          <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
              <h4 class="text-md font-semibold mb-3">📊 قاعدة البيانات</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span>النوع:</span>
                  <span class="font-mono">{{ databaseInfo.type || 'SQLite' }}</span>
                </div>
                <div class="flex justify-between">
                  <span>الجداول:</span>
                  <span class="font-mono">{{ databaseInfo.total_tables || syncedTables.length }}</span>
                </div>
              </div>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
              <h4 class="text-md font-semibold mb-3">🔄 المزامنة</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span>في الانتظار:</span>
                  <span class="font-bold">{{ syncStatus.pendingCount }}</span>
                </div>
                <div class="flex justify-between">
                  <span>الحالة:</span>
                  <span :class="connectionStatus.online ? 'text-green-600' : 'text-red-600'">
                    {{ connectionStatus.online ? 'متصل' : 'غير متصل' }}
                  </span>
                </div>
              </div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg">
              <h4 class="text-md font-semibold mb-3">⚡ إجراءات سريعة</h4>
              <div class="space-y-2">
                <button
                  @click="syncAll"
                  :disabled="!connectionStatus.online || isSyncing"
                  class="w-full px-3 py-2 bg-green-600 text-white text-xs rounded hover:bg-green-700 disabled:opacity-50"
                >
                  🔄 مزامنة الكل
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- رسالة Offline -->
        <div v-if="!connectionStatus.online" class="mb-6 bg-yellow-50 dark:bg-yellow-900 border-l-4 border-yellow-500 p-4 rounded">
          <div class="flex items-center">
            <span class="text-3xl">⚠️</span>
            <div class="mr-3">
              <p class="text-lg font-medium text-yellow-800 dark:text-yellow-200">أنت حالياً في وضع Offline</p>
              <p class="text-sm text-yellow-700 dark:text-yellow-300">التغييرات سيتم حفظها محلياً ومزامنتها تلقائياً عند عودة الاتصال</p>
            </div>
          </div>
        </div>

        <!-- تبويبات -->
        <div class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
          <div class="border-b border-gray-200 dark:border-gray-700">
            <div class="flex">
              <button
                @click="activeTab = 'tables'"
                :class="['px-6 py-3 text-sm font-medium border-b-2 transition-colors', activeTab === 'tables' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500']"
              >
                📋 الجداول
              </button>
              <button
                @click="activeTab = 'sync'"
                :class="['px-6 py-3 text-sm font-medium border-b-2 transition-colors', activeTab === 'sync' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500']"
              >
                🔄 المزامنة
              </button>
              <button
                @click="activeTab = 'backups'"
                :class="['px-6 py-3 text-sm font-medium border-b-2 transition-colors', activeTab === 'backups' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500']"
              >
                💾 النسخ الاحتياطية
              </button>
            </div>
          </div>

          <!-- تبويب الجداول -->
          <div v-if="activeTab === 'tables'" class="p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold">📋 الجداول المزامنة ({{ syncedTables.length }})</h3>
              <div class="flex gap-2">
                <select
                  v-model="selectedDatabase"
                  @change="loadSyncedTables"
                  class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-gray-200 text-sm"
                >
                  <option value="auto">🔄 تلقائي</option>
                  <option value="mysql">☁️ MySQL</option>
                  <option value="sync_sqlite">🖥️ SQLite</option>
                </select>
                <button @click="loadSyncedTables" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm" :disabled="loadingTables">
                  <span v-if="!loadingTables">🔄 تحديث</span>
                  <span v-else>⏳ جاري...</span>
                </button>
              </div>
            </div>
            <div v-if="loadingTables" class="text-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            </div>
            <div v-else class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">اسم الجدول</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">عدد السجلات</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الاتصال</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الإجراءات</th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200">
                  <tr v-for="table in syncedTables" :key="table.name" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ table.name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ (table.rows || table.count || 0).toLocaleString() }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="table.connection === 'sync_sqlite' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'" class="px-2 py-1 text-xs rounded-full">
                        {{ table.connection === 'sync_sqlite' ? 'SQLite' : 'MySQL' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <button @click="viewTableDetails(table.name)" class="text-blue-600 hover:text-blue-900">عرض التفاصيل</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- تبويب المزامنة -->
          <div v-if="activeTab === 'sync'" class="p-6">
            <div class="mb-4">
              <h3 class="text-lg font-semibold mb-4">🔄 عمليات المزامنة</h3>
              <div class="flex gap-2 flex-wrap">
                <button @click="syncDirection('up')" :disabled="isSyncing" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50">
                  📤 SQLite → MySQL
                </button>
                <button @click="syncDirection('down')" :disabled="isSyncing" class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 disabled:opacity-50">
                  📥 MySQL → SQLite
                </button>
                <button @click="syncAllTables('up')" :disabled="isSyncing" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50">
                  🔄 الكل ↑
                </button>
                <button @click="syncAllTables('down')" :disabled="isSyncing" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 disabled:opacity-50">
                  🔄 الكل ↓
                </button>
              </div>
            </div>
            <div class="mt-4">
              <h4 class="text-md font-semibold mb-2">📊 بيانات المزامنة</h4>
              <button @click="loadSyncMetadata" :disabled="loadingMetadata" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm mb-4">
                <span v-if="!loadingMetadata">🔄 تحديث Metadata</span>
                <span v-else>⏳ جاري...</span>
              </button>
              <div v-if="syncMetadata.data.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">اسم الجدول</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الاتجاه</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">آخر ID</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">إجمالي المزامن</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="item in syncMetadata.data" :key="`${item.table_name}-${item.direction}`">
                      <td class="px-6 py-4 text-sm">{{ item.table_name }}</td>
                      <td class="px-6 py-4">
                        <span :class="item.direction === 'down' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'" class="px-2 py-1 text-xs rounded-full">
                          {{ item.direction === 'down' ? '↓' : '↑' }}
                        </span>
                      </td>
                      <td class="px-6 py-4 text-sm">{{ item.last_synced_id?.toLocaleString() || 0 }}</td>
                      <td class="px-6 py-4 text-sm">{{ item.total_synced?.toLocaleString() || 0 }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>


          <!-- تبويب النسخ الاحتياطية -->
          <div v-if="activeTab === 'backups'" class="p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold">💾 النسخ الاحتياطية</h3>
              <button @click="loadBackups" :disabled="loadingBackups" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
                <span v-if="!loadingBackups">🔄 تحديث</span>
                <span v-else>⏳ جاري...</span>
              </button>
            </div>
            <div v-if="loadingBackups" class="text-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            </div>
            <div v-else-if="backups.length === 0" class="text-center py-8 text-gray-500">لا توجد نسخ احتياطية</div>
            <div v-else class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">اسم الملف</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحجم</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">التاريخ</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الإجراءات</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="backup in backups" :key="backup.name" class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm">{{ backup.name }}</td>
                    <td class="px-6 py-4 text-sm">{{ formatFileSize(backup.size) }}</td>
                    <td class="px-6 py-4 text-sm">{{ formatDate(backup.date) }}</td>
                    <td class="px-6 py-4 text-sm">
                      <button @click="restoreBackup(backup.name)" class="text-green-600 hover:text-green-900 mr-3" :disabled="restoringBackup">
                        🔄 استعادة
                      </button>
                      <button @click="downloadBackup(backup.name)" class="text-blue-600 hover:text-blue-900 mr-3">📥 تحميل</button>
                      <button @click="deleteBackup(backup.name)" class="text-red-600 hover:text-red-900">🗑️ حذف</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal تفاصيل الجدول -->
    <Modal :show="tableDetailsModal.show" @close="tableDetailsModal.show = false">
      <div class="p-6">
        <h3 class="text-lg font-semibold mb-4">📋 تفاصيل الجدول: {{ tableDetailsModal.tableName }}</h3>
        <div v-if="loadingTableDetails" class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        </div>
        <div v-else-if="tableDetailsModal.error" class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
          <p class="text-red-800">{{ tableDetailsModal.error }}</p>
        </div>
        <div v-else>
          <div class="mb-4 flex justify-between items-center">
            <div class="text-sm text-gray-600">
              إجمالي السجلات: <span class="font-semibold">{{ tableDetailsModal.total?.toLocaleString() || 0 }}</span>
            </div>
            <div class="text-sm text-gray-600">
              الاتصال: <span class="font-semibold">{{ tableDetailsModal.connection || '-' }}</span>
            </div>
          </div>
          
          <div v-if="tableDetailsModal.columns.length === 0 && tableDetailsModal.data.length === 0" class="text-center py-8 text-gray-500">
            <div class="text-5xl mb-2">📋</div>
            <p>لا توجد بيانات في هذا الجدول</p>
            <p class="text-xs mt-2">الجدول فارغ أو لا يحتوي على سجلات</p>
          </div>
          
          <div v-else-if="tableDetailsModal.columns.length > 0">
            <!-- معلومات الصفحة -->
            <div class="mb-3 flex justify-between items-center text-sm text-gray-600">
              <div>
                عرض {{ (tableDetailsModal.offset || 0) + 1 }} إلى {{ Math.min((tableDetailsModal.offset || 0) + tableDetailsModal.limit, tableDetailsModal.total) }} من {{ tableDetailsModal.total?.toLocaleString() || 0 }} سجل
              </div>
              <div class="flex items-center gap-2">
                <span>عدد السجلات في الصفحة:</span>
                <select 
                  v-model="tableDetailsModal.limit" 
                  @change="loadTableDetails(tableDetailsModal.tableName, 0)"
                  class="px-2 py-1 border rounded text-sm"
                >
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                  <option :value="200">200</option>
                  <option :value="500">500</option>
                </select>
              </div>
            </div>
            
            <!-- الجدول -->
            <div class="overflow-x-auto max-h-96 border rounded">
              <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 sticky top-0">
                  <tr>
                    <th v-for="column in tableDetailsModal.columns" :key="column" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase border-r border-gray-200">
                      {{ column }}
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-if="tableDetailsModal.data.length === 0">
                    <td :colspan="tableDetailsModal.columns.length || 1" class="px-4 py-8 text-center text-gray-500">
                      لا توجد بيانات للعرض
                    </td>
                  </tr>
                  <tr v-for="(row, index) in tableDetailsModal.data" :key="index" class="hover:bg-gray-50">
                    <td v-for="column in tableDetailsModal.columns" :key="`${index}-${column}`" class="px-4 py-2 text-xs border-r border-gray-100 whitespace-nowrap dark:text-gray-300">
                      {{ formatCellValue(row[column]) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="tableDetailsModal.total > tableDetailsModal.limit" class="mt-4 flex justify-between items-center">
              <div class="text-sm text-gray-600">
                الصفحة {{ Math.floor((tableDetailsModal.offset || 0) / tableDetailsModal.limit) + 1 }} من {{ Math.ceil(tableDetailsModal.total / tableDetailsModal.limit) }}
              </div>
              <div class="flex gap-2">
                <button
                  @click="loadTableDetails(tableDetailsModal.tableName, 0)"
                  :disabled="tableDetailsModal.offset === 0"
                  class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                  title="الصفحة الأولى"
                >
                  ⏮️ الأولى
                </button>
                <button
                  @click="loadTableDetails(tableDetailsModal.tableName, Math.max(0, tableDetailsModal.offset - tableDetailsModal.limit))"
                  :disabled="tableDetailsModal.offset === 0"
                  class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                  title="السابقة"
                >
                  ⬅️ السابقة
                </button>
                <span class="px-4 py-1 text-sm">
                  {{ Math.floor((tableDetailsModal.offset || 0) / tableDetailsModal.limit) + 1 }} / {{ Math.ceil(tableDetailsModal.total / tableDetailsModal.limit) }}
                </span>
                <button
                  @click="loadTableDetails(tableDetailsModal.tableName, tableDetailsModal.offset + tableDetailsModal.limit)"
                  :disabled="tableDetailsModal.offset + tableDetailsModal.limit >= tableDetailsModal.total"
                  class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                  title="التالية"
                >
                  التالية ➡️
                </button>
                <button
                  @click="loadTableDetails(tableDetailsModal.tableName, Math.floor((tableDetailsModal.total - 1) / tableDetailsModal.limit) * tableDetailsModal.limit)"
                  :disabled="tableDetailsModal.offset + tableDetailsModal.limit >= tableDetailsModal.total"
                  class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                  title="الصفحة الأخيرة"
                >
                  الأخيرة ⏭️
                </button>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-8 text-gray-500">
            <div class="text-5xl mb-2">⚠️</div>
            <p>لا يمكن عرض البيانات</p>
            <p class="text-xs mt-2">الأعمدة: {{ tableDetailsModal.columns.length }}, البيانات: {{ tableDetailsModal.data.length }}</p>
          </div>
        </div>
        <div class="mt-4 flex justify-end">
          <button @click="tableDetailsModal.show = false" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">إغلاق</button>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const toast = useToast();
const props = defineProps({ translations: Object });

// البيانات الأساسية
const isRefreshing = ref(false);
const isSyncing = ref(false);
const connectionStatus = ref({ online: navigator.onLine, syncing: false });
const syncStatus = ref({ pendingCount: 0, lastSync: null });
const activeTab = ref('tables');

// الجداول
const syncedTables = ref([]);
const loadingTables = ref(false);
const selectedDatabase = ref('auto');

// قاعدة البيانات
const databaseInfo = ref({});
const loadingDatabaseInfo = ref(false);

// المزامنة
const syncMetadata = ref({ data: [], stats: null, error: null });
const loadingMetadata = ref(false);


// النسخ الاحتياطية
const backups = ref([]);
const loadingBackups = ref(false);
const restoringBackup = ref(false);

// Modal
const tableDetailsModal = ref({
  show: false,
  tableName: '',
  columns: [],
  data: [],
  total: 0,
  limit: 50,
  offset: 0,
  connection: '',
  error: null
});
const loadingTableDetails = ref(false);

// الوظائف الأساسية
const refreshData = async () => {
  isRefreshing.value = true;
  try {
    connectionStatus.value.online = navigator.onLine;
    await loadSyncedTables();
    await loadDatabaseInfo();
    toast.success('تم تحديث البيانات', { timeout: 2000 });
  } catch (error) {
    toast.error('فشل تحديث البيانات');
  } finally {
    isRefreshing.value = false;
  }
};

const syncAll = async () => {
  if (!connectionStatus.value.online) {
    toast.warning('غير متصل بالإنترنت');
    return;
  }
  isSyncing.value = true;
  try {
    toast.info('🔄 بدء المزامنة...', { timeout: 3000 });
    await refreshData();
    toast.success('✅ تمت المزامنة بنجاح!', { timeout: 3000 });
  } catch (error) {
    toast.error('❌ فشلت المزامنة');
  } finally {
    isSyncing.value = false;
  }
};

// الجداول
const loadSyncedTables = async () => {
  loadingTables.value = true;
  try {
    const params = selectedDatabase.value !== 'auto' ? { force_connection: selectedDatabase.value } : {};
    const response = await axios.get('/api/sync-monitor/tables', { params, withCredentials: true });
    
    if (response.data.success) {
      syncedTables.value = response.data.tables || [];
      console.log('Loaded tables:', syncedTables.value.length, 'tables');
      console.log('Sample table:', syncedTables.value[0]);
      toast.success(`تم تحميل ${syncedTables.value.length} جدول`, { timeout: 2000 });
    } else {
      toast.error(response.data.message || 'فشل تحميل الجداول');
    }
  } catch (error) {
    console.error('Error loading tables:', error);
    toast.error('فشل تحميل الجداول: ' + (error.response?.data?.message || error.message));
  } finally {
    loadingTables.value = false;
  }
};

const viewTableDetails = async (tableName) => {
  tableDetailsModal.value = {
    show: true,
    tableName,
    columns: [],
    data: [],
    total: 0,
    limit: 50,
    offset: 0,
    connection: '',
    error: null
  };
  await loadTableDetails(tableName, 0);
};

const loadTableDetails = async (tableName, offset = 0) => {
  loadingTableDetails.value = true;
  tableDetailsModal.value.error = null;
  try {
    const params = { limit: tableDetailsModal.value.limit, offset };
    if (selectedDatabase.value !== 'auto') params.force_connection = selectedDatabase.value;
    
    const response = await axios.get(`/api/sync-monitor/table/${tableName}`, { params, withCredentials: true });
    
    if (response.data.success) {
      // استخدام البيانات من response.data.table أو response.data مباشرة
      const tableData = response.data.table || response.data;
      
      let columns = [];
      if (Array.isArray(tableData.columns)) {
        columns = tableData.columns;
      } else if (tableData.columns && typeof tableData.columns === 'object') {
        columns = Object.values(tableData.columns);
      }
      
      let data = [];
      if (Array.isArray(tableData.data)) {
        data = tableData.data;
      } else if (tableData.data && typeof tableData.data === 'object') {
        data = Object.values(tableData.data);
      }
      
      // إذا كانت الأعمدة فارغة لكن هناك بيانات، استخرج الأعمدة من أول سطر
      if (columns.length === 0 && data.length > 0 && data[0]) {
        columns = Object.keys(data[0]);
      }
      
      console.log('Table details loaded:', {
        tableName,
        columns: columns.length,
        data: data.length,
        total: tableData.total,
        sampleRow: data[0]
      });
      
      tableDetailsModal.value.columns = columns;
      tableDetailsModal.value.data = data;
      tableDetailsModal.value.total = tableData.total || 0;
      tableDetailsModal.value.offset = tableData.offset || offset;
      tableDetailsModal.value.connection = tableData.connection || '';
    } else {
      tableDetailsModal.value.error = response.data.error || response.data.message || 'فشل تحميل البيانات';
      toast.error('فشل تحميل التفاصيل: ' + tableDetailsModal.value.error);
    }
  } catch (error) {
    console.error('Error loading table details:', error);
    tableDetailsModal.value.error = error.response?.data?.error || error.response?.data?.message || error.message;
    toast.error('فشل تحميل التفاصيل: ' + tableDetailsModal.value.error);
  } finally {
    loadingTableDetails.value = false;
  }
};

const formatCellValue = (value) => {
  if (value === null || value === undefined) return '-';
  if (typeof value === 'boolean') return value ? '✓' : '✗';
  if (typeof value === 'object') return JSON.stringify(value);
  if (typeof value === 'string' && value.length > 50) return value.substring(0, 50) + '...';
  return String(value);
};

// المزامنة
const syncDirection = async (direction) => {
  if (!confirm(`هل تريد المزامنة ${direction === 'up' ? 'من SQLite إلى MySQL' : 'من MySQL إلى SQLite'}?`)) return;
  isSyncing.value = true;
  try {
    const response = await axios.post('/api/sync-monitor/sync', {
      direction,
      tables: null,
      safe_mode: direction === 'up',
      create_backup: direction === 'up'
    }, { withCredentials: true });
    if (response.data.success) {
      toast.success(`✅ تمت المزامنة: ${response.data.results?.total_synced || 0} سجل`);
      await loadSyncMetadata();
      await loadSyncedTables();
    } else {
      toast.error('فشلت المزامنة');
    }
  } catch (error) {
    toast.error('فشلت المزامنة');
  } finally {
    isSyncing.value = false;
  }
};

const syncAllTables = async (direction) => {
  await syncDirection(direction);
};

const loadSyncMetadata = async () => {
  loadingMetadata.value = true;
  try {
    const response = await axios.get('/api/sync-monitor/metadata', { withCredentials: true });
    syncMetadata.value.data = response.data.metadata || [];
    syncMetadata.value.stats = response.data.stats || null;
  } catch (error) {
    syncMetadata.value.error = error.response?.data?.error || error.message;
  } finally {
    loadingMetadata.value = false;
  }
};


// النسخ الاحتياطية
const loadBackups = async () => {
  loadingBackups.value = true;
  try {
    const response = await axios.get('/api/sync-monitor/backups', { withCredentials: true });
    backups.value = response.data.backups || [];
  } catch (error) {
    toast.error('فشل تحميل النسخ الاحتياطية');
  } finally {
    loadingBackups.value = false;
  }
};

const restoreBackup = async (backupName) => {
  if (!confirm(`هل تريد استعادة النسخة الاحتياطية "${backupName}"?`)) return;
  restoringBackup.value = true;
  try {
    const response = await axios.post('/api/sync-monitor/restore-backup', { backup_file: backupName }, { withCredentials: true });
    if (response.data.success) {
      toast.success('✅ تمت الاستعادة بنجاح');
      await loadBackups();
      await loadSyncedTables();
    } else {
      toast.error('فشلت الاستعادة');
    }
  } catch (error) {
    toast.error('فشلت الاستعادة');
  } finally {
    restoringBackup.value = false;
  }
};

const downloadBackup = (backupName) => {
  window.open(`/api/sync-monitor/download-backup?file=${encodeURIComponent(backupName)}`, '_blank');
};

const deleteBackup = async (backupName) => {
  if (!confirm(`هل تريد حذف النسخة الاحتياطية "${backupName}"?`)) return;
  try {
    const response = await axios.delete('/api/sync-monitor/backup/delete', {
      params: { file: backupName },
      withCredentials: true
    });
    if (response.data.success) {
      toast.success('✅ تم الحذف');
      await loadBackups();
    } else {
      toast.error('فشل الحذف');
    }
  } catch (error) {
    toast.error('فشل الحذف');
  }
};

// معلومات قاعدة البيانات
const loadDatabaseInfo = async () => {
  loadingDatabaseInfo.value = true;
  try {
    const response = await axios.get('/api/sync-monitor/tables', { withCredentials: true });
    const tables = response.data.tables || [];
    databaseInfo.value = {
      type: 'SQLite',
      total_tables: tables.length,
      size: 'غير محدد'
    };
  } catch (error) {
    databaseInfo.value = { type: 'SQLite', total_tables: syncedTables.value.length, size: 'غير محدد' };
  } finally {
    loadingDatabaseInfo.value = false;
  }
};

// مساعدات
const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleString('ar-EG');
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const getLogIcon = (type) => {
  const icons = { info: 'ℹ️', success: '✅', warning: '⚠️', error: '❌' };
  return icons[type] || '📝';
};

const getLogClass = (type) => {
  const classes = {
    info: 'bg-blue-100 text-blue-800',
    success: 'bg-green-100 text-green-800',
    warning: 'bg-yellow-100 text-yellow-800',
    error: 'bg-red-100 text-red-800'
  };
  return classes[type] || 'bg-gray-100 text-gray-800';
};

// Event Listeners
const handleOnline = () => {
  connectionStatus.value.online = true;
  toast.success('🌐 عاد الاتصال!');
  refreshData();
};

const handleOffline = () => {
  connectionStatus.value.online = false;
  toast.warning('📴 فقدان الاتصال');
};

onMounted(() => {
  refreshData();
  loadSyncedTables();
  loadBackups();
  loadSyncMetadata();
  loadDatabaseInfo();
  window.addEventListener('online', handleOnline);
  window.addEventListener('offline', handleOffline);
});

onUnmounted(() => {
  window.removeEventListener('online', handleOnline);
  window.removeEventListener('offline', handleOffline);
});
</script>

<style scoped>
.animate-spin {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
