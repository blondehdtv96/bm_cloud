<template>
  <div class="notif-panel glass-card animate-slide-up" role="dialog" aria-label="Notifikasi">
    <div class="notif-head">
      <div class="notif-title">
        <h2>Notifikasi</h2>
        <span v-if="store.unreadCount" class="badge badge-red">{{ store.badgeLabel }} baru</span>
      </div>
      <button
        v-if="store.hasUnread"
        type="button"
        class="notif-link"
        @click="store.markAllRead()"
      >
        Tandai semua dibaca
      </button>
    </div>

    <div class="notif-tabs" role="tablist">
      <button
        type="button"
        class="notif-tab"
        :class="{ active: store.filter === 'all' }"
        role="tab"
        :aria-selected="store.filter === 'all'"
        @click="store.setFilter('all')"
      >
        Semua
      </button>
      <button
        type="button"
        class="notif-tab"
        :class="{ active: store.filter === 'unread' }"
        role="tab"
        :aria-selected="store.filter === 'unread'"
        @click="store.setFilter('unread')"
      >
        Belum dibaca
      </button>
    </div>

    <div class="notif-body">
      <div v-if="store.loading" class="notif-placeholder">
        <svg class="w-6 h-6 animate-spin" style="color: var(--accent-primary)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
        <span>Memuat notifikasi...</span>
      </div>

      <div v-else-if="store.items.length === 0" class="notif-placeholder">
        <span class="notif-empty-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        </span>
        <strong>{{ store.filter === 'unread' ? 'Semua sudah dibaca' : 'Belum ada notifikasi' }}</strong>
        <span>{{ store.filter === 'unread' ? 'Tidak ada notifikasi yang belum dibaca.' : 'Aktivitas berbagi, kuota, dan backup akan muncul di sini.' }}</span>
      </div>

      <ul v-else class="notif-list">
        <li v-for="item in store.items" :key="item.id">
          <div class="notif-item" :class="{ unread: !item.read_at }">
            <button type="button" class="notif-item-main" @click="open(item)">
              <span class="notif-icon" :class="`tone-${tone(item.type)}`">
                <component :is="iconFor(item.type)" />
              </span>
              <span class="notif-text">
                <span class="notif-item-title">{{ item.title }}</span>
                <span class="notif-item-message">{{ item.message }}</span>
                <span class="notif-item-time">{{ timeAgo(item.created_at) }}</span>
              </span>
              <span v-if="!item.read_at" class="notif-unread-dot" aria-label="Belum dibaca"></span>
            </button>
            <button
              type="button"
              class="notif-remove"
              title="Hapus notifikasi"
              @click.stop="store.remove(item)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>
        </li>
      </ul>

      <button
        v-if="store.hasMore && !store.loading"
        type="button"
        class="notif-more"
        :disabled="store.loadingMore"
        @click="store.fetch({ append: true })"
      >
        {{ store.loadingMore ? 'Memuat...' : 'Muat lebih banyak' }}
      </button>
    </div>

    <div v-if="store.items.length" class="notif-foot">
      <button type="button" class="notif-link danger" @click="confirmClear">Hapus semua</button>
    </div>
  </div>
</template>

<script setup>
import { h, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useNotificationStore } from '../../stores/notifications';

const emit = defineEmits(['close']);
const router = useRouter();
const store = useNotificationStore();

const svg = (...children) =>
  h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': 2 }, children);

