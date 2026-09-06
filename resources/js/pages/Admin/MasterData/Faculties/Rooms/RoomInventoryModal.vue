<script setup lang="ts">
import { ref, watch } from "vue";

interface Room {
    id: number;
    code: string;
    name: string;
}

interface MasterItem {
    id: number;
    name: string;
}

interface RoomInventory {
    id?: number;
    room_id: number | string | null;
    item_id: number | string | null;
    asset_code?: string | null;
    condition: "good" | "damaged_light" | "damaged_heavy";
    is_borrowable: boolean;
    notes: string | null;
}

interface InventoryFormData extends Omit<RoomInventory, "id"> {
    quantity?: number;
}

const props = withDefaults(
    defineProps<{
        show: boolean;
        inventory?: RoomInventory | null;
        rooms?: Room[];
        items?: MasterItem[];
        processing?: boolean;
        defaultRoomId?: number | string | null;
    }>(),
    {
        rooms: () => [],
        items: () => [],
        processing: false,
        defaultRoomId: null,
    },
);

const emit = defineEmits<{
    (e: "close"): void;
    (e: "submit", data: InventoryFormData): void;
}>();

const form = ref<InventoryFormData>({
    room_id: "",
    item_id: "",
    quantity: 1,
    asset_code: "",
    condition: "good",
    is_borrowable: true,
    notes: "",
});

watch(
    () => [props.inventory, props.show, props.defaultRoomId] as const,
    ([newInventory, isShown, defaultRoomId]) => {
        if (!isShown) {
            return;
        }

        if (newInventory) {
            form.value = {
                room_id: newInventory.room_id ?? "",
                item_id: newInventory.item_id ?? "",
                quantity: 1,
                asset_code: newInventory.asset_code ?? "",
                condition: newInventory.condition ?? "good",
                is_borrowable:
                    newInventory.is_borrowable ?? true,
                notes: newInventory.notes ?? "",
            };

            return;
        }

        form.value = {
            room_id: defaultRoomId ?? "",
            item_id: "",
            quantity: 1,
            asset_code: "",
            condition: "good",
            is_borrowable: true,
            notes: "",
        };
    },
    {
        immediate: true,
    },
);

const handleSubmit = () => {
    emit("submit", {
        ...form.value,
        room_id: form.value.room_id,
        item_id: form.value.item_id,
        quantity: form.value.quantity ?? 1,
        asset_code: form.value.asset_code ?? "",
        condition: form.value.condition,
        is_borrowable: form.value.is_borrowable,
        notes: form.value.notes ?? null,
    });
};

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
            class="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-2xl border border-black/5 bg-white p-6 shadow-xl dark:border-white/10 dark:bg-[#161615]"
        >
            <div
                class="flex items-center justify-between border-b border-[#e3e3e0] pb-4 dark:border-[#3E3E3A]"
            >
                <h3
                    class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                >
                    {{
                        inventory
                            ? "Edit Aset Inventaris"
                            : "Tambah Aset Inventaris Baru"
                    }}
                </h3>
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
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >Ruangan</label
                    >
                    <select
                        v-model="form.room_id"
                        required
                        :disabled="rooms.length === 0"
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433] dark:disabled:bg-white/5 dark:disabled:text-[#5c5c58]"
                    >
                        <option value="" disabled>
                            {{
                                rooms.length === 0
                                    ? "Belum ada data Ruangan"
                                    : "-- Pilih Ruangan --"
                            }}
                        </option>
                        <option
                            v-for="room in rooms"
                            :key="room.id"
                            :value="room.id"
                        >
                            {{ room.code }} - {{ room.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >Master Barang</label
                    >
                    <select
                        v-model="form.item_id"
                        required
                        :disabled="items.length === 0"
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433] dark:disabled:bg-white/5 dark:disabled:text-[#5c5c58]"
                    >
                        <option value="" disabled>
                            {{
                                items.length === 0
                                    ? "Belum ada data Barang"
                                    : "-- Pilih Master Barang --"
                            }}
                        </option>
                        <option
                            v-for="item in items"
                            :key="item.id"
                            :value="item.id"
                        >
                            {{ item.name }}
                        </option>
                    </select>
                    <p
                        v-if="items.length === 0"
                        class="mt-1 text-[11px] font-medium text-amber-600 dark:text-amber-500"
                    >
                        Silahkan input data Barang terlebih dahulu.
                    </p>
                </div>

                <!-- Input Jumlah Unit (Hanya muncul saat mode Tambah Data baru) -->
                <div v-if="!inventory">
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Jumlah Unit (Quantity)
                    </label>
                    <input
                        v-model.number="form.quantity"
                        type="number"
                        min="1"
                        max="100"
                        required
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    />
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >Kode Aset Fisik
                        <span class="text-[#a1a09a]">(Otomatis)</span></label
                    >
                    <input
                        type="text"
                        disabled
                        :value="
                            inventory
                                ? inventory.asset_code
                                : 'Dibuat otomatis oleh sistem'
                        "
                        class="w-full cursor-not-allowed rounded-lg border border-[#e3e3e0] bg-slate-100 px-3 py-2 text-xs text-slate-400 dark:border-[#3E3E3A] dark:bg-white/5 dark:text-[#5c5c58]"
                    />
                    <p
                        class="mt-1 text-[10px] text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Kode akan digenerate otomatis berdasarkan Ruangan &
                        Barang.
                    </p>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >Kondisi Aset</label
                    >
                    <select
                        v-model="form.condition"
                        required
                        class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    >
                        <option value="good">Baik (Good)</option>
                        <option value="damaged_light">Rusak Ringan</option>
                        <option value="damaged_heavy">Rusak Berat</option>
                    </select>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >Catatan
                        <span class="text-[#a1a09a]">(Opsional)</span></label
                    >
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        placeholder="Contoh: Kabel agak terkelupas"
                        class="w-full resize-none rounded-lg border border-[#e3e3e0] bg-transparent px-3 py-2 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                    ></textarea>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input
                        id="is_borrowable"
                        v-model="form.is_borrowable"
                        type="checkbox"
                        class="h-4 w-4 rounded border-[#e3e3e0] text-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:checked:bg-[#FF4433]"
                    />
                    <label
                        for="is_borrowable"
                        class="cursor-pointer text-xs font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Aset ini dapat dipinjam oleh pengguna
                    </label>
                </div>

                <div
                    class="mt-6 flex items-center justify-end gap-2 border-t border-[#e3e3e0] pt-4 dark:border-[#3E3E3A]"
                >
                    <button
                        type="button"
                        @click="handleClose"
                        :disabled="processing"
                        class="rounded-lg border border-[#e3e3e0] bg-white px-4 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="processing"
                        class="rounded-lg bg-[#f53003] px-4 py-2 text-xs font-medium text-white transition hover:bg-[#d92900] disabled:opacity-50 dark:bg-[#FF4433] dark:hover:bg-[#e03b2b]"
                    >
                        {{ processing ? "Menyimpan..." : "Simpan Data" }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
