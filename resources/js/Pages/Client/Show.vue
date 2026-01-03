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
            <div class="col-md-3">
              <strong>الرصيد بالدولار:</strong> 
              <span class="badge bg-info fs-6">{{ formatCurrency(customer.balance.balance_dollar || 0, 'dollar') }}</span>
            </div>
            <div class="col-md-3">
              <strong>الرصيد بالدينار:</strong> 
              <span class="badge bg-info fs-6">{{ formatCurrency(customer.balance.balance_dinar || 0, 'dinar') }}</span>
            </div>
            <div class="col-md-3">
              <button class="btn btn-sm btn-primary" @click="verifyBalance(true)" :disabled="verifyingBalance">
                <span v-if="verifyingBalance" class="spinner-border spinner-border-sm me-2"></span>
                <i class="bi bi-check-circle"></i> التحقق من الرصيد
              </button>
            </div>
            <div class="col-md-3">
              <button class="btn btn-sm btn-success me-2" @click="showAddBalanceModal">
                <i class="bi bi-plus-circle"></i> إضافة رصيد
              </button>
              <button 
                class="btn btn-sm btn-warning" 
                @click="showWithdrawBalanceModal"
                :disabled="(customer.balance.balance_dollar || 0) <= 0 && (customer.balance.balance_dinar || 0) <= 0"
              >
                <i class="bi bi-dash-circle"></i> سحب رصيد
              </button>
            </div>
          </div>
          
          <!-- Balance Verification Alert -->
          <div v-if="balanceVerification" class="alert mt-3" :class="balanceVerification.is_balanced ? 'alert-success' : 'alert-warning'">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <strong v-if="balanceVerification.is_balanced">✓ الرصيد متطابق</strong>
                <strong v-else>⚠ يوجد اختلاف في الرصيد</strong>
                <div class="mt-2" v-if="!balanceVerification.is_balanced">
                  <small>
                    <strong>المحفوظ:</strong> 
                    {{ formatCurrency(balanceVerification.stored.dinar) }} / 
                    {{ formatCurrency(balanceVerification.stored.dollar, 'dollar') }}
                    <br>
                    <strong>المحسوب:</strong> 
                    {{ formatCurrency(balanceVerification.calculated.dinar) }} / 
                    {{ formatCurrency(balanceVerification.calculated.dollar, 'dollar') }}
                    <br>
                    <strong>الفرق:</strong> 
                    <span class="text-danger">
                      {{ formatCurrency(balanceVerification.difference.dinar) }} / 
                      {{ formatCurrency(balanceVerification.difference.dollar, 'dollar') }}
                    </span>
                  </small>
                </div>
              </div>
              <button 
                v-if="!balanceVerification.is_balanced"
                class="btn btn-sm btn-warning"
                @click="fixBalance"
                :disabled="fixingBalance"
              >
                <span v-if="fixingBalance" class="spinner-border spinner-border-sm me-2"></span>
                تصحيح الرصيد
              </button>
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

      <!-- Tabs for Invoices and Transactions -->
      <div class="card">
        <div class="card-header">
          <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button 
                class="nav-link" 
                :class="{ active: activeTab === 'invoices' }"
                @click="activeTab = 'invoices'"
                type="button"
              >
                <i class="bi bi-receipt"></i> الفواتير
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button 
                class="nav-link" 
                :class="{ active: activeTab === 'transactions' }"
                @click="activeTab = 'transactions'"
                type="button"
              >
                <i class="bi bi-arrow-left-right"></i> الحركات المالية
              </button>
            </li>
          </ul>
        </div>
        <div class="card-body">
          <!-- Invoices Tab -->
          <div v-show="activeTab === 'invoices'" class="tab-content">
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
          
          <!-- Transactions Tab -->
          <div v-show="activeTab === 'transactions'" class="tab-content">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>التاريخ</th>
                    <th>نوع الحركة</th>
                    <th>المبلغ</th>
                    <th>العملة</th>
                    <th>الوصف</th>
                    <th>الملاحظات</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(transaction, index) in transactions" :key="transaction.id">
                    <td>{{ index + 1 }}</td>
                    <td>{{ formatDate(transaction.date) }}</td>
                    <td>
                      <span :class="getTransactionTypeBadgeClass(transaction.type)">
                        {{ getTransactionTypeText(transaction.type) }}
                      </span>
                    </td>
                    <td 
                      :class="transaction.type === 'deposit' ? 'text-success' : transaction.type === 'withdrawal' ? 'text-danger' : 'text-info'"
                    >
                      <strong>
                        {{ transaction.type === 'deposit' ? '+' : transaction.type === 'withdrawal' ? '-' : '' }}
                        {{ formatCurrency(transaction.amount, transaction.currency === 'USD' || transaction.currency === '$' ? 'dollar' : 'dinar') }}
                      </strong>
                    </td>
                    <td>{{ transaction.currency === 'USD' || transaction.currency === '$' ? 'دولار' : 'دينار' }}</td>
                    <td>{{ transaction.description || transaction.name || '-' }}</td>
                    <td>{{ transaction.details?.notes || '-' }}</td>
                  </tr>
                  <tr v-if="transactions.length === 0">
                    <td colspan="7" class="text-center text-muted">لا توجد حركات مالية</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Payment Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
          <div class="modal-container">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">دفع الفاتورة {{ selectedInvoice?.order_number }}</h5>
                <button type="button" class="btn-close" @click="closeModal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
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
                      ref="amountInput"
                    />
                  </div>
                  <div class="mb-3">
                    <label class="form-label">طريقة الدفع <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="paymentForm.payment_method" required>
                      <option value="cash">نقدي</option>
                      <option value="balance" :disabled="!canPayFromBalance">
                        من الرصيد 
                        <span v-if="customer.balance">
                          (المتاح: {{ formatCurrency(customer.balance.balance_dinar || 0) }})
                        </span>
                      </option>
                      <option value="transfer">تحويل</option>
                    </select>
                    <small v-if="paymentForm.payment_method === 'balance' && !canPayFromBalance" class="text-danger">
                      الرصيد غير كافي (المتاح: {{ formatCurrency(customer.balance?.balance_dinar || 0) }})
                    </small>
                    <small v-if="paymentForm.payment_method === 'balance' && canPayFromBalance" class="text-success">
                      ✓ الرصيد كافي للدفع
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
        </div>
      </Transition>
    </Teleport>

    <!-- Add Balance Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showAddBalanceModalFlag" class="modal-overlay" @click.self="closeAddBalanceModal">
          <div class="modal-container">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">إضافة رصيد للعميل</h5>
                <button type="button" class="btn-close" @click="closeAddBalanceModal">
                  <span>&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                  <input 
                    type="number" 
                    class="form-control" 
                    v-model="balanceForm.amount"
                    step="0.01"
                    min="0.01"
                    required
                  />
                </div>
                <div class="mb-3">
                  <label class="form-label">العملة <span class="text-danger">*</span></label>
                  <select class="form-select" v-model="balanceForm.currency" required>
                    <option value="dollar">دولار</option>
                    <option value="dinar">دينار</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">ملاحظات</label>
                  <textarea class="form-control" v-model="balanceForm.notes" rows="3"></textarea>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="closeAddBalanceModal">إلغاء</button>
                <button type="button" class="btn btn-success" @click="addBalance" :disabled="isLoading">
                  <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                  إضافة
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Withdraw Balance Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showWithdrawBalanceModalFlag" class="modal-overlay" @click.self="closeWithdrawBalanceModal">
          <div class="modal-container">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">سحب رصيد من العميل</h5>
                <button type="button" class="btn-close" @click="closeWithdrawBalanceModal">
                  <span>&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="alert alert-info">
                  <strong>الرصيد المتاح:</strong><br>
                  دولار: {{ formatCurrency(customer.balance?.balance_dollar || 0, 'dollar') }}<br>
                  دينار: {{ formatCurrency(customer.balance?.balance_dinar || 0, 'dinar') }}
                </div>
                <div class="mb-3">
                  <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                  <input 
                    type="number" 
                    class="form-control" 
                    v-model="withdrawForm.amount"
                    step="0.01"
                    min="0.01"
                    required
                  />
                </div>
                <div class="mb-3">
                  <label class="form-label">العملة <span class="text-danger">*</span></label>
                  <select class="form-select" v-model="withdrawForm.currency" required>
                    <option value="dollar">دولار</option>
                    <option value="dinar">دينار</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">ملاحظات</label>
                  <textarea class="form-control" v-model="withdrawForm.notes" rows="3"></textarea>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="closeWithdrawBalanceModal">إلغاء</button>
                <button type="button" class="btn btn-warning" @click="withdrawBalance" :disabled="isLoading">
                  <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                  سحب
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted } from 'vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
  customer: Object,
  invoices: Array,
  transactions: Array,
  statistics: Object,
  translations: Object,
});

