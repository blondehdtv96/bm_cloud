<template>
  <div class="h-full flex flex-col gap-6">
    <div class="page-header">
      <h1>
        <svg class="w-5 h-5 text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        Sampah
      </h1>
      <button class="btn btn-danger text-sm">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        Kosongkan Sampah
      </button>
    </div>

    <div class="bg-amber-500/10 border border-amber-500/30 rounded-lg p-3 text-sm text-amber-200/80 flex items-start gap-2">
      <svg class="w-5 h-5 flex-shrink-0 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
      Item di sampah akan otomatis terhapus setelah 30 hari.
    </div>

    <div class="glass-card flex-1 overflow-hidden flex flex-col">
      <table class="table-modern">
        <thead>
          <tr>
            <th>Nama</th>
            <th class="hidden sm:table-cell">Tanggal Dihapus</th>
            <th class="text-right">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="i in 3" :key="i" class="group">
            <td>
              <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                <span class="font-medium text-gray-300">Old_Report_0{{i}}.xlsx</span>
              </div>
            </td>
            <td class="text-secondary hidden sm:table-cell">{{ i }} Okt 2023</td>
            <td class="text-right">
              <div class="flex justify-end gap-1">
                <button class="btn-icon text-emerald-400 hover:bg-emerald-400/20" title="Pulihkan">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 14 12 11 15 14"></polyline><path d="M12 11v8"></path><path d="M20 21v-2a4 4 0 0 0-4-4h-8a4 4 0 0 0-4 4v2"></path></svg>
                </button>
                <button class="btn-icon text-red-400 hover:bg-red-400/20" title="Hapus Permanen">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
</script>
