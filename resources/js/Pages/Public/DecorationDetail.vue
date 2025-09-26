<template>
  <div class="detail-container">
    <!-- Header -->
    <header class="detail-header">
      <div class="header-content">
        <div class="logo-section">
          <img v-if="companyInfo.logo" :src="companyInfo.logo" alt="Logo" class="logo">
          <h1 class="company-name">{{ companyInfo.name }}</h1>
        </div>
        <button @click="goBack" class="back-btn">
          <i class="bi bi-arrow-right"></i>
          العودة
        </button>
      </div>
    </header>

    <!-- Main Content -->
    <div class="detail-content">
      <div class="detail-grid">
        <!-- Media Section -->
        <div class="media-section">
          <div class="main-media">
            <!-- Images Slider -->
            <div v-if="!showVideo && decoration.images_urls && decoration.images_urls.length > 0" class="images-slider-container">
              <!-- Image Navigation -->
              <div class="image-navigation">
                <button 
                  @click="prevImage" 
                  :disabled="currentImageIndex === 0"
                  class="nav-btn prev-btn"
                >
                  <i class="bi bi-chevron-left"></i>
                </button>
                <div class="image-counter">
                  {{ currentImageIndex + 1 }} / {{ decoration.images_urls.length }}
                </div>
                <button 
                  @click="nextImage" 
                  :disabled="currentImageIndex === decoration.images_urls.length - 1"
                  class="nav-btn next-btn"
                >
                  <i class="bi bi-chevron-right"></i>
                </button>
              </div>

              <!-- Image Display -->
              <div class="image-display">
                <div 
                  v-for="(image, index) in decoration.images_urls" 
                  :key="index"
                  :class="['image-container', { active: currentImageIndex === index }]"
                >
                  <img 
                    :src="image" 
                    :alt="`${decoration.name} - صورة ${index + 1}`"
                    class="main-image"
                    @click="openImageModal(index)"
                  >
                </div>
              </div>

              <!-- Image Thumbnails -->
              <div v-if="decoration.images_urls.length > 1" class="image-thumbnails">
                <div 
                  v-for="(image, index) in decoration.images_urls" 
                  :key="index"
                  @click="setCurrentImage(index)"
                  :class="['image-thumbnail', { active: currentImageIndex === index }]"
                >
                  <img :src="image" :alt="`مصغرة ${index + 1}`" class="thumbnail-img">
                  <div class="thumbnail-overlay">
                    <i class="bi bi-eye"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- Single Image Display -->
            <img 
              v-else-if="!showVideo && decoration.image_url" 
              :src="decoration.image_url" 
              :alt="decoration.name"
              class="main-image"
              @click="openImageModal(0)"
            >
            <!-- Video File -->
            <video 
              v-if="showVideo && decoration.video_url_attribute && !isYouTubeUrl(decoration.video_url_attribute)" 
              :src="decoration.video_url_attribute" 
              controls
              class="main-video"
            >
              متصفحك لا يدعم تشغيل الفيديو
            </video>
            
            <!-- YouTube Videos Slider -->
            <div 
              v-if="showVideo && getYouTubeLinks().length > 0" 
              class="youtube-slider-container"
            >
              <!-- Video Navigation -->
              <div class="video-navigation">
                <button 
                  @click="prevVideo" 
                  :disabled="currentVideoIndex === 0"
                  class="nav-btn prev-btn"
                >
                  <i class="bi bi-chevron-left"></i>
                </button>
                <div class="video-counter">
                  {{ currentVideoIndex + 1 }} / {{ getYouTubeLinks().length }}
                </div>
                <button 
                  @click="nextVideo" 
                  :disabled="currentVideoIndex === getYouTubeLinks().length - 1"
                  class="nav-btn next-btn"
                >
                  <i class="bi bi-chevron-right"></i>
                </button>
              </div>

              <!-- Video Display -->
              <div class="video-display">
              <div 
                v-for="(link, index) in getYouTubeLinks()" 
                :key="index"
                  :class="['youtube-container', { active: currentVideoIndex === index }]"
              >
                  <div class="video-title">
                    <i class="bi bi-play-circle"></i>
                    فيديو {{ index + 1 }}
                </div>
                <iframe 
                  :src="getYouTubeEmbedUrl(link)"
                  frameborder="0" 
                  allowfullscreen
                  class="youtube-video"
                  @load="onYouTubeLoad"
                  @error="onYouTubeError"
                ></iframe>
                </div>
              </div>

              <!-- Video Thumbnails -->
              <div v-if="getYouTubeLinks().length > 1" class="video-thumbnails">
                <div 
                  v-for="(link, index) in getYouTubeLinks()" 
                  :key="index"
                  @click="setCurrentVideo(index)"
                  :class="['video-thumbnail', { active: currentVideoIndex === index }]"
                >
                  <div class="thumbnail-number">{{ index + 1 }}</div>
                  <div class="thumbnail-overlay">
                    <i class="bi bi-play-circle"></i>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- External Video URL -->
            <video 
              v-if="showVideo && decoration.video_url && !isYouTubeUrl(decoration.video_url) && !decoration.video_url_attribute" 
              :src="decoration.video_url" 
              controls
              class="main-video"
            >
              متصفحك لا يدعم تشغيل الفيديو
            </video>
            
          </div>

          <!-- Media Controls - Outside the media frame -->
          <div class="media-controls-outside">
              <button 
              v-if="decoration.images_urls && decoration.images_urls.length > 0"
                @click="showVideo = false; setCurrentImage(0)"
                :class="['media-btn', { active: !showVideo }]"
              >
                <i class="bi bi-image"></i>
              الصور ({{ decoration.images_urls.length }})
              </button>
              <button 
                v-if="getYouTubeLinks().length > 0 || decoration.video_url"
                @click="showVideo = true"
                :class="['media-btn', { active: showVideo }]"
              >
                <i class="bi bi-play-circle"></i>
                الفيديو ({{ getYouTubeLinks().length }})
              </button>
          </div>

        </div>

        <!-- Info Section -->
        <div class="info-section">
          <div class="info-header">
            <h1 class="decoration-title">{{ decoration.name }}</h1>
            <div v-if="decoration.is_featured" class="featured-badge">
              <i class="bi bi-star-fill"></i>
              مميز
            </div>
          </div>

          <div class="decoration-type">
            <i class="bi bi-tag"></i>
            <span>{{ decoration.type_name }}</span>
          </div>

          <div class="price-section">
            <div class="price">
              <span class="price-label">السعر</span>
              <span class="price-value">{{ decoration.formatted_price }}</span>
            </div>
          </div>

          <div class="description">
            <h3>الوصف</h3>
            <p>{{ decoration.description }}</p>
          </div>

          <!-- Features -->
          <div v-if="decoration.included_items && decoration.included_items.length > 0" class="features">
            <h3>المميزات المشمولة</h3>
            <ul class="features-list">
              <li v-for="item in decoration.included_items" :key="item">
                <i class="bi bi-check-circle"></i>
                {{ item }}
              </li>
            </ul>
          </div>

          <!-- Optional Items -->
          <div v-if="decoration.optional_items && decoration.optional_items.length > 0" class="optional-items">
            <h3>عناصر اختيارية</h3>
            <ul class="optional-list">
              <li v-for="item in decoration.optional_items" :key="item">
                <i class="bi bi-plus-circle"></i>
                {{ item }}
              </li>
            </ul>
          </div>

          <!-- Contact Section -->
          <div class="contact-section">
            <h3>تواصل معنا</h3>
            <div class="contact-buttons">
              <a v-if="companyInfo.phone" :href="`tel:${companyInfo.phone}`" class="contact-btn phone">
                <i class="bi bi-telephone"></i>
                اتصل بنا
              </a>
              <a v-if="companyInfo.whatsapp || companyInfo.phone" :href="getWhatsAppUrl()" target="_blank" class="contact-btn whatsapp">
                <i class="bi bi-whatsapp"></i>
                واتساب
              </a>
              <a v-if="companyInfo.email" :href="`mailto:${companyInfo.email}`" class="contact-btn email">
                <i class="bi bi-envelope"></i>
                أرسل رسالة
              </a>
              <a v-if="companyInfo.address" :href="`https://maps.google.com/?q=${companyInfo.address}`" target="_blank" class="contact-btn location">
                <i class="bi bi-geo-alt"></i>
                الموقع
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Related Decorations -->
      <div v-if="relatedDecorations.length > 0" class="related-section">
        <h2>ديكورات مشابهة</h2>
        <div class="related-grid">
          <div 
            v-for="related in relatedDecorations" 
            :key="related.id"
            @click="viewDecoration(related)"
            class="related-item"
          >
            <div class="related-image">
              <img 
                :src="related.thumbnail_url || related.image_url || '/dashboard-assets/img/placeholder.jpg'" 
                :alt="related.name"
              >
            </div>
            <div class="related-info">
              <h4>{{ related.name }}</h4>
              <p>{{ related.type_name }}</p>
              <span class="related-price">{{ related.formatted_price }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Image Modal -->
    <div v-if="showImageModal" class="image-modal" @click="closeImageModal">
      <div class="modal-content" @click.stop>
        <!-- Modal Header -->
        <div class="modal-header">
          <div class="image-counter">
            {{ currentImageIndex + 1 }} / {{ decoration.images_urls?.length || 1 }}
          </div>
          <div class="modal-controls">
            <button @click="zoomIn" class="control-btn" title="تكبير">
              <i class="bi bi-zoom-in"></i>
            </button>
            <button @click="zoomOut" class="control-btn" title="تصغير">
              <i class="bi bi-zoom-out"></i>
            </button>
            <button @click="resetZoom" class="control-btn" title="إعادة تعيين">
              <i class="bi bi-arrow-clockwise"></i>
            </button>
            <button @click="closeImageModal" class="control-btn close-btn" title="إغلاق">
              <i class="bi bi-x"></i>
            </button>
          </div>
        </div>

        <!-- Image Container -->
        <div class="image-container-modal">
          <img 
            :src="getCurrentModalImage()" 
            :alt="`${decoration.name} - صورة ${currentImageIndex + 1}`"
            class="modal-image"
            :style="{
              transform: `scale(${imageZoom}) translate(${imagePosition.x}px, ${imagePosition.y}px)`,
              cursor: isDragging ? 'grabbing' : 'grab'
            }"
            @mousedown="startDrag"
            @mousemove="drag"
            @mouseup="endDrag"
            @mouseleave="endDrag"
            @wheel="handleWheel"
          >
        </div>

        <!-- Navigation Arrows -->
        <button 
          v-if="decoration.images_urls && decoration.images_urls.length > 1"
          @click="prevImageInModal" 
          class="nav-arrow prev-arrow"
          :disabled="currentImageIndex === 0"
        >
          <i class="bi bi-chevron-left"></i>
        </button>
        <button 
          v-if="decoration.images_urls && decoration.images_urls.length > 1"
          @click="nextImageInModal" 
          class="nav-arrow next-arrow"
          :disabled="currentImageIndex === decoration.images_urls.length - 1"
        >
          <i class="bi bi-chevron-right"></i>
        </button>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner">
        <i class="bi bi-arrow-clockwise"></i>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  decoration: Object,
  relatedDecorations: Array,
  companyInfo: Object
})

