<template>
  <div>
    <!-- Search and Filters -->
    <div class="card mb-4">
      <div class="card-body">
        <form @submit.prevent="searchOrders">
          <div class="row">
            <div class="col-md-3">
              <input 
                type="text" 
                class="form-control" 
                v-model="searchForm.search" 
                :placeholder="translations.search + ' (' + translations.customer_name + ', ' + translations.phone + ')'"
              >
            </div>
            <div class="col-md-2">
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
            <div class="col-md-2">
              <select class="form-select" v-model="searchForm.assigned_employee_id">
                <option value="">{{ translations.all_employees }}</option>
                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                  {{ employee.name }}
                </option>
              </select>
            </div>
            <div class="col-md-2">
              <input 
                type="date" 
                class="form-control" 
                v-model="searchForm.event_date"
                :placeholder="translations.event_date"
              >
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search"></i> {{ translations.search }}
              </button>
            </div>
            <div class="col-md-1">
              <button type="button" class="btn btn-outline-secondary w-100" @click="resetFilters">
                <i class="bi bi-arrow-clockwise"></i>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Orders Cards -->
    <div class="row" v-if="orders.data.length > 0">
      <div class="col-xl-4 col-lg-6 col-md-12 mb-4" v-for="order in orders.data" :key="order.id">
        <div class="card h-100 order-card">
          <!-- Card Header -->
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h6 class="mb-0">{{ translations.order }} #{{ order.id }}</h6>
              <small class="text-muted">{{ formatDate(order.created_at) }}</small>
            </div>
            <span class="badge" :class="`bg-${getStatusColor(order.status)}`">
              {{ getStatusName(order.status) }}
            </span>
          </div>

          <!-- Card Body -->
          <div class="card-body">
            <!-- Decoration Info -->
            <div class="mb-3">
              <h6 class="text-primary">{{ order.decoration?.name }}</h6>
              <small class="text-muted">{{ order.decoration?.type_name }}</small>
            </div>

            <!-- Customer Info -->
            <div class="mb-3">
              <div class="d-flex align-items-center mb-1">
                <i class="bi bi-person me-2"></i>
                <strong>{{ order.customer_name }}</strong>
              </div>
              <div class="d-flex align-items-center mb-1">
                <i class="bi bi-telephone me-2"></i>
                <span>{{ order.customer_phone }}</span>
              </div>
              <div class="d-flex align-items-center">
                <i class="bi bi-calendar-event me-2"></i>
                <span>{{ formatDate(order.event_date) }} {{ order.event_time }}</span>
              </div>
            </div>

            <!-- Event Details -->
            <div class="mb-3">
              <div class="d-flex align-items-center mb-1">
                <i class="bi bi-geo-alt me-2"></i>
                <small class="text-muted">{{ truncateText(order.event_address, 30) }}</small>
              </div>
              <div class="d-flex align-items-center">
                <i class="bi bi-people me-2"></i>
                <small class="text-muted">{{ order.guest_count }} {{ translations.guests }}</small>
              </div>
            </div>

            <!-- Pricing -->
            <div class="mb-3">
              <div class="pricing-breakdown">
                <div class="d-flex justify-content-between mb-1">
                  <small>{{ translations.base_price }}:</small>
                  <small>{{ order.base_price }} {{ getCurrency(order) }}</small>
                </div>
                <div v-if="order.additional_cost > 0" class="d-flex justify-content-between mb-1">
                  <small class="text-success">{{ translations.additional_cost }}:</small>
                  <small class="text-success">+{{ order.additional_cost }} {{ getCurrency(order) }}</small>
                </div>
                <div v-if="order.discount > 0" class="d-flex justify-content-between mb-1">
                  <small class="text-danger">{{ translations.discount }}:</small>
                  <small class="text-danger">-{{ order.discount }} {{ getCurrency(order) }}</small>
                </div>
                <hr class="my-1">
                <div class="d-flex justify-content-between mb-1">
                  <strong>{{ translations.total_price }}:</strong>
                  <strong class="text-primary">{{ order.total_price }} {{ getCurrency(order) }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>{{ translations.paid_amount }}:</span>
                  <span class="text-info">{{ order.paid_amount || 0 }} {{ getCurrency(order) }}</span>
                </div>
                <div class="d-flex justify-content-between">
                  <span>{{ translations.remaining_amount }}:</span>
                  <span class="text-warning">{{ (order.total_price - (order.paid_amount || 0)).toFixed(2) }} {{ getCurrency(order) }}</span>
                </div>
              </div>
            </div>

            <!-- Assigned Employee -->
            <div v-if="order.assigned_employee" class="mb-3">
              <div class="d-flex align-items-center">
                <i class="bi bi-person-badge me-2"></i>
                <small class="text-muted">{{ translations.assigned_employee }}: {{ order.assigned_employee.name }}</small>
              </div>
            </div>
          </div>

          <!-- Card Footer -->
          <div class="card-footer">
            <div class="btn-group w-100" role="group">
              <button @click="viewOrder(order)" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-eye"></i> {{ translations.view }}
              </button>
              <button @click="openStatusModal(order)" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil"></i> {{ translations.update_status }}
              </button>
              <button @click="openPricingModal(order)" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-calculator"></i> {{ translations.edit_pricing }}
              </button>
              <a :href="route('decoration.orders.print', order.id)" target="_blank" class="btn btn-outline-info btn-sm">
                <i class="bi bi-printer"></i> {{ translations.print_invoice }}
              </a>
              <button @click="assignEmployee(order)" class="btn btn-outline-success btn-sm">
                <i class="bi bi-person-plus"></i> {{ translations.assign }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-5">
      <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
      <h4 class="text-muted mt-3">{{ translations.no_orders }}</h4>
      <p class="text-muted">{{ translations.no_orders_description }}</p>
    </div>

    <!-- Pagination -->
    <div v-if="orders.data.length > 0" class="d-flex justify-content-center mt-4">
      <Pagination :links="orders.links" />
    </div>

    <!-- Status Update Modal -->
    <StatusUpdateModal 
      v-if="showStatusModal"
      :order="selectedOrder"
      :employees="employees"
      :translations="translations"
      @close="showStatusModal = false"
      @success="handleStatusSuccess"
    />

    <!-- Employee Assignment Modal -->
    <EmployeeAssignmentModal 
      v-if="showAssignModal"
      :order="selectedOrder"
      :employees="employees"
      :translations="translations"
      @close="showAssignModal = false"
      @success="handleAssignSuccess"
    />

    <!-- Pricing Edit Modal -->
    <PricingEditModal 
      v-if="showPricingModal"
      :order="selectedOrder"
      :translations="translations"
      @close="showPricingModal = false"
      @success="handlePricingSuccess"
    />
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import Pagination from '@/Components/Pagination.vue'
import StatusUpdateModal from '@/Components/Decorations/StatusUpdateModal.vue'
import EmployeeAssignmentModal from '@/Components/Decorations/EmployeeAssignmentModal.vue'
import PricingEditModal from '@/Components/Decorations/PricingEditModal.vue'

const props = defineProps({
  orders: Object,
  employees: Array,
  translations: Object
})

const emit = defineEmits(['refresh'])

// State
const showStatusModal = ref(false)
const showAssignModal = ref(false)
const showPricingModal = ref(false)
const selectedOrder = ref(null)

// Search form
const searchForm = reactive({
  search: '',
  status: '',
  assigned_employee_id: '',
  event_date: ''
})

// Methods
const searchOrders = () => {
  router.get(route('decorations.dashboard'), { 
    tab: 'orders',
    ...searchForm 
  }, {
    preserveState: true,
    replace: true
  })
}

const resetFilters = () => {
  searchForm.search = ''
  searchForm.status = ''
  searchForm.assigned_employee_id = ''
  searchForm.event_date = ''
  searchOrders()
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US')
}

const truncateText = (text, length) => {
  return text && text.length > length ? text.substring(0, length) + '...' : text
}

const getStatusColor = (status) => {
  const colors = {
    'created': 'secondary',
    'received': 'info',
    'executing': 'primary',
    'partial_payment': 'warning',
    'full_payment': 'success',
    'completed': 'success',
    'cancelled': 'danger'
  }
  return colors[status] || 'secondary'
}

const getStatusName = (status) => {
  const statuses = {
    'created': props.translations.created,
    'received': props.translations.received,
    'executing': props.translations.executing,
    'partial_payment': props.translations.partial_payment,
    'full_payment': props.translations.full_payment,
    'completed': props.translations.completed,
    'cancelled': props.translations.cancelled
  }
  return statuses[status] || status
}

const getCurrency = (order) => {
  return order.currency === 'dollar' ? props.translations.dollar : props.translations.dinar
}

const viewOrder = (order) => {
  router.get(route('decoration.orders.show', order.id))
}

const openStatusModal = (order) => {
  selectedOrder.value = order
  showStatusModal.value = true
}

const assignEmployee = (order) => {
  selectedOrder.value = order
  showAssignModal.value = true
}

const openPricingModal = (order) => {
  selectedOrder.value = order
  showPricingModal.value = true
}

const handleStatusSuccess = () => {
  showStatusModal.value = false
  emit('refresh')
}

const handleAssignSuccess = () => {
  showAssignModal.value = false
  emit('refresh')
}

const handlePricingSuccess = () => {
  showPricingModal.value = false
  emit('refresh')
}
</script>

<style scoped>
.order-card {
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  border: 1px solid #e3e6f0;
}

.order-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

.pricing-breakdown {
  font-size: 0.875rem;
}

.pricing-breakdown hr {
  margin: 0.25rem 0;
  border-color: #dee2e6;
}

.btn-group .btn {
  margin-right: 2px;
}

.btn-group .btn:last-child {
  margin-right: 0;
}

.card-header {
  background-color: #f8f9fc;
  border-bottom: 1px solid #e3e6f0;
}

.btn-group .btn {
  flex: 1;
}

.text-primary {
  color: #4e73df !important;
}

.text-success {
  color: #1cc88a !important;
}

.text-info {
  color: #36b9cc !important;
}

.text-warning {
  color: #f6c23e !important;
}
</style>
