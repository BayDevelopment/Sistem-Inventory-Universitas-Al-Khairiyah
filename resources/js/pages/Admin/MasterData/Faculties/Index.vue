<script setup lang="ts">
import { ref, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";

import FacultyFormModal from "./FacultyFormModal.vue";
import StudyProgramModal from "./StudyProgramModal.vue";

import {
    store as storeFaculty,
    update as updateFaculty,
    destroy as destroyFaculty,
} from "@/actions/App/Http/Controllers/FacultyController";

import {
    store as storeProdi,
    update as updateProdi,
    destroy as destroyProdi,
} from "@/actions/App/Http/Controllers/StudyProgramController";

/*
|--------------------------------------------------------------------------
| Interfaces
|--------------------------------------------------------------------------
*/

interface StudyProgram {
    id: number;
    faculty_id: number | null;
    code: string;
    degree: string;
    name: string;
    head_of_program: string;
}

interface Faculty {
    id: number;
    code: string;
    name: string;
    dean: string;
    studyPrograms: StudyProgram[];
}

interface PaginatedFaculties {
    data: Faculty[];
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    faculties: PaginatedFaculties;
}>();

const isLoading = ref(false);
const searchQuery = ref("");
const expandedFaculties = ref<number[]>([]);
const isFacultyModalOpen = ref(false);
const editingFaculty = ref<Faculty | null>(null);
const isProdiModalOpen = ref(false);
const isProdiProcessing = ref(false);
const editingProdi = ref<StudyProgram | null>(null);
const activeFacultyForProdi = ref<Faculty | null>(null);
const faculties = computed(() => props.faculties?.data ?? []);

const isConfirmModalOpen = ref(false);
const confirmTitle = ref("Konfirmasi Penghapusan");
const confirmMessage = ref("");
const confirmAction = ref<(() => void) | null>(null);
const isDeleting = ref(false);

const totalFaculties = computed(
    () => props.faculties?.total ?? faculties.value.length,
);

const totalStudyPrograms = computed(() =>
    faculties.value.reduce(
        (total, faculty) => total + (faculty.studyPrograms?.length ?? 0),
        0,
    ),
);

const filteredFaculties = computed(() => {
    if (!searchQuery.value) {
        return faculties.value;
    }

    const query = searchQuery.value.toLowerCase();

    return faculties.value.filter((faculty) => {
        const facultyMatch =
            faculty.name.toLowerCase().includes(query) ||
            faculty.code.toLowerCase().includes(query);

        const prodiMatch = (faculty.studyPrograms ?? []).some(
            (prodi) =>
                prodi.name?.toLowerCase().includes(query) ||
                prodi.code?.toLowerCase().includes(query) ||
                prodi.degree?.toLowerCase().includes(query) ||
                prodi.head_of_program?.toLowerCase().includes(query),
        );

        return facultyMatch || prodiMatch;
    });
});

const toggleExpand = (id: number) => {
    const index = expandedFaculties.value.indexOf(id);

    if (index > -1) {
        expandedFaculties.value.splice(index, 1);
    } else {
        expandedFaculties.value.push(id);
    }
};

const openCreateFacultyModal = () => {
    editingFaculty.value = null;
    isFacultyModalOpen.value = true;
};

const openEditFacultyModal = (faculty: Faculty) => {
    editingFaculty.value = faculty;
    isFacultyModalOpen.value = true;
};

const handleSaveFaculty = (data: any) => {
    if (editingFaculty.value) {
        router.put(updateFaculty.url(editingFaculty.value.id), data, {
            preserveScroll: true,

            onSuccess: () => {
                isFacultyModalOpen.value = false;
            },
        });

        return;
    }

    router.post(storeFaculty.url(), data, {
        preserveScroll: true,

        onSuccess: () => {
            isFacultyModalOpen.value = false;
        },
    });
};

const deleteFaculty = (facultyId: number) => {
    confirmTitle.value = "Hapus Fakultas?";
    confirmMessage.value =
        "Apakah Anda yakin ingin menghapus fakultas ini? Semua program studi di dalamnya juga akan ikut terhapus.";

    confirmAction.value = () => {
        isDeleting.value = true;

        router.delete(destroyFaculty.url(facultyId), {
            preserveScroll: true,

            onFinish: () => {
                isDeleting.value = false;
                isConfirmModalOpen.value = false;
                confirmAction.value = null;
            },
        });
    };

    isConfirmModalOpen.value = true;
};

const cancelDelete = () => {
    if (isDeleting.value) {
        return;
    }

    isConfirmModalOpen.value = false;
    confirmAction.value = null;
};

const executeDelete = () => {
    if (!confirmAction.value || isDeleting.value) {
        return;
    }

    confirmAction.value();
};

/**
 * Tambah Program Studi
 */
const openProdiModal = (faculty: Faculty) => {
    editingProdi.value = null;

    activeFacultyForProdi.value = faculty;

    isProdiModalOpen.value = true;
};

const openEditProdiModal = (faculty: Faculty, prodi: StudyProgram) => {
    console.log("DATA PRODI YANG AKAN DIEDIT:", prodi);

    /*
     * Simpan fakultas aktif.
     */
    activeFacultyForProdi.value = faculty;

    editingProdi.value = {
        id: prodi.id,
        faculty_id: prodi.faculty_id ?? faculty.id,
        code: prodi.code ?? "",
        degree: prodi.degree ?? "",
        name: prodi.name ?? "",
        head_of_program: prodi.head_of_program ?? "",
    };

    isProdiModalOpen.value = true;
};

const closeProdiModal = () => {
    isProdiModalOpen.value = false;

    editingProdi.value = null;

    activeFacultyForProdi.value = null;

    isProdiProcessing.value = false;
};

const handleSaveProdi = (data: {
    faculty_id: number | null;
    code: string;
    degree: string;
    name: string;
    head_of_program: string;
}) => {
    if (!activeFacultyForProdi.value) {
        return;
    }

    isProdiProcessing.value = true;

    const payload = {
        faculty_id: activeFacultyForProdi.value.id,
        code: data.code,
        degree: data.degree,
        name: data.name,
        head_of_program: data.head_of_program,
    };

    /*
     * EDIT
     */
    if (editingProdi.value?.id) {
        router.put(updateProdi.url(editingProdi.value.id), payload, {
            preserveScroll: true,

            onSuccess: () => {
                closeProdiModal();
            },

            onError: (errors) => {
                console.error("Gagal update Program Studi:", errors);
            },

            onFinish: () => {
                isProdiProcessing.value = false;
            },
        });

        return;
    }

    /*
     * CREATE
     */
    router.post(storeProdi.url(), payload, {
        preserveScroll: true,

        onSuccess: () => {
            closeProdiModal();
        },

        onError: (errors) => {
            console.error("Gagal menyimpan Program Studi:", errors);
        },

        onFinish: () => {
            isProdiProcessing.value = false;
        },
    });
};

const deleteProdi = (prodiId: number) => {
    confirmTitle.value = "Hapus Program Studi?";
    confirmMessage.value =
        "Apakah Anda yakin ingin menghapus program studi ini? Data yang sudah dihapus tidak dapat dikembalikan.";

    confirmAction.value = () => {
        isDeleting.value = true;

        router.delete(destroyProdi.url(prodiId), {
            preserveScroll: true,

            onFinish: () => {
                isDeleting.value = false;
                isConfirmModalOpen.value = false;
                confirmAction.value = null;
            },
        });
    };

    isConfirmModalOpen.value = true;
};
</script>

<template>
    <Head title="Fakultas & Prodi - Sistem Inventory" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- STATS CARDS -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <template v-if="isLoading">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="animate-pulse rounded-xl border border-black/5 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <div
                            class="h-3 w-28 rounded bg-slate-200 dark:bg-zinc-800"
                        ></div>
                        <div
                            class="h-9 w-9 rounded-lg bg-slate-200 dark:bg-zinc-800"
                        ></div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div
                            class="h-8 w-16 rounded bg-slate-200 dark:bg-zinc-800"
                        ></div>
                        <div
                            class="h-3 w-36 rounded bg-slate-200 dark:bg-zinc-800"
                        ></div>
                    </div>
                </div>
            </template>

            <template v-else>
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                            >Total Fakultas</span
                        >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff2f2] text-[#f53003] transition group-hover:bg-[#f53003] group-hover:text-white dark:bg-[#1D0002] dark:text-[#FF4433] dark:group-hover:bg-[#FF4433] dark:group-hover:text-white"
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
                                    d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ totalFaculties }}
                        </div>
                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Fakultas aktif
                        </p>
                    </div>
                    <div
                        class="absolute bottom-0 left-0 h-[2px] w-full bg-[#f53003]/20 opacity-0 transition group-hover:opacity-100 dark:bg-[#FF4433]/30"
                    ></div>
                </div>

                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                            >Total Program Studi</span
                        >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff2f2] text-[#f53003] transition group-hover:bg-[#f53003] group-hover:text-white dark:bg-[#1D0002] dark:text-[#FF4433] dark:group-hover:bg-[#FF4433] dark:group-hover:text-white"
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
                                    d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ totalStudyPrograms }}
                        </div>
                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Program studi terdaftar
                        </p>
                    </div>
                    <div
                        class="absolute bottom-0 left-0 h-[2px] w-full bg-[#f53003]/20 opacity-0 transition group-hover:opacity-100 dark:bg-[#FF4433]/30"
                    ></div>
                </div>

                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md sm:col-span-2 lg:col-span-1 dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                            >Aksi Cepat</span
                        >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff2f2] text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
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
                                    d="M12 4.5v15m7.5-7.5h-15"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-col gap-2">
                        <button
                            @click="openCreateFacultyModal"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#1b1b18] px-3 py-2 text-xs font-medium text-white transition hover:bg-black dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white"
                        >
                            <span>+ Tambah Fakultas Baru</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- MAIN CONTENT SECTION -->
        <div
            class="flex flex-1 flex-col rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]"
        >
            <div
                class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <template v-if="isLoading">
                    <div class="animate-pulse space-y-2">
                        <div
                            class="h-5 w-64 rounded bg-slate-200 dark:bg-zinc-800"
                        ></div>
                        <div
                            class="h-3 w-80 rounded bg-slate-200 dark:bg-zinc-800"
                        ></div>
                    </div>
                    <div
                        class="h-9 w-full rounded-lg bg-slate-200 sm:w-72 dark:bg-zinc-800"
                    ></div>
                </template>
                <template v-else>
                    <div>
                        <h2
                            class="text-lg font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Master Data Fakultas & Program Studi
                        </h2>
                        <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            Kelola struktur unit fakultas dan jurusan di
                            lingkungan kampus.
                        </p>
                    </div>
                    <div class="relative w-full sm:w-72">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari fakultas atau prodi..."
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent py-2 pl-9 pr-3.5 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="absolute left-3 top-2.5 h-4 w-4 text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                            />
                        </svg>
                    </div>
                </template>
            </div>

            <div v-if="isLoading" class="space-y-4">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="animate-pulse overflow-hidden rounded-xl border border-[#e3e3e0] bg-[#FDFDFC] p-4 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-7 w-7 rounded-lg bg-slate-200 dark:bg-zinc-800"
                            ></div>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-4 w-10 rounded bg-slate-200 dark:bg-zinc-800"
                                    ></div>
                                    <div
                                        class="h-4 w-40 rounded bg-slate-200 dark:bg-zinc-800"
                                    ></div>
                                </div>
                                <div
                                    class="h-3 w-52 rounded bg-slate-200 dark:bg-zinc-800"
                                ></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div
                                class="h-7 w-16 rounded-lg bg-slate-200 dark:bg-zinc-800"
                            ></div>
                            <div
                                class="h-7 w-7 rounded-lg bg-slate-200 dark:bg-zinc-800"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="faculty in filteredFaculties"
                    :key="faculty.id"
                    class="overflow-hidden rounded-xl border border-[#e3e3e0] bg-[#FDFDFC] transition dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                >
                    <!-- Faculty Header -->
                    <div
                        class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                        :class="{
                            'border-b border-[#e3e3e0] dark:border-[#3E3E3A]':
                                expandedFaculties.includes(faculty.id),
                        }"
                    >
                        <div class="flex items-start gap-3">
                            <button
                                @click="toggleExpand(faculty.id)"
                                class="mt-0.5 rounded-lg border border-black/5 bg-white p-1.5 text-[#706f6c] hover:text-[#1b1b18] dark:border-white/10 dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="h-4 w-4 transition-transform duration-200"
                                    :class="{
                                        'rotate-180':
                                            expandedFaculties.includes(
                                                faculty.id,
                                            ),
                                    }"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m19.5 8.25-7.5 7.5-7.5-7.5"
                                    />
                                </svg>
                            </button>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="rounded bg-[#fff2f2] px-2 py-0.5 text-[10px] font-bold text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
                                        >{{ faculty.code }}</span
                                    >
                                    <h3
                                        class="text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                    >
                                        {{ faculty.name }}
                                    </h3>
                                </div>
                                <p
                                    class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Dekan:
                                    <span
                                        class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >{{ faculty.dean }}</span
                                    >
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-2 self-end sm:self-center"
                        >
                            <span
                                class="mr-2 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                >{{
                                    faculty.studyPrograms?.length ?? 0
                                }}
                                Prodi</span
                            >
                            <button
                                @click="openProdiModal(faculty)"
                                class="rounded-lg border border-[#e3e3e0] bg-white px-2.5 py-1.5 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                            >
                                + Prodi
                            </button>
                            <button
                                @click="openEditFacultyModal(faculty)"
                                class="rounded-lg border border-[#e3e3e0] bg-white p-1.5 text-[#706f6c] hover:text-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:text-[#FF4433]"
                                title="Edit Fakultas"
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
                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                    />
                                </svg>
                            </button>
                            <button
                                @click="deleteFaculty(faculty.id)"
                                class="rounded-lg border border-[#e3e3e0] bg-white p-1.5 text-[#706f6c] hover:text-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:text-[#FF4433]"
                                title="Hapus Fakultas"
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
                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Expandable Study Programs List -->
                    <div
                        v-if="expandedFaculties.includes(faculty.id)"
                        class="bg-white/50 p-4 dark:bg-[#161615]/30"
                    >
                        <div
                            v-if="(faculty.studyPrograms?.length ?? 0) > 0"
                            class="overflow-x-auto"
                        >
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr
                                        class="border-b border-[#e3e3e0] text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]"
                                    >
                                        <th
                                            class="pb-2 font-medium uppercase tracking-wider"
                                        >
                                            Kode
                                        </th>
                                        <th
                                            class="pb-2 font-medium uppercase tracking-wider"
                                        >
                                            Jenjang
                                        </th>
                                        <th
                                            class="pb-2 font-medium uppercase tracking-wider"
                                        >
                                            Nama Program Studi
                                        </th>
                                        <th
                                            class="pb-2 font-medium uppercase tracking-wider"
                                        >
                                            Ketua Prodi
                                        </th>
                                        <th
                                            class="pb-2 text-right font-medium uppercase tracking-wider"
                                        >
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-[#e3e3e0]/50 dark:divide-[#3E3E3A]/50"
                                >
                                    <tr
                                        v-for="prodi in faculty.studyPrograms"
                                        :key="prodi.id"
                                        class="text-[#1b1b18] dark:text-[#EDEDEC]"
                                    >
                                        <td
                                            class="py-2.5 font-mono font-semibold text-[#f53003] dark:text-[#FF4433]"
                                        >
                                            {{ prodi.code }}
                                        </td>
                                        <td class="py-2.5">
                                            <span
                                                class="rounded border border-slate-200 bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300"
                                                >{{ prodi.degree }}</span
                                            >
                                        </td>
                                        <td class="py-2.5 font-medium">
                                            {{ prodi.name }}
                                        </td>
                                        <td
                                            class="py-2.5 text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            {{ prodi.head_of_program }}
                                        </td>
                                        <td class="py-2.5 text-right">
                                            <div
                                                class="flex items-center justify-end gap-1.5"
                                            >
                                                <button
                                                    @click="
                                                        openEditProdiModal(
                                                            faculty,
                                                            prodi,
                                                        )
                                                    "
                                                    class="p-1 text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                                                    title="Edit Prodi"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke-width="1.5"
                                                        stroke="currentColor"
                                                        class="h-3.5 w-3.5"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                                                        />
                                                    </svg>
                                                </button>
                                                <button
                                                    @click="
                                                        deleteProdi(prodi.id)
                                                    "
                                                    class="p-1 text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]"
                                                    title="Hapus Prodi"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke-width="1.5"
                                                        stroke="currentColor"
                                                        class="h-3.5 w-3.5"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                                        />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="py-6 text-center">
                            <p
                                class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Belum ada program studi di fakultas ini.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="filteredFaculties.length === 0"
                    class="rounded-xl border border-dashed border-[#e3e3e0] p-8 text-center dark:border-[#3E3E3A]"
                >
                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        Data fakultas atau program studi tidak ditemukan.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form Fakultas -->
    <FacultyFormModal
        :show="isFacultyModalOpen"
        :faculty="editingFaculty"
        @close="isFacultyModalOpen = false"
        @submit="handleSaveFaculty"
    />

    <StudyProgramModal
        :show="isProdiModalOpen"
        :study-program="editingProdi"
        :faculty-id="activeFacultyForProdi?.id ?? null"
        :faculty-name="activeFacultyForProdi?.name"
        :processing="isProdiProcessing"
        :errors="$page.props.errors"
        @close="closeProdiModal"
        @submit="handleSaveProdi"
    />

    <!-- Confirmation Modal -->
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
                v-if="isConfirmModalOpen"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                @keydown.esc="cancelDelete"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/50 backdrop-blur-sm dark:bg-black/70"
                    @click="cancelDelete"
                ></div>

                <!-- Modal -->
                <Transition
                    appear
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="scale-95 opacity-0"
                    enter-to-class="scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="scale-100 opacity-100"
                    leave-to-class="scale-95 opacity-0"
                >
                    <div
                        v-if="isConfirmModalOpen"
                        class="relative w-full max-w-md overflow-hidden rounded-2xl border border-[#e3e3e0] bg-white shadow-2xl dark:border-[#3E3E3A] dark:bg-[#161615]"
                    >
                        <!-- Content -->
                        <div class="p-6">
                            <!-- Icon -->
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                    class="h-6 w-6"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 9v3.75m9-3.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"
                                    />
                                </svg>
                            </div>

                            <!-- Title -->
                            <div class="mt-4 text-center">
                                <h3
                                    class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ confirmTitle }}
                                </h3>

                                <p
                                    class="mt-2 text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    {{ confirmMessage }}
                                </p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex flex-col-reverse gap-2 border-t border-[#e3e3e0] bg-[#fafafa] p-4 sm:flex-row sm:justify-end dark:border-[#3E3E3A] dark:bg-[#111110]"
                        >
                            <button
                                type="button"
                                :disabled="isDeleting"
                                @click="cancelDelete"
                                class="w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm font-medium text-[#1b1b18] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                            >
                                Batal
                            </button>

                            <button
                                type="button"
                                :disabled="isDeleting"
                                @click="executeDelete"
                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto dark:bg-red-600 dark:hover:bg-red-500"
                            >
                                <svg
                                    v-if="isDeleting"
                                    class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4Z"
                                    />
                                </svg>

                                {{ isDeleting ? "Menghapus..." : "Ya, Hapus" }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
