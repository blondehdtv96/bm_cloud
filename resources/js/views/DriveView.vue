<template>
  <div
    class="page drive-page"
    @dragenter.prevent="onDragEnter"
    @dragover.prevent
    @dragleave.prevent="onDragLeave"
    @drop.prevent="onDrop"
  >
    <!-- Page header: title + breadcrumb, primary actions -->
    <div class="page-header !mb-0">
      <div class="min-w-0">
        <nav class="crumbs" aria-label="Lokasi folder">
          <button type="button" class="crumb" :class="{ 'is-current': !breadcrumbs.length }" @click="navigateToFolder(null)">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            Drive Saya
          </button>
          <template v-for="(crumb, idx) in breadcrumbs" :key="crumb.id">
            <svg class="crumb-sep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            <button
              type="button"
              class="crumb"
              :class="{ 'is-current': idx === breadcrumbs.length - 1 }"
              :title="crumb.name"
              @click="navigateToFolder(crumb.id)"
            >{{ crumb.name }}</button>
          </template>
        </nav>
        <h1 class="truncate">{{ currentFolderName }}</h1>
      </div>

      <div class="flex items-center gap-2 flex-shrink-0">
        <button class="btn btn-secondary" @click="showNewFolderModal = true">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg>
          Folder Baru
        </button>
        <button class="btn btn-primary" @click="openFilePicker">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
          Unggah
        </button>
        <input ref="fileInputRef" type="file" multiple class="hidden" @change="onFilesSelected">
      </div>
    </div>

    <!-- Toolbar / selection bar -->
    <div class="drive-toolbar" :class="{ 'is-selecting': selectedCount > 0 }">
      <template v-if="selectedCount === 0">
        <label v-if="totalItems" class="select-all" title="Pilih semua">
          <input type="checkbox" class="check" :checked="false" @change="selectAll">
          <span>Pilih</span>
        </label>
        <span class="toolbar-meta">{{ summaryText }}</span>
        <span class="toolbar-spacer"></span>
        <div class="segmented" role="group" aria-label="Tampilan">
          <button type="button" :class="{ 'is-active': viewMode === 'grid' }" title="Tampilan petak" :aria-pressed="viewMode === 'grid'" @click="setViewMode('grid')">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
          </button>
          <button type="button" :class="{ 'is-active': viewMode === 'list' }" title="Tampilan daftar" :aria-pressed="viewMode === 'list'" @click="setViewMode('list')">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
          </button>
        </div>
      </template>

      <template v-else>
        <label class="select-all" :title="allSelected ? 'Batalkan semua' : 'Pilih semua'">
          <input
            type="checkbox"
            class="check"
            :checked="allSelected"
            :indeterminate.prop="!allSelected"
            @change="allSelected ? clearSelection() : selectAll()"
          >
        </label>
        <span class="selection-count">{{ selectedCount }} item dipilih</span>
        <span class="toolbar-meta hidden sm:inline">{{ selectedSizeText }}</span>
        <span class="toolbar-spacer"></span>
        <button class="btn btn-sm btn-primary" :disabled="downloading" @click="startDownload">
          <svg v-if="!downloading" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          <svg v-else class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
          {{ downloading ? downloadProgressText : 'Unduh' }}
        </button>
        <button class="btn btn-sm btn-secondary sel-danger" :disabled="downloading" @click="deleteSelected">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          <span class="hidden sm:inline">Hapus</span>
        </button>
        <button class="btn-icon" title="Batalkan pilihan (Esc)" @click="clearSelection">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </template>
    </div>

    <!-- Upload progress panel -->
    <transition name="fade">
      <div v-if="uploadQueue.length" class="card upload-panel">
        <div class="flex items-center justify-between">
          <span class="section-label !mb-0">Unggahan · {{ activeUploadCount }} aktif</span>
          <button v-if="allUploadsSettled" type="button" class="btn btn-sm btn-ghost" @click="uploadQueue = []">Bersihkan</button>
        </div>
        <div v-for="item in uploadQueue" :key="item.id" class="flex items-center gap-3">
          <FileTypeIcon :name="item.name" size="sm" />
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-3 text-xs mb-1.5">
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
            <div class="progress-track">
              <div
                class="progress-bar"
                :class="[
                  item.status === 'error' ? 'is-error' : (item.status === 'done' ? 'is-done' : 'upload-bar-active'),
                  item.status === 'processing' ? 'upload-bar-indeterminate' : '',
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

    <!-- Content -->
    <div class="drive-content">
      <div v-if="isDragging" class="drop-overlay">
        <svg class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
        <p>Lepaskan file untuk mengunggah ke <strong>{{ currentFolderName }}</strong></p>
      </div>

      <div v-if="loading" class="card loading-state">
        <svg class="w-7 h-7 animate-spin text-accent-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
        <span class="text-sm text-secondary">Memuat isi folder…</span>
      </div>

      <div v-else-if="totalItems === 0" class="card">
        <EmptyState
          title="Folder ini masih kosong"
          description="Seret file ke sini atau klik tombol Unggah untuk menambahkan file."
          action-text="Unggah File"
          @action="openFilePicker"
        />
      </div>

      <!-- Grid view -->
      <template v-else-if="viewMode === 'grid'">
        <section v-if="folders.length">
          <h2 class="section-label">Folder</h2>
          <div class="folder-grid">
            <div
              v-for="folder in folders"
              :key="'f' + folder.id"
              class="folder-tile"
              :class="{ 'is-selected': isSelected('folder', folder.id) }"
              tabindex="0"
              @click="onItemClick($event, 'folder', folder)"
              @keydown.enter="navigateToFolder(folder.id)"
            >
              <input
                type="checkbox"
                class="check tile-check"
                :checked="isSelected('folder', folder.id)"
                :aria-label="`Pilih ${folder.name}`"
                @click.stop
                @change="toggleSelect('folder', folder.id)"
              >
              <svg class="folder-glyph" viewBox="0 0 24 24" fill="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
              <span class="folder-name" :title="folder.name">{{ folder.name }}</span>
              <div class="tile-actions">
                <button class="btn-icon" title="Bagikan" @click.stop="openShare(folder, 'folder')">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                </button>
                <button class="btn-icon danger" title="Hapus" @click.stop="deleteFolder(folder)">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
              </div>
            </div>
          </div>
        </section>

        <section v-if="files.length">
          <h2 class="section-label">File</h2>
          <div class="file-grid">
            <div
              v-for="file in files"
              :key="'doc' + file.id"
              class="file-card"
              :class="{ 'is-selected': isSelected('file', file.id) }"
              tabindex="0"
              @click="onItemClick($event, 'file', file)"
              @keydown.enter="openPreview(file)"
            >
              <div class="file-thumb">
                <FileTypeIcon :name="file.original_name" size="lg" />
                <input
                  type="checkbox"
                  class="check tile-check"
                  :checked="isSelected('file', file.id)"
                  :aria-label="`Pilih ${file.original_name}`"
                  @click.stop
                  @change="toggleSelect('file', file.id)"
                >
                <div class="tile-actions">
                  <button class="btn-icon" title="Bagikan" @click.stop="openShare(file, 'file')">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                  </button>
                  <button class="btn-icon" title="Unduh" @click.stop="downloadFile(file)">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                  </button>
                  <button class="btn-icon danger" title="Hapus" @click.stop="deleteFile(file)">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                  </button>
                </div>
              </div>
              <div class="file-info">
                <span class="file-name" :title="file.original_name">{{ file.original_name }}</span>
                <span class="file-meta" :title="formatDateTime(file.created_at)">{{ file.formatted_size }} · {{ formatDate(file.created_at) }}</span>
              </div>
            </div>
          </div>
        </section>
      </template>

      <!-- List view: folders and files in one table -->
      <div v-else class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table-modern drive-table">
            <thead>
              <tr>
                <th class="w-10">
                  <input
                    type="checkbox"
                    class="check"
                    :checked="allSelected"
                    :indeterminate.prop="selectedCount > 0 && !allSelected"
                    aria-label="Pilih semua"
                    @change="allSelected ? clearSelection() : selectAll()"
                  >
                </th>
                <th>Nama</th>
                <th class="hidden md:table-cell w-32">Ukuran</th>
                <th class="hidden md:table-cell w-48">Diubah</th>
                <th class="w-32"><span class="sr-only">Aksi</span></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="folder in folders"
                :key="'lf' + folder.id"
                class="cursor-pointer"
                :class="{ 'is-selected': isSelected('folder', folder.id) }"
                @click="onItemClick($event, 'folder', folder)"
              >
                <td @click.stop>
                  <input type="checkbox" class="check" :checked="isSelected('folder', folder.id)" :aria-label="`Pilih ${folder.name}`" @change="toggleSelect('folder', folder.id)">
                </td>
                <td>
                  <div class="flex items-center gap-3 min-w-0">
                    <span class="folder-icon-sm">
                      <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                    </span>
                    <span class="font-medium truncate">{{ folder.name }}</span>
                  </div>
                </td>
                <td class="text-secondary hidden md:table-cell">—</td>
                <td class="text-secondary hidden md:table-cell">{{ formatDateTime(folder.updated_at || folder.created_at) }}</td>
                <td>
                  <div class="row-actions">
                    <button class="btn-icon" title="Bagikan" @click.stop="openShare(folder, 'folder')">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                    </button>
                    <button class="btn-icon danger" title="Hapus" @click.stop="deleteFolder(folder)">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr
                v-for="file in files"
                :key="'lx' + file.id"
                class="cursor-pointer"
                :class="{ 'is-selected': isSelected('file', file.id) }"
                @click="onItemClick($event, 'file', file)"
              >
                <td @click.stop>
                  <input type="checkbox" class="check" :checked="isSelected('file', file.id)" :aria-label="`Pilih ${file.original_name}`" @change="toggleSelect('file', file.id)">
                </td>
                <td>
                  <div class="flex items-center gap-3 min-w-0">
                    <FileTypeIcon :name="file.original_name" size="sm" />
                    <span class="font-medium truncate" :title="file.original_name">{{ file.original_name }}</span>
                  </div>
                </td>
                <td class="text-secondary hidden md:table-cell">{{ file.formatted_size }}</td>
                <td class="text-secondary hidden md:table-cell">{{ formatDateTime(file.created_at) }}</td>
                <td>
                  <div class="row-actions">
                    <button class="btn-icon" title="Bagikan" @click.stop="openShare(file, 'file')">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                    </button>
                    <button class="btn-icon" title="Unduh" @click.stop="downloadFile(file)">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    </button>
                    <button class="btn-icon danger" title="Hapus" @click.stop="deleteFile(file)">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
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

    <!-- Download options modal -->
    <DownloadOptionsModal
      :visible="downloadModalVisible"
      :file-count="selectedFiles.length"
      :folder-count="selectedFolders.length"
      :default-name="archiveName"
      @close="downloadModalVisible = false"
      @confirm="confirmDownload"
    />

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
import { saveBlobResponse, blobErrorMessage } from '../composables/fileTypes';
import { addToast } from '../components/ui/Toast.vue';
import Modal from '../components/ui/Modal.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import FilePreviewModal from '../components/ui/FilePreviewModal.vue';
import ShareModal from '../components/ui/ShareModal.vue';
import FileTypeIcon from '../components/ui/FileTypeIcon.vue';
import DownloadOptionsModal from '../components/ui/DownloadOptionsModal.vue';

