<template>
  <div class="h-full flex flex-col gap-6">
    <div class="page-header">
      <div>
        <h1>Backup Sistem</h1>
        <p>Kelola backup data sistem dan proses pemulihan.</p>
      </div>
      <button @click="createBackup" class="btn btn-primary" :disabled="isCreating">
        <svg v-if="isCreating" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
        <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
        {{ isCreating ? 'Membuat...' : 'Buat Backup' }}
      </button>
    </div>

    <!-- Storage Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="glass-card p-4 flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-indigo-500/15 text-indigo-400 flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
        </div>
        <div>
          <div class="text-secondary text-sm font-medium">Total Backup</div>
          <div class="text-2xl font-bold text-primary">{{ backups.length }}</div>
        </div>
      </div>
      
      <div class="glass-card p-4 flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-emerald-500/15 text-emerald-400 flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
        </div>
        <div>
          <div class="text-secondary text-sm font-medium">Total Ukuran</div>
          <div class="text-2xl font-bold text-primary">{{ totalSizeFormatted }}</div>
        </div>
      </div>

      <div class="glass-card p-4 flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-blue-500/15 text-blue-400 flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <div>
          <div class="text-secondary text-sm font-medium">Backup Otomatis Berikutnya</div>
          <div class="text-lg font-bold text-primary">Setiap hari, 01:00</div>
        </div>
      </div>
    </div>

    <!-- Backup List -->
    <div class="glass-card flex-1 overflow-hidden flex flex-col">
      <div class="p-4 border-b border-glass-border">
        <h2 class="font-bold text-primary text-sm">Riwayat Backup</h2>
      </div>
      
      <div class="overflow-auto flex-1 relative">
        <div v-if="loading" class="absolute inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-10">
           <div class="animate-pulse flex flex-col items-center gap-2">
             <svg class="w-8 h-8 text-primary animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
          </div>
        </div>

        <table class="table-modern">
          <thead>
            <tr>
              <th>Nama File</th>
              <th class="hidden md:table-cell">Tipe</th>
              <th class="hidden md:table-cell">Ukuran</th>
              <th>Dibuat</th>
              <th>Status</th>
              <th class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="backup in backups" :key="backup.id">
              <td>
                <div class="flex items-center gap-3">
                  <svg class="w-5 h-5 text-indigo-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                  <span class="font-medium text-primary truncate">{{ backup.name }}</span>
                </div>
              </td>
              <td class="text-secondary hidden md:table-cell capitalize">{{ backup.type || 'Manual' }}</td>
              <td class="text-secondary hidden md:table-cell">{{ formatSize(backup.size) }}</td>
              <td class="text-secondary text-xs">{{ formatDate(backup.created_at) }}</td>
              <td>
                <span v-if="backup.status === 'completed'" class="badge badge-emerald">
                  <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Selesai
                </span>
                <span v-else-if="backup.status === 'running' || backup.status === 'pending'" class="badge badge-indigo">
                  <svg class="w-3 h-3 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg> Berjalan
                </span>
                <span v-else class="badge badge-red" :title="backup.error_message">
                  <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> Gagal
                </span>
              </td>
              <td class="text-right">
                <div class="flex justify-end gap-1">
                  <button class="btn-icon text-indigo-400 hover:bg-indigo-400/20" title="Unduh" :disabled="backup.status !== 'completed'" @click="downloadBackup(backup)">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                  </button>
                  <button class="btn-icon text-emerald-400 hover:bg-emerald-400/20" title="Pulihkan" :disabled="backup.status !== 'completed' || restoringId === backup.id" @click="restoreBackup(backup)">
                    <svg v-if="restoringId === backup.id" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
                    <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 14 12 11 15 14"></polyline><path d="M12 11v8"></path><path d="M20 21v-2a4 4 0 0 0-4-4h-8a4 4 0 0 0-4 4v2"></path></svg>
                  </button>
                  <button class="btn-icon text-red-400 hover:bg-red-400/20" title="Hapus" @click="deleteBackup(backup)">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div v-if="!loading && backups.length === 0" class="p-8 text-center text-secondary">
          Belum ada backup yang tersedia.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from '../../composables/useApi';

const loading = ref(true);
const isCreating = ref(false);
const restoringId = ref(null);
const backups = ref([]);

const totalSizeFormatted = computed(() => {
  const total = backups.value.reduce((sum, b) => sum + (b.size || 0), 0);
  return formatSize(total);
});

const fetchBackups = async () => {
  loading.value = true;
  try {
    const response = await api.get('/admin/backups');
    backups.value = response.data.data || response.data || [];
  } catch (error) {
    console.error("Failed to fetch backups", error);
  } finally {
    loading.value = false;
  }
};

const createBackup = async () => {
  isCreating.value = true;
  try {
    const response = await api.post('/admin/backups');
    backups.value.unshift(response.data);
  } catch (error) {
    console.error('Failed to create backup', error);
    alert(error.response?.data?.message || 'Gagal membuat backup');
  } finally {
    isCreating.value = false;
  }
};

const restoreBackup = async (backup) => {
  if (!confirm(`Pulihkan sistem dari backup "${backup.name}"? Data saat ini akan ditimpa.`)) return;

  restoringId.value = backup.id;
  try {
    await api.post(`/admin/backups/${backup.id}/restore`);
    alert('Backup berhasil dipulihkan.');
  } catch (error) {
    console.error('Failed to restore backup', error);
    alert(error.response?.data?.message || 'Gagal memulihkan backup');
  } finally {
    restoringId.value = null;
  }
};

const downloadBackup = (backup) => {
  window.open(`/api/admin/backups/${backup.id}/download`, '_blank');
};

const deleteBackup = async (backup) => {
  if (!confirm(`Hapus backup "${backup.name}"?`)) return;

  try {
    await api.delete(`/admin/backups/${backup.id}`);
    backups.value = backups.value.filter(b => b.id !== backup.id);
  } catch (error) {
    console.error('Failed to delete backup', error);
  }
};

const formatSize = (bytes) => {
  if (!bytes) return '--';
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return parseFloat((bytes / Math.pow(1024, i)).toFixed(1)) + ' ' + units[i];
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('id-ID', { 
    year: 'numeric', month: 'short', day: 'numeric', 
    hour: '2-digit', minute: '2-digit' 
  }).format(date);
};

onMounted(() => {
  fetchBackups();
});
</script>
