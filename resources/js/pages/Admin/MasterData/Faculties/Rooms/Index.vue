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

type RoomType =
    | "kelas"
    | "lab_komputer"
    | "ruang_dosen"
    | "ruang_akademik";

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

interface Faculty {
    id: number;
    code: string;
    name: string;
    dean?: string | null;
}

interface Room {
    id: number;
    faculty_id: number;
    code: string;
    name: string;
    type: RoomType;
    building: string | null;
    floor: string | null;
    building_floor: string | null;
    description: string | null;
    is_active: boolean;
    faculty?: Faculty | null;
    roomInventories: RoomInventory[];
    inventories_count?: number;
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
    items: Item[];
}>();

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

const totalRooms = computed(() => props.rooms?.total ?? rooms.value.length);

const totalInventories = computed(() =>
    rooms.value.reduce(
        (total, room) =>
            total +
            (room.inventories_count ??
                room.roomInventories?.length ??
                0),
        0,
    ),
);

const filteredRooms = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    if (!query) {
        return rooms.value;
    }

    return rooms.value.filter((room) => {
        const roomMatch =
            room.code?.toLowerCase().includes(query) ||
            room.name?.toLowerCase().includes(query) ||
            roomTypeLabels[room.type]?.toLowerCase().includes(query) ||
            room.building?.toLowerCase().includes(query) ||
            room.floor?.toLowerCase().includes(query) ||
            room.building_floor?.toLowerCase().includes(query) ||
            room.description?.toLowerCase().includes(query) ||
            room.faculty?.code?.toLowerCase().includes(query) ||
            room.faculty?.name?.toLowerCase().includes(query) ||
            (room.is_active ? "aktif" : "nonaktif").includes(query);

        const inventoryMatch = (room.roomInventories ?? []).some(
            (inv) =>
                inv.asset_code?.toLowerCase().includes(query) ||
                inv.item?.name?.toLowerCase().includes(query) ||
                conditionLabels[inv.condition]
                    ?.toLowerCase()
                    .includes(query) ||
                (inv.notes ?? "").toLowerCase().includes(query),
        );

        return roomMatch || inventoryMatch;
    });
});

const toggleExpand = (id: number) => {
    const index = expandedRooms.value.indexOf(id);

    if (index > -1) {
        expandedRooms.value.splice(index, 1);
    } else {
        expandedRooms.value.push(id);
    }
};

const openCreateRoomModal = () => {
    editingRoom.value = null;
    isRoomModalOpen.value = true;
};

const openEditRoomModal = (room: Room) => {
    editingRoom.value = room;
    isRoomModalOpen.value = true;
};

