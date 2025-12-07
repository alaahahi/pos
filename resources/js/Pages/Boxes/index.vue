<template>
  <AuthenticatedLayout :translations="translations">
    <div dir="rtl" lang="ar">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-title">
          <i class="bi bi-safe2"></i>
          <h1>{{ translations.boxes }}</h1>
    </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="statistics-section">
      <div class="stats-grid">
        <div class="stat-card balance-usd">
          <div class="stat-icon">
            <i class="bi bi-currency-dollar"></i>
          </div>
          <div class="stat-details">
            <h6>الرصيد بالدولار</h6>
            <span class="stat-value">{{ updateResults(mainBox?.balance_usd ?? 0) }}</span>
            <small>USD</small>
          </div>
        </div>

        <div class="stat-card balance-iqd">
          <div class="stat-icon">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div class="stat-details">
            <h6>الرصيد بالدينار</h6>
            <span class="stat-value">{{ updateResults(mainBox?.balance ?? 0) }}</span>
            <small>IQD</small>
          </div>
        </div>

        <div class="stat-card total-in">
          <div class="stat-icon">
            <i class="bi bi-arrow-down-circle"></i>
          </div>
          <div class="stat-details">
            <h6>إجمالي الإضافات</h6>
            <div class="stat-value-group">
              <span class="stat-value-currency">{{ calculateTotalInUSD() }} USD</span>
              <span class="stat-value-currency">{{ calculateTotalInIQD() }} IQD</span>
            </div>
            <small>عمليات اليوم</small>
          </div>
        </div>

        <div class="stat-card total-out">
          <div class="stat-icon">
            <i class="bi bi-arrow-up-circle"></i>
          </div>
          <div class="stat-details">
            <h6>إجمالي السحوبات</h6>
            <div class="stat-value-group">
              <span class="stat-value-currency">{{ calculateTotalOutUSD() }} USD</span>
              <span class="stat-value-currency">{{ calculateTotalOutIQD() }} IQD</span>
            </div>
            <small>عمليات اليوم</small>
          </div>
        </div>
      </div>
    </div>


    <!-- Actions Section -->
     <section class="section dashboard">
      <div class="card main-card">
        <!-- Tabs Navigation -->
        <div class="tabs-navigation">
          <button 
            class="tab-btn" 
            :class="{ active: activeTab === 'transactions' }"
            @click="activeTab = 'transactions'"
          >
            <i class="bi bi-list-ul"></i>
            المعاملات
          </button>
          <button 
            class="tab-btn" 
            :class="{ active: activeTab === 'close' }"
            @click="activeTab = 'close'"
          >
            <i class="bi bi-calendar-check"></i>
            الإغلاق اليومي والشهري
          </button>
          <button 
            class="tab-btn" 
            :class="{ active: activeTab === 'closes-list' }"
            @click="activeTab = 'closes-list'; loadClosesList()"
          >
            <i class="bi bi-list-check"></i>
            قائمة الإغلاقات
          </button>
        </div>

        <!-- Transactions Tab Content -->
        <div v-show="activeTab === 'transactions'" class="tab-content">
        <div class="card-header-custom">
          <div class="actions-wrapper">
            <!-- Primary Actions -->
            <div class="action-group primary-actions">
              <button 
                v-if="hasPermission('create order')" 
                class="action-btn success" 
                @click="openAddSales()"
                :disabled="loading"
              >
                <i class="bi bi-plus-circle"></i>
                <span>{{ translations.add_to_box }}</span>
                </button>

              <button 
                v-if="hasPermission('create order')" 
                class="action-btn danger" 
                @click="openAddExpenses()"
                :disabled="loading"
              >
                <i class="bi bi-dash-circle"></i>
                <span>{{ translations.drop_from_box }}</span>
                </button>
            </div>

            <!-- Conversion Actions -->
            <div class="action-group conversion-actions">
              <button 
                v-if="hasPermission('create order')" 
                class="action-btn warning" 
                @click="openConvertDinarDollar()"
                :disabled="loading"
              >
                <i class="bi bi-arrow-left-right"></i>
                <span>دينار → دولار</span>
                </button>

              <button 
                v-if="hasPermission('create order')" 
                class="action-btn warning" 
                @click="openConvertDollarDinar()"
                :disabled="loading"
              >
                <i class="bi bi-arrow-right-left"></i>
                <span>دولار → دينار</span>
                </button>
            </div>

            <!-- Refresh Button -->
            <div class="action-group">
              <button 
                class="action-btn info" 
                @click="refresh()"
                :disabled="loading"
              >
                <i class="bi bi-arrow-clockwise" :class="{ 'spin': loading }"></i>
                <span>تحديث</span>
                </button>
            </div>
            </div>

          <!-- Exchange Rate Info -->
          <div class="exchange-rate-info">
            <i class="bi bi-graph-up-arrow"></i>
            <span>سعر الصرف: <strong>{{ exchangeRate }}</strong> IQD/USD</span>
          </div>
        </div>

        <!-- Filters Section -->
        <div class="card-body">
          <div class="filters-section">
            <form @submit.prevent="Filter" class="filter-form">
              <div class="filter-grid">
                <div class="filter-item">
                  <label>
                    <i class="bi bi-calendar-event"></i>
                    تاريخ البداية
                  </label>
                  <input 
                    type="date" 
                    class="form-control" 
                    v-model="filterForm.start_date"
                  />
              </div>

                <div class="filter-item">
                  <label>
                    <i class="bi bi-calendar-check"></i>
                    تاريخ النهاية
                  </label>
                  <input 
                    type="date" 
                    class="form-control" 
                    v-model="filterForm.end_date"
                  />
              </div>

                <div class="filter-item">
                  <label>
                    <i class="bi bi-person"></i>
                    الاسم
                  </label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="filterForm.name" 
                    placeholder="ابحث بالاسم..."
                  />
              </div>

                <div class="filter-item">
                  <label>
                    <i class="bi bi-file-text"></i>
                    الملاحظة
                  </label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="filterForm.note" 
                    placeholder="ابحث بالملاحظة..."
                  />
              </div>

                <div class="filter-actions">
                  <button type="submit" class="btn btn-primary" :disabled="loading">
                    <i class="bi bi-search"></i>
                    {{ translations.search }}
                  </button>
                  <button type="button" class="btn btn-secondary" @click="clearFilters" :disabled="loading">
                    <i class="bi bi-x-circle"></i>
                    مسح
                </button>
              </div>
            </div>
          </form>
          </div>

          <!-- Loading Overlay -->
          <div v-if="loading" class="loading-overlay">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">جاري التحميل...</span>
            </div>
          </div>

          <!-- Transactions Table -->
          <div class="table-container">
          <div class="table-responsive">
              <table class="table transactions-table">
              <thead>
                <tr>
                  <th scope="col">#</th>
                    <th scope="col">
                      <i class="bi bi-person-circle"></i>
                      الحساب
                    </th>
                    <th scope="col">
                      <i class="bi bi-arrow-up text-danger"></i>
                      سحب
                    </th>
                    <th scope="col">
                      <i class="bi bi-arrow-down text-success"></i>
                      إضافة
                    </th>
                    <th scope="col">
                      <i class="bi bi-card-text"></i>
                      البيان
                    </th>
                    <th scope="col">
                      <i class="bi bi-calendar3"></i>
                      {{ translations.created_at }}
                    </th>
                    <th scope="col" class="actions-col">
                      <i class="bi bi-gear"></i>
                      الإجراءات
                    </th>
                </tr>
              </thead>
              <tbody>
                  <template v-if="transactions.data && transactions.data.length > 0">
                    <tr 
                      v-for="(tran, index) in transactions.data" 
                      :key="tran.id"
                      :class="getRowClass(tran)"
                      class="transaction-row"
                    >
                      <td class="index-cell">
                        <span class="index-badge">{{ (transactions.current_page - 1) * transactions.per_page + index + 1 }}</span>
                      </td>
                      <td class="account-cell">
                        <div class="account-info">
                          <i class="bi bi-person-badge"></i>
                          <span>{{ tran.morphed?.name || 'غير محدد' }}</span>
                        </div>
                      </td>
                      <td class="amount-cell out">
                        <span v-if="tran.type == 'outUser' || tran.type == 'out'" class="amount-badge out">
                          <i class="bi bi-dash-circle"></i>
                          {{ updateResults(tran.amount) }} {{ tran.currency }}
                        </span>
                        <span v-else>-</span>
                      </td>
                      <td class="amount-cell in">
                        <span v-if="tran.type == 'inUser' || tran.type == 'in'" class="amount-badge in">
                          <i class="bi bi-plus-circle"></i>
                          {{ updateResults(tran.amount) }} {{ tran.currency }}
                        </span>
                        <span v-else>-</span>
                      </td>
                      <td class="description-cell">
                        <span class="description-text">{{ tran.description || 'لا يوجد' }}</span>
                      </td>
                      <td class="date-cell">
                        <div class="date-info">
                          <i class="bi bi-clock"></i>
                          <span>{{ formatDate(tran.created_at) }}</span>
                        </div>
                      </td>
                      <td class="actions-cell">
                        <div class="action-buttons">
                          <!-- Delete Button -->
                          <button 
                            v-if="hasPermission('delete order')"
                            class="action-icon delete" 
                            @click="Delete(tran.id, tran.amount)"
                            title="حذف"
                          >
                      <trash />
                    </button>

                          <!-- Upload Button -->
                          <button 
                            class="action-icon upload" 
                            @click="openModalUploader(tran)"
                            title="تحميل مرفق"
                          >
                      <imags />
                    </button>

                          <!-- Print Button -->
                          <a  
                            v-if="tran.type === 'out' || tran.type === 'outUser' || tran.type === 'debt'"
                            target="_blank"
                      :href="`/api/getIndexAccountsSelas?user_id=${mainBox.id}&print=2&transactions_id=${tran.id}`"
                            class="action-icon print"
                            title="طباعة"
                          >
                            <print />
                          </a>
                          <a  
                            v-if="tran.type === 'in' || tran.type === 'inUser'"
                            target="_blank"
                      :href="`/api/getIndexAccountsSelas?user_id=${mainBox.id}&print=3&transactions_id=${tran.id}`"
                            class="action-icon print"
                            title="طباعة"
                          >
                            <print />
                          </a>

                          <!-- View Images Button -->
                          <button 
                            v-if="tran.transactions_images && tran.transactions_images.length > 0"
                            class="action-icon view" 
                            @click="viewImages(tran)"
                            title="عرض المرفقات"
                          >
                            <i class="bi bi-images"></i>
                            <span class="badge">{{ tran.transactions_images.length }}</span>
                          </button>
                        </div>
                  </td>
                </tr>
                </template>
                  <tr v-else>
                    <td colspan="7" class="empty-state">
                      <div class="empty-content">
                        <i class="bi bi-inbox"></i>
                        <p>لا توجد معاملات</p>
                      </div>
                    </td>
                  </tr>
              </tbody>
            </table>
          </div>
        </div>
        </div>
      
        <!-- Pagination -->
        <Pagination :links="transactions?.links" />
        </div>

        <!-- Close Tab Content -->
        <div v-show="activeTab === 'close'" class="tab-content">
          <div class="close-section">
            <div class="close-grid">
              <!-- Daily Close Card -->
              <div class="close-card daily-close">
                <div class="close-card-header">
                  <div class="close-card-title">
                    <i class="bi bi-calendar-day"></i>
                    <h3>إغلاق يومي</h3>
                  </div>
                  <span class="close-status" :class="dailyClose?.status === 'closed' ? 'closed' : 'open'">
                    {{ dailyClose?.status === 'closed' ? 'مغلق' : 'مفتوح' }}
                  </span>
                </div>
                <div class="close-card-body">
                  <div class="close-info-row">
                    <span class="close-label">المبيعات:</span>
                    <span class="close-value">
                      <span class="currency-badge usd">{{ updateResults(dailyClose?.total_sales_usd ?? 0) }} USD</span>
                      <span class="currency-badge iqd">{{ updateResults(dailyClose?.total_sales_iqd ?? 0) }} IQD</span>
                    </span>
                  </div>
                  <div class="close-info-row">
                    <span class="close-label">إضافة مباشرة:</span>
                    <span class="close-value">
                      <span class="currency-badge usd">{{ updateResults(dailyClose?.direct_deposits_usd ?? 0) }} USD</span>
                      <span class="currency-badge iqd">{{ updateResults(dailyClose?.direct_deposits_iqd ?? 0) }} IQD</span>
                    </span>
                  </div>
                  <div class="close-info-row">
                    <span class="close-label">سحب مباشر:</span>
                    <span class="close-value">
                      <span class="currency-badge usd">{{ updateResults(dailyClose?.direct_withdrawals_usd ?? 0) }} USD</span>
                      <span class="currency-badge iqd">{{ updateResults(dailyClose?.direct_withdrawals_iqd ?? 0) }} IQD</span>
                    </span>
                  </div>
                  <div class="close-info-row">
                    <span class="close-label">المصاريف:</span>
                    <span class="close-value">
                      <span class="currency-badge usd">{{ updateResults(dailyClose?.total_expenses_usd ?? 0) }} USD</span>
                      <span class="currency-badge iqd">{{ updateResults(dailyClose?.total_expenses_iqd ?? 0) }} IQD</span>
                    </span>
                  </div>
                  <div class="close-info-row">
                    <span class="close-label">الرصيد الافتتاحي:</span>
                    <span class="close-value">
                      <span class="currency-badge usd">{{ updateResults(dailyClose?.opening_balance_usd ?? 0) }} USD</span>
                      <span class="currency-badge iqd">{{ updateResults(dailyClose?.opening_balance_iqd ?? 0) }} IQD</span>
                    </span>
                  </div>
                  <div class="close-info-row highlight">
                    <span class="close-label">الرصيد الختامي:</span>
                    <span class="close-value">
                      <span class="currency-badge usd">{{ updateResults(dailyClose?.closing_balance_usd ?? 0) }} USD</span>
                      <span class="currency-badge iqd">{{ updateResults(dailyClose?.closing_balance_iqd ?? 0) }} IQD</span>
                    </span>
                  </div>
                  <div class="close-info-row">
                    <span class="close-label">عدد الطلبات:</span>
                    <span class="close-value">{{ dailyClose?.total_orders ?? 0 }}</span>
                  </div>
                </div>
                <div class="close-card-footer">
                  <button 
                    v-if="dailyClose?.status === 'open'"
                    class="btn-close-action"
                    @click="handleCloseDaily"
                    :disabled="loading"
                  >
                    <i class="bi bi-lock-fill"></i>
                    إغلاق اليوم
                  </button>
                  <span v-else class="closed-badge">
                    <i class="bi bi-check-circle"></i>
                    تم الإغلاق في {{ formatDate(dailyClose?.closed_at) }}
                  </span>
                </div>
              </div>

              <!-- Monthly Close Card -->
              <div class="close-card monthly-close">
                <div class="close-card-header">
                  <div class="close-card-title">
                    <i class="bi bi-calendar-month"></i>
                    <h3>إغلاق شهري - {{ monthlyClose?.month_name || getMonthName(monthlyClose?.month) }}</h3>
                  </div>
                  <span class="close-status" :class="monthlyClose?.status === 'closed' ? 'closed' : 'open'">
                    {{ monthlyClose?.status === 'closed' ? 'مغلق' : 'مفتوح' }}
                  </span>
                </div>
                <div class="close-card-body">
                  <div class="close-info-row">
                    <span class="close-label">المبيعات:</span>
                    <span class="close-value">
                      <span class="currency-badge usd">{{ updateResults(monthlyClose?.total_sales_usd ?? 0) }} USD</span>
                      <span class="currency-badge iqd">{{ updateResults(monthlyClose?.total_sales_iqd ?? 0) }} IQD</span>
                    </span>
                  </div>
                  <div class="close-info-row">
                    <span class="close-label">إضافة مباشرة:</span>
                    <span class="close-value">
                      <span class="currency-badge usd">{{ updateResults(monthlyClose?.direct_deposits_usd ?? 0) }} USD</span>
                      <span class="currency-badge iqd">{{ updateResults(monthlyClose?.direct_deposits_iqd ?? 0) }} IQD</span>
                    </span>
                  </div>
                  <div class="close-info-row">
                    <span class="close-label">سحب مباشر:</span>
                    <span class="close-value">
                      <span class="currency-badge usd">{{ updateResults(monthlyClose?.direct_withdrawals_usd ?? 0) }} USD</span>
                      <span class="currency-badge iqd">{{ updateResults(monthlyClose?.direct_withdrawals_iqd ?? 0) }} IQD</span>
                    </span>
                  </div>
                  <div class="close-info-row">
                    <span class="close-label">المصاريف:</span>
                    <span class="close-value">
                      <span class="currency-badge usd">{{ updateResults(monthlyClose?.total_expenses_usd ?? 0) }} USD</span>
                      <span class="currency-badge iqd">{{ updateResults(monthlyClose?.total_expenses_iqd ?? 0) }} IQD</span>
                    </span>
                  </div>
                  <div class="close-info-row">
                    <span class="close-label">الرصيد الافتتاحي:</span>
                    <span class="close-value">
                      <span class="currency-badge usd">{{ updateResults(monthlyClose?.opening_balance_usd ?? 0) }} USD</span>
                      <span class="currency-badge iqd">{{ updateResults(monthlyClose?.opening_balance_iqd ?? 0) }} IQD</span>
                    </span>
                  </div>
                  <div class="close-info-row highlight">
                    <span class="close-label">الرصيد الختامي:</span>
                    <span class="close-value">
                      <span class="currency-badge usd">{{ updateResults(monthlyClose?.closing_balance_usd ?? 0) }} USD</span>
                      <span class="currency-badge iqd">{{ updateResults(monthlyClose?.closing_balance_iqd ?? 0) }} IQD</span>
                    </span>
                  </div>
                  <div class="close-info-row">
                    <span class="close-label">عدد الطلبات:</span>
                    <span class="close-value">{{ monthlyClose?.total_orders ?? 0 }}</span>
                  </div>
                </div>
                <div class="close-card-footer">
                  <button 
                    v-if="monthlyClose?.status === 'open'"
                    class="btn-close-action"
                    @click="handleCloseMonthly"
                    :disabled="loading"
                  >
                    <i class="bi bi-lock-fill"></i>
                    إغلاق الشهر
                  </button>
                  <span v-else class="closed-badge">
                    <i class="bi bi-check-circle"></i>
                    تم الإغلاق في {{ formatDate(monthlyClose?.closed_at) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Closes List Tab Content -->
    <div v-show="activeTab === 'closes-list'" class="tab-content">
      <div class="card-header-custom">
        <div class="actions-wrapper">
          <h3 class="card-title">قائمة الإغلاقات</h3>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="filters-section" style="margin-bottom: 1.5rem;">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">النوع</label>
            <select v-model="closesFilters.type" @change="loadClosesList" class="form-select">
              <option value="all">الكل</option>
              <option value="daily">يومي</option>
              <option value="monthly">شهري</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">الحالة</label>
            <select v-model="closesFilters.status" @change="loadClosesList" class="form-select">
              <option value="all">الكل</option>
              <option value="open">مفتوح</option>
              <option value="closed">مغلق</option>
            </select>
          </div>
          <div class="col-md-2" v-if="closesFilters.type === 'all' || closesFilters.type === 'daily'">
            <label class="form-label">من تاريخ</label>
            <input type="date" v-model="closesFilters.start_date" @change="loadClosesList" class="form-control">
          </div>
          <div class="col-md-2" v-if="closesFilters.type === 'all' || closesFilters.type === 'daily'">
            <label class="form-label">إلى تاريخ</label>
            <input type="date" v-model="closesFilters.end_date" @change="loadClosesList" class="form-control">
          </div>
          <div class="col-md-2" v-if="closesFilters.type === 'all' || closesFilters.type === 'monthly'">
            <label class="form-label">السنة</label>
            <input type="number" v-model="closesFilters.year" @change="loadClosesList" class="form-control" placeholder="مثال: 2025">
          </div>
          <div class="col-md-2" v-if="closesFilters.type === 'all' || closesFilters.type === 'monthly'">
            <label class="form-label">الشهر</label>
            <select v-model="closesFilters.month" @change="loadClosesList" class="form-select">
              <option value="">الكل</option>
              <option value="1">يناير</option>
              <option value="2">فبراير</option>
              <option value="3">مارس</option>
              <option value="4">أبريل</option>
              <option value="5">مايو</option>
              <option value="6">يونيو</option>
              <option value="7">يوليو</option>
              <option value="8">أغسطس</option>
              <option value="9">سبتمبر</option>
              <option value="10">أكتوبر</option>
              <option value="11">نوفمبر</option>
              <option value="12">ديسمبر</option>
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button @click="clearClosesFilters" class="btn btn-secondary w-100">
              <i class="bi bi-x-circle"></i> مسح الفلاتر
            </button>
          </div>
        </div>
      </div>

      <!-- Daily Closes List -->
      <div v-if="closesFilters.type === 'all' || closesFilters.type === 'daily'" class="mb-4">
        <h5 class="mb-3">الإغلاقات اليومية</h5>
        <div v-if="loadingCloses" class="text-center py-4">
          <div class="spinner-border" role="status"></div>
        </div>
        <div v-else-if="dailyClosesList && dailyClosesList.data && dailyClosesList.data.length > 0" class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>التاريخ</th>
                <th>الحالة</th>
                <th>المبيعات</th>
                <th>إضافة مباشرة</th>
                <th>المصاريف</th>
                <th>الرصيد الافتتاحي</th>
                <th>الرصيد الختامي</th>
                <th>عدد الطلبات</th>
                <th>الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="close in dailyClosesList.data" :key="'daily-' + close.id">
                <td>{{ formatDate(close.close_date) }}</td>
                <td>
                  <span :class="close.status === 'closed' ? 'badge bg-success' : 'badge bg-warning'">
                    {{ close.status === 'closed' ? 'مغلق' : 'مفتوح' }}
                  </span>
                </td>
                <td>
                  <span class="currency-badge usd">{{ updateResults(close.total_sales_usd ?? 0) }} USD</span>
                  <span class="currency-badge iqd">{{ updateResults(close.total_sales_iqd ?? 0) }} IQD</span>
                </td>
                <td>
                  <span class="currency-badge usd">{{ updateResults(close.direct_deposits_usd ?? 0) }} USD</span>
                  <span class="currency-badge iqd">{{ updateResults(close.direct_deposits_iqd ?? 0) }} IQD</span>
                </td>
                <td>
                  <span class="currency-badge usd">{{ updateResults(close.total_expenses_usd ?? 0) }} USD</span>
                  <span class="currency-badge iqd">{{ updateResults(close.total_expenses_iqd ?? 0) }} IQD</span>
                </td>
                <td>
                  <span class="currency-badge usd">{{ updateResults(close.opening_balance_usd ?? 0) }} USD</span>
                  <span class="currency-badge iqd">{{ updateResults(close.opening_balance_iqd ?? 0) }} IQD</span>
                </td>
                <td>
                  <span class="currency-badge usd">{{ updateResults(close.closing_balance_usd ?? 0) }} USD</span>
                  <span class="currency-badge iqd">{{ updateResults(close.closing_balance_iqd ?? 0) }} IQD</span>
                </td>
                <td>{{ close.total_orders ?? 0 }}</td>
                <td>
                  <button @click="viewCloseDetails('daily', close)" class="btn btn-sm btn-info">
                    <i class="bi bi-eye"></i> التفاصيل
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          <Pagination v-if="dailyClosesList.links" :links="dailyClosesList.links" @page-changed="loadClosesList" />
        </div>
        <div v-else class="alert alert-info">لا توجد إغلاقات يومية</div>
      </div>

      <!-- Monthly Closes List -->
      <div v-if="closesFilters.type === 'all' || closesFilters.type === 'monthly'">
        <h5 class="mb-3">الإغلاقات الشهرية</h5>
        <div v-if="loadingCloses" class="text-center py-4">
          <div class="spinner-border" role="status"></div>
        </div>
        <div v-else-if="monthlyClosesList && monthlyClosesList.data && monthlyClosesList.data.length > 0" class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>الشهر</th>
                <th>الحالة</th>
                <th>المبيعات</th>
                <th>إضافة مباشرة</th>
                <th>المصاريف</th>
                <th>الرصيد الافتتاحي</th>
                <th>الرصيد الختامي</th>
                <th>عدد الطلبات</th>
                <th>الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="close in monthlyClosesList.data" :key="'monthly-' + close.id">
                <td>{{ getMonthName(close.month) }} {{ close.year }}</td>
                <td>
                  <span :class="close.status === 'closed' ? 'badge bg-success' : 'badge bg-warning'">
                    {{ close.status === 'closed' ? 'مغلق' : 'مفتوح' }}
                  </span>
                </td>
                <td>
                  <span class="currency-badge usd">{{ updateResults(close.total_sales_usd ?? 0) }} USD</span>
                  <span class="currency-badge iqd">{{ updateResults(close.total_sales_iqd ?? 0) }} IQD</span>
                </td>
                <td>
                  <span class="currency-badge usd">{{ updateResults(close.direct_deposits_usd ?? 0) }} USD</span>
                  <span class="currency-badge iqd">{{ updateResults(close.direct_deposits_iqd ?? 0) }} IQD</span>
                </td>
                <td>
                  <span class="currency-badge usd">{{ updateResults(close.total_expenses_usd ?? 0) }} USD</span>
                  <span class="currency-badge iqd">{{ updateResults(close.total_expenses_iqd ?? 0) }} IQD</span>
                </td>
                <td>
                  <span class="currency-badge usd">{{ updateResults(close.opening_balance_usd ?? 0) }} USD</span>
                  <span class="currency-badge iqd">{{ updateResults(close.opening_balance_iqd ?? 0) }} IQD</span>
                </td>
                <td>
                  <span class="currency-badge usd">{{ updateResults(close.closing_balance_usd ?? 0) }} USD</span>
                  <span class="currency-badge iqd">{{ updateResults(close.closing_balance_iqd ?? 0) }} IQD</span>
                </td>
                <td>{{ close.total_orders ?? 0 }}</td>
                <td>
                  <button @click="viewCloseDetails('monthly', close)" class="btn btn-sm btn-info">
                    <i class="bi bi-eye"></i> التفاصيل
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          <Pagination v-if="monthlyClosesList.links" :links="monthlyClosesList.links" @page-changed="loadClosesList" />
        </div>
        <div v-else class="alert alert-info">لا توجد إغلاقات شهرية</div>
      </div>
    </div>

    <!-- Modals -->
    <ModalUploader
      :show="showModalUploader"
            :formData="tranId"
            @a="UpdatePage($event)"
            @close="showModalUploader = false"
            >
          <template #header>
        <h2 class="modal-title">
          <i class="bi bi-cloud-upload"></i>
            تحميل ملفات
          </h2>
          </template>
    </ModalUploader>

    <ModalConvertDollarDinar 
      :show="showModalConvertDollarDinar"
            :boxes="boxes"
            :exchangeRate="exchangeRate"
            @a="confirmConvertDollarDinar($event)"
            @close="showModalConvertDollarDinar = false"
            >
          <template #header>
        <h3 class="modal-title">
          <i class="bi bi-arrow-left-right"></i>
          تحويل من الدولار إلى دينار
        </h3>
           </template>
      </ModalConvertDollarDinar>

      <ModalConvertDinarDollar 
      :show="showModalConvertDinarDollar"
            :boxes="boxes"
            :exchangeRate="exchangeRate"
      @a="confirmConvertDinarDollar($event)"
            @close="showModalConvertDinarDollar = false"
            >
          <template #header>
        <h3 class="modal-title">
          <i class="bi bi-arrow-right-left"></i>
          تحويل من الدينار إلى دولار
        </h3>
           </template>
      </ModalConvertDinarDollar>

      <ModalAddToBox
      :show="showModalAddToBox"
            :data="users"
            :accounts="accounts"
      @a="refresh(); showModalAddToBox = false"
            @close="showModalAddToBox = false"
            >
          <template #header>
        <h3 class="modal-title">
          <i class="bi bi-plus-circle"></i>
          وصل قبض - إضافة للصندوق
        </h3>
           </template>
      </ModalAddToBox>

      <ModalDropFromBox 
      :show="showModalDropFromBox"
            :boxes="boxes"
      @a="refresh(); showModalDropFromBox = false"
            @close="showModalDropFromBox = false"
            >
          <template #header>
        <h2 class="modal-title">
          <i class="bi bi-dash-circle"></i>
          وصل سحب - سحب من الصندوق
        </h2>
           </template>
      </ModalDropFromBox>

    <!-- Images Modal -->
    <div v-if="showImagesModal" class="images-modal-overlay" @click="showImagesModal = false">
      <div class="images-modal-content" @click.stop>
        <div class="images-modal-header">
          <h4>المرفقات</h4>
          <button @click="showImagesModal = false" class="close-btn">
            <i class="bi bi-x"></i>
          </button>
        </div>
        <div class="images-grid">
          <a
            v-for="(image, index) in selectedImages"
            :key="index"
            :href="getDownloadUrl(image.name)"
            target="_blank"
            class="image-item"
          >
            <img :src="getImageUrl(image.name)" :alt="`صورة ${index + 1}`" />
            <div class="image-overlay">
              <i class="bi bi-download"></i>
              <span>تحميل</span>
            </div>
          </a>
        </div>
      </div>
    </div>
    </div><!-- إغلاق div dir="rtl" -->
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Link, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { router } from '@inertiajs/vue3';
import { reactive, ref, computed } from 'vue';
import ModalConvertDollarDinar from "@/Components/ModalConvertDollarDinar.vue";
import ModalConvertDinarDollar from "@/Components/ModalConvertDinarDollar.vue";
import ModalAddToBox from "@/Components/ModalAddToBox.vue";
import ModalDropFromBox from "@/Components/ModalDropFromBox.vue";
import print from "@/Components/icon/print.vue";
import imags from "@/Components/icon/imags.vue";
import trash from "@/Components/icon/trash.vue";
import ModalUploader from "@/Components/ModalUploader.vue";
import { useToast } from "vue-toastification";
import axios from 'axios';

const toast = useToast();

// Refs
let showModalConvertDinarDollar = ref(false);
let showModalConvertDollarDinar = ref(false);
let showModalAddToBox = ref(false);
let showModalDropFromBox = ref(false);
let showModalUploader = ref(false);
let showImagesModal = ref(false);
let tranId = ref(0);
let selectedImages = ref([]);
let loading = ref(false);
let activeTab = ref('transactions');
let loadingCloses = ref(false);
let dailyClosesList = ref(null);
let monthlyClosesList = ref(null);
let closesFilters = reactive({
  type: 'all',
  status: 'all',
  start_date: '',
  end_date: '',
  year: '',
  month: ''
});
 
const props = defineProps({
  boxes: Object, 
  transactions: Object,
  translations: Object,
  mainBox: Object,
  exchangeRate: {
    type: Number,
    default: 1500
  },
  dailyClose: Object,
  monthlyClose: Object
});

const page = usePage();

const filterForm = reactive({
  name: '',
  note: '',
  start_date: getTodayDate(),
  end_date: getTodayDate()
});

// Methods
function openConvertDollarDinar() {
  showModalConvertDollarDinar.value = true;
}

function openConvertDinarDollar() {
  showModalConvertDinarDollar.value = true;
}

function openAddSales() {
  showModalAddToBox.value = true;
}

function openAddExpenses() {
  showModalDropFromBox.value = true;
}

function UpdatePage() {
  refresh();
}

function refresh() {
  loading.value = true;
  axios.get('api/boxes/transactions')
 .then(response => {
      router.reload({ only: ['transactions', 'mainBox', 'dailyClose', 'monthlyClose'] });
      toast.success("تم تحديث البيانات بنجاح", {
        timeout: 2000,
        position: "bottom-right",
        rtl: true
      });
 })
 .catch(error => {
  console.log(error);
      toast.error("حدث خطأ أثناء التحديث", {
        timeout: 3000,
        position: "bottom-right",
        rtl: true
      });
 }) 
    .finally(() => {
      loading.value = false;
    });
}

function confirmConvertDollarDinar(V) {
  loading.value = true;
  axios.post('/api/convertDollarDinar', V)
  .then(response => {
    refresh();
      showModalConvertDollarDinar.value = false;
      toast.success("تم التحويل بنجاح", {
        timeout: 3000,
        position: "bottom-right",
        rtl: true
      });
  })
  .catch(error => {
      toast.error("حدث خطأ أثناء التحويل", {
        timeout: 3000,
        position: "bottom-right",
        rtl: true
      });
    })
    .finally(() => {
      loading.value = false;
    });
}

function confirmConvertDinarDollar(V) {
  loading.value = true;
  axios.post('/api/convertDinarDollar', V)
  .then(response => {
    refresh();
      showModalConvertDinarDollar.value = false;
      toast.success("تم التحويل بنجاح", {
        timeout: 3000,
        position: "bottom-right",
        rtl: true
      });
  })
  .catch(error => {
      toast.error("حدث خطأ أثناء التحويل", {
        timeout: 3000,
        position: "bottom-right",
        rtl: true
      });
    })
    .finally(() => {
      loading.value = false;
    });
}

const Filter = () => {
  loading.value = true;
  router.get(
    route('boxes.index'),
    filterForm,
    { 
      preserveState: true, 
      preserveScroll: true,
      onFinish: () => {
        loading.value = false;
      }
    }
  );
};

const clearFilters = () => {
  filterForm.name = '';
  filterForm.note = '';
  filterForm.start_date = getTodayDate();
  filterForm.end_date = getTodayDate();
  Filter();
};

const hasPermission = (permission) => {
  return page.props.auth_permissions.includes(permission);
};

function updateResults(input) {
  if (typeof input !== 'number') {
    input = parseFloat(input) || 0;
  }
  return Math.round(input).toLocaleString();
}

const Delete = (id, amount) => {
  Swal.fire({
    title: 'تأكيد الحذف',
    html: `هل أنت متأكد من حذف هذه المعاملة؟<br><strong>المبلغ: ${updateResults(amount)}</strong>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'نعم، احذف',
    cancelButtonText: 'إلغاء',
  }).then((result) => {
    if (result.isConfirmed) {
      loading.value = true;
      axios.post('api/delTransactions', { id: id })
      .then(response => {
          router.reload({ only: ['transactions', 'mainBox'] });
          toast.success("تم حذف المعاملة بنجاح", {
            timeout: 2000,
            position: "bottom-right",
            rtl: true
          });
      })
      .catch(error => {
          toast.error("حدث خطأ أثناء الحذف", {
            timeout: 3000,
            position: "bottom-right",
            rtl: true
          });
        })
        .finally(() => {
          loading.value = false;
        });
    }
  });
};

function getTodayDate() {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}
 
const formatDate = (date) => {
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  const hours = String(d.getHours()).padStart(2, '0');
  const minutes = String(d.getMinutes()).padStart(2, '0');
  return `${year}-${month}-${day} ${hours}:${minutes}`;
};

function openModalUploader(tran) {
  tranId.value = tran;
  showModalUploader.value = true;
}

function viewImages(tran) {
  selectedImages.value = tran.transactions_images || [];
  showImagesModal.value = true;
}

function getImageUrl(imageName) {
  return `/storage/transactions/${imageName}`;
}

function getDownloadUrl(imageName) {
  return `/storage/transactions/${imageName}`;
}

function getRowClass(tran) {
  if (tran.type === 'outUser' || tran.type === 'out') {
    return 'row-out';
  } else if (tran.type === 'inUser' || tran.type === 'in') {
    return 'row-in';
  }
  return '';
}

// Computed - حساب منفصل لكل عملة
const calculateTotalInUSD = () => {
  if (!props.transactions.data) return 0;
  const total = props.transactions.data
    .filter(t => (t.type === 'in' || t.type === 'inUser') && (t.currency === 'USD' || t.currency === '$'))
    .reduce((sum, t) => sum + (parseFloat(t.amount) || 0), 0);
  return updateResults(total);
};

const calculateTotalInIQD = () => {
  if (!props.transactions.data) return 0;
  const total = props.transactions.data
    .filter(t => (t.type === 'in' || t.type === 'inUser') && t.currency === 'IQD')
    .reduce((sum, t) => sum + (parseFloat(t.amount) || 0), 0);
  return updateResults(total);
};

const calculateTotalOutUSD = () => {
  if (!props.transactions.data) return 0;
  const total = props.transactions.data
    .filter(t => (t.type === 'out' || t.type === 'outUser') && (t.currency === 'USD' || t.currency === '$'))
    .reduce((sum, t) => sum + Math.abs(parseFloat(t.amount) || 0), 0);
  return updateResults(total);
};

const calculateTotalOutIQD = () => {
  if (!props.transactions.data) return 0;
  const total = props.transactions.data
    .filter(t => (t.type === 'out' || t.type === 'outUser') && t.currency === 'IQD')
    .reduce((sum, t) => sum + Math.abs(parseFloat(t.amount) || 0), 0);
  return updateResults(total);
};

// Close Daily Function
const handleCloseDaily = async () => {
  // First, recalculate daily close data to ensure accuracy
  loading.value = true;
  try {
    const date = props.dailyClose?.close_date || getTodayDate();
    const response = await axios.get(`/boxes/daily-close?date=${date}`);
    const updatedDailyClose = response.data;
    
    Swal.fire({
      title: 'تأكيد إغلاق اليوم',
      html: `
        <p>هل أنت متأكد من إغلاق اليوم؟</p>
        <div style="text-align: right; margin-top: 1rem; font-family: Arial, sans-serif;">
          <div style="margin-bottom: 1rem;">
            <p style="margin-bottom: 0.5rem; font-weight: bold; color: #495057;">المبيعات:</p>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
              <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">${updateResults(updatedDailyClose?.total_sales_usd ?? 0)} USD</span>
              <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">${updateResults(updatedDailyClose?.total_sales_iqd ?? 0)} IQD</span>
            </div>
          </div>
          <div style="margin-bottom: 1rem;">
            <p style="margin-bottom: 0.5rem; font-weight: bold; color: #495057;">إضافة مباشرة:</p>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
              <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">${updateResults(updatedDailyClose?.direct_deposits_usd ?? 0)} USD</span>
              <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">${updateResults(updatedDailyClose?.direct_deposits_iqd ?? 0)} IQD</span>
            </div>
          </div>
          <div style="margin-bottom: 1rem;">
            <p style="margin-bottom: 0.5rem; font-weight: bold; color: #495057;">المصاريف:</p>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
              <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">${updateResults(updatedDailyClose?.total_expenses_usd ?? 0)} USD</span>
              <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">${updateResults(updatedDailyClose?.total_expenses_iqd ?? 0)} IQD</span>
            </div>
          </div>
          <div style="margin-bottom: 1rem;">
            <p style="margin-bottom: 0.5rem; font-weight: bold; color: #495057;">الرصيد الافتتاحي:</p>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
              <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">${updateResults(updatedDailyClose?.opening_balance_usd ?? 0)} USD</span>
              <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">${updateResults(updatedDailyClose?.opening_balance_iqd ?? 0)} IQD</span>
            </div>
          </div>
          <div style="margin-bottom: 1rem;">
            <p style="margin-bottom: 0.5rem; font-weight: bold; color: #495057;">الرصيد الختامي:</p>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
              <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">${updateResults(updatedDailyClose?.closing_balance_usd ?? 0)} USD</span>
              <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">${updateResults(updatedDailyClose?.closing_balance_iqd ?? 0)} IQD</span>
            </div>
          </div>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#667eea',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'نعم، إغلاق',
      cancelButtonText: 'إلغاء',
      input: 'textarea',
      inputPlaceholder: 'ملاحظات (اختياري)',
      inputAttributes: {
        'aria-label': 'ملاحظات'
      }
    }).then((result) => {
      if (result.isConfirmed) {
        loading.value = true;
        axios.post('/boxes/close-daily', {
          date: date,
          notes: result.value || ''
        })
      .then(response => {
        if (response.data.success) {
          toast.success(response.data.message || 'تم إغلاق اليوم بنجاح', {
            timeout: 3000,
            position: "bottom-right",
            rtl: true
          });
          router.reload({ only: ['dailyClose', 'monthlyClose', 'mainBox'] });
        } else {
          toast.error(response.data.message || 'حدث خطأ', {
            timeout: 3000,
            position: "bottom-right",
            rtl: true
          });
        }
      })
      .catch(error => {
        toast.error(error.response?.data?.message || 'حدث خطأ أثناء إغلاق اليوم', {
          timeout: 3000,
          position: "bottom-right",
          rtl: true
        });
      })
      .finally(() => {
        loading.value = false;
      });
    }
  });
  } catch (error) {
    console.error('Error fetching daily close data:', error);
    toast.error('حدث خطأ أثناء جلب بيانات الإغلاق', {
      timeout: 3000,
      position: "bottom-right",
      rtl: true
    });
    loading.value = false;
  }
};

// Close Monthly Function
const handleCloseMonthly = () => {
  Swal.fire({
    title: 'تأكيد إغلاق الشهر',
    html: `
      <p>هل أنت متأكد من إغلاق الشهر الحالي؟</p>
      <div style="text-align: right; margin-top: 1rem;">
        <p><strong>المبيعات:</strong> ${updateResults(props.monthlyClose?.total_sales_usd ?? 0)} USD / ${updateResults(props.monthlyClose?.total_sales_iqd ?? 0)} IQD</p>
        <p><strong>المصاريف:</strong> ${updateResults(props.monthlyClose?.total_expenses_usd ?? 0)} USD / ${updateResults(props.monthlyClose?.total_expenses_iqd ?? 0)} IQD</p>
        <p><strong>الرصيد الختامي:</strong> ${updateResults(props.monthlyClose?.closing_balance_usd ?? 0)} USD / ${updateResults(props.monthlyClose?.closing_balance_iqd ?? 0)} IQD</p>
      </div>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#667eea',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'نعم، إغلاق',
    cancelButtonText: 'إلغاء',
    input: 'textarea',
    inputPlaceholder: 'ملاحظات (اختياري)',
    inputAttributes: {
      'aria-label': 'ملاحظات'
    }
  }).then((result) => {
    if (result.isConfirmed) {
      loading.value = true;
      axios.post('/boxes/close-monthly', {
        year: props.monthlyClose?.year || new Date().getFullYear(),
        month: props.monthlyClose?.month || new Date().getMonth() + 1,
        notes: result.value || ''
      })
      .then(response => {
        if (response.data.success) {
          toast.success(response.data.message || 'تم إغلاق الشهر بنجاح', {
            timeout: 3000,
            position: "bottom-right",
            rtl: true
          });
          router.reload({ only: ['dailyClose', 'monthlyClose', 'mainBox'] });
        } else {
          toast.error(response.data.message || 'حدث خطأ', {
            timeout: 3000,
            position: "bottom-right",
            rtl: true
          });
        }
      })
      .catch(error => {
        toast.error(error.response?.data?.message || 'حدث خطأ أثناء إغلاق الشهر', {
          timeout: 3000,
          position: "bottom-right",
          rtl: true
        });
      })
      .finally(() => {
        loading.value = false;
      });
    }
  });
};

// Load Closes List
const loadClosesList = async () => {
  loadingCloses.value = true;
  try {
    const params = new URLSearchParams();
    if (closesFilters.type) params.append('type', closesFilters.type);
    if (closesFilters.status) params.append('status', closesFilters.status);
    if (closesFilters.start_date) params.append('start_date', closesFilters.start_date);
    if (closesFilters.end_date) params.append('end_date', closesFilters.end_date);
    if (closesFilters.year) params.append('year', closesFilters.year);
    if (closesFilters.month) params.append('month', closesFilters.month);
    
    const response = await axios.get(`/boxes/closes-list?${params.toString()}`);
    dailyClosesList.value = response.data.daily_closes;
    monthlyClosesList.value = response.data.monthly_closes;
  } catch (error) {
    console.error('Error loading closes list:', error);
    toast.error('حدث خطأ أثناء جلب قائمة الإغلاقات', {
      timeout: 3000,
      position: "bottom-right",
      rtl: true
    });
  } finally {
    loadingCloses.value = false;
  }
};

// Clear Closes Filters
const clearClosesFilters = () => {
  closesFilters.type = 'all';
  closesFilters.status = 'all';
  closesFilters.start_date = '';
  closesFilters.end_date = '';
  closesFilters.year = '';
  closesFilters.month = '';
  loadClosesList();
};

// View Close Details
const viewCloseDetails = (type, close) => {
  const details = type === 'daily' 
    ? `
      <div style="text-align: right; font-family: Arial, sans-serif;">
        <p><strong>التاريخ:</strong> ${formatDate(close.close_date)}</p>
        <p><strong>الحالة:</strong> ${close.status === 'closed' ? 'مغلق' : 'مفتوح'}</p>
        <p><strong>المبيعات:</strong> 
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin: 0 0.25rem;">${updateResults(close.total_sales_usd ?? 0)} USD</span>
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">${updateResults(close.total_sales_iqd ?? 0)} IQD</span>
        </p>
        <p><strong>إضافة مباشرة:</strong> 
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin: 0 0.25rem;">${updateResults(close.direct_deposits_usd ?? 0)} USD</span>
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">${updateResults(close.direct_deposits_iqd ?? 0)} IQD</span>
        </p>
        <p><strong>المصاريف:</strong> 
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin: 0 0.25rem;">${updateResults(close.total_expenses_usd ?? 0)} USD</span>
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">${updateResults(close.total_expenses_iqd ?? 0)} IQD</span>
        </p>
        <p><strong>الرصيد الافتتاحي:</strong> 
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin: 0 0.25rem;">${updateResults(close.opening_balance_usd ?? 0)} USD</span>
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">${updateResults(close.opening_balance_iqd ?? 0)} IQD</span>
        </p>
        <p><strong>الرصيد الختامي:</strong> 
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin: 0 0.25rem;">${updateResults(close.closing_balance_usd ?? 0)} USD</span>
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">${updateResults(close.closing_balance_iqd ?? 0)} IQD</span>
        </p>
        <p><strong>عدد الطلبات:</strong> ${close.total_orders ?? 0}</p>
        ${close.notes ? `<p><strong>ملاحظات:</strong> ${close.notes}</p>` : ''}
      </div>
    `
    : `
      <div style="text-align: right; font-family: Arial, sans-serif;">
        <p><strong>الشهر:</strong> ${getMonthName(close.month)} ${close.year}</p>
        <p><strong>الحالة:</strong> ${close.status === 'closed' ? 'مغلق' : 'مفتوح'}</p>
        <p><strong>المبيعات:</strong> 
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin: 0 0.25rem;">${updateResults(close.total_sales_usd ?? 0)} USD</span>
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">${updateResults(close.total_sales_iqd ?? 0)} IQD</span>
        </p>
        <p><strong>إضافة مباشرة:</strong> 
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin: 0 0.25rem;">${updateResults(close.direct_deposits_usd ?? 0)} USD</span>
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">${updateResults(close.direct_deposits_iqd ?? 0)} IQD</span>
        </p>
        <p><strong>المصاريف:</strong> 
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin: 0 0.25rem;">${updateResults(close.total_expenses_usd ?? 0)} USD</span>
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">${updateResults(close.total_expenses_iqd ?? 0)} IQD</span>
        </p>
        <p><strong>الرصيد الافتتاحي:</strong> 
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin: 0 0.25rem;">${updateResults(close.opening_balance_usd ?? 0)} USD</span>
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">${updateResults(close.opening_balance_iqd ?? 0)} IQD</span>
        </p>
        <p><strong>الرصيد الختامي:</strong> 
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin: 0 0.25rem;">${updateResults(close.closing_balance_usd ?? 0)} USD</span>
          <span style="display: inline-block; padding: 0.3rem 0.6rem; border-radius: 6px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">${updateResults(close.closing_balance_iqd ?? 0)} IQD</span>
        </p>
        <p><strong>عدد الطلبات:</strong> ${close.total_orders ?? 0}</p>
        ${close.notes ? `<p><strong>ملاحظات:</strong> ${close.notes}</p>` : ''}
      </div>
    `;
  
  Swal.fire({
    title: type === 'daily' ? 'تفاصيل الإغلاق اليومي' : 'تفاصيل الإغلاق الشهري',
    html: details,
    icon: 'info',
    confirmButtonText: 'إغلاق'
  });
};

// Get Month Name
const getMonthName = (month) => {
  const months = {
    1: 'يناير', 2: 'فبراير', 3: 'مارس', 4: 'أبريل',
    5: 'مايو', 6: 'يونيو', 7: 'يوليو', 8: 'أغسطس',
    9: 'سبتمبر', 10: 'أكتوبر', 11: 'نوفمبر', 12: 'ديسمبر'
  };
  return months[month] || month;
};
</script>

<style scoped>
/* RTL Support - دعم العربية */
* {
  direction: rtl;
  text-align: right;
}

/* Page Header */
.page-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1.5rem 2rem;
  margin-bottom: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  direction: rtl;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-title i {
  font-size: 2rem;
}

.header-title h1 {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 600;
}

/* Statistics Section */
.statistics-section {
  margin-bottom: 1.5rem;
  direction: rtl;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
  direction: rtl;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: white;
}

.balance-usd .stat-icon {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.balance-iqd .stat-icon {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.total-in .stat-icon {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.total-out .stat-icon {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-details {
  flex: 1;
}

.stat-details h6 {
  margin: 0 0 0.5rem 0;
  font-size: 0.875rem;
  color: #6c757d;
  font-weight: 500;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #2c3e50;
  display: block;
  margin-bottom: 0.25rem;
}

.stat-value-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.stat-value-currency {
  font-size: 1.25rem;
  font-weight: 700;
  color: #2c3e50;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.stat-value-currency:first-child {
  font-size: 1.5rem;
}

.stat-details small {
  font-size: 0.75rem;
  color: #95a5a6;
}

/* Main Card */
.main-card {
  border: none;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.card-header-custom {
  background: #f8f9fa;
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  direction: rtl;
}

.actions-wrapper {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
  direction: rtl;
}

.action-group {
  display: flex;
  gap: 0.75rem;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.action-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.action-btn.success {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
  color: white;
}

.action-btn.success:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(67, 233, 123, 0.4);
}

.action-btn.danger {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
}

.action-btn.danger:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(240, 147, 251, 0.4);
}

.action-btn.warning {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  color: white;
}

.action-btn.warning:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(250, 112, 154, 0.4);
}

.action-btn.info {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.action-btn.info:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.action-btn i {
  font-size: 1.1rem;
}

.action-btn i.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.exchange-rate-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: white;
  border-radius: 8px;
  border: 2px solid #e9ecef;
  font-size: 0.9rem;
  color: #495057;
}

.exchange-rate-info i {
  color: #667eea;
  font-size: 1.1rem;
}

.exchange-rate-info strong {
  color: #667eea;
  font-weight: 600;
}

/* Filters Section */
.filters-section {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  direction: rtl;
}

.filter-form {
  width: 100%;
  direction: rtl;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  align-items: end;
  direction: rtl;
}

.filter-item {
  display: flex;
  flex-direction: column;
}

.filter-item label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #495057;
  margin-bottom: 0.5rem;
}

.filter-item label i {
  color: #667eea;
}

.filter-item .form-control {
  border-radius: 6px;
  border: 1px solid #ced4da;
  padding: 0.5rem 0.75rem;
}

.filter-item .form-control:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.filter-actions {
  display: flex;
  gap: 0.5rem;
}

.filter-actions .btn {
  border-radius: 6px;
  padding: 0.5rem 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Loading Overlay */
.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  border-radius: 8px;
}

.table-container {
  position: relative;
}

/* Table Styles */
.transactions-table {
  margin-bottom: 0;
}

.transactions-table thead {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.transactions-table thead th {
  border: none;
  padding: 1rem;
  font-weight: 600;
  font-size: 0.9rem;
  text-align: center;
  vertical-align: middle;
  direction: rtl;
}

.transactions-table thead th i {
  margin-right: 0.5rem;
}

.transaction-row {
  transition: all 0.2s ease;
}

.transaction-row:hover {
  background: #f8f9fa;
  transform: scale(1.01);
}

.transaction-row.row-in {
  border-right: 4px solid #43e97b;
  background: rgba(67, 233, 123, 0.05);
}

.transaction-row.row-out {
  border-right: 4px solid #f5576c;
  background: rgba(245, 87, 108, 0.05);
}

.transactions-table tbody td {
  vertical-align: middle;
  padding: 1rem;
  text-align: center;
  direction: rtl;
}

.index-cell .index-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #667eea;
  color: white;
  font-weight: 600;
  font-size: 0.875rem;
}

.account-cell .account-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  justify-content: center;
}

.account-cell .account-info i {
  color: #667eea;
  font-size: 1.1rem;
}

.amount-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.9rem;
}

.amount-badge.in {
  background: rgba(67, 233, 123, 0.15);
  color: #2d8659;
}

.amount-badge.out {
  background: rgba(245, 87, 108, 0.15);
  color: #c23848;
}

.description-cell {
  max-width: 250px;
}

.description-text {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.date-cell .date-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  justify-content: center;
  font-size: 0.875rem;
}

.date-cell .date-info i {
  color: #6c757d;
}

.actions-cell {
  width: 200px;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  align-items: center;
}

.action-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.action-icon:hover {
  transform: translateY(-2px);
}

.action-icon.delete {
  background: #f5576c;
  color: white;
}

.action-icon.delete:hover {
  background: #e04656;
}

.action-icon.upload {
  background: #9b59b6;
  color: white;
}

.action-icon.upload:hover {
  background: #8e44ad;
}

.action-icon.print {
  background: #43e97b;
  color: white;
}

.action-icon.print:hover {
  background: #38d96a;
}

.action-icon.view {
  background: #667eea;
  color: white;
}

.action-icon.view:hover {
  background: #5568d3;
}

.action-icon .badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #f5576c;
  color: white;
  border-radius: 10px;
  padding: 2px 6px;
  font-size: 0.7rem;
  font-weight: 600;
}

.empty-state {
  padding: 3rem !important;
}

.empty-content {
  text-align: center;
  color: #6c757d;
}

.empty-content i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-content p {
  margin: 0;
  font-size: 1.1rem;
}

/* Images Modal */
.images-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.images-modal-content {
  background: white;
  border-radius: 12px;
  max-width: 900px;
  width: 100%;
  max-height: 80vh;
  overflow: auto;
}

.images-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
}

.images-modal-header h4 {
  margin: 0;
  color: #2c3e50;
}

.close-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: #e9ecef;
}

.close-btn i {
  font-size: 1.5rem;
}

.images-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
  padding: 1.5rem;
}

.image-item {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  aspect-ratio: 1;
  cursor: pointer;
}

.image-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.image-item:hover .image-overlay {
  opacity: 1;
}

.image-overlay i {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

/* Modal Title */
.modal-title {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-title i {
  color: #667eea;
}

/* Responsive */
@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .actions-wrapper {
    flex-direction: column;
  }
  
  .action-group {
    width: 100%;
  }
  
  .action-btn {
    flex: 1;
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .filter-grid {
    grid-template-columns: 1fr;
  }
  
  .action-buttons {
    flex-wrap: wrap;
  }
  
  .page-header {
    padding: 1rem;
  }
  
  .header-title h1 {
    font-size: 1.25rem;
  }
}

/* Tabs Navigation */
.tabs-navigation {
  display: flex;
  gap: 0.5rem;
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-bottom: 2px solid #e9ecef;
  direction: rtl;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  background: transparent;
  color: #6c757d;
  font-weight: 500;
  font-size: 0.95rem;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  transition: all 0.3s ease;
  position: relative;
}

.tab-btn:hover {
  color: #667eea;
  background: rgba(102, 126, 234, 0.05);
}

.tab-btn.active {
  color: #667eea;
  border-bottom-color: #667eea;
  background: rgba(102, 126, 234, 0.1);
}

.tab-btn i {
  font-size: 1.1rem;
}

.tab-content {
  padding: 0;
}

/* Close Section */
.close-section {
  padding: 1.5rem;
  direction: rtl;
}

.close-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  direction: rtl;
}

.close-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.close-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
}

.daily-close {
  border-right: 4px solid #43e97b;
}

.monthly-close {
  border-right: 4px solid #667eea;
}

.close-card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1.25rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.daily-close .close-card-header {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.monthly-close .close-card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.close-card-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.close-card-title i {
  font-size: 1.5rem;
}

.close-card-title h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.close-status {
  padding: 0.4rem 1rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.2);
}

.close-status.open {
  background: rgba(255, 255, 255, 0.3);
}

.close-status.closed {
  background: rgba(255, 255, 255, 0.9);
  color: #667eea;
}

.close-card-body {
  padding: 1.5rem;
}

.close-info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f0f0f0;
}

.close-info-row:last-child {
  border-bottom: none;
}

.close-info-row.highlight {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  margin-top: 0.5rem;
  border: 2px solid #e9ecef;
}

.close-label {
  font-weight: 600;
  color: #495057;
  font-size: 0.9rem;
}

.close-value {
  font-weight: 700;
  color: #2c3e50;
  font-size: 0.95rem;
  text-align: left;
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.currency-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.85rem;
  white-space: nowrap;
}

.currency-badge.usd {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.currency-badge.iqd {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
}

.close-card-footer {
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: center;
}

.btn-close-action {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.daily-close .btn-close-action {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.btn-close-action:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-close-action:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.closed-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #e9ecef;
  color: #6c757d;
  border-radius: 8px;
  font-size: 0.9rem;
}

.closed-badge i {
  color: #43e97b;
}

@media (max-width: 1200px) {
  .close-grid {
    grid-template-columns: 1fr;
  }
}

@media print {
  .page-header,
  .statistics-section,
  .card-header-custom,
  .filters-section,
  .actions-cell,
  .close-section {
    display: none;
  }
}
</style>
