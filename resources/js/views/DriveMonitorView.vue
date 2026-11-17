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

          <!-- Actions: download all / selection -->
          <div v-if="!loadingContents && totalItems" class="content-actions" :class="{ 'is-selecting': selectedCount > 0 }">
            <label class="select-all" :title="allSelected ? 'Batalkan semua' : 'Pilih semua'">
              <input
                type="checkbox"
                class="check"
                :checked="allSelected"
                :indeterminate.prop="selectedCount > 0 && !allSelected"
                @change="allSelected ? clearSelection() : selectAll()"
              >
              <span v-if="selectedCount === 0">Pilih</span>
            </label>
            <span v-if="selectedCount" class="selection-count">{{ selectedCount }} item dipilih</span>
            <span v-else class="actions-meta">{{ summaryText }}</span>
            <span class="flex-1"></span>
            <template v-if="selectedCount">
              <button class="btn btn-sm btn-primary" :disabled="downloading" @click="startDownload">
                <svg v-if="!downloading" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <svg v-else class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
                {{ downloading ? downloadProgressText : 'Unduh' }}
              </button>
              <button class="btn-icon" title="Batalkan pilihan (Esc)" :disabled="downloading" @click="clearSelection">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              </button>
            </template>
            <button v-else class="btn btn-sm btn-secondary" :disabled="downloading" @click="downloadAll">
              <svg v-if="!downloading" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8v13H3V8"></path><path d="M1 3h22v5H1z"></path><path d="M10 12h4"></path></svg>
              <svg v-else class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
              {{ downloading ? downloadProgressText : 'Unduh Semua' }}
            </button>
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
                    :class="{ 'is-selected': isSelected('folder', folder.id) }"
                    :style="`--d: ${i * 45}ms`"
                    @click="onItemClick($event, 'folder', folder)"
                  >
                    <input
                      type="checkbox"
                      class="check item-check"
                      :checked="isSelected('folder', folder.id)"
                      :aria-label="`Pilih ${folder.name}`"
                      @click.stop
                      @change="toggleSelect('folder', folder.id)"
                    >
                    <svg class="w-6 h-6 folder-glyph flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                    <span class="text-sm font-medium truncate" :title="folder.name">{{ folder.name }}</span>
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
                    :class="{ 'is-selected': isSelected('file', file.id) }"
                    :style="`--d: ${i * 45}ms`"
                    @click="onItemClick($event, 'file', file)"
                  >
                    <div class="file-thumb">
                      <FileTypeIcon :name="file.original_name" size="lg" />
                      <input
                        type="checkbox"
                        class="check item-check thumb-check"
                        :checked="isSelected('file', file.id)"
                        :aria-label="`Pilih ${file.original_name}`"
                        @click.stop
                        @change="toggleSelect('file', file.id)"
                      >
                      <button class="btn-icon thumb-download" title="Unduh" @click.stop="downloadFile(file)">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                      </button>
                    </div>
                    <div class="file-info">
                      <span class="file-name" :title="file.original_name">{{ file.original_name }}</span>
                      <span class="file-meta" :title="formatDateTime(file.created_at)">{{ file.formatted_size }} · {{ formatDateTime(file.created_at) }}</span>
                    </div>
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

    <DownloadOptionsModal
      :visible="downloadModalVisible"
      :title="`Unduh dari drive ${selectedUser?.name || ''}`"
      :file-count="selectedFiles.length"
      :folder-count="selectedFolders.length"
      :default-name="defaultArchiveName()"
      @close="downloadModalVisible = false"
      @confirm="confirmDownload"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { api } from '../composables/useApi';
import { saveBlobResponse, blobErrorMessage } from '../composables/fileTypes';
import { addToast } from '../components/ui/Toast.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import FilePreviewModal from '../components/ui/FilePreviewModal.vue';
import FileTypeIcon from '../components/ui/FileTypeIcon.vue';
import DownloadOptionsModal from '../components/ui/DownloadOptionsModal.vue';

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
  clearSelection();
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
    saveBlobResponse(response, file.original_name);
    return true;
  } catch (error) {
    addToast({ type: 'error', title: 'Gagal mengunduh', message: await blobErrorMessage(error, `Tidak bisa mengunduh ${file.original_name}.`) });
    return false;
  }
};

