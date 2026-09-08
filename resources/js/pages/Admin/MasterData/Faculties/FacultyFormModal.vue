<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from "vue";

interface Faculty {
    id?: number;
    code: string;
    name: string;
    dean: string;
    dean_nip?: string | null;
    letterhead_path?: string | null;
    dean_signature?: string | null;
}

interface FacultyFormPayload {
    code: string;
    name: string;
    dean: string;
    dean_nip: string;
    letterhead: File | null;
    dean_signature: File | null;
    remove_letterhead: boolean;
    remove_dean_signature: boolean;
}

const props = defineProps<{
    show: boolean;
    faculty?: Faculty | null;
    processing?: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "submit", data: FacultyFormPayload): void;
}>();

const errorMessage = ref("");

const form = ref<FacultyFormPayload>({
    code: "",
    name: "",
    dean: "",
    dean_nip: "",
    letterhead: null,
    dean_signature: null,
    remove_letterhead: false,
    remove_dean_signature: false,
});

const letterheadPreview = ref<string | null>(null);
const deanSignaturePreview = ref<string | null>(null);

const letterheadInput = ref<HTMLInputElement | null>(null);
const deanSignatureInput = ref<HTMLInputElement | null>(null);

/**
 * Mengubah storage path menjadi URL publik.
 *
 * Contoh:
 * letterheads/file.jpeg
 * -> /storage/letterheads/file.jpeg
 */
const storageUrl = (path: string | null | undefined): string | null => {
    if (!path) {
        return null;
    }

    // Jika sudah berupa URL absolut, gunakan langsung.
    if (/^https?:\/\//i.test(path)) {
        return path;
    }

    // Jika sudah berupa URL storage, jangan tambahkan /storage lagi.
    if (path.startsWith("/storage/")) {
        return path;
    }

    return `/storage/${path.replace(/^\/+/, "")}`;
};

/**
 * Reset form berdasarkan data fakultas.
 */
const resetForm = (faculty: Faculty | null | undefined) => {
    form.value = {
        code: faculty?.code ?? "",
        name: faculty?.name ?? "",
        dean: faculty?.dean ?? "",
        dean_nip: faculty?.dean_nip ?? "",
        letterhead: null,
        dean_signature: null,
        remove_letterhead: false,
        remove_dean_signature: false,
    };

    letterheadPreview.value = storageUrl(faculty?.letterhead_path);
    deanSignaturePreview.value = storageUrl(faculty?.dean_signature);

    errorMessage.value = "";
};

/**
 * Membersihkan blob URL preview agar tidak terjadi memory leak.
 */
const revokeBlobUrl = (preview: string | null) => {
    if (preview?.startsWith("blob:")) {
        URL.revokeObjectURL(preview);
    }
};

/**
 * Watch perubahan data fakultas.
 */
watch(
    () => props.faculty,
    (newVal) => {
        revokeBlobUrl(letterheadPreview.value);
        revokeBlobUrl(deanSignaturePreview.value);

        resetForm(newVal);
    },
    { immediate: true },
);

/**
 * Reset form setiap kali modal dibuka.
 */
watch(
    () => props.show,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        revokeBlobUrl(letterheadPreview.value);
        revokeBlobUrl(deanSignaturePreview.value);

        resetForm(props.faculty);
    },
);

/**
 * Validasi file gambar pada sisi frontend.
 * Backend tetap wajib melakukan validasi ulang.
 */
const validateImageFile = (
    file: File,
    allowedTypes: string[],
    maxSize: number,
    label: string,
): string | null => {
    if (!allowedTypes.includes(file.type)) {
        const formats = allowedTypes
            .map((type) => type.split("/")[1]?.toUpperCase())
            .filter(Boolean)
            .join(" atau ");

        return `${label} harus berformat ${formats}.`;
    }

    if (file.size > maxSize) {
        return `${label} maksimal ${maxSize / 1024 / 1024} MB.`;
    }

    return null;
};

/**
 * Handle upload kop surat.
 */