const loading = ref(false)
const showVideo = ref(false)
const currentImageIndex = ref(0)
const currentVideoIndex = ref(0)
const showImageModal = ref(false)
const imageZoom = ref(1)
const imagePosition = ref({ x: 0, y: 0 })
const isDragging = ref(false)
const dragStart = ref({ x: 0, y: 0 })

const currentImage = computed(() => {
  if (props.decoration.images_urls && props.decoration.images_urls.length > 0) {
    return props.decoration.images_urls[currentImageIndex.value]
  }
  return props.decoration.image_url
})

const setCurrentImage = (index) => {
  currentImageIndex.value = index
  showVideo.value = false
}

// Image slider functions
const nextImage = () => {
  if (props.decoration.images_urls && currentImageIndex.value < props.decoration.images_urls.length - 1) {
    currentImageIndex.value++
  }
}

const prevImage = () => {
  if (currentImageIndex.value > 0) {
    currentImageIndex.value--
  }
}

// Video slider functions
const setCurrentVideo = (index) => {
  currentVideoIndex.value = index
}

const nextVideo = () => {
  if (currentVideoIndex.value < getYouTubeLinks().length - 1) {
    currentVideoIndex.value++
  }
}

const prevVideo = () => {
  if (currentVideoIndex.value > 0) {
    currentVideoIndex.value--
  }
}