// ----- Selection -----
const selected = ref(new Set());
const keyOf = (type, id) => `${type}:${id}`;
const isSelected = (type, id) => selected.value.has(keyOf(type, id));
const toggleSelect = (type, id) => {
  const next = new Set(selected.value);
  const key = keyOf(type, id);
  next.has(key) ? next.delete(key) : next.add(key);
  selected.value = next;
};
const selectAll = () => {
  selected.value = new Set([
    ...folders.value.map((f) => keyOf('folder', f.id)),
    ...files.value.map((f) => keyOf('file', f.id)),
  ]);
};
const clearSelection = () => { selected.value = new Set(); };
const totalItems = computed(() => folders.value.length + files.value.length);
const selectedCount = computed(() => selected.value.size);
const allSelected = computed(() => totalItems.value > 0 && selectedCount.value === totalItems.value);
const selectedFiles = computed(() => files.value.filter((f) => isSelected('file', f.id)));
const selectedFolders = computed(() => folders.value.filter((f) => isSelected('folder', f.id)));
const summaryText = computed(() => [
  folders.value.length ? `${folders.value.length} folder` : '',
  files.value.length ? `${files.value.length} file` : '',
].filter(Boolean).join(' · '));

const onItemClick = (event, type, item) => {
  if (selectedCount.value > 0 || event.ctrlKey || event.metaKey) {
    toggleSelect(type, item.id);
    return;
  }
  if (type === 'folder') navigateToFolder(item.id);
  else openPreview(item);
};

// ----- Bulk download -----
const downloadModalVisible = ref(false);
const downloading = ref(false);
const downloadProgressText = ref('');

