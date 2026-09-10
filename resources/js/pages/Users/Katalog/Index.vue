<script setup lang="ts">
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
} from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

interface Faculty {
    id: number;
    name: string;
    code: string;
}

interface Room {
    id: number;
    name: string;
    code: string;
    faculty_id: number;
    is_active: boolean;
    faculty?: Faculty | null;
}

interface Item {
    id: number;
    name: string;
}

interface RoomInventory {
    id: number;
    room_id: number;
    item_id: number;
    asset_code: string;
    condition:
        | 'good'
        | 'damaged_light'
        | 'damaged_heavy'
        | string;
    is_borrowable: boolean;
    notes?: string | null;
    room?: Room | null;
    item?: Item | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedInventories {
    current_page: number;
    data: RoomInventory[];
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: PaginationLink[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}

interface Filters {
    search?: string;
    faculty_id?: number | string | null;
    condition?: string | null;
    sort?: string;
}

interface Props {
    roomInventories: PaginatedInventories;
    faculties: Faculty[];
    filters: Filters;
}

const props = defineProps<Props>();

const CATALOG_URL = '/user/katalog';
const BORROWING_CREATE_URL = '/peminjaman/create';

const search = ref(props.filters.search ?? '');

const selectedFaculty = ref(
    props.filters.faculty_id !== null &&
        props.filters.faculty_id !== undefined &&
        props.filters.faculty_id !== ''
        ? String(props.filters.faculty_id)
        : 'all',
);

const selectedCondition = ref(
    props.filters.condition ?? 'all',
);

const sortBy = ref(
    props.filters.sort ?? 'asset_asc',
);

const showFilters = ref(false);
const isFiltering = ref(false);
const isNavigating = ref(false);

let removeStartListener:
    | (() => void)
    | undefined;

let removeFinishListener:
    | (() => void)
    | undefined;

let removeErrorListener:
    | (() => void)
    | undefined;

onMounted(() => {
    removeStartListener = router.on('start', (event) => {
        const method = String(
            event.detail.visit.method,
        ).toLowerCase();

        isNavigating.value = method === 'get';
    });

    removeFinishListener = router.on('finish', () => {
        isNavigating.value = false;
        isFiltering.value = false;
    });

    removeErrorListener = router.on('error', () => {
        isNavigating.value = false;
        isFiltering.value = false;
    });
});

onBeforeUnmount(() => {
    removeStartListener?.();
    removeFinishListener?.();
    removeErrorListener?.();
});

const showSkeleton = computed(
    () => isFiltering.value || isNavigating.value,
);

const totalInventory = computed(
    () => props.roomInventories.total,
);

const currentPage = computed(
    () => props.roomInventories.current_page,
);

const lastPage = computed(
    () => props.roomInventories.last_page,
);

const from = computed(
    () => props.roomInventories.from ?? 0,
);

const to = computed(
    () => props.roomInventories.to ?? 0,
);

const hasInventory = computed(
    () => props.roomInventories.data.length > 0,
);

const hasActiveFilters = computed(() => {
    return (
        search.value.trim() !== '' ||
        selectedFaculty.value !== 'all' ||
        selectedCondition.value !== 'all' ||
        sortBy.value !== 'asset_asc'
    );
});

const conditionLabels: Record<string, string> = {
    good: 'Baik',
    damaged_light: 'Rusak Ringan',
    damaged_heavy: 'Rusak Berat',
};

const getConditionLabel = (
    condition: string,
) => {
    return (
        conditionLabels[condition] ??
        condition
    );
};

const getConditionClass = (
    condition: string,
) => {
    switch (condition) {
        case 'good':
            return [
                'border-emerald-200',
                'bg-emerald-50',
                'text-emerald-700',
                'dark:border-emerald-500/20',
                'dark:bg-emerald-500/10',
                'dark:text-emerald-300',
            ];

        case 'damaged_light':
            return [
                'border-amber-200',
                'bg-amber-50',
                'text-amber-700',
                'dark:border-amber-500/20',
                'dark:bg-amber-500/10',
                'dark:text-amber-300',
            ];

        case 'damaged_heavy':
            return [
                'border-red-200',
                'bg-red-50',
                'text-red-700',
                'dark:border-red-500/20',
                'dark:bg-red-500/10',
                'dark:text-red-300',
            ];

        default:
            return [
                'border-black/10',
                'bg-black/5',
                'text-[#706f6c]',
                'dark:border-white/10',
                'dark:bg-white/5',
                'dark:text-[#A1A09A]',
            ];
    }
};

const getBorrowingUrl = (
    inventoryId: number,
) => {
    return `${BORROWING_CREATE_URL}?room_inventory_id=${inventoryId}`;
};

const applyFilters = () => {
    if (isFiltering.value) {
        return;
    }

    isFiltering.value = true;

    router.get(
        CATALOG_URL,
        {
            search:
                search.value.trim() ||
                undefined,
            faculty_id:
                selectedFaculty.value !== 'all'
                    ? selectedFaculty.value
                    : undefined,
            condition:
                selectedCondition.value !== 'all'
                    ? selectedCondition.value
                    : undefined,
            sort:
                sortBy.value !== 'asset_asc'
                    ? sortBy.value
                    : undefined,
            page: 1,
        },
        {
            preserveState: true,
            preserveScroll: false,
            replace: true,
            onFinish: () => {
                isFiltering.value = false;
            },
        },
    );
};

const resetFilters = () => {
    if (isFiltering.value) {
        return;
    }

    search.value = '';
    selectedFaculty.value = 'all';
    selectedCondition.value = 'all';
    sortBy.value = 'asset_asc';
    isFiltering.value = true;

    router.get(
        CATALOG_URL,
        {},
        {
            preserveState: false,
            preserveScroll: false,
            replace: true,
            onFinish: () => {
                isFiltering.value = false;
            },
        },
    );
};

const handleSearchKeydown = (
    event: KeyboardEvent,
) => {
    if (event.key !== 'Enter') {
        return;
    }

    event.preventDefault();
    applyFilters();
};
</script>

<template>
    <Head title="Katalog Inventaris" />

    <div
        class="relative flex min-h-screen flex-1 flex-col overflow-hidden bg-[#fdfdfc] dark:bg-[#0f0f0e]"
    >
        <div
            class="pointer-events-none absolute -left-32 -top-32 h-72 w-72 rounded-full bg-[#f53003]/10 blur-3xl animate-blob"
        />

        <div
            class="pointer-events-none absolute right-[-8rem] top-[30%] h-80 w-80 rounded-full bg-[#f53003]/[0.07] blur-3xl animate-blob animation-delay-2000 dark:bg-[#FF4433]/[0.08]"
        />

        <div
            class="pointer-events-none absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-[#f53003]/[0.06] blur-3xl animate-blob animation-delay-4000 dark:bg-[#FF4433]/[0.06]"
        />

        <div
            class="pointer-events-none absolute -bottom-40 -right-32 h-96 w-96 rounded-full bg-[#f53003]/10 blur-3xl animate-blob"
        />

        <div
            class="pointer-events-none absolute inset-0 opacity-[0.025] dark:opacity-[0.035]"
            style="
                background-image:
                    linear-gradient(to right, currentColor 1px, transparent 1px),
                    linear-gradient(to bottom, currentColor 1px, transparent 1px);
                background-size: 32px 32px;
            "
        />

        <div
            class="relative z-10 flex flex-1 flex-col gap-6 p-4 opacity-100 transition-opacity duration-750 starting:opacity-0 sm:p-5 md:p-6"
        >
            <div class="flex flex-col gap-5">
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="min-w-0">
                        <div
                            class="mb-2 inline-flex max-w-full items-center gap-2 rounded-full border border-[#f53003]/15 bg-[#f53003]/5 px-3 py-1 text-xs font-semibold text-[#f53003] dark:border-[#FF4433]/20 dark:bg-[#FF4433]/10 dark:text-[#FF4433]"
                        >
                            <span
                                class="h-1.5 w-1.5 shrink-0 rounded-full bg-current"
                            />

                            <span class="truncate">
                                Katalog Inventaris
                            </span>
                        </div>

                        <h1
                            class="text-2xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC] sm:text-3xl"
                        >
                            Pilih Inventaris
                        </h1>

                        <p
                            class="mt-1 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Temukan inventaris yang tersedia
                            dari seluruh fakultas untuk diajukan
                            sebagai peminjaman.
                        </p>
                    </div>

                    <div class="flex w-full items-center gap-2 sm:w-auto">
                        <Link
                            href="/user/dashboard"
                            class="inline-flex h-10 flex-1 items-center justify-center rounded-lg border border-black/10 bg-white px-4 text-sm font-semibold text-[#1b1b18] shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-black/[0.03] hover:shadow-md sm:flex-none dark:border-white/10 dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-white/[0.04]"
                        >
                            <svg
                                class="mr-2 h-4 w-4 shrink-0"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="m15 18-6-6 6-6"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>

                            Kembali
                        </Link>

                        <button
                            type="button"
                            :aria-expanded="showFilters"
                            class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg border border-black/10 bg-white px-4 text-sm font-semibold text-[#1b1b18] shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-black/[0.03] hover:shadow-md sm:flex-none lg:hidden dark:border-white/10 dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-white/[0.04]"
                            @click="showFilters = !showFilters"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M4 6h16M7 12h10M10 18h4"
                                    stroke-linecap="round"
                                />
                            </svg>

                            Filter
                        </button>
                    </div>
                </div>

                <div
                    class="grid grid-cols-2 gap-3 md:grid-cols-3"
                >
                    <div
                        class="rounded-xl border border-black/5 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                    >
                        <p
                            class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Total Inventaris
                        </p>

                        <p
                            class="mt-1 text-xl font-bold text-[#1b1b18] sm:text-2xl dark:text-[#EDEDEC]"
                        >
                            {{ totalInventory.toLocaleString('id-ID') }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-black/5 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                    >
                        <p
                            class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Fakultas
                        </p>

                        <p
                            class="mt-1 text-xl font-bold text-[#1b1b18] sm:text-2xl dark:text-[#EDEDEC]"
                        >
                            {{ faculties.length.toLocaleString('id-ID') }}
                        </p>
                    </div>

                    <div
                        class="col-span-2 rounded-xl border border-black/5 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md md:col-span-1 dark:border-white/10 dark:bg-[#161615]"
                    >
                        <p
                            class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Halaman
                        </p>

                        <p
                            class="mt-1 text-xl font-bold text-[#f53003] sm:text-2xl dark:text-[#FF4433]"
                        >
                            {{ currentPage }}

                            <span
                                class="text-sm font-medium text-[#706f6c] sm:text-base dark:text-[#A1A09A]"
                            >
                                / {{ lastPage }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="relative overflow-hidden rounded-xl border border-[#f53003]/10 bg-white p-4 shadow-sm transition-all duration-300 hover:border-[#f53003]/20 hover:shadow-md dark:border-[#FF4433]/15 dark:bg-[#161615] dark:hover:border-[#FF4433]/25"
            >
                <div
                    class="pointer-events-none absolute -right-12 -top-12 h-32 w-32 rounded-full bg-[#f53003]/[0.06] blur-2xl animate-blob animation-delay-2000 dark:bg-[#FF4433]/[0.08]"
                />

                <div
                    class="relative flex items-start gap-3"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f53003]/10 text-[#f53003] dark:bg-[#FF4433]/10 dark:text-[#FF4433]"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                d="M8 7h12m0 0-3-3m3 3-3 3M16 17H4m0 0 3-3m-3 3 3 3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <p
                            class="text-sm font-bold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Peminjaman lintas fakultas
                        </p>

                        <p
                            class="mt-1 max-w-3xl text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Kamu dapat mengajukan peminjaman
                            inventaris milik fakultas lain.
                            Pengajuan akan diproses oleh fakultas
                            pemilik inventaris tersebut.
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="rounded-xl border border-black/5 bg-white p-4 shadow-sm transition-all duration-300 dark:border-white/10 dark:bg-[#161615]"
            >
                <div
                    class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_190px_190px_auto]"
                >
                    <div class="relative min-w-0">
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#706f6c] dark:text-[#A1A09A]"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle
                                cx="11"
                                cy="11"
                                r="7"
                            />

                            <path d="m20 20-4-4" />
                        </svg>

                        <input
                            v-model="search"
                            type="search"
                            autocomplete="off"
                            placeholder="Cari barang, kode aset, ruangan, fakultas..."
                            class="h-10 w-full rounded-lg border border-black/10 bg-white pl-10 pr-4 text-sm text-[#1b1b18] outline-none transition-all duration-200 placeholder:text-[#706f6c]/70 focus:border-[#f53003]/50 focus:ring-2 focus:ring-[#f53003]/10 dark:border-white/10 dark:bg-[#0f0f0e] dark:text-[#EDEDEC] dark:placeholder:text-[#A1A09A]/70"
                            @keydown="handleSearchKeydown"
                        />
                    </div>

                    <div
                        :class="{
                            'hidden lg:block': !showFilters,
                        }"
                    >
                        <select
                            v-model="selectedFaculty"
                            :disabled="isFiltering"
                            class="h-10 w-full rounded-lg border border-black/10 bg-white px-3 text-sm text-[#1b1b18] outline-none transition-all duration-200 focus:border-[#f53003]/50 focus:ring-2 focus:ring-[#f53003]/10 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-[#0f0f0e] dark:text-[#EDEDEC]"
                            @change="applyFilters"
                        >
                            <option value="all">
                                Semua Fakultas
                            </option>

                            <option
                                v-for="faculty in faculties"
                                :key="faculty.id"
                                :value="String(faculty.id)"
                            >
                                {{ faculty.name }}
                            </option>
                        </select>
                    </div>

                    <div
                        :class="{
                            'hidden lg:block': !showFilters,
                        }"
                    >
                        <select
                            v-model="selectedCondition"
                            :disabled="isFiltering"
                            class="h-10 w-full rounded-lg border border-black/10 bg-white px-3 text-sm text-[#1b1b18] outline-none transition-all duration-200 focus:border-[#f53003]/50 focus:ring-2 focus:ring-[#f53003]/10 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-[#0f0f0e] dark:text-[#EDEDEC]"
                            @change="applyFilters"
                        >
                            <option value="all">
                                Semua Kondisi
                            </option>

                            <option value="good">
                                Baik
                            </option>

                            <option value="damaged_light">
                                Rusak Ringan
                            </option>
                        </select>
                    </div>

                    <div
                        :class="{
                            'hidden lg:block': !showFilters,
                        }"
                    >
                        <select
                            v-model="sortBy"
                            :disabled="isFiltering"
                            class="h-10 w-full rounded-lg border border-black/10 bg-white px-3 text-sm text-[#1b1b18] outline-none transition-all duration-200 focus:border-[#f53003]/50 focus:ring-2 focus:ring-[#f53003]/10 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-[#0f0f0e] dark:text-[#EDEDEC]"
                            @change="applyFilters"
                        >
                            <option value="asset_asc">
                                Kode Aset A-Z
                            </option>

