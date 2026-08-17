<template>
  <Modal :visible="visible" title="Bagikan" size="md" @close="close">
    <div v-if="item" class="share-target">
      <span class="share-target-icon" :class="isFolder ? 'is-folder' : 'is-file'">
        <svg v-if="isFolder" viewBox="0 0 24 24" fill="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
      </span>
      <span class="share-target-name" :title="itemName">{{ itemName }}</span>
    </div>

    <!-- Cari & tambah penerima -->
    <div class="form-group">
      <label class="form-label">Bagikan kepada</label>
      <div class="recipient-search">
        <input
          v-model="search"
          type="text"
          class="form-control"
          placeholder="Cari nama atau email..."
          @input="onSearchInput"
          @focus="showResults = true"
        >
        <select v-model="newPermission" class="form-control permission-select" aria-label="Tingkat akses">
          <option value="view">Lihat</option>
          <option value="edit">Edit</option>
        </select>
      </div>

      <div v-if="showResults && (searching || results.length)" class="recipient-results">
        <div v-if="searching" class="recipient-hint">Mencari...</div>
        <button
          v-for="user in results"
          :key="user.id"
          type="button"
          class="recipient-option"
          @click="addRecipient(user)"
        >
          <span class="avatar-sm">{{ initials(user.name) }}</span>
          <span class="recipient-text">
            <span class="recipient-name">{{ user.name }}</span>
            <span class="recipient-email">{{ user.email }}</span>
          </span>
        </button>
        <div v-if="!searching && results.length === 0" class="recipient-hint">Tidak ada pengguna ditemukan.</div>
      </div>
    </div>

    <!-- Orang yang sudah punya akses -->
    <div class="shared-section">
      <div class="shared-section-head">
        <span>Punya akses</span>
        <svg v-if="loading" class="w-4 h-4 animate-spin" style="color: var(--accent-primary)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
      </div>

      <p v-if="!loading && shares.length === 0" class="shared-empty">Belum dibagikan ke siapa pun.</p>

      <ul v-else class="shared-list">
        <li v-for="share in shares" :key="share.id" class="shared-row">
          <span class="avatar-sm">{{ initials(share.shared_to?.name) }}</span>
          <span class="shared-text">
            <span class="shared-name">{{ share.shared_to?.name || 'Pengguna' }}</span>
            <span class="shared-email">{{ share.shared_to?.email }}</span>
          </span>
          <select
            class="form-control form-control-sm perm-select"
            :value="share.permission"
            :disabled="busyId === share.id"
            @change="changePermission(share, $event.target.value)"
          >
            <option value="view">Lihat</option>
            <option value="edit">Edit</option>
          </select>
          <button
            type="button"
            class="btn-icon shared-remove"
            title="Cabut akses"
            :disabled="busyId === share.id"
            @click="revoke(share)"
          >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </li>
      </ul>
    </div>

    <template #footer>
      <button class="btn btn-secondary" @click="close">Tutup</button>
    </template>
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { api } from '../../composables/useApi';
import { addToast } from './Toast.vue';
import Modal from './Modal.vue';

const props = defineProps({
  visible: { type: Boolean, default: false },
  /** { id, type: 'file' | 'folder', name } */
  item: { type: Object, default: null },
});
const emit = defineEmits(['close', 'shared', 'revoked']);

const shares = ref([]);
const loading = ref(false);
const busyId = ref(null);

const search = ref('');
const results = ref([]);
const searching = ref(false);
const showResults = ref(false);
const newPermission = ref('view');
let searchTimeout = null;

const isFolder = computed(() => props.item?.type === 'folder');
const itemName = computed(() => props.item?.name || '');

const initials = (name) =>
  (name || '?')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0].toUpperCase())
    .join('');

const fetchShares = async () => {
  if (!props.item) return;
  loading.value = true;
  try {
    const { data } = await api.get(`/shares/item/${props.item.type}/${props.item.id}`);
    shares.value = data.shares || [];
  } catch (error) {
    console.error('Failed to fetch item shares', error);
    addToast({ type: 'error', title: 'Gagal memuat', message: 'Tidak bisa memuat daftar akses.' });
  } finally {
    loading.value = false;
  }
};

watch(
  () => props.visible,
  (visible) => {
    if (visible) {
      search.value = '';
      results.value = [];
      showResults.value = false;
      newPermission.value = 'view';
      fetchShares();
    }
  }
);

const onSearchInput = () => {
  clearTimeout(searchTimeout);
  showResults.value = true;
  const q = search.value.trim();

  searchTimeout = setTimeout(async () => {
    searching.value = true;
    try {
      const { data } = await api.get('/shares/recipients', { params: { q } });
      // Sembunyikan yang sudah punya akses supaya tidak dobel di daftar hasil.
      const alreadySharedIds = new Set(shares.value.map((s) => s.shared_to?.id));
      results.value = data.filter((u) => !alreadySharedIds.has(u.id));
    } catch (error) {
      results.value = [];
    } finally {
      searching.value = false;
    }
  }, 300);
};

