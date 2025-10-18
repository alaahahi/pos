<template>
  <div class="modal-mask">
    <div class="modal-wrapper">
      <div class="modal-container" style="max-width: 900px;">
        <div class="modal-header">
          <h3>{{ translations.create_order }} - {{ decoration.name }}</h3>
          <button @click="$emit('close')" class="btn-close"></button>
        </div>
        <div class="modal-body">
          <!-- Error Summary -->
          <div v-if="Object.keys(errors).length > 0" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <h5 class="alert-heading">
              <i class="bi bi-exclamation-triangle"></i>
              يرجى تصحيح الأخطاء التالية:
            </h5>
            <ul class="mb-0">
              <li v-for="(error, field) in errors" :key="field">
                <strong>{{ getFieldLabel(field) }}:</strong> {{ Array.isArray(error) ? error[0] : error }}
              </li>
            </ul>
          </div>

          <form @submit.prevent="submitOrder">
            <!-- Customer Selection -->
            <div class="row mb-4">
              <div class="col-12">
                <h5 class="text-primary">
                  <i class="bi bi-person"></i> {{ translations.customer_details }}
                </h5>
                <hr>
              </div>
              <div class="col-md-8">
                <label class="form-label">{{ translations.select_customer }}</label>
                <select class="form-select" v-model="form.customer_id" @change="onCustomerSelect" :class="{ 'is-invalid': errors.customer_id }">
                  <option value="">{{ translations.select_customer }}</option>
                  <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                    {{ customer.name }} - {{ customer.phone }}
                  </option>
                </select>
                <div class="invalid-feedback" v-if="errors.customer_id">{{ errors.customer_id }}</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-outline-primary w-100" @click="showNewCustomerForm = !showNewCustomerForm">
                  <i class="bi bi-plus-circle"></i> {{ translations.create_new_customer }}
                </button>
              </div>

              <!-- New Customer Form -->
              <div v-if="showNewCustomerForm" class="col-12 mt-3">
                <div class="border rounded p-3 bg-light">
                  <div class="row">
                    <div class="col-md-4">
                      <label class="form-label">{{ translations.name }} *</label>
                      <input type="text" class="form-control" v-model="newCustomer.name" :class="{ 'is-invalid': errors.customer_name }" required>
                      <div class="invalid-feedback" v-if="errors.customer_name">{{ errors.customer_name }}</div>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">{{ translations.phone }}</label>
                      <input type="text" class="form-control" v-model="newCustomer.phone" :class="{ 'is-invalid': errors.customer_phone }">
                      <div class="invalid-feedback" v-if="errors.customer_phone">{{ errors.customer_phone }}</div>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">{{ translations.email }}</label>
                      <input type="email" class="form-control" v-model="newCustomer.email" :class="{ 'is-invalid': errors.customer_email }">
                      <div class="invalid-feedback" v-if="errors.customer_email">{{ errors.customer_email }}</div>
                    </div>
                    <div class="col-12 mt-2">
                      <label class="form-label">{{ translations.address }}</label>
                      <input  class="form-control" type="text"  v-model="newCustomer.address"> 
                    </div>
                  </div>
                </div>
              </div>

              <!-- Selected Customer Info -->
              <div v-if="form.customer_name && !showNewCustomerForm" class="col-12 mt-3">
                <div class="alert alert-info">
                  <strong>{{ translations.selected_customer }}:</strong><br>
                  <i class="bi bi-person"></i> {{ form.customer_name }}<br>
                  <i class="bi bi-telephone"></i> {{ form.customer_phone }}<br>
                  <i class="bi bi-envelope"></i> {{ form.customer_email || translations.not_provided }}
                </div>
              </div>
            </div>

            <!-- Event Details -->
            <div class="row mb-4">
              <div class="col-12">
                <h5 class="text-success">
                  <i class="bi bi-calendar-event"></i> {{ translations.event_details }}
                </h5>
                <hr>
              </div>
              <div class="col-md-6">
                <label class="form-label">{{ translations.event_address }} *</label>
                <input  class="form-control" type="text" v-model="form.event_address" :class="{ 'is-invalid': errors.event_address }" required @input="clearError('event_address')"> 
                <div class="invalid-feedback" v-if="errors.event_address">{{ errors.event_address }}</div>
              </div>
              <div class="col-md-3">
                <label class="form-label">{{ translations.event_date }} *</label>
                <input type="date" class="form-control" v-model="form.event_date" :class="{ 'is-invalid': errors.event_date }" required :min="getTodayDate()" @input="clearError('event_date')">
                <div class="invalid-feedback" v-if="errors.event_date">{{ errors.event_date }}</div>
              </div>
              <div class="col-md-3">
                <label class="form-label">{{ translations.event_time }} *</label>
                <input type="time" class="form-control" v-model="form.event_time" :class="{ 'is-invalid': errors.event_time }" required @input="clearError('event_time')">
                <div class="invalid-feedback" v-if="errors.event_time">{{ errors.event_time }}</div>
              </div>
              <div class="col-md-6 mt-3">
                <label class="form-label">{{ translations.guest_count }}</label>
                <input type="number" class="form-control" v-model="form.guest_count" :class="{ 'is-invalid': errors.guest_count }" min="1">
                <div class="invalid-feedback" v-if="errors.guest_count">{{ errors.guest_count }}</div>
              </div>
              <div class="col-12 mt-3">
                <label class="form-label">{{ translations.order_specifications || 'مواصفات الطلب' }}</label>
                <textarea 
                  class="form-control  " 
                  style="border-color: #007bff;box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);"
                  v-model="form.special_requests" 
                  :class="{ 'is-invalid': errors.special_requests }"
                  rows="4"
                  :placeholder="translations.enter_order_specifications || 'أدخل مواصفات الطلب والطلبات الخاصة...'"
                  @input="clearError('special_requests')"
                ></textarea>
                <div class="invalid-feedback" v-if="errors.special_requests">{{ errors.special_requests }}</div>
                <small class="text-muted">مثل: عدد البالونات، الألوان المطلوبة، تفاصيل الديكور، إلخ...</small>
              </div>
            </div>

            <!-- Decoration Details -->
            <div class="row mb-4">
              <div class="col-12">
                <h5 class="text-info">
                  <i class="bi bi-palette"></i> {{ translations.decoration_details }}
                </h5>
                <hr>
              </div>
              <div class="col-md-6">
                <div class="card">
                  <div class="card-body">
                    <h6 class="card-title">{{ decoration.name }}</h6>
                    <p class="card-text">{{ decoration.description }}</p>
                    <div class="d-flex justify-content-between">
                      <span class="badge bg-primary">{{ decoration.type_name }}</span>
                      <span class="badge bg-info">{{ decoration.duration_hours }} {{ translations.hours }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card">
                  <div class="card-body">
                    <h6 class="card-title">{{ translations.pricing_details }}</h6>
                    <div class="d-flex justify-content-between mb-2">
                      <span>{{ translations.base_price }}:</span>
                      <strong>{{ formatPrice(decoration) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                      <span>{{ translations.team_size }}:</span>
                      <span>{{ decoration.team_size }} {{ translations.team_members }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                      <span>{{ translations.currency }}:</span>
                      <span class="badge" :class="decoration.currency === 'dollar' ? 'bg-success' : 'bg-primary'">
                        {{ decoration.currency === 'dollar' ? translations.dollar : translations.dinar }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pricing -->
            <div class="row mb-4">
              <div class="col-12">
                <h5 class="text-warning">
                  <i class="bi bi-calculator"></i> {{ translations.payment_details }}
                </h5>
                <hr>
              </div>
              <div class="col-md-3">
                <label class="form-label">{{ translations.base_price }}</label>
                <input type="number" step="0.01" class="form-control" v-model="form.base_price" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">{{ translations.additional_cost }}</label>
                <input type="number" step="0.01" class="form-control" v-model="form.additional_cost" @input="calculateTotal" :class="{ 'is-invalid': errors.additional_cost }">
                <div class="invalid-feedback" v-if="errors.additional_cost">{{ errors.additional_cost }}</div>
              </div>
              <div class="col-md-3">
                <label class="form-label">{{ translations.discount }}</label>
                <input type="number" step="0.01" class="form-control" v-model="form.discount" @input="calculateTotal" :class="{ 'is-invalid': errors.discount }">
                <div class="invalid-feedback" v-if="errors.discount">{{ errors.discount }}</div>
              </div>
              <div class="col-md-3">
                <label class="form-label">{{ translations.total_price }}</label>
                <input type="number" step="0.01" class="form-control" v-model="form.total_price" readonly>
              </div>
            </div>

            <!-- Actions -->
            <div class="d-flex justify-content-center gap-2">
              <button type="button" class="btn btn-secondary text-center w-100" @click="$emit('close')">
                <i class="bi bi-x-circle"></i> {{ translations.cancel }}
              </button>
              <button type="submit" class="btn btn-success w-100 text-center" :disabled="processing">
                <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
                <i class="bi bi-check-circle"></i> {{ translations.create_order }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  decoration: Object,
  customers: Array,
  translations: Object
})

const emit = defineEmits(['close', 'success'])

// State
const processing = ref(false)
const showNewCustomerForm = ref(false)
const errors = ref({})

// Forms
const form = reactive({
  decoration_id: props.decoration.id,
  customer_id: '',
  customer_name: '',
  customer_phone: '',
  customer_email: '',
  event_address: '',
  event_date: '',
  event_time: '',
  guest_count: 50,
  special_requests: '',
  base_price: 0,
  additional_cost: 0,
  discount: 0,
  total_price: 0
})

const newCustomer = reactive({
  name: '',
  phone: '',
  email: '',
  address: ''
})

// Methods
const formatPrice = (decoration) => {
  const price = decoration.currency === 'dollar' ? decoration.base_price_dollar : decoration.base_price_dinar
  const currency = decoration.currency === 'dollar' ? props.translations.dollar : props.translations.dinar
  return `${parseFloat(price).toFixed(2)} ${currency}`
}

const onCustomerSelect = () => {
  if (form.customer_id) {
    const customer = props.customers.find(c => c.id == form.customer_id)
    if (customer) {
      form.customer_name = customer.name
      form.customer_phone = customer.phone
      form.customer_email = customer.email || ''
      showNewCustomerForm.value = false
    }
  } else {
    form.customer_name = ''
    form.customer_phone = ''
    form.customer_email = ''
  }
}

const getTodayDate = () => {
  const today = new Date()
  const year = today.getFullYear()
  const month = String(today.getMonth() + 1).padStart(2, '0')
  const day = String(today.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const clearError = (field) => {
  if (errors.value[field]) {
    delete errors.value[field]
  }
}

const getFieldLabel = (field) => {
  const labels = {
    'customer_name': props.translations.customer_name || 'اسم العميل',
    'customer_phone': props.translations.customer_phone || 'رقم الهاتف',
    'customer_email': props.translations.customer_email || 'البريد الإلكتروني',
    'event_address': props.translations.event_address || 'عنوان الحدث',
    'event_date': props.translations.event_date || 'تاريخ الحدث',
    'event_time': props.translations.event_time || 'وقت الحدث',
    'guest_count': props.translations.guest_count || 'عدد الضيوف',
    'special_requests': props.translations.order_specifications || 'مواصفات الطلب',
    'additional_cost': props.translations.additional_cost || 'تكلفة إضافية',
    'discount': props.translations.discount || 'خصم'
  }
  return labels[field] || field
}

const calculateTotal = () => {
  const base = parseFloat(form.base_price) || 0
  const additional = parseFloat(form.additional_cost) || 0
  const discount = parseFloat(form.discount) || 0
  form.total_price = Math.max(0, base + additional - discount)
}

const submitOrder = async () => {
  processing.value = true
  
  try {
    // Create customer if new customer form is filled
    if (showNewCustomerForm.value && newCustomer.name) {
      await router.post('/customers', {
        ...newCustomer,
        is_active: true,
        notes: props.translations.created_from_decoration
      }, {
        preserveState: true,
        onSuccess: (page) => {
          // Update form with new customer data
          form.customer_name = newCustomer.name
          form.customer_phone = newCustomer.phone
          form.customer_email = newCustomer.email
        }
      })
    }
    
    // Create the order
    router.post('/decoration-orders', form, {
      onSuccess: () => {
        processing.value = false
        errors.value = {}
      axios.post('/logs', {
        module_name: 'Decoration Order',
        action: 'Order Creation Modal Submit',
        affected_record_id: null,
        updated_data: form
      }).catch(() => {})
        emit('success')
      },
      onError: (errs) => {
        processing.value = false
        errors.value = errs
        console.error('Validation errors:', errs)
      }
    })
  } catch (error) {
    processing.value = false
    console.error('Error creating order:', error)
  }
}

// Initialize form
onMounted(() => {
  const price = props.decoration.currency === 'dollar' ? props.decoration.base_price_dollar : props.decoration.base_price_dinar
  form.base_price = parseFloat(price)
  form.total_price = parseFloat(price)
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
  display: table;
  transition: opacity 0.3s ease;
}

.modal-wrapper {
  display: table-cell;
  vertical-align: middle;
}

.modal-container {
  width: 90%;
  max-width: 900px;
  margin: 0px auto;
  padding: 20px 30px;
  background-color: #fff;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
  transition: all 0.3s ease;
  max-height: 90vh;
  overflow-y: auto;
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

.alert {
  border-radius: 8px;
}

.card {
  border-radius: 8px;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

hr {
  margin: 0.5rem 0 1rem 0;
}

.text-primary {
  color: #007bff !important;
}

.text-success {
  color: #28a745 !important;
}

.text-info {
  color: #17a2b8 !important;
}

.text-warning {
  color: #ffc107 !important;
}

/* Error Styles */
.is-invalid {
  border-color: #dc3545 !important;
  padding-right: calc(1.5em + 0.75rem);
  background-repeat: no-repeat;
  background-position: right calc(0.375em + 0.1875rem) center;
  background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.invalid-feedback {
  display: block;
  width: 100%;
  margin-top: 0.25rem;
  font-size: 0.875em;
  color: #dc3545;
}

.alert-danger {
  background-color: #f8d7da;
  border-color: #f5c2c7;
  color: #842029;
}

.alert-danger .alert-heading {
  color: #842029;
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
}

.alert-danger ul {
  padding-left: 1.5rem;
  margin-bottom: 0;
}

.alert-danger li {
  margin-bottom: 0.25rem;
}

.input-group .is-invalid {
  border-right: 2px solid #dc3545;
}

/* Textarea Styles */
textarea.form-control {
  resize: vertical;
  min-height: 100px;
  font-family: inherit;
  line-height: 1.5;
}

textarea.form-control:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
</style>
