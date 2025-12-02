<template>
 
<aside id="sidebar" class="sidebar bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
<ul class="sidebar-nav" id="sidebar-nav">
    <li class="nav-item">
        <Link  class="nav-link "  :href="route('dashboard')"   :class="{ 'collapsed': $page.url !== '/dashboard' }" >
            <i class="bi bi-grid"></i>
            <span> {{translations.dashboard  }}  </span>
               </Link>
    </li>

    <li class="nav-item" v-if="hasPermission('read order')">
    <Link  class="nav-link "  :href="route('orders.index')"  :class="{ 'collapsed':  !$page.url.startsWith('/orders') }" >
            <i class="bi bi-people"></i>
            <span>{{translations.orders  }}</span>
               </Link>
    </li>

    <li class="nav-item" v-if="hasPermission('read product')">
    <Link  class="nav-link "  :href="route('products.index')"  :class="{ 'collapsed':  !$page.url.startsWith('/products') }" >
            <i class="bi bi-people"></i>
            <span>{{translations.products  }}</span>
               </Link>
    </li>

    <li class="nav-item" v-if="hasPermission('read product')">
    <Link  class="nav-link "  :href="route('barcode.index')"  :class="{ 'collapsed':  !$page.url.startsWith('/barcode') }" >
            <i class="bi bi-qr-code"></i>
            <span>{{translations.barcode_generation  }}</span>
               </Link>
    </li>

    <!-- Decorations Section -->
    <li class="nav-item" v-if="hasPermission('read decoration')">
        <a class="nav-link " data-bs-target="#decorations-nav" data-bs-toggle="collapse" href="#"  :class="{ 'collapsed':  !$page.url.startsWith('/decorations') && !$page.url.startsWith('/decoration-orders') && !$page.url.startsWith('/decoration-payments') && !$page.url.startsWith('/decoration-monthly-accounting') }" >
            <i class="bi bi-palette"></i><span>{{translations.decorations  }}</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="decorations-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
            <li>
                <Link   :href="route('decorations.dashboard')"  :class="{ 'collapsed':  !$page.url.startsWith('/decorations-dashboard') }" >
            <i class="bi bi-circle"></i>
            <span>{{translations.decorations_dashboard  }}</span>
               </Link>
            </li>
   
            <li>
                <Link   :href="route('decorations.orders')"  :class="{ 'collapsed':  !$page.url.startsWith('/decorations-orders') }" >
            <i class="bi bi-circle"></i>
            <span>{{translations.decoration_orders  }}</span>
               </Link>
            </li>
            <li v-if="hasPermission('read payment')">
                <Link   :href="route('decoration.payments')"  :class="{ 'collapsed':  !$page.url.startsWith('/decoration-payments') }" >
            <i class="bi bi-circle"></i>
            <span>إدارة الدفعات</span>
               </Link>
            </li>
            <li v-if="hasPermission('read monthly_accounting')">
                <Link   :href="route('decoration.monthly.accounting')"  :class="{ 'collapsed':  !$page.url.startsWith('/decoration-monthly-accounting') }" >
            <i class="bi bi-circle"></i>
            <span>المحاسبة الشهرية</span>
               </Link>
            </li>
        </ul>
    </li>

    <li class="nav-item" v-if="hasPermission('read boxes')">
    <Link  class="nav-link "  :href="route('boxes.index')"  :class="{ 'collapsed':  !$page.url.startsWith('/boxes') }" >
            <i class="bi bi-people"></i>
            <span>{{translations.boxes  }}</span>
               </Link>
    </li>
    
    <li class="nav-item" v-if="hasPermission('read supplier')">
    <Link  class="nav-link "  :href="route('customers.index')"  :class="{ 'collapsed':  !$page.url.startsWith('/customers') }" >
            <i class="bi bi-people"></i>
            <span>{{translations.customers  }}</span>
               </Link>
    </li>

    <li class="nav-item" v-if="hasPermission('read supplier')">
    <Link  class="nav-link "  :href="route('suppliers.index')"  :class="{ 'collapsed':  !$page.url.startsWith('/suppliers') }" >
            <i class="bi bi-people"></i>
            <span>{{translations.suppliers  }}</span>
               </Link>
    </li>
    
    <li class="nav-item" v-if="hasPermission('read users')">
    <Link  class="nav-link "  :href="route('users.index')"  :class="{ 'collapsed':  !$page.url.startsWith('/users') }" >
            <i class="bi bi-people"></i>
            <span>{{translations.users  }}</span>
               </Link>
    </li>



    <li class="nav-item" >
        <a class="nav-link " data-bs-target="#components-nav" data-bs-toggle="collapse" href="#"  :class="{ 'collapsed':  !$page.url.startsWith('/roles') && !$page.url.startsWith('/permissions') }" >
            <i class="bi bi-lock"></i><span>{{translations.roles_control  }}</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="components-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
            <li>
                <Link   :href="route('roles.index')"  :class="{ 'collapsed':  !$page.url.startsWith('/roles') }" >
            <i class="bi bi-circle"></i>
            <span>{{translations.roles  }}</span>
               </Link>
            </li>
            <li>
                <Link   :href="route('permissions.index')"  :class="{ 'collapsed':  !$page.url.startsWith('/permissions') }" >
            <i class="bi bi-circle"></i>
            <span>{{translations.permissions  }}</span>
               </Link>
            </li>
        </ul>
    </li>
    
    <li class="nav-item" v-if="hasPermission('read logs')">
    <Link  class="nav-link "  :href="route('logs')"  :class="{ 'collapsed':  !$page.url.startsWith('/logs') }" >
            <i class="bi bi-database"></i>
            <span>{{translations.logs  }}</span>
               </Link>
    </li>

    <li class="nav-item">
    <Link  class="nav-link "  :href="route('admin.migrations')"  :class="{ 'collapsed':  !$page.url.startsWith('/admin/migrations') }" >
            <i class="bi bi-gear"></i>
            <span>إدارة المايكريشنات</span>
               </Link>
    </li>

    <li class="nav-item" v-if="hasPermission('read system_config')">
    <Link  class="nav-link "  :href="route('system-config.index')"  :class="{ 'collapsed':  !$page.url.startsWith('/system-config') }" >
            <i class="bi bi-gear-fill"></i>
            <span>{{translations.system_settings}}</span>
               </Link>
    </li>

    <li class="nav-item" v-if="hasPermission('read expenses')">
    <Link  class="nav-link "  :href="route('expenses.index')"  :class="{ 'collapsed':  !$page.url.startsWith('/expenses') }" >
            <i class="bi bi-receipt"></i>
            <span>المصاريف</span>
               </Link>
    </li>



