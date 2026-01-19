<template>
  <AuthenticatedLayout>
      <div class="d-flex justify-content-between align-items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          📋 {{ translations.decoration_orders || 'طلبات الديكور' }}
        </h2>
        <div class="d-flex gap-2">
          <button 
            v-if="hasPermission('create decoration')" 
            @click="openCreateModal" 
            class="btn btn-success"
          >
            <i class="bi bi-plus-circle"></i> إضافة طلب جديد
          </button>
          
        </div>
      </div>

    <div class="py-4">
      <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row mb-4">
          <div class="col-md-3">
            <div class="stat-card bg-primary">
              <div class="stat-icon">
                <i class="bi bi-list-check"></i>
              </div>
              <div class="stat-content">
                <h3>{{ orders.total || 0 }}</h3>
                <p>إجمالي الطلبات</p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stat-card bg-warning">
              <div class="stat-icon">
                <i class="bi bi-clock-history"></i>
              </div>
              <div class="stat-content">
                <h3>{{ pendingCount }}</h3>
                <p>قيد التنفيذ</p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stat-card bg-success">
              <div class="stat-icon">
                <i class="bi bi-check-circle"></i>
              </div>
              <div class="stat-content">
                <h3>{{ completedCount }}</h3>
                <p>المكتملة</p>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stat-card bg-info">
              <div class="stat-icon">
                <i class="bi bi-cash-stack"></i>
              </div>
              <div class="stat-content">
                <h3>{{ formatCurrency(totalRevenue) }}</h3>
                <p>إجمالي الإيرادات</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters and Search -->
        <div class="card mb-4 shadow-sm">
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-5">
                <div class="search-box">
                  <i class="bi bi-search search-icon"></i>
                  <input 
                    type="text" 
                    class="form-control ps-5" 
                    v-model="searchForm.search" 
                    @input="debouncedSearch"
                    placeholder="🔍 ابحث عن اسم الزبون، رقم الهاتف، أو اسم الديكور..."
                  >
                </div>
              </div>
              <div class="col-md-3">
                <select class="form-select" v-model="searchForm.status" @change="applyFilters">
                  <option value="">📊 كل الحالات</option>
                  <option value="created">📝 تم الإنشاء</option>
                  <option value="received">📥 تم الاستلام</option>
                  <option value="executing">⚙️ قيد التنفيذ</option>
                  <option value="partial_payment">💰 دفعة جزئية</option>
                  <option value="full_payment">💵 دفعة كاملة</option>
                  <option value="completed">✅ مكتمل</option>
                  <option value="cancelled">❌ ملغي</option>
                </select>
              </div>
              <div class="col-md-2">
                <select class="form-select" v-model="searchForm.employee" @change="applyFilters">
                  <option value="">👤 كل الموظفين</option>
                  <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                    {{ employee.name }}
                  </option>
                </select>
              </div>
              <div class="col-md-2">
                <input type="date" class="form-control" v-model="searchForm.date_from" @change="applyFilters" placeholder="من تاريخ">
              </div>
              <div class="col-md-2">
                <input type="date" class="form-control" v-model="searchForm.date_to" @change="applyFilters" placeholder="إلى تاريخ">
              </div>
              <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" @click="resetFilters">
                  <i class="bi bi-arrow-clockwise"></i> إعادة تعيين
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Orders Table (Excel Style) -->
        <div class="card shadow-sm">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover table-excel mb-0">
                <thead class="table-header-excel">
                  <tr>
                    <th style="width: 60px;">#</th>
                    <th style="min-width: 200px;">📦 اسم الديكور</th>
                    <th style="min-width: 180px;">👤 اسم الزبون</th>
                    <th style="min-width: 150px;">🔧 المنجز</th>
                    <th style="min-width: 120px;">💵 السعر الكلي</th>
                    <th style="min-width: 120px;">💰 المدفوع</th>
                    <th style="min-width: 120px;">📊 المتبقي</th>
                    <th style="min-width: 130px;">📅 تاريخ المناسبة</th>
                    <th style="min-width: 120px;">✨ الحالة</th>
                    <th style="min-width: 150px; text-align: center;">⚡ إجراءات</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(order, index) in orders.data" :key="order.id" class="table-row-excel">
                    <td class="text-center fw-bold text-muted">{{ index + 1 + (orders.current_page - 1) * orders.per_page }}</td>
                    <td>
                      {{ order.decoration_name || 'غير محدد' }}
                    </td>
                    <td>
                      <div>
                        <strong>{{ order.customer_name }}</strong>
                        <br>
                        <small class="text-muted">
                          <i class="bi bi-telephone"></i> {{ order.customer_phone }}
                        </small>
                      </div>
                    </td>
                    <td>
                      <span v-if="order.assigned_employee" class="employee-badge">
                        <i class="bi bi-person-check"></i> {{ order.assigned_employee.name }}
                      </span>
                      <span v-else class="text-muted">
                        <i class="bi bi-person-x"></i> غير معين
                      </span>
                    </td>
                    <td>
                      <div class="price-cell">
                        <strong class="text-primary">{{ formatNumber(order.total_price) }}</strong>
                        <small class="d-block text-muted">{{ getCurrencySymbol(order.currency) }}</small>
                      </div>
                    </td>
                    <td>
                      <div class="price-cell">
                        <strong class="text-success">{{ formatNumber(order.paid_amount || 0) }}</strong>
                        <small class="d-block text-muted">{{ getCurrencySymbol(order.currency) }}</small>
                      </div>
                    </td>
                    <td>
                      <div class="price-cell">
                        <strong :class="getRemainingClass(order)">{{ formatNumber(order.total_price - (order.paid_amount || 0)) }}</strong>
                        <small class="d-block text-muted">{{ getCurrencySymbol(order.currency) }}</small>
                      </div>
                    </td>
                    <td>
                      <div class="date-cell">
                        <i class="bi bi-calendar-event"></i>
                        {{ formatDate(order.event_date) }}
                        <br>
                        <small class="text-muted">
                          <i class="bi bi-clock"></i> {{ order.event_time || 'غير محدد' }}
                        </small>
                      </div>
                    </td>
                    <td>
                      <span class="status-badge" :class="`status-${order.status}`">
                        {{ getStatusText(order.status) }}
                      </span>
                    </td>
                    <td class="text-center">
                      <div class="btn-group-sm" role="group">
                        <button v-if="hasPermission('read decoration')" @click="showDetails(order)" class="btn btn-sm btn-outline-primary" title="عرض التفاصيل">
                          <i class="bi bi-eye"></i>
                        </button>
                        <a v-if="hasPermission('read decoration')" :href="route('decoration.orders.print', order.id)" target="_blank" class="btn btn-sm btn-outline-info" title="طباعة">
                          <i class="bi bi-printer"></i>
                        </a>
                        <button v-if="hasPermission('update decoration')" @click="quickEdit(order)" class="btn btn-sm btn-outline-warning" title="تعديل سريع">
                          <i class="bi bi-pencil"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
                <tfoot v-if="orders.data.length > 0" class="table-footer-excel">
                  <tr>
                    <td colspan="4" class="text-end fw-bold">المجموع:</td>
                    <td>
                      <strong class="text-primary">{{ formatNumber(getTotalPrice()) }}</strong>
                    </td>
                    <td>
                      <strong class="text-success">{{ formatNumber(getTotalPaid()) }}</strong>
                    </td>
                    <td>
                      <strong class="text-danger">{{ formatNumber(getTotalRemaining()) }}</strong>
                    </td>
                    <td colspan="3"></td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Empty State -->
            <div v-if="orders.data.length === 0" class="text-center py-5">
              <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4>لا توجد طلبات</h4>
                <p class="text-muted">لم يتم العثور على أي طلبات ديكور.</p>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="orders.data.length > 0" class="card-footer bg-light">
            <div class="d-flex justify-content-between align-items-center">
              <div class="text-muted">
                عرض {{ orders.from }} - {{ orders.to }} من أصل {{ orders.total }} طلب
              </div>
              <Pagination :links="orders.links" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Edit Modal -->
    <div v-if="showEditModal" class="modal-overlay" @click.self="showEditModal = false">
      <div class="modal-content-custom" style="max-width: 600px;">
        <div class="modal-header-custom" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
          <h5>⚡ تعديل سريع - {{ selectedOrder?.customer_name }}</h5>
          <button @click="showEditModal = false" class="btn-close-custom">×</button>
        </div>
        <div class="modal-body-custom">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">✨ الحالة</label>
              <select class="form-select" v-model="editForm.status">
                <option value="created">📝 تم الإنشاء</option>
                <option value="received">📥 تم الاستلام</option>
                <option value="executing">⚙️ قيد التنفيذ</option>
                <option value="partial_payment">💰 دفعة جزئية</option>
                <option value="full_payment">💵 دفعة كاملة</option>
                <option value="completed">✅ مكتمل</option>
                <option value="cancelled">❌ ملغي</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">🔧 المنجز</label>
              <select class="form-select" v-model="editForm.assigned_employee_id">
                <option value="">اختر موظف</option>
                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                  {{ employee.name }}
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">💵 السعر الكلي ($)</label>
              <input type="number" class="form-control" v-model="editForm.total_price" min="0" step="0.01">
            </div>
            <div class="col-md-6">
              <label class="form-label">💰 المدفوع ($)</label>
              <input type="number" class="form-control" v-model="editForm.paid_amount" min="0" step="0.01">
            </div>
          </div>
        </div>
        <div class="modal-footer-custom">
          <button @click="showEditModal = false" class="btn btn-secondary">إلغاء</button>
          <button @click="saveQuickEdit" class="btn btn-primary">
            <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
            <i class="bi bi-check-circle me-1"></i> حفظ
          </button>
        </div>
      </div>
    </div>

    <!-- Create Order Modal -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
      <div class="modal-content-custom" style="max-width: 600px;">
        <div class="modal-header-custom" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
          <h5>➕ إضافة طلب جديد</h5>
          <button @click="showCreateModal = false" class="btn-close-custom">×</button>
        </div>
        <div class="modal-body-custom">
          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label">📦 اسم الديكور <span class="text-danger">*</span></label>
              <input type="text" class="form-control" v-model="createForm.decoration_name" placeholder="أدخل اسم الديكور">
            </div>
            <div class="col-md-6">
              <label class="form-label">👤 اسم الزبون <span class="text-danger">*</span></label>
              <input type="text" class="form-control" v-model="createForm.customer_name" placeholder="اسم الزبون">
            </div>
            <div class="col-md-6">
              <label class="form-label">📞 رقم الهاتف <span class="text-danger">*</span></label>
              <input type="text" class="form-control" v-model="createForm.customer_phone" placeholder="07XX XXX XXXX">
            </div>
            <div class="col-md-6">
              <label class="form-label">📅 تاريخ المناسبة <span class="text-danger">*</span></label>
              <input type="date" class="form-control" v-model="createForm.event_date">
            </div>
            <div class="col-md-6">
              <label class="form-label">🔧 المنجز</label>
              <select class="form-select" v-model="createForm.assigned_employee_id">
                <option value="">اختر موظف</option>
                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                  {{ employee.name }}
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">💵 السعر الكلي ($) <span class="text-danger">*</span></label>
              <input type="number" class="form-control" v-model="createForm.total_price" min="0" step="0.01">
            </div>
            <div class="col-md-6">
              <label class="form-label">💰 المدفوع ($)</label>
              <input type="number" class="form-control" v-model="createForm.paid_amount" min="0" step="0.01">
            </div>
            <div class="col-md-12">
              <label class="form-label">📝 ملاحظات</label>
              <textarea class="form-control" v-model="createForm.special_requests" rows="2" placeholder="ملاحظات أو طلبات خاصة..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer-custom">
          <button @click="showCreateModal = false" class="btn btn-secondary">إلغاء</button>
          <button @click="saveNewOrder" class="btn btn-success">
            <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
            <i class="bi bi-check-circle me-1"></i> حفظ
          </button>
        </div>
      </div>
    </div>

    <!-- Show Details Modal -->
    <div v-if="showDetailsModal" class="modal-overlay" @click.self="showDetailsModal = false">
      <div class="modal-content-custom" style="max-width: 700px;">
        <div class="modal-header-custom" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
          <h5>📋 تفاصيل الطلب</h5>
          <button @click="showDetailsModal = false" class="btn-close-custom">×</button>
        </div>
        <div class="modal-body-custom">
          <div class="details-grid" v-if="selectedOrder">
            <div class="detail-card">
              <div class="detail-icon">📦</div>
              <div class="detail-content">
                <div class="detail-label">اسم الديكور</div>
                <div class="detail-value">{{ selectedOrder.decoration_name || 'غير محدد' }}</div>
              </div>
            </div>
            
            <div class="detail-card">
              <div class="detail-icon">👤</div>
              <div class="detail-content">
                <div class="detail-label">اسم الزبون</div>
                <div class="detail-value">{{ selectedOrder.customer_name }}</div>
              </div>
            </div>
            
            <div class="detail-card">
              <div class="detail-icon">📞</div>
              <div class="detail-content">
                <div class="detail-label">رقم الهاتف</div>
                <div class="detail-value">{{ selectedOrder.customer_phone }}</div>
              </div>
            </div>
            
            <div class="detail-card">
              <div class="detail-icon">🔧</div>
              <div class="detail-content">
                <div class="detail-label">المنجز</div>
                <div class="detail-value">{{ selectedOrder.assigned_employee?.name || 'غير محدد' }}</div>
              </div>
            </div>
            
            <div class="detail-card">
              <div class="detail-icon">📅</div>
              <div class="detail-content">
                <div class="detail-label">تاريخ المناسبة</div>
                <div class="detail-value">{{ formatDate(selectedOrder.event_date) }}</div>
              </div>
            </div>
            
            <div class="detail-card">
              <div class="detail-icon">✨</div>
              <div class="detail-content">
                <div class="detail-label">الحالة</div>
                <div class="detail-value">
                  <span class="status-badge" :class="`status-${selectedOrder.status}`">
                    {{ getStatusText(selectedOrder.status) }}
                  </span>
                </div>
              </div>
            </div>
            
            <div class="detail-card">
              <div class="detail-icon">💵</div>
              <div class="detail-content">
                <div class="detail-label">السعر الكلي</div>
                <div class="detail-value text-primary fw-bold">{{ formatCurrency(selectedOrder.total_price) }}</div>
              </div>
            </div>
            
            <div class="detail-card">
              <div class="detail-icon">💰</div>
              <div class="detail-content">
                <div class="detail-label">المدفوع</div>
                <div class="detail-value text-success fw-bold">{{ formatCurrency(selectedOrder.paid_amount) }}</div>
              </div>
            </div>
            
            <div class="detail-card">
              <div class="detail-icon">📊</div>
              <div class="detail-content">
                <div class="detail-label">المتبقي</div>
                <div class="detail-value text-danger fw-bold">{{ formatCurrency(selectedOrder.total_price - selectedOrder.paid_amount) }}</div>
              </div>
            </div>
            
            <div class="detail-card full-width" v-if="selectedOrder.special_requests">
              <div class="detail-icon">📝</div>
              <div class="detail-content">
                <div class="detail-label">ملاحظات خاصة</div>
                <div class="detail-value" style="white-space: pre-wrap;">{{ selectedOrder.special_requests }}</div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer-custom">
          <button @click="showDetailsModal = false" class="btn btn-secondary">إغلاق</button>
          <button v-if="hasPermission('update decoration')" @click="editFromDetails" class="btn btn-warning">
            <i class="bi bi-pencil me-1"></i> تعديل
          </button>
          <a v-if="hasPermission('read decoration')" :href="route('decoration.orders.print', selectedOrder?.id)" target="_blank" class="btn btn-info">
            <i class="bi bi-printer me-1"></i> طباعة
          </a>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { Link } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'