const handleLetterheadChange = (event: Event) => {
    if (props.processing) {
        return;
    }

    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    errorMessage.value = "";

    if (!file) {
        return;
    }

    const error = validateImageFile(
        file,
        ["image/png", "image/jpeg"],
        2 * 1024 * 1024,
        "Kop surat",
    );

    if (error) {
        errorMessage.value = error;
        target.value = "";
        form.value.letterhead = null;

        return;
    }

    revokeBlobUrl(letterheadPreview.value);

    form.value.letterhead = file;
    form.value.remove_letterhead = false;

    letterheadPreview.value = URL.createObjectURL(file);
};

/**
 * Handle upload tanda tangan Dekan.
 */
const handleDeanSignatureChange = (event: Event) => {
    if (props.processing) {
        return;
    }

    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    errorMessage.value = "";

    if (!file) {
        return;
    }

    const error = validateImageFile(
        file,
        ["image/png"],
        1 * 1024 * 1024,
        "Tanda tangan Dekan",
    );

    if (error) {
        errorMessage.value = error;
        target.value = "";
        form.value.dean_signature = null;

        return;
    }

    revokeBlobUrl(deanSignaturePreview.value);

    form.value.dean_signature = file;
    form.value.remove_dean_signature = false;

    deanSignaturePreview.value = URL.createObjectURL(file);
};

/**
 * Hapus kop surat.
 */
const removeLetterhead = () => {
    if (props.processing) {
        return;
    }

    errorMessage.value = "";

    revokeBlobUrl(letterheadPreview.value);

    form.value.letterhead = null;
    form.value.remove_letterhead = true;

    letterheadPreview.value = null;

    if (letterheadInput.value) {
        letterheadInput.value.value = "";
    }
};

/**
 * Hapus tanda tangan Dekan.
 */
const removeDeanSignature = () => {
    if (props.processing) {
        return;
    }

    errorMessage.value = "";

    revokeBlobUrl(deanSignaturePreview.value);

    form.value.dean_signature = null;
    form.value.remove_dean_signature = true;

    deanSignaturePreview.value = null;

    if (deanSignatureInput.value) {
        deanSignatureInput.value.value = "";
    }
};

/**
 * Submit form.
 */
const handleSubmit = () => {
    if (props.processing) {
        return;
    }

    errorMessage.value = "";

    emit("submit", {
        ...form.value,
    });
};

/**
 * Tutup modal.
 */
const handleClose = () => {
    if (props.processing) {
        return;
    }

    errorMessage.value = "";

    emit("close");
};

/**
 * Cleanup blob URL ketika component dihancurkan.
 */
onBeforeUnmount(() => {
    revokeBlobUrl(letterheadPreview.value);
    revokeBlobUrl(deanSignaturePreview.value);
});