</ul>

</aside>


</template>

<style scoped>
/* تحسين الوضع الليلي - خلفيات وألوان محسنة */
.dark .nav-content {
  background: linear-gradient(135deg, #1f2937 0%, #111827 100%) !important;
  border: 2px solid #374151 !important;
  border-radius: 12px;
  margin-top: 8px;
  padding: 15px 0;
  box-shadow: 
    0 4px 6px rgba(0, 0, 0, 0.4),
    0 0 0 1px rgba(59, 130, 246, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.dark .nav-content li {
  margin: 0;
}

.dark .nav-content .nav-link {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%) !important;
  color: #f8fafc !important;
  padding: 12px 25px;
  margin: 4px 20px;
  border-radius: 8px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-weight: 600;
  border: 1px solid rgba(255, 255, 255, 0.1);
  position: relative;
  overflow: hidden;
}

.dark .nav-content .nav-link::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
  transition: left 0.5s;
}

.dark .nav-content .nav-link:hover::before {
  left: 100%;
}

.dark .nav-content .nav-link:hover {
  background: linear-gradient(135deg, #374151 0%, #1f2937 100%) !important;
  color: #ffffff !important;
  border-color: #4b5563 !important;
  transform: translateX(8px) scale(1.02);
  box-shadow: 
    0 4px 12px rgba(0, 0, 0, 0.3),
    0 0 0 1px rgba(59, 130, 246, 0.3);
}

.dark .nav-content .nav-link.collapsed {
  background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%) !important;
  color: #dbeafe !important;
  border-color: #3b82f6 !important;
  box-shadow: 
    0 4px 12px rgba(59, 130, 246, 0.4),
    0 0 0 1px rgba(59, 130, 246, 0.5),
    inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.dark .nav-content .nav-link i {
  color: #cbd5e1 !important;
  margin-right: 12px;
  font-size: 16px;
  transition: all 0.3s ease;
}

.dark .nav-content .nav-link:hover i {
  color: #ffffff !important;
  transform: scale(1.1);
}

.dark .nav-content .nav-link.collapsed i {
  color: #dbeafe !important;
  text-shadow: 0 0 8px rgba(219, 234, 254, 0.5);
}

/* Light mode styles for sub-navigation */
.nav-content {
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  margin-top: 5px;
  padding: 10px 0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.nav-content li {
  margin: 0;
}

.nav-content .nav-link {
  background-color: transparent !important;
  color: #374151 !important;
  padding: 10px 20px;
  margin: 3px 15px;
  border-radius: 6px;
  transition: all 0.3s ease;
  font-weight: 500;
  border: 1px solid transparent;
}

.nav-content .nav-link:hover {
  background-color: #f3f4f6 !important;
  color: #1f2937 !important;
  border-color: #d1d5db !important;
  transform: translateX(5px);
}

.nav-content .nav-link.collapsed {
  background-color: #dbeafe !important;
  color: #1e40af !important;
  border-color: #3b82f6 !important;
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

.nav-content .nav-link i {
  color: #6b7280 !important;
  margin-right: 10px;
  font-size: 14px;
}

.nav-content .nav-link.collapsed i {
  color: #1e40af !important;
}

/* Main navigation link styles for dark mode */
.dark .nav-link {
  color: #d1d5db !important;
  background-color: #374151 !important;
  border: 1px solid #4b5563 !important;
  font-weight: 600;
}

.dark .nav-link:hover {
  background-color: #4b5563 !important;
  color: #ffffff !important;
  border-color: #6b7280 !important;
}

.dark .nav-link.collapsed {
  color: #9ca3af !important;
  border-color: #374151 !important;
}

.dark .nav-link i {
  color: #9ca3af !important;
}

.dark .nav-link.collapsed i {
  color: #6b7280 !important;
}
 

/* Chevron icon animation */
.nav-link .bi-chevron-down {
  transition: transform 0.3s ease;
}

.nav-link[aria-expanded="true"] .bi-chevron-down {
  transform: rotate(180deg);
}

/* Collapse animation */
.nav-content.collapse {
  transition: height 0.3s ease;
}

.nav-content.collapsing {
  height: 0;
  overflow: hidden;
  transition: height 0.3s ease;
}

/* Light mode main navigation improvements */
.nav-link {
  border: 1px solid #e5e7eb !important;
  font-weight: 600;
}

.nav-link:hover {
  border-color: #d1d5db !important;
}

 

/* تحسين ألوان التبويبات الفرعية */
/* الوضع الليلي - أبيض مع تأثيرات إضافية */
.dark .sidebar-nav .nav-content a {
  color: #ffffff !important;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
  font-weight: 600;
}

.dark .sidebar-nav .nav-content a:hover {
  color: #ffffff !important;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
}

.dark .sidebar-nav .nav-content a.collapsed {
  color: #dbeafe !important;
  text-shadow: 0 0 8px rgba(219, 234, 254, 0.6);
}

/* الوضع العادي - أزرق */
.sidebar-nav .nav-content a {
  color: #1e40af !important;
}

.sidebar-nav .nav-content a:hover {
  color: #1e3a8a !important;
}

.sidebar-nav .nav-content a.collapsed {
  color: #1e40af !important;
}

/* تحسين الأيقونات */
/* الوضع الليلي - أيقونات بيضاء */
.dark .sidebar-nav .nav-content a i {
  color: #ffffff !important;
}

.dark .sidebar-nav .nav-content a.collapsed i {
  color: #dbeafe !important;
}

/* الوضع العادي - أيقونات زرقاء */
.sidebar-nav .nav-content a i {
  color: #1e40af !important;
}

.sidebar-nav .nav-content a.collapsed i {
  color: #1e40af !important;
}
</style>

<script setup>
import { Link , usePage} from '@inertiajs/vue3'
import { onMounted, nextTick } from 'vue'

const page = usePage()

const hasPermission = (permission) => {
  return page.props.auth_permissions.includes(permission);
}

// Close sidebar on mobile when clicking a link
const closeSidebarOnMobile = () => {
  if (window.innerWidth < 1200) {
    document.body.classList.remove('toggle-sidebar');
  }
};

// Auto-expand decoration submenu if on decoration pages
onMounted(() => {
  nextTick(() => {
    const currentUrl = page.url.value
    if (currentUrl.startsWith('/decorations') || currentUrl.startsWith('/decoration-orders') || currentUrl.startsWith('/decoration-payments') || currentUrl.startsWith('/decoration-monthly-accounting')) {
      const decorationsNav = document.getElementById('decorations-nav')
      if (decorationsNav) {
        decorationsNav.classList.add('show')
        decorationsNav.classList.remove('collapse')
      }
    }
    
    // Add click listeners to all sidebar links to close sidebar on mobile
    const sidebarLinks = document.querySelectorAll('#sidebar .nav-link')
    sidebarLinks.forEach(link => {
      link.addEventListener('click', closeSidebarOnMobile)
    })
  })
})

defineProps({message: String,translations: Object})
</script>

<script>
export default {
  name: 'Sidebar'
};
</script>