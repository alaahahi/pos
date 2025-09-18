<template>
  <AuthenticatedLayout :translations="translations">
    <!-- breadcrumb-->
    <div class="pagetitle dark:text-white">
      <h1 class="dark:text-white">{{ translations.products }}</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <Link class="nav-link dark:text-white" :href="route('dashboard')">
              {{ translations.home }}
            </Link>
          </li>
          <li class="breadcrumb-item active dark:text-white">{{ translations.products }}</li>
        </ol>
      </nav>
    </div>
    <!-- End breadcrumb-->

    <section class="section dashboard">
      <div class="card">
        <div class="card-header">
          <div class="d-flex">
          </div>
        </div>
        <div class="card-body">
          <form @submit.prevent="Filter">
            <div class="row filter_form">
              <div class="col-md-6">
                <input type="text" class="form-control search_box" v-model="filterForm.search" 
                  :placeholder="translations.search_by_name_or_barcode" />
              </div>
              <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                  {{ translations.search }} &nbsp; <i class="bi bi-search"></i>
                </button>
              </div>
              <div class="col-md-3">
                <Link v-if="hasPermission('create products')" class="btn btn-primary" :href="route('products.create')">
                  {{ translations.create }} &nbsp; <i class="bi bi-plus-circle"></i>
                </Link>
              </div>
            </div>
          </form>

          <div class="table-responsive">
            <table class="table text-center">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">{{ translations.name }}</th>
                  <th scope="col">{{ translations.barcode }}</th>
                  <th scope="col">{{ translations.model }}</th>
                  <th scope="col">{{ translations.quantity }}</th>
                  <th scope="col">{{ translations.price }}</th>
                  
                  <th scope="col">{{ translations.created_at }}</th>
                  <th scope="col"> {{ translations.statusProdact }}</th>
                  <th scope="col" v-if="hasPermission('update products')">{{ translations.edit }}</th>
                  <th scope="col" v-if="hasPermission('delete products')">{{ translations.delete }}</th>
                  <th scope="col" v-if="hasPermission('update products')">{{ translations.add_quantity }}</th>
                  <th scope="col">{{ translations.barcode_actions }}</th>

                </tr>
              </thead>
              <tbody>
                <tr v-for="(product, index) in products?.data" :key="product.id">
                  <th scope="row">{{ index + 1 }}</th>
                  <td>{{ product.name }}</td>
                  <td>{{ product.barcode }}</td>
                  <td>{{ product.model }}</td>
                  <td>{{ product.quantity }}</td>
                  <td>{{ product.price }}</td>
                  <td>{{ product.created }}</td>
                  <td>
                  <div>
                    <label class="inline-flex items-center me-5 cursor-pointer">
                      <input type="checkbox" class="sr-only peer" :checked="product.is_active == 1"
                        @change="Activate(product.id)">
                      <div
                        class="relative w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-700 peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600">
                      </div>
                    </label>
                  </div>
                  </td>
                  <td v-if="hasPermission('update products')">
                    <a class="btn btn-primary" :href="route('products.edit', { product: product.id })">
                      <i class="bi bi-pencil-square"></i>
                    </a>
                  </td>
                  <td v-if="hasPermission('delete products')">
                    <button type="button" class="btn btn-danger" @click="Delete(product.id)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                  <td v-if="hasPermission('update products')">
                  <button class="btn btn-success" @click="showModalPurchasesProduct = true; data = product" >
                      <i class="bi bi-currency-dollar"></i>
                  </button>
                  </td>
                  <td>
                    <div class="btn-group" role="group">
                      <!-- Generate button - only show if no barcode -->
                      <button 
                        v-if="!product.barcode"
                        class="btn btn-sm btn-primary" 
                        @click="generateBarcode(product)"
                        :disabled="loading"
                        title="توليد باركود"
                      >
                        <i class="bi bi-qr-code"></i>
                      </button>
                      
                      <!-- Print button - only show if has barcode -->
                      <button 
                        v-if="product.barcode"
                        class="btn btn-sm btn-success" 
                        @click="printBarcode(product)"
                        title="طباعة باركود"
                      >
                        <i class="bi bi-printer"></i>
                      </button>
                      
                      <!-- Preview button - only show if has barcode -->
                      <button 
                        v-if="product.barcode"
                        class="btn btn-sm btn-info" 
                        @click="previewBarcode(product)"
                        title="معاينة باركود"
                      >
                        <i class="bi bi-eye"></i>
                      </button>
                      
                      <!-- Download button - only show if has barcode -->
                      <button 
                        v-if="product.barcode"
                        class="btn btn-sm btn-secondary" 
                        @click="downloadBarcode(product)"
                        title="تحميل باركود"
                      >
                        <i class="bi bi-download"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <Pagination :links="products?.links" />
    </section>
    <ModalPurchasesProduct :show="showModalPurchasesProduct" :translations="translations" @close="showModalPurchasesProduct = false" :data="data"  />
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import BarcodePrinter from '@/Components/BarcodePrinter.vue';
import { Link, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { ref } from 'vue';
import ModalPurchasesProduct from '@/Components/ModalPurchasesProduct.vue';
import { useToast } from 'vue-toastification';

const props = defineProps({
  products: Object, 
  translations: Array 
});

const page = usePage();
const toast = useToast();

const showModalPurchasesProduct = ref(false);
const data = ref({});
const loading = ref(false);
const previewLoading = ref(false);
const previewImage = ref(null);
const selectedProduct = ref(null);

const filterForm = reactive({
  search: ''
});

// Barcode functions
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

const printBarcode = async (product) => {
  if (!product.barcode) {
    toast.error('لا يوجد باركود للطباعة')
    return
  }

  loading.value = true

  try {
    // Generate barcode image
    const params = new URLSearchParams({
      code: product.barcode,
      type: 'PNG',
      width: 2,
      height: 30
    })
    
    const response = await fetch(`/barcode/preview?${params}`)
    
    if (!response.ok) {
      throw new Error('Failed to generate barcode image')
    }
    
    const blob = await response.blob()
    const barcodeImageUrl = URL.createObjectURL(blob)

    // Create a temporary div for printing
    const printDiv = document.createElement('div')
    printDiv.id = 'barcode-print-overlay'
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
          .no-print { display: none !important; }
        }
      </style>
      <div class="print-container" style="max-width: 400px; margin: 50px auto; border: 2px solid #333; padding: 30px; background: white; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 20px; color: #333;">طباعة الباركود</h3>
        
        <div class="barcode-container" style="border: 1px solid #ddd; padding: 20px; margin: 20px 0; background: #f9f9f9;">
          <div class="product-name" style="font-size: 18px; font-weight: bold; margin-bottom: 15px; color: #2c3e50;">${product.name || 'Product'}</div>
          <img class="barcode-image" src="${barcodeImageUrl}" alt="Barcode" style="max-width: 100%;width: 100%; height: auto; border: 1px solid #ccc;" onload="console.log('Barcode image loaded successfully')" onerror="console.error('Failed to load barcode image')">
          <div class="barcode-text" style="font-size: 14px; margin-top: 15px; font-family: monospace; color: #666;">${product.barcode}</div>
        </div>
        
        <div class="no-print" style="margin-top: 30px;">
          <button onclick="printBarcodeContent()" style="padding: 12px 25px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 15px; font-size: 16px;">
            <i class="bi bi-printer"></i> طباعة
          </button>
          <button onclick="closePrintOverlay()" style="padding: 12px 25px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
            <i class="bi bi-x"></i> إلغاء
          </button>
        </div>
        
        <div class="no-print" style="margin-top: 20px; font-size: 12px; color: #666;">
          <p>💡 نصيحة: اختر "Microsoft Print to PDF" لطباعة إلى ملف PDF</p>
        </div>
      </div>
    `

    // Add print and close functions to window
    window.printBarcodeContent = function() {
      // Wait for image to load before printing
      const img = printDiv.querySelector('img')
      if (img && !img.complete) {
        img.onload = function() {
          window.print()
        }
        return
      }
      window.print()
    }
    
    window.closePrintOverlay = function() {
      document.body.removeChild(printDiv)
      if (barcodeImageUrl) {
        URL.revokeObjectURL(barcodeImageUrl)
      }
    }

    document.body.appendChild(printDiv)
    toast.success('تم تحضير الباركود للطباعة. اضغط زر الطباعة في الأعلى.')

    // Clean up URL object after a delay
    if (barcodeImageUrl) {
      setTimeout(() => {
        if (document.getElementById('barcode-print-overlay')) {
          URL.revokeObjectURL(barcodeImageUrl)
        }
      }, 30000) // Clean up after 30 seconds
    }

  } catch (error) {
    console.error('Print error:', error)
    toast.error('حدث خطأ أثناء تحضير الطباعة')
  } finally {
    loading.value = false
  }
}

const previewBarcode = async (product) => {
  previewLoading.value = true
  selectedProduct.value = product
  
  try {
    const params = new URLSearchParams({
      code: product.barcode,
      type: 'PNG',
      width: 2,
      height: 30
    })
    
    const response = await fetch(`/barcode/preview?${params}`)
    
    if (response.ok) {
      const blob = await response.blob()
      previewImage.value = URL.createObjectURL(blob)
      
      // Show preview in a simple modal
      Swal.fire({
        title: 'معاينة الباركود',
        html: `
          <div style="text-align: center;">
            <h4 class="text-center" style=" ; margin-bottom: 15px;">${product.name}</h4>
            <img src="${previewImage.value}" style="max-width: 100%; border: 1px solid #ddd; padding: 10px;width: 100%;">
            <div style="margin-top: 15px; font-family: monospace; font-size: 16px;">${product.barcode}</div>
          </div>
        `,
        showConfirmButton: true,
        confirmButtonText: 'إغلاق',
        width: '500px'
      })
    } else {
      toast.error('فشل في تحميل معاينة الباركود')
    }
  } catch (error) {
    console.error('Preview error:', error)
    toast.error('حدث خطأ أثناء معاينة الباركود')
  } finally {
    previewLoading.value = false
  }
}

const downloadBarcode = async (product) => {
  try {
    // Generate barcode image
    const params = new URLSearchParams({
      code: product.barcode,
      type: 'PNG',
      width: 2,
      height: 30
    })
    
    const response = await fetch(`/barcode/preview?${params}`)
    
    if (!response.ok) {
      throw new Error('Failed to generate barcode image')
    }
    
    const blob = await response.blob()
    const barcodeImageUrl = URL.createObjectURL(blob)

    // Create a canvas with product name and barcode for download
    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')
    
    // Set canvas size - smaller to reduce empty spaces
    canvas.width = 300
    canvas.height = 150
    
    // Fill background
    ctx.fillStyle = '#ffffff'
    ctx.fillRect(0, 0, canvas.width, canvas.height)
    
    // Draw border
    ctx.strokeStyle = '#000000'
    ctx.lineWidth = 1
    ctx.strokeRect(2, 2, canvas.width - 4, canvas.height - 4)
    
    // Draw product name
    ctx.fillStyle = '#000000'
    ctx.font = 'bold 16px Arial'
    ctx.textAlign = 'center'
    ctx.fillText(product.name || 'Product', canvas.width / 2, 25)
    
    // Load and draw barcode image
    const img = new Image()
    img.onload = function() {
      // Calculate barcode position (centered)
      const barcodeWidth = Math.min(img.width, canvas.width - 20)
      const barcodeHeight = Math.min(img.height, canvas.height - 80)
      const x = (canvas.width - barcodeWidth) / 2
      const y = 40
      
      ctx.drawImage(img, x, y, barcodeWidth, barcodeHeight)
      
      // Draw barcode number
      ctx.font = '14px monospace'
      ctx.fillText(product.barcode, canvas.width / 2, y + barcodeHeight + 20)
      
      // Create download link
      const link = document.createElement('a')
      link.href = canvas.toDataURL('image/png')
      link.download = `barcode_${product.name || 'product'}_${product.barcode}.png`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      
      // Clean up
      URL.revokeObjectURL(barcodeImageUrl)
      
      toast.success('تم تحميل الباركود بنجاح')
    }
    
    img.onerror = function() {
      // Fallback: download original image
      const link = document.createElement('a')
      link.href = barcodeImageUrl
      link.download = `barcode_${product.name || 'product'}_${product.barcode}.png`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      URL.revokeObjectURL(barcodeImageUrl)
      
      toast.success('تم تحميل الباركود بنجاح')
    }
    
    img.src = barcodeImageUrl
    
  } catch (error) {
    console.error('Download error:', error)
    toast.error('حدث خطأ أثناء تحميل الباركود')
  }
}

const Filter = () => {
  router.get(
    route('products.index'),
    filterForm,
    { preserveState: true, preserveScroll: true },
  );
};

const hasPermission = (permission) => {
  return page.props.auth_permissions.includes(permission);
};
const Activate = (id) => {
  Swal.fire({
    title: props.translations.are_your_sure,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#7066e0',
    confirmButtonText: props.translations.yes,
    cancelButtonText: props.translations.cancel,
  }).then((result) => {
    if (result.isConfirmed) {
      router.post(`/products/${id}/activate`, {
        onSuccess: () => {
          Swal.fire(
            'Updated !',
            'product stuaus item has been updated.',
            'success'
          );
        },
        onError: () => {
          Swal.fire(
            'Error!',
            'There was an issue updating product status.',
            'error'
          );
        }
      });
    }
  });
}
const Delete = (id) => {
  Swal.fire({
    title: props.translations.are_you_sure,
    text: props.translations.you_will_not_be_able_to_revert_this,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: props.translations.yes,
    cancelButtonText: props.translations.cancel,
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete('products/' + id, {
        onSuccess: () => {
          Swal.fire({
            title: props.translations.data_deleted_successfully,
            icon: "success"
          });
        },
        onError: () => {
          Swal.fire(
            'Error!',
            'There was an issue deleting the product.',
            'error'
          );
        }
      });
    }
  });
};
</script>
