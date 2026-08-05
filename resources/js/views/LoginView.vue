<template>
  <div class="login-page">
    <!-- Brand panel -->
    <div class="brand-panel">
      <div class="brand-content">
        <div class="brand-logo">
          <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17.5 19C19.9853 19 22 16.9853 22 14.5C22 12.1388 20.1873 10.2016 17.8778 10.0191C17.433 6.62104 14.5262 4 11 4C7.13401 4 4 7.13401 4 11C4 11.2339 4.01146 11.4651 4.03395 11.6925C1.76189 12.0674 0 14.0768 0 16.5C0 19.5376 2.46243 22 5.5 22H17.5V19Z" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>BM Cloud</span>
        </div>

        <h1 class="brand-title">Penyimpanan file internal<br>yang aman untuk sekolah.</h1>
        <p class="brand-subtitle">
          Kelola, bagikan, dan pantau seluruh dokumen sekolah dalam satu platform terpusat.
        </p>

        <ul class="brand-features">
          <li>
            <span class="feature-icon">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </span>
            Akses berbasis peran &amp; izin
          </li>
          <li>
            <span class="feature-icon">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </span>
            Riwayat versi &amp; aktivitas file
          </li>
          <li>
            <span class="feature-icon">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </span>
            Berbagi tautan yang terkontrol
          </li>
        </ul>
      </div>

      <p class="brand-footer">© {{ currentYear }} SMK BM &mdash; Penyimpanan Cloud Internal</p>
    </div>

    <!-- Form panel -->
    <div class="form-panel">
      <div class="form-panel-inner">
        <div class="mobile-brand">
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17.5 19C19.9853 19 22 16.9853 22 14.5C22 12.1388 20.1873 10.2016 17.8778 10.0191C17.433 6.62104 14.5262 4 11 4C7.13401 4 4 7.13401 4 11C4 11.2339 4.01146 11.4651 4.03395 11.6925C1.76189 12.0674 0 14.0768 0 16.5C0 19.5376 2.46243 22 5.5 22H17.5V19Z" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>BM Cloud</span>
        </div>

        <div class="form-card">
          <div class="form-header">
            <h2>Masuk ke akun Anda</h2>
            <p>Gunakan email dan kata sandi yang terdaftar untuk melanjutkan.</p>
          </div>

          <div v-if="error" class="alert-error">
            <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span>{{ error }}</span>
          </div>

          <form @submit.prevent="handleLogin" class="login-form" novalidate>
            <div class="form-group">
              <label for="email" class="form-label">Email</label>
              <div class="input-wrap">
                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                <input
                  id="email"
                  v-model.trim="form.email"
                  type="email"
                  autocomplete="email"
                  required
                  class="form-control"
                  placeholder="nama@smkbm.sch.id"
                />
              </div>
            </div>

            <div class="form-group">
              <div class="label-row">
                <label for="password" class="form-label">Kata Sandi</label>
              </div>
              <div class="input-wrap">
                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="current-password"
                  required
                  class="form-control"
                  placeholder="Masukkan kata sandi"
                />
                <button
                  type="button"
                  class="toggle-visibility"
                  @click="showPassword = !showPassword"
                  :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                >
                  <svg v-if="!showPassword" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                  <svg v-else class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                </button>
              </div>
            </div>

            <button type="submit" class="btn btn-primary submit-btn" :disabled="loading">
              <svg v-if="loading" class="spinner" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              <span>{{ loading ? 'Memproses...' : 'Masuk' }}</span>
            </button>
          </form>
        </div>

        <p class="help-text">
          Lupa kata sandi atau butuh bantuan? Hubungi administrator ICT sekolah.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({ email: '', password: '' });
const showPassword = ref(false);
const loading = ref(false);
const error = ref('');

const currentYear = computed(() => new Date().getFullYear());

const handleLogin = async () => {
  loading.value = true;
  error.value = '';

  const result = await authStore.login(form.email, form.password);

  if (result.success) {
    router.push('/dashboard');
  } else {
    error.value = result.message || 'Email atau kata sandi salah';
  }

  loading.value = false;
};
</script>