const transactions = ref(props.transactions || []);

const page = usePage();
const showModal = ref(false);
const selectedInvoice = ref(null);
const processing = ref(false);
const amountInput = ref(null);
const showAddBalanceModalFlag = ref(false);
const showWithdrawBalanceModalFlag = ref(false);
const isLoading = ref(false);
const verifyingBalance = ref(false);
const fixingBalance = ref(false);
const balanceVerification = ref(null);
const activeTab = ref('invoices');

const paymentForm = ref({
  amount: '',
  payment_method: 'cash',
  notes: '',
});

const balanceForm = ref({
  amount: '',
  currency: 'dinar',
  notes: '',
});

const withdrawForm = ref({
  amount: '',
  currency: 'dinar',
  notes: '',
});

const canPayFromBalance = computed(() => {
  if (!props.customer.balance) return false;
  if (paymentForm.value.payment_method !== 'balance') return true;
  const balance = props.customer.balance.balance_dinar || 0;
  return balance >= parseFloat(paymentForm.value.amount || 0);
});

const formatCurrency = (amount, currency = 'dinar') => {
  if (amount === null || amount === undefined || amount === '') return '0';
  
  const num = parseFloat(amount);
  if (isNaN(num)) return '0';
  
  // Format with commas and remove .00 if it's a whole number
  const formatted = num.toLocaleString('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  });
  
  // Remove .00 if it's a whole number
  const cleaned = formatted.replace(/\.00$/, '');
  
  const currencySymbol = currency === 'dollar' ? '$' : 'د.ع';
  return `${cleaned} ${currencySymbol}`;
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

const getTransactionTypeText = (type) => {
  const typeMap = {
    'deposit': 'إيداع',
    'withdrawal': 'سحب',
    'payment': 'دفع',
    'in': 'داخل',
    'out': 'خارج',
  };
  return typeMap[type] || type;
};

const getTransactionTypeBadgeClass = (type) => {
  const classMap = {
    'deposit': 'badge bg-success',
    'withdrawal': 'badge bg-danger',
    'payment': 'badge bg-primary',
    'in': 'badge bg-success',
    'out': 'badge bg-danger',
  };
  return classMap[type] || 'badge bg-secondary';
};

const showPaymentModal = async (invoice) => {
  selectedInvoice.value = invoice;
  paymentForm.value = {
    amount: invoice.remaining.toFixed(2),
    payment_method: 'cash',
    notes: '',
  };
  showModal.value = true;
  // Focus on amount input after modal is shown
  await nextTick();
  if (amountInput.value) {
    amountInput.value.focus();
    amountInput.value.select();
  }
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

const verifyBalance = async (showAlert = false) => {
  verifyingBalance.value = true;
  
  try {
    const response = await axios.get(route('customers.verify-balance', props.customer.id));
    
    if (response.data.success) {
      balanceVerification.value = response.data.balance;
    }
  } catch (error) {
    console.error('Error verifying balance:', error);
  } finally {
    verifyingBalance.value = false;
  }
};

const fixBalance = async () => {
  fixingBalance.value = true;
  
  try {
    const response = await axios.get(route('customers.verify-balance', props.customer.id), {
      params: { fix: true }
    });
    
    if (response.data.success) {
      balanceVerification.value = response.data.balance;
      // تحديث الرصيد في customer object
      if (props.customer.balance) {
        props.customer.balance.balance_dollar = response.data.balance.calculated.dollar;
        props.customer.balance.balance_dinar = response.data.balance.calculated.dinar;
      }
      // إعادة تحميل الصفحة لتحديث البيانات
      router.reload({ only: ['customer'] });
    }
  } catch (error) {
    console.error('Error fixing balance:', error);
  } finally {
    fixingBalance.value = false;
  }
};

// التحقق من الرصيد تلقائياً عند تحميل الصفحة (بدون عرض تنبيه)
onMounted(() => {
  verifyBalance(false);
});

const showAddBalanceModal = () => {
  balanceForm.value = {
    amount: '',
    currency: 'dinar',
    notes: '',
  };
  showAddBalanceModalFlag.value = true;
};

const closeAddBalanceModal = () => {
  showAddBalanceModalFlag.value = false;
  balanceForm.value = {
    amount: '',
    currency: 'dinar',
    notes: '',
  };
};

const addBalance = async () => {
  if (!balanceForm.value.amount || parseFloat(balanceForm.value.amount) <= 0) {
    Swal.fire('خطأ', 'يرجى إدخال مبلغ صحيح', 'error');
    return;
  }

  isLoading.value = true;
  
  try {
    const response = await axios.post('/decoration-payments/balance/add', {
      customer_id: props.customer.id,
      amount: balanceForm.value.amount,
      currency: balanceForm.value.currency,
      notes: balanceForm.value.notes,
    });

    if (response.data.success) {
      Swal.fire('نجح', 'تم إضافة الرصيد بنجاح', 'success');
      closeAddBalanceModal();
      router.reload();
    } else {
      Swal.fire('خطأ', response.data.message || 'حدث خطأ أثناء إضافة الرصيد', 'error');
    }
  } catch (error) {
    console.error('Error adding balance:', error);
    Swal.fire('خطأ', error.response?.data?.message || 'حدث خطأ أثناء إضافة الرصيد', 'error');
  } finally {
    isLoading.value = false;
  }
};

const showWithdrawBalanceModal = () => {
  withdrawForm.value = {
    amount: '',
    currency: 'dinar',
    notes: '',
  };
  showWithdrawBalanceModalFlag.value = true;
};

const closeWithdrawBalanceModal = () => {
  showWithdrawBalanceModalFlag.value = false;
  withdrawForm.value = {
    amount: '',
    currency: 'dinar',
    notes: '',
  };
};

const withdrawBalance = async () => {
  if (!withdrawForm.value.amount || parseFloat(withdrawForm.value.amount) <= 0) {
    Swal.fire('خطأ', 'يرجى إدخال مبلغ صحيح', 'error');
    return;
  }

  const balance = withdrawForm.value.currency === 'dollar' 
    ? (props.customer.balance?.balance_dollar || 0)
    : (props.customer.balance?.balance_dinar || 0);

  if (parseFloat(withdrawForm.value.amount) > balance) {
    Swal.fire('خطأ', 'الرصيد غير كافي', 'error');
    return;
  }

  isLoading.value = true;
  
  try {
    const response = await axios.post('/decoration-payments/balance/withdraw', {
      customer_id: props.customer.id,
      amount: withdrawForm.value.amount,
      currency: withdrawForm.value.currency,
      notes: withdrawForm.value.notes,
    });

    if (response.data.success) {
      Swal.fire('نجح', 'تم سحب الرصيد بنجاح', 'success');
      closeWithdrawBalanceModal();
      router.reload();
    } else {
      Swal.fire('خطأ', response.data.message || 'حدث خطأ أثناء سحب الرصيد', 'error');
    }
  } catch (error) {
    console.error('Error withdrawing balance:', error);
    Swal.fire('خطأ', error.response?.data?.message || 'حدث خطأ أثناء سحب الرصيد', 'error');
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.modal-container {
  position: relative;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-content {
  background: white;
  border-radius: 10px;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.5);
  position: relative;
  display: flex;
  flex-direction: column;
  pointer-events: auto;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  border-bottom: 1px solid #dee2e6;
  border-top-left-radius: 10px;
  border-top-right-radius: 10px;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 500;
}

.modal-body {
  padding: 1rem;
  flex: 1 1 auto;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 1rem;
  border-top: 1px solid #dee2e6;
  border-bottom-left-radius: 10px;
  border-bottom-right-radius: 10px;
  gap: 0.5rem;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1;
  color: #000;
  text-shadow: 0 1px 0 #fff;
  opacity: 0.5;
  cursor: pointer;
  padding: 0;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-close:hover {
  opacity: 0.75;
}

/* Modal transition */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
  transition: transform 0.3s ease;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
  transform: scale(0.9);
}
</style>

