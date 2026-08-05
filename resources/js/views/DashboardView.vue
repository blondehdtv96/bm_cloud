<template>
  <div class="dashboard">
    <!-- Greeting -->
    <header class="dash-hero reveal" style="--d: 0ms">
      <div>
        <p class="dash-date">{{ todayLabel }}</p>
        <h1 class="dash-title">Selamat datang kembali, {{ firstName }} <span class="wave">👋</span></h1>
        <p class="dash-subtitle">Berikut ringkasan aktivitas ruang kerja Anda.</p>
      </div>
      <button class="btn btn-primary dash-hero-cta" @click="$router.push('/drive')">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
        Unggah File
      </button>
    </header>

    <!-- Stat cards -->
    <section class="stat-grid">
      <div
        v-for="(stat, index) in statCards"
        :key="stat.key"
        class="stat-card reveal"
        :style="`--d: ${80 + index * 70}ms`"
      >
        <span class="stat-icon" :class="stat.tone">
          <component :is="stat.icon" class="w-5 h-5" />
        </span>
        <div class="stat-meta">
          <h3>{{ stat.display }}</h3>
          <p>{{ stat.label }}</p>
        </div>
      </div>
    </section>

    <!-- Main grid -->
    <section class="dash-columns">
      <!-- Recent files -->
      <div class="glass-card dash-panel reveal" style="--d: 360ms">
        <div class="panel-head">
          <h2>File Terbaru</h2>
          <router-link to="/drive" class="panel-link">Lihat Semua</router-link>
        </div>

        <div v-if="loading" class="file-grid">
          <div v-for="n in 6" :key="n" class="file-tile skeleton"></div>
        </div>

        <TransitionGroup v-else-if="recentFiles.length" name="stagger" tag="div" class="file-grid">
          <button
            v-for="(file, i) in recentFiles"
            :key="file.id"
            class="file-tile"
            :style="`--d: ${i * 55}ms`"
            @click="goToFile(file)"
          >
            <span class="file-thumb" :class="fileTone(file)">
              <component :is="fileIcon(file)" class="w-5 h-5" />
            </span>
            <span class="file-name" :title="file.original_name">{{ file.original_name }}</span>
            <span class="file-sub">{{ formatSize(file.size) }} · {{ timeAgo(file.created_at) }}</span>
          </button>
        </TransitionGroup>

        <EmptyState
          v-else
          title="Belum ada file"
          description="Unggah file pertama Anda untuk melihatnya muncul di sini."
          action-text="Unggah File"
          @action="$router.push('/drive')"
        />
      </div>

      <!-- Side column -->
      <div class="dash-side">
        <div class="glass-card dash-panel reveal" style="--d: 420ms">
          <div class="panel-head"><h2>Penyimpanan</h2></div>
          <StorageUsage />
        </div>

        <div class="glass-card dash-panel reveal" style="--d: 480ms">
          <div class="panel-head"><h2>Aksi Cepat</h2></div>
          <div class="quick-actions">
            <button class="quick-btn" @click="$router.push('/drive')">
              <span class="quick-ic tone-blue"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></span>
              Unggah File Baru
              <svg class="quick-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
            <button class="quick-btn" @click="$router.push('/drive')">
              <span class="quick-ic tone-amber"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg></span>
              Buat Folder
              <svg class="quick-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
            <button class="quick-btn" @click="$router.push('/shared')">
              <span class="quick-ic tone-green"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg></span>
              Kelola Berbagi
              <svg class="quick-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
          </div>
        </div>

        <div class="glass-card dash-panel reveal" style="--d: 540ms">
          <div class="panel-head"><h2>Aktivitas Terbaru</h2></div>
          <div v-if="loading" class="activity-list">
            <div v-for="n in 4" :key="n" class="activity-row skeleton-row"></div>
          </div>
          <ul v-else-if="recentActivities.length" class="activity-list">
            <li v-for="(act, i) in recentActivities" :key="act.id" class="activity-row reveal" :style="`--d: ${i * 60}ms`">
              <span class="activity-dot" :class="actionTone(act.action)"></span>
              <div class="activity-text">
                <span class="activity-title">{{ actionLabel(act.action) }}</span>
                <span class="activity-time">{{ timeAgo(act.created_at) }}</span>
              </div>
            </li>
          </ul>
          <p v-else class="activity-empty">Belum ada aktivitas tercatat.</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { api } from '../composables/useApi';
