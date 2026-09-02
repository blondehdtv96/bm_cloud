<template>
  <div
    class="w-full h-full flex flex-col gap-4 relative"
    @dragenter.prevent="onDragEnter"
    @dragover.prevent
    @dragleave.prevent="onDragLeave"
    @drop.prevent="onDrop"
  >
    <!-- Toolbar -->
    <div class="glass-card p-3 flex flex-wrap items-center justify-between gap-3 animate-slide-down">
      <div class="flex items-center gap-2">
        <button class="btn btn-primary" @click="openFilePicker">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
          Unggah
        </button>
        <input ref="fileInputRef" type="file" multiple class="hidden" @change="onFilesSelected">

        <button class="btn btn-secondary" @click="showNewFolderModal = true">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg>
          Folder Baru
        </button>
      </div>
      
      <div class="view-toggle">
        <button class="view-toggle-btn" :class="{ active: viewMode === 'grid' }" @click="viewMode = 'grid'">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        </button>
        <button class="view-toggle-btn" :class="{ active: viewMode === 'list' }" @click="viewMode = 'list'">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
        </button>
      </div>
    </div>

    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-sm text-secondary font-medium px-2 animate-fade-in overflow-x-auto">
      <button class="hover:text-primary transition-colors flex items-center gap-1 flex-shrink-0" @click="navigateToFolder(null)">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        Drive Saya
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

    <!-- Upload progress panel -->
    <transition name="fade">
      <div v-if="uploadQueue.length" class="glass-card p-3 flex flex-col gap-2 animate-slide-down">
        <div class="flex items-center justify-between px-1">
          <span class="text-xs font-semibold text-secondary uppercase tracking-wider">
            Unggahan · {{ activeUploadCount }} aktif
          </span>
          <button v-if="allUploadsSettled" type="button" class="text-xs font-semibold text-secondary hover:text-primary" @click="uploadQueue = []">Bersihkan</button>
        </div>
        <div v-for="item in uploadQueue" :key="item.id" class="flex items-center gap-3 px-1 py-1">
          <svg v-if="item.status === 'done'" class="w-4 h-4 text-emerald-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
          <svg v-else-if="item.status === 'error'" class="w-4 h-4 text-red-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
          <svg v-else-if="item.status === 'queued'" class="w-4 h-4 text-secondary flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><polyline points="12 7 12 12 15 14"></polyline></svg>
          <svg v-else class="w-4 h-4 text-indigo-500 flex-shrink-0 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-3 text-xs mb-1">
              <span class="truncate font-medium" :class="item.status === 'error' ? 'text-danger' : 'text-primary'">{{ item.name }}</span>
              <div class="flex items-center gap-3 flex-shrink-0">
                <span class="text-secondary">
                  {{ item.status === 'queued' ? 'Menunggu…' : item.status === 'uploading' ? item.progress + '%' : item.status === 'processing' ? 'Memproses…' : item.status === 'done' ? 'Selesai' : 'Gagal' }}
                </span>
                <button
                  v-if="item.status === 'uploading' || item.status === 'processing'"
                  type="button"
                  class="font-semibold text-danger hover:underline"
                  @click="cancelUpload(item)"
                >Batal</button>
              </div>
            </div>
            <div class="h-1.5 w-full bg-slate-200 rounded-full overflow-hidden relative">
              <div
                class="h-full rounded-full"
                :class="[
                  item.status === 'error' ? 'bg-red-500' : (item.status === 'done' ? 'bg-emerald-500' : 'bg-indigo-500 upload-bar-active'),
                  item.status === 'processing' ? 'upload-bar-indeterminate' : 'transition-all duration-200',
                ]"
                :style="item.status === 'processing' ? '' : `width: ${item.progress}%`"
              ></div>
            </div>
            <div v-if="item.status === 'error'" class="flex items-center justify-between gap-2 mt-1">
              <span class="text-xs text-danger truncate" :title="item.errorMessage">{{ item.errorMessage }}</span>
              <button type="button" class="text-xs font-semibold text-accent-primary hover:underline flex-shrink-0" @click="retryUpload(item)">Coba lagi</button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Dropzone / Content Area -->
    <div class="flex-1 relative glass-card p-4 overflow-y-auto min-h-[400px]">

      <!-- Drag overlay -->
      <div v-if="isDragging" class="absolute inset-2 z-20 rounded-xl border-2 border-dashed border-indigo-400 bg-indigo-500/10 flex flex-col items-center justify-center pointer-events-none">
        <svg class="w-12 h-12 text-indigo-400 mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
        <p class="text-indigo-300 font-medium">Lepaskan file di sini untuk mengunggah</p>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="h-full flex items-center justify-center">
        <div class="animate-pulse flex flex-col items-center gap-2">
          <svg class="w-8 h-8 text-primary animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
          <span class="text-sm text-secondary">Memuat isi folder...</span>
        </div>
      </div>

      <!-- Empty state -->
      <EmptyState
        v-else-if="folders.length === 0 && files.length === 0"
        title="Folder ini masih kosong"
        description="Seret file ke sini atau klik tombol Unggah untuk menambahkan file."
        action-text="Unggah File"
        @action="openFilePicker"
      />

      <template v-else>
        <!-- Folder Grid -->
        <template v-if="folders.length">
          <h3 class="section-label">Folder</h3>
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
            <div
              v-for="folder in folders"
              :key="'f' + folder.id"
              class="folder-tile group"
              @click="navigateToFolder(folder.id)"
            >
              <svg class="w-5 h-5 folder-glyph flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
              <span class="text-sm font-medium truncate flex-1">{{ folder.name }}</span>
              <button class="tile-action btn-icon text-secondary hover:text-primary opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0" @click.stop="openShare(folder, 'folder')" title="Bagikan">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
              </button>
              <button class="tile-action btn-icon text-secondary hover:text-danger opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0" @click.stop="deleteFolder(folder)" title="Hapus">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
              </button>
            </div>
          </div>
        </template>

        <!-- File Grid / List -->
        <template v-if="files.length">
          <h3 class="section-label">File</h3>
          <div v-if="viewMode === 'grid'" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <div
              v-for="file in files"
              :key="'doc' + file.id"
              class="file-tile-card group"
              @click="openPreview(file)"
            >
              <div class="file-thumb">
                <svg class="w-9 h-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <div class="file-thumb-actions">
                  <button class="btn-icon text-secondary hover:text-primary bg-white" @click.stop="openShare(file, 'file')" title="Bagikan">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                  </button>
                  <button class="btn-icon text-secondary hover:text-primary bg-white" @click.stop="downloadFile(file)" title="Unduh">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                  </button>
                  <button class="btn-icon text-secondary hover:text-danger bg-white" @click.stop="deleteFile(file)" title="Hapus">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                  </button>
                </div>
              </div>
              <span class="text-sm font-medium truncate mb-0.5" :title="file.original_name">{{ file.original_name }}</span>
              <span class="text-xs text-secondary">{{ file.formatted_size }}</span>
              <span class="text-xs text-secondary" :title="formatDateTime(file.created_at)">{{ formatDate(file.created_at) }}</span>
            </div>
          </div>

          <table v-else class="table-modern">
            <thead>
              <tr>
                <th>Nama</th>
                <th class="hidden md:table-cell">Ukuran</th>
                <th class="hidden md:table-cell">Diunggah</th>
                <th class="text-right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="file in files" :key="'list' + file.id" class="group cursor-pointer" @click="openPreview(file)">
                <td>
                  <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-indigo-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    <span class="font-medium truncate">{{ file.original_name }}</span>
                  </div>
                </td>
                <td class="text-secondary hidden md:table-cell">{{ file.formatted_size }}</td>
                <td class="text-secondary hidden md:table-cell">{{ formatDateTime(file.created_at) }}</td>
                <td class="text-right">
                  <div class="row-actions flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button class="btn-icon text-secondary hover:text-primary" @click.stop="openShare(file, 'file')" title="Bagikan">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                    </button>
                    <button class="btn-icon text-secondary hover:text-primary" @click.stop="downloadFile(file)" title="Unduh">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    </button>
                    <button class="btn-icon text-secondary hover:text-danger" @click.stop="deleteFile(file)" title="Hapus">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </template>
      </template>
    </div>

    <!-- File Preview Modal -->
    <FilePreviewModal
      :visible="previewVisible"
      :file="previewFile"
      @close="closePreview"
      @download="downloadFile"
    />

    <!-- Share Modal -->
    <ShareModal :visible="shareVisible" :item="shareItem" @close="shareVisible = false" />

    <!-- New Folder Modal -->
    <Modal :visible="showNewFolderModal" title="Buat Folder Baru" size="sm" @close="closeNewFolderModal">
      <form @submit.prevent="createFolder">
        <div class="form-group mb-0">
          <label class="form-label">Nama Folder</label>
          <input
            ref="folderNameInputRef"
            v-model="newFolderName"
            type="text"
            class="form-control"
            placeholder="Folder Baru"
            maxlength="255"
            required
          >
        </div>
      </form>
      <template #footer>
        <button class="btn btn-secondary" @click="closeNewFolderModal">Batal</button>
        <button class="btn btn-primary" :disabled="creatingFolder || !newFolderName.trim()" @click="createFolder">
          {{ creatingFolder ? 'Membuat...' : 'Buat Folder' }}
        </button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api } from '../composables/useApi';
