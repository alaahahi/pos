
<template>

    <!-- ======= Header ======= -->
    <header id="header" class="header fixed-top d-flex align-items-center justify-content-between bg-gray-100 dark:bg-gray-800">   

        <div class="d-flex align-items-center">
            <!-- Hamburger Menu Button (Mobile Only) -->
            <button 
                @click="toggleSidebar" 
                class="mobile-menu-toggle d-lg-none btn btn-link p-0 text-gray-600 dark:text-gray-400 me-3"
                type="button"
                aria-label="Toggle sidebar"
            >
                <i class="bi bi-list fs-3"></i>
            </button>
            <Link class="logo d-flex align-items-center" :href="route('dashboard')">
            <img :src="`/${companyLogo}`" alt="">
            <span class="d-none d-lg-block dark:text-white">{{ companyName }}</span>
            </Link>
        </div>
        <!-- End Logo -->
   

        <nav class="header-nav ms-auto text-gray-600 dark:text-gray-400">
            <ul class="d-flex align-items-center text-gray-600 dark:text-gray-400">

                <!-- Language Selector -->
                <li class="nav-item dropdown text-gray-600 dark:text-gray-400 me-2">
                    <select class="form-control changeLang" @change="changeLanguage" style="min-width: 120px;">
                        <option value="" selected> {{ translations.language || 'اللغة' }} 🌍 </option>
                        <option value="en"> English</option>
                        <option value="ar">العربية</option>
                    </select>
                </li>
                <!-- End Language Selector -->

                <!-- Dark Mode Toggle -->
                <li class="nav-item text-gray-600 dark:text-gray-400 me-2">
                    <DarkModeToggle />
                </li>
                <!-- End Dark Mode Toggle -->

                <!-- Active Users -->
                <li class="nav-item text-gray-600 dark:text-gray-400 me-2">
                    <ActiveUsers />
                </li>
                <!-- End Active Users -->

                <!-- Notifications -->
                <li class="nav-item dropdown text-gray-600 dark:text-gray-400 me-2">
                    <Link
                        class="nav-link nav-icon position-relative" 
            :href="route('notification.index')"
          >
                        <i class="bi bi-bell"></i>
                        <span v-if="notificationCount > 0" class="badge bg-primary badge-number position-absolute top-0 start-100 translate-middle">
                            {{ notificationCount }}
                        </span>
                 </Link>
                    <!-- End Notification Icon -->
                </li>
                <!-- End Notification Nav -->

                <!-- Sync/WiFi Icon -->
                <li class="nav-item dropdown text-gray-600 dark:text-gray-400 me-2">
                    <WiFiIconHeader :pendingCount="syncPendingCount" />
                </li>
                <!-- End Sync Icon -->
<!-- 
                <li class="nav-item dropdown">

                    <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-chat-left-text"></i>
                        <span class="badge bg-success badge-number">3</span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow messages">
                        <li class="dropdown-header">
                            You have 3 new messages
                            <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li class="message-item">
                            <a href="#">
                                <img src="/dashboard-assets/img/messages-1.jpg" alt="" class="rounded-circle">
                                <div>
                                    <h4>Maria Hudson</h4>
                                    <p>Velit asperiores et ducimus soluta repudiandae labore officia est ut...</p>
                                    <p>4 hrs. ago</p>
                                </div>
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li class="message-item">
                            <a href="#">
                                <img src="/dashboard-assets/img/messages-2.jpg" alt="" class="rounded-circle">
                                <div>
                                    <h4>Anna Nelson</h4>
                                    <p>Velit asperiores et ducimus soluta repudiandae labore officia est ut...</p>
                                    <p>6 hrs. ago</p>
                                </div>
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li class="message-item">
                            <a href="#">
                                <img src="/dashboard-assets/img/messages-3.jpg" alt="" class="rounded-circle">
                                <div>
                                    <h4>David Muldon</h4>
                                    <p>Velit asperiores et ducimus soluta repudiandae labore officia est ut...</p>
                                    <p>8 hrs. ago</p>
                                </div>
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li class="dropdown-footer">
                            <a href="#">Show all messages</a>
                        </li>

                    </ul>

                </li> -->
                <!-- End Messages Nav -->

                <!-- User Profile -->
                <li class="nav-item dropdown pe-3 text-gray-600 dark:text-gray-400">

                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                        <img :src="user.avatar_url || '/dashboard-assets/img/profile-img.jpg'" alt="Profile" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                        <span class="d-none d-md-block dropdown-toggle ps-2 dark:text-white">{{ user.name }}</span>
                    </a>
                    <!-- End Profile Iamge Icon -->

                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile text-gray-600 dark:text-gray-400">
                        <li class="dropdown-header">
                            <h6 class="dark:text-white">{{ user.name }}</h6>
                            <span class="dark:text-white">{{ user.email }}</span>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <a class="dropdown-item d-flex align-items-center dark:text-white" :href="route('profile.edit')">
                                <i class="bi bi-person"></i>
                                <span>{{translations.my_profile || 'حسابي الشخصي'}} </span>
                            </a>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <Link :href="route('logout')" method="post" as="button"
                                class="dropdown-item d-flex align-items-center">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>{{translations.log_out || 'تسجيل خروج'}}</span>
                            </Link>



                        </li>

                    </ul>
                    <!-- End Profile Dropdown Items -->
                </li>
                <!-- End Profile Nav -->

            </ul>
        </nav>
        <!-- End Icons Navigation -->




    </header>

    <!-- Sidebar Overlay (Mobile Only) -->
    <div 
        v-if="isSidebarOpen" 
        @click="closeSidebar"
        class="sidebar-overlay d-lg-none"
    ></div>

    <!-- Include the Sidebar here -->
    <Sidebar :translations="translations" :permissions=" page.props.Permissions" />

    <!-- Include the main content here -->

    <main id="main" class="main bg-gray-100 dark:bg-gray-800" >
        <div v-if="flashSuccess" class="alert alert-success bg-success text-light border-0 alert-dismissible fade show"
            role="alert">
             {{flashSuccess }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <main>
            <slot />
        </main>
    </main>

</template>


<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import DarkModeToggle from '@/Components/DarkToggle.vue';

const page = usePage();
defineProps({translations: Object})

const showingNavigationDropdown = ref(false);
const isSidebarOpen = ref(false);

// Access window.translations safely
const translations = computed(() => window.translations || {});

// Company name and logo from props
const companyName = computed(() => page.props.company_name || 'WEDOO EVENTS');
const companyLogo = computed(() => page.props.company_logo || 'dashboard-assets/img/WEDOO  LOGO PNG.webp');

// Toggle sidebar function
const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
    if (isSidebarOpen.value) {
        document.body.classList.add('toggle-sidebar');
    } else {
        document.body.classList.remove('toggle-sidebar');
    }
};

