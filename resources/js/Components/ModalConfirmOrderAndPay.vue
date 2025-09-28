<script setup>
import { ref, watch, computed, nextTick, onMounted } from 'vue';

const props = defineProps({
  show: Boolean,
  total: Number,
  translations: Object,
  defaultCurrency: String
});

function getTodayDate() {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

const form = ref({
  date: getTodayDate(),
  printInvoice: false,
  amountDollar: props.total,
  discount_amount: 0,
  discount_rate: 0,
  notes: '',
  paymentMethod: 'cash'
});

const amountInput = ref(null);

// Computed properties
const discountAmount = computed(() => {
  if (form.value.discount_rate > 0) {
    return (props.total * form.value.discount_rate) / 100;
  }
  return form.value.discount_amount || 0;
});

const finalTotal = computed(() => {
  return Math.max(0, props.total - discountAmount.value);
});

const changeAmount = computed(() => {
  return Math.max(0, form.value.amountDollar - finalTotal.value);
});

// Watchers
watch(
  () => props.total,
  (newVal) => {
    form.value.amountDollar = finalTotal.value;
  }
);

watch(
  () => form.value.amountDollar,
  (newVal) => {
    if (newVal < 0) {
      form.value.amountDollar = 0;
    }
  }
);

watch(
  () => form.value.discount_rate,
  (newVal) => {
    if (newVal > 0) {
      form.value.discount_amount = 0;
    }
    form.value.amountDollar = finalTotal.value;
  }
);

watch(
  () => form.value.discount_amount,
  (newVal) => {
    if (newVal > 0) {
      form.value.discount_rate = 0;
    }
    form.value.amountDollar = finalTotal.value;
  }
);

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      nextTick(() => {
        amountInput.value?.focus();
        amountInput.value?.select();
      });
    }
  }
);

const resetForm = () => {
  form.value = {
    date: getTodayDate(),
    printInvoice: false,
    amountDollar: finalTotal.value,
    discount_amount: 0,
    discount_rate: 0,
    notes: '',
    paymentMethod: 'cash'
  };
};

const setExactAmount = () => {
  form.value.amountDollar = finalTotal.value;
};

const addQuickAmount = (amount) => {
  form.value.amountDollar = finalTotal.value + amount;
};

// Keyboard shortcuts for modal
const handleKeydown = (event) => {
  if (!props.show) return;
  
  // Enter - Save with print
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault();
    form.value.printInvoice = true;
    emit('confirm', form.value);
    resetForm();
  }
  
  // Shift+Enter - Save without print
  if (event.key === 'Enter' && event.shiftKey) {
    event.preventDefault();
    form.value.printInvoice = false;
    emit('confirm', form.value);
    resetForm();
  }
  
  // Escape - Close modal
  if (event.key === 'Escape') {
    event.preventDefault();
    emit('close');
  }
};

