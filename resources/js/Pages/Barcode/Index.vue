<template>
  <AuthenticatedLayout :translations="translations">
    <!-- breadcrumb-->
    <div class="pagetitle dark:text-white">
      <h1 class="dark:text-white">{{ translations.barcode_generation }}</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link dark:text-white" :href="route('dashboard')">
              {{ translations.Home }}
            </Link>
          </li>
          <li class="breadcrumb-item active dark:text-white">{{ translations.barcode_generation }}</li>
        </ol>
      </nav>
    </div>
    <!-- End breadcrumb-->

    <section class="section dashboard">
      <div class="row">
        <!-- Search and Filters -->
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <form @submit.prevent="searchProducts">
                <div class="row">
                  <div class="col-md-4">
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="searchForm.search" 
                      :placeholder="translations.search"
                    />
                  </div>
                  <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">
                      {{ translations.search }} <i class="bi bi-search"></i>
                    </button>
                  </div>
                  <div class="col-md-3">
                    <button 
                      type="button" 
                      class="btn btn-success" 
                      @click="showBatchPrintModal = true"
                      :disabled="selectedProducts.length === 0"
                    >
                      {{ translations.batch_print }} ({{ selectedProducts.length }})
                    </button>
                  </div>
                  <div class="col-md-3">
                    <button 
                      type="button" 
                      class="btn btn-info" 
                      @click="showPrinterSettings = true"
                    >
                      {{ translations.printer_settings }}
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Products List -->
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <div class="table-responsive">
                <table class="table text-center">
                  <thead>
                    <tr>
                      <th>
                        <input 
                          type="checkbox" 
                          @change="toggleAllProducts"
                          :checked="selectedProducts.length === products.data.length && products.data.length > 0"
                        />
                      </th>
                      <th>{{ translations.name }}</th>
                      <th>{{ translations.model }}</th>
                      <th>{{ translations.barcode }}</th>
                      <th>{{ translations.quantity }}</th>
                      <th>{{ translations.price }}</th>
                      <th>{{ translations.actions }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="product in products.data" :key="product.id">
                      <td>
                        <input 
                          type="checkbox" 
                          :value="product.id"
                          v-model="selectedProducts"
                        />
                      </td>
                      <td>{{ product.name }}</td>
                      <td>{{ product.model }}</td>
                      <td>
                        <span v-if="product.barcode" class="badge bg-success">{{ product.barcode }}</span>
                        <span v-else class="badge bg-warning">{{ translations.no_barcode }}</span>
                      </td>
                      <td>{{ product.quantity }}</td>
                      <td>{{ product.price }} {{ translations.dinar }}</td>
                      <td>
                        <div class="btn-group" role="group">
                          <button 
                            class="btn btn-sm btn-primary" 
                            @click="generateBarcode(product)"
                            :disabled="loading"
                          >
                            <i class="bi bi-qr-code"></i> {{ translations.generate }}
                          </button>
                          <button 
                            class="btn btn-sm btn-success" 
                            @click="printBarcode(product)"
                            :disabled="!product.barcode || loading"
                          >
                            <i class="bi bi-printer"></i> {{ translations.print }}
                          </button>
                          <button 
                            class="btn btn-sm btn-info" 
                            @click="previewBarcode(product)"
                            :disabled="!product.barcode"
                          >
                            <i class="bi bi-eye"></i> {{ translations.preview }}
                          </button>
                          <button 
                            class="btn btn-sm btn-secondary" 
                            @click="downloadBarcode(product)"
                            :disabled="!product.barcode"
                          >
                            <i class="bi bi-download"></i> {{ translations.download }}
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
                    <li class="page-item" :class="{ disabled: !products.prev_page_url }">
                      <Link class="page-link" :href="products.prev_page_url" v-if="products.prev_page_url">
                        {{ translations.previous }}
                      </Link>
                      <span class="page-link" v-else>{{ translations.previous }}</span>
                    </li>
                    <li class="page-item" :class="{ disabled: !products.next_page_url }">
                      <Link class="page-link" :href="products.next_page_url" v-if="products.next_page_url">
                        {{ translations.next }}
                      </Link>
                      <span class="page-link" v-else>{{ translations.next }}</span>
                    </li>
                  </ul>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Generate Barcode Modal -->
      <div class="modal fade" :class="{ show: showGenerateModal }" :style="{ display: showGenerateModal ? 'block' : 'none' }">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ translations.generate_barcode }}</h5>
              <button type="button" class="btn-close" @click="showGenerateModal = false"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="confirmGenerateBarcode">
                <div class="mb-3">
                  <label class="form-label">{{ translations.product_name }}</label>
                  <input type="text" class="form-control" :value="selectedProduct?.name" readonly>
                </div>
                <div class="mb-3">
                  <label class="form-label">{{ translations.barcode_type }}</label>
                  <select class="form-select" v-model="barcodeSettings.type">
                    <option value="PNG">PNG</option>
                    <option value="SVG">SVG</option>
                    <option value="JPG">JPG</option>
                  </select>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">{{ translations.width }}</label>
                      <input type="number" class="form-control" v-model="barcodeSettings.width" min="1" max="10">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">{{ translations.height }}</label>
                      <input type="number" class="form-control" v-model="barcodeSettings.height" min="10" max="200">
                    </div>
                  </div>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showGenerateModal = false">
                {{ translations.cancel }}
              </button>
              <button type="button" class="btn btn-primary" @click="confirmGenerateBarcode" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                {{ translations.generate }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Print Barcode Modal -->
      <div class="modal fade" :class="{ show: showPrintModal }" :style="{ display: showPrintModal ? 'block' : 'none' }">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ translations.print_barcode }}</h5>
              <button type="button" class="btn-close" @click="showPrintModal = false"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="confirmPrintBarcode">
                <div class="mb-3">
                  <label class="form-label">{{ translations.product_name }}</label>
                  <input type="text" class="form-control" :value="selectedProduct?.name" readonly>
                </div>
                <div class="mb-3">
                  <label class="form-label">{{ translations.barcode }}</label>
                  <input type="text" class="form-control" :value="selectedProduct?.barcode" readonly>
                </div>
                <div class="mb-3">
                  <label class="form-label">{{ translations.print_quantity }}</label>
                  <input type="number" class="form-control" v-model="printQuantity" min="1" max="100" value="1">
                </div>
                <div class="mb-3">
                  <label class="form-label">{{ translations.barcode_type }}</label>
                  <select class="form-select" v-model="printSettings.type">
                    <option value="PNG">PNG</option>
                    <option value="SVG">SVG</option>
                    <option value="JPG">JPG</option>
                  </select>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">{{ translations.width }}</label>
                      <input type="number" class="form-control" v-model="printSettings.width" min="1" max="10">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">{{ translations.height }}</label>
                      <input type="number" class="form-control" v-model="printSettings.height" min="10" max="200">
                    </div>
                  </div>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showPrintModal = false">
                {{ translations.cancel }}
              </button>
              <button type="button" class="btn btn-success" @click="confirmPrintBarcode" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                <i class="bi bi-printer"></i> {{ translations.print }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Batch Print Modal -->
      <div class="modal fade" :class="{ show: showBatchPrintModal }" :style="{ display: showBatchPrintModal ? 'block' : 'none' }">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ translations.batch_print }}</h5>
              <button type="button" class="btn-close" @click="showBatchPrintModal = false"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">{{ translations.selected_products }} ({{ selectedProducts.length }})</label>
                <div class="border p-2" style="max-height: 200px; overflow-y: auto;">
                  <div v-for="productId in selectedProducts" :key="productId" class="mb-1">
                    {{ getProductName(productId) }}
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">{{ translations.quantity_per_product }}</label>
                <input type="number" class="form-control" v-model="batchPrintQuantity" min="1" max="10" value="1">
              </div>
              <div class="mb-3">
                <label class="form-label">{{ translations.barcode_type }}</label>
                <select class="form-select" v-model="batchPrintSettings.type">
                  <option value="PNG">PNG</option>
                  <option value="SVG">SVG</option>
                  <option value="JPG">JPG</option>
                </select>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.width }}</label>
                    <input type="number" class="form-control" v-model="batchPrintSettings.width" min="1" max="10">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.height }}</label>
                    <input type="number" class="form-control" v-model="batchPrintSettings.height" min="10" max="200">
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showBatchPrintModal = false">
                {{ translations.cancel }}
              </button>
              <button type="button" class="btn btn-success" @click="confirmBatchPrint" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                <i class="bi bi-printer"></i> {{ translations.print_all }} ({{ selectedProducts.length * batchPrintQuantity }})
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Printer Settings Modal -->
      <div class="modal fade" :class="{ show: showPrinterSettings }" :style="{ display: showPrinterSettings ? 'block' : 'none' }">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ translations.printer_settings }}</h5>
              <button type="button" class="btn-close" @click="showPrinterSettings = false"></button>
            </div>
            <div class="modal-body">
              <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                {{ translations.printer_settings_info }}
              </div>
              <div class="mb-3">
                <label class="form-label">{{ translations.default_barcode_type }}</label>
                <select class="form-select" v-model="printerSettings.type">
                  <option value="PNG">PNG</option>
                  <option value="SVG">SVG</option>
                  <option value="JPG">JPG</option>
                </select>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.default_width }}</label>
                    <input type="number" class="form-control" v-model="printerSettings.width" min="1" max="10">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ translations.default_height }}</label>
                    <input type="number" class="form-control" v-model="printerSettings.height" min="10" max="200">
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showPrinterSettings = false">
                {{ translations.cancel }}
              </button>
              <button type="button" class="btn btn-primary" @click="savePrinterSettings">
                {{ translations.save }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Barcode Preview Modal -->
      <div class="modal fade" :class="{ show: showPreviewModal }" :style="{ display: showPreviewModal ? 'block' : 'none' }">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ translations.barcode_preview }}</h5>
              <button type="button" class="btn-close" @click="showPreviewModal = false"></button>
            </div>
            <div class="modal-body text-center">
              <div v-if="previewLoading" class="text-center">
                <div class="spinner-border" role="status">
                  <span class="visually-hidden">{{ translations.loading }}</span>
                </div>
              </div>
              <div v-else-if="previewImage" class="border p-3">
                <img :src="previewImage" :alt="translations.barcode_preview" class="img-fluid">
                <div class="mt-2">
                  <strong>{{ translations.barcode }}:</strong> {{ selectedProduct?.barcode }}
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showPreviewModal = false">
                {{ translations.close }}
              </button>
              <button type="button" class="btn btn-success" @click="printFromPreview" :disabled="!selectedProduct?.barcode">
                <i class="bi bi-printer"></i> {{ translations.print }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

const props = defineProps({
  products: Object,
  translations: Object,
  filters: Object
})

// Reactive data
const loading = ref(false)
const previewLoading = ref(false)
const searchForm = reactive({
  search: props.filters?.search || ''
})

const selectedProducts = ref([])
const selectedProduct = ref(null)

// Modal states
const showGenerateModal = ref(false)
const showPrintModal = ref(false)
const showBatchPrintModal = ref(false)
const showPrinterSettings = ref(false)
const showPreviewModal = ref(false)

// Settings
const barcodeSettings = reactive({
  type: 'PNG',
  width: 2,
  height: 30
})

const printSettings = reactive({
  type: 'PNG',
  width: 2,
  height: 30
})

const batchPrintSettings = reactive({
  type: 'PNG',
  width: 2,
  height: 30
})

const printerSettings = reactive({
  type: 'PNG',
  width: 2,
  height: 30
})

const printQuantity = ref(1)
const batchPrintQuantity = ref(1)
const previewImage = ref('')

// Methods
const searchProducts = () => {
  router.get(route('barcode.index'), searchForm, {
    preserveState: true,
    replace: true
  })
}

const toggleAllProducts = () => {
  if (selectedProducts.value.length === props.products.data.length) {
    selectedProducts.value = []
  } else {
    selectedProducts.value = props.products.data.map(product => product.id)
  }
}

const getProductName = (productId) => {
  const product = props.products.data.find(p => p.id === productId)
  return product ? product.name : ''
}

const generateBarcode = (product) => {
  selectedProduct.value = product
  showGenerateModal.value = true
}

const confirmGenerateBarcode = async () => {
  loading.value = true
  
  try {
    const response = await fetch(route('barcode.generate'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        product_id: selectedProduct.value.id,
        ...barcodeSettings
      })
    })

    const data = await response.json()
    
    if (data.success) {
      toast.success(data.message)
      showGenerateModal.value = false
      // Refresh the page to show updated barcode
      router.reload()
    } else {
      toast.error(data.message)
    }
  } catch (error) {
    toast.error('حدث خطأ أثناء توليد الباركود')
  } finally {
    loading.value = false
  }
}

