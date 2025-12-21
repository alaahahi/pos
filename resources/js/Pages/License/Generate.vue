<template>
  <div class="min-h-screen bg-light d-flex align-items-center justify-content-center py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card shadow">
            <div class="card-header bg-primary text-white">
              <h5 class="card-title mb-0">
                <i class="bi bi-key me-2"></i>
                توليد مفتاح الترخيص
              </h5>
            </div>
            <div class="card-body">
              <!-- رسالة النجاح -->
              <div v-if="success" class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ success }}
                <button type="button" class="btn-close" @click="success = ''"></button>
              </div>

              <!-- رسالة الخطأ -->
              <div v-if="error" class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                {{ error }}
                <button type="button" class="btn-close" @click="error = ''"></button>
              </div>

              <!-- نموذج التوليد -->
              <form @submit.prevent="generateLicense" v-if="!generatedKey">
                <div class="row">
                  <!-- Domain -->
                  <div class="col-md-6 mb-3">
                    <label for="domain" class="form-label">الدومين</label>
                    <input
                      type="text"
                      id="domain"
                      v-model="form.domain"
                      class="form-control"
                      placeholder="example.com"
                    />
                    <small class="form-text text-muted">اتركه فارغاً لاستخدام الدومين الحالي</small>
                  </div>

                  <!-- Fingerprint -->
                  <div class="col-md-6 mb-3">
                    <label for="fingerprint" class="form-label">Fingerprint</label>
                    <input
                      type="text"
                      id="fingerprint"
                      v-model="form.fingerprint"
                      class="form-control"
                      placeholder="Server Fingerprint"
                    />
                    <small class="form-text text-muted">اتركه فارغاً لاستخدام Fingerprint الحالي</small>
                  </div>

                  <!-- Type -->
                  <div class="col-md-6 mb-3">
                    <label for="type" class="form-label">نوع الترخيص</label>
                    <select id="type" v-model="form.type" class="form-select">
                      <option value="trial">تجريبي (Trial)</option>
                      <option value="standard">عادي (Standard)</option>
                      <option value="premium">مميز (Premium)</option>
                    </select>
                  </div>

                  <!-- Expires At -->
                  <div class="col-md-6 mb-3">
                    <label for="expires_at" class="form-label">تاريخ الانتهاء</label>
                    <input
                      type="date"
                      id="expires_at"
                      v-model="form.expires_at"
                      class="form-control"
                      :min="minDate"
                    />
                    <small class="form-text text-muted">اتركه فارغاً لترخيص دائم</small>
                  </div>

                  <!-- Max Installations -->
                  <div class="col-md-6 mb-3">
                    <label for="max_installations" class="form-label">عدد التثبيتات المسموح بها</label>
                    <input
                      type="number"
                      id="max_installations"
                      v-model.number="form.max_installations"
                      class="form-control"
                      min="1"
                    />
                  </div>
                </div>

                <!-- معلومات السيرفر الحالي -->
                <div class="card bg-light mb-3">
                  <div class="card-header">
                    <h6 class="card-title mb-0">
                      <i class="bi bi-server me-2"></i>
                      معلومات السيرفر الحالي
                    </h6>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-6">
                        <p class="mb-1"><strong>الدومين:</strong></p>
                        <code>{{ serverInfo.domain }}</code>
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-secondary ms-2"
                          @click="copyToClipboard(serverInfo.domain)"
                          title="نسخ"
                        >
                          <i class="bi bi-clipboard"></i>
                        </button>
                      </div>
                      <div class="col-md-6">
                        <p class="mb-1"><strong>Fingerprint:</strong></p>
                        <code class="small">{{ serverInfo.fingerprint }}</code>
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-secondary ms-2"
                          @click="copyToClipboard(serverInfo.fingerprint)"
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
                        @click="loadServerInfo"
                        :disabled="loading"
                      >
                        <i class="bi bi-arrow-clockwise me-1" :class="{ 'spinning': loading }"></i>
                        تحديث معلومات السيرفر
                      </button>
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-success ms-2"
                        @click="useCurrentServerInfo"
                      >
                        <i class="bi bi-check-circle me-1"></i>
                        استخدام معلومات السيرفر الحالي
                      </button>
                    </div>
                  </div>
                </div>

                <!-- أزرار الإجراء -->
                <div class="d-flex justify-content-end">
                  <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="form.processing"
                  >
                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-key me-1"></i>
                    {{ form.processing ? 'جاري التوليد...' : 'توليد مفتاح الترخيص' }}
                  </button>
                </div>
              </form>

              <!-- عرض المفتاح المُولد -->
              <div v-else class="generated-key-section">
                <div class="alert alert-success">
                  <h6 class="alert-heading">
                    <i class="bi bi-check-circle me-2"></i>
                    تم توليد مفتاح الترخيص بنجاح!
                  </h6>
                  <hr>
                  <p class="mb-2"><strong>نوع الترخيص:</strong> {{ licenseData.type }}</p>
                  <p class="mb-2" v-if="licenseData.expires_at">
                    <strong>تاريخ الانتهاء:</strong> {{ licenseData.expires_at }}
                  </p>
                  <p class="mb-2" v-else>
                    <strong>النوع:</strong> ترخيص دائم
                  </p>
                  <p class="mb-2"><strong>الدومين:</strong> {{ licenseData.domain }}</p>
                </div>

                <div class="mb-3">
                  <label class="form-label"><strong>مفتاح الترخيص:</strong></label>
                  <div class="input-group">
                    <textarea
                      v-model="generatedKey"
                      class="form-control font-monospace"
                      rows="6"
                      readonly
                      style="font-size: 0.85em;"
                    ></textarea>
                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      @click="copyToClipboard(generatedKey)"
                      title="نسخ"
                    >
                      <i class="bi bi-clipboard"></i>
                    </button>
                  </div>
                  <small class="form-text text-muted">
                    ⚠️ احفظ هذا المفتاح في مكان آمن. لن تتمكن من رؤيته مرة أخرى.
                  </small>
                </div>

                <div class="d-flex justify-content-between">
                  <button
                    type="button"
                    class="btn btn-secondary"
                    @click="resetForm"
                  >
                    <i class="bi bi-arrow-right me-1"></i>
                    توليد مفتاح جديد
                  </button>
                  <button
                    type="button"
                    class="btn btn-success"
                    @click="copyToClipboard(generatedKey)"
                  >
                    <i class="bi bi-clipboard me-1"></i>
                    نسخ المفتاح
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'