import { addToast } from '../components/ui/Toast.vue';
import Modal from '../components/ui/Modal.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import FilePreviewModal from '../components/ui/FilePreviewModal.vue';
import ShareModal from '../components/ui/ShareModal.vue';

const route = useRoute();
const router = useRouter();

const folderId = ref(route.params.folderId ? Number(route.params.folderId) : null);
const viewMode = ref('grid'); // 'grid' | 'list'

const folders = ref([]);
const files = ref([]);
const breadcrumbs = ref([]);
const loading = ref(false);

const fileInputRef = ref(null);
const isDragging = ref(false);
let dragCounter = 0;

const uploadQueue = ref([]);
const activeUploadCount = computed(() => uploadQueue.value.filter(i => ['queued', 'uploading', 'processing'].includes(i.status)).length);
const allUploadsSettled = computed(() => uploadQueue.value.length > 0 && uploadQueue.value.every(i => ['done', 'error'].includes(i.status)));

const showNewFolderModal = ref(false);
const newFolderName = ref('');
const creatingFolder = ref(false);
const folderNameInputRef = ref(null);

const previewVisible = ref(false);
const previewFile = ref(null);

const openPreview = (file) => {
  previewFile.value = file;
  previewVisible.value = true;
};
const closePreview = () => {
  previewVisible.value = false;
};

