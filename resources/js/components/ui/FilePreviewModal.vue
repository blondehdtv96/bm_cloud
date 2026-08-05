<template>
  <div class="preview-overlay" v-if="visible" @click.self="close">
    <div class="preview-shell animate-slide-up">
      <!-- Top bar -->
      <div class="preview-topbar">
        <div class="flex items-center gap-3 min-w-0">
          <span class="file-icon-badge" :class="typeInfo.badgeClass">
            <component :is="typeInfo.icon" class="w-4 h-4" />
          </span>
          <div class="min-w-0">
            <div class="text-sm font-medium text-primary truncate">{{ file?.original_name }}</div>
            <div class="text-xs text-secondary">{{ file?.formatted_size }}</div>
          </div>
        </div>

        <div class="flex items-center gap-1 flex-shrink-0">
          <button class="icon-btn" title="Unduh" @click="$emit('download', file)">
            <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          </button>
          <button class="icon-btn" title="Tutup" @click="close">
            <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="preview-body">
        <div v-if="loading" class="preview-state">
          <svg class="w-8 h-8 text-primary animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
          <span class="text-sm text-secondary mt-3">Memuat pratinjau...</span>
        </div>

        <div v-else-if="error" class="preview-state">
          <span class="file-icon-badge lg" :class="typeInfo.badgeClass">
            <component :is="typeInfo.icon" class="w-8 h-8" />
          </span>
          <p class="text-sm text-secondary mt-4 max-w-xs text-center">{{ error }}</p>
          <button class="btn btn-primary mt-4" @click="$emit('download', file)">Unduh File</button>
        </div>

        <template v-else>
          <img v-if="typeInfo.category === 'image'" :src="blobUrl" :alt="file.original_name" class="preview-media preview-image">

          <iframe v-else-if="typeInfo.category === 'pdf'" :src="blobUrl" class="preview-media preview-frame"></iframe>

          <video v-else-if="typeInfo.category === 'video'" :src="blobUrl" controls autoplay class="preview-media preview-video"></video>

          <div v-else-if="typeInfo.category === 'audio'" class="preview-state">
            <span class="file-icon-badge lg" :class="typeInfo.badgeClass">
              <component :is="typeInfo.icon" class="w-8 h-8" />
            </span>
            <audio :src="blobUrl" controls autoplay class="mt-6 w-full max-w-md"></audio>
          </div>

          <pre v-else-if="typeInfo.category === 'text'" class="preview-media preview-text">{{ textContent }}</pre>

          <div v-else class="preview-state">
            <span class="file-icon-badge lg" :class="typeInfo.badgeClass">
              <component :is="typeInfo.icon" class="w-8 h-8" />
            </span>
            <p class="text-sm text-secondary mt-4 max-w-xs text-center">
              Pratinjau tidak tersedia untuk tipe file ini. Unduh untuk membuka di aplikasi terkait.
            </p>
            <button class="btn btn-primary mt-4" @click="$emit('download', file)">Unduh File</button>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, h, onUnmounted, onMounted } from 'vue';
import { api } from '../../composables/useApi';

const props = defineProps({
  visible: Boolean,
  file: { type: Object, default: null },
});
const emit = defineEmits(['close', 'download']);

const loading = ref(false);
const error = ref('');
const blobUrl = ref('');
const textContent = ref('');

// ---- Icon helpers (kept lightweight, no external deps) ----
const IconFile = () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.5 }, [
  h('path', { d: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z' }), h('polyline', { points: '14 2 14 8 20 8' })
]);
const IconImage = () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.5 }, [
  h('rect', { x: 3, y: 3, width: 18, height: 18, rx: 2 }), h('circle', { cx: 8.5, cy: 8.5, r: 1.5 }), h('polyline', { points: '21 15 16 10 5 21' })
]);
const IconPdf = () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.5 }, [
  h('path', { d: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z' }), h('polyline', { points: '14 2 14 8 20 8' }), h('line', { x1: 9, y1: 15, x2: 15, y2: 15 }), h('line', { x1: 9, y1: 11, x2: 12, y2: 11 })
]);
const IconVideo = () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.5 }, [
  h('polygon', { points: '23 7 16 12 23 17 23 7' }), h('rect', { x: 1, y: 5, width: 15, height: 14, rx: 2 })
]);
const IconAudio = () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.5 }, [
  h('path', { d: 'M9 18V5l12-2v13' }), h('circle', { cx: 6, cy: 18, r: 3 }), h('circle', { cx: 18, cy: 16, r: 3 })
]);
const IconArchive = () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.5 }, [
  h('rect', { x: 3, y: 3, width: 18, height: 18, rx: 2 }), h('line', { x1: 9, y1: 3, x2: 9, y2: 21 })
]);
const IconSheet = () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.5 }, [
  h('rect', { x: 3, y: 3, width: 18, height: 18, rx: 2 }), h('line', { x1: 3, y1: 9, x2: 21, y2: 9 }), h('line', { x1: 3, y1: 15, x2: 21, y2: 15 }), h('line', { x1: 9, y1: 3, x2: 9, y2: 21 })
]);
const IconDoc = () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.5 }, [
  h('path', { d: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z' }), h('polyline', { points: '14 2 14 8 20 8' }), h('line', { x1: 8, y1: 13, x2: 16, y2: 13 }), h('line', { x1: 8, y1: 17, x2: 16, y2: 17 })
]);
const IconText = () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 1.5 }, [
  h('path', { d: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z' }), h('polyline', { points: '14 2 14 8 20 8' }), h('line', { x1: 16, y1: 13, x2: 8, y2: 13 }), h('line', { x1: 16, y1: 17, x2: 8, y2: 17 })
]);

const TEXT_EXTENSIONS = ['txt', 'md', 'json', 'csv', 'log', 'xml', 'yml', 'yaml', 'ini', 'js', 'ts', 'php', 'css', 'html'];

const typeInfo = computed(() => {
  const mime = props.file?.mime_type || '';
  const ext = (props.file?.original_name || '').split('.').pop()?.toLowerCase() || '';

  if (mime.startsWith('image/')) return { category: 'image', icon: IconImage, badgeClass: 'badge-pink' };
  if (mime === 'application/pdf') return { category: 'pdf', icon: IconPdf, badgeClass: 'badge-red' };
  if (mime.startsWith('video/')) return { category: 'video', icon: IconVideo, badgeClass: 'badge-purple' };
  if (mime.startsWith('audio/')) return { category: 'audio', icon: IconAudio, badgeClass: 'badge-orange' };
  if (mime.startsWith('text/') || TEXT_EXTENSIONS.includes(ext)) return { category: 'text', icon: IconText, badgeClass: 'badge-slate' };
  if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) return { category: 'other', icon: IconArchive, badgeClass: 'badge-amber' };
  if (['xls', 'xlsx', 'csv'].includes(ext)) return { category: 'other', icon: IconSheet, badgeClass: 'badge-emerald' };
  if (['doc', 'docx'].includes(ext)) return { category: 'other', icon: IconDoc, badgeClass: 'badge-indigo' };
  return { category: 'other', icon: IconFile, badgeClass: 'badge-slate' };
});

