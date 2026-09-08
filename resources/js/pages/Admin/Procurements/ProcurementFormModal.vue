<script setup lang="ts">
import { ref, watch, nextTick, onBeforeUnmount, computed } from "vue";

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

interface ExistingAttachment {
    path: string;
    url: string;
    name: string;
}

interface Procurement {
    id?: number;

    faculty_id: number | string;
    requested_by?: number | string;
    room_id?: number | string | null;

    item_name: string;
    quantity: number | string;
    type: "replacement" | "new_item";
    reason: string;

    subject?: string | null;
    attachments?: ExistingAttachment[] | null;
    requester_signature?: string | null;

    requested_at?: string | null;

    status?: "pending" | "approved" | "rejected" | "completed";
    document_number?: string | null;
    processed_by?: number | string | null;
    approver_signature?: string | null;
    processed_at?: string | null;
    admin_note?: string | null;
}

interface ProcurementFormData {
    faculty_id: number;
    room_id: number | null;
    item_name: string;
    quantity: number;
    type: "replacement" | "new_item";
    reason: string;
    subject: string | null;
    requester_signature: string | null;
    attachments: File[];
    remove_attachments: string[];
}

const props = defineProps<{
    show: boolean;
    procurement?: Procurement | null;
    faculties: Faculty[];
    rooms: Room[];
    processing?: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "submit", data: ProcurementFormData): void;
}>();

const MAX_ATTACHMENTS = 5;
const MAX_ATTACHMENT_SIZE = 5 * 1024 * 1024;

const ALLOWED_ATTACHMENT_TYPES = [
    "image/jpeg",
    "image/png",
    "application/pdf",
];

const ALLOWED_ATTACHMENT_EXTENSIONS = [
    ".jpg",
    ".jpeg",
    ".png",
    ".pdf",
];

// Unique-ish id suffix so multiple instances of this modal never collide
// on label "for" / input "id" pairs.
const uid = `proc-${Math.random().toString(36).slice(2, 9)}`;
const fieldId = (name: string) => `${uid}-${name}`;
const dialogTitleId = fieldId("title");

const emptyForm = (): ProcurementFormData => ({
    faculty_id: 0,
    room_id: null,
    item_name: "",
    quantity: 1,
    type: "replacement",
    reason: "",
    subject: "",
    requester_signature: null,
    attachments: [],
    remove_attachments: [],
});

const form = ref<ProcurementFormData>(emptyForm());

const errorMessage = ref("");

const existingAttachments = ref<ExistingAttachment[]>([]);

const attachmentInput = ref<HTMLInputElement | null>(null);

const modalRoot = ref<HTMLElement | null>(null);

/*
|--------------------------------------------------------------------------
| SIGNATURE CANVAS
|--------------------------------------------------------------------------
*/

const signatureCanvas = ref<HTMLCanvasElement | null>(null);

const isDrawing = ref(false);
const hasSignature = ref(false);

let canvasContext: CanvasRenderingContext2D | null = null;

// Bumped every time the canvas is (re)initialized so an in-flight
// image.onload from a previous init can be safely ignored (avoids
// drawing a stale/foreign signature onto a fresh canvas after rapid
// close/reopen or switching between records).
let canvasInitToken = 0;

const CANVAS_WIDTH = 800;
const CANVAS_HEIGHT = 250;

const initializeCanvas = () => {
    const canvas = signatureCanvas.value;

    if (!canvas) {
        return;
    }

    const initToken = ++canvasInitToken;

    canvas.width = CANVAS_WIDTH;
    canvas.height = CANVAS_HEIGHT;

    const context = canvas.getContext("2d");

    if (!context) {
        canvasContext = null;
        return;
    }

    canvasContext = context;

    canvasContext.clearRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);

    canvasContext.lineWidth = 2.5;
    canvasContext.lineCap = "round";
    canvasContext.lineJoin = "round";
    canvasContext.strokeStyle = "#1b1b18";

    hasSignature.value = false;
    isDrawing.value = false;

    if (!form.value.requester_signature) {
        return;
    }

    const image = new Image();

    image.onload = () => {
        // Bail out if the canvas was re-initialized (or unmounted)
        // while this image was loading.
        if (initToken !== canvasInitToken || !canvasContext) {
            return;
        }

        canvasContext.drawImage(image, 0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);

        hasSignature.value = true;
    };

    image.onerror = () => {
        // Corrupt/unreachable signature data: fail quietly, leave
        // the canvas blank rather than leaving the UI in a broken state.
        if (initToken !== canvasInitToken) {
            return;
        }

        hasSignature.value = false;
    };

    image.src = form.value.requester_signature;
};