const emit = defineEmits(['close', 'confirm']);

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
});
</script>
  
  <template>
    <Transition name="modal">
      <div v-if="show" class="modal-mask">
        <div class="modal-wrapper">
          <div class="pos-payment-modal">
            <!-- Modal Header -->
            <div class="payment-header">
              <h4 class="payment-title">
                <i class="bi bi-credit-card"></i>
                إتمام عملية الدفع
              </h4>
              <button @click="$emit('close')" class="btn-close-modal">
                <i class="bi bi-x"></i>
              </button>
            </div>

            <!-- Payment Summary -->
            <div class="payment-summary">
              <div class="summary-card">
                <div class="summary-grid">
                  <div class="summary-item">
                    <div class="summary-label">المبلغ الأصلي</div>
                    <div class="summary-value amount">{{ total.toFixed(2) }} {{ defaultCurrency }}</div>
                  </div>
                  <div class="summary-item" v-if="discountAmount > 0">
                    <div class="summary-label">الخصم</div>
                    <div class="summary-value discount">-{{ discountAmount.toFixed(2) }} {{ defaultCurrency }}</div>
                  </div>
                  <div class="summary-item total-item">
                    <div class="summary-label">المبلغ النهائي</div>
                    <div class="summary-value final-total">{{ finalTotal.toFixed(2) }} {{ defaultCurrency }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Payment Form -->
            <div class="payment-form">
              <div class="form-layout">
                <!-- Left Column -->
                <div class="form-column">
                  <!-- Payment Method - Cash Only -->
                  <div class="form-group">
                    <label class="form-label">طريقة الدفع</label>
                    <div class="payment-method-cash">
                      <div class="cash-indicator">
                        <i class="bi bi-cash"></i>
                        <span>نقدي</span>
                      </div>
                    </div>
                </div>
                
                  <!-- Discount Section -->
                  <div class="form-group">
                    <label class="form-label">خصم مبلغ</label>
                  <input
                    type="number"
                    class="form-control"
                      v-model.number="form.discount_amount"
                      min="0"
                      :max="total"
                      step="0.01"
                      placeholder="0.00"
                  />
                </div>

                  <div class="form-group">
                    <label class="form-label">خصم نسبة %</label>
                  <input
                     type="number"
                    class="form-control"
                      v-model.number="form.discount_rate"
                      min="0"
                      max="100"
                      step="0.1"
                      placeholder="0"
                  />
                </div>

                <!-- Notes -->
                  <div class="form-group">
                    <label class="form-label">{{ translations.note }}</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="form.notes"
                      placeholder="ملاحظات إضافية..."
                  />
                </div>

                <!-- Date -->
                  <div class="form-group">
                    <label class="form-label">{{ translations.date }}</label>
                  <input
                    type="date"
                    class="form-control"
                    v-model="form.date"
                  />
              </div>
            </div>

                <!-- Right Column -->
                <div class="form-column">
                  <!-- Amount Paid -->
                  <div class="form-group">
                    <label class="form-label">المبلغ المدفوع</label>
                    <div class="amount-input-group">
                      <input
                        ref="amountInput"
                        type="number"
                        class="form-control amount-input"
                        v-model.number="form.amountDollar"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                      />
                      <button @click="setExactAmount" class="btn btn-outline-primary exact-btn">
                        المبلغ الصحيح
                  </button>
                </div>

                    <!-- Quick Amount Buttons -->
                    <div class="quick-amounts">
                  <button
                        v-for="amount in [5, 10, 20, 50, 100, 200]" 
                        :key="amount"
                        @click="addQuickAmount(amount)"
                        class="btn btn-sm btn-outline-secondary quick-amount-btn"
                      >
                        +{{ amount }}
                  </button>
                    </div>
                </div>

                  <!-- Change Amount -->
                  <div class="form-group" v-if="changeAmount > 0">
                    <div class="change-display">
                      <i class="bi bi-arrow-return-left"></i>
                      <span>المبلغ المرتجع: </span>
                      <span class="change-amount">{{ changeAmount.toFixed(2) }} {{ defaultCurrency }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="payment-actions">
              <button
                @click="$emit('close')"
                class="btn btn-secondary cancel-btn"
              >
                <i class="bi bi-x-circle"></i>
                {{ translations.cancel }}
              </button>

              <button
                @click="form.printInvoice=false; $emit('confirm', form); resetForm();"
                class="btn btn-success save-btn"
              >
                <i class="bi bi-check-circle"></i>
                {{ translations.save_without_invoice }}
              </button>

              <button
                @click="form.printInvoice=true; $emit('confirm', form); resetForm();"
                class="btn btn-primary print-btn"
              >
                <i class="bi bi-printer"></i>
                {{ translations.save_with_invoice }}
              </button>
            </div>

            <!-- Keyboard Shortcuts Info -->
            <div class="keyboard-info">
              <small class="text-muted">
                <kbd>Enter</kbd> حفظ وطباعة • 
                <kbd>Shift+Enter</kbd> حفظ بدون طباعة • 
                <kbd>Esc</kbd> إلغاء
              </small>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </template>
  
<style scoped>
/* Modal Styles */
.modal-mask {
  position: fixed;
  z-index: 9998;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(5px);
}

.modal-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  padding: 2rem;
}

.pos-payment-modal {
  background: white;
  border-radius: 12px;
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
  width: 100%;
  max-width: 60vw;
  max-height: 80vh;
  overflow-y: auto;
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* Header */
.payment-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.75rem 1rem;
  border-radius: 12px 12px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.payment-title {
  margin: 0;
  font-weight: 600;
  font-size: 1rem;
}

.payment-title i {
  margin-right: 0.5rem;
}

.btn-close-modal {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-close-modal:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: scale(1.1);
}

/* Payment Summary */
.payment-summary {
  padding: 0.75rem 1rem;
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.summary-card {
  background: white;
  border-radius: 8px;
  padding: 0.75rem;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 0.75rem;
}

.summary-item {
  text-align: center;
  padding: 0.5rem;
  border-radius: 6px;
  background: #f8f9fa;
  border: 1px solid #e9ecef;
}

.summary-item.total-item {
  background: linear-gradient(135deg, #28a745, #20c997);
  color: white;
  border: none;
  grid-column: 1 / -1;
}

.summary-label {
  font-size: 0.75rem;
  color: #6c757d;
  margin-bottom: 0.25rem;
  font-weight: 500;
}

.summary-item.total-item .summary-label {
  color: rgba(255, 255, 255, 0.9);
}

.summary-value {
  font-size: 0.9rem;
  font-weight: bold;
}

.amount {
  color: #6c757d;
}

.discount {
  color: #dc3545;
}

.final-total {
  color: white;
  font-size: 1rem;
}

/* Payment Form */
.payment-form {
  padding: 0.75rem 1rem;
}

.form-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  align-items: start;
}

.form-column {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-label {
  font-weight: 600;
  margin-bottom: 0.2rem;
  font-size: 0.8rem;
}

.form-control {
  border: 1px solid #e9ecef;
  border-radius: 6px;
  padding: 0.4rem 0.6rem;
  font-size: 0.85rem;
  transition: all 0.3s ease;
  color: #212529 !important;
  background-color: #fff;
}

.form-control:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
  outline: none;
  color: #212529;
  background-color: #fff;
}

/* Payment Method - Cash Only */
.payment-method-cash {
  margin-top: 0.2rem;
}

.cash-indicator {
  background: linear-gradient(135deg, #28a745, #20c997);
  color: white;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  font-weight: 600;
  font-size: 0.8rem;
  box-shadow: 0 1px 4px rgba(40, 167, 69, 0.3);
}

.cash-indicator i {
  font-size: 1rem;
}

/* Amount Input */
.amount-input-group {
  display: flex;
  gap: 0.75rem;
  align-items: stretch;
}

.amount-input {
  flex: 1;
  font-size: 1.2rem;
  font-weight: 600;
  text-align: center;
  color: #212529;
  background-color: #fff;
}

.amount-input:focus {
  color: #212529;
  background-color: #fff;
}

.exact-btn {
  border-radius: 10px;
  padding: 0.75rem 1rem;
  white-space: nowrap;
  font-size: 0.9rem;
}

.quick-amounts {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.2rem;
  margin-top: 0.4rem;
}

.quick-amount-btn {
  border-radius: 12px;
  padding: 0.2rem 0.4rem;
  font-weight: 600;
  font-size: 0.75rem;
  transition: all 0.3s ease;
}

.quick-amount-btn:hover {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

/* Change Display */
.change-display {
  background: linear-gradient(135deg, #28a745, #20c997);
  color: white;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-weight: 600;
  font-size: 0.8rem;
  margin-top: 0.2rem;
  animation: changeGlow 2s ease-in-out infinite alternate;
}

@keyframes changeGlow {
  from {
    box-shadow: 0 1px 4px rgba(40, 167, 69, 0.3);
  }
  to {
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.5);
  }
}

.change-amount {
  font-size: 0.9rem;
  font-weight: bold;
}

/* Action Buttons */
.payment-actions {
  padding: 0.75rem 1rem 1rem;
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 0.5rem;
  border-top: 1px solid #e9ecef;
}

.payment-actions .btn {
  padding: 0.6rem 0.8rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.2rem;
  transition: all 0.3s ease;
  border: 1px solid transparent;
}

.cancel-btn {
  background: #6c757d;
  color: white;
}

.cancel-btn:hover {
  background: #5a6268;
  transform: translateY(-2px);
}

.save-btn {
  background: #28a745;
  color: white;
}

.save-btn:hover {
  background: #218838;
  transform: translateY(-2px);
}

.print-btn {
  background: #007bff;
  color: white;
}

.print-btn:hover {
  background: #0056b3;
  transform: translateY(-2px);
}

/* Keyboard Info */
.keyboard-info {
  padding: 0.5rem 1rem;
  text-align: center;
  background: #f8f9fa;
  border-radius: 0 0 12px 12px;
}

.keyboard-info kbd {
  background: #495057;
  color: white;
  padding: 0.15rem 0.3rem;
  border-radius: 2px;
  font-size: 0.65rem;
  margin: 0 0.05rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .pos-payment-modal {
    max-width: 75vw;
  }
  
  .form-layout {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }
}

@media (max-width: 768px) {
  .modal-wrapper {
    padding: 0.5rem;
  }
  
  .pos-payment-modal {
    max-width: 95vw;
    border-radius: 10px;
  }
  
  .payment-header {
    padding: 0.5rem 0.75rem;
  }
  
  .payment-title {
    font-size: 0.9rem;
  }
  
  .payment-summary,
  .payment-form {
    padding: 0.75rem;
  }
  
  .form-layout {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }
  
  .payment-actions {
    grid-template-columns: 1fr;
    padding: 0.75rem;
  }
  
  .amount-input-group {
    flex-direction: column;
  }
  
  .quick-amounts {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .payment-header {
    padding: 0.4rem 0.6rem;
  }
  
  .payment-summary,
  .payment-form {
    padding: 0.5rem;
  }
  
  .summary-card {
    padding: 0.5rem;
  }
  
  .payment-actions {
    padding: 0.5rem;
  }
  
  .quick-amounts {
    grid-template-columns: 1fr;
  }
  
  .summary-grid {
    grid-template-columns: 1fr;
  }
  
  .form-layout {
    gap: 0.4rem;
  }
  
  .form-column {
    gap: 0.4rem;
  }
  
  .form-control {
    padding: 0.3rem 0.5rem;
    font-size: 0.8rem;
  }
}

/* Modal Transitions */
.modal-enter-active, .modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

.modal-enter-from .pos-payment-modal,
.modal-leave-to .pos-payment-modal {
  transform: scale(0.9) translateY(-20px);
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .pos-payment-modal {
    background: #2c3e50;
    color: white;
  }
  
  .payment-summary {
    background: #34495e;
  }
  
  .summary-card {
    background: #3c4f66;
    color: white;
  }
  
  .form-control {
    background: #34495e;
    border-color: #495057;
    color: white;
  }
  
  .form-control:focus {
    background: #3c4f66;
  }
  
  .payment-method-btn {
    background: #34495e;
    border-color: #495057;
    color: white;
  }
  
  .payment-method-btn:hover {
    background: #3c4f66;
  }
  
  .keyboard-info {
    background: #34495e;
  }
}
</style>