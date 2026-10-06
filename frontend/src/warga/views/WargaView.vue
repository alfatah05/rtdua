<template>
  <div>
    <h1 class="text-[22px] font-extrabold mb-4 text-[var(--text)]">Warga</h1>
    <p class="text-[13px] text-[var(--mut)] mb-4">Daftar nama dan alamat keluarga (data dummy)</p>
    <div class="space-y-2">
      <div
        v-for="k in keluarga"
        :key="k.id"
        class="flex items-center gap-3 bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-4 shadow-[var(--sh)]"
      >
        <div class="w-11 h-11 rounded-full bg-[var(--gd)] text-[var(--gm)] grid place-items-center font-bold text-sm flex-none">
          {{ k.inisial }}
        </div>
        <div class="min-w-0">
          <p class="font-bold text-[15px]">{{ k.nama }}</p>
          <p class="text-[13px] text-[var(--mut)]">{{ k.alamat }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const keluarga = [
  { id: 1, nama: 'Budi Santoso', alamat: 'AB2-22', inisial: 'BS' },
  { id: 2, nama: 'Siti Aminah', alamat: 'AB2-22a', inisial: 'SA' },
  { id: 3, nama: 'Andi Wijaya', alamat: 'AB1-05', inisial: 'AW' },
  { id: 4, nama: 'Rina Marlina', alamat: 'AB11-12', inisial: 'RM' },
  { id: 5, nama: 'Joko Prasetyo', alamat: 'AB12-03', inisial: 'JP' },
]
</script>