const inputClass =
    "w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]";
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm transition-opacity"
    >
        <div
            class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-black/5 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-[#161615]"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-[#e3e3e0] pb-4 dark:border-[#3E3E3A]"
            >
                <h3
                    class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                >
                    {{ faculty ? "Edit Fakultas" : "Tambah Fakultas Baru" }}
                </h3>

                <button
                    type="button"
                    @click="handleClose"
                    :disabled="processing"
                    aria-label="Tutup modal"
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

            <!-- Form -->
            <form
                class="mt-4 space-y-4"
                @submit.prevent="handleSubmit"
            >
                <!-- Frontend validation error -->
                <div
                    v-if="errorMessage"
                    role="alert"
                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-400"
                >
                    {{ errorMessage }}
                </div>

                <!-- Kode Fakultas -->
                <div>
                    <label
                        for="faculty-code"
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Kode Fakultas
                    </label>

                    <input
                        id="faculty-code"
                        v-model="form.code"
                        type="text"
                        placeholder="Contoh: FT, FEB"
                        autocomplete="off"
                        required
                        :disabled="processing"
                        :class="inputClass"
                    />
                </div>

                <!-- Nama Fakultas -->
                <div>
                    <label
                        for="faculty-name"
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Nama Fakultas
                    </label>

                    <input
                        id="faculty-name"
                        v-model="form.name"
                        type="text"
                        placeholder="Contoh: Fakultas Teknik"
                        autocomplete="organization"
                        required
                        :disabled="processing"
                        :class="inputClass"
                    />
                </div>

                <!-- Nama Dekan -->
                <div>
                    <label
                        for="faculty-dean"
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Nama Dekan
                    </label>

                    <input
                        id="faculty-dean"
                        v-model="form.dean"
                        type="text"
                        placeholder="Contoh: Dr. Ir. Ahmad Hidayat, M.T."
                        autocomplete="name"
                        required
                        :disabled="processing"
                        :class="inputClass"
                    />
                </div>

                <!-- NIP Dekan -->
                <div>
                    <label
                        for="faculty-dean-nip"
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        NIP Dekan
                    </label>

                    <input
                        id="faculty-dean-nip"
                        v-model="form.dean_nip"
                        type="text"
                        inputmode="numeric"
                        placeholder="Contoh: 198501012010011001"
                        autocomplete="off"
                        :disabled="processing"
                        :class="inputClass"
                    />
                </div>

                <!-- Kop Surat -->
                <div>
                    <label
                        for="faculty-letterhead"
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Kop Surat
                    </label>

                    <!-- Preview -->
                    <div
                        v-if="letterheadPreview"
                        class="mb-2 overflow-hidden rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A]"
                    >
                        <img
                            :src="letterheadPreview"
                            alt="Preview kop surat"
                            class="max-h-40 w-full object-contain"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="faculty-letterhead"
                            ref="letterheadInput"
                            type="file"
                            accept="image/png,image/jpeg"
                            :disabled="processing"
                            @change="handleLetterheadChange"
                            class="block w-full text-xs text-[#706f6c] file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-[#1b1b18] hover:file:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-50 dark:text-[#A1A09A] dark:file:bg-[#20201e] dark:file:text-[#EDEDEC]"
                        />

                        <button
                            v-if="letterheadPreview"
                            type="button"
                            @click="removeLetterhead"
                            :disabled="processing"
                            class="shrink-0 text-xs font-medium text-red-600 transition hover:underline disabled:cursor-not-allowed disabled:opacity-50 dark:text-red-400"
                        >
                            Hapus
                        </button>
                    </div>

                    <p class="mt-1 text-[10px] text-[#a1a09a]">
                        Format JPG/PNG, maksimal 2MB. Berisi logo, nama
                        institusi, alamat, dan kontak.
                    </p>
                </div>

                <!-- Tanda Tangan Dekan -->
                <div>
                    <label
                        for="faculty-dean-signature"
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Tanda Tangan Dekan (opsional)
                    </label>

                    <!-- Preview -->
                    <div
                        v-if="deanSignaturePreview"
                        class="mb-2 flex h-20 w-40 items-center justify-center overflow-hidden rounded-lg border border-[#e3e3e0] bg-slate-50 dark:border-[#3E3E3A] dark:bg-[#0f0f0e]"
                    >
                        <img
                            :src="deanSignaturePreview"
                            alt="Preview tanda tangan Dekan"
                            class="max-h-full max-w-full object-contain"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="faculty-dean-signature"
                            ref="deanSignatureInput"
                            type="file"
                            accept="image/png"
                            :disabled="processing"
                            @change="handleDeanSignatureChange"
                            class="block w-full text-xs text-[#706f6c] file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-[#1b1b18] hover:file:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-50 dark:text-[#A1A09A] dark:file:bg-[#20201e] dark:file:text-[#EDEDEC]"
                        />

                        <button
                            v-if="deanSignaturePreview"
                            type="button"
                            @click="removeDeanSignature"
                            :disabled="processing"
                            class="shrink-0 text-xs font-medium text-red-600 transition hover:underline disabled:cursor-not-allowed disabled:opacity-50 dark:text-red-400"
                        >
                            Hapus
                        </button>
                    </div>

                    <p class="mt-1 text-[10px] text-[#a1a09a]">
                        Format PNG (background transparan), maksimal 1MB. Jika
                        kosong, surat akan dicetak tanpa tanda tangan digital.
                    </p>
                </div>

                <!-- Footer -->
                <div
                    class="mt-6 flex items-center justify-end gap-2 pt-2"
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
                                ? "Menyimpan..."
                                : faculty
                                  ? "Simpan Perubahan"
                                  : "Simpan Data"
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>