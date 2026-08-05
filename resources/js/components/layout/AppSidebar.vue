<template>
  <div class="glass-panel h-full flex flex-col">
    <div class="h-16 px-5 flex items-center gap-3 border-b border-glass-border flex-shrink-0">
      <div class="logo-badge">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17.5 19C19.9853 19 22 16.9853 22 14.5C22 12.1388 20.1873 10.2016 17.8778 10.0191C17.433 6.62104 14.5262 4 11 4C7.13401 4 4 7.13401 4 11C4 11.2339 4.01146 11.4651 4.03395 11.6925C1.76189 12.0674 0 14.0768 0 16.5C0 19.5376 2.46243 22 5.5 22H17.5V19Z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <span class="text-base font-bold text-primary tracking-tight">BM Cloud</span>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 flex flex-col gap-0.5">
      <router-link to="/dashboard" class="nav-item" active-class="active" @click="$emit('navigate')">
        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        <span>Dashboard</span>
      </router-link>
      <router-link to="/drive" class="nav-item" active-class="active" :class="{ active: route.path.startsWith('/drive') }" @click="$emit('navigate')">
        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
        <span>Drive Saya</span>
      </router-link>
      <router-link to="/shared" class="nav-item" active-class="active" @click="$emit('navigate')">
        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        <span>Dibagikan</span>
      </router-link>
      <router-link to="/favorites" class="nav-item" active-class="active" @click="$emit('navigate')">
        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
        <span>Favorit</span>
      </router-link>
      <router-link to="/trash" class="nav-item" active-class="active" @click="$emit('navigate')">
        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        <span>Sampah</span>
      </router-link>

      <template v-if="authStore.canMonitorDrives">
        <div class="nav-section-label">Pengawasan</div>
        <router-link to="/drive-monitor" class="nav-item" active-class="active" @click="$emit('navigate')">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
          <span>Pantau Drive</span>
        </router-link>
      </template>

      <template v-if="authStore.isAdmin">
        <div class="nav-section-label">Administrasi</div>

        <router-link to="/admin" class="nav-item" active-class="active" exact @click="$emit('navigate')">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"></path><path d="M18 20V4"></path><path d="M6 20v-4"></path></svg>
          <span>Ringkasan</span>
        </router-link>
        <router-link to="/admin/users" class="nav-item" active-class="active" @click="$emit('navigate')">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          <span>Pengguna</span>
        </router-link>
        <router-link to="/admin/roles" class="nav-item" active-class="active" @click="$emit('navigate')">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          <span>Peran</span>
        </router-link>
        <router-link to="/admin/logs" class="nav-item" active-class="active" @click="$emit('navigate')">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
          <span>Log Aktivitas</span>
        </router-link>
        <router-link to="/admin/backup" class="nav-item" active-class="active" @click="$emit('navigate')">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
          <span>Backup</span>
        </router-link>
      </template>
    </nav>

    <div class="p-4 border-t border-glass-border flex-shrink-0">
      <StorageUsage />
    </div>
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import StorageUsage from '../ui/StorageUsage.vue';

defineEmits(['navigate']);

const route = useRoute();
const authStore = useAuthStore();
</script>

<style scoped>
.glass-panel {
  background: rgba(249, 249, 251, 0.9);
  -webkit-backdrop-filter: saturate(180%) blur(24px);
  backdrop-filter: saturate(180%) blur(24px);
  border-right: 1px solid var(--separator);
}

.logo-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 10px;
  background: var(--accent-primary);
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.18);
}

.nav-section-label {
  margin: 1rem 0.75rem 0.4rem;
  padding-top: 0.8rem;
  border-top: 1px solid var(--separator);
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.055em;
  text-transform: uppercase;
  color: var(--text-muted);
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-height: 42px;
  padding: 0.6rem 0.75rem;
  color: var(--text-secondary);
  border-radius: 10px;
  transition: background 0.15s ease, color 0.15s ease;
  font-weight: 500;
  font-size: 0.875rem;
  text-decoration: none;
}
.nav-item svg { flex-shrink: 0; }
.nav-item:hover {
  background: var(--fill-tertiary);
  color: var(--text-primary);
}
.nav-item.active {
  background: rgba(0, 122, 255, 0.11);
  color: var(--accent-primary);
  font-weight: 600;
}

@media (max-width: 767px) {
  .glass-panel {
    box-shadow: 18px 0 50px rgba(0, 0, 0, 0.14);
  }
}
</style>
