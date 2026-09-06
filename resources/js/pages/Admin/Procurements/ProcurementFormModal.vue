<script setup lang="ts">
import { ref, watch } from "vue";

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

interface Procurement {
    id?: number;
    faculty_id: number | string;
    requested_by?: number | string;
    room_id?: number | string | null;
    item_name: string;
    quantity: number | string;
    type: "replacement" | "new_item";
    reason: string;
    requester_signature?: string | null;
    requested_at?: string | null;
    status?: "pending" | "approved" | "rejected" | "completed";
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
    requester_signature: string | null;
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

const emptyForm = (): ProcurementFormData => ({
    faculty_id: 0,
    room_id: null,
    item_name: "",
    quantity: 1,
    type: "replacement",
    reason: "",
    requester_signature: null,
});

const form = ref<ProcurementFormData>(emptyForm());

const errorMessage = ref("");

/*
|--------------------------------------------------------------------------
| SYNC FORM
|--------------------------------------------------------------------------
*/

const syncForm = (
    procurement: Procurement | null | undefined,
) => {
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

              requester_signature:
                  procurement.requester_signature ?? null,
          }
        : emptyForm();
};

/*
|--------------------------------------------------------------------------
| WATCH PROCUREMENT
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| WATCH MODAL
|--------------------------------------------------------------------------
| Setiap modal dibuka, form disinkronkan ulang.
| Ini mencegah data lama tertinggal ketika modal dibuka kembali.
|--------------------------------------------------------------------------
*/

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            errorMessage.value = "";
            syncForm(props.procurement);
        }
    },
);

/*
|--------------------------------------------------------------------------
| WATCH FACULTY
|--------------------------------------------------------------------------
| Ketika fakultas berubah, room yang sebelumnya dipilih tidak boleh
| tetap digunakan jika room tersebut bukan milik fakultas baru.
|--------------------------------------------------------------------------
*/

watch(
    () => form.value.faculty_id,
    (newFacultyId, oldFacultyId) => {
        if (
            oldFacultyId !== undefined &&
            newFacultyId !== oldFacultyId
        ) {
            const selectedRoom = props.rooms.find(
                (room) =>
                    Number(room.id) ===
                    Number(form.value.room_id),
            );

            if (
                selectedRoom &&
                Number(selectedRoom.faculty_id) !==
                    Number(newFacultyId)
            ) {
                form.value.room_id = null;
            }
        }
    },
);

/*
|--------------------------------------------------------------------------
| FILTER ROOMS
|--------------------------------------------------------------------------
*/