import StorageUsage from '../components/ui/StorageUsage.vue';
import EmptyState from '../components/ui/EmptyState.vue';

const router = useRouter();
const authStore = useAuthStore();

const loading = ref(true);
const stats = ref({ total_files: 0, total_folders: 0, shared_by_me: 0, storage_used: 0, storage_quota: 0 });
const recentFiles = ref([]);
const recentActivities = ref([]);

const firstName = computed(() => authStore.user?.name?.split(' ')[0] || 'Pengguna');
const todayLabel = computed(() =>
  new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date())
);

const svg = (children, w = 2) => () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': w }, children);
const FileIcon = svg([h('path', { d: 'M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z' }), h('polyline', { points: '13 2 13 9 20 9' })]);
const FolderIcon = svg([h('path', { d: 'M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z' })]);
const DriveIcon = svg([h('ellipse', { cx: 12, cy: 5, rx: 9, ry: 3 }), h('path', { d: 'M21 12c0 1.66-4 3-9 3s-9-1.34-9-3' }), h('path', { d: 'M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5' })]);
const ShareIcon = svg([h('circle', { cx: 18, cy: 5, r: 3 }), h('circle', { cx: 6, cy: 12, r: 3 }), h('circle', { cx: 18, cy: 19, r: 3 }), h('line', { x1: 8.59, y1: 13.51, x2: 15.42, y2: 17.49 }), h('line', { x1: 15.41, y1: 6.51, x2: 8.59, y2: 10.49 })]);
const ImageIcon = svg([h('rect', { x: 3, y: 3, width: 18, height: 18, rx: 2 }), h('circle', { cx: 8.5, cy: 8.5, r: 1.5 }), h('polyline', { points: '21 15 16 10 5 21' })], 1.6);
const VideoIcon = svg([h('polygon', { points: '23 7 16 12 23 17 23 7' }), h('rect', { x: 1, y: 5, width: 15, height: 14, rx: 2 })], 1.6);

const statCards = computed(() => [
  { key: 'files', label: 'Total File', display: formatNumber(stats.value.total_files), icon: FileIcon, tone: 'tone-blue' },
  { key: 'folders', label: 'Folder', display: formatNumber(stats.value.total_folders), icon: FolderIcon, tone: 'tone-amber' },
  { key: 'storage', label: 'Penyimpanan Terpakai', display: formatSize(stats.value.storage_used), icon: DriveIcon, tone: 'tone-teal' },
  { key: 'shared', label: 'Item Dibagikan', display: formatNumber(stats.value.shared_by_me), icon: ShareIcon, tone: 'tone-green' },
]);

const formatNumber = (n) => new Intl.NumberFormat('id-ID').format(n || 0);
const formatSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const timeAgo = (dateString) => {
  if (!dateString) return '';
  const diff = (Date.now() - new Date(dateString).getTime()) / 1000;
  if (diff < 60) return 'Baru saja';
  if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`;
  if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`;
  if (diff < 604800) return `${Math.floor(diff / 86400)} hari lalu`;
  return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short' }).format(new Date(dateString));
};

const fileIcon = (file) => {
  const mime = file.mime_type || '';
  if (mime.startsWith('image/')) return ImageIcon;
  if (mime.startsWith('video/')) return VideoIcon;
  return FileIcon;
};
const fileTone = (file) => {
  const mime = file.mime_type || '';
  if (mime.startsWith('image/')) return 'tone-pink';
  if (mime.startsWith('video/')) return 'tone-purple';
  if (mime === 'application/pdf') return 'tone-red';
  return 'tone-blue';
};

