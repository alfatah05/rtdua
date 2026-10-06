<template>
  <div>
    <header class="flex items-center gap-3 mb-5">
      <button type="button" class="min-h-[44px] px-3 -ml-2 font-semibold text-[var(--g)]" @click="$router.back()">← Kembali</button>
      <h1 class="text-xl font-extrabold m-0">Detail</h1>
    </header>
    <div class="bg-[var(--card)] border border-[var(--line)] rounded-[20px] p-5 shadow-[var(--sh)]">
      <p class="text-[13px] text-[var(--mut)] mb-2">3 Oktober 2026</p>
      <h2 class="text-[17px] font-bold mb-3">Kerja bakti membersihkan selokan</h2>
      <p class="text-[15px] leading-relaxed m-0">
        Hari Minggu, 12 Oktober pukul 07.00. Semua warga diharapkan hadir dan membawa alat kebersihan.
        Titik kumpul di depan balai RT. Terima kasih atas partisipasinya.
      </p>
    </div>
  </div>
</template>
