<template>
  <div class="glass-panel sidebar-root" :class="{ 'is-collapsed': collapsed }">
    <div class="h-16 px-5 flex items-center gap-3 border-b border-glass-border flex-shrink-0 sidebar-brand">
      <div class="logo-badge">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17.5 19C19.9853 19 22 16.9853 22 14.5C22 12.1388 20.1873 10.2016 17.8778 10.0191C17.433 6.62104 14.5262 4 11 4C7.13401 4 4 7.13401 4 11C4 11.2339 4.01146 11.4651 4.03395 11.6925C1.76189 12.0674 0 14.0768 0 16.5C0 19.5376 2.46243 22 5.5 22H17.5V19Z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <span class="text-base font-bold text-primary tracking-tight logo-text">BM Cloud</span>
    </div>

    <!-- Anak panah untuk melipat/melebarkan sidebar di layar desktop. -->
    <button
      type="button"
      class="collapse-toggle"
      :aria-expanded="!collapsed"
      aria-controls="app-sidebar"
      :aria-label="collapsed ? 'Lebarkan menu' : 'Lipat menu'"
      :title="collapsed ? 'Lebarkan menu' : 'Lipat menu'"
      @click="$emit('toggle-collapse')"
    >
      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
    </button>

    <!-- Tombol "Baru": aksi utama, gaya CTA pill khas Drive. -->
    <div class="new-btn-wrap" ref="newMenuRef">
      <button type="button" class="new-btn" :aria-expanded="newMenuOpen" aria-haspopup="menu" title="Baru" @click="newMenuOpen = !newMenuOpen">
        <span class="new-btn-icon">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        </span>
        <span class="new-btn-label">Baru</span>
      </button>

      <transition name="new-menu">
        <div v-if="newMenuOpen" class="new-menu" role="menu">
          <button type="button" class="new-menu-item" role="menuitem" @click="startNew('upload')">
            <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            Unggah File
          </button>
          <button type="button" class="new-menu-item" role="menuitem" @click="startNew('folder')">
            <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg>
            Folder Baru
          </button>
        </div>
      </transition>
    </div>

    <nav class="flex-1 overflow-y-auto py-2 px-3 flex flex-col gap-0.5">
      <router-link to="/dashboard" class="nav-item" active-class="active" title="Dashboard" @click="$emit('navigate')">
        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        <span>Dashboard</span>
      </router-link>
      <router-link to="/drive" class="nav-item" active-class="active" :class="{ active: route.path.startsWith('/drive') }" title="Drive Saya" @click="$emit('navigate')">
        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
        <span>Drive Saya</span>
      </router-link>
      <router-link to="/shared" class="nav-item" active-class="active" title="Dibagikan" @click="$emit('navigate')">
        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        <span>Dibagikan</span>
      </router-link>
      <router-link to="/favorites" class="nav-item" active-class="active" title="Favorit" @click="$emit('navigate')">
        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
        <span>Favorit</span>
      </router-link>
      <router-link to="/trash" class="nav-item" active-class="active" title="Sampah" @click="$emit('navigate')">
        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        <span>Sampah</span>
      </router-link>

      <template v-if="authStore.canMonitorDrives">
        <div class="nav-section-label">Pengawasan</div>
        <router-link to="/drive-monitor" class="nav-item" active-class="active" title="Pantau Drive" @click="$emit('navigate')">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
          <span>Pantau Drive</span>
        </router-link>
      </template>

      <template v-if="authStore.isAdmin">
        <div class="nav-section-label">Administrasi</div>

        <router-link to="/admin" class="nav-item" active-class="active" exact title="Ringkasan" @click="$emit('navigate')">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"></path><path d="M18 20V4"></path><path d="M6 20v-4"></path></svg>
          <span>Ringkasan</span>
        </router-link>
        <router-link to="/admin/users" class="nav-item" active-class="active" title="Pengguna" @click="$emit('navigate')">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          <span>Pengguna</span>
        </router-link>
        <router-link to="/admin/roles" class="nav-item" active-class="active" title="Peran" @click="$emit('navigate')">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          <span>Peran</span>
        </router-link>
        <router-link to="/admin/logs" class="nav-item" active-class="active" title="Log Aktivitas" @click="$emit('navigate')">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
          <span>Log Aktivitas</span>
        </router-link>
        <router-link to="/admin/backup" class="nav-item" active-class="active" title="Backup" @click="$emit('navigate')">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
          <span>Backup</span>
        </router-link>
      </template>
    </nav>

    <div class="p-4 border-t border-glass-border flex-shrink-0 sidebar-footer">
      <StorageUsage />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import StorageUsage from '../ui/StorageUsage.vue';

defineProps({
  collapsed: { type: Boolean, default: false },
});
const emit = defineEmits(['navigate', 'toggle-collapse']);

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const newMenuOpen = ref(false);
const newMenuRef = ref(null);

const startNew = (action) => {
  newMenuOpen.value = false;
  router.push({ path: '/drive', query: { action } });
  emit('navigate');
};

const onClickOutside = (event) => {
  if (newMenuOpen.value && newMenuRef.value && !newMenuRef.value.contains(event.target)) {
    newMenuOpen.value = false;
  }
};

onMounted(() => document.addEventListener('click', onClickOutside));
onUnmounted(() => document.removeEventListener('click', onClickOutside));
</script>

