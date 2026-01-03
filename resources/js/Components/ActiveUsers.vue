<template>
  <li class="nav-item dropdown">
    <a 
      class="nav-link nav-icon position-relative active-users-icon" 
      href="#" 
      data-bs-toggle="dropdown"
      aria-expanded="false"
      title="المستخدمين النشطين"
    >
      <div class="active-users-wrapper">
        <div class="active-users-avatars">
          <div 
            v-for="(user, index) in activeUsers.slice(0, 3)" 
            :key="user.id"
            class="user-avatar-circle"
            :style="{ 
              backgroundColor: getColorForUser(user.name),
              zIndex: 10 - index,
              marginLeft: index > 0 ? '-8px' : '0'
            }"
            :title="user.name"
          >
            <span class="user-initials">{{ getInitials(user.name) }}</span>
            <span class="user-status-indicator"></span>
          </div>
          <div 
            v-if="activeUsers.length === 0"
            class="user-avatar-circle empty-state"
            title="لا يوجد مستخدمين نشطين"
          >
            <i class="bi bi-people"></i>
          </div>
        </div>
      </div>
      <span 
        v-if="activeUsersCount > 3" 
        class="badge bg-success badge-number position-absolute top-0 start-100 translate-middle"
        style="font-size: 0.65rem; padding: 0.25em 0.4em;"
      >
        +{{ activeUsersCount - 3 }}
      </span>
    </a>
    
    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow" style="min-width: 250px;">
      <li class="dropdown-header">
        <h6>المستخدمين النشطين</h6>
        <span class="text-muted" style="font-size: 0.85rem;">آخر 3 دقائق</span>
      </li>
      <li><hr class="dropdown-divider"></li>
      
      <li v-if="activeUsers.length === 0" class="px-3 py-2 text-muted text-center">
        <small>لا يوجد مستخدمين نشطين</small>
      </li>
      
      <li v-for="user in activeUsers" :key="user.id" class="px-3 py-2">
        <div class="d-flex align-items-center">
          <img 
            :src="user.avatar_url || '/dashboard-assets/img/profile-img.jpg'" 
            alt="" 
            class="rounded-circle me-2"
            style="width: 32px; height: 32px; object-fit: cover;"
          >
          <div class="flex-grow-1">
            <div class="fw-semibold" style="font-size: 0.9rem;">{{ user.name }}</div>
            <small class="text-muted">{{ user.last_activity_at }}</small>
          </div>
          <span class="badge bg-success rounded-pill" style="width: 8px; height: 8px; padding: 0;"></span>
        </div>
      </li>
      
      <li v-if="activeUsers.length > 0"><hr class="dropdown-divider"></li>
      <li class="px-3 py-2">
        <small class="text-muted">
          <i class="bi bi-arrow-clockwise"></i> يتم التحديث تلقائياً
        </small>
      </li>
    </ul>
  </li>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const activeUsers = ref([]);
const activeUsersCount = ref(0);
let pollInterval = null;

const fetchActiveUsers = async () => {
  try {
    const response = await axios.get('/api/active-users');
    activeUsers.value = response.data.active_users || [];
    activeUsersCount.value = response.data.count || 0;
  } catch (error) {
    console.error('Error fetching active users:', error);
  }
};

// الحصول على أول حرفين من الاسم
const getInitials = (name) => {
  if (!name) return '??';
  const words = name.trim().split(' ');
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
};

// الحصول على لون فريد لكل مستخدم بناءً على اسمه
const getColorForUser = (name) => {
  if (!name) return '#6c757d';
  
  // قائمة ألوان جذابة
  const colors = [
    '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6',
    '#1abc9c', '#e67e22', '#34495e', '#16a085', '#c0392b',
    '#2980b9', '#27ae60', '#d35400', '#8e44ad', '#2c3e50'
  ];
  
  // إنشاء hash بسيط من الاسم
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  
  // استخدام hash لاختيار لون
  const index = Math.abs(hash) % colors.length;
  return colors[index];
};

onMounted(() => {
  // جلب البيانات فوراً
  fetchActiveUsers();
  
  // تحديث كل 30 ثانية
  pollInterval = setInterval(fetchActiveUsers, 30000);
});

onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval);
  }
});
</script>

<style scoped>
.active-users-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
}

.active-users-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.active-users-avatars {
  display: flex;
  align-items: center;
  position: relative;
}

.user-avatar-circle {
  position: relative;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 0.75rem;
  border: 2px solid #fff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  cursor: pointer;
}

.user-avatar-circle:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  z-index: 20 !important;
}

.user-avatar-circle.empty-state {
  background-color: #e9ecef;
  color: #6c757d;
  border-color: #dee2e6;
}

.user-avatar-circle.empty-state i {
  font-size: 1rem;
}

.user-initials {
  font-size: 0.7rem;
  line-height: 1;
  text-align: center;
  user-select: none;
}

.user-status-indicator {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 10px;
  height: 10px;
  background-color: #28a745;
  border: 2px solid #fff;
  border-radius: 50%;
  animation: pulse 2s infinite;
  box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
}

.dark .user-status-indicator {
  border-color: #1f2937;
}

.dark .user-avatar-circle {
  border-color: #374151;
}

.dark .user-avatar-circle.empty-state {
  background-color: #374151;
  color: #9ca3af;
  border-color: #4b5563;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
  }
  70% {
    box-shadow: 0 0 0 4px rgba(40, 167, 69, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
  }
}

.dropdown-menu {
  max-height: 400px;
  overflow-y: auto;
}

.badge-number {
  font-size: 0.65rem;
  padding: 0.25em 0.4em;
  font-weight: 600;
}
</style>

