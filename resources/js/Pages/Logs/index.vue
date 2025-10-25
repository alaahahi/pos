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
          <!-- Search and Filters -->
          <div class="row mb-4">
            <div class="col-12">
              <div class="input-group mb-3">
                <span class="input-group-text bg-primary text-white">
                  <i class="bi bi-search"></i>
                </span>
                <input 
                  type="text" 
                  v-model="searchQuery"
                  @input="handleSearch"
                  class="form-control form-control-lg" 
                  placeholder="بحث في السجلات..."
                >
                <button 
                  v-if="searchQuery"
                  @click="clearSearch" 
                  class="btn btn-outline-secondary"
                  type="button"
                >
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Filters -->
          <div class="row mb-3 g-3">
            <div class="col-md-3">
              <label class="form-label fw-bold">
                <i class="bi bi-box-seam text-primary me-1"></i>
                الوحدة
              </label>
              <select v-model="filters.module_name" @change="applyFilters" class="form-select">
                <option value="">جميع الوحدات</option>
                <option v-for="module in modules" :key="module" :value="module">
                  {{ module }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">
                <i class="bi bi-lightning text-warning me-1"></i>
                العملية
              </label>
              <select v-model="filters.action" @change="applyFilters" class="form-select">
                <option value="">جميع العمليات</option>
                <option v-for="action in actions" :key="action" :value="action">
                  {{ action }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">
                <i class="bi bi-person text-info me-1"></i>
                المستخدم
              </label>
              <select v-model="filters.by_user_id" @change="applyFilters" class="form-select">
                <option value="">جميع المستخدمين</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">
                <i class="bi bi-calendar text-success me-1"></i>
                التاريخ
              </label>
              <input 
                type="date" 
                v-model="filters.date" 
                @change="applyFilters" 
                class="form-control"
              >
            </div>
          </div>

          <!-- Active Filters Display -->
          <div v-if="hasActiveFilters" class="mb-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="badge bg-secondary">الفلاتر النشطة:</span>
              <span v-if="filters.module_name" class="badge bg-primary">
                {{ filters.module_name }}
                <i @click="clearFilter('module_name')" class="bi bi-x ms-1" style="cursor: pointer;"></i>
              </span>
              <span v-if="filters.action" class="badge bg-warning">
                {{ filters.action }}
                <i @click="clearFilter('action')" class="bi bi-x ms-1" style="cursor: pointer;"></i>
              </span>
              <span v-if="filters.by_user_id" class="badge bg-info">
                {{ getUserName(filters.by_user_id) }}
                <i @click="clearFilter('by_user_id')" class="bi bi-x ms-1" style="cursor: pointer;"></i>
              </span>
              <span v-if="filters.date" class="badge bg-success">
                {{ filters.date }}
                <i @click="clearFilter('date')" class="bi bi-x ms-1" style="cursor: pointer;"></i>
              </span>
              <button @click="clearAllFilters" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-x-circle me-1"></i>
                مسح الكل
              </button>
            </div>
          </div>

          <!-- Logs Table -->
          <div class="table-responsive">
            <table class="table table-hover table-striped align-middle">
              <thead class="table-primary">
                <tr>
                  <th scope="col" class="text-center" style="width: 50px;">#</th>
                  <th scope="col">
                    <i class="bi bi-person me-1"></i>
                    المستخدم
                  </th>
                  <th scope="col">
                    <i class="bi bi-box-seam me-1"></i>
                    الوحدة
                  </th>
                  <th scope="col">
                    <i class="bi bi-lightning me-1"></i>
                    العملية
                  </th>
                  <th scope="col" class="text-center">
                    <i class="bi bi-hash me-1"></i>
                    معرف السجل
                  </th>
                  <th scope="col">
                    <i class="bi bi-clock me-1"></i>
                    التاريخ
                  </th>
                  <th scope="col" class="text-center" style="width: 120px;">الإجراءات</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="logs.data.length === 0">
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox display-4 d-block mb-3"></i>
                    <h5>لا توجد سجلات</h5>
                    <p>جرب تغيير الفلاتر أو البحث</p>
                  </td>
                </tr>
                <tr v-for="(log, index) in logs.data" :key="log.id">
                  <th scope="row" class="text-center">{{ logs.from + index }}</th>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="avatar-circle me-2">
                        <i class="bi bi-person-fill"></i>
                      </div>
                      <strong>{{ log.user?.name || 'غير محدد' }}</strong>
                    </div>
                  </td>
                  <td>
                    <span :class="['badge', 'rounded-pill', getModuleBadgeClass(log.module_name)]">
                      {{ log.module_name }}
                    </span>
                  </td>
                  <td>
                    <span :class="['badge', 'rounded-pill', getActionBadgeClass(log.action)]">
                      <i :class="getActionIcon(log.action)"></i>
                      {{ translateAction(log.action) }}
                    </span>
                  </td>
                  <td class="text-center">
                    <code class="bg-light px-2 py-1 rounded">{{ log.affected_record_id }}</code>
                  </td>
                  <td>
                    <small class="text-muted">
                      <i class="bi bi-calendar3 me-1"></i>
                      {{ formatDate(log.created_at) }}
                    </small>
                  </td>
                  <td class="text-center">
                    <div class="btn-group" role="group">
                      <Link 
                        :href="route('logs.view', log.id)" 
                        class="btn btn-sm btn-outline-primary"
                        title="عرض التفاصيل"
                      >
                        <i class="bi bi-eye"></i>
                      </Link>
                      <button 
                        v-if="canUndo(log)"
                        @click="undoLog(log.id)" 
                        class="btn btn-sm btn-outline-warning"
                        title="التراجع"
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
                  <a 
                    v-if="link.url" 
                    :href="link.url" 
                    class="page-link"
                    @click.prevent="router.get(link.url)"
                    v-html="link.label"
                  ></a>
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
import { ref, watch, computed } from 'vue';

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
const searchQuery = ref('');

// Reactive filters
const filters = ref({
  module_name: props.filters?.module_name || '',
  action: props.filters?.action || '',
  by_user_id: props.filters?.by_user_id || '',
  date: props.filters?.date || '',
  search: '',
});

// Apply filters
const applyFilters = () => {
  router.get(route('logs'), filters.value, {
    preserveState: true,
    replace: true,
  });
};

// Format date - دالة محسنة لمعالجة جميع صيغ التاريخ
const formatDate = (date) => {
  if (!date) return '-';
  
  try {
    // إذا كان التاريخ string، نحاول معالجته
    let dateString = date;
    
    // إذا كان object وله date property
    if (typeof date === 'object' && date.date) {
      dateString = date.date;
    }
    
    // تنظيف التاريخ من الأحرف الغريبة
    if (typeof dateString === 'string') {
      // إزالة timezone info إذا كان موجود
      dateString = dateString.replace(/\.\d+Z?$/, '').trim();
    }
    
    // تحويل التاريخ إلى Date object
    const dateObj = new Date(dateString);
    
    // التحقق من صحة التاريخ
    if (isNaN(dateObj.getTime())) {
      console.warn('تاريخ غير صالح:', date);
      return '-';
    }
    
    // تنسيق التاريخ بالميلادي الأمريكي
    const formatted = new Intl.DateTimeFormat('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    }).format(dateObj);
    
    return formatted;
  } catch (error) {
    console.error('خطأ في تنسيق التاريخ:', error, 'القيمة:', date);
    return '-';
  }
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

// Search handler
const handleSearch = () => {
  filters.value.search = searchQuery.value;
  applyFilters();
};

// Clear search
const clearSearch = () => {
  searchQuery.value = '';
  filters.value.search = '';
  applyFilters();
};

// Check if has active filters
const hasActiveFilters = computed(() => {
  return filters.value.module_name || 
         filters.value.action || 
         filters.value.by_user_id || 
         filters.value.date;
});

// Clear single filter
const clearFilter = (filterName) => {
  filters.value[filterName] = '';
  applyFilters();
};

// Clear all filters
const clearAllFilters = () => {
  filters.value = {
    module_name: '',
    action: '',
    by_user_id: '',
    date: '',
    search: filters.value.search,
  };
  applyFilters();
};

// Get user name by ID
const getUserName = (userId) => {
  const user = props.users.find(u => u.id == userId);
  return user ? user.name : 'غير محدد';
};

// Get module badge class
const getModuleBadgeClass = (moduleName) => {
  const moduleColors = {
    'Product': 'bg-primary',
    'Order': 'bg-success',
    'User': 'bg-info',
    'Customer': 'bg-warning',
    'Supplier': 'bg-secondary',
    'Expense': 'bg-danger',
    'Box': 'bg-dark',
  };
  return moduleColors[moduleName] || 'bg-secondary';
};

// Get action badge class
const getActionBadgeClass = (action) => {
  const actionColors = {
    'Created': 'bg-success',
    'Updated': 'bg-warning',
    'Deleted': 'bg-danger',
    'Status Toggled': 'bg-info',
    'Restored': 'bg-primary',
  };
  return actionColors[action] || 'bg-secondary';
};

// Get action icon
const getActionIcon = (action) => {
  const actionIcons = {
    'Created': 'bi bi-plus-circle me-1',
    'Updated': 'bi bi-pencil-square me-1',
    'Deleted': 'bi bi-trash me-1',
    'Status Toggled': 'bi bi-toggle-on me-1',
    'Restored': 'bi bi-arrow-counterclockwise me-1',
  };
  return actionIcons[action] || 'bi bi-circle me-1';
};

// Translate action to Arabic
const translateAction = (action) => {
  const translations = {
    'Created': 'إنشاء',
    'Updated': 'تحديث',
    'Deleted': 'حذف',
    'Status Toggled': 'تغيير الحالة',
    'Restored': 'استعادة',
  };
  return translations[action] || action;
};
</script>

<style scoped>
.avatar-circle {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 16px;
}

.table-hover tbody tr {
  transition: all 0.2s ease;
}

.table-hover tbody tr:hover {
  background-color: #f8f9fa;
  transform: scale(1.01);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.badge {
  font-size: 0.85rem;
  padding: 0.5em 0.8em;
  font-weight: 600;
}

.badge i {
  font-size: 0.9rem;
}

.card-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 2rem;
}

.btn-group .btn {
  transition: all 0.2s ease;
}

.btn-group .btn:hover {
  transform: translateY(-2px);
}

code {
  font-family: 'Courier New', monospace;
  font-size: 0.9rem;
}

.form-control:focus,
.form-select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.input-group-text {
  border-radius: 10px 0 0 10px;
}

.form-control-lg {
  border-radius: 0 10px 10px 0;
}

.pagination .page-link {
  color: #667eea;
  border-radius: 5px;
  margin: 0 2px;
}

.pagination .page-item.active .page-link {
  background-color: #667eea;
  border-color: #667eea;
}

.pagination .page-link:hover {
  background-color: #f0f2ff;
  color: #667eea;
}
</style>