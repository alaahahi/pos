<template>
  <AuthenticatedLayout :translations="translations">
    <!-- breadcrumb-->
    <div class="pagetitle dark:text-white">
      <h1 class="dark:text-white">المحاسبة الشهرية للديكورات</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link dark:text-white" :href="route('decorations.dashboard')">
              الديكورات
            </Link>
          </li>
          <li class="breadcrumb-item active">المحاسبة الشهرية</li>
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
              <ul class="nav nav-tabs" id="monthlyTabs" role="tablist">
                <li class="nav-item" role="presentation">
                  <button 
                    class="nav-link" 
                    :class="{ active: activeTab === 'current' }"
                    @click="activeTab = 'current'"
                    type="button"
                  >
                    الشهر الحالي
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button 
                    class="nav-link" 
                    :class="{ active: activeTab === 'history' }"
                    @click="activeTab = 'history'"
                    type="button"
                  >
                    السجل الشهري
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button 
                    class="nav-link" 
                    :class="{ active: activeTab === 'reports' }"
                    @click="activeTab = 'reports'"
                    type="button"
                  >
                    التقارير
                  </button>
                </li>
              </ul>

              <div class="tab-content mt-4">
                <!-- Current Month Tab -->
                <div v-if="activeTab === 'current'" class="tab-pane fade show active">
                  <div class="row">
                    <div class="col-md-8">
                      <div class="card">
                        <div class="card-header">
                          <h5 class="card-title mb-0">
                            الشهر الحالي: {{ currentMonth.month_name }} {{ currentMonth.year }}
                            <span class="badge" :class="'bg-' + (currentMonth.status === 'open' ? 'success' : 'secondary')">
                              {{ currentMonth.status_name }}
                            </span>
                          </h5>
                        </div>
                        <div class="card-body">
                          <div class="row">
                            <div class="col-md-6">
                              <div class="info-item">
                                <label>عدد الطلبات:</label>
                                <span class="value">{{ currentMonth.total_orders }}</span>
                              </div>
                              <div class="info-item">
                                <label>إجمالي مبلغ الطلبات:</label>
                                <span class="value">{{ formatCurrency(currentMonth.total_orders_amount) }}</span>
                              </div>
                              <div class="info-item">
                                <label>إجمالي المدفوعات المستلمة:</label>
                                <span class="value">{{ formatCurrency(currentMonth.total_payments_received) }}</span>
                              </div>
                              <div class="info-item">
                                <label>عمولات الموظفين (دولار):</label>
                                <span class="value text-danger">{{ formatCurrency(currentMonth.total_commissions_usd, 'dollar') }}</span>
                              </div>
                              <div class="info-item">
                                <label>عمولات الموظفين (دينار):</label>
                                <span class="value text-danger">{{ formatCurrency(currentMonth.total_commissions_iqd, 'dinar') }}</span>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="info-item">
                                <label>الرصيد بالدولار:</label>
                                <span class="value">{{ formatCurrency(currentMonth.total_balance_dollar, 'dollar') }}</span>
                              </div>
                              <div class="info-item">
                                <label>الرصيد بالدينار:</label>
                                <span class="value">{{ formatCurrency(currentMonth.total_balance_dinar, 'dinar') }}</span>
                              </div>
                              <div class="info-item">
                                <label>صافي الربح:</label>
                                <span class="value text-success">{{ formatCurrency(currentMonth.net_profit_dollar) }}</span>
                              </div>
                            </div>
                          </div>
                          
                          <div class="mt-4">
                            <h6>الرصيد الافتتاحي:</h6>
                            <div class="row">
                              <div class="col-md-6">
                                <div class="info-item">
                                  <label>الدولار:</label>
                                  <span class="value">{{ formatCurrency(currentMonth.opening_balance_dollar, 'dollar') }}</span>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="info-item">
                                  <label>الدينار:</label>
                                  <span class="value">{{ formatCurrency(currentMonth.opening_balance_dinar, 'dinar') }}</span>
                                </div>
                              </div>
                            </div>
                          </div>

                          <div class="mt-4">
                            <h6>عمولات الموظفين للشهر:</h6>
                            <div class="row">
                              <div class="col-md-6">
                                <div class="info-item">
                                  <label>بالدولار:</label>
                                  <span class="value text-danger">{{ formatCurrency(currentMonth.total_commissions_usd, 'dollar') }}</span>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="info-item">
                                  <label>بالدينار:</label>
                                  <span class="value text-danger">{{ formatCurrency(currentMonth.total_commissions_iqd, 'dinar') }}</span>
                                </div>
                              </div>
                            </div>
                          </div>

                          <div v-if="currentMonth.notes" class="mt-3">
                            <h6>ملاحظات:</h6>
                            <p class="text-muted">{{ currentMonth.notes }}</p>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-md-4">
                      <div class="card">
                        <div class="card-header">
                          <h5 class="card-title mb-0">إجراءات الشهر</h5>
                        </div>
                        <div class="card-body">
                          <div v-if="currentMonth.status === 'open'" class="d-grid gap-2">
                            <button 
                              class="btn btn-warning" 
                              @click="showCloseMonthModal = true"
                            >
                              <i class="bi bi-lock"></i> إغلاق الشهر
                            </button>
                            <button 
                              class="btn btn-info" 
                              @click="recalculateData(currentMonth.id)"
                            >
                              <i class="bi bi-arrow-clockwise"></i> إعادة حساب البيانات
                            </button>
                          </div>
                          <div v-else class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            الشهر مغلق - لا يمكن إجراء تعديلات
                          </div>
                          <div class="mt-3">
                            <h6>دفع عمولات الموظفين</h6>
                            <p class="text-muted">يمكن دفع العمولات المستحقة وتصفيتها للشهر القادم.</p>
                            <button 
                              class="btn btn-outline-success w-100"
                              @click="payoutCommissions(currentMonth.year, currentMonth.month)"
                              :disabled="currentMonth.status !== 'open'"
                            >
                              <i class="bi bi-cash-coin"></i> دفع العمولات للشهر
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- History Tab -->
                <div v-if="activeTab === 'history'" class="tab-pane fade show active">
                  <div class="card">
                    <div class="card-header">
                      <h5 class="card-title mb-0">السجل الشهري</h5>
                    </div>
                    <div class="card-body">
                      <div class="table-responsive">
                        <table class="table table-striped">
                          <thead>
                            <tr>
                              <th>الشهر</th>
                              <th>السنة</th>
                              <th>الحالة</th>
                              <th>عدد الطلبات</th>
                              <th>إجمالي المبلغ</th>
                              <th>المدفوعات</th>
                              <th>صافي الربح</th>
                              <th>الإجراءات</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="account in monthlyAccounts.data" :key="account.id">
                              <td>{{ account.month_name }}</td>
                              <td>{{ account.year }}</td>
                              <td>
                                <span class="badge" :class="'bg-' + (account.status === 'open' ? 'success' : 'secondary')">
                                  {{ account.status_name }}
                                </span>
                              </td>
                              <td>{{ account.total_orders }}</td>
                              <td>{{ formatCurrency(account.total_orders_amount) }}</td>
                              <td>{{ formatCurrency(account.total_payments_received) }}</td>
                              <td>{{ formatCurrency(account.net_profit_dollar) }}</td>
                              <td>
                                <div class="btn-group" role="group">
                                  <button 
                                    class="btn btn-sm btn-outline-info"
                                    @click="viewReport(account.year, account.month)"
                                  >
                                    <i class="bi bi-eye"></i>
                                  </button>
                                  <button 
                                    v-if="account.status === 'open'"
                                    class="btn btn-sm btn-outline-warning"
                                    @click="recalculateData(account.id)"
                                  >
                                    <i class="bi bi-arrow-clockwise"></i>
                                  </button>
                                </div>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      
                      <!-- Pagination -->
                      <div class="d-flex justify-content-center mt-3">
                        <nav>
                          <ul class="pagination">
                            <li v-for="link in monthlyAccounts.links" :key="link.label" class="page-item" :class="{ active: link.active, disabled: !link.url }">
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
                  </div>
                </div>

                <!-- Reports Tab -->
                <div v-if="activeTab === 'reports'" class="tab-pane fade show active">
                  <div class="card">
                    <div class="card-header">
                      <h5 class="card-title mb-0">تقارير شهرية</h5>
                    </div>
                    <div class="card-body">
                      <div class="row mb-3">
                        <div class="col-md-4">
                          <select v-model="selectedYear" class="form-select">
                            <option value="">اختر السنة</option>
                            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                          </select>
                        </div>
                        <div class="col-md-4">
                          <select v-model="selectedMonth" class="form-select">
                            <option value="">اختر الشهر</option>
                            <option v-for="(month, index) in months" :key="index" :value="index + 1">{{ month }}</option>
                          </select>
                        </div>
                        <div class="col-md-4">
                          <button 
                            class="btn btn-primary"
                            @click="loadMonthlyReport"
                            :disabled="!selectedYear || !selectedMonth"
                          >
                            <i class="bi bi-search"></i> عرض التقرير
                          </button>
                        </div>
                      </div>

                      <!-- Monthly Report -->
                      <div v-if="monthlyReport" class="mt-4">
                        <div class="card">
                          <div class="card-header">
                            <h5 class="card-title mb-0">
                              تقرير {{ months[selectedMonth - 1] }} {{ selectedYear }}
                            </h5>
                          </div>
                          <div class="card-body">
                            <div class="row">
                              <div class="col-md-6">
                                <h6>ملخص الشهر:</h6>
                                <div class="info-item">
                                  <label>عدد الطلبات:</label>
                                  <span class="value">{{ monthlyReport.monthly_account.total_orders }}</span>
                                </div>
                                <div class="info-item">
                                  <label>إجمالي المبلغ:</label>
                                  <span class="value">{{ formatCurrency(monthlyReport.monthly_account.total_orders_amount) }}</span>
                                </div>
                                <div class="info-item">
                                  <label>المدفوعات المستلمة:</label>
                                  <span class="value">{{ formatCurrency(monthlyReport.monthly_account.total_payments_received) }}</span>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <h6>الرصيد:</h6>
                                <div class="info-item">
                                  <label>الدولار:</label>
                                  <span class="value">{{ formatCurrency(monthlyReport.monthly_account.total_balance_dollar, 'dollar') }}</span>
                                </div>
                                <div class="info-item">
                                  <label>الدينار:</label>
                                  <span class="value">{{ formatCurrency(monthlyReport.monthly_account.total_balance_dinar, 'dinar') }}</span>
                                </div>
                                <div class="info-item">
                                  <label>صافي الربح:</label>
                                  <span class="value text-success">{{ formatCurrency(monthlyReport.monthly_account.net_profit_dollar) }}</span>
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
          </div>
        </div>
      </div>
    </section>

    <!-- Close Month Modal -->
    <div v-if="showCloseMonthModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">إغلاق الشهر</h5>
            <button type="button" class="btn-close" @click="showCloseMonthModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning">
              <i class="bi bi-exclamation-triangle"></i>
              هل أنت متأكد من إغلاق الشهر؟ لا يمكن التراجع عن هذا الإجراء.
            </div>
            <div class="mb-3">
              <label for="closeNotes" class="form-label">ملاحظات (اختياري)</label>
              <textarea 
                id="closeNotes"
                v-model="closeNotes"
                class="form-control"
                rows="3"
                placeholder="أضف ملاحظات حول إغلاق الشهر..."
              ></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showCloseMonthModal = false">إلغاء</button>
            <button type="button" class="btn btn-warning" @click="closeMonth" :disabled="isLoading">
              <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
              إغلاق الشهر
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="showCloseMonthModal" class="modal-backdrop fade show"></div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  translations: Object,
  activeTab: String,
  currentMonth: Object,
  monthlyAccounts: Object,
  monthlyReport: Object,
});