// Image Modal Functions
const openImageModal = (index) => {
  currentImageIndex.value = index
  showImageModal.value = true
  imageZoom.value = 1
  imagePosition.value = { x: 0, y: 0 }
  document.body.style.overflow = 'hidden'
}

const closeImageModal = () => {
  showImageModal.value = false
  imageZoom.value = 1
  imagePosition.value = { x: 0, y: 0 }
  document.body.style.overflow = 'auto'
}

const getCurrentModalImage = () => {
  if (props.decoration.images_urls && props.decoration.images_urls.length > 0) {
    return props.decoration.images_urls[currentImageIndex.value]
  }
  return props.decoration.image_url
}

const zoomIn = () => {
  if (imageZoom.value < 3) {
    imageZoom.value += 0.2
  }
}

const zoomOut = () => {
  if (imageZoom.value > 0.5) {
    imageZoom.value -= 0.2
  }
}

const resetZoom = () => {
  imageZoom.value = 1
  imagePosition.value = { x: 0, y: 0 }
}

const startDrag = (e) => {
  isDragging.value = true
  dragStart.value = { x: e.clientX - imagePosition.value.x, y: e.clientY - imagePosition.value.y }
}

const drag = (e) => {
  if (isDragging.value) {
    imagePosition.value = {
      x: e.clientX - dragStart.value.x,
      y: e.clientY - dragStart.value.y
    }
  }
}

