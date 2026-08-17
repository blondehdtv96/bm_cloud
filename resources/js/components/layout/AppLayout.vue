<template>
  <div class="app-layout">
    <!-- Overlay hanya untuk mode drawer di layar kecil -->
    <transition name="overlay">
      <div
        v-if="sidebarOpen"
        class="sidebar-overlay"
        @click="closeSidebar"
      ></div>
    </transition>

    <AppSidebar
      id="app-sidebar"
      class="app-sidebar"
      :class="{ 'is-open': sidebarOpen, 'is-collapsed': collapsed }"
      :collapsed="collapsed"
      :aria-hidden="isMobile && !sidebarOpen ? 'true' : null"
      @navigate="closeSidebar"
      @toggle-collapse="toggleCollapse"
    />

    <div class="main-content">
      <AppHeader :sidebar-open="sidebarOpen" @toggle-sidebar="toggleSidebar" />

      <main class="app-main">
        <router-view v-slot="{ Component }">
          <transition name="slide-fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import AppSidebar from './AppSidebar.vue';
import AppHeader from './AppHeader.vue';

/** Harus sama dengan breakpoint md di CSS di bawah (48rem = 768px). */
const MOBILE_BREAKPOINT = 768;
const COLLAPSE_STORAGE_KEY = 'bmclouds_sidebar_collapsed';

const sidebarOpen = ref(false);
const isMobile = ref(false);
// Hanya relevan di desktop; drawer mobile selalu tampil penuh saat dibuka.
const collapsed = ref(localStorage.getItem(COLLAPSE_STORAGE_KEY) === '1');
const route = useRoute();

const syncViewport = () => {
  isMobile.value = window.innerWidth < MOBILE_BREAKPOINT;
  // Kalau layar dilebarkan ke desktop, drawer tidak relevan lagi.
  if (!isMobile.value) sidebarOpen.value = false;
};

const toggleSidebar = () => { sidebarOpen.value = !sidebarOpen.value; };
const closeSidebar = () => { sidebarOpen.value = false; };

const toggleCollapse = () => {
  collapsed.value = !collapsed.value;
  localStorage.setItem(COLLAPSE_STORAGE_KEY, collapsed.value ? '1' : '0');
};

const onKeydown = (event) => {
  if (event.key === 'Escape') closeSidebar();
};

onMounted(() => {
  syncViewport();
  window.addEventListener('resize', syncViewport);
  document.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
  window.removeEventListener('resize', syncViewport);
  document.removeEventListener('keydown', onKeydown);
});

// Tutup drawer setiap pindah halaman.
watch(() => route.fullPath, closeSidebar);
</script>

<style scoped>
.app-layout {
  display: flex;
  width: 100%;
  height: 100vh;
  overflow: hidden;
  background: var(--bg-primary);
}

/*
  Sidebar ditulis sebagai CSS eksplisit, bukan utility Tailwind di root
  komponen anak. Kelas komponen di app.css (.icon-btn, .glass-panel, dst.)
  tidak berada dalam @layer sehingga bisa mengalahkan utility seperti
  md:hidden / md:translate-x-0 dan membuat toggle tampak tidak berfungsi.
*/
.app-sidebar {
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
  z-index: 30;
  width: 16rem;
  flex-shrink: 0;
  visibility: hidden;
  transform: translateX(-100%);
  transition: transform .28s cubic-bezier(.2, .8, .2, 1), visibility .28s;
}
.app-sidebar.is-open {
  visibility: visible;
  transform: translateX(0);
}

.sidebar-overlay {
  position: fixed;
  inset: 0;
  z-index: 25;
  background: rgba(32, 33, 36, .5);
}
.overlay-enter-active, .overlay-leave-active { transition: opacity .2s ease; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }

.main-content {
  position: relative;
  z-index: 10;
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
  height: 100%;
  overflow: hidden;
  background: var(--bg-primary);
}

.app-main {
  position: relative;
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}

@media (min-width: 768px) {
  /* Desktop: sidebar jadi kolom tetap, drawer dan overlay dimatikan. */
  .app-sidebar {
    position: static;
    visibility: visible;
    transform: none;
    transition: width .22s ease;
  }
  .app-sidebar.is-collapsed { width: 4.5rem; }
  .sidebar-overlay { display: none; }
  .app-main { padding: 1.5rem; }
}
</style>
