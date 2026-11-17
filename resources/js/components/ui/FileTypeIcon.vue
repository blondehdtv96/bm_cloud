<template>
  <span
    class="file-type-icon"
    :class="`is-${size}`"
    :style="{ color: type.color, background: type.bg }"
    :title="type.kind"
    aria-hidden="true"
  >
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
      <polyline points="14 2 14 8 20 8"></polyline>
    </svg>
    <span v-if="size === 'lg'" class="file-type-label" :style="{ background: type.color }">{{ type.label }}</span>
  </span>
</template>

<script setup>
import { computed } from 'vue';
import { fileType } from '../../composables/fileTypes';

const props = defineProps({
  name: { type: String, default: '' },
  size: { type: String, default: 'md' }, // sm | md | lg
});

const type = computed(() => fileType(props.name));
</script>

<style scoped>
.file-type-icon {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.is-sm { width: 32px; height: 32px; border-radius: 8px; }
.is-sm svg { width: 18px; height: 18px; }
.is-md { width: 40px; height: 40px; border-radius: 10px; }
.is-md svg { width: 22px; height: 22px; }
.is-lg { width: 64px; height: 64px; border-radius: 16px; }
.is-lg svg { width: 40px; height: 40px; }
.file-type-label {
  position: absolute;
  left: 50%;
  bottom: 13px;
  transform: translateX(-50%);
  padding: 1px 4px;
  border-radius: 4px;
  color: #fff;
  font-size: 9px;
  font-weight: 700;
  line-height: 1.3;
  letter-spacing: .03em;
}
</style>
