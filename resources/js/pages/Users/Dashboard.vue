<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard Saya',
                href: dashboard(),
            },
        ],
    },
});

// Mock Data Statistik Peminjaman User
const userStats = [
    {
        title: 'Sedang Dipinjam',
        value: '2 Barang',
        description: 'Batas kembali: 3 hari lagi',
        statusColor: 'amber',
        icon: 'archive',
    },
    {
        title: 'Menunggu Persetujuan',
        value: '1 Pengajuan',
        description: 'Prosedur verifikasi admin',
        statusColor: 'blue',
        icon: 'clock',
    },
    {
        title: 'Riwayat Peminjaman',
        value: '14 Selesai',
        description: 'Total pengembalian tepat waktu',
        statusColor: 'emerald',
        icon: 'check-circle',
    },
];
</script>

<template>
    <Head title="Dashboard Saya - Sistem Inventory" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="relative overflow-hidden rounded-xl border border-black/5 bg-gradient-to-r from-white via-white to-[#fff2f2] p-6 shadow-sm dark:border-white/10 dark:from-[#161615] dark:via-[#161615] dark:to-[#1D0002]"
        >
            <div class="relative z-10 max-w-2xl">
                <span
                    class="inline-block rounded-md bg-[#f53003]/10 px-2.5 py-1 text-xs font-semibold text-[#f53003] dark:bg-[#FF4433]/20 dark:text-[#FF4433]"
                >
                    Portal Peminjaman Universitas Al-Khairiyah
                </span>
                <h1
                    class="mt-3 text-xl font-bold tracking-tight text-[#1b1b18] sm:text-2xl dark:text-[#EDEDEC]"
                >
                    Selamat Datang di Sistem Inventaris Kampus
                </h1>
                <p
                    class="mt-1.5 text-xs leading-relaxed text-[#706f6c] sm:text-sm dark:text-[#A1A09A]"
                >
                    Cari barang yang Anda butuhkan untuk kegiatan akademik atau organisasi, lalu ajukan peminjaman secara mandiri dengan cepat.
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button
                        class="inline-flex items-center gap-2 rounded-lg bg-[#1b1b18] px-4 py-2 text-xs font-medium text-white transition hover:bg-black focus:outline-none focus:ring-2 focus:ring-[#1b1b18] dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 4.5v15m7.5-7.5h-15"
                            />
                        </svg>
                        <span>Ajukan Peminjaman Baru</span>
                    </button>
                    <button
                        class="rounded-lg border border-[#e3e3e0] bg-transparent px-4 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-[#f5f5f4] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:bg-[#262624]"
                    >
                        Cari Katalog Barang
                    </button>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="(stat, index) in userStats"
                :key="index"
                class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        {{ stat.title }}
                    </span>
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff2f2] text-[#f53003] transition group-hover:bg-[#f53003] group-hover:text-white dark:bg-[#1D0002] dark:text-[#FF4433] dark:group-hover:bg-[#FF4433] dark:group-hover:text-white"
                    >
                        <svg
                            v-if="stat.icon === 'archive'"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-5 w-5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"
                            />
                        </svg>
                        <svg
                            v-else-if="stat.icon === 'clock'"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-5 w-5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                            />
                        </svg>
                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-5 w-5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                            />
                        </svg>
                    </div>
                </div>

                <div class="mt-4">
                    <div
                        class="text-2xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        {{ stat.value }}
                    </div>
                    <div class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        {{ stat.description }}
                    </div>
                </div>
            </div>
        </div>

        <div
            class="flex flex-1 flex-col rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]"
        >
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2
                        class="text-lg font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Status Peminjaman Saya
                    </h2>
                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        Pantau status persetujuan dan pengembalian barang yang Anda gunakan.
                    </p>
                </div>
            </div>

            <div class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-[#e3e3e0] bg-[#FDFDFC] p-8 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]">
                <div class="text-center">
                    <div
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#fff2f2] text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-6 w-6"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12h3.75M9 15h3.75M9 18h3.75m3-6h.008v.008H15.75V12Zm0 3h.008v.008H15.75V15Zm0 3h.008v.008H15.75V18M6 10.5h12a2.25 2.25 0 0 1 2.25 2.25v6a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25v-6A2.25 2.25 0 0 1 6 10.5Z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="mt-3 text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Belum Ada Aktivitas Peminjaman Aktif
                    </h3>
                    <p
                        class="mt-1 max-w-sm text-xs text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Silakan telusuri katalog barang kampus dan buat permohonan peminjaman baru sesuai kebutuhan Anda.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
