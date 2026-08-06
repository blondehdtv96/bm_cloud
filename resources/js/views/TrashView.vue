<template>
  <div class="trash-page">
    <div class="page-header">
      <div>
        <h1>
          <svg class="w-5 h-5 text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          Sampah
        </h1>
        <p>{{ subtitle }}</p>
      </div>
      <button
        v-if="items.length > 0"
        class="btn btn-danger"
        :disabled="busy"
        @click="emptyTrash"
      >
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        Kosongkan Sampah
      </button>
    </div>

    <div class="alert-warning">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
      <span>Item di sampah akan otomatis terhapus setelah 30 hari.</span>
    </div>

    <div class="glass-card panel">
      <div v-if="loading" class="panel-state">
        <svg class="w-8 h-8 animate-spin" style="color: var(--accent-primary)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
        <span class="text-sm text-secondary">Memuat sampah...</span>
      </div>

      <div v-else-if="items.length === 0" class="panel-state">
        <EmptyState
          icon="trash"
          title="Sampah kosong"
          description="Item yang Anda hapus akan muncul di sini sebelum dihapus permanen."
        />
      </div>

      <div v-else class="panel-scroll animate-fade-in">
        <table class="table-modern">
          <thead>
            <tr>
              <th>Nama</th>
              <th class="hidden sm:table-cell">Tanggal Dihapus</th>
              <th class="col-action">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="`${item.type}-${item.id}`">
              <td>
                <div class="item-cell">
                  <span class="item-icon" :class="item.type === 'folder' ? 'is-folder' : 'is-file'">
                    <svg v-if="item.type === 'folder'" viewBox="0 0 24 24" fill="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                  </span>
                  <span class="item-name" :title="item.name">{{ item.name }}</span>
                </div>
              </td>
              <td class="text-secondary hidden sm:table-cell">{{ formatDate(item.deleted_at) }}</td>
              <td class="col-action">
                <div class="row-actions">
                  <button class="btn-icon action-restore" title="Pulihkan" :disabled="busy" @click="restore(item)">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 14 12 11 15 14"></polyline><path d="M12 11v8"></path><path d="M20 21v-2a4 4 0 0 0-4-4h-8a4 4 0 0 0-4 4v2"></path></svg>
                  </button>
                  <button class="btn-icon action-delete" title="Hapus Permanen" :disabled="busy" @click="destroy(item)">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                  </button>
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
const busy = ref(false);
const items = ref([]);

const subtitle = computed(() => {
  if (loading.value) return 'Memuat item...';
  if (items.value.length === 0) return 'Tidak ada item di sampah.';
  return `${items.value.length} item menunggu dihapus permanen.`;
});

const formatDate = (value) => {
  if (!value) return '--';
  return new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const fetchTrash = async () => {
  loading.value = true;
  try {
    const { data } = await api.get('/trash');
    const folders = (data.folders || []).map((folder) => ({
      id: folder.id,
      type: 'folder',
      name: folder.name,
      deleted_at: folder.deleted_at,
    }));
    const files = (data.files || []).map((file) => ({
      id: file.id,
      type: 'file',
      name: file.original_name,
      deleted_at: file.deleted_at,
    }));
    items.value = [...folders, ...files].sort(
      (a, b) => new Date(b.deleted_at) - new Date(a.deleted_at)
    );
  } catch (error) {
    console.error('Failed to fetch trash', error);
    items.value = [];
  } finally {
    loading.value = false;
  }
};

const restore = async (item) => {
  busy.value = true;
  try {
    await api.post(`/trash/restore/${item.type}/${item.id}`);
    items.value = items.value.filter((i) => !(i.id === item.id && i.type === item.type));
  } catch (error) {
    console.error('Failed to restore item', error);
  } finally {
    busy.value = false;
  }
};

const destroy = async (item) => {
  if (!window.confirm(`Hapus permanen "${item.name}"? Tindakan ini tidak bisa dibatalkan.`)) return;
  busy.value = true;
  try {
    await api.delete(`/trash/${item.type}/${item.id}`);
    items.value = items.value.filter((i) => !(i.id === item.id && i.type === item.type));
  } catch (error) {
    console.error('Failed to delete item', error);
  } finally {
    busy.value = false;
  }
};

const emptyTrash = async () => {
  if (!window.confirm('Kosongkan sampah? Semua item akan dihapus permanen.')) return;
  busy.value = true;
  try {
    await api.delete('/trash/empty');
    items.value = [];
  } catch (error) {
    console.error('Failed to empty trash', error);
  } finally {
    busy.value = false;
  }
};

onMounted(fetchTrash);
</script>

<style scoped>
.trash-page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  min-height: 100%;
}

/* page-header punya margin-bottom global; di sini spacing diatur oleh gap. */
.trash-page .page-header {
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
  background: var(--fill-secondary);
  color: var(--text-muted);
}
.item-icon svg { width: 17px; height: 17px; }
.item-icon.is-folder { color: var(--accent-warning); }

.item-name {
  min-width: 0;
  font-weight: 500;
  color: var(--text-secondary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.col-action {
  width: 1%;
  white-space: nowrap;
  text-align: right;
}

.row-actions {
  display: flex;
  justify-content: flex-end;
  gap: .15rem;
}

.action-restore:hover:not(:disabled) { color: var(--accent-success); background: rgba(52, 199, 89, .12); }
.action-delete:hover:not(:disabled) { color: var(--accent-danger); background: rgba(255, 59, 48, .10); }
</style>
