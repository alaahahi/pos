<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="d-flex justify-content-between align-items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ translations.order_details }} #{{ order.id }}
        </h2>
        <div class="d-flex gap-2">
          <button @click="openStatusModal" class="btn btn-primary">
            <i class="bi bi-pencil"></i> {{ translations.update_status }}
          </button>
          <button @click="openPricingModal" class="btn btn-warning">
            <i class="bi bi-calculator"></i> {{ translations.edit_pricing }}
          </button>
          <a :href="route('decoration.orders.print', order.id)" target="_blank" class="btn btn-info">
            <i class="bi bi-printer"></i> {{ translations.print_invoice }}
          </a>
          <Link class="btn btn-secondary" :href="route('decorations.orders')">
            <i class="bi bi-arrow-left"></i> {{ translations.cancel }}
          </Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="row">
          <!-- Order Info -->
          <div class="col-md-8">
            <div class="card mb-4">
              <div class="card-header">
                <h5>{{ translations.order_details }}</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <h6>{{ translations.decoration }}</h6>
                    <p><strong>{{ order.decoration?.name }}</strong></p>
                    <p class="text-muted">{{ order.decoration?.type_name }}</p>
                    <p class="text-muted" v-if="order.decoration?.description">{{ order.decoration.description }}</p>
                    <div class="mt-2">
                      <span class="badge bg-info me-2">{{ translations.duration }}: {{ order.decoration?.duration_hours }} {{ translations.hours }}</span>
                      <span class="badge bg-warning">{{ translations.team_size }}: {{ order.decoration?.team_size }}</span>
                    </div>
                    
                    <h6 class="mt-3">{{ translations.customer_details }}</h6>
                    <p><strong>{{ order.customer_name }}</strong></p>
                    <p>{{ translations.phone }}: {{ order.customer_phone }}</p>
                    <p v-if="order.customer_email">{{ translations.email }}: {{ order.customer_email }}</p>
                    <p v-if="order.customer?.address">{{ translations.address }}: {{ order.customer.address }}</p>
                    <div v-if="order.customer" class="mt-2">
                      <span class="badge bg-success" v-if="order.customer.is_active">{{ translations.active_customer }}</span>
                      <span class="badge bg-secondary" v-else>{{ translations.inactive_customer }}</span>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <h6>{{ translations.event_details }}</h6>
                    <p><strong>{{ translations.event_date }}:</strong> {{ formatDate(order.event_date) }}</p>
                    <p><strong>{{ translations.event_time }}:</strong> {{ order.event_time }}</p>
                    <p><strong>{{ translations.guest_count }}:</strong> {{ order.guest_count }}</p>
                    <p><strong>{{ translations.event_address }}:</strong></p>
                    <p class="text-muted">{{ order.event_address }}</p>
                
                <div v-if="order.special_requests" class="mt-3">
                  <h6>{{ translations.special_requests }}</h6>
                  <p class="text-muted">{{ order.special_requests }}</p>
                    </div>
                    
                    <div v-if="order.selected_items && order.selected_items.length > 0" class="mt-3">
                      <h6>{{ translations.selected_items }}</h6>
                      <ul class="list-unstyled">
                        <li v-for="item in order.selected_items" :key="item" class="text-muted">
                          <i class="bi bi-check-circle text-success me-2"></i>{{ item }}
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Order Timeline -->
            <div class="card">
              <div class="card-header">
                <h5>{{ translations.order_timeline }}</h5>
              </div>
              <div class="card-body">
                <div class="timeline">
                  <div class="timeline-item" :class="{ active: order.created_at }">
                    <div class="timeline-marker bg-secondary"></div>
                    <div class="timeline-content">
                      <h6>{{ translations.created }}</h6>
                      <p class="text-muted" v-if="order.created_at">{{ formatDateTime(order.created_at) }}</p>
                    </div>
                  </div>
                  
                  <div class="timeline-item" :class="{ active: order.received_at }">
                    <div class="timeline-marker" :class="order.received_at ? 'bg-info' : 'bg-light'"></div>
                    <div class="timeline-content">
                      <h6>{{ translations.received }}</h6>
                      <p class="text-muted" v-if="order.received_at">{{ formatDateTime(order.received_at) }}</p>
                    </div>
                  </div>
                  
                  <div class="timeline-item" :class="{ active: order.executing_at }">
                    <div class="timeline-marker" :class="order.executing_at ? 'bg-primary' : 'bg-light'"></div>
                    <div class="timeline-content">
                      <h6>{{ translations.executing }}</h6>
                      <p class="text-muted" v-if="order.executing_at">{{ formatDateTime(order.executing_at) }}</p>
                    </div>
                  </div>
                  
                  <div class="timeline-item" :class="{ active: order.partial_payment_at }">
                    <div class="timeline-marker" :class="order.partial_payment_at ? 'bg-warning' : 'bg-light'"></div>
                    <div class="timeline-content">
                      <h6>{{ translations.partial_payment }}</h6>
                      <p class="text-muted" v-if="order.partial_payment_at">{{ formatDateTime(order.partial_payment_at) }}</p>
                    </div>
                  </div>
                  
                  <div class="timeline-item" :class="{ active: order.full_payment_at }">
                    <div class="timeline-marker" :class="order.full_payment_at ? 'bg-success' : 'bg-light'"></div>
                    <div class="timeline-content">
                      <h6>{{ translations.full_payment }}</h6>
                      <p class="text-muted" v-if="order.full_payment_at">{{ formatDateTime(order.full_payment_at) }}</p>
                    </div>
                  </div>
                  
                  <div class="timeline-item" :class="{ active: order.completed_at }">
                    <div class="timeline-marker" :class="order.completed_at ? 'bg-success' : 'bg-light'"></div>
                    <div class="timeline-content">
                      <h6>{{ translations.completed }}</h6>
                      <p class="text-muted" v-if="order.completed_at">{{ formatDateTime(order.completed_at) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Summary -->
          <div class="col-md-4">
            <div class="card mb-4">
              <div class="card-header">
                <h5>{{ translations.order_summary }}</h5>
              </div>
              <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                  <span>{{ translations.status }}:</span>
                  <span class="badge" :class="`bg-${order.status_color}`">{{ order.status_name }}</span>
                </div>
                
                <div class="d-flex justify-content-between mb-2" v-if="order.assigned_employee">
                  <span>{{ translations.assigned_employee }}:</span>
                  <span class="badge bg-primary">{{ order.assigned_employee.name }}</span>
                </div>
                
                <div class="d-flex justify-content-between mb-2" v-if="order.assigned_team">
                  <span>{{ translations.assigned_team }}:</span>
                  <span class="badge bg-info">{{ order.assigned_team.name }}</span>
                </div>
                
                <div class="d-flex justify-content-between mb-2" v-if="order.scheduled_date">
                  <span>{{ translations.scheduled_date }}:</span>
                  <span>{{ formatDate(order.scheduled_date) }}</span>
                </div>
                
                <div class="d-flex justify-content-between mb-2">
                  <span>{{ translations.order_id }}:</span>
                  <span class="badge bg-secondary">#{{ order.id }}</span>
                </div>
                
                <div class="d-flex justify-content-between mb-2">
                  <span>{{ translations.created_at }}:</span>
                  <span>{{ formatDateTime(order.created_at) }}</span>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-between mb-2">
                  <span>{{ translations.base_price }}:</span>
                  <span>{{ order.base_price }} {{ getCurrencySymbol(order.currency) }}</span>
                </div>
                
                <div class="d-flex justify-content-between mb-2" v-if="order.additional_cost > 0">
                  <span>{{ translations.additional_cost }}:</span>
                  <span>{{ order.additional_cost }} {{ getCurrencySymbol(order.currency) }}</span>
                </div>
                
                <div class="d-flex justify-content-between mb-2" v-if="order.discount > 0">
                  <span>{{ translations.discount }}:</span>
                  <span class="text-danger">-{{ order.discount }} {{ getCurrencySymbol(order.currency) }}</span>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-between mb-2">
                  <strong>{{ translations.total_price }}:</strong>
                  <strong>{{ order.total_price }} {{ getCurrencySymbol(order.currency) }}</strong>
                </div>
                
                <div class="d-flex justify-content-between mb-2">
                  <span>{{ translations.paid_amount }}:</span>
                  <span class="text-success">{{ order.paid_amount || 0 }} {{ getCurrencySymbol(order.currency) }}</span>
                </div>
                
                <div class="d-flex justify-content-between">
                  <span>{{ translations.remaining_amount }}:</span>
                  <span class="text-warning">{{ (order.total_price - (order.paid_amount || 0)) }} {{ getCurrencySymbol(order.currency) }}</span>
                </div>
                
                <div v-if="order.paid_at" class="mt-3">
                  <div class="d-flex justify-content-between mb-2">
                    <span>{{ translations.paid_at }}:</span>
                    <span>{{ formatDateTime(order.paid_at) }}</span>
                  </div>
                </div>
                
                <div v-if="order.completed_at" class="mt-3">
                  <div class="d-flex justify-content-between mb-2">
                    <span>{{ translations.completed_at }}:</span>
                    <span>{{ formatDateTime(order.completed_at) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div class="card" v-if="order.notes">
              <div class="card-header">
                <h5>{{ translations.notes }}</h5>
              </div>
              <div class="card-body">
                <p class="text-muted">{{ order.notes }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Status Update Modal -->
    <StatusUpdateModal 
      v-if="showStatusModal"
      :order="order"
      :employees="employees"
      :translations="translations"
      @close="showStatusModal = false"
      @success="showStatusModal = false; router.reload()"
    />

    <!-- Pricing Edit Modal -->
    <PricingEditModal 
      v-if="showPricingModal"
      :order="order"
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
import { Link } from '@inertiajs/vue3'
import StatusUpdateModal from '@/Components/Decorations/StatusUpdateModal.vue'
import PricingEditModal from '@/Components/Decorations/PricingEditModal.vue'

const props = defineProps({
  order: Object,
  translations: Object,
  employees: Array
})

// Modal states
const showStatusModal = ref(false)
const showPricingModal = ref(false)
const processing = ref(false)

// Status form
const statusForm = reactive({
  status: props.order.status,
  assigned_employee_id: props.order.assigned_employee_id || '',
  paid_amount: props.order.paid_amount || '',
  scheduled_date: props.order.scheduled_date ? formatDateTimeLocal(props.order.scheduled_date) : '',
  notes: props.order.notes || ''
})

// Functions
const openStatusModal = () => {
  showStatusModal.value = true
}

const openPricingModal = () => {
  showPricingModal.value = true
}

const updateOrderStatus = () => {
  processing.value = true
  
  router.patch(route('decoration.orders.status', props.order.id), statusForm, {
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

const formatDateTime = (date) => {
  return new Date(date).toLocaleString('ar-EG')
}

const formatDateTimeLocal = (date) => {
  const d = new Date(date)
  return d.toISOString().slice(0, 16)
}

const getCurrencySymbol = (currency) => {
  return currency === 'dollar' ? 'دولار' : 'دينار'
}
</script>

<style scoped>
.timeline {
  position: relative;
  padding-left: 30px;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 15px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e9ecef;
}

.timeline-item {
  position: relative;
  margin-bottom: 30px;
}

.timeline-marker {
  position: absolute;
  left: -22px;
  top: 0;
  width: 15px;
  height: 15px;
  border-radius: 50%;
  border: 2px solid #fff;
  box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-item.active .timeline-marker {
  box-shadow: 0 0 0 2px #007bff;
}

.timeline-content h6 {
  margin-bottom: 5px;
  font-weight: 600;
}

.timeline-content p {
  margin-bottom: 0;
  font-size: 0.875rem;
}
</style>
