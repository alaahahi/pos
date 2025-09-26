<template>
  <div class="gallery-container">
    <!-- Header -->
    <header class="gallery-header">
      <div class="header-content">
        <div class="logo-section">
          <img v-if="companyInfo.logo" :src="companyInfo.logo" alt="Logo" class="logo">
          <h1 class="company-name">{{ companyInfo.name }}</h1>
        </div>
        <div class="contact-info">
          <div v-if="companyInfo.phone" class="contact-item">
            <i class="bi bi-telephone"></i>
            <span>{{ companyInfo.phone }}</span>
          </div>
          <div v-if="companyInfo.email" class="contact-item">
            <i class="bi bi-envelope"></i>
            <span>{{ companyInfo.email }}</span>
          </div>
          <div v-if="companyInfo.address" class="contact-item">
            <i class="bi bi-geo-alt"></i>
            <span>{{ companyInfo.address }}</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Filters -->
    <div class="filters-section">
      <div class="filters-container">
        <div class="search-box">
          <i class="bi bi-search"></i>
          <input 
            type="text" 
            v-model="searchQuery" 
            @input="debounceSearch"
            placeholder="البحث في الديكورات..."
            class="search-input"
          >
        </div>
        
        <div class="filter-buttons">
          <button 
            @click="setFilter('type', '')" 
            :class="['filter-btn', { active: !filters.type }]"
          >
            الكل
          </button>
          <button 
            v-for="type in types" 
            :key="type.value"
            @click="setFilter('type', type.value)" 
            :class="['filter-btn', { active: filters.type === type.value }]"
          >
            {{ type.label }}
          </button>
          <button 
            @click="setFilter('featured', filters.featured ? '' : '1')" 
            :class="['filter-btn', { active: filters.featured }]"
          >
            <i class="bi bi-star-fill"></i>
            مميز
          </button>
        </div>
      </div>
    </div>

    <!-- Gallery Grid -->
    <div class="gallery-grid" v-if="decorations.data && decorations.data.length > 0">
      <div 
        v-for="decoration in decorations.data" 
        :key="decoration.id"
        class="gallery-item"
        @click="viewDecoration(decoration)"
      >
        <div class="item-image">
          <img 
            :src="decoration.thumbnail_url || decoration.image_url || '/dashboard-assets/img/placeholder.jpg'" 
            :alt="decoration.name"
            class="main-image"
          >
          <div v-if="decoration.is_featured" class="featured-badge">
            <i class="bi bi-star-fill"></i>
          </div>
          <div v-if="decoration.video_url_attribute || decoration.video_url" class="video-indicator">
            <i v-if="isYouTubeUrl(decoration.video_url_attribute || decoration.video_url)" class="bi bi-youtube"></i>
            <i v-else class="bi bi-play-circle-fill"></i>
          </div>
          <div v-if="decoration.images && decoration.images.length > 1" class="images-count">
            <i class="bi bi-images"></i>
            <span>{{ decoration.images.length }}</span>
          </div>
        </div>
        
        <div class="item-content">
          <h3 class="item-title">{{ decoration.name }}</h3>
          <p class="item-type">{{ decoration.type_name }}</p>
          <div class="item-price">
            <span class="price">{{ decoration.formatted_price }}</span>
          </div>
          <p class="item-description">{{ truncateText(decoration.description, 100) }}</p>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon">
        <i class="bi bi-image"></i>
      </div>
      <h3>لا توجد ديكورات</h3>
      <p>لم يتم العثور على ديكورات تطابق معايير البحث</p>
    </div>

    <!-- Pagination -->
    <div v-if="decorations.data && decorations.data.length > 0" class="pagination-container">
      <nav class="pagination">
        <button 
          v-for="link in decorations.links" 
          :key="link.label"
          @click="loadPage(link.url)"
          :disabled="!link.url"
          :class="['page-btn', { active: link.active }]"
          v-html="link.label"
        ></button>
      </nav>
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
import { ref, reactive, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  decorations: Object,
  types: Array,
  companyInfo: Object,
  filters: Object
})

const loading = ref(false)
const searchQuery = ref(props.filters.search || '')
const filters = reactive({
  type: props.filters.type || '',
  featured: props.filters.featured || '',
  search: props.filters.search || ''
})

let searchTimeout = null

const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filters.search = searchQuery.value
    applyFilters()
  }, 500)
}

const setFilter = (key, value) => {
  filters[key] = value
  applyFilters()
}

