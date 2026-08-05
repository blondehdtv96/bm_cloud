<template>
  <div class="dashboard">
    <div class="page-header reveal" style="--d: 0ms">
      <div>
        <h1>Ringkasan Admin</h1>
        <p>Pantau kondisi sistem, pengguna, dan penyimpanan secara menyeluruh.</p>
      </div>
      <span class="badge badge-indigo">Super Admin</span>
    </div>

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

    <!-- Panels -->
    <section class="admin-columns">
      <!-- Storage per role -->
      <div class="glass-card dash-panel reveal" style="--d: 360ms">
        <div class="panel-head">
          <h2>Penyimpanan per Peran</h2>
          <span class="panel-note">{{ formatSize(stats.storage_used) }} total</span>
        </div>

        <div v-if="loading" class="role-list">
          <div v-for="n in 4" :key="n" class="skeleton-row"></div>
        </div>

        <div v-else-if="roleUsage.length" class="role-list">
          <div v-for="(role, i) in roleUsage" :key="role.name" class="role-row reveal" :style="`--d: ${i * 70}ms`">
            <div class="role-top">
              <span class="role-name">{{ role.name }}</span>
              <span class="role-value">{{ formatSize(role.storage) }}</span>
            </div>
            <div class="role-track">
              <div class="role-bar" :class="barTone(i)" :style="`width: ${rolePercent(role)}%`"></div>
            </div>
            <span class="role-sub">{{ role.users_count }} pengguna</span>
          </div>
        </div>

        <p v-else class="panel-empty">Belum ada data penyimpanan.</p>
      </div>

      <!-- System info -->
      <div class="glass-card dash-panel reveal" style="--d: 420ms">
        <div class="panel-head"><h2>Informasi Sistem</h2></div>
        <div class="info-list">
          <div class="info-row">
            <span class="info-label">Pengguna Aktif (30 hari)</span>
            <span class="info-value">{{ formatNumber(stats.active_users) }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Total Folder</span>
            <span class="info-value">{{ formatNumber(stats.total_folders) }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Ruang Disk Tersedia</span>
            <span class="info-value info-ok">{{ formatSize(stats.storage_available) }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Versi Laravel</span>
            <span class="info-value">{{ laravelVersion }}</span>
          </div>
        </div>
        <button class="btn btn-secondary w-full" @click="$router.push('/admin/backup')">Kelola Backup</button>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue';
import { api } from '../../composables/useApi';

const loading = ref(true);
const stats = ref({ total_users: 0, total_files: 0, total_folders: 0, storage_used: 0, storage_available: 0, active_users: 0, storage_by_role: [] });
const laravelVersion = ref('12.x');

const svg = (children, w = 2) => () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': w }, children);
const UsersIcon = svg([h('path', { d: 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2' }), h('circle', { cx: 9, cy: 7, r: 4 }), h('path', { d: 'M23 21v-2a4 4 0 0 0-3-3.87' }), h('path', { d: 'M16 3.13a4 4 0 0 1 0 7.75' })]);
const FileIcon = svg([h('path', { d: 'M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z' }), h('polyline', { points: '13 2 13 9 20 9' })]);
const DriveIcon = svg([h('ellipse', { cx: 12, cy: 5, rx: 9, ry: 3 }), h('path', { d: 'M21 12c0 1.66-4 3-9 3s-9-1.34-9-3' }), h('path', { d: 'M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5' })]);
const PulseIcon = svg([h('path', { d: 'M22 12h-4l-3 9L9 3l-3 9H2' })]);

const statCards = computed(() => [
  { key: 'users', label: 'Total Pengguna', display: formatNumber(stats.value.total_users), icon: UsersIcon, tone: 'tone-blue' },
  { key: 'files', label: 'Total File', display: formatNumber(stats.value.total_files), icon: FileIcon, tone: 'tone-purple' },
  { key: 'storage', label: 'Penyimpanan Terpakai', display: formatSize(stats.value.storage_used), icon: DriveIcon, tone: 'tone-teal' },
  { key: 'active', label: 'Pengguna Aktif', display: formatNumber(stats.value.active_users), icon: PulseIcon, tone: 'tone-green' },
]);

const roleUsage = computed(() => (stats.value.storage_by_role || []).filter(r => r.users_count > 0 || r.storage > 0));
const maxRoleStorage = computed(() => Math.max(1, ...roleUsage.value.map(r => r.storage)));
const rolePercent = (role) => Math.max(3, Math.round((role.storage / maxRoleStorage.value) * 100));
const barTones = ['bar-blue', 'bar-purple', 'bar-teal', 'bar-green', 'bar-amber'];
const barTone = (i) => barTones[i % barTones.length];

const formatNumber = (n) => new Intl.NumberFormat('id-ID').format(n || 0);
const formatSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const loadStats = async () => {
  loading.value = true;
  try {
    const { data } = await api.get('/admin/stats');
    stats.value = { ...stats.value, ...data };
  } catch (e) {
    // Render gracefully with zeros on failure.
  } finally {
    loading.value = false;
  }
};

onMounted(loadStats);
</script>

<style scoped>
.dashboard { display: flex; flex-direction: column; gap: 1.25rem; }

.stat-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 1rem; }
@media (min-width: 640px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1024px) { .stat-grid { grid-template-columns: repeat(4, 1fr); } }

.stat-card { display: flex; align-items: center; gap: .85rem; padding: 1.1rem 1.15rem; background: var(--bg-card); border: 1px solid var(--separator); border-radius: var(--radius-card); box-shadow: var(--shadow-card); transition: transform .25s cubic-bezier(.2,.8,.2,1), box-shadow .25s ease; }
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,.08); }
.stat-icon { width: 46px; height: 46px; display: flex; align-items: center; justify-content: center; border-radius: 13px; flex-shrink: 0; }
.stat-meta h3 { font-size: 1.6rem; font-weight: 750; letter-spacing: -.03em; line-height: 1.1; }
.stat-meta p { font-size: .8rem; font-weight: 500; color: var(--text-secondary); }

.admin-columns { display: grid; grid-template-columns: 1fr; gap: 1.25rem; }
@media (min-width: 1024px) { .admin-columns { grid-template-columns: 3fr 2fr; align-items: start; } }
.dash-panel { padding: 1.25rem; }
.panel-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.1rem; }
.panel-head h2 { font-size: 1.02rem; font-weight: 700; }
.panel-note { font-size: .75rem; font-weight: 600; color: var(--text-muted); }
.panel-empty { font-size: .82rem; color: var(--text-muted); }

.role-list { display: flex; flex-direction: column; gap: 1rem; }
.role-row { display: flex; flex-direction: column; gap: .35rem; }
.role-top { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; }
.role-name { font-size: .85rem; font-weight: 600; color: var(--text-primary); }
.role-value { font-size: .82rem; font-weight: 600; color: var(--text-secondary); }
.role-track { height: 8px; width: 100%; border-radius: 999px; background: var(--fill-primary); overflow: hidden; }
.role-bar { height: 100%; border-radius: inherit; width: 0; animation: grow-bar .8s cubic-bezier(.2,.8,.2,1) forwards; }
.role-sub { font-size: .7rem; color: var(--text-muted); }
@keyframes grow-bar { from { transform: scaleX(0); transform-origin: left; } to { transform: scaleX(1); transform-origin: left; } }

.bar-blue { background: var(--accent-primary); }
.bar-purple { background: #af52de; }
.bar-teal { background: var(--accent-tertiary); }
.bar-green { background: var(--accent-success); }
.bar-amber { background: var(--accent-warning); }

.info-list { display: flex; flex-direction: column; margin-bottom: 1rem; }
.info-row { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .7rem 0; border-bottom: 1px solid var(--separator); }
.info-row:last-child { border-bottom: 0; }
.info-label { font-size: .82rem; color: var(--text-secondary); }
.info-value { font-size: .82rem; font-weight: 600; color: var(--text-primary); }
.info-ok { color: var(--accent-success); }

.tone-blue { background: rgba(0,122,255,.12); color: var(--accent-primary); }
.tone-purple { background: rgba(175,82,222,.13); color: #8944ab; }
.tone-teal { background: rgba(50,173,230,.14); color: #0a84a8; }
.tone-green { background: rgba(52,199,89,.14); color: #248a3d; }

.reveal { opacity: 0; animation: reveal-up .5s cubic-bezier(.2,.8,.2,1) forwards; animation-delay: var(--d, 0ms); }
@keyframes reveal-up { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.skeleton-row { height: 42px; border-radius: 10px; background: var(--fill-tertiary); position: relative; overflow: hidden; margin-bottom: .3rem; }
.skeleton-row::after { content: ''; position: absolute; inset: 0; transform: translateX(-100%); background: linear-gradient(90deg, transparent, rgba(255,255,255,.6), transparent); animation: shimmer 1.4s infinite; }
@keyframes shimmer { 100% { transform: translateX(100%); } }

@media (prefers-reduced-motion: reduce) {
  .reveal, .role-bar { animation: none; opacity: 1; transform: none; }
}
</style>
