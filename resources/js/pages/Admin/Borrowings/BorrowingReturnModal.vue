<script setup lang="ts">
import { computed, ref, watch } from "vue";

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

interface BorrowingReturnData {
    actual_return_date: string;
    condition?: string | null;
    return_note?: string | null;
}

const props = withDefaults(
    defineProps<{
        show: boolean;
        borrowing: Borrowing | null;
        processing?: boolean;
        errors?: Record<string, string>;
    }>(),
    {
        processing: false,
        errors: () => ({}),
    },
);

const emit = defineEmits<{
    (e: "close"): void;
    (e: "submit", data: BorrowingReturnData): void;
}>();

const form = ref<BorrowingReturnData>({
    actual_return_date: "",
    condition: "",
    return_note: "",
});

const localError = ref<string | null>(null);

const isValid = computed(() => {
    return (
        form.value.actual_return_date !== "" &&
        form.value.condition !== ""
    );
});

function getCurrentDateTime(): string {
    const now = new Date();

    const offset = now.getTimezoneOffset();
    const localDate = new Date(
        now.getTime() - offset * 60 * 1000,
    );

    return localDate.toISOString().slice(0, 16);
}

function resetForm() {
    form.value = {
        actual_return_date: getCurrentDateTime(),
        condition: "",
        return_note: "",
    };

    localError.value = null;
}

function close() {
    if (props.processing) {
        return;
    }

    emit("close");
}

function handleSubmit() {
    localError.value = null;

    if (!props.borrowing) {
        localError.value =
            "Data peminjaman tidak ditemukan.";

        return;
    }

    if (!isValid.value) {
        localError.value =
            "Lengkapi data pengembalian yang wajib diisi.";

        return;
    }

    const borrowDate = props.borrowing.borrow_date
        ? new Date(props.borrowing.borrow_date)
        : null;

    const actualReturnDate = new Date(
        form.value.actual_return_date,
    );

    if (Number.isNaN(actualReturnDate.getTime())) {
        localError.value =
            "Tanggal pengembalian tidak valid.";

        return;
    }

    if (
        borrowDate &&
        !Number.isNaN(borrowDate.getTime()) &&
        actualReturnDate < borrowDate
    ) {
        localError.value =
            "Tanggal pengembalian tidak boleh lebih awal dari tanggal peminjaman.";

        return;
    }

    emit("submit", {
        actual_return_date:
            form.value.actual_return_date,

        condition:
            form.value.condition || null,

        return_note:
            form.value.return_note?.trim() || null,
    });
}

function formatDate(
    date: string | null | undefined,
): string {
    if (!date) {
        return "-";
    }

    const parsedDate = new Date(date);

    if (Number.isNaN(parsedDate.getTime())) {
        return date;
    }

    return new Intl.DateTimeFormat("id-ID", {
        dateStyle: "medium",
        timeStyle: "short",
    }).format(parsedDate);
}

function getInventoryName(): string {
    if (!props.borrowing) {
        return "-";
    }

    const inventory =
        props.borrowing.room_inventory;

    if (!inventory) {
        return "-";
    }

    const itemName =
        inventory.item?.name ??
        "Inventaris";

    const assetCode = inventory.asset_code
        ? ` (${inventory.asset_code})`
        : "";

    const roomName = inventory.room?.name
        ? ` - ${inventory.room.name}`
        : "";

    return `${itemName}${assetCode}${roomName}`;
}

function getConditionLabel(
    condition: string,
): string {
    switch (condition) {
        case "good":
            return "Baik";

        case "damaged_light":
            return "Rusak Ringan";

        case "damaged_heavy":
            return "Rusak Berat";

        default:
            return condition;
    }
}

watch(
    () => props.show,
    (show) => {
        if (show) {
            resetForm();
        }
    },
);

