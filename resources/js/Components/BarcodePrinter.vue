<template>
  <div class="barcode-printer">
    <!-- Print Button -->
    <button 
      class="btn btn-sm btn-success" 
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
    // Create a new window for printing
    const printWindow = window.open('', '_blank', 'width=400,height=600')
    
    if (!printWindow) {
      toast.error('تم منع النافذة المنبثقة. يرجى السماح بالنوافذ المنبثقة للطباعة.')
      return
    }

    // Generate barcode image
    const barcodeImage = await generateBarcodeImage()
    
    // Create print content
    const printContent = `
      <!DOCTYPE html>
      <html>
      <head>
        <title>طباعة الباركود</title>
        <style>
          body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            text-align: center;
          }
          .barcode-container {
            border: 1px solid #ccc;
            padding: 10px;
            margin: 10px 0;
            display: inline-block;
          }
          .product-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
          }
          .barcode-image {
            max-width: 100%;
            height: auto;
          }
          .barcode-text {
            font-size: 12px;
            margin-top: 5px;
            font-family: monospace;
          }
          @media print {
            body { margin: 0; }
            .barcode-container { 
              page-break-inside: avoid;
              margin: 5px;
            }
          }
        </style>
      </head>
      <body>
        <div class="barcode-container">
          <div class="product-name">${props.productName}</div>
          <img src="${barcodeImage}" alt="Barcode" class="barcode-image">
          <div class="barcode-text">${props.barcodeData}</div>
        </div>
        <script>
          window.onload = function() {
            window.print();
            window.onafterprint = function() {
              window.close();
            };
          };
        </script>
      </body>
      </html>
    `

    printWindow.document.write(printContent)
    printWindow.document.close()

    toast.success('تم إرسال الباركود للطباعة')

  } catch (error) {
    console.error('Print error:', error)
    toast.error('حدث خطأ أثناء الطباعة')
  } finally {
    loading.value = false
  }
}

const generateBarcodeImage = async () => {
  try {
    // Use the barcode service to generate image
    const response = await fetch(route('barcode.preview'), {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        code: props.barcodeData,
        type: props.printerSettings.type,
        width: props.printerSettings.width,
        height: props.printerSettings.height
      })
    })

    if (response.ok) {
      const blob = await response.blob()
      return URL.createObjectURL(blob)
    } else {
      throw new Error('Failed to generate barcode image')
    }
  } catch (error) {
    console.error('Barcode generation error:', error)
    throw error
  }
}

// Alternative method using canvas (if needed)
const generateBarcodeWithCanvas = () => {
  const canvas = barcodeCanvas.value
  if (!canvas) return

  const ctx = canvas.getContext('2d')
  const width = 200
  const height = 100

  canvas.width = width
  canvas.height = height

  // Clear canvas
  ctx.clearRect(0, 0, width, height)

  // Draw barcode pattern (simplified)
  ctx.fillStyle = '#000'
  const barWidth = 2
  const barHeight = 60
  
  for (let i = 0; i < props.barcodeData.length; i++) {
    const digit = parseInt(props.barcodeData[i])
    if (digit % 2 === 0) {
      ctx.fillRect(i * barWidth, 20, barWidth, barHeight)
    }
  }

  // Add product name
  ctx.fillStyle = '#000'
  ctx.font = '12px Arial'
  ctx.textAlign = 'center'
  ctx.fillText(props.productName, width / 2, 15)

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