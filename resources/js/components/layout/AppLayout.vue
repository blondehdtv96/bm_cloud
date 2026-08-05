<template>
  <div class="app-layout flex h-screen w-screen overflow-hidden">
    <!-- Overlay for mobile sidebar -->
    <div 
      v-if="sidebarOpen" 
      class="fixed inset-0 bg-black/60 z-20 md:hidden"
      @click="sidebarOpen = false"
    ></div>

    <AppSidebar 
      class="fixed md:static inset-y-0 left-0 z-30 w-64 h-full flex-shrink-0 transition-transform duration-300 ease-in-out"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
      @navigate="sidebarOpen = false"
    />
    
    <div class="main-content flex-1 flex flex-col h-full overflow-hidden relative z-10 min-w-0">
      <AppHeader @toggle-sidebar="sidebarOpen = !sidebarOpen" />
      
      <main class="flex-1 overflow-y-auto p-4 md:p-6 bg-transparent relative">
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
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import AppSidebar from './AppSidebar.vue';
import AppHeader from './AppHeader.vue';

const sidebarOpen = ref(false);
const route = useRoute();

// Close sidebar on route change for mobile
watch(route, () => {
  if (window.innerWidth < 768) {
    sidebarOpen.value = false;
  }
});
</script>

<style scoped>
.app-layout {
  background: var(--bg-primary);
}

.main-content {
  background: var(--bg-primary);
}
</style>
