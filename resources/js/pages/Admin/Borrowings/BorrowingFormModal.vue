```vue
<script setup lang="ts">
import {
    computed,
    nextTick,
    onBeforeUnmount,
    ref,
    watch,
} from "vue";

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

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

interface User {
    id: number;
    name: string;
    email?: string | null;
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
    applicant_signature: string | null;
}

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        show: boolean;

        faculties?: Faculty[];

        roomInventories?: RoomInventory[];

        borrowings?: Borrowing[];

        borrowing?: Borrowing | null;

        processing?: boolean;

        errors?: Record<string, string>;
    }>(),
    {
        faculties: () => [],
        roomInventories: () => [],
        borrowings: () => [],
        borrowing: null,
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
        data: BorrowingFormData,
    ): void;
}>();

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = ref<BorrowingFormData>({
    faculty_id: null,
    room_inventory_id: null,
    borrow_date: "",
    expected_return_date: "",
    purpose: "",
    applicant_signature: null,
});

const localError = ref<string | null>(null);

/*
|--------------------------------------------------------------------------
| Active Borrowing Status
|--------------------------------------------------------------------------
*/

const activeStatuses = [
    "pending",
    "approved",
    "borrowed",
];

/*
|--------------------------------------------------------------------------
| Date Helpers
|--------------------------------------------------------------------------
*/

/**
 * Normalisasi tanggal supaya aman dibandingkan.
 *
 * Input dari datetime-local:
 * 2026-09-15T08:00
 *
 * Input dari database:
 * 2026-09-15 / 2026-09-15 08:00:00
 */
const parseDate = (
    value?: string | null,
): number | null => {
    if (!value) {
        return null;
    }

    const normalized =
        value.includes("T")
            ? value
            : value.replace(" ", "T");

    const timestamp =
        new Date(normalized).getTime();

    if (Number.isNaN(timestamp)) {
        return null;
    }

    return timestamp;
};

/*
|--------------------------------------------------------------------------
| Check Date Overlap
|--------------------------------------------------------------------------
*/

const datesOverlap = (
    requestedStart: string,
    requestedEnd: string,
    existingStart?: string | null,
    existingEnd?: string | null,
): boolean => {
    const start =
        parseDate(requestedStart);

    const end =
        parseDate(requestedEnd);

    const existingBorrowDate =
        parseDate(existingStart);

    const existingReturnDate =
        parseDate(existingEnd);

    if (
        start === null ||
        end === null ||
        existingBorrowDate === null ||
        existingReturnDate === null
    ) {
        return false;
    }

    return (
        existingBorrowDate <= end &&
        existingReturnDate >= start
    );
};

/*
|--------------------------------------------------------------------------
| Find Existing Conflict
|--------------------------------------------------------------------------
*/

const getInventoryConflict = (
    inventoryId: number,
): Borrowing | null => {
    if (
        !form.value.borrow_date ||
        !form.value.expected_return_date
    ) {
        return null;
    }

    const conflict =
        props.borrowings.find(
            (borrowing) => {
                /*
                |--------------------------------------------------------------------------
                | Hanya status aktif yang mengunci aset
                |--------------------------------------------------------------------------
                */

                if (
                    !activeStatuses.includes(
                        borrowing.status ?? "",
                    )
                ) {
                    return false;
                }

                /*
                |--------------------------------------------------------------------------
                | Inventory harus sama
                |--------------------------------------------------------------------------
                */

                if (
                    Number(
                        borrowing.room_inventory_id,
                    ) !== Number(inventoryId)
                ) {
                    return false;
                }

                /*
                |--------------------------------------------------------------------------
                | Saat EDIT, borrowing milik sendiri tidak dianggap konflik
                |--------------------------------------------------------------------------
                */

                if (
                    props.borrowing?.id &&
                    Number(
                        borrowing.id,
                    ) === Number(
                        props.borrowing.id,
                    )
                ) {
                    return false;
                }

                /*
                |--------------------------------------------------------------------------
                | Cek overlap tanggal
                |--------------------------------------------------------------------------
                */

                return datesOverlap(
                    form.value.borrow_date,
                    form.value.expected_return_date,
                    borrowing.borrow_date,
                    borrowing.expected_return_date,
                );
            },
        );

    return conflict ?? null;
};

/*
|--------------------------------------------------------------------------
| Inventory Availability
|--------------------------------------------------------------------------
*/

const isInventoryUnavailable = (
    inventoryId: number,
): boolean => {
    /*
    |--------------------------------------------------------------------------
    | Jika tanggal belum lengkap,
    | jangan disable berdasarkan tanggal.
    |--------------------------------------------------------------------------
    */

    if (
        !form.value.borrow_date ||
        !form.value.expected_return_date
    ) {
        return false;
    }

    return !!getInventoryConflict(
        inventoryId,
    );
};

/*
|--------------------------------------------------------------------------
| Inventory Conflict Message
|--------------------------------------------------------------------------
*/

const getInventoryUnavailableMessage = (
    inventory: RoomInventory,
): string => {
    const conflict =
        getInventoryConflict(
            inventory.id,
        );

    if (!conflict) {
        return "";
    }

    const start =
        formatDate(
            conflict.borrow_date,
        );

    const end =
        formatDate(
            conflict.expected_return_date,
        );

    if (start && end) {
        return `Tidak tersedia — sudah dipesan ${start} s/d ${end}`;
    }

    return "Tidak tersedia — sedang dalam peminjaman.";
};

/*
|--------------------------------------------------------------------------
| Available Inventory
|--------------------------------------------------------------------------
|
| Semua inventory tetap ditampilkan.
|
| Inventory yang bentrok dengan tanggal dipisahkan
| ke bagian unavailable sehingga user dapat melihat
| kenapa aset tidak bisa dipilih.
|
*/

const availableInventories =
    computed(() => {
        return props.roomInventories.filter(
            (inventory) =>
                !isInventoryUnavailable(
                    inventory.id,
                ),
        );
    });

const unavailableInventories =
    computed(() => {
        return props.roomInventories.filter(
            (inventory) =>
                isInventoryUnavailable(
                    inventory.id,
                ),
        );
    });

/*
|--------------------------------------------------------------------------
| Selected Inventory
|--------------------------------------------------------------------------
*/

const selectedInventory =
    computed(() => {
        if (
            form.value.room_inventory_id ===
            null
        ) {
            return null;
        }

        return (
            props.roomInventories.find(
                (inventory) =>
                    Number(
                        inventory.id,
                    ) ===
                    Number(
                        form.value
                            .room_inventory_id,
                    ),
            ) ?? null
        );
    });

/*
|--------------------------------------------------------------------------
| Date Warning
|--------------------------------------------------------------------------
*/

const dateWarning = computed(() => {
    if (
        !form.value.borrow_date ||
        !form.value.expected_return_date
    ) {
        return null;
    }

    const borrowDate =
        parseDate(
            form.value.borrow_date,
        );

    const returnDate =
        parseDate(
            form.value.expected_return_date,
        );

    if (
        borrowDate === null ||
        returnDate === null
    ) {
        return null;
    }

    if (returnDate < borrowDate) {
        return "Tanggal pengembalian harus sama atau setelah tanggal peminjaman.";
    }

    return null;
});

/*
|--------------------------------------------------------------------------
| Inventory Availability Summary
|--------------------------------------------------------------------------
*/

const availabilityMessage =
    computed(() => {
        if (
            !form.value.borrow_date ||
            !form.value.expected_return_date
        ) {
            return "Pilih tanggal peminjaman dan pengembalian untuk melihat ketersediaan aset.";
        }

        if (
            availableInventories.value
                .length === 0
        ) {
            return "Tidak ada aset yang tersedia pada rentang tanggal tersebut.";
        }

        return `${availableInventories.value.length} aset tersedia untuk periode yang dipilih.`;
    });

/*
|--------------------------------------------------------------------------
| Canvas
|--------------------------------------------------------------------------
*/

const canvasRef =
    ref<HTMLCanvasElement | null>(null);

const isDrawing = ref(false);

const hasSignature = ref(false);

let canvasContext:
    CanvasRenderingContext2D | null =
    null;

const CANVAS_WIDTH = 800;
const CANVAS_HEIGHT = 250;

/*
|--------------------------------------------------------------------------
| Form Validation
|--------------------------------------------------------------------------
*/

const isValid = computed(() => {
    return (
        form.value.faculty_id !== null &&
        form.value.room_inventory_id !== null &&
        form.value.borrow_date.trim() !== "" &&
        form.value.expected_return_date.trim() !== "" &&
        form.value.purpose.trim() !== "" &&
        !dateWarning.value &&
        !isInventoryUnavailable(
            form.value.room_inventory_id,
        )
    );
});

/*
|--------------------------------------------------------------------------
| Reset Form
|--------------------------------------------------------------------------
*/

const resetForm = () => {
    form.value = {
        faculty_id:
            props.borrowing?.faculty_id ??
            null,

        room_inventory_id:
            props.borrowing
                ?.room_inventory_id ??
            null,

        borrow_date:
            props.borrowing
                ?.borrow_date ??
            "",

        expected_return_date:
            props.borrowing
                ?.expected_return_date ??
            "",

        purpose:
            props.borrowing
                ?.purpose ??
            "",

        applicant_signature:
            null,
    };

    localError.value = null;

    isDrawing.value = false;

    hasSignature.value = false;

    canvasContext = null;

    nextTick(() => {
        initializeCanvas();
    });
};

/*
|--------------------------------------------------------------------------
| Initialize Canvas
|--------------------------------------------------------------------------
*/

const initializeCanvas = () => {
    const canvas = canvasRef.value;

    if (!canvas) {
        return;
    }

    canvas.width = CANVAS_WIDTH;
    canvas.height = CANVAS_HEIGHT;

    const context = canvas.getContext("2d");

    if (!context) {
        canvasContext = null;
        return;
    }

    canvasContext = context;

    canvasContext.clearRect(
        0,
        0,
        CANVAS_WIDTH,
        CANVAS_HEIGHT,
    );

    canvasContext.lineWidth = 2.5;

    canvasContext.lineCap = "round";

    canvasContext.lineJoin = "round";

    canvasContext.strokeStyle = "#1b1b18";

    hasSignature.value = false;

    form.value.applicant_signature =
        null;
};

/*
|--------------------------------------------------------------------------
| Pointer Position
|--------------------------------------------------------------------------
*/

const getPointerPosition = (
    event: PointerEvent,
) => {
    const canvas = canvasRef.value;

    if (!canvas) {
        return null;
    }

    const rect =
        canvas.getBoundingClientRect();

    if (
        rect.width === 0 ||
        rect.height === 0
    ) {
        return null;
    }

    const scaleX =
        canvas.width / rect.width;

    const scaleY =
        canvas.height / rect.height;

    return {
        x:
            (event.clientX - rect.left) *
            scaleX,

        y:
            (event.clientY - rect.top) *
            scaleY,
    };
};

/*
|--------------------------------------------------------------------------
| Start Drawing
|--------------------------------------------------------------------------
*/

const startDrawing = (
    event: PointerEvent,
) => {
    if (props.processing) {
        return;
    }

    const canvas = canvasRef.value;

    if (!canvas || !canvasContext) {
        return;
    }

    const position =
        getPointerPosition(event);

    if (!position) {
        return;
    }

    isDrawing.value = true;

    try {
        canvas.setPointerCapture(
            event.pointerId,
        );
    } catch {
        // Pointer capture tidak tersedia.
    }

    canvasContext.beginPath();

    canvasContext.moveTo(
        position.x,
        position.y,
    );
};

/*
|--------------------------------------------------------------------------
| Draw
|--------------------------------------------------------------------------
*/

const draw = (
    event: PointerEvent,
) => {
    if (
        !isDrawing.value ||
        props.processing ||
        !canvasContext
    ) {
        return;
    }

    const position =
        getPointerPosition(event);

    if (!position) {
        return;
    }

    canvasContext.lineTo(
        position.x,
        position.y,
    );

    canvasContext.stroke();

    hasSignature.value = true;
};

/*
|--------------------------------------------------------------------------
| Stop Drawing
|--------------------------------------------------------------------------
*/

const stopDrawing = (
    event?: PointerEvent,
) => {
    if (!isDrawing.value) {
        return;
    }

    isDrawing.value = false;

    if (
        canvasRef.value &&
        event
    ) {
        try {
            canvasRef.value.releasePointerCapture(
                event.pointerId,
            );
        } catch {
            // Pointer capture sudah dilepas.
        }
    }

    saveCanvasAsBase64();
};

/*
|--------------------------------------------------------------------------
| Save Signature
|--------------------------------------------------------------------------
*/

const saveCanvasAsBase64 = () => {
    const canvas = canvasRef.value;

    if (
        !canvas ||
        !hasSignature.value
    ) {
        form.value.applicant_signature =
            null;

        return;
    }

    form.value.applicant_signature =
        canvas.toDataURL(
            "image/png",
        );
};

/*
|--------------------------------------------------------------------------
| Clear Signature
|--------------------------------------------------------------------------
*/

const clearSignature = () => {
    if (props.processing) {
        return;
    }

    initializeCanvas();
};

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    localError.value = null;

    if (!isValid.value) {
        if (dateWarning.value) {
            localError.value =
                dateWarning.value;
        } else if (
            form.value.room_inventory_id !==
                null &&
            isInventoryUnavailable(
                form.value.room_inventory_id,
            )
        ) {
            localError.value =
                "Aset yang dipilih tidak tersedia pada rentang tanggal tersebut. Silakan pilih aset atau tanggal lain.";
        } else {
            localError.value =
                "Lengkapi seluruh data peminjaman yang wajib diisi.";
        }

        return;
    }

    const borrowDate =
        parseDate(
            form.value.borrow_date,
        );

    const expectedReturnDate =
        parseDate(
            form.value
                .expected_return_date,
        );

    if (
        borrowDate === null ||
        expectedReturnDate === null
    ) {
        localError.value =
            "Format tanggal peminjaman tidak valid.";

        return;
    }

    if (
        expectedReturnDate <
        borrowDate
    ) {
        localError.value =
            "Tanggal pengembalian tidak boleh lebih awal dari tanggal peminjaman.";

        return;
    }

    if (
        form.value.room_inventory_id !==
            null &&
        isInventoryUnavailable(
            form.value.room_inventory_id,
        )
    ) {
        localError.value =
            "Aset yang dipilih sudah digunakan pada rentang tanggal tersebut.";

        return;
    }

    if (hasSignature.value) {
        saveCanvasAsBase64();
    }

    emit("submit", {
        faculty_id:
            form.value.faculty_id,

        room_inventory_id:
            form.value.room_inventory_id,

        borrow_date:
            form.value.borrow_date,

        expected_return_date:
            form.value.expected_return_date,

        purpose:
            form.value.purpose.trim(),

        applicant_signature:
            form.value.applicant_signature,
    });
};

/*
|--------------------------------------------------------------------------
| Close
|--------------------------------------------------------------------------
*/

const close = () => {
    if (props.processing) {
        return;
    }

    emit("close");
};

/*
|--------------------------------------------------------------------------
| Inventory Label
|--------------------------------------------------------------------------
*/

const getInventoryLabel = (
    inventory: RoomInventory,
) => {
    const itemName =
        inventory.item?.name ??
        "Barang tidak diketahui";

    const assetCode =
        inventory.asset_code ??
        "Tanpa kode aset";

    const roomName =
        inventory.room?.name ??
        "Ruangan tidak diketahui";

    return `${itemName} - ${assetCode} - ${roomName}`;
};

/*
|--------------------------------------------------------------------------
| Format Date
|--------------------------------------------------------------------------
*/

const formatDate = (
    value?: string | null,
) => {
    if (!value) {
        return "";
    }

    const timestamp =
        parseDate(value);

    if (timestamp === null) {
        return "";
    }

    return new Intl.DateTimeFormat(
        "id-ID",
        {
            day: "2-digit",
            month: "short",
            year: "numeric",
        },
    ).format(
        new Date(timestamp),
    );
};

/*
|--------------------------------------------------------------------------
| Watch Selected Inventory
|--------------------------------------------------------------------------
|
| Jika user sudah memilih Laptop A kemudian mengubah
| tanggal sehingga Laptop A bentrok, otomatis dilepas.
|--------------------------------------------------------------------------
*/

watch(
    [
        () => form.value.borrow_date,
        () =>
            form.value
                .expected_return_date,
    ],
    () => {
        if (
            form.value
                .room_inventory_id ===
            null
        ) {
            return;
        }

        if (
            isInventoryUnavailable(
                form.value
                    .room_inventory_id,
            )
        ) {
            form.value.room_inventory_id =
                null;
        }
    },
);

/*
|--------------------------------------------------------------------------
| Watch Modal
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

/*
|--------------------------------------------------------------------------
| Cleanup
|--------------------------------------------------------------------------
*/

onBeforeUnmount(() => {
    canvasContext = null;
});
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
                        {{
                            borrowing
                                ? "Edit Peminjaman"
                                : "Tambah Peminjaman"
                        }}
                    </h2>

                    <p
                        class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Pilih tanggal terlebih dahulu untuk melihat aset yang tersedia.
                    </p>
                </div>

                <button
                    type="button"
                    :disabled="processing"
                    class="rounded-lg p-2 text-[#706f6c] transition hover:bg-black/5 hover:text-[#1b1b18] disabled:cursor-not-allowed disabled:opacity-50 dark:text-[#A1A09A] dark:hover:bg-white/10 dark:hover:text-white"
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

                <div class="space-y-5">
                    <!-- Fakultas -->
                    <div>
                        <label
                            for="faculty_id"
                            class="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Fakultas
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="faculty_id"
                            v-model="form.faculty_id"
                            :disabled="processing"
                            class="w-full rounded-xl border border-black/10 bg-white px-4 py-3 text-sm text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-2 focus:ring-[#f53003]/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-[#0f0f0e] dark:text-[#EDEDEC]"
                        >
                            <option :value="null">
                                Pilih fakultas
                            </option>

                            <option
                                v-for="faculty in faculties"
                                :key="faculty.id"
                                :value="faculty.id"
                            >
                                {{ faculty.name }}
                            </option>
                        </select>

                        <p
                            v-if="errors.faculty_id"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ errors.faculty_id }}
                        </p>
                    </div>

                    <!-- Tanggal -->
                    <div
                        class="grid gap-5 sm:grid-cols-2"
                    >
                        <!-- Borrow Date -->
                        <div>
                            <label
                                for="borrow_date"
                                class="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Tanggal Peminjaman
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="borrow_date"
                                v-model="form.borrow_date"
                                :disabled="processing"
                                type="datetime-local"
                                class="w-full rounded-xl border border-black/10 bg-white px-4 py-3 text-sm text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-2 focus:ring-[#f53003]/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-[#0f0f0e] dark:text-[#EDEDEC]"
                            />

                            <p
                                v-if="errors.borrow_date"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ errors.borrow_date }}
                            </p>
                        </div>

                        <!-- Expected Return -->
                        <div>
                            <label
                                for="expected_return_date"
                                class="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Rencana Pengembalian
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="expected_return_date"
                                v-model="
                                    form.expected_return_date
                                "
                                :disabled="processing"
                                type="datetime-local"
                                class="w-full rounded-xl border border-black/10 bg-white px-4 py-3 text-sm text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-2 focus:ring-[#f53003]/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-[#0f0f0e] dark:text-[#EDEDEC]"
                            />

                            <p
                                v-if="
                                    errors.expected_return_date
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    errors.expected_return_date
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Availability Info -->
                    <div
                        class="rounded-xl border border-black/10 bg-black/[0.02] p-3 dark:border-white/10 dark:bg-white/[0.03]"
                    >
                        <div
                            class="flex items-start gap-3"
                        >
                            <div
                                class="mt-0.5 shrink-0"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-[#706f6c] dark:text-[#A1A09A]"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>

                            <div>
                                <p
                                    class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Ketersediaan aset
                                </p>

                                <p
                                    class="mt-0.5 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    {{ availabilityMessage }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Date Warning -->
                    <div
                        v-if="dateWarning"
                        class="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-900/50 dark:bg-amber-950/20"
                    >
                        <p
                            class="text-sm text-amber-700 dark:text-amber-400"
                        >
                            {{ dateWarning }}
                        </p>
                    </div>

                    <!-- Inventaris -->
                    <div>
                        <label
                            for="room_inventory_id"
                            class="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Inventaris / Barang
                            <span class="text-red-500">*</span>
                        </label>

                        <!--
                        |--------------------------------------------------------------------------
                        | Native select
                        |--------------------------------------------------------------------------
                        |
                        | Asset yang bentrok tetap ditampilkan tetapi disabled.
                        | Ini membuat user tahu aset tersebut memang ada,
                        | tetapi sedang tidak tersedia pada periode yang dipilih.
                        |
                        -->

                        <select
                            id="room_inventory_id"
                            v-model="form.room_inventory_id"
                            :disabled="
                                processing ||
                                roomInventories.length === 0
                            "
                            class="w-full rounded-xl border border-black/10 bg-white px-4 py-3 text-sm text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-2 focus:ring-[#f53003]/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-[#0f0f0e] dark:text-[#EDEDEC]"
                        >
                            <option :value="null">
                                Pilih inventaris
                            </option>

                            <!-- Available -->
                            <option
                                v-for="inventory in availableInventories"
                                :key="inventory.id"
                                :value="inventory.id"
                            >
                                {{ getInventoryLabel(inventory) }}
                            </option>

                            <!-- Unavailable -->
                            <option
                                v-for="inventory in unavailableInventories"
                                :key="`unavailable-${inventory.id}`"
                                :value="inventory.id"
                                disabled
                            >
                                {{
                                    getInventoryLabel(
                                        inventory,
                                    )
                                }}
                                —
                                {{
                                    getInventoryUnavailableMessage(
                                        inventory,
                                    )
                                }}
                            </option>
                        </select>

                        <p
                            v-if="
                                selectedInventory &&
                                !isInventoryUnavailable(
                                    selectedInventory.id,
                                )
                            "
                            class="mt-2 text-xs text-emerald-600 dark:text-emerald-400"
                        >
                            Aset tersedia untuk periode yang dipilih.
                        </p>

                        <p
                            v-if="
                                roomInventories.length === 0
                            "
                            class="mt-2 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Tidak ada inventaris yang dapat dipinjam.
                        </p>

                        <p
                            v-if="
                                unavailableInventories.length > 0 &&
                                form.borrow_date &&
                                form.expected_return_date
                            "
                            class="mt-2 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            {{
                                unavailableInventories.length
                            }}
                            aset tidak tersedia pada periode yang dipilih.
                        </p>

                        <p
                            v-if="errors.room_inventory_id"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ errors.room_inventory_id }}
                        </p>
                    </div>

                    <!-- Tujuan -->
                    <div>
                        <label
                            for="purpose"
                            class="mb-2 block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Tujuan Peminjaman
                            <span class="text-red-500">*</span>
                        </label>

                        <textarea
                            id="purpose"
                            v-model="form.purpose"
                            :disabled="processing"
                            rows="4"
                            maxlength="1000"
                            placeholder="Contoh: Digunakan untuk kegiatan praktikum mahasiswa..."
                            class="w-full resize-none rounded-xl border border-black/10 bg-white px-4 py-3 text-sm text-[#1b1b18] outline-none transition placeholder:text-[#9b9a97] focus:border-[#f53003] focus:ring-2 focus:ring-[#f53003]/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-[#0f0f0e] dark:text-[#EDEDEC]"
                        ></textarea>

                        <div
                            class="mt-1 flex justify-between"
                        >
                            <p
                                v-if="errors.purpose"
                                class="text-xs text-red-500"
                            >
                                {{ errors.purpose }}
                            </p>

                            <span
                                class="ml-auto text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{ form.purpose.length }}/1000 karakter
                            </span>
                        </div>
                    </div>

                    <!-- Tanda Tangan -->
                    <div>
                        <div
                            class="mb-2 flex items-center justify-between"
                        >
                            <label
                                class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Tanda Tangan Pemohon
                            </label>

                            <span
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{
                                    hasSignature
                                        ? "Sudah ditandatangani"
                                        : "Belum ditandatangani"
                                }}
                            </span>
                        </div>

                        <div
                            class="overflow-hidden rounded-xl border border-dashed border-black/15 bg-white dark:border-white/15 dark:bg-[#0f0f0e]"
                        >
                            <div
                                class="relative w-full"
                            >
                                <canvas
                                    ref="canvasRef"
                                    class="block h-[180px] w-full touch-none bg-white dark:bg-[#0f0f0e]"
                                    @pointerdown="startDrawing"
                                    @pointermove="draw"
                                    @pointerup="stopDrawing"
                                    @pointercancel="stopDrawing"
                                    @pointerleave="stopDrawing"
                                />

                                <div
                                    v-if="!hasSignature"
                                    class="pointer-events-none absolute inset-0 flex items-center justify-center"
                                >
                                    <span
                                        class="text-sm text-[#b0afac] dark:text-[#666560]"
                                    >
                                        Tanda tangan di sini
                                    </span>
                                </div>

                                <div
                                    class="pointer-events-none absolute bottom-8 left-8 right-8 border-b border-black/15 dark:border-white/15"
                                />
                            </div>

                            <div
                                class="flex items-center justify-between border-t border-black/10 px-4 py-3 dark:border-white/10"
                            >
                                <p
                                    class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Gunakan mouse atau layar sentuh untuk tanda tangan.
                                </p>

                                <button
                                    type="button"
                                    :disabled="
                                        processing ||
                                        !hasSignature
                                    "
                                    class="rounded-lg px-3 py-2 text-xs font-medium text-[#f53003] transition hover:bg-[#f53003]/10 disabled:cursor-not-allowed disabled:opacity-40"
                                    @click="clearSignature"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>

                        <p
                            v-if="
                                errors.applicant_signature
                            "
                            class="mt-1 text-xs text-red-500"
                        >
                            {{
                                errors.applicant_signature
                            }}
                        </p>
                    </div>
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

                        Menyimpan...
                    </span>

                    <span v-else>
                        {{
                            borrowing
                                ? "Simpan Perubahan"
                                : "Ajukan Peminjaman"
                        }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>
```
