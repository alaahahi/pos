 
<template>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Action Buttons -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="bi bi-gear"></i> إجراءات المايكريشنات
            </h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-2">
                <button 
                  @click="runMigrations" 
                  :disabled="loading || migrations.pending.length === 0"
                  class="btn btn-success w-100"
                >
                  <i class="bi bi-play-circle" v-if="!loading"></i>
                  <span class="spinner-border spinner-border-sm me-2" v-if="loading"></span>
                  {{ translations.run_pending }}
                  <span class="badge bg-light text-dark ms-2" v-if="migrations.pending.length > 0">
                    {{ migrations.pending.length }}
                  </span>
                </button>
              </div>
              <div class="col-md-2">
                <button 
                  @click="rollbackMigrations" 
                  :disabled="loading || migrations.executed.length === 0"
                  class="btn btn-warning w-100"
                >
                  <i class="bi bi-arrow-counterclockwise" v-if="!loading"></i>
                  <span class="spinner-border spinner-border-sm me-2" v-if="loading"></span>
                  {{ translations.rollback_last }}
                </button>
              </div>
              <div class="col-md-2">
                <button 
                  @click="refreshMigrations" 
                  :disabled="loading"
                  class="btn btn-danger w-100"
                >
                  <i class="bi bi-arrow-repeat" v-if="!loading"></i>
                  <span class="spinner-border spinner-border-sm me-2" v-if="loading"></span>
                  {{ translations.refresh_all }}
                </button>
              </div>
              <div class="col-md-3">
                <button 
                  @click="runSeeders" 
                  :disabled="loading"
                  class="btn btn-info w-100"
                >
                  <i class="bi bi-database-add" v-if="!loading"></i>
                  <span class="spinner-border spinner-border-sm me-2" v-if="loading"></span>
                  {{ translations.run_seeders }}
                </button>
              </div>
              <div class="col-md-3">
                <button 
                  @click="refreshData" 
                  :disabled="loading"
                  class="btn btn-outline-primary w-100"
                >
                  <i class="bi bi-arrow-clockwise"></i>
                  تحديث البيانات
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Migration Status -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="bi bi-list-check"></i> {{ translations.migration_status }}
            </h5>
          </div>
          <div class="card-body">
            <!-- All Migrations -->
            <div v-if="migrations.all && migrations.all.length > 0">
              <h6 class="text-primary">
                <i class="bi bi-list-ul"></i> جميع المايكريشنات ({{ migrations.all.length }})
              </h6>
              <div class="table-responsive">
                <table class="table table-sm table-hover">
                  <thead>
                    <tr>
                      <th>{{ translations.migration_name }}</th>
                      <th>الحالة</th>
                      <th>{{ translations.batch }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="migration in migrations.all" :key="migration.migration" 
                        :class="{ 'table-warning': migration.status === 'Pending', 'table-success': migration.status === 'Ran' }">
                      <td>
                        <code>{{ migration.migration }}</code>
                      </td>
                      <td>
                        <span v-if="migration.status === 'Ran'" class="badge bg-success">
                          <i class="bi bi-check-circle"></i> منفذ
                        </span>
                        <span v-else class="badge bg-warning">
                          <i class="bi bi-clock"></i> معلق
                        </span>
                      </td>
                      <td>
                        <span v-if="migration.batch !== 'N/A'" class="badge bg-info">{{ migration.batch }}</span>
                        <span v-else class="text-muted">-</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Summary -->
            <div class="row mt-3">
              <div class="col-md-6">
                <div class="alert alert-warning">
                  <i class="bi bi-clock"></i>
                  <strong>المايكريشنات المعلقة:</strong> {{ migrations.pending ? migrations.pending.length : 0 }}
                </div>
              </div>
              <div class="col-md-6">
                <div class="alert alert-success">
                  <i class="bi bi-check-circle"></i>
                  <strong>المايكريشنات المنفذة:</strong> {{ migrations.executed ? migrations.executed.length : 0 }}
                </div>
              </div>
            </div>

            <!-- No Migrations -->
            <div v-if="!migrations.all || migrations.all.length === 0" class="text-center text-muted py-4">
              <i class="bi bi-inbox display-4"></i>
              <p class="mt-2">لا توجد مايكريشنات</p>
            </div>
          </div>
        </div>

        <!-- Tables Information -->
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="bi bi-table"></i>
              {{ translations.table_info }}
            </h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped table-hover">
                <thead>
                  <tr>
                    <th>{{ translations.table_name }}</th>
                    <th>{{ translations.table_rows }}</th>
                    <th>{{ translations.table_size }}</th>
                    <th>المحرك</th>
                    <th>الترميز</th>
                    <th>{{ translations.last_modified }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="table in tables" :key="table.name">
                    <td>
                      <strong>{{ table.name }}</strong>
                    </td>
                    <td>
                      <span class="badge bg-info">{{ formatNumber(table.rows) }}</span>
                    </td>
                    <td>{{ table.size }}</td>
                    <td>
                      <span class="badge bg-secondary">{{ table.engine }}</span>
                    </td>
                    <td>
                      <small class="text-muted">{{ table.collation }}</small>
                    </td>
                    <td>
                      <small class="text-muted">{{ formatDate(table.updated) }}</small>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Output Modal -->
        <div v-if="showOutputModal" class="modal-mask">
          <div class="modal-wrapper">
            <div class="modal-container modal-lg">
              <div class="modal-header">
                <h5 class="modal-title">نتيجة تنفيذ المايكريشن</h5>
                <button @click="showOutputModal = false" class="btn-close"></button>
              </div>
              <div class="modal-body">
                <div class="alert" :class="outputSuccess ? 'alert-success' : 'alert-danger'">
                  <i class="bi" :class="outputSuccess ? 'bi-check-circle' : 'bi-exclamation-triangle'"></i>
                  {{ outputMessage }}
                </div>
                <div v-if="outputSuggestion" class="alert alert-info">
                  <i class="bi bi-lightbulb"></i>
                  <strong>اقتراح:</strong> {{ outputSuggestion }}
                </div>
                <div v-if="outputText" class="bg-dark text-light p-3 rounded" style="font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto;">
                  {{ outputText }}
                </div>
              </div>
              <div class="modal-footer">
                <button @click="showOutputModal = false" class="btn btn-secondary">إغلاق</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
 </template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
 
const props = defineProps({
  migrations: Object,
  tables: Array,
  translations: Object
})

// State
const loading = ref(false)
const showOutputModal = ref(false)
const outputMessage = ref('')
const outputText = ref('')
const outputSuccess = ref(false)
const outputSuggestion = ref('')

// Methods
const runMigrations = async () => {
  if (confirm('هل أنت متأكد من تشغيل المايكريشنات المعلقة؟')) {
    loading.value = true
    try {
      const response = await fetch('/admin/migrations/run?key=migrate123', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
      
      const data = await response.json()
      
      outputMessage.value = data.message
      outputText.value = data.output ? data.output.join('\n') : ''
      outputSuccess.value = data.success
      outputSuggestion.value = data.suggestion || ''
      showOutputModal.value = true
      
      if (data.success) {
        // Refresh the page data
        router.reload()
      }
    } catch (error) {
      outputMessage.value = 'حدث خطأ في الاتصال'
      outputText.value = error.message
      outputSuccess.value = false
      outputSuggestion.value = 'تحقق من اتصال الإنترنت وحاول مرة أخرى'
      showOutputModal.value = true
    } finally {
      loading.value = false
    }
  }
}

const rollbackMigrations = async () => {
  if (confirm(props.translations.confirm_rollback)) {
    loading.value = true
    try {
      const response = await fetch('/admin/migrations/rollback?key=migrate123', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
      
      const data = await response.json()
      
      outputMessage.value = data.message
      outputText.value = data.output ? data.output.join('\n') : ''
      outputSuccess.value = data.success
      outputSuggestion.value = data.suggestion || ''
      showOutputModal.value = true
      
      if (data.success) {
        // Refresh the page data
        router.reload()
      }
    } catch (error) {
      outputMessage.value = 'حدث خطأ في الاتصال'
      outputText.value = error.message
      outputSuccess.value = false
      outputSuggestion.value = 'تحقق من اتصال الإنترنت وحاول مرة أخرى'
      showOutputModal.value = true
    } finally {
      loading.value = false
    }
  }
}

const refreshMigrations = async () => {
  if (confirm(props.translations.confirm_refresh)) {
    loading.value = true
    try {
      const response = await fetch('/admin/migrations/refresh?key=migrate123', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
      
      const data = await response.json()
      
      outputMessage.value = data.message
      outputText.value = data.output ? data.output.join('\n') : ''
      outputSuccess.value = data.success
      outputSuggestion.value = data.suggestion || ''
      showOutputModal.value = true
      
      if (data.success) {
        // Refresh the page data
        router.reload()
      }
    } catch (error) {
      outputMessage.value = 'حدث خطأ في الاتصال'
      outputText.value = error.message
      outputSuccess.value = false
      outputSuggestion.value = 'تحقق من اتصال الإنترنت وحاول مرة أخرى'
      showOutputModal.value = true
    } finally {
      loading.value = false
    }
  }
}

const runSeeders = async () => {
  if (confirm(props.translations.confirm_seeders)) {
    loading.value = true
    try {
      const response = await fetch('/admin/migrations/seeders?key=migrate123', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
      
      const data = await response.json()
      
      outputMessage.value = data.message
      outputText.value = data.output ? data.output.join('\n') : ''
      outputSuccess.value = data.success
      outputSuggestion.value = data.suggestion || ''
      showOutputModal.value = true
      
      if (data.success) {
        // Refresh the page data
        router.reload()
      }
    } catch (error) {
      outputMessage.value = 'حدث خطأ في الاتصال'
      outputText.value = error.message
      outputSuccess.value = false
      outputSuggestion.value = 'تحقق من اتصال الإنترنت وحاول مرة أخرى'
      showOutputModal.value = true
    } finally {
      loading.value = false
    }
  }
}

const refreshData = () => {
  router.reload()
}

const formatDate = (date) => {
  if (!date) return 'غير محدد'
  return new Date(date).toLocaleString('ar-SA')
}

const formatNumber = (number) => {
  return new Intl.NumberFormat('ar-SA').format(number)
}

onMounted(() => {
  // Auto refresh every 30 seconds
  setInterval(() => {
    if (!loading.value) {
      refreshData()
    }
  }, 30000)
})
</script>

<style scoped>
.modal-mask {
  position: fixed;
  z-index: 9998;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  transition: opacity 0.3s ease;
}

.modal-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

.modal-container {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
  transition: all 0.3s ease;
  max-width: 90%;
  max-height: 90%;
  overflow-y: auto;
}

.modal-lg {
  width: 800px;
}

.modal-header {
  padding: 1rem;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body {
  padding: 1rem;
}

.modal-footer {
  padding: 1rem;
  border-top: 1px solid #dee2e6;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

.list-group-item {
  border-left: none;
  border-right: none;
}

.list-group-item:first-child {
  border-top: none;
}

.list-group-item:last-child {
  border-bottom: none;
}
</style>