// ----- Share -----
const shareVisible = ref(false);
const shareItem = ref(null);

const openShare = (entity, type) => {
  shareItem.value = {
    id: entity.id,
    type,
    name: type === 'folder' ? entity.name : entity.original_name,
  };
  shareVisible.value = true;
};

const fetchContents = async () => {
  loading.value = true;
  try {
    const response = await api.get('/folders', { params: { folder_id: folderId.value } });
    folders.value = response.data.folders || [];
    files.value = response.data.files || [];
  } catch (error) {
    console.error('Failed to load folder contents', error);
    addToast({ type: 'error', title: 'Gagal memuat', message: 'Tidak bisa memuat isi folder.' });
  } finally {
    loading.value = false;
  }
};

const fetchBreadcrumbs = async () => {
  if (!folderId.value) {
    breadcrumbs.value = [];
    return;
  }
  try {
    const response = await api.get(`/folders/${folderId.value}`);
    breadcrumbs.value = response.data.trail || [];
  } catch (error) {
    breadcrumbs.value = [];
  }
};

const loadAll = () => Promise.all([fetchContents(), fetchBreadcrumbs()]);

const navigateToFolder = (id) => {
  if (id) {
    router.push(`/drive/${id}`);
  } else {
    router.push('/drive');
  }
};

