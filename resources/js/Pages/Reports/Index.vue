<template>
  <AuthenticatedLayout :translations="translations">
    <template #header>
      <div class="d-flex justify-content-between align-items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          التقارير
        </h2>
        <button v-if="reportData" class="btn btn-primary" @click="printReport">
          <i class="bi bi-printer"></i> طباعة التقرير
        </button>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Navigation Tabs -->
        <div class="card mb-4">
          <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button 
                  class="nav-link" 
                  :class="{ active: activeTab === 'sales' }"
                  @click="changeTab('sales')"
                  type="button"
                >
                  <i class="bi bi-cart-check"></i> تقرير المبيعات
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button 
                  class="nav-link" 
                  :class="{ active: activeTab === 'purchases' }"
                  @click="changeTab('purchases')"
                  type="button"
                >
                  <i class="bi bi-box-arrow-in-down"></i> تقرير المشتريات
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button 
                  class="nav-link" 
                  :class="{ active: activeTab === 'expenses' }"
                  @click="changeTab('expenses')"
                  type="button"
                >
                  <i class="bi bi-cash-stack"></i> تقرير المصاريف
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button 
                  class="nav-link" 
                  :class="{ active: activeTab === 'summary' }"
                  @click="changeTab('summary')"
                  type="button"
                >
                  <i class="bi bi-graph-up"></i> التقرير الشامل
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button 
                  class="nav-link" 
                  :class="{ active: activeTab === 'daily-closes' }"
                  @click="changeTab('daily-closes')"
                  type="button"
                >
                  <i class="bi bi-calendar-day"></i> الإغلاق اليومي
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button 
                  class="nav-link" 
                  :class="{ active: activeTab === 'monthly-closes' }"
                  @click="changeTab('monthly-closes')"
                  type="button"
                >
                  <i class="bi bi-calendar-month"></i> الإغلاق الشهري
                </button>
              </li>
            </ul>
          </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label">من تاريخ</label>
                <input 
                  type="date" 
                  class="form-control" 
                  v-model="filters.start_date"
                  @change="loadReport"
                />
              </div>
              <div class="col-md-3">
                <label class="form-label">إلى تاريخ</label>
                <input 
                  type="date" 
                  class="form-control" 
                  v-model="filters.end_date"
                  @change="loadReport"
                />
              </div>
              <div class="col-md-3" v-if="activeTab === 'sales'">
                <label class="form-label">العميل</label>
                <select 
                  class="form-select" 
                  v-model="filters.customer_id"
                  @change="loadReport"
                >
                  <option value="">الكل</option>
                  <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                    {{ customer.name }}
                  </option>
                </select>
              </div>
              <div class="col-md-3" v-if="activeTab === 'sales'">
                <label class="form-label">المنتج</label>
                <select 
                  class="form-select" 
                  v-model="filters.product_id"
                  @change="loadReport"
                >
                  <option value="">الكل</option>
                  <option v-for="product in products" :key="product.id" :value="product.id">
                    {{ product.name }}
                  </option>
                </select>
              </div>
              <div class="col-md-3" v-if="activeTab === 'purchases'">
                <label class="form-label">المورد</label>
                <select 
                  class="form-select" 
                  v-model="filters.supplier_id"
                  @change="loadReport"
                >
                  <option value="">الكل</option>
                  <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                    {{ supplier.name }}
                  </option>
                </select>
              </div>
              <div class="col-md-3" v-if="activeTab === 'expenses'">
                <label class="form-label">الفئة</label>
                <select 
                  class="form-select" 
                  v-model="filters.category"
                  @change="loadReport"
                >
                  <option value="">الكل</option>
                  <option v-for="cat in categories" :key="cat" :value="cat">
                    {{ cat }}
                  </option>
                </select>
              </div>
              <div class="col-md-3" v-if="activeTab === 'expenses'">
                <label class="form-label">العملة</label>
                <select 
                  class="form-select" 
                  v-model="filters.currency"
                  @change="loadReport"
                >
                  <option value="">الكل</option>
                  <option value="IQD">IQD</option>
                  <option value="USD">USD</option>
                </select>
              </div>
              <div class="col-md-12">
                <button class="btn btn-primary" @click="loadReport">
                  <i class="bi bi-search"></i> تطبيق الفلتر
                </button>
                <button class="btn btn-secondary ms-2" @click="resetFilters">
                  <i class="bi bi-arrow-counterclockwise"></i> إعادة تعيين
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">جاري التحميل...</span>
          </div>
        </div>

        <!-- Report Content -->
        <div v-else-if="reportData" class="card">
          <div class="card-body">
            <!-- Print Button -->
            <div class="mb-4 text-end">
              <button class="btn btn-primary btn-lg" @click="printReport">
                <i class="bi bi-printer"></i> طباعة التقرير
              </button>
            </div>
            
            <!-- Sales Report -->
            <div v-if="activeTab === 'sales' && reportData.statistics">
              <h4 class="mb-4">إحصائيات المبيعات</h4>
              <div class="row mb-4">
                <div class="col-md-3">
                  <div class="card bg-primary text-white">
                    <div class="card-body">
                      <h6>إجمالي المبيعات</h6>
                      <h3>{{ formatNumber(reportData.statistics.total_amount) }}</h3>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="card bg-success text-white">
                    <div class="card-body">
                      <h6>المدفوع</h6>
                      <h3>{{ formatNumber(reportData.statistics.total_paid) }}</h3>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="card bg-warning text-white">
                    <div class="card-body">
                      <h6>المتبقي</h6>
                      <h3>{{ formatNumber(reportData.statistics.remaining) }}</h3>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="card bg-info text-white">
                    <div class="card-body">
                      <h6>عدد الفواتير</h6>
                      <h3>{{ reportData.statistics.total_count }}</h3>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Direct Deposits and Withdrawals -->
              <div class="row mb-4">
                <div class="col-md-3">
                  <div class="card bg-success text-white">
                    <div class="card-body">
                      <h6>الإضافة المباشرة (USD)</h6>
                      <h3>{{ formatNumber(reportData.statistics.direct_deposits_usd || 0) }}</h3>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="card bg-success text-white">
                    <div class="card-body">
                      <h6>الإضافة المباشرة (IQD)</h6>
                      <h3>{{ formatNumber(reportData.statistics.direct_deposits_iqd || 0) }}</h3>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="card bg-danger text-white">
                    <div class="card-body">
                      <h6>السحب المباشر (USD)</h6>
                      <h3>{{ formatNumber(reportData.statistics.direct_withdrawals_usd || 0) }}</h3>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="card bg-danger text-white">
                    <div class="card-body">
                      <h6>السحب المباشر (IQD)</h6>
                      <h3>{{ formatNumber(reportData.statistics.direct_withdrawals_iqd || 0) }}</h3>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Orders Table -->
              <h5 class="mb-3">الفواتير</h5>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>التاريخ</th>
                      <th>العميل</th>
                      <th>المبلغ</th>
                      <th>المدفوع</th>
                      <th>الحالة</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(order, index) in reportData.orders" :key="order.id">
                      <td>{{ index + 1 }}</td>
                      <td>{{ formatDate(order.date) }}</td>
                      <td>{{ order.customer?.name || 'N/A' }}</td>
                      <td>{{ formatNumber(order.final_amount || order.total_amount) }}</td>
                      <td>{{ formatNumber(order.total_paid) }}</td>
                      <td>
                        <span :class="getStatusBadgeClass(order.status)">
                          {{ order.status }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Purchases Report -->
            <div v-if="activeTab === 'purchases' && reportData.statistics">
              <h4 class="mb-4">إحصائيات المشتريات</h4>
              <div class="row mb-4">
                <div class="col-md-4">
                  <div class="card bg-primary text-white">
                    <div class="card-body">
                      <h6>إجمالي المشتريات</h6>
                      <h3>{{ formatNumber(reportData.statistics.total_amount) }}</h3>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="card bg-info text-white">
                    <div class="card-body">
                      <h6>عدد الفواتير</h6>
                      <h3>{{ reportData.statistics.total_count }}</h3>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="card bg-secondary text-white">
                    <div class="card-body">
                      <h6>متوسط الفاتورة</h6>
                      <h3>{{ formatNumber(reportData.statistics.average_purchase) }}</h3>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Purchases Table -->
              <h5 class="mb-3">فواتير المشتريات</h5>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>التاريخ</th>
                      <th>المورد</th>
                      <th>المبلغ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(purchase, index) in reportData.purchases" :key="purchase.id">
                      <td>{{ index + 1 }}</td>
                      <td>{{ formatDate(purchase.invoice_date) }}</td>
                      <td>{{ purchase.supplier?.name || 'N/A' }}</td>
                      <td>{{ formatNumber(purchase.total_amount) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Expenses Report -->
            <div v-if="activeTab === 'expenses' && reportData.statistics">
              <h4 class="mb-4">إحصائيات المصاريف</h4>
              <div class="row mb-4">
                <div class="col-md-4">
                  <div class="card bg-danger text-white">
                    <div class="card-body">
                      <h6>إجمالي المصاريف</h6>
                      <h3>{{ formatNumber(reportData.statistics.total_amount) }}</h3>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="card bg-info text-white">
                    <div class="card-body">
                      <h6>عدد السجلات</h6>
                      <h3>{{ reportData.statistics.total_count }}</h3>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="card bg-secondary text-white">
                    <div class="card-body">
                      <h6>متوسط المصروف</h6>
                      <h3>{{ formatNumber(reportData.statistics.average_expense) }}</h3>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Expenses Table -->
              <h5 class="mb-3">المصاريف</h5>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>التاريخ</th>
                      <th>الوصف</th>
                      <th>الفئة</th>
                      <th>المبلغ</th>
                      <th>العملة</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(expense, index) in reportData.expenses" :key="expense.id">
                      <td>{{ index + 1 }}</td>
                      <td>{{ formatDate(expense.expense_date) }}</td>
                      <td>{{ expense.title }}</td>
                      <td>{{ expense.category || 'غير محدد' }}</td>
                      <td>{{ formatNumber(expense.amount) }}</td>
                      <td>{{ expense.currency }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Summary Report -->
            <div v-if="activeTab === 'summary' && reportData">
              <h4 class="mb-4">التقرير الشامل</h4>
              <div class="row mb-4">
                <div class="col-md-3">
                  <div class="card bg-success text-white">
                    <div class="card-body">
                      <h6>إجمالي المبيعات</h6>
                      <h3>{{ formatNumber(reportData.sales?.total || 0) }}</h3>
                      <small>{{ reportData.sales?.count || 0 }} فاتورة</small>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="card bg-primary text-white">
                    <div class="card-body">
                      <h6>إجمالي المشتريات</h6>
                      <h3>{{ formatNumber(reportData.purchases?.total || 0) }}</h3>
                      <small>{{ reportData.purchases?.count || 0 }} فاتورة</small>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="card bg-danger text-white">
                    <div class="card-body">
                      <h6>إجمالي المصاريف</h6>
                      <h3>{{ formatNumber(reportData.expenses?.total || 0) }}</h3>
                      <small>{{ reportData.expenses?.count || 0 }} سجل</small>
                    </div>
                  </div>
                </div>
                  <div class="col-md-3">
                    <div class="card" :class="reportData.profit >= 0 ? 'bg-info text-white' : 'bg-warning text-white'">
                      <div class="card-body text-white">
                      <h6>الربح الإجمالي</h6>
                      <h3>{{ formatNumber(reportData.profit || 0) }}</h3>
                      <small>{{ (reportData.profit_percentage || 0).toFixed(2) }}%</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Daily Closes Report -->
        <div v-else-if="activeTab === 'daily-closes' && reportData" class="card">
          <div class="card-body">
            <!-- Print Button -->
            <div class="mb-4 text-end">
              <button class="btn btn-primary btn-lg" @click="printReport">
                <i class="bi bi-printer"></i> طباعة التقرير
              </button>
            </div>

            <h4 class="mb-4">إحصائيات الإغلاق اليومي</h4>
            <div class="row mb-4">
              <div class="col-md-3">
                <div class="card bg-primary text-white">
                  <div class="card-body">
                    <h6>المبيعات (USD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_sales_usd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-primary text-white">
                  <div class="card-body">
                    <h6>المبيعات (IQD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_sales_iqd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-success text-white">
                  <div class="card-body">
                    <h6>الإضافة المباشرة (USD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_direct_deposits_usd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-success text-white">
                  <div class="card-body">
                    <h6>الإضافة المباشرة (IQD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_direct_deposits_iqd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-danger text-white">
                  <div class="card-body">
                    <h6>السحب المباشر (USD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_direct_withdrawals_usd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-danger text-white">
                  <div class="card-body">
                    <h6>السحب المباشر (IQD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_direct_withdrawals_iqd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-warning text-white">
                  <div class="card-body">
                    <h6>المصاريف (USD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_expenses_usd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-warning text-white">
                  <div class="card-body">
                    <h6>المصاريف (IQD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_expenses_iqd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-info text-white">
                  <div class="card-body">
                    <h6>عدد الطلبات</h6>
                    <h3>{{ reportData.statistics?.total_orders || 0 }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-success text-white">
                  <div class="card-body">
                    <h6>مغلق</h6>
                    <h3>{{ reportData.statistics?.closed_count || 0 }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-warning text-white">
                  <div class="card-body">
                    <h6>مفتوح</h6>
                    <h3>{{ reportData.statistics?.open_count || 0 }}</h3>
                  </div>
                </div>
              </div>
            </div>

            <h5 class="mb-3">قائمة الإغلاق اليومي</h5>
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                    <th>المبيعات (USD)</th>
                    <th>المبيعات (IQD)</th>
                    <th>الإضافة المباشرة (USD)</th>
                    <th>الإضافة المباشرة (IQD)</th>
                    <th>السحب المباشر (USD)</th>
                    <th>السحب المباشر (IQD)</th>
                    <th>المصاريف (USD)</th>
                    <th>المصاريف (IQD)</th>
                    <th>عدد الطلبات</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(close, index) in reportData.daily_closes" :key="close.id">
                    <td>{{ index + 1 }}</td>
                    <td>{{ formatDate(close.close_date) }}</td>
                    <td>
                      <span :class="close.status === 'closed' ? 'badge bg-success' : 'badge bg-warning'">
                        {{ close.status === 'closed' ? 'مغلق' : 'مفتوح' }}
                      </span>
                    </td>
                    <td>{{ formatNumber(close.total_sales_usd) }}</td>
                    <td>{{ formatNumber(close.total_sales_iqd) }}</td>
                    <td>{{ formatNumber(close.direct_deposits_usd) }}</td>
                    <td>{{ formatNumber(close.direct_deposits_iqd) }}</td>
                    <td>{{ formatNumber(close.direct_withdrawals_usd) }}</td>
                    <td>{{ formatNumber(close.direct_withdrawals_iqd) }}</td>
                    <td>{{ formatNumber(close.total_expenses_usd) }}</td>
                    <td>{{ formatNumber(close.total_expenses_iqd) }}</td>
                    <td>{{ close.total_orders }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Monthly Closes Report -->
        <div v-else-if="activeTab === 'monthly-closes' && reportData" class="card">
          <div class="card-body">
            <!-- Print Button -->
            <div class="mb-4 text-end">
              <button class="btn btn-primary btn-lg" @click="printReport">
                <i class="bi bi-printer"></i> طباعة التقرير
              </button>
            </div>

            <h4 class="mb-4">إحصائيات الإغلاق الشهري</h4>
            <div class="row mb-4">
              <div class="col-md-3">
                <div class="card bg-primary text-white">
                  <div class="card-body">
                    <h6>المبيعات (USD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_sales_usd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-primary text-white">
                  <div class="card-body">
                    <h6>المبيعات (IQD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_sales_iqd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-success text-white">
                  <div class="card-body">
                    <h6>الإضافة المباشرة (USD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_direct_deposits_usd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-success text-white">
                  <div class="card-body">
                    <h6>الإضافة المباشرة (IQD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_direct_deposits_iqd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-danger text-white">
                  <div class="card-body">
                    <h6>السحب المباشر (USD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_direct_withdrawals_usd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-danger text-white">
                  <div class="card-body">
                    <h6>السحب المباشر (IQD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_direct_withdrawals_iqd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-warning text-white">
                  <div class="card-body">
                    <h6>المصاريف (USD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_expenses_usd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-warning text-white">
                  <div class="card-body">
                    <h6>المصاريف (IQD)</h6>
                    <h3>{{ formatNumber(reportData.statistics?.total_expenses_iqd || 0) }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-info text-white">
                  <div class="card-body">
                    <h6>عدد الطلبات</h6>
                    <h3>{{ reportData.statistics?.total_orders || 0 }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-success text-white">
                  <div class="card-body">
                    <h6>مغلق</h6>
                    <h3>{{ reportData.statistics?.closed_count || 0 }}</h3>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-warning text-white">
                  <div class="card-body">
                    <h6>مفتوح</h6>
                    <h3>{{ reportData.statistics?.open_count || 0 }}</h3>
                  </div>
                </div>
              </div>
            </div>

            <h5 class="mb-3">قائمة الإغلاق الشهري</h5>
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>الشهر</th>
                    <th>السنة</th>
                    <th>الحالة</th>
                    <th>المبيعات (USD)</th>
                    <th>المبيعات (IQD)</th>
                    <th>الإضافة المباشرة (USD)</th>
                    <th>الإضافة المباشرة (IQD)</th>
                    <th>السحب المباشر (USD)</th>
                    <th>السحب المباشر (IQD)</th>
                    <th>المصاريف (USD)</th>
                    <th>المصاريف (IQD)</th>
                    <th>عدد الطلبات</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(close, index) in reportData.monthly_closes" :key="close.id">
                    <td>{{ index + 1 }}</td>
                    <td>{{ getMonthName(close.month) }}</td>
                    <td>{{ close.year }}</td>
                    <td>
                      <span :class="close.status === 'closed' ? 'badge bg-success' : 'badge bg-warning'">
                        {{ close.status === 'closed' ? 'مغلق' : 'مفتوح' }}
                      </span>
                    </td>
                    <td>{{ formatNumber(close.total_sales_usd) }}</td>
                    <td>{{ formatNumber(close.total_sales_iqd) }}</td>
                    <td>{{ formatNumber(close.direct_deposits_usd) }}</td>
                    <td>{{ formatNumber(close.direct_deposits_iqd) }}</td>
                    <td>{{ formatNumber(close.direct_withdrawals_usd) }}</td>
                    <td>{{ formatNumber(close.direct_withdrawals_iqd) }}</td>
                    <td>{{ formatNumber(close.total_expenses_usd) }}</td>
                    <td>{{ formatNumber(close.total_expenses_iqd) }}</td>
                    <td>{{ close.total_orders }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- No Data -->
        <div v-else class="card">
          <div class="card-body text-center py-5">
            <p class="text-muted">لا توجد بيانات لعرضها</p>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

const page = usePage()

const props = defineProps({
  activeTab: {
    type: String,
    default: 'sales'
  },
  translations: Object,
})

const activeTab = ref(props.activeTab)
const loading = ref(false)
const reportData = ref(null)
const customers = ref([])
const products = ref([])
const suppliers = ref([])
const categories = ref([])

const filters = ref({
  start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
  customer_id: '',
  product_id: '',
  supplier_id: '',
  category: '',
  currency: '',
})

const translations = computed(() => props.translations || {})

const changeTab = (tab) => {
  activeTab.value = tab
  reportData.value = null
  resetFilters()
  loadReport()
}

const resetFilters = () => {
  filters.value = {
    start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    customer_id: '',
    product_id: '',
    supplier_id: '',
    category: '',
    currency: '',
  }
}

const loadReport = async () => {
  loading.value = true
  try {
    let endpoint = ''
    const params = {
      start_date: filters.value.start_date,
      end_date: filters.value.end_date,
    }

    switch (activeTab.value) {
      case 'sales':
        endpoint = route('reports.sales')
        if (filters.value.customer_id) params.customer_id = filters.value.customer_id
        if (filters.value.product_id) params.product_id = filters.value.product_id
        break
      case 'purchases':
        endpoint = route('reports.purchases')
        if (filters.value.supplier_id) params.supplier_id = filters.value.supplier_id
        break
      case 'expenses':
        endpoint = route('reports.expenses')
        if (filters.value.category) params.category = filters.value.category
        if (filters.value.currency) params.currency = filters.value.currency
        break
      case 'summary':
        endpoint = route('reports.summary')
        break
      case 'daily-closes':
        endpoint = route('reports.daily-closes')
        break
      case 'monthly-closes':
        endpoint = route('reports.monthly-closes')
        // For monthly closes, we need year and month filters
        params.start_year = new Date(filters.value.start_date).getFullYear()
        params.end_year = new Date(filters.value.end_date).getFullYear()
        params.start_month = new Date(filters.value.start_date).getMonth() + 1
        params.end_month = new Date(filters.value.end_date).getMonth() + 1
        break
    }

    const response = await axios.get(endpoint, { params })
    reportData.value = response.data

    // Load dropdown data
    if (activeTab.value === 'sales' && reportData.value.customers) {
      customers.value = reportData.value.customers
      products.value = reportData.value.products || []
    }
    if (activeTab.value === 'purchases' && reportData.value.suppliers) {
      suppliers.value = reportData.value.suppliers
    }
    if (activeTab.value === 'expenses' && reportData.value.categories) {
      categories.value = reportData.value.categories
    }
  } catch (error) {
    console.error('Error loading report:', error)
  } finally {
    loading.value = false
  }
}

const printReport = () => {
  const params = new URLSearchParams({
    ...filters.value,
    format: 'print'
  })
  
  let routeName = ''
  switch (activeTab.value) {
    case 'sales':
      routeName = 'reports.sales.print'
      break
    case 'purchases':
      routeName = 'reports.purchases.print'
      break
    case 'expenses':
      routeName = 'reports.expenses.print'
      break
    case 'summary':
      routeName = 'reports.summary.print'
      break
    case 'daily-closes':
      routeName = 'reports.daily-closes.print'
      break
    case 'monthly-closes':
      routeName = 'reports.monthly-closes.print'
      break
  }
  
  if (routeName) {
    window.open(route(routeName) + '?' + params.toString(), '_blank')
  }
}

const formatNumber = (num) => {
  const formatted = parseFloat(num || 0).toLocaleString('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  })
  // إزالة .00 إذا كان الرقم صحيحاً
  return formatted.replace(/\.00$/, '')
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US')
}

const getMonthName = (month) => {
  const months = [
    'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
    'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
  ]
  return months[month - 1] || month
}

const getStatusBadgeClass = (status) => {
  const classes = {
    'paid': 'badge bg-success',
    'unpaid': 'badge bg-danger',
    'partial': 'badge bg-warning',
  }
  return classes[status] || 'badge bg-secondary'
}

onMounted(() => {
  loadReport()
})
</script>

<style scoped>
.nav-tabs .nav-link {
  border: none;
  border-radius: 0;
  color: #6c757d;
  font-weight: 500;
  padding: 1rem 1.5rem;
  transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
  border-color: transparent;
  color: #495057;
  background-color: #f8f9fa;
}

.nav-tabs .nav-link.active {
  color: #495057;
  background-color: #fff;
  border-color: #dee2e6 #dee2e6 #fff;
  border-bottom: 2px solid #007bff;
}
</style>

