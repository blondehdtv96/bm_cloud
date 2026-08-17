<template>
  <div class="h-full flex flex-col gap-6">
    <div class="page-header">
      <div>
        <h1>Log Aktivitas Sistem</h1>
        <p>Pantau aktivitas pengguna dan kejadian sistem di seluruh aplikasi.</p>
      </div>
      <button class="btn btn-secondary">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        Ekspor Log
      </button>
    </div>

    <div class="glass-card flex-1 overflow-hidden flex flex-col">
      <!-- Filters -->
      <div class="p-4 border-b border-glass-border flex flex-col sm:flex-row flex-wrap gap-3 sm:items-center">
        <div class="relative flex-1 min-w-[200px]">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input type="text" placeholder="Cari log..." class="form-control form-control-sm pl-9 bg-black/20 w-full">
        </div>

        <div class="flex gap-3">
          <select class="form-control form-control-sm bg-black/20 flex-1 sm:flex-none sm:w-40">
            <option value="">Semua Aksi</option>
            <option value="upload">Upload</option>
            <option value="download">Download</option>
            <option value="delete">Hapus</option>
            <option value="login">Login</option>
            <option value="share">Bagikan</option>
          </select>

          <select class="form-control form-control-sm bg-black/20 flex-1 sm:flex-none sm:w-40">
            <option value="7d">7 Hari Terakhir</option>
            <option value="30d">30 Hari Terakhir</option>
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
                  <div class="w-6 h-6 rounded-full bg-indigo-500/15 text-indigo-400 flex items-center justify-center text-xs font-bold flex-shrink-0">{{ log.user.name.charAt(0) }}</div>
                  <div class="min-w-0">
                    <span class="font-medium text-primary truncate block max-w-[120px]">{{ log.user.name }}</span>
                    <span class="text-secondary text-xs sm:hidden">{{ formatDate(log.created_at) }}</span>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge w-max" :class="getActionClass(log.action)">
                  <component :is="getActionIcon(log.action)" class="w-3 h-3" />
                  {{ capitalize(log.action) }}
                </span>
              </td>
              <td class="text-secondary hidden md:table-cell">
                <span v-if="log.action === 'upload'">Mengunggah <span class="text-primary">{{ log.details.filename }}</span> ({{ log.details.size }})</span>
                <span v-else-if="log.action === 'delete'">Menghapus <span class="text-danger">{{ log.details.filename }}</span></span>
                <span v-else-if="log.action === 'share'">Membagikan <span class="text-primary">{{ log.details.filename }}</span> ke {{ log.details.target }}</span>
                <span v-else-if="log.action === 'login'">Berhasil masuk dari {{ log.details.device || 'perangkat tidak dikenal' }}</span>
                <span v-else>{{ log.description || 'Melakukan sebuah aksi' }}</span>
              </td>
              <td class="hidden lg:table-cell text-right text-secondary text-xs font-mono">{{ log.ip_address }}</td>
            </tr>
          </tbody>
        </table>
        
        <div v-if="!loading && logs.length === 0" class="p-8 text-center text-secondary">
          Tidak ada log yang sesuai dengan kriteria Anda.
        </div>
      </div>
      
      <!-- Pagination -->
      <div class="p-3 border-t border-glass-border bg-black/10 flex flex-col sm:flex-row items-center justify-between gap-2 text-sm text-secondary">
        <span>Menampilkan 1 - 10 dari 2.451 entri</span>
        <div class="flex gap-1 overflow-x-auto max-w-full">
          <button class="px-3 py-1 border border-glass-border rounded hover:bg-white/5 disabled:opacity-50 flex-shrink-0">Sebelumnya</button>
          <button class="px-3 py-1 border border-primary bg-primary/20 text-primary rounded flex-shrink-0">1</button>
          <button class="px-3 py-1 border border-glass-border rounded hover:bg-white/5 flex-shrink-0">2</button>
          <button class="px-3 py-1 border border-glass-border rounded hover:bg-white/5 flex-shrink-0">3</button>
          <button class="px-3 py-1 border border-glass-border rounded hover:bg-white/5 disabled:opacity-50 flex-shrink-0">Selanjutnya</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, h } from 'vue';
import { api } from '../../composables/useApi';

const loading = ref(true);
const logs = ref([]);

const fetchLogs = async () => {
  loading.value = true;
  try {
    const response = await api.get('/admin/logs');
    logs.value = response.data.data || response.data;
  } catch (error) {
    console.error("Failed to fetch logs", error);
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('id-ID', { 
    year: 'numeric', month: 'short', day: 'numeric', 
    hour: '2-digit', minute: '2-digit' 
  }).format(date);
};

const capitalize = (s) => s.charAt(0).toUpperCase() + s.slice(1);

const getActionClass = (action) => {
  switch(action) {
    case 'upload': return 'badge-emerald';
    case 'delete': return 'badge-red';
    case 'share': return 'badge-purple';
    case 'download': return 'badge-indigo';
    case 'login': return 'badge-slate';
    default: return 'badge-slate';
  }
};

const getActionIcon = (action) => {
  switch(action) {
    case 'upload': return () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 2 }, [h('path', { d: 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4' }), h('polyline', { points: '17 8 12 3 7 8' }), h('line', { x1: 12, y1: 3, x2: 12, y2: 15 })]);
    case 'delete': return () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 2 }, [h('polyline', { points: '3 6 5 6 21 6' }), h('path', { d: 'M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2' })]);
    case 'share': return () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 2 }, [h('circle', { cx: 18, cy: 5, r: 3 }), h('circle', { cx: 6, cy: 12, r: 3 }), h('circle', { cx: 18, cy: 19, r: 3 }), h('line', { x1: 8.59, y1: 13.51, x2: 15.42, y2: 17.49 }), h('line', { x1: 15.41, y1: 6.51, x2: 8.59, y2: 10.49 })]);
    case 'download': return () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 2 }, [h('path', { d: 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4' }), h('polyline', { points: '7 10 12 15 17 10' }), h('line', { x1: 12, y1: 15, x2: 12, y2: 3 })]);
    case 'login': return () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 2 }, [h('path', { d: 'M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4' }), h('polyline', { points: '10 17 15 12 10 7' }), h('line', { x1: 15, y1: 12, x2: 3, y2: 12 })]);
    default: return () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 2 }, [h('circle', { cx: 12, cy: 12, r: 10 })]);
  }
};

onMounted(() => {
  fetchLogs();
});
</script>