const getPointerPosition = (event: PointerEvent) => {
    const canvas = signatureCanvas.value;

    if (!canvas) {
        return null;
    }

    const rect = canvas.getBoundingClientRect();

    if (rect.width === 0 || rect.height === 0) {
        return null;
    }

    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;

    return {
        x: (event.clientX - rect.left) * scaleX,
        y: (event.clientY - rect.top) * scaleY,
    };
};

const startDrawing = (event: PointerEvent) => {
    if (props.processing) {
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
};

const drawSignature = (event: PointerEvent) => {
    if (!isDrawing.value || !canvasContext || props.processing) {
        return;
    }

    const position = getPointerPosition(event);

    if (!position) {
        return;
    }

    event.preventDefault();

    canvasContext.lineTo(position.x, position.y);
    canvasContext.stroke();

    hasSignature.value = true;
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
        form.value.requester_signature = null;
        return;
    }

    form.value.requester_signature = canvas.toDataURL("image/png");
};

const clearSignature = () => {
    const canvas = signatureCanvas.value;

    if (!canvas || !canvasContext) {
        return;
    }

    canvasContext.clearRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);

    hasSignature.value = false;
    isDrawing.value = false;
    form.value.requester_signature = null;
};

/*
|--------------------------------------------------------------------------
| ATTACHMENTS
|--------------------------------------------------------------------------
*/

const totalAttachmentCount = () => {
    return existingAttachments.value.length + form.value.attachments.length;
};

const isAllowedAttachment = (file: File) => {
    const fileName = file.name.toLowerCase();

    const hasAllowedExtension = ALLOWED_ATTACHMENT_EXTENSIONS.some(
        (extension) => fileName.endsWith(extension),
    );

    // Some browsers/OSes report an empty or generic MIME type for
    // certain files (e.g. some PDF sources on mobile browsers).
    // Extension is treated as the primary signal; MIME type is only
    // used as an additional check when the browser actually provides one.
    const hasAllowedMimeType =
        file.type === "" || ALLOWED_ATTACHMENT_TYPES.includes(file.type);

    return hasAllowedExtension && hasAllowedMimeType;
};

const handleAttachmentsChange = (event: Event) => {
    const target = event.target as HTMLInputElement;

    const files = Array.from(target.files ?? []);

    if (attachmentInput.value) {
        attachmentInput.value.value = "";
    }

    if (files.length === 0) {
        return;
    }

    errorMessage.value = "";

    for (const file of files) {
        if (totalAttachmentCount() >= MAX_ATTACHMENTS) {
            errorMessage.value = `Maksimal ${MAX_ATTACHMENTS} lampiran per pengajuan.`;

            break;
        }

        if (!isAllowedAttachment(file)) {
            errorMessage.value = `File "${file.name}" harus berformat JPG, PNG, atau PDF.`;

            continue;
        }

        if (file.size > MAX_ATTACHMENT_SIZE) {
            errorMessage.value = `File "${file.name}" melebihi batas maksimal 5MB.`;

            continue;
        }

        const alreadySelected = form.value.attachments.some(
            (existingFile) =>
                existingFile.name === file.name &&
                existingFile.size === file.size &&
                existingFile.lastModified === file.lastModified,
        );

        if (alreadySelected) {
            errorMessage.value = `File "${file.name}" sudah dipilih.`;

            continue;
        }

        form.value.attachments.push(file);
    }
};

const removeNewAttachment = (index: number) => {
    form.value.attachments.splice(index, 1);
};

const removeExistingAttachment = (attachment: ExistingAttachment) => {
    const alreadyMarked = form.value.remove_attachments.includes(
        attachment.path,
    );

    if (!alreadyMarked) {
        form.value.remove_attachments.push(attachment.path);
    }

    existingAttachments.value = existingAttachments.value.filter(
        (item) => item.path !== attachment.path,
    );
};

/*
|--------------------------------------------------------------------------
| SYNC FORM
|--------------------------------------------------------------------------
*/

