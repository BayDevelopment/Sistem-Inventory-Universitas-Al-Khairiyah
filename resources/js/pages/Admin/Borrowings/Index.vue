<script setup lang="ts">
import { computed, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";

import BorrowingFormModal from "./BorrowingFormModal.vue";
import BorrowingApprovalModal from "./BorrowingApprovalModal.vue";
import BorrowingReturnModal from "./BorrowingReturnModal.vue";
import BorrowingDetailModal from "./BorrowingDetailModal.vue";

/*
|--------------------------------------------------------------------------
| Type
|--------------------------------------------------------------------------
*/

interface Faculty {
    id: number;
    name: string;
    code?: string | null;
}

interface User {
    id: number;
    name: string;
    email?: string | null;
}

interface Room {
    id: number;
    name: string;
    code?: string | null;
    faculty_id?: number;
    is_active?: boolean;
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
    condition?: string | null;
    is_borrowable?: boolean;
    room?: Room | null;
    item?: Item | null;
}

interface Approver {
    id: number;
    name: string;
    email?: string | null;
}

interface Borrowing {
    id: number;
    user_id?: number | null;
    faculty_id?: number | null;
    room_inventory_id?: number | null;

    borrow_date?: string | null;
    expected_return_date?: string | null;
    actual_return_date?: string | null;

    purpose?: string | null;

    applicant_signature?: string | null;
    signed_at?: string | null;

    status?:
        | "pending"
        | "approved"
        | "rejected"
        | "borrowed"
        | "returned"
        | "cancelled"
        | string
        | null;

    approved_by?: number | null;
    approved_at?: string | null;

    approver_signature?: string | null;
    rejection_note?: string | null;

    user?: User | null;
    faculty?: Faculty | null;
    room_inventory?: RoomInventory | null;
    approver?: Approver | null;
}

interface BorrowingFormData {
    faculty_id: number | null;
    room_inventory_id: number | null;
    borrow_date: string;
    expected_return_date: string;
    purpose: string;
    applicant_signature?: string | null;
}

interface BorrowingReturnData {
    actual_return_date: string;
    condition?: string | null;
    return_note?: string | null;
}

interface Props {
    borrowings: Borrowing[];
    faculties: Faculty[];
    roomInventories: RoomInventory[];
}

const props = defineProps<Props>();

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const search = ref("");
const statusFilter = ref("all");

const showFormModal = ref(false);
const showApprovalModal = ref(false);
const showReturnModal = ref(false);
const showDetailModal = ref(false);

const selectedBorrowing = ref<Borrowing | null>(null);

const processing = ref(false);
const errorMessage = ref<string | null>(null);

/*
|--------------------------------------------------------------------------
| Custom Confirmation Modal
|--------------------------------------------------------------------------
*/

const isConfirmModalOpen = ref(false);
const confirmTitle = ref("Konfirmasi");
const confirmMessage = ref("");
const confirmAction = ref<(() => void) | null>(null);
const isCancelling = ref(false);

/*
|--------------------------------------------------------------------------
| Search & Filter
|--------------------------------------------------------------------------
*/

const filteredBorrowings = computed(() => {
    const keyword = search.value.trim().toLowerCase();

    return props.borrowings.filter((borrowing) => {
        const matchesStatus =
            statusFilter.value === "all" ||
            borrowing.status === statusFilter.value;

        if (!matchesStatus) {
            return false;
        }

        if (!keyword) {
            return true;
        }

        const userName = borrowing.user?.name?.toLowerCase() ?? "";
        const userEmail = borrowing.user?.email?.toLowerCase() ?? "";

        const facultyName = borrowing.faculty?.name?.toLowerCase() ?? "";
        const facultyCode = borrowing.faculty?.code?.toLowerCase() ?? "";

        const itemName =
            borrowing.room_inventory?.item?.name?.toLowerCase() ?? "";

        const assetCode =
            borrowing.room_inventory?.asset_code?.toLowerCase() ?? "";

        const roomName =
            borrowing.room_inventory?.room?.name?.toLowerCase() ?? "";

        const roomCode =
            borrowing.room_inventory?.room?.code?.toLowerCase() ?? "";

        const purpose = borrowing.purpose?.toLowerCase() ?? "";

        return (
            userName.includes(keyword) ||
            userEmail.includes(keyword) ||
            facultyName.includes(keyword) ||
            facultyCode.includes(keyword) ||
            itemName.includes(keyword) ||
            assetCode.includes(keyword) ||
            roomName.includes(keyword) ||
            roomCode.includes(keyword) ||
            purpose.includes(keyword)
        );
    });
});

/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

const totalBorrowings = computed(() => {
    return props.borrowings.length;
});

const totalPending = computed(() => {
    return props.borrowings.filter(
        (borrowing) => borrowing.status === "pending",
    ).length;
});

const totalBorrowed = computed(() => {
    return props.borrowings.filter(
        (borrowing) => borrowing.status === "borrowed",
    ).length;
});

const totalReturned = computed(() => {
    return props.borrowings.filter(
        (borrowing) =>
            borrowing.status === "returned" || !!borrowing.actual_return_date,
    ).length;
});

/*
|--------------------------------------------------------------------------
| Modal
|--------------------------------------------------------------------------
*/

function openCreateModal() {
    selectedBorrowing.value = null;
    errorMessage.value = null;
    showFormModal.value = true;
}

function closeFormModal() {
    showFormModal.value = false;
    selectedBorrowing.value = null;
    errorMessage.value = null;
}

function openDetailModal(borrowing: Borrowing) {
    selectedBorrowing.value = borrowing;
    errorMessage.value = null;
    showDetailModal.value = true;
}

function closeDetailModal() {
    showDetailModal.value = false;
    selectedBorrowing.value = null;
    errorMessage.value = null;
}

function openApprovalModal(borrowing: Borrowing) {
    selectedBorrowing.value = borrowing;
    errorMessage.value = null;
    showApprovalModal.value = true;
}

function closeApprovalModal() {
    showApprovalModal.value = false;
    selectedBorrowing.value = null;
    errorMessage.value = null;
}

function openReturnModal(borrowing: Borrowing) {
    selectedBorrowing.value = borrowing;
    errorMessage.value = null;
    showReturnModal.value = true;
}

function closeReturnModal() {
    showReturnModal.value = false;
    selectedBorrowing.value = null;
    errorMessage.value = null;
}

/*
|--------------------------------------------------------------------------
| Store
|--------------------------------------------------------------------------
*/

function submitCreate(data: BorrowingFormData) {
    processing.value = true;
    errorMessage.value = null;

    router.post("/admin/borrowings", data, {
        preserveScroll: true,

        onSuccess: () => {
            closeFormModal();
        },

        onError: (errors) => {
            errorMessage.value =
                Object.values(errors)[0] ??
                "Terjadi kesalahan saat menyimpan data.";
        },

        onFinish: () => {
            processing.value = false;
        },
    });
}

/*
|--------------------------------------------------------------------------
| Approval
|--------------------------------------------------------------------------
*/

function submitApproval(
    borrowing: Borrowing,
    status: "approved" | "rejected",
    rejectionNote?: string | null,
) {
    processing.value = true;
    errorMessage.value = null;

    const payload: Record<string, unknown> = {
        status,
    };

    if (status === "rejected") {
        payload.rejection_note = rejectionNote ?? null;
    }

    router.put(`/admin/borrowings/${borrowing.id}`, payload, {
        preserveScroll: true,

        onSuccess: () => {
            closeApprovalModal();
        },

        onError: (errors) => {
            errorMessage.value =
                Object.values(errors)[0] ??
                "Terjadi kesalahan saat memproses persetujuan.";
        },

        onFinish: () => {
            processing.value = false;
        },
    });
}

function handleApprovalSubmit(
    status: "approved" | "rejected",
    rejectionNote?: string | null,
) {
    if (!selectedBorrowing.value) {
        return;
    }

    submitApproval(selectedBorrowing.value, status, rejectionNote);
}

/*
|--------------------------------------------------------------------------
| Return
|--------------------------------------------------------------------------
*/

function handleReturnSubmit(data: BorrowingReturnData) {
    if (!selectedBorrowing.value) {
        return;
    }

    submitReturn(selectedBorrowing.value, data);
}

function submitReturn(borrowing: Borrowing, data: BorrowingReturnData) {
    processing.value = true;
    errorMessage.value = null;

    const payload: Record<string, unknown> = {
        status: "returned",
        actual_return_date: data.actual_return_date,
    };

    router.put(`/admin/borrowings/${borrowing.id}`, payload, {
        preserveScroll: true,

        onSuccess: () => {
            closeReturnModal();
        },

        onError: (errors) => {
            errorMessage.value =
                Object.values(errors)[0] ??
                "Terjadi kesalahan saat memproses pengembalian.";
        },

        onFinish: () => {
            processing.value = false;
        },
    });
}

/*
|--------------------------------------------------------------------------
| Cancel Confirmation
|--------------------------------------------------------------------------
*/

function cancelBorrowing(borrowing: Borrowing) {
    if (borrowing.status !== "pending" && borrowing.status !== "approved") {
        return;
    }

    confirmTitle.value = "Batalkan Peminjaman?";
    confirmMessage.value =
        `Peminjaman #${borrowing.id} akan dibatalkan. ` +
        `Data peminjaman tetap disimpan sebagai riwayat dan tidak akan dihapus dari sistem.`;

    confirmAction.value = () => {
        isCancelling.value = true;
        errorMessage.value = null;

        router.put(
            `/admin/borrowings/${borrowing.id}`,
            {
                status: "cancelled",
            },
            {
                preserveScroll: true,

                onSuccess: () => {
                    closeConfirmModal();
                },

                onError: (errors) => {
                    errorMessage.value =
                        Object.values(errors)[0] ??
                        "Terjadi kesalahan saat membatalkan peminjaman.";

                    closeConfirmModal();
                },

                onFinish: () => {
                    isCancelling.value = false;
                },
            },
        );
    };

    isConfirmModalOpen.value = true;
}

function cancelConfirmModal() {
    if (isCancelling.value) {
        return;
    }

    isConfirmModalOpen.value = false;
    confirmAction.value = null;
}

function executeConfirmAction() {
    if (!confirmAction.value || isCancelling.value) {
        return;
    }

    confirmAction.value();
}

function closeConfirmModal() {
    isConfirmModalOpen.value = false;
    confirmAction.value = null;
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function formatDate(value?: string | null) {
    if (!value) {
        return "-";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    }).format(date);
}

function statusLabel(status?: string | null) {
    switch (status) {
        case "pending":
            return "Menunggu";

        case "approved":
            return "Disetujui";

        case "rejected":
            return "Ditolak";

        case "borrowed":
            return "Dipinjam";

        case "returned":
            return "Dikembalikan";

        case "cancelled":
            return "Dibatalkan";

        default:
            return status ?? "-";
    }
}

function statusClass(status?: string | null) {
    switch (status) {
        case "pending":
            return "bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400";

        case "approved":
            return "bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400";

        case "borrowed":
            return "bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-400";

        case "returned":
            return "bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400";

        case "rejected":
            return "bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400";

        case "cancelled":
            return "bg-slate-100 text-slate-600 dark:bg-white/5 dark:text-slate-400";

        default:
            return "bg-slate-100 text-slate-600 dark:bg-white/5 dark:text-slate-400";
    }
}
</script>

<template>
    <Head title="Manajemen Peminjaman - Sistem Inventory" />

    <div class="relative flex flex-1 flex-col gap-6 overflow-hidden p-4 md:p-6">
        <!-- Decorative Background -->
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

        <div
            class="relative z-10 flex flex-1 flex-col gap-6 opacity-100 transition-opacity duration-750 starting:opacity-0"
        >
            <!-- BREADCRUMB -->
            <div
                class="flex items-center gap-2 text-xs text-[#706f6c] dark:text-[#A1A09A]"
            >
                <Link
                    href="/admin"
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

                    Dashboard
                </Link>

                <span>/</span>

                <span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                    Manajemen Peminjaman
                </span>
            </div>

            <!-- STATS -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Total Peminjaman
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
                                    d="M9 12.75 11.25 15 15 9.75m-3-7.5 8.25 3v6.75c0 5.25-3.75 8.25-8.25 9.75C7.5 20.25 3.75 17.25 3.75 12V5.25l8.25-3Z"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ totalBorrowings }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Seluruh pengajuan peminjaman
                        </p>
                    </div>
                </div>

                <!-- Pending -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Menunggu
                        </span>

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition group-hover:bg-amber-500 group-hover:text-white dark:bg-amber-950/40 dark:text-amber-400 dark:group-hover:bg-amber-500 dark:group-hover:text-white"
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
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-amber-600 dark:text-amber-400"
                        >
                            {{ totalPending }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Menunggu persetujuan
                        </p>
                    </div>
                </div>

                <!-- Borrowed -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Sedang Dipinjam
                        </span>

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-50 text-violet-600 transition group-hover:bg-violet-500 group-hover:text-white dark:bg-violet-950/40 dark:text-violet-400 dark:group-hover:bg-violet-500 dark:group-hover:text-white"
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
                                    d="M20.25 7.5 12 3 3.75 7.5M20.25 7.5 12 12m8.25-4.5v9L12 21m0-9L3.75 7.5M12 12v9m-8.25-4.5V7.5"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-violet-600 dark:text-violet-400"
                        >
                            {{ totalBorrowed }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Inventaris sedang dipinjam
                        </p>
                    </div>
                </div>

                <!-- Returned -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Dikembalikan
                        </span>

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 transition group-hover:bg-emerald-500 group-hover:text-white dark:bg-emerald-950/40 dark:text-emerald-400 dark:group-hover:bg-emerald-500 dark:group-hover:text-white"
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
                                    d="M9 12.75 11.25 15 15 9.75m6.75 2.25a9.75 9.75 0 1 1-19.5 0Z"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400"
                        >
                            {{ totalReturned }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Peminjaman selesai
                        </p>
                    </div>
                </div>
            </div>

            <!-- MAIN -->
            <div
                class="flex flex-1 flex-col rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]"
            >
                <!-- HEADER -->
                <div
                    class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div>
                        <h2
                            class="text-lg font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Data Peminjaman
                        </h2>

                        <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            Kelola pengajuan, persetujuan, pengembalian, dan
                            riwayat peminjaman inventaris.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="openCreateModal"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#1b1b18] px-3.5 py-2 text-xs font-medium text-white transition hover:bg-black dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white"
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

                        Ajukan Peminjaman
                    </button>
                </div>

                <!-- ERROR -->
                <div
                    v-if="errorMessage"
                    class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-400"
                >
                    {{ errorMessage }}
                </div>

                <!-- FILTER -->
                <div
                    class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <!-- Search -->
                    <div class="relative w-full sm:w-96">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari peminjam, item, aset, ruangan..."
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

                    <!-- Status -->
                    <div class="w-full sm:w-56">
                        <select
                            v-model="statusFilter"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3.5 py-2 text-xs text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        >
                            <option value="all">Semua Status</option>
                            <option value="pending">Menunggu</option>
                            <option value="approved">Disetujui</option>
                            <option value="borrowed">Dipinjam</option>
                            <option value="returned">Dikembalikan</option>
                            <option value="rejected">Ditolak</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>
                </div>

                <!-- EMPTY -->
                <div
                    v-if="filteredBorrowings.length === 0"
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
                        Tidak ada peminjaman ditemukan
                    </h3>

                    <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        Coba gunakan kata kunci atau filter status yang berbeda.
                    </p>
                </div>

                <!-- TABLE -->
                <div
                    v-else
                    class="overflow-hidden rounded-xl border border-[#e3e3e0] dark:border-[#3E3E3A]"
                >
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-[#FDFDFC] dark:bg-[#0a0a0a]">
                                <tr
                                    class="border-b border-[#e3e3e0] text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]"
                                >
                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider"
                                    >
                                        Peminjam
                                    </th>

                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider"
                                    >
                                        Inventaris
                                    </th>

                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider"
                                    >
                                        Fakultas
                                    </th>

                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider"
                                    >
                                        Periode
                                    </th>

                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider"
                                    >
                                        Status
                                    </th>

                                    <th
                                        class="px-4 py-3 text-right font-medium uppercase tracking-wider"
                                    >
                                        Aksi
                                    </th>
                                </tr>
                            </thead>

                            <tbody
                                class="divide-y divide-[#e3e3e0]/70 dark:divide-[#3E3E3A]/70"
                            >
                                <tr
                                    v-for="borrowing in filteredBorrowings"
                                    :key="borrowing.id"
                                    class="transition hover:bg-slate-50/70 dark:hover:bg-white/[0.02]"
                                >
                                    <!-- PEMINJAM -->
                                    <td class="px-4 py-3.5">
                                        <div
                                            class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{
                                                borrowing.user?.name ??
                                                "Tidak tersedia"
                                            }}
                                        </div>

                                        <div
                                            v-if="borrowing.user?.email"
                                            class="mt-1 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            {{ borrowing.user.email }}
                                        </div>
                                    </td>

                                    <!-- INVENTARIS -->
                                    <td class="px-4 py-3.5">
                                        <div
                                            class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{
                                                borrowing.room_inventory?.item
                                                    ?.name ??
                                                "Barang tidak tersedia"
                                            }}
                                        </div>

                                        <div
                                            class="mt-1 flex flex-wrap items-center gap-x-1.5 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            <span
                                                v-if="
                                                    borrowing.room_inventory
                                                        ?.asset_code
                                                "
                                                class="font-mono font-semibold text-[#f53003] dark:text-[#FF4433]"
                                            >
                                                {{
                                                    borrowing.room_inventory
                                                        .asset_code
                                                }}
                                            </span>

                                            <span
                                                v-if="
                                                    borrowing.room_inventory
                                                        ?.room?.name
                                                "
                                            >
                                                •
                                                {{
                                                    borrowing.room_inventory
                                                        .room.name
                                                }}
                                            </span>

                                            <span
                                                v-if="
                                                    borrowing.room_inventory
                                                        ?.room?.code
                                                "
                                            >
                                                ({{
                                                    borrowing.room_inventory
                                                        .room.code
                                                }})
                                            </span>
                                        </div>
                                    </td>

                                    <!-- FAKULTAS -->
                                    <td class="px-4 py-3.5">
                                        <div
                                            class="text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{ borrowing.faculty?.name ?? "-" }}
                                        </div>

                                        <div
                                            v-if="borrowing.faculty?.code"
                                            class="mt-1 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            {{ borrowing.faculty.code }}
                                        </div>
                                    </td>

                                    <!-- PERIODE -->
                                    <td class="whitespace-nowrap px-4 py-3.5">
                                        <div
                                            class="text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{
                                                formatDate(
                                                    borrowing.borrow_date,
                                                )
                                            }}
                                        </div>

                                        <div
                                            class="mt-1 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            s/d
                                            {{
                                                formatDate(
                                                    borrowing.expected_return_date,
                                                )
                                            }}
                                        </div>
                                    </td>

                                    <!-- STATUS -->
                                    <td class="px-4 py-3.5">
                                        <span
                                            class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                            :class="
                                                statusClass(borrowing.status)
                                            "
                                        >
                                            {{ statusLabel(borrowing.status) }}
                                        </span>

                                        <div
                                            v-if="
                                                borrowing.status ===
                                                    'rejected' &&
                                                borrowing.rejection_note
                                            "
                                            class="mt-1.5 max-w-xs truncate text-[10px] text-red-600 dark:text-red-400"
                                            :title="borrowing.rejection_note"
                                        >
                                            {{ borrowing.rejection_note }}
                                        </div>

                                        <div
                                            v-if="
                                                borrowing.status ===
                                                    'returned' &&
                                                borrowing.actual_return_date
                                            "
                                            class="mt-1.5 text-[10px] text-emerald-600 dark:text-emerald-400"
                                        >
                                            Kembali:
                                            {{
                                                formatDate(
                                                    borrowing.actual_return_date,
                                                )
                                            }}
                                        </div>
                                    </td>

                                    <!-- AKSI -->
                                    <td class="px-4 py-3.5">
                                        <div
                                            class="flex items-center justify-end gap-1.5"
                                        >
                                            <!-- Detail -->
                                            <button
                                                type="button"
                                                @click="
                                                    openDetailModal(borrowing)
                                                "
                                                class="rounded-lg border border-[#e3e3e0] bg-white p-1.5 text-[#706f6c] transition hover:text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                                                title="Lihat Detail"
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
                                                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.644C3.423 7.51 7.363 4.5 12 4.5c4.638 0 8.577 3.01 9.964 7.178.07.21.07.434 0 .644C20.577 16.49 16.637 19.5 12 19.5c-4.638 0-8.577-3.01-9.964-7.178Z"
                                                    />

                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                                    />
                                                </svg>
                                            </button>

                                            <!-- Approval -->
                                            <button
                                                v-if="
                                                    borrowing.status ===
                                                    'pending'
                                                "
                                                type="button"
                                                @click="
                                                    openApprovalModal(borrowing)
                                                "
                                                class="rounded-lg border border-blue-200 bg-blue-50 p-1.5 text-blue-700 transition hover:bg-blue-100 dark:border-blue-900/40 dark:bg-blue-950/30 dark:text-blue-400 dark:hover:bg-blue-950/50"
                                                title="Proses Persetujuan"
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
                                                        d="m4.5 12.75 6 6 9-13.5"
                                                    />
                                                </svg>
                                            </button>

                                            <!-- Return -->
                                            <button
                                                v-if="
                                                    borrowing.status ===
                                                        'approved' ||
                                                    borrowing.status ===
                                                        'borrowed'
                                                "
                                                type="button"
                                                @click="
                                                    openReturnModal(borrowing)
                                                "
                                                class="rounded-lg border border-emerald-200 bg-emerald-50 p-1.5 text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-400 dark:hover:bg-emerald-950/50"
                                                title="Proses Pengembalian"
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
                                                        d="M9 15 6 12m0 0 3-3m-3 3h9a3 3 0 0 0 3-3V6"
                                                    />

                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M15 9 18 12m0 0-3 3m3-3H9a3 3 0 0 0-3 3v3"
                                                    />
                                                </svg>
                                            </button>

                                            <!-- Cancel -->
                                            <button
                                                v-if="
                                                    borrowing.status ===
                                                        'pending' ||
                                                    borrowing.status ===
                                                        'approved'
                                                "
                                                type="button"
                                                @click="
                                                    cancelBorrowing(borrowing)
                                                "
                                                :disabled="
                                                    processing || isCancelling
                                                "
                                                class="rounded-lg border border-red-200 bg-red-50 p-1.5 text-red-600 transition hover:bg-red-100 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-400 dark:hover:bg-red-950/50"
                                                title="Batalkan Peminjaman"
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
                                                        d="M6 18 18 6M6 6l12 12"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- FOOTER -->
                    <div
                        class="border-t border-[#e3e3e0] bg-[#FDFDFC] px-4 py-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                    >
                        <p
                            class="text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Menampilkan
                            <span
                                class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{ filteredBorrowings.length }}
                            </span>
                            dari
                            <span
                                class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{ props.borrowings.length }}
                            </span>
                            peminjaman.
                        </p>
                    </div>
                </div>
            </div>

            <!-- INFO -->
            <div
                class="rounded-xl border border-blue-100 bg-blue-50/80 px-5 py-4 dark:border-blue-900/40 dark:bg-blue-950/20"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400"
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
                                d="M11.25 11.25h1.5v5.25h-1.5z"
                            />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 7.5h.007v.008H12V7.5Z"
                            />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"
                            />
                        </svg>
                    </div>

                    <div>
                        <div
                            class="text-xs font-semibold text-blue-900 dark:text-blue-300"
                        >
                            Informasi Peminjaman
                        </div>

                        <p
                            class="mt-1 text-[11px] leading-relaxed text-blue-800 dark:text-blue-400"
                        >
                            Inventaris yang dapat dipinjam harus berstatus dapat
                            dipinjam, tidak dalam kondisi rusak berat, dan
                            berada di ruangan yang aktif. Pengajuan baru akan
                            masuk ke status menunggu sampai diproses oleh pihak
                            yang berwenang. Peminjaman yang dibatalkan tetap
                            disimpan sebagai riwayat dan tidak dihapus dari
                            sistem.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- FORM MODAL -->
        <BorrowingFormModal
            :show="showFormModal"
            :faculties="props.faculties"
            :room-inventories="props.roomInventories"
            :processing="processing"
            @close="closeFormModal"
            @submit="submitCreate"
        />

        <!-- DETAIL MODAL -->
        <BorrowingDetailModal
            :show="showDetailModal"
            :borrowing="selectedBorrowing"
            @close="closeDetailModal"
        />

        <!-- APPROVAL MODAL -->
        <BorrowingApprovalModal
            :show="showApprovalModal"
            :borrowing="selectedBorrowing"
            :faculties="props.faculties"
            :room-inventories="props.roomInventories"
            :processing="processing"
            @close="closeApprovalModal"
            @submit="handleApprovalSubmit"
        />

        <!-- RETURN MODAL -->
        <BorrowingReturnModal
            :show="showReturnModal"
            :borrowing="selectedBorrowing"
            :processing="processing"
            @close="closeReturnModal"
            @submit="handleReturnSubmit"
        />

        <!-- CUSTOM CONFIRMATION MODAL -->
        <div
            v-if="isConfirmModalOpen"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-sm overflow-hidden rounded-2xl border border-black/5 bg-white shadow-2xl dark:border-white/10 dark:bg-[#161615]"
            >
                <!-- HEADER -->
                <div
                    class="flex items-start gap-3 border-b border-[#e3e3e0] px-6 py-5 dark:border-[#3E3E3A]"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-950/30 dark:text-red-400"
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
                                d="M12 9v3.75m0 3.75h.007v.008H12v-.008ZM10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78A1.5 1.5 0 0 0 22.18 18L13.71 3.86a1.5 1.5 0 0 0-2.42 0Z"
                            />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <h3
                            class="text-base font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ confirmTitle }}
                        </h3>

                        <p
                            class="mt-1.5 text-xs leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            {{ confirmMessage }}
                        </p>
                    </div>
                </div>

                <!-- FOOTER -->
                <div
                    class="flex items-center justify-end gap-2 bg-[#FDFDFC] px-6 py-4 dark:bg-[#0a0a0a]"
                >
                    <button
                        type="button"
                        @click="cancelConfirmModal"
                        :disabled="isCancelling"
                        class="rounded-lg border border-[#e3e3e0] bg-white px-3.5 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        @click="executeConfirmAction"
                        :disabled="isCancelling"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-3.5 py-2 text-xs font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg
                            v-if="isCancelling"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-3.5 w-3.5 animate-spin"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 3v3m6.364-.364-2.121 2.121M21 12h-3m.364 6.364-2.121-2.121M12 21v-3m-6.364.364 2.121-2.121M3 12h3m-.364-6.364 2.121 2.121"
                            />
                        </svg>

                        {{ isCancelling ? "Membatalkan..." : "Ya, Batalkan" }}
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
