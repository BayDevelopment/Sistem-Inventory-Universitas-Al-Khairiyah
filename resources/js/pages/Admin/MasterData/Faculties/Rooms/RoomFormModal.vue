<script setup lang="ts">
import { ref, watch } from "vue";

interface Faculty {
    id: number;
    code: string;
    name: string;
}

interface RoomType {
    id: number;
    name: string;
    slug: string;
}

interface Room {
    id?: number;
    faculty_id: number | string;
    room_type_id: number | string;
    code: string;
    name: string;
    building: string | null;
    floor: string | null;
    building_floor: string | null;
    description: string | null;
    is_active: boolean;
}

type RoomFormData = Omit<Room, "id">;

const props = defineProps<{
    show: boolean;
    room?: Room | null;
    faculties: Faculty[];
    roomTypes: RoomType[];
    processing?: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "submit", data: RoomFormData): void;
}>();

const emptyForm = (): RoomFormData => ({
    faculty_id: "",
    room_type_id: "",
    code: "",
    name: "",
    building: "",
    floor: "",
    building_floor: "",
    description: "",
    is_active: true,
});

const form = ref<RoomFormData>(emptyForm());

const errorMessage = ref("");

const syncForm = (room: Room | null | undefined) => {
    form.value = room
        ? {
              faculty_id: room.faculty_id ?? "",
              room_type_id: room.room_type_id ?? "",
              code: room.code ?? "",
              name: room.name ?? "",
              building: room.building ?? "",
              floor: room.floor ?? "",
              building_floor: room.building_floor ?? "",
              description: room.description ?? "",
              is_active: room.is_active ?? true,
          }
        : emptyForm();
};

watch(
    () => props.room,
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
| Setiap modal dibuka, selalu sinkronkan ulang dari props.room saat itu.
| Ini mencegah form "nyangkut" menampilkan editan yang dibatalkan
| ketika room yang sama dibuka untuk diedit lagi (reference-nya tidak
| berubah sehingga watcher di atas tidak retrigger).
|--------------------------------------------------------------------------
*/

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            errorMessage.value = "";
            syncForm(props.room);
        }
    },
);

const handleSubmit = () => {
    if (props.processing) {
        return;
    }

    errorMessage.value = "";

    const facultyId = Number(form.value.faculty_id);
    const roomTypeId = Number(form.value.room_type_id);

    const code = String(form.value.code ?? "").trim();
    const name = String(form.value.name ?? "").trim();

    if (!facultyId) {
        errorMessage.value =
            "Silakan pilih Fakultas terlebih dahulu.";
        return;
    }

    if (!roomTypeId) {
        errorMessage.value =
            "Silakan pilih Jenis Ruangan terlebih dahulu.";
        return;
    }

    if (!code) {
        errorMessage.value =
            "Kode Ruangan wajib diisi.";
        return;
    }

    if (!name) {
        errorMessage.value =
            "Nama Ruangan wajib diisi.";
        return;
    }

    const payload: RoomFormData = {
        faculty_id: facultyId,
        room_type_id: roomTypeId,

        code,
        name,

        building:
            String(form.value.building ?? "").trim() || null,

        floor:
            String(form.value.floor ?? "").trim() || null,

        building_floor:
            String(form.value.building_floor ?? "").trim() || null,

        description:
            String(form.value.description ?? "").trim() || null,

        is_active: Boolean(form.value.is_active),
    };

    console.log("=================================");
    console.log("ROOM FORM SUBMIT");
    console.log("ROOM ID:", props.room?.id ?? "NEW");
    console.log("ROOM PAYLOAD:", payload);
    console.log("=================================");

    emit("submit", payload);
};

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
            <div
                class="flex items-center justify-between border-b border-[#e3e3e0] pb-4 dark:border-[#3E3E3A]"
            >
                <h3
                    class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                >
                    {{ room ? "Edit Ruangan" : "Tambah Ruangan Baru" }}
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
                <div
                    v-if="errorMessage"
                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-400"
                >
                    {{ errorMessage }}
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Pilih Fakultas
                    </label>

                    <select
                        v-model="form.faculty_id"
                        :disabled="faculties.length === 0"
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433] dark:disabled:bg-white/5 dark:disabled:text-[#5c5c58]"
                    >
                        <option value="">
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

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Kode Ruangan
                        </label>

                        <input
                            v-model="form.code"
                            type="text"
                            placeholder="Contoh: LAB-01"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Nama Ruangan
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Contoh: Lab Komputer"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                    </div>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Jenis Ruangan
                    </label>

                    <select
                        v-model="form.room_type_id"
                        :disabled="roomTypes.length === 0"
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433] dark:disabled:bg-white/5 dark:disabled:text-[#5c5c58]"
                    >
                        <option value="">
                            {{
                                roomTypes.length === 0
                                    ? "Belum ada Jenis Ruangan"
                                    : "-- Pilih Jenis Ruangan --"
                            }}
                        </option>

                        <option
                            v-for="roomType in roomTypes"
                            :key="roomType.id"
                            :value="roomType.id"
                        >
                            {{ roomType.name }}
                        </option>
                    </select>

                    <p
                        v-if="roomTypes.length === 0"
                        class="mt-1 text-[11px] font-medium text-amber-600 dark:text-amber-500"
                    >
                        Silakan input data Jenis Ruangan terlebih dahulu.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Gedung
                            <span class="text-[#a1a09a]">
                                (Opsional)
                            </span>
                        </label>

                        <input
                            v-model="form.building"
                            type="text"
                            placeholder="Contoh: Gedung A"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Lantai
                            <span class="text-[#a1a09a]">
                                (Opsional)
                            </span>
                        </label>

                        <input
                            v-model="form.floor"
                            type="text"
                            placeholder="Contoh: Lantai 2"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                    </div>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Gedung / Lantai
                        <span class="text-[#a1a09a]">
                            (Opsional)
                        </span>
                    </label>

                    <input
                        v-model="form.building_floor"
                        type="text"
                        placeholder="Contoh: Gedung A - Lantai 2"
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    />

                    <p
                        class="mt-1 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Contoh: Gedung A - Lantai 2.
                    </p>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Deskripsi
                        <span class="text-[#a1a09a]">
                            (Opsional)
                        </span>
                    </label>

                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Tambahkan keterangan ruangan..."
                        class="w-full resize-none rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    ></textarea>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input
                        id="is_active"
                        v-model="form.is_active"
                        type="checkbox"
                        class="h-4 w-4 rounded border-[#e3e3e0] text-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:checked:bg-[#FF4433]"
                    />

                    <label
                        for="is_active"
                        class="cursor-pointer text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Ruangan Aktif (Bisa digunakan untuk inventaris)
                    </label>
                </div>

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
                        {{ processing ? "Menyimpan..." : (room ? "Simpan Perubahan" : "Simpan Data") }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>