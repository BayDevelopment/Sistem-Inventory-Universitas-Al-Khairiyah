<script setup lang="ts">
import { computed } from "vue";

interface User {
    id: number;
    name: string;
    email?: string | null;
}

interface Faculty {
    id: number;
    name: string;
    code?: string | null;
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

    created_at?: string | null;
    updated_at?: string | null;
}

const props = withDefaults(
    defineProps<{
        show: boolean;
        borrowing: Borrowing | null;
    }>(),
    {
        borrowing: null,
    },
);

const emit = defineEmits<{
    (e: "close"): void;
}>();

function close() {
    emit("close");
}

function formatDate(
    date: string | null | undefined,
): string {
    if (!date) {
        return "-";
    }

    const parsed = new Date(date);

    if (Number.isNaN(parsed.getTime())) {
        return date;
    }

    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(parsed);
}

const statusLabel = computed(() => {
    const status = props.borrowing?.status;

    switch (status) {
        case "pending":
            return "Menunggu Persetujuan";

        case "approved":
            return "Disetujui";

        case "rejected":
            return "Ditolak";

        case "borrowed":
            return "Sedang Dipinjam";

        case "returned":
            return "Sudah Dikembalikan";

        case "cancelled":
            return "Dibatalkan";

        default:
            return status ?? "-";
    }
});

const statusClass = computed(() => {
    const status = props.borrowing?.status;

    switch (status) {
        case "pending":
            return "bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400";

        case "approved":
            return "bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400";

        case "rejected":
            return "bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400";

        case "borrowed":
            return "bg-purple-100 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400";

        case "returned":
            return "bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400";

        case "cancelled":
            return "bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300";

        default:
            return "bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300";
    }
});

const itemName = computed(() => {
    return (
        props.borrowing?.room_inventory?.item
            ?.name ?? "-"
    );
});

const assetCode = computed(() => {
    return (
        props.borrowing?.room_inventory?.asset_code ??
        "-"
    );
});

const roomName = computed(() => {
    return (
        props.borrowing?.room_inventory?.room?.name ??
        "-"
    );
});

const roomCode = computed(() => {
    return (
        props.borrowing?.room_inventory?.room?.code ??
        null
    );
});

const conditionLabel = computed(() => {
    const condition =
        props.borrowing?.room_inventory?.condition;

    switch (condition) {
        case "good":
            return "Baik";

        case "damaged_light":
            return "Rusak Ringan";

        case "damaged_heavy":
            return "Rusak Berat";

        default:
            return condition ?? "-";
    }
});

const inventoryBorrowableLabel = computed(() => {
    const isBorrowable =
        props.borrowing?.room_inventory
            ?.is_borrowable;

    if (isBorrowable === true) {
        return "Dapat Dipinjam";
    }

    if (isBorrowable === false) {
        return "Tidak Dapat Dipinjam";
    }

    return "-";
});