// Close sidebar function
const closeSidebar = () => {
    isSidebarOpen.value = false;
    document.body.classList.remove('toggle-sidebar');
};

// Close sidebar when clicking outside on mobile
const handleResize = () => {
    if (window.innerWidth >= 1200) {
        isSidebarOpen.value = false;
        document.body.classList.remove('toggle-sidebar');
    }
};

onMounted(() => {
    window.addEventListener('resize', handleResize);
    // Ensure sidebar is hidden by default on mobile
    if (window.innerWidth < 1200) {
        document.body.classList.remove('toggle-sidebar');
        isSidebarOpen.value = false;
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});

// Change language function
const changeLanguage = (event) => {
    const selectedLanguage = event.target.value;
    const currentUrl = window.location.origin;
    const newUrl = `${currentUrl}/lang/change?lang=${selectedLanguage}`;
    window.location.href = newUrl;
};
</script>

<script>
import Sidebar from '@/Components/SideBar.vue';
import SyncStatusBar from '@/Components/SyncStatusBar.vue';
import WiFiIconHeader from '@/Components/WiFiIconHeader.vue';
import ActiveUsers from '@/Components/ActiveUsers.vue';
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()

const flashSuccess = computed(
    () => page.props.flash.success,
)

const user = computed(
    () => page.props.auth,
)

const notificationCount = computed(
  () => Math.min(page.props.auth.notificationCount, 9),
)

const syncPendingCount = computed(
  () => page.props.syncPendingCount || 0,
)
    
export default {
    components: {
        Sidebar,
        SyncStatusBar,
        WiFiIconHeader,
        ActiveUsers,
    },
  data() {
    return {
      parentMessage: 'Hello from Parent',
    };
  },
};
</script>

<style scoped>
/* Header Navigation Spacing */
.header-nav ul {
    gap: 0.5rem;
}

.header-nav .nav-item {
    margin: 0;
}

.header-nav .changeLang {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    border: 1px solid #dee2e6;
    background-color: #fff;
}

.header-nav .changeLang:focus {
    outline: none;
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.dark .header-nav .changeLang {
    background-color: #1f2937;
    border-color: #374151;
    color: #d1d5db;
}

/* Sidebar Overlay for Mobile */
.sidebar-overlay {
    position: fixed;
    top: 60px;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 995;
    transition: opacity 0.3s ease;
}

/* Hamburger Menu Button Styles */
.mobile-menu-toggle {
    border: none;
    background: transparent;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    min-height: 40px;
}

.mobile-menu-toggle:hover {
    opacity: 0.7;
    transform: scale(1.1);
}

.mobile-menu-toggle:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
    border-radius: 4px;
}

.dark .mobile-menu-toggle {
    color: #d1d5db;
}

.dark .mobile-menu-toggle:hover {
    color: #ffffff;
}
</style>