<style scoped>
.sidebar-root {
  position: relative; /* konteks untuk anak panah lipat/lebar */
  height: 100%;
  display: flex;
  flex-direction: column;
}

.glass-panel {
  background: var(--bg-secondary);
  border-right: 1px solid var(--separator);
}

.sidebar-brand { overflow: hidden; }

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
}

.logo-text { white-space: nowrap; transition: opacity .15s ease; }

/* Anak panah lipat/lebar: mengambang di tepi kanan sidebar, hanya untuk desktop. */
.collapse-toggle {
  display: none;
  position: absolute;
  top: 4rem;
  right: -0.7rem;
  width: 1.65rem;
  height: 1.65rem;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  border: 1px solid var(--separator);
  background: var(--bg-secondary);
  color: var(--text-secondary);
  box-shadow: var(--shadow-card);
  cursor: pointer;
  z-index: 5;
  transition: color .15s ease, border-color .15s ease, box-shadow .15s ease, transform .15s ease;
}
.collapse-toggle:hover { color: var(--accent-primary); border-color: var(--accent-primary); box-shadow: var(--shadow-hover); }
.collapse-toggle:active { transform: scale(.92); }
.collapse-toggle svg { transition: transform .25s ease; }

/* Tombol Baru: CTA pill mengambang khas Drive. */
.new-btn-wrap { position: relative; flex-shrink: 0; margin: .9rem .9rem .35rem; }
.new-btn {
  display: flex;
  align-items: center;
  gap: .65rem;
  width: 100%;
  min-height: 44px;
  padding: .5rem 1.1rem .5rem .65rem;
  border: 0;
  border-radius: 999px;
  background: var(--bg-secondary);
  color: var(--text-primary);
  font-weight: 500;
  font-size: .9rem;
  cursor: pointer;
  box-shadow: var(--shadow-card);
  border: 1px solid var(--separator);
  transition: box-shadow .15s ease, background .15s ease;
}
.new-btn:hover { box-shadow: var(--shadow-hover); background: var(--bg-secondary); }
.new-btn:active { box-shadow: var(--shadow-card); }
.new-btn-icon { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; color: var(--accent-primary); flex-shrink: 0; }
.new-btn-label { white-space: nowrap; }

.new-menu {
  position: absolute;
  left: 0;
  top: calc(100% + .4rem);
  z-index: 20;
  width: 13rem;
  padding: .4rem;
  border-radius: 12px;
  background: var(--bg-secondary);
  border: 1px solid var(--separator);
  box-shadow: var(--shadow-popover);
}
.new-menu-item {
  display: flex;
  align-items: center;
  gap: .65rem;
  width: 100%;
  min-height: 40px;
  padding: .5rem .65rem;
  border: 0;
  border-radius: 8px;
  background: none;
  color: var(--text-primary);
  font-size: .85rem;
  font-weight: 500;
  text-align: left;
  cursor: pointer;
  transition: background .15s ease;
}
.new-menu-item:hover { background: var(--fill-secondary); }
.new-menu-item svg { color: var(--text-secondary); flex-shrink: 0; }
.new-menu-enter-active, .new-menu-leave-active { transition: opacity .14s ease, transform .14s ease; }
.new-menu-enter-from, .new-menu-leave-to { opacity: 0; transform: translateY(-4px) scale(.98); }

.nav-section-label {
  margin: 1rem 1.05rem 0.4rem;
  padding-top: 0.8rem;
  border-top: 1px solid var(--separator);
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.055em;
  text-transform: uppercase;
  color: var(--text-muted);
  white-space: nowrap;
  overflow: hidden;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  min-height: 42px;
  padding: 0.6rem 1rem;
  margin: 0 .3rem;
  color: var(--text-secondary);
  border-radius: 999px;
  transition: background 0.15s ease, color 0.15s ease;
  font-weight: 500;
  font-size: 0.875rem;
  text-decoration: none;
  overflow: hidden;
}
.nav-item svg { flex-shrink: 0; }
.nav-item span { white-space: nowrap; }
.nav-item:hover {
  background: var(--fill-tertiary);
  color: var(--text-primary);
}
.nav-item.active {
  background: var(--accent-tint);
  color: var(--accent-primary-active);
  font-weight: 700;
}

.sidebar-footer { overflow: hidden; }

@media (max-width: 767px) {
  .glass-panel {
    box-shadow: var(--shadow-popover);
  }
}

/* Mode terlipat hanya berlaku di desktop; lihat AppLayout untuk lebar sidebar. */
@media (min-width: 768px) {
  .collapse-toggle { display: flex; }

  .is-collapsed .logo-text,
  .is-collapsed .nav-item span,
  .is-collapsed .nav-section-label,
  .is-collapsed .sidebar-footer,
  .is-collapsed .new-btn-label {
    display: none;
  }
  .is-collapsed .sidebar-brand { justify-content: center; padding-left: 0; padding-right: 0; }
  .is-collapsed .nav-item { justify-content: center; padding-left: 0.5rem; padding-right: 0.5rem; }
  .is-collapsed .new-btn-wrap { margin-left: .5rem; margin-right: .5rem; }
  .is-collapsed .new-btn { justify-content: center; padding-left: .5rem; padding-right: .5rem; }
  .is-collapsed .collapse-toggle svg { transform: rotate(180deg); }
}
</style>