                            <option value="asset_desc">
                                Kode Aset Z-A
                            </option>

                            <option value="item_asc">
                                Nama Barang A-Z
                            </option>

                            <option value="item_desc">
                                Nama Barang Z-A
                            </option>
                        </select>
                    </div>

                    <button
                        type="button"
                        :disabled="
                            isFiltering ||
                            isNavigating
                        "
                        class="h-10 rounded-lg bg-[#f53003] px-5 text-sm font-bold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#d92b02] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60 dark:bg-[#FF4433] dark:hover:bg-[#ff5a4c]"
                        @click="applyFilters"
                    >
                        <span
                            v-if="
                                isFiltering ||
                                isNavigating
                            "
                            class="inline-flex items-center justify-center gap-2"
                        >
                            <svg
                                class="h-4 w-4 animate-spin"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />

                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                />
                            </svg>

                            Memuat...
                        </span>

                        <span v-else>
                            Terapkan
                        </span>
                    </button>
                </div>

                <div
                    v-if="
                        showFilters &&
                        hasActiveFilters
                    "
                    class="mt-3 lg:hidden"
                >
                    <button
                        type="button"
                        :disabled="isFiltering"
                        class="h-10 w-full rounded-lg border border-black/10 bg-white px-4 text-sm font-semibold text-[#706f6c] transition-all duration-200 hover:bg-black/[0.03] disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:bg-[#0f0f0e] dark:text-[#A1A09A] dark:hover:bg-white/[0.04]"
                        @click="resetFilters"
                    >
                        Reset Filter
                    </button>
                </div>

