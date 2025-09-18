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
                          <!-- Generate button - only show if no barcode -->
                          <button 
                            v-if="!product.barcode"
                            class="btn btn-sm btn-primary" 
                            @click="generateBarcode(product)"
                            :disabled="loading"
                          >
                            <i class="bi bi-qr-code"></i> {{ translations.generate }}
                          </button>
                          
                          <!-- Print button - only show if has barcode -->
                          <BarcodePrinter 
                            v-if="product.barcode"
                            :barcode-data="product.barcode"
                            :product-name="product.name"
                            :translations="translations"
                            :printer-settings="printSettings"
                          />
                          
                          <!-- Preview button - only show if has barcode -->
                          <button 
                            v-if="product.barcode"
                            class="btn btn-sm btn-info" 
                            @click="previewBarcode(product)"
                          >
                            <i class="bi bi-eye"></i> {{ translations.preview }}
                          </button>
                          
                          <!-- Download button - only show if has barcode -->
                          <button 
                            v-if="product.barcode"
                            class="btn btn-sm btn-secondary" 
                            @click="downloadBarcode(product)"
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
              <BarcodePrinter 
                v-if="selectedProduct?.barcode"
                :barcode-data="selectedProduct.barcode"
                :product-name="selectedProduct.name"
                :translations="translations"
                :printer-settings="printSettings"
              />
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
              <div v-else-if="previewImage" class="border p-4" style="max-width: 400px; margin: 0 auto;">
                <!-- Product Name -->
                <div class="mb-3">
                  <h4 class="fw-bold text-center">{{ selectedProduct?.name || 'Product' }}</h4>
                </div>
                
                <!-- Barcode Image -->
                <div class="mb-3">
                  <img :src="previewImage" :alt="translations.barcode_preview" class="img-fluid" style="width: 100%; padding: 10px; background: white;">
                </div>
                
                <!-- Barcode Number -->
                <div class="mb-3">
                  <div class="text-muted small">{{ translations.barcode }}:</div>
                  <div class="fw-bold font-monospace fs-5">{{ selectedProduct?.barcode }}</div>
                </div>
                
                <!-- Print Preview Note -->
                <div class="alert alert-info small">
                  <i class="bi bi-info-circle"></i> هذه معاينة لما سيتم طباعته
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showPreviewModal = false">
                {{ translations.close }}
              </button>
              <BarcodePrinter 
                v-if="selectedProduct?.barcode"
                :barcode-data="selectedProduct.barcode"
                :product-name="selectedProduct.name"
                :translations="translations"
                :printer-settings="printSettings"
              />
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
import BarcodePrinter from '@/Components/BarcodePrinter.vue'
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

