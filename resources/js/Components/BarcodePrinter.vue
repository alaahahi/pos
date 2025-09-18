<template>
  <div class="barcode-printer">
    <!-- Print Button -->
    <button 
      class="btn btn-sm btn-success me-2" 
      @click="printBarcode"
      :disabled="loading || !barcodeData"
    >
      <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
      <i class="bi bi-printer"></i> {{ translations.print }}
    </button>

    

    <!-- Hidden canvas for rendering -->
    <canvas ref="barcodeCanvas" style="display: none;"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

const props = defineProps({
  barcodeData: {
    type: String,
    required: true
  },
  productName: {
    type: String,
    default: ''
  },
  translations: {
    type: Object,
    required: true
  },
  printerSettings: {
    type: Object,
    default: () => ({
      width: 2,
      height: 30,
      type: 'PNG'
    })
  }
})

const loading = ref(false)
const barcodeCanvas = ref(null)

const printBarcode = async () => {
  if (!props.barcodeData) {
    toast.error('لا يوجد بيانات باركود للطباعة')
    return
  }

  loading.value = true

  try {
    // Use current window for printing (more reliable)
    await printInCurrentWindow()
  } catch (error) {
    console.error('Print error:', error)
    toast.error('حدث خطأ أثناء الطباعة')
  } finally {
    loading.value = false
  }
}

const generateBarcodeImage = async () => {
  try {
    // Check if route function exists
    if (typeof route === 'undefined') {
      throw new Error('Route function is not available')
    }

    // Build URL with query parameters for GET request
    const params = new URLSearchParams({
        code: props.barcodeData,
        type: props.printerSettings.type,
        width: props.printerSettings.width,
        height: props.printerSettings.height
      })

    const response = await fetch(`/barcode/preview?${params}`, {
      method: 'GET',
      headers: {
        'Accept': 'image/png,image/jpeg,image/gif,*/*',
      }
    })

    if (response.ok) {
      const blob = await response.blob()
      return URL.createObjectURL(blob)
    } else {
      throw new Error(`Failed to generate barcode image: ${response.status} ${response.statusText}`)
    }
  } catch (error) {
    console.error('Barcode generation error:', error)
    throw error
  }
}

// Print in current window
const printInCurrentWindow = async () => {
  try {
    // Generate barcode image
    let barcodeImageUrl
    try {
      barcodeImageUrl = await generateBarcodeImage()
    } catch (error) {
      console.warn('Server barcode generation failed, using canvas fallback:', error)
      barcodeImageUrl = generateBarcodeWithCanvas()
      if (!barcodeImageUrl) {
        throw new Error('Failed to generate barcode image using both server and canvas methods')
      }
    }

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
          <div class="product-name" style="font-size: 18px; font-weight: bold; margin-bottom: 15px; color: #2c3e50;">${props.productName || 'Product'}</div>
          <img class="barcode-image" src="${barcodeImageUrl}" alt="Barcode" style="max-width: 100%;width: 100%; height: auto; border: 1px solid #ccc;" onload="console.log('Barcode image loaded successfully')" onerror="console.error('Failed to load barcode image')">
          <div class="barcode-text" style="font-size: 14px; margin-top: 15px; font-family: monospace; color: #666;">${props.barcodeData}</div>
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
    console.error('Print in current window error:', error)
    toast.error('حدث خطأ أثناء تحضير الطباعة')
  }
}

// Download barcode as image
const downloadBarcode = async () => {
  if (!props.barcodeData) {
    toast.error('لا يوجد بيانات باركود للتحميل')
    return
  }

  loading.value = true

  try {
    // Generate barcode image
    let barcodeImageUrl
    try {
      barcodeImageUrl = await generateBarcodeImage()
    } catch (error) {
      console.warn('Server barcode generation failed, using canvas fallback:', error)
      barcodeImageUrl = generateBarcodeWithCanvas()
      if (!barcodeImageUrl) {
        throw new Error('Failed to generate barcode image using both server and canvas methods')
      }
    }

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
    ctx.fillText(props.productName || 'Product', canvas.width / 2, 25)
    
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
      ctx.fillText(props.barcodeData, canvas.width / 2, y + barcodeHeight + 20)
      
      // Create download link
      const link = document.createElement('a')
      link.href = canvas.toDataURL('image/png')
      link.download = `barcode_${props.productName || 'product'}_${props.barcodeData}.png`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      
      // Clean up
      URL.revokeObjectURL(barcodeImageUrl)
    }
    
    img.onerror = function() {
      // Fallback: download original image
      const link = document.createElement('a')
      link.href = barcodeImageUrl
      link.download = `barcode_${props.productName || 'product'}_${props.barcodeData}.png`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    }
    
    img.src = barcodeImageUrl

    toast.success('تم تحميل الباركود بنجاح')

    // Clean up URL object after a delay
    setTimeout(() => {
      URL.revokeObjectURL(barcodeImageUrl)
    }, 5000)

  } catch (error) {
    console.error('Download error:', error)
    toast.error('حدث خطأ أثناء تحميل الباركود')
  } finally {
    loading.value = false
  }
}

// Alternative method using canvas (fallback if server generation fails)
const generateBarcodeWithCanvas = () => {
  const canvas = barcodeCanvas.value
  if (!canvas) return null

  const ctx = canvas.getContext('2d')
  const width = 200
  const height = 100

  canvas.width = width
  canvas.height = height

  // Clear canvas
  ctx.clearRect(0, 0, width, height)

  // Draw barcode pattern (simplified representation)
  ctx.fillStyle = '#000'
  const barWidth = 2
  const barHeight = 60
  const startY = 20
  
  // Simple barcode pattern based on digits
  for (let i = 0; i < props.barcodeData.length; i++) {
    const digit = parseInt(props.barcodeData[i])
    if (!isNaN(digit)) {
      // Draw bars based on digit value
    if (digit % 2 === 0) {
        ctx.fillRect(i * barWidth, startY, barWidth, barHeight)
      }
      // Add some variation for better visual representation
      if (digit > 5) {
        ctx.fillRect(i * barWidth + 1, startY + 10, barWidth - 1, barHeight - 20)
      }
    }
  }

  // Add product name
  ctx.fillStyle = '#000'
  ctx.font = '12px Arial'
  ctx.textAlign = 'center'
  ctx.fillText(props.productName || 'Product', width / 2, 15)

  // Add barcode text
  ctx.font = '10px monospace'
  ctx.fillText(props.barcodeData, width / 2, height - 5)

  return canvas.toDataURL('image/png')
}

onMounted(() => {
  // Initialize any required setup
})
</script>

<style scoped>
.barcode-printer {
  display: inline-block;
}

.btn {
  transition: all 0.3s ease;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}
</style>
