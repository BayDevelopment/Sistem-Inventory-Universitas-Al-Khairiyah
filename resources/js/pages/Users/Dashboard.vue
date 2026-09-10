<script setup lang="ts">
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

interface RoomInventoryOption {
    id: number;
    asset_code: string;
    condition: string;
    room: {
        id: number;
        name: string;
        code: string;
    };
    item: {
        id: number;
        name: string;
    };
}

type BorrowingStatus =
    | 'pending'
    | 'approved'
    | 'borrowed'
    | 'returned'
    | 'rejected'
    | 'cancelled';

interface Borrowing {
    id: number;
    faculty_id: number;
    room_inventory_id: number;
    borrow_date: string;
    expected_return_date: string;
    actual_return_date: string | null;
    purpose: string;
    status: BorrowingStatus;
    rejection_note: string | null;
    created_at: string;
    room_inventory: RoomInventoryOption | null;
}

interface Props {
    borrowings?: Borrowing[];
    roomInventories?: RoomInventoryOption[];
    availableAssetCount?: number;
}

const props = withDefaults(defineProps<Props>(), {
    borrowings: () => [],
    roomInventories: () => [],
    availableAssetCount: 0,
});

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

const CATALOG_URL = '/user/katalog';
const BORROWING_CREATE_URL = '/peminjaman/create';

const isNavigating = ref(false);

let removeStartListener: (() => void) | undefined;
let removeFinishListener: (() => void) | undefined;
let removeErrorListener: (() => void) | undefined;

onMounted(() => {
    removeStartListener = router.on('start', (event) => {
        const method = String(event.detail.visit.method).toLowerCase();

        isNavigating.value = method === 'get';
    });

    removeFinishListener = router.on('finish', () => {
        isNavigating.value = false;
    });

    removeErrorListener = router.on('error', () => {
        isNavigating.value = false;
    });
});

onBeforeUnmount(() => {
    removeStartListener?.();
    removeFinishListener?.();
    removeErrorListener?.();
});

const showSkeleton = computed(() => isNavigating.value);

const EDITABLE_STATUSES: BorrowingStatus[] = [
    'pending',
    'rejected',
];

const CANCELLABLE_STATUSES: BorrowingStatus[] = [
    'pending',
];

const canEdit = (borrowing: Borrowing) =>
    EDITABLE_STATUSES.includes(borrowing.status);

const canCancel = (borrowing: Borrowing) =>
    CANCELLABLE_STATUSES.includes(borrowing.status);

const statusMeta: Record<
    BorrowingStatus,
    {
        label: string;
        classes: string;
    }