                <div
                    v-if="hasActiveFilters"
                    class="mt-3 hidden items-center justify-between lg:flex"
                >
                    <p
                        class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Filter aktif diterapkan dari server.
                    </p>

                    <button
                        type="button"
                        :disabled="isFiltering"
                        class="text-xs font-semibold text-[#f53003] transition-colors hover:underline disabled:cursor-not-allowed disabled:opacity-50 dark:text-[#FF4433]"
                        @click="resetFilters"
                    >
                        Reset semua filter
                    </button>
                </div>
            </div>

            <div
                v-if="showSkeleton"
                class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                aria-busy="true"
                aria-label="Memuat katalog inventaris"
            >
                <article
                    v-for="index in 6"
                    :key="`inventory-skeleton-${index}`"
                    class="overflow-hidden rounded-xl border border-black/5 bg-white shadow-sm dark:border-white/10 dark:bg-[#161615]"
                >
                    <div
                        class="h-1 animate-pulse bg-black/[0.06] dark:bg-white/[0.06]"
                    />

                    <div class="flex flex-col p-5">
                        <div
                            class="flex items-start justify-between gap-3"
                        >
                            <div class="min-w-0 flex-1">
                                <div
                                    class="h-3 w-24 animate-pulse rounded bg-black/[0.08] dark:bg-white/[0.08]"
                                />

