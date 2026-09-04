<script setup lang="ts">
import { ref, watch } from 'vue';

interface Faculty {
    id: number;
    code: string;
    name: string;
}

interface Room {
    id?: number;
    faculty_id: number | string;
    code: string;
    name: string;
    building: string | null;
    floor: string | null;
    description: string | null;
    is_active: boolean;
}

type RoomFormData = Omit<Room, 'id'>;

const props = defineProps<{
    show: boolean;
    room?: Room | null;
    faculties: Faculty[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit', data: RoomFormData): void;
}>();

const form = ref<RoomFormData>({
    faculty_id: '',
    code: '',
    name: '',
    building: '',
    floor: '',
    description: '',
    is_active: true,
});

watch(
    () => props.room,
    (newVal) => {
        if (newVal) {
            form.value = {
                faculty_id: newVal.faculty_id,
                code: newVal.code,
                name: newVal.name,
                building: newVal.building || '',
                floor: newVal.floor || '',
                description: newVal.description || '',
                is_active: newVal.is_active ?? true,
            };
        } else {
            form.value = {
                faculty_id: '',
                code: '',
                name: '',
                building: '',
                floor: '',
                description: '',
                is_active: true,
            };
        }
    },
    { immediate: true }
);

const handleSubmit = () => {
    emit('submit', form.value);
};

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm transition-opacity"
    >
        <div class="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-2xl border border-black/5 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-[#161615]">
            <div class="flex items-center justify-between border-b border-[#e3e3e0] pb-4 dark:border-[#3E3E3A]">
                <h3 class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                    {{ room ? 'Edit Ruangan' : 'Tambah Ruangan Baru' }}
                </h3>
                <button @click="handleClose" class="rounded-lg p-1 text-[#706f6c] hover:bg-slate-100 hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#20201e] dark:hover:text-[#EDEDEC]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Pilih Fakultas</label>
                    <select v-model="form.faculty_id" required
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]">
                        <option value="" disabled>-- Pilih Fakultas --</option>
                        <option v-for="faculty in faculties" :key="faculty.id" :value="faculty.id">
                            {{ faculty.code }} - {{ faculty.name }}
                        </option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Kode Ruangan</label>
                        <input v-model="form.code" type="text" placeholder="Contoh: LAB-01" required
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Nama Ruangan</label>
                        <input v-model="form.name" type="text" placeholder="Contoh: Lab Komputer" required
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Gedung <span class="text-[#a1a09a]">(Opsional)</span></label>
                        <input v-model="form.building" type="text" placeholder="Contoh: Gedung A"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Lantai <span class="text-[#a1a09a]">(Opsional)</span></label>
                        <input v-model="form.floor" type="text" placeholder="Contoh: Lantai 2"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]" />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Deskripsi <span class="text-[#a1a09a]">(Opsional)</span></label>
                    <textarea v-model="form.description" rows="3" placeholder="Tambahkan keterangan ruangan..."
                        class="w-full resize-none rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"></textarea>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input id="is_active" v-model="form.is_active" type="checkbox"
                        class="h-4 w-4 rounded border-[#e3e3e0] text-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:checked:bg-[#FF4433]" />
                    <label for="is_active" class="cursor-pointer text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                        Ruangan Aktif (Bisa digunakan untuk inventaris)
                    </label>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2 pt-2 border-t border-[#e3e3e0] dark:border-[#3E3E3A]">
                    <button type="button" @click="handleClose"
                        class="mt-4 rounded-lg border border-[#e3e3e0] bg-white px-4 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]">
                        Batal
                    </button>
                    <button type="submit"
                        class="mt-4 rounded-lg bg-[#f53003] px-4 py-2 text-xs font-medium text-white transition hover:bg-[#d92900] dark:bg-[#FF4433] dark:hover:bg-[#e03b2b]">
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
