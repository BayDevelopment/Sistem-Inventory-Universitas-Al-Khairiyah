<script setup lang="ts">
import { ref, watch } from "vue";

interface RoomType {
    id?: number;
    name: string;
    slug: string;
    description?: string | null;
}

type RoomTypeFormData = Omit<RoomType, "id">;

const props = defineProps<{
    show: boolean;
    roomType?: RoomType | null;
    processing?: boolean;
    errors?: Record<string, any>;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "submit", data: RoomTypeFormData): void;
}>();

const form = ref<RoomTypeFormData>({
    name: "",
    slug: "",
    description: "",
});

watch(
    () => props.roomType,
    (roomType) => {
        if (roomType) {
            form.value = {
                name: roomType.name ?? "",
                slug: roomType.slug ?? "",
                description: roomType.description ?? "",
            };

            return;
        }

        form.value = {
            name: "",
            slug: "",
            description: "",
        };
    },
    {
        immediate: true,
    },
);



const generateSlug = () => {
    form.value.slug = form.value.name
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, "")
        .replace(/\s+/g, "-")
        .replace(/-+/g, "-");
};



const handleSubmit = () => {
    if (!form.value.name.trim()) {
        return;
    }

    if (!form.value.slug.trim()) {
        generateSlug();
    }

    emit("submit", {
        name: form.value.name.trim(),
        slug: form.value.slug.trim(),
        description: form.value.description?.trim() || null,
    });
};



const handleClose = () => {
    if (props.processing) return;

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
                        Edit Jenis Ruangan
                    </h3>

                    <p
                        class="mt-0.5 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Perbarui informasi jenis ruangan
                    </p>
                </div>

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

            <!-- Form -->
            <form
                @submit.prevent="handleSubmit"
                class="mt-4 space-y-4"
            >
                <!-- Nama -->
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Nama Jenis Ruangan
                    </label>

                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Contoh: Kelas, Laboratorium, Ruang Dosen"
                        required
                        @blur="!roomType && generateSlug()"
                        class="w-full rounded-lg border bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:outline-none focus:ring-1 dark:text-[#EDEDEC]"
                        :class="
                            errors?.name
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                                : 'border-[#e3e3e0] focus:border-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]'
                        "
                    />

                    <p
                        v-if="errors?.name"
                        class="mt-1 text-[11px] text-red-500 dark:text-red-400"
                    >
                        {{ errors.name }}
                    </p>
                </div>

                <!-- Slug -->
                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <label
                            class="block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Slug
                        </label>

                        <button
                            type="button"
                            @click="generateSlug"
                            :disabled="!form.name.trim() || processing"
                            class="text-[10px] font-medium text-[#f53003] transition hover:underline disabled:cursor-not-allowed disabled:opacity-50 dark:text-[#FF4433]"
                        >
                            Generate otomatis
                        </button>
                    </div>

                    <input
                        v-model="form.slug"
                        type="text"
                        placeholder="Contoh: lab-komputer"
                        required
                        class="w-full rounded-lg border bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:outline-none focus:ring-1 dark:text-[#EDEDEC]"
                        :class="
                            errors?.slug
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                                : 'border-[#e3e3e0] focus:border-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]'
                        "
                    />

                    <p
                        v-if="errors?.slug"
                        class="mt-1 text-[11px] text-red-500 dark:text-red-400"
                    >
                        {{ errors.slug }}
                    </p>

                    <p
                        v-else
                        class="mt-1 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Slug digunakan sebagai identitas URL/internal jenis
                        ruangan.
                    </p>
                </div>

                <!-- Deskripsi -->
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Deskripsi
                    </label>

                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Contoh: Ruangan yang digunakan untuk kegiatan perkuliahan..."
                        class="w-full resize-none rounded-lg border bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:outline-none focus:ring-1 dark:text-[#EDEDEC]"
                        :class="
                            errors?.description
                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                                : 'border-[#e3e3e0] focus:border-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]'
                        "
                    ></textarea>

                    <p
                        v-if="errors?.description"
                        class="mt-1 text-[11px] text-red-500 dark:text-red-400"
                    >
                        {{ errors.description }}
                    </p>
                </div>

                <!-- Actions -->
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
                        class="rounded-lg bg-[#f53003] px-4 py-2 text-xs font-medium text-white transition hover:bg-[#d92900] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#FF4433] dark:hover:bg-[#e03b2b]"
                    >
                        {{
                            processing
                                ? "Menyimpan..."
                                : "Simpan Perubahan"
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>