const page = usePage()
const toast = useToast()

const props = defineProps({
  orders: Object,
  filters: Object,
  translations: Object,
  employees: Array
})

// Check permissions
const hasPermission = (permission) => {
  return page.props.auth_permissions && page.props.auth_permissions.includes(permission)
}

// State
const showEditModal = ref(false)
const showCreateModal = ref(false)
const showDetailsModal = ref(false)
const selectedOrder = ref(null)
const processing = ref(false)

// Get first and last day of current month
const getFirstDayOfMonth = () => {
  const now = new Date()
  return new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0]
}

const getLastDayOfMonth = () => {
  const now = new Date()
  return new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0]
}

// Search form
const searchForm = reactive({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  employee: props.filters?.employee || '',
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || ''
})

// Edit form
const editForm = reactive({
  status: '',
  assigned_employee_id: '',
  total_price: 0,
  paid_amount: 0
})

// Create form
const createForm = reactive({
  decoration_name: '',
  customer_name: '',
  customer_phone: '',
  event_date: '',
  total_price: 0,
  paid_amount: 0,
  assigned_employee_id: '',
  special_requests: ''
})

// Computed statistics
const pendingCount = computed(() => {
  return props.orders.data.filter(o => ['created', 'received', 'executing'].includes(o.status)).length
})