                                <div
                                    class="mt-3 h-5 w-3/4 animate-pulse rounded bg-black/[0.08] dark:bg-white/[0.08]"
                                />

                                <div
                                    class="mt-2 h-5 w-1/2 animate-pulse rounded bg-black/[0.06] dark:bg-white/[0.06]"
                                />
                            </div>

                            <div
                                class="h-6 w-20 shrink-0 animate-pulse rounded-full bg-black/[0.07] dark:bg-white/[0.07]"
                            />
                        </div>

                        <div
                            class="mt-5 rounded-lg border border-black/5 bg-black/[0.02] p-3 dark:border-white/10 dark:bg-white/[0.025]"
                        >
                            <div
                                class="h-3 w-28 animate-pulse rounded bg-black/[0.07] dark:bg-white/[0.07]"
                            />

                            <div
                                class="mt-2 h-4 w-4/5 animate-pulse rounded bg-black/[0.08] dark:bg-white/[0.08]"
                            />

                            <div
                                class="mt-2 h-3 w-12 animate-pulse rounded bg-black/[0.06] dark:bg-white/[0.06]"
                            />
                        </div>

                        <div class="mt-4 flex items-start gap-3">
                            <div
                                class="h-8 w-8 shrink-0 animate-pulse rounded-lg bg-black/[0.06] dark:bg-white/[0.06]"
                            />

