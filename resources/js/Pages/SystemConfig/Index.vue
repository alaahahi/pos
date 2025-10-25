<template>
  <AuthenticatedLayout :translations="translations">
    

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

            <!-- Decoration Types Section -->
            <div class="row mt-3">
              <div class="col-12">
                <div class="card">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                      <i class="bi bi-palette me-2"></i>
                      أنواع الديكورات
                    </h6>
                    <button type="button" class="btn btn-sm btn-success" @click="addDecorationType">
                      <i class="bi bi-plus-circle me-1"></i>
                      إضافة نوع جديد
                    </button>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-hover">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>القيمة (Value)</th>
                            <th>الاسم بالعربية</th>
                            <th>الاسم بالإنجليزية</th>
                            <th>الإجراءات</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(type, index) in form.decoration_types" :key="index">
                            <td>{{ index + 1 }}</td>
                            <td>
                              <input
                                type="text"
                                class="form-control form-control-sm"
                                v-model="type.value"
                                placeholder="birthday"
                                required
                              />
                            </td>
                            <td>
                              <input
                                type="text"
                                class="form-control form-control-sm"
                                v-model="type.label_ar"
                                placeholder="عيد ميلاد"
                                required
                              />
                            </td>
                            <td>
                              <input
                                type="text"
                                class="form-control form-control-sm"
                                v-model="type.label_en"
                                placeholder="Birthday"
                              />
                            </td>
                            <td>
                              <button
                                type="button"
                                class="btn btn-sm btn-danger"
                                @click="removeDecorationType(index)"
                                :disabled="form.decoration_types.length === 1"
                              >
                                <i class="bi bi-trash"></i>
                              </button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                    <small class="text-muted">
                      <i class="bi bi-info-circle me-1"></i>
                      ملاحظة: القيمة (Value) يجب أن تكون بالإنجليزية بدون مسافات (مثال: birthday, wedding)
                    </small>
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
  decoration_types: props.config?.decoration_types || [
    { value: 'birthday', label_ar: 'عيد ميلاد', label_en: 'Birthday' },
    { value: 'gender_reveal', label_ar: 'تحديد جنس المولود', label_en: 'Gender Reveal' },
    { value: 'baby_shower', label_ar: 'حفلة الولادة', label_en: 'Baby Shower' },
    { value: 'wedding', label_ar: 'زفاف', label_en: 'Wedding' },
    { value: 'graduation', label_ar: 'تخرج', label_en: 'Graduation' },
    { value: 'corporate', label_ar: 'شركات', label_en: 'Corporate' },
    { value: 'religious', label_ar: 'ديني', label_en: 'Religious' },
    { value: 'other', label_ar: 'أخرى', label_en: 'Other' },
  ],
});

const addDecorationType = () => {
  form.decoration_types.push({
    value: '',
    label_ar: '',
    label_en: ''
  });
};

const removeDecorationType = (index) => {
  if (form.decoration_types.length > 1) {
    form.decoration_types.splice(index, 1);
  }
};

const submit = () => {
  form.put(route('system-config.update'));
};
</script>