const completedCount = computed(() => {
  return props.orders.data.filter(o => o.status === 'completed').length
})

const totalRevenue = computed(() => {
  return props.orders.data
    .filter(o => ['full_payment', 'completed'].includes(o.status))
    .reduce((sum, o) => sum + (o.total_price || 0), 0)
})

// Functions
const debouncedSearch = (() => {
  let timeout
  return () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => applyFilters(), 500)
  }
})()

const applyFilters = () => {
  router.get(route('decorations.orders.simple'), searchForm, {
    preserveState: true,
    replace: true
  })
}

const resetFilters = () => {
  searchForm.search = ''
  searchForm.status = ''
  searchForm.employee = ''
  searchForm.date_from = ''
  searchForm.date_to = ''
  applyFilters()
}

const quickEdit = (order) => {
  selectedOrder.value = order
  editForm.status = order.status
  editForm.assigned_employee_id = order.assigned_employee_id || ''
  editForm.total_price = order.total_price || 0
  editForm.paid_amount = order.paid_amount || 0
  showEditModal.value = true
}

const showDetails = (order) => {
  selectedOrder.value = order
  showDetailsModal.value = true
}

const editFromDetails = () => {
  showDetailsModal.value = false
  showEditModal.value = true
}

const saveQuickEdit = () => {
  processing.value = true
  router.patch(route('decoration.orders.status', selectedOrder.value.id), editForm, {
    onSuccess: () => {
      processing.value = false
      showEditModal.value = false
      toast.success('✅ تم التعديل بنجاح')
    },
    onError: () => {
      processing.value = false
      toast.error('❌ حدث خطأ أثناء التعديل')
    }
  })
}

