<template>
  <AuthenticatedLayout :translations="translations">
    <section class="section dashboard">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="bi bi-shield-check me-2"></i>
            حالة الترخيص
          </h5>
        </div>
        <div class="card-body">
          <!-- حالة الترخيص -->
          <div v-if="license.activated && license.valid" class="alert alert-success">
            <h6 class="alert-heading">
              <i class="bi bi-check-circle me-2"></i>
              الترخيص مفعل وصالح
            </h6>
            <hr>
            <div class="row">
              <div class="col-md-6">
                <p class="mb-1"><strong>النوع:</strong> 
                  <span class="badge bg-primary">{{ license.type }}</span>
                </p>
                <p class="mb-1" v-if="license.expires_at">
                  <strong>تاريخ الانتهاء:</strong> {{ license.expires_at }}
                </p>
                <p class="mb-1" v-else>
                  <strong>النوع:</strong> <span class="badge bg-success">ترخيص دائم</span>
                </p>
                <p class="mb-1" v-if="license.days_remaining !== null">
                  <strong>الأيام المتبقية:</strong> 
                  <span :class="getDaysRemainingClass(license.days_remaining)">
                    {{ license.days_remaining }} يوم
                  </span>
                </p>
              </div>
              <div class="col-md-6">
                <p class="mb-1"><strong>الدومين:</strong> <code>{{ license.domain }}</code></p>
                <p class="mb-1"><strong>تاريخ التفعيل:</strong> {{ license.activated_at }}</p>
                <p class="mb-1" v-if="license.last_verified_at">
                  <strong>آخر تحقق:</strong> {{ license.last_verified_at }}
                </p>
              </div>
            </div>
          </div>

          <div v-else-if="license.activated && !license.valid" class="alert alert-warning">
            <h6 class="alert-heading">
              <i class="bi bi-exclamation-triangle me-2"></i>
              الترخيص منتهي الصلاحية
            </h6>
            <hr>
            <p>يرجى تجديد الترخيص أو الاتصال بالدعم الفني.</p>
          </div>

          <div v-else class="alert alert-danger">
            <h6 class="alert-heading">
              <i class="bi bi-x-circle me-2"></i>
              الترخيص غير مفعل
            </h6>
            <hr>
            <p>يرجى تفعيل الترخيص للاستمرار في استخدام النظام.</p>
            <Link :href="route('license.activate')" class="btn btn-primary">
              <i class="bi bi-key me-1"></i>
              تفعيل الترخيص
            </Link>
          </div>

          <!-- معلومات السيرفر -->
          <div class="card bg-light mt-4">
            <div class="card-header">
              <h6 class="card-title mb-0">
                <i class="bi bi-server me-2"></i>
                معلومات السيرفر
              </h6>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label"><strong>الدومين:</strong></label>
                  <div class="input-group">
                    <input type="text" class="form-control" :value="server.domain" readonly>
                    <button
                      class="btn btn-outline-secondary"
                      type="button"
                      @click="copyToClipboard(server.domain)"
                    >
                      <i class="bi bi-clipboard"></i>
                    </button>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label"><strong>Fingerprint:</strong></label>
                  <div class="input-group">
                    <input type="text" class="form-control" :value="server.fingerprint" readonly>
                    <button
                      class="btn btn-outline-secondary"
                      type="button"
                      @click="copyToClipboard(server.fingerprint)"
                    >
                      <i class="bi bi-clipboard"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- أزرار الإجراء -->
          <div class="mt-4 d-flex justify-content-between">
            <button
              type="button"
              class="btn btn-secondary"
              @click="window.history.back()"
            >
              <i class="bi bi-arrow-right me-1"></i>
              رجوع
            </button>
            <div>
              <button
                type="button"
                class="btn btn-primary me-2"
                @click="verifyLicense"
                :disabled="verifying"
              >
                <span v-if="verifying" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-shield-check me-1"></i>
                {{ verifying ? 'جاري التحقق...' : 'التحقق من الترخيص' }}
              </button>
              <Link
                :href="route('license.activate')"
                class="btn btn-warning"
              >
                <i class="bi bi-key me-1"></i>
                تفعيل/إعادة تفعيل
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
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
const verifying = ref(false)
const success = ref('')
const error = ref('')

const verifyLicense = async () => {
  verifying.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await axios.get('/api/license/verify')
    
    if (response.data.success && response.data.valid) {
      success.value = 'الترخيص صالح ومفعل'
      setTimeout(() => {
        window.location.reload()
      }, 1500)
    } else {
      error.value = 'الترخيص غير صالح أو منتهي الصلاحية'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'حدث خطأ أثناء التحقق من الترخيص'
  } finally {
    verifying.value = false
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

const getDaysRemainingClass = (days) => {
  if (days === null) return 'badge bg-success'
  if (days > 30) return 'badge bg-success'
  if (days > 7) return 'badge bg-warning'
  return 'badge bg-danger'
}
</script>

<style scoped>
code {
  font-size: 0.9em;
  word-break: break-all;
}
</style>

