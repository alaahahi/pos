<template>
  <AuthenticatedLayout :translations="translations">
    <!-- breadcrumb-->
    <div class="pagetitle dark:text-white">
      <h1 class="dark:text-white">إدارة دفعات الديكورات</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link dark:text-white" :href="route('decorations.dashboard')">
              الديكورات
            </Link>
          </li>
          <li class="breadcrumb-item active">إدارة الدفعات</li>
        </ol>
      </nav>
    </div>
    <!-- End breadcrumb-->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <!-- Tabs -->
          <div class="card">
            <div class="card-body">
              <ul class="nav nav-tabs" id="paymentsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                  <button 
                    class="nav-link" 
                    :class="{ active: activeTab === 'orders' }"
                    @click="activeTab = 'orders'"
                    type="button"
                  >
                    طلبات الديكور
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button 
                    class="nav-link" 
                    :class="{ active: activeTab === 'payments' }"
                    @click="activeTab = 'payments'"
                    type="button"
                  >
                    سجل الدفعات
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button 
                    class="nav-link" 
                    :class="{ active: activeTab === 'balance' }"
                    @click="activeTab = 'balance'"
                    type="button"
                  >
                    إدارة الرصيد
                  </button>
                </li>
              </ul>

              <div class="tab-content mt-4">
                <!-- Orders Tab -->
                <div v-if="activeTab === 'orders'" class="tab-pane fade show active">
                  <div class="row mb-3">
                    <div class="col-md-4">
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="searchForm.search" 
                        placeholder="البحث في الطلبات..."
                      >
                    </div>
                    <div class="col-md-3">
                      <select class="form-select" v-model="searchForm.status">
                        <option value="">جميع الحالات</option>
                        <option value="created">تم الإنشاء</option>
                        <option value="received">تم الاستلام</option>
                        <option value="executing">قيد التنفيذ</option>
                        <option value="partial_payment">دفع جزئي</option>
                        <option value="full_payment">دفع كامل</option>
                        <option value="completed">مكتمل</option>
                        <option value="cancelled">ملغي</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <select class="form-select" v-model="searchForm.currency">
                        <option value="">جميع العملات</option>
                        <option value="dollar">دولار</option>
                        <option value="dinar">دينار</option>
                      </select>
                    </div>
                    <div class="col-md-2">
                      <button class="btn btn-primary w-100" @click="searchOrders">
                        <i class="bi bi-search"></i> بحث
                      </button>
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th>رقم الطلب</th>
                          <th>العميل</th>
                          <th>الديكور</th>
                          <th>المبلغ الإجمالي</th>
                          <th>المدفوع</th>
                          <th>المتبقي</th>
                          <th>الحالة</th>
                          <th>الإجراءات</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="order in orders.data" :key="order.id">
                          <td>#{{ order.id }}</td>
                          <td>{{ order.customer_name }}</td>
                          <td>{{ order.decoration.name }}</td>
                          <td>{{ formatCurrency(order.total_price, order.currency) }}</td>
                          <td>{{ formatCurrency(order.paid_amount, order.currency) }}</td>
                          <td>{{ formatCurrency(order.total_price - order.paid_amount, order.currency) }}</td>
                          <td>
                            <span class="badge" :class="'bg-' + getStatusColor(order.status)">
                              {{ order.status_name }}
                            </span>
                          </td>
                          <td>
                            <button 
                              class="btn btn-sm btn-success"
                              @click="showPaymentModal(order)"
                              :disabled="order.status === 'completed' || order.status === 'cancelled'"
                            >
                              <i class="bi bi-plus"></i> إضافة دفعة
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- Pagination -->
                  <div class="d-flex justify-content-center mt-3">
                    <nav>
                      <ul class="pagination">
                        <li v-for="link in orders.links" :key="link.label" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                          <button 
                            class="page-link" 
                            @click="loadPage(link.url)"
                            v-html="link.label"
                            :disabled="!link.url"
                          ></button>
                        </li>
                      </ul>
                    </nav>
                  </div>
                </div>

                <!-- Payments Tab -->
                <div v-if="activeTab === 'payments'" class="tab-pane fade show active">
                  <div class="table-responsive">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th>رقم الدفعة</th>
                          <th>الوصف</th>
                          <th>المبلغ</th>
                          <th>العملة</th>
                          <th>طريقة الدفع</th>
                          <th>التاريخ</th>
                          <th>الملاحظات</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="payment in payments.data" :key="payment.id">
                          <td>#{{ payment.id }}</td>
                          <td>{{ payment.description }}</td>
                          <td>{{ formatCurrency(payment.amount, payment.currency) }}</td>
                          <td>{{ payment.currency === 'dollar' ? 'دولار' : 'دينار' }}</td>
                          <td>{{ getPaymentMethodName(payment.details?.payment_method) }}</td>
                          <td>{{ formatDate(payment.created) }}</td>
                          <td>{{ payment.details?.notes || '-' }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- Pagination -->
                  <div class="d-flex justify-content-center mt-3">
                    <nav>
                      <ul class="pagination">
                        <li v-for="link in payments.links" :key="link.label" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                          <button 
                            class="page-link" 
                            @click="loadPage(link.url)"
                            v-html="link.label"
                            :disabled="!link.url"
                          ></button>
                        </li>
                      </ul>
                    </nav>
                  </div>
                </div>

                <!-- Balance Tab -->
                <div v-if="activeTab === 'balance'" class="tab-pane fade show active">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="card">
                        <div class="card-header">
                          <h5 class="card-title mb-0">أرصدة العملاء</h5>
                        </div>
                        <div class="card-body">
                          <div class="table-responsive">
                            <table class="table table-sm">
                              <thead>
                                <tr>
                                  <th>العميل</th>
                                  <th>الرصيد بالدولار</th>
                                  <th>الرصيد بالدينار</th>
                                  <th>الإجراءات</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr v-for="balance in customerBalances" :key="balance.id">
                                  <td>{{ balance.customer.name }}</td>
                                  <td>{{ formatCurrency(balance.balance_dollar, 'dollar') }}</td>
                                  <td>{{ formatCurrency(balance.balance_dinar, 'dinar') }}</td>
                                  <td>
                                    <button 
                                      class="btn btn-sm btn-primary me-1"
                                      @click="showAddBalanceModal(balance.customer)"
                                    >
                                      <i class="bi bi-plus"></i>
                                    </button>
                                    <button 
                                      class="btn btn-sm btn-warning"
                                      @click="showWithdrawBalanceModal(balance.customer)"
                                      :disabled="balance.balance_dollar <= 0 && balance.balance_dinar <= 0"
                                    >
                                      <i class="bi bi-dash"></i>
                                    </button>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-md-6">
                      <div class="card">
                        <div class="card-header">
                          <h5 class="card-title mb-0">إجراءات سريعة</h5>
                        </div>
                        <div class="card-body">
                          <div class="d-grid gap-2">
                            <button class="btn btn-success" @click="showAddBalanceModal()">
                              <i class="bi bi-plus-circle"></i> إضافة رصيد لعميل
                            </button>
                            <button class="btn btn-warning" @click="showWithdrawBalanceModal()">
                              <i class="bi bi-dash-circle"></i> سحب رصيد من عميل
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Payment Modal -->
    <div v-if="showPaymentModalFlag" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">إضافة دفعة لطلب #{{ selectedOrder?.id }}</h5>
            <button type="button" class="btn-close" @click="closePaymentModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="addPayment">
              <div class="mb-3">
                <label for="amount" class="form-label">المبلغ</label>
                <input 
                  type="number" 
                  class="form-control" 
                  id="amount"
                  v-model="paymentForm.amount"
                  step="0.01"
                  min="0.01"
                  required
                >
              </div>
              <div class="mb-3">
                <label for="currency" class="form-label">العملة</label>
                <select class="form-select" id="currency" v-model="paymentForm.currency" required>
                  <option value="dollar">دولار</option>
                  <option value="dinar">دينار</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="payment_method" class="form-label">طريقة الدفع</label>
                <select class="form-select" id="payment_method" v-model="paymentForm.payment_method" required>
                  <option value="cash">نقدي</option>
                  <option value="balance">من الرصيد</option>
                  <option value="transfer">تحويل</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="notes" class="form-label">ملاحظات</label>
                <textarea 
                  class="form-control" 
                  id="notes"
                  v-model="paymentForm.notes"
                  rows="3"
                ></textarea>
              </div>
              <div v-if="paymentForm.payment_method === 'balance' && selectedOrder?.customer" class="alert alert-info">
                <strong>الرصيد المتاح:</strong><br>
                دولار: {{ formatCurrency(customerBalance.balance_dollar, 'dollar') }}<br>
                دينار: {{ formatCurrency(customerBalance.balance_dinar, 'dinar') }}
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closePaymentModal">إلغاء</button>
            <button type="button" class="btn btn-success" @click="addPayment" :disabled="isLoading">
              <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
              إضافة الدفعة
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="showPaymentModalFlag" class="modal-backdrop fade show"></div>

    <!-- Add Balance Modal -->
    <div v-if="showAddBalanceModalFlag" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">إضافة رصيد</h5>
            <button type="button" class="btn-close" @click="closeAddBalanceModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="addBalance">
              <div class="mb-3">
                <label for="customer_id" class="form-label">العميل</label>
                <select class="form-select" id="customer_id" v-model="balanceForm.customer_id" required>
                  <option value="">اختر العميل</option>
                  <option v-for="balance in customerBalances" :key="balance.customer.id" :value="balance.customer.id">
                    {{ balance.customer.name }}
                  </option>
                </select>
              </div>
              <div class="mb-3">
                <label for="amount" class="form-label">المبلغ</label>
                <input 
                  type="number" 
                  class="form-control" 
                  id="amount"
                  v-model="balanceForm.amount"
                  step="0.01"
                  min="0.01"
                  required
                >
              </div>
              <div class="mb-3">
                <label for="currency" class="form-label">العملة</label>
                <select class="form-select" id="currency" v-model="balanceForm.currency" required>
                  <option value="dollar">دولار</option>
                  <option value="dinar">دينار</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="notes" class="form-label">ملاحظات</label>
                <textarea 
                  class="form-control" 
                  id="notes"
                  v-model="balanceForm.notes"
                  rows="3"
                ></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeAddBalanceModal">إلغاء</button>
            <button type="button" class="btn btn-success" @click="addBalance" :disabled="isLoading">
              <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
              إضافة الرصيد
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="showAddBalanceModalFlag" class="modal-backdrop fade show"></div>

    <!-- Withdraw Balance Modal -->
    <div v-if="showWithdrawBalanceModalFlag" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">سحب رصيد</h5>
            <button type="button" class="btn-close" @click="closeWithdrawBalanceModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="withdrawBalance">
              <div class="mb-3">
                <label for="customer_id" class="form-label">العميل</label>
                <select class="form-select" id="customer_id" v-model="withdrawForm.customer_id" required>
                  <option value="">اختر العميل</option>
                  <option v-for="balance in customerBalances" :key="balance.customer.id" :value="balance.customer.id">
                    {{ balance.customer.name }}
                  </option>
                </select>
              </div>
              <div class="mb-3">
                <label for="amount" class="form-label">المبلغ</label>
                <input 
                  type="number" 
                  class="form-control" 
                  id="amount"
                  v-model="withdrawForm.amount"
                  step="0.01"
                  min="0.01"
                  required
                >
              </div>
              <div class="mb-3">
                <label for="currency" class="form-label">العملة</label>
                <select class="form-select" id="currency" v-model="withdrawForm.currency" required>
                  <option value="dollar">دولار</option>
                  <option value="dinar">دينار</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="notes" class="form-label">ملاحظات</label>
                <textarea 
                  class="form-control" 
                  id="notes"
                  v-model="withdrawForm.notes"
                  rows="3"
                ></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeWithdrawBalanceModal">إلغاء</button>
            <button type="button" class="btn btn-warning" @click="withdrawBalance" :disabled="isLoading">
              <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
              سحب الرصيد
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="showWithdrawBalanceModalFlag" class="modal-backdrop fade show"></div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  translations: Object,
  activeTab: String,
  orders: Object,
  customerBalances: Array,
  payments: Object,
});