> = {
    pending: {
        label: 'Menunggu',
        classes:
            'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400',
    },
    approved: {
        label: 'Disetujui',
        classes:
            'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400',
    },
    borrowed: {
        label: 'Dipinjam',
        classes:
            'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400',
    },
    returned: {
        label: 'Selesai',
        classes:
            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400',
    },
    rejected: {
        label: 'Ditolak',
        classes:
            'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400',
    },
    cancelled: {
        label: 'Dibatalkan',
        classes:
            'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    },
};

const userStats = computed(() => {
    const active = props.borrowings.filter(
        (borrowing) =>
            borrowing.status === 'approved' ||
            borrowing.status === 'borrowed',
    ).length;

    const pending = props.borrowings.filter(
        (borrowing) => borrowing.status === 'pending',
    ).length;

    const finished = props.borrowings.filter(
        (borrowing) => borrowing.status === 'returned',
    ).length;

    return [
        {
            title: 'Sedang Dipinjam',
            value: `${active} Barang`,
            description:
                active > 0
                    ? 'Barang yang sedang aktif dalam peminjaman'
                    : 'Tidak ada barang yang sedang dipinjam',
            icon: 'archive',
        },
        {
            title: 'Menunggu Persetujuan',
            value: `${pending} Pengajuan`,
            description:
                pending > 0
                    ? 'Menunggu verifikasi admin fakultas'
                    : 'Tidak ada pengajuan yang menunggu',
            icon: 'clock',
        },
        {
            title: 'Riwayat Selesai',
            value: `${finished} Peminjaman`,
            description:
                finished > 0
                    ? 'Peminjaman yang telah dikembalikan'
                    : 'Belum ada riwayat peminjaman selesai',
            icon: 'check-circle',
        },
    ];
});

const formatDate = (value: string | null) => {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const assetLabel = (asset: RoomInventoryOption | null) => {
    if (!asset) {
        return 'Aset tidak ditemukan';
    }

    return `${asset.item.name} (${asset.asset_code})`;
};

const roomLabel = (asset: RoomInventoryOption | null) => {
    if (!asset) {
        return '-';
    }

    return asset.room.name;
};

const getBorrowingUrl = (inventoryId: number) =>
    `${BORROWING_CREATE_URL}?room_inventory_id=${inventoryId}`;

const filterTabs: {
    key: 'all' | BorrowingStatus;
    label: string;
}[] = [
    {
        key: 'all',
        label: 'Semua',
    },
    {
        key: 'pending',
        label: 'Menunggu',
    },
    {
        key: 'approved',
        label: 'Disetujui',
    },
    {
        key: 'borrowed',
        label: 'Dipinjam',
    },
    {
        key: 'returned',
        label: 'Selesai',
    },
    {
        key: 'rejected',
        label: 'Ditolak',
    },
    {
        key: 'cancelled',
        label: 'Dibatalkan',
    },
];

const selectedFilter =
    ref<'all' | BorrowingStatus>('all');

const filteredBorrowings = computed(() => {
    if (selectedFilter.value === 'all') {
        return props.borrowings;
    }

    return props.borrowings.filter(
        (borrowing) =>
            borrowing.status === selectedFilter.value,
    );
});

const isEditModalOpen = ref(false);
const isEditProcessing = ref(false);
const editingBorrowing =
    ref<Borrowing | null>(null);

const editForm = ref({
    faculty_id: 0,
    room_inventory_id: 0,
    borrow_date: '',
    expected_return_date: '',
    purpose: '',
});

const editErrors =
    ref<Record<string, string>>({});

const isResubmit = computed(
    () =>
        editingBorrowing.value?.status ===
        'rejected',
);

const openEditBorrowing = (
    borrowing: Borrowing,
) => {
    if (!canEdit(borrowing)) {
        return;
    }

    editingBorrowing.value = borrowing;
    editErrors.value = {};

    editForm.value = {
        faculty_id: borrowing.faculty_id,
        room_inventory_id:
            borrowing.room_inventory_id,
        borrow_date:
            borrowing.borrow_date?.slice(
                0,
                10,
            ) ?? '',
        expected_return_date:
            borrowing.expected_return_date?.slice(
                0,
                10,
            ) ?? '',
        purpose: borrowing.purpose ?? '',
    };

    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    if (isEditProcessing.value) {
        return;
    }

    isEditModalOpen.value = false;
    editingBorrowing.value = null;
    editErrors.value = {};
};

const submitEditBorrowing = () => {
    const borrowing =
        editingBorrowing.value;

    if (
        !borrowing ||
        isEditProcessing.value
    ) {
        return;
    }

    isEditProcessing.value = true;
    editErrors.value = {};

    router.put(
        `/user/borrowings/${borrowing.id}`,
        editForm.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                isEditModalOpen.value = false;
                editingBorrowing.value = null;
            },
            onError: (errors) => {
                editErrors.value =
                    errors as Record<
                        string,
                        string
                    >;
            },
            onFinish: () => {
                isEditProcessing.value = false;
            },
        },
    );
};

const isConfirmModalOpen = ref(false);
const confirmMessage = ref('');
const isCancelling = ref(false);
const cancellingBorrowing =
    ref<Borrowing | null>(null);

const confirmCancelBorrowing = (
    borrowing: Borrowing,
) => {
    if (
        !canCancel(borrowing) ||
        isCancelling.value
    ) {
        return;
    }

    cancellingBorrowing.value =
        borrowing;

    confirmMessage.value =
        `Batalkan pengajuan peminjaman "${assetLabel(
            borrowing.room_inventory,
        )}"? Tindakan ini tidak dapat diurungkan.`;

    isConfirmModalOpen.value = true;
};

const closeConfirmModal = () => {
    if (isCancelling.value) {
        return;
    }

    isConfirmModalOpen.value = false;
    cancellingBorrowing.value = null;
    confirmMessage.value = '';
};

const executeCancelBorrowing = () => {
    const borrowing =
        cancellingBorrowing.value;

    if (
        !borrowing ||
        isCancelling.value
    ) {
        return;
    }

    isCancelling.value = true;

    router.put(
        `/user/borrowings/${borrowing.id}`,
        {
            status: 'cancelled',
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isConfirmModalOpen.value = false;
                cancellingBorrowing.value = null;
                confirmMessage.value = '';
            },
            onError: (errors) => {
                console.error(
                    'Gagal membatalkan peminjaman:',
                    errors,
                );
            },
            onFinish: () => {
                isCancelling.value = false;
            },
        },
    );
};

const anyModalOpen = computed(
    () =>
        isEditModalOpen.value ||
        isConfirmModalOpen.value,
);

watch(anyModalOpen, (open) => {
    if (typeof document === 'undefined') {
        return;
    }

    document.body.style.overflow = open
        ? 'hidden'
        : '';
});

const handleEscape = (event: KeyboardEvent) => {
    if (event.key !== 'Escape') {
        return;
    }

    if (
        isEditProcessing.value ||
        isCancelling.value
    ) {
        return;
    }

    if (isEditModalOpen.value) {
        closeEditModal();
        return;
    }

    if (isConfirmModalOpen.value) {
        closeConfirmModal();
    }
};

