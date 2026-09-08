<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";

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
    url?: string;
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
    status: "pending" | "approved" | "rejected" | "completed";
    document_number?: string | null;
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

const props = withDefaults(
    defineProps<{
        show: boolean;
        procurement?: Procurement | null;
        processing?: boolean;
    }>(),
    {
        procurement: null,
        processing: false,
    },
);

const emit = defineEmits<{
    submit: [data: ApprovalFormData];
    close: [];
}>();

const emptyForm = (): ApprovalFormData => ({
    action: "approve",
    approver_signature: null,
    admin_note: null,
});

const form = ref<ApprovalFormData>(emptyForm());
const errorMessage = ref("");

const isPending = computed(() => {
    return props.procurement?.status === "pending";
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

const requesterSignatureUrl = computed(() => {
    return props.procurement?.requester_signature ?? null;
});

const approverSignatureUrl = computed(() => {
    return props.procurement?.approver_signature ?? null;
});

const hasSignatures = computed(() => {
    return Boolean(
        props.procurement?.requester_signature ||
        props.procurement?.approver_signature,
    );
});

const isImageUrl = (value?: string | null): boolean => {
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

const formatDate = (value?: string | null): string => {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

const signatureCanvas = ref<HTMLCanvasElement | null>(null);
const isDrawing = ref(false);
const hasSignature = ref(false);

let canvasContext: CanvasRenderingContext2D | null = null;

const CANVAS_WIDTH = 800;
const CANVAS_HEIGHT = 250;

const resetCanvasState = () => {
    isDrawing.value = false;
    hasSignature.value = false;
    canvasContext = null;
};

const initializeCanvas = () => {
    const canvas = signatureCanvas.value;

    if (!canvas) {
        return;
    }

    canvas.width = CANVAS_WIDTH;
    canvas.height = CANVAS_HEIGHT;

    const context = canvas.getContext("2d");

    if (!context) {
        resetCanvasState();
        return;
    }

    canvasContext = context;

    context.clearRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);

    context.lineWidth = 2.5;
    context.lineCap = "round";
    context.lineJoin = "round";
    context.strokeStyle = "#1b1b18";

    isDrawing.value = false;
    hasSignature.value = false;

    const existingSignature = approverSignatureUrl.value;

    if (!existingSignature) {
        form.value.approver_signature = null;
        return;
    }

    const image = new Image();

    image.onload = () => {
        if (!canvasContext) {
            return;
        }

        canvasContext.clearRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);

        canvasContext.drawImage(image, 0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);

        hasSignature.value = true;

        form.value.approver_signature = existingSignature;
    };

    image.onerror = () => {
        hasSignature.value = false;
        form.value.approver_signature = null;
    };

    image.src = existingSignature;
};

const getPointerPosition = (event: PointerEvent) => {
    const canvas = signatureCanvas.value;

    if (!canvas) {
        return null;
    }

    const rect = canvas.getBoundingClientRect();

    if (rect.width <= 0 || rect.height <= 0) {
        return null;
    }

    return {
        x: (event.clientX - rect.left) * (canvas.width / rect.width),
        y: (event.clientY - rect.top) * (canvas.height / rect.height),
    };
};

const startDrawing = (event: PointerEvent) => {
    if (props.processing || !isPending.value) {
        return;
    }

    const position = getPointerPosition(event);

    if (!position || !canvasContext) {
        return;
    }

    event.preventDefault();

    const canvas = signatureCanvas.value;

    if (canvas) {
        canvas.setPointerCapture(event.pointerId);
    }

    isDrawing.value = true;

    canvasContext.beginPath();
    canvasContext.moveTo(position.x, position.y);

    hasSignature.value = true;
};

const drawSignature = (event: PointerEvent) => {
    if (
        !isDrawing.value ||
        !canvasContext ||
        props.processing ||
        !isPending.value
    ) {
        return;
    }

    const position = getPointerPosition(event);

    if (!position) {
        return;
    }

    event.preventDefault();

    canvasContext.lineTo(position.x, position.y);

    canvasContext.stroke();
};

const stopDrawing = (event?: PointerEvent) => {
    if (!isDrawing.value) {
        return;
    }

    isDrawing.value = false;

    const canvas = signatureCanvas.value;

    if (canvas && event && canvas.hasPointerCapture(event.pointerId)) {
        canvas.releasePointerCapture(event.pointerId);
    }

    saveSignature();
};

const saveSignature = () => {
    const canvas = signatureCanvas.value;

    if (!canvas || !hasSignature.value) {
        form.value.approver_signature = null;

        return;
    }

    form.value.approver_signature = canvas.toDataURL("image/png");
};

const clearSignature = () => {
    const canvas = signatureCanvas.value;

    if (!canvas || !canvasContext) {
        return;
    }

    canvasContext.clearRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);

    hasSignature.value = false;
    isDrawing.value = false;
    form.value.approver_signature = null;
};

const syncForm = (
    procurement:
        | Procurement
        | null
        | undefined,
) => {
    form.value = {
        action: "approve",
        approver_signature:
            procurement?.approver_signature ??
            null,
        admin_note:
            procurement?.admin_note ??
            null,
    };

    errorMessage.value = "";
};

watch(
    () => props.procurement,
    (newVal) => {
        syncForm(newVal);

        if (!newVal) {
            resetCanvasState();
        }
    },
    {
        immediate: true,
    },
);

watch(
    () => props.show,
    async (isOpen) => {
        if (!isOpen) {
            isDrawing.value = false;
            return;
        }

        errorMessage.value = "";

        syncForm(props.procurement);

        await nextTick();

        if (isPending.value && props.procurement) {
            initializeCanvas();
        }
    },
);

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === "Escape" && props.show && !props.processing) {
        handleClose();
    }
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            window.addEventListener("keydown", handleKeydown);
        } else {
            window.removeEventListener("keydown", handleKeydown);
        }
    },
);