const route = useRoute();
const router = useRouter();

const VIEW_MODE_KEY = 'bmclouds_drive_view';
const readViewMode = () => {
  try { return localStorage.getItem(VIEW_MODE_KEY) === 'list' ? 'list' : 'grid'; } catch { return 'grid'; }
};

const folderId = ref(route.params.folderId ? Number(route.params.folderId) : null);
const viewMode = ref(readViewMode()); // 'grid' | 'list'
const setViewMode = (mode) => {
  viewMode.value = mode;
  try { localStorage.setItem(VIEW_MODE_KEY, mode); } catch { /* ignore */ }
};

const folders = ref([]);
const files = ref([]);
const breadcrumbs = ref([]);
const loading = ref(false);

const totalItems = computed(() => folders.value.length + files.value.length);
const currentFolderName = computed(() => breadcrumbs.value.length ? breadcrumbs.value[breadcrumbs.value.length - 1].name : 'Drive Saya');
const summaryText = computed(() => {
  const parts = [];
  if (folders.value.length) parts.push(`${folders.value.length} folder`);
  if (files.value.length) parts.push(`${files.value.length} file`);
  return parts.length ? parts.join(' · ') : 'Kosong';
});

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

// ----- Selection -----
// Keys look like "file:12" / "folder:3".
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
const selectedCount = computed(() => selected.value.size);
const allSelected = computed(() => totalItems.value > 0 && selectedCount.value === totalItems.value);
const selectedFiles = computed(() => files.value.filter((f) => isSelected('file', f.id)));
const selectedFolders = computed(() => folders.value.filter((f) => isSelected('folder', f.id)));
const selectedSizeText = computed(() => {
  const bytes = selectedFiles.value.reduce((sum, f) => sum + (Number(f.size) || 0), 0);
  if (!bytes) return '';
  const units = ['B', 'KB', 'MB', 'GB'];
  let i = 0; let v = bytes;
  while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
  return `${v.toFixed(i ? 1 : 0)} ${units[i]}${selectedFolders.value.length ? ' + isi folder' : ''}`;
});

