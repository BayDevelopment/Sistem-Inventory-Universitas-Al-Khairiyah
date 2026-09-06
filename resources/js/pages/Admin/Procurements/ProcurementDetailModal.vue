<script setup lang="ts">
import { computed } from "vue";

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

    created_at?: string | null;
    updated_at?: string | null;

    faculty?: Faculty | null;
    room?: Room | null;
    requester?: User | null;
    processor?: User | null;
}

const props = defineProps<{
    show: boolean;
    procurement?: Procurement | null;
}>();

const emit = defineEmits<{
    (e: "close"): void;
}>();

/*
|--------------------------------------------------------------------------
| COMPUTED
|--------------------------------------------------------------------------
*/

const facultyName = computed(() => {
    return (
        props.procurement?.faculty?.name ??
        "—"
    );
});

const facultyCode = computed(() => {
    return (
        props.procurement?.faculty?.code ??
        "—"
    );
});

const roomName = computed(() => {
    if (!props.procurement) {
        return "—";
    }

    if (props.procurement.room) {
        return `${props.procurement.room.code} - ${props.procurement.room.name}`;
    }

    return "Tidak ditentukan";
});

const roomLocation = computed(() => {
    const room = props.procurement?.room;

    if (!room) {
        return "";
    }

    const location = [
        room.building,
        room.floor,
    ].filter(Boolean);

    return location.join(" - ");
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

const requesterName = computed(() => {
    return (
        props.procurement?.requester?.name ??
        "—"
    );
});

const processorName = computed(() => {
    return (
        props.procurement?.processor?.name ??
        "—"
    );
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
| HANDLE CLOSE
|--------------------------------------------------------------------------
*/

const handleClose = () => {
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
            class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-black/5 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-[#161615]"
        >
            <!-- HEADER -->
            <div
                class="flex items-center justify-between border-b border-[#e3e3e0] pb-4 dark:border-[#3E3E3A]"
            >
                <div>
                    <h3
                        class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Detail Pengadaan
                    </h3>

                    <p
                        class="mt-1 text-[11px] text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Informasi lengkap pengajuan
                        pengadaan barang.
                    </p>
                </div>

                <button
                    type="button"
                    @click="handleClose"
                    class="rounded-lg p-1 text-[#706f6c] transition hover:bg-slate-100 hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#20201e] dark:hover:text-[#EDEDEC]"
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
                <!-- STATUS -->
                <div
                    class="flex items-center justify-between rounded-xl border p-4"
                    :class="statusClass"
                >
                    <div>
                        <p
                            class="text-[10px] font-medium uppercase tracking-wide opacity-70"
                        >
                            Status Pengadaan
                        </p>

                        <p
                            class="mt-1 text-sm font-semibold"
                        >
                            {{ statusLabel }}
                        </p>
                    </div>

                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-white/70 dark:bg-black/10"
                    >
                        <!-- PENDING -->
                        <svg
                            v-if="
                                procurement.status ===
                                'pending'
                            "
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
                                d="M12 6v6l4 2"
                            />
                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                            />
                        </svg>

                        <!-- APPROVED -->
                        <svg
                            v-else-if="
                                procurement.status ===
                                'approved'
                            "
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
                                d="m4.5 12.75 6 6 9-13.5"
                            />
                        </svg>

                        <!-- REJECTED -->
                        <svg
                            v-else-if="
                                procurement.status ===
                                'rejected'
                            "
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

                        <!-- COMPLETED -->
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
                                d="M9 12.75 11.25 15 15 9.75"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 3v18"
                            />
                        </svg>
                    </div>
                </div>

                <!-- INFORMASI PENGADAAN -->
                <div>
                    <h4
                        class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Informasi Pengadaan
                    </h4>

                    <div
                        class="overflow-hidden rounded-xl border border-[#e3e3e0] dark:border-[#3E3E3A]"
                    >
                        <!-- FAKULTAS -->
                        <div
                            class="grid grid-cols-3 gap-4 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                        >
                            <div
                                class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Fakultas
                            </div>

                            <div
                                class="col-span-2 text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{ facultyCode }}
                                -
                                {{ facultyName }}
                            </div>
                        </div>

                        <!-- RUANGAN -->
                        <div
                            class="grid grid-cols-3 gap-4 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                        >
                            <div
                                class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Ruangan
                            </div>

                            <div
                                class="col-span-2"
                            >
                                <p
                                    class="text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ roomName }}
                                </p>

                                <p
                                    v-if="roomLocation"
                                    class="mt-0.5 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    {{ roomLocation }}
                                </p>
                            </div>
                        </div>

                        <!-- NAMA BARANG -->
                        <div
                            class="grid grid-cols-3 gap-4 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                        >
                            <div
                                class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Nama Barang
                            </div>

                            <div
                                class="col-span-2 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{ procurement.item_name }}
                            </div>
                        </div>

                        <!-- JUMLAH -->
                        <div
                            class="grid grid-cols-3 gap-4 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                        >
                            <div
                                class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Jumlah
                            </div>

                            <div
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
                            </div>
                        </div>

                        <!-- JENIS -->
                        <div
                            class="grid grid-cols-3 gap-4 px-4 py-3"
                        >
                            <div
                                class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Jenis Pengadaan
                            </div>

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
                                "Tidak ada alasan yang diberikan."
                            }}
                        </p>
                    </div>
                </div>

                <!-- PENGAJUAN -->
                <div>
                    <h4
                        class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Informasi Pengajuan
                    </h4>

                    <div
                        class="grid grid-cols-2 gap-4"
                    >
                        <!-- PENGAJU -->
                        <div
                            class="rounded-xl border border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                        >
                            <p
                                class="text-[10px] font-medium uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Diajukan Oleh
                            </p>

                            <p
                                class="mt-1 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{ requesterName }}
                            </p>
                        </div>

                        <!-- TANGGAL -->
                        <div
                            class="rounded-xl border border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                        >
                            <p
                                class="text-[10px] font-medium uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Tanggal Pengajuan
                            </p>

                            <p
                                class="mt-1 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    formatDate(
                                        procurement.requested_at,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- VERIFIKASI -->
                <div
                    v-if="
                        procurement.status ===
                            'approved' ||
                        procurement.status ===
                            'rejected' ||
                        procurement.status ===
                            'completed'
                    "
                >
                    <h4
                        class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Informasi Verifikasi
                    </h4>

                    <div
                        class="grid grid-cols-2 gap-4"
                    >
                        <!-- VERIFIKATOR -->
                        <div
                            class="rounded-xl border border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                        >
                            <p
                                class="text-[10px] font-medium uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Diverifikasi Oleh
                            </p>

                            <p
                                class="mt-1 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{ processorName }}
                            </p>
                        </div>

                        <!-- TANGGAL PROSES -->
                        <div
                            class="rounded-xl border border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                        >
                            <p
                                class="text-[10px] font-medium uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Tanggal Verifikasi
                            </p>

                            <p
                                class="mt-1 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    formatDate(
                                        procurement.processed_at,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- CATATAN ADMIN -->
                <div
                    v-if="procurement.admin_note"
                >
                    <h4
                        class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Catatan Verifikasi
                    </h4>

                    <div
                        class="rounded-xl border border-[#e3e3e0] bg-slate-50 px-4 py-3 dark:border-[#3E3E3A] dark:bg-white/5"
                    >
                        <p
                            class="whitespace-pre-line text-xs leading-relaxed text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ procurement.admin_note }}
                        </p>
                    </div>
                </div>

                <!-- TANDA TANGAN -->
                <div
                    v-if="
                        procurement.requester_signature ||
                        procurement.approver_signature
                    "
                >
                    <h4
                        class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Tanda Tangan
                    </h4>

                    <div
                        class="grid grid-cols-2 gap-4"
                    >
                        <!-- PENGAJU -->
                        <div
                            v-if="
                                procurement.requester_signature
                            "
                            class="rounded-xl border border-[#e3e3e0] px-4 py-4 dark:border-[#3E3E3A]"
                        >
                            <p
                                class="mb-3 text-center text-[10px] font-medium uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Pengaju
                            </p>

                            <div
                                class="flex min-h-[80px] items-center justify-center"
                            >
                                <img
                                    v-if="
                                        procurement.requester_signature.startsWith(
                                            'http',
                                        ) ||
                                        procurement.requester_signature.startsWith(
                                            '/',
                                        )
                                    "
                                    :src="
                                        procurement.requester_signature
                                    "
                                    alt="Tanda tangan pengaju"
                                    class="max-h-20 max-w-full object-contain"
                                />

                                <span
                                    v-else
                                    class="text-center text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{
                                        procurement.requester_signature
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- VERIFIKATOR -->
                        <div
                            v-if="
                                procurement.approver_signature
                            "
                            class="rounded-xl border border-[#e3e3e0] px-4 py-4 dark:border-[#3E3E3A]"
                        >
                            <p
                                class="mb-3 text-center text-[10px] font-medium uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Verifikator
                            </p>

                            <div
                                class="flex min-h-[80px] items-center justify-center"
                            >
                                <img
                                    v-if="
                                        procurement.approver_signature.startsWith(
                                            'http',
                                        ) ||
                                        procurement.approver_signature.startsWith(
                                            '/',
                                        )
                                    "
                                    :src="
                                        procurement.approver_signature
                                    "
                                    alt="Tanda tangan verifikator"
                                    class="max-h-20 max-w-full object-contain"
                                />

                                <span
                                    v-else
                                    class="text-center text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{
                                        procurement.approver_signature
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ID PENGADAAN -->
                <div
                    class="rounded-lg border border-[#e3e3e0] bg-slate-50 px-3 py-2 dark:border-[#3E3E3A] dark:bg-white/5"
                >
                    <div
                        class="flex items-center justify-between gap-4"
                    >
                        <span
                            class="text-[10px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            ID Pengadaan
                        </span>

                        <span
                            class="font-mono text-[10px] font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            #{{ procurement.id }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <div
                class="mt-6 flex items-center justify-end border-t border-[#e3e3e0] pt-4 dark:border-[#3E3E3A]"
            >
                <button
                    type="button"
                    @click="handleClose"
                    class="rounded-lg border border-[#e3e3e0] bg-white px-4 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>