const defaultArchiveName = () => {
  const d = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  const folderName = breadcrumbs.value.length ? breadcrumbs.value[breadcrumbs.value.length - 1].name : 'Semua';
  return `${selectedUser.value?.name || 'Drive'} - ${folderName} ${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
};

const startDownload = () => {
  if (selectedFiles.value.length === 1 && selectedFolders.value.length === 0) {
    runSingleDownloads();
    return;
  }
  downloadModalVisible.value = true;
};

const downloadAll = () => {
  selectAll();
  startDownload();
};

const confirmDownload = ({ mode, name }) => {
  downloadModalVisible.value = false;
  if (mode === 'zip') runArchiveDownload(name);
  else runSingleDownloads();
};

const runSingleDownloads = async () => {
  const list = [...selectedFiles.value];
  downloading.value = true;
  let ok = 0;
  for (const [i, file] of list.entries()) {
    downloadProgressText.value = `Mengunduh ${i + 1}/${list.length}`;
    if (await downloadFile(file)) ok++;
    if (i < list.length - 1) await new Promise((r) => setTimeout(r, 400));
  }
  downloading.value = false;
  if (list.length > 1) {
    addToast({ type: ok === list.length ? 'success' : 'warning', title: 'Unduhan selesai', message: `${ok} dari ${list.length} file berhasil diunduh.` });
  }
  if (ok === list.length) clearSelection();
};

const runArchiveDownload = async (name) => {
  downloading.value = true;
  downloadProgressText.value = 'Menyiapkan ZIP…';
  try {
    const response = await api.post(`/drive-monitor/users/${selectedUser.value.id}/download-archive`, {
      file_ids: selectedFiles.value.map((f) => f.id),
      folder_ids: selectedFolders.value.map((f) => f.id),
      name: name || undefined,
    }, {
      responseType: 'blob',
      timeout: 15 * 60 * 1000,
      onDownloadProgress: (e) => {
        if (e.loaded > 0) {
          downloadProgressText.value = e.total
            ? `Mengunduh ${Math.round((e.loaded / e.total) * 100)}%`
            : `Mengunduh ${(e.loaded / 1048576).toFixed(1)} MB`;
        }
      },
    });
    const filename = saveBlobResponse(response, `${name || 'Drive'}.zip`);
    const count = Number(response.headers['x-archive-files'] || 0);
    const missing = Number(response.headers['x-archive-missing'] || 0);
    addToast({
      type: missing ? 'warning' : 'success',
      title: 'Arsip ZIP siap',
      message: `${filename} berisi ${count} file.` + (missing ? ` ${missing} file tidak ditemukan di server dan dilewati.` : ''),
    });
    clearSelection();
  } catch (error) {
    console.error('Archive download failed', error);
    addToast({ type: 'error', title: 'Gagal membuat ZIP', message: await blobErrorMessage(error, 'Arsip tidak bisa dibuat. Coba lagi.') });
  } finally {
    downloading.value = false;
  }
};

const onKeydown = (event) => {
  if (event.key === 'Escape' && selectedCount.value && !downloadModalVisible.value) clearSelection();
};

onMounted(() => {
  fetchUsers();
  document.addEventListener('keydown', onKeydown);
});
onUnmounted(() => document.removeEventListener('keydown', onKeydown));
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

/* Download / selection bar above the content */
.content-actions {
  display: flex; align-items: center; gap: .65rem; min-height: 50px;
  padding: .4rem .75rem; border-bottom: 1px solid var(--separator);
  transition: background .15s ease;
}
.content-actions.is-selecting { background: var(--accent-tint); }
.select-all { display: inline-flex; align-items: center; gap: .55rem; font-size: .8125rem; font-weight: 500; color: var(--text-secondary); cursor: pointer; user-select: none; }
.actions-meta { font-size: .8125rem; color: var(--text-muted); white-space: nowrap; }
.selection-count { font-size: .875rem; font-weight: 600; color: #1e40af; white-space: nowrap; }

.folder-card { position: relative; display: flex; align-items: center; gap: .65rem; min-height: 54px; padding: .65rem .8rem; min-width: 0; background: var(--bg-secondary); border: 1px solid var(--separator); border-radius: 12px; box-shadow: var(--shadow-card); cursor: pointer; user-select: none; transition: border-color .15s ease, box-shadow .15s ease; }
.folder-card:hover { border-color: #cfd7e2; box-shadow: var(--shadow-hover); }
.folder-glyph { color: #f5b70a; }

.file-card { position: relative; display: flex; flex-direction: column; min-width: 0; overflow: hidden; background: var(--bg-secondary); border: 1px solid var(--separator); border-radius: 14px; box-shadow: var(--shadow-card); cursor: pointer; user-select: none; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
.file-card:hover { border-color: #cfd7e2; box-shadow: var(--shadow-hover); transform: translateY(-1px); }
.file-thumb { position: relative; height: 112px; display: flex; align-items: center; justify-content: center; background: linear-gradient(180deg, #f8fafc, #f1f4f8); border-bottom: 1px solid var(--separator); }
.file-info { display: flex; flex-direction: column; gap: .2rem; padding: .65rem .75rem .75rem; min-width: 0; }
.file-name { font-size: .8125rem; font-weight: 600; color: var(--text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.file-meta { font-size: .72rem; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Selection */
.folder-card.is-selected, .file-card.is-selected { border-color: var(--accent-primary); box-shadow: 0 0 0 1px var(--accent-primary); background: #f5f9ff; }
.file-card.is-selected .file-thumb { background: var(--accent-tint); }
.item-check { opacity: 0; transition: opacity .12s ease; }
.thumb-check { position: absolute; top: .55rem; left: .55rem; }
.folder-card .item-check { position: absolute; left: .8rem; }
.folder-card:hover .item-check, .file-card:hover .item-check, .item-check:checked, .item-check:focus-visible,
.monitor-page:has(.content-actions.is-selecting) .item-check { opacity: 1; }
.folder-card:hover .folder-glyph, .folder-card.is-selected .folder-glyph,
.monitor-page:has(.content-actions.is-selecting) .folder-glyph { opacity: 0; }
.thumb-download { position: absolute; top: .45rem; right: .45rem; width: 30px; height: 30px; border-radius: 8px; background: #fff; box-shadow: var(--shadow-card); opacity: 0; transition: opacity .12s ease; }
.file-card:hover .thumb-download, .thumb-download:focus-visible { opacity: 1; }
.monitor-page:has(.content-actions.is-selecting) .thumb-download { display: none; }
@media (hover: none) {
  .item-check, .thumb-download { opacity: 1; }
  .folder-card .folder-glyph { display: none; }
  .folder-card .item-check { position: static; }
}

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
