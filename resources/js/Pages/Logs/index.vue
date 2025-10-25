<template>
  <AuthenticatedLayout :translations="translations">
    

    <section class="section dashboard">
      <!-- Statistics Cards -->
      <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
          <div class="card info-card">
            <div class="card-body">
              <h5 class="card-title">إجمالي السجلات</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-journal-text"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ statistics?.total || 0 }}</h6>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card info-card">
            <div class="card-body">
              <h5 class="card-title">اليوم</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-calendar-day"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ statistics?.today || 0 }}</h6>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card info-card">
            <div class="card-body">
              <h5 class="card-title">هذا الأسبوع</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-calendar-week"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ statistics?.this_week || 0 }}</h6>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card info-card">
            <div class="card-body">
              <h5 class="card-title">هذا الشهر</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-calendar-month"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ statistics?.this_month || 0 }}</h6>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Card -->
      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="bi bi-journal-text me-2"></i>
              {{ translations.logs }}
            </h5>
            
            <!-- Clean Old Logs Button -->
            <div class="d-flex gap-2">
              <button 
                type="button" 
                class="btn btn-warning btn-sm"
                data-bs-toggle="modal" 
                data-bs-target="#cleanLogsModal"
              >
                <i class="bi bi-trash me-1"></i>
                تنظيف السجلات القديمة
              </button>
            </div>
          </div>
        </div>

        <div class="card-body">
          <!-- Filters -->
          <div class="row mb-3">
            <div class="col-md-3">
              <label class="form-label">الوحدة</label>
              <select v-model="filters.module_name" @change="applyFilters" class="form-select">
                <option value="">جميع الوحدات</option>
                <option v-for="module in modules" :key="module" :value="module">
                  {{ module }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">العملية</label>
              <select v-model="filters.action" @change="applyFilters" class="form-select">
                <option value="">جميع العمليات</option>
                <option v-for="action in actions" :key="action" :value="action">
                  {{ action }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">المستخدم</label>
              <select v-model="filters.by_user_id" @change="applyFilters" class="form-select">
                <option value="">جميع المستخدمين</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">التاريخ</label>
              <input 
                type="date" 
                v-model="filters.date" 
                @change="applyFilters" 
                class="form-control"
              >
            </div>
          </div>

          <!-- Logs Table -->
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">المستخدم</th>
                  <th scope="col">الوحدة</th>
                  <th scope="col">العملية</th>
                  <th scope="col">معرف السجل</th>
                  <th scope="col">التاريخ</th>
                  <th scope="col">الإجراءات</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(log, index) in logs.data" :key="log.id">
                  <th scope="row">{{ index + 1 }}</th>
                  <td>{{ log.user?.name || 'غير محدد' }}</td>
                  <td>
                    <span class="badge bg-primary">{{ log.module_name }}</span>
                  </td>
                  <td>
                    <span :class="['badge', 'bg-' + log.badge]">{{ log.action }}</span>
                  </td>
                  <td>{{ log.affected_record_id }}</td>
                  <td>{{ formatDate(log.created_at) }}</td>
                  <td>
                    <div class="d-flex gap-1">
                      <Link 
                        :href="route('logs.view', log.id)" 
                        class="btn btn-sm btn-outline-primary"
                      >
                        <i class="bi bi-eye"></i>
                      </Link>
                      <button 
                        v-if="canUndo(log)"
                        @click="undoLog(log.id)" 
                        class="btn btn-sm btn-outline-warning"
                      >
                        <i class="bi bi-arrow-counterclockwise"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="d-flex justify-content-center mt-3">
            <nav>
              <ul class="pagination">
                <li v-for="link in logs.links" :key="link.label" class="page-item" :class="{ 'active': link.active, 'disabled': !link.url }">
                  <Link 
                    v-if="link.url" 
                    :href="link.url" 
                    class="page-link"
                    v-html="link.label"
                  ></Link>
                  <span v-else class="page-link" v-html="link.label"></span>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </section>

    <!-- Clean Logs Modal -->
    <div class="modal fade" id="cleanLogsModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">تنظيف السجلات القديمة</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form @submit.prevent="cleanOldLogs">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">حذف السجلات الأقدم من (أيام)</label>
                <input 
                  type="number" 
                  v-model="cleanDays" 
                  class="form-control" 
                  min="1" 
                  max="365"
                  required
                >
                <div class="form-text">
                  سيتم حذف جميع السجلات الأقدم من {{ cleanDays }} يوم
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
              <button type="submit" class="btn btn-warning">تنظيف</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
  logs: Object,
  users: Array,
  modules: Array,
  actions: Array,
  statistics: Object,
  filters: Object,
  translations: Object,
});

const cleanDays = ref(30);

// Reactive filters
const filters = ref({
  module_name: props.filters?.module_name || '',
  action: props.filters?.action || '',
  by_user_id: props.filters?.by_user_id || '',
  date: props.filters?.date || '',
});

// Apply filters
const applyFilters = () => {
  router.get(route('logs'), filters.value, {
    preserveState: true,
    replace: true,
  });
};

// Format date
const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('ar-SA', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Check if log can be undone
const canUndo = (log) => {
  return ['Created', 'Updated', 'Deleted'].includes(log.action);
};

// Undo log
const undoLog = (logId) => {
  if (confirm('هل أنت متأكد من التراجع عن هذا الإجراء؟')) {
    router.post(route('logs.undo', logId));
  }
};

// Clean old logs
const cleanOldLogs = () => {
  if (confirm(`هل أنت متأكد من حذف السجلات الأقدم من ${cleanDays.value} يوم؟`)) {
    router.post(route('logs.clean'), { days: cleanDays.value });
  }
};

// Watch for filter changes
watch(filters, () => {
  applyFilters();
}, { deep: true });
</script>