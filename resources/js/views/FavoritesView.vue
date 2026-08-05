<template>
  <div class="w-full h-full flex flex-col gap-4">
    <!-- Header / Toolbar -->
    <div class="glass-card p-4 flex flex-wrap items-center justify-between gap-3 animate-slide-down">
      <div class="flex items-center gap-2">
        <svg class="w-5 h-5 text-yellow-400" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
          <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
        </svg>
        <h1 class="text-xl font-bold">Favorit</h1>
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

    <!-- Content Area -->
    <div class="flex-1 relative glass-card p-4 overflow-y-auto">
      <div v-if="loading" class="w-full h-full flex items-center justify-center">
        <div class="animate-pulse flex flex-col items-center gap-2">
           <svg class="w-8 h-8 text-primary animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
           <span class="text-sm text-secondary">Memuat favorit...</span>
        </div>
      </div>
      
      <div v-else-if="favorites.length === 0" class="h-full flex items-center justify-center">
        <EmptyState 
          title="Belum ada item favorit" 
          description="Tandai item sebagai favorit agar mudah ditemukan lagi. Klik kanan pada file atau folder lalu pilih 'Tambah ke favorit'." 
        />
      </div>

      <div v-else class="animate-fade-in">
        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <div v-for="item in favorites" :key="item.id" class="p-3 rounded-xl border border-glass-border bg-black/10 hover:bg-black/30 transition-all cursor-pointer group flex flex-col hover:shadow-lg">
            <div class="h-24 rounded-lg bg-black/30 mb-3 flex items-center justify-center group-hover:scale-105 transition-transform relative">
              <button @click.stop="toggleFavorite(item)" class="absolute top-2 right-2 text-yellow-400 opacity-0 group-hover:opacity-100 transition-opacity p-1 bg-black/50 rounded-full hover:bg-black/80">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
              </button>
              
              <svg v-if="item.type === 'folder'" class="w-10 h-10 text-yellow-500" viewBox="0 0 24 24" fill="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
              <svg v-else class="w-10 h-10 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            </div>
            <span class="text-sm font-medium truncate mb-1" :title="item.name">{{ item.name }}</span>
            <span class="text-xs text-secondary">{{ item.type === 'folder' ? 'Folder' : item.size || 'Ukuran tidak diketahui' }}</span>
          </div>
        </div>

        <!-- List View -->
        <table v-else class="table-modern">
          <thead>
            <tr>
              <th>Nama</th>
              <th class="hidden md:table-cell">Tipe</th>
              <th class="hidden md:table-cell">Ukuran</th>
              <th class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in favorites" :key="item.id" class="group">
              <td>
                <div class="flex items-center gap-3">
                  <svg v-if="item.type === 'folder'" class="w-5 h-5 text-yellow-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                  <svg v-else class="w-5 h-5 text-indigo-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                  <span class="font-medium truncate max-w-[200px] md:max-w-md">{{ item.name }}</span>
                </div>
              </td>
              <td class="text-secondary hidden md:table-cell capitalize">{{ item.type }}</td>
              <td class="text-secondary hidden md:table-cell">{{ item.type === 'folder' ? '--' : item.size || '--' }}</td>
              <td class="text-right">
                <div class="flex justify-end gap-1">
                  <button @click.stop="toggleFavorite(item)" class="btn-icon text-yellow-400 opacity-0 group-hover:opacity-100 transition-opacity hover:bg-white/10" title="Hapus dari favorit">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                  </button>
                  <button class="btn-icon text-secondary hover:text-primary opacity-0 group-hover:opacity-100 transition-opacity">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
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
import { ref, onMounted } from 'vue';
import { api } from '../composables/useApi';
import EmptyState from '../components/ui/EmptyState.vue';

const viewMode = ref('grid');
const loading = ref(true);
const favorites = ref([]);

const fetchFavorites = async () => {
  loading.value = true;
  try {
    const response = await api.get('/favorites');
    favorites.value = response.data.data || response.data || [];
  } catch (error) {
    console.error("Failed to fetch favorites", error);
  } finally {
    loading.value = false;
  }
};

const toggleFavorite = async (item) => {
  try {
    await api.post('/favorites/toggle', { 
      type: item.type,
      id: item.id
    });
    // Optimistic UI update
    favorites.value = favorites.value.filter(f => !(f.id === item.id && f.type === item.type));
  } catch (error) {
    console.error("Failed to toggle favorite", error);
  }
};

onMounted(() => {
  fetchFavorites();
});
</script>

<style scoped>
.view-toggle { display: flex; align-items: center; gap: 2px; border: 0; border-radius: 9px; background: var(--fill-secondary); padding: 2px; }
.view-toggle-btn { display: flex; align-items: center; justify-content: center; width: 34px; height: 32px; border-radius: 7px; color: var(--text-secondary); background: none; border: none; cursor: pointer; transition: background .15s ease, color .15s ease, box-shadow .15s ease; }
.view-toggle-btn:hover { color: var(--text-primary); }
.view-toggle-btn.active { background: var(--bg-secondary); color: var(--accent-primary); box-shadow: 0 1px 4px rgba(0,0,0,.10); }
</style>