const close = () => emit('close');

const onKeydown = (e) => {
  if (e.key === 'Escape' && props.visible) close();
};
onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown);
  revokeBlobUrl();
});

const revokeBlobUrl = () => {
  if (blobUrl.value) {
    window.URL.revokeObjectURL(blobUrl.value);
    blobUrl.value = '';
  }
};

const loadPreview = async () => {
  revokeBlobUrl();
  textContent.value = '';
  error.value = '';

  if (!props.file || typeInfo.value.category === 'other') return;

  loading.value = true;
  try {
    const response = await api.get(`/files/${props.file.id}/preview`, { responseType: 'blob' });
    const blob = response.data;

    if (typeInfo.value.category === 'text') {
      textContent.value = await blob.text();
    } else {
      blobUrl.value = window.URL.createObjectURL(blob);
    }
  } catch (e) {
    error.value = 'Gagal memuat pratinjau file.';
  } finally {
    loading.value = false;
  }
};

watch(() => [props.visible, props.file?.id], ([isVisible]) => {
  if (isVisible && props.file) {
    loadPreview();
  } else {
    revokeBlobUrl();
  }
});

watch(() => props.visible, (val) => {
  document.body.style.overflow = val ? 'hidden' : '';
});
</script>

<style scoped>
.preview-overlay { position: fixed; inset: 0; z-index: 60; display: flex; align-items: center; justify-content: center; padding: 1.5rem; background: rgba(28,28,30,.48); -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px); }
.preview-shell { width: 100%; max-width: 960px; height: 85vh; display: flex; flex-direction: column; background: rgba(255,255,255,.97); border: 1px solid var(--separator); border-radius: 20px; overflow: hidden; box-shadow: var(--shadow-popover); }
.preview-topbar { min-height: 58px; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .7rem .85rem .7rem 1rem; border-bottom: 1px solid var(--separator); background: rgba(249,249,251,.9); flex-shrink: 0; }
.icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 999px; color: var(--accent-primary); background: transparent; border: none; cursor: pointer; transition: background .15s ease, transform .1s ease; }
.icon-btn:hover { background: var(--fill-secondary); }
.icon-btn:active { transform: scale(.94); }
.file-icon-badge { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: 8px; flex-shrink: 0; }
.file-icon-badge.lg { width: 4.5rem; height: 4.5rem; border-radius: 18px; }
.badge-pink { background: rgba(255,45,85,.11); color: #d30f45; }
.badge-red { background: rgba(255,59,48,.10); color: #d70015; }
.badge-purple { background: rgba(175,82,222,.11); color: #8944ab; }
.badge-orange, .badge-amber { background: rgba(255,149,0,.12); color: #b25000; }
.badge-slate { background: var(--fill-secondary); color: var(--text-secondary); }
.badge-emerald { background: rgba(52,199,89,.12); color: #248a3d; }
.badge-indigo { background: rgba(0,122,255,.10); color: #0066d6; }
.preview-body { flex: 1; min-height: 0; display: flex; align-items: center; justify-content: center; background: #e9e9ee; overflow: auto; }
.preview-state { display: flex; flex-direction: column; align-items: center; padding: 2rem; }
.preview-media { width: 100%; height: 100%; }
.preview-image { object-fit: contain; padding: 1rem; }
.preview-frame { border: none; background: #fff; }
.preview-video { background: #000; object-fit: contain; }
.preview-text { width: calc(100% - 2rem); height: calc(100% - 2rem); margin: 1rem; padding: 1.5rem; overflow: auto; white-space: pre-wrap; word-break: break-word; font-family: "SFMono-Regular", Consolas, Menlo, monospace; font-size: .85rem; line-height: 1.6; color: var(--text-primary); background: #fff; border: 1px solid var(--separator); border-radius: 12px; text-align: left; }
@media (max-width: 640px) { .preview-overlay { align-items: flex-end; padding: 0; } .preview-shell { height: 92vh; border-radius: 20px 20px 0 0; border-width: 1px 0 0; } }
</style>
