<template>
  <div class="h-full flex flex-col gap-6">
    <div class="page-header">
      <div>
        <h1>Manajemen Pengguna</h1>
        <p>Kelola akun, peran, dan kuota penyimpanan setiap pengguna.</p>
      </div>
      <button class="btn btn-primary" @click="openCreateModal">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Tambah Pengguna
      </button>
    </div>

    <div class="glass-card flex-1 overflow-hidden flex flex-col">
      <div class="p-4 border-b border-glass-border flex flex-col sm:flex-row flex-wrap gap-3">
        <div class="relative flex-1 min-w-[200px]">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input v-model="search" @input="debounceSearch" type="text" placeholder="Cari nama atau email..." class="form-control form-control-sm pl-9 bg-black/20 w-full">
        </div>
        <select v-model="roleFilter" @change="fetchUsers" class="form-control form-control-sm bg-black/20 w-full sm:w-44">
          <option value="">Semua Peran</option>
          <option v-for="role in roles" :key="role.id" :value="role.slug">{{ role.name }}</option>
        </select>
      </div>
      
      <div class="overflow-auto flex-1 relative">
        <div v-if="loading" class="absolute inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-10">
          <svg class="w-8 h-8 text-primary animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
        </div>

        <table class="table-modern">
          <thead>
            <tr>
              <th>Pengguna</th>
              <th class="hidden md:table-cell">Peran</th>
              <th class="hidden lg:table-cell">Penyimpanan</th>
              <th class="hidden sm:table-cell">Status</th>
              <th class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in users" :key="u.id">
              <td>
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-indigo-500/15 text-indigo-400 flex items-center justify-center font-bold text-sm flex-shrink-0">{{ initials(u.name) }}</div>
                  <div class="min-w-0">
                    <div class="font-medium text-primary truncate">{{ u.name }}</div>
                    <div class="text-xs text-secondary truncate">{{ u.email }}</div>
                    <div class="flex items-center gap-2 mt-1 md:hidden">
                      <span class="badge badge-purple">{{ u.roles?.[0]?.name || '—' }}</span>
                      <span class="badge" :class="statusBadgeClass(u.status)">
                        <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(u.status)"></span>
                        {{ statusLabel(u.status) }}
                      </span>
                    </div>
                  </div>
                </div>
              </td>
              <td class="hidden md:table-cell">
                <span class="badge badge-purple">{{ u.roles?.[0]?.name || '—' }}</span>
              </td>
              <td class="hidden lg:table-cell">
                <div class="flex flex-col gap-1 w-28">
                  <span class="text-xs text-secondary">{{ formatSize(u.storage_used) }} / {{ formatSize(u.storage_quota) }}</span>
                  <div class="h-1.5 w-full bg-black/40 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-500 rounded-full" :style="`width: ${storagePercent(u)}%`"></div>
                  </div>
                </div>
              </td>
              <td class="hidden sm:table-cell">
                <span class="badge" :class="statusBadgeClass(u.status)">
                  <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(u.status)"></span>
                  {{ statusLabel(u.status) }}
                </span>
              </td>
              <td class="text-right">
                <div class="flex justify-end gap-1">
                  <button class="btn-icon text-indigo-400 hover:bg-indigo-400/20" title="Edit" @click="openEditModal(u)">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                  </button>
                  <button class="btn-icon text-red-400 hover:bg-red-400/20" title="Hapus" @click="deleteUser(u)">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!loading && users.length === 0">
              <td colspan="5" class="text-center text-secondary py-8">Tidak ada pengguna ditemukan.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="pagination.last_page > 1" class="p-3 border-t border-glass-border flex items-center justify-between text-sm text-secondary">
        <span>Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}</span>
        <div class="flex gap-1">
          <button class="px-3 py-1 border border-glass-border rounded hover:bg-white/5 disabled:opacity-40" :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">Sebelumnya</button>
          <button class="px-3 py-1 border border-glass-border rounded hover:bg-white/5 disabled:opacity-40" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">Selanjutnya</button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Modal :visible="showModal" :title="editingUser ? 'Edit Pengguna' : 'Tambah Pengguna'" size="md" @close="closeModal">
      <form id="user-form" @submit.prevent="saveUser" class="flex flex-col gap-4">
        <div v-if="formError" class="alert-error">
          <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <span>{{ formError }}</span>
        </div>

        <div class="form-group mb-0">
          <label class="form-label">Nama Lengkap</label>
          <input v-model="form.name" type="text" class="form-control" required>
        </div>
        <div class="form-group mb-0">
          <label class="form-label">Email</label>
          <input v-model="form.email" type="email" class="form-control" required>
        </div>
        <div class="form-group mb-0">
          <label class="form-label">{{ editingUser ? 'Kata Sandi Baru (opsional)' : 'Kata Sandi' }}</label>
          <input v-model="form.password" type="password" class="form-control" :placeholder="editingUser ? 'Biarkan kosong jika tidak diubah' : 'Minimal 8 karakter'" :required="!editingUser" minlength="8">
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="form-group mb-0">
            <label class="form-label">Peran</label>
            <select v-model="form.role_id" class="form-control" required>
              <option value="" disabled>Pilih peran</option>
              <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
            </select>
          </div>
          <div class="form-group mb-0">
            <label class="form-label">Status</label>
            <select v-model="form.status" class="form-control">
              <option value="active">Aktif</option>
              <option value="inactive">Nonaktif</option>
              <option value="suspended">Ditangguhkan</option>
            </select>
          </div>
        </div>
        <div class="form-group mb-0">
          <label class="form-label">Kuota Penyimpanan (GB)</label>
          <input v-model.number="quotaGb" type="number" min="1" step="1" class="form-control">
        </div>
      </form>
      <template #footer>
        <button type="button" class="btn btn-secondary" @click="closeModal">Batal</button>
        <button type="submit" form="user-form" class="btn btn-primary" :disabled="saving">
          {{ saving ? 'Menyimpan...' : 'Simpan' }}
        </button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { api } from '../../composables/useApi';
