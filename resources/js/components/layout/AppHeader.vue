<template>
  <header class="app-header sticky top-0 z-10 flex items-center justify-between gap-4 px-4 md:px-6">
    <div class="flex items-center gap-3 min-w-0">
      <button
        type="button"
        class="icon-btn sidebar-toggle"
        aria-controls="app-sidebar"
        :aria-expanded="props.sidebarOpen"
        :aria-label="props.sidebarOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi'"
        @click="emit('toggle-sidebar')"
      >
        <svg v-if="props.sidebarOpen" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        <svg v-else class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
      </button>

      <div class="min-w-0">
        <h1 class="text-sm font-semibold text-primary truncate">{{ currentRouteName }}</h1>
      </div>
    </div>

    <div class="flex items-center gap-2 md:gap-3">
      <SearchBar class="hidden md:block w-56 lg:w-80" />

      <div ref="notifMenuContainerRef" class="relative">
        <button
          type="button"
          class="icon-btn relative"
          :aria-expanded="notifMenuOpen"
          aria-haspopup="dialog"
          :aria-label="notifStore.unreadCount ? `Notifikasi, ${notifStore.unreadCount} belum dibaca` : 'Notifikasi'"
          @click="toggleNotifMenu"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
          <span v-if="notifStore.hasUnread" class="notif-badge">{{ notifStore.badgeLabel }}</span>
        </button>

        <transition name="fade">
          <NotificationPanel v-if="notifMenuOpen" @close="notifMenuOpen = false" />
        </transition>
      </div>

      <div ref="profileMenuContainerRef" class="relative">
        <button
          type="button"
          class="profile-btn"
          :aria-expanded="profileMenuOpen"
          aria-haspopup="menu"
          @click="profileMenuOpen = !profileMenuOpen"
        >
          <div class="avatar">{{ userInitials }}</div>
          <span class="text-sm font-medium hidden sm:block">{{ authStore.user?.name?.split(' ')[0] }}</span>
          <svg class="w-4 h-4 hidden sm:block text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </button>

        <transition name="fade">
          <div v-if="profileMenuOpen" class="profile-menu glass-card animate-slide-up" role="menu">
            <div class="profile-menu-header">
              <div class="font-medium text-primary truncate">{{ authStore.user?.name }}</div>
              <div class="text-xs text-secondary truncate">{{ authStore.user?.email }}</div>
            </div>
            <router-link to="/profile" @click="profileMenuOpen = false" class="profile-menu-item">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              Pengaturan Profil
            </router-link>
            <div class="profile-menu-divider"></div>
            <button @click="logout" class="profile-menu-item danger">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
              Keluar
            </button>
          </div>
        </transition>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useNotificationStore } from '../../stores/notifications';
import { addToast } from '../ui/Toast.vue';
import SearchBar from '../ui/SearchBar.vue';
import NotificationPanel from '../ui/NotificationPanel.vue';

const props = defineProps({
  sidebarOpen: { type: Boolean, default: false },
});
const emit = defineEmits(['toggle-sidebar']);

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const notifStore = useNotificationStore();
const profileMenuOpen = ref(false);
const profileMenuContainerRef = ref(null);
const notifMenuOpen = ref(false);
const notifMenuContainerRef = ref(null);

const routeLabels = {
  Dashboard: 'Ringkasan',
  Drive: 'Drive Saya',
  DriveMonitor: 'Pantau Drive',
  Shared: 'Dibagikan',
  Favorites: 'Favorit',
  Trash: 'Sampah',
  Search: 'Pencarian',
  Profile: 'Profil',
  AdminDashboard: 'Ringkasan Admin',
  AdminUsers: 'Manajemen Pengguna',
  AdminRoles: 'Peran & Izin',
  AdminLogs: 'Log Aktivitas',
  AdminBackup: 'Backup Sistem',
};
const currentRouteName = computed(() => routeLabels[route.name] || 'Penyimpanan Cloud');

const userInitials = computed(() => {
  const name = authStore.user?.name || 'User';
  return name.substring(0, 2).toUpperCase();
});

const toggleNotifMenu = () => {
  notifMenuOpen.value = !notifMenuOpen.value;
  if (notifMenuOpen.value) profileMenuOpen.value = false;
};

