<template>
  <div class="monitor-page">
    <div class="page-header">
      <div>
        <h1>
          <svg class="w-5 h-5 text-accent-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
          Pantau Drive Pengguna
        </h1>
        <p>Tinjau file dan folder milik staf secara read-only, tanpa mengubah data mereka.</p>
      </div>
    </div>

    <div class="glass-card monitor-body">
      <!-- User list panel -->
      <div class="user-panel" :class="{ 'is-collapsed': selectedUser }">
        <div class="panel-toolbar">
          <div class="search-field">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input v-model="userSearch" type="text" placeholder="Cari pengguna..." class="form-control form-control-sm pl-9 w-full">
          </div>
          <span class="panel-count">{{ filteredUsers.length }} pengguna</span>
        </div>

        <div class="panel-scroll">
          <div v-if="loadingUsers" class="p-6 flex justify-center">
            <svg class="w-6 h-6 text-primary animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
          </div>

          <button
            v-for="u in filteredUsers"
            :key="u.id"
            class="user-row"
            :class="{ active: selectedUser?.id === u.id }"
            @click="selectUser(u)"
          >
            <div class="w-9 h-9 rounded-full bg-indigo-500/15 text-indigo-400 flex items-center justify-center font-bold text-sm flex-shrink-0">
              {{ initials(u.name) }}
            </div>
            <div class="min-w-0 flex-1 text-left">
              <div class="font-medium text-primary truncate text-sm">{{ u.name }}</div>
              <div class="text-xs text-secondary truncate">{{ u.roles?.[0]?.name || 'Tanpa Peran' }}</div>
            </div>
            <div class="user-meta">
              <span class="text-xs text-secondary">{{ u.files_count }} file</span>
              <span class="text-[10px] text-muted">{{ formatSize(u.storage_used) }}</span>
            </div>
          </button>

          <div v-if="!loadingUsers && filteredUsers.length === 0" class="p-6 text-center text-sm text-secondary">
            Tidak ada pengguna ditemukan.
          </div>
        </div>
      </div>

      <!-- Drive content panel -->
      <div class="content-panel" :class="{ 'is-active': selectedUser }">
        <template v-if="selectedUser">
          <div class="content-toolbar">
            <button class="btn-icon back-btn" @click="selectedUser = null">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            </button>
            <div class="min-w-0 flex-1">
              <div class="text-sm font-semibold text-primary truncate">{{ selectedUser.name }}</div>
              <div class="text-xs text-secondary truncate">{{ selectedUser.email }}</div>
            </div>
            <span class="badge badge-indigo flex-shrink-0">
              <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              Hanya Baca
            </span>
          </div>

          <!-- Breadcrumbs -->
          <div class="breadcrumbs">
            <button class="crumb" @click="navigateToFolder(null)">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
              Root
            </button>
            <template v-for="(crumb, idx) in breadcrumbs" :key="crumb.id">
              <span class="crumb-sep">/</span>
              <button
                class="crumb"
                :class="{ 'is-current': idx === breadcrumbs.length - 1 }"
                @click="navigateToFolder(crumb.id)"
              >
                {{ crumb.name }}
              </button>
            </template>
          </div>

          <div class="content-scroll">
            <div v-if="loadingContents" class="panel-state">
              <svg class="w-8 h-8 animate-spin" style="color: var(--accent-primary)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
            </div>

            <div v-else-if="folders.length === 0 && files.length === 0" class="panel-state">
              <EmptyState
                title="Folder ini kosong"
                description="Pengguna ini belum menyimpan apa pun di folder ini."
              />
            </div>

            <template v-else>
              <template v-if="folders.length">
                <h3 class="section-label">Folder</h3>
                <TransitionGroup name="grid-in" tag="div" class="content-grid mb-6">
                  <div
                    v-for="(folder, i) in folders"
                    :key="'f' + folder.id"
                    class="folder-card"
                    :style="`--d: ${i * 45}ms`"
                    @click="navigateToFolder(folder.id)"
                  >
                    <svg class="w-8 h-8 folder-glyph flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                    <span class="text-sm font-medium truncate">{{ folder.name }}</span>
                  </div>
                </TransitionGroup>
              </template>

              <template v-if="files.length">
                <h3 class="section-label">File</h3>
                <TransitionGroup name="grid-in" tag="div" class="content-grid">
                  <div
                    v-for="(file, i) in files"
                    :key="'doc' + file.id"
                    class="file-card group"
                    :style="`--d: ${i * 45}ms`"
                    @click="openPreview(file)"
                  >
                    <div class="file-thumb">
                      <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    </div>
                    <span class="text-sm font-medium truncate mb-1" :title="file.original_name">{{ file.original_name }}</span>
                    <span class="text-xs text-secondary">{{ file.formatted_size }}</span>
                    <span class="text-xs text-secondary truncate" :title="formatDateTime(file.created_at)">Diunggah {{ formatDateTime(file.created_at) }}</span>
                  </div>
                </TransitionGroup>
              </template>
            </template>
          </div>
        </template>

        <div v-else class="panel-state">
          <EmptyState
            title="Pilih pengguna"
            description="Pilih salah satu pengguna di panel kiri untuk melihat isi drive mereka."
          />
        </div>
      </div>
    </div>

    <FilePreviewModal
      :visible="previewVisible"
      :file="previewFile"
      @close="previewVisible = false"
      @download="downloadFile"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from '../composables/useApi';
