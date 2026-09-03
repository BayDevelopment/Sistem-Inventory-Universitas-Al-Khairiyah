<script setup lang="ts">
import { ref, watch } from 'vue';

interface Faculty {
    id?: number;
    code: string;
    name: string;
    dean: string;
}

const props = defineProps<{
    show: boolean;
    faculty?: Faculty | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit', data: Faculty): void;
}>();

const form = ref<Faculty>({
    code: '',
    name: '',
    dean: '',
});

// Sync data ketika modal dibuka atau props.faculty berubah (untuk mode Edit)
watch(
    () => props.faculty,
    (newVal) => {
        if (newVal) {
            form.value = { ...newVal };
        } else {
            form.value = { code: '', name: '', dean: '' };
        }
    },
    { immediate: true }
);

const handleSubmit = () => {
    emit('submit', form.value);
    handleClose();
};

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <!-- Backdrop & Modal Overlay -->
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm transition-opacity"
    >
        <div
            class="w-full max-w-md rounded-2xl border border-black/5 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-[#161615]"
        >
            <!-- Header Modal -->
            <div class="flex items-center justify-between border-b border-[#e3e3e0] pb-4 dark:border-[#3E3E3A]">
                <h3 class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                    {{ faculty ? 'Edit Fakultas' : 'Tambah Fakultas Baru' }}
                </h3>
                <button
                    @click="handleClose"
                    class="rounded-lg p-1 text-[#706f6c] hover:bg-slate-100 hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#20201e] dark:hover:text-[#EDEDEC]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Form Content -->
            <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                        Kode Fakultas
                    </label>
                    <input
                        v-model="form.code"
                        type="text"
                        placeholder="Contoh: FT, FEB"
                        required
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                        Nama Fakultas
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Contoh: Fakultas Teknik"
                        required
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                        Nama Dekan
                    </label>
                    <input
                        v-model="form.dean"
                        type="text"
                        placeholder="Contoh: Dr. Ir. Ahmad Hidayat, M.T."
                        required
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    />
                </div>

                <!-- Footer Action Buttons -->
                <div class="mt-6 flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="handleClose"
                        class="rounded-lg border border-[#e3e3e0] bg-white px-4 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-[#f53003] px-4 py-2 text-xs font-medium text-white transition hover:bg-[#d92900] dark:bg-[#FF4433] dark:hover:bg-[#e03b2b]"
                    >
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