const endDrag = () => {
  isDragging.value = false
}

const handleWheel = (e) => {
  e.preventDefault()
  if (e.deltaY < 0) {
    zoomIn()
  } else {
    zoomOut()
  }
}

const nextImageInModal = () => {
  if (props.decoration.images_urls && currentImageIndex.value < props.decoration.images_urls.length - 1) {
    currentImageIndex.value++
    resetZoom()
  }
}

const prevImageInModal = () => {
  if (currentImageIndex.value > 0) {
    currentImageIndex.value--
    resetZoom()
  }
}

// WhatsApp function
const getWhatsAppUrl = () => {
  const phone = props.companyInfo.whatsapp || props.companyInfo.phone
  if (!phone) return '#'
  
  // Remove any non-numeric characters and add country code if needed
  const cleanPhone = phone.replace(/\D/g, '')
  const countryCode = cleanPhone.startsWith('964') ? '' : '964'
  const finalPhone = countryCode + cleanPhone
  
  // Create message about the decoration
  const message = `مرحباً، أريد الاستفسار عن ديكور: ${props.decoration.name}`
  
  return `https://wa.me/${finalPhone}?text=${encodeURIComponent(message)}`
}

const goBack = () => {
  router.visit(route('public.gallery'))
}

const viewDecoration = (decoration) => {
  router.visit(route('public.decoration.show', decoration.id))
}

// YouTube helper functions
const isYouTubeUrl = (url) => {
  if (!url) return false
  return url.includes('youtube.com') || url.includes('youtu.be')
}

