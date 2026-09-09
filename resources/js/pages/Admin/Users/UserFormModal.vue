<script setup lang="ts">
import { computed, nextTick, reactive, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import {
    X,
    Loader2,
    UserPlus,
    Pencil,
    Lock,
    Eye,
    EyeOff,
} from "lucide-vue-next";

interface Faculty {
    id: number;
    name: string;
    code?: string | null;
}

interface User {
    id: number;
    name: string;
    position?: string | null;
    email: string;
    role:
        | "super_admin"
        | "admin_fakultas"
        | "sdm"
        | "dosen"
        | "mahasiswa";
    nip?: string | null;
    faculty_id?: number | null;
    department?: string | null;
    phone_number?: string | null;
    status: "active" | "pending" | "suspended";
}

const props = defineProps<{
    show: boolean;
    user?: User | null;
    faculties?: Faculty[];
}>();

const emit = defineEmits<{
    (event: "close"): void;
    (event: "saved", user: User): void;
}>();

const roleOptions = [
    { value: "super_admin", label: "Super Admin" },
    { value: "admin_fakultas", label: "Admin Fakultas" },
    { value: "sdm", label: "SDM" },
    { value: "dosen", label: "Dosen" },
    { value: "mahasiswa", label: "Mahasiswa" },
];

const statusOptions = [
    { value: "active", label: "Aktif" },
    { value: "pending", label: "Pending" },
    { value: "suspended", label: "Suspended" },
];

const isEdit = computed(() => Boolean(props.user?.id));

type FormState = {
    name: string;
    email: string;
    position: string;
    role: User["role"] | "";
    nip: string;
    faculty_id: string;
    department: string;
    phone_number: string;
    status: User["status"];
    password: string;
    password_confirmation: string;
};

const emptyForm = (): FormState => ({
    name: "",
    email: "",
    position: "",
    role: "",
    nip: "",
    faculty_id: "",
    department: "",
    phone_number: "",
    status: "active",
    password: "",
    password_confirmation: "",
});

const form = reactive<FormState>(emptyForm());
const errors = ref<Record<string, string>>({});
const processing = ref(false);
const changePassword = ref(false);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);
const nameInput = ref<HTMLInputElement | null>(null);

const resetForm = () => {
    errors.value = {};
    changePassword.value = !isEdit.value;
    showPassword.value = false;
    showPasswordConfirmation.value = false;

    if (props.user) {
        Object.assign(form, {
            name: props.user.name ?? "",
            email: props.user.email ?? "",
            position: props.user.position ?? "",
            role: props.user.role,
            nip: props.user.nip ?? "",
            faculty_id:
                props.user.faculty_id !== null &&
                props.user.faculty_id !== undefined
                    ? String(props.user.faculty_id)
                    : "",
            department: props.user.department ?? "",
            phone_number: props.user.phone_number ?? "",
            status: props.user.status,
            password: "",
            password_confirmation: "",
        });
    } else {
        Object.assign(form, emptyForm());
    }
};

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            resetForm();
            nextTick(() => nameInput.value?.focus());
        }
    },
    { immediate: true },
);

const requiresFacultyContext = computed(() =>
    ["admin_fakultas", "dosen", "mahasiswa"].includes(form.role),
);

const close = () => {
    if (processing.value) return;
    emit("close");
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === "Escape") close();
};