                            <div class="min-w-0 flex-1">
                                <div
                                    class="h-3 w-16 animate-pulse rounded bg-black/[0.06] dark:bg-white/[0.06]"
                                />

                                <div
                                    class="mt-2 h-4 w-3/4 animate-pulse rounded bg-black/[0.08] dark:bg-white/[0.08]"
                                />

                                <div
                                    class="mt-2 h-3 w-14 animate-pulse rounded bg-black/[0.06] dark:bg-white/[0.06]"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-5 flex items-center gap-2"
                        >
                            <div
                                class="h-2 w-2 animate-pulse rounded-full bg-black/10 dark:bg-white/10"
                            />

                            <div
                                class="h-3 w-40 animate-pulse rounded bg-black/[0.06] dark:bg-white/[0.06]"
                            />
                        </div>

                        <div
                            class="mt-5 h-11 w-full animate-pulse rounded-lg bg-black/[0.07] dark:bg-white/[0.07]"
                        />
                    </div>
                </article>
            </div>

            <div
                v-else-if="hasInventory"
                class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
            >
                <article
                    v-for="inventory in roomInventories.data"
                    :key="inventory.id"
                    class="group flex min-w-0 flex-col overflow-hidden rounded-xl border border-black/5 bg-white shadow-sm transition-all duration-300 ease-out hover:-translate-y-1 hover:border-[#f53003]/20 hover:shadow-lg hover:shadow-[#f53003]/5 dark:border-white/10 dark:bg-[#161615] dark:hover:border-[#FF4433]/25 dark:hover:shadow-black/20"
                >
                    <div
                        class="h-1 shrink-0 bg-gradient-to-r from-[#f53003] to-[#ff8066]"
                    />

