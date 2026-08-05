<template>
  <div class="storage-usage">
    <div class="storage-summary">
      <span class="storage-label">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
        Penyimpanan
      </span>
      <span class="storage-value">{{ formattedUsed }} / {{ formattedTotal }}</span>
    </div>
    <div class="storage-track"><div class="storage-bar" :class="colorClass" :style="`width: ${percentage}%`"></div></div>
    <div class="storage-percent">{{ percentage }}% terpakai</div>
  </div>
</template>

<style scoped>
.storage-usage { display: flex; flex-direction: column; gap: .5rem; }
.storage-summary { display: flex; align-items: center; justify-content: space-between; gap: .5rem; font-size: .72rem; font-weight: 600; }
.storage-label { display: flex; align-items: center; gap: .3rem; color: var(--text-secondary); }
.storage-value { color: var(--text-primary); white-space: nowrap; }
.storage-track { width: 100%; height: 6px; overflow: hidden; border-radius: 999px; background: var(--fill-primary); }
.storage-bar { height: 100%; border-radius: inherit; background: var(--accent-primary); transition: width .4s ease; }
.storage-percent { color: var(--text-muted); font-size: .65rem; text-align: right; }
</style>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();

const usedBytes = computed(() => authStore.user?.storage_used || 0);
const totalBytes = computed(() => authStore.user?.storage_quota || 1073741824);

const percentage = computed(() => {
  if (totalBytes.value === 0) return 0;
  return Math.min(100, Math.round((usedBytes.value / totalBytes.value) * 100));
});

const colorClass = computed(() => {
  if (percentage.value > 90) return 'bg-accent-danger';
  if (percentage.value > 75) return 'bg-accent-warning';
  return 'bg-gradient-to-r from-indigo-500 to-purple-500';
});

const formatBytes = (bytes) => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const formattedUsed = computed(() => formatBytes(usedBytes.value));
const formattedTotal = computed(() => formatBytes(totalBytes.value));
</script>