const setAction = (action: ApprovalAction) => {
    if (props.processing || !isPending.value) {
        return;
    }

    errorMessage.value = "";
    form.value.action = action;
};

const handleSubmit = () => {
    if (props.processing) {
        return;
    }

    errorMessage.value = "";

    if (!props.procurement) {
        errorMessage.value = "Data pengadaan tidak ditemukan.";

        return;
    }

    if (!isPending.value) {
        errorMessage.value =
            "Pengadaan ini sudah diproses dan tidak dapat diverifikasi kembali.";

        return;
    }

    if (hasSignature.value) {
        saveSignature();
    }

    const approverSignature = form.value.approver_signature?.trim() || null;

    const adminNote = form.value.admin_note?.trim() || null;

    if (form.value.action === "reject" && !adminNote) {
        errorMessage.value = "Catatan wajib diisi ketika pengadaan ditolak.";

        return;
    }

    if (form.value.action === "reject" && adminNote.length < 5) {
        errorMessage.value = "Catatan penolakan minimal 5 karakter.";

        return;
    }

    if (adminNote && adminNote.length > 5000) {
        errorMessage.value = "Catatan verifikasi maksimal 5000 karakter.";

        return;
    }

    emit("submit", {
        action: form.value.action,
        approver_signature: approverSignature,
        admin_note: adminNote,
    });
};

const handleClose = () => {
    if (props.processing) {
        return;
    }

    errorMessage.value = "";
    isDrawing.value = false;

    emit("close");
};

