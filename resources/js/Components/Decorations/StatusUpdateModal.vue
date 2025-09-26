<template>
  <div class="modal-mask">
    <div class="modal-wrapper">
      <div class="modal-container">
        <div class="modal-header">
          <h3>{{ translations.update_status }}</h3>
          <button @click="$emit('close')" class="btn-close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="updateStatus">
            <!-- Status Selection -->
            <div class="mb-4">
              <label class="form-label fw-bold">{{ translations.change_status_to }}</label>
              <div class="row g-2">
                <div class="col-6" v-for="status in statusOptions" :key="status.value">
                  <button 
                    type="button"
                    class="btn w-100 status-btn" 
                    :class="{ 'btn-primary': form.status === status.value, 'btn-outline-secondary': form.status !== status.value }"
                    @click="form.status = status.value"
                  >
                    <i :class="status.icon"></i>
                    {{ status.label }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Employee Assignment (only show if no employee assigned yet) -->
            <div class="mb-3" v-if="!order.assigned_employee_id">
              <label class="form-label">{{ translations.assign_employee }}</label>
              <select class="form-select" v-model="form.assigned_employee_id">
                <option value="">{{ translations.select_employee }}</option>
                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                  {{ employee.name }}
                </option>
              </select>
            </div>

            <!-- Paid Amount (only for payment statuses and not full payment) -->
            <div class="mb-3" v-if="(form.status === 'partial_payment') && form.status !== 'full_payment'">
              <label class="form-label">{{ translations.paid_amount }}</label>
              <input type="number" step="0.01" class="form-control" v-model="form.paid_amount">
            </div>

            <!-- Notes -->
            <div class="mb-3">
              <label class="form-label">{{ translations.notes }}</label>
              <textarea class="form-control" rows="3" v-model="form.notes" :placeholder="translations.add_notes"></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-center gap-2">
              <button type="button" class="btn btn-secondary text-center w-100" @click="$emit('close')">
                {{ translations.cancel }}
              </button>
              <button type="submit" class="btn btn-primary w-100 text-center" :disabled="processing">
                <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
                {{ translations.update_status }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  order: Object,
  employees: Array,
  translations: Object
})

const emit = defineEmits(['close', 'success'])

const processing = ref(false)

const form = reactive({
  status: props.order.status,
  assigned_employee_id: props.order.assigned_employee_id || '',
  paid_amount: props.order.paid_amount || 0,
  notes: props.order.notes || ''
})

// Status options with icons
const statusOptions = [
  { value: 'created', label: props.translations.created, icon: 'bi bi-plus-circle' },
  { value: 'received', label: props.translations.received, icon: 'bi bi-check-circle' },
  { value: 'executing', label: props.translations.executing, icon: 'bi bi-play-circle' },
  { value: 'partial_payment', label: props.translations.partial_payment, icon: 'bi bi-cash-coin' },
  { value: 'full_payment', label: props.translations.full_payment, icon: 'bi bi-credit-card' },
  { value: 'completed', label: props.translations.completed, icon: 'bi bi-check2-all' },
  { value: 'cancelled', label: props.translations.cancelled, icon: 'bi bi-x-circle' }
]

const updateStatus = () => {
  processing.value = true
  
  // Prepare form data
  const formData = {
    status: form.status,
    assigned_employee_id: form.assigned_employee_id,
    notes: form.notes
  }
  
  // Only include paid_amount for partial payment
  if (form.status === 'partial_payment') {
    formData.paid_amount = form.paid_amount
  }
  
  router.patch(route('decoration.orders.status', props.order.id), formData, {
    onSuccess: () => {
      processing.value = false
      emit('success')
    },
    onError: () => {
      processing.value = false
    }
  })
}
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
  max-width: 500px;
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

.status-btn {
  padding: 0.75rem 0.5rem;
  font-size: 0.875rem;
  border-radius: 8px;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.status-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.status-btn.btn-primary {
  background: linear-gradient(135deg, #007bff, #0056b3);
  border-color: #007bff;
}

.status-btn.btn-outline-secondary {
  border-color: #dee2e6;
  background-color: #f8f9fa;
}

.status-btn.btn-outline-secondary:hover {
  background-color: #e9ecef;
  border-color: #adb5bd;
}

.status-btn i {
  display: block;
  font-size: 1.2rem;
  margin-bottom: 0.25rem;
}
</style>
