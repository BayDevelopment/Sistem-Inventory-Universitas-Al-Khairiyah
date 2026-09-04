<script setup lang="ts">
import { ref, watch } from "vue";

interface StudyProgram {
    id?: number;
    faculty_id: number | null;
    code: string;
    degree: string;
    name: string;
    head_of_program: string;
}

type StudyProgramFormData = Omit<StudyProgram, "id">;

const props = defineProps<{
    show: boolean;
    facultyId?: number | null;
    studyProgram?: StudyProgram | null;
    facultyName?: string;
    processing?: boolean;
    errors?: Record<string, any>;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "submit", data: StudyProgramFormData): void;
}>();

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = ref<StudyProgramFormData>({
    faculty_id: props.facultyId ?? null,
    code: "",
    degree: "",
    name: "",
    head_of_program: "",
});

/*
|--------------------------------------------------------------------------
| Isi Form Saat Create / Edit
|--------------------------------------------------------------------------
*/

watch(
    () => props.studyProgram,
    (studyProgram) => {
        if (studyProgram) {
            form.value = {
                faculty_id:
                    studyProgram.faculty_id ??
                    props.facultyId ??
                    null,

                code: studyProgram.code ?? "",

                degree: studyProgram.degree ?? "",

                name: studyProgram.name ?? "",

                head_of_program:
                    studyProgram.head_of_program ??
                    (studyProgram as any).headOfProgram ??
                    "",
            };

            console.log("DATA EDIT PROGRAM STUDI:", studyProgram);
            console.log("FORM MODAL:", form.value);

            return;
        }

        form.value = {
            faculty_id: props.facultyId ?? null,
            code: "",
            degree: "",
            name: "",
            head_of_program: "",
        };
    },
    {
        immediate: true,
    },
);

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

const handleSubmit = () => {
    emit("submit", {
        faculty_id: form.value.faculty_id,
        code: form.value.code,
        degree: form.value.degree,
        name: form.value.name,
        head_of_program: form.value.head_of_program,
    });
};

/*
|--------------------------------------------------------------------------
| Close
|--------------------------------------------------------------------------
*/

const handleClose = () => {
    emit("close");
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm transition-opacity"
    >
        <div
            class="w-full max-w-md rounded-2xl border border-black/5 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-[#161615]"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-[#e3e3e0] pb-4 dark:border-[#3E3E3A]"
            >
                <div>
                    <h3
                        class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        {{
                            studyProgram
                                ? 'Edit Program Studi'
                                : 'Tambah Program Studi'
                        }}
                    </h3>

                    <p
                        v-if="facultyName"
                        class="mt-0.5 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        {{ facultyName }}
                    </p>
                </div>

                <button
                    type="button"
                    @click="handleClose"
                    class="rounded-lg p-1 text-[#706f6c] hover:bg-slate-100 hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#20201e] dark:hover:text-[#EDEDEC]"
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
                @submit.prevent="handleSubmit"
                class="mt-4 space-y-4"
            >
                <!-- Kode Program Studi -->
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Kode Program Studi
                    </label>

                    <input
                        v-model="form.code"
                        type="text"
                        placeholder="Contoh: TI, SI"
                        class="w-full rounded-lg border bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:outline-none focus:ring-1"
                        :class="
                            errors?.code
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                                : 'border-[#e3e3e0] focus:border-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]'
                        "
                    />

                    <p
                        v-if="errors?.code"
                        class="mt-1 text-[11px] text-red-500 dark:text-red-400"
                    >
                        {{ errors.code }}
                    </p>
                </div>

                <!-- Jenjang -->
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Jenjang
                    </label>

                    <select
                        v-model="form.degree"
                        class="w-full rounded-lg border bg-transparent px-3 py-2 text-xs text-[#1b1b18] transition focus:outline-none focus:ring-1 dark:text-[#EDEDEC]"
                        :class="
                            errors?.degree
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                                : 'border-[#e3e3e0] focus:border-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]'
                        "
                    >
                        <option value="" disabled>
                            Pilih jenjang
                        </option>
                        <option value="D3">D3</option>
                        <option value="S1">S1</option>
                        <option value="S2">S2</option>
                        <option value="S3">S3</option>
                    </select>

                    <p
                        v-if="errors?.degree"
                        class="mt-1 text-[11px] text-red-500 dark:text-red-400"
                    >
                        {{ errors.degree }}
                    </p>
                </div>

                <!-- Nama Program Studi -->
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Nama Program Studi
                    </label>

                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Contoh: Teknik Informatika"
                        class="w-full rounded-lg border bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:outline-none focus:ring-1"
                        :class="
                            errors?.name
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                                : 'border-[#e3e3e0] focus:border-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]'
                        "
                    />

                    <p
                        v-if="errors?.name"
                        class="mt-1 text-[11px] text-red-500 dark:text-red-400"
                    >
                        {{ errors.name }}
                    </p>
                </div>

                <!-- Ketua Program Studi -->
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Ketua Program Studi
                    </label>

                    <input
                        v-model="form.head_of_program"
                        type="text"
                        placeholder="Contoh: Dr. Siti Aminah, M.Kom"
                        class="w-full rounded-lg border bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:outline-none focus:ring-1"
                        :class="
                            errors?.head_of_program
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                                : 'border-[#e3e3e0] focus:border-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]'
                        "
                    />

                    <p
                        v-if="errors?.head_of_program"
                        class="mt-1 text-[11px] text-red-500 dark:text-red-400"
                    >
                        {{ errors.head_of_program }}
                    </p>
                </div>

                <!-- Actions -->
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
                        :disabled="processing"
                        class="rounded-lg bg-[#f53003] px-4 py-2 text-xs font-medium text-white transition hover:bg-[#d92900] disabled:opacity-50 dark:bg-[#FF4433] dark:hover:bg-[#e03b2b]"
                    >
                        {{ processing ? 'Menyimpan...' : 'Simpan Data' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>