const handleSaveRoom = (data: {
    faculty_id: number | string;
    code: string;
    name: string;
    type: RoomType;
    building: string | null;
    floor: string | null;
    building_floor: string | null;
    description: string | null;
    is_active: boolean;
}) => {
    const payload = {
        faculty_id: Number(data.faculty_id),
        code: data.code,
        name: data.name,
        type: data.type,
        building: data.building,
        floor: data.floor,
        building_floor: data.building_floor,
        description: data.description,
        is_active: data.is_active,
    };

    if (editingRoom.value) {
        router.put(updateRoom.url(editingRoom.value.id), payload, {
            preserveScroll: true,
            onSuccess: () => {
                isRoomModalOpen.value = false;
                editingRoom.value = null;
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
        "Ruangan hanya dapat dihapus jika tidak memiliki inventaris atau data pengadaan yang terkait.";

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

const openInventoryModal = (room: Room) => {
    editingInventory.value = null;
    activeRoomForInventory.value = room;
    isInventoryModalOpen.value = true;
};

const openEditInventoryModal = (
    room: Room,
    inventory: RoomInventory,
) => {
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
        router.put(
            updateInventory.url(editingInventory.value.id),
            payload,
            {
                preserveScroll: true,
                onSuccess: () => {
                    closeInventoryModal();
                },
                onError: (errors) => {
                    console.error(
                        "Gagal update inventaris ruangan:",
                        errors,
                    );
                },
                onFinish: () => {
                    isInventoryProcessing.value = false;
                },
            },
        );

        return;
    }

    router.post(storeInventory.url(), payload, {
        preserveScroll: true,
        onSuccess: () => {
            closeInventoryModal();
        },
        onError: (errors) => {
            console.error(
                "Gagal menyimpan inventaris ruangan:",
                errors,
            );
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

    <div
        class="relative flex flex-1 flex-col gap-6 overflow-hidden p-4 md:p-6"
    >
        <!-- Decorative Background -->
        <div
            class="pointer-events-none absolute inset-0 overflow-hidden"
        >
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

        <div
            class="relative z-10 flex flex-1 flex-col gap-6 opacity-100 transition-opacity duration-750 starting:opacity-0"
        >
            <!-- BREADCRUMB -->
            <div
                class="flex items-center gap-2 text-xs text-[#706f6c] dark:text-[#A1A09A]"
            >
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

                <span
                    class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                >
                    Manajemen Ruangan
                </span>
            </div>

            <!-- STATS -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Total Room -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Total Ruangan
                        </span>

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

                <!-- Total Inventory -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Total Inventaris
                        </span>

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

                <!-- Quick Action -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md sm:col-span-2 lg:col-span-1 dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Aksi Cepat
                        </span>

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
                            type="button"
                            @click="openCreateRoomModal"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#1b1b18] px-3 py-2 text-xs font-medium text-white transition hover:bg-black dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white"
                        >
                            <span>+ Tambah Ruangan Baru</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- MAIN -->
            <div
                class="flex flex-1 flex-col rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]"
            >
                <!-- HEADER -->
                <div
                    class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2
                            class="text-lg font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Master Data Ruangan & Inventaris
                        </h2>

                        <p
                            class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Kelola seluruh ruangan beserta aset inventaris di
                            lingkungan kampus.
                        </p>
                    </div>

                    <div class="relative w-full sm:w-80">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari kode, ruangan, fakultas, gedung, aset..."
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

                <!-- EMPTY SEARCH -->
                <div
                    v-if="filteredRooms.length === 0"
                    class="rounded-xl border border-dashed border-[#e3e3e0] px-6 py-12 text-center dark:border-[#3E3E3A]"
                >
                    <div
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400"
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
                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                            />
                        </svg>
                    </div>

                    <h3
                        class="mt-3 text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Tidak ada ruangan ditemukan
                    </h3>

                    <p
                        class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Coba gunakan kata kunci pencarian yang berbeda.
                    </p>
                </div>

                <!-- ROOM LIST -->
                <div v-else class="space-y-4">
                    <div
                        v-for="room in filteredRooms"
                        :key="room.id"
                        class="overflow-hidden rounded-xl border border-[#e3e3e0] bg-[#FDFDFC] transition dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                    >
                        <!-- ROOM HEADER -->
                        <div
                            class="flex flex-col gap-4 p-4 lg:flex-row lg:items-start lg:justify-between"
                            :class="{
                                'border-b border-[#e3e3e0] dark:border-[#3E3E3A]':
                                    expandedRooms.includes(room.id),
                            }"
                        >
                            <div class="flex min-w-0 items-start gap-3">
                                <!-- Expand -->
                                <button
                                    type="button"
                                    @click="toggleExpand(room.id)"
                                    class="mt-0.5 shrink-0 rounded-lg border border-black/5 bg-white p-1.5 text-[#706f6c] hover:text-[#1b1b18] dark:border-white/10 dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="h-4 w-4 transition-transform duration-200"
                                        :class="{
                                            'rotate-180':
                                                expandedRooms.includes(
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

                                <div class="min-w-0 flex-1">
                                    <!-- Title -->
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <span
                                            class="rounded bg-[#fff2f2] px-2 py-0.5 text-[10px] font-bold text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
                                        >
                                            {{
                                                roomTypeLabels[room.type] ??
                                                room.type
                                            }}
                                        </span>

                                        <span
                                            v-if="room.is_active"
                                            class="rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400"
                                        >
                                            Aktif
                                        </span>

                                        <span
                                            v-else
                                            class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-white/5 dark:text-slate-400"
                                        >
                                            Nonaktif
                                        </span>

                                        <h3
                                            class="text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{ room.name }}
                                        </h3>
                                    </div>

                                    <!-- Code -->
                                    <p
                                        class="mt-1 font-mono text-[11px] font-semibold text-[#f53003] dark:text-[#FF4433]"
                                    >
                                        {{ room.code || "-" }}
                                    </p>

                                    <!-- Faculty -->
                                    <p
                                        class="mt-2 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        Fakultas:
                                        <span
                                            class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{
                                                room.faculty?.code
                                                    ? `${room.faculty.code} - ${room.faculty.name}`
                                                    : room.faculty?.name ?? "-"
                                            }}
                                        </span>
                                    </p>

                                    <!-- Location -->
                                    <div
                                        class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        <span>
                                            Gedung:
                                            <strong
                                                class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                            >
                                                {{ room.building || "-" }}
                                            </strong>
                                        </span>

                                        <span>
                                            Lantai:
                                            <strong
                                                class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                            >
                                                {{ room.floor || "-" }}
                                            </strong>
                                        </span>

                                        <span v-if="room.building_floor">
                                            Lokasi:
                                            <strong
                                                class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                            >
                                                {{ room.building_floor }}
                                            </strong>
                                        </span>
                                    </div>

                                    <!-- Description -->
                                    <p
                                        v-if="room.description"
                                        class="mt-2 max-w-3xl text-[11px] leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        {{ room.description }}
                                    </p>
                                </div>
                            </div>

                            <!-- ACTIONS -->
                            <div
                                class="flex shrink-0 items-center gap-2 self-end lg:self-center"
                            >
                                <span
                                    class="mr-1 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    {{
                                        room.inventories_count ??
                                        room.roomInventories?.length ??
                                        0
                                    }}
                                    Item
                                </span>

                                <!-- Add Asset -->
                                <button
                                    type="button"
                                    @click="openInventoryModal(room)"
                                    class="rounded-lg border border-[#e3e3e0] bg-white px-2.5 py-1.5 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                                >
                                    + Aset
                                </button>

                                <!-- Edit -->
                                <button
                                    type="button"
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

                                <!-- Delete -->
                                <button
                                    type="button"
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

                        <!-- INVENTORY -->
                        <div
                            v-if="expandedRooms.includes(room.id)"
                            class="bg-white/50 p-4 dark:bg-[#161615]/30"
                        >
                            <div
                                v-if="
                                    (room.roomInventories?.length ?? 0) > 0
                                "
                                class="overflow-x-auto"
                            >
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr
                                            class="border-b border-[#e3e3e0] text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]"
                                        >
                                            <th
                                                class="pb-2 font-medium uppercase tracking-wider"
                                            >
                                                Kode Aset
                                            </th>

                                            <th
                                                class="pb-2 font-medium uppercase tracking-wider"
                                            >
                                                Nama Barang
                                            </th>

                                            <th
                                                class="pb-2 font-medium uppercase tracking-wider"
                                            >
                                                Kondisi
                                            </th>

                                            <th
                                                class="pb-2 font-medium uppercase tracking-wider"
                                            >
                                                Bisa Dipinjam
                                            </th>

                                            <th
                                                class="pb-2 font-medium uppercase tracking-wider"
                                            >
                                                Catatan
                                            </th>

                                            <th
                                                class="pb-2 text-right font-medium uppercase tracking-wider"
                                            >
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
                                            <td
                                                class="py-2.5 font-mono font-semibold text-[#f53003] dark:text-[#FF4433]"
                                            >
                                                {{ inv.asset_code }}
                                            </td>

                                            <td class="py-2.5 font-medium">
                                                {{
                                                    inv.item?.name ??
                                                    "Barang tidak ditemukan"
                                                }}
                                            </td>

                                            <td class="py-2.5">
                                                <span
                                                    class="rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                                    :class="
                                                        conditionClasses[
                                                            inv.condition
                                                        ]
                                                    "
                                                >
                                                    {{
                                                        conditionLabels[
                                                            inv.condition
                                                        ] ??
                                                        inv.condition
                                                    }}
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

                                            <td
                                                class="max-w-xs py-2.5 text-[#706f6c] dark:text-[#A1A09A]"
                                            >
                                                <span
                                                    class="block truncate"
                                                    :title="
                                                        inv.notes || undefined
                                                    "
                                                >
                                                    {{ inv.notes || "-" }}
                                                </span>
                                            </td>

                                            <td class="py-2.5 text-right">
                                                <div
                                                    class="flex items-center justify-end gap-1.5"
                                                >
                                                    <!-- Edit Asset -->
                                                    <button
                                                        type="button"
                                                        @click="
                                                            openEditInventoryModal(
                                                                room,
                                                                inv,
                                                            )
                                                        "
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

                                                    <!-- Delete Asset -->
                                                    <button
                                                        type="button"
                                                        @click="
                                                            deleteInventory(
                                                                inv.id,
                                                            )
                                                        "
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
                                                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                                            />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div
                                v-else
                                class="py-3 text-center text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Belum ada item inventaris di ruangan ini.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROOM FORM MODAL -->
        <RoomFormModal
            :show="isRoomModalOpen"
            :room="editingRoom"
            :faculties="faculties"
            @close="
                isRoomModalOpen = false;
                editingRoom = null;
            "
            @submit="handleSaveRoom"
        />

        <!-- ROOM INVENTORY MODAL -->
        <RoomInventoryModal
            :show="isInventoryModalOpen"
            :inventory="editingInventory"
            :rooms="rooms"
            :items="items"
            :is-processing="isInventoryProcessing"
            @close="closeInventoryModal"
            @submit="handleSaveInventory"
        />

        <!-- CONFIRMATION MODAL -->
        <div
            v-if="isConfirmModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-[#161615]"
            >
                <h3
                    class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                >
                    {{ confirmTitle }}
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
                        {{ isDeleting ? "Menghapus..." : "Ya, Hapus" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
`


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
