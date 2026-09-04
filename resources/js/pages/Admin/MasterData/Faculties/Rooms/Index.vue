<script setup lang="ts">
import { ref, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";

import RoomFormModal from "./RoomFormModal.vue";
import RoomInventoryModal from "./RoomInventoryModal.vue";

import {
    store as storeRoom,
    update as updateRoom,
    destroy as destroyRoom,
} from "@/actions/App/Http/Controllers/RoomController";

import {
    store as storeInventory,
    update as updateInventory,
    destroy as destroyInventory,
} from "@/actions/App/Http/Controllers/RoomInventoryController";

/*
|--------------------------------------------------------------------------
| Interfaces
|--------------------------------------------------------------------------
*/

type RoomType = "kelas" | "lab_komputer" | "ruang_dosen" | "ruang_akademik";
type ItemCondition = "good" | "damaged_light" | "damaged_heavy";

interface Item {
    id: number;
    name: string;
}

interface RoomInventory {
    id: number;
    room_id: number | null;
    item_id: number | null;
    item?: Item | null;
    asset_code: string;
    condition: ItemCondition;
    is_borrowable: boolean;
    notes: string | null;
}

interface Room {
    id: number;
    faculty_id: number;
    name: string;
    type: RoomType;
    building_floor: string | null;
    roomInventories: RoomInventory[];
}

interface Faculty {
    id: number;
    code: string;
    name: string;
    dean: string;
}

interface PaginatedRooms {
    data: Room[];
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    faculties: Faculty[];
    rooms: PaginatedRooms;
}>();

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const isLoading = ref(false);
const searchQuery = ref("");
const expandedRooms = ref<number[]>([]);

const isRoomModalOpen = ref(false);
const editingRoom = ref<Room | null>(null);

const isInventoryModalOpen = ref(false);
const isInventoryProcessing = ref(false);
const editingInventory = ref<RoomInventory | null>(null);
const activeRoomForInventory = ref<Room | null>(null);

const isConfirmModalOpen = ref(false);
const confirmTitle = ref("Konfirmasi Penghapusan");
const confirmMessage = ref("");
const confirmAction = ref<(() => void) | null>(null);
const isDeleting = ref(false);

const rooms = computed(() => props.rooms?.data ?? []);

/*
|--------------------------------------------------------------------------
| Labels & Badge Styling
|--------------------------------------------------------------------------
*/

const roomTypeLabels: Record<RoomType, string> = {
    kelas: "Kelas",
    lab_komputer: "Lab Komputer",
    ruang_dosen: "Ruang Dosen",
    ruang_akademik: "Ruang Akademik",
};

const conditionLabels: Record<ItemCondition, string> = {
    good: "Baik",
    damaged_light: "Rusak Ringan",
    damaged_heavy: "Rusak Berat",
};

const conditionClasses: Record<ItemCondition, string> = {
    good: "bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400",
    damaged_light:
        "bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400",
    damaged_heavy:
        "bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400",
};

/*
|--------------------------------------------------------------------------
| Computed Stats
|--------------------------------------------------------------------------
*/

const totalRooms = computed(() => props.rooms?.total ?? rooms.value.length);

const totalInventories = computed(() =>
    rooms.value.reduce(
        (total, room) => total + (room.roomInventories?.length ?? 0),
        0,
    ),
);

const filteredRooms = computed(() => {
    if (!searchQuery.value) {
        return rooms.value;
    }

    const query = searchQuery.value.toLowerCase();

    return rooms.value.filter((room) => {
        const roomMatch =
            room.name.toLowerCase().includes(query) ||
            roomTypeLabels[room.type]?.toLowerCase().includes(query) ||
            (room.building_floor ?? "").toLowerCase().includes(query);

        const inventoryMatch = (room.roomInventories ?? []).some(
            (inv) =>
                inv.asset_code?.toLowerCase().includes(query) ||
                inv.item?.name?.toLowerCase().includes(query) ||
                conditionLabels[inv.condition]?.toLowerCase().includes(query) ||
                (inv.notes ?? "").toLowerCase().includes(query),
        );

        return roomMatch || inventoryMatch;
    });
});

/*
|--------------------------------------------------------------------------
| Expand / Collapse
|--------------------------------------------------------------------------
*/

const toggleExpand = (id: number) => {
    const index = expandedRooms.value.indexOf(id);

    if (index > -1) {
        expandedRooms.value.splice(index, 1);
    } else {
        expandedRooms.value.push(id);
    }
};

/*
|--------------------------------------------------------------------------
| Room CRUD
|--------------------------------------------------------------------------
*/

const openCreateRoomModal = () => {
    editingRoom.value = null;
    isRoomModalOpen.value = true;
};

const openEditRoomModal = (room: Room) => {
    editingRoom.value = room;
    isRoomModalOpen.value = true;
};

const handleSaveRoom = (data: any) => {
    const payload = {
        ...data,
    };

    if (editingRoom.value) {
        router.put(updateRoom.url(editingRoom.value.id), payload, {
            preserveScroll: true,
            onSuccess: () => {
                isRoomModalOpen.value = false;
            },
        });

        return;
    }

    router.post(storeRoom.url(), payload, {
        preserveScroll: true,
        onSuccess: () => {
            isRoomModalOpen.value = false;
        },
    });
};

const deleteRoom = (roomId: number) => {
    confirmTitle.value = "Hapus Ruangan?";
    confirmMessage.value =
        "Apakah Anda yakin ingin menghapus ruangan ini? Semua data inventaris di dalamnya juga akan ikut terhapus.";

    confirmAction.value = () => {
        isDeleting.value = true;

        router.delete(destroyRoom.url(roomId), {
            preserveScroll: true,
            onFinish: () => {
                isDeleting.value = false;
                isConfirmModalOpen.value = false;
                confirmAction.value = null;
            },
        });
    };

    isConfirmModalOpen.value = true;
};

/*
|--------------------------------------------------------------------------
| Room Inventory CRUD
|--------------------------------------------------------------------------
*/

const openInventoryModal = (room: Room) => {
    editingInventory.value = null;
    activeRoomForInventory.value = room;
    isInventoryModalOpen.value = true;
};

const openEditInventoryModal = (room: Room, inventory: RoomInventory) => {
    activeRoomForInventory.value = room;

    editingInventory.value = {
        id: inventory.id,
        room_id: inventory.room_id ?? room.id,
        item_id: inventory.item_id ?? null,
        item: inventory.item ?? null,
        asset_code: inventory.asset_code ?? "",
        condition: inventory.condition ?? "good",
        is_borrowable: inventory.is_borrowable ?? true,
        notes: inventory.notes ?? "",
    };

    isInventoryModalOpen.value = true;
};

const closeInventoryModal = () => {
    isInventoryModalOpen.value = false;
    editingInventory.value = null;
    activeRoomForInventory.value = null;
    isInventoryProcessing.value = false;
};

const handleSaveInventory = (data: {
    item_id: number | null;
    asset_code: string;
    condition: ItemCondition;
    is_borrowable: boolean;
    notes: string | null;
}) => {
    if (!activeRoomForInventory.value) {
        return;
    }

    isInventoryProcessing.value = true;

    const payload = {
        room_id: activeRoomForInventory.value.id,
        item_id: data.item_id,
        asset_code: data.asset_code,
        condition: data.condition,
        is_borrowable: data.is_borrowable,
        notes: data.notes,
    };

    if (editingInventory.value?.id) {
        router.put(updateInventory.url(editingInventory.value.id), payload, {
            preserveScroll: true,
            onSuccess: () => {
                closeInventoryModal();
            },
            onError: (errors) => {
                console.error("Gagal update inventaris ruangan:", errors);
            },
            onFinish: () => {
                isInventoryProcessing.value = false;
            },
        });

        return;
    }

    router.post(storeInventory.url(), payload, {
        preserveScroll: true,
        onSuccess: () => {
            closeInventoryModal();
        },
        onError: (errors) => {
            console.error("Gagal menyimpan inventaris ruangan:", errors);
        },
        onFinish: () => {
            isInventoryProcessing.value = false;
        },
    });
};

const deleteInventory = (inventoryId: number) => {
    confirmTitle.value = "Hapus Item Inventaris?";
    confirmMessage.value =
        "Apakah Anda yakin ingin menghapus item inventaris ini? Data yang sudah dihapus tidak dapat dikembalikan.";

    confirmAction.value = () => {
        isDeleting.value = true;

        router.delete(destroyInventory.url(inventoryId), {
            preserveScroll: true,
            onFinish: () => {
                isDeleting.value = false;
                isConfirmModalOpen.value = false;
                confirmAction.value = null;
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
    <Head title="Manajemen Ruangan & Inventaris - Sistem Inventory" />

    <div class="relative flex flex-1 flex-col gap-6 overflow-hidden p-4 md:p-6">
        <!-- Animated decorative background -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div
                class="animate-blob absolute -left-24 -top-24 h-72 w-72 rounded-full bg-[#f53003]/10 blur-3xl dark:bg-[#FF4433]/10 sm:h-96 sm:w-96"
            ></div>
            <div
                class="animate-blob animation-delay-2000 absolute -bottom-24 -right-16 h-72 w-72 rounded-full bg-[#f53003]/5 blur-3xl dark:bg-[#FF4433]/10 sm:h-96 sm:w-96"
            ></div>
            <div
                class="animate-blob animation-delay-4000 absolute left-1/2 top-1/3 h-56 w-56 -translate-x-1/2 rounded-full bg-amber-300/5 blur-3xl dark:bg-amber-500/10 sm:h-72 sm:w-72"
            ></div>
        </div>

        <!-- Wrapper konten -->
        <div
            class="relative z-10 flex flex-1 flex-col gap-6 opacity-100 transition-opacity duration-750 starting:opacity-0"
        >
            <!-- BREADCRUMB / BACK -->
            <div class="flex items-center gap-2 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                <Link
                    href="/admin/faculties"
                    class="flex items-center gap-1 font-medium text-[#f53003] transition hover:underline dark:text-[#FF4433]"
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
                            d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
                        />
                    </svg>
                    Fakultas & Prodi
                </Link>
                <span>/</span>
                <span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                    Manajemen Ruangan
                </span>
            </div>

            <!-- STATS CARDS -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                            >Total Ruangan</span
                        >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff2f2] text-[#f53003] transition group-hover:bg-[#f53003] group-hover:text-white dark:bg-[#1D0002] dark:text-[#FF4433] dark:group-hover:bg-[#FF4433] dark:group-hover:text-white"
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
                                    d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ totalRooms }}
                        </div>
                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Ruangan terdaftar
                        </p>
                    </div>
                </div>

                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                            >Total Inventaris</span
                        >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff2f2] text-[#f53003] transition group-hover:bg-[#f53003] group-hover:text-white dark:bg-[#1D0002] dark:text-[#FF4433] dark:group-hover:bg-[#FF4433] dark:group-hover:text-white"
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
                                    d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ totalInventories }}
                        </div>
                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Item inventaris tercatat
                        </p>
                    </div>
                </div>

                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md sm:col-span-2 lg:col-span-1 dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                            >Aksi Cepat</span
                        >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff2f2] text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
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
                                    d="M12 4.5v15m7.5-7.5h-15"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-col gap-2">
                        <button
                            @click="openCreateRoomModal"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#1b1b18] px-3 py-2 text-xs font-medium text-white transition hover:bg-black dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white"
                        >
                            <span>+ Tambah Ruangan Baru</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT SECTION -->
            <div
                class="flex flex-1 flex-col rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]"
            >
                <div
                    class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2
                            class="text-lg font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Master Data Ruangan & Inventaris
                        </h2>
                        <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            Kelola seluruh ruangan beserta aset inventaris di lingkungan kampus.
                        </p>
                    </div>
                    <div class="relative w-full sm:w-72">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari ruangan atau aset..."
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent py-2 pl-9 pr-3.5 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="absolute left-3 top-2.5 h-4 w-4 text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                            />
                        </svg>
                    </div>
                </div>

                <!-- ROOM LIST -->
                <div class="space-y-4">
                    <div
                        v-for="room in filteredRooms"
                        :key="room.id"
                        class="overflow-hidden rounded-xl border border-[#e3e3e0] bg-[#FDFDFC] transition dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                    >
                        <!-- Room Header -->
                        <div
                            class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                            :class="{
                                'border-b border-[#e3e3e0] dark:border-[#3E3E3A]':
                                    expandedRooms.includes(room.id),
                            }"
                        >
                            <div class="flex items-start gap-3">
                                <button
                                    @click="toggleExpand(room.id)"
                                    class="mt-0.5 rounded-lg border border-black/5 bg-white p-1.5 text-[#706f6c] hover:text-[#1b1b18] dark:border-white/10 dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="h-4 w-4 transition-transform duration-200"
                                        :class="{
                                            'rotate-180': expandedRooms.includes(
                                                room.id,
                                            ),
                                        }"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m19.5 8.25-7.5 7.5-7.5-7.5"
                                        />
                                    </svg>
                                </button>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            class="rounded bg-[#fff2f2] px-2 py-0.5 text-[10px] font-bold text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
                                            >{{ roomTypeLabels[room.type] }}</span
                                        >
                                        <h3
                                            class="text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{ room.name }}
                                        </h3>
                                    </div>
                                    <p
                                        class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        Lantai/Gedung:
                                        <span
                                            class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                            >{{
                                                room.building_floor || "-"
                                            }}</span
                                        >
                                    </p>
                                </div>
                            </div>

                            <div
                                class="flex items-center gap-2 self-end sm:self-center"
                            >
                                <span
                                    class="mr-2 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                    >{{
                                        room.roomInventories?.length ?? 0
                                    }}
                                    Item</span
                                >
                                <button
                                    @click="openInventoryModal(room)"
                                    class="rounded-lg border border-[#e3e3e0] bg-white px-2.5 py-1.5 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                                >
                                    + Aset
                                </button>
                                <button
                                    @click="openEditRoomModal(room)"
                                    class="rounded-lg border border-[#e3e3e0] bg-white p-1.5 text-[#706f6c] hover:text-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:text-[#FF4433]"
                                    title="Edit Ruangan"
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
                                            d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                        />
                                    </svg>
                                </button>
                                <button
                                    @click="deleteRoom(room.id)"
                                    class="rounded-lg border border-[#e3e3e0] bg-white p-1.5 text-[#706f6c] hover:text-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:text-[#FF4433]"
                                    title="Hapus Ruangan"
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
                                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Expandable Room Inventory List -->
                        <div
                            v-if="expandedRooms.includes(room.id)"
                            class="bg-white/50 p-4 dark:bg-[#161615]/30"
                        >
                            <div
                                v-if="(room.roomInventories?.length ?? 0) > 0"
                                class="overflow-x-auto"
                            >
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr
                                            class="border-b border-[#e3e3e0] text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]"
                                        >
                                            <th class="pb-2 font-medium uppercase tracking-wider">
                                                Kode Aset
                                            </th>
                                            <th class="pb-2 font-medium uppercase tracking-wider">
                                                Nama Barang
                                            </th>
                                            <th class="pb-2 font-medium uppercase tracking-wider">
                                                Kondisi
                                            </th>
                                            <th class="pb-2 font-medium uppercase tracking-wider">
                                                Bisa Dipinjam
                                            </th>
                                            <th class="pb-2 font-medium uppercase tracking-wider">
                                                Catatan
                                            </th>
                                            <th class="pb-2 text-right font-medium uppercase tracking-wider">
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-[#e3e3e0]/50 dark:divide-[#3E3E3A]/50"
                                    >
                                        <tr
                                            v-for="inv in room.roomInventories"
                                            :key="inv.id"
                                            class="text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            <td class="py-2.5 font-mono font-semibold text-[#f53003] dark:text-[#FF4433]">
                                                {{ inv.asset_code }}
                                            </td>
                                            <td class="py-2.5 font-medium">
                                                {{ inv.item?.name ?? "-" }}
                                            </td>
                                            <td class="py-2.5">
                                                <span
                                                    class="rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                                    :class="conditionClasses[inv.condition]"
                                                >
                                                    {{ conditionLabels[inv.condition] }}
                                                </span>
                                            </td>
                                            <td class="py-2.5">
                                                <span
                                                    v-if="inv.is_borrowable"
                                                    class="rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400"
                                                >
                                                    Ya
                                                </span>
                                                <span
                                                    v-else
                                                    class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-white/5 dark:text-slate-300"
                                                >
                                                    Tidak
                                                </span>
                                            </td>
                                            <td class="py-2.5 text-[#706f6c] dark:text-[#A1A09A]">
                                                {{ inv.notes || "-" }}
                                            </td>
                                            <td class="py-2.5 text-right">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <button
                                                        @click="openEditInventoryModal(room, inv)"
                                                        class="p-1 text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                                                        title="Edit Aset"
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
                                                    <button
                                                        @click="deleteInventory(inv.id)"
                                                        class="p-1 text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]"
                                                        title="Hapus Aset"
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
                                                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                                            />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="py-3 text-center text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                Belum ada item inventaris di ruangan ini.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Form Modal -->
        <RoomFormModal
            :show="isRoomModalOpen"
            :room="editingRoom"
            :faculties="faculties"
            @close="isRoomModalOpen = false"
            @submit="handleSaveRoom"
        />

        <!-- Room Inventory Modal -->
        <RoomInventoryModal
            :show="isInventoryModalOpen"
            :inventory="editingInventory"
            :rooms="rooms"
            :items="[]"
            @close="closeInventoryModal"
            @submit="handleSaveInventory"
        />

        <!-- Confirmation Modal -->
        <div
            v-if="isConfirmModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-[#161615]">
                <h3 class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                    {{ confirmTitle }}
                </h3>
                <p class="mt-2 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                    {{ confirmMessage }}
                </p>
                <div class="mt-6 flex justify-end gap-2">
                    <button
                        type="button"
                        @click="cancelDelete"
                        class="rounded-lg border border-[#e3e3e0] bg-white px-3 py-1.5 text-xs font-medium text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDelete"
                        :disabled="isDeleting"
                        class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700"
                    >
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes blob {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(20px, -30px) scale(1.1);
    }
    66% {
        transform: translate(-15px, 15px) scale(0.95);
    }
}

.animate-blob {
    animation: blob 10s infinite ease-in-out;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

@media (prefers-reduced-motion: reduce) {
    .animate-blob {
        animation: none;
    }
}
</style>
