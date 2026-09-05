<script setup lang="ts">
import { ref, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";

import {
    update as updateCategory,
    destroy as destroyCategory,
} from "@/actions/App/Http/Controllers/ItemCategoryController";

/*
|--------------------------------------------------------------------------
| Interfaces
|--------------------------------------------------------------------------
*/

interface CategoryRow {
    id: number;
    code: string;
    name: string;
    description: string | null;
    items_count: number;
}

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    categories: CategoryRow[];
}>();

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const searchQuery = ref("");

const isEditModalOpen = ref(false);
const isEditProcessing = ref(false);
const editingCategory = ref<CategoryRow | null>(null);

const editForm = ref({
    code: "",
    name: "",
    description: "",
});

const editErrors = ref<Record<string, string>>({});

const isConfirmModalOpen = ref(false);
const confirmTitle = ref("Konfirmasi Penghapusan");
const confirmMessage = ref("");
const confirmAction = ref<(() => void) | null>(null);
const isDeleting = ref(false);

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const totalCategories = computed(() => props.categories.length);

const totalCategorizedItems = computed(() =>
    props.categories.reduce(
        (total, category) => total + Number(category.items_count || 0),
        0,
    ),
);

const filteredCategories = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    if (!query) {
        return props.categories;
    }

    return props.categories.filter((category) => {
        return (
            category.code.toLowerCase().includes(query) ||
            category.name.toLowerCase().includes(query) ||
            (category.description ?? "").toLowerCase().includes(query)
        );
    });
});

/*
|--------------------------------------------------------------------------
| Edit Category
|--------------------------------------------------------------------------
*/

const openEditModal = (category: CategoryRow) => {
    editingCategory.value = category;

    editForm.value = {
        code: category.code,
        name: category.name,
        description: category.description ?? "",
    };

    editErrors.value = {};
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    if (isEditProcessing.value) {
        return;
    }

    isEditModalOpen.value = false;
    editingCategory.value = null;

    editForm.value = {
        code: "",
        name: "",
        description: "",
    };

    editErrors.value = {};
};

const handleEditSubmit = () => {
    if (!editingCategory.value) {
        return;
    }

    const code = editForm.value.code.trim();
    const name = editForm.value.name.trim();
    const description = editForm.value.description.trim();

    editErrors.value = {};

    if (!code) {
        editErrors.value.code = "Kode kategori wajib diisi.";
    }

    if (!name) {
        editErrors.value.name = "Nama kategori wajib diisi.";
    }

    if (Object.keys(editErrors.value).length > 0) {
        return;
    }

    isEditProcessing.value = true;

    router.put(
        updateCategory.url(editingCategory.value.id),
        {
            code,
            name,
            description: description || null,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                closeEditModal();
            },

            onError: (errors) => {
                editErrors.value = {
                    code: errors.code ?? "",
                    name: errors.name ?? "",
                    description: errors.description ?? "",
                };
            },

            onFinish: () => {
                isEditProcessing.value = false;
            },
        },
    );
};

/*
|--------------------------------------------------------------------------
| Delete Category
|--------------------------------------------------------------------------
*/

