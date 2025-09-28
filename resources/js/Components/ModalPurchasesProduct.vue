<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const props = defineProps({
  show: Boolean,
  data: Object,
  accounts: Array,
  translations: Array
});

const emit = defineEmits(['close', 'success']);

const toast = useToast();

const form = ref({
  date: getTodayDate(),
  price_cost: 0,
  quantity: 0, 
  price: 0,
  withdraw_from_box: false,
  currency: 'IQD'
});

const loading = ref(false);

function getTodayDate() {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

const resetForm = () => {
  form.value = {
    date: getTodayDate(),
    price_cost: 0,
    quantity: 0, 
    price: 0,
    withdraw_from_box: false,
    currency: 'IQD'
  };
}

// Watch for data changes to populate form
watch(() => props.data, (newData) => {
  if (newData && newData.id) {
    form.value.price_cost = newData.price_cost || 0;
    form.value.price = newData.price || 0;
    form.value.quantity = 0; // Always start with 0 for new purchase
  }
}, { immediate: true });

const submit = async () => {
  if (!props.data || !props.data.id) {
    toast.error('لم يتم تحديد المنتج');
    return;
  }

  if (form.value.quantity <= 0) {
    toast.error('يجب إدخال كمية أكبر من صفر');
    return;
  }

  if (form.value.price_cost <= 0) {
    toast.error('يجب إدخال سعر التكلفة');
    return;
  }

  if (form.value.price <= 0) {
    toast.error('يجب إدخال سعر البيع');
    return;
  }

  loading.value = true;

  try {
    console.log('Sending data:', {
      product_id: props.data.id,
      quantity: form.value.quantity,
      price_cost: form.value.price_cost,
      price: form.value.price,
      date: form.value.date,
      withdraw_from_box: form.value.withdraw_from_box,
      currency: form.value.currency
    });
    
    const response = await axios.post('api/products/process-purchase', {
      product_id: props.data.id,
      quantity: form.value.quantity,
      price_cost: form.value.price_cost,
      price: form.value.price,
      date: form.value.date,
      withdraw_from_box: form.value.withdraw_from_box,
      currency: form.value.currency
    });

    if (response.data.success) {
      toast.success(response.data.message);
      emit('success', response.data);
      emit('close');
      resetForm();
    } else {
      toast.error(response.data.message || 'حدث خطأ أثناء المعالجة');
    }
  } catch (error) {
    console.error('Purchase error:', error);
    if (error.response && error.response.data && error.response.data.message) {
      toast.error(error.response.data.message);
    } else {
      toast.error('حدث خطأ أثناء معالجة المشتريات');
    }
  } finally {
    loading.value = false;
  }
}

const calculateTotal = () => {
  return (form.value.quantity * form.value.price_cost).toFixed(2);
}
</script>
  
<template>
  <Transition name="modal">
    <div v-if="show" class="modal-mask">
      <div class="modal-wrapper max-h-[80vh]">
        <div class="modal-container">
          <div class="modal-header">
            <h3 class="text-primary">
              <i class="bi bi-cart-plus"></i>
              إضافة مشتريات - {{ data?.name || 'منتج' }}
            </h3>
          </div>
          
          <div class="modal-body">
            <div class="row g-3">
              <!-- Product Info -->
              <div class="col-12">
                <div class="alert alert-info">
                  <strong>المنتج:</strong> {{ data?.name || 'غير محدد' }}<br>
                  <strong>الكمية الحالية:</strong> {{ data?.quantity || 0 }}<br>
                  <strong>سعر التكلفة الحالي:</strong> {{ data?.price_cost || 0 }}<br>
                  <strong>سعر البيع الحالي:</strong> {{ data?.price || 0 }}
                </div>
              </div>

              <!-- Price Cost -->
              <div class="col-md-6">
                <label for="price_cost" class="form-label">
                  <i class="bi bi-currency-dollar"></i>
                  {{ translations.price_cost }}
                </label>
                <input
                  id="price_cost"
                  type="number"
                  step="0.01"
                  class="form-control"
                  :placeholder="translations.price_cost"
                  v-model="form.price_cost"
                  required
                />
              </div>

              <!-- Price -->
              <div class="col-md-6">
                <label for="price" class="form-label">
                  <i class="bi bi-tag"></i>
                  {{ translations.price }}
                </label>
                <input
                  id="price"
                  type="number"
                  step="0.01"
                  class="form-control"
                  :placeholder="translations.price"
                  v-model="form.price"
                  required
                />
              </div>

              <!-- Quantity -->
              <div class="col-md-6">
                <label for="quantity" class="form-label">
                  <i class="bi bi-box"></i>
                  {{ translations.quantity }}
                </label>
                <input
                  id="quantity"
                  type="number"
                  min="1"
                  class="form-control"
                  :placeholder="translations.quantity"
                  v-model="form.quantity"
                  required
                />
              </div>

              <!-- Currency -->
              <div class="col-md-6">
                <label for="currency" class="form-label">
                  <i class="bi bi-currency-exchange"></i>
                  العملة
                </label>
                <select id="currency" class="form-control" v-model="form.currency">
                  <option value="IQD">دينار عراقي</option>
                  <option value="$">دولار</option>
                </select>
              </div>

              <!-- Date -->
              <div class="col-md-6">
                <label for="date" class="form-label">
                  <i class="bi bi-calendar"></i>
                  {{ translations.date }}
                </label>
                <input
                  id="date"
                  type="date"
                  class="form-control"
                  v-model="form.date"
                  required
                />
              </div>

              <!-- Total Cost Display -->
              <div class="col-md-6">
                <label class="form-label">
                  <i class="bi bi-calculator"></i>
                  إجمالي التكلفة
                </label>
                <div class="form-control-plaintext bg-light p-2 rounded">
                  <strong>{{ calculateTotal() }} {{ form.currency }}</strong>
                </div>
              </div>

              <!-- Withdraw from Box Option -->
              <div class="col-12">
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="withdraw_from_box"
                    v-model="form.withdraw_from_box"
                  />
                  <label class="form-check-label" for="withdraw_from_box">
                    <i class="bi bi-wallet2"></i>
                    سحب المبلغ من الصندوق تلقائياً
                  </label>
                </div>
                <small class="text-muted">
                  عند تفعيل هذا الخيار، سيتم سحب {{ calculateTotal() }} {{ form.currency }} من الصندوق الرئيسي تلقائياً
                </small>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <div class="d-flex w-100 gap-2">
              <!-- Cancel Button -->
              <button
                class="btn btn-secondary flex-fill"
                @click="$emit('close')"
                :disabled="loading"
              >
                <i class="bi bi-x-circle"></i>
                إلغاء
              </button>

              <!-- Submit Button -->
              <button
                class="btn btn-primary flex-fill"
                @click="submit"
                :disabled="loading || form.quantity <= 0 || form.price_cost <= 0 || form.price <= 0"
              >
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-check-circle"></i>
                {{ loading ? 'جاري المعالجة...' : 'تأكيد المشتريات' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>
  
<style>
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
  width: 60%;
  min-width: 500px;
  margin: 0px auto;
  padding: 20px 30px;
  padding-bottom: 60px;
  background-color: #fff;
  border-radius: 2px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
  transition: all 0.3s ease;
  border-radius: 10px;
}

.modal-header h3 {
  margin-top: 0;
  color: #42b983;
}

.modal-body {
  margin: 20px 0;
}

.modal-default-button {
  float: right;
  width: 100%;
  color: #fff;
}

/*
 * The following styles are auto-applied to elements with
 * transition="modal" when their visibility is toggled
 * by Vue.js.
 *
 * You can easily play with the modal transition by editing
 * these styles.
 */

.modal-enter-from {
  opacity: 0;
}

.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
  -webkit-transform: scale(1.1);
  transform: scale(1.1);
}
</style>