<style scoped>
.login-page { min-height: 100vh; display: flex; background: var(--bg-primary); }
.brand-panel {
  display: none;
  position: relative;
  flex-direction: column;
  justify-content: space-between;
  width: 45%;
  padding: clamp(2.5rem, 5vw, 4.5rem);
  overflow: hidden;
  background: linear-gradient(155deg, #0a84ff 0%, #007aff 52%, #0062cc 100%);
  color: #fff;
}
.brand-panel::before, .brand-panel::after {
  content: '';
  position: absolute;
  border-radius: 50%;
  background: rgba(255,255,255,.10);
  pointer-events: none;
}
.brand-panel::before { width: 28rem; height: 28rem; right: -14rem; top: -12rem; }
.brand-panel::after { width: 20rem; height: 20rem; left: -10rem; bottom: -10rem; }
.brand-content { position: relative; z-index: 1; max-width: 440px; margin: auto 0; }
.brand-logo { display: flex; align-items: center; gap: .65rem; color: #fff; font-weight: 700; font-size: 1.15rem; margin-bottom: 3.25rem; }
.brand-logo svg { color: #fff; }
.brand-title { font-size: clamp(2rem, 3.3vw, 2.75rem); line-height: 1.15; font-weight: 750; letter-spacing: -.035em; color: #fff; margin-bottom: 1.2rem; }
.brand-subtitle { color: rgba(255,255,255,.78); font-size: 1rem; line-height: 1.6; margin-bottom: 2.25rem; }
.brand-features { list-style: none; display: flex; flex-direction: column; gap: .95rem; }
.brand-features li { display: flex; align-items: center; gap: .75rem; color: rgba(255,255,255,.92); font-size: .9rem; }
.feature-icon { display: inline-flex; align-items: center; justify-content: center; width: 1.55rem; height: 1.55rem; border-radius: 50%; background: rgba(255,255,255,.16); color: #fff; flex-shrink: 0; }
.brand-footer { position: relative; z-index: 1; color: rgba(255,255,255,.62); font-size: .78rem; }
.form-panel { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem 1.5rem; background: var(--bg-primary); }
.form-panel-inner { width: 100%; max-width: 410px; }
.mobile-brand { display: flex; align-items: center; justify-content: center; gap: .55rem; color: var(--text-primary); font-weight: 700; font-size: 1.1rem; margin-bottom: 2rem; }
.mobile-brand svg { color: var(--accent-primary); }
.form-card { background: rgba(255,255,255,.92); border: 1px solid var(--separator); border-radius: 20px; padding: clamp(1.5rem, 4vw, 2rem); box-shadow: var(--shadow-card); }
.form-header { margin-bottom: 1.5rem; }
.form-header h2 { font-size: 1.45rem; font-weight: 700; letter-spacing: -.025em; margin-bottom: .4rem; }
.form-header p { font-size: .875rem; color: var(--text-secondary); margin: 0; }
.alert-error { margin-bottom: 1.25rem; }
.login-form { display: flex; flex-direction: column; gap: 1.05rem; }
.form-group { margin-bottom: 0; }
.label-row { display: flex; align-items: center; justify-content: space-between; }
.input-wrap { position: relative; }
.input-icon { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); width: 1.05rem; height: 1.05rem; color: var(--text-muted); pointer-events: none; }
.input-wrap .form-control { padding-left: 2.6rem; }
.input-wrap:has(.toggle-visibility) .form-control { padding-right: 2.8rem; }
.toggle-visibility { position: absolute; right: .55rem; top: 50%; transform: translateY(-50%); width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border: 0; border-radius: 50%; color: var(--text-muted); background: transparent; cursor: pointer; }
.toggle-visibility:hover { color: var(--accent-primary); background: var(--fill-tertiary); }
.submit-btn { width: 100%; margin-top: .4rem; font-size: .95rem; }
.spinner { width: 1.1rem; height: 1.1rem; animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.help-text { text-align: center; font-size: .8rem; color: var(--text-muted); margin-top: 1.5rem; }
@media (min-width: 992px) { .brand-panel { display: flex; } .mobile-brand { display: none; } }
@media (max-width: 480px) { .form-panel { padding: 1rem; } .form-card { border-radius: 18px; } }
</style>
