<script setup lang="ts">
import { computed, ref, watch } from "vue";

interface Faculty {
    id: number;
    code: string;
    name: string;
}

interface Room {
    id: number;
    faculty_id: number | string;
    code: string;
    name: string;
    building?: string | null;
    floor?: string | null;
}

interface User {
    id: number;
    name: string;
    email?: string | null;
}

interface Procurement {
    id: number;
    faculty_id: number | string;
    requested_by: number | string;
    room_id?: number | string | null;

    item_name: string;
    quantity: number | string;

    type: "replacement" | "new_item";
    reason: string;

    requester_signature?: string | null;
    requested_at?: string | null;

    status:
        | "pending"
        | "approved"
        | "rejected"
        | "completed";

    processed_by?: number | string | null;
    approver_signature?: string | null;
    processed_at?: string | null;

    admin_note?: string | null;

    faculty?: Faculty | null;
    room?: Room | null;
    requester?: User | null;
    processor?: User | null;
}

type ApprovalAction = "approve" | "reject";

interface ApprovalFormData {
    action: ApprovalAction;
    approver_signature: string | null;
    admin_note: string | null;
}

const props = defineProps<{
    show: boolean;
    procurement?: Procurement | null;
    processing?: boolean;
}>();

const emit = defineEmits<{
    (
        e: "submit",
        data: ApprovalFormData,
    ): void;

    (e: "close"): void;
}>();

const emptyForm = (): ApprovalFormData => ({
    action: "approve",
    approver_signature: null,
    admin_note: null,
});

const form = ref<ApprovalFormData>(
    emptyForm(),
);

const errorMessage = ref("");

/*
|--------------------------------------------------------------------------
| COMPUTED
|--------------------------------------------------------------------------
*/

const isPending = computed(() => {
    return (
        props.procurement?.status ===
        "pending"
    );
});

const isApprove = computed(() => {
    return form.value.action === "approve";
});

const isReject = computed(() => {
    return form.value.action === "reject";
});

const typeLabel = computed(() => {
    if (!props.procurement) {
        return "—";
    }

    return props.procurement.type ===
        "replacement"
        ? "Penggantian Barang"
        : "Barang Baru";
});

const statusLabel = computed(() => {
    if (!props.procurement) {
        return "—";
    }

    switch (props.procurement.status) {
        case "pending":
            return "Menunggu Verifikasi";

        case "approved":
            return "Disetujui";

        case "rejected":
            return "Ditolak";

        case "completed":
            return "Selesai";

        default:
            return props.procurement.status;
    }
});

const statusClass = computed(() => {
    if (!props.procurement) {
        return "";
    }

    switch (props.procurement.status) {
        case "pending":
            return "border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-400";

        case "approved":
            return "border-green-200 bg-green-50 text-green-700 dark:border-green-900/50 dark:bg-green-950/20 dark:text-green-400";

        case "rejected":
            return "border-red-200 bg-red-50 text-red-700 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-400";

        case "completed":
            return "border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-400";

        default:
            return "border-[#e3e3e0] bg-slate-50 text-[#706f6c] dark:border-[#3E3E3A] dark:bg-white/5 dark:text-[#A1A09A]";
    }
});

/*
|--------------------------------------------------------------------------
| DATE FORMAT
|--------------------------------------------------------------------------
*/

const formatDate = (
    value?: string | null,
): string => {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat(
        "id-ID",
        {
            day: "2-digit",
            month: "long",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        },
    ).format(date);
};

/*
|--------------------------------------------------------------------------
| SYNC FORM
|--------------------------------------------------------------------------
*/

const syncForm = (
    procurement: Procurement | null | undefined,
) => {
    form.value = {
        action: "approve",

        approver_signature:
            procurement?.approver_signature ??
            null,

        admin_note:
            procurement?.admin_note ?? null,
    };

    errorMessage.value = "";
};

/*
|--------------------------------------------------------------------------
| WATCH PROCUREMENT
|--------------------------------------------------------------------------
*/

watch(
    () => props.procurement,
    (newVal) => {
        syncForm(newVal);
    },
    {
        immediate: true,
    },
);

/*
|--------------------------------------------------------------------------
| WATCH MODAL
|--------------------------------------------------------------------------
*/

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            syncForm(props.procurement);
        }
    },
);

/*
|--------------------------------------------------------------------------
| CHANGE ACTION
|--------------------------------------------------------------------------
*/

const setAction = (
    action: ApprovalAction,
) => {
    if (props.processing || !isPending.value) {
        return;
    }

    errorMessage.value = "";

    form.value.action = action;
};