const getYouTubeLinks = () => {
  const links = []
  
  console.log('Decoration data:', props.decoration)
  console.log('youtube_links:', props.decoration.youtube_links)
  console.log('video_url:', props.decoration.video_url)
  
  // Add youtube_links if available
  if (props.decoration.youtube_links && Array.isArray(props.decoration.youtube_links)) {
    const filteredLinks = props.decoration.youtube_links.filter(link => link && link.trim() !== '')
    console.log('Filtered youtube_links:', filteredLinks)
    links.push(...filteredLinks)
  }
  
  // Add video_url if it's a YouTube link and not already in the list
  if (props.decoration.video_url && isYouTubeUrl(props.decoration.video_url)) {
    if (!links.includes(props.decoration.video_url)) {
      links.push(props.decoration.video_url)
    }
  }
  
  // Remove duplicates
  const uniqueLinks = [...new Set(links)]
  
  console.log('Final YouTube links:', uniqueLinks)
  return uniqueLinks
}

const getYouTubeEmbedUrl = (url) => {
  if (!url) return ''
  
  let videoId = ''
  
  // Extract video ID from different YouTube URL formats
  if (url.includes('youtube.com/watch?v=')) {
    videoId = url.split('v=')[1].split('&')[0]
  } else if (url.includes('youtu.be/')) {
    videoId = url.split('youtu.be/')[1].split('?')[0]
  } else if (url.includes('youtube.com/embed/')) {
    videoId = url.split('embed/')[1].split('?')[0]
  }
  
  return `https://www.youtube.com/embed/${videoId}?rel=0&modestbranding=1&autoplay=0&controls=1&showinfo=0&iv_load_policy=3&fs=1&cc_load_policy=0&start=0&end=0&loop=0&playlist=${videoId}`
}

// YouTube iframe event handlers
const onYouTubeLoad = () => {
  // Video loaded successfully
}

const onYouTubeError = (error) => {
  console.error('YouTube iframe error:', error)
}

onMounted(() => {
  // Set initial image
  if (props.decoration.images_urls && props.decoration.images_urls.length > 0) {
    currentImageIndex.value = 0
  }
  
  // Set initial video
  if (getYouTubeLinks().length > 0) {
    currentVideoIndex.value = 0
  }
  
  // Debug: Log decoration data
  console.log('Decoration data:', props.decoration)
  console.log('Images URLs:', props.decoration.images_urls)
  console.log('Current image index:', currentImageIndex.value)
})
</script>

<style scoped>
.detail-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Header */
.detail-header {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
  padding: 1rem 0;
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo {
  height: 40px;
  width: auto;
  border-radius: 8px;
}

.company-name {
  font-size: 1.2rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: v-bind('companyInfo.primary_color');
  color: white;
  border: none;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.back-btn:hover {
  background: v-bind('companyInfo.primary_color_dark');
  transform: translateY(-2px);
}

/* Main Content */
.detail-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3rem;
  margin-bottom: 3rem;
}

/* Media Section */
.media-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}


.media-container:hover {
  transform: translateY(-5px);
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
}

.main-image, .main-video {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 20px;
  transition: all 0.3s ease;
}

/* Images Slider Container */
.images-slider-container {
  width: 100%;
  position: relative;
}

/* YouTube Slider Container */
.youtube-slider-container {
  width: 100%;
  position: relative;
}

/* Image Navigation */
.image-navigation {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding: 0 1rem;
}

/* Video Navigation */
.video-navigation {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding: 0 1rem;
}

.nav-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.nav-btn:hover:not(:disabled) {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.nav-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.image-counter, .video-counter {
  background: rgba(255, 255, 255, 0.9);
  color: #333;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.9rem;
  backdrop-filter: blur(10px);
}

/* Image Display */
.image-display {
  position: relative;
  width: 100%;
  height: 0;
  padding-bottom: 56.25%; /* 16:9 aspect ratio */
  overflow: hidden;
  border-radius: 15px;
}

/* Video Display */
.video-display {
  position: relative;
  width: 100%;
  height: 0;
  padding-bottom: 56.25%; /* 16:9 aspect ratio */
  overflow: hidden;
  border-radius: 15px;
}

.image-container, .youtube-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: #000;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
  transition: all 0.5s ease;
  opacity: 0;
  transform: translateX(100%);
}

