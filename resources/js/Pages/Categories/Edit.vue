<template>
  <AuthenticatedLayout :translations="translations">
    <div dir="rtl" lang="ar">
      <section class="section dashboard">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="bi bi-pencil me-2"></i>
              تعديل التصنيف
            </h5>
          </div>

          <div class="card-body">
            <form @submit.prevent="submit">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">اسم التصنيف <span class="text-danger">*</span></label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.name"
                    :class="{ 'is-invalid': form.errors.name }"
                    required
                  />
                  <div v-if="form.errors.name" class="invalid-feedback">
                    {{ form.errors.name }}
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">الترتيب</label>
                  <input 
                    type="number" 
                    class="form-control" 
                    v-model.number="form.sort_order"
                    min="0"
                  />
                  <small class="text-muted">كلما كان الرقم أصغر، ظهر التصنيف أولاً</small>
                </div>

                <div class="col-md-12 mb-3">
                  <label class="form-label">الوصف</label>
                  <textarea 
                    class="form-control" 
                    v-model="form.description"
                    rows="3"
                  ></textarea>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">اللون</label>
                  <div class="input-group">
                    <input 
                      type="color" 
                      class="form-control form-control-color" 
                      v-model="form.color"
                      title="اختر لون التصنيف"
                    />
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.color"
                      placeholder="#667eea"
                    />
                  </div>
                  <small class="text-muted">لون التصنيف في صفحة المبيعات</small>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">الأيقونة</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.icon"
                    placeholder="bi bi-tag"
                  />
                  <small class="text-muted">اسم أيقونة Bootstrap Icons (مثال: bi bi-tag)</small>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">الحالة</label>
                  <div class="form-check form-switch">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      v-model="form.is_active"
                      id="is_active"
                    />
                    <label class="form-check-label" for="is_active">
                      {{ form.is_active ? 'نشط' : 'غير نشط' }}
                    </label>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-end gap-2 mt-4">
                <Link :href="route('categories.index')" class="btn btn-secondary">
                  إلغاء
                </Link>
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                  <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                  حفظ التغييرات
                </button>
              </div>
            </form>
          </div>
        </div>
      </section>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  category: Object,
  translations: Object,
});

const form = useForm({
  name: props.category.name,
  description: props.category.description || '',
  color: props.category.color || '#667eea',
  icon: props.category.icon || 'bi bi-tag',
  sort_order: props.category.sort_order || 0,
  is_active: props.category.is_active !== undefined ? props.category.is_active : true,
});

const submit = () => {
  form.put(route('categories.update', props.category.id));
};
</script>

