<script setup lang="ts">
import { computed, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";

import {
    store as storeItem,
    update as updateItem,
    destroy as destroyItem,
} from "@/actions/App/Http/Controllers/ItemController";

/*
|--------------------------------------------------------------------------
| Interfaces
|--------------------------------------------------------------------------
*/

interface Item {
    id: number;
    name: string;
    category: string | null;
    description: string | null;
    room_inventories_count?: number;
    created_at?: string;
    updated_at?: string;
}

interface CategoryRow {
    category: string;
    items_count?: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedItems {
    data: Item[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    items: PaginatedItems;
    categories?: CategoryRow[];
}>();

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

const searchQuery = ref("");

/*
|--------------------------------------------------------------------------
| Add / Edit Modal
|--------------------------------------------------------------------------
*/

const isFormModalOpen = ref(false);
const isFormProcessing = ref(false);
const formError = ref<string | null>(null);

const editingItem = ref<Item | null>(null);

const form = ref({
    name: "",
    category: "",
    description: "",
});

/*
|--------------------------------------------------------------------------
| Delete Modal
|--------------------------------------------------------------------------
*/

const isDeleteModalOpen = ref(false);
const isDeleting = ref(false);

const deletingItem = ref<Item | null>(null);
const deleteError = ref<string | null>(null);

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const totalItems = computed(() => {
    return props.items?.total ?? 0;
});

const totalCategories = computed(() => {
    return props.categories?.length ?? 0;
});

const totalAssets = computed(() => {
    return (
        props.items?.data?.reduce(
            (total, item) =>
                total + Number(item.room_inventories_count || 0),
            0,
        ) ?? 0
    );
});

const filteredItems = computed(() => {
    const items = props.items?.data ?? [];
    const query = searchQuery.value.trim().toLowerCase();

    if (!query) {
        return items;
    }

    return items.filter((item) => {
        return (
            item.name.toLowerCase().includes(query) ||
            (item.category ?? "").toLowerCase().includes(query) ||
            (item.description ?? "").toLowerCase().includes(query)
        );
    });
});

/*
|--------------------------------------------------------------------------
| Open Add Modal
|--------------------------------------------------------------------------
*/

const openCreateModal = () => {
    editingItem.value = null;

    form.value = {
        name: "",
        category: "",
        description: "",
    };

    formError.value = null;
    isFormModalOpen.value = true;
};

/*
|--------------------------------------------------------------------------
| Open Edit Modal
|--------------------------------------------------------------------------
*/

const openEditModal = (item: Item) => {
    editingItem.value = { ...item };

    form.value = {
        name: item.name ?? "",
        category: item.category ?? "",
        description: item.description ?? "",
    };

    formError.value = null;
    isFormModalOpen.value = true;
};

/*
|--------------------------------------------------------------------------
| Close Form Modal
|--------------------------------------------------------------------------
*/

const closeFormModal = () => {
    if (isFormProcessing.value) {
        return;
    }

    isFormModalOpen.value = false;
    editingItem.value = null;

    form.value = {
        name: "",
        category: "",
        description: "",
    };

    formError.value = null;
};

/*
|--------------------------------------------------------------------------
| Submit Add / Edit
|--------------------------------------------------------------------------
*/

const submitForm = () => {
    formError.value = null;

    const name = form.value.name.trim();
    const category = form.value.category.trim();
    const description = form.value.description.trim();

    if (!name) {
        formError.value = "Nama barang wajib diisi.";
        return;
    }

    if (name.length > 255) {
        formError.value =
            "Nama barang tidak boleh lebih dari 255 karakter.";
        return;
    }

    if (category.length > 255) {
        formError.value =
            "Kategori tidak boleh lebih dari 255 karakter.";
        return;
    }

    isFormProcessing.value = true;

    const payload = {
        name,
        category: category || null,
        description: description || null,
    };

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    if (!editingItem.value) {
        router.post(storeItem.url(), payload, {
            preserveScroll: true,

            onSuccess: () => {
                closeFormModal();
            },

            onError: (errors) => {
                formError.value =
                    errors.name ??
                    errors.category ??
                    errors.description ??
                    "Gagal menambahkan barang.";
            },

            onFinish: () => {
                isFormProcessing.value = false;
            },
        });

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    router.put(
        updateItem.url(editingItem.value.id),
        payload,
        {
            preserveScroll: true,

            onSuccess: () => {
                closeFormModal();
            },

            onError: (errors) => {
                formError.value =
                    errors.name ??
                    errors.category ??
                    errors.description ??
                    "Gagal memperbarui barang.";
            },

            onFinish: () => {
                isFormProcessing.value = false;
            },
        },
    );
};

/*
|--------------------------------------------------------------------------
| Open Delete Modal
|--------------------------------------------------------------------------
*/

const openDeleteModal = (item: Item) => {
    deletingItem.value = { ...item };
    deleteError.value = null;
    isDeleteModalOpen.value = true;
};

/*
|--------------------------------------------------------------------------
| Close Delete Modal
|--------------------------------------------------------------------------
*/

const closeDeleteModal = () => {
    if (isDeleting.value) {
        return;
    }

    isDeleteModalOpen.value = false;
    deletingItem.value = null;
    deleteError.value = null;
};

/*
|--------------------------------------------------------------------------
| Execute Delete
|--------------------------------------------------------------------------
*/

const executeDelete = () => {
    if (!deletingItem.value || isDeleting.value) {
        return;
    }

    deleteError.value = null;
    isDeleting.value = true;

    router.delete(destroyItem.url(deletingItem.value.id), {
        preserveScroll: true,

        onError: (errors) => {
            deleteError.value =
                errors.message ??
                "Barang gagal dihapus.";
        },

        onFinish: () => {
            isDeleting.value = false;
            isDeleteModalOpen.value = false;
            deletingItem.value = null;
        },
    });
};

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

const goToPage = (url: string | null) => {
    if (!url) {
        return;
    }

    router.visit(url, {
        preserveScroll: true,
        preserveState: true,
    });
};

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const getCategoryClass = (category: string | null) => {
    if (!category) {
        return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-400";
    }

    return "bg-[#fff2f2] text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]";
};
</script>

<template>
    <Head title="Master Barang - Sistem Inventory" />

    <div
        class="relative flex flex-1 flex-col gap-6 overflow-hidden p-4 md:p-6"
    >
        <!-- Decorative Background -->
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

        <!-- Main Content -->
        <div
            class="relative z-10 flex flex-1 flex-col gap-6 opacity-100 transition-opacity duration-750 starting:opacity-0"
        >
            <!-- Breadcrumb -->
            <div
                class="flex items-center gap-2 text-xs text-[#706f6c] dark:text-[#A1A09A]"
            >
                <span
                    class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                >
                    Master Barang
                </span>
            </div>

            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="text-2xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Master Barang
                    </h1>

                    <p
                        class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Kelola data master barang yang digunakan dalam sistem
                        inventaris.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Category -->
                    <Link
                        href="/admin/categories"
                        class="flex w-fit items-center gap-2 rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
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
                                d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"
                            />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 6h.008v.008H6V6Z"
                            />
                        </svg>

                        Kategori
                    </Link>

                    <!-- Add -->
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="flex w-fit items-center gap-2 rounded-lg bg-[#f53003] px-4 py-2.5 text-xs font-medium text-white shadow-sm transition hover:bg-[#d92900] dark:bg-[#FF4433] dark:hover:bg-[#e03b2b]"
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
                                d="M12 4.5v15m7.5-7.5h-15"
                            />
                        </svg>

                        Tambah Barang
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 sm:grid-cols-3">
                <!-- Total Barang -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Total Barang
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
                                    d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25M21 7.5v9l-9 5.25M3 7.5v9l9 5.25M3 7.5l9 5.25m0 0 9-5.25"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ totalItems }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Master barang terdaftar
                        </p>
                    </div>
                </div>

                <!-- Category -->
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
                            Kategori barang
                        </p>
                    </div>
                </div>

                <!-- Assets -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Digunakan Aset
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
                                    d="M3.75 7.5h16.5M5.25 7.5A2.25 2.25 0 0 0 3 9.75v8.25a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 18V9.75a2.25 2.25 0 0 0-2.25-2.25M7.5 7.5V5.25A2.25 2.25 0 0 1 9.75 3h4.5a2.25 2.25 0 0 1 2.25 2.25V7.5"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ totalAssets }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Penggunaan pada inventaris
                        </p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div
                class="flex flex-1 flex-col rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]"
            >
                <!-- Toolbar -->
                <div
                    class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2
                            class="text-lg font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Daftar Barang
                        </h2>

                        <p
                            class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Kelola seluruh master barang.
                        </p>
                    </div>

                    <div class="relative w-full sm:w-80">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama, kategori, deskripsi..."
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

                <!-- Table -->
                <div
                    v-if="filteredItems.length > 0"
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
                                    Barang
                                </th>

                                <th
                                    class="px-4 py-3 font-medium uppercase tracking-wider"
                                >
                                    Kategori
                                </th>

                                <th
                                    class="px-4 py-3 font-medium uppercase tracking-wider"
                                >
                                    Deskripsi
                                </th>

                                <th
                                    class="px-4 py-3 font-medium uppercase tracking-wider"
                                >
                                    Digunakan
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
                                v-for="item in filteredItems"
                                :key="item.id"
                                class="text-[#1b1b18] transition hover:bg-slate-50 dark:text-[#EDEDEC] dark:hover:bg-white/5"
                            >
                                <!-- Name -->
                                <td class="px-4 py-3">
                                    <div class="font-medium">
                                        {{ item.name }}
                                    </div>

                                    <div
                                        class="mt-0.5 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        ID #{{ item.id }}
                                    </div>
                                </td>

                                <!-- Category -->
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded px-2 py-1 text-[10px] font-semibold"
                                        :class="
                                            getCategoryClass(item.category)
                                        "
                                    >
                                        {{
                                            item.category ||
                                            "Tanpa kategori"
                                        }}
                                    </span>
                                </td>

                                <!-- Description -->
                                <td class="max-w-xs px-4 py-3">
                                    <span
                                        v-if="item.description"
                                        class="line-clamp-2 text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        {{ item.description }}
                                    </span>

                                    <span
                                        v-else
                                        class="italic text-[#a1a09a]"
                                    >
                                        Tidak ada deskripsi
                                    </span>
                                </td>

                                <!-- Usage -->
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-600 dark:bg-white/10 dark:text-slate-300"
                                    >
                                        {{
                                            item.room_inventories_count ?? 0
                                        }}
                                        aset
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
                                            @click="openEditModal(item)"
                                            class="rounded p-1.5 text-[#706f6c] transition hover:bg-slate-100 hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-white/10 dark:hover:text-[#EDEDEC]"
                                            title="Edit Barang"
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

                                        <!-- Delete -->
                                        <button
                                            type="button"
                                            @click="openDeleteModal(item)"
                                            class="rounded p-1.5 text-[#706f6c] transition hover:bg-red-50 hover:text-red-600 dark:text-[#A1A09A] dark:hover:bg-red-950/30 dark:hover:text-red-400"
                                            title="Hapus Barang"
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
                                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m0 0a48.11 48.11 0 0 0-7.5 0m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0C9.91 2.747 9 3.731 9 4.911v.916m0 0H4.772"
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
                    class="rounded-xl border border-dashed border-[#e3e3e0] py-12 text-center text-xs text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]"
                >
                    <div
                        class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-[#20201e]"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-6 w-6"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621.504-1.125 1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"
                            />
                        </svg>
                    </div>

                    <p v-if="props.items.total === 0">
                        Belum ada master barang.
                    </p>

                    <p v-else>
                        Tidak ada barang yang cocok dengan pencarian.
                    </p>
                </div>

                <!-- Pagination -->
                <div
                    v-if="props.items.last_page > 1"
                    class="mt-6 flex flex-col gap-3 border-t border-[#e3e3e0] pt-4 dark:border-[#3E3E3A] sm:flex-row sm:items-center sm:justify-between"
                >
                    <p
                        class="text-[11px] text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Menampilkan
                        <strong class="text-[#1b1b18] dark:text-[#EDEDEC]">
                            {{ props.items.from ?? 0 }}
                        </strong>
                        -
                        <strong class="text-[#1b1b18] dark:text-[#EDEDEC]">
                            {{ props.items.to ?? 0 }}
                        </strong>
                        dari
                        <strong class="text-[#1b1b18] dark:text-[#EDEDEC]">
                            {{ props.items.total }}
                        </strong>
                        barang
                    </p>

                    <div class="flex items-center gap-1">
                        <button
                            v-for="(link, index) in props.items.links"
                            :key="index"
                            type="button"
                            :disabled="!link.url"
                            @click="goToPage(link.url)"
                            class="min-w-8 rounded-lg px-2.5 py-1.5 text-[11px] transition"
                            :class="[
                                link.active
                                    ? 'bg-[#f53003] font-semibold text-white dark:bg-[#FF4433]'
                                    : 'text-[#706f6c] hover:bg-slate-100 dark:text-[#A1A09A] dark:hover:bg-white/10',
                                !link.url
                                    ? 'cursor-not-allowed opacity-40'
                                    : '',
                            ]"
                            v-html="link.label"
                        ></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- ADD / EDIT ITEM MODAL -->
        <!-- ============================================================= -->

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
                    v-if="isFormModalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                >
                    <div
                        class="w-full max-w-lg overflow-hidden rounded-2xl border border-black/10 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-[#161615]"
                    >
                        <div class="flex items-start gap-4">
                            <!-- Icon -->
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#fff2f2] text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
                            >
                                <svg
                                    v-if="!editingItem"
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

                                <svg
                                    v-else
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
                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"
                                    />
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex items-center justify-between"
                                >
                                    <div>
                                        <h3
                                            class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{
                                                editingItem
                                                    ? "Edit Barang"
                                                    : "Tambah Barang"
                                            }}
                                        </h3>

                                        <p
                                            class="mt-0.5 text-[11px] text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            {{
                                                editingItem
                                                    ? "Perbarui informasi master barang."
                                                    : "Tambahkan barang baru ke master."
                                            }}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        @click="closeFormModal"
                                        :disabled="isFormProcessing"
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
                                    @submit.prevent="submitForm"
                                    class="mt-5 space-y-4"
                                >
                                    <!-- Name -->
                                    <div>
                                        <label
                                            class="mb-1.5 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            Nama Barang
                                            <span class="text-red-500">*</span>
                                        </label>

                                        <input
                                            v-model="form.name"
                                            type="text"
                                            required
                                            maxlength="255"
                                            :disabled="isFormProcessing"
                                            autocomplete="off"
                                            placeholder="Contoh: Meja Dosen"
                                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2.5 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:bg-slate-100 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433] dark:disabled:bg-white/5"
                                        />
                                    </div>

                                    <!-- Category -->
                                    <div>
                                        <label
                                            class="mb-1.5 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            Kategori
                                        </label>

                                        <input
                                            v-model="form.category"
                                            type="text"
                                            maxlength="255"
                                            :disabled="isFormProcessing"
                                            autocomplete="off"
                                            placeholder="Contoh: Mebel"
                                            list="item-categories"
                                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2.5 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:bg-slate-100 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433] dark:disabled:bg-white/5"
                                        />

                                        <datalist id="item-categories">
                                            <option
                                                v-for="category in props.categories"
                                                :key="category.category"
                                                :value="category.category"
                                            />
                                        </datalist>

                                        <p
                                            class="mt-1 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            Contoh: Mebel, Elektronik, ATK.
                                        </p>
                                    </div>

                                    <!-- Description -->
                                    <div>
                                        <label
                                            class="mb-1.5 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            Deskripsi
                                        </label>

                                        <textarea
                                            v-model="form.description"
                                            rows="4"
                                            :disabled="isFormProcessing"
                                            placeholder="Tambahkan deskripsi barang..."
                                            class="w-full resize-none rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2.5 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:bg-slate-100 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433] dark:disabled:bg-white/5"
                                        ></textarea>
                                    </div>

                                    <!-- Error -->
                                    <div
                                        v-if="formError"
                                        class="rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-[11px] font-medium text-red-600 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-400"
                                    >
                                        {{ formError }}
                                    </div>

                                    <!-- Actions -->
                                    <div
                                        class="flex items-center justify-end gap-3 border-t border-[#e3e3e0] pt-4 dark:border-[#3E3E3A]"
                                    >
                                        <button
                                            type="button"
                                            @click="closeFormModal"
                                            :disabled="isFormProcessing"
                                            class="rounded-lg border border-[#e3e3e0] bg-white px-4 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                                        >
                                            Batal
                                        </button>

                                        <button
                                            type="submit"
                                            :disabled="
                                                isFormProcessing ||
                                                !form.name.trim()
                                            "
                                            class="flex items-center gap-2 rounded-lg bg-[#f53003] px-4 py-2 text-xs font-medium text-white transition hover:bg-[#d92900] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#FF4433] dark:hover:bg-[#e03b2b]"
                                        >
                                            <svg
                                                v-if="isFormProcessing"
                                                class="h-3.5 w-3.5 animate-spin"
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
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                />
                                            </svg>

                                            {{
                                                isFormProcessing
                                                    ? "Menyimpan..."
                                                    : editingItem
                                                      ? "Simpan Perubahan"
                                                      : "Tambah Barang"
                                            }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ============================================================= -->
        <!-- DELETE MODAL -->
        <!-- ============================================================= -->

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
                    v-if="isDeleteModalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                >
                    <div
                        class="w-full max-w-md overflow-hidden rounded-2xl border border-black/10 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-[#161615]"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-950/50 dark:text-red-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="h-6 w-6"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
                                    />
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1">
                                <h3
                                    class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Hapus Barang?
                                </h3>

                                <p
                                    class="mt-2 text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Barang

                                    <strong
                                        class="text-[#1b1b18] dark:text-[#EDEDEC]"
                                    >
                                        "{{ deletingItem?.name }}"
                                    </strong>

                                    akan dihapus dari master barang.
                                </p>

                                <!-- Warning if used -->
                                <div
                                    v-if="
                                        (deletingItem?.room_inventories_count ??
                                            0) > 0
                                    "
                                    class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-[11px] leading-5 text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-400"
                                >
                                    <strong>Tidak dapat dihapus.</strong>

                                    Barang ini masih digunakan pada

                                    <strong>
                                        {{
                                            deletingItem?.room_inventories_count
                                        }}
                                    </strong>

                                    aset inventaris. Hapus atau lepaskan
                                    penggunaan aset tersebut terlebih dahulu.
                                </div>

                                <div
                                    v-if="deleteError"
                                    class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-[11px] font-medium text-red-600 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-400"
                                >
                                    {{ deleteError }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <button
                                type="button"
                                @click="closeDeleteModal"
                                :disabled="isDeleting"
                                class="rounded-lg border border-[#e3e3e0] bg-white px-4 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                            >
                                Batal
                            </button>

                            <button
                                type="button"
                                @click="executeDelete"
                                :disabled="
                                    isDeleting ||
                                    (deletingItem?.room_inventories_count ??
                                        0) > 0
                                "
                                class="flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-xs font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-red-500 dark:hover:bg-red-600"
                            >
                                <svg
                                    v-if="isDeleting"
                                    class="h-3.5 w-3.5 animate-spin"
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
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    />
                                </svg>

                                <span>
                                    {{
                                        isDeleting
                                            ? "Menghapus..."
                                            : "Ya, Hapus"
                                    }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
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
