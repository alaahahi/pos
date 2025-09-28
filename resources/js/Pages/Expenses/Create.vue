<template>
  <AuthenticatedLayout :translations="translations">
    <!-- breadcrumb-->
    <div class="pagetitle dark:text-white">
      <h1 class="dark:text-white">إضافة مصروف جديد</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link dark:text-white" :href="route('dashboard')">
              {{ translations.Home }}
            </Link>
          </li>
          <li class="breadcrumb-item">
            <Link class="nav-link dark:text-white" :href="route('expenses.index')">
              المصاريف
            </Link>
          </li>
          <li class="breadcrumb-item active dark:text-white">إضافة جديد</li>
        </ol>
      </nav>
    </div>
    <!-- End breadcrumb-->

    <section class="section dashboard">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="bi bi-plus-circle me-2"></i>
            إضافة مصروف جديد
          </h5>
        </div>
        <div class="card-body">
          <form @submit.prevent="submit">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="title" class="form-label">عنوان المصروف *</label>
                  <input
                    type="text"
                    class="form-control"
                    id="title"
                    v-model="form.title"
                    :class="{ 'is-invalid': form.errors.title }"
                    required
                  />
                  <div v-if="form.errors.title" class="invalid-feedback">
                    {{ form.errors.title }}
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="mb-3">
                  <label for="category" class="form-label">فئة المصروف *</label>
                  <select
                    class="form-select"
                    id="category"
                    v-model="form.category"
                    :class="{ 'is-invalid': form.errors.category }"
                    required
                  >
                    <option value="">اختر الفئة</option>
                    <option v-for="(label, key) in categories" :key="key" :value="key">
                      {{ label }}
                    </option>
                  </select>
                  <div v-if="form.errors.category" class="invalid-feedback">
                    {{ form.errors.category }}
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <div class="mb-3">
                  <label for="amount" class="form-label">المبلغ *</label>
                  <input
                    type="number"
                    step="0.01"
                    min="0.01"
                    class="form-control"
                    id="amount"
                    v-model="form.amount"
                    :class="{ 'is-invalid': form.errors.amount }"
                    required
                  />
                  <div v-if="form.errors.amount" class="invalid-feedback">
                    {{ form.errors.amount }}
                  </div>
                </div>
              </div>

              <div class="col-md-2">
                <div class="mb-3">
                  <label for="currency" class="form-label">العملة *</label>
                  <select
                    class="form-select"
                    id="currency"
                    v-model="form.currency"
                    :class="{ 'is-invalid': form.errors.currency }"
                    required
                  >
                    <option value="">اختر العملة</option>
                    <option v-for="(label, key) in currencies" :key="key" :value="key">
                      {{ label }}
                    </option>
                  </select>
                  <div v-if="form.errors.currency" class="invalid-feedback">
                    {{ form.errors.currency }}
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="mb-3">
                  <label for="expense_date" class="form-label">تاريخ المصروف *</label>
                  <input
                    type="date"
                    class="form-control"
                    id="expense_date"
                    v-model="form.expense_date"
                    :class="{ 'is-invalid': form.errors.expense_date }"
                    required
                  />
                  <div v-if="form.errors.expense_date" class="invalid-feedback">
                    {{ form.errors.expense_date }}
                  </div>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label for="description" class="form-label">وصف المصروف</label>
              <textarea
                class="form-control"
                id="description"
                rows="3"
                v-model="form.description"
                :class="{ 'is-invalid': form.errors.description }"
                placeholder="وصف تفصيلي للمصروف (اختياري)"
              ></textarea>
              <div v-if="form.errors.description" class="invalid-feedback">
                {{ form.errors.description }}
              </div>
            </div>

            <div class="d-flex justify-content-between">
              <Link :href="route('expenses.index')" class="btn btn-secondary">
                <i class="bi bi-arrow-right me-1"></i>
                إلغاء
              </Link>
              <button type="submit" class="btn btn-primary" :disabled="form.processing">
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                <i class="bi bi-check-circle me-1"></i>
                حفظ المصروف
              </button>
            </div>
          </form>
        </div>
      </div>
    </section>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  categories: Object,
  currencies: Object,
  translations: Object,
});

const form = useForm({
  title: '',
  description: '',
  amount: '',
  currency: 'IQD',
  category: '',
  expense_date: new Date().toISOString().split('T')[0],
});

const submit = () => {
  form.post(route('expenses.store'));
};
</script>
