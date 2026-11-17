<template>
  <div class="h-full flex flex-col gap-6">
    <div class="page-header">
      <div>
        <h1>Log Aktivitas Sistem</h1>
        <p>Pantau aktivitas pengguna dan kejadian sistem di seluruh aplikasi.</p>
      </div>
      <button class="btn btn-secondary" :disabled="exporting" @click="exportLogs">
        <svg v-if="!exporting" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        <svg v-else class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
        {{ exporting ? 'Mengekspor...' : 'Ekspor Log' }}
      </button>
    </div>

    <div class="glass-card flex-1 overflow-hidden flex flex-col">
      <!-- Filters -->
      <div class="p-4 border-b border-glass-border flex flex-col sm:flex-row flex-wrap gap-3 sm:items-center">
        <div class="relative flex-1 min-w-[200px]">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Cari pengguna, item, aksi, atau IP..."
            class="form-control form-control-sm pl-9 bg-black/20 w-full"
          >
        </div>

        <div class="flex gap-3">
          <select v-model="filters.action" class="form-control form-control-sm bg-black/20 log-filter-select">
            <option value="">Semua Aksi</option>
            <option v-for="opt in actionOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>

          <select v-model="filters.period" class="form-control form-control-sm bg-black/20 log-filter-select">
            <option value="24h">24 Jam Terakhir</option>
            <option value="7d">7 Hari Terakhir</option>
            <option value="30d">30 Hari Terakhir</option>
            <option value="90d">90 Hari Terakhir</option>
            <option value="all">Sepanjang Waktu</option>
          </select>
        </div>
      </div>

      <!-- Log Table -->
      <div class="overflow-auto flex-1 relative">
        <div v-if="loading" class="absolute inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-10">
           <div class="animate-pulse flex flex-col items-center gap-2">
             <svg class="w-8 h-8 text-primary animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
          </div>
        </div>

        <table class="table-modern">
          <thead>
            <tr>
              <th class="hidden sm:table-cell">Waktu</th>
              <th>Pengguna</th>
              <th>Aksi</th>
              <th class="hidden md:table-cell">Detail</th>
              <th class="hidden lg:table-cell text-right">Alamat IP</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td class="text-secondary text-xs hidden sm:table-cell">{{ formatDate(log.created_at) }}</td>
              <td>
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-indigo-500/15 text-indigo-400 flex items-center justify-center text-xs font-bold flex-shrink-0">{{ userName(log).charAt(0) }}</div>
                  <div class="min-w-0">
                    <span class="font-medium text-primary truncate block max-w-[120px]">{{ userName(log) }}</span>
                    <span class="text-secondary text-xs sm:hidden">{{ formatDate(log.created_at) }}</span>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge w-max" :class="getActionClass(log.action)">
                  <component :is="getActionIcon(log.action)" class="w-3 h-3" />
                  {{ log.action_label || log.action }}
                </span>
              </td>
              <td class="text-secondary hidden md:table-cell">
                <template v-if="log.subject_name">
                  {{ subjectTypeLabel(log.subject_type) }}
                  <span :class="groupOf(log.action) === 'delete' ? 'text-danger' : 'text-primary'">{{ log.subject_name }}</span>
                </template>
                <span v-else>-</span>
                <span v-if="log.details?.version_number"> (versi {{ log.details.version_number }})</span>
              </td>
              <td class="hidden lg:table-cell text-right text-secondary text-xs font-mono">{{ log.ip_address || '-' }}</td>
            </tr>
          </tbody>
        </table>

        <div v-if="!loading && logs.length === 0" class="p-8 text-center text-secondary">
          Tidak ada log yang sesuai dengan kriteria Anda.
        </div>
      </div>

      <!-- Pagination -->
      <div class="p-3 border-t border-glass-border bg-black/10 flex flex-col sm:flex-row items-center justify-between gap-2 text-sm text-secondary">
        <span v-if="meta.total">Menampilkan {{ meta.from }} - {{ meta.to }} dari {{ formatNumber(meta.total) }} entri</span>
        <span v-else>0 entri</span>
        <div v-if="meta.last_page > 1" class="flex gap-1 overflow-x-auto max-w-full">
          <button
            class="px-3 py-1 border border-glass-border rounded hover:bg-white/5 disabled:opacity-50 flex-shrink-0"
            :disabled="meta.current_page <= 1 || loading"
            @click="goToPage(meta.current_page - 1)"
          >Sebelumnya</button>
          <template v-for="p in pageItems" :key="p.key">
            <span v-if="p.gap" class="px-2 py-1 flex-shrink-0">…</span>
            <button
              v-else
              class="px-3 py-1 border rounded flex-shrink-0"
              :class="p.page === meta.current_page ? 'border-primary bg-primary/20 text-primary' : 'border-glass-border hover:bg-white/5'"
              :disabled="loading"
              @click="goToPage(p.page)"
            >{{ p.page }}</button>
          </template>
          <button
            class="px-3 py-1 border border-glass-border rounded hover:bg-white/5 disabled:opacity-50 flex-shrink-0"
            :disabled="meta.current_page >= meta.last_page || loading"
            @click="goToPage(meta.current_page + 1)"
          >Selanjutnya</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, h } from 'vue';
import { api } from '../../composables/useApi';
import { addToast } from '../../components/ui/Toast.vue';

const PER_PAGE = 25;

const loading = ref(true);
const exporting = ref(false);
const logs = ref([]);
const meta = reactive({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0 });
const filters = reactive({ search: '', action: '', period: '7d' });

// Values match AdminController::ACTION_GROUPS.
const actionOptions = [
  { value: 'upload', label: 'Upload' },
  { value: 'download', label: 'Download' },
  { value: 'create', label: 'Buat Folder' },
  { value: 'delete', label: 'Hapus' },
  { value: 'share', label: 'Bagikan' },
  { value: 'monitor', label: 'Pantau Drive' },
  { value: 'backup', label: 'Backup' },
];