const error = ref('')
const success = ref('')
const loading = ref(false)
const generatedKey = ref('')
const licenseData = ref({})
const minDate = new Date().toISOString().split('T')[0]

const serverInfo = reactive({
  domain: '',
  fingerprint: ''
})

const form = reactive({
  domain: '',
  fingerprint: '',
  type: 'standard',
  expires_at: '',
  max_installations: 1,
  processing: false
})

const generateLicense = async () => {
  error.value = ''
  success.value = ''
  form.processing = true

  try {
    // الحصول على password من URL
    const urlParams = new URLSearchParams(window.location.search)
    const password = urlParams.get('password')
    
    const response = await axios.post('/api/license/generate', {
      password: password, // إرسال password في body
      domain: form.domain || undefined,
      fingerprint: form.fingerprint || undefined,
      type: form.type,
      expires_at: form.expires_at || undefined,
      max_installations: form.max_installations
    })

    if (response.data.success) {
      generatedKey.value = response.data.license_key
      licenseData.value = response.data.license_data
      success.value = response.data.message
    } else {
      error.value = response.data.message || 'فشل توليد مفتاح الترخيص'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'حدث خطأ أثناء توليد مفتاح الترخيص'
  } finally {
    form.processing = false
  }
}

const loadServerInfo = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/license/server-info')
    if (response.data.success) {
      serverInfo.domain = response.data.domain
      serverInfo.fingerprint = response.data.fingerprint
      success.value = 'تم تحديث معلومات السيرفر بنجاح'
      setTimeout(() => {
        success.value = ''
      }, 2000)
    }
  } catch (err) {
    error.value = 'فشل تحديث معلومات السيرفر'
  } finally {
    loading.value = false
  }
}

const useCurrentServerInfo = () => {
  form.domain = serverInfo.domain
  form.fingerprint = serverInfo.fingerprint
  success.value = 'تم استخدام معلومات السيرفر الحالي'
  setTimeout(() => {
    success.value = ''
  }, 2000)
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

const resetForm = () => {
  generatedKey.value = ''
  licenseData.value = {}
  form.domain = ''
  form.fingerprint = ''
  form.type = 'standard'
  form.expires_at = ''
  form.max_installations = 1
}

onMounted(() => {
  loadServerInfo()
})
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

.font-monospace {
  font-family: 'Courier New', monospace;
}

.generated-key-section {
  animation: fadeIn 0.5s;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
</style>