const activeTab = ref(props.activeTab || 'orders');
const isLoading = ref(false);

// Search form
const searchForm = ref({
  search: '',
  status: '',
  currency: '',
});

// Payment modal
const showPaymentModalFlag = ref(false);
const selectedOrder = ref(null);
const customerBalance = ref({ balance_dollar: 0, balance_dinar: 0 });
const paymentForm = ref({
  order_id: null,
  amount: '',
  currency: 'dollar',
  payment_method: 'cash',
  notes: '',
});

// Add balance modal
const showAddBalanceModalFlag = ref(false);
const balanceForm = ref({
  customer_id: '',
  amount: '',
  currency: 'dollar',
  notes: '',
});

// Withdraw balance modal
const showWithdrawBalanceModalFlag = ref(false);
const withdrawForm = ref({
  customer_id: '',
  amount: '',
  currency: 'dollar',
  notes: '',
});

const formatCurrency = (amount, currency = 'dollar') => {
  if (!amount) return '0.00';
  const symbol = currency === 'dollar' ? '$' : 'د.ع';
  return `${parseFloat(amount).toFixed(2)} ${symbol}`;
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ar-SA');
};

const getStatusColor = (status) => {
  const colors = {
    'created': 'secondary',
    'received': 'info',
    'executing': 'primary',
    'partial_payment': 'warning',
    'full_payment': 'success',
    'completed': 'success',
    'cancelled': 'danger'
  };
  return colors[status] || 'secondary';
};

