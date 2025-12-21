<template>
  <AuthenticatedLayout :translations="translations">
    <Head title="مراقبة المزامنة" />

    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl dark:text-gray-50 text-gray-800">
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
              <h3 class="text-lg font-semibold dark:text-gray-50">🗄️ قاعدة البيانات والمزامنة</h3>
            <button
              @click="loadAllData" 
              :disabled="isRefreshing"
              class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50"
            >
              <span v-if="!isRefreshing">🔄 تحديث</span>
              <span v-else>⏳ جاري...</span>
            </button>
          </div>
          <div v-if="isRefreshing" class="text-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
          </div>
          <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
              <h4 class="text-md font-semibold mb-3 dark:text-gray-100">📊 قاعدة البيانات</h4>
              <div class="space-y-2 text-sm text-gray-700 dark:text-gray-100">
                <div class="flex justify-between">
                  <span>النوع:</span>
                  <span class="font-mono text-gray-900 dark:text-gray-100">{{ databaseInfo.type || 'SQLite' }}</span>
                </div>
                <div class="flex justify-between">
                  <span>الجداول:</span>
                  <span class="font-mono text-gray-900 dark:text-gray-100">{{ databaseInfo.total_tables || syncedTables.length }}</span>
                </div>
              </div>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
              <h4 class="text-md font-semibold mb-3 dark:text-blue-100">🔄 المزامنة</h4>
              <div class="space-y-2 text-sm text-blue-900 dark:text-blue-100">
                <div class="flex justify-between">
                  <span>في الانتظار:</span>
                  <span class="font-bold text-blue-950 dark:text-blue-50">{{ syncStatus.pendingCount }}</span>
                </div>
                <div class="flex justify-between">
                  <span>الحالة:</span>
                  <span :class="connectionStatus.online ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                    {{ connectionStatus.online ? 'متصل' : 'غير متصل' }}
                  </span>
                </div>
              </div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg">
              <h4 class="text-md font-semibold mb-3 dark:text-purple-100">⚡ إجراءات سريعة</h4>
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
              <p class="text-lg font-medium text-yellow-800 dark:text-yellow-100">أنت حالياً في وضع Offline</p>
              <p class="text-sm text-yellow-700 dark:text-yellow-200">التغييرات سيتم حفظها محلياً ومزامنتها تلقائياً عند عودة الاتصال</p>
            </div>
          </div>
        </div>

        <!-- تبويبات -->
        <div class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
          <div class="border-b border-gray-200 dark:border-gray-700">
            <div class="flex">
              <button
                @click="activeTab = 'tables'"
                :class="['px-6 py-3 text-sm font-medium border-b-2 transition-colors', activeTab === 'tables' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400']"
              >
                📋 الجداول
              </button>
              <button
                @click="activeTab = 'sync'"
                :class="['px-6 py-3 text-sm font-medium border-b-2 transition-colors', activeTab === 'sync' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-gray-400']"
              >
                🔄 المزامنة
              </button>
              <button
                @click="activeTab = 'backups'"
                :class="['px-6 py-3 text-sm font-medium border-b-2 transition-colors', activeTab === 'backups' ? 'border-yellow-500 text-yellow-600 dark:text-yellow-400' : 'border-transparent text-gray-500 dark:text-gray-400']"
              >
                💾 النسخ الاحتياطية
              </button>
            </div>
          </div>

          <!-- تبويب الجداول -->
          <div v-if="activeTab === 'tables'" class="p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold dark:text-gray-50">📋 الجداول المزامنة ({{ syncedTables.length }})</h3>
              <div class="flex gap-2">
                <select
                  v-model="selectedDatabase"
                  @change="loadAllData"
                  class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-gray-200 text-sm"
                >
                  <option value="auto">🔄 تلقائي</option>
                  <option value="mysql">☁️ MySQL</option>
                  <option value="sync_sqlite">🖥️ SQLite</option>
                </select>
                <button @click="loadAllData" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm" :disabled="isRefreshing">
                  <span v-if="!isRefreshing">🔄 تحديث</span>
                  <span v-else>⏳ جاري...</span>
                </button>
              </div>
            </div>
            <div v-if="isRefreshing" class="text-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 dark:border-blue-400 mx-auto"></div>
            </div>
            <div v-else class="overflow-x-auto">
              <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-500">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b-2 border-gray-300 dark:border-gray-500">
                  <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-50 uppercase border-r border-gray-300 dark:border-gray-500">اسم الجدول</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-50 uppercase border-r border-gray-300 dark:border-gray-500">عدد السجلات</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-50 uppercase border-r border-gray-300 dark:border-gray-500">الاتصال</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-50 uppercase">الإجراءات</th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800">
                  <tr v-for="table in syncedTables" :key="table.name" class="hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 border-b border-gray-200 dark:border-gray-500">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-50 border-r border-gray-200 dark:border-gray-500">{{ table.name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-50 border-r border-gray-200 dark:border-gray-500">{{ (table.rows || table.count || 0).toLocaleString() }}</td>
                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200 dark:border-gray-500">
                      <span :class="table.connection === 'sync_sqlite' ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' : 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'" class="px-2 py-1 text-xs rounded-full">
                        {{ table.connection === 'sync_sqlite' ? 'SQLite' : 'MySQL' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <button @click="viewTableDetails(table.name)" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 font-medium">عرض التفاصيل</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- تبويب المزامنة -->
          <div v-if="activeTab === 'sync'" class="p-6">
            <div class="mb-4">
              <h3 class="text-lg font-semibold mb-4 dark:text-gray-50">🔄 عمليات المزامنة</h3>
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
              <h4 class="text-md font-semibold mb-2 dark:text-gray-50">📊 بيانات المزامنة</h4>
              <button @click="loadAllData" :disabled="isRefreshing" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm mb-4">
                <span v-if="!isRefreshing">🔄 تحديث Metadata</span>
                <span v-else>⏳ جاري...</span>
              </button>
              <div v-if="syncMetadata.data.length > 0" class="overflow-x-auto">
                <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-500">
                  <thead class="bg-gray-50 dark:bg-gray-700 border-b-2 border-gray-300 dark:border-gray-500">
                    <tr>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-50 uppercase border-r border-gray-300 dark:border-gray-500">اسم الجدول</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-50 uppercase border-r border-gray-300 dark:border-gray-500">الاتجاه</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-50 uppercase border-r border-gray-300 dark:border-gray-500">آخر ID</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-50 uppercase">إجمالي المزامن</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white dark:bg-gray-800">
                    <tr v-for="item in syncMetadata.data" :key="`${item.table_name}-${item.direction}`" class="hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 border-b border-gray-200 dark:border-gray-500">
                      <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-50 font-medium border-r border-gray-200 dark:border-gray-500">{{ item.table_name }}</td>
                      <td class="px-6 py-4 border-r border-gray-200 dark:border-gray-500">
                        <span :class="item.direction === 'down' ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' : 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'" class="px-2 py-1 text-xs rounded-full font-medium">
                          {{ item.direction === 'down' ? '↓' : '↑' }}
                        </span>
                      </td>
                      <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-50 border-r border-gray-200 dark:border-gray-500">{{ item.last_synced_id?.toLocaleString() || 0 }}</td>
                      <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-50">{{ item.total_synced?.toLocaleString() || 0 }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>


          <!-- تبويب النسخ الاحتياطية -->
          <div v-if="activeTab === 'backups'" class="p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold dark:text-gray-50">💾 النسخ الاحتياطية</h3>
              <div class="flex gap-2">
                <button 
                  @click="createBackup" 
                  :disabled="creatingBackup || isRefreshing" 
                  class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 text-sm disabled:opacity-50"
                >
                  <span v-if="!creatingBackup">➕ إنشاء نسخة احتياطية</span>
                  <span v-else>⏳ جاري الإنشاء...</span>
                </button>
                <button 
                  @click="loadAllData" 
                  :disabled="isRefreshing || creatingBackup" 
                  class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm disabled:opacity-50"
                >
                  <span v-if="!isRefreshing">🔄 تحديث</span>
                <span v-else>⏳ جاري...</span>
              </button>
            </div>
            </div>

            <!-- معلومات النسخ الاحتياطية -->
            <div class="mb-4 bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
              <div class="flex items-center gap-2 text-sm text-blue-900 dark:text-blue-100">
                <span class="text-lg">📊</span>
                <span>إجمالي النسخ الاحتياطية: <strong class="text-blue-950 dark:text-blue-50">{{ backups.length }}</strong></span>
                <span class="mx-2 text-blue-700 dark:text-blue-300">|</span>
                <span>إجمالي الحجم: <strong class="text-blue-950 dark:text-blue-50">{{ totalBackupSize }}</strong></span>
              </div>
            </div>

            <div v-if="isRefreshing" class="text-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 dark:border-blue-400 mx-auto"></div>
            </div>
            <div v-else-if="backups.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-300">
              <div class="text-5xl mb-2">💾</div>
              <p class="text-lg dark:text-gray-50 font-medium">لا توجد نسخ احتياطية</p>
              <p class="text-sm mt-2 dark:text-gray-100">انقر على "إنشاء نسخة احتياطية" لإنشاء أول نسخة</p>
            </div>
            <div v-else class="overflow-x-auto">
              <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-500">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b-2 border-gray-300 dark:border-gray-500">
                  <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-50 uppercase border-r border-gray-300 dark:border-gray-500">اسم الملف</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-50 uppercase border-r border-gray-300 dark:border-gray-500">الحجم</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-50 uppercase border-r border-gray-300 dark:border-gray-500">تاريخ الإنشاء</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-50 uppercase">الإجراءات</th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800">
                  <tr v-for="backup in backups" :key="backup.name" class="hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 border-b border-gray-200 dark:border-gray-500">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-50 border-r border-gray-200 dark:border-gray-500">
                      <div class="flex items-center gap-2">
                        <span>📄</span>
                        <span>{{ backup.name }}</span>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-50 border-r border-gray-200 dark:border-gray-500">
                      {{ backup.size_formatted || formatFileSize(backup.size) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-50 border-r border-gray-200 dark:border-gray-500">
                      {{ formatDate(backup.created_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <div class="flex gap-2">
                        <button 
                          @click="showRestoreModal(backup)" 
                          :disabled="restoringBackup" 
                          class="px-3 py-1 bg-green-500 dark:bg-green-600 text-white rounded hover:bg-green-600 dark:hover:bg-green-700 text-xs disabled:opacity-50 font-medium"
                          title="استعادة النسخة الاحتياطية"
                        >
                        🔄 استعادة
                      </button>
                        <button 
                          @click="downloadBackup(backup.path || backup.name)" 
                          class="px-3 py-1 bg-blue-500 dark:bg-blue-600 text-white rounded hover:bg-blue-600 dark:hover:bg-blue-700 text-xs font-medium"
                          title="تحميل النسخة الاحتياطية"
                        >
                          📥 تحميل
                        </button>
                        <button 
                          @click="deleteBackup(backup.path || backup.name)" 
                          class="px-3 py-1 bg-red-500 dark:bg-red-600 text-white rounded hover:bg-red-600 dark:hover:bg-red-700 text-xs font-medium"
                          title="حذف النسخة الاحتياطية"
                        >
                          🗑️ حذف
                        </button>
                      </div>
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
      <div class="p-6 dark:bg-gray-800">
        <h3 class="text-lg font-semibold mb-4 dark:text-gray-50">📋 تفاصيل الجدول: {{ tableDetailsModal.tableName }}</h3>
        <div v-if="loadingTableDetails" class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 dark:border-blue-400 mx-auto"></div>
        </div>
        <div v-else-if="tableDetailsModal.error" class="bg-red-50 dark:bg-red-900 border-l-4 border-red-500 p-4 mb-4">
          <p class="text-red-800 dark:text-red-200">{{ tableDetailsModal.error }}</p>
        </div>
        <div v-else>
          <div class="mb-4 flex justify-between items-center">
            <div class="text-sm text-gray-600 dark:text-gray-200">
              إجمالي السجلات: <span class="font-semibold text-gray-900 dark:text-gray-50">{{ tableDetailsModal.total?.toLocaleString() || 0 }}</span>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-200">
              الاتصال: <span class="font-semibold text-gray-900 dark:text-gray-50">{{ tableDetailsModal.connection || '-' }}</span>
            </div>
          </div>
          
          <div v-if="tableDetailsModal.columns.length === 0 && tableDetailsModal.data.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-300">
            <div class="text-5xl mb-2">📋</div>
            <p class="dark:text-gray-50">لا توجد بيانات في هذا الجدول</p>
            <p class="text-xs mt-2 dark:text-gray-100">الجدول فارغ أو لا يحتوي على سجلات</p>
          </div>
          
          <div v-else-if="tableDetailsModal.columns.length > 0">
            <!-- معلومات الصفحة -->
            <div class="mb-3 flex justify-between items-center text-sm text-gray-600 dark:text-gray-200">
              <div class="dark:text-gray-100">
                عرض {{ (tableDetailsModal.offset || 0) + 1 }} إلى {{ Math.min((tableDetailsModal.offset || 0) + tableDetailsModal.limit, tableDetailsModal.total) }} من {{ tableDetailsModal.total?.toLocaleString() || 0 }} سجل
              </div>
              <div class="flex items-center gap-2">
                <span class="dark:text-gray-100">عدد السجلات في الصفحة:</span>
                <select 
                  v-model="tableDetailsModal.limit" 
                  @change="loadTableDetails(tableDetailsModal.tableName, 0)"
                  class="px-2 py-1 border dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-gray-200"
                >
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                  <option :value="200">200</option>
                  <option :value="500">500</option>
                </select>
              </div>
            </div>
            
            <!-- الجدول -->
            <div class="overflow-x-auto max-h-96 border dark:border-gray-500 rounded">
              <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-500 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 border-b-2 border-gray-300 dark:border-gray-500">
                  <tr>
                    <th v-for="column in tableDetailsModal.columns" :key="column" class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 uppercase border-r border-gray-300 dark:border-gray-500">
                      {{ column }}
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800">
                  <tr v-if="tableDetailsModal.data.length === 0">
                    <td :colspan="tableDetailsModal.columns.length || 1" class="px-4 py-8 text-center text-gray-500 dark:text-gray-200">
                      لا توجد بيانات للعرض
                    </td>
                  </tr>
                  <tr v-for="(row, index) in tableDetailsModal.data" :key="index" class="hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 border-b border-gray-200 dark:border-gray-500">
                    <td v-for="column in tableDetailsModal.columns" :key="`${index}-${column}`" class="px-4 py-2 text-xs border-r border-gray-200 dark:border-gray-500 whitespace-nowrap text-gray-700 dark:text-gray-50">
                      {{ formatCellValue(row[column]) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="tableDetailsModal.total > tableDetailsModal.limit" class="mt-4 flex justify-between items-center">
              <div class="text-sm text-gray-600 dark:text-gray-200">
                الصفحة {{ Math.floor((tableDetailsModal.offset || 0) / tableDetailsModal.limit) + 1 }} من {{ Math.ceil(tableDetailsModal.total / tableDetailsModal.limit) }}
              </div>
              <div class="flex gap-2">
                <button
                  @click="loadTableDetails(tableDetailsModal.tableName, 0)"
                  :disabled="tableDetailsModal.offset === 0"
                  class="px-3 py-1 border dark:border-gray-600 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-100"
                  title="الصفحة الأولى"
                >
                  ⏮️ الأولى
                </button>
                <button
                  @click="loadTableDetails(tableDetailsModal.tableName, Math.max(0, tableDetailsModal.offset - tableDetailsModal.limit))"
                  :disabled="tableDetailsModal.offset === 0"
                  class="px-3 py-1 border dark:border-gray-600 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-100"
                  title="السابقة"
                >
                  ⬅️ السابقة
                </button>
                <span class="px-4 py-1 text-sm text-gray-700 dark:text-gray-100">
                  {{ Math.floor((tableDetailsModal.offset || 0) / tableDetailsModal.limit) + 1 }} / {{ Math.ceil(tableDetailsModal.total / tableDetailsModal.limit) }}
                </span>
                <button
                  @click="loadTableDetails(tableDetailsModal.tableName, tableDetailsModal.offset + tableDetailsModal.limit)"
                  :disabled="tableDetailsModal.offset + tableDetailsModal.limit >= tableDetailsModal.total"
                  class="px-3 py-1 border dark:border-gray-600 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-100"
                  title="التالية"
                >
                  التالية ➡️
                </button>
                <button
                  @click="loadTableDetails(tableDetailsModal.tableName, Math.floor((tableDetailsModal.total - 1) / tableDetailsModal.limit) * tableDetailsModal.limit)"
                  :disabled="tableDetailsModal.offset + tableDetailsModal.limit >= tableDetailsModal.total"
                  class="px-3 py-1 border dark:border-gray-600 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-100"
                  title="الصفحة الأخيرة"
                >
                  الأخيرة ⏭️
                </button>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-8 text-gray-500 dark:text-gray-300">
            <div class="text-5xl mb-2">⚠️</div>
            <p class="dark:text-gray-50">لا يمكن عرض البيانات</p>
            <p class="text-xs mt-2 dark:text-gray-200">الأعمدة: {{ tableDetailsModal.columns.length }}, البيانات: {{ tableDetailsModal.data.length }}</p>
          </div>
        </div>
        <div class="mt-4 flex justify-end">
          <button @click="tableDetailsModal.show = false" class="px-4 py-2 bg-gray-500 dark:bg-gray-600 text-white rounded hover:bg-gray-600 dark:hover:bg-gray-700">إغلاق</button>
        </div>
      </div>
    </Modal>

    <!-- Modal استعادة النسخة الاحتياطية -->
    <Modal :show="restoreModal.show" @close="restoreModal.show = false">
      <div class="p-6 dark:bg-gray-800">
        <h3 class="text-lg font-semibold mb-4 dark:text-gray-50">🔄 استعادة النسخة الاحتياطية</h3>
        
        <div class="mb-4">
          <p class="text-sm text-gray-700 dark:text-gray-200 mb-2">
            <strong class="text-gray-900 dark:text-gray-50">الملف:</strong> <span class="text-gray-600 dark:text-gray-200">{{ restoreModal.backup?.name }}</span>
          </p>
          <p class="text-sm text-gray-700 dark:text-gray-200">
            <strong class="text-gray-900 dark:text-gray-50">الحجم:</strong> <span class="text-gray-600 dark:text-gray-200">{{ restoreModal.backup?.size_formatted || formatFileSize(restoreModal.backup?.size) }}</span>
          </p>
          <p class="text-sm text-gray-700 dark:text-gray-200">
            <strong class="text-gray-900 dark:text-gray-50">التاريخ:</strong> <span class="text-gray-600 dark:text-gray-200">{{ formatDate(restoreModal.backup?.created_at) }}</span>
          </p>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium mb-2 dark:text-gray-50">نوع الاستعادة:</label>
          <div class="space-y-2">
            <label class="flex items-center text-gray-700 dark:text-gray-100">
              <input 
                type="radio" 
                v-model="restoreModal.restoreType" 
                value="full" 
                class="ml-2"
              />
              <span>استعادة كاملة (جميع الجداول)</span>
            </label>
            <label class="flex items-center text-gray-700 dark:text-gray-100">
              <input 
                type="radio" 
                v-model="restoreModal.restoreType" 
                value="selected" 
                class="ml-2"
              />
              <span>استعادة جداول محددة</span>
            </label>
          </div>
        </div>

        <div v-if="restoreModal.restoreType === 'selected'" class="mb-4">
          <label class="block text-sm font-medium mb-2 dark:text-gray-50">اختر الجداول:</label>
          <div class="max-h-60 overflow-y-auto border dark:border-gray-600 rounded p-2 bg-white dark:bg-gray-700">
            <div v-for="table in syncedTables" :key="table.name" class="mb-2">
              <label class="flex items-center text-gray-700 dark:text-gray-100">
                <input 
                  type="checkbox" 
                  :value="table.name"
                  v-model="restoreModal.selectedTables"
                  class="ml-2"
                />
                <span class="text-sm dark:text-gray-100">{{ table.name }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-300 mr-2">({{ (table.rows || table.count || 0).toLocaleString() }} سجل)</span>
              </label>
            </div>
          </div>
          <p v-if="restoreModal.selectedTables.length > 0" class="text-sm text-green-600 dark:text-green-400 mt-2">
            تم اختيار {{ restoreModal.selectedTables.length }} جدول
          </p>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900 border-l-4 border-yellow-500 p-4 mb-4 rounded">
          <p class="text-sm text-yellow-800 dark:text-yellow-200">
            ⚠️ <strong>تحذير:</strong> استعادة النسخة الاحتياطية سيستبدل البيانات الحالية في الجداول المحددة. تأكد من أن لديك نسخة احتياطية حديثة قبل المتابعة.
          </p>
        </div>

        <div class="flex justify-end gap-2">
          <button 
            @click="restoreModal.show = false" 
            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
          >
            إلغاء
          </button>
          <button 
            @click="restoreBackup" 
            :disabled="restoringBackup || (restoreModal.restoreType === 'selected' && restoreModal.selectedTables.length === 0)"
            class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 disabled:opacity-50"
          >
            <span v-if="!restoringBackup">✅ استعادة</span>
            <span v-else>⏳ جاري الاستعادة...</span>
          </button>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
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
const selectedDatabase = ref('auto');

// قاعدة البيانات
const databaseInfo = ref({});

// المزامنة
const syncMetadata = ref({ data: [], stats: null, error: null });

// النسخ الاحتياطية
const backups = ref([]);
const restoringBackup = ref(false);
const creatingBackup = ref(false);
const restoreModal = ref({
  show: false,
  backup: null,
  selectedTables: [],
  restoreType: 'full' // 'full' or 'selected'
});

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

// الوظائف الأساسية - جلب جميع البيانات في request واحد
const loadAllData = async () => {
  isRefreshing.value = true;
  try {
    connectionStatus.value.online = navigator.onLine;
    
    const response = await axios.get('/api/sync-monitor/all-data', { 
      params: { force_connection: selectedDatabase.value !== 'auto' ? selectedDatabase.value : 'auto' },
      withCredentials: true 
    });
    
    if (response.data.success) {
      // تحديث الجداول
      syncedTables.value = response.data.tables || [];
      
      // تحديث metadata
      syncMetadata.value.data = response.data.metadata || [];
      syncMetadata.value.stats = response.data.queue_stats || null;
      
      // تحديث النسخ الاحتياطية
      backups.value = response.data.backups || [];
      
      // تحديث معلومات قاعدة البيانات
      databaseInfo.value = response.data.database_info || {
        type: 'MySQL',
        total_tables: syncedTables.value.length,
      };
      
      // تحديث حالة المزامنة
      syncStatus.value.pendingCount = response.data.queue_stats?.pending || 0;
    } else {
      toast.error(response.data.message || 'فشل تحميل البيانات');
    }
  } catch (error) {
    console.error('Error loading all data:', error);
    toast.error('فشل تحميل البيانات: ' + (error.response?.data?.message || error.message));
  } finally {
    isRefreshing.value = false;
  }
};

const refreshData = async () => {
  await loadAllData();
  toast.success('تم تحديث البيانات', { timeout: 2000 });
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

// الجداول (للحفاظ على التوافق - لكن loadAllData يستخدم الآن)
const loadSyncedTables = async () => {
  await loadAllData();
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
      await loadAllData();
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
  await loadAllData();
};


// النسخ الاحتياطية (للحفاظ على التوافق - لكن loadAllData يستخدم الآن)
const loadBackups = async () => {
  await loadAllData();
};

const createBackup = async () => {
  if (!confirm('هل تريد إنشاء نسخة احتياطية جديدة من قاعدة البيانات؟\n\nملاحظة: قد يستغرق هذا بعض الوقت حسب حجم قاعدة البيانات.')) {
    return;
  }
  
  creatingBackup.value = true;
  try {
    toast.info('🔄 جاري إنشاء النسخة الاحتياطية...', { timeout: 5000 });
    const response = await axios.post('/api/sync-monitor/backup/create', {}, { withCredentials: true });
    
    if (response.data.success) {
      toast.success('✅ تم إنشاء النسخة الاحتياطية بنجاح: ' + response.data.backup_name, { timeout: 5000 });
      await loadAllData();
    } else {
      toast.error(response.data.message || 'فشل إنشاء النسخة الاحتياطية');
    }
  } catch (error) {
    console.error('Error creating backup:', error);
    toast.error('فشل إنشاء النسخة الاحتياطية: ' + (error.response?.data?.message || error.message));
  } finally {
    creatingBackup.value = false;
  }
};

const showRestoreModal = (backup) => {
  restoreModal.value = {
    show: true,
    backup: backup,
    selectedTables: [],
    restoreType: 'full'
  };
};

const restoreBackup = async () => {
  if (!restoreModal.value.backup) return;
  
  const backupFile = restoreModal.value.backup.path || restoreModal.value.backup.name;
  const confirmMessage = restoreModal.value.restoreType === 'full' 
    ? `هل تريد استعادة النسخة الاحتياطية "${restoreModal.value.backup.name}" بالكامل؟\n\n⚠️ تحذير: سيتم استبدال جميع البيانات الحالية!`
    : `هل تريد استعادة الجداول المحددة من النسخة الاحتياطية "${restoreModal.value.backup.name}"?`;
  
  if (!confirm(confirmMessage)) return;
  
  restoringBackup.value = true;
  try {
    const requestData = {
      backup_file: backupFile
    };
    
    if (restoreModal.value.restoreType === 'selected' && restoreModal.value.selectedTables.length > 0) {
      requestData.tables = restoreModal.value.selectedTables;
    }
    
    toast.info('🔄 جاري استعادة النسخة الاحتياطية...', { timeout: 10000 });
    const response = await axios.post('/api/sync-monitor/backup/restore', requestData, { withCredentials: true });
    
    if (response.data.success) {
      toast.success('✅ تمت استعادة النسخة الاحتياطية بنجاح', { timeout: 5000 });
      restoreModal.value.show = false;
      await loadAllData();
    } else {
      toast.error(response.data.message || 'فشلت الاستعادة');
    }
  } catch (error) {
    console.error('Error restoring backup:', error);
    toast.error('فشلت الاستعادة: ' + (error.response?.data?.message || error.message));
  } finally {
    restoringBackup.value = false;
  }
};

const downloadBackup = async (backupPath) => {
  try {
    const response = await axios.get('/api/sync-monitor/backup/download', {
      params: { backup_file: backupPath },
      responseType: 'blob',
      withCredentials: true
    });
    
    // إنشاء رابط تحميل
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', backupPath.split('/').pop() || 'backup.sql');
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    
    toast.success('✅ تم بدء تحميل النسخة الاحتياطية', { timeout: 3000 });
  } catch (error) {
    console.error('Error downloading backup:', error);
    toast.error('فشل تحميل النسخة الاحتياطية: ' + (error.response?.data?.message || error.message));
  }
};

const deleteBackup = async (backupPath) => {
  const backupName = backupPath.split('/').pop() || backupPath;
  if (!confirm(`هل تريد حذف النسخة الاحتياطية "${backupName}"؟\n\n⚠️ تحذير: لا يمكن التراجع عن هذا الإجراء!`)) {
    return;
  }
  
  try {
    const response = await axios.delete('/api/sync-monitor/backup/delete', {
      params: { backup_file: backupPath },
      withCredentials: true
    });
    
    if (response.data.success) {
      toast.success('✅ تم حذف النسخة الاحتياطية بنجاح', { timeout: 3000 });
      await loadAllData();
    } else {
      toast.error(response.data.message || 'فشل الحذف');
    }
  } catch (error) {
    console.error('Error deleting backup:', error);
    toast.error('فشل حذف النسخة الاحتياطية: ' + (error.response?.data?.message || error.message));
  }
};

// حساب إجمالي حجم النسخ الاحتياطية
const totalBackupSize = computed(() => {
  const total = backups.value.reduce((sum, backup) => sum + (backup.size || 0), 0);
  return formatFileSize(total);
});

// معلومات قاعدة البيانات (للحفاظ على التوافق - لكن loadAllData يستخدم الآن)
const loadDatabaseInfo = async () => {
  await loadAllData();
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
  loadAllData();
};

const handleOffline = () => {
  connectionStatus.value.online = false;
  toast.warning('📴 فقدان الاتصال');
};

onMounted(() => {
  // جلب جميع البيانات في request واحد فقط
  loadAllData();
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

/* Fix dark mode text colors */
  
</style>
