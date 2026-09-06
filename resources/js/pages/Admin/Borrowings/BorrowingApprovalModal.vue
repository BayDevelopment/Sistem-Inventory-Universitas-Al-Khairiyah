<script setup lang="ts">
import { computed, ref, watch } from "vue";

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

interface ApprovalSubmitData {
    status: "approved" | "rejected";
    rejectionNote?: string | null;
}

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        show: boolean;
        borrowing: Borrowing | null;
        faculties?: Faculty[];
        roomInventories?: RoomInventory[];
        processing?: boolean;
        errors?: Record<string, string>;
    }>(),
    {
        faculties: () => [],
        roomInventories: () => [],
        processing: false,
        errors: () => ({}),
    },
);

/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    (e: "close"): void;
    (
        e: "submit",
        status: "approved" | "rejected",
        rejectionNote?: string | null,
    ): void;
}>();

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const rejectionNote = ref("");

const localError = ref<string | null>(null);

const showRejectForm = ref(false);

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const isRejectValid = computed(() => {
    return rejectionNote.value.trim().length > 0;
});

/*
|--------------------------------------------------------------------------
| Reset
|--------------------------------------------------------------------------
*/

function resetForm() {
    rejectionNote.value = "";
    localError.value = null;
    showRejectForm.value = false;
}

/*
|--------------------------------------------------------------------------
| Modal Actions
|--------------------------------------------------------------------------
*/

function close() {
    if (props.processing) {
        return;
    }

    emit("close");
}

function openRejectForm() {
    localError.value = null;
    showRejectForm.value = true;
}

function cancelReject() {
    if (props.processing) {
        return;
    }

    rejectionNote.value = "";
    localError.value = null;
    showRejectForm.value = false;
}

function handleApprove() {
    if (!props.borrowing) {
        return;
    }

    localError.value = null;

    emit("submit", "approved");
}

function handleReject() {
    if (!props.borrowing) {
        return;
    }

    localError.value = null;

    if (!isRejectValid.value) {
        localError.value = "Alasan penolakan wajib diisi.";
        return;
    }

    emit(
        "submit",
        "rejected",
        rejectionNote.value.trim(),
    );
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
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
}

function getFacultyName() {
    if (!props.borrowing) {
        return "-";
    }

    if (props.borrowing.faculty?.name) {
        return props.borrowing.faculty.name;
    }

    const faculty = props.faculties.find(
        (item) => item.id === props.borrowing?.faculty_id,
    );

    return faculty?.name ?? "-";
}

function getInventoryName() {
    if (!props.borrowing) {
        return "-";
    }

    const inventory = props.borrowing.room_inventory;

    if (inventory) {
        const itemName =
            inventory.item?.name ?? "Item tidak tersedia";

        const assetCode = inventory.asset_code
            ? ` • ${inventory.asset_code}`
            : "";

        const roomName = inventory.room?.name
            ? ` • ${inventory.room.name}`
            : "";

        const roomCode = inventory.room?.code
            ? ` (${inventory.room.code})`
            : "";

        return `${itemName}${assetCode}${roomName}${roomCode}`;
    }

    const fallback = props.roomInventories.find(
        (item) =>
            item.id === props.borrowing?.room_inventory_id,
    );

    if (fallback) {
        const itemName =
            fallback.item?.name ?? "Item tidak tersedia";

        const assetCode = fallback.asset_code
            ? ` • ${fallback.asset_code}`
            : "";

        const roomName = fallback.room?.name
            ? ` • ${fallback.room.name}`
            : "";

        const roomCode = fallback.room?.code
            ? ` (${fallback.room.code})`
            : "";

        return `${itemName}${assetCode}${roomName}${roomCode}`;
    }

    return "-";
}

