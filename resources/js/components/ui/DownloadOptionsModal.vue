<template>
  <Modal :visible="visible" :title="title" size="sm" @close="$emit('close')">
    <p class="text-sm mb-4">
      {{ fileCount }} file<template v-if="folderCount"> dan {{ folderCount }} folder</template> dipilih.
      Pilih cara mengunduh:
    </p>
    <div class="download-options" role="radiogroup">
      <label class="download-option" :class="{ 'is-active': mode === 'zip' }">
        <input v-model="mode" type="radio" value="zip" class="sr-only">
        <span class="option-icon zip">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8v13H3V8"></path><path d="M1 3h22v5H1z"></path><path d="M10 12h4"></path></svg>
        </span>
        <span class="flex-1 min-w-0">
          <span class="option-title">Satu arsip ZIP <span class="badge badge-indigo ml-1">Disarankan</span></span>
          <span class="option-desc">Semua item digabung jadi satu file .zip. Struktur folder tetap terjaga. Bisa dibuka dengan WinRAR atau Windows Explorer.</span>
        </span>
      </label>
      <label class="download-option" :class="{ 'is-active': mode === 'single', 'is-disabled': singleDisabled }">
        <input v-model="mode" type="radio" value="single" class="sr-only" :disabled="singleDisabled">
        <span class="option-icon single">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
        </span>
        <span class="flex-1 min-w-0">
          <span class="option-title">Satu per satu</span>
          <span class="option-desc">
            <template v-if="folderCount">Tidak tersedia jika ada folder yang dipilih.</template>
            <template v-else-if="fileCount === 0">Tidak ada file untuk diunduh.</template>
            <template v-else>Setiap file diunduh terpisah ({{ fileCount }} unduhan). Browser mungkin meminta izin mengunduh banyak file.</template>
          </span>
        </span>
      </label>
    </div>
    <div v-if="mode === 'zip'" class="form-group mt-4 mb-0">
      <label class="form-label" for="archive-name">Nama arsip</label>
      <div class="flex items-center gap-2">
        <input id="archive-name" v-model="name" type="text" class="form-control" maxlength="120" placeholder="Nama file ZIP" @keydown.enter="confirm">
        <span class="text-sm text-secondary">.zip</span>
      </div>
    </div>
    <template #footer>
      <button class="btn btn-secondary" @click="$emit('close')">Batal</button>
      <button class="btn btn-primary" @click="confirm">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        {{ mode === 'zip' ? 'Unduh ZIP' : `Unduh ${fileCount} file` }}
      </button>
    </template>
  </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import Modal from './Modal.vue';

const props = defineProps({
  visible: { type: Boolean, default: false },
  title: { type: String, default: 'Unduh item terpilih' },
  fileCount: { type: Number, default: 0 },
  folderCount: { type: Number, default: 0 },
  defaultName: { type: String, default: 'BM-Cloud' },
});
const emit = defineEmits(['close', 'confirm']);

const mode = ref('zip'); // 'zip' | 'single'
const name = ref('');
const singleDisabled = computed(() => props.folderCount > 0 || props.fileCount === 0);

// Reset each time the dialog opens.
watch(() => props.visible, (open) => {
  if (!open) return;
  mode.value = 'zip';
  name.value = props.defaultName;
});

const confirm = () => {
  emit('confirm', { mode: mode.value, name: name.value.trim() });
};
</script>

<style scoped>
.download-options { display: flex; flex-direction: column; gap: .6rem; }
.download-option {
  display: flex; align-items: flex-start; gap: .8rem; padding: .85rem .9rem;
  border: 1.5px solid var(--separator-opaque); border-radius: 12px; cursor: pointer;
  transition: border-color .15s ease, background .15s ease;
}
.download-option:hover { border-color: #b7c6dc; }
.download-option.is-active { border-color: var(--accent-primary); background: #f5f9ff; }
.download-option.is-disabled { opacity: .55; cursor: not-allowed; }
.option-icon { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0; }
.option-icon.zip { background: #fff7e6; color: #b45309; }
.option-icon.single { background: var(--accent-tint); color: var(--accent-primary); }
.option-title { display: flex; align-items: center; font-size: .875rem; font-weight: 600; color: var(--text-primary); }
.option-desc { display: block; margin-top: .2rem; font-size: .78rem; line-height: 1.45; color: var(--text-secondary); }
</style>
