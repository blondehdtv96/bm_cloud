<template>
  <div class="h-full flex flex-col gap-6">
    <div class="page-header">
      <h1>
        <svg class="w-5 h-5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        Dibagikan dengan Saya
      </h1>
    </div>

    <div class="glass-card flex-1 overflow-hidden flex flex-col">
      <table class="table-modern">
        <thead>
          <tr>
            <th>Nama</th>
            <th>Dibagikan Oleh</th>
            <th class="hidden sm:table-cell">Tanggal</th>
            <th class="text-right">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="i in 5" :key="i" class="group">
            <td>
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-500/15 flex items-center justify-center text-blue-400 flex-shrink-0">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                </div>
                <span class="font-medium">Design_Assets_v{{i}}.zip</span>
              </div>
            </td>
            <td>
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0">AD</div>
                <span class="text-secondary text-sm">Admin Sistem</span>
              </div>
            </td>
            <td class="text-secondary hidden sm:table-cell">2{{i}} Okt 2023</td>
            <td class="text-right opacity-0 group-hover:opacity-100 transition-opacity">
              <button class="btn-icon text-indigo-400 hover:bg-indigo-400/20" title="Unduh">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
</script>