const openCreateModal = () => {
  // Reset form
  createForm.decoration_name = ''
  createForm.customer_name = ''
  createForm.customer_phone = ''
  createForm.event_date = ''
  createForm.total_price = 0
  createForm.paid_amount = 0
  createForm.assigned_employee_id = ''
  createForm.special_requests = ''
  
  showCreateModal.value = true
}

const saveNewOrder = () => {
  if (!createForm.decoration_name || !createForm.customer_name || !createForm.customer_phone || !createForm.event_date || !createForm.total_price) {
    toast.warning('⚠️ الرجاء ملء جميع الحقول المطلوبة (*)')
    return
  }
  
  processing.value = true
  
  const formData = {
    decoration_id: null,
    decoration_name: createForm.decoration_name,
    customer_name: createForm.customer_name,
    customer_phone: createForm.customer_phone,
    event_date: createForm.event_date,
    event_time: '12:00',
    event_address: createForm.special_requests || '-',
    guest_count: 1,
    special_requests: createForm.special_requests,
    total_price: createForm.total_price,
    paid_amount: createForm.paid_amount || 0,
    assigned_employee_id: createForm.assigned_employee_id || null,
    currency: 'dollar'
  }
  
  router.post(route('decoration.orders.store'), formData, {
    onSuccess: () => {
      processing.value = false
      showCreateModal.value = false
      toast.success('✅ تم إضافة الطلب بنجاح')
      router.reload({ only: ['orders'] })
    },
    onError: (errors) => {
      processing.value = false
      console.error('Errors:', errors)
      toast.error('❌ حدث خطأ أثناء حفظ الطلب')
    }
  })
}

