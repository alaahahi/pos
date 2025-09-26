<template>
  <div class="modal-mask">
    <div class="modal-wrapper">
      <div class="modal-container">
        <div class="modal-header">
          <h3>{{ translations.assign_employee }}</h3>
          <button @click="$emit('close')" class="btn-close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="assignEmployee">
            <div class="mb-3">
              <label class="form-label">{{ translations.select_employee }}</label>
              <select class="form-select" v-model="form.assigned_employee_id" required>
                <option value="">{{ translations.select_employee }}</option>
                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                  {{ employee.name }}
                </option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">{{ translations.notes }}</label>
              <input  class="form-control" type="text" v-model="form.notes"> 
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

const form = reactive({
  assigned_employee_id: props.order.assigned_employee_id || '',
  notes: props.order.notes || ''
})

const assignEmployee = () => {
  processing.value = true
  
  router.patch(route('decoration.orders.status', props.order.id), form, {
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
</style>
