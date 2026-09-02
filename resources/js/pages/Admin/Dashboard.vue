<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

// Mock Data Statistik Inventaris
const stats = [
    {
        title: 'Total Barang',
        value: '1,248',
        change: '+12% bulan ini',
        isPositive: true,
        icon: 'archive',
    },
    {
        title: 'Barang Dipinjam',
        value: '84',
        change: '15 perlu persetujuan',
        isPositive: false,
        icon: 'hand-raised',
    },
    {
        title: 'Kondisi Rusak / Perbaikan',
        value: '12',
        change: '-2 dari minggu lalu',
        isPositive: true,
        icon: 'wrench-screwdriver',
    },
];
</script>

<template>
    <Head title="Dashboard Admin - Sistem Inventory" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="(stat, index) in stats"
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
                            v-else-if="stat.icon === 'hand-raised'"
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
                                d="M10.05 4.575a1.5 1.5 0 1 0-3 0v6.958l-1.025-.56a1.5 1.5 0 0 0-1.928.532l-.4.67a1.5 1.5 0 0 0 .426 2.016l5.77 3.905A6 6 0 0 0 13.344 19.5H16.5a6 6 0 0 0 6-6V9a1.5 1.5 0 1 0-3 0v.75a1.5 1.5 0 0 0-3 0V4.575Z"
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
                                d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.654-4.654m0 0a3.001 3.001 0 0 1 4.242-4.242m-4.242 4.242 3.03-2.496"
                            />
                        </svg>
                    </div>
                </div>

                <div class="mt-4">
                    <div
                        class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        {{ stat.value }}
                    </div>
                    <div
                        class="mt-1 flex items-center gap-1 text-xs font-medium"
                        :class="
                            stat.isPositive
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-[#f53003] dark:text-[#FF4433]'
                        "
                    >
                        <span>{{ stat.change }}</span>
                    </div>
                </div>

                <div
                    class="absolute bottom-0 left-0 h-[2px] w-full bg-[#f53003]/20 opacity-0 transition group-hover:opacity-100 dark:bg-[#FF4433]/30"
                ></div>
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
                        Aktivitas Peminjaman Terbaru
                    </h2>
                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        Daftar riwayat dan pengajuan peminjaman barang terkini.
                    </p>
                </div>
                <button
                    class="rounded-lg bg-[#1b1b18] px-3.5 py-1.5 text-xs font-medium text-white transition hover:bg-black dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white"
                >
                    Lihat Semua Data
                </button>
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
                                d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M3.75 4.5h16.5m-16.5 3.75h16.5"
                            />
                        </svg>
                    </div>
                    <h3
                        class="mt-3 text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Sistem Inventaris Universitas Al-Khairiyah
                    </h3>
                    <p
                        class="mt-1 max-w-sm text-xs text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Pilih menu di navigasi samping untuk mengelola inventaris barang, persetujuan peminjaman, atau laporan berkala.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