watch(() => route.params.folderId, (val) => {
  folderId.value = val ? Number(val) : null;
  loadAll();
});

// Dipicu oleh tombol "Baru" di sidebar (router.push({ path: '/drive', query: { action } })).
const applyQueryAction = () => {
  const action = route.query.action;
  if (!action) return;
  if (action === 'upload') openFilePicker();
  if (action === 'folder') showNewFolderModal.value = true;
  router.replace({ path: route.path, query: {} });
};

watch(() => route.query.action, applyQueryAction);

onMounted(() => {
  loadAll();
  applyQueryAction();
});

// ----- Upload -----
const openFilePicker = () => fileInputRef.value?.click();

const onFilesSelected = (e) => {
  const selected = Array.from(e.target.files || []);
  uploadFiles(selected);
  e.target.value = '';
};

const onDragEnter = () => {
  dragCounter++;
  isDragging.value = true;
};
const onDragLeave = () => {
  dragCounter = Math.max(0, dragCounter - 1);
  if (dragCounter === 0) isDragging.value = false;
};
const onDrop = (e) => {
  dragCounter = 0;
  isDragging.value = false;
  const dropped = Array.from(e.dataTransfer?.files || []);
  uploadFiles(dropped);
};

let uploadIdSeq = 1;
const MAX_CONCURRENT_UPLOADS = 2;
const MAX_UPLOAD_SIZE_BYTES = 512 * 1024 * 1024;
const UPLOAD_TIMEOUT_MS = 180000;
const uploadControllers = new Map();

const uploadFiles = (fileList) => {
  if (!fileList.length) return;

  fileList.forEach((file) => {
    const tooLarge = file.size > MAX_UPLOAD_SIZE_BYTES;
    uploadQueue.value.push({
      id: uploadIdSeq++,
      file,
      name: file.name,
      progress: 0,
      status: tooLarge ? 'error' : 'queued',
      errorMessage: tooLarge ? 'Ukuran maksimum file adalah 512 MB.' : '',
    });
  });

  processUploadQueue();
};

/**
 * Maksimal dua transfer bersamaan. Mengirim semua file sekaligus sebelumnya
 * membuat koneksi browser/Apache penuh dan item lain terlihat diam di 0%.
 */
const processUploadQueue = () => {
  const running = uploadQueue.value.filter((item) => ['uploading', 'processing'].includes(item.status)).length;
  const slots = Math.max(0, MAX_CONCURRENT_UPLOADS - running);

  uploadQueue.value
    .filter((item) => item.status === 'queued')
    .slice(0, slots)
    .forEach(uploadSingleFile);
};

const uploadSingleFile = async (item) => {
  const file = item.file;
  const formData = new FormData();
  formData.append('file', file);
  if (folderId.value) formData.append('folder_id', folderId.value);

  const controller = new AbortController();
  uploadControllers.set(item.id, controller);
  item.status = 'uploading';
  item.progress = 1; // Beri umpan balik segera, bahkan sebelum event progress pertama.
  item.errorMessage = '';

  try {
    const response = await api.post('/files/upload', formData, {
      // Jangan set Content-Type manual. Axios/browser akan menambahkan boundary.
      signal: controller.signal,
      timeout: UPLOAD_TIMEOUT_MS,
      onUploadProgress: (event) => {
        if (event.loaded <= 0) return;

        const total = event.total || file.size;
        const percent = total > 0 ? Math.round((event.loaded / total) * 100) : 1;
        item.progress = Math.max(1, Math.min(100, percent));

        // Transfer browser selesai; server masih menyimpan, hash, dan mencatat DB.
        if ((event.total && event.loaded >= event.total) || item.progress >= 100) {
          item.progress = 100;
          item.status = 'processing';
        }
      },
    });

    item.status = 'done';
    item.progress = 100;

    // Retry setelah respons jaringan terputus dapat mengembalikan item yang sama;
    // hindari menampilkan duplikat berdasarkan id database.
    if (!files.value.some((existing) => existing.id === response.data.id)) {
      files.value.push(response.data);
    }

    addToast({ type: 'success', title: 'Unggah berhasil', message: `${file.name} berhasil diunggah.` });
  } catch (error) {
    item.status = 'error';
    item.errorMessage = uploadErrorMessage(error, file);
    console.error('Upload failed', error);
    addToast({
      type: 'error',
      title: 'Unggah gagal',
      message: `${file.name}: ${item.errorMessage}`,
    });
  } finally {
    uploadControllers.delete(item.id);
    processUploadQueue();
  }
};

