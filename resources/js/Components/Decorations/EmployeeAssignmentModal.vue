<template>
  <div class="modal-mask">
    <div class="modal-wrapper">
      <div class="modal-container">
        <div class="modal-header">
          <h3>{{ translations.assign_employee }}</h3>
          <button @click="$emit('close')" class="btn-close"></button>
        </div>
        <div class="modal-body">
          <!-- Error Alert -->
          <div v-if="Object.keys(errors).length > 0" class="alert alert-danger mb-3" role="alert">
            <h6 class="alert-heading">
              <i class="bi bi-exclamation-triangle"></i>
              حدثت أخطاء:
            </h6>
            <ul class="mb-0">
              <li v-for="(error, field) in errors" :key="field">
                {{ Array.isArray(error) ? error[0] : error }}
              </li>
            </ul>
          </div>

          <form @submit.prevent="assignEmployee">
            <div class="mb-3">
              <label class="form-label">{{ translations.select_employee }}</label>
              <select class="form-select" v-model="form.assigned_employee_id" :class="{ 'is-invalid': errors.assigned_employee_id }" required>
                <option value="">{{ translations.select_employee }}</option>
                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                  {{ employee.name }}
                </option>
              </select>
              <div class="invalid-feedback" v-if="errors.assigned_employee_id">
                {{ Array.isArray(errors.assigned_employee_id) ? errors.assigned_employee_id[0] : errors.assigned_employee_id }}
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">{{ translations.notes }}</label>
              <textarea class="form-control" v-model="form.notes" :class="{ 'is-invalid': errors.notes }" rows="3"></textarea>
              <div class="invalid-feedback" v-if="errors.notes">
                {{ Array.isArray(errors.notes) ? errors.notes[0] : errors.notes }}
              </div>
            </div>

            <div class="d-flex justify-content-center gap-2">
              <button type="button" class="btn btn-secondary text-center w-100" @click="$emit('close')">
                {{ translations.cancel }}
              </button>
              <button type="submit" class="btn btn-primary w-100 text-center" :disabled="processing">
                <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
                {{ translations.assign }}
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
const errors = ref({})

const form = reactive({
  assigned_employee_id: props.order.assigned_employee_id || '',
  notes: props.order.notes || ''
})

const assignEmployee = () => {
  if (!form.assigned_employee_id) {
    alert(props.translations.select_employee || 'يرجى اختيار موظف')
    return
  }
  
  processing.value = true
  errors.value = {}
  
  router.patch(route('decoration.orders.status', props.order.id), form, {
    onSuccess: () => {
      processing.value = false
      emit('success')
    },
    onError: (errs) => {
      processing.value = false
      errors.value = errs
      console.error('Assignment errors:', errs)
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

.alert-danger ul {
  padding-left: 1.5rem;
  margin-bottom: 0;
}
</style>