const groupOf = (action) => ({
  uploaded: 'upload', uploaded_new_version: 'upload',
  downloaded: 'download', monitored_file_download: 'download',
  created: 'create', deleted: 'delete',
  shared: 'share', created_share_link: 'share',
  monitored_drive: 'monitor', monitored_file_preview: 'monitor',
  created_backup: 'backup', restored_backup: 'backup',
}[action] || 'other');

const queryParams = () => {
  const params = { action: filters.action || undefined, period: filters.period };
  const search = filters.search.trim();
  if (search) params.search = search;
  return params;
};

let requestSeq = 0;
const fetchLogs = async (page = 1) => {
  const seq = ++requestSeq;
  loading.value = true;
  try {
    const { data } = await api.get('/admin/logs', { params: { ...queryParams(), page, per_page: PER_PAGE } });
    if (seq !== requestSeq) return; // a newer filter request superseded this one
    logs.value = data.data || [];
    Object.assign(meta, {
      current_page: data.current_page || 1,
      last_page: data.last_page || 1,
      total: data.total || 0,
      from: data.from || 0,
      to: data.to || 0,
    });
  } catch (error) {
    if (seq === requestSeq) addToast({ type: 'error', title: 'Gagal memuat', message: 'Log aktivitas tidak bisa dimuat.' });
    console.error('Failed to fetch logs', error);
  } finally {
    if (seq === requestSeq) loading.value = false;
  }
};

const goToPage = (page) => {
  if (page < 1 || page > meta.last_page || page === meta.current_page) return;
  fetchLogs(page);
};

let searchTimer = null;
watch(() => filters.search, () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetchLogs(1), 350);
});
watch(() => [filters.action, filters.period], () => {
  clearTimeout(searchTimer);
  fetchLogs(1);
});
onBeforeUnmount(() => clearTimeout(searchTimer));

const pageItems = computed(() => {
  const { current_page: cur, last_page: last } = meta;
  const pages = new Set([1, last, cur - 1, cur, cur + 1].filter((p) => p >= 1 && p <= last));
  const sorted = [...pages].sort((a, b) => a - b);
  const items = [];
  sorted.forEach((p, i) => {
    if (i > 0 && p - sorted[i - 1] > 1) items.push({ gap: true, key: `gap-${p}` });
    items.push({ page: p, key: `p-${p}` });
  });
  return items;
});

const exportLogs = async () => {
  exporting.value = true;
  try {
    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    const response = await api.get('/admin/logs/export', { params: { ...queryParams(), tz }, responseType: 'blob' });
    const disposition = response.headers['content-disposition'] || '';
    const match = disposition.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
    const filename = match ? decodeURIComponent(match[1]) : 'log-aktivitas.csv';
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'text/csv;charset=utf-8' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Failed to export logs', error);
    addToast({ type: 'error', title: 'Ekspor gagal', message: 'Log aktivitas tidak bisa diekspor.' });
  } finally {
    exporting.value = false;
  }
};

const userName = (log) => log.user?.name || 'Pengguna dihapus';

const subjectTypeLabel = (type) => ({
  'App\\Models\\File': 'File',
  'App\\Models\\Folder': 'Folder',
  'App\\Models\\User': 'Pengguna',
  'App\\Models\\Backup': 'Backup',
}[type] || '');

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('id-ID', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit'
  }).format(date);
};

const formatNumber = (n) => new Intl.NumberFormat('id-ID').format(n);

const getActionClass = (action) => ({
  upload: 'badge-emerald',
  delete: 'badge-red',
  share: 'badge-purple',
  download: 'badge-indigo',
  create: 'badge-emerald',
}[groupOf(action)] || 'badge-slate');

const svg = (children) => () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 2 }, children);
const icons = {
  upload: svg([h('path', { d: 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4' }), h('polyline', { points: '17 8 12 3 7 8' }), h('line', { x1: 12, y1: 3, x2: 12, y2: 15 })]),
  delete: svg([h('polyline', { points: '3 6 5 6 21 6' }), h('path', { d: 'M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2' })]),
  share: svg([h('circle', { cx: 18, cy: 5, r: 3 }), h('circle', { cx: 6, cy: 12, r: 3 }), h('circle', { cx: 18, cy: 19, r: 3 }), h('line', { x1: 8.59, y1: 13.51, x2: 15.42, y2: 17.49 }), h('line', { x1: 15.41, y1: 6.51, x2: 8.59, y2: 10.49 })]),
  download: svg([h('path', { d: 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4' }), h('polyline', { points: '7 10 12 15 17 10' }), h('line', { x1: 12, y1: 15, x2: 12, y2: 3 })]),
  create: svg([h('path', { d: 'M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z' }), h('line', { x1: 12, y1: 11, x2: 12, y2: 17 }), h('line', { x1: 9, y1: 14, x2: 15, y2: 14 })]),
  monitor: svg([h('path', { d: 'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z' }), h('circle', { cx: 12, cy: 12, r: 3 })]),
  other: svg([h('circle', { cx: 12, cy: 12, r: 10 })]),
};
const getActionIcon = (action) => icons[groupOf(action)] || icons.other;

onMounted(() => {
  fetchLogs(1);
});
</script>

<style scoped>
/* .form-control (app.css) is unlayered, so it beats Tailwind v4's layered
   utilities like sm:w-40; size the filter selects here instead. */
.log-filter-select { width: auto; flex: 1 1 0%; min-width: 0; }
@media (min-width: 640px) {
  .log-filter-select { flex: none; width: 11rem; }
}
</style>