const syncForm = (procurement: Procurement | null | undefined) => {
    form.value = procurement
        ? {
              faculty_id: Number(procurement.faculty_id) || 0,

              room_id:
                  procurement.room_id !== null &&
                  procurement.room_id !== undefined &&
                  procurement.room_id !== ""
                      ? Number(procurement.room_id)
                      : null,

              item_name: procurement.item_name ?? "",

              quantity:
                  Number(procurement.quantity) > 0
                      ? Number(procurement.quantity)
                      : 1,

              type:
                  procurement.type === "new_item"
                      ? "new_item"
                      : "replacement",

              reason: procurement.reason ?? "",

              subject: procurement.subject ?? "",

              requester_signature:
                  procurement.requester_signature ?? null,

              attachments: [],

              remove_attachments: [],
          }
        : emptyForm();

    existingAttachments.value = procurement?.attachments
        ? [...procurement.attachments]
        : [];
};

watch(
    () => props.procurement,
    (newVal) => {
        errorMessage.value = "";
        syncForm(newVal);
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

        initializeCanvas();
    },
);

watch(
    () => form.value.faculty_id,
    (newFacultyId, oldFacultyId) => {
        if (oldFacultyId !== undefined && newFacultyId !== oldFacultyId) {
            const selectedRoom = props.rooms.find(
                (room) => Number(room.id) === Number(form.value.room_id),
            );

            if (
                selectedRoom &&
                Number(selectedRoom.faculty_id) !== Number(newFacultyId)
            ) {
                form.value.room_id = null;
            }
        }
    },
);

const availableRooms = computed(() => {
    const facultyId = Number(form.value.faculty_id);

    if (!facultyId) {
        return [];
    }

    return props.rooms.filter(
        (room) => Number(room.faculty_id) === facultyId,
    );
});

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

    if (hasSignature.value) {
        saveSignature();
    }

    const facultyId = Number(form.value.faculty_id);

    const roomId =
        form.value.room_id !== null && form.value.room_id !== undefined
            ? Number(form.value.room_id)
            : null;

    const itemName = String(form.value.item_name ?? "").trim();

    const quantity = Number(form.value.quantity);

    const type = form.value.type;

    const reason = String(form.value.reason ?? "").trim();

    const subject = String(form.value.subject ?? "").trim() || null;

    const requesterSignature =
        String(form.value.requester_signature ?? "").trim() || null;

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (!facultyId) {
        errorMessage.value = "Silakan pilih Fakultas terlebih dahulu.";

        return;
    }

    if (roomId === null || !Number.isInteger(roomId) || roomId < 1) {
        errorMessage.value = "Silakan pilih Ruangan terlebih dahulu.";

        return;
    }

    const selectedRoom = props.rooms.find(
        (room) => Number(room.id) === Number(roomId),
    );

    if (!selectedRoom) {
        errorMessage.value = "Ruangan yang dipilih tidak ditemukan.";

        return;
    }

    if (Number(selectedRoom.faculty_id) !== facultyId) {
        errorMessage.value =
            "Ruangan tidak sesuai dengan Fakultas yang dipilih.";

        return;
    }

    if (!itemName) {
        errorMessage.value = "Nama Barang wajib diisi.";

        return;
    }

    if (itemName.length > 255) {
        errorMessage.value = "Nama Barang maksimal 255 karakter.";

        return;
    }

    if (!Number.isFinite(quantity) || !Number.isInteger(quantity) || quantity < 1) {
        errorMessage.value =
            "Jumlah pengadaan harus berupa angka bulat minimal 1.";

        return;
    }

    if (type !== "replacement" && type !== "new_item") {
        errorMessage.value = "Silakan pilih jenis pengadaan.";

        return;
    }

    if (!reason) {
        errorMessage.value = "Alasan Pengadaan wajib diisi.";

        return;
    }

    if (totalAttachmentCount() > MAX_ATTACHMENTS) {
        errorMessage.value = `Maksimal ${MAX_ATTACHMENTS} lampiran per pengajuan.`;

        return;
    }

    for (const file of form.value.attachments) {
        if (!isAllowedAttachment(file)) {
            errorMessage.value = `File "${file.name}" harus berformat JPG, PNG, atau PDF.`;

            return;
        }

        if (file.size > MAX_ATTACHMENT_SIZE) {
            errorMessage.value = `File "${file.name}" melebihi batas maksimal 5MB.`;

            return;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PAYLOAD
    |--------------------------------------------------------------------------
    */

    const payload: ProcurementFormData = {
        faculty_id: facultyId,
        room_id: roomId,
        item_name: itemName,
        quantity,
        type,
        reason,
        subject,
        requester_signature: requesterSignature,
        attachments: form.value.attachments,
        remove_attachments: form.value.remove_attachments,
    };

    emit("submit", payload);
};

const handleClose = () => {
    if (props.processing) {
        return;
    }

    errorMessage.value = "";

    isDrawing.value = false;

    emit("close");
};

/*
|--------------------------------------------------------------------------
| ACCESSIBILITY / UX: ESC TO CLOSE + BODY SCROLL LOCK
|--------------------------------------------------------------------------
*/

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === "Escape" && props.show) {
        handleClose();
    }
};

