<template>
  <div class="shared-page">
    <div class="page-header">
      <div>
        <h1>
          <svg class="w-5 h-5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          Dibagikan dengan Saya
        </h1>
        <p>{{ subtitle }}</p>
      </div>
    </div>

    <div class="glass-card panel">
      <div v-if="loading" class="panel-state">
        <svg class="w-8 h-8 animate-spin" style="color: var(--accent-primary)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
        <span class="text-sm text-secondary">Memuat item...</span>
      </div>

      <div v-else-if="shares.length === 0" class="panel-state">
        <EmptyState
          title="Belum ada item yang dibagikan"
          description="File atau folder yang dibagikan pengguna lain kepada Anda akan muncul di sini."
        />
      </div>

      <div v-else class="panel-scroll animate-fade-in">
        <table class="table-modern">
          <thead>
            <tr>
              <th>Nama</th>
              <th class="hidden md:table-cell">Dibagikan Oleh</th>
              <th class="hidden sm:table-cell">Tanggal</th>
              <th class="col-action">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="share in shares" :key="share.id">
              <td>
                <div class="item-cell">
                  <span class="item-icon" :class="isFolder(share) ? 'is-folder' : 'is-file'">
                    <svg v-if="isFolder(share)" viewBox="0 0 24 24" fill="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                  </span>
                  <div class="item-text">
                    <span class="item-name" :title="itemName(share)">{{ itemName(share) }}</span>
                    <span class="item-sub md:hidden">{{ sharedByName(share) }}</span>
                  </div>
                </div>
              </td>
              <td class="hidden md:table-cell">
                <div class="owner-cell">
                  <span class="avatar">{{ initials(sharedByName(share)) }}</span>
                  <span class="text-secondary text-sm">{{ sharedByName(share) }}</span>
                </div>
              </td>
              <td class="text-secondary hidden sm:table-cell">{{ formatDate(share.created_at) }}</td>
              <td class="col-action">
                <div class="row-actions">
                  <button
                    v-if="!isFolder(share)"
                    class="btn-icon action-download"
                    title="Unduh"
                    :disabled="downloadingId === share.id"
                    @click="downloadFile(share)"
                  >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                  </button>
                  <span v-else class="badge badge-slate">Folder</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { api } from '../composables/useApi';
import EmptyState from '../components/ui/EmptyState.vue';

const loading = ref(true);
const shares = ref([]);
const downloadingId = ref(null);

const subtitle = computed(() => {
  if (loading.value) return 'Memuat item...';
  if (shares.value.length === 0) return 'Tidak ada item yang dibagikan kepada Anda.';
  return `${shares.value.length} item dibagikan kepada Anda.`;
});

const isFolder = (share) => (share.shareable_type || '').endsWith('Folder');

const itemName = (share) =>
  share.shareable?.original_name || share.shareable?.name || 'Item tidak tersedia';

const sharedByName = (share) => share.shared_by?.name || 'Pengguna';

const initials = (name) =>
  (name || '?')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word[0].toUpperCase())
    .join('');

const formatDate = (value) => {
  if (!value) return '--';
  return new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const fetchShares = async () => {
  loading.value = true;
  try {
    const response = await api.get('/shares');
    shares.value = response.data.data || response.data || [];
  } catch (error) {
    console.error('Failed to fetch shares', error);
    shares.value = [];
  } finally {
    loading.value = false;
  }
};

const downloadFile = async (share) => {
  downloadingId.value = share.id;
  try {
    const response = await api.get(`/files/${share.shareable_id}/download`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.download = itemName(share);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Failed to download file', error);
  } finally {
    downloadingId.value = null;
  }
};

onMounted(fetchShares);
</script>

<style scoped>
.shared-page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  min-height: 100%;
}

/* page-header punya margin-bottom global; di sini spacing diatur oleh gap. */
.shared-page .page-header {
  margin-bottom: 0;
}

.panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 320px;
  overflow: hidden;
}

.panel-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: .6rem;
  padding: 2rem 1rem;
}

.panel-scroll {
  flex: 1;
  overflow-y: auto;
}

.item-cell {
  display: flex;
  align-items: center;
  gap: .75rem;
  min-width: 0;
}

.item-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  flex-shrink: 0;
  border-radius: 9px;
  background: rgba(26, 115, 232, .10);
  color: var(--accent-primary);
}
.item-icon svg { width: 17px; height: 17px; }
.item-icon.is-folder { background: rgba(249, 171, 0, .13); color: var(--accent-warning); }

.item-text {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.item-name {
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.item-sub {
  font-size: .75rem;
  color: var(--text-muted);
}

.owner-cell {
  display: flex;
  align-items: center;
  gap: .5rem;
  min-width: 0;
}

.avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  flex-shrink: 0;
  border-radius: 50%;
  background: var(--accent-primary);
  color: #fff;
  font-size: .65rem;
  font-weight: 700;
  letter-spacing: .02em;
}

.col-action {
  width: 1%;
  white-space: nowrap;
  text-align: right;
}

.row-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: .15rem;
}

.action-download:hover:not(:disabled) { color: var(--accent-primary); background: rgba(26, 115, 232, .10); }

@media (min-width: 768px) {
  .item-sub { display: none; }
}
</style>