/*
|--------------------------------------------------------------------------
| HANDLE SUBMIT
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    if (props.processing) {
        return;
    }

    errorMessage.value = "";

    if (!props.procurement) {
        errorMessage.value =
            "Data pengadaan tidak ditemukan.";
        return;
    }

    if (!isPending.value) {
        errorMessage.value =
            "Pengadaan ini sudah diproses dan tidak dapat diverifikasi kembali.";
        return;
    }

    const approverSignature =
        String(
            form.value.approver_signature ??
                "",
        ).trim() || null;

    const adminNote =
        String(
            form.value.admin_note ?? "",
        ).trim() || null;

    /*
    |--------------------------------------------------------------------------
    | VALIDASI REJECT
    |--------------------------------------------------------------------------
    */

    if (
        form.value.action === "reject" &&
        !adminNote
    ) {
        errorMessage.value =
            "Catatan wajib diisi ketika pengadaan ditolak.";
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | PAYLOAD
    |--------------------------------------------------------------------------
    */

    const payload: ApprovalFormData = {
        action: form.value.action,

        approver_signature:
            approverSignature,

        admin_note: adminNote,
    };

    console.log("=================================");
    console.log("PROCUREMENT APPROVAL");
    console.log(
        "PROCUREMENT ID:",
        props.procurement.id,
    );
    console.log(
        "ACTION:",
        payload.action,
    );
    console.log(
        "APPROVAL PAYLOAD:",
        payload,
    );
    console.log("=================================");

    emit("submit", payload);
};

/*
|--------------------------------------------------------------------------
| HANDLE CLOSE
|--------------------------------------------------------------------------
*/

const handleClose = () => {
    if (props.processing) {
        return;
    }

    errorMessage.value = "";

    emit("close");
};
</script>