watch(
    () => props.borrowing,
    (borrowing) => {
        if (borrowing && props.show) {
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
                        Pengembalian Peminjaman
                    </h2>

                    <p
                        class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Periksa barang dan lengkapi data pengembalian.
                    </p>
                </div>

                <button
                    type="button"
                    class="rounded-lg p-2 text-[#706f6c] transition hover:bg-black/5 hover:text-[#1b1b18] disabled:cursor-not-allowed disabled:opacity-50 dark:text-[#A1A09A] dark:hover:bg-white/10 dark:hover:text-white"
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
            <div
                class="max-h-[75vh] overflow-y-auto px-6 py-5"
            >
                <!-- Error -->
                <div
                    v-if="
                        localError ||
                        Object.keys(errors).length
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
                    <!-- Borrowing Information -->
                    <div
                        class="rounded-xl border border-black/10 bg-black/[0.02] p-4 dark:border-white/10 dark:bg-white/[0.03]"
                    >
                        <div class="space-y-4">
                            <!-- Peminjam -->
                            <div
                                v-if="borrowing.user"
                            >
                                <p
                                    class="mb-1 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Peminjam
                                </p>

                                <p
                                    class="text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ borrowing.user.name }}
                                </p>

                                <p
                                    v-if="borrowing.user.email"
                                    class="mt-0.5 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    {{ borrowing.user.email }}
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
                                    class="text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ getInventoryName() }}
                                </p>

                                <p
                                    v-if="
                                        borrowing
                                            .room_inventory
                                            ?.room
                                    "
                                    class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Ruangan:
                                    {{
                                        borrowing
                                            .room_inventory
                                            .room.name
                                    }}

                                    <span
                                        v-if="
                                            borrowing
                                                .room_inventory
                                                .room.code
                                        "
                                    >
                                        ({{
                                            borrowing
                                                .room_inventory
                                                .room.code
                                        }})
                                    </span>
                                </p>
                            </div>

                            <!-- Fakultas -->
                            <div
                                v-if="
                                    borrowing.faculty
                                "
                            >
                                <p
                                    class="mb-1 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Fakultas
                                </p>

                                <p
                                    class="text-sm text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{
                                        borrowing.faculty
                                            .name
                                    }}

                                    <span
                                        v-if="
                                            borrowing
                                                .faculty
                                                .code
                                        "
                                        class="text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        ({{
                                            borrowing
                                                .faculty
                                                .code
                                        }})
                                    </span>
                                </p>
                            </div>

                            <!-- Tanggal -->
                            <div
                                class="grid gap-4 sm:grid-cols-2"
                            >
                                <div>
                                    <p
                                        class="mb-1 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        Dipinjam Pada
                                    </p>

                                    <p
                                        class="text-sm text-[#1b1b18] dark:text-[#EDEDEC]"
                                    >
                                        {{
                                            formatDate(
                                                borrowing.borrow_date,
                                            )
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p
                                        class="mb-1 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        Rencana Kembali
                                    </p>

                                    <p
                                        class="text-sm text-[#1b1b18] dark:text-[#EDEDEC]"
                                    >
                                        {{
                                            formatDate(
                                                borrowing.expected_return_date,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Purpose -->
                            <div>
                                <p
                                    class="mb-1 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Tujuan Peminjaman
                                </p>

                                <p
                                    class="text-sm leading-relaxed text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{
                                        borrowing.purpose ||
                                        "-"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Actual Return Date -->
                    <div>
                        <label
                            for="actual_return_date"
                            class="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Tanggal Pengembalian
                            <span
                                class="text-red-500"
                            >
                                *
                            </span>
                        </label>

                        <input
                            id="actual_return_date"
                            v-model="
                                form.actual_return_date
                            "
                            type="datetime-local"
                            :disabled="processing"
                            class="w-full rounded-xl border border-black/10 bg-white px-4 py-3 text-sm text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-2 focus:ring-[#f53003]/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-[#0f0f0e] dark:text-[#EDEDEC]"
                        />

                        <p
                            v-if="
                                errors.actual_return_date
                            "
                            class="mt-1 text-xs text-red-500"
                        >
                            {{
                                errors.actual_return_date
                            }}
                        </p>
                    </div>

                    <!-- Condition -->
                    <div>
                        <label
                            for="condition"
                            class="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Kondisi Barang
                            <span
                                class="text-red-500"
                            >
                                *
                            </span>
                        </label>

                        <select
                            id="condition"
                            v-model="form.condition"
                            :disabled="processing"
                            class="w-full rounded-xl border border-black/10 bg-white px-4 py-3 text-sm text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-2 focus:ring-[#f53003]/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-[#0f0f0e] dark:text-[#EDEDEC]"
                        >
                            <option value="">
                                Pilih kondisi barang
                            </option>

                            <option value="good">
                                Baik
                            </option>

                            <option
                                value="damaged_light"
                            >
                                Rusak Ringan
                            </option>

                            <option
                                value="damaged_heavy"
                            >
                                Rusak Berat
                            </option>
                        </select>

                        <p
                            v-if="errors.condition"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ errors.condition }}
                        </p>

                        <p
                            v-if="form.condition"
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Kondisi dipilih:
                            <span class="font-medium">
                                {{
                                    getConditionLabel(
                                        form.condition,
                                    )
                                }}
                            </span>
                        </p>
                    </div>

                    <!-- Return Note -->
                    <div>
                        <label
                            for="return_note"
                            class="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Catatan Pengembalian
                        </label>

                        <textarea
                            id="return_note"
                            v-model="form.return_note"
                            :disabled="processing"
                            rows="4"
                            maxlength="1000"
                            placeholder="Contoh: Barang dikembalikan dalam kondisi baik dan lengkap..."
                            class="w-full resize-none rounded-xl border border-black/10 bg-white px-4 py-3 text-sm text-[#1b1b18] outline-none transition placeholder:text-[#9b9a97] focus:border-[#f53003] focus:ring-2 focus:ring-[#f53003]/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-[#0f0f0e] dark:text-[#EDEDEC]"
                        ></textarea>

                        <div
                            class="mt-1 flex justify-between"
                        >
                            <p
                                v-if="
                                    errors.return_note
                                "
                                class="text-xs text-red-500"
                            >
                                {{
                                    errors.return_note
                                }}
                            </p>

                            <span
                                class="ml-auto text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{
                                    form.return_note
                                        ?.length ?? 0
                                }}
                                karakter
                            </span>
                        </div>
                    </div>

                    <!-- Confirmation -->
                    <div
                        class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-900/40 dark:bg-yellow-950/20"
                    >
                        <div class="flex gap-3">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="mt-0.5 h-5 w-5 shrink-0 text-yellow-600 dark:text-yellow-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v3.75m0 3.75h.007M10.29 3.86l-7.06 12.23A1.5 1.5 0 004.53 18.34h14.94a1.5 1.5 0 001.3-2.25L13.71 3.86a1.5 1.5 0 00-2.6 0z"
                                />
                            </svg>

                            <p
                                class="text-sm leading-relaxed text-yellow-800 dark:text-yellow-300"
                            >
                                Pastikan kondisi barang
                                sudah diperiksa sebelum
                                mengonfirmasi
                                pengembalian.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Empty -->
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
                <button
                    type="button"
                    :disabled="processing"
                    class="rounded-xl border border-black/10 px-5 py-2.5 text-sm font-medium text-[#1b1b18] transition hover:bg-black/5 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:text-[#EDEDEC] dark:hover:bg-white/10"
                    @click="close"
                >
                    Batal
                </button>

                <button
                    type="button"
                    :disabled="
                        processing ||
                        !borrowing ||
                        !isValid
                    "
                    class="rounded-xl bg-[#f53003] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#d92a02] disabled:cursor-not-allowed disabled:opacity-50"
                    @click="handleSubmit"
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
                        Konfirmasi Pengembalian
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>