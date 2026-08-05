<template>
  <div class="w-full h-full flex flex-col gap-4">
    <div class="page-header">
      <div>
        <h1>
          <svg class="w-5 h-5 text-accent-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
          Pantau Drive Pengguna
        </h1>
        <p>Tinjau file dan folder milik staf secara read-only, tanpa mengubah data mereka.</p>
      </div>
    </div>

    <div class="glass-card flex-1 overflow-hidden flex flex-col md:flex-row">
      <!-- User list panel -->
      <div class="user-panel" :class="{ 'md:hidden': selectedUser }">
        <div class="p-3 border-b border-glass-border">
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input v-model="userSearch" type="text" placeholder="Cari pengguna..." class="form-control form-control-sm pl-9 bg-black/20 w-full">
          </div>
        </div>

        <div class="flex-1 overflow-y-auto">
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
            <div class="text-right flex-shrink-0">
              <div class="text-xs text-secondary">{{ u.files_count }} file</div>
              <div class="text-[10px] text-muted">{{ formatSize(u.storage_used) }}</div>
            </div>
          </button>

          <div v-if="!loadingUsers && filteredUsers.length === 0" class="p-6 text-center text-sm text-secondary">
            Tidak ada pengguna ditemukan.
          </div>
        </div>
      </div>

      <!-- Drive content panel -->
      <div class="flex-1 flex flex-col min-w-0" :class="{ 'md:flex': true, 'hidden': !selectedUser }">
        <template v-if="selectedUser">
          <div class="p-3 border-b border-glass-border flex items-center gap-3">
            <button class="btn-icon text-secondary hover:text-primary md:hidden" @click="selectedUser = null">
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
          <div class="flex items-center gap-2 text-sm text-secondary font-medium px-3 py-2 overflow-x-auto border-b border-glass-border">
            <button class="hover:text-primary transition-colors flex items-center gap-1 flex-shrink-0" @click="navigateToFolder(null)">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
              Root
            </button>
            <template v-for="(crumb, idx) in breadcrumbs" :key="crumb.id">
              <span class="flex-shrink-0">/</span>
              <button
                class="hover:text-primary transition-colors truncate max-w-[140px] flex-shrink-0"
                :class="{ 'text-primary': idx === breadcrumbs.length - 1 }"
                @click="navigateToFolder(crumb.id)"
              >
                {{ crumb.name }}
              </button>
            </template>
          </div>

          <div class="flex-1 overflow-y-auto p-4 relative">
            <div v-if="loadingContents" class="h-full flex items-center justify-center">
              <svg class="w-8 h-8 text-primary animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
            </div>

            <EmptyState
              v-else-if="folders.length === 0 && files.length === 0"
              title="Folder ini kosong"
              description="Pengguna ini belum menyimpan apa pun di folder ini."
            />

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
                  </div>
                </TransitionGroup>
              </template>
            </template>
          </div>
        </template>

        <div v-else class="flex-1 hidden md:flex items-center justify-center">
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
.user-panel { width: 100%; display: flex; flex-direction: column; border-right: 1px solid var(--separator); background: rgba(249,249,251,.7); }
@media (min-width: 768px) { .user-panel { width: 290px; flex-shrink: 0; } }
.user-row { width: 100%; display: flex; align-items: center; gap: .65rem; min-height: 58px; padding: .65rem .85rem; background: none; border: none; border-left: 3px solid transparent; cursor: pointer; transition: background .15s ease; text-align: left; }
.user-row:hover { background: var(--fill-tertiary); }
.user-row.active { background: rgba(0,122,255,.10); border-left-color: var(--accent-primary); }

.section-label { font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--text-muted); margin-bottom: .8rem; }
.content-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
@media (min-width: 768px) { .content-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1024px) { .content-grid { grid-template-columns: repeat(4, 1fr); } }

.folder-card { display: flex; align-items: center; gap: .75rem; padding: .95rem 1rem; background: var(--bg-secondary); border: 1px solid var(--separator); border-radius: 14px; cursor: pointer; transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; }
.folder-card:hover { transform: translateY(-3px); border-color: rgba(0,122,255,.4); box-shadow: 0 8px 22px rgba(0,0,0,.07); }
.folder-glyph { color: #ffb300; }

.file-card { display: flex; flex-direction: column; padding: .85rem; background: var(--bg-secondary); border: 1px solid var(--separator); border-radius: 14px; cursor: pointer; transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; }
.file-card:hover { transform: translateY(-3px); border-color: rgba(0,122,255,.4); box-shadow: 0 8px 22px rgba(0,0,0,.07); }
.file-thumb { height: 76px; display: flex; align-items: center; justify-content: center; margin-bottom: .75rem; border-radius: 11px; background: rgba(0,122,255,.08); color: var(--accent-primary); transition: transform .2s ease; }
.file-card:hover .file-thumb { transform: scale(1.04); }

.grid-in-enter-active { transition: opacity .4s ease, transform .4s cubic-bezier(.2,.8,.2,1); transition-delay: var(--d, 0ms); }
.grid-in-enter-from { opacity: 0; transform: translateY(12px); }
@media (prefers-reduced-motion: reduce) { .grid-in-enter-active { transition: none; } }
</style>
