<template>
  <div class="roles-page">
    <div class="page-header reveal" style="--d: 0ms">
      <div>
        <h1>Peran &amp; Izin Akses</h1>
        <p>Kelola peran sistem dan tingkat aksesnya.</p>
      </div>
      <button class="btn btn-primary">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Peran Baru
      </button>
    </div>

    <!-- Loading skeletons -->
    <div v-if="loading" class="role-stack">
      <div v-for="n in 3" :key="n" class="glass-card skeleton-card"></div>
    </div>

    <!-- Roles -->
    <div v-else class="role-stack">
      <div
        v-for="(role, index) in roles"
        :key="role.id"
        class="glass-card role-card reveal"
        :style="`--d: ${80 + index * 80}ms`"
      >
        <div class="role-header">
          <div class="role-id">
            <span class="role-badge">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            </span>
            <div>
              <h3>{{ role.name }}</h3>
              <span class="role-count">{{ role.users_count }} pengguna</span>
            </div>
          </div>
          <div class="role-actions">
            <button class="btn-icon" title="Edit Peran">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            </button>
            <button v-if="!role.is_system" class="btn-icon danger" title="Hapus Peran">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            </button>
          </div>
        </div>

        <div class="role-body">
          <h4 class="perm-title">Izin Akses</h4>
          <div class="perm-grid">
            <div v-for="(perms, group) in role.permissionsByGroup" :key="group" class="perm-group">
              <div class="perm-group-title">{{ group }}</div>
              <label v-for="perm in perms" :key="perm.id" class="perm-item" :class="{ granted: perm.granted }">
                <span class="perm-check" :class="{ on: perm.granted }">
                  <svg v-if="perm.granted" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </span>
                <span class="perm-name">{{ perm.name }}</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../../composables/useApi';

const loading = ref(true);
const roles = ref([]);

const fetchRoles = async () => {
  loading.value = true;
  try {
    const response = await api.get('/admin/roles');
    roles.value = response.data;
  } catch (error) {
    console.error("Failed to fetch roles", error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchRoles();
});
</script>

<style scoped>
.roles-page { display: flex; flex-direction: column; gap: 1.25rem; }
.role-stack { display: flex; flex-direction: column; gap: 1.15rem; }

.role-card { overflow: hidden; padding: 0; }
.role-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.05rem 1.25rem; border-bottom: 1px solid var(--separator); background: var(--fill-tertiary); }
.role-id { display: flex; align-items: center; gap: .8rem; min-width: 0; }
.role-badge { width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 12px; flex-shrink: 0; background: rgba(26, 115, 232,.11); color: var(--accent-primary); }
.role-id h3 { font-size: 1.05rem; font-weight: 700; letter-spacing: -.02em; }
.role-count { font-size: .75rem; color: var(--text-muted); }
.role-actions { display: flex; gap: .35rem; flex-shrink: 0; }
.btn-icon.danger { color: var(--accent-danger); }
.btn-icon.danger:hover { background: rgba(217, 48, 37,.09); }

.role-body { padding: 1.1rem 1.25rem 1.25rem; }
.perm-title { font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--text-muted); margin-bottom: .9rem; }
.perm-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 1.1rem 1.5rem; }
@media (min-width: 640px) { .perm-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1024px) { .perm-grid { grid-template-columns: repeat(4, 1fr); } }
.perm-group { display: flex; flex-direction: column; gap: .55rem; }
.perm-group-title { font-size: .78rem; font-weight: 700; color: var(--text-primary); padding-bottom: .4rem; border-bottom: 1px solid var(--separator); text-transform: capitalize; }
.perm-item { display: flex; align-items: center; gap: .55rem; }
.perm-check { width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; border-radius: 6px; border: 1.5px solid var(--separator-opaque); background: var(--bg-secondary); color: #fff; transition: background .15s ease, border-color .15s ease; }
.perm-check.on { background: var(--accent-primary); border-color: var(--accent-primary); }
.perm-name { font-size: .82rem; color: var(--text-secondary); }
.perm-item.granted .perm-name { color: var(--text-primary); font-weight: 500; }

.reveal { opacity: 0; animation: reveal-up .5s cubic-bezier(.2,.8,.2,1) forwards; animation-delay: var(--d, 0ms); }
@keyframes reveal-up { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.skeleton-card { height: 180px; position: relative; overflow: hidden; }
.skeleton-card::after { content: ''; position: absolute; inset: 0; transform: translateX(-100%); background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent); animation: shimmer 1.4s infinite; }
@keyframes shimmer { 100% { transform: translateX(100%); } }

@media (prefers-reduced-motion: reduce) { .reveal { animation: none; opacity: 1; transform: none; } }
</style>
