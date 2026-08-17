<template>
  <div v-if="visible" class="modal-root" role="dialog" aria-modal="true" :aria-label="title">
    <div class="modal-backdrop" @click="$emit('close')"></div>
    <section class="modal-sheet" :class="`modal-${size}`">
      <div class="modal-handle" aria-hidden="true"></div>
      <header class="modal-header">
        <h3>{{ title }}</h3>
        <button type="button" class="modal-close" aria-label="Tutup" @click="$emit('close')">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </header>
      <div class="modal-body"><slot></slot></div>
      <footer v-if="$slots.footer" class="modal-footer"><slot name="footer"></slot></footer>
    </section>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  visible: Boolean,
  title: String,
  size: { type: String, default: 'md' }
});

const emit = defineEmits(['close']);
const onKeydown = (event) => {
  if (event.key === 'Escape' && props.visible) emit('close');
};

onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown);
  document.body.style.overflow = '';
});
watch(() => props.visible, (visible) => {
  document.body.style.overflow = visible ? 'hidden' : '';
});
</script>

<style scoped>
.modal-root { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
.modal-backdrop { position: absolute; inset: 0; background: rgba(32, 33, 36, .55); }
.modal-sheet { position: relative; width: 100%; max-height: min(82vh, 760px); display: flex; flex-direction: column; overflow: hidden; background: var(--bg-secondary); border: 1px solid var(--separator); border-radius: var(--radius-card); box-shadow: var(--shadow-popover); animation: modal-in .22s cubic-bezier(.2,.8,.2,1); }
.modal-sm { max-width: 24rem; }
.modal-md { max-width: 32rem; }
.modal-lg { max-width: 42rem; }
.modal-handle { display: none; }
.modal-header { min-height: 58px; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .8rem 1rem .8rem 1.25rem; border-bottom: 1px solid var(--separator); }
.modal-header h3 { font-size: 1.05rem; font-weight: 700; }
.modal-close { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 50%; color: var(--text-secondary); background: var(--fill-secondary); cursor: pointer; }
.modal-close:hover { color: var(--text-primary); background: var(--fill-primary); }
.modal-body { padding: 1.25rem; overflow-y: auto; }
.modal-footer { display: flex; justify-content: flex-end; gap: .65rem; padding: .8rem 1rem; border-top: 1px solid var(--separator); background: var(--fill-tertiary); }
@keyframes modal-in { from { opacity: 0; transform: scale(.97) translateY(8px); } to { opacity: 1; transform: none; } }
@media (max-width: 640px) {
  .modal-root { align-items: flex-end; padding: 0; }
  .modal-sheet { max-width: none; max-height: 88vh; border-width: 1px 0 0; border-radius: 20px 20px 0 0; animation-name: sheet-in; }
  .modal-handle { display: block; width: 36px; height: 5px; margin: 8px auto 0; border-radius: 999px; background: var(--separator-opaque); }
  .modal-header { padding-top: .55rem; }
  .modal-footer { padding-bottom: max(.9rem, env(safe-area-inset-bottom)); }
  .modal-footer :deep(.btn) { flex: 1; }
  @keyframes sheet-in { from { transform: translateY(100%); } to { transform: none; } }
}
</style>