const availableRooms = () => {
    const facultyId = Number(form.value.faculty_id);

    if (!facultyId) {
        return [];
    }

    return props.rooms.filter(
        (room) =>
            Number(room.faculty_id) === facultyId,
    );
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

    const facultyId = Number(form.value.faculty_id);
    const roomId =
        form.value.room_id !== null &&
        form.value.room_id !== undefined &&
        form.value.room_id !== null ? Number(form.value.room_id): null;

    const itemName = String(
        form.value.item_name ?? "",
    ).trim();

    const quantity = Number(form.value.quantity);

    const type = form.value.type;

    const reason = String(
        form.value.reason ?? "",
    ).trim();

    const requesterSignature =
        String(
            form.value.requester_signature ?? "",
        ).trim() || null;

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (!facultyId) {
        errorMessage.value =
            "Silakan pilih Fakultas terlebih dahulu.";
        return;
    }

    if (!itemName) {
        errorMessage.value =
            "Nama Barang wajib diisi.";
        return;
    }

    if (!quantity || quantity < 1) {
        errorMessage.value =
            "Jumlah pengadaan minimal 1 barang.";
        return;
    }

    if (!Number.isInteger(quantity)) {
        errorMessage.value =
            "Jumlah pengadaan harus berupa angka bulat.";
        return;
    }

    if (
        type !== "replacement" &&
        type !== "new_item"
    ) {
        errorMessage.value =
            "Silakan pilih jenis pengadaan.";
        return;
    }

    if (!reason) {
        errorMessage.value =
            "Alasan Pengadaan wajib diisi.";
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATE ROOM
    |--------------------------------------------------------------------------
    */

    if (roomId !== null) {
        const selectedRoom = props.rooms.find(
            (room) =>
                Number(room.id) === Number(roomId),
        );

        if (!selectedRoom) {
            errorMessage.value =
                "Ruangan yang dipilih tidak ditemukan.";
            return;
        }

        if (
            Number(selectedRoom.faculty_id) !==
            facultyId
        ) {
            errorMessage.value =
                "Ruangan tidak sesuai dengan Fakultas yang dipilih.";
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

        requester_signature:
            requesterSignature,
    };

    console.log("=================================");
    console.log("PROCUREMENT FORM SUBMIT");
    console.log(
        "PROCUREMENT ID:",
        props.procurement?.id ?? "NEW",
    );
    console.log(
        "PROCUREMENT PAYLOAD:",
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
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
    >
        <div
            class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-black/5 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-[#161615]"
        >
            <!-- HEADER -->
            <div
                class="flex items-center justify-between border-b border-[#e3e3e0] pb-4 dark:border-[#3E3E3A]"
            >
                <h3
                    class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                >
                    {{
                        procurement
                            ? "Edit Pengadaan"
                            : "Ajukan Pengadaan Baru"
                    }}
                </h3>

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

            <form
                class="mt-4 space-y-4"
                @submit.prevent="handleSubmit"
            >
                <!-- ERROR -->
                <div
                    v-if="errorMessage"
                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-400"
                >
                    {{ errorMessage }}
                </div>

                <!-- FAKULTAS -->
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Pilih Fakultas
                    </label>

                    <select
                        v-model="form.faculty_id"
                        :disabled="
                            faculties.length === 0 ||
                            processing
                        "
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
                            {{ faculty.code }} -
                            {{ faculty.name }}
                        </option>
                    </select>

                    <p
                        v-if="faculties.length === 0"
                        class="mt-1 text-[11px] font-medium text-amber-600 dark:text-amber-500"
                    >
                        Silakan input data Fakultas
                        terlebih dahulu.
                    </p>
                </div>

                <!-- RUANGAN -->
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Ruangan
                        <span
                            class="text-[#a1a09a]"
                        >
                            (Opsional)
                        </span>
                    </label>

                    <select
                        v-model="form.room_id"
                        :disabled="
                            !form.faculty_id ||
                            availableRooms().length ===
                                0 ||
                            processing
                        "
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433] dark:disabled:bg-white/5 dark:disabled:text-[#5c5c58]"
                    >
                        <option :value="null">
                            {{
                                !form.faculty_id
                                    ? "-- Pilih Fakultas Dahulu --"
                                    : availableRooms().length ===
                                        0
                                      ? "Belum ada Ruangan"
                                      : "-- Pilih Ruangan --"
                            }}
                        </option>

                        <option
                            v-for="room in availableRooms()"
                            :key="room.id"
                            :value="room.id"
                        >
                            {{ room.code }} -
                            {{ room.name }}
                        </option>
                    </select>

                    <p
                        v-if="
                            form.faculty_id &&
                            availableRooms().length === 0
                        "
                        class="mt-1 text-[11px] font-medium text-amber-600 dark:text-amber-500"
                    >
                        Belum ada ruangan untuk
                        fakultas yang dipilih.
                    </p>
                </div>

                <!-- NAMA + JUMLAH -->
                <div
                    class="grid grid-cols-2 gap-4"
                >
                    <!-- NAMA BARANG -->
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Nama Barang
                        </label>

                        <input
                            v-model="form.item_name"
                            type="text"
                            placeholder="Contoh: Laptop"
                            :disabled="processing"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                    </div>

                    <!-- JUMLAH -->
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Jumlah
                        </label>

                        <input
                            v-model.number="
                                form.quantity
                            "
                            type="number"
                            min="1"
                            step="1"
                            placeholder="Contoh: 5"
                            :disabled="processing"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                    </div>
                </div>

                <!-- JENIS PENGADAAN -->
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Jenis Pengadaan
                    </label>

                    <select
                        v-model="form.type"
                        :disabled="processing"
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433] dark:disabled:bg-white/5 dark:disabled:text-[#5c5c58]"
                    >
                        <option value="replacement">
                            Penggantian Barang Rusak /
                            Tidak Layak
                        </option>

                        <option value="new_item">
                            Pengadaan Barang Baru
                        </option>
                    </select>
                </div>

                <!-- INFO JENIS -->
                <div
                    v-if="form.type === 'replacement'"
                    class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-900/50 dark:bg-amber-950/20"
                >
                    <p
                        class="text-[11px] leading-relaxed text-amber-700 dark:text-amber-400"
                    >
                        Pengadaan ini digunakan untuk
                        mengganti barang yang rusak,
                        hilang, atau sudah tidak layak
                        digunakan.
                    </p>
                </div>

                <div
                    v-else
                    class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 dark:border-blue-900/50 dark:bg-blue-950/20"
                >
                    <p
                        class="text-[11px] leading-relaxed text-blue-700 dark:text-blue-400"
                    >
                        Pengadaan ini digunakan untuk
                        menambahkan barang baru ke
                        kebutuhan Fakultas.
                    </p>
                </div>

                <!-- ALASAN -->
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Alasan Pengadaan
                    </label>

                    <textarea
                        v-model="form.reason"
                        rows="4"
                        placeholder="Jelaskan alasan pengadaan barang..."
                        :disabled="processing"
                        class="w-full resize-none rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    ></textarea>

                    <p
                        class="mt-1 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Jelaskan kebutuhan atau kondisi
                        barang secara jelas untuk
                        memudahkan proses verifikasi.
                    </p>
                </div>

                <!-- TANDA TANGAN -->
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Tanda Tangan Pengaju
                        <span
                            class="text-[#a1a09a]"
                        >
                            (Opsional)
                        </span>
                    </label>

                    <input
                        v-model="
                            form.requester_signature
                        "
                        type="text"
                        placeholder="Masukkan data/path tanda tangan"
                        :disabled="processing"
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    />

                    <p
                        class="mt-1 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Tanda tangan pengaju akan
                        digunakan pada dokumen pengadaan.
                    </p>
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
