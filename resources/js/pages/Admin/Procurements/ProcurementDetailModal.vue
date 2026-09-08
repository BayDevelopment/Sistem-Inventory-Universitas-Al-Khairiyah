<script setup lang="ts">
import {
    computed,
    onBeforeUnmount,
    ref,
    watch,
} from "vue";

/*
|--------------------------------------------------------------------------
| TYPES
|--------------------------------------------------------------------------
*/

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

interface ProcurementAttachment {
    path: string;
    name: string;
    url: string;
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
    subject?: string | null;

    attachments?: ProcurementAttachment[] | null;

    requester_signature?: string | null;
    requested_at?: string | null;

    status:
        | "pending"
        | "approved"
        | "rejected"
        | "completed";

    document_number?: string | null;

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

/*
|--------------------------------------------------------------------------
| PROPS / EMITS
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        show: boolean;
        procurement?: Procurement | null;
    }>(),
    {
        procurement: null,
    },
);

const emit = defineEmits<{
    close: [];
}>();

/*
|--------------------------------------------------------------------------
| COMPUTED
|--------------------------------------------------------------------------
*/

const facultyName = computed(() => {
    return props.procurement?.faculty?.name ?? "—";
});

const facultyCode = computed(() => {
    return props.procurement?.faculty?.code ?? "—";
});

const roomName = computed(() => {
    const room = props.procurement?.room;

    if (!room) {
        return "Tidak ditentukan";
    }

    return `${room.code} - ${room.name}`;
});

const roomLocation = computed(() => {
    const room = props.procurement?.room;

    if (!room) {
        return "";
    }

    return [room.building, room.floor]
        .filter(
            (value): value is string =>
                Boolean(value),
        )
        .join(" - ");
});

const typeLabel = computed(() => {
    if (!props.procurement) {
        return "—";
    }

    return props.procurement.type === "replacement"
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

const statusIconClass = computed(() => {
    if (!props.procurement) {
        return "";
    }

    switch (props.procurement.status) {
        case "pending":
            return "text-amber-600 dark:text-amber-400";

        case "approved":
            return "text-green-600 dark:text-green-400";

        case "rejected":
            return "text-red-600 dark:text-red-400";

        case "completed":
            return "text-blue-600 dark:text-blue-400";

        default:
            return "text-[#706f6c] dark:text-[#A1A09A]";
    }
});

const requesterName = computed(() => {
    return props.procurement?.requester?.name ?? "—";
});

const processorName = computed(() => {
    return props.procurement?.processor?.name ?? "—";
});

const attachments = computed(() => {
    return props.procurement?.attachments ?? [];
});

const hasAttachments = computed(() => {
    return attachments.value.length > 0;
});

const hasVerification = computed(() => {
    const procurement = props.procurement;

    if (!procurement) {
        return false;
    }

    return (
        procurement.processed_by !== null &&
        procurement.processed_by !== undefined
    ) ||
        Boolean(procurement.processed_at) ||
        Boolean(procurement.processor) ||
        procurement.status === "approved" ||
        procurement.status === "rejected" ||
        procurement.status === "completed";
});

const hasSignatures = computed(() => {
    return Boolean(
        props.procurement?.requester_signature ||
            props.procurement?.approver_signature,
    );
});

/*
|--------------------------------------------------------------------------
| FORMAT
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

const formatQuantity = (
    value: number | string,
): string => {
    const quantity = Number(value);

    if (!Number.isFinite(quantity)) {
        return String(value);
    }

    return quantity.toLocaleString("id-ID");
};

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

const isImageUrl = (
    value?: string | null,
): boolean => {
    if (!value) {
        return false;
    }

    return (
        value.startsWith("http://") ||
        value.startsWith("https://") ||
        value.startsWith("/") ||
        value.startsWith("data:image/")
    );
};

const getAttachmentName = (
    attachment: ProcurementAttachment,
): string => {
    return attachment.name || "Lampiran";
};

/*
|--------------------------------------------------------------------------
| MODAL LIFECYCLE
|--------------------------------------------------------------------------
|
| Mengikuti pola ProcurementApprovalModal:
| - Escape ditangani di window
| - body scroll dikunci ketika modal terbuka
| - listener dibersihkan ketika modal ditutup
| - overflow body dikembalikan ketika modal ditutup/unmount
|--------------------------------------------------------------------------
*/

const handleKeydown = (
    event: KeyboardEvent,
) => {
    if (
        event.key === "Escape" &&
        props.show
    ) {
        handleClose();
    }
};

let previousBodyOverflow = "";

watch(
    () => props.show,
    (isOpen) => {
        if (typeof window === "undefined") {
            return;
        }

        if (!isOpen) {
            window.removeEventListener(
                "keydown",
                handleKeydown,
            );

            document.body.style.overflow =
                previousBodyOverflow;

            return;
        }

        window.addEventListener(
            "keydown",
            handleKeydown,
        );

        previousBodyOverflow =
            document.body.style.overflow;

        document.body.style.overflow = "hidden";
    },
    {
        immediate: true,
    },
);

/*
|--------------------------------------------------------------------------
| HANDLE CLOSE
|--------------------------------------------------------------------------
*/

const handleClose = () => {
    emit("close");
};

/*
|--------------------------------------------------------------------------
| CLEANUP
|--------------------------------------------------------------------------
*/

onBeforeUnmount(() => {
    if (typeof window !== "undefined") {
        window.removeEventListener(
            "keydown",
            handleKeydown,
        );
    }

    if (
        typeof document !== "undefined" &&
        props.show
    ) {
        document.body.style.overflow =
            previousBodyOverflow;
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show && procurement"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
                aria-labelledby="procurement-detail-title"
                @click.self="handleClose"
            >
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="scale-95 opacity-0"
                    enter-to-class="scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="scale-100 opacity-100"
                    leave-to-class="scale-95 opacity-0"
                >
                    <div
                        class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-black/5 bg-white shadow-xl dark:border-white/10 dark:bg-[#161615]"
                    >
                        <!--
                        |--------------------------------------------------------------------------
                        | HEADER
                        |--------------------------------------------------------------------------
                        -->

                        <div
                            class="sticky top-0 z-10 flex items-center justify-between border-b border-[#e3e3e0] bg-white px-5 py-4 dark:border-[#3E3E3A] dark:bg-[#161615] sm:px-6"
                        >
                            <div class="min-w-0 pr-4">
                                <h3
                                    id="procurement-detail-title"
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
                                class="shrink-0 rounded-lg p-2 text-[#706f6c] transition hover:bg-slate-100 hover:text-[#1b1b18] focus:outline-none focus:ring-2 focus:ring-black/10 dark:text-[#A1A09A] dark:hover:bg-[#20201e] dark:hover:text-[#EDEDEC] dark:focus:ring-white/10"
                                aria-label="Tutup detail pengadaan"
                                @click="handleClose"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 18 18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!--
                        |--------------------------------------------------------------------------
                        | CONTENT
                        |--------------------------------------------------------------------------
                        -->

                        <div
                            class="space-y-5 px-5 py-5 sm:px-6"
                        >
                            <!-- STATUS -->

                            <section>
                                <div
                                    class="flex items-center justify-between gap-4 rounded-xl border p-4"
                                    :class="statusClass"
                                >
                                    <div class="min-w-0">
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

                                        <p
                                            v-if="
                                                procurement.document_number
                                            "
                                            class="mt-1 break-all font-mono text-[10px] opacity-70"
                                        >
                                            {{
                                                procurement.document_number
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/70 dark:bg-black/10"
                                        :class="statusIconClass"
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
                                            aria-hidden="true"
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
                                            aria-hidden="true"
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
                                            aria-hidden="true"
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
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="m5 12 4 4L19 6"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </section>

                            <!-- INFORMASI PENGADAAN -->

                            <section>
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
                                        class="grid grid-cols-1 gap-1 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A] sm:grid-cols-3 sm:gap-4"
                                    >
                                        <div
                                            class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            Fakultas
                                        </div>

                                        <div
                                            class="break-words text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC] sm:col-span-2"
                                        >
                                            {{ facultyCode }}
                                            -
                                            {{ facultyName }}
                                        </div>
                                    </div>

                                    <!-- RUANGAN -->

                                    <div
                                        class="grid grid-cols-1 gap-1 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A] sm:grid-cols-3 sm:gap-4"
                                    >
                                        <div
                                            class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            Ruangan
                                        </div>

                                        <div
                                            class="sm:col-span-2"
                                        >
                                            <p
                                                class="break-words text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                            >
                                                {{ roomName }}
                                            </p>

                                            <p
                                                v-if="
                                                    roomLocation
                                                "
                                                class="mt-0.5 break-words text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                            >
                                                {{ roomLocation }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- SUBJEK -->

                                    <div
                                        v-if="
                                            procurement.subject
                                        "
                                        class="grid grid-cols-1 gap-1 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A] sm:grid-cols-3 sm:gap-4"
                                    >
                                        <div
                                            class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            Subjek
                                        </div>

                                        <div
                                            class="break-words text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC] sm:col-span-2"
                                        >
                                            {{ procurement.subject }}
                                        </div>
                                    </div>

                                    <!-- NAMA BARANG -->

                                    <div
                                        class="grid grid-cols-1 gap-1 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A] sm:grid-cols-3 sm:gap-4"
                                    >
                                        <div
                                            class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            Nama Barang
                                        </div>

                                        <div
                                            class="break-words text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC] sm:col-span-2"
                                        >
                                            {{ procurement.item_name }}
                                        </div>
                                    </div>

                                    <!-- JUMLAH -->

                                    <div
                                        class="grid grid-cols-1 gap-1 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A] sm:grid-cols-3 sm:gap-4"
                                    >
                                        <div
                                            class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            Jumlah
                                        </div>

                                        <div
                                            class="text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC] sm:col-span-2"
                                        >
                                            {{
                                                formatQuantity(
                                                    procurement.quantity,
                                                )
                                            }}
                                            unit
                                        </div>
                                    </div>

                                    <!-- JENIS -->

                                    <div
                                        class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3 sm:gap-4"
                                    >
                                        <div
                                            class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            Jenis Pengadaan
                                        </div>

                                        <div
                                            class="sm:col-span-2"
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
                            </section>

                            <!-- ALASAN -->

                            <section>
                                <h4
                                    class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Alasan Pengadaan
                                </h4>

                                <div
                                    class="rounded-xl border border-[#e3e3e0] bg-slate-50 px-4 py-3 dark:border-[#3E3E3A] dark:bg-white/5"
                                >
                                    <p
                                        class="whitespace-pre-line break-words text-xs leading-relaxed text-[#1b1b18] dark:text-[#EDEDEC]"
                                    >
                                        {{
                                            procurement.reason ||
                                            "Tidak ada alasan yang diberikan."
                                        }}
                                    </p>
                                </div>
                            </section>

                            <!-- INFORMASI PENGAJUAN -->

                            <section>
                                <h4
                                    class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Informasi Pengajuan
                                </h4>

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
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
                                            class="mt-1 break-words text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{ requesterName }}
                                        </p>

                                        <p
                                            v-if="
                                                procurement.requester
                                                    ?.email
                                            "
                                            class="mt-0.5 break-all text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            {{
                                                procurement.requester
                                                    .email
                                            }}
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
                                            class="mt-1 break-words text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{
                                                formatDate(
                                                    procurement.requested_at,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </section>

                            <!-- LAMPIRAN -->

                            <section
                                v-if="hasAttachments"
                            >
                                <h4
                                    class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Lampiran
                                </h4>

                                <div class="space-y-2">
                                    <a
                                        v-for="attachment in attachments"
                                        :key="
                                            attachment.path
                                        "
                                        :href="attachment.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center gap-3 rounded-xl border border-[#e3e3e0] bg-slate-50 px-4 py-3 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-black/10 dark:border-[#3E3E3A] dark:bg-white/5 dark:hover:bg-white/10 dark:focus:ring-white/10"
                                    >
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-[#706f6c] shadow-sm dark:bg-[#20201e] dark:text-[#A1A09A]"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.5"
                                                stroke="currentColor"
                                                class="h-4 w-4"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="m18.375 12.739-7.72 7.72a4.5 4.5 0 0 1-6.364-6.364l9.94-9.94a3 3 0 1 1 4.243 4.243l-9.94 9.94a1.5 1.5 0 0 1-2.121-2.121l8.88-8.88"
                                                />
                                            </svg>
                                        </div>

                                        <div
                                            class="min-w-0 flex-1"
                                        >
                                            <p
                                                class="truncate text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                            >
                                                {{
                                                    getAttachmentName(
                                                        attachment,
                                                    )
                                                }}
                                            </p>

                                            <p
                                                class="mt-0.5 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                            >
                                                Buka lampiran
                                            </p>
                                        </div>

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                            class="h-4 w-4 shrink-0 text-[#706f6c] dark:text-[#A1A09A]"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M13.5 6H18m0 0v4.5M18 6l-6 6"
                                            />

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M18 13.5V18a1.5 1.5 0 0 1-1.5 1.5h-10A1.5 1.5 0 0 1 5 18V8a1.5 1.5 0 0 1 1.5-1.5H11"
                                            />
                                        </svg>
                                    </a>
                                </div>
                            </section>

                            <!-- INFORMASI VERIFIKASI -->

                            <section
                                v-if="hasVerification"
                            >
                                <h4
                                    class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Informasi Verifikasi
                                </h4>

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
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
                                            class="mt-1 break-words text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{ processorName }}
                                        </p>

                                        <p
                                            v-if="
                                                procurement.processor
                                                    ?.email
                                            "
                                            class="mt-0.5 break-all text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            {{
                                                procurement.processor
                                                    .email
                                            }}
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
                                            class="mt-1 break-words text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{
                                                formatDate(
                                                    procurement.processed_at,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <!-- NOMOR DOKUMEN -->

                                    <div
                                        v-if="
                                            procurement.document_number
                                        "
                                        class="rounded-xl border border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A] sm:col-span-2"
                                    >
                                        <p
                                            class="text-[10px] font-medium uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            Nomor Dokumen
                                        </p>

                                        <p
                                            class="mt-1 break-all font-mono text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{
                                                procurement.document_number
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </section>

                            <!-- CATATAN VERIFIKASI -->

                            <section
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
                                        class="whitespace-pre-line break-words text-xs leading-relaxed text-[#1b1b18] dark:text-[#EDEDEC]"
                                    >
                                        {{
                                            procurement.admin_note
                                        }}
                                    </p>
                                </div>
                            </section>

                            <!-- TANDA TANGAN -->

                            <section
                                v-if="hasSignatures"
                            >
                                <h4
                                    class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Tanda Tangan
                                </h4>

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
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
                                            class="flex min-h-[120px] items-center justify-center overflow-hidden rounded-lg bg-slate-50 p-3 dark:bg-white/5"
                                        >
                                            <img
                                                v-if="
                                                    isImageUrl(
                                                        procurement.requester_signature,
                                                    )
                                                "
                                                :src="
                                                    procurement.requester_signature
                                                "
                                                alt="Tanda tangan pengaju"
                                                class="max-h-24 max-w-full object-contain"
                                            />

                                            <span
                                                v-else
                                                class="break-all text-center text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                            >
                                                Tanda tangan tersimpan
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
                                            class="flex min-h-[120px] items-center justify-center overflow-hidden rounded-lg bg-slate-50 p-3 dark:bg-white/5"
                                        >
                                            <img
                                                v-if="
                                                    isImageUrl(
                                                        procurement.approver_signature,
                                                    )
                                                "
                                                :src="
                                                    procurement.approver_signature
                                                "
                                                alt="Tanda tangan verifikator"
                                                class="max-h-24 max-w-full object-contain"
                                            />

                                            <span
                                                v-else
                                                class="break-all text-center text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                            >
                                                Tanda tangan tersimpan
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </section>

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

                        <!--
                        |--------------------------------------------------------------------------
                        | FOOTER
                        |--------------------------------------------------------------------------
                        -->

                        <div
                            class="sticky bottom-0 flex items-center justify-end border-t border-[#e3e3e0] bg-white px-5 py-4 dark:border-[#3E3E3A] dark:bg-[#161615] sm:px-6"
                        >
                            <button
                                type="button"
                                class="rounded-lg border border-[#e3e3e0] bg-white px-4 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-black/10 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e] dark:focus:ring-white/10"
                                @click="handleClose"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