const onClickOutside = (event) => {
  if (profileMenuOpen.value && profileMenuContainerRef.value && !profileMenuContainerRef.value.contains(event.target)) {
    profileMenuOpen.value = false;
  }
  if (notifMenuOpen.value && notifMenuContainerRef.value && !notifMenuContainerRef.value.contains(event.target)) {
    notifMenuOpen.value = false;
  }
};

const onEscape = (event) => {
  if (event.key !== 'Escape') return;
  profileMenuOpen.value = false;
  notifMenuOpen.value = false;
};

onMounted(() => {
  document.addEventListener('click', onClickOutside);
  document.addEventListener('keydown', onEscape);

  // Tidak ada websocket di stack ini (BROADCAST_CONNECTION=log), jadi badge
  // disegarkan lewat polling ringan yang hanya mengambil jumlah belum dibaca.
  notifStore.startPolling((delta) => {
    addToast({
      type: 'info',
      title: 'Notifikasi baru',
      message: delta === 1 ? 'Anda punya 1 notifikasi baru.' : `Anda punya ${delta} notifikasi baru.`,
    });
  });
});

onUnmounted(() => {
  document.removeEventListener('click', onClickOutside);
  document.removeEventListener('keydown', onEscape);
  notifStore.stopPolling();
});

const logout = async () => {
  notifStore.reset();
  await authStore.logout();
  router.push('/login');
};
</script>

<style scoped>
.app-header {
  height: 4rem;
  flex-shrink: 0;
  background: rgba(249, 249, 251, 0.82);
  -webkit-backdrop-filter: saturate(180%) blur(22px);
  backdrop-filter: saturate(180%) blur(22px);
  border-bottom: 1px solid var(--separator);
}

.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 999px;
  color: var(--text-secondary);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease, transform .1s ease;
  flex-shrink: 0;
}
.icon-btn:hover { background: var(--fill-secondary); color: var(--accent-primary); }
.icon-btn:active { transform: scale(.94); }

/*
  Jangan pakai utility md:hidden di sini: .icon-btn (di app.css maupun scoped
  style ini) menyetel display tanpa @layer, jadi selalu menang atas .hidden
  milik Tailwind. Visibilitasnya diatur eksplisit lewat media query.
*/
.sidebar-toggle { display: inline-flex; }
@media (min-width: 768px) {
  .sidebar-toggle { display: none; }
}

.notif-badge {
  position: absolute;
  top: -0.1rem;
  right: -0.1rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.05rem;
  height: 1.05rem;
  padding: 0 0.25rem;
  border-radius: 999px;
  background: var(--accent-danger);
  color: #fff;
  font-size: 0.62rem;
  font-weight: 700;
  line-height: 1;
  box-shadow: 0 0 0 2px #f9f9fb;
  pointer-events: none;
}

.profile-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-height: 42px;
  padding: 0.25rem 0.65rem 0.25rem 0.3rem;
  border-radius: 999px;
  color: var(--text-primary);
  background: transparent;
  border: 1px solid transparent;
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease;
}
.profile-btn:hover { background: var(--fill-tertiary); border-color: var(--separator); }

.avatar {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.72rem;
  font-weight: 700;
  color: #fff;
  background: var(--accent-primary);
  flex-shrink: 0;
}

.profile-menu {
  position: absolute;
  right: 0;
  top: calc(100% + 0.5rem);
  width: 15rem;
  padding: 0.4rem;
  z-index: 50;
  box-shadow: var(--shadow-popover);
}
.profile-menu-header { padding: 0.65rem 0.75rem 0.75rem; border-bottom: 1px solid var(--separator); margin-bottom: 0.4rem; }
.profile-menu-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  width: 100%;
  min-height: 40px;
  padding: 0.55rem 0.75rem;
  border-radius: 9px;
  font-size: 0.85rem;
  color: var(--text-primary);
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
  text-decoration: none;
  transition: background 0.15s ease, color 0.15s ease;
}
.profile-menu-item:hover { background: var(--fill-secondary); color: var(--accent-primary); }
.profile-menu-item.danger { color: var(--accent-danger); }
.profile-menu-item.danger:hover { background: rgba(255, 59, 48, 0.09); color: var(--accent-danger); }
.profile-menu-divider { height: 1px; background: var(--separator); margin: 0.3rem 0; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