function getStatusLabel(status?: string | null) {
    switch (status) {
        case "pending":
            return "Menunggu Persetujuan";

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

/*
|--------------------------------------------------------------------------
| Watch
|--------------------------------------------------------------------------
*/

watch(
    () => props.show,
    (show) => {
        if (show) {
            resetForm();
        }
    },
);
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        @click.self="close"
    >
        <div
            class="w-full max-w-lg overflow-hidden rounded-2xl border border-black/10 bg-white shadow-2xl dark:border-white/10 dark:bg-[#161615]"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-black/10 px-6 py-5 dark:border-white/10"
            >
                <div>
                    <h2
                        class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Persetujuan Peminjaman
                    </h2>

                    <p
                        class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Periksa detail pengajuan sebelum memberikan keputusan.
                    </p>
                </div>

                <button
                    type="button"
                    class="rounded-lg p-2 text-[#706f6c] transition hover:bg-black/5 hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-white/10 dark:hover:text-white"
                    :disabled="processing"
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
            <div class="max-h-[75vh] overflow-y-auto px-6 py-5">
                <!-- Error -->
                <div
                    v-if="
                        localError ||
                        Object.keys(errors).length > 0
                    "
                    class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-950/20"
                >
                    <p
                        v-if="localError"
                        class="text-sm text-red-600 dark:text-red-400"
                    >
                        {{ localError }}
                    </p>

                    <div
                        v-for="(error, key) in errors"
                        :key="key"
                        class="text-sm text-red-600 dark:text-red-400"
                    >
                        {{ error }}
                    </div>
                </div>

                <!-- Borrowing -->
                <div
                    v-if="borrowing"
                    class="space-y-5"
                >
                    <!-- Status -->
                    <div
                        class="rounded-xl border border-black/10 bg-black/[0.02] p-4 dark:border-white/10 dark:bg-white/[0.03]"
                    >
                        <div
                            class="flex items-center justify-between gap-3"
                        >
                            <div>
                                <p
                                    class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Status Pengajuan
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ getStatusLabel(borrowing.status) }}
                                </p>
                            </div>

                            <span
                                class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400"
                            >
                                Menunggu
                            </span>
                        </div>
                    </div>

                    <!-- Detail -->
                    <div class="space-y-4">
                        <!-- Peminjam -->
                        <div v-if="borrowing.user">
                            <p
                                class="mb-1 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Peminjam
                            </p>

                            <p
                                class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{ borrowing.user.name }}
                            </p>

                            <p
                                v-if="borrowing.user.email"
                                class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{ borrowing.user.email }}
                            </p>
                        </div>

                        <!-- Fakultas -->
                        <div>
                            <p
                                class="mb-1 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Fakultas
                            </p>

                            <p
                                class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{ getFacultyName() }}
                            </p>

                            <p
                                v-if="borrowing.faculty?.code"
                                class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{ borrowing.faculty.code }}
                            </p>
                        </div>

                        <!-- Inventaris -->
                        <div>
                            <p
                                class="mb-1 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Inventaris / Barang
                            </p>

                            <p
                                class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{ getInventoryName() }}
                            </p>
                        </div>

                        <!-- Tanggal -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p
                                    class="mb-1 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Tanggal Peminjaman
                                </p>

                                <p
                                    class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ formatDate(borrowing.borrow_date) }}
                                </p>
                            </div>

                            <div>
                                <p
                                    class="mb-1 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Rencana Pengembalian
                                </p>

                                <p
                                    class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{
                                        formatDate(
                                            borrowing.expected_return_date,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Tujuan -->
                        <div>
                            <p
                                class="mb-1 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Tujuan Peminjaman
                            </p>

                            <div
                                class="rounded-xl border border-black/10 bg-black/[0.02] p-4 dark:border-white/10 dark:bg-white/[0.03]"
                            >
                                <p
                                    class="whitespace-pre-wrap text-sm leading-relaxed text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ borrowing.purpose || "-" }}
                                </p>
                            </div>
                        </div>

                        <!-- Signature -->
                        <div>
                            <p
                                class="mb-2 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Tanda Tangan Pemohon
                            </p>

                            <div
                                class="rounded-xl border border-black/10 bg-black/[0.02] p-4 dark:border-white/10 dark:bg-white/[0.03]"
                            >
                                <template
                                    v-if="borrowing.applicant_signature"
                                >
                                    <img
                                        :src="
                                            borrowing.applicant_signature
                                        "
                                        alt="Tanda tangan pemohon"
                                        class="max-h-28 max-w-full object-contain"
                                    />
                                </template>

                                <p
                                    v-else
                                    class="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Tidak ada tanda tangan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Reject Form -->
                    <div
                        v-if="showRejectForm"
                        class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-950/20"
                    >
                        <label
                            for="rejection_note"
                            class="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Alasan Penolakan
                            <span class="text-red-500">*</span>
                        </label>

                        <textarea
                            id="rejection_note"
                            v-model="rejectionNote"
                            rows="4"
                            placeholder="Masukkan alasan penolakan pengajuan..."
                            class="w-full resize-none rounded-xl border border-red-200 bg-white px-4 py-3 text-sm text-[#1b1b18] outline-none transition placeholder:text-[#9b9a97] focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-red-900/50 dark:bg-[#0f0f0e] dark:text-[#EDEDEC]"
                        ></textarea>

                        <div class="mt-1 flex justify-between">
                            <p
                                v-if="errors.rejection_note"
                                class="text-xs text-red-500"
                            >
                                {{ errors.rejection_note }}
                            </p>

                            <span
                                class="ml-auto text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{ rejectionNote.length }} karakter
                            </span>
                        </div>
                    </div>
                </div>

                <!-- No Data -->
                <div
                    v-else
                    class="py-8 text-center"
                >
                    <p
                        class="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Data peminjaman tidak tersedia.
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex flex-col-reverse gap-3 border-t border-black/10 px-6 py-4 sm:flex-row sm:justify-end dark:border-white/10"
            >
                <!-- Reject Mode -->
                <template v-if="showRejectForm">
                    <button
                        type="button"
                        :disabled="processing"
                        class="rounded-xl border border-black/10 px-5 py-2.5 text-sm font-medium text-[#1b1b18] transition hover:bg-black/5 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:text-[#EDEDEC] dark:hover:bg-white/10"
                        @click="cancelReject"
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        :disabled="processing || !isRejectValid"
                        class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="handleReject"
                    >
                        <span
                            v-if="processing"
                            class="inline-flex items-center gap-2"
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

                            Memproses...
                        </span>

                        <span v-else>
                            Tolak Peminjaman
                        </span>
                    </button>
                </template>

                <!-- Normal Mode -->
                <template v-else>
                    <button
                        type="button"
                        :disabled="processing"
                        class="rounded-xl border border-black/10 px-5 py-2.5 text-sm font-medium text-[#1b1b18] transition hover:bg-black/5 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:text-[#EDEDEC] dark:hover:bg-white/10"
                        @click="close"
                    >
                        Tutup
                    </button>

                    <button
                        type="button"
                        :disabled="processing || !borrowing"
                        class="rounded-xl border border-red-200 px-5 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-950/20"
                        @click="openRejectForm"
                    >
                        Tolak
                    </button>

                    <button
                        type="button"
                        :disabled="processing || !borrowing"
                        class="rounded-xl bg-[#f53003] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#d92a02] disabled:cursor-not-allowed disabled:opacity-50"
                        @click="handleApprove"
                    >
                        <span
                            v-if="processing"
                            class="inline-flex items-center gap-2"
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
                                    d="M4 12a8 8 0 018-8v4a4 4 0 01-4 4H4z"
                                />
                            </svg>

                            Memproses...
                        </span>

                        <span v-else>
                            Setujui Peminjaman
                        </span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>