.image-container.active, .youtube-container.active {
  opacity: 1;
  transform: translateX(0);
}

.image-container.active:hover, .youtube-container.active:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 35px rgba(0,0,0,0.3);
}

.video-title {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.75rem 1rem;
  border-radius: 10px 10px 0 0;
  font-weight: 600;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0;
}

.video-title i {
  font-size: 1.1rem;
}

.youtube-video {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border: none;
  border-radius: 0 0 15px 15px;
  transition: all 0.3s ease;
}

.youtube-video:hover {
  transform: scale(1.01);
}

/* Image Thumbnails */
.image-thumbnails {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  margin-top: 1rem;
  padding: 0 1rem;
  flex-wrap: wrap;
}

/* Video Thumbnails */
.video-thumbnails {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  margin-top: 1rem;
  padding: 0 1rem;
}

.image-thumbnail, .video-thumbnail {
  width: 60px;
  height: 40px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid transparent;
  overflow: hidden;
}

.image-thumbnail:hover, .video-thumbnail:hover {
  transform: scale(1.1);
  border-color: #667eea;
}

.image-thumbnail.active, .video-thumbnail.active {
  border-color: #ffd700;
  box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.3);
}

.thumbnail-number {
  color: white;
  font-weight: 600;
  font-size: 0.8rem;
  z-index: 2;
}

.thumbnail-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: all 0.3s ease;
  border-radius: 6px;
}

.video-thumbnail:hover .thumbnail-overlay {
  opacity: 1;
}

.thumbnail-overlay i {
  color: white;
  font-size: 1.2rem;
}

.thumbnail-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 6px;
}

/* Media Controls Outside */
.media-controls-outside {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 200px;
  padding: 1rem;
}

.media-btn {
  padding: 1rem 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 25px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1rem;
  font-weight: 600;
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
  min-width: 150px;
  justify-content: center;
}

.media-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
}

.media-btn.active {
  background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
  color: white;
  box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
}

.media-btn i {
  font-size: 1rem;
}

/* Image Modal Styles */
.image-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  backdrop-filter: blur(10px);
}

.modal-content {
  position: relative;
  width: 90%;
  height: 90%;
  max-width: 1200px;
  max-height: 800px;
  background: #000;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
}

.modal-header {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.8) 0%, transparent 100%);
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  z-index: 10;
}

.modal-header .image-counter {
  background: rgba(255, 255, 255, 0.9);
  color: #333;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.9rem;
  backdrop-filter: blur(10px);
}

.modal-controls {
  display: flex;
  gap: 0.5rem;
}

.control-btn {
  background: rgba(255, 255, 255, 0.9);
  color: #333;
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.control-btn:hover {
  background: rgba(255, 255, 255, 1);
  transform: scale(1.1);
}

.control-btn.close-btn {
  background: rgba(255, 107, 107, 0.9);
  color: white;
}

.control-btn.close-btn:hover {
  background: rgba(255, 107, 107, 1);
}

.image-container-modal {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  position: relative;
}

.modal-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  transition: transform 0.3s ease;
  user-select: none;
  -webkit-user-drag: none;
}

.nav-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.9);
  color: #333;
  border: none;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
  z-index: 10;
}

.nav-arrow:hover:not(:disabled) {
  background: rgba(255, 255, 255, 1);
  transform: translateY(-50%) scale(1.1);
}

.nav-arrow:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.prev-arrow {
  left: 1rem;
}

.next-arrow {
  right: 1rem;
}

.nav-arrow i {
  font-size: 1.5rem;
}


.thumbnail-gallery {
  display: flex;
  gap: 0.75rem;
  overflow-x: auto;
  padding: 1rem 0;
  margin-top: 1rem;
}

.thumbnail-gallery::-webkit-scrollbar {
  height: 6px;
}

.thumbnail-gallery::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.1);
  border-radius: 3px;
}

