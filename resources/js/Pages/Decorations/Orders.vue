<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="d-flex justify-content-between align-items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ translations.decoration_orders }}
        </h2>
        <Link class="btn btn-secondary" :href="route('decorations.index')">
          <i class="bi bi-arrow-left"></i> {{ translations.cancel }}
        </Link>
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
            <form @submit.prevent="updateOrderStatus">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.status }} *</label>
                    <select class="form-select" v-model="statusForm.status" required>
                      <option value="created">{{ translations.created }}</option>
                      <option value="received">{{ translations.received }}</option>
                      <option value="executing">{{ translations.executing }}</option>
                      <option value="partial_payment">{{ translations.partial_payment }}</option>
                      <option value="full_payment">{{ translations.full_payment }}</option>
                      <option value="completed">{{ translations.completed }}</option>
                      <option value="cancelled">{{ translations.cancelled }}</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.assign_employee }}</label>
                    <select class="form-select" v-model="statusForm.assigned_employee_id">
                      <option value="">{{ translations.select_employee }}</option>
                      <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                        {{ employee.name }}
                      </option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6" v-if="statusForm.status === 'partial_payment' || statusForm.status === 'full_payment'">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.paid_amount }}</label>
                    <input type="number" step="0.01" class="form-control" v-model="statusForm.paid_amount">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.scheduled_date }}</label>
                    <input type="datetime-local" class="form-control" v-model="statusForm.scheduled_date">
                  </div>
                </div>
                <div class="col-12">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.notes }}</label>
                    <input  class="form-control" type="text" v-model="statusForm.notes"> 
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
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import PricingEditModal from '@/Components/Decorations/PricingEditModal.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  orders: Object,
  filters: Object,
  translations: Object,
  employees: Array
})

// Modal states
const showStatusModal = ref(false)
const showPricingModal = ref(false)
const processing = ref(false)
const selectedOrder = ref(null)

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
  statusForm.status = order.status
  statusForm.assigned_employee_id = order.assigned_employee_id || ''
  statusForm.paid_amount = order.paid_amount || ''
  statusForm.scheduled_date = order.scheduled_date ? formatDateTimeLocal(order.scheduled_date) : ''
  statusForm.notes = order.notes || ''
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
  
  router.patch(route('decoration.orders.status', selectedOrder.value.id), statusForm, {
    onSuccess: () => {
      processing.value = false
      showStatusModal.value = false
    },
    onError: () => {
      processing.value = false
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
</style>