import { addToast } from '../../components/ui/Toast.vue';
import Modal from '../../components/ui/Modal.vue';

const users = ref([]);
const roles = ref([]);
const loading = ref(false);
const search = ref('');
const roleFilter = ref('');
const pagination = ref({ current_page: 1, last_page: 1 });

const showModal = ref(false);
const editingUser = ref(null);
const saving = ref(false);
const formError = ref('');

const form = reactive({
  name: '',
  email: '',
  password: '',
  role_id: '',
  status: 'active',
  storage_quota: 10737418240,
});

const quotaGb = computed({
  get: () => Math.round((form.storage_quota || 0) / 1073741824),
  set: (val) => { form.storage_quota = Math.max(1, val) * 1073741824; },
});

let searchTimeout = null;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => fetchUsers(1), 400);
};

const fetchUsers = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/admin/users', {
      params: { search: search.value || undefined, role: roleFilter.value || undefined, page },
    });
    users.value = response.data.data || [];
    pagination.value = {
      current_page: response.data.current_page || 1,
      last_page: response.data.last_page || 1,
    };
  } catch (error) {
    console.error('Failed to fetch users', error);
    addToast({ type: 'error', title: 'Gagal memuat', message: 'Tidak bisa memuat daftar pengguna.' });
  } finally {
    loading.value = false;
  }
};

const fetchRoles = async () => {
  try {
    const response = await api.get('/admin/roles-list');
    roles.value = response.data;
  } catch (error) {
    console.error('Failed to fetch roles', error);
  }
};

const goToPage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return;
  fetchUsers(page);
};

const resetForm = () => {
  form.name = '';
  form.email = '';
  form.password = '';
  form.role_id = '';
  form.status = 'active';
  form.storage_quota = 10737418240;
};