.thumbnail-gallery::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.3);
  border-radius: 3px;
}

.thumbnail-gallery::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.5);
}

.thumbnail {
  flex-shrink: 0;
  width: 90px;
  height: 90px;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  border: 3px solid transparent;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.thumbnail:hover {
  transform: scale(1.05);
  border-color: #667eea;
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.thumbnail.active {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.4);
  transform: scale(1.05);
}

.thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: all 0.3s ease;
}

.thumbnail:hover img {
  transform: scale(1.1);
}

/* Info Section */
.info-section {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  height: fit-content;
  transition: all 0.3s ease;
}

.info-section:hover {
  transform: translateY(-5px);
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
}

.info-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.decoration-title {
  font-size: 1.8rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
  flex: 1;
}

.featured-badge {
  background: linear-gradient(45deg, #ffd700, #ffed4e);
  color: #333;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.decoration-type {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: v-bind('companyInfo.primary_color');
  font-weight: 500;
  margin-bottom: 1.5rem;
}

.price-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem;
  border-radius: 20px;
  margin-bottom: 2rem;
  text-align: center;
  box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
  transition: all 0.3s ease;
}

.price-section:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 45px rgba(102, 126, 234, 0.4);
}

.price-label {
  display: block;
  font-size: 0.9rem;
  opacity: 0.9;
  margin-bottom: 0.5rem;
}

.price-value {
  font-size: 2rem;
  font-weight: 700;
}

.description {
  margin-bottom: 2rem;
}

.description h3 {
  color: #2d3748;
  margin-bottom: 1rem;
  font-size: 1.2rem;
}

.description p {
  color: #4a5568;
  line-height: 1.6;
}

.features, .optional-items {
  margin-bottom: 2rem;
}

.features h3, .optional-items h3 {
  color: #2d3748;
  margin-bottom: 1rem;
  font-size: 1.2rem;
}

.features-list, .optional-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.features-list li, .optional-list li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0;
  color: #4a5568;
}

.features-list i {
  color: #48bb78;
}

.optional-list i {
  color: v-bind('companyInfo.primary_color');
}

.contact-section {
  border-top: 1px solid #e2e8f0;
  padding-top: 2rem;
}

.contact-section h3 {
  color: #2d3748;
  margin-bottom: 1rem;
  font-size: 1.2rem;
}

.contact-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.contact-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
}

.contact-btn.phone {
  background: #48bb78;
  color: white;
}

.contact-btn.email {
  background: #4299e1;
  color: white;
}

.contact-btn.location {
  background: #ed8936;
  color: white;
}

.contact-btn.whatsapp {
  background: #25d366;
  color: white;
}

.contact-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Related Section */
.related-section {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.related-section h2 {
  color: #2d3748;
  margin-bottom: 1.5rem;
  font-size: 1.5rem;
}

.related-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.related-item {
  border-radius: 10px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #f7fafc;
}

.related-item:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.related-image {
  height: 120px;
  overflow: hidden;
}

.related-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.related-info {
  padding: 1rem;
}

.related-info h4 {
  font-size: 1rem;
  color: #2d3748;
  margin: 0 0 0.5rem 0;
}

.related-info p {
  font-size: 0.9rem;
  color: #4a5568;
  margin: 0 0 0.5rem 0;
}

.related-price {
  font-size: 0.9rem;
  font-weight: 600;
  color: v-bind('companyInfo.primary_color');
}

/* Loading Overlay */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.loading-spinner {
  color: white;
  font-size: 2rem;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
  .detail-grid {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .info-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .main-image, .main-video {
    height: 300px;
  }
  
  .related-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  }
}

@media (max-width: 480px) {
  .detail-content {
    padding: 1rem;
  }
  
  .info-section {
    padding: 1.5rem;
  }
  
  .decoration-title {
    font-size: 1.5rem;
  }
  
  .price-value {
    font-size: 1.5rem;
  }
  
  .contact-buttons {
    gap: 0.25rem;
  }
  
  .contact-btn {
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
  }
}
</style>
