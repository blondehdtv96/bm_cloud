<template>
  <div class="relative w-full group">
    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-muted group-focus-within:text-primary transition-colors pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
    <input 
      v-model="query"
      type="text" 
      class="search-input"
      placeholder="Cari di Drive..."
      @input="onInput"
    >
    <button v-if="query" @click="clear" class="absolute right-0 top-0 h-full px-3 flex items-center text-secondary hover:text-primary">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const emit = defineEmits(['search']);
const query = ref('');
let timeout = null;

const onInput = () => {
  clearTimeout(timeout);
  timeout = setTimeout(() => {
    emit('search', query.value);
  }, 300);
};

const clear = () => {
  query.value = '';
  emit('search', '');
};
</script>

<style scoped>
.search-input {
  width: 100%;
  height: 2.35rem;
  padding: 0 2.25rem;
  border-radius: 10px;
  background: var(--fill-secondary);
  border: 1px solid transparent;
  color: var(--text-primary);
  font-size: 0.85rem;
  font-family: inherit;
  transition: background 0.15s ease, border-color 0.15s ease, box-shadow .15s ease;
}
.search-input::placeholder { color: var(--text-muted); }
.search-input:hover { background: var(--fill-primary); }
.search-input:focus {
  outline: none;
  background: var(--bg-secondary);
  border-color: var(--accent-primary);
  box-shadow: 0 0 0 3px rgba(0,122,255,0.12);
}
</style>
