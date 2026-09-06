```vue
<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import RoomTypeEditModal from './RoomTypeEditModal.vue';

import {
    update as updateRoomType,
    destroy as destroyRoomType,
} from '@/actions/App/Http/Controllers/DashboardAdminController';

interface RoomType {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    rooms_count?: number;
}

const props = withDefaults(
    defineProps<{
        roomTypes?: RoomType[];
    }>(),
    {
        roomTypes: () => [],
    },
);

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



const isRoomTypeModalOpen = ref(false);
const isRoomTypeProcessing = ref(false);
const editingRoomType = ref<RoomType | null>(null);

const openEditRoomType = (roomType: RoomType) => {
    editingRoomType.value = {
        ...roomType,
    };

    isRoomTypeModalOpen.value = true;
};

const closeRoomTypeModal = () => {
    if (isRoomTypeProcessing.value) {
        return;
    }

    isRoomTypeModalOpen.value = false;
    editingRoomType.value = null;
};

const handleSaveRoomType = (data: {
    name: string;
    slug: string;
    description: string | null;
}) => {
    const roomType = editingRoomType.value;

    if (!roomType) {
        return;
    }

    isRoomTypeProcessing.value = true;

    router.put(updateRoomType.url(roomType.id), data, {
        preserveScroll: true,

        onSuccess: () => {
            // Tutup modal SETELAH update berhasil
            isRoomTypeModalOpen.value = false;
            editingRoomType.value = null;
        },

        onError: (errors) => {
            console.error('Gagal update jenis ruangan:', errors);
        },

        onFinish: () => {
            isRoomTypeProcessing.value = false;
        },
    });
};



const isConfirmModalOpen = ref(false);
const confirmMessage = ref('');
const confirmAction = ref<(() => void) | null>(null);
const isDeleting = ref(false);

const confirmDeleteRoomType = (roomType: RoomType) => {
    if (isDeleting.value) {
        return;
    }

    confirmMessage.value =
        `Hapus jenis ruangan "${roomType.name}"? ` +
        `Jenis ruangan yang masih dipakai oleh ruangan tidak dapat dihapus.`;

    confirmAction.value = () => {
        if (isDeleting.value) {
            return;
        }

        isDeleting.value = true;

        router.delete(destroyRoomType.url(roomType.id), {
            preserveScroll: true,

            onSuccess: () => {
                isConfirmModalOpen.value = false;
                confirmAction.value = null;
            },

            onError: (errors) => {
                console.error('Gagal menghapus jenis ruangan:', errors);
            },

            onFinish: () => {
                isDeleting.value = false;
            },
        });
    };

    isConfirmModalOpen.value = true;
};

const cancelDelete = () => {
    if (isDeleting.value) {
        return;
    }

    isConfirmModalOpen.value = false;
    confirmAction.value = null;
};

const executeDelete = () => {
    if (!confirmAction.value || isDeleting.value) {
        return;
    }

    confirmAction.value();
};
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
                        <!-- Archive -->
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

                        <!-- Hand -->
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

                        <!-- Wrench -->
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

    

        <div class="grid flex-1 gap-6 lg:grid-cols-3">
           

            <div
                class="flex flex-col rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615] lg:col-span-2"
            >
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2
                            class="text-lg font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Aktivitas Peminjaman Terbaru
                        </h2>

                        <p
                            class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Daftar riwayat dan pengajuan peminjaman barang
                            terkini.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg bg-[#1b1b18] px-3.5 py-1.5 text-xs font-medium text-white transition hover:bg-black dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white"
                    >
                        Lihat Semua Data
                    </button>
                </div>

                <div
                    class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-[#e3e3e0] bg-[#FDFDFC] p-8 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                >
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
                            Pilih menu di navigasi samping untuk mengelola
                            inventaris barang, persetujuan peminjaman, atau
                            laporan berkala.
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="flex min-h-0 flex-col rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]"
            >
                <div class="mb-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2
                                class="text-sm font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Jenis Ruangan
                            </h2>

                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Kelola cepat master data jenis ruangan.
                            </p>
                        </div>

                        <span
                            class="rounded-full bg-[#f5f5f3] px-2 py-1 text-[10px] font-semibold text-[#706f6c] dark:bg-[#20201e] dark:text-[#A1A09A]"
                        >
                            {{ props.roomTypes.length }}
                        </span>
                    </div>
                </div>

                <!-- Empty -->
                <div
                    v-if="props.roomTypes.length === 0"
                    class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-[#e3e3e0] p-6 text-center text-xs text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]"
                >
                    <div>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="mx-auto h-8 w-8 opacity-50"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M20.25 7.5 12 3.75 3.75 7.5m16.5 0v9L12 20.25l-8.25-3.75v-9m16.5 0L12 11.25m0 0L3.75 7.5M12 11.25v9"
                            />
                        </svg>

                        <p class="mt-2">
                            Belum ada jenis ruangan.
                        </p>
                    </div>
                </div>

                <!-- List -->
                <ul
                    v-else
                    class="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1"
                >
                    <li
                        v-for="roomType in props.roomTypes"
                        :key="roomType.id"
                        class="flex items-center justify-between gap-2 rounded-lg border border-[#e3e3e0] bg-[#FDFDFC] px-3 py-2 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                    >
                        <div class="min-w-0">
                            <p
                                class="truncate text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                :title="roomType.name"
                            >
                                {{ roomType.name }}
                            </p>

                            <p
                                class="truncate text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{ roomType.rooms_count ?? 0 }}
                                {{
                                    (roomType.rooms_count ?? 0) === 1
                                        ? 'ruangan'
                                        : 'ruangan'
                                }}
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <!-- EDIT -->
                            <button
                                type="button"
                                @click="openEditRoomType(roomType)"
                                class="rounded-md p-1.5 text-[#706f6c] transition hover:bg-slate-100 hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#20201e] dark:hover:text-[#EDEDEC]"
                                title="Edit"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="h-3.5 w-3.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                    />
                                </svg>
                            </button>

                            <!-- DELETE -->
                            <button
                                type="button"
                                @click="confirmDeleteRoomType(roomType)"
                                class="rounded-md p-1.5 text-[#706f6c] transition hover:bg-red-50 hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:bg-[#1D0002] dark:hover:text-[#FF4433]"
                                title="Hapus"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="h-3.5 w-3.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m0 0L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m0 0a48.11 48.11 0 0 0-3.478-.397m-7.5 0a48.108 48.108 0 0 1-3.478.397m3.478-.397v-.916c0-1.18.91-2.164 2.09-2.201a51.964 51.964 0 0 1 3.32 0c1.18.037 2.09 1.022 2.09 2.201v.916"
                                    />
                                </svg>
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <RoomTypeEditModal
        :show="isRoomTypeModalOpen"
        :room-type="editingRoomType"
        :processing="isRoomTypeProcessing"
        @close="closeRoomTypeModal"
        @submit="handleSaveRoomType"
    />

    <div
        v-if="isConfirmModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
    >
        <div
            class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-[#161615]"
        >
            <!-- Icon -->
            <div
                class="flex h-10 w-10 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-400"
            >
                <svg
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
                        d="M12 9v3.75m0 3.75h.007v.008H12v-.008ZM10.34 3.94 2.69 17.25a1.875 1.875 0 0 0 1.624 2.812h15.372a1.875 1.875 0 0 0 1.624-2.812L13.66 3.94a1.875 1.875 0 0 0-3.32 0Z"
                    />
                </svg>
            </div>

            <h3
                class="mt-4 text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
            >
                Hapus Jenis Ruangan?
            </h3>

            <p
                class="mt-2 text-xs leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
            >
                {{ confirmMessage }}
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    @click="cancelDelete"
                    :disabled="isDeleting"
                    class="rounded-lg border border-[#e3e3e0] bg-white px-3 py-1.5 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                >
                    Batal
                </button>

                <button
                    type="button"
                    @click="executeDelete"
                    :disabled="isDeleting"
                    class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
                </button>
            </div>
        </div>
    </div>
</template>
```