const submit = () => {
    if (processing.value) return;

    processing.value = true;
    errors.value = {};

    const basePayload = {
        name: form.name,
        email: form.email,
        position: form.position || null,
        role: form.role,
        nip: form.nip || null,
        faculty_id: form.faculty_id ? Number(form.faculty_id) : null,
        department: form.department || null,
        phone_number: form.phone_number || null,
        status: form.status,
    };

    const payload = changePassword.value
        ? {
              ...basePayload,
              password: form.password,
              password_confirmation: form.password_confirmation,
          }
        : basePayload;

    const onDone = {
        preserveScroll: true,
        onSuccess: () => {
            processing.value = false;
            emit("saved", { ...(props.user ?? {}), ...basePayload } as User);
        },
        onError: (responseErrors: Record<string, string>) => {
            processing.value = false;
            errors.value = responseErrors;
        },
    };

    if (isEdit.value) {
        router.put(`/admin/users/${props.user!.id}`, payload, onDone);
    } else {
        router.post("/admin/users", payload, onDone);
    }
};
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="show"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            :aria-label="isEdit ? 'Edit Pengguna' : 'Tambah Pengguna'"
            @keydown="onKeydown"
            @click.self="close"
        >
            <div
                class="absolute inset-0 bg-black/50 backdrop-blur-sm dark:bg-black/70"
            ></div>

            <Transition
                appear
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="scale-95 opacity-0"
                enter-to-class="scale-100 opacity-100"
            >
                <div
                    v-if="show"
                    class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-[#e3e3e0] bg-white shadow-2xl dark:border-[#3E3E3A] dark:bg-[#161615]"
                >
                    <div class="h-1 bg-[#f53003] dark:bg-[#FF4433]"></div>

                    <div
                        class="flex items-center justify-between border-b border-[#e3e3e0] px-6 py-4 dark:border-[#3E3E3A]"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff2f2] text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
                            >
                                <component
                                    :is="isEdit ? Pencil : UserPlus"
                                    class="h-4 w-4"
                                />
                            </div>

                            <h2
                                class="text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{
                                    isEdit
                                        ? "Edit Pengguna"
                                        : "Tambah Pengguna Baru"
                                }}
                            </h2>
                        </div>

                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-[#706f6c] transition hover:bg-slate-100 dark:text-[#A1A09A] dark:hover:bg-[#20201e]"
                            :disabled="processing"
                            @click="close"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <form
                        id="user-form"
                        class="flex-1 overflow-y-auto px-6 py-5"
                        @submit.prevent="submit"
                    >
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label
                                    class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Nama Lengkap
                                </label>

                                <input
                                    ref="nameInput"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="cth. Dr. Ahmad Fauzi, M.Kom."
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3.5 py-2.5 text-xs text-[#1b1b18] placeholder-[#a1a09a] outline-none transition focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC]"
                                />

                                <p
                                    v-if="errors.name"
                                    class="mt-1 text-[10px] font-medium text-red-600 dark:text-red-400"
                                >
                                    {{ errors.name }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Email
                                </label>

                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="nama@kampus.ac.id"
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3.5 py-2.5 text-xs text-[#1b1b18] placeholder-[#a1a09a] outline-none transition focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC]"
                                />

                                <p
                                    v-if="errors.email"
                                    class="mt-1 text-[10px] font-medium text-red-600 dark:text-red-400"
                                >
                                    {{ errors.email }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Jabatan
                                </label>

                                <input
                                    v-model="form.position"
                                    type="text"
                                    placeholder="cth. Kepala Prodi"
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3.5 py-2.5 text-xs text-[#1b1b18] placeholder-[#a1a09a] outline-none transition focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC]"
                                />

                                <p
                                    v-if="errors.position"
                                    class="mt-1 text-[10px] font-medium text-red-600 dark:text-red-400"
                                >
                                    {{ errors.position }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Peran
                                </label>

                                <select
                                    v-model="form.role"
                                    required
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-white px-3 py-2.5 text-xs text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                >
                                    <option value="" disabled>
                                        Pilih peran
                                    </option>

                                    <option
                                        v-for="role in roleOptions"
                                        :key="role.value"
                                        :value="role.value"
                                    >
                                        {{ role.label }}
                                    </option>
                                </select>

                                <p
                                    v-if="errors.role"
                                    class="mt-1 text-[10px] font-medium text-red-600 dark:text-red-400"
                                >
                                    {{ errors.role }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Status
                                </label>

                                <select
                                    v-model="form.status"
                                    required
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-white px-3 py-2.5 text-xs text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                >
                                    <option
                                        v-for="status in statusOptions"
                                        :key="status.value"
                                        :value="status.value"
                                    >
                                        {{ status.label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    NIP / NIM
                                </label>

                                <input
                                    v-model="form.nip"
                                    type="text"
                                    placeholder="Nomor identitas"
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3.5 py-2.5 text-xs text-[#1b1b18] placeholder-[#a1a09a] outline-none transition focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC]"
                                />

                                <p
                                    v-if="errors.nip"
                                    class="mt-1 text-[10px] font-medium text-red-600 dark:text-red-400"
                                >
                                    {{ errors.nip }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Nomor Telepon
                                </label>

                                <input
                                    v-model="form.phone_number"
                                    type="text"
                                    placeholder="08xxxxxxxxxx"
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3.5 py-2.5 text-xs text-[#1b1b18] placeholder-[#a1a09a] outline-none transition focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC]"
                                />
                            </div>

                            <div
                                :class="
                                    requiresFacultyContext
                                        ? ''
                                        : 'opacity-60'
                                "
                            >
                                <label
                                    class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Fakultas
                                </label>

                                <select
                                    v-model="form.faculty_id"
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-white px-3 py-2.5 text-xs text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                >
                                    <option value="">
                                        Tidak terafiliasi
                                    </option>

                                    <option
                                        v-for="faculty in faculties ?? []"
                                        :key="faculty.id"
                                        :value="String(faculty.id)"
                                    >
                                        {{
                                            faculty.code
                                                ? `${faculty.code} — ${faculty.name}`
                                                : faculty.name
                                        }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Program Studi / Departemen
                                </label>

                                <input
                                    v-model="form.department"
                                    type="text"
                                    placeholder="cth. Teknik Informatika"
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-transparent px-3.5 py-2.5 text-xs text-[#1b1b18] placeholder-[#a1a09a] outline-none transition focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC]"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-5 rounded-xl border border-[#e3e3e0] bg-[#FDFDFC] p-4 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Lock
                                        class="h-3.5 w-3.5 text-[#706f6c] dark:text-[#A1A09A]"
                                    />

                                    <span
                                        class="text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                    >
                                        Kata Sandi
                                    </span>
                                </div>

                                <label
                                    v-if="isEdit"
                                    class="inline-flex cursor-pointer items-center gap-2 text-[10px] font-medium text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    <input
                                        v-model="changePassword"
                                        type="checkbox"
                                        class="h-3.5 w-3.5 rounded border-[#e3e3e0] text-[#f53003] focus:ring-[#f53003] dark:border-[#3E3E3A]"
                                    />
                                    Ubah kata sandi
                                </label>
                            </div>

                            <div
                                v-if="changePassword"
                                class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2"
                            >
                                <div class="relative">
                                    <input
                                        v-model="form.password"
                                        :type="
                                            showPassword ? 'text' : 'password'
                                        "
                                        :required="changePassword"
                                        placeholder="Kata sandi baru"
                                        class="w-full rounded-lg border border-[#e3e3e0] bg-white px-3.5 py-2.5 pr-9 text-xs text-[#1b1b18] placeholder-[#a1a09a] outline-none transition focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                    />

                                    <button
                                        type="button"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#A1A09A]"
                                        @click="showPassword = !showPassword"
                                    >
                                        <component
                                            :is="
                                                showPassword ? EyeOff : Eye
                                            "
                                            class="h-3.5 w-3.5"
                                        />
                                    </button>
                                </div>

                                <div class="relative">
                                    <input
                                        v-model="form.password_confirmation"
                                        :type="
                                            showPasswordConfirmation
                                                ? 'text'
                                                : 'password'
                                        "
                                        :required="changePassword"
                                        placeholder="Konfirmasi kata sandi"
                                        class="w-full rounded-lg border border-[#e3e3e0] bg-white px-3.5 py-2.5 pr-9 text-xs text-[#1b1b18] placeholder-[#a1a09a] outline-none transition focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                    />

                                    <button
                                        type="button"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#A1A09A]"
                                        @click="
                                            showPasswordConfirmation =
                                                !showPasswordConfirmation
                                        "
                                    >
                                        <component
                                            :is="
                                                showPasswordConfirmation
                                                    ? EyeOff
                                                    : Eye
                                            "
                                            class="h-3.5 w-3.5"
                                        />
                                    </button>
                                </div>

                                <p
                                    v-if="errors.password"
                                    class="text-[10px] font-medium text-red-600 dark:text-red-400 sm:col-span-2"
                                >
                                    {{ errors.password }}
                                </p>
                            </div>
                        </div>
                    </form>

                    <div
                        class="flex flex-col-reverse gap-2 border-t border-[#e3e3e0] bg-[#fafafa] p-4 sm:flex-row sm:justify-end dark:border-[#3E3E3A] dark:bg-[#111110]"
                    >
                        <button
                            type="button"
                            :disabled="processing"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm font-medium text-[#1b1b18] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                            @click="close"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            form="user-form"
                            :disabled="processing"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#1b1b18] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white"
                        >
                            <Loader2
                                v-if="processing"
                                class="h-4 w-4 animate-spin"
                            />

                            {{
                                processing
                                    ? "Menyimpan..."
                                    : isEdit
                                      ? "Simpan Perubahan"
                                      : "Simpan Pengguna"
                            }}
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>