onMounted(() => {
    window.addEventListener(
        'keydown',
        handleEscape,
    );
});

onBeforeUnmount(() => {
    window.removeEventListener(
        'keydown',
        handleEscape,
    );

    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <Head title="Dashboard Saya - Sistem Inventory" />

    <div
        class="relative flex min-h-screen flex-1 flex-col overflow-hidden bg-[#fdfdfc] dark:bg-[#0f0f0e]"
    >
        <!-- Background blobs -->
        <div
            class="pointer-events-none absolute -left-32 -top-32 h-72 w-72 animate-blob rounded-full bg-[#f53003]/10 blur-3xl"
        />

        <div
            class="pointer-events-none absolute right-[-8rem] top-[30%] h-80 w-80 animate-blob rounded-full bg-[#f53003]/[0.07] blur-3xl animation-delay-2000 dark:bg-[#FF4433]/[0.08]"
        />

        <div
            class="pointer-events-none absolute -bottom-40 -left-24 h-96 w-96 animate-blob rounded-full bg-[#f53003]/[0.06] blur-3xl animation-delay-4000 dark:bg-[#FF4433]/[0.06]"
        />

        <div
            class="pointer-events-none absolute -bottom-40 -right-32 h-96 w-96 animate-blob rounded-full bg-[#f53003]/10 blur-3xl"
        />

        <!-- Grid -->
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
            class="relative z-10 flex flex-1 flex-col gap-6 p-4 opacity-100 transition-opacity duration-750 starting:opacity-0 md:p-6"
        >
            <!-- Skeleton -->
            <template v-if="showSkeleton">
                <section
                    class="relative overflow-hidden rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]"
                    aria-busy="true"
                    aria-label="Memuat dashboard"
                >
                    <div
                        class="h-6 w-32 animate-pulse rounded-full bg-black/[0.07] dark:bg-white/[0.07]"
                    />

                    <div
                        class="mt-4 h-7 w-3/4 animate-pulse rounded bg-black/[0.08] dark:bg-white/[0.08] sm:w-1/2"
                    />

                    <div
                        class="mt-3 h-3 w-full animate-pulse rounded bg-black/[0.06] dark:bg-white/[0.06]"
                    />

                    <div
                        class="mt-2 h-3 w-4/5 animate-pulse rounded bg-black/[0.05] dark:bg-white/[0.05]"
                    />

                    <div class="mt-6 flex flex-wrap gap-2">
                        <div
                            class="h-9 w-40 animate-pulse rounded-lg bg-black/[0.07] dark:bg-white/[0.07]"
                        />

                        <div
                            class="h-9 w-32 animate-pulse rounded-lg bg-black/[0.05] dark:bg-white/[0.05]"
                        />
                    </div>
                </section>

                <section
                    class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="index in 3"
                        :key="`stat-skeleton-${index}`"
                        class="rounded-xl border border-black/5 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#161615]"
                    >
                        <div
                            class="flex items-center justify-between"
                        >
                            <div
                                class="h-3 w-28 animate-pulse rounded bg-black/[0.07] dark:bg-white/[0.07]"
                            />

                            <div
                                class="h-9 w-9 animate-pulse rounded-lg bg-black/[0.06] dark:bg-white/[0.06]"
                            />
                        </div>

                        <div
                            class="mt-5 h-7 w-28 animate-pulse rounded bg-black/[0.08] dark:bg-white/[0.08]"
                        />

                        <div
                            class="mt-2 h-3 w-full animate-pulse rounded bg-black/[0.05] dark:bg-white/[0.05]"
                        />

                        <div
                            class="mt-1 h-3 w-3/4 animate-pulse rounded bg-black/[0.04] dark:bg-white/[0.04]"
                        />
                    </div>
                </section>

                <div
                    class="grid flex-1 items-stretch gap-6 lg:grid-cols-3"
                >
                    <section
                        class="rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615] lg:col-span-2"
                    >
                        <div
                            class="flex items-center justify-between gap-3"
                        >
                            <div>
                                <div
                                    class="h-5 w-48 animate-pulse rounded bg-black/[0.08] dark:bg-white/[0.08]"
                                />

                                <div
                                    class="mt-2 h-3 w-64 animate-pulse rounded bg-black/[0.05] dark:bg-white/[0.05]"
                                />
                            </div>

                            <div
                                class="h-6 w-16 animate-pulse rounded-full bg-black/[0.06] dark:bg-white/[0.06]"
                            />
                        </div>

                        <div
                            class="mt-5 flex flex-wrap gap-1.5"
                        >
                            <div
                                v-for="index in 5"
                                :key="`filter-skeleton-${index}`"
                                class="h-7 w-16 animate-pulse rounded-full bg-black/[0.06] dark:bg-white/[0.06]"
                            />
                        </div>

                        <div class="mt-5 space-y-2">
                            <div
                                v-for="index in 5"
                                :key="`borrowing-skeleton-${index}`"
                                class="rounded-lg border border-black/5 bg-[#FDFDFC] p-4 dark:border-white/10 dark:bg-[#0a0a0a]"
                            >
                                <div
                                    class="flex items-center justify-between gap-4"
                                >
                                    <div
                                        class="min-w-0 flex-1"
                                    >
                                        <div
                                            class="h-4 w-3/5 animate-pulse rounded bg-black/[0.08] dark:bg-white/[0.08]"
                                        />

                                        <div
                                            class="mt-2 h-3 w-2/5 animate-pulse rounded bg-black/[0.05] dark:bg-white/[0.05]"
                                        />

                                        <div
                                            class="mt-2 h-3 w-1/2 animate-pulse rounded bg-black/[0.04] dark:bg-white/[0.04]"
                                        />
                                    </div>

                                    <div
                                        class="h-6 w-20 shrink-0 animate-pulse rounded-full bg-black/[0.06] dark:bg-white/[0.06]"
                                    />
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]"
                    >
                        <div
                            class="flex items-center justify-between"
                        >
                            <div>
                                <div
                                    class="h-4 w-28 animate-pulse rounded bg-black/[0.08] dark:bg-white/[0.08]"
                                />

                                <div
                                    class="mt-2 h-3 w-36 animate-pulse rounded bg-black/[0.05] dark:bg-white/[0.05]"
                                />
                            </div>

                            <div
                                class="h-6 w-8 animate-pulse rounded-full bg-black/[0.06] dark:bg-white/[0.06]"
                            />
                        </div>

                        <div class="mt-5 space-y-2">
                            <div
                                v-for="index in 3"
                                :key="`asset-skeleton-${index}`"
                                class="flex items-center justify-between gap-3 rounded-lg border border-black/5 bg-[#FDFDFC] px-3 py-3 dark:border-white/10 dark:bg-[#0a0a0a]"
                            >
                                <div
                                    class="min-w-0 flex-1"
                                >
                                    <div
                                        class="h-3 w-3/5 animate-pulse rounded bg-black/[0.08] dark:bg-white/[0.08]"
                                    />

                                    <div
                                        class="mt-2 h-2.5 w-4/5 animate-pulse rounded bg-black/[0.05] dark:bg-white/[0.05]"
                                    />
                                </div>

                                <div
                                    class="h-7 w-7 shrink-0 animate-pulse rounded-md bg-black/[0.06] dark:bg-white/[0.06]"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-4 h-9 w-full animate-pulse rounded-lg bg-black/[0.07] dark:bg-white/[0.07]"
                        />
                    </section>
                </div>
            </template>

            <!-- Content -->
            <template v-else>
                <!-- Hero -->
                <section
                    class="relative overflow-hidden rounded-xl border border-black/5 bg-gradient-to-r from-white via-white to-[#fff2f2] p-6 shadow-sm dark:border-white/10 dark:from-[#161615] dark:via-[#161615] dark:to-[#1D0002]"
                >
                    <div
                        class="pointer-events-none absolute -right-16 -top-20 h-48 w-48 animate-blob rounded-full bg-[#f53003]/10 blur-3xl dark:bg-[#FF4433]/10"
                    />

                    <div
                        class="pointer-events-none absolute -bottom-24 right-24 h-40 w-40 animate-blob rounded-full bg-[#f53003]/[0.07] blur-3xl animation-delay-2000 dark:bg-[#FF4433]/10"
                    />

                    <div class="relative z-10 max-w-2xl">
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-[#f53003]/15 bg-[#f53003]/5 px-3 py-1 text-xs font-semibold text-[#f53003] dark:border-[#FF4433]/20 dark:bg-[#FF4433]/10 dark:text-[#FF4433]"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-current"
                            />

                            Portal Peminjaman
                        </span>

                        <h1
                            class="mt-3 text-xl font-bold tracking-tight text-[#1b1b18] sm:text-2xl dark:text-[#EDEDEC]"
                        >
                            Selamat Datang di Sistem Inventaris Kampus
                        </h1>

                        <p
                            class="mt-1.5 max-w-xl text-xs leading-relaxed text-[#706f6c] sm:text-sm dark:text-[#A1A09A]"
                        >
                            Cari aset yang tersedia di berbagai fakultas,
                            ajukan peminjaman, dan pantau status pengajuan
                            langsung dari dashboard.
                        </p>

                        <div
                            class="mt-5 flex flex-wrap items-center gap-2.5"
                        >
                            <Link
                                :href="BORROWING_CREATE_URL"
                                class="inline-flex items-center gap-2 rounded-lg bg-[#f53003] px-4 py-2.5 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#d92b02] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#f53003]/30 dark:bg-[#FF4433] dark:hover:bg-[#ff5a4c]"
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

                                <span>Ajukan Peminjaman</span>
                            </Link>

                            <Link
                                :href="CATALOG_URL"
                                class="inline-flex items-center gap-2 rounded-lg border border-black/10 bg-white px-4 py-2.5 text-xs font-bold text-[#1b1b18] shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-black/[0.03] hover:shadow-md dark:border-white/10 dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-white/[0.04]"
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
                                        d="m21 21-4.35-4.35m1.1-5.4a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"
                                    />
                                </svg>

                                <span>Lihat Katalog</span>
                            </Link>
                        </div>
                    </div>
                </section>

                <!-- Stats -->
                <section
                    class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="(stat, index) in userStats"
                        :key="index"
                        class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                    >
                        <div
                            class="flex items-center justify-between"
                        >
                            <span
                                class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{ stat.title }}
                            </span>

                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#f53003]/10 text-[#f53003] transition-all duration-300 group-hover:bg-[#f53003] group-hover:text-white dark:bg-[#FF4433]/10 dark:text-[#FF4433] dark:group-hover:bg-[#FF4433] dark:group-hover:text-white"
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
                                        d="m9 12.75 2.25 2.25L15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
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

                            <div
                                class="mt-1 text-xs leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{ stat.description }}
                            </div>
                        </div>

                        <div
                            class="absolute bottom-0 left-0 h-0.5 w-full bg-[#f53003] opacity-0 transition-opacity duration-300 group-hover:opacity-100 dark:bg-[#FF4433]"
                        />
                    </div>
                </section>

                <!-- Main content -->
                <div
                    class="grid flex-1 items-stretch gap-6 lg:grid-cols-3"
                >
                    <!-- Borrowings -->
                    <section
                        class="flex min-h-full flex-col rounded-xl border border-black/5 bg-white p-6 shadow-sm transition-shadow duration-300 hover:shadow-md dark:border-white/10 dark:bg-[#161615] lg:col-span-2"
                    >
                        <div
                            class="mb-5 flex flex-wrap items-center justify-between gap-3"
                        >
                            <div>
                                <h2
                                    class="text-lg font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Status Peminjaman Saya
                                </h2>

                                <p
                                    class="mt-0.5 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Pantau status pengajuan dan pengembalian
                                    aset yang Anda ajukan.
                                </p>
                            </div>

                            <span
                                class="rounded-full bg-black/[0.04] px-2.5 py-1 text-[10px] font-semibold text-[#706f6c] dark:bg-white/[0.06] dark:text-[#A1A09A]"
                            >
                                {{ props.borrowings.length }} Data
                            </span>
                        </div>

                        <div
                            class="mb-5 flex flex-wrap gap-1.5"
                        >
                            <button
                                v-for="tab in filterTabs"
                                :key="tab.key"
                                type="button"
                                class="rounded-full px-3 py-1.5 text-[11px] font-semibold transition-all duration-200"
                                :class="
                                    selectedFilter === tab.key
                                        ? 'bg-[#f53003] text-white shadow-sm dark:bg-[#FF4433]'
                                        : 'bg-black/[0.04] text-[#706f6c] hover:bg-black/[0.08] dark:bg-white/[0.06] dark:text-[#A1A09A] dark:hover:bg-white/[0.10]'
                                "
                                @click="selectedFilter = tab.key"
                            >
                                {{ tab.label }}
                            </button>
                        </div>

                        <!-- Empty state -->
                        <div
                            v-if="filteredBorrowings.length === 0"
                            class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-black/10 bg-[#FDFDFC] p-8 dark:border-white/10 dark:bg-[#0a0a0a]"
                        >
                            <div class="text-center">
                                <div
                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#f53003]/10 text-[#f53003] dark:bg-[#FF4433]/10 dark:text-[#FF4433]"
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
                                            d="M9 12h3.75M9 15h3.75M9 18h3.75m3-6h.008v.008H15.75V12Zm0 3h.008v.008H15.75V15Zm0 3h.008v.008H15.75V18M6 10.5h12a2.25 2.25 0 0 1 2.25 2.25v6A2.25 2.25 0 0 1 18 21H6a2.25 2.25 0 0 1-2.25-2.25v-6A2.25 2.25 0 0 1 6 10.5Z"
                                        />
                                    </svg>
                                </div>

                                <h3
                                    class="mt-3 text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{
                                        selectedFilter === 'all'
                                            ? 'Belum Ada Aktivitas Peminjaman'
                                            : 'Tidak Ada Data untuk Filter Ini'
                                    }}
                                </h3>

                                <p
                                    class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Telusuri katalog barang kampus dan buat
                                    pengajuan peminjaman sesuai kebutuhan
                                    Anda.
                                </p>

                                <Link
                                    :href="CATALOG_URL"
                                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#f53003] px-3.5 py-2 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#d92b02] hover:shadow-md dark:bg-[#FF4433] dark:hover:bg-[#ff5a4c]"
                                >
                                    Lihat Katalog

                                    <svg
                                        class="h-3.5 w-3.5"
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

                        <!-- Borrowing list -->
                        <ul
                            v-else
                            class="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1"
                        >
                            <li
                                v-for="borrowing in filteredBorrowings"
                                :key="borrowing.id"
                                class="group flex flex-col gap-3 rounded-lg border border-black/5 bg-[#FDFDFC] p-4 transition-all duration-200 hover:border-[#f53003]/20 hover:shadow-sm dark:border-white/10 dark:bg-[#0a0a0a] dark:hover:border-[#FF4433]/20 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <p
                                            class="truncate text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                            :title="
                                                assetLabel(
                                                    borrowing.room_inventory,
                                                )
                                            "
                                        >
                                            {{
                                                assetLabel(
                                                    borrowing.room_inventory,
                                                )
                                            }}
                                        </p>

                                        <span
                                            class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="
                                                statusMeta[
                                                    borrowing.status
                                                ].classes
                                            "
                                        >
                                            {{
                                                statusMeta[
                                                    borrowing.status
                                                ].label
                                            }}
                                        </span>
                                    </div>

                                    <div
                                        class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        <span>
                                            {{
                                                roomLabel(
                                                    borrowing.room_inventory,
                                                )
                                            }}
                                        </span>

                                        <span class="hidden sm:inline">
                                            •
                                        </span>

                                        <span>
                                            {{
                                                formatDate(
                                                    borrowing.borrow_date,
                                                )
                                            }}
                                            →
                                            {{
                                                formatDate(
                                                    borrowing.expected_return_date,
                                                )
                                            }}
                                        </span>
                                    </div>

                                    <p
                                        class="mt-1 truncate text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                        :title="borrowing.purpose"
                                    >
                                        Keperluan:
                                        {{ borrowing.purpose }}
                                    </p>

                                    <p
                                        v-if="
                                            borrowing.status === 'rejected' &&
                                            borrowing.rejection_note
                                        "
                                        class="mt-1 text-xs italic text-red-600 dark:text-red-400"
                                    >
                                        Alasan ditolak:
                                        {{
                                            borrowing.rejection_note
                                        }}
                                    </p>
                                </div>

                                <div
                                    v-if="
                                        canEdit(borrowing) ||
                                        canCancel(borrowing)
                                    "
                                    class="flex shrink-0 items-center gap-1"
                                >
                                    <button
                                        v-if="canEdit(borrowing)"
                                        type="button"
                                        class="rounded-md p-1.5 text-[#706f6c] transition-all duration-200 hover:bg-[#f53003]/10 hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:bg-[#FF4433]/10 dark:hover:text-[#FF4433]"
                                        :title="
                                            borrowing.status === 'rejected'
                                                ? 'Ajukan Ulang'
                                                : 'Edit Pengajuan'
                                        "
                                        @click="
                                            openEditBorrowing(
                                                borrowing,
                                            )
                                        "
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
                                        v-if="canCancel(borrowing)"
                                        type="button"
                                        class="rounded-md p-1.5 text-[#706f6c] transition-all duration-200 hover:bg-red-50 hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:bg-[#1D0002] dark:hover:text-[#FF4433]"
                                        title="Batalkan Pengajuan"
                                        @click="
                                            confirmCancelBorrowing(
                                                borrowing,
                                            )
                                        "
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
                                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m0 0L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m0 0a48.11 48.11 0 0 0-3.478.397m-7.5 0a48.108 48.108 0 0 1-3.478.397m3.478-.397v-.916c0-1.18.91-2.164 2.09-2.201a51.964 51.964 0 0 1 3.32 0c1.18.037 2.09 1.022 2.09 2.201v.916"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </section>

                    <!-- Available assets -->
                    <section
                        class="flex min-h-full flex-col rounded-xl border border-black/5 bg-white p-6 shadow-sm transition-shadow duration-300 hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                    >
                        <div
                            class="mb-5 flex items-center justify-between gap-3"
                        >
                            <div>
                                <h2
                                    class="text-sm font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Aset Tersedia
                                </h2>

                                <p
                                    class="mt-0.5 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    {{ props.availableAssetCount }}
                                    aset yang dapat diajukan.
                                </p>
                            </div>

                            <span
                                class="rounded-full bg-black/[0.04] px-2 py-1 text-[10px] font-semibold text-[#706f6c] dark:bg-white/[0.06] dark:text-[#A1A09A]"
                            >
                                {{ props.availableAssetCount }}
                            </span>
                        </div>

                        <div
                            v-if="props.roomInventories.length === 0"
                            class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-black/10 p-6 text-center text-xs text-[#706f6c] dark:border-white/10 dark:text-[#A1A09A]"
                        >
                            <div>
                                <div
                                    class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-[#f53003]/10 text-[#f53003] dark:bg-[#FF4433]/10 dark:text-[#FF4433]"
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
                                            d="M20.25 7.5 12 3.75 3.75 7.5m16.5 0v9L12 20.25l-8.25-3.75v-9m16.5 0L12 11.25m0 0L3.75 7.5M12 11.25v9"
                                        />
                                    </svg>
                                </div>

                                <p class="mt-2 font-medium">
                                    Belum ada aset tersedia.
                                </p>

                                <p class="mt-1 text-[11px]">
                                    Saat ini belum terdapat barang yang dapat
                                    dipinjam.
                                </p>
                            </div>
                        </div>

                        <ul
                            v-else
                            class="flex-1 space-y-2"
                        >
                            <li
                                v-for="asset in props.roomInventories"
                                :key="asset.id"
                                class="group flex items-center justify-between gap-3 rounded-lg border border-black/5 bg-[#FDFDFC] px-3 py-3 transition-all duration-200 hover:border-[#f53003]/20 hover:shadow-sm dark:border-white/10 dark:bg-[#0a0a0a] dark:hover:border-[#FF4433]/20"
                            >
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                        :title="asset.item.name"
                                    >
                                        {{ asset.item.name }}
                                    </p>

                                    <p
                                        class="mt-0.5 truncate text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        {{ asset.asset_code }}
                                        ·
                                        {{ asset.room.name }}
                                    </p>
                                </div>

                                <Link
                                    :href="
                                        getBorrowingUrl(
                                            asset.id,
                                        )
                                    "
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[#f53003]/10 text-[#f53003] transition-all duration-200 hover:bg-[#f53003] hover:text-white dark:bg-[#FF4433]/10 dark:text-[#FF4433] dark:hover:bg-[#FF4433] dark:hover:text-white"
                                    title="Ajukan Peminjaman"
                                    :aria-label="`Ajukan peminjaman ${asset.item.name}`"
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
                                            d="M12 4.5v15m7.5-7.5h-15"
                                        />
                                    </svg>
                                </Link>
                            </li>
                        </ul>

                        <div
                            v-if="props.roomInventories.length > 0"
                            class="mt-4 border-t border-black/5 pt-4 dark:border-white/10"
                        >
                            <Link
                                :href="CATALOG_URL"
                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#1b1b18] px-3 py-2.5 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-black hover:shadow-md dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white"
                            >
                                <span>Lihat Semua Katalog</span>

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
                                        d="M13.5 6H18m0 0v4.5M18 6l-7.5 7.5"
                                    />
                                </svg>
                            </Link>
                        </div>
                    </section>
                </div>
            </template>
        </div>
    </div>

    <!-- Edit modal -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isEditModalOpen"
            class="fixed inset-0 z-[110] flex items-center justify-center p-4"
        >
            <div
                class="absolute inset-0 bg-black/50 backdrop-blur-sm dark:bg-black/70"
                @click="closeEditModal"
            />

            <div
                class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-black/5 bg-white p-6 shadow-2xl dark:border-white/10 dark:bg-[#161615]"
            >
                <div
                    class="pointer-events-none absolute -right-16 -top-16 h-36 w-36 rounded-full bg-[#f53003]/10 blur-3xl dark:bg-[#FF4433]/10"
                />

                <div
                    class="relative flex items-start justify-between gap-4"
                >
                    <div>
                        <span
                            class="inline-flex rounded-full bg-[#f53003]/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-[#f53003] dark:bg-[#FF4433]/10 dark:text-[#FF4433]"
                        >
                            {{
                                isResubmit
                                    ? 'Ajukan Ulang'
                                    : 'Edit'
                            }}
                        </span>

                        <h3
                            class="mt-2 text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{
                                isResubmit
                                    ? 'Ajukan Ulang Peminjaman'
                                    : 'Edit Pengajuan Peminjaman'
                            }}
                        </h3>

                        <p
                            v-if="isResubmit"
                            class="mt-1 text-xs leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Pengajuan sebelumnya ditolak. Perubahan
                            yang disimpan akan diajukan kembali untuk
                            persetujuan admin.
                        </p>
                    </div>

                    <button
                        type="button"
                        :disabled="isEditProcessing"
                        class="rounded-lg p-2 text-[#706f6c] transition hover:bg-black/[0.04] hover:text-[#1b1b18] disabled:opacity-50 dark:text-[#A1A09A] dark:hover:bg-white/[0.06] dark:hover:text-[#EDEDEC]"
                        @click="closeEditModal"
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
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <form
                    class="relative mt-5 space-y-4"
                    @submit.prevent="submitEditBorrowing"
                >
                    <div>
                        <label
                            class="text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Aset
                        </label>

                        <select
                            v-model.number="
                                editForm.room_inventory_id
                            "
                            class="mt-1.5 w-full rounded-lg border border-black/10 bg-white px-3 py-2.5 text-sm text-[#1b1b18] outline-none transition focus:border-[#f53003]/50 focus:ring-2 focus:ring-[#f53003]/10 dark:border-white/10 dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                        >
                            <option
                                v-for="asset in roomInventories"
                                :key="asset.id"
                                :value="asset.id"
                            >
                                {{ assetLabel(asset) }}
                            </option>
                        </select>

                        <p
                            v-if="editErrors.room_inventory_id"
                            class="mt-1 text-[11px] text-red-600 dark:text-red-400"
                        >
                            {{ editErrors.room_inventory_id }}
                        </p>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                    >
                        <div>
                            <label
                                class="text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Tanggal Pinjam
                            </label>

                            <input
                                v-model="editForm.borrow_date"
                                type="date"
                                class="mt-1.5 w-full rounded-lg border border-black/10 bg-white px-3 py-2.5 text-sm text-[#1b1b18] outline-none transition focus:border-[#f53003]/50 focus:ring-2 focus:ring-[#f53003]/10 dark:border-white/10 dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                            />

                            <p
                                v-if="editErrors.borrow_date"
                                class="mt-1 text-[11px] text-red-600 dark:text-red-400"
                            >
                                {{ editErrors.borrow_date }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Tanggal Kembali
                            </label>

                            <input
                                v-model="
                                    editForm.expected_return_date
                                "
                                type="date"
                                class="mt-1.5 w-full rounded-lg border border-black/10 bg-white px-3 py-2.5 text-sm text-[#1b1b18] outline-none transition focus:border-[#f53003]/50 focus:ring-2 focus:ring-[#f53003]/10 dark:border-white/10 dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                            />

                            <p
                                v-if="
                                    editErrors.expected_return_date
                                "
                                class="mt-1 text-[11px] text-red-600 dark:text-red-400"
                            >
                                {{
                                    editErrors.expected_return_date
                                }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label
                            class="text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Keperluan
                        </label>

                        <textarea
                            v-model="editForm.purpose"
                            rows="3"
                            class="mt-1.5 w-full resize-none rounded-lg border border-black/10 bg-white px-3 py-2.5 text-sm text-[#1b1b18] outline-none transition focus:border-[#f53003]/50 focus:ring-2 focus:ring-[#f53003]/10 dark:border-white/10 dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
                        />

                        <p
                            v-if="editErrors.purpose"
                            class="mt-1 text-[11px] text-red-600 dark:text-red-400"
                        >
                            {{ editErrors.purpose }}
                        </p>
                    </div>

                    <div
                        class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                    >
                        <button
                            type="button"
                            :disabled="isEditProcessing"
                            class="rounded-lg border border-black/10 bg-white px-4 py-2.5 text-xs font-semibold text-[#1b1b18] transition hover:bg-black/[0.03] disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-white/[0.04]"
                            @click="closeEditModal"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            :disabled="isEditProcessing"
                            class="rounded-lg bg-[#f53003] px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-[#d92b02] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#FF4433] dark:hover:bg-[#ff5a4c]"
                        >
                            {{
                                isEditProcessing
                                    ? 'Menyimpan...'
                                    : isResubmit
                                      ? 'Ajukan Ulang'
                                      : 'Simpan Perubahan'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Transition>

    <!-- Cancel confirmation modal -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isConfirmModalOpen"
            class="fixed inset-0 z-[110] flex items-center justify-center p-4"
        >
            <div
                class="absolute inset-0 bg-black/50 backdrop-blur-sm dark:bg-black/70"
                @click="closeConfirmModal"
            />

            <div
                class="relative w-full max-w-sm overflow-hidden rounded-2xl border border-black/5 bg-white p-6 shadow-2xl dark:border-white/10 dark:bg-[#161615]"
            >
                <div
                    class="pointer-events-none absolute -right-16 -top-16 h-32 w-32 rounded-full bg-red-500/10 blur-3xl"
                />

                <div class="relative">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-400"
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
                        class="mt-4 text-base font-bold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Batalkan Pengajuan?
                    </h3>

                    <p
                        class="mt-2 text-xs leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        {{ confirmMessage }}
                    </p>

                    <div
                        class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                    >
                        <button
                            type="button"
                            :disabled="isCancelling"
                            class="rounded-lg border border-black/10 bg-white px-4 py-2.5 text-xs font-semibold text-[#1b1b18] transition hover:bg-black/[0.03] disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-white/[0.04]"
                            @click="closeConfirmModal"
                        >
                            Tidak
                        </button>

                        <button
                            type="button"
                            :disabled="isCancelling"
                            class="rounded-lg bg-red-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="executeCancelBorrowing"
                        >
                            {{
                                isCancelling
                                    ? 'Membatalkan...'
                                    : 'Ya, Batalkan'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
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