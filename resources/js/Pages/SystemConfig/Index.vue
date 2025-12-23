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

            <!-- License Status Section -->
            <div class="row mt-3">
              <div class="col-12">
                <div class="card">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                      <i class="bi bi-shield-check me-2"></i>
                      حالة الترخيص
                    </h6>
                    <Link :href="route('license.activate')" class="btn btn-sm btn-primary">
                      <i class="bi bi-key me-1"></i>
                      إدارة الترخيص
                    </Link>
                  </div>
                  <div class="card-body">
                    <!-- حالة الترخيص -->
                    <div v-if="license.activated && license.valid" class="alert alert-success mb-3">
                      <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle me-2 fs-4"></i>
                        <div>
                          <h6 class="mb-1">الترخيص مفعل وصالح</h6>
                          <p class="mb-0">
                            <strong>النوع:</strong> 
                            <span class="badge bg-primary">{{ license.type }}</span>
                            <span v-if="license.expires_at" class="ms-2">
                              | <strong>تاريخ الانتهاء:</strong> {{ license.expires_at }}
                            </span>
                            <span v-else class="ms-2">
                              | <span class="badge bg-success">ترخيص دائم</span>
                            </span>
                            <span v-if="license.days_remaining !== null" class="ms-2">
                              | <strong>الأيام المتبقية:</strong> 
                              <span :class="getDaysRemainingClass(license.days_remaining)">
                                {{ license.days_remaining }} يوم
                              </span>
                            </span>
                          </p>
                        </div>
                      </div>
                    </div>

                    <div v-else-if="license.activated && !license.valid" class="alert alert-warning mb-3">
                      <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle me-2 fs-4"></i>
                        <div>
                          <h6 class="mb-1">الترخيص منتهي الصلاحية</h6>
                          <p class="mb-0">يرجى تجديد الترخيص أو الاتصال بالدعم الفني.</p>
                        </div>
                      </div>
                    </div>

                    <div v-else class="alert alert-danger mb-3">
                      <div class="d-flex align-items-center">
                        <i class="bi bi-x-circle me-2 fs-4"></i>
                        <div>
                          <h6 class="mb-1">الترخيص غير مفعل</h6>
                          <p class="mb-0">يرجى تفعيل الترخيص للاستمرار في استخدام النظام.</p>
                        </div>
                      </div>
                    </div>

                    <!-- معلومات الترخيص -->
                    <div class="row">
                      <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                          <tbody>
                            <tr>
                              <td class="fw-bold" style="width: 40%;">الدومين:</td>
                              <td>
                                <code class="small">{{ server.domain }}</code>
                                <button
                                  type="button"
                                  class="btn btn-sm btn-outline-secondary ms-2"
                                  @click="copyToClipboard(server.domain)"
                                  title="نسخ"
                                >
                                  <i class="bi bi-clipboard"></i>
                                </button>
                              </td>
                            </tr>
                            <tr v-if="license.activated">
                              <td class="fw-bold">تاريخ التفعيل:</td>
                              <td>{{ license.activated_at || 'غير محدد' }}</td>
                            </tr>
                            <tr v-if="license.last_verified_at">
                              <td class="fw-bold">آخر تحقق:</td>
                              <td>{{ license.last_verified_at }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                          <tbody>
                            <tr>
                              <td class="fw-bold" style="width: 40%;">Fingerprint:</td>
                              <td>
                                <code class="small" style="word-break: break-all;">{{ server.fingerprint }}</code>
                                <button
                                  type="button"
                                  class="btn btn-sm btn-outline-secondary ms-2"
                                  @click="copyToClipboard(server.fingerprint)"
                                  title="نسخ"
                                >
                                  <i class="bi bi-clipboard"></i>
                                </button>
                              </td>
                            </tr>
                            <tr>
                              <td class="fw-bold">الحالة:</td>
                              <td>
                                <span v-if="license.activated && license.valid" class="badge bg-success">مفعل وصالح</span>
                                <span v-else-if="license.activated && !license.valid" class="badge bg-warning">منتهي الصلاحية</span>
                                <span v-else class="badge bg-danger">غير مفعل</span>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>

                    <!-- أزرار الإجراء -->
                    <div class="mt-3 d-flex gap-2">
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-primary"
                        @click="verifyLicense"
                        :disabled="verifying"
                      >
                        <span v-if="verifying" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="bi bi-shield-check me-1"></i>
                        {{ verifying ? 'جاري التحقق...' : 'التحقق من الترخيص' }}
                      </button>
                      <Link
                        :href="route('license.status')"
                        class="btn btn-sm btn-outline-info"
                      >
                        <i class="bi bi-info-circle me-1"></i>
                        عرض التفاصيل
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Database Connection Section -->
            <div class="row mt-3">
              <div class="col-12">
                <div class="card">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                      <i class="bi bi-database me-2"></i>
                      حالة الاتصال بقاعدة البيانات
                    </h6>
                    <button
                      type="button"
                      class="btn btn-sm btn-primary"
                      @click="checkDatabaseConnection"
                      :disabled="checkingConnection"
                    >
                      <span v-if="checkingConnection" class="spinner-border spinner-border-sm me-2"></span>
                      <i v-else class="bi bi-arrow-clockwise me-1"></i>
                      {{ checkingConnection ? 'جاري التحقق...' : 'التحقق من الاتصال' }}
                    </button>
                  </div>
                  <div class="card-body">
                    <!-- معلومات الاتصال -->
                    <div v-if="connectionInfo" class="mt-3">
                      <!-- رسالة الحالة -->
                      <div :class="getConnectionAlertClass(connectionInfo.connection)" class="alert mb-3">
                        <div class="d-flex align-items-center">
                          <i :class="getConnectionIcon(connectionInfo.connection)" class="me-2 fs-4"></i>
                          <div>
                            <h6 class="mb-1">{{ connectionInfo.message }}</h6>
                            <p class="mb-0">
                              <strong>الاتصال الافتراضي:</strong> 
                              <span class="badge" :class="getConnectionBadgeClass(connectionInfo.connection.default_connection)">
                                {{ connectionInfo.connection.default_connection }}
                              </span>
                              <span class="ms-2">
                                | <strong>نوع قاعدة البيانات:</strong> 
                                <span class="badge bg-info">{{ connectionInfo.connection.driver }}</span>
                              </span>
                            </p>
                          </div>
                        </div>
                      </div>

                      <!-- تفاصيل الاتصال -->
                      <div class="row">
                        <div class="col-md-6">
                          <table class="table table-sm table-borderless">
                            <tbody>
                              <tr>
                                <td class="fw-bold" style="width: 40%;">الاتصال الافتراضي:</td>
                                <td>
                                  <code class="small">{{ connectionInfo.connection.default_connection }}</code>
                                </td>
                              </tr>
                              <tr>
                                <td class="fw-bold">نوع قاعدة البيانات:</td>
                                <td>
                                  <span class="badge" :class="getConnectionBadgeClass(connectionInfo.connection.driver)">
                                    {{ connectionInfo.connection.driver }}
                                  </span>
                                </td>
                              </tr>
                              <tr>
                                <td class="fw-bold">حالة الاتصال:</td>
                                <td>
                                  <span v-if="connectionInfo.connection.connected" class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>متصل
                                  </span>
                                  <span v-else class="badge bg-danger">
                                    <i class="bi bi-x-circle me-1"></i>غير متصل
                                  </span>
                                </td>
                              </tr>
                              <tr v-if="connectionInfo.connection.driver === 'sqlite'">
                                <td class="fw-bold">مسار الملف:</td>
                                <td>
                                  <code class="small">{{ connectionInfo.connection.file_path || connectionInfo.connection.database }}</code>
                                </td>
                              </tr>
                              <tr v-if="connectionInfo.connection.driver === 'sqlite' && connectionInfo.connection.file_exists">
                                <td class="fw-bold">حجم الملف:</td>
                                <td>{{ formatFileSize(connectionInfo.connection.file_size) }}</td>
                              </tr>
                              <tr v-if="connectionInfo.connection.driver === 'mysql'">
                                <td class="fw-bold">اسم قاعدة البيانات:</td>
                                <td>
                                  <code class="small">{{ connectionInfo.connection.database_name || connectionInfo.connection.database }}</code>
                                </td>
                              </tr>
                              <tr v-if="connectionInfo.connection.driver === 'mysql'">
                                <td class="fw-bold">الخادم:</td>
                                <td>
                                  <code class="small">{{ connectionInfo.connection.host }}:{{ connectionInfo.connection.port || 3306 }}</code>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                        <div class="col-md-6">
                          <table class="table table-sm table-borderless">
                            <tbody>
                              <tr>
                                <td class="fw-bold" style="width: 40%;">وضع التشغيل:</td>
                                <td>
                                  <span v-if="connectionInfo.connection.is_local" class="badge bg-warning">
                                    <i class="bi bi-laptop me-1"></i>Local
                                  </span>
                                  <span v-else class="badge bg-primary">
                                    <i class="bi bi-cloud me-1"></i>Online
                                  </span>
                                </td>
                              </tr>
                              <tr>
                                <td class="fw-bold">بيئة التطبيق:</td>
                                <td>
                                  <code class="small">{{ connectionInfo.connection.app_env || 'N/A' }}</code>
                                </td>
                              </tr>
                              <tr>
                                <td class="fw-bold">URL التطبيق:</td>
                                <td>
                                  <code class="small">{{ connectionInfo.connection.app_url || 'N/A' }}</code>
                                </td>
                              </tr>
                              <tr v-if="connectionInfo.connection.query_test !== undefined">
                                <td class="fw-bold">اختبار Query:</td>
                                <td>
                                  <span v-if="connectionInfo.connection.query_test" class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>نجح
                                  </span>
                                  <span v-else class="badge bg-danger">
                                    <i class="bi bi-x-circle me-1"></i>فشل
                                  </span>
                                </td>
                              </tr>
                              <tr v-if="connectionInfo.mysql">
                                <td class="fw-bold">MySQL متاح:</td>
                                <td>
                                  <span v-if="connectionInfo.mysql.connected" class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>متصل
                                  </span>
                                  <span v-else-if="connectionInfo.mysql.available" class="badge bg-warning">
                                    <i class="bi bi-exclamation-triangle me-1"></i>غير متصل
                                  </span>
                                  <span v-else class="badge bg-secondary">
                                    <i class="bi bi-x-circle me-1"></i>غير متاح
                                  </span>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>

                      <!-- رسالة خطأ إذا كان هناك خطأ -->
                      <div v-if="connectionInfo.connection.error" class="alert alert-danger mt-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>خطأ:</strong> {{ connectionInfo.connection.error }}
                      </div>
                    </div>

                    <!-- رسالة عند عدم وجود معلومات -->
                    <div v-else class="text-center text-muted py-4">
                      <i class="bi bi-info-circle me-2"></i>
                      اضغط على "التحقق من الاتصال" لعرض معلومات الاتصال
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
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  config: Object,
  translations: Object,
  license: {
    type: Object,
    default: () => ({})
  },
  server: {
    type: Object,
    default: () => ({})
  },
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

// License functions
const verifying = ref(false);
const success = ref('');
const error = ref('');

const verifyLicense = async () => {
  verifying.value = true;
  error.value = '';
  success.value = '';

  try {
    const response = await axios.get('/api/license/verify');
    
    if (response.data.success && response.data.valid) {
      success.value = 'الترخيص صالح ومفعل';
      setTimeout(() => {
        window.location.reload();
      }, 1500);
    } else {
      error.value = 'الترخيص غير صالح أو منتهي الصلاحية';
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'حدث خطأ أثناء التحقق من الترخيص';
  } finally {
    verifying.value = false;
  }
};

// Database Connection functions
const checkingConnection = ref(false);
const connectionInfo = ref(null);

const checkDatabaseConnection = async () => {
  checkingConnection.value = true;
  error.value = '';
  success.value = '';

  try {
    const response = await axios.get('/api/check-database-connection');
    
    if (response.data.success) {
      connectionInfo.value = response.data;
      success.value = response.data.message || 'تم التحقق من الاتصال بنجاح';
    } else {
      error.value = response.data.error || 'فشل التحقق من الاتصال';
      connectionInfo.value = null;
    }
  } catch (err) {
    error.value = err.response?.data?.error || 'حدث خطأ أثناء التحقق من الاتصال';
    connectionInfo.value = null;
  } finally {
    checkingConnection.value = false;
  }
};

const getConnectionAlertClass = (connection) => {
  if (!connection || !connection.connected) {
    return 'alert-danger';
  }
  if (connection.default_connection === 'sync_sqlite') {
    return 'alert-info';
  }
  return 'alert-success';
};

const getConnectionIcon = (connection) => {
  if (!connection || !connection.connected) {
    return 'bi bi-x-circle';
  }
  if (connection.default_connection === 'sync_sqlite') {
    return 'bi bi-database';
  }
  return 'bi bi-check-circle';
};

const getConnectionBadgeClass = (type) => {
  if (type === 'sync_sqlite' || type === 'sqlite') {
    return 'bg-info';
  }
  if (type === 'mysql') {
    return 'bg-primary';
  }
  return 'bg-secondary';
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text).then(() => {
    success.value = 'تم النسخ بنجاح';
    setTimeout(() => {
      success.value = '';
    }, 2000);
  }).catch(() => {
    error.value = 'فشل النسخ';
  });
};

const getDaysRemainingClass = (days) => {
  if (days === null) return 'badge bg-success';
  if (days > 30) return 'badge bg-success';
  if (days > 7) return 'badge bg-warning';
  return 'badge bg-danger';
};
</script>
