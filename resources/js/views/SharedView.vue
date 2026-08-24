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

    <!-- Breadcrumb saat menelusuri isi folder yang dibagikan -->
    <div v-if="folderId" class="flex items-center gap-2 text-sm text-secondary font-medium px-2 animate-fade-in overflow-x-auto">
      <button class="hover:text-primary transition-colors flex items-center gap-1 flex-shrink-0" @click="navigateToFolder(null)">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
        Dibagikan dengan Saya
      </button>
      <template v-for="(crumb, idx) in breadcrumbs" :key="crumb.id">
        <span class="flex-shrink-0">/</span>
        <button
          class="hover:text-primary transition-colors truncate max-w-[160px] flex-shrink-0"
          :class="{ 'text-primary': idx === breadcrumbs.length - 1 }"
          @click="navigateToFolder(crumb.id)"
        >
          {{ crumb.name }}
        </button>
      </template>
    </div>

    <div class="glass-card panel">
      <div v-if="loading" class="panel-state">
        <svg class="w-8 h-8 animate-spin" style="color: var(--accent-primary)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
        <span class="text-sm text-secondary">Memuat item...</span>
      </div>

      <div v-else-if="loadError" class="panel-state">
        <EmptyState
          title="Tidak bisa membuka folder ini"
          :description="loadError"
        />
      </div>

      <div v-else-if="rows.length === 0" class="panel-state">
        <EmptyState
          :title="folderId ? 'Folder ini kosong' : 'Belum ada item yang dibagikan'"
          :description="folderId ? 'Tidak ada file atau subfolder di dalam folder ini.' : 'File atau folder yang dibagikan pengguna lain kepada Anda akan muncul di sini.'"
        />
      </div>

      <div v-else class="panel-scroll animate-fade-in">
        <table class="table-modern">
          <thead>
            <tr>
              <th>Nama</th>
              <th class="hidden md:table-cell">{{ folderId ? 'Ukuran' : 'Dibagikan Oleh' }}</th>
              <th class="hidden sm:table-cell">Tanggal</th>
              <th class="col-action">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in rows"
              :key="row.key"
              :class="{ 'row-clickable': row.isFolder || row.kind === 'file' }"
              @click="openRow(row)"
            >
              <td>
                <div class="item-cell">
                  <span class="item-icon" :class="row.isFolder ? 'is-folder' : 'is-file'">
                    <svg v-if="row.isFolder" viewBox="0 0 24 24" fill="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                  </span>
                  <div class="item-text">
                    <span class="item-name" :title="row.name">{{ row.name }}</span>
                    <span class="item-sub md:hidden">{{ folderId ? row.sizeLabel : row.sharedByName }}</span>
                  </div>
                </div>
              </td>
              <td class="hidden md:table-cell">
                <template v-if="folderId">
                  <span class="text-secondary text-sm">{{ row.sizeLabel }}</span>
                </template>
                <div v-else class="owner-cell">
                  <span class="avatar">{{ initials(row.sharedByName) }}</span>
                  <span class="text-secondary text-sm">{{ row.sharedByName }}</span>
                </div>
              </td>
              <td class="text-secondary hidden sm:table-cell">{{ formatDate(row.date) }}</td>
              <td class="col-action">
                <div class="row-actions">
                  <button
                    v-if="!row.isFolder"
                    class="btn-icon action-download"
                    title="Unduh"
                    :disabled="downloadingId === row.fileId"
                    @click.stop="downloadFile(row)"
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

    <!-- File Preview Modal (dipakai saat menelusuri isi folder yang dibagikan) -->
    <FilePreviewModal
      :visible="previewVisible"
      :file="previewFile"
      @close="previewVisible = false"
      @download="downloadFile"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api } from '../composables/useApi';
import EmptyState from '../components/ui/EmptyState.vue';
import FilePreviewModal from '../components/ui/FilePreviewModal.vue';

const route = useRoute();
const router = useRouter();

const folderId = ref(route.params.folderId ? Number(route.params.folderId) : null);

