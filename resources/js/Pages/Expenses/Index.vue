<template>
  <AuthenticatedLayout :translations="translations">
    <!-- breadcrumb-->
    <div class="pagetitle dark:text-white">
      <h1 class="dark:text-white">إدارة المصاريف</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link dark:text-white" :href="route('dashboard')">
              {{ translations.Home }}
            </Link>
          </li>
          <li class="breadcrumb-item active dark:text-white">المصاريف</li>
        </ol>
      </nav>
    </div>
    <!-- End breadcrumb-->

    <section class="section dashboard">
      <!-- Statistics Cards -->
      <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
          <div class="card info-card revenue-card">
            <div class="card-body">
              <h5 class="card-title">إجمالي المصاريف</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ formatPrice(statistics?.total || 0) }}</h6>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card info-card sales-card">
            <div class="card-body">
              <h5 class="card-title">مصاريف هذا الشهر</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-calendar-month"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ formatPrice(statistics?.monthly || 0) }}</h6>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card info-card customers-card">
            <div class="card-body">
              <h5 class="card-title">مصاريف اليوم</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-calendar-day"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ formatPrice(statistics?.today || 0) }}</h6>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card info-card revenue-card">
            <div class="card-body">
              <h5 class="card-title">عدد المصاريف</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-list-ul"></i>
                </div>
                <div class="ps-3">
                  <h6>{{ expenses?.total || 0 }}</h6>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Card -->
      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="bi bi-receipt me-2"></i>
              قائمة المصاريف
            </h5>
            <Link :href="route('expenses.create')" class="btn btn-primary">
              <i class="bi bi-plus-circle me-1"></i>
              إضافة مصروف جديد
            </Link>
          </div>
        </div>

        <div class="card-body">
          <!-- Filters -->
          <div class="row mb-3">
            <div class="col-md-3">
              <label class="form-label">الفئة</label>
              <select v-model="filters.category" @change="applyFilters" class="form-select">
                <option value="">جميع الفئات</option>
                <option v-for="(label, key) in categories" :key="key" :value="key">
                  {{ label }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">من تاريخ</label>
              <input 
                type="date" 
                v-model="filters.start_date" 
                @change="applyFilters" 
                class="form-control"
              >
            </div>
            <div class="col-md-3">
              <label class="form-label">إلى تاريخ</label>
              <input 
                type="date" 
                v-model="filters.end_date" 
                @change="applyFilters" 
                class="form-control"
              >
            </div>
            <div class="col-md-3">
              <label class="form-label">البحث</label>
              <input 
                type="text" 
                v-model="filters.search" 
                @input="applyFilters" 
                placeholder="البحث في العنوان أو الوصف"
                class="form-control"
              >
            </div>
          </div>

          <!-- Expenses Table -->
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">العنوان</th>
                  <th scope="col">الفئة</th>
                  <th scope="col">المبلغ</th>
                  <th scope="col">التاريخ</th>
                  <th scope="col">المنشئ</th>
                  <th scope="col">الإجراءات</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(expense, index) in expenses.data" :key="expense.id">
                  <th scope="row">{{ index + 1 }}</th>
                  <td>
                    <div>
                      <strong>{{ expense.title }}</strong>
                      <div v-if="expense.description" class="text-muted small">
                        {{ expense.description }}
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-primary">{{ categories[expense.category] }}</span>
                  </td>
                  <td>
                    <span class="text-danger fw-bold">{{ formatPrice(expense.amount, expense.currency) }}</span>
                  </td>
                  <td>{{ formatDate(expense.expense_date) }}</td>
                  <td>{{ expense.creator?.name || 'غير محدد' }}</td>
                  <td>
                    <div class="d-flex gap-1">
                      <Link 
                        :href="route('expenses.edit', expense.id)" 
                        class="btn btn-sm btn-outline-primary"
                      >
                        <i class="bi bi-pencil"></i>
                      </Link>
                      <button 
                        @click="deleteExpense(expense.id)" 
                        class="btn btn-sm btn-outline-danger"
                      >
                        <i class="bi bi-trash"></i>
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
                <li v-for="link in expenses.links" :key="link.label" class="page-item" :class="{ 'active': link.active, 'disabled': !link.url }">
                  <Link 
                    v-if="link.url" 
                    :href="link.url" 
                    class="page-link"
                    v-html="link.label"
                  ></Link>
                  <span v-else class="page-link" v-html="link.label"></span>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </section>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
  expenses: Object,
  categories: Object,
  statistics: Object,
  filters: Object,
  translations: Object,
});

// Reactive filters
const filters = ref({
  category: props.filters?.category || '',
  start_date: props.filters?.start_date || '',
  end_date: props.filters?.end_date || '',
  search: props.filters?.search || '',
});

// Apply filters
const applyFilters = () => {
  router.get(route('expenses.index'), filters.value, {
    preserveState: true,
    replace: true,
  });
};

// Format price
const formatPrice = (price, currency = 'IQD') => {
  return parseFloat(price || 0).toFixed(2) + ' ' + currency;
};

// Format date
const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('ar-SA');
};

// Delete expense
const deleteExpense = (expenseId) => {
  if (confirm('هل أنت متأكد من حذف هذا المصروف؟')) {
    router.delete(route('expenses.destroy', expenseId));
  }
};

// Watch for filter changes
watch(filters, () => {
  applyFilters();
}, { deep: true });
</script>