// Click on a card: open it, or toggle selection when already selecting / Ctrl-click.
const onItemClick = (event, type, item) => {
  if (selectedCount.value > 0 || event.ctrlKey || event.metaKey) {
    toggleSelect(type, item.id);
    return;
  }
  if (type === 'folder') navigateToFolder(item.id);
  else openPreview(item);
};

const onGlobalKeydown = (event) => {
  if (event.key === 'Escape' && selectedCount.value && !downloadModalVisible.value) clearSelection();
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
  clearSelection();
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
  document.addEventListener('keydown', onGlobalKeydown);
});
onUnmounted(() => document.removeEventListener('keydown', onGlobalKeydown));

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
const UPLOAD_TIMEOUT_MS = 10 * 60 * 1000;
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
  if (error.code === 'ECONNABORTED') return 'Server tidak merespons dalam 10 menit. Periksa MySQL lalu coba lagi.';

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
    if (isSelected('folder', folder.id)) toggleSelect('folder', folder.id);
    addToast({ type: 'success', title: 'Folder dihapus', message: `"${folder.name}" dipindahkan ke sampah.` });
  } catch (error) {
    addToast({ type: 'error', title: 'Gagal', message: 'Tidak bisa menghapus folder.' });
  }
};

// ----- File actions -----
const downloadFile = async (file) => {
  try {
    const response = await api.get(`/files/${file.id}/download`, { responseType: 'blob' });
    saveBlobResponse(response, file.original_name);
    return true;
  } catch (error) {
    addToast({ type: 'error', title: 'Gagal mengunduh', message: await blobErrorMessage(error, `Tidak bisa mengunduh ${file.original_name}.`) });
    return false;
  }
};