const deleteCategory = (category: CategoryRow) => {
    const itemCount = Number(category.items_count || 0);

    confirmTitle.value = "Hapus Kategori?";

    if (itemCount > 0) {
        confirmMessage.value =
            `Kategori "${category.name}" masih digunakan oleh ${itemCount} barang. ` +
            "Menghapus kategori akan membuat kategori pada barang terkait menjadi kosong. " +
            "Data barang tidak akan ikut terhapus. Lanjutkan?";
    } else {
        confirmMessage.value =
            `Kategori "${category.name}" belum digunakan oleh barang. ` +
            "Kategori ini akan dihapus secara permanen. Lanjutkan?";
    }

    confirmAction.value = () => {
        isDeleting.value = true;

        router.delete(destroyCategory.url(category.id), {
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
</script>

<template>
    <Head title="Kategori Barang - Sistem Inventory" />

    <div
        class="relative flex flex-1 flex-col gap-6 overflow-hidden p-4 md:p-6"
    >
        <!-- Animated Background -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div
                class="animate-blob absolute -left-24 -top-24 h-72 w-72 rounded-full bg-[#f53003]/10 blur-3xl dark:bg-[#FF4433]/10 sm:h-96 sm:w-96"
            ></div>

            <div
                class="animate-blob animation-delay-2000 absolute -bottom-24 -right-16 h-72 w-72 rounded-full bg-[#f53003]/5 blur-3xl dark:bg-[#FF4433]/10 sm:h-96 sm:w-96"
            ></div>

            <div
                class="animate-blob animation-delay-4000 absolute left-1/2 top-1/3 h-56 w-56 -translate-x-1/2 rounded-full bg-amber-300/5 blur-3xl dark:bg-amber-500/10 sm:h-72 sm:w-72"
            ></div>
        </div>

        <!-- Content -->
        <div
            class="relative z-10 flex flex-1 flex-col gap-6 opacity-100 transition-opacity duration-750 starting:opacity-0"
        >
            <!-- Breadcrumb -->
            <div
                class="flex items-center gap-2 text-xs text-[#706f6c] dark:text-[#A1A09A]"
            >
                <Link
                    href="/admin/items"
                    class="flex items-center gap-1 font-medium text-[#f53003] transition hover:underline dark:text-[#FF4433]"
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
                            d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
                        />
                    </svg>

                    Master Barang
                </Link>

                <span>/</span>

                <span
                    class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                >
                    Kategori Barang
                </span>
            </div>

            <!-- STATS -->
            <div class="grid gap-4 sm:grid-cols-2">
                <!-- Total Category -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Total Kategori
                        </span>

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
                                    d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 6h.008v.008H6V6Z"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ totalCategories }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Data dari master kategori
                        </p>
                    </div>
                </div>

                <!-- Categorized Items -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Barang Terkategori
                        </span>

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
                                    d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ totalCategorizedItems }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Total barang yang memiliki kategori
                        </p>
                    </div>
                </div>
            </div>

            <!-- INFO -->
            <div
                class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 text-xs text-blue-800 dark:border-blue-900/40 dark:bg-blue-950/20 dark:text-blue-400"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="mt-0.5 h-4 w-4 flex-shrink-0"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0zm-9-3.75h.008v.008H12V8.25z"
                    />
                </svg>

                <p>
                    Kategori merupakan
                    <strong>master data tersendiri</strong>. Barang akan
                    menggunakan kategori melalui relasi
                    <code class="font-semibold">category_id</code>.
                    Pengubahan nama kategori di sini akan otomatis berlaku
                    untuk barang yang menggunakan kategori tersebut.
                </p>
            </div>

            <!-- MAIN CONTENT -->
            <div
                class="flex flex-1 flex-col rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]"
            >
                <!-- Header -->
                <div
                    class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2
                            class="text-lg font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Daftar Kategori Barang
                        </h2>

                        <p
                            class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Kelola master kategori yang digunakan oleh Barang.
                        </p>
                    </div>

                    <!-- Search -->
                    <div class="relative w-full sm:w-80">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari kode, nama kategori..."
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
                </div>

                <!-- TABLE -->
                <div
                    v-if="filteredCategories.length > 0"
                    class="overflow-x-auto rounded-xl border border-[#e3e3e0] dark:border-[#3E3E3A]"
                >
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr
                                class="border-b border-[#e3e3e0] bg-[#FDFDFC] text-[#706f6c] dark:border-[#3E3E3A] dark:bg-[#0a0a0a] dark:text-[#A1A09A]"
                            >
                                <th
                                    class="px-4 py-3 font-medium uppercase tracking-wider"
                                >
                                    Kode
                                </th>

                                <th
                                    class="px-4 py-3 font-medium uppercase tracking-wider"
                                >
                                    Nama Kategori
                                </th>

                                <th
                                    class="px-4 py-3 font-medium uppercase tracking-wider"
                                >
                                    Deskripsi
                                </th>

                                <th
                                    class="px-4 py-3 font-medium uppercase tracking-wider"
                                >
                                    Jumlah Barang
                                </th>

                                <th
                                    class="px-4 py-3 text-right font-medium uppercase tracking-wider"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-[#e3e3e0]/50 dark:divide-[#3E3E3A]/50"
                        >
                            <tr
                                v-for="category in filteredCategories"
                                :key="category.id"
                                class="text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                <!-- Code -->
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-md bg-slate-100 px-2 py-1 font-mono text-[10px] font-semibold text-slate-700 dark:bg-[#20201e] dark:text-[#D1D1CC]"
                                    >
                                        {{ category.code }}
                                    </span>
                                </td>

                                <!-- Name -->
                                <td class="px-4 py-3 font-medium">
                                    {{ category.name }}
                                </td>

                                <!-- Description -->
                                <td
                                    class="max-w-xs px-4 py-3 text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    <span
                                        v-if="category.description"
                                        class="line-clamp-2"
                                    >
                                        {{ category.description }}
                                    </span>

                                    <span
                                        v-else
                                        class="italic text-[#a1a09a]"
                                    >
                                        Tidak ada deskripsi
                                    </span>
                                </td>

                                <!-- Items Count -->
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded bg-[#fff2f2] px-1.5 py-0.5 text-[10px] font-semibold text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
                                    >
                                        {{ category.items_count }} barang
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3 text-right">
                                    <div
                                        class="flex items-center justify-end gap-1.5"
                                    >
                                        <!-- Edit -->
                                        <button
                                            type="button"
                                            @click="openEditModal(category)"
                                            class="rounded-md p-1.5 text-[#706f6c] transition hover:bg-slate-100 hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#20201e] dark:hover:text-[#EDEDEC]"
                                            title="Edit Kategori"
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

                                        <!-- Delete -->
                                        <button
                                            type="button"
                                            @click="deleteCategory(category)"
                                            class="rounded-md p-1.5 text-[#706f6c] transition hover:bg-red-50 hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:bg-red-950/30 dark:hover:text-[#FF4433]"
                                            title="Hapus Kategori"
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

                <!-- Empty -->
                <div
                    v-else
                    class="rounded-xl border border-dashed border-[#e3e3e0] py-10 text-center text-xs text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]"
                >
                    <p v-if="props.categories.length === 0">
                        Belum ada data kategori barang.
                    </p>

                    <p v-else>
                        Tidak ada kategori yang cocok dengan pencarian saat
                        ini.
                    </p>
                </div>
            </div>
        </div>

        <!-- ================================================================ -->
        <!-- EDIT CATEGORY MODAL -->
        <!-- ================================================================ -->

        <div
            v-if="isEditModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
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
                            Edit Kategori
                        </h3>

                        <p
                            class="mt-0.5 text-[11px] text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Perbarui informasi master kategori.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="closeEditModal"
                        :disabled="isEditProcessing"
                        class="rounded-lg p-1 text-[#706f6c] hover:bg-slate-100 hover:text-[#1b1b18] disabled:opacity-50 dark:text-[#A1A09A] dark:hover:bg-[#20201e] dark:hover:text-[#EDEDEC]"
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

                <!-- Form -->
                <form
                    @submit.prevent="handleEditSubmit"
                    class="mt-5 space-y-4"
                >
                    <!-- Code -->
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Kode Kategori
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            v-model="editForm.code"
                            type="text"
                            required
                            maxlength="255"
                            placeholder="Contoh: ELK"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />

                        <p
                            v-if="editErrors.code"
                            class="mt-1 text-[11px] font-medium text-red-600 dark:text-red-400"
                        >
                            {{ editErrors.code }}
                        </p>
                    </div>

                    <!-- Name -->
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Nama Kategori
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            maxlength="255"
                            placeholder="Contoh: Elektronik"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />

                        <p
                            v-if="editErrors.name"
                            class="mt-1 text-[11px] font-medium text-red-600 dark:text-red-400"
                        >
                            {{ editErrors.name }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Deskripsi
                        </label>

                        <textarea
                            v-model="editForm.description"
                            rows="4"
                            placeholder="Deskripsi kategori..."
                            class="w-full resize-none rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        ></textarea>

                        <p
                            v-if="editErrors.description"
                            class="mt-1 text-[11px] font-medium text-red-600 dark:text-red-400"
                        >
                            {{ editErrors.description }}
                        </p>
                    </div>

                    <!-- Footer -->
                    <div
                        class="mt-6 flex items-center justify-end gap-2 border-t border-[#e3e3e0] pt-4 dark:border-[#3E3E3A]"
                    >
                        <button
                            type="button"
                            @click="closeEditModal"
                            :disabled="isEditProcessing"
                            class="rounded-lg border border-[#e3e3e0] bg-white px-4 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            :disabled="isEditProcessing"
                            class="rounded-lg bg-[#f53003] px-4 py-2 text-xs font-medium text-white transition hover:bg-[#d92900] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#FF4433] dark:hover:bg-[#e03b2b]"
                        >
                            {{
                                isEditProcessing
                                    ? "Menyimpan..."
                                    : "Simpan Perubahan"
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================================================================ -->
        <!-- DELETE CONFIRMATION MODAL -->
        <!-- ================================================================ -->

        <div
            v-if="isConfirmModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-sm rounded-2xl border border-black/5 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-[#161615]"
            >
                <!-- Icon -->
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-950/40 dark:text-red-400"
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
                            d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                        />
                    </svg>
                </div>

                <h3
                    class="mt-4 text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                >
                    {{ confirmTitle }}
                </h3>

                <p
                    class="mt-2 text-xs leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                >
                    {{ confirmMessage }}
                </p>

                <div class="mt-6 flex justify-end gap-2">
                    <button
                        type="button"
                        @click="cancelDelete"
                        :disabled="isDeleting"
                        class="rounded-lg border border-[#e3e3e0] bg-white px-3 py-1.5 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        @click="executeDelete"
                        :disabled="isDeleting"
                        class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ isDeleting ? "Menghapus..." : "Ya, Hapus" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes blob {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }

    33% {
        transform: translate(20px, -30px) scale(1.1);
    }

    66% {
        transform: translate(-15px, 15px) scale(0.95);
    }
}

.animate-blob {
    animation: blob 10s infinite ease-in-out;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

@media (prefers-reduced-motion: reduce) {
    .animate-blob {
        animation: none;
    }
}
</style>