const getPaymentMethodName = (method) => {
  const methods = {
    'cash': 'نقدي',
    'balance': 'من الرصيد',
    'transfer': 'تحويل'
  };
  return methods[method] || method;
};

const searchOrders = () => {
  router.get(route('decoration.payments'), searchForm.value, {
    preserveState: true,
    preserveScroll: true,
  });
};

const loadPage = (url) => {
  if (url) {
    router.visit(url);
  }
};

const showPaymentModal = async (order) => {
  selectedOrder.value = order;
  paymentForm.value.order_id = order.id;
  paymentForm.value.currency = order.currency;
  
  // Get customer balance
  if (order.customer) {
    try {
      const response = await fetch(route('decoration.payments.balance.get', order.customer.id));
      const data = await response.json();
      customerBalance.value = data;
    } catch (error) {
      console.error('Error fetching customer balance:', error);
    }
  }
  
  showPaymentModalFlag.value = true;
};

const closePaymentModal = () => {
  showPaymentModalFlag.value = false;
  selectedOrder.value = null;
  paymentForm.value = {
    order_id: null,
    amount: '',
    currency: 'dollar',
    payment_method: 'cash',
    notes: '',
  };
};

const addPayment = async () => {
  isLoading.value = true;
  
  try {
    const response = await fetch(route('decoration.payments.add'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify(paymentForm.value),
    });

    const data = await response.json();

    if (data.success) {
      closePaymentModal();
      router.reload();
    } else {
      alert(data.message || 'حدث خطأ أثناء إضافة الدفعة');
    }
  } catch (error) {
    console.error('Error adding payment:', error);
    alert('حدث خطأ أثناء إضافة الدفعة');
  } finally {
    isLoading.value = false;
  }
};