const addRecipient = async (user) => {
  if (!props.item) return;
  showResults.value = false;
  search.value = '';
  results.value = [];

  try {
    const { data } = await api.post('/shares', {
      shareable_type: props.item.type,
      shareable_id: props.item.id,
      shared_to: user.id,
      permission: newPermission.value,
    });
    shares.value = [data, ...shares.value.filter((s) => s.shared_to?.id !== user.id)];
    addToast({ type: 'success', title: 'Berhasil dibagikan', message: `Dibagikan ke ${user.name}.` });
    emit('shared', data);
  } catch (error) {
    addToast({
      type: 'error',
      title: 'Gagal membagikan',
      message: error.response?.data?.message || 'Tidak bisa membagikan item ini.',
    });
  }
};

const changePermission = async (share, permission) => {
  busyId.value = share.id;
  try {
    const { data } = await api.put(`/shares/${share.id}`, { permission });
    const idx = shares.value.findIndex((s) => s.id === share.id);
    if (idx > -1) shares.value[idx] = data;
  } catch (error) {
    addToast({ type: 'error', title: 'Gagal', message: 'Tidak bisa mengubah tingkat akses.' });
  } finally {
    busyId.value = null;
  }
};

const revoke = async (share) => {
  if (!window.confirm(`Cabut akses ${share.shared_to?.name || 'pengguna ini'}?`)) return;
  busyId.value = share.id;
  try {
    await api.delete(`/shares/${share.id}`);
    shares.value = shares.value.filter((s) => s.id !== share.id);
    emit('revoked', share);
  } catch (error) {
    addToast({ type: 'error', title: 'Gagal', message: 'Tidak bisa mencabut akses.' });
  } finally {
    busyId.value = null;
  }
};

const close = () => emit('close');
</script>

<style scoped>
.share-target {
  display: flex;
  align-items: center;
  gap: .6rem;
  margin-bottom: 1rem;
  padding: .6rem .7rem;
  border-radius: 10px;
  background: var(--fill-tertiary);
}
.share-target-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  flex-shrink: 0;
  border-radius: 8px;
  background: rgba(26, 115, 232, .10);
  color: var(--accent-primary);
}
.share-target-icon svg { width: 16px; height: 16px; }
.share-target-icon.is-folder { background: rgba(249, 171, 0, .13); color: var(--accent-warning); }
.share-target-name { font-weight: 600; font-size: .875rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.recipient-search { position: relative; display: flex; gap: .5rem; }
.recipient-search .form-control:first-child { flex: 1; }
.permission-select { flex-shrink: 0; width: 6.5rem; }

/*
  Sengaja BUKAN overlay position:absolute: .modal-body induk memakai
  overflow-y:auto, yang membuat overflow-x ikut jadi auto (aturan CSS
  overflow), sehingga dropdown absolute bisa terpotong/tidak bisa diklik saat
  modal-body sedang di-scroll. Ditulis sebagai blok inline yang mendorong
  konten di bawahnya supaya selalu utuh terlihat dan bisa diklik.
*/
.recipient-results {
  position: relative;
  z-index: 5;
  margin-top: .4rem;
  max-height: 14rem;
  overflow-y: auto;
  padding: .3rem;
  border: 1px solid var(--separator);
  border-radius: 12px;
  background: var(--bg-secondary);
  box-shadow: var(--shadow-card);
}
.recipient-hint { padding: .6rem .5rem; font-size: .8rem; color: var(--text-muted); text-align: center; }
.recipient-option {
  display: flex;
  align-items: center;
  gap: .6rem;
  width: 100%;
  padding: .5rem .55rem;
  border: 0;
  border-radius: 9px;
  background: none;
  cursor: pointer;
  text-align: left;
  transition: background .15s ease;
}
.recipient-option:hover { background: var(--fill-tertiary); }
.recipient-text { display: flex; flex-direction: column; min-width: 0; }
.recipient-name { font-size: .82rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.recipient-email { font-size: .75rem; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.avatar-sm {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  flex-shrink: 0;
  border-radius: 50%;
  background: var(--accent-primary);
  color: #fff;
  font-size: .68rem;
  font-weight: 700;
}

.shared-section { margin-top: 1.35rem; }
.shared-section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: .5rem;
  font-size: .78rem;
  font-weight: 700;
  letter-spacing: .02em;
  text-transform: uppercase;
  color: var(--text-muted);
}
.shared-empty { padding: .75rem 0; font-size: .82rem; color: var(--text-muted); }

.shared-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .3rem; }
.shared-row { display: flex; align-items: center; gap: .6rem; padding: .4rem; border-radius: 10px; }
.shared-row:hover { background: var(--fill-tertiary); }
.shared-text { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.shared-name { font-size: .82rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.shared-email { font-size: .74rem; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.perm-select { flex-shrink: 0; width: 6rem; min-height: 34px; padding: .35rem .5rem; }
.shared-remove:hover:not(:disabled) { color: var(--accent-danger); background: rgba(217, 48, 37, .10); }
</style>