const activeTab = ref(props.activeTab || 'current');
const showCloseMonthModal = ref(false);
const closeNotes = ref('');
const isLoading = ref(false);
const selectedYear = ref('');
const selectedMonth = ref('');
const monthlyReport = ref(props.monthlyReport);

const years = computed(() => {
  const currentYear = new Date().getFullYear();
  return Array.from({ length: 5 }, (_, i) => currentYear - i);
});

const months = [
  'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
  'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
];

const formatCurrency = (amount, currency = 'dollar') => {
  if (amount === undefined || amount === null) return '0.00';
  const symbol = currency === 'dollar' ? '$' : 'د.ع';
  return `${parseFloat(amount).toFixed(2)} ${symbol}`;
};

const closeMonth = async () => {
  isLoading.value = true;
  
  try {
    const response = await fetch(route('decoration.monthly.close'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({
        notes: closeNotes.value,
      }),
    });

    const data = await response.json();

    if (data.success) {
      showCloseMonthModal.value = false;
      closeNotes.value = '';
      // Reload the page to update data
      router.reload();
    } else {
      alert(data.message || 'حدث خطأ أثناء إغلاق الشهر');
    }
  } catch (error) {
    console.error('Error closing month:', error);
    alert('حدث خطأ أثناء إغلاق الشهر');
  } finally {
    isLoading.value = false;
  }
};

