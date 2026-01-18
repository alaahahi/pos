<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="d-flex justify-content-between align-items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ translations.decoration_orders }}
        </h2>
        <div class="d-flex gap-2">
          <Link v-if="hasPermission('read decoration')" class="btn btn-success" :href="route('decorations.orders.simple')">
            <i class="bi bi-table"></i> عرض بسيط
          </Link>
        <Link class="btn btn-secondary" :href="route('decorations.index')">
          <i class="bi bi-arrow-left"></i> {{ translations.cancel }}
        </Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Search and Filters -->
        <div class="card mb-4">
          <div class="card-body">
            <form @submit.prevent="searchOrders">
              <div class="row">
                <div class="col-md-4">
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="searchForm.search" 
                    :placeholder="translations.search"
                  >
                </div>
                <div class="col-md-4">
                  <select class="form-select" v-model="searchForm.status">
                    <option value="">{{ translations.all_status }}</option>
                    <option value="created">{{ translations.created }}</option>
                    <option value="received">{{ translations.received }}</option>
                    <option value="executing">{{ translations.executing }}</option>
                    <option value="partial_payment">{{ translations.partial_payment }}</option>
                    <option value="full_payment">{{ translations.full_payment }}</option>
                    <option value="completed">{{ translations.completed }}</option>
                    <option value="cancelled">{{ translations.cancelled }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> {{ translations.search }}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>{{ translations.decoration }}</th>
                    <th>{{ translations.customer_name }}</th>
                    <th>{{ translations.event_date }}</th>
                    <th>{{ translations.pricing }}</th>
                    <th>{{ translations.status }}</th>
                    <th>{{ translations.assigned_employee }}</th>
                    <th>{{ translations.action }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="order in orders.data" :key="order.id">
                    <td>
                      <div>
                        <strong>{{ order.decoration?.name }}</strong>
                        <br>
                        <small class="text-muted">{{ order.decoration?.type_name }}</small>
                      </div>
                    </td>
                    <td>
                      <div>
                        {{ order.customer_name }}
                        <br>
                        <small class="text-muted">{{ order.customer_phone }}</small>
                      </div>
                    </td>
                    <td>
                      {{ formatDate(order.event_date) }}
                      <br>
                      <small class="text-muted">{{ order.event_time }}</small>
                    </td>
                    <td>
                      <div class="pricing-details">
                        <div class="d-flex justify-content-between mb-1">
                          <small>{{ translations.base_price }}:</small>
                          <small>{{ order.base_price }} {{ getCurrencySymbol(order.currency) }}</small>
                        </div>
                        <div v-if="order.additional_cost > 0" class="d-flex justify-content-between mb-1">
                          <small class="text-success">{{ translations.additional_cost }}:</small>
                          <small class="text-success">+{{ order.additional_cost }} {{ getCurrencySymbol(order.currency) }}</small>
                        </div>
                        <div v-if="order.discount > 0" class="d-flex justify-content-between mb-1">
                          <small class="text-danger">{{ translations.discount }}:</small>
                          <small class="text-danger">-{{ order.discount }} {{ getCurrencySymbol(order.currency) }}</small>
                        </div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between">
                          <strong>{{ translations.total_price }}:</strong>
                          <strong class="text-primary">{{ order.total_price }} {{ getCurrencySymbol(order.currency) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                          <small>{{ translations.paid_amount }}:</small>
                          <small class="text-success">{{ order.paid_amount || 0 }} {{ getCurrencySymbol(order.currency) }}</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge" :class="`bg-${order.status_color}`">
                        {{ order.status_name }}
                      </span>
                    </td>
                    <td>
                      <span v-if="order.assigned_employee">
                        {{ order.assigned_employee.name }}
                      </span>
                      <span v-else class="text-muted">{{ translations.not_assigned }}</span>
                    </td>
                    <td>
                      <div class="btn-group" role="group">
                        <Link :href="route('decoration.orders.show', order.id)" class="btn btn-outline-primary btn-sm">
                          <i class="bi bi-eye"></i> {{ translations.view }}
                        </Link>
                        <button @click="openStatusModal(order)" class="btn btn-outline-secondary btn-sm">
                          <i class="bi bi-pencil"></i> {{ translations.update_status }}
                        </button>
                        <button @click="openPricingModal(order)" class="btn btn-outline-warning btn-sm">
                          <i class="bi bi-calculator"></i> {{ translations.edit_pricing }}
                        </button>
                        <a :href="route('decoration.orders.print', order.id)" target="_blank" class="btn btn-outline-info btn-sm">
                          <i class="bi bi-printer"></i> {{ translations.print_invoice }}
                        </a>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="orders.data.length > 0" class="d-flex justify-content-center mt-4">
              <Pagination :links="orders.links" />
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-5">
              <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
              <h4 class="text-muted mt-3">{{ translations.no_orders }}</h4>
              <p class="text-muted">{{ translations.no_orders_description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Status Update Modal -->
    <div v-if="showStatusModal" class="modal-mask">
      <div class="modal-wrapper">
        <div class="modal-container">
          <div class="modal-header">
            <h3>{{ translations.update_status }} - {{ selectedOrder?.decoration?.name }}</h3>
            <button @click="showStatusModal = false" class="btn-close"></button>
          </div>
          <div class="modal-body">
            <!-- Error Alert -->
            <div v-if="Object.keys(errors).length > 0" class="alert alert-danger mb-3" role="alert">
              <h6 class="alert-heading">
                <i class="bi bi-exclamation-triangle"></i>
                يرجى تصحيح الأخطاء التالية:
              </h6>
              <ul class="mb-0">
                <li v-for="(error, field) in errors" :key="field">
                  <strong>{{ getFieldLabel(field) }}:</strong> {{ Array.isArray(error) ? error[0] : error }}
                </li>
              </ul>
            </div>

            <form @submit.prevent="updateOrderStatus">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.status }}</label>
                    <select class="form-select" v-model="statusForm.status" :class="{ 'is-invalid': errors.status }" @change="clearError('status')">
                      <option value="">{{ translations.select_type }}</option>
                      <option value="created">{{ translations.created }}</option>
                      <option value="received">{{ translations.received }}</option>
                      <option value="executing">{{ translations.executing }}</option>
                      <option value="partial_payment">{{ translations.partial_payment }}</option>
                      <option value="full_payment">{{ translations.full_payment }}</option>
                      <option value="completed">{{ translations.completed }}</option>
                      <option value="cancelled">{{ translations.cancelled }}</option>
                    </select>
                    <div class="invalid-feedback" v-if="errors.status">
                      {{ Array.isArray(errors.status) ? errors.status[0] : errors.status }}
                    </div>
                    <small class="text-muted">اترك فارغاً للاحتفاظ بالحالة الحالية</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.assign_employee }}</label>
                    <select class="form-select" v-model="statusForm.assigned_employee_id" :class="{ 'is-invalid': errors.assigned_employee_id }" @change="clearError('assigned_employee_id')">
                      <option value="">{{ translations.select_employee }}</option>
                      <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                        {{ employee.name }}
                      </option>
                    </select>
                    <div class="invalid-feedback" v-if="errors.assigned_employee_id">
                      {{ Array.isArray(errors.assigned_employee_id) ? errors.assigned_employee_id[0] : errors.assigned_employee_id }}
                    </div>
                  </div>
                </div>
                <div class="col-md-6" v-if="statusForm.status === 'partial_payment' || statusForm.status === 'full_payment'">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.paid_amount }}</label>
                    <input type="number" step="0.01" class="form-control" v-model="statusForm.paid_amount" :class="{ 'is-invalid': errors.paid_amount }" @input="clearError('paid_amount')">
                    <div class="invalid-feedback" v-if="errors.paid_amount">
                      {{ Array.isArray(errors.paid_amount) ? errors.paid_amount[0] : errors.paid_amount }}
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.scheduled_date }}</label>
                    <input type="datetime-local" class="form-control" v-model="statusForm.scheduled_date" :class="{ 'is-invalid': errors.scheduled_date }" @input="clearError('scheduled_date')">
                    <div class="invalid-feedback" v-if="errors.scheduled_date">
                      {{ Array.isArray(errors.scheduled_date) ? errors.scheduled_date[0] : errors.scheduled_date }}
                    </div>
                  </div>
                </div>
                <div class="col-12">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.notes }}</label>
                    <textarea class="form-control" v-model="statusForm.notes" :class="{ 'is-invalid': errors.notes }" rows="3" @input="clearError('notes')"></textarea>
                    <div class="invalid-feedback" v-if="errors.notes">
                      {{ Array.isArray(errors.notes) ? errors.notes[0] : errors.notes }}
                    </div>
                  </div>
                </div>
              </div>
              <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-secondary text-center w-100" @click="showStatusModal = false">
                  {{ translations.cancel }}
                </button>
                <button type="submit" class="btn btn-primary w-100 text-center" :disabled="processing">
                  <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
                  {{ translations.update }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Pricing Edit Modal -->
    <PricingEditModal 
      v-if="showPricingModal"
      :order="selectedOrder"
      :translations="translations"
      @close="showPricingModal = false"
      @success="showPricingModal = false; router.reload()"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import PricingEditModal from '@/Components/Decorations/PricingEditModal.vue'
import { Link } from '@inertiajs/vue3'

const page = usePage()

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

// Modal states
const showStatusModal = ref(false)
const showPricingModal = ref(false)
const processing = ref(false)
const selectedOrder = ref(null)
const errors = ref({})

// Search form
const searchForm = reactive({
  search: props.filters?.search || '',
  status: props.filters?.status || ''
})

// Status form
const statusForm = reactive({
  status: '',
  assigned_employee_id: '',
  paid_amount: '',
  scheduled_date: '',
  notes: ''
})

// Functions
const searchOrders = () => {
  router.get(route('decorations.orders'), searchForm, {
    preserveState: true,
    replace: true
  })
}

const openStatusModal = (order) => {
  selectedOrder.value = order
  statusForm.status = order.status || ''
  statusForm.assigned_employee_id = order.assigned_employee_id || ''
  statusForm.paid_amount = order.paid_amount || ''
  statusForm.scheduled_date = order.scheduled_date ? formatDateTimeLocal(order.scheduled_date) : ''
  statusForm.notes = order.notes || ''
  errors.value = {}
  showStatusModal.value = true
}

const openPricingModal = (order) => {
  selectedOrder.value = order
  showPricingModal.value = true
}

// Currency symbol helper
const getCurrencySymbol = (currency) => {
  return currency === 'dollar' ? 'دولار' : 'دينار'
}

const updateOrderStatus = () => {
  processing.value = true
  errors.value = {}
  
  // Build data object with only filled fields
  const dataToSend = {}
  
  if (statusForm.status && statusForm.status !== '') {
    dataToSend.status = statusForm.status
  }
  
  if (statusForm.assigned_employee_id && statusForm.assigned_employee_id !== '') {
    dataToSend.assigned_employee_id = statusForm.assigned_employee_id
  }
  
  if (statusForm.paid_amount && statusForm.paid_amount !== '') {
    dataToSend.paid_amount = statusForm.paid_amount
  }
  
  if (statusForm.scheduled_date && statusForm.scheduled_date !== '') {
    dataToSend.scheduled_date = statusForm.scheduled_date
  }
  
  if (statusForm.notes && statusForm.notes !== '') {
    dataToSend.notes = statusForm.notes
  }
  
  router.patch(route('decoration.orders.status', selectedOrder.value.id), dataToSend, {
    onSuccess: () => {
      processing.value = false
      showStatusModal.value = false
      errors.value = {}
    },
    onError: (errs) => {
      processing.value = false
      errors.value = errs
      console.error('Update status errors:', errs)
    }
  })
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ar-EG')
}

const formatDateTimeLocal = (date) => {
  const d = new Date(date)
  return d.toISOString().slice(0, 16)
}

const clearError = (field) => {
  if (errors.value[field]) {
    delete errors.value[field]
  }
}

const getFieldLabel = (field) => {
  const labels = {
    'status': props.translations.status || 'الحالة',
    'assigned_employee_id': props.translations.assign_employee || 'تعيين موظف',
    'paid_amount': props.translations.paid_amount || 'المبلغ المدفوع',
    'scheduled_date': props.translations.scheduled_date || 'التاريخ المجدول',
    'notes': props.translations.notes || 'ملاحظات'
  }
  return labels[field] || field
}
</script>

<style scoped>
.pricing-details {
  min-width: 200px;
  font-size: 0.875rem;
}

.pricing-details hr {
  margin: 0.25rem 0;
  border-color: #dee2e6;
}

.btn-group .btn {
  margin-right: 2px;
}

.btn-group .btn:last-child {
  margin-right: 0;
}

.modal-mask {
  position: fixed;
  z-index: 9998;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: table;
  transition: opacity 0.3s ease;
}

.modal-wrapper {
  display: table-cell;
  vertical-align: middle;
}

.modal-container {
  width: 90%;
  max-width: 600px;
  margin: 0px auto;
  padding: 20px 30px;
  background-color: #fff;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
  transition: all 0.3s ease;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
  margin: 0;
  color: #495057;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #999;
}

.btn-close:hover {
  color: #666;
}

.is-invalid {
  border-color: #dc3545 !important;
}

.invalid-feedback {
  display: block;
  color: #dc3545;
  font-size: 0.875em;
  margin-top: 0.25rem;
}

.alert-danger {
  background-color: #f8d7da;
  border-color: #f5c2c7;
  color: #842029;
}

.alert-danger .alert-heading {
  margin-bottom: 0.5rem;
}

.alert-danger ul {
  padding-left: 1.5rem;
  margin-bottom: 0;
}
</style>