const printBarcode = (product) => {
  selectedProduct.value = product
  showPrintModal.value = true
}

const confirmPrintBarcode = async () => {
  loading.value = true
  
  try {
    const response = await fetch(route('barcode.print'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        product_id: selectedProduct.value.id,
        quantity: printQuantity.value,
        printer_settings: printSettings
      })
    })

    const data = await response.json()
    
    if (data.success) {
      toast.success(data.message)
      showPrintModal.value = false
    } else {
      toast.error(data.message)
    }
  } catch (error) {
    toast.error('حدث خطأ أثناء الطباعة')
  } finally {
    loading.value = false
  }
}

const confirmBatchPrint = async () => {
  loading.value = true
  
  try {
    const response = await fetch(route('barcode.batch.print'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        product_ids: selectedProducts.value,
        quantity_per_product: batchPrintQuantity.value,
        printer_settings: batchPrintSettings
      })
    })

    const data = await response.json()
    
    if (data.success) {
      toast.success(data.message)
      showBatchPrintModal.value = false
      selectedProducts.value = []
    } else {
      toast.error(data.message)
    }
  } catch (error) {
    toast.error('حدث خطأ أثناء الطباعة المجمعة')
  } finally {
    loading.value = false
  }
}

const previewBarcode = async (product) => {
  selectedProduct.value = product
  previewLoading.value = true
  showPreviewModal.value = true
  
  try {
    const params = new URLSearchParams({
      code: product.barcode,
      type: printerSettings.type,
      width: printerSettings.width,
      height: printerSettings.height
    })
    
    const response = await fetch(`${route('barcode.preview')}?${params}`)
    
    if (response.ok) {
      const blob = await response.blob()
      previewImage.value = URL.createObjectURL(blob)
    } else {
      toast.error('فشل في تحميل معاينة الباركود')
    }
  } catch (error) {
    toast.error('حدث خطأ أثناء تحميل المعاينة')
  } finally {
    previewLoading.value = false
  }
}

