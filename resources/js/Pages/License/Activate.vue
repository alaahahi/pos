<template>
  <AuthenticatedLayout :translations="translations">
    <section class="section dashboard">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="bi bi-key me-2"></i>
            تفعيل الترخيص
          </h5>
        </div>
        <div class="card-body">
          <!-- معلومات الترخيص الحالي -->
          <div v-if="license.activated" class="alert alert-info mb-4">
            <h6 class="alert-heading">
              <i class="bi bi-info-circle me-2"></i>
              الترخيص مفعل حالياً
            </h6>
            <hr>
            <div class="row">
              <div class="col-md-6">
                <p class="mb-1"><strong>النوع:</strong> {{ license.type }}</p>
                <p class="mb-1" v-if="license.expires_at">
                  <strong>تاريخ الانتهاء:</strong> {{ license.expires_at }}
                </p>
                <p class="mb-1" v-else>
                  <strong>النوع:</strong> ترخيص دائم
                </p>
                <p class="mb-1" v-if="license.days_remaining !== null">
                  <strong>الأيام المتبقية:</strong> {{ license.days_remaining }} يوم
                </p>
              </div>
              <div class="col-md-6">
                <p class="mb-1"><strong>الدومين:</strong> {{ server.domain }}</p>
                <p class="mb-1"><strong>تاريخ التفعيل:</strong> {{ license.activated_at }}</p>
              </div>
            </div>
          </div>

          <!-- رسالة الخطأ -->
          <div v-if="error" class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ error }}
            <button type="button" class="btn-close" @click="error = ''"></button>
          </div>

          <!-- رسالة النجاح -->
          <div v-if="success" class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            {{ success }}
            <button type="button" class="btn-close" @click="success = ''"></button>
          </div>

          <!-- نموذج التفعيل -->
          <form @submit.prevent="activateLicense" v-if="!license.activated || showReactivate">
            <div class="row">
              <div class="col-md-12 mb-3">
                <label for="license_key" class="form-label">
                  مفتاح الترخيص <span class="text-danger">*</span>
                </label>
                <textarea
                  id="license_key"
                  v-model="form.license_key"
                  class="form-control"
                  rows="5"
                  placeholder="الصق مفتاح الترخيص هنا..."
                  :class="{ 'is-invalid': form.errors.license_key }"
                  required
                ></textarea>
                <div v-if="form.errors.license_key" class="invalid-feedback">
                  {{ form.errors.license_key }}
                </div>
                <small class="form-text text-muted">
                  أدخل مفتاح الترخيص الذي حصلت عليه من المزود
                </small>
              </div>
            </div>

            <!-- معلومات السيرفر -->
            <div class="card bg-light mb-3">
              <div class="card-header">
                <h6 class="card-title mb-0">
                  <i class="bi bi-server me-2"></i>
                  معلومات السيرفر
                </h6>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <p class="mb-1"><strong>الدومين:</strong></p>
                    <code>{{ server.domain }}</code>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary ms-2"
                      @click="copyToClipboard(server.domain)"
                      title="نسخ"
                    >
                      <i class="bi bi-clipboard"></i>
                    </button>
                  </div>
                  <div class="col-md-6">
                    <p class="mb-1"><strong>Fingerprint:</strong></p>
                    <code class="small">{{ server.fingerprint }}</code>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary ms-2"
                      @click="copyToClipboard(server.fingerprint)"
                      title="نسخ"
                    >
                      <i class="bi bi-clipboard"></i>
                    </button>
                  </div>
                </div>
                <div class="mt-2">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    @click="getServerInfo"
                    :disabled="loading"
                  >
                    <i class="bi bi-arrow-clockwise me-1" :class="{ 'spinning': loading }"></i>
                    تحديث معلومات السيرفر
                  </button>
                </div>
              </div>
            </div>

            <!-- أزرار الإجراء -->
            <div class="d-flex justify-content-between">
              <button
                type="button"
                class="btn btn-secondary"
                @click="window.history.back()"
                :disabled="form.processing"
              >
                <i class="bi bi-arrow-right me-1"></i>
                رجوع
              </button>
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="form.processing || !form.license_key"
              >
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-key me-1"></i>
                {{ form.processing ? 'جاري التفعيل...' : 'تفعيل الترخيص' }}
              </button>
            </div>
          </form>

          <!-- زر إعادة التفعيل -->
          <div v-else class="text-center">
            <button
              type="button"
              class="btn btn-warning me-2"
              @click="showReactivate = true"
            >
              <i class="bi bi-arrow-clockwise me-1"></i>
              إعادة تفعيل
            </button>
            <button
              type="button"
              class="btn btn-danger"
              @click="deactivateLicense"
              :disabled="deactivating"
            >
              <span v-if="deactivating" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-x-circle me-1"></i>
              {{ deactivating ? 'جاري الإلغاء...' : 'إلغاء التفعيل' }}
            </button>
          </div>
        </div>
      </div>
    </section>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

const props = defineProps({
  license: {
    type: Object,
    default: () => ({})
  },
  server: {
    type: Object,
    required: true
  },
  translations: {
    type: Object,
    default: () => ({})
  }
})

const error = ref('')
const success = ref('')
const loading = ref(false)
const deactivating = ref(false)
const showReactivate = ref(false)

const form = useForm({
  license_key: '',
  domain: props.server.domain,
  fingerprint: props.server.fingerprint
})

const activateLicense = async () => {
  error.value = ''
  success.value = ''

  try {
    const response = await axios.post('/api/license/activate', {
      license_key: form.license_key,
      domain: form.domain,
      fingerprint: form.fingerprint
    })

    if (response.data.success) {
      success.value = response.data.message
      form.license_key = ''
      showReactivate.value = false
      
      // إعادة تحميل الصفحة بعد ثانيتين
      setTimeout(() => {
        window.location.reload()
      }, 2000)
    } else {
      error.value = response.data.message || 'فشل تفعيل الترخيص'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'حدث خطأ أثناء تفعيل الترخيص'
  }
}

const deactivateLicense = async () => {
  if (!confirm('هل أنت متأكد من إلغاء تفعيل الترخيص؟')) {
    return
  }

  deactivating.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await axios.post('/api/license/deactivate')

    if (response.data.success) {
      success.value = response.data.message
      setTimeout(() => {
        window.location.reload()
      }, 2000)
    } else {
      error.value = response.data.message || 'فشل إلغاء تفعيل الترخيص'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'حدث خطأ أثناء إلغاء تفعيل الترخيص'
  } finally {
    deactivating.value = false
  }
}

const getServerInfo = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/license/server-info')
    if (response.data.success) {
      form.domain = response.data.domain
      form.fingerprint = response.data.fingerprint
      success.value = 'تم تحديث معلومات السيرفر بنجاح'
    }
  } catch (err) {
    error.value = 'فشل تحديث معلومات السيرفر'
  } finally {
    loading.value = false
  }
}

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text).then(() => {
    success.value = 'تم النسخ بنجاح'
    setTimeout(() => {
      success.value = ''
    }, 2000)
  }).catch(() => {
    error.value = 'فشل النسخ'
  })
}
</script>

<style scoped>
.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

code {
  font-size: 0.9em;
  word-break: break-all;
}
</style>