let previousBodyOverflow = "";

watch(
    () => props.show,
    (isOpen) => {
        if (typeof document === "undefined") {
            return;
        }

        if (isOpen) {
            previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = "hidden";
        } else {
            document.body.style.overflow = previousBodyOverflow;
        }
    },
    { immediate: true },
);

if (typeof window !== "undefined") {
    window.addEventListener("keydown", handleKeydown);
}

onBeforeUnmount(() => {
    canvasContext = null;
    isDrawing.value = false;

    if (typeof window !== "undefined") {
        window.removeEventListener("keydown", handleKeydown);
    }

    if (typeof document !== "undefined" && props.show) {
        document.body.style.overflow = previousBodyOverflow;
    }
});
</script>

<template>
    <div
        v-if="show"
        ref="modalRoot"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="dialogTitleId"
        @click.self="handleClose"
    >
        <div
            class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-black/5 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-[#161615]"
        >
            <!-- HEADER -->
            <div
                class="flex items-center justify-between border-b border-[#e3e3e0] pb-4 dark:border-[#3E3E3A]"
            >
                <h3
                    :id="dialogTitleId"
                    class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                >
                    {{ procurement ? "Edit Pengadaan" : "Ajukan Pengadaan Baru" }}
                </h3>

                <button
                    type="button"
                    @click="handleClose"
                    :disabled="processing"
                    aria-label="Tutup"
                    class="rounded-lg p-1 text-[#706f6c] transition hover:bg-slate-100 hover:text-[#1b1b18] disabled:cursor-not-allowed disabled:opacity-50 dark:text-[#A1A09A] dark:hover:bg-[#20201e] dark:hover:text-[#EDEDEC]"
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

            <form class="mt-4 space-y-4" @submit.prevent="handleSubmit" novalidate>
                <!-- ERROR -->
                <div
                    v-if="errorMessage"
                    role="alert"
                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-400"
                >
                    {{ errorMessage }}
                </div>

                <!-- FAKULTAS -->
                <div>
                    <label
                        :for="fieldId('faculty')"
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Pilih Fakultas
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        :id="fieldId('faculty')"
                        v-model="form.faculty_id"
                        required
                        :disabled="faculties.length === 0 || processing"
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433] dark:disabled:bg-white/5 dark:disabled:text-[#5c5c58]"
                    >
                        <option value="0">
                            {{
                                faculties.length === 0
                                    ? "Belum ada data Fakultas"
                                    : "-- Pilih Fakultas --"
                            }}
                        </option>

                        <option
                            v-for="faculty in faculties"
                            :key="faculty.id"
                            :value="faculty.id"
                        >
                            {{ faculty.code }} - {{ faculty.name }}
                        </option>
                    </select>

                    <p
                        v-if="faculties.length === 0"
                        class="mt-1 text-[11px] font-medium text-amber-600 dark:text-amber-500"
                    >
                        Silakan input data Fakultas terlebih dahulu.
                    </p>
                </div>

                <!-- RUANGAN -->
                <div>
                    <label
                        :for="fieldId('room')"
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Ruangan
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        :id="fieldId('room')"
                        v-model="form.room_id"
                        required
                        :disabled="
                            !form.faculty_id ||
                            availableRooms.length === 0 ||
                            processing
                        "
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433] dark:disabled:bg-white/5 dark:disabled:text-[#5c5c58]"
                    >
                        <option :value="null">
                            {{
                                !form.faculty_id
                                    ? "-- Pilih Fakultas Dahulu --"
                                    : availableRooms.length === 0
                                      ? "Belum ada Ruangan"
                                      : "-- Pilih Ruangan --"
                            }}
                        </option>

                        <option
                            v-for="room in availableRooms"
                            :key="room.id"
                            :value="room.id"
                        >
                            {{ room.code }} - {{ room.name }}
                        </option>
                    </select>

                    <p
                        v-if="form.faculty_id && availableRooms.length === 0"
                        class="mt-1 text-[11px] font-medium text-amber-600 dark:text-amber-500"
                    >
                        Belum ada ruangan untuk fakultas yang dipilih.
                    </p>
                </div>

                <!-- NAMA + JUMLAH -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- NAMA BARANG -->
                    <div>
                        <label
                            :for="fieldId('item_name')"
                            class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Nama Barang
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            :id="fieldId('item_name')"
                            v-model="form.item_name"
                            type="text"
                            required
                            maxlength="255"
                            placeholder="Contoh: Laptop"
                            :disabled="processing"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                    </div>

                    <!-- JUMLAH -->
                    <div>
                        <label
                            :for="fieldId('quantity')"
                            class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Jumlah
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            :id="fieldId('quantity')"
                            v-model.number="form.quantity"
                            type="number"
                            required
                            min="1"
                            step="1"
                            inputmode="numeric"
                            placeholder="Contoh: 5"
                            :disabled="processing"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                    </div>
                </div>

                <!-- JENIS PENGADAAN -->
                <div>
                    <label
                        :for="fieldId('type')"
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Jenis Pengadaan
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        :id="fieldId('type')"
                        v-model="form.type"
                        required
                        :disabled="processing"
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433] dark:disabled:bg-white/5 dark:disabled:text-[#5c5c58]"
                    >
                        <option value="replacement">
                            Penggantian Barang Rusak / Tidak Layak
                        </option>

                        <option value="new_item">Pengadaan Barang Baru</option>
                    </select>
                </div>

                <!-- INFO JENIS -->
                <div
                    v-if="form.type === 'replacement'"
                    class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-900/50 dark:bg-amber-950/20"
                >
                    <p class="text-[11px] leading-relaxed text-amber-700 dark:text-amber-400">
                        Pengadaan ini digunakan untuk mengganti barang yang
                        rusak, hilang, atau sudah tidak layak digunakan.
                    </p>
                </div>

                <div
                    v-else
                    class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 dark:border-blue-900/50 dark:bg-blue-950/20"
                >
                    <p class="text-[11px] leading-relaxed text-blue-700 dark:text-blue-400">
                        Pengadaan ini digunakan untuk menambahkan barang baru
                        ke kebutuhan Fakultas.
                    </p>
                </div>

                <!-- PERIHAL -->
                <div>
                    <label
                        :for="fieldId('subject')"
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Perihal
                        <span class="text-[10px] font-normal text-[#a1a09a]">
                            (opsional)
                        </span>
                    </label>

                    <input
                        :id="fieldId('subject')"
                        v-model="form.subject"
                        type="text"
                        maxlength="255"
                        placeholder="Kosongkan untuk memakai perihal otomatis"
                        :disabled="processing"
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    />

                    <p class="mt-1 text-[10px] text-[#706f6c] dark:text-[#A1A09A]">
                        Jika dikosongkan, perihal pada surat akan dibuat
                        otomatis berdasarkan nama barang dan jenis pengadaan.
                    </p>
                </div>

                <!-- ALASAN -->
                <div>
                    <label
                        :for="fieldId('reason')"
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Alasan Pengadaan
                        <span class="text-red-500">*</span>
                    </label>

                    <textarea
                        :id="fieldId('reason')"
                        v-model="form.reason"
                        required
                        rows="4"
                        placeholder="Jelaskan alasan pengadaan barang..."
                        :disabled="processing"
                        class="w-full resize-none rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    ></textarea>

                    <p class="mt-1 text-[10px] text-[#706f6c] dark:text-[#A1A09A]">
                        Jelaskan kebutuhan atau kondisi barang secara jelas
                        untuk memudahkan proses verifikasi.
                    </p>
                </div>

                <!-- LAMPIRAN -->
                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <label
                            :for="fieldId('attachments')"
                            class="block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Lampiran
                            <span class="text-[10px] font-normal text-[#a1a09a]">
                                (opsional)
                            </span>
                        </label>

                        <span class="text-[10px] text-[#a1a09a]">
                            {{ totalAttachmentCount() }}/{{ MAX_ATTACHMENTS }} berkas
                        </span>
                    </div>

                    <!-- LAMPIRAN LAMA -->
                    <ul v-if="existingAttachments.length > 0" class="mb-2 space-y-1">
                        <li
                            v-for="attachment in existingAttachments"
                            :key="attachment.path"
                            class="flex items-center justify-between rounded-lg border border-[#e3e3e0] px-3 py-1.5 text-xs dark:border-[#3E3E3A]"
                        >
                            <a
                                :href="attachment.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="truncate text-[#1b1b18] hover:underline dark:text-[#EDEDEC]"
                            >
                                {{ attachment.name }}
                            </a>

                            <button
                                type="button"
                                :disabled="processing"
                                @click="removeExistingAttachment(attachment)"
                                class="shrink-0 pl-2 text-red-600 hover:underline disabled:opacity-40"
                            >
                                Hapus
                            </button>
                        </li>
                    </ul>

                    <!-- FILE BARU -->
                    <ul v-if="form.attachments.length > 0" class="mb-2 space-y-1">
                        <li
                            v-for="(file, index) in form.attachments"
                            :key="`${file.name}-${file.size}-${file.lastModified}`"
                            class="flex items-center justify-between rounded-lg border border-dashed border-[#e3e3e0] px-3 py-1.5 text-xs dark:border-[#3E3E3A]"
                        >
                            <span class="truncate text-[#1b1b18] dark:text-[#EDEDEC]">
                                {{ file.name }}
                            </span>

                            <button
                                type="button"
                                :disabled="processing"
                                @click="removeNewAttachment(index)"
                                class="shrink-0 pl-2 text-red-600 hover:underline disabled:opacity-40"
                            >
                                Batal
                            </button>
                        </li>
                    </ul>

                    <input
                        :id="fieldId('attachments')"
                        ref="attachmentInput"
                        type="file"
                        multiple
                        accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
                        :disabled="processing || totalAttachmentCount() >= MAX_ATTACHMENTS"
                        @change="handleAttachmentsChange"
                        class="block w-full text-xs text-[#706f6c] file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-[#1b1b18] hover:file:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-50 dark:text-[#A1A09A] dark:file:bg-[#20201e] dark:file:text-[#EDEDEC]"
                    />

                    <p class="mt-1 text-[10px] text-[#706f6c] dark:text-[#A1A09A]">
                        Format JPG, PNG, atau PDF. Maksimal {{ MAX_ATTACHMENTS }}
                        berkas, masing-masing 5MB.
                    </p>
                </div>

                <!-- TANDA TANGAN -->
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <span class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                            Tanda Tangan Pengaju
                            <span class="text-[10px] font-normal text-[#a1a09a]">
                                (opsional)
                            </span>
                        </span>

                        <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            {{ hasSignature ? "Sudah ditandatangani" : "Belum ditandatangani" }}
                        </span>
                    </div>

                    <div
                        class="overflow-hidden rounded-xl border border-dashed border-black/15 bg-white dark:border-white/15 dark:bg-[#0f0f0e]"
                    >
                        <div class="relative w-full">
                            <canvas
                                ref="signatureCanvas"
                                role="img"
                                aria-label="Kanvas tanda tangan"
                                class="block h-[180px] w-full touch-none bg-white dark:bg-[#0f0f0e]"
                                @pointerdown="startDrawing"
                                @pointermove="drawSignature"
                                @pointerup="stopDrawing"
                                @pointercancel="stopDrawing"
                                @pointerleave="stopDrawing"
                            />

                            <div
                                v-if="!hasSignature"
                                class="pointer-events-none absolute inset-0 flex items-center justify-center"
                            >
                                <span class="text-sm text-[#b0afac] dark:text-[#666560]">
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
                            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                Gunakan mouse atau layar sentuh untuk tanda tangan.
                            </p>

                            <button
                                type="button"
                                :disabled="processing || !hasSignature"
                                class="rounded-lg px-3 py-2 text-xs font-medium text-[#f53003] transition hover:bg-[#f53003]/10 disabled:cursor-not-allowed disabled:opacity-40"
                                @click="clearSignature"
                            >
                                Hapus
                            </button>
                        </div>
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
                        Batal
                    </button>

                    <button
                        type="submit"
                        :disabled="processing"
                        class="rounded-lg bg-[#f53003] px-4 py-2 text-xs font-medium text-white transition hover:bg-[#d92900] focus:outline-none focus:ring-2 focus:ring-[#f53003] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#FF4433] dark:hover:bg-[#e03b2b] dark:focus:ring-[#FF4433] dark:focus:ring-offset-[#161615]"
                    >
                        {{
                            processing
                                ? "Mengirim..."
                                : procurement
                                  ? "Simpan Perubahan"
                                  : "Ajukan Pengadaan"
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
