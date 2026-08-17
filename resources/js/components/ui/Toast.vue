<template>
  <div class="toast-region" aria-live="polite">
    <TransitionGroup name="toast-slide">
      <div v-for="toast in toasts" :key="toast.id" class="toast-card" :class="`toast-${toast.type}`">
        <div class="toast-icon">
          <svg v-if="toast.type === 'success'" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          <svg v-else-if="toast.type === 'error'" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
          <svg v-else class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        </div>
        <div class="toast-content"><h4>{{ toast.title }}</h4><p>{{ toast.message }}</p></div>
        <button type="button" class="toast-close" aria-label="Tutup notifikasi" @click="removeToast(toast.id)">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script>
import { ref } from 'vue';
const toasts = ref([]);
let nextId = 1;
export const addToast = (toast) => {
  const id = nextId++;
  toasts.value.push({ ...toast, id });
  setTimeout(() => removeToast(id), toast.duration || 5000);
};
export const removeToast = (id) => {
  const index = toasts.value.findIndex(toast => toast.id === id);
  if (index > -1) toasts.value.splice(index, 1);
};
</script>

<script setup></script>

<style scoped>
.toast-region { position: fixed; top: 1rem; left: 50%; z-index: 70; width: min(92vw, 390px); display: flex; flex-direction: column; gap: .6rem; transform: translateX(-50%); pointer-events: none; }
.toast-card { display: flex; align-items: flex-start; gap: .75rem; padding: .8rem .8rem .8rem .9rem; border: 1px solid var(--separator); border-radius: 12px; background: var(--bg-secondary); box-shadow: var(--shadow-popover); pointer-events: auto; }
.toast-icon { width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border-radius: 10px; color: var(--accent-primary); background: rgba(26, 115, 232,.10); }
.toast-success .toast-icon { color: #1e8e3e; background: rgba(30, 142, 62,.12); }
.toast-error .toast-icon { color: #b3261e; background: rgba(217, 48, 37,.10); }
.toast-warning .toast-icon { color: #a35a00; background: rgba(249, 171, 0,.12); }
.toast-content { min-width: 0; flex: 1; }
.toast-content h4 { font-size: .875rem; font-weight: 700; }
.toast-content p { margin-top: .15rem; font-size: .78rem; line-height: 1.4; }
.toast-close { width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; border: 0; border-radius: 50%; color: var(--text-muted); background: transparent; cursor: pointer; }
.toast-close:hover { color: var(--text-primary); background: var(--fill-secondary); }
.toast-slide-enter-active, .toast-slide-leave-active { transition: opacity .2s ease, transform .22s cubic-bezier(.2,.8,.2,1); }
.toast-slide-enter-from, .toast-slide-leave-to { opacity: 0; transform: translateY(-12px) scale(.98); }
</style>