const cancelUpload = (item) => {
  uploadControllers.get(item.id)?.abort();
};

const retryUpload = (item) => {
  item.status = 'queued';
  item.progress = 0;
  item.errorMessage = '';
  processUploadQueue();
};

const uploadErrorMessage = (error, file) => {
  if (error.code === 'ERR_CANCELED') return 'Unggahan dibatalkan.';
  if (error.code === 'ECONNABORTED') return 'Server tidak merespons dalam 3 menit. Periksa MySQL lalu coba lagi.';

  const status = error.response?.status;
  if (status === 413) return 'Ukuran file melebihi batas yang diizinkan server.';
  if (status === 422) {
    const firstFieldError = Object.values(error.response?.data?.errors || {})[0]?.[0];
    return firstFieldError || error.response?.data?.message || 'File tidak valid.';
  }
  if (status === 503) return 'Layanan penyimpanan atau database sedang tidak tersedia.';
  if (status >= 500) return error.response?.data?.message || 'Terjadi kesalahan pada server. Coba lagi beberapa saat lagi.';

  const serverMessage = error.response?.data?.message;
  if (serverMessage) return serverMessage;
  if (!error.response) return 'Koneksi terputus saat mengunggah. Periksa server dan jaringan lalu coba lagi.';
  return `Gagal mengunggah ${file.name}.`;
};

onUnmounted(() => {
  uploadControllers.forEach((controller) => controller.abort());
  uploadControllers.clear();
});

// ----- Folder actions -----
const closeNewFolderModal = () => {
  showNewFolderModal.value = false;
  newFolderName.value = '';
};

watch(showNewFolderModal, async (val) => {
  if (val) {
    await nextTick();
    folderNameInputRef.value?.focus();
  }
});

const createFolder = async () => {
  const name = newFolderName.value.trim();
  if (!name) return;

  creatingFolder.value = true;
  try {
    const response = await api.post('/folders', {
      name,
      parent_id: folderId.value,
    });
    folders.value.push(response.data);
    addToast({ type: 'success', title: 'Folder dibuat', message: `Folder "${name}" berhasil dibuat.` });
    closeNewFolderModal();
  } catch (error) {
    console.error('Failed to create folder', error);
    addToast({ type: 'error', title: 'Gagal', message: error.response?.data?.message || 'Tidak bisa membuat folder.' });
  } finally {
    creatingFolder.value = false;
  }
};

const deleteFolder = async (folder) => {
  if (!confirm(`Hapus folder "${folder.name}"? Folder akan dipindahkan ke sampah.`)) return;
  try {
    await api.delete(`/folders/${folder.id}`);
    folders.value = folders.value.filter(f => f.id !== folder.id);
    addToast({ type: 'success', title: 'Folder dihapus', message: `"${folder.name}" dipindahkan ke sampah.` });
  } catch (error) {
    addToast({ type: 'error', title: 'Gagal', message: 'Tidak bisa menghapus folder.' });
  }
};

