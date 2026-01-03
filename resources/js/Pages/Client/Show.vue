<template>
  <AuthenticatedLayout :translations="translations">
    <section class="section dashboard">
      <!-- Customer Info Card -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">معلومات العميل</h5>
          <Link :href="route('customers.index')" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-right"></i> رجوع
          </Link>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3">
              <strong>الاسم:</strong> {{ customer.name }}
            </div>
            <div class="col-md-3">
              <strong>الهاتف:</strong> {{ customer.phone || '-' }}
            </div>
            <div class="col-md-3">
              <strong>البريد الإلكتروني:</strong> {{ customer.email || '-' }}
            </div>
            <div class="col-md-3">
              <strong>الحالة:</strong> 
              <span :class="customer.is_active ? 'badge bg-success' : 'badge bg-danger'">
                {{ customer.is_active ? 'نشط' : 'غير نشط' }}
              </span>
            </div>
          </div>
          <div class="row mt-3" v-if="customer.balance">
            <div class="col-md-6">
              <strong>الرصيد بالدولار:</strong> 
              <span class="badge bg-info">{{ formatCurrency(customer.balance.balance_dollar || 0, 'dollar') }}</span>
            </div>
            <div class="col-md-6">
              <strong>الرصيد بالدينار:</strong> 
              <span class="badge bg-info">{{ formatCurrency(customer.balance.balance_dinar || 0, 'dinar') }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistics Card -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="card text-center">
            <div class="card-body">
              <h6 class="text-muted">إجمالي الفواتير</h6>
              <h4 class="text-primary">{{ statistics.total_invoices }}</h4>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center">
            <div class="card-body">
              <h6 class="text-muted">إجمالي المبلغ</h6>
              <h4 class="text-info">{{ formatCurrency(statistics.total_amount) }}</h4>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center">
            <div class="card-body">
              <h6 class="text-muted">إجمالي المدفوع</h6>
              <h4 class="text-success">{{ formatCurrency(statistics.total_paid) }}</h4>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center">
            <div class="card-body">
              <h6 class="text-muted">إجمالي الدين</h6>
              <h4 class="text-danger">{{ formatCurrency(statistics.total_debt) }}</h4>
            </div>
          </div>
        </div>
      </div>

      <!-- Invoices Table -->
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">الفواتير المرتبطة</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>رقم الفاتورة</th>
                  <th>التاريخ</th>
                  <th>المبلغ الإجمالي</th>
                  <th>الخصم</th>
                  <th>المبلغ النهائي</th>
                  <th>المدفوع</th>
                  <th>المتبقي</th>
                  <th>الحالة</th>
                  <th>طريقة الدفع</th>
                  <th>الإجراءات</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="invoice in invoices" :key="invoice.id">
                  <td>{{ invoice.order_number }}</td>
                  <td>{{ formatDate(invoice.date) }}</td>
                  <td>{{ formatCurrency(invoice.total_amount) }}</td>
                  <td>{{ formatCurrency(invoice.discount_amount) }}</td>
                  <td><strong>{{ formatCurrency(invoice.final_amount) }}</strong></td>
                  <td class="text-success">{{ formatCurrency(invoice.total_paid) }}</td>
                  <td class="text-danger" :class="{'fw-bold': invoice.remaining > 0}">
                    {{ formatCurrency(invoice.remaining) }}
                  </td>
                  <td>
                    <span :class="getStatusBadgeClass(invoice.status)">
                      {{ getStatusText(invoice.status) }}
                    </span>
                  </td>
                  <td>{{ getPaymentMethodText(invoice.payment_method) }}</td>
                  <td>
                    <button 
                      v-if="invoice.remaining > 0"
                      class="btn btn-sm btn-primary"
                      @click="showPaymentModal(invoice)"
                    >
                      <i class="bi bi-cash-coin"></i> دفع
                    </button>
                    <Link 
                      :href="route('order.print', invoice.id)"
                      class="btn btn-sm btn-info"
                      target="_blank"
                    >
                      <i class="bi bi-printer"></i> طباعة
                    </Link>
                  </td>
                </tr>
                <tr v-if="invoices.length === 0">
                  <td colspan="10" class="text-center text-muted">لا توجد فواتير</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <!-- Payment Modal -->
    <div class="modal fade" :class="{ show: showModal, 'd-block': showModal }" tabindex="-1" v-if="showModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">دفع الفاتورة {{ selectedInvoice?.order_number }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitPayment">
              <div class="mb-3">
                <label class="form-label">المبلغ المتبقي</label>
                <input 
                  type="text" 
                  class="form-control" 
                  :value="formatCurrency(selectedInvoice?.remaining || 0)" 
                  disabled
                />
              </div>
              <div class="mb-3">
                <label class="form-label">المبلغ المدفوع <span class="text-danger">*</span></label>
                <input 
                  type="number" 
                  class="form-control" 
                  v-model="paymentForm.amount"
                  step="0.01"
                  min="0.01"
                  :max="selectedInvoice?.remaining"
                  required
                />
              </div>
              <div class="mb-3">
                <label class="form-label">طريقة الدفع <span class="text-danger">*</span></label>
                <select class="form-select" v-model="paymentForm.payment_method" required>
                  <option value="cash">نقدي</option>
                  <option value="balance" :disabled="!canPayFromBalance">من الرصيد</option>
                  <option value="transfer">تحويل</option>
                </select>
                <small v-if="paymentForm.payment_method === 'balance' && !canPayFromBalance" class="text-danger">
                  الرصيد غير كافي
                </small>
              </div>
              <div class="mb-3">
                <label class="form-label">ملاحظات</label>
                <textarea class="form-control" v-model="paymentForm.notes" rows="3"></textarea>
              </div>
              <div class="alert alert-info" v-if="selectedInvoice">
                <strong>المبلغ النهائي:</strong> {{ formatCurrency(selectedInvoice.final_amount) }}<br>
                <strong>المدفوع سابقاً:</strong> {{ formatCurrency(selectedInvoice.total_paid) }}<br>
                <strong>المتبقي:</strong> {{ formatCurrency(selectedInvoice.remaining) }}
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModal">إلغاء</button>
            <button type="button" class="btn btn-primary" @click="submitPayment" :disabled="processing">
              <span v-if="processing" class="spinner-border spinner-border-sm me-2"></span>
              دفع
            </button>
          </div>
        </div>
      </div>
      <div class="modal-backdrop fade show" v-if="showModal"></div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
  customer: Object,
  invoices: Array,
  statistics: Object,
  translations: Object,
});