onBeforeUnmount(() => {
    window.removeEventListener("keydown", handleKeydown);

    canvasContext = null;
    isDrawing.value = false;
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
                aria-labelledby="procurement-approval-title"
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
                        class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-black/5 bg-white shadow-xl dark:border-white/10 dark:bg-[#161615]"
                    >
                        <div
                            class="sticky top-0 z-10 flex items-center justify-between border-b border-[#e3e3e0] bg-white px-6 py-4 dark:border-[#3E3E3A] dark:bg-[#161615]"
                        >
                            <div>
                                <h3
                                    id="procurement-approval-title"
                                    class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Verifikasi Pengadaan
                                </h3>

                                <p
                                    class="mt-1 text-[11px] text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Periksa pengajuan sebelum menyetujui atau
                                    menolaknya.
                                </p>
                            </div>

                            <button
                                type="button"
                                :disabled="processing"
                                class="rounded-lg p-1 text-[#706f6c] transition hover:bg-slate-100 hover:text-[#1b1b18] disabled:cursor-not-allowed disabled:opacity-50 dark:text-[#A1A09A] dark:hover:bg-[#20201e] dark:hover:text-[#EDEDEC]"
                                aria-label="Tutup modal"
                                @click="handleClose"
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

                        <div class="space-y-5 px-6 py-5">
                            <div
                                v-if="errorMessage"
                                class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-400"
                                role="alert"
                            >
                                {{ errorMessage }}
                            </div>

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

                                <p class="mt-1 text-sm font-semibold">
                                    {{ statusLabel }}
                                </p>

                                <p
                                    class="mt-1 text-[11px] leading-relaxed opacity-80"
                                >
                                    Pengadaan ini sudah diproses sehingga tidak
                                    dapat diverifikasi kembali.
                                </p>
                            </div>

                            <section>
                                <h4
                                    class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Ringkasan Pengadaan
                                </h4>

                                <div
                                    class="overflow-hidden rounded-xl border border-[#e3e3e0] dark:border-[#3E3E3A]"
                                >
                                    <div
                                        class="grid grid-cols-3 gap-4 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                                    >
                                        <span
                                            class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            Barang
                                        </span>

                                        <span
                                            class="col-span-2 break-words text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{ procurement.item_name }}
                                        </span>
                                    </div>

                                    <div
                                        v-if="procurement.subject"
                                        class="grid grid-cols-3 gap-4 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                                    >
                                        <span
                                            class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            Subjek
                                        </span>

                                        <span
                                            class="col-span-2 break-words text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{ procurement.subject }}
                                        </span>
                                    </div>

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
                                                ).toLocaleString("id-ID")
                                            }}
                                            unit
                                        </span>
                                    </div>

                                    <div
                                        class="grid grid-cols-3 gap-4 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                                    >
                                        <span
                                            class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            Jenis
                                        </span>

                                        <div class="col-span-2">
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
                                                procurement.faculty?.code ?? "—"
                                            }}
                                            -
                                            {{
                                                procurement.faculty?.name ?? "—"
                                            }}
                                        </span>
                                    </div>

                                    <div
                                        class="grid grid-cols-3 gap-4 border-b border-[#e3e3e0] px-4 py-3 dark:border-[#3E3E3A]"
                                    >
                                        <span
                                            class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            Ruangan
                                        </span>

                                        <div class="col-span-2">
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
                                                    procurement.room?.floor
                                                "
                                                class="mt-0.5 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                            >
                                                {{
                                                    [
                                                        procurement.room
                                                            ?.building,
                                                        procurement.room?.floor,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(" - ")
                                                }}
                                            </p>
                                        </div>
                                    </div>

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
                                                procurement.requester?.name ??
                                                "—"
                                            }}
                                        </span>
                                    </div>

                                    <div
                                        class="grid grid-cols-3 gap-4 px-4 py-3"
                                    >
                                        <span
                                            class="text-[11px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            Tanggal Pengajuan
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
                            </section>

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
                                        class="whitespace-pre-line text-xs leading-relaxed text-[#1b1b18] dark:text-[#EDEDEC]"
                                    >
                                        {{
                                            procurement.reason ||
                                            "Tidak ada alasan."
                                        }}
                                    </p>
                                </div>
                            </section>

                            <section v-if="hasSignatures">
                                <h4
                                    class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Tanda Tangan
                                </h4>

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <div
                                        v-if="procurement.requester_signature"
                                        class="rounded-xl border border-[#e3e3e0] px-4 py-4 dark:border-[#3E3E3A]"
                                    >
                                        <p
                                            class="mb-3 text-center text-[10px] font-medium uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            Pemohon
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
                                                alt="Tanda tangan pemohon"
                                                class="max-h-24 max-w-full object-contain"
                                            />

                                            <span
                                                v-else
                                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                            >
                                                Tanda tangan tersimpan
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        v-if="procurement.approver_signature"
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
                                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                            >
                                                Tanda tangan tersimpan
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section v-if="isPending">
                                <h4
                                    class="mb-3 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Keputusan Verifikasi
                                </h4>

                                <div
                                    class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                                >
                                    <button
                                        type="button"
                                        :disabled="processing"
                                        class="rounded-xl border px-4 py-3 text-left transition disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="
                                            isApprove
                                                ? 'border-green-300 bg-green-50 text-green-700 ring-1 ring-green-300 dark:border-green-800 dark:bg-green-950/20 dark:text-green-400 dark:ring-green-800'
                                                : 'border-[#e3e3e0] bg-white text-[#1b1b18] hover:bg-slate-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]'
                                        "
                                        @click="setAction('approve')"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
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
                                                    Verifikasi disetujui
                                                </p>
                                            </div>
                                        </div>
                                    </button>

                                    <button
                                        type="button"
                                        :disabled="processing"
                                        class="rounded-xl border px-4 py-3 text-left transition disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="
                                            isReject
                                                ? 'border-red-300 bg-red-50 text-red-700 ring-1 ring-red-300 dark:border-red-800 dark:bg-red-950/20 dark:text-red-400 dark:ring-red-800'
                                                : 'border-[#e3e3e0] bg-white text-[#1b1b18] hover:bg-slate-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]'
                                        "
                                        @click="setAction('reject')"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
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
                                                    Pengajuan ditolak
                                                </p>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </section>

                            <section v-if="isPending">
                                <label
                                    for="procurement-admin-note"
                                    class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Catatan Verifikasi

                                    <span v-if="isReject" class="text-red-500">
                                        *
                                    </span>

                                    <span v-else class="text-[#a1a09a]">
                                        (Opsional)
                                    </span>
                                </label>

                                <textarea
                                    id="procurement-admin-note"
                                    v-model="form.admin_note"
                                    rows="4"
                                    :disabled="processing"
                                    :maxlength="5000"
                                    :placeholder="
                                        isReject
                                            ? 'Jelaskan alasan penolakan pengadaan...'
                                            : 'Tambahkan catatan verifikasi jika diperlukan...'
                                    "
                                    class="w-full resize-none rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                                ></textarea>

                                <div
                                    class="mt-1 flex items-center justify-between"
                                >
                                    <p
                                        v-if="isReject"
                                        class="text-[10px] text-red-500"
                                    >
                                        Catatan wajib diisi untuk menjelaskan
                                        alasan penolakan.
                                    </p>

                                    <span
                                        class="ml-auto text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        {{
                                            (form.admin_note ?? "").length
                                        }}/5000
                                    </span>
                                </div>
                            </section>

                            <section v-if="isPending">
                                <div
                                    class="mb-2 flex items-center justify-between"
                                >
                                    <label
                                        for="procurement-approver-signature"
                                        class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                    >
                                        Tanda Tangan Verifikator

                                        <span class="text-[#a1a09a]">
                                            (Opsional)
                                        </span>
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
                                    id="procurement-approver-signature"
                                    class="overflow-hidden rounded-xl border border-dashed border-black/15 bg-white dark:border-white/15 dark:bg-[#0f0f0e]"
                                >
                                    <div class="relative w-full">
                                        <canvas
                                            ref="signatureCanvas"
                                            width="800"
                                            height="250"
                                            class="block h-[180px] w-full touch-none cursor-crosshair bg-white dark:bg-[#0f0f0e]"
                                            @pointerdown="startDrawing"
                                            @pointermove="drawSignature"
                                            @pointerup="stopDrawing"
                                            @pointercancel="stopDrawing"
                                            @pointerleave="stopDrawing"
                                        ></canvas>

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
                                        ></div>
                                    </div>

                                    <div
                                        class="flex flex-col gap-2 border-t border-black/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-white/10"
                                    >
                                        <p
                                            class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            Gunakan mouse atau layar sentuh
                                            untuk membuat tanda tangan.
                                        </p>

                                        <button
                                            type="button"
                                            :disabled="
                                                processing || !hasSignature
                                            "
                                            class="rounded-lg px-3 py-2 text-xs font-medium text-[#f53003] transition hover:bg-[#f53003]/10 disabled:cursor-not-allowed disabled:opacity-40"
                                            @click="clearSignature"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </div>

                                <p
                                    class="mt-1 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Tanda tangan verifikator akan dikirim
                                    sebagai gambar PNG saat verifikasi.
                                </p>
                            </section>

                            <div
                                v-if="procurement.status === 'approved'"
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
                                    Status ini berarti pengajuan sudah
                                    diverifikasi oleh Super Admin dan dapat
                                    dilanjutkan ke proses pencetakan laporan
                                    untuk Pimpinan Yayasan.
                                </p>

                                <p
                                    v-if="procurement.document_number"
                                    class="mt-2 text-[11px] font-semibold text-green-700 dark:text-green-400"
                                >
                                    Nomor Dokumen:
                                    {{ procurement.document_number }}
                                </p>
                            </div>

                            <div
                                v-if="procurement.status === 'rejected'"
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

                            <div
                                v-if="procurement.status === 'completed'"
                                class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-900/50 dark:bg-blue-950/20"
                            >
                                <p
                                    class="text-xs font-semibold text-blue-700 dark:text-blue-400"
                                >
                                    Pengadaan telah selesai.
                                </p>

                                <p
                                    class="mt-1 text-[11px] leading-relaxed text-blue-700/80 dark:text-blue-400/80"
                                >
                                    Pengadaan ini telah melewati proses
                                    verifikasi dan ditandai sebagai selesai.
                                </p>

                                <p
                                    v-if="procurement.document_number"
                                    class="mt-2 text-[11px] font-semibold text-blue-700 dark:text-blue-400"
                                >
                                    Nomor Dokumen:
                                    {{ procurement.document_number }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="sticky bottom-0 flex items-center justify-end gap-2 border-t border-[#e3e3e0] bg-white px-6 py-4 dark:border-[#3E3E3A] dark:bg-[#161615]"
                        >
                            <button
                                type="button"
                                :disabled="processing"
                                class="rounded-lg border border-[#e3e3e0] bg-white px-4 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                                @click="handleClose"
                            >
                                Tutup
                            </button>

                            <button
                                v-if="isPending"
                                type="button"
                                :disabled="processing"
                                class="rounded-lg px-4 py-2 text-xs font-medium text-white transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="
                                    isApprove
                                        ? 'bg-[#16a34a] hover:bg-[#15803d] focus:ring-[#16a34a] dark:bg-[#22c55e] dark:hover:bg-[#16a34a] dark:focus:ring-[#22c55e] dark:focus:ring-offset-[#161615]'
                                        : 'bg-[#dc2626] hover:bg-[#b91c1c] focus:ring-[#dc2626] dark:bg-[#ef4444] dark:hover:bg-[#dc2626] dark:focus:ring-[#ef4444] dark:focus:ring-offset-[#161615]'
                                "
                                @click="handleSubmit"
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
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