const applyFilters = () => {
  loading.value = true
  router.get(route('public.gallery'), filters, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

const loadPage = (url) => {
  if (url) {
    loading.value = true
    router.visit(url, {
      onFinish: () => {
        loading.value = false
      }
    })
  }
}

const viewDecoration = (decoration) => {
  router.visit(route('public.decoration.show', decoration.id))
}

const truncateText = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

// YouTube helper functions
const isYouTubeUrl = (url) => {
  if (!url) return false
  return url.includes('youtube.com') || url.includes('youtu.be')
}

// Watch for filter changes
watch(filters, () => {
  applyFilters()
}, { deep: true })
</script>

<style scoped>
.gallery-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Header Styles */
.gallery-header {
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
  flex-wrap: wrap;
  gap: 1rem;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo {
  height: 50px;
  width: auto;
  border-radius: 8px;
}

.company-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
}

.contact-info {
  display: flex;
  gap: 2rem;
  flex-wrap: wrap;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #4a5568;
  font-size: 0.9rem;
}

.contact-item i {
  color: v-bind('companyInfo.primary_color');
}

/* Filters Section */
.filters-section {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  padding: 1.5rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.filters-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.search-box {
  position: relative;
  max-width: 400px;
  margin: 0 auto;
}

.search-box i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 3rem;
  border: 2px solid rgba(255, 255, 255, 0.2);
  border-radius: 25px;
  background: rgba(255, 255, 255, 0.9);
  font-size: 1rem;
  transition: all 0.3s ease;
}

.search-input:focus {
  outline: none;
  border-color: v-bind('companyInfo.primary_color');
  background: white;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.filter-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 0.5rem 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.filter-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
}

.filter-btn.active {
  background: v-bind('companyInfo.primary_color');
  border-color: v-bind('companyInfo.primary_color');
  color: white;
}

/* Gallery Grid - Instagram Style */
.gallery-grid {
  max-width: 1200px;
  margin: 2rem auto;
  padding: 0 1rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}

.gallery-item {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  cursor: pointer;
  position: relative;
}

.gallery-item:hover {
  transform: scale(1.02);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.item-image {
  position: relative;
  height: 280px;
  overflow: hidden;
  aspect-ratio: 1;
}

.main-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.gallery-item:hover .main-image {
  transform: scale(1.05);
}

.featured-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: linear-gradient(45deg, #ffd700, #ffed4e);
  color: #333;
  padding: 0.25rem 0.5rem;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.video-indicator {
  position: absolute;
  top: 1rem;
  left: 1rem;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  padding: 0.5rem;
  border-radius: 50%;
  font-size: 1.2rem;
}

.images-count {
  position: absolute;
  bottom: 1rem;
  right: 1rem;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 15px;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.item-content {
  padding: 1rem;
}

.item-title {
  font-size: 1rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 0.25rem 0;
  line-height: 1.3;
}

.item-type {
  color: v-bind('companyInfo.primary_color');
  font-size: 0.8rem;
  font-weight: 500;
  margin: 0 0 0.25rem 0;
}

.item-price {
  margin: 0.25rem 0;
}

.price {
  font-size: 1rem;
  font-weight: 700;
  color: #2d3748;
}

.item-description {
  color: #718096;
  font-size: 0.8rem;
  line-height: 1.4;
  margin: 0.25rem 0 0 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: white;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.7;
}

.empty-state h3 {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}

.empty-state p {
  font-size: 1rem;
  opacity: 0.8;
}

/* Pagination */
.pagination-container {
  display: flex;
  justify-content: center;
  padding: 2rem;
}

.pagination {
  display: flex;
  gap: 0.5rem;
}

.page-btn {
  padding: 0.5rem 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  cursor: pointer;
  transition: all 0.3s ease;
}

.page-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
}

.page-btn.active {
  background: v-bind('companyInfo.primary_color');
  border-color: v-bind('companyInfo.primary_color');
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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
  .header-content {
    flex-direction: column;
    text-align: center;
  }
  
  .contact-info {
    justify-content: center;
  }
  
  .gallery-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.75rem;
  }
  
  .filters-container {
    padding: 0 0.5rem;
  }
  
  .filter-buttons {
    gap: 0.25rem;
  }
  
  .filter-btn {
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
  }
}

@media (max-width: 480px) {
  .gallery-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
  }
  
  .item-image {
    height: 180px;
  }
  
  .item-content {
    padding: 0.75rem;
  }
  
  .item-title {
    font-size: 0.9rem;
  }
  
  .item-type {
    font-size: 0.7rem;
  }
  
  .price {
    font-size: 0.9rem;
  }
  
  .item-description {
    font-size: 0.7rem;
    -webkit-line-clamp: 1;
  }
}
</style>