const actionLabels = { created: 'Membuat item', uploaded: 'Mengunggah file', downloaded: 'Mengunduh file', deleted: 'Menghapus item', shared: 'Membagikan item', updated: 'Memperbarui item', monitored_drive: 'Memantau drive' };
const actionLabel = (action) => actionLabels[action] || 'Aktivitas';
const actionTone = (action) => ({ uploaded: 'tone-blue', created: 'tone-green', shared: 'tone-teal', downloaded: 'tone-amber', deleted: 'tone-red', monitored_drive: 'tone-purple' }[action] || 'tone-blue');

const goToFile = (file) => {
  router.push(file.folder_id ? `/drive/${file.folder_id}` : '/drive');
};

const loadDashboard = async () => {
  loading.value = true;
  try {
    const { data } = await api.get('/dashboard');
    stats.value = data.stats || stats.value;
    recentFiles.value = data.recent_files || [];
    recentActivities.value = data.recent_activities || [];
  } catch (e) {
    // Keep zeros on failure; UI still renders gracefully.
  } finally {
    loading.value = false;
  }
};

onMounted(loadDashboard);
</script>

<style scoped>
.dashboard { display: flex; flex-direction: column; gap: 1.25rem; }

.dash-hero { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.dash-date { font-size: .75rem; font-weight: 600; letter-spacing: .02em; text-transform: capitalize; color: var(--accent-primary); }
.dash-title { margin-top: .2rem; font-size: clamp(1.4rem, 2.6vw, 1.85rem); font-weight: 750; letter-spacing: -.03em; }
.dash-subtitle { margin-top: .25rem; font-size: .9rem; color: var(--text-secondary); }
.wave { display: inline-block; transform-origin: 70% 70%; animation: wave 2.4s ease-in-out infinite; }
@keyframes wave { 0%,60%,100% { transform: rotate(0); } 10% { transform: rotate(14deg); } 20% { transform: rotate(-8deg); } 30% { transform: rotate(14deg); } 40% { transform: rotate(-4deg); } 50% { transform: rotate(10deg); } }

.stat-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 1rem; }
@media (min-width: 640px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1024px) { .stat-grid { grid-template-columns: repeat(4, 1fr); } }

.stat-card { display: flex; align-items: center; gap: .85rem; padding: 1.1rem 1.15rem; background: var(--bg-card); border: 1px solid var(--separator); border-radius: var(--radius-card); box-shadow: var(--shadow-card); transition: transform .25s cubic-bezier(.2,.8,.2,1), box-shadow .25s ease; }
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,.08); }
.stat-icon { width: 46px; height: 46px; display: flex; align-items: center; justify-content: center; border-radius: 13px; flex-shrink: 0; }
.stat-meta h3 { font-size: 1.6rem; font-weight: 750; letter-spacing: -.03em; line-height: 1.1; }
.stat-meta p { font-size: .8rem; font-weight: 500; color: var(--text-secondary); }

.dash-columns { display: grid; grid-template-columns: 1fr; gap: 1.25rem; }
@media (min-width: 1024px) { .dash-columns { grid-template-columns: 2fr 1fr; align-items: start; } }
.dash-side { display: flex; flex-direction: column; gap: 1.25rem; }
.dash-panel { padding: 1.25rem; }

.panel-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.panel-head h2 { font-size: 1.02rem; font-weight: 700; }
.panel-link { font-size: .8rem; font-weight: 600; color: var(--accent-primary); }
.panel-link:hover { text-decoration: underline; }

