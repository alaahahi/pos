<template>
  <div class="modal-mask">
    <div class="modal-wrapper">
      <div class="modal-container" style="max-width: 800px;">
        <div class="modal-header">
          <h3>{{ translations.edit }} {{ translations.decoration }}</h3>
          <button @click="$emit('close')" class="btn-close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <div class="row">
              <!-- Basic Information -->
              <div class="col-md-6">
                <h6 class="text-primary mb-3">
                  <i class="bi bi-info-circle"></i> {{ translations.basic_information }}
                </h6>
                
                <div class="mb-3">
                  <label class="form-label">{{ translations.decoration_name }} *</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.name"
                    required
                  >
                </div>

                <div class="mb-3">
                  <label class="form-label">{{ translations.decoration_description }}</label>
                  <input  
                    class="form-control" 
                    type="text"
                    v-model="form.description"
                  > 
                </div>

                <div class="mb-3">
                  <label class="form-label">{{ translations.decoration_type }} *</label>
                  <select class="form-select" v-model="form.type" required>
                    <option value="">{{ translations.select_type }}</option>
                    <option value="birthday">{{ translations.birthday }}</option>
                    <option value="gender_reveal">{{ translations.gender_reveal }}</option>
                    <option value="baby_shower">{{ translations.baby_shower }}</option>
                    <option value="wedding">{{ translations.wedding }}</option>
                    <option value="graduation">{{ translations.graduation }}</option>
                    <option value="corporate">{{ translations.corporate }}</option>
                    <option value="religious">{{ translations.religious }}</option>
                  </select>
                </div>
              </div>

              <!-- Pricing & Details -->
              <div class="col-md-6">
                <h6 class="text-success mb-3">
                  <i class="bi bi-currency-dollar"></i> {{ translations.pricing_details }}
                </h6>

                <div class="mb-3">
                  <label class="form-label">{{ translations.currency }} *</label>
                  <select class="form-select" v-model="form.currency" required>
                    <option value="dinar">{{ translations.dinar }}</option>
                    <option value="dollar">{{ translations.dollar }}</option>
                  </select>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">{{ translations.base_price_dinar }} *</label>
                      <input 
                        type="number" 
                        step="0.01"
                        class="form-control" 
                        v-model="form.base_price_dinar"
                        required
                      >
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">{{ translations.base_price_dollar }} *</label>
                      <input 
                        type="number" 
                        step="0.01"
                        class="form-control" 
                        v-model="form.base_price_dollar"
                        required
                      >
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">{{ translations.duration_hours }} *</label>
                      <input 
                        type="number" 
                        class="form-control" 
                        v-model="form.duration_hours"
                        required
                      >
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">{{ translations.team_size }} *</label>
                      <input 
                        type="number" 
                        class="form-control" 
                        v-model="form.team_size"
                        required
                      >
                    </div>
                  </div>
                </div>

                <div class="mb-3">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      v-model="form.is_active"
                      id="is_active_edit"
                    >
                    <label class="form-check-label" for="is_active_edit">
                      {{ translations.active }}
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="d-flex justify-content-center gap-2 mt-4">
              <button type="button" class="btn btn-secondary text-center w-100" @click="$emit('close')">
                <i class="bi bi-x-circle"></i> {{ translations.cancel }}
              </button>
              <button type="submit" class="btn btn-primary w-100 text-center" :disabled="processing">
                <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
                <i class="bi bi-check-circle"></i> {{ translations.save }}
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

const props = defineProps({
  decoration: Object,
  translations: Object
})

const emit = defineEmits(['success', 'close'])

// State
const processing = ref(false)

// Form
const form = reactive({
  name: '',
  description: '',
  type: '',
  currency: 'dinar',
  base_price: '',
  base_price_dinar: '',
  base_price_dollar: '',
  duration_hours: '',
  team_size: '',
  is_active: true,
  materials_cost: 0,
  labor_cost: 0,
  transportation_cost: 0
})

// Methods
const submitForm = () => {
  processing.value = true
  
  // إعداد البيانات للإرسال
  const formData = {
    ...form,
    base_price: form.currency === 'dinar' ? form.base_price_dinar : form.base_price_dollar,
    materials_cost: parseFloat(form.materials_cost) || 0,
    labor_cost: parseFloat(form.labor_cost) || 0,
    transportation_cost: parseFloat(form.transportation_cost) || 0
  }
  
  router.patch(route('decorations.update', props.decoration.id), formData, {
    onSuccess: () => {
      processing.value = false
      emit('success')
    },
    onError: () => {
      processing.value = false
    }
  })
}

// Initialize form with decoration data
onMounted(() => {
  if (props.decoration) {
    form.name = props.decoration.name || ''
    form.description = props.decoration.description || ''
    form.type = props.decoration.type || ''
    form.currency = props.decoration.currency || 'dinar'
    form.base_price = props.decoration.base_price || ''
    form.base_price_dinar = props.decoration.base_price_dinar || ''
    form.base_price_dollar = props.decoration.base_price_dollar || ''
    form.duration_hours = props.decoration.duration_hours || ''
    form.team_size = props.decoration.team_size || ''
    form.is_active = props.decoration.is_active !== undefined ? props.decoration.is_active : true
    
    // تحميل التكاليف الإضافية
    if (props.decoration.pricing_details) {
      const details = typeof props.decoration.pricing_details === 'string' 
        ? JSON.parse(props.decoration.pricing_details) 
        : props.decoration.pricing_details
      form.materials_cost = details.materials || 0
      form.labor_cost = details.labor || 0
      form.transportation_cost = details.transportation || 0
    }
  }
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
  max-width: 800px;
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

.text-primary {
  color: #007bff !important;
}

.text-success {
  color: #28a745 !important;
}

h6 {
  font-weight: 600;
  margin-bottom: 1rem;
}

.form-label {
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.form-control, .form-select {
  border-radius: 0.375rem;
  border: 1px solid #ced4da;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus, .form-select:focus {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.btn {
  border-radius: 0.375rem;
  font-weight: 500;
}
</style>