const recalculateData = async (monthlyAccountId) => {
  try {
    const response = await fetch(route('decoration.monthly.recalculate', monthlyAccountId), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
    });

    const data = await response.json();

    if (data.success) {
      alert('تم إعادة حساب البيانات بنجاح');
      router.reload();
    } else {
      alert(data.message || 'حدث خطأ أثناء إعادة حساب البيانات');
    }
  } catch (error) {
    console.error('Error recalculating data:', error);
    alert('حدث خطأ أثناء إعادة حساب البيانات');
  }
};

const payoutCommissions = async (year, month) => {
  try {
    const response = await fetch(route('decoration.monthly.payoutCommissions'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({ year, month }),
    });

    const data = await response.json();
    if (data.success) {
      alert('تم دفع العمولات بنجاح');
      router.reload();
    } else {
      alert(data.message || 'حدث خطأ أثناء دفع العمولات');
    }
  } catch (error) {
    console.error('Error paying out commissions:', error);
    alert('حدث خطأ أثناء دفع العمولات');
  }
};

const viewReport = (year, month) => {
  selectedYear.value = year;
  selectedMonth.value = month;
  activeTab.value = 'reports';
  loadMonthlyReport();
};

const loadMonthlyReport = async () => {
  if (!selectedYear.value || !selectedMonth.value) return;

  try {
    const response = await fetch(route('decoration.monthly.report', {
      year: selectedYear.value,
      month: selectedMonth.value,
    }));

    const data = await response.json();

    if (data.success) {
      monthlyReport.value = data.data;
    } else {
      alert(data.message || 'لا توجد بيانات لهذا الشهر');
    }
  } catch (error) {
    console.error('Error loading monthly report:', error);
    alert('حدث خطأ أثناء تحميل التقرير');
  }
};

const loadPage = (url) => {
  if (url) {
    router.visit(url);
  }
};

onMounted(() => {
  // Set current year and month as default
  const now = new Date();
  selectedYear.value = now.getFullYear();
  selectedMonth.value = now.getMonth() + 1;
});
</script>

<style scoped>
.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #eee;
}

.info-item:last-child {
  border-bottom: none;
}

.info-item label {
  font-weight: 600;
  color: #555;
  margin: 0;
}

.info-item .value {
  font-weight: 500;
  color: #333;
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

.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-backdrop {
  background-color: rgba(0, 0, 0, 0.5);
}
</style>