const formatNumber = (num) => {
  return new Intl.NumberFormat('en-US').format(num || 0)
}

const formatCurrency = (num) => {
  return '$' + new Intl.NumberFormat('en-US').format(num || 0)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

const getCurrencySymbol = (currency) => {
  return currency === 'dollar' ? '$' : 'IQD'
}

const getRemainingClass = (order) => {
  const remaining = order.total_price - (order.paid_amount || 0)
  return remaining > 0 ? 'text-danger' : 'text-success'
}

const getStatusText = (status) => {
  const statuses = {
    created: '📝 إنشاء',
    received: '📥 استلام',
    executing: '⚙️ تنفيذ',
    partial_payment: '💰 دفع جزئي',
    full_payment: '💵 دفع كامل',
    completed: '✅ مكتمل',
    cancelled: '❌ ملغي'
  }
  return statuses[status] || status
}

const getTotalPrice = () => {
  return props.orders.data.reduce((sum, o) => sum + (o.total_price || 0), 0)
}

const getTotalPaid = () => {
  return props.orders.data.reduce((sum, o) => sum + (o.paid_amount || 0), 0)
}

const getTotalRemaining = () => {
  return getTotalPrice() - getTotalPaid()
}
</script>

<style scoped>
/* Statistics Cards */
.stat-card {
  border-radius: 15px;
  padding: 20px;
  color: white;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
}

.stat-icon {
  font-size: 2.5rem;
  opacity: 0.9;
}

.stat-content h3 {
  margin: 0;
  font-size: 1.8rem;
  font-weight: bold;
}

.stat-content p {
  margin: 0;
  font-size: 0.9rem;
  opacity: 0.9;
}

/* Search Box */
.search-box {
  position: relative;
}

.search-icon {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
  z-index: 10;
}

/* Excel Style Table */
.table-excel {
  border-collapse: separate;
  border-spacing: 0;
}

.table-header-excel {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.table-header-excel th {
  padding: 15px 12px;
  font-weight: 600;
  border: none;
  font-size: 0.9rem;
  white-space: nowrap;
}

.table-row-excel {
  transition: all 0.2s ease;
  border-bottom: 1px solid #e9ecef;
}

.table-row-excel:hover {
  background-color: #f8f9fa;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.table-row-excel td {
  padding: 12px;
  vertical-align: middle;
  font-size: 0.9rem;
}

.table-footer-excel {
  background-color: #f8f9fa;
  font-weight: 600;
}

.table-footer-excel td {
  padding: 15px 12px;
  border-top: 2px solid #dee2e6;
}

/* Decoration Icon */
.decoration-icon {
  font-size: 1.5rem;
  margin-left: 10px;
}

/* Employee Badge */
.employee-badge {
  display: inline-block;
  padding: 5px 10px;
  background-color: #e3f2fd;
  color: #1976d2;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 500;
}

/* Price Cell */
.price-cell {
  text-align: center;
}

.price-cell strong {
  font-size: 1rem;
}

/* Date Cell */
.date-cell {
  font-size: 0.85rem;
}

/* Status Badge */
.status-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
  white-space: nowrap;
}

.status-created { background-color: #e3f2fd; color: #1976d2; }
.status-received { background-color: #f3e5f5; color: #7b1fa2; }
.status-executing { background-color: #fff3e0; color: #f57c00; }
.status-partial_payment { background-color: #fffde7; color: #f9a825; }
.status-full_payment { background-color: #e8f5e9; color: #388e3c; }
.status-completed { background-color: #c8e6c9; color: #2e7d32; }
.status-cancelled { background-color: #ffebee; color: #c62828; }

/* Empty State */
.empty-state {
  padding: 40px;
}

.empty-state i {
  font-size: 4rem;
  color: #dee2e6;
}

.empty-state h4 {
  margin-top: 20px;
  color: #6c757d;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-content-custom {
  background: white;
  border-radius: 15px;
  width: 90%;
  max-width: 600px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header-custom {
  padding: 20px 25px;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header-custom h5 {
  margin: 0;
  font-weight: 600;
}

.btn-close-custom {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #6c757d;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s;
}

.btn-close-custom:hover {
  color: #dc3545;
}

.modal-body-custom {
  padding: 25px;
}

.modal-footer-custom {
  padding: 15px 25px;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* Responsive */
@media (max-width: 768px) {
  .table-excel {
    font-size: 0.8rem;
  }
  
  .stat-card {
    margin-bottom: 15px;
  }
}

/* Details Grid */
.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  padding: 10px 0;
}

.detail-card {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 12px;
  padding: 15px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  transition: all 0.3s ease;
  border: 1px solid #dee2e6;
}

.detail-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  border-color: #6366f1;
}

.detail-card.full-width {
  grid-column: 1 / -1;
}

.detail-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.detail-content {
  flex: 1;
  min-width: 0;
}

.detail-label {
  font-size: 12px;
  color: #6c757d;
  font-weight: 600;
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  font-size: 15px;
  color: #212529;
  font-weight: 500;
  word-wrap: break-word;
}
</style>
