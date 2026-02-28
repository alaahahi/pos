<template>
    <Head title="مراقبة المزامنة" />

    <template>
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
            v-if="connectionStatus.online"
            @click="syncAll"
            class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
            :disabled="isSyncing"
          >
            <span v-if="!isSyncing">✅ مزامنة الكل (سحب + رفع)</span>
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
                  <span>الإنترنت:</span>
                  <span :class="connectionStatus.online ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                    {{ connectionStatus.online ? '✅ متصل' : '❌ منقطع' }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span>السيرفر:</span>
                  <span :class="connectionStatus.serverAvailable ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                    {{ connectionStatus.serverAvailable ? '✅ متاح' : connectionStatus.serverChecked ? '❌ غير متاح' : '⏳ لم يفحص' }}
                  </span>
                </div>
              </div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg">
              <h4 class="text-md font-semibold mb-3 dark:text-purple-100">⚡ إجراءات سريعة</h4>
              <div class="space-y-2">
                <button
                  @click="checkRemoteConnection"
                  :disabled="isCheckingConnection"
                  class="w-full px-3 py-2 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 disabled:opacity-50"
                >
                  <span v-if="!isCheckingConnection">🌐 فحص الاتصال</span>
                  <span v-else>⏳ جاري الفحص...</span>
                </button>
                <button
                  @click="syncAllTablesFromServer"
                  :disabled="!connectionStatus.online || !connectionStatus.serverAvailable || isSyncing"
                  class="w-full px-3 py-2 bg-purple-600 text-white text-xs rounded hover:bg-purple-700 disabled:opacity-50"
                  :title="!connectionStatus.serverAvailable ? 'السيرفر غير متاح' : 'مزامنة كل الجداول من السيرفر'"
                >
                  <span v-if="!isSyncing">📥 مزامنة من السيرفر</span>
                  <span v-else>⏳ جاري المزامنة...</span>
                </button>
                <button
                  @click="syncAll"
                  :disabled="!connectionStatus.online || !connectionStatus.serverAvailable || isSyncing"
                  class="w-full px-3 py-2 bg-green-600 text-white text-xs rounded hover:bg-green-700 disabled:opacity-50"
                  :title="!connectionStatus.serverAvailable ? 'السيرفر غير متاح' : ''"
                >
                  🔄 مزامنة الكل (سحب + رفع)
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
                @click="activeTab = 'health'"
                :class="['px-6 py-3 text-sm font-medium border-b-2 transition-colors', activeTab === 'health' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 dark:text-gray-400']"
              >
                🔍 فحص المزامنة
              </button>
              <button
                @click="activeTab = 'backups'"
                :class="['px-6 py-3 text-sm font-medium border-b-2 transition-colors', activeTab === 'backups' ? 'border-yellow-500 text-yellow-600 dark:text-yellow-400' : 'border-transparent text-gray-500 dark:text-gray-400']"
              >
                💾 النسخ الاحتياطية
              </button>
              <button
                @click="activeTab = 'migrations'"
                :class="['px-6 py-3 text-sm font-medium border-b-2 transition-colors', activeTab === 'migrations' ? 'border-orange-500 text-orange-600 dark:text-orange-400' : 'border-transparent text-gray-500 dark:text-gray-400']"
              >
                📦 Migrations
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
                  <option value="mysql">☁️ MySQL (سيرفر)</option>
                  <option value="sync_sqlite">🖥️ SQLite (محلي)</option>
                </select>
                <button @click="loadServerTables" class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 text-sm" :disabled="isRefreshing" title="عرض جداول السيرفر">
                  <span v-if="!isRefreshing">☁️ جداول السيرفر</span>
                  <span v-else>⏳ جاري...</span>
                </button>
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

          <!-- تبويب فحص المزامنة -->
          <div v-if="activeTab === 'health'" class="p-6">
            <div class="mb-6">
              <h3 class="text-lg font-semibold mb-4 dark:text-gray-50">🔍 فحص حالة المزامنة</h3>
              
              <!-- ملخص الإحصائيات السريع -->
              <div v-if="syncMetadata.stats" class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <h4 class="text-md font-semibold mb-4 dark:text-gray-50">📊 ملخص إحصائيات المزامنة</h4>
                <div class="grid grid-cols-3 gap-4">
                  <div class="p-4 bg-blue-50 dark:bg-blue-900 rounded-lg border-2 border-blue-300 dark:border-blue-700">
                    <div class="flex justify-between items-center mb-2">
                      <span class="text-sm text-blue-700 dark:text-blue-300">في الانتظار</span>
                      <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ syncMetadata.stats.pending || 0 }}</span>
                    </div>
                    <button @click="loadSyncQueueDetails('pending')" class="w-full mt-2 px-3 py-2 bg-blue-500 text-white text-sm rounded hover:bg-blue-600">
                      📋 عرض التفاصيل
                    </button>
                  </div>
                  <div class="p-4 bg-green-50 dark:bg-green-900 rounded-lg border-2 border-green-300 dark:border-green-700">
                    <div class="flex justify-between items-center mb-2">
                      <span class="text-sm text-green-700 dark:text-green-300">تمت المزامنة</span>
                      <span class="text-2xl font-bold text-green-600 dark:text-green-400">{{ syncMetadata.stats.synced || 0 }}</span>
                    </div>
                    <button @click="loadSyncQueueDetails('synced')" class="w-full mt-2 px-3 py-2 bg-green-500 text-white text-sm rounded hover:bg-green-600">
                      ✅ عرض التفاصيل
                    </button>
                  </div>
                  <div class="p-4 bg-red-50 dark:bg-red-900 rounded-lg border-2 border-red-300 dark:border-red-700">
                    <div class="flex justify-between items-center mb-2">
                      <span class="text-sm text-red-700 dark:text-red-300">فاشلة</span>
                      <span class="text-2xl font-bold text-red-600 dark:text-red-400">{{ syncMetadata.stats.failed || 0 }}</span>
                    </div>
                    <button @click="loadSyncQueueDetails('failed')" class="w-full mt-2 px-3 py-2 bg-red-500 text-white text-sm rounded hover:bg-red-600">
                      ❌ عرض التفاصيل
                    </button>
                  </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                  <div class="text-center text-sm text-gray-600 dark:text-gray-400">
                    <strong class="text-gray-900 dark:text-gray-100">الإجمالي:</strong> {{ syncMetadata.stats.total || 0 }} سجل
                  </div>
                </div>
              </div>
              <div class="flex gap-2 flex-wrap mb-6">
                <button 
                  @click="checkSyncMetadata" 
                  :disabled="loadingMetadata" 
                  class="px-4 py-2 bg-indigo-500 text-white rounded hover:bg-indigo-600 disabled:opacity-50"
                >
                  <span v-if="!loadingMetadata">📊 تحديث الإحصائيات</span>
                  <span v-else>⏳ جاري...</span>
                </button>
                <button 
                  @click="checkSyncHealth" 
                  :disabled="loadingHealth" 
                  class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
                >
                  <span v-if="!loadingHealth">🔍 فحص الحالة العامة</span>
                  <span v-else>⏳ جاري الفحص...</span>
                </button>
                <button 
                  @click="checkPendingChanges" 
                  :disabled="loadingPending" 
                  class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 disabled:opacity-50"
                >
                  <span v-if="!loadingPending">📋 التغييرات المعلقة</span>
                  <span v-else>⏳ جاري...</span>
                </button>
                <button 
                  @click="checkSyncMetadata" 
                  :disabled="loadingMetadata" 
                  class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 disabled:opacity-50"
                >
                  <span v-if="!loadingMetadata">📊 إحصائيات المزامنة</span>
                  <span v-else>⏳ جاري...</span>
                </button>
                <button 
                  @click="startSmartSync" 
                  :disabled="isSyncing || !connectionStatus.online" 
                  class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600 disabled:opacity-50"
                >
                  <span v-if="!isSyncing">🚀 بدء المزامنة الذكية</span>
                  <span v-else>⏳ جاري المزامنة...</span>
                </button>
              </div>

              <!-- قسم مقارنة الجداول -->
              <div class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <h4 class="text-md font-semibold mb-4 dark:text-gray-50">🔍 مقارنة البيانات (السيرفر vs المحلي)</h4>
                <div class="flex gap-4 items-end mb-4">
                  <div class="flex-1">
                    <label class="block text-sm font-medium mb-2 dark:text-gray-300">اختر الجدول:</label>
                    <input
                      v-model="compareTableName"
                      type="text"
                      placeholder="مثال: orders"
                      class="w-full px-4 py-2 border rounded dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"
                    />
                  </div>
                  <button
                    @click="compareTables"
                    :disabled="!compareTableName || comparingTables"
                    class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="!comparingTables">🔍 مقارنة</span>
                    <span v-else>⏳ جاري المقارنة...</span>
                  </button>
                  <button
                    @click="syncCompareTable"
                    :disabled="!compareTableName || isSyncing || !connectionStatus.online"
                    class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    title="مزامنة هذا الجدول"
                  >
                    <span v-if="!isSyncing">🔄 مزامنة</span>
                    <span v-else>⏳ جاري المزامنة...</span>
                  </button>
                </div>

                <!-- نتائج المقارنة -->
                <div v-if="compareResult" class="mt-6">
                  <div class="mb-4 p-4 rounded-lg" :class="compareResult.summary?.is_identical ? 'bg-green-50 dark:bg-green-900' : 'bg-yellow-50 dark:bg-yellow-900'">
                    <div class="flex items-center justify-between mb-2">
                      <div class="flex items-center gap-3">
                        <h5 class="font-semibold dark:text-gray-100">
                          {{ compareResult.summary?.is_identical ? '✅ البيانات متطابقة' : '⚠️ يوجد اختلافات' }}
                        </h5>
                        <button
                          @click="syncCompareTable"
                          :disabled="isSyncing || !connectionStatus.online"
                          class="px-4 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                          :title="`مزامنة جدول ${compareTableName}`"
                        >
                          <span v-if="!isSyncing">🔄 مزامنة {{ compareTableName }}</span>
                          <span v-else>⏳ جاري المزامنة...</span>
                        </button>
                      </div>
                      <span class="text-2xl">{{ compareResult.summary?.is_identical ? '✅' : '⚠️' }}</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                      <div>
                        <span class="text-gray-600 dark:text-gray-300">المحلي:</span>
                        <span class="font-bold ml-2 dark:text-gray-100">{{ compareResult.summary?.local_count || 0 }}</span>
                      </div>
                      <div>
                        <span class="text-gray-600 dark:text-gray-300">السيرفر:</span>
                        <span class="font-bold ml-2 dark:text-gray-100">{{ compareResult.summary?.server_count || 0 }}</span>
                      </div>
                      <div>
                        <span class="text-gray-600 dark:text-gray-300">متطابق:</span>
                        <span class="font-bold text-green-600 dark:text-green-400 ml-2">{{ compareResult.summary?.matched_count || 0 }}</span>
                      </div>
                      <div>
                        <span class="text-gray-600 dark:text-gray-300">مختلف:</span>
                        <span class="font-bold text-red-600 dark:text-red-400 ml-2">{{ compareResult.summary?.differences_count || 0 }}</span>
                      </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-3 text-sm">
                      <div>
                        <span class="text-gray-600 dark:text-gray-300">في المحلي فقط:</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400 ml-2">{{ compareResult.summary?.only_local_count || 0 }}</span>
                      </div>
                      <div>
                        <span class="text-gray-600 dark:text-gray-300">في السيرفر فقط:</span>
                        <span class="font-bold text-orange-600 dark:text-orange-400 ml-2">{{ compareResult.summary?.only_server_count || 0 }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- السجلات المختلفة -->
                  <div v-if="compareResult.differences && compareResult.differences.length > 0" class="mt-4">
                    <h5 class="font-semibold mb-3 dark:text-gray-100">📋 السجلات المختلفة (عرض أول {{ compareResult.differences.length }} من {{ compareResult.total_differences }})</h5>
                    <div class="overflow-x-auto">
                      <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-500 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                          <tr>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">ID</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">الحقل</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">المحلي</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">السيرفر</th>
                          </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800">
                          <template v-for="diff in compareResult.differences" :key="diff.id">
                            <tr v-for="(fieldValue, fieldName) in diff.fields" :key="`${diff.id}-${fieldName}`" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                              <td class="px-4 py-2 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500 font-medium">{{ diff.id }}</td>
                              <td class="px-4 py-2 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-500">{{ fieldName }}</td>
                              <td class="px-4 py-2 text-red-600 dark:text-red-400 border border-gray-300 dark:border-gray-500 font-mono text-xs">{{ fieldValue.local }}</td>
                              <td class="px-4 py-2 text-green-600 dark:text-green-400 border border-gray-300 dark:border-gray-500 font-mono text-xs">{{ fieldValue.server }}</td>
                            </tr>
                          </template>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- السجلات الموجودة في المحلي فقط -->
                  <div v-if="compareResult.only_local_ids && compareResult.only_local_ids.length > 0" class="mt-4">
                    <h5 class="font-semibold mb-3 dark:text-gray-100">📋 في المحلي فقط (عرض أول {{ compareResult.only_local_ids.length }} من {{ compareResult.total_only_local }})</h5>
                    <div class="p-3 bg-blue-50 dark:bg-blue-900 rounded border border-blue-300 dark:border-blue-700">
                      <span v-for="id in compareResult.only_local_ids" :key="id" class="inline-block px-3 py-1 bg-blue-200 dark:bg-blue-700 text-blue-800 dark:text-blue-200 rounded mr-2 mb-2">
                        ID: {{ id }}
                      </span>
                    </div>
                  </div>

                  <!-- السجلات الموجودة في السيرفر فقط -->
                  <div v-if="compareResult.only_server_ids && compareResult.only_server_ids.length > 0" class="mt-4">
                    <h5 class="font-semibold mb-3 dark:text-gray-100">📋 في السيرفر فقط (عرض أول {{ compareResult.only_server_ids.length }} من {{ compareResult.total_only_server }})</h5>
                    <div class="p-3 bg-orange-50 dark:bg-orange-900 rounded border border-orange-300 dark:border-orange-700">
                      <span v-for="id in compareResult.only_server_ids" :key="id" class="inline-block px-3 py-1 bg-orange-200 dark:bg-orange-700 text-orange-800 dark:text-orange-200 rounded mr-2 mb-2">
                        ID: {{ id }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- عرض نتائج فحص الحالة العامة -->
              <div v-if="syncHealth && Object.keys(syncHealth).length > 0" class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <h4 class="text-md font-semibold mb-4 dark:text-gray-50">📊 نتائج فحص الحالة العامة</h4>
                <div class="space-y-4">
                  <div v-if="syncHealth.overall_status" class="p-4 rounded-lg" :class="{
                    'bg-green-50 dark:bg-green-900': syncHealth.overall_status === 'ok',
                    'bg-yellow-50 dark:bg-yellow-900': syncHealth.overall_status === 'warning',
                    'bg-red-50 dark:bg-red-900': syncHealth.overall_status === 'issue'
                  }">
                    <div class="flex items-center justify-between">
                      <span class="font-semibold dark:text-gray-100">الحالة العامة:</span>
                      <span class="px-3 py-1 rounded-full text-sm font-bold" :class="{
                        'bg-green-200 text-green-800 dark:bg-green-700 dark:text-green-100': syncHealth.overall_status === 'ok',
                        'bg-yellow-200 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100': syncHealth.overall_status === 'warning',
                        'bg-red-200 text-red-800 dark:bg-red-700 dark:text-red-100': syncHealth.overall_status === 'issue'
                      }">
                        {{ syncHealth.overall_status === 'ok' ? '✅ جيد' : syncHealth.overall_status === 'warning' ? '⚠️ تحذير' : '❌ مشكلة' }}
                      </span>
                    </div>
                  </div>

                  <div v-if="syncHealth.api" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <h5 class="font-semibold mb-2 dark:text-gray-100">🌐 API:</h5>
                    <div class="text-sm space-y-1 dark:text-gray-300">
                      <div><strong>الحالة:</strong> {{ syncHealth.api.status || 'غير متاح' }}</div>
                      <div v-if="syncHealth.api.url"><strong>URL:</strong> {{ syncHealth.api.url }}</div>
                      <div v-if="syncHealth.api.available !== undefined"><strong>متاح:</strong> {{ syncHealth.api.available ? 'نعم' : 'لا' }}</div>
                    </div>
                  </div>

                  <div v-if="syncHealth.sync_queue" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <h5 class="font-semibold mb-2 dark:text-gray-100">📋 Sync Queue:</h5>
                    <div class="text-sm space-y-1 dark:text-gray-300 mb-3">
                      <div><strong>الحالة:</strong> {{ syncHealth.sync_queue.status || 'غير معروف' }}</div>
                      <div v-if="syncHealth.sync_queue.stats">
                        <div class="flex justify-between items-center mb-1">
                          <span><strong>في الانتظار:</strong> {{ syncHealth.sync_queue.stats.pending || 0 }}</span>
                          <button @click="loadSyncQueueDetails('pending')" class="px-2 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600">عرض التفاصيل</button>
                        </div>
                        <div class="flex justify-between items-center mb-1">
                          <span><strong>تمت المزامنة:</strong> {{ syncHealth.sync_queue.stats.synced || 0 }}</span>
                          <button @click="loadSyncQueueDetails('synced')" class="px-2 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600">عرض التفاصيل</button>
                        </div>
                        <div class="flex justify-between items-center mb-1">
                          <span><strong>فاشلة:</strong> {{ syncHealth.sync_queue.stats.failed || 0 }}</span>
                          <button @click="loadSyncQueueDetails('failed')" class="px-2 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600">عرض التفاصيل</button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- عرض تفاصيل sync_queue -->
                  <div v-if="syncQueueDetails && syncQueueDetails.changes && syncQueueDetails.changes.length > 0" class="mt-4 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                      <h4 class="text-md font-semibold dark:text-gray-50">
                        📋 تفاصيل {{ syncQueueDetails.status === 'pending' ? 'في الانتظار' : syncQueueDetails.status === 'synced' ? 'تمت المزامنة' : 'فاشلة' }}
                        ({{ syncQueueDetails.total }})
                      </h4>
                      <button @click="syncQueueDetails = null" class="px-3 py-1 bg-gray-500 text-white text-sm rounded hover:bg-gray-600">إخفاء</button>
                    </div>
                    <div class="overflow-x-auto">
                      <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-500 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                          <tr>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">ID</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">الجدول</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">Record ID</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">الإجراء</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">عدد المحاولات</th>
                            <th v-if="syncQueueDetails.status === 'failed'" class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">رسالة الخطأ</th>
                            <th v-if="syncQueueDetails.status === 'synced'" class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">تاريخ المزامنة</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">تاريخ الإنشاء</th>
                          </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800">
                          <tr v-for="change in syncQueueDetails.changes" :key="change.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500">{{ change.id }}</td>
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500 font-medium">{{ change.table_name }}</td>
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500">{{ change.record_id }}</td>
                            <td class="px-4 py-2 border border-gray-300 dark:border-gray-500">
                              <span :class="{
                                'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100': change.action === 'insert',
                                'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100': change.action === 'update',
                                'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100': change.action === 'delete'
                              }" class="px-2 py-1 text-xs rounded-full font-medium">
                                {{ change.action === 'insert' ? 'إضافة' : change.action === 'update' ? 'تحديث' : 'حذف' }}
                              </span>
                            </td>
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500">{{ change.retry_count || 0 }}</td>
                            <td v-if="syncQueueDetails.status === 'failed'" class="px-4 py-2 text-red-600 dark:text-red-400 text-xs border border-gray-300 dark:border-gray-500 max-w-xs truncate" :title="change.error_message">
                              {{ change.error_message || '-' }}
                            </td>
                            <td v-if="syncQueueDetails.status === 'synced'" class="px-4 py-2 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500 text-xs">
                              {{ change.synced_at || '-' }}
                            </td>
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500 text-xs">{{ change.created_at }}</td>
                          </tr>
                        </tbody>
                      </table>
                      <div v-if="syncQueueDetails.total > syncQueueDetails.limit" class="mt-4 flex justify-between items-center">
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                          عرض {{ syncQueueDetails.offset + 1 }} إلى {{ Math.min(syncQueueDetails.offset + syncQueueDetails.limit, syncQueueDetails.total) }} من {{ syncQueueDetails.total }}
                        </div>
                        <div class="flex gap-2">
                          <button 
                            @click="loadSyncQueueDetails(syncQueueDetails.status, Math.max(0, syncQueueDetails.offset - syncQueueDetails.limit))"
                            :disabled="syncQueueDetails.offset === 0"
                            class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 disabled:opacity-50"
                          >
                            السابقة
                          </button>
                          <button 
                            @click="loadSyncQueueDetails(syncQueueDetails.status, syncQueueDetails.offset + syncQueueDetails.limit)"
                            :disabled="syncQueueDetails.offset + syncQueueDetails.limit >= syncQueueDetails.total"
                            class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 disabled:opacity-50"
                          >
                            التالية
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-if="syncHealth.issues && syncHealth.issues.length > 0" class="p-4 bg-red-50 dark:bg-red-900 rounded-lg">
                    <h5 class="font-semibold mb-2 text-red-800 dark:text-red-100">❌ المشاكل:</h5>
                    <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-200 space-y-1 mb-3">
                      <li v-for="issue in syncHealth.issues" :key="issue">{{ issue }}</li>
                    </ul>
                    <div v-if="syncHealth.api_service && !syncHealth.api_service.available && syncHealth.api_sync && syncHealth.api_sync.online_url" class="mt-3 p-3 bg-red-100 dark:bg-red-800 rounded">
                      <p class="text-sm text-red-800 dark:text-red-200 mb-2">
                        <strong>سبب المشكلة:</strong> لا يمكن الاتصال بالـ API على السيرفر:
                      </p>
                      <p class="text-xs text-red-700 dark:text-red-300 font-mono mb-2">
                        {{ syncHealth.api_sync.online_url }}
                      </p>
                      <div class="text-xs text-red-700 dark:text-red-300 space-y-1">
                        <p>• تحقق من أن السيرفر متاح ويعمل</p>
                        <p>• تحقق من الاتصال بالإنترنت</p>
                        <p>• تأكد من أن SYNC_API_TOKEN صحيح في ملف .env</p>
                        <p>• المزامنة ستتم تلقائياً عند عودة الاتصال</p>
                      </div>
                    </div>
                  </div>

                  <div v-if="syncHealth.warnings && syncHealth.warnings.length > 0" class="p-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg">
                    <h5 class="font-semibold mb-2 text-yellow-800 dark:text-yellow-100">⚠️ التحذيرات:</h5>
                    <ul class="list-disc list-inside text-sm text-yellow-700 dark:text-yellow-200 space-y-1">
                      <li v-for="warning in syncHealth.warnings" :key="warning">{{ warning }}</li>
                    </ul>
                  </div>

                  <div v-if="syncHealth.info && syncHealth.info.length > 0" class="p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
                    <h5 class="font-semibold mb-2 text-blue-800 dark:text-blue-100">ℹ️ معلومات:</h5>
                    <ul class="list-disc list-inside text-sm text-blue-700 dark:text-blue-200 space-y-1">
                      <li v-for="info in syncHealth.info" :key="info">{{ info }}</li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Jobs للمزامنة من السيرفر -->
              <div class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                  <h4 class="text-md font-semibold dark:text-gray-50">📥 Jobs للمزامنة من السيرفر ({{ syncFromServerJobs?.total || 0 }})</h4>
                  <div class="flex gap-2">
                    <button 
                      @click="loadSyncFromServerJobs" 
                      :disabled="loadingSyncJobs" 
                      class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50 text-sm"
                    >
                      <span v-if="!loadingSyncJobs">🔄 تحديث</span>
                      <span v-else>⏳ جاري...</span>
                    </button>
                    <button 
                      @click="clearSyncFromServerJobs" 
                      :disabled="loadingSyncJobs" 
                      class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 disabled:opacity-50 text-sm"
                    >
                      🗑️ إفراغ الكل
                    </button>
                  </div>
                </div>
                
                <div v-if="loadingSyncJobs" class="text-center py-4">
                  <span>⏳ جاري التحميل...</span>
                </div>
                <div v-else-if="syncFromServerJobs && syncFromServerJobs.jobs && syncFromServerJobs.jobs.length > 0" class="overflow-x-auto">
                  <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-500">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                      <tr>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">ID</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">الجدول</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">Record ID</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">الإجراء</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">الحالة</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">المحاولات</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">تاريخ الإنشاء</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">الإجراءات</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800">
                      <tr v-for="job in syncFromServerJobs.jobs" :key="job.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500">{{ job.id }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500">{{ job.table_name || '-' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500">{{ job.record_id || '-' }}</td>
                        <td class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-500">
                          <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                            {{ job.action || 'insert' }}
                          </span>
                        </td>
                        <td class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-500">
                          <span :class="{
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100': job.status === 'pending',
                            'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100': job.status === 'processing' || job.status === 'running',
                            'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100': job.status === 'completed',
                            'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100': job.status === 'failed'
                          }" class="px-2 py-1 text-xs rounded-full">
                            {{ job.status === 'pending' ? 'في الانتظار' : job.status === 'processing' || job.status === 'running' ? 'قيد التنفيذ' : job.status === 'completed' ? 'مكتمل' : 'فاشل' }}
                          </span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500">{{ job.attempts || 0 }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500">{{ job.created_at || '-' }}</td>
                        <td class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-500">
                          <button 
                            @click="deleteSyncFromServerJob(job.id)" 
                            class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs"
                            title="حذف"
                          >
                            🗑️
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div v-else class="text-center py-4 text-gray-500 dark:text-gray-400">
                  <p>لا توجد Jobs للمزامنة من السيرفر</p>
                </div>
              </div>

              <!-- عرض التغييرات المعلقة -->
              <div v-if="pendingChanges && pendingChanges.length > 0" class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <h4 class="text-md font-semibold mb-4 dark:text-gray-50">📋 التغييرات المعلقة ({{ pendingChanges.length }})</h4>
                <div class="overflow-x-auto">
                  <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-500">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                      <tr>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">ID</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">الجدول</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">Record ID</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">الإجراء</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">تاريخ الإنشاء</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800">
                      <tr v-for="change in pendingChanges.slice(0, 20)" :key="change.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500">{{ change.id }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500">{{ change.table_name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500">{{ change.record_id }}</td>
                        <td class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-500">
                          <span :class="{
                            'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100': change.action === 'insert',
                            'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100': change.action === 'update',
                            'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100': change.action === 'delete'
                          }" class="px-2 py-1 text-xs rounded-full">
                            {{ change.action === 'insert' ? 'إضافة' : change.action === 'update' ? 'تحديث' : 'حذف' }}
                          </span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500">{{ change.created_at }}</td>
                      </tr>
                    </tbody>
                  </table>
                  <p v-if="pendingChanges.length > 20" class="text-sm text-gray-500 dark:text-gray-400 mt-2 text-center">
                    عرض {{ Math.min(20, pendingChanges.length) }} من {{ pendingChanges.length }} سجل
                  </p>
                </div>
              </div>

              <!-- عرض حالة المزامنة -->
              <div v-if="currentSyncStatus" class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <h4 class="text-md font-semibold mb-4 dark:text-gray-50">🚀 حالة المزامنة</h4>
                <div class="space-y-2 text-sm">
                  <div class="flex justify-between">
                    <span class="dark:text-gray-300">Job ID:</span>
                    <span class="font-mono dark:text-gray-100">{{ currentSyncStatus.job_id }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="dark:text-gray-300">الحالة:</span>
                    <span class="font-semibold" :class="{
                      'text-green-600 dark:text-green-400': currentSyncStatus.status?.status === 'completed',
                      'text-blue-600 dark:text-blue-400': currentSyncStatus.status?.status === 'running',
                      'text-red-600 dark:text-red-400': currentSyncStatus.status?.status === 'failed',
                      'text-yellow-600 dark:text-yellow-400': currentSyncStatus.status?.status === 'waiting'
                    }">
                      {{ currentSyncStatus.status?.status === 'completed' ? '✅ مكتملة' : 
                         currentSyncStatus.status?.status === 'running' ? '🔄 قيد التنفيذ' : 
                         currentSyncStatus.status?.status === 'failed' ? '❌ فاشلة' : 
                         currentSyncStatus.status?.status === 'waiting' ? '⏳ في الانتظار' : currentSyncStatus.status?.status }}
                    </span>
                  </div>
                  <div v-if="currentSyncStatus.status?.synced !== undefined" class="flex justify-between">
                    <span class="dark:text-gray-300">تمت المزامنة:</span>
                    <span class="font-bold text-green-600 dark:text-green-400">{{ currentSyncStatus.status.synced }}</span>
                  </div>
                  <div v-if="currentSyncStatus.status?.failed !== undefined" class="flex justify-between">
                    <span class="dark:text-gray-300">فاشلة:</span>
                    <span class="font-bold text-red-600 dark:text-red-400">{{ currentSyncStatus.status.failed }}</span>
                  </div>
                  <div v-if="currentSyncStatus.status?.elapsed_time" class="flex justify-between">
                    <span class="dark:text-gray-300">الوقت المستغرق:</span>
                    <span class="dark:text-gray-100">{{ currentSyncStatus.status.elapsed_time }} ثانية</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- تبويب المزامنة -->
          <div v-if="activeTab === 'sync'" class="p-6">
            <!-- Auto-sync + تشغيل scheduler/worker (مثل shipping) -->
            <div class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
              <div class="flex justify-between items-center gap-4 flex-wrap">
                <div>
                  <h4 class="text-md font-semibold dark:text-gray-50">⏱️ المزامنة التلقائية + تشغيل الخدمات</h4>
                  <p class="text-xs text-gray-600 dark:text-gray-400">
                    تشغيل مرة: <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">schedule:run</code> و <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">queue:work --once</code>. للمزامنة كل 5 دقائق شغّل <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">run-scheduler.vbs</code> أو في التيرمنال: <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">php artisan schedule:work</code>.
                  </p>
                </div>
                <div class="flex gap-2 flex-wrap">
                  <button
                    type="button"
                    class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50"
                    :disabled="runningSchedule"
                    @click="triggerScheduleRun"
                  >
                    <span v-if="!runningSchedule">▶ تشغيل المهام المجدولة</span>
                    <span v-else>⏳ جاري...</span>
                  </button>
                  <button
                    type="button"
                    class="px-4 py-2 bg-slate-700 text-white rounded hover:bg-slate-800 disabled:opacity-50"
                    :disabled="runningWorkerOnce"
                    @click="runWorkerOnce"
                  >
                    <span v-if="!runningWorkerOnce">⚙️ تشغيل Worker مرة</span>
                    <span v-else>⏳ جاري...</span>
                  </button>
                  <button
                    type="button"
                    class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 disabled:opacity-50"
                    :disabled="isSyncing"
                    @click="initSQLite"
                  >
                    💾 تهيئة SQLite
                  </button>
                </div>
              </div>

              <div class="mt-3 text-sm">
                <span class="font-medium dark:text-gray-200">الحالة:</span>
                <span class="mr-2 font-semibold" :class="autoSyncStatusFormatted.class">
                  {{ autoSyncStatusFormatted.icon }} {{ autoSyncStatusFormatted.text }}
                </span>
              </div>
              <p v-if="autoSyncStatus?.status && !autoSyncStatus.status.schedule_running && autoSyncStatus.status.scheduler_hint" class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                💡 {{ autoSyncStatus.status.scheduler_hint }}
              </p>

              <div v-if="lastCommandOutput" class="mt-3">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">آخر نتيجة أمر:</div>
                <pre class="text-xs whitespace-pre-wrap bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded p-3 overflow-auto max-h-48 dark:text-gray-100">{{ JSON.stringify(lastCommandOutput, null, 2) }}</pre>
              </div>
            </div>

            <!-- جوب رتل المزامنة (للتحقق) -->
            <div class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
              <h4 class="text-md font-semibold mb-3 dark:text-gray-50">📋 جوب رتل المزامنة (sync_queue)</h4>
              <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">عرض السجلات في رتل المزامنة للتحقق: في الانتظار، تمت مزامنتها، أو فاشلة.</p>
              <div class="flex flex-wrap items-center gap-4 mb-3">
                <div class="flex items-center gap-2">
                  <span class="text-sm text-gray-600 dark:text-gray-400">في الانتظار:</span>
                  <span class="font-bold text-blue-600 dark:text-blue-400">{{ syncMetadata.stats?.pending ?? '–' }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-sm text-gray-600 dark:text-gray-400">تمت المزامنة:</span>
                  <span class="font-bold text-green-600 dark:text-green-400">{{ syncMetadata.stats?.synced ?? '–' }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-sm text-gray-600 dark:text-gray-400">فاشلة:</span>
                  <span class="font-bold text-red-600 dark:text-red-400">{{ syncMetadata.stats?.failed ?? '–' }}</span>
                </div>
                <button @click="loadAllData(); $nextTick(() => loadSyncQueueDetails('pending'))" :disabled="loadingQueueDetails" class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">
                  <span v-if="!loadingQueueDetails">🔄 تحديث وعرض جوب في الانتظار</span>
                  <span v-else>⏳ جاري...</span>
                </button>
                <button @click="loadSyncQueueDetails('pending')" :disabled="loadingQueueDetails" class="px-3 py-1.5 bg-blue-500 text-white text-sm rounded hover:bg-blue-600">عرض في الانتظار</button>
                <button @click="loadSyncQueueDetails('synced')" :disabled="loadingQueueDetails" class="px-3 py-1.5 bg-green-500 text-white text-sm rounded hover:bg-green-600">عرض تمت مزامنتها</button>
                <button @click="loadSyncQueueDetails('failed')" :disabled="loadingQueueDetails" class="px-3 py-1.5 bg-red-500 text-white text-sm rounded hover:bg-red-600">عرض فاشلة</button>
              </div>
              <!-- جدول الجوب (في تبويب المزامنة) -->
              <div v-if="activeTab === 'sync' && syncQueueDetails && syncQueueDetails.changes && syncQueueDetails.changes.length > 0" class="mt-4 overflow-x-auto">
                <div class="flex justify-between items-center mb-2">
                  <h5 class="text-sm font-semibold dark:text-gray-50">
                    📋 تفاصيل {{ syncQueueDetails.status === 'pending' ? 'في الانتظار' : syncQueueDetails.status === 'synced' ? 'تمت المزامنة' : 'فاشلة' }}
                    ({{ syncQueueDetails.total }})
                  </h5>
                  <button @click="syncQueueDetails = null" class="px-2 py-1 bg-gray-500 text-white text-xs rounded hover:bg-gray-600">إخفاء</button>
                </div>
                <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-500 text-sm">
                  <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                      <th class="px-3 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">ID</th>
                      <th class="px-3 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">الجدول</th>
                      <th class="px-3 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">Record ID</th>
                      <th class="px-3 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">الإجراء</th>
                      <th class="px-3 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">محاولات</th>
                      <th v-if="syncQueueDetails.status === 'failed'" class="px-3 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">رسالة الخطأ</th>
                      <th v-if="syncQueueDetails.status === 'synced'" class="px-3 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">تاريخ المزامنة</th>
                      <th class="px-3 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-50 border border-gray-300 dark:border-gray-500">تاريخ الإنشاء</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white dark:bg-gray-800">
                    <tr v-for="change in syncQueueDetails.changes" :key="change.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                      <td class="px-3 py-2 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500">{{ change.id }}</td>
                      <td class="px-3 py-2 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500 font-medium">{{ change.table_name }}</td>
                      <td class="px-3 py-2 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500">{{ change.record_id }}</td>
                      <td class="px-3 py-2 border border-gray-300 dark:border-gray-500">
                        <span :class="{
                          'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100': change.action === 'insert',
                          'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100': change.action === 'update',
                          'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100': change.action === 'delete'
                        }" class="px-2 py-0.5 text-xs rounded font-medium">
                          {{ change.action === 'insert' ? 'إضافة' : change.action === 'update' ? 'تحديث' : 'حذف' }}
                        </span>
                      </td>
                      <td class="px-3 py-2 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500">{{ change.retry_count || 0 }}</td>
                      <td v-if="syncQueueDetails.status === 'failed'" class="px-3 py-2 text-red-600 dark:text-red-400 text-xs border border-gray-300 dark:border-gray-500 max-w-xs truncate" :title="change.error_message">{{ change.error_message || '–' }}</td>
                      <td v-if="syncQueueDetails.status === 'synced'" class="px-3 py-2 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500 text-xs">{{ change.synced_at || '–' }}</td>
                      <td class="px-3 py-2 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-500 text-xs">{{ change.created_at }}</td>
                    </tr>
                  </tbody>
                </table>
                <div v-if="syncQueueDetails.total > syncQueueDetails.limit" class="mt-2 flex justify-between items-center text-xs">
                  <span class="text-gray-600 dark:text-gray-400">عرض {{ syncQueueDetails.offset + 1 }}–{{ Math.min(syncQueueDetails.offset + syncQueueDetails.limit, syncQueueDetails.total) }} من {{ syncQueueDetails.total }}</span>
                  <div class="flex gap-2">
                    <button @click="loadSyncQueueDetails(syncQueueDetails.status, Math.max(0, syncQueueDetails.offset - syncQueueDetails.limit))" :disabled="syncQueueDetails.offset === 0" class="px-2 py-1 bg-gray-200 dark:bg-gray-600 rounded disabled:opacity-50">السابق</button>
                    <button @click="loadSyncQueueDetails(syncQueueDetails.status, syncQueueDetails.offset + syncQueueDetails.limit)" :disabled="syncQueueDetails.offset + syncQueueDetails.limit >= syncQueueDetails.total" class="px-2 py-1 bg-gray-200 dark:bg-gray-600 rounded disabled:opacity-50">التالي</button>
                  </div>
                </div>
              </div>
            </div>

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

            <!-- سجل العمليات (مثل shipping) -->
            <div class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
              <div class="flex justify-between items-center mb-3">
                <h4 class="text-md font-semibold dark:text-gray-50">🧾 سجل الصفحة (آخر {{ systemLogs.length }})</h4>
                <button
                  type="button"
                  class="px-3 py-1.5 text-xs bg-gray-200 dark:bg-gray-700 dark:text-gray-100 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                  @click="clearSystemLogs"
                  :disabled="systemLogs.length === 0"
                >
                  مسح
                </button>
              </div>
              <div v-if="systemLogs.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                لا يوجد سجل بعد.
              </div>
              <div v-else class="space-y-2 max-h-56 overflow-auto">
                <div
                  v-for="log in systemLogs"
                  :key="log.id"
                  class="text-xs p-2 rounded border dark:border-gray-700"
                  :class="{
                    'bg-red-50 border-red-200 dark:bg-red-900/20': log.type === 'error',
                    'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20': log.type === 'warning',
                    'bg-green-50 border-green-200 dark:bg-green-900/20': log.type === 'success',
                    'bg-blue-50 border-blue-200 dark:bg-blue-900/20': log.type === 'info'
                  }"
                >
                  <div class="flex justify-between gap-2">
                    <div class="dark:text-gray-100">{{ log.message }}</div>
                    <div class="text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ log.at }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- لوغ الأخطاء (laravel.log) -->
            <div class="mb-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
              <div class="flex justify-between items-center flex-wrap gap-2 mb-3">
                <h4 class="text-md font-semibold dark:text-gray-50">📄 لوغ الأخطاء (laravel.log)</h4>
                <div class="flex gap-2">
                  <button
                    type="button"
                    class="px-3 py-1.5 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 disabled:opacity-50"
                    :disabled="loadingErrorLog"
                    @click="loadErrorLog"
                  >
                    <span v-if="!loadingErrorLog">🔄 تحديث</span>
                    <span v-else>⏳ جاري...</span>
                  </button>
                  <button
                    type="button"
                    class="px-3 py-1.5 bg-red-500 text-white text-sm rounded hover:bg-red-600 disabled:opacity-50"
                    :disabled="clearingErrorLog"
                    @click="clearErrorLog"
                  >
                    <span v-if="!clearingErrorLog">🗑 إفراغ</span>
                    <span v-else>⏳ جاري...</span>
                  </button>
                </div>
              </div>
              <p v-if="errorLogLinesCount !== null" class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                عرض آخر {{ errorLogLinesCount }} سطر{{ errorLogTotalLines != null && errorLogTotalLines > errorLogLinesCount ? ` من ${errorLogTotalLines}` : '' }}
              </p>
              <pre class="text-xs whitespace-pre-wrap bg-gray-900 text-gray-100 rounded p-4 overflow-auto max-h-80 font-mono border border-gray-700 dark:border-gray-600">{{ errorLogContent || 'انقر «تحديث» لتحميل اللوغ.' }}</pre>
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
              <div v-else class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-center">
                  <p class="text-gray-600 dark:text-gray-300 mb-2">📭 لا توجد بيانات مزامنة متاحة</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    بيانات المزامنة (sync_metadata) تظهر فقط عند استخدام المزامنة الكلاسيكية. 
                    النظام يستخدم الآن المزامنة الذكية التي تعتمد على <strong>sync_queue</strong>.
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    يمكنك متابعة حالة المزامنة من خلال قسم "🔄 المزامنة" في الأعلى أو من خلال <strong>sync_queue</strong>.
                  </p>
                  <div v-if="syncMetadata.stats && (syncMetadata.stats.pending > 0 || syncMetadata.stats.synced > 0 || syncMetadata.stats.failed > 0)" class="mt-4 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
                    <h5 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">📊 إحصائيات المزامنة الذكية:</h5>
                    <div class="grid grid-cols-3 gap-4 text-sm">
                      <div>
                        <span class="text-gray-600 dark:text-gray-300">في الانتظار:</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400 ml-2">{{ syncMetadata.stats.pending || 0 }}</span>
                      </div>
                      <div>
                        <span class="text-gray-600 dark:text-gray-300">تمت المزامنة:</span>
                        <span class="font-bold text-green-600 dark:text-green-400 ml-2">{{ syncMetadata.stats.synced || 0 }}</span>
                      </div>
                      <div>
                        <span class="text-gray-600 dark:text-gray-300">فاشلة:</span>
                        <span class="font-bold text-red-600 dark:text-red-400 ml-2">{{ syncMetadata.stats.failed || 0 }}</span>
                      </div>
                    </div>
                  </div>
                </div>
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

    <!-- تبويب Migrations -->
    <div v-if="activeTab === 'migrations'" class="p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h3 class="text-xl font-semibold dark:text-gray-200 mb-2">
            📦 إدارة Migrations
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            تنفيذ migration محدد بأمان بدون حذف البيانات
          </p>
        </div>
        <div class="flex gap-2">
          <button
            @click="loadMigrations"
            :disabled="loadingMigrations"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="!loadingMigrations">🔄 تحديث</span>
            <span v-else>⏳ جاري...</span>
          </button>
        </div>
      </div>

      <!-- إحصائيات -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-orange-50 dark:bg-orange-900 p-4 rounded-lg">
          <div class="text-2xl font-bold text-orange-600">{{ dbMigrationStats.total_pending || 0 }}</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">قيد الانتظار</div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
          <div class="text-2xl font-bold text-gray-600">{{ dbMigrationStats.total_executed || 0 }}</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">منفذة</div>
        </div>
      </div>

      <!-- فلتر عرض المنفذة -->
      <div class="mb-4 flex items-center">
        <input
          type="checkbox"
          v-model="showExecutedMigrations"
          @change="loadMigrations"
          id="showExecuted"
          class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
        >
        <label for="showExecuted" class="mr-2 text-sm text-gray-700 dark:text-gray-300">
          عرض المنفذة
        </label>
      </div>

      <div v-if="loadingMigrations" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600 mx-auto"></div>
        <p class="mt-4 text-gray-600 dark:text-gray-400">جاري التحميل...</p>
      </div>

      <div v-else class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">اسم Migration</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">اسم الملف</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">التاريخ</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">الإجراءات</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="migration in migrations"
                :key="migration.file"
                class="hover:bg-gray-50 dark:hover:bg-gray-700"
                :class="{'opacity-60': migration.executed}"
              >
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200">
                  <div class="flex items-center">
                    {{ migration.name }}
                    <span v-if="migration.executed" class="mr-2 px-2 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full">
                      ✓ منفذ
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono text-xs">
                  {{ migration.file }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  {{ migration.date }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <button
                    v-if="!migration.executed"
                    @click="runSpecificMigration(migration.name)"
                    :disabled="runningMigration === migration.name"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="runningMigration !== migration.name">▶️ تنفيذ</span>
                    <span v-else>⏳ جاري...</span>
                  </button>
                  <span v-else class="text-gray-400 dark:text-gray-500 text-sm">
                    تم التنفيذ
                  </span>
                </td>
              </tr>
              <tr v-if="migrations.length === 0">
                <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                  <div class="text-5xl mb-2">📭</div>
                  <p class="text-lg">لا توجد migrations</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- تحذير وجود بيانات -->
      <div v-if="migrationWarning" class="mt-6 bg-yellow-50 dark:bg-yellow-900 border-l-4 border-yellow-500 p-4 rounded-lg">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <span class="text-2xl">⚠️</span>
          </div>
          <div class="mr-3 flex-1">
            <h4 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
              تحذير: يوجد بيانات في الجداول!
            </h4>
            <p class="text-sm text-yellow-700 dark:text-yellow-300 mb-3">
              تنفيذ هذا Migration قد يحذف البيانات الموجودة!
            </p>
            <div v-if="migrationWarning.tables" class="mb-3">
              <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">
                الجداول المتأثرة ({{ migrationWarning.total_records }} سجل):
              </p>
              <ul class="list-disc list-inside text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                <li v-for="table in migrationWarning.tables" :key="table.name">
                  {{ table.name }}: {{ table.count }} سجل
                </li>
              </ul>
            </div>
            <div v-else-if="migrationWarning.table" class="mb-3">
              <p class="text-sm text-yellow-700 dark:text-yellow-300">
                جدول <strong>{{ migrationWarning.table }}</strong>: {{ migrationWarning.record_count }} سجل
              </p>
            </div>
            <div class="flex gap-2 mt-4">
              <button
                @click="forceRunMigration(migrationWarning.migration)"
                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm font-medium"
              >
                ⚠️ تنفيذ رغم التحذير (حذف البيانات)
              </button>
              <button
                @click="migrationWarning = null"
                class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm"
              >
                إلغاء
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- عرض نتيجة التنفيذ -->
      <div v-if="migrationOutput" class="mt-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg">
        <div class="p-4 border-b border-gray-200 dark:border-gray-600">
          <h4 class="text-lg font-semibold dark:text-gray-200">📋 نتيجة التنفيذ:</h4>
        </div>
        <div class="p-4">
          <pre class="text-xs font-mono bg-gray-900 text-green-400 p-4 rounded overflow-x-auto max-h-64 overflow-y-auto">{{ migrationOutput }}</pre>
          <button
            @click="migrationOutput = ''"
            class="mt-4 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm"
          >
            إغلاق
          </button>
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
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const toast = useToast();
const props = defineProps({ translations: Object, syncServerApiUrl: { type: String, default: null } });

// البيانات الأساسية
const isRefreshing = ref(false);
const isSyncing = ref(false);
const isCheckingConnection = ref(false);
const connectionStatus = ref({ 
  online: navigator.onLine, 
  syncing: false,
  serverAvailable: false,
  serverChecked: false,
  lastServerCheck: null
});
const syncStatus = ref({ pendingCount: 0, lastSync: null });
const activeTab = ref('tables');

// Lazy Loading - تتبع ما تم تحميله
const loadedTabs = ref({
  tables: false,
  sync: false,
  health: false,
  backups: false
});

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

// Database Migrations
const migrations = ref([]);
const loadingMigrations = ref(false);
const runningMigration = ref(null);
const migrationOutput = ref('');
const migrationWarning = ref(null);
const showExecutedMigrations = ref(false);
const dbMigrationStats = ref({
  total_pending: 0,
  total_executed: 0
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

// فحص المزامنة
const syncHealth = ref(null);
const loadingHealth = ref(false);
const pendingChanges = ref([]);
const loadingPending = ref(false);
const loadingMetadata = ref(false);
const currentSyncStatus = ref(null);
const currentJobId = ref(null);
const syncQueueDetails = ref(null);
const loadingQueueDetails = ref(false);

// لوغ الأخطاء
const errorLogContent = ref('');
const loadingErrorLog = ref(false);
const clearingErrorLog = ref(false);
const errorLogLinesCount = ref(null);
const errorLogTotalLines = ref(null);

// Auto Sync + أدوات تشغيل (مثل shipping)
const autoSyncStatus = ref(null);
const runningSchedule = ref(false);
const runningWorkerOnce = ref(false);
const lastCommandOutput = ref(null);

// System logs (مثل shipping) - آخر 50 حدث
const systemLogs = ref([]);

// Jobs للمزامنة من السيرفر
const syncFromServerJobs = ref(null);
const loadingSyncJobs = ref(false);

const addSystemLog = (type, message) => {
  systemLogs.value.unshift({
    id: `${Date.now()}_${Math.random().toString(16).slice(2)}`,
    type,
    message,
    at: new Date().toLocaleString()
  });
  if (systemLogs.value.length > 50) {
    systemLogs.value = systemLogs.value.slice(0, 50);
  }
};

const clearSystemLogs = () => {
  systemLogs.value = [];
};

const autoSyncStatusFormatted = computed(() => {
  const s = autoSyncStatus.value?.status;
  if (!s) return { text: 'غير محمّل', class: 'text-gray-600 dark:text-gray-300', icon: '⏳' };
  if (!s.is_local) return { text: 'متاح فقط محلياً', class: 'text-amber-600 dark:text-amber-400', icon: '⚠️' };
  if (s.is_running) return { text: 'جاري المزامنة...', class: 'text-blue-600 dark:text-blue-400', icon: '🔄' };
  if (!s.enabled) return { text: 'غير مفعّل', class: 'text-amber-600 dark:text-amber-400', icon: '⚠️' };
  const last = s.last_sync_at ? `آخر: ${s.last_sync_at}` : 'لم تُشغّل بعد';
  const next = typeof s.next_sync_in === 'number' ? ` | القادم خلال: ${s.next_sync_in}s` : '';
  const sched = s.schedule_running ? ' | Scheduler: ✅' : ' | Scheduler: ❌';
  return { text: `${last}${next}${sched}`, class: 'text-green-600 dark:text-green-400', icon: '✅' };
});

// Jobs للمزامنة من السيرفر
const loadSyncFromServerJobs = async () => {
  loadingSyncJobs.value = true;
  try {
    const response = await axios.get('/api/sync-monitor/sync-from-server-jobs', {
      params: { limit: 50 },
      withCredentials: true
    });
    
    if (response.data.success) {
      syncFromServerJobs.value = response.data;
    } else {
      toast.error(response.data.message || 'فشل تحميل Jobs');
    }
  } catch (error) {
    console.error('Error loading sync from server jobs:', error);
    toast.error('فشل تحميل Jobs: ' + (error.response?.data?.message || error.message));
  } finally {
    loadingSyncJobs.value = false;
  }
};

const loadAutoSyncStatus = async () => {
  try {
    const response = await axios.get('/api/sync-monitor/auto-sync-status', { withCredentials: true });
    autoSyncStatus.value = response.data;
  } catch (error) {
    autoSyncStatus.value = { success: false, status: null, error: error.response?.data?.message || error.message };
  }
};

const triggerScheduleRun = async () => {
  if (runningSchedule.value) return;
  runningSchedule.value = true;
  lastCommandOutput.value = null;
  try {
    addSystemLog('info', 'تشغيل schedule:run...');
    const response = await axios.post('/api/sync-monitor/run-schedule', {}, { withCredentials: true, timeout: 60000 });
    lastCommandOutput.value = response.data;
    if (response.data.success) {
      toast.success('تم تشغيل المهام المجدولة');
      addSystemLog('success', 'تم تشغيل schedule:run بنجاح');
    } else {
      toast.warning(response.data.message || 'انتهى مع أخطاء');
      addSystemLog('warning', response.data.message || 'انتهى schedule:run مع أخطاء');
    }
    await loadAutoSyncStatus();
  } catch (error) {
    const msg = error.response?.data?.message || error.response?.data?.error || error.message;
    toast.error('فشل تشغيل المهام المجدولة: ' + msg);
    addSystemLog('error', 'فشل تشغيل schedule:run: ' + msg);
  } finally {
    runningSchedule.value = false;
  }
};

const runWorkerOnce = async () => {
  if (runningWorkerOnce.value) return;
  runningWorkerOnce.value = true;
  lastCommandOutput.value = null;
  try {
    addSystemLog('info', 'تشغيل queue worker مرة واحدة...');
    const response = await axios.post('/api/sync-monitor/run-worker-once', { queue: 'sync', timeout: 60 }, { withCredentials: true, timeout: 90000 });
    lastCommandOutput.value = response.data;
    if (response.data.success) {
      toast.success('تم تشغيل الـ Worker مرة واحدة');
      addSystemLog('success', 'تم تشغيل worker once بنجاح');
    } else {
      toast.warning(response.data.message || 'انتهى مع أخطاء');
      addSystemLog('warning', response.data.message || 'انتهى worker once مع أخطاء');
    }
  } catch (error) {
    const msg = error.response?.data?.message || error.response?.data?.error || error.message;
    toast.error('فشل تشغيل الـ Worker: ' + msg);
    addSystemLog('error', 'فشل تشغيل worker once: ' + msg);
  } finally {
    runningWorkerOnce.value = false;
  }
};

const initSQLite = async () => {
  if (!confirm('هل تريد تهيئة/إعادة بناء SQLite من MySQL؟\n\nقد يستغرق ذلك بعض الوقت.')) return;
  isSyncing.value = true;
  lastCommandOutput.value = null;
  try {
    addSystemLog('info', 'بدء تهيئة SQLite...');
    const response = await axios.post('/api/sync-monitor/init-sqlite', {}, { withCredentials: true, timeout: 180000 });
    lastCommandOutput.value = response.data;
    if (response.data.success) {
      toast.success(response.data.message || 'تمت تهيئة SQLite بنجاح');
      addSystemLog('success', 'تمت تهيئة SQLite');
      await loadAllData();
    } else {
      toast.error(response.data.message || 'فشلت تهيئة SQLite');
      addSystemLog('error', response.data.message || 'فشلت تهيئة SQLite');
    }
  } catch (error) {
    const msg = error.response?.data?.message || error.response?.data?.error || error.message;
    toast.error('فشلت تهيئة SQLite: ' + msg);
    addSystemLog('error', 'فشلت تهيئة SQLite: ' + msg);
  } finally {
    isSyncing.value = false;
  }
};

const deleteSyncFromServerJob = async (jobId) => {
  if (!confirm('هل تريد حذف هذا Job؟')) return;
  
  try {
    const response = await axios.delete('/api/sync-monitor/sync-from-server-job', {
      params: { job_id: jobId },
      withCredentials: true
    });
    
    if (response.data.success) {
      toast.success('تم حذف Job بنجاح');
      await loadSyncFromServerJobs();
    } else {
      toast.error(response.data.message || 'فشل حذف Job');
    }
  } catch (error) {
    console.error('Error deleting sync from server job:', error);
    toast.error('فشل حذف Job: ' + (error.response?.data?.message || error.message));
  }
};

const clearSyncFromServerJobs = async () => {
  if (!confirm('هل تريد حذف جميع Jobs للمزامنة من السيرفر؟')) return;
  
  try {
    const response = await axios.delete('/api/sync-monitor/sync-from-server-jobs', {
      withCredentials: true
    });
    
    if (response.data.success) {
      toast.success(`تم حذف ${response.data.deleted_count || 0} Job(ات)`);
      await loadSyncFromServerJobs();
    } else {
      toast.error(response.data.message || 'فشل حذف Jobs');
    }
  } catch (error) {
    console.error('Error clearing sync from server jobs:', error);
    toast.error('فشل حذف Jobs: ' + (error.response?.data?.message || error.message));
  }
};

// مقارنة الجداول
const compareTableName = ref('orders');
const comparingTables = ref(false);
const compareResult = ref(null);

// الوظائف الأساسية - جلب جميع البيانات في request واحد
const checkRemoteConnection = async () => {
  isCheckingConnection.value = true;
  try {
    // فحص الإنترنت أولاً
    connectionStatus.value.online = navigator.onLine;
    
    toast.info('🔄 جاري فحص الاتصال...', { timeout: 2000 });
    
    // استخدام الـ endpoint الجديد الأسرع - Offline First
    const response = await axios.get('/api/sync-monitor/check-health', {
      timeout: 5000, // 5 ثواني فقط (أسرع)
      withCredentials: true
    });

    if (response.data.success) {
      const status = response.data.system_status;
      
      // تحديث حالة الاتصال
      connectionStatus.value.online = status.internet_available;
      connectionStatus.value.serverAvailable = status.remote_server_available;
      connectionStatus.value.serverChecked = true;
      connectionStatus.value.lastServerCheck = new Date().toISOString();
      
      // رسائل حسب الحالة
      if (status.local_database_available) {
        if (status.remote_server_available) {
          toast.success('✅ النظام يعمل - السيرفر متاح للمزامنة', { timeout: 3000 });
        } else if (status.internet_available) {
          toast.warning('⚠️ النظام يعمل Offline - السيرفر غير متاح', { timeout: 3000 });
        } else {
          toast.info('📴 النظام يعمل Offline - لا يوجد إنترنت', { timeout: 3000 });
        }
      } else {
        toast.error('❌ قاعدة البيانات المحلية غير متاحة', { timeout: 5000 });
      }
      
      // تحديث معلومات المزامنة التلقائية
      if (status.auto_sync_enabled) {
        console.log('Auto sync enabled:', {
          last_sync: status.last_sync,
          next_sync: status.next_sync
        });
      }
      
      return status.remote_server_available;
    } else {
      connectionStatus.value.serverAvailable = false;
      connectionStatus.value.serverChecked = true;
      toast.error('❌ فشل فحص الحالة: ' + (response.data.message || 'خطأ غير معروف'), { timeout: 3000 });
      return false;
    }
  } catch (error) {
    console.error('Connection check failed:', error);
    connectionStatus.value.serverAvailable = false;
    connectionStatus.value.serverChecked = true;
    
    if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
      toast.error('❌ انتهت مهلة الاتصال - يرجى المحاولة لاحقاً', { timeout: 5000 });
    } else if (error.response?.status === 404) {
      toast.error('❌ API غير موجود - تحقق من التكوين', { timeout: 5000 });
    } else {
      // في حالة الخطأ - نفترض offline mode
      connectionStatus.value.online = navigator.onLine;
      toast.warning('⚠️ النظام يعمل Offline Mode', { timeout: 3000 });
    }
    return false;
  } finally {
    isCheckingConnection.value = false;
  }
};

// جلب البيانات الأساسية فقط (للتحميل الأولي)
const loadEssentialData = async () => {
  isRefreshing.value = true;
  try {
    connectionStatus.value.online = navigator.onLine;
    
    // فحص سريع للاتصال (lightweight)
    const healthResponse = await axios.get('/api/sync-monitor/check-health', {
      timeout: 3000,
      withCredentials: true
    });
    
    if (healthResponse.data.success) {
      const status = healthResponse.data.system_status;
      connectionStatus.value.online = status.internet_available;
      connectionStatus.value.serverAvailable = status.remote_server_available;
      connectionStatus.value.serverChecked = true;
      
      // جلب stats فقط (بدون جداول أو metadata)
      const statsResponse = await axios.get('/api/sync-monitor/sync-queue-details', {
        withCredentials: true
      });
      
      if (statsResponse.data.success) {
        syncMetadata.value.stats = statsResponse.data.queue_stats || null;
        syncStatus.value.pendingCount = statsResponse.data.queue_stats?.pending || 0;
      }
    }
  } catch (error) {
    console.error('Error loading essential data:', error);
    // لا نعرض toast هنا - فقط نسجل الخطأ
  } finally {
    isRefreshing.value = false;
  }
};

// جلب بيانات كل tab حسب الحاجة (Lazy Loading)
const loadTabData = async (tab) => {
  if (loadedTabs.value[tab]) {
    return; // تم تحميله مسبقاً
  }
  
  isRefreshing.value = true;
  try {
    switch (tab) {
      case 'tables':
        const tablesResponse = await axios.get('/api/sync-monitor/tables', { 
          params: { force_connection: selectedDatabase.value !== 'auto' ? selectedDatabase.value : 'auto' },
          withCredentials: true 
        });
        if (tablesResponse.data.success) {
          syncedTables.value = tablesResponse.data.tables || [];
          databaseInfo.value = tablesResponse.data.database_info || {
            type: 'MySQL',
            total_tables: syncedTables.value.length,
          };
          loadedTabs.value.tables = true;
        }
        break;
        
      case 'sync':
        // تحميل metadata + jobs
        const metadataResponse = await axios.get('/api/sync-monitor/metadata', { 
          withCredentials: true 
        });
        if (metadataResponse.data.success) {
          syncMetadata.value.data = metadataResponse.data.metadata || [];
          syncMetadata.value.stats = metadataResponse.data.queue_stats || null;
          syncStatus.value.pendingCount = metadataResponse.data.queue_stats?.pending || 0;
        }
        
        // جلب jobs
        await loadSyncFromServerJobs();

        // Auto-sync status
        await loadAutoSyncStatus();
        
        loadedTabs.value.sync = true;
        break;
        
      case 'health':
        // فحص صحة المزامنة (diagnostic)
        await checkSyncHealth();
        loadedTabs.value.health = true;
        break;
        
      case 'backups':
        const backupsResponse = await axios.get('/api/sync-monitor/backups', { 
          withCredentials: true 
        });
        if (backupsResponse.data.success) {
          backups.value = backupsResponse.data.backups || [];
          loadedTabs.value.backups = true;
        }
        break;
        
      case 'migrations':
        await loadMigrations();
        loadedTabs.value.migrations = true;
        break;
    }
  } catch (error) {
    console.error(`Error loading ${tab} data:`, error);
    // لا نعرض toast - فقط نسجل الخطأ
  } finally {
    isRefreshing.value = false;
  }
};

// عنوان طلبات sync-monitor: عند طلب MySQL فقط والسيرفر معرّف نرسل الطلب للسيرفر مباشرة (لا للوكل)
const getSyncMonitorBaseUrl = (forceConnection) => {
  const useServer = forceConnection === 'mysql' && props.syncServerApiUrl;
  return useServer ? props.syncServerApiUrl.replace(/\/$/, '') : '';
};

// جلب كل البيانات (للتحديث الكامل)
const loadAllData = async () => {
  isRefreshing.value = true;
  try {
    connectionStatus.value.online = navigator.onLine;
    const forceConnection = selectedDatabase.value !== 'auto' ? selectedDatabase.value : 'auto';
    const baseUrl = getSyncMonitorBaseUrl(forceConnection);
    const url = baseUrl ? `${baseUrl}/api/sync-monitor/all-data` : '/api/sync-monitor/all-data';
    const isCrossOrigin = !!baseUrl;

    const response = await axios.get(url, { 
      params: { force_connection: forceConnection },
      withCredentials: !isCrossOrigin 
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
      
      // تحديث حالة التحميل
      loadedTabs.value.tables = true;
      loadedTabs.value.sync = true;
      loadedTabs.value.backups = true;
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

const loadServerTables = async () => {
  isRefreshing.value = true;
  try {
    toast.info('🔄 جاري تحميل جداول السيرفر...', { timeout: 2000 });
    const baseUrl = getSyncMonitorBaseUrl('mysql');
    const url = baseUrl ? `${baseUrl}/api/sync-monitor/all-data` : '/api/sync-monitor/all-data';
    const isCrossOrigin = !!baseUrl;

    const response = await axios.get(url, { 
      params: { force_connection: 'mysql' },
      withCredentials: !isCrossOrigin,
      timeout: 15000
    });
    
    if (response.data.success) {
      syncedTables.value = response.data.tables || [];
      
      const mysqlTables = syncedTables.value.filter(t => t.connection === 'mysql');
      toast.success(`✅ تم تحميل ${mysqlTables.length} جدول من السيرفر`, { timeout: 3000 });
      
      // تحديث معلومات قاعدة البيانات
      databaseInfo.value = response.data.database_info || {
        type: 'MySQL',
        total_tables: mysqlTables.length,
      };
    } else {
      toast.error(response.data.message || 'فشل تحميل جداول السيرفر');
    }
  } catch (error) {
    console.error('Error loading server tables:', error);
    if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
      toast.error('❌ انتهت مهلة الاتصال بالسيرفر', { timeout: 5000 });
    } else {
      toast.error('فشل تحميل جداول السيرفر: ' + (error.response?.data?.message || error.message));
    }
  } finally {
    isRefreshing.value = false;
  }
};

const syncAllTablesFromServer = async () => {
  if (!connectionStatus.value.online) {
    toast.error('❌ لا يوجد اتصال بالإنترنت');
    return;
  }

  // فحص الاتصال بالسيرفر أولاً
  if (!connectionStatus.value.serverChecked || !connectionStatus.value.serverAvailable) {
    toast.info('🔄 جاري فحص الاتصال بالسيرفر...', { timeout: 2000 });
    const serverAvailable = await checkRemoteConnection();
    if (!serverAvailable) {
      return;
    }
  }

  const confirmed = confirm('هل تريد مزامنة كل الجداول من السيرفر إلى المحلي؟\n\nسيتم جلب البيانات من MySQL وحفظها في SQLite المحلي.');
  if (!confirmed) return;

  isSyncing.value = true;
  try {
    toast.info('🔄 جاري مزامنة الجداول من السيرفر... قد يستغرق بعض الوقت', { timeout: 5000 });
    
    const response = await axios.post('/api/sync-monitor/sync', {
      direction: 'down', // من MySQL إلى SQLite
      tables: null, // كل الجداول
      safe_mode: false,
      create_backup: false
    }, { 
      withCredentials: true,
      timeout: 120000 // 2 دقيقة
    });
    
    if (response.data.success) {
      const results = response.data.results;
      const totalSynced = results?.total_synced || 0;
      const successCount = Object.keys(results?.success || {}).length;
      const failedCount = Object.keys(results?.failed || {}).length;
      
      let message = `✅ تمت المزامنة بنجاح!\n`;
      message += `📊 ${totalSynced} سجل من ${successCount} جدول\n`;
      if (failedCount > 0) {
        message += `⚠️ فشل ${failedCount} جدول`;
      }
      
      toast.success(message, { timeout: 5000 });
      
      // تحديث البيانات
      await loadAllData();
    } else {
      toast.error('فشلت المزامنة: ' + (response.data.message || 'خطأ غير معروف'));
    }
  } catch (error) {
    console.error('Error syncing from server:', error);
    if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
      toast.error('❌ انتهت مهلة المزامنة - الرجاء المحاولة مرة أخرى', { timeout: 5000 });
    } else {
      toast.error('فشلت المزامنة: ' + (error.response?.data?.message || error.message), { timeout: 5000 });
    }
  } finally {
    isSyncing.value = false;
  }
};

const syncAll = async () => {
  if (!connectionStatus.value.online) {
    toast.warning('غير متصل بالإنترنت');
    return;
  }

  // فحص الاتصال بالسيرفر أولاً
  if (!connectionStatus.value.serverChecked || !connectionStatus.value.serverAvailable) {
    toast.info('🔄 جاري فحص الاتصال بالسيرفر...', { timeout: 2000 });
    const serverAvailable = await checkRemoteConnection();
    if (!serverAvailable) {
      return;
    }
  }

  if (!confirm('هل تريد مزامنة الكل؟\n\nسيتم أولاً: 📥 سحب (MySQL → SQLite)\nثم: 📤 رفع الطابور (SQLite → Server)')) {
    return;
  }

  isSyncing.value = true;
  try {
    toast.info('🔄 بدء مزامنة الكل...', { timeout: 3000 });
    addSystemLog('info', 'بدء مزامنة الكل (Pull ثم Push)');

    // 1) Pull: MySQL → SQLite (في الخلفية لتجنب 504)
    addSystemLog('info', '📥 Pull: بدء سحب البيانات من السيرفر (في الخلفية)...');
    const pullResponse = await axios.post('/api/sync-monitor/sync', {
      direction: 'down',
      tables: null,
      safe_mode: false,
      create_backup: false,
      async: true
    }, { withCredentials: true, timeout: 15000 });

    if (!pullResponse.data?.success || !pullResponse.data?.job_id) {
      const msg = pullResponse.data?.message || pullResponse.data?.error || 'فشل بدء Pull';
      toast.error(msg);
      addSystemLog('error', 'فشل بدء Pull: ' + msg);
      return;
    }

    const pullJobId = pullResponse.data.job_id;
    addSystemLog('info', `تم بدء Pull (Job: ${pullJobId}) - جاري الانتظار...`);
    toast.info('📥 جاري السحب في الخلفية...', { timeout: 2000 });

    const pullCompleted = await pollSyncStatusUntilDone(pullJobId);
    if (!pullCompleted) {
      toast.warning('⚠️ لم يكتمل السحب أو انتهت المهلة', { timeout: 5000 });
      addSystemLog('warning', 'Pull: لم يكتمل أو انتهت المهلة');
    } else {
      const totalSynced = pullCompleted.results?.total_synced ?? 0;
      toast.success(`📥 تم السحب: ${totalSynced} سجل`, { timeout: 4000 });
      addSystemLog('success', `تم Pull بنجاح: ${totalSynced} سجل`);
    }

    // تحديث counts بعد Pull
    await loadAllData();

    // 2) Push: Smart Sync (SQLite → Server) عبر Queue
    if (syncStatus.value.pendingCount === 0) {
      toast.info('لا يوجد طابور Pending للرفع', { timeout: 3000 });
      addSystemLog('info', 'لا يوجد Pending للـ Push');
      return;
    }

    addSystemLog('info', `📤 Push: بدء Smart Sync للطابور (${syncStatus.value.pendingCount})...`);
    const pushResponse = await axios.post('/api/sync-monitor/smart-sync', {
      limit: 1000
    }, { withCredentials: true });

    if (!pushResponse.data?.success || !pushResponse.data?.job_id) {
      throw new Error(pushResponse.data?.message || 'فشل بدء Smart Sync');
    }

    const jobId = pushResponse.data.job_id;
    currentJobId.value = jobId;
    toast.info('📤 تم بدء الرفع في الخلفية (Job)', { timeout: 3000 });
    addSystemLog('info', `تم dispatch Job: ${jobId}`);

    // شغّل Worker مرة واحدة لمعالجة job (بدون تشغيل دائم)
    await runWorkerOnce();

    // Polling status (الموجود أصلاً)
    pollSyncStatus(jobId);
  } catch (error) {
    console.error('Error syncing:', error);
    const msg = error.response?.data?.message || error.response?.data?.error || error.message;
    toast.error('❌ فشلت المزامنة: ' + msg);
    addSystemLog('error', 'فشلت مزامنة الكل: ' + msg);
  }
  finally {
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
    addSystemLog('info', `بدء المزامنة: ${direction === 'up' ? 'SQLite → Server' : 'Server → SQLite'}`);

    const response = await axios.post('/api/sync-monitor/sync', {
      direction,
      tables: null,
      safe_mode: direction === 'up',
      create_backup: direction === 'up',
      force_full_sync: false
    }, {
      withCredentials: true,
      timeout: direction === 'down' ? 120000 : 60000
    });

    if (response.data.success) {
      toast.success(`✅ تمت المزامنة: ${response.data.results?.total_synced || 0} سجل`);
      addSystemLog('success', `نجحت المزامنة (${direction}): ${response.data.results?.total_synced || 0} سجل`);
      await loadAllData();
    } else {
      if (response.data.mysql_available === false) {
        toast.warning('⚠️ قاعدة MySQL البعيدة غير متاحة حالياً. يمكنك الاستمرار بالعمل على SQLite محلياً.');
        addSystemLog('warning', 'MySQL غير متاح - تم البقاء على SQLite');
      } else {
        toast.error(response.data.message || 'فشلت المزامنة');
        addSystemLog('error', 'فشلت المزامنة: ' + (response.data.message || ''));
      }
    }
  } catch (error) {
    toast.error('فشلت المزامنة: ' + (error.response?.data?.message || error.message));
    addSystemLog('error', 'فشلت المزامنة: ' + (error.response?.data?.message || error.message));
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

// فحص المزامنة - APIs
const checkSyncHealth = async () => {
  loadingHealth.value = true;
  try {
    const response = await axios.get('/api/sync-monitor/sync-health', { withCredentials: true });
    if (response.data.success) {
      syncHealth.value = response.data.health || response.data;
      // تحديث الإحصائيات من health check
      updateSyncStatsFromHealth();
      toast.success('✅ تم فحص الحالة العامة بنجاح');
    } else {
      toast.error(response.data.message || 'فشل فحص الحالة');
    }
  } catch (error) {
    console.error('Error checking sync health:', error);
    toast.error('فشل فحص الحالة: ' + (error.response?.data?.message || error.message));
  } finally {
    loadingHealth.value = false;
  }
};

const checkPendingChanges = async () => {
  loadingPending.value = true;
  try {
    const response = await axios.get('/api/sync-monitor/pending-changes', { withCredentials: true });
    if (response.data.success) {
      pendingChanges.value = response.data.pending_changes || response.data.changes || [];
      toast.success(`✅ تم جلب ${pendingChanges.value.length} تغيير معلق`);
    } else {
      toast.error(response.data.message || 'فشل جلب التغييرات المعلقة');
    }
  } catch (error) {
    console.error('Error checking pending changes:', error);
    toast.error('فشل جلب التغييرات المعلقة: ' + (error.response?.data?.message || error.message));
  } finally {
    loadingPending.value = false;
  }
};

const checkSyncMetadata = async () => {
  loadingMetadata.value = true;
  try {
    const response = await axios.get('/api/sync-monitor/metadata', { withCredentials: true });
    if (response.data.success) {
      syncMetadata.value.data = response.data.metadata || [];
      syncMetadata.value.stats = response.data.queue_stats || null;
      toast.success('✅ تم تحديث إحصائيات المزامنة بنجاح');
    } else {
      toast.error(response.data.message || 'فشل جلب الإحصائيات');
    }
  } catch (error) {
    console.error('Error checking sync metadata:', error);
    toast.error('فشل جلب الإحصائيات: ' + (error.response?.data?.message || error.message));
  } finally {
    loadingMetadata.value = false;
  }
};

// تحديث الإحصائيات تلقائياً عند فتح التبويب
const updateSyncStatsFromHealth = () => {
  if (syncHealth.value && syncHealth.value.sync_queue && syncHealth.value.sync_queue.stats) {
    syncMetadata.value.stats = syncHealth.value.sync_queue.stats;
  }
};

const startSmartSync = async () => {
  if (!connectionStatus.value.online) {
    toast.error('❌ لا يمكن المزامنة - أنت في وضع Offline');
    return;
  }

  // فحص الاتصال بالسيرفر أولاً
  if (!connectionStatus.value.serverChecked || !connectionStatus.value.serverAvailable) {
    const serverAvailable = await checkRemoteConnection();
    if (!serverAvailable) {
      return;
    }
  }

  if (!confirm('هل تريد بدء المزامنة الذكية؟\n\nسيتم مزامنة التغييرات المعلقة في sync_queue.')) {
    return;
  }

  isSyncing.value = true;
  try {
    const response = await axios.post('/api/sync-monitor/smart-sync', {
      limit: 100
    }, { withCredentials: true });

    if (response.data.success && response.data.job_id) {
      currentJobId.value = response.data.job_id;
      toast.success('✅ تم بدء المزامنة في الخلفية');
      addSystemLog('info', `تم dispatch Smart Sync Job: ${response.data.job_id}`);

      // شغّل Worker مرة واحدة لمعالجة job
      await runWorkerOnce();
      
      // بدء polling لحالة المزامنة
      pollSyncStatus(response.data.job_id);
    } else {
      toast.error(response.data.message || 'فشل بدء المزامنة');
      addSystemLog('error', response.data.message || 'فشل بدء Smart Sync');
    }
  } catch (error) {
    console.error('Error starting smart sync:', error);
    toast.error('فشل بدء المزامنة: ' + (error.response?.data?.message || error.message));
    addSystemLog('error', 'فشل بدء Smart Sync: ' + (error.response?.data?.message || error.message));
  } finally {
    isSyncing.value = false;
  }
};

// استعلام عن حالة job حتى اكتماله أو فشله (للمزامنة الكاملة في الخلفية)
const pollSyncStatusUntilDone = (jobId, maxWaitSeconds = 1800) => {
  const pollInterval = 2000;
  const maxAttempts = Math.ceil(maxWaitSeconds * 1000 / pollInterval);
  let attempts = 0;
  return new Promise((resolve) => {
    const tick = async () => {
      attempts++;
      try {
        const response = await axios.get('/api/sync-monitor/sync-status', {
          params: { job_id: jobId },
          withCredentials: true
        });
        if (response.data?.success && response.data?.status) {
          const st = response.data.status;
          const status = typeof st === 'object' ? st.status : st;
          if (status === 'completed') {
            resolve(st);
            return;
          }
          if (status === 'failed') {
            resolve({ ...st, results: { total_synced: 0 }, success: false });
            return;
          }
        }
      } catch (e) {
        console.warn('Poll sync status error:', e);
      }
      if (attempts >= maxAttempts) {
        resolve(null);
        return;
      }
      setTimeout(tick, pollInterval);
    };
    tick();
  });
};

const pollSyncStatus = async (jobId) => {
  const maxAttempts = 60; // 60 محاولة (دقيقة واحدة)
  let attempts = 0;
  
  const interval = setInterval(async () => {
    attempts++;
    try {
      const response = await axios.get('/api/sync-monitor/sync-status', {
        params: { job_id: jobId },
        withCredentials: true
      });

      if (response.data.success) {
        currentSyncStatus.value = response.data;
        const status = response.data.status?.status;

        if (status === 'completed' || status === 'failed') {
          clearInterval(interval);
          if (status === 'completed') {
            toast.success(`✅ اكتملت المزامنة: ${response.data.status?.synced || 0} سجل`);
          } else {
            toast.error('❌ فشلت المزامنة: ' + (response.data.status?.error || 'خطأ غير معروف'));
          }
          // تحديث البيانات
          await loadAllData();
          await checkPendingChanges();
        }
      } else if (response.data.status === 'not_found') {
        clearInterval(interval);
        currentSyncStatus.value = null;
      }
    } catch (error) {
      console.error('Error polling sync status:', error);
    }

    if (attempts >= maxAttempts) {
      clearInterval(interval);
      toast.warning('⏱️ انتهت مهلة الانتظار لحالة المزامنة');
    }
    }, 1000); // كل ثانية
};

const loadSyncQueueDetails = async (status = 'pending', offset = 0, limit = 50) => {
  loadingQueueDetails.value = true;
  try {
    const response = await axios.get('/api/sync-monitor/sync-queue-details', {
      params: {
        status: status,
        offset: offset,
        limit: limit
      },
      withCredentials: true
    });

    if (response.data.success) {
      syncQueueDetails.value = response.data;
      toast.success(`✅ تم جلب ${response.data.changes.length} سجل`);
    } else {
      toast.error(response.data.message || 'فشل جلب التفاصيل');
    }
  } catch (error) {
    console.error('Error loading sync queue details:', error);
    toast.error('فشل جلب التفاصيل: ' + (error.response?.data?.message || error.message));
  } finally {
    loadingQueueDetails.value = false;
  }
};

const loadErrorLog = async () => {
  loadingErrorLog.value = true;
  errorLogContent.value = '';
  errorLogLinesCount.value = null;
  errorLogTotalLines.value = null;
  try {
    const response = await axios.get('/api/sync-monitor/error-log', {
      params: { lines: 500 },
      withCredentials: true
    });
    if (response.data.success) {
      errorLogContent.value = response.data.content || '(فارغ)';
      errorLogLinesCount.value = response.data.lines_count ?? 0;
      errorLogTotalLines.value = response.data.total_lines ?? null;
      toast.success('تم تحميل لوغ الأخطاء');
    } else {
      toast.error(response.data.message || 'فشل تحميل اللوغ');
    }
  } catch (error) {
    toast.error('فشل تحميل اللوغ: ' + (error.response?.data?.message || error.message));
    errorLogContent.value = 'تعذر تحميل اللوغ.';
  } finally {
    loadingErrorLog.value = false;
  }
};

const clearErrorLog = async () => {
  if (!confirm('هل تريد إفراغ ملف لوغ الأخطاء (laravel.log)؟')) return;
  clearingErrorLog.value = true;
  try {
    const response = await axios.post('/api/sync-monitor/error-log/clear', {}, { withCredentials: true });
    if (response.data.success) {
      toast.success(response.data.message || 'تم إفراغ اللوغ');
      errorLogContent.value = '(تم الإفراغ)';
      errorLogLinesCount.value = 0;
      errorLogTotalLines.value = 0;
    } else {
      toast.error(response.data.message || 'فشل إفراغ اللوغ');
    }
  } catch (error) {
    toast.error('فشل إفراغ اللوغ: ' + (error.response?.data?.message || error.message));
  } finally {
    clearingErrorLog.value = false;
  }
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
  return new Date(dateString).toLocaleString('en-US');
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

// مقارنة الجداول
const compareTables = async () => {
  if (!compareTableName.value) {
    toast.error('يرجى إدخال اسم الجدول');
    return;
  }

  comparingTables.value = true;
  compareResult.value = null;

  try {
    const response = await axios.post('/api/sync-monitor/compare-tables', {
      table_name: compareTableName.value,
      limit: 1000
    }, { withCredentials: true });

    if (response.data.success) {
      compareResult.value = response.data;
      toast.success(`✅ تمت المقارنة: ${compareResult.value.summary?.is_identical ? 'البيانات متطابقة' : 'يوجد اختلافات'}`);
    } else {
      toast.error(response.data.message || 'فشلت المقارنة');
    }
  } catch (error) {
    console.error('Error comparing tables:', error);
    toast.error('فشلت المقارنة: ' + (error.response?.data?.message || error.message));
  } finally {
    comparingTables.value = false;
  }
};

// مزامنة جدول المقارنة
const syncCompareTable = async () => {
  if (!compareTableName.value) {
    toast.error('يرجى إدخال اسم الجدول أولاً');
    return;
  }

  if (!connectionStatus.value.online) {
    toast.error('❌ لا يمكن المزامنة - أنت في وضع Offline');
    return;
  }

  // سؤال المستخدم: هل يريد مزامنة sync_queue أم السجلات المفقودة؟
  const syncType = confirm(
    `مزامنة جدول "${compareTableName.value}"؟\n\n` +
    `نعم = مزامنة السجلات المفقودة (من SQLite إلى MySQL)\n` +
    `إلغاء = مزامنة sync_queue فقط`
  ) ? 'missing' : 'queue';

  if (syncType === 'queue') {
    // إلغاء - لا نعمل شيء
    return;
  }

  isSyncing.value = true;
  try {
    // مزامنة السجلات المفقودة
    const response = await axios.post('/api/sync-monitor/sync-missing-records', {
      table_name: compareTableName.value,
      limit: 1000
    }, { withCredentials: true });

    if (response.data.success) {
      toast.success(
        `✅ تمت المزامنة: ${response.data.synced} نجحت، ${response.data.failed} فشلت` +
        (response.data.total_missing > 0 ? ` (${response.data.total_missing} سجل مفقود)` : '')
      );
      
      // تحديث المقارنة بعد قليل
      setTimeout(() => {
        compareTables();
      }, 2000);
    } else {
      toast.error(response.data.message || 'فشلت المزامنة');
    }
  } catch (error) {
    console.error('Error syncing compare table:', error);
    toast.error('فشلت المزامنة: ' + (error.response?.data?.message || error.message));
  } finally {
    isSyncing.value = false;
  }
};

// Event Listeners
const handleOnline = async () => {
  connectionStatus.value.online = true;
  toast.success('🌐 عاد الاتصال!');
  
  // فحص الاتصال بالسيرفر عند عودة الإنترنت
  await checkRemoteConnection();
  loadAllData();
};

const handleOffline = () => {
  connectionStatus.value.online = false;
  toast.warning('📴 فقدان الاتصال');
};

// Watcher لجلب بيانات التاب عند تغييره
watch(activeTab, async (newTab) => {
  await loadTabData(newTab);
});

onMounted(async () => {
  // جلب البيانات الأساسية فقط (lightweight)
  await loadEssentialData();
  
  // جلب بيانات التاب النشط (tables بشكل افتراضي)
  await loadTabData(activeTab.value);
  
  // جلب Migrations
  await loadMigrations();
  
  // Event listeners
  window.addEventListener('online', handleOnline);
  window.addEventListener('offline', handleOffline);
});

onUnmounted(() => {
  window.removeEventListener('online', handleOnline);
  window.removeEventListener('offline', handleOffline);
});

// ============================================
// Database Migrations Functions
// ============================================
async function loadMigrations() {
  loadingMigrations.value = true;
  try {
    const response = await axios.get('/api/sync-monitor/migrations', {
      params: {
        show_executed: showExecutedMigrations.value
      }
    });
    migrations.value = response.data.migrations || [];
    dbMigrationStats.value = {
      total_pending: response.data.total_pending || 0,
      total_executed: response.data.total_executed || 0
    };
  } catch (error) {
    console.error('Error loading migrations:', error);
    toast.error('فشل تحميل Migrations');
  } finally {
    loadingMigrations.value = false;
  }
}

async function checkMigrationSafety(migrationName) {
  try {
    const response = await axios.post('/api/sync-monitor/check-migration', {
      migration_name: migrationName
    });
    
    if (response.data.success && response.data.has_data) {
      return {
        hasData: true,
        tables: response.data.tables_with_data,
        totalRecords: response.data.total_records
      };
    }
    return { hasData: false };
  } catch (error) {
    console.error('Error checking migration safety:', error);
    return { hasData: false };
  }
}

async function runSpecificMigration(migrationName, force = false) {
  // Check for data before running
  if (!force) {
    const safetyCheck = await checkMigrationSafety(migrationName);
    
    if (safetyCheck.hasData) {
      migrationWarning.value = {
        migration: migrationName,
        tables: safetyCheck.tables,
        total_records: safetyCheck.totalRecords
      };
      return;
    }
  }

  runningMigration.value = migrationName;
  migrationOutput.value = '';
  migrationWarning.value = null;

  try {
    const response = await axios.post('/api/sync-monitor/run-migration', {
      migration_name: migrationName,
      force: force
    });

    if (response.data.success) {
      toast.success('تم تنفيذ Migration بنجاح');
      migrationOutput.value = response.data.output || '';
      await loadMigrations();
    } else {
      // Check if it's a warning about data
      if (response.data.warning && response.data.table) {
        migrationWarning.value = {
          migration: migrationName,
          table: response.data.table,
          record_count: response.data.record_count
        };
      } else {
        toast.error(response.data.error || 'فشل تنفيذ Migration');
        migrationOutput.value = response.data.output || response.data.error || '';
      }
    }
  } catch (error) {
    console.error('Error running migration:', error);
    
    if (error.response?.data?.warning) {
      migrationWarning.value = {
        migration: migrationName,
        table: error.response.data.table,
        record_count: error.response.data.record_count
      };
    } else {
      toast.error(error.response?.data?.error || error.message || 'فشل تنفيذ Migration');
      migrationOutput.value = error.response?.data?.output || error.message || '';
    }
  } finally {
    runningMigration.value = null;
  }
}

function forceRunMigration(migrationName) {
  runSpecificMigration(migrationName, true);
}
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