                    <div
                        class="flex flex-1 flex-col p-4 sm:p-5"
                    >
                        <div
                            class="flex items-start justify-between gap-3"
                        >
                            <div class="min-w-0 flex-1">
                                <div
                                    class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-[#f53003] dark:text-[#FF4433]"
                                >
                                    <span
                                        class="h-2 w-2 shrink-0 rounded-full bg-current"
                                    />

                                    <span class="truncate">
                                        {{
                                            inventory.asset_code
                                        }}
                                    </span>
                                </div>

                                <h2
                                    class="line-clamp-2 break-words text-base font-bold text-[#1b1b18] sm:text-lg dark:text-[#EDEDEC]"
                                >
                                    {{
                                        inventory.item
                                            ?.name ??
                                        'Inventaris'
                                    }}
                                </h2>
                            </div>

                            <span
                                class="max-w-[45%] shrink-0 rounded-full border px-2.5 py-1 text-center text-[10px] font-semibold leading-4 sm:text-[11px]"
                                :class="
                                    getConditionClass(
                                        inventory.condition,
                                    )
                                "
                            >
                                {{
                                    getConditionLabel(
                                        inventory.condition,
                                    )
                                }}
                            </span>
                        </div>

                        <div
                            class="mt-5 rounded-lg border border-[#f53003]/10 bg-[#f53003]/[0.035] p-3 transition-all duration-300 group-hover:border-[#f53003]/15 dark:border-[#FF4433]/10 dark:bg-[#FF4433]/[0.05] dark:group-hover:border-[#FF4433]/20"
                        >
                            <p
                                class="text-[11px] font-semibold uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Fakultas Pemilik
                            </p>

                            <p
                                class="mt-1 break-words font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    inventory.room
                                        ?.faculty?.name ??
                                    'Fakultas tidak tersedia'
                                }}
                            </p>

                            <p
                                v-if="
                                    inventory.room
                                        ?.faculty?.code
                                "
                                class="mt-0.5 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{
                                    inventory.room
                                        .faculty.code
                                }}
                            </p>
                        </div>

                        <div class="mt-4">
                            <div class="flex items-start gap-3">
                                <div
                                    class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-black/[0.04] text-[#706f6c] transition-colors duration-300 group-hover:bg-[#f53003]/10 group-hover:text-[#f53003] dark:bg-white/[0.06] dark:text-[#A1A09A] dark:group-hover:bg-[#FF4433]/10 dark:group-hover:text-[#FF4433]"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5M9 9h.01M15 9h.01"
                                        />
                                    </svg>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        Ruangan
                                    </p>

                                    <p
                                        class="break-words text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                    >
                                        {{
                                            inventory.room
                                                ?.name ??
                                            'Ruangan tidak tersedia'
                                        }}
                                    </p>

                                    <p
                                        v-if="
                                            inventory.room
                                                ?.code
                                        "
                                        class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        {{
                                            inventory.room
                                                .code
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="inventory.notes"
                            class="mt-4 rounded-lg border border-black/5 bg-black/[0.02] p-3 dark:border-white/10 dark:bg-white/[0.025]"
                        >
                            <p
                                class="line-clamp-3 break-words text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{ inventory.notes }}
                            </p>
                        </div>

                        <div
                            class="mt-5 flex items-center gap-2 text-xs font-medium text-emerald-600 dark:text-emerald-400"
                        >
                            <span
                                class="h-2 w-2 shrink-0 rounded-full bg-emerald-500"
                            />

                            <span>
                                Dapat diajukan untuk peminjaman
                            </span>
                        </div>

                        <div class="mt-auto pt-5">
                            <Link
                                :href="
                                    getBorrowingUrl(
                                        inventory.id,
                                    )
                                "
                                class="flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#f53003] px-4 text-sm font-bold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#d92b02] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#f53003]/30 focus:ring-offset-2 dark:bg-[#FF4433] dark:hover:bg-[#ff5a4c] dark:focus:ring-[#FF4433]/30 dark:focus:ring-offset-[#161615]"
                            >
                                <span>
                                    Ajukan Peminjaman
                                </span>

                                <svg
                                    class="h-4 w-4 shrink-0 transition-transform duration-200 group-hover:translate-x-0.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M5 12h14M13 6l6 6-6 6"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </article>
            </div>

            <div
                v-else
                class="rounded-xl border border-dashed border-black/10 bg-white px-5 py-14 text-center shadow-sm sm:px-6 sm:py-16 dark:border-white/10 dark:bg-[#161615]"
            >
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#f53003]/10 text-[#f53003] dark:bg-[#FF4433]/10 dark:text-[#FF4433]"
                >
                    <svg
                        class="h-7 w-7"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    >
                        <circle
                            cx="11"
                            cy="11"
                            r="7"
                        />

                        <path d="m20 20-4-4" />
                    </svg>
                </div>

                <h2
                    class="mt-4 text-lg font-bold text-[#1b1b18] dark:text-[#EDEDEC]"
                >
                    Inventaris tidak ditemukan
                </h2>

                <p
                    class="mx-auto mt-2 max-w-md text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]"
                >
                    Tidak ada inventaris yang sesuai
                    dengan pencarian atau filter yang
                    kamu pilih.
                </p>

                <button
                    v-if="hasActiveFilters"
                    type="button"
                    :disabled="isFiltering"
                    class="mt-5 inline-flex h-10 items-center justify-center rounded-lg bg-[#f53003] px-4 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#d92b02] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#FF4433] dark:hover:bg-[#ff5a4c]"
                    @click="resetFilters"
                >
                    Reset Filter
                </button>
            </div>

            <div
                v-if="
                    !showSkeleton &&
                    lastPage > 1
                "
                class="flex flex-col gap-4 rounded-xl border border-black/5 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-[#161615] sm:flex-row sm:items-center sm:justify-between"
            >
                <div
                    class="text-center text-sm text-[#706f6c] sm:text-left dark:text-[#A1A09A]"
                >
                    Menampilkan

                    <span
                        class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        {{ from.toLocaleString('id-ID') }}
                    </span>

                    -

                    <span
                        class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        {{ to.toLocaleString('id-ID') }}
                    </span>

                    dari

                    <span
                        class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        {{ totalInventory.toLocaleString('id-ID') }}
                    </span>

                    inventaris
                </div>

                <div
                    class="flex max-w-full items-center justify-center overflow-x-auto pb-1 sm:justify-end"
                >
                    <div class="flex min-w-max items-center gap-1">
                        <template
                            v-for="(
                                link, index
                            ) in roomInventories.links"
                            :key="`${index}-${link.label}`"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                                preserve-state
                                :class="[
                                    'inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg px-3 py-2 text-sm font-semibold transition-all duration-200',
                                    link.active
                                        ? 'bg-[#f53003] text-white shadow-sm dark:bg-[#FF4433]'
                                        : 'border border-black/10 bg-white text-[#706f6c] hover:-translate-y-0.5 hover:bg-black/[0.03] hover:shadow-sm dark:border-white/10 dark:bg-[#0f0f0e] dark:text-[#A1A09A] dark:hover:bg-white/[0.04]',
                                ]"
                            >
                                <span
                                    v-html="
                                        link.label
                                    "
                                />
                            </Link>

                            <span
                                v-else
                                class="inline-flex min-h-9 min-w-9 cursor-not-allowed items-center justify-center rounded-lg px-3 py-2 text-sm text-[#706f6c]/40 dark:text-[#A1A09A]/40"
                            >
                                <span
                                    v-html="
                                        link.label
                                    "
                                />
                            </span>
                        </template>
                    </div>
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
    .animate-blob,
    .animate-pulse {
        animation: none !important;
    }
}
</style>