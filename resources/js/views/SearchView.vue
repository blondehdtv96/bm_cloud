<template>
  <div class="w-full h-full flex flex-col gap-4">
    <!-- Header / Search Bar -->
    <div class="glass-card p-4 animate-slide-down">
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <div class="relative flex-1">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input 
            v-model="searchQuery" 
            @input="debounceSearch"
            type="text" 
            class="form-control pl-9 pr-9 bg-black/20 w-full" 
            placeholder="Cari file dan folder..."
            autofocus
          >
          <button v-if="searchQuery" @click="clearSearch" class="absolute right-0 top-0 h-full px-3 flex items-center text-secondary hover:text-primary">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <select v-model="filterType" @change="performSearch" class="form-control bg-black/20 sm:w-44">
          <option value="all">Semua Tipe</option>
          <option value="folder">Folder</option>
          <option value="file">File</option>
        </select>
      </div>
    </div>

    <!-- Content Area -->
    <div class="flex-1 relative glass-card p-4 overflow-y-auto">
      <!-- Loading State -->
      <div v-if="loading" class="w-full h-full flex items-center justify-center">
        <div class="animate-pulse flex flex-col items-center gap-2">
           <svg class="w-8 h-8 text-primary animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
           <span class="text-sm text-secondary">Mencari...</span>
        </div>
      </div>
      
      <!-- Initial Empty State -->
      <div v-else-if="!hasSearched" class="h-full flex items-center justify-center">
        <EmptyState 
          icon="search"
          title="Cari di drive Anda" 
          description="Temukan file dan folder Anda dengan cepat dengan mengetik kata kunci di atas." 
        />
      </div>

      <!-- No Results State -->
      <div v-else-if="results.length === 0" class="h-full flex items-center justify-center">
        <EmptyState 
          icon="search"
          title="Tidak ada hasil" 
          :description="`Tidak ditemukan hasil yang cocok dengan '${searchQuery}'`" 
        />
      </div>

      <!-- Results View -->
      <div v-else class="animate-fade-in w-full">
        <h3 class="text-sm font-semibold text-secondary mb-3">Hasil Pencarian ({{ results.length }})</h3>
        
        <table class="table-modern">
          <thead>
            <tr>
              <th>Nama</th>
              <th class="hidden md:table-cell">Tipe</th>
              <th class="hidden md:table-cell">Lokasi</th>
              <th class="hidden md:table-cell">Diubah</th>
              <th class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in results" :key="item.id" class="group cursor-pointer" @click="navigateToItem(item)">
              <td>
                <div class="flex items-center gap-3">
                  <svg v-if="item.type === 'folder'" class="w-5 h-5 text-yellow-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                  <svg v-else class="w-5 h-5 text-indigo-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                  <span class="font-medium truncate max-w-[150px] md:max-w-md" v-html="highlight(item.name)"></span>
                </div>
              </td>
              <td class="text-secondary hidden md:table-cell capitalize">{{ item.type }}</td>
              <td class="text-secondary hidden md:table-cell truncate max-w-[150px]">{{ item.path || '/' }}</td>
              <td class="text-secondary hidden md:table-cell">{{ item.updated_at || 'Baru saja' }}</td>
              <td class="text-right">
                <button class="btn-icon text-secondary hover:text-primary opacity-0 group-hover:opacity-100 transition-opacity" @click.stop="openMenu(item)">
                  <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../composables/useApi';
import EmptyState from '../components/ui/EmptyState.vue';

const router = useRouter();
const searchQuery = ref('');
const filterType = ref('all');
const loading = ref(false);
const hasSearched = ref(false);
const results = ref([]);
let debounceTimer = null;

const clearSearch = () => {
  searchQuery.value = '';
  hasSearched.value = false;
  results.value = [];
};

const debounceSearch = () => {
  clearTimeout(debounceTimer);
  
  if (!searchQuery.value || searchQuery.value.length < 2) {
    hasSearched.value = false;
    results.value = [];
    return;
  }
  
  debounceTimer = setTimeout(() => {
    performSearch();
  }, 500); // 500ms debounce
};

const performSearch = async () => {
  if (!searchQuery.value || searchQuery.value.length < 2) return;
  
  loading.value = true;
  hasSearched.value = true;
  
  try {
    const response = await api.get('/search', {
      params: {
        q: searchQuery.value,
        type: filterType.value === 'all' ? null : filterType.value
      }
    });
    
    results.value = response.data.data || response.data || [];
  } catch (error) {
    console.error("Search failed", error);
    results.value = [];
  } finally {
    loading.value = false;
  }
};

const highlight = (text) => {
  if (!searchQuery.value) return text;
  const regex = new RegExp(`(${searchQuery.value})`, 'gi');
  return text.replace(regex, '<span class="text-primary font-bold bg-primary/20 rounded px-1">$1</span>');
};

const navigateToItem = (item) => {
  if (item.type === 'folder') {
    router.push(`/drive/${item.id}`);
  } else {
    // Open file preview or download
    console.log('Open file:', item.id);
  }
};

const openMenu = (item) => {
  console.log('Open menu for:', item.id);
};
</script>
