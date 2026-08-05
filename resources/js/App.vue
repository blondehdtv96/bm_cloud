<template>
  <div v-if="!authStore.initialized" class="init-loading flex items-center justify-center h-screen w-screen bg-primary">
    <div class="glass-card p-6 flex flex-col items-center gap-4 animate-pulse">
      <svg class="w-12 h-12 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M17.5 19C19.9853 19 22 16.9853 22 14.5C22 12.1388 20.1873 10.2016 17.8778 10.0191C17.433 6.62104 14.5262 4 11 4C7.13401 4 4 7.13401 4 11C4 11.2339 4.01146 11.4651 4.03395 11.6925C1.76189 12.0674 0 14.0768 0 16.5C0 19.5376 2.46243 22 5.5 22H17.5V19Z" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <h2 class="text-xl font-bold">SMKBM Cloud</h2>
      <p class="text-sm text-secondary">Loading your workspace...</p>
    </div>
  </div>
  <router-view v-else v-slot="{ Component }">
    <transition name="fade" mode="out-in">
      <component :is="Component" />
    </transition>
  </router-view>
  <Toast />
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from './stores/auth';
import Toast from './components/ui/Toast.vue';

const authStore = useAuthStore();

onMounted(async () => {
    if (!authStore.initialized) {
        await authStore.init();
    }
});
</script>

<style scoped>
.bg-primary {
  background: var(--bg-primary);
}
</style>
