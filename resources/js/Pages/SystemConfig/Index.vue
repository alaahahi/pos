<template>
  <AuthenticatedLayout :translations="translations">
    <!-- breadcrumb-->
    <div class="pagetitle dark:text-white">
      <h1 class="dark:text-white">{{ translations.system_settings }}</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link dark:text-white" :href="route('dashboard')">
              {{ translations.Home }}
            </Link>
          </li>
          <li class="breadcrumb-item active dark:text-white">{{ translations.system_settings }}</li>
        </ol>
      </nav>
    </div>
    <!-- End breadcrumb-->

    <section class="section dashboard">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="bi bi-gear me-2"></i>
            {{ translations.system_settings }}
          </h5>
        </div>
        <div class="card-body">
          <form @submit.prevent="submit">
            <div class="row">
              <!-- Exchange Rate -->
              <div class="col-md-6">
                <div class="card">
                  <div class="card-header">
                    <h6 class="card-title">سعر الصرف</h6>
                  </div>
                  <div class="card-body">
                    <div class="mb-3">
                      <label for="exchange_rate" class="form-label">سعر صرف الدولار (IQD)</label>
                      <input
                        type="number"
                        step="0.01"
                        class="form-control"
                        id="exchange_rate"
                        v-model="form.exchange_rate"
                        :class="{ 'is-invalid': form.errors.exchange_rate }"
                      />
                      <div v-if="form.errors.exchange_rate" class="invalid-feedback">
                        {{ form.errors.exchange_rate }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Titles -->
              <div class="col-md-6">
                <div class="card">
                  <div class="card-header">
                    <h6 class="card-title">عناوين النظام</h6>
                  </div>
                  <div class="card-body">
                    <div class="mb-3">
                      <label for="first_title_ar" class="form-label">العنوان الأول (عربي)</label>
                      <input
                        type="text"
                        class="form-control"
                        id="first_title_ar"
                        v-model="form.first_title_ar"
                      />
                    </div>
                    <div class="mb-3">
                      <label for="first_title_kr" class="form-label">العنوان الأول (كردي)</label>
                      <input
                        type="text"
                        class="form-control"
                        id="first_title_kr"
                        v-model="form.first_title_kr"
                      />
                    </div>
                    <div class="mb-3">
                      <label for="second_title_ar" class="form-label">العنوان الثاني (عربي)</label>
                      <input
                        type="text"
                        class="form-control"
                        id="second_title_ar"
                        v-model="form.second_title_ar"
                      />
                    </div>
                    <div class="mb-3">
                      <label for="second_title_kr" class="form-label">العنوان الثاني (كردي)</label>
                      <input
                        type="text"
                        class="form-control"
                        id="second_title_kr"
                        v-model="form.second_title_kr"
                      />
                    </div>
                    <div class="mb-3">
                      <label for="third_title_ar" class="form-label">العنوان الثالث (عربي)</label>
                      <input
                        type="text"
                        class="form-control"
                        id="third_title_ar"
                        v-model="form.third_title_ar"
                      />
                    </div>
                    <div class="mb-3">
                      <label for="third_title_kr" class="form-label">العنوان الثالث (كردي)</label>
                      <input
                        type="text"
                        class="form-control"
                        id="third_title_kr"
                        v-model="form.third_title_kr"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-12">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                  <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                  حفظ الإعدادات
                </button>
              </div>
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
  config: Object,
  translations: Object,
});

const form = useForm({
  first_title_ar: props.config?.first_title_ar || '',
  first_title_kr: props.config?.first_title_kr || '',
  second_title_ar: props.config?.second_title_ar || '',
  second_title_kr: props.config?.second_title_kr || '',
  third_title_ar: props.config?.third_title_ar || '',
  third_title_kr: props.config?.third_title_kr || '',
  exchange_rate: props.config?.exchange_rate || 1500,
});

const submit = () => {
  form.put(route('system-config.update'));
};
</script>