.file-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .8rem; }
@media (min-width: 640px) { .file-grid { grid-template-columns: repeat(3, 1fr); } }
.file-tile { display: flex; flex-direction: column; align-items: flex-start; gap: .3rem; padding: .9rem; text-align: left; background: var(--bg-secondary); border: 1px solid var(--separator); border-radius: 14px; cursor: pointer; transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; }
.file-tile:hover { transform: translateY(-3px); border-color: rgba(0,122,255,.4); box-shadow: 0 8px 22px rgba(0,0,0,.07); }
.file-thumb { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 11px; margin-bottom: .35rem; }
.file-name { width: 100%; font-size: .82rem; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.file-sub { font-size: .7rem; color: var(--text-muted); }

.quick-actions { display: flex; flex-direction: column; gap: .55rem; }
.quick-btn { display: flex; align-items: center; gap: .7rem; width: 100%; min-height: 48px; padding: .6rem .8rem; font-size: .875rem; font-weight: 600; color: var(--text-primary); background: var(--bg-secondary); border: 1px solid var(--separator); border-radius: 12px; cursor: pointer; transition: background .15s ease, transform .12s ease, border-color .15s ease; }
.quick-btn:hover { background: var(--fill-tertiary); border-color: rgba(0,122,255,.35); }
.quick-btn:active { transform: scale(.98); }
.quick-ic { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 9px; flex-shrink: 0; }
.quick-chevron { width: 16px; height: 16px; margin-left: auto; color: var(--text-muted); transition: transform .18s ease; }
.quick-btn:hover .quick-chevron { transform: translateX(3px); color: var(--accent-primary); }

.activity-list { display: flex; flex-direction: column; gap: .1rem; }
.activity-row { display: flex; align-items: center; gap: .7rem; padding: .5rem 0; border-bottom: 1px solid var(--separator); }
.activity-row:last-child { border-bottom: 0; }
.activity-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.activity-text { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; flex: 1; min-width: 0; }
.activity-title { font-size: .82rem; font-weight: 500; color: var(--text-primary); }
.activity-time { font-size: .7rem; color: var(--text-muted); white-space: nowrap; }
.activity-empty { font-size: .82rem; color: var(--text-muted); padding: .5rem 0; }

/* Tones */
.tone-blue { background: rgba(0,122,255,.12); color: var(--accent-primary); }
.tone-amber { background: rgba(255,149,0,.14); color: #b25000; }
.tone-teal { background: rgba(50,173,230,.14); color: #0a84a8; }
.tone-green { background: rgba(52,199,89,.14); color: #248a3d; }
.tone-pink { background: rgba(255,45,85,.12); color: #d30f45; }
.tone-purple { background: rgba(175,82,222,.13); color: #8944ab; }
.tone-red { background: rgba(255,59,48,.11); color: #d70015; }
.activity-dot.tone-blue { background: var(--accent-primary); }
.activity-dot.tone-amber { background: var(--accent-warning); }
.activity-dot.tone-teal { background: var(--accent-tertiary); }
.activity-dot.tone-green { background: var(--accent-success); }
.activity-dot.tone-red { background: var(--accent-danger); }
.activity-dot.tone-purple { background: #af52de; }

/* Reveal + skeleton */
.reveal { opacity: 0; animation: reveal-up .5s cubic-bezier(.2,.8,.2,1) forwards; animation-delay: var(--d, 0ms); }
@keyframes reveal-up { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.stagger-enter-active { transition: opacity .4s ease, transform .4s cubic-bezier(.2,.8,.2,1); transition-delay: var(--d, 0ms); }
.stagger-enter-from { opacity: 0; transform: translateY(12px); }

.skeleton, .skeleton-row { position: relative; overflow: hidden; background: var(--fill-tertiary); border: 1px solid var(--separator); }
.file-tile.skeleton { height: 108px; border-radius: 14px; cursor: default; }
.skeleton-row { height: 20px; border-radius: 7px; border: 0; margin: .55rem 0; }
.skeleton::after, .skeleton-row::after { content: ''; position: absolute; inset: 0; transform: translateX(-100%); background: linear-gradient(90deg, transparent, rgba(255,255,255,.6), transparent); animation: shimmer 1.4s infinite; }
@keyframes shimmer { 100% { transform: translateX(100%); } }

@media (prefers-reduced-motion: reduce) {
  .reveal, .wave, .stagger-enter-active { animation: none; opacity: 1; transform: none; transition: none; }
}
</style>