const page = usePage();
const showModal = ref(false);
const selectedInvoice = ref(null);
const processing = ref(false);

const paymentForm = ref({
  amount: '',
  payment_method: 'cash',
  notes: '',
});

const canPayFromBalance = computed(() => {
  if (!props.customer.balance) return false;
  if (paymentForm.value.payment_method !== 'balance') return true;
  const balance = props.customer.balance.balance_dinar || 0;
  return balance >= parseFloat(paymentForm.value.amount || 0);
});

const formatCurrency = (amount, currency = 'dinar') => {
  if (!amount) return '0.00';
  const formatted = parseFloat(amount).toFixed(2);
  const symbol = currency === 'dollar' ? '$' : 'د.ع';
  return `${formatted} ${symbol}`;
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('ar-IQ');
};

const getStatusText = (status) => {
  const statusMap = {
    'paid': 'مدفوعة',
    'due': 'غير مدفوعة',
    'pending': 'قيد الانتظار',
    'completed': 'مكتملة',
    'cancelled': 'ملغاة',
  };
  return statusMap[status] || status;
};

const getStatusBadgeClass = (status) => {
  const classMap = {
    'paid': 'badge bg-success',
    'due': 'badge bg-danger',
    'pending': 'badge bg-warning',
    'completed': 'badge bg-success',
    'cancelled': 'badge bg-secondary',
  };
  return classMap[status] || 'badge bg-secondary';
};

const getPaymentMethodText = (method) => {
  const methodMap = {
    'cash': 'نقدي',
    'balance': 'من الرصيد',
    'transfer': 'تحويل',
  };
  return methodMap[method] || method;
};

const showPaymentModal = (invoice) => {
  selectedInvoice.value = invoice;
  paymentForm.value = {
    amount: invoice.remaining.toFixed(2),
    payment_method: 'cash',
    notes: '',
  };
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  selectedInvoice.value = null;
  paymentForm.value = {
    amount: '',
    payment_method: 'cash',
    notes: '',
  };
};

const submitPayment = () => {
  if (!selectedInvoice.value) return;
  
  if (parseFloat(paymentForm.value.amount) <= 0) {
    Swal.fire('خطأ', 'المبلغ يجب أن يكون أكبر من صفر', 'error');
    return;
  }
  
  if (parseFloat(paymentForm.value.amount) > selectedInvoice.value.remaining) {
    Swal.fire('خطأ', 'المبلغ المدفوع أكبر من المبلغ المتبقي', 'error');
    return;
  }
  
  if (paymentForm.value.payment_method === 'balance' && !canPayFromBalance.value) {
    Swal.fire('خطأ', 'الرصيد غير كافي', 'error');
    return;
  }
  
  processing.value = true;
  
  router.post(
    route('customers.orders.pay', {
      customer: props.customer.id,
      order: selectedInvoice.value.id,
    }),
    paymentForm.value,
    {
      onSuccess: () => {
        Swal.fire('نجح', 'تم دفع الفاتورة بنجاح', 'success');
        closeModal();
      },
      onError: (errors) => {
        const errorMessage = errors.message || errors.amount || 'حدث خطأ أثناء الدفع';
        Swal.fire('خطأ', errorMessage, 'error');
      },
      onFinish: () => {
        processing.value = false;
      },
    }
  );
};

const hasPermission = (permission) => {
  return page.props.auth_permissions?.includes(permission) || false;
};
</script>

<style scoped>
.modal-backdrop {
  opacity: 0.5;
}
</style>