import { addToast } from '../components/ui/Toast.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import FilePreviewModal from '../components/ui/FilePreviewModal.vue';

const users = ref([]);
const loadingUsers = ref(false);
const userSearch = ref('');

const selectedUser = ref(null);
const folders = ref([]);
const files = ref([]);
const breadcrumbs = ref([]);
const currentFolderId = ref(null);
const loadingContents = ref(false);

const previewVisible = ref(false);
const previewFile = ref(null);

const filteredUsers = computed(() => {
  const q = userSearch.value.trim().toLowerCase();
  if (!q) return users.value;
  return users.value.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
});

const initials = (name) => (name || 'U').substring(0, 2).toUpperCase();

const formatSize = (bytes) => {
  if (!bytes) return '0 B';
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return parseFloat((bytes / Math.pow(1024, i)).toFixed(1)) + ' ' + units[i];
};

const formatDateTime = (dateString) => {
  if (!dateString) return '--';
  return new Intl.DateTimeFormat('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(dateString));
};

const fetchUsers = async () => {
  loadingUsers.value = true;
  try {
    const response = await api.get('/drive-monitor/users');
    users.value = response.data;
  } catch (error) {
    console.error('Failed to load users', error);
    addToast({ type: 'error', title: 'Gagal memuat', message: 'Tidak bisa memuat daftar pengguna.' });
  } finally {
    loadingUsers.value = false;
  }
};

const selectUser = (user) => {
  selectedUser.value = user;
  currentFolderId.value = null;
  breadcrumbs.value = [];
  fetchContents();
};

const fetchContents = async () => {
  if (!selectedUser.value) return;
  loadingContents.value = true;
  try {
    const response = await api.get(`/drive-monitor/users/${selectedUser.value.id}/contents`, {
      params: { folder_id: currentFolderId.value },
    });
    folders.value = response.data.folders || [];
    files.value = response.data.files || [];
  } catch (error) {
    console.error('Failed to load drive contents', error);
    addToast({ type: 'error', title: 'Gagal memuat', message: 'Tidak bisa memuat isi drive pengguna.' });
  } finally {
    loadingContents.value = false;
  }
};

const fetchBreadcrumbs = async () => {
  if (!selectedUser.value || !currentFolderId.value) {
    breadcrumbs.value = [];
    return;
  }
  try {
    const response = await api.get(`/drive-monitor/users/${selectedUser.value.id}/folders/${currentFolderId.value}`);
    breadcrumbs.value = response.data.trail || [];
  } catch (error) {
    breadcrumbs.value = [];
  }
};

const navigateToFolder = (folderId) => {
  currentFolderId.value = folderId;
  fetchContents();
  fetchBreadcrumbs();
};

const openPreview = (file) => {
  previewFile.value = file;
  previewVisible.value = true;
};

const downloadFile = async (file) => {
  try {
    const response = await api.get(`/drive-monitor/users/${selectedUser.value.id}/files/${file.id}/download`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.download = file.original_name;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    addToast({ type: 'error', title: 'Gagal mengunduh', message: `Tidak bisa mengunduh ${file.original_name}.` });
  }
};

onMounted(fetchUsers);
</script>

<style scoped>
.monitor-page { display: flex; flex-direction: column; gap: 1rem; min-height: 100%; }
/* page-header punya margin-bottom global; di sini spacing diatur oleh gap. */
.monitor-page .page-header { margin-bottom: 0; }

.monitor-body { flex: 1; display: flex; flex-direction: column; min-height: 420px; overflow: hidden; }

/* --- Panel daftar pengguna --- */
.user-panel { display: flex; flex-direction: column; min-height: 0; border-bottom: 1px solid var(--separator); background: var(--fill-tertiary); }
.panel-toolbar { display: flex; align-items: center; gap: .75rem; padding: .75rem; border-bottom: 1px solid var(--separator); }
.search-field { position: relative; flex: 1; min-width: 0; }
.search-icon { position: absolute; left: .7rem; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-muted); pointer-events: none; }
.panel-count { flex-shrink: 0; font-size: .72rem; font-weight: 600; color: var(--text-muted); white-space: nowrap; }
.panel-scroll { flex: 1; min-height: 0; overflow-y: auto; }

.user-row { width: 100%; display: flex; align-items: center; gap: .65rem; min-height: 58px; padding: .65rem .85rem; background: none; border: none; border-left: 3px solid transparent; cursor: pointer; transition: background .15s ease; text-align: left; }
.user-row:hover { background: var(--fill-tertiary); }
.user-row.active { background: var(--accent-tint); border-left-color: var(--accent-primary); }
.user-meta { display: flex; flex-direction: column; align-items: flex-end; gap: .1rem; flex-shrink: 0; }

/* --- Panel isi drive --- */
.content-panel { display: none; flex: 1; flex-direction: column; min-width: 0; min-height: 0; }
.content-panel.is-active { display: flex; }
.content-toolbar { display: flex; align-items: center; gap: .75rem; padding: .75rem; border-bottom: 1px solid var(--separator); }
.content-scroll { flex: 1; min-height: 0; display: flex; flex-direction: column; overflow-y: auto; padding: 1rem; }
.panel-state { flex: 1; display: flex; align-items: center; justify-content: center; padding: 1.5rem 1rem; }

.breadcrumbs { display: flex; align-items: center; gap: .4rem; padding: .55rem .75rem; overflow-x: auto; border-bottom: 1px solid var(--separator); font-size: .8125rem; font-weight: 500; }
.crumb { display: inline-flex; align-items: center; gap: .3rem; flex-shrink: 0; max-width: 160px; padding: .2rem .45rem; border: 0; border-radius: 7px; background: none; color: var(--text-secondary); cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color .15s ease, background .15s ease; }
.crumb svg { width: 15px; height: 15px; flex-shrink: 0; }
.crumb:hover { color: var(--accent-primary); background: var(--fill-secondary); }
.crumb.is-current { color: var(--text-primary); font-weight: 600; }
.crumb-sep { flex-shrink: 0; color: var(--text-muted); }

.content-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
@media (min-width: 768px) { .content-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1024px) { .content-grid { grid-template-columns: repeat(4, 1fr); } }

.folder-card { display: flex; align-items: center; gap: .65rem; padding: .7rem .85rem; background: var(--bg-secondary); border: 1px solid var(--separator); border-radius: 8px; cursor: pointer; transition: background .15s ease, border-color .15s ease; }
.folder-card:hover { background: var(--fill-secondary); }
.folder-glyph { color: #fbbc04; }

.file-card { display: flex; flex-direction: column; padding: .6rem; background: var(--bg-secondary); border: 1px solid var(--separator); border-radius: 8px; cursor: pointer; transition: box-shadow .15s ease; }
.file-card:hover { box-shadow: var(--shadow-hover); }
.file-thumb { height: 6rem; display: flex; align-items: center; justify-content: center; margin-bottom: .65rem; border-radius: 6px; background: var(--fill-secondary); color: var(--accent-primary); }

.grid-in-enter-active { transition: opacity .4s ease, transform .4s cubic-bezier(.2,.8,.2,1); transition-delay: var(--d, 0ms); }
.grid-in-enter-from { opacity: 0; transform: translateY(12px); }
@media (prefers-reduced-motion: reduce) { .grid-in-enter-active { transition: none; } }

/* Mobile: satu panel sekaligus, panel pengguna disembunyikan saat drive dibuka. */
@media (max-width: 767px) {
  .user-panel { flex: 1; min-height: 0; }
  .user-panel.is-collapsed { display: none; }
}

/* Desktop: dua panel berdampingan, tombol kembali tidak diperlukan. */
@media (min-width: 768px) {
  .monitor-body { flex-direction: row; }
  .user-panel { flex: 0 0 300px; border-bottom: 0; border-right: 1px solid var(--separator); }
  .user-panel.is-collapsed { display: flex; }
  .content-panel { display: flex; }
  .back-btn { display: none; }
}
</style>