function getSignatureUrl(
    path: string | null | undefined,
): string | null {
    if (!path) {
        return null;
    }

    if (
        path.startsWith("http://") ||
        path.startsWith("https://") ||
        path.startsWith("/")
    ) {
        return path;
    }

    return `/storage/${path}`;
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        @click.self="close"
    >
        <div
            class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-black/10 bg-white shadow-2xl dark:border-white/10 dark:bg-[#161615]"
        >
            <!-- Header -->
            <div
                class="flex shrink-0 items-center justify-between border-b border-black/10 px-6 py-5 dark:border-white/10"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#f53003]/10 text-[#f53003]"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h6.586a1 1 0 01.707.293l4.414 4.414A1 1 0 0119 8.414V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>

                    <div>
                        <h2
                            class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Detail Peminjaman
                        </h2>

                        <p
                            class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Informasi lengkap pengajuan
                            peminjaman.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="rounded-lg p-2 text-[#706f6c] transition hover:bg-black/5 hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-white/10 dark:hover:text-white"
                    @click="close"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div
                v-if="borrowing"
                class="overflow-y-auto px-6 py-6"
            >
                <!-- Status -->
                <div
                    class="mb-6 flex flex-col gap-3 rounded-xl border border-black/10 bg-black/[0.02] p-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-white/[0.03]"
                >
                    <div>
                        <p
                            class="text-xs font-medium uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Nomor Peminjaman
                        </p>

                        <p
                            class="mt-1 text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            #{{ borrowing.id }}
                        </p>
                    </div>

                    <span
                        class="inline-flex w-fit items-center rounded-full px-3 py-1.5 text-xs font-semibold"
                        :class="statusClass"
                    >
                        {{ statusLabel }}
                    </span>
                </div>

                <!-- Informasi Pemohon -->
                <section class="mb-6">
                    <div
                        class="mb-3 flex items-center gap-2"
                    >
                        <div
                            class="h-5 w-1 rounded-full bg-[#f53003]"
                        ></div>

                        <h3
                            class="text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Informasi Pemohon
                        </h3>
                    </div>

                    <div
                        class="grid gap-4 rounded-xl border border-black/10 p-4 sm:grid-cols-2 dark:border-white/10"
                    >
                        <!-- Nama -->
                        <div>
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Nama Pemohon
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    borrowing.user?.name ??
                                    "-"
                                }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div>
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Email
                            </p>

                            <p
                                class="mt-1 break-all text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    borrowing.user?.email ??
                                    "-"
                                }}
                            </p>
                        </div>

                        <!-- Fakultas -->
                        <div
                            class="sm:col-span-2"
                        >
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Fakultas
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    borrowing.faculty
                                        ?.name ?? "-"
                                }}

                                <span
                                    v-if="
                                        borrowing.faculty
                                            ?.code
                                    "
                                    class="font-normal text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    ({{
                                        borrowing.faculty
                                            .code
                                    }})
                                </span>
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Informasi Inventaris -->
                <section class="mb-6">
                    <div
                        class="mb-3 flex items-center gap-2"
                    >
                        <div
                            class="h-5 w-1 rounded-full bg-[#f53003]"
                        ></div>

                        <h3
                            class="text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Informasi Inventaris
                        </h3>
                    </div>

                    <div
                        class="grid gap-4 rounded-xl border border-black/10 p-4 sm:grid-cols-2 dark:border-white/10"
                    >
                        <!-- Barang -->
                        <div>
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Nama Barang
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{ itemName }}
                            </p>
                        </div>

                        <!-- Kode Aset -->
                        <div>
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Kode Aset
                            </p>

                            <p
                                class="mt-1 font-mono text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{ assetCode }}
                            </p>
                        </div>

                        <!-- Ruangan -->
                        <div>
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Ruangan
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{ roomName }}

                                <span
                                    v-if="roomCode"
                                    class="font-normal text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    ({{ roomCode }})
                                </span>
                            </p>
                        </div>

                        <!-- ID Inventaris -->
                        <div>
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                ID Inventaris
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    borrowing.room_inventory_id
                                        ? `#${borrowing.room_inventory_id}`
                                        : "-"
                                }}
                            </p>
                        </div>

                        <!-- Kondisi -->
                        <div>
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Kondisi
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{ conditionLabel }}
                            </p>
                        </div>

                        <!-- Status Inventaris -->
                        <div>
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Status Inventaris
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    inventoryBorrowableLabel
                                }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Waktu Peminjaman -->
                <section class="mb-6">
                    <div
                        class="mb-3 flex items-center gap-2"
                    >
                        <div
                            class="h-5 w-1 rounded-full bg-[#f53003]"
                        ></div>

                        <h3
                            class="text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Waktu Peminjaman
                        </h3>
                    </div>

                    <div
                        class="grid gap-4 rounded-xl border border-black/10 p-4 sm:grid-cols-3 dark:border-white/10"
                    >
                        <!-- Borrow -->
                        <div>
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Tanggal Peminjaman
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    formatDate(
                                        borrowing.borrow_date,
                                    )
                                }}
                            </p>
                        </div>

                        <!-- Expected -->
                        <div>
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Rencana Pengembalian
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    formatDate(
                                        borrowing.expected_return_date,
                                    )
                                }}
                            </p>
                        </div>

                        <!-- Actual -->
                        <div>
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Pengembalian Aktual
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    formatDate(
                                        borrowing.actual_return_date,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Tujuan -->
                <section class="mb-6">
                    <div
                        class="mb-3 flex items-center gap-2"
                    >
                        <div
                            class="h-5 w-1 rounded-full bg-[#f53003]"
                        ></div>

                        <h3
                            class="text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Tujuan Peminjaman
                        </h3>
                    </div>

                    <div
                        class="rounded-xl border border-black/10 p-4 dark:border-white/10"
                    >
                        <p
                            class="whitespace-pre-line text-sm leading-6 text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{
                                borrowing.purpose || "-"
                            }}
                        </p>
                    </div>
                </section>

                <!-- Tanda Tangan Pemohon -->
                <section class="mb-6">
                    <div
                        class="mb-3 flex items-center gap-2"
                    >
                        <div
                            class="h-5 w-1 rounded-full bg-[#f53003]"
                        ></div>

                        <h3
                            class="text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Tanda Tangan Pemohon
                        </h3>
                    </div>

                    <div
                        class="rounded-xl border border-black/10 p-4 dark:border-white/10"
                    >
                        <div
                            v-if="
                                borrowing.applicant_signature
                            "
                            class="rounded-lg border border-black/10 bg-black/[0.02] p-4 dark:border-white/10 dark:bg-white/[0.03]"
                        >
                            <div
                                class="flex min-h-[140px] items-center justify-center rounded-lg bg-white p-4 dark:bg-[#0f0f0e]"
                            >
                                <img
                                    :src="
                                        getSignatureUrl(
                                            borrowing.applicant_signature,
                                        ) ?? ''
                                    "
                                    alt="Tanda tangan pemohon"
                                    class="max-h-32 max-w-full object-contain"
                                />
                            </div>

                            <p
                                v-if="
                                    borrowing.signed_at
                                "
                                class="mt-3 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Ditandatangani:
                                {{
                                    formatDate(
                                        borrowing.signed_at,
                                    )
                                }}
                            </p>
                        </div>

                        <p
                            v-else
                            class="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Belum ada tanda tangan
                            pemohon.
                        </p>
                    </div>
                </section>

                <!-- Persetujuan -->
                <section class="mb-6">
                    <div
                        class="mb-3 flex items-center gap-2"
                    >
                        <div
                            class="h-5 w-1 rounded-full bg-[#f53003]"
                        ></div>

                        <h3
                            class="text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Persetujuan
                        </h3>
                    </div>

                    <div
                        class="grid gap-4 rounded-xl border border-black/10 p-4 sm:grid-cols-2 dark:border-white/10"
                    >
                        <!-- Approver -->
                        <div>
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Disetujui Oleh
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    borrowing.approver?.name ??
                                    "-"
                                }}
                            </p>

                            <p
                                v-if="
                                    borrowing.approver?.email
                                "
                                class="mt-0.5 break-all text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{
                                    borrowing.approver.email
                                }}
                            </p>
                        </div>

                        <!-- Approved At -->
                        <div>
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Waktu Persetujuan
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    formatDate(
                                        borrowing.approved_at,
                                    )
                                }}
                            </p>
                        </div>

                        <!-- Approver Signature -->
                        <div
                            v-if="
                                borrowing.approver_signature
                            "
                            class="sm:col-span-2"
                        >
                            <p
                                class="mb-2 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Tanda Tangan Approver
                            </p>

                            <div
                                class="rounded-lg border border-black/10 bg-black/[0.02] p-4 dark:border-white/10 dark:bg-white/[0.03]"
                            >
                                <div
                                    class="flex min-h-[140px] items-center justify-center rounded-lg bg-white p-4 dark:bg-[#0f0f0e]"
                                >
                                    <img
                                        :src="
                                            getSignatureUrl(
                                                borrowing.approver_signature,
                                            ) ?? ''
                                        "
                                        alt="Tanda tangan approver"
                                        class="max-h-32 max-w-full object-contain"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Rejection Note -->
                        <div
                            v-if="
                                borrowing.rejection_note
                            "
                            class="sm:col-span-2"
                        >
                            <p
                                class="mb-2 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Alasan Penolakan
                            </p>

                            <div
                                class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900/40 dark:bg-red-950/20"
                            >
                                <p
                                    class="whitespace-pre-line text-sm text-red-700 dark:text-red-400"
                                >
                                    {{
                                        borrowing.rejection_note
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Pending -->
                        <div
                            v-if="
                                borrowing.status ===
                                'pending'
                            "
                            class="sm:col-span-2"
                        >
                            <div
                                class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-950/20"
                            >
                                <p
                                    class="text-sm text-amber-700 dark:text-amber-400"
                                >
                                    Peminjaman ini masih
                                    menunggu persetujuan.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Metadata -->
                <section>
                    <div
                        class="mb-3 flex items-center gap-2"
                    >
                        <div
                            class="h-5 w-1 rounded-full bg-[#f53003]"
                        ></div>

                        <h3
                            class="text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Informasi Sistem
                        </h3>
                    </div>

                    <div
                        class="grid gap-4 rounded-xl border border-black/10 p-4 sm:grid-cols-2 dark:border-white/10"
                    >
                        <div>
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Dibuat
                            </p>

                            <p
                                class="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    formatDate(
                                        borrowing.created_at,
                                    )
                                }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Terakhir Diperbarui
                            </p>

                            <p
                                class="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    formatDate(
                                        borrowing.updated_at,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="flex min-h-[300px] items-center justify-center px-6"
            >
                <div class="text-center">
                    <div
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-black/5 dark:bg-white/10"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 text-[#706f6c] dark:text-[#A1A09A]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>

                    <p
                        class="mt-3 text-sm text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Data peminjaman tidak
                        tersedia.
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex shrink-0 justify-end border-t border-black/10 px-6 py-4 dark:border-white/10"
            >
                <button
                    type="button"
                    class="rounded-xl border border-black/10 px-5 py-2.5 text-sm font-medium text-[#1b1b18] transition hover:bg-black/5 dark:border-white/10 dark:text-[#EDEDEC] dark:hover:bg-white/10"
                    @click="close"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>