const printFromPreview = () => {
  showPreviewModal.value = false
  printBarcode(selectedProduct.value)
}

const downloadBarcode = (product) => {
  const params = new URLSearchParams({
    type: printerSettings.type,
    width: printerSettings.width,
    height: printerSettings.height
  })
  
  window.open(`${route('barcode.download', product.id)}?${params}`, '_blank')
}

const savePrinterSettings = () => {
  // Save settings to localStorage
  localStorage.setItem('printerSettings', JSON.stringify(printerSettings))
  toast.success('تم حفظ إعدادات الطابعة')
  showPrinterSettings.value = false
}

// Load printer settings on mount
onMounted(() => {
  const savedSettings = localStorage.getItem('printerSettings')
  if (savedSettings) {
    const settings = JSON.parse(savedSettings)
    Object.assign(printerSettings, settings)
    Object.assign(barcodeSettings, settings)
    Object.assign(printSettings, settings)
    Object.assign(batchPrintSettings, settings)
  }
})
</script>

<style scoped>
.modal.show {
  background-color: rgba(0, 0, 0, 0.5);
}

.btn-group .btn {
  margin-right: 2px;
}

.btn-group .btn:last-child {
  margin-right: 0;
}

.table th, .table td {
  vertical-align: middle;
}

.badge {
  font-size: 0.8em;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}
</style>