const showAddBalanceModal = (customer = null) => {
  if (customer) {
    balanceForm.value.customer_id = customer.id;
  }
  showAddBalanceModalFlag.value = true;
};

const closeAddBalanceModal = () => {
  showAddBalanceModalFlag.value = false;
  balanceForm.value = {
    customer_id: '',
    amount: '',
    currency: 'dollar',
    notes: '',
  };
};

const addBalance = async () => {
  isLoading.value = true;
  
  try {
    const response = await fetch(route('decoration.payments.balance.add'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify(balanceForm.value),
    });

    const data = await response.json();

    if (data.success) {
      closeAddBalanceModal();
      router.reload();
    } else {
      alert(data.message || 'حدث خطأ أثناء إضافة الرصيد');
    }
  } catch (error) {
    console.error('Error adding balance:', error);
    alert('حدث خطأ أثناء إضافة الرصيد');
  } finally {
    isLoading.value = false;
  }
};

const showWithdrawBalanceModal = (customer = null) => {
  if (customer) {
    withdrawForm.value.customer_id = customer.id;
  }
  showWithdrawBalanceModalFlag.value = true;
};

const closeWithdrawBalanceModal = () => {
  showWithdrawBalanceModalFlag.value = false;
  withdrawForm.value = {
    customer_id: '',
    amount: '',
    currency: 'dollar',
    notes: '',
  };
};

const withdrawBalance = async () => {
  isLoading.value = true;
  
  try {
    const response = await fetch(route('decoration.payments.balance.withdraw'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify(withdrawForm.value),
    });

    const data = await response.json();

    if (data.success) {
      closeWithdrawBalanceModal();
      router.reload();
    } else {
      alert(data.message || 'حدث خطأ أثناء سحب الرصيد');
    }
  } catch (error) {
    console.error('Error withdrawing balance:', error);
    alert('حدث خطأ أثناء سحب الرصيد');
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-backdrop {
  background-color: rgba(0, 0, 0, 0.5);
}

.nav-tabs .nav-link {
  color: #6c757d;
  border: 1px solid transparent;
  border-top-left-radius: 0.375rem;
  border-top-right-radius: 0.375rem;
}

.nav-tabs .nav-link:hover {
  border-color: #e9ecef #e9ecef #dee2e6;
  isolation: isolate;
}

.nav-tabs .nav-link.active {
  color: #495057;
  background-color: #fff;
  border-color: #dee2e6 #dee2e6 #fff;
}
</style>
