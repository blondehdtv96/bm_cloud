<template>
  <div class="empty-state animate-fade-in">
    <div class="empty-icon"><component :is="iconComponent" /></div>
    <h2>{{ title }}</h2>
    <p>{{ description }}</p>
    <button v-if="actionText" type="button" class="btn btn-primary" @click="$emit('action')">{{ actionText }}</button>
  </div>
</template>

<style scoped>
.empty-state { width: 100%; height: 100%; min-height: 260px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; text-align: center; }
.empty-icon { width: 76px; height: 76px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.15rem; border-radius: 22px; color: var(--accent-primary); background: rgba(26, 115, 232,.09); }
.empty-icon :deep(svg) { width: 36px; height: 36px; }
h2 { margin-bottom: .35rem; font-size: 1.15rem; font-weight: 700; }
p { max-width: 28rem; margin-bottom: 1.25rem; font-size: .875rem; }
</style>

<script setup>
import { computed, h } from 'vue';

const props = defineProps({
  icon: { type: String, default: 'folder' },
  title: { type: String, default: 'Belum ada item' },
  description: { type: String, default: 'Folder ini masih kosong. Unggah file untuk mulai menggunakannya.' },
  actionText: { type: String, default: '' }
});

const iconMap = {
  folder: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.5 }, [h('path', { d: 'M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z' })]),
  search: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.5 }, [h('circle', { cx: 11, cy: 11, r: 8 }), h('line', { x1: 21, y1: 21, x2: 16.65, y2: 16.65 })]),
  trash: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.5 }, [h('polyline', { points: '3 6 5 6 21 6' }), h('path', { d: 'M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2' })])
};

const iconComponent = computed(() => iconMap[props.icon] || iconMap.folder);
</script>