const openCreateModal = () => {
  editingUser.value = null;
  formError.value = '';
  resetForm();
  showModal.value = true;
};

const openEditModal = (user) => {
  editingUser.value = user;
  formError.value = '';
  form.name = user.name;
  form.email = user.email;
  form.password = '';
  form.role_id = user.roles?.[0]?.id || '';
  form.status = user.status || 'active';
  form.storage_quota = user.storage_quota || 10737418240;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  formError.value = '';
};

const validateForm = () => {
  if (!form.name.trim()) return 'Nama lengkap wajib diisi.';
  if (!form.email.trim()) return 'Email wajib diisi.';
  if (!form.role_id) return 'Peran wajib dipilih.';
  if (!editingUser.value && !form.password) return 'Kata sandi wajib diisi untuk pengguna baru.';
  if (form.password && form.password.length < 8) return 'Kata sandi minimal 8 karakter.';
  return '';
};

const saveUser = async () => {
  const validationMessage = validateForm();
  if (validationMessage) {
    formError.value = validationMessage;
    return;
  }
  formError.value = '';

  saving.value = true;
  try {
    const payload = {
      name: form.name,
      email: form.email,
      role_id: form.role_id,
      status: form.status,
      storage_quota: form.storage_quota,
    };
    if (form.password) payload.password = form.password;

    if (editingUser.value) {
      await api.put(`/admin/users/${editingUser.value.id}`, payload);
      addToast({ type: 'success', title: 'Pengguna diperbarui', message: `Data "${form.name}" berhasil diperbarui.` });
    } else {
      await api.post('/admin/users', payload);
      addToast({ type: 'success', title: 'Pengguna ditambahkan', message: `"${form.name}" berhasil ditambahkan.` });
    }

    closeModal();
    fetchUsers(pagination.value.current_page);
  } catch (error) {
    console.error('Failed to save user', error);
    const message = error.response?.data?.message
      || Object.values(error.response?.data?.errors || {})[0]?.[0]
      || 'Tidak bisa menyimpan data pengguna. Periksa koneksi atau coba lagi.';
    formError.value = message;
    addToast({ type: 'error', title: 'Gagal', message });
  } finally {
    saving.value = false;
  }
};

const deleteUser = async (user) => {
  if (!confirm(`Hapus pengguna "${user.name}"? Tindakan ini tidak bisa dibatalkan.`)) return;
  try {
    await api.delete(`/admin/users/${user.id}`);
    addToast({ type: 'success', title: 'Pengguna dihapus', message: `"${user.name}" berhasil dihapus.` });
    fetchUsers(pagination.value.current_page);
  } catch (error) {
    addToast({ type: 'error', title: 'Gagal', message: 'Tidak bisa menghapus pengguna.' });
  }
};

const initials = (name) => (name || 'U').substring(0, 2).toUpperCase();

const formatSize = (bytes) => {
  if (!bytes) return '0 GB';
  const gb = bytes / 1073741824;
  return gb >= 1 ? gb.toFixed(1) + ' GB' : (bytes / 1048576).toFixed(0) + ' MB';
};

const storagePercent = (u) => {
  if (!u.storage_quota) return 0;
  return Math.min(100, Math.round((u.storage_used / u.storage_quota) * 100));
};

const statusLabel = (status) => ({ active: 'Aktif', inactive: 'Nonaktif', suspended: 'Ditangguhkan' }[status] || 'Aktif');
const statusBadgeClass = (status) => ({ active: 'badge-emerald', inactive: 'badge-slate', suspended: 'badge-red' }[status] || 'badge-emerald');
const statusDotClass = (status) => ({ active: 'bg-emerald-400', inactive: 'bg-slate-400', suspended: 'bg-red-400' }[status] || 'bg-emerald-400');

onMounted(() => {
  fetchUsers();
  fetchRoles();
});
</script>

<style scoped>
.alert-error { margin-bottom: .25rem; }
</style>