const deleteFile = async (file) => {
  if (!confirm(`Hapus "${file.original_name}"? File akan dipindahkan ke sampah.`)) return;
  try {
    await api.delete(`/files/${file.id}`);
    files.value = files.value.filter(f => f.id !== file.id);
    if (isSelected('file', file.id)) toggleSelect('file', file.id);
    addToast({ type: 'success', title: 'File dihapus', message: `"${file.original_name}" dipindahkan ke sampah.` });
  } catch (error) {
    addToast({ type: 'error', title: 'Gagal', message: 'Tidak bisa menghapus file.' });
  }
};

// ----- Bulk download (single files or one ZIP archive) -----
const downloadModalVisible = ref(false);
const archiveName = ref('');
const downloading = ref(false);
const downloadProgressText = ref('');

const defaultArchiveName = () => {
  const d = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  const base = selectedFolders.value.length === 1 && selectedFiles.value.length === 0
    ? selectedFolders.value[0].name
    : (breadcrumbs.value.length ? currentFolderName.value : 'BM-Cloud');
  return `${base} ${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
};

const startDownload = () => {
  // One plain file: no need to ask.
  if (selectedFiles.value.length === 1 && selectedFolders.value.length === 0) {
    runSingleDownloads();
    return;
  }
  archiveName.value = defaultArchiveName();
  downloadModalVisible.value = true;
};

const confirmDownload = ({ mode, name }) => {
  downloadModalVisible.value = false;
  archiveName.value = name;
  if (mode === 'zip') runArchiveDownload();
  else runSingleDownloads();
};

const runSingleDownloads = async () => {
  const list = [...selectedFiles.value];
  downloading.value = true;
  let ok = 0;
  for (const [i, file] of list.entries()) {
    downloadProgressText.value = `Mengunduh ${i + 1}/${list.length}`;
    if (await downloadFile(file)) ok++;
    // Small gap so the browser does not drop rapid consecutive downloads.
    if (i < list.length - 1) await new Promise((r) => setTimeout(r, 400));
  }
  downloading.value = false;
  if (list.length > 1) {
    addToast({ type: ok === list.length ? 'success' : 'warning', title: 'Unduhan selesai', message: `${ok} dari ${list.length} file berhasil diunduh.` });
  }
  if (ok === list.length) clearSelection();
};

const runArchiveDownload = async () => {
  downloading.value = true;
  downloadProgressText.value = 'Menyiapkan ZIP…';
  try {
    const response = await api.post('/files/download-archive', {
      file_ids: selectedFiles.value.map((f) => f.id),
      folder_ids: selectedFolders.value.map((f) => f.id),
      name: archiveName.value.trim() || undefined,
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
    const filename = saveBlobResponse(response, `${archiveName.value.trim() || 'BM-Cloud'}.zip`);
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

// ----- Bulk delete -----
const deleteSelected = async () => {
  const fileList = [...selectedFiles.value];
  const folderList = [...selectedFolders.value];
  const total = fileList.length + folderList.length;
  if (!confirm(`Hapus ${total} item terpilih? Item akan dipindahkan ke sampah.`)) return;

  let ok = 0;
  for (const folder of folderList) {
    try {
      await api.delete(`/folders/${folder.id}`);
      folders.value = folders.value.filter((f) => f.id !== folder.id);
      ok++;
    } catch { /* reported below */ }
  }
  for (const file of fileList) {
    try {
      await api.delete(`/files/${file.id}`);
      files.value = files.value.filter((f) => f.id !== file.id);
      ok++;
    } catch { /* reported below */ }
  }
  clearSelection();
  addToast({
    type: ok === total ? 'success' : 'warning',
    title: ok === total ? 'Item dihapus' : 'Sebagian gagal dihapus',
    message: `${ok} dari ${total} item dipindahkan ke sampah.`,
  });
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
.drive-page { min-height: 100%; position: relative; }

/* Breadcrumb above the page title */
.crumbs { display: flex; align-items: center; gap: .25rem; margin-bottom: .35rem; overflow-x: auto; scrollbar-width: none; }
.crumbs::-webkit-scrollbar { display: none; }
.crumb {
  display: inline-flex; align-items: center; gap: .35rem; flex-shrink: 0;
  max-width: 14rem; padding: .15rem .35rem; border: 0; border-radius: 6px; background: none;
  color: var(--text-muted); font-size: .8125rem; font-weight: 500;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.crumb:hover { color: var(--accent-primary); background: var(--accent-tint); }
.crumb.is-current { color: var(--text-secondary); }
.crumb-sep { width: 14px; height: 14px; flex-shrink: 0; color: #b8c2cf; }

/* Toolbar */
.drive-toolbar {
  position: sticky; top: -1.5rem; z-index: 5;
  display: flex; align-items: center; gap: .75rem; min-height: 52px;
  padding: .45rem .6rem .45rem .9rem;
  border: 1px solid var(--separator); border-radius: 12px;
  background: var(--bg-secondary); box-shadow: var(--shadow-card);
  transition: background .15s ease, border-color .15s ease;
}
.drive-toolbar.is-selecting { background: var(--accent-tint); border-color: #bfd3fb; }
.select-all { display: inline-flex; align-items: center; gap: .55rem; font-size: .8125rem; font-weight: 500; color: var(--text-secondary); cursor: pointer; user-select: none; }
.toolbar-meta { font-size: .8125rem; color: var(--text-muted); white-space: nowrap; }
.toolbar-spacer { flex: 1 1 auto; }
.selection-count { font-size: .875rem; font-weight: 600; color: #1e40af; white-space: nowrap; }
.sel-danger { color: var(--accent-danger); }
.sel-danger:hover:not(:disabled) { background: #fdecec; border-color: #f5b5b5; }

/* Upload panel */
.upload-panel { display: flex; flex-direction: column; gap: .75rem; padding: .9rem 1rem; }
.progress-bar.is-error { background: var(--accent-danger); }
.progress-bar.is-done { background: var(--accent-success); }

/* Content area */
.drive-content { position: relative; display: flex; flex-direction: column; gap: 1.5rem; flex: 1; }
.loading-state { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .6rem; min-height: 280px; }
.drop-overlay {
  position: absolute; inset: 0; z-index: 20; min-height: 240px;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .6rem;
  border: 2px dashed var(--accent-primary); border-radius: 14px;
  background: rgba(234, 241, 255, .92); color: var(--accent-primary);
  pointer-events: none;
}
.drop-overlay p { color: #1e40af; font-size: .9rem; }

/* Folder tiles */
.folder-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: .75rem; }
.folder-tile {
  position: relative; display: flex; align-items: center; gap: .7rem;
  min-height: 56px; padding: .65rem .75rem;
  border: 1px solid var(--separator); border-radius: 12px; background: var(--bg-secondary);
  box-shadow: var(--shadow-card); cursor: pointer; user-select: none;
  transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
}
.folder-tile:hover { border-color: #cfd7e2; box-shadow: var(--shadow-hover); }
.folder-glyph { width: 22px; height: 22px; flex-shrink: 0; color: #f5b70a; }
.folder-name { flex: 1; min-width: 0; font-size: .875rem; font-weight: 550; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* File cards */
.file-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(176px, 1fr)); gap: .9rem; }
.file-card {
  position: relative; display: flex; flex-direction: column; overflow: hidden;
  border: 1px solid var(--separator); border-radius: 14px; background: var(--bg-secondary);
  box-shadow: var(--shadow-card); cursor: pointer; user-select: none;
  transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
}
.file-card:hover { border-color: #cfd7e2; box-shadow: var(--shadow-hover); transform: translateY(-1px); }
.file-thumb {
  position: relative; display: flex; align-items: center; justify-content: center;
  height: 124px; background: linear-gradient(180deg, #f8fafc, #f1f4f8);
  border-bottom: 1px solid var(--separator);
}
.file-info { display: flex; flex-direction: column; gap: .2rem; padding: .7rem .8rem .8rem; min-width: 0; }
.file-name { font-size: .8125rem; font-weight: 600; color: var(--text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.file-meta { font-size: .75rem; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Selection state */
.folder-tile.is-selected, .file-card.is-selected { border-color: var(--accent-primary); box-shadow: 0 0 0 1px var(--accent-primary); background: #f5f9ff; }
.file-card.is-selected .file-thumb { background: var(--accent-tint); }
.tile-check { opacity: 0; transition: opacity .12s ease; }
.file-thumb .tile-check { position: absolute; top: .6rem; left: .6rem; }
.folder-tile:hover .tile-check, .file-card:hover .tile-check,
.tile-check:checked, .tile-check:focus-visible,
.drive-page:has(.drive-toolbar.is-selecting) .tile-check { opacity: 1; }
/* Folder checkbox takes the icon's place until the tile is hovered/selected. */
.folder-tile .tile-check { position: absolute; left: .75rem; }
.folder-tile:hover .folder-glyph, .folder-tile.is-selected .folder-glyph,
.drive-page:has(.drive-toolbar.is-selecting) .folder-glyph { opacity: 0; }

/* Hover actions float over the card, so they never squeeze the name. */
.tile-actions {
  display: flex; gap: .2rem; opacity: 0; pointer-events: none;
  transition: opacity .12s ease;
}
.folder-tile .tile-actions {
  position: absolute; right: .4rem; top: 50%; transform: translateY(-50%);
  padding-left: 1.25rem;
  background: linear-gradient(90deg, rgba(255,255,255,0), var(--bg-secondary) 1.1rem);
}
.folder-tile.is-selected .tile-actions { background: linear-gradient(90deg, rgba(245,249,255,0), #f5f9ff 1.1rem); }
.file-thumb .tile-actions { position: absolute; top: .5rem; right: .5rem; }
.file-thumb .tile-actions .btn-icon { width: 30px; height: 30px; border-radius: 8px; background: #fff; box-shadow: var(--shadow-card); }
.folder-tile:hover .tile-actions, .file-card:hover .tile-actions,
.tile-actions:focus-within { opacity: 1; pointer-events: auto; }
.drive-page:has(.drive-toolbar.is-selecting) .tile-actions { opacity: 0; pointer-events: none; }
.btn-icon.danger:hover { color: var(--accent-danger); background: #fdecec; }

/* List view */
.drive-table tbody tr.is-selected { background: #f5f9ff; }
.drive-table tbody tr.is-selected:hover { background: var(--accent-tint); }
.folder-icon-sm { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: #fff7e0; color: #f5b70a; flex-shrink: 0; }
.row-actions { display: flex; justify-content: flex-end; gap: .15rem; opacity: 0; transition: opacity .12s ease; }
.drive-table tbody tr:hover .row-actions, .row-actions:focus-within { opacity: 1; }

/* Upload bar animations */
.upload-bar-active { position: relative; overflow: hidden; }
.upload-bar-active::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .35), transparent);
  animation: upload-shimmer 1.2s linear infinite;
}
@keyframes upload-shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
.upload-bar-indeterminate { width: 40% !important; animation: upload-indeterminate 1.1s ease-in-out infinite; }
@keyframes upload-indeterminate { 0% { transform: translateX(-100%); } 100% { transform: translateX(250%); } }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@media (max-width: 767px) {
  .drive-toolbar { top: -1rem; padding-left: .7rem; gap: .5rem; }
  .folder-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; }
  .file-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; }
  .file-thumb { height: 100px; }
}

/* Touch devices have no hover: always show checkboxes and actions. */
@media (hover: none) {
  .tile-check, .row-actions { opacity: 1; }
  .folder-tile .folder-glyph { display: none; }
  .folder-tile .tile-check { position: static; }
  .file-thumb .tile-actions { opacity: 1; pointer-events: auto; }
  .folder-tile .tile-actions { position: static; transform: none; opacity: 1; pointer-events: auto; background: none; padding: 0; }
}

@media (prefers-reduced-motion: reduce) {
  .upload-bar-active::after, .upload-bar-indeterminate { animation: none; }
}
</style>
