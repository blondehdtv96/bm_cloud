<template>
  <div class="max-w-2xl mx-auto flex flex-col gap-6">
    <!-- Profile summary -->
    <div class="glass-card p-8 flex flex-col items-center text-center">
      <div class="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-2xl font-bold text-white shadow-lg mb-4">
        {{ initials }}
      </div>
      <h2 class="text-xl font-bold text-primary">{{ authStore.user?.name || 'Nama Pengguna' }}</h2>
      <p class="text-secondary text-sm mb-3">{{ authStore.user?.email || 'user@example.com' }}</p>
      <span class="badge badge-indigo uppercase tracking-wide">
        {{ authStore.userRole }}
      </span>
    </div>

    <!-- Storage -->
    <div class="glass-card p-6">
      <h3 class="font-semibold text-base mb-4">Penggunaan Penyimpanan</h3>
      <StorageUsage />
    </div>

    <!-- Account settings -->
    <div class="glass-card p-6">
      <h3 class="font-semibold text-base mb-1">Ubah Kata Sandi</h3>
      <p class="text-sm text-secondary mb-5">Gunakan kata sandi yang kuat dan tidak dipakai di tempat lain.</p>
      <form @submit.prevent class="flex flex-col gap-4">
        <div class="form-group mb-0">
          <label class="form-label">Kata Sandi Saat Ini</label>
          <input type="password" class="form-control" placeholder="••••••••">
        </div>
        <div class="form-group mb-0">
          <label class="form-label">Kata Sandi Baru</label>
          <input type="password" class="form-control" placeholder="••••••••">
        </div>
        <div class="form-group mb-0">
          <label class="form-label">Konfirmasi Kata Sandi Baru</label>
          <input type="password" class="form-control" placeholder="••••••••">
        </div>
        <button class="btn btn-primary self-start mt-1">Perbarui Kata Sandi</button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import StorageUsage from '../components/ui/StorageUsage.vue';

const authStore = useAuthStore();
const initials = computed(() => {
  const n = authStore.user?.name || 'U';
  return n.substring(0, 2).toUpperCase();
});
</script>