const generateBarcode = async (product) => {
  loading.value = true
  
  try {
    const response = await fetch(route('barcode.generate'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        product_id: product.id,
        type: 'PNG',
        width: 2,
        height: 30
      })
    })

    const data = await response.json()
    
    if (data.success) {
      toast.success('تم توليد الباركود بنجاح')
      // Refresh the page to show updated barcode
      window.location.reload()
    } else {
      toast.error(data.message || 'حدث خطأ أثناء توليد الباركود')
    }
  } catch (error) {
    console.error('Generate barcode error:', error)
    toast.error('حدث خطأ أثناء توليد الباركود')
  } finally {
    loading.value = false
  }
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
        type: barcodeSettings.type,
        width: barcodeSettings.width,
        height: barcodeSettings.height
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
        printer_settings: {
          type: printSettings.type,
          width: printSettings.width,
          height: printSettings.height
        }
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
        printer_settings: {
          type: batchPrintSettings.type,
          width: batchPrintSettings.width,
          height: batchPrintSettings.height
        }
      })
    })

    const data = await response.json()
    
    if (data.success) {
      toast.success(data.message)
      
      // Print all barcodes
      await printBatchBarcodes(data.results)
      
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

const printBatchBarcodes = async (results) => {
  try {
    // Create a temporary div for batch printing
    const printDiv = document.createElement('div')
    printDiv.id = 'batch-barcode-print-overlay'
    printDiv.style.position = 'fixed'
    printDiv.style.top = '0'
    printDiv.style.left = '0'
    printDiv.style.width = '100%'
    printDiv.style.height = '100%'
    printDiv.style.backgroundColor = 'white'
    printDiv.style.zIndex = '9999'
    printDiv.style.padding = '20px'
    printDiv.style.textAlign = 'center'
    printDiv.style.fontFamily = 'Arial, sans-serif'
    printDiv.style.overflow = 'auto'

    let barcodeHtml = ''
    
    for (const result of results) {
      if (result.success) {
        const barcodeImageUrl = `data:image/png;base64,${result.data}`
        
        barcodeHtml += `
          <div class="barcode-container" style="border: 2px solid #000; padding: 20px; margin: 20px 0; background: white; page-break-after: always;">
            <div class="product-name" style="font-size: 18px; font-weight: bold; margin-bottom: 15px; color: #2c3e50;">${result.product.name}</div>
            <img class="barcode-image" src="${barcodeImageUrl}" alt="Barcode" style="max-width: 100%; height: auto; border: 1px solid #ccc;">
            <div class="barcode-text" style="font-size: 14px; margin-top: 15px; font-family: monospace; color: #666;">${result.product.barcode}</div>
            <div class="price-text" style="font-size: 16px; font-weight: bold; margin-top: 10px; color: #2c3e50;">${result.product.price} دينار</div>
          </div>
        `
      }
    }

    printDiv.innerHTML = `
      <style>
        @media print {
          body { margin: 0; padding: 0; }
          .print-container { 
            max-width: 100% !important; 
            width: 100% !important; 
            margin: 0 !important; 
            padding: 20px !important; 
            border: none !important;
            box-shadow: none !important;
          }
          .barcode-container { 
            max-width: 100% !important; 
            width: 100% !important; 
            margin: 0 !important; 
            padding: 30px !important; 
            border: 2px solid #000 !important;
            background: white !important;
            page-break-after: always !important;
          }
          .barcode-image { 
            width: 100% !important; 
            height: auto !important; 
            min-height: 300px !important; 
            max-height: 500px !important;
            border: 1px solid #000 !important;
          }
          .product-name { 
            font-size: 24px !important; 
            font-weight: bold !important; 
            margin-bottom: 20px !important; 
            text-align: center !important;
          }
          .barcode-text { 
            font-size: 18px !important; 
            margin-top: 20px !important; 
            font-family: monospace !important; 
            text-align: center !important;
            font-weight: bold !important;
          }
          .price-text {
            font-size: 20px !important;
            font-weight: bold !important;
            margin-top: 15px !important;
            text-align: center !important;
            color: #2c3e50 !important;
          }
          .no-print { display: none !important; }
        }
      </style>
      <div class="print-container" style="max-width: 400px; margin: 50px auto; border: 2px solid #333; padding: 30px; background: white; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 20px; color: #333;">طباعة الباركود المجمعة</h3>
        
        ${barcodeHtml}
        
        <div class="no-print" style="margin-top: 30px;">
          <button onclick="printBatchContent()" style="padding: 12px 25px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 15px; font-size: 16px;">
            <i class="bi bi-printer"></i> طباعة الكل
          </button>
          <button onclick="closeBatchPrintOverlay()" style="padding: 12px 25px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
            <i class="bi bi-x"></i> إلغاء
          </button>
        </div>
        
        <div class="no-print" style="margin-top: 20px; font-size: 12px; color: #666;">
          <p>💡 نصيحة: اختر "Microsoft Print to PDF" لطباعة إلى ملف PDF</p>
        </div>
      </div>
    `

    // Add print and close functions to window
    window.printBatchContent = function() {
      // Wait for all images to load before printing
      const images = printDiv.querySelectorAll('img')
      let loadedImages = 0
      
      if (images.length === 0) {
        window.print()
        return
      }
      
      images.forEach(img => {
        if (img.complete) {
          loadedImages++
        } else {
          img.onload = function() {
            loadedImages++
            if (loadedImages === images.length) {
              window.print()
            }
          }
        }
      })
      
      if (loadedImages === images.length) {
        window.print()
      }
    }
    
    window.closeBatchPrintOverlay = function() {
      document.body.removeChild(printDiv)
    }

    document.body.appendChild(printDiv)
    toast.success('تم تحضير الباركودات للطباعة المجمعة. اضغط زر الطباعة في الأعلى.')

  } catch (error) {
    console.error('Batch print overlay error:', error)
    toast.error('حدث خطأ أثناء تحضير الطباعة المجمعة')
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
