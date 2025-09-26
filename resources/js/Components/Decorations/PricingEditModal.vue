<template>
  <div class="modal-mask">
    <div class="modal-wrapper">
      <div class="modal-container">
        <div class="modal-header">
          <h3>{{ translations.edit_pricing }}</h3>
          <button @click="$emit('close')" class="btn-close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="updatePricing">
            <!-- Base Price -->
            <div class="mb-3">
              <label class="form-label">{{ translations.base_price }}</label>
              <div class="input-group">
                <input type="number" step="0.01" class="form-control" v-model="form.base_price" readonly>
                <span class="input-group-text">{{ getCurrencySymbol(order.currency) }}</span>
              </div>
            </div>

            <!-- Additional Cost -->
            <div class="mb-3">
              <label class="form-label">{{ translations.additional_cost }}</label>
              <div class="input-group">
                <input type="number" step="0.01" class="form-control" v-model="form.additional_cost" @input="calculateTotal">
                <span class="input-group-text">{{ getCurrencySymbol(order.currency) }}</span>
              </div>
              <small class="text-muted">{{ translations.additional_cost_help }}</small>
            </div>

            <!-- Discount -->
            <div class="mb-3">
              <label class="form-label">{{ translations.discount }}</label>
              <div class="input-group">
                <input type="number" step="0.01" class="form-control" v-model="form.discount" @input="calculateTotal">
                <span class="input-group-text">{{ getCurrencySymbol(order.currency) }}</span>
              </div>
              <small class="text-muted">{{ translations.discount_help }}</small>
            </div>

            <!-- Total Price Preview -->
            <div class="mb-4">
              <div class="card bg-light">
                <div class="card-body">
                  <h6 class="card-title">{{ translations.price_summary }}</h6>
                  <div class="d-flex justify-content-between mb-2">
                    <span>{{ translations.base_price }}:</span>
                    <span>{{ form.base_price }} {{ getCurrencySymbol(order.decoration?.currency) }}</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2" v-if="form.additional_cost > 0">
                    <span>{{ translations.additional_cost }}:</span>
                    <span class="text-success">+{{ form.additional_cost }} {{ getCurrencySymbol(order.decoration?.currency) }}</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2" v-if="form.discount > 0">
                    <span>{{ translations.discount }}:</span>
                    <span class="text-danger">-{{ form.discount }} {{ getCurrencySymbol(order.decoration?.currency) }}</span>
                  </div>
                  <hr>
                  <div class="d-flex justify-content-between">
                    <strong>{{ translations.total_price }}:</strong>
                    <strong class="text-primary">{{ calculatedTotal }} {{ getCurrencySymbol(order.decoration?.currency) }}</strong>
                  </div>
                </div>
              </div>
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
                {{ translations.update_pricing }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  order: Object,
  translations: Object
})

const emit = defineEmits(['close', 'success'])

const processing = ref(false)

const form = reactive({
  base_price: parseFloat(props.order.base_price) || 0,
  additional_cost: parseFloat(props.order.additional_cost) || 0,
  discount: parseFloat(props.order.discount) || 0,
  notes: props.order.notes || ''
})

// Computed total
const calculatedTotal = computed(() => {
  const basePrice = parseFloat(form.base_price) || 0
  const additionalCost = parseFloat(form.additional_cost) || 0
  const discount = parseFloat(form.discount) || 0
  const total = basePrice + additionalCost - discount
  return total.toFixed(2)
})

// Calculate total and update form
const calculateTotal = () => {
  form.total_price = calculatedTotal.value
}

// Currency symbol helper
const getCurrencySymbol = (currency) => {
  return currency === 'dollar' ? 'دولار' : 'دينار'
}

const updatePricing = () => {
  processing.value = true
  
  const formData = {
    base_price: parseFloat(form.base_price) || 0,
    additional_cost: parseFloat(form.additional_cost) || 0,
    discount: parseFloat(form.discount) || 0,
    total_price: parseFloat(calculatedTotal.value) || 0,
    notes: form.notes
  }
  
  router.patch(route('decoration.orders.pricing', props.order.id), formData, {
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

.input-group-text {
  background-color: #f8f9fa;
  border-color: #ced4da;
  color: #495057;
  font-weight: 500;
}
</style>