const loading = ref(true);
const loadError = ref('');
const shares = ref([]);       // mode akar: daftar item yang langsung dibagikan
const folderContents = ref({ folders: [], files: [] }); // mode folder: isi folder yang sedang dibuka
const breadcrumbs = ref([]);
const downloadingId = ref(null);

const previewVisible = ref(false);
const previewFile = ref(null);

const subtitle = computed(() => {
  if (loading.value) return 'Memuat item...';
  if (folderId.value) return `${rows.value.length} item dalam folder ini.`;
  if (shares.value.length === 0) return 'Tidak ada item yang dibagikan kepada Anda.';
  return `${shares.value.length} item dibagikan kepada Anda.`;
});

// Baris tabel dinormalkan dari dua sumber data (share akar vs isi folder)
// supaya template tidak perlu tahu sedang berada di mode mana.
const rows = computed(() => {
  if (folderId.value) {
    const folderRows = folderContents.value.folders.map((f) => ({
      key: 'f' + f.id,
      kind: 'folder',
      isFolder: true,
      id: f.id,
      name: f.name,
      date: f.updated_at,
      sizeLabel: '--',
    }));
    const fileRows = folderContents.value.files.map((f) => ({
      key: 'file' + f.id,
      kind: 'file',
      isFolder: false,
      fileId: f.id,
      name: f.original_name,
      date: f.updated_at,
      sizeLabel: f.formatted_size,
      raw: f,
    }));
    return [...folderRows, ...fileRows];
  }

  return shares.value.map((share) => ({
    key: 'share' + share.id,
    kind: isFolderShare(share) ? 'folder' : 'file',
    isFolder: isFolderShare(share),
    id: share.shareable_id,
    fileId: share.shareable_id,
    name: share.shareable?.original_name || share.shareable?.name || 'Item tidak tersedia',
    date: share.created_at,
    sharedByName: share.shared_by?.name || 'Pengguna',
    raw: share.shareable,
  }));
});

const isFolderShare = (share) => (share.shareable_type || '').endsWith('Folder');

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

const fetchRoot = async () => {
  const response = await api.get('/shares');
  shares.value = response.data.data || response.data || [];
};

const fetchFolder = async (id) => {
  const [contentsRes, trailRes] = await Promise.all([
    api.get(`/shares/folders/${id}`),
    api.get(`/shares/folders/${id}/trail`),
  ]);
  folderContents.value = {
    folders: contentsRes.data.folders || [],
    files: contentsRes.data.files || [],
  };
  breadcrumbs.value = trailRes.data.trail || [];
};

const load = async () => {
  loading.value = true;
  loadError.value = '';
  try {
    if (folderId.value) {
      await fetchFolder(folderId.value);
    } else {
      await fetchRoot();
    }
  } catch (error) {
    console.error('Failed to load shared items', error);
    if (folderId.value) {
      loadError.value = error.response?.data?.message || 'Anda tidak memiliki akses ke folder ini.';
      folderContents.value = { folders: [], files: [] };
    } else {
      shares.value = [];
    }
  } finally {
    loading.value = false;
  }
};

const navigateToFolder = (id) => {
  if (id) {
    router.push(`/shared/${id}`);
  } else {
    router.push('/shared');
  }
};

const openRow = (row) => {
  if (row.isFolder) {
    navigateToFolder(row.id);
  } else if (row.kind === 'file') {
    previewFile.value = row.raw || { id: row.fileId, original_name: row.name };
    previewVisible.value = true;
  }
};

const downloadFile = async (row) => {
  const fileId = row.fileId ?? row.id;
  const name = row.name ?? row.original_name;
  downloadingId.value = fileId;
  try {
    const response = await api.get(`/files/${fileId}/download`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.download = name;
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

watch(() => route.params.folderId, (val) => {
  folderId.value = val ? Number(val) : null;
  load();
});

onMounted(load);
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

.row-clickable { cursor: pointer; }
.row-clickable:hover { background: var(--fill-secondary); }

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