const icons = {
  share: () =>
    svg(
      h('circle', { cx: 18, cy: 5, r: 3 }),
      h('circle', { cx: 6, cy: 12, r: 3 }),
      h('circle', { cx: 18, cy: 19, r: 3 }),
      h('line', { x1: 8.59, y1: 13.51, x2: 15.42, y2: 17.49 }),
      h('line', { x1: 15.41, y1: 6.51, x2: 8.59, y2: 10.49 })
    ),
  share_revoked: () =>
    svg(
      h('path', { d: 'M18.36 6.64A9 9 0 1 1 5.64 5.64' }),
      h('line', { x1: 12, y1: 2, x2: 12, y2: 12 })
    ),
  storage: () =>
    svg(
      h('ellipse', { cx: 12, cy: 5, rx: 9, ry: 3 }),
      h('path', { d: 'M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5' }),
      h('path', { d: 'M3 12c0 1.66 4 3 9 3s9-1.34 9-3' })
    ),
  backup: () =>
    svg(
      h('path', { d: 'M21 12a9 9 0 1 1-3-6.7' }),
      h('polyline', { points: '21 3 21 9 15 9' })
    ),
  account: () =>
    svg(
      h('path', { d: 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2' }),
      h('circle', { cx: 12, cy: 7, r: 4 })
    ),
  default: () =>
    svg(
      h('circle', { cx: 12, cy: 12, r: 10 }),
      h('line', { x1: 12, y1: 16, x2: 12, y2: 12 }),
      h('line', { x1: 12, y1: 8, x2: 12.01, y2: 8 })
    ),
};

const tones = {
  share: 'blue',
  share_revoked: 'red',
  storage: 'amber',
  backup: 'green',
  account: 'purple',
};

const iconFor = (type) => icons[type] || icons.default;
const tone = (type) => tones[type] || 'blue';

const timeAgo = (value) => {
  if (!value) return '';
  const seconds = Math.floor((Date.now() - new Date(value).getTime()) / 1000);
  if (seconds < 60) return 'Baru saja';
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes} menit lalu`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours} jam lalu`;
  const days = Math.floor(hours / 24);
  if (days < 7) return `${days} hari lalu`;
  return new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const open = async (item) => {
  await store.markAsRead(item);
  const url = item.data?.url;
  if (url) {
    emit('close');
    router.push(url);
  }
};

const confirmClear = () => {
  if (window.confirm('Hapus semua notifikasi? Tindakan ini tidak bisa dibatalkan.')) {
    store.clearAll();
  }
};

onMounted(() => {
  // Selalu ambil ulang saat panel dibuka supaya daftarnya segar.
  store.fetch();
});
</script>

<style scoped>
.notif-panel {
  position: absolute;
  right: 0;
  top: calc(100% + .5rem);
  z-index: 50;
  display: flex;
  flex-direction: column;
  width: min(92vw, 24rem);
  max-height: min(32rem, calc(100vh - 6rem));
  padding: 0;
  overflow: hidden;
  box-shadow: var(--shadow-popover);
}

.notif-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
  padding: .85rem 1rem .7rem;
}
.notif-title { display: flex; align-items: center; gap: .5rem; min-width: 0; }
.notif-title h2 { font-size: .95rem; font-weight: 700; }

.notif-link {
  flex-shrink: 0;
  padding: .2rem .35rem;
  border: 0;
  border-radius: 7px;
  background: none;
  color: var(--accent-primary);
  font-size: .78rem;
  font-weight: 600;
  cursor: pointer;
  transition: background .15s ease;
}
.notif-link:hover { background: var(--fill-secondary); }
.notif-link.danger { color: var(--accent-danger); }
.notif-link.danger:hover { background: rgba(217, 48, 37, .09); }

.notif-tabs {
  display: flex;
  gap: 2px;
  margin: 0 1rem .25rem;
  padding: 2px;
  border-radius: 9px;
  background: var(--fill-secondary);
}
.notif-tab {
  flex: 1;
  min-height: 30px;
  border: 0;
  border-radius: 7px;
  background: none;
  color: var(--text-secondary);
  font-size: .78rem;
  font-weight: 600;
  cursor: pointer;
  transition: background .15s ease, color .15s ease;
}
.notif-tab.active { background: var(--bg-secondary); color: var(--accent-primary); box-shadow: 0 1px 4px rgba(0, 0, 0, .1); }

.notif-body { flex: 1; min-height: 0; overflow-y: auto; padding: .35rem .5rem .5rem; }

.notif-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: .4rem;
  padding: 2.5rem 1.25rem;
  text-align: center;
  font-size: .8rem;
  color: var(--text-secondary);
}
.notif-placeholder strong { font-size: .875rem; color: var(--text-primary); }
.notif-empty-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  margin-bottom: .35rem;
  border-radius: 16px;
  color: var(--accent-primary);
  background: rgba(26, 115, 232, .09);
}
.notif-empty-icon svg { width: 26px; height: 26px; }

.notif-list { list-style: none; margin: 0; padding: 0; }

.notif-item { position: relative; display: flex; align-items: stretch; border-radius: 11px; }
.notif-item:hover { background: var(--fill-tertiary); }
.notif-item.unread { background: rgba(26, 115, 232, .06); }
.notif-item.unread:hover { background: rgba(26, 115, 232, .1); }

.notif-item-main {
  flex: 1;
  display: flex;
  align-items: flex-start;
  gap: .7rem;
  min-width: 0;
  padding: .65rem .6rem;
  border: 0;
  border-radius: inherit;
  background: none;
  text-align: left;
  cursor: pointer;
}

.notif-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  flex-shrink: 0;
  border-radius: 10px;
}
.notif-icon :deep(svg) { width: 17px; height: 17px; }
.tone-blue { color: var(--accent-primary); background: rgba(26, 115, 232, .10); }
.tone-green { color: #1e8e3e; background: rgba(30, 142, 62, .12); }
.tone-amber { color: #a35a00; background: rgba(249, 171, 0, .13); }
.tone-red { color: #b3261e; background: rgba(217, 48, 37, .10); }
.tone-purple { color: #8430ce; background: rgba(161, 66, 244, .12); }

.notif-text { display: flex; flex-direction: column; gap: .1rem; min-width: 0; }
.notif-item-title { font-size: .82rem; font-weight: 600; color: var(--text-primary); }
.notif-item-message {
  font-size: .78rem;
  line-height: 1.4;
  color: var(--text-secondary);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.notif-item-time { margin-top: .1rem; font-size: .7rem; color: var(--text-muted); }

.notif-unread-dot {
  flex-shrink: 0;
  width: .42rem;
  height: .42rem;
  margin-top: .45rem;
  border-radius: 50%;
  background: var(--accent-primary);
}

.notif-remove {
  flex-shrink: 0;
  width: 30px;
  margin: .35rem .25rem .35rem 0;
  border: 0;
  border-radius: 8px;
  background: none;
  color: var(--text-muted);
  cursor: pointer;
  opacity: 0;
  transition: opacity .15s ease, color .15s ease, background .15s ease;
}
.notif-remove svg { width: 15px; height: 15px; margin: 0 auto; }
.notif-item:hover .notif-remove, .notif-remove:focus-visible { opacity: 1; }
.notif-remove:hover { color: var(--accent-danger); background: rgba(217, 48, 37, .10); }

.notif-more {
  width: 100%;
  min-height: 38px;
  margin-top: .35rem;
  border: 0;
  border-radius: 9px;
  background: var(--fill-tertiary);
  color: var(--accent-primary);
  font-size: .8rem;
  font-weight: 600;
  cursor: pointer;
}
.notif-more:hover:not(:disabled) { background: var(--fill-secondary); }
.notif-more:disabled { color: var(--text-muted); cursor: not-allowed; }

.notif-foot { flex-shrink: 0; display: flex; justify-content: flex-end; padding: .5rem .75rem; border-top: 1px solid var(--separator); }

/* Touch device: tombol hapus tidak bisa mengandalkan hover. */
@media (hover: none) {
  .notif-remove { opacity: 1; }
}
</style>