// ----- File actions -----
const downloadFile = async (file) => {
  try {
    const response = await api.get(`/files/${file.id}/download`, { responseType: 'blob' });
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

const deleteFile = async (file) => {
  if (!confirm(`Hapus "${file.original_name}"? File akan dipindahkan ke sampah.`)) return;
  try {
    await api.delete(`/files/${file.id}`);
    files.value = files.value.filter(f => f.id !== file.id);
    addToast({ type: 'success', title: 'File dihapus', message: `"${file.original_name}" dipindahkan ke sampah.` });
  } catch (error) {
    addToast({ type: 'error', title: 'Gagal', message: 'Tidak bisa menghapus file.' });
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '--';
  return new Intl.DateTimeFormat('id-ID', { year: 'numeric', month: 'short', day: 'numeric' }).format(new Date(dateString));
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
</script>

<style scoped>
.view-toggle { display: flex; align-items: center; gap: 2px; border: 0; border-radius: 9px; background: var(--fill-secondary); padding: 2px; }
.view-toggle-btn { display: flex; align-items: center; justify-content: center; width: 34px; height: 32px; border-radius: 7px; color: var(--text-secondary); background: none; border: none; cursor: pointer; transition: background .15s ease, color .15s ease, box-shadow .15s ease; }
.view-toggle-btn:hover { color: var(--text-primary); }
.view-toggle-btn.active { background: var(--bg-secondary); color: var(--accent-primary); box-shadow: var(--shadow-card); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Efek shimmer bergerak di atas progress bar selama transfer berlangsung. */
.upload-bar-active {
  position: relative;
  overflow: hidden;
}
.upload-bar-active::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .35), transparent);
  animation: upload-shimmer 1.2s linear infinite;
}
@keyframes upload-shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

/* Saat server masih menyimpan file (progress browser sudah 100%), tampilkan bar tak-tentu. */
.upload-bar-indeterminate {
  width: 40% !important;
  animation: upload-indeterminate 1.1s ease-in-out infinite;
}
@keyframes upload-indeterminate {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(250%); }
}

@media (prefers-reduced-motion: reduce) {
  .upload-bar-active::after,
  .upload-bar-indeterminate { animation: none; }
}

/* Kartu folder ala Drive: baris kompak, ikon + nama, tanpa efek angkat. */
.folder-tile {
  display: flex;
  align-items: center;
  gap: .65rem;
  padding: .7rem .85rem;
  border: 1px solid var(--separator);
  border-radius: 8px;
  background: var(--bg-secondary);
  cursor: pointer;
  transition: background .15s ease, border-color .15s ease;
}
.folder-tile:hover { background: var(--fill-secondary); border-color: var(--separator-opaque); }
.folder-glyph { color: #fbbc04; }

/* Kartu file ala Drive: thumbnail di atas, nama + ukuran di bawah, terangkat saat hover. */
.file-tile-card {
  display: flex;
  flex-direction: column;
  padding: .6rem;
  border: 1px solid var(--separator);
  border-radius: 8px;
  background: var(--bg-secondary);
  cursor: pointer;
  transition: box-shadow .15s ease, border-color .15s ease;
}
.file-tile-card:hover { box-shadow: var(--shadow-hover); border-color: var(--separator); }
.file-thumb {
  position: relative;
  height: 6rem;
  margin-bottom: .65rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  background: var(--fill-secondary);
  color: var(--accent-primary);
}
.file-thumb-actions {
  position: absolute;
  top: .35rem;
  right: .35rem;
  display: flex;
  gap: .25rem;
  opacity: 0;
  transition: opacity .15s ease;
}
.file-tile-card:hover .file-thumb-actions,
.file-thumb-actions:focus-within { opacity: 1; }
.file-thumb-actions .btn-icon { width: 28px; height: 28px; box-shadow: var(--shadow-card); }

/* Perangkat sentuh tidak punya :hover, jadi aksi (Bagikan/Unduh/Hapus) yang
   sebelumnya hanya muncul saat hover harus selalu tampil di sana. */
@media (hover: none) {
  .tile-action,
  .file-thumb-actions,
  .row-actions {
    opacity: 1;
  }
}
</style>