<template>
    <div
        v-if="show && procurement"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        @click.self="handleClose"
    >
        <div
            class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-black/5 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-[#161615]"
        >
            <!-- HEADER -->
            <div
                class="flex items-center justify-between border-b border-[#e3e3e0] pb-4 dark:border-[#3E3E3A]"
            >
                <div>
                    <h3
                        class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Verifikasi Pengadaan
                    </h3>

                    <p
                        class="mt-1 text-[11px] text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Periksa pengajuan sebelum
                        menyetujui atau menolaknya.
                    </p>
                </div>

                <button
                    type="button"
                    @click="handleClose"
                    :disabled="processing"
                    class="rounded-lg p-1 text-[#706f6c] transition hover:bg-slate-100 hover:text-[#1b1b18] disabled:cursor-not-allowed disabled:opacity-50 dark:text-[#A1A09A] dark:hover:bg-[#20201e] dark:hover:text-[#EDEDEC]"
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
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- CONTENT -->
            <div class="mt-5 space-y-5">
                <!-- ERROR -->
                <div
                    v-if="errorMessage"
                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-400"
                >
                    {{ errorMessage }}
                </div>

                <!-- STATUS NON-PENDING -->
                <div
                    v-if="!isPending"
                    class="rounded-xl border p-4"
                    :class="statusClass"
                >
                    <p
                        class="text-[10px] font-medium uppercase tracking-wide opacity-70"
                    >
                        Status Saat Ini
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold"
                    >
                        {{ statusLabel }}
                    </p>

                    <p
                        class="mt-1 text-[11px] leading-relaxed opacity-80"
                    >
                        Pengadaan ini sudah diproses
                        sehingga tidak dapat
                        diverifikasi kembali.
                    </p>
                </div>

                <!-- SUMMARY -->
                <div>
                    <h4
                        class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Ringkasan Pengadaan
                    </h4>

                    <div
                        class="overflow-hidden rounded-xl border border-[#e3e3e0] dark:border-[#3E3E3A]"
                    >
                        <!-- ITEM -->
                        <div
                            class="grid grid-cols-3 gap-4 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                        >
                            <span
                                class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Barang
                            </span>

                            <span
                                class="col-span-2 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    procurement.item_name
                                }}
                            </span>
                        </div>

                        <!-- JUMLAH -->
                        <div
                            class="grid grid-cols-3 gap-4 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                        >
                            <span
                                class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Jumlah
                            </span>

                            <span
                                class="col-span-2 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    Number(
                                        procurement.quantity,
                                    ).toLocaleString(
                                        "id-ID",
                                    )
                                }}
                                unit
                            </span>
                        </div>

                        <!-- JENIS -->
                        <div
                            class="grid grid-cols-3 gap-4 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                        >
                            <span
                                class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Jenis
                            </span>

                            <div
                                class="col-span-2"
                            >
                                <span
                                    class="inline-flex rounded-full border px-2 py-1 text-[10px] font-semibold"
                                    :class="
                                        procurement.type ===
                                        'replacement'
                                            ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-400'
                                            : 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-400'
                                    "
                                >
                                    {{ typeLabel }}
                                </span>
                            </div>
                        </div>

                        <!-- FAKULTAS -->
                        <div
                            class="grid grid-cols-3 gap-4 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                        >
                            <span
                                class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Fakultas
                            </span>

                            <span
                                class="col-span-2 text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    procurement.faculty
                                        ?.code ??
                                    "—"
                                }}
                                -
                                {{
                                    procurement.faculty
                                        ?.name ??
                                    "—"
                                }}
                            </span>
                        </div>

                        <!-- RUANGAN -->
                        <div
                            class="grid grid-cols-3 gap-4 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                        >
                            <span
                                class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Ruangan
                            </span>

                            <div
                                class="col-span-2"
                            >
                                <p
                                    class="text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{
                                        procurement.room
                                            ? `${procurement.room.code} - ${procurement.room.name}`
                                            : "Tidak ditentukan"
                                    }}
                                </p>

                                <p
                                    v-if="
                                        procurement.room
                                            ?.building ||
                                        procurement.room
                                            ?.floor
                                    "
                                    class="mt-0.5 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    {{
                                        [
                                            procurement
                                                .room
                                                ?.building,
                                            procurement
                                                .room
                                                ?.floor,
                                        ]
                                            .filter(
                                                Boolean,
                                            )
                                            .join(
                                                " - ",
                                            )
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- PENGAJU -->
                        <div
                            class="grid grid-cols-3 gap-4 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                        >
                            <span
                                class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Pengaju
                            </span>

                            <span
                                class="col-span-2 text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    procurement.requester
                                        ?.name ??
                                    "—"
                                }}
                            </span>
                        </div>

                        <!-- TANGGAL -->
                        <div
                            class="grid grid-cols-3 gap-4 px-4 py-3"
                        >
                            <span
                                class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Tanggal
                            </span>

                            <span
                                class="col-span-2 text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    formatDate(
                                        procurement.requested_at,
                                    )
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ALASAN -->
                <div>
                    <h4
                        class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Alasan Pengadaan
                    </h4>

                    <div
                        class="rounded-xl border border-[#e3e3e0] bg-slate-50 px-4 py-3 dark:border-[#3E3E3A] dark:bg-white/5"
                    >
                        <p
                            class="whitespace-pre-line text-xs leading-relaxed text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{
                                procurement.reason ||
                                "Tidak ada alasan."
                            }}
                        </p>
                    </div>
                </div>

                <!-- ACTION -->
                <div v-if="isPending">
                    <h4
                        class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Keputusan Verifikasi
                    </h4>

                    <div
                        class="grid grid-cols-2 gap-3"
                    >
                        <!-- APPROVE -->
                        <button
                            type="button"
                            @click="
                                setAction(
                                    'approve',
                                )
                            "
                            :disabled="processing"
                            class="rounded-xl border px-4 py-3 text-left transition disabled:cursor-not-allowed disabled:opacity-50"
                            :class="
                                isApprove
                                    ? 'border-green-300 bg-green-50 text-green-700 ring-1 ring-green-300 dark:border-green-800 dark:bg-green-950/20 dark:text-green-400 dark:ring-green-800'
                                    : 'border-[#e3e3e0] bg-white text-[#1b1b18] hover:bg-slate-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]'
                            "
                        >
                            <div
                                class="flex items-center gap-2"
                            >
                                <div
                                    class="flex h-7 w-7 items-center justify-center rounded-full"
                                    :class="
                                        isApprove
                                            ? 'bg-green-100 dark:bg-green-900/40'
                                            : 'bg-slate-100 dark:bg-white/5'
                                    "
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
                                            d="m4.5 12.75 6 6 9-13.5"
                                        />
                                    </svg>
                                </div>

                                <div>
                                    <p
                                        class="text-xs font-semibold"
                                    >
                                        Setujui
                                    </p>

                                    <p
                                        class="mt-0.5 text-[10px] opacity-70"
                                    >
                                        Verifikasi
                                        disetujui
                                    </p>
                                </div>
                            </div>
                        </button>

                        <!-- REJECT -->
                        <button
                            type="button"
                            @click="
                                setAction(
                                    'reject',
                                )
                            "
                            :disabled="processing"
                            class="rounded-xl border px-4 py-3 text-left transition disabled:cursor-not-allowed disabled:opacity-50"
                            :class="
                                isReject
                                    ? 'border-red-300 bg-red-50 text-red-700 ring-1 ring-red-300 dark:border-red-800 dark:bg-red-950/20 dark:text-red-400 dark:ring-red-800'
                                    : 'border-[#e3e3e0] bg-white text-[#1b1b18] hover:bg-slate-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]'
                            "
                        >
                            <div
                                class="flex items-center gap-2"
                            >
                                <div
                                    class="flex h-7 w-7 items-center justify-center rounded-full"
                                    :class="
                                        isReject
                                            ? 'bg-red-100 dark:bg-red-900/40'
                                            : 'bg-slate-100 dark:bg-white/5'
                                    "
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
                                </div>

                                <div>
                                    <p
                                        class="text-xs font-semibold"
                                    >
                                        Tolak
                                    </p>

                                    <p
                                        class="mt-0.5 text-[10px] opacity-70"
                                    >
                                        Pengajuan
                                        ditolak
                                    </p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- CATATAN -->
                <div v-if="isPending">
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Catatan Verifikasi

                        <span
                            v-if="isReject"
                            class="text-red-500"
                        >
                            *
                        </span>

                        <span
                            v-else
                            class="text-[#a1a09a]"
                        >
                            (Opsional)
                        </span>
                    </label>

                    <textarea
                        v-model="
                            form.admin_note
                        "
                        rows="4"
                        :disabled="processing"
                        :placeholder="
                            isReject
                                ? 'Jelaskan alasan penolakan pengadaan...'
                                : 'Tambahkan catatan verifikasi jika diperlukan...'
                        "
                        class="w-full resize-none rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    ></textarea>

                    <p
                        v-if="isReject"
                        class="mt-1 text-[10px] text-red-500"
                    >
                        Catatan wajib diisi untuk
                        menjelaskan alasan penolakan.
                    </p>
                </div>

                <!-- SIGNATURE -->
                <div v-if="isPending">
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Tanda Tangan Verifikator

                        <span
                            class="text-[#a1a09a]"
                        >
                            (Opsional)
                        </span>
                    </label>

                    <input
                        v-model="
                            form.approver_signature
                        "
                        type="text"
                        :disabled="processing"
                        placeholder="Masukkan data/path tanda tangan"
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    />

                    <p
                        class="mt-1 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Tanda tangan ini digunakan
                        pada dokumen pengadaan setelah
                        diverifikasi.
                    </p>
                </div>

                <!-- INFO APPROVED -->
                <div
                    v-if="
                        procurement.status ===
                        'approved'
                    "
                    class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 dark:border-green-900/50 dark:bg-green-950/20"
                >
                    <p
                        class="text-xs font-semibold text-green-700 dark:text-green-400"
                    >
                        Pengadaan telah diverifikasi.
                    </p>

                    <p
                        class="mt-1 text-[11px] leading-relaxed text-green-700/80 dark:text-green-400/80"
                    >
                        Status ini berarti pengajuan
                        sudah diverifikasi oleh Super
                        Admin dan dapat dilanjutkan ke
                        proses pencetakan laporan untuk
                        Pimpinan Yayasan.
                    </p>
                </div>

                <!-- INFO REJECTED -->
                <div
                    v-if="
                        procurement.status ===
                        'rejected'
                    "
                    class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 dark:border-red-900/50 dark:bg-red-950/20"
                >
                    <p
                        class="text-xs font-semibold text-red-700 dark:text-red-400"
                    >
                        Pengadaan telah ditolak.
                    </p>

                    <p
                        v-if="procurement.admin_note"
                        class="mt-1 whitespace-pre-line text-[11px] leading-relaxed text-red-700/80 dark:text-red-400/80"
                    >
                        {{ procurement.admin_note }}
                    </p>
                </div>
            </div>

            <!-- FOOTER -->
            <div
                class="mt-6 flex items-center justify-end gap-2 border-t border-[#e3e3e0] pt-4 dark:border-[#3E3E3A]"
            >
                <button
                    type="button"
                    @click="handleClose"
                    :disabled="processing"
                    class="rounded-lg border border-[#e3e3e0] bg-white px-4 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                >
                    Tutup
                </button>

                <button
                    v-if="isPending"
                    type="button"
                    @click="handleSubmit"
                    :disabled="processing"
                    class="rounded-lg px-4 py-2 text-xs font-medium text-white transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    :class="
                        isApprove
                            ? 'bg-[#16a34a] hover:bg-[#15803d] focus:ring-[#16a34a] dark:bg-[#22c55e] dark:hover:bg-[#16a34a] dark:focus:ring-[#22c55e] dark:focus:ring-offset-[#161615]'
                            : 'bg-[#dc2626] hover:bg-[#b91c1c] focus:ring-[#dc2626] dark:bg-[#ef4444] dark:hover:bg-[#dc2626] dark:focus:ring-[#ef4444] dark:focus:ring-offset-[#161615]'
                    "
                >
                    {{
                        processing
                            ? "Memproses..."
                            : isApprove
                              ? "Setujui Pengadaan"
                              : "Tolak Pengadaan"
                    }}
                </button>
            </div>
        </div>
    </div>
</template>
