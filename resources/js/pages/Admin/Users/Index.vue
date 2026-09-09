<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import {
    Search,
    Plus,
    Users as UsersIcon,
    ShieldCheck,
    GraduationCap,
    UserCog,
    MoreHorizontal,
    Pencil,
    Trash2,
    Eye,
    Mail,
    Phone,
    Building2,
    BriefcaseBusiness,
    Hash,
    BadgeCheck,
    CheckCircle2,
    Clock3,
    Ban,
    ChevronLeft,
    ChevronRight,
    Filter,
    X,
    UserRoundCheck,
    Loader2,
} from "lucide-vue-next";
import UserDetailModal from "./UserDetailModal.vue";
import UserFormModal from "./UserFormModal.vue";

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
    email_verified_at?: string | null;
    role: "super_admin" | "admin_fakultas" | "sdm" | "dosen" | "mahasiswa";
    nip?: string | null;
    faculty_id?: number | null;
    department?: string | null;
    phone_number?: string | null;
    status: "active" | "pending" | "suspended";
    two_factor_confirmed_at?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    faculty?: Faculty | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
}

interface PaginatedUsers {
    data: User[];
    links: PaginationLink[];
    meta?: PaginationMeta;
    current_page?: number;
    last_page?: number;
    from?: number | null;
    to?: number | null;
    total?: number;
    per_page?: number;
}

interface Filters {
    search?: string;
    role?: string;
    status?: string;
    faculty_id?: string | number | null;
}

interface UserStats {
    total?: number;
    active?: number;
    pending?: number;
    suspended?: number;
    faculties?: number;
}

const props = defineProps<{
    users: PaginatedUsers;
    faculties?: Faculty[];
    filters?: Filters;
    stats?: UserStats;
}>();

const search = ref(props.filters?.search ?? "");
const selectedRole = ref(props.filters?.role ?? "");
const selectedStatus = ref(props.filters?.status ?? "");
const selectedFaculty = ref(
    props.filters?.faculty_id !== undefined &&
        props.filters?.faculty_id !== null
        ? String(props.filters.faculty_id)
        : "",
);

const showFilters = ref(false);
const openMenuId = ref<number | null>(null);
const deletingUser = ref<User | null>(null);
const verifyingUser = ref<User | null>(null);
const processing = ref(false);
const loading = ref(false);

const detailUser = ref<User | null>(null);
const showFormModal = ref(false);
const editingUser = ref<User | null>(null);

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

const usersData = computed(() => props.users?.data ?? []);

const currentPage = computed(
    () => props.users?.meta?.current_page ?? props.users?.current_page ?? 1,
);

const lastPage = computed(
    () => props.users?.meta?.last_page ?? props.users?.last_page ?? 1,
);

const totalUsers = computed(
    () =>
        props.stats?.total ??
        props.users?.meta?.total ??
        props.users?.total ??
        0,
);

const fromUser = computed(
    () => props.users?.meta?.from ?? props.users?.from ?? null,
);

const toUser = computed(() => props.users?.meta?.to ?? props.users?.to ?? null);

const activeUsers = computed(
    () =>
        props.stats?.active ??
        usersData.value.filter((user) => user.status === "active").length,
);

const pendingUsers = computed(
    () =>
        props.stats?.pending ??
        usersData.value.filter((user) => user.status === "pending").length,
);

const suspendedUsers = computed(
    () =>
        props.stats?.suspended ??
        usersData.value.filter((user) => user.status === "suspended").length,
);

const facultyCount = computed(
    () =>
        props.stats?.faculties ??
        new Set(
            usersData.value
                .map((user) => user.faculty_id)
                .filter((value) => value !== null && value !== undefined),
        ).size,
);

const usingPageOnlyStats = computed(() => props.stats === undefined);

const hasActiveFilters = computed(
    () =>
        Boolean(search.value) ||
        Boolean(selectedRole.value) ||
        Boolean(selectedStatus.value) ||
        Boolean(selectedFaculty.value),
);

const roleLabel = (role: User["role"]) => {
    return roleOptions.find((item) => item.value === role)?.label ?? role;
};

const statusLabel = (status: User["status"]) => {
    return statusOptions.find((item) => item.value === status)?.label ?? status;
};

const roleClass = (role: User["role"]) => {
    const classes: Record<User["role"], string> = {
        super_admin:
            "bg-[#1b1b18] text-white dark:bg-[#EDEDEC] dark:text-[#1b1b18]",
        admin_fakultas:
            "bg-[#fff2f2] text-[#f53003] ring-[#f53003]/20 dark:bg-[#1D0002] dark:text-[#FF4433] dark:ring-[#FF4433]/30",
        sdm: "bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-900/60",
        dosen: "bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900/60",
        mahasiswa:
            "bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900/60",
    };

    return classes[role];
};

const statusClass = (status: User["status"]) => {
    const classes: Record<User["status"], string> = {
        active: "bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900/60",
        pending:
            "bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-900/60",
        suspended:
            "bg-red-50 text-red-700 ring-red-200 dark:bg-red-950/40 dark:text-red-300 dark:ring-red-900/60",
    };

    return classes[status];
};

const roleIcon = (role: User["role"]) => {
    if (role === "super_admin") return ShieldCheck;
    if (role === "admin_fakultas") return Building2;
    if (role === "sdm") return UserCog;
    if (role === "dosen") return GraduationCap;
    return UsersIcon;
};

const statusIcon = (status: User["status"]) => {
    if (status === "active") return CheckCircle2;
    if (status === "pending") return Clock3;
    return Ban;
};

const initials = (name: string) => {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join("")
        .toUpperCase();
};

const facultyName = (user: User) => {
    return user.faculty?.name ?? "Tidak terafiliasi";
};

const identityLabel = (user: User) => {
    if (user.nip) return user.nip;
    return "—";
};

const formatDate = (value?: string | null) => {
    if (!value) return "—";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return "—";

    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(date);
};

const isEmailVerified = (user: User) => {
    return Boolean(user.email_verified_at);
};

const emailVerificationLabel = (user: User) => {
    return isEmailVerified(user) ? "Terverifikasi" : "Belum diverifikasi";
};

const emailVerificationClass = (user: User) => {
    return isEmailVerified(user)
        ? "bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900/60"
        : "bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-900/60";
};

const applyFilters = () => {
    loading.value = true;

    router.get(
        "/admin/users",
        {
            search: search.value || undefined,
            role: selectedRole.value || undefined,
            status: selectedStatus.value || undefined,
            faculty_id: selectedFaculty.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                loading.value = false;
            },
        },
    );
};

const clearFilters = () => {
    search.value = "";
    selectedRole.value = "";
    selectedStatus.value = "";
    selectedFaculty.value = "";

    applyFilters();
};

const verifyEmail = (user: User) => {
    if (processing.value || isEmailVerified(user)) {
        return;
    }

    openMenuId.value = null;
    verifyingUser.value = user;
};

const cancelVerifyEmail = () => {
    if (processing.value) return;

    verifyingUser.value = null;
};

const confirmVerifyEmail = () => {
    const user = verifyingUser.value;

    if (!user || processing.value || isEmailVerified(user)) {
        return;
    }

    processing.value = true;

    router.patch(
        `/admin/users/${user.id}/send-verification-email`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                verifyingUser.value = null;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(search, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

watch(
    [selectedRole, selectedStatus, selectedFaculty],
    () => {
        applyFilters();
    },
    {
        flush: "post",
    },
);

onBeforeUnmount(() => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});

const goToPage = (url: string | null) => {
    if (!url) return;

    loading.value = true;

    router.get(
        url,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                loading.value = false;
            },
        },
    );
};

const confirmDelete = (user: User) => {
    openMenuId.value = null;
    deletingUser.value = user;
};

const cancelDelete = () => {
    if (processing.value) return;

    deletingUser.value = null;
};

const deleteUser = () => {
    if (!deletingUser.value || processing.value) return;

    const userId = deletingUser.value.id;

    processing.value = true;

    router.delete(`/admin/users/${userId}`, {
        preserveScroll: true,
        onSuccess: () => {
            deletingUser.value = null;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const closeMenus = () => {
    openMenuId.value = null;
};

const openDetail = (user: User) => {
    openMenuId.value = null;
    detailUser.value = user;
};

const closeDetail = () => {
    detailUser.value = null;
};

const openCreateForm = () => {
    openMenuId.value = null;
    editingUser.value = null;
    showFormModal.value = true;
};

const openEditForm = (user: User) => {
    openMenuId.value = null;
    editingUser.value = user;
    showFormModal.value = true;
};

const closeForm = () => {
    showFormModal.value = false;
};

const handleSaved = () => {
    showFormModal.value = false;
    editingUser.value = null;
};

const editFromDetail = (user: User) => {
    detailUser.value = null;
    openEditForm(user);
};

const deleteFromDetail = (user: User) => {
    detailUser.value = null;
    confirmDelete(user);
};

const anyModalOpen = computed(
    () =>
        Boolean(deletingUser.value) ||
        Boolean(verifyingUser.value) ||
        Boolean(detailUser.value) ||
        showFormModal.value,
);

watch(anyModalOpen, (open) => {
    if (typeof document === "undefined") return;

    document.body.style.overflow = open ? "hidden" : "";
});

onBeforeUnmount(() => {
    if (typeof document !== "undefined") {
        document.body.style.overflow = "";
    }
});

const onGlobalKeydown = (event: KeyboardEvent) => {
    if (event.key !== "Escape") return;

    if (processing.value) return;

    if (openMenuId.value !== null) {
        openMenuId.value = null;
        return;
    }

    if (verifyingUser.value) {
        verifyingUser.value = null;
        return;
    }

    if (deletingUser.value) {
        deletingUser.value = null;
    }
};
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <div
        class="relative flex min-h-screen flex-1 flex-col overflow-hidden p-4 md:p-6"
        @click="closeMenus"
        @keydown="onGlobalKeydown"
    >
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

        <div
            class="relative z-10 flex flex-1 flex-col gap-6 opacity-100 transition-opacity duration-750 starting:opacity-0"
        >
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Total Pengguna
                        </span>

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff2f2] text-[#f53003] transition group-hover:bg-[#f53003] group-hover:text-white dark:bg-[#1D0002] dark:text-[#FF4433] dark:group-hover:bg-[#FF4433] dark:group-hover:text-white"
                        >
                            <UsersIcon class="h-5 w-5" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ totalUsers }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Seluruh akun pengguna
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
                        >
                            Pengguna Aktif
                        </span>

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff2f2] text-[#f53003] transition group-hover:bg-[#f53003] group-hover:text-white dark:bg-[#1D0002] dark:text-[#FF4433] dark:group-hover:bg-[#FF4433] dark:group-hover:text-white"
                        >
                            <CheckCircle2 class="h-5 w-5" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ activeUsers }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Akun dengan status aktif
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
                        >
                            Pending
                        </span>

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff2f2] text-[#f53003] transition group-hover:bg-[#f53003] group-hover:text-white dark:bg-[#1D0002] dark:text-[#FF4433] dark:group-hover:bg-[#FF4433] dark:group-hover:text-white"
                        >
                            <Clock3 class="h-5 w-5" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ pendingUsers }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Menunggu tindakan
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
                        >
                            Suspended
                        </span>

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff2f2] text-[#f53003] transition group-hover:bg-[#f53003] group-hover:text-white dark:bg-[#1D0002] dark:text-[#FF4433] dark:group-hover:bg-[#FF4433] dark:group-hover:text-white"
                        >
                            <Ban class="h-5 w-5" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ suspendedUsers }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Akun ditangguhkan
                        </p>
                    </div>

                    <div
                        class="absolute bottom-0 left-0 h-[2px] w-full bg-[#f53003]/20 opacity-0 transition group-hover:opacity-100 dark:bg-[#FF4433]/30"
                    ></div>
                </div>
            </div>

            <div
                v-if="usingPageOnlyStats"
                class="-mt-2 text-[10px] text-[#A1A09A]"
            >
                Kartu ringkasan menggunakan data halaman saat prop
                <code>stats</code> belum dikirim backend.
            </div>

            <div
                class="flex flex-1 flex-col rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]"
            >
                <div
                    class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#fff2f2] text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
                            >
                                <UserRoundCheck class="h-4 w-4" />
                            </div>

                            <h2
                                class="text-lg font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Manajemen Pengguna
                            </h2>
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Kelola akun, peran, identitas, fakultas, dan status
                            pengguna sistem.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#1b1b18] px-4 py-2.5 text-xs font-medium text-white transition hover:bg-black dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white sm:w-auto"
                        @click.stop="openCreateForm"
                    >
                        <Plus class="h-4 w-4" />
                        Tambah Pengguna
                    </button>
                </div>

                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="relative min-w-0 flex-1">
                        <Search
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#706f6c] dark:text-[#A1A09A]"
                        />

                        <input
                            v-model="search"
                            type="search"
                            placeholder="Cari nama, email, NIP/NIM, jabatan..."
                            aria-label="Cari pengguna"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-transparent py-2.5 pl-9 pr-3.5 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                        />

                        <Loader2
                            v-if="loading"
                            class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-[#A1A09A]"
                        />
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-[#e3e3e0] bg-white px-4 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                        aria-haspopup="true"
                        :aria-expanded="showFilters"
                        @click.stop="showFilters = !showFilters"
                    >
                        <Filter class="h-4 w-4" />
                        Filter

                        <span
                            v-if="hasActiveFilters"
                            class="inline-flex min-w-[20px] items-center justify-center rounded-full bg-[#f53003] px-1.5 py-0.5 text-[10px] font-bold text-white"
                        >
                            {{
                                [
                                    selectedRole,
                                    selectedStatus,
                                    selectedFaculty,
                                ].filter(Boolean).length + (search ? 1 : 0)
                            }}
                        </span>
                    </button>
                </div>

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <div
                        v-if="showFilters"
                        class="mt-3 rounded-xl border border-[#e3e3e0] bg-[#FDFDFC] p-4 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                        @click.stop
                    >
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div>
                                <label
                                    class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Peran
                                </label>

                                <select
                                    v-model="selectedRole"
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-white px-3 py-2 text-xs text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                >
                                    <option value="">Semua Peran</option>

                                    <option
                                        v-for="role in roleOptions"
                                        :key="role.value"
                                        :value="role.value"
                                    >
                                        {{ role.label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Status
                                </label>

                                <select
                                    v-model="selectedStatus"
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-white px-3 py-2 text-xs text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                >
                                    <option value="">Semua Status</option>

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
                                    Fakultas
                                </label>

                                <select
                                    v-model="selectedFaculty"
                                    class="w-full rounded-lg border border-[#e3e3e0] bg-white px-3 py-2 text-xs text-[#1b1b18] outline-none transition focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                                >
                                    <option value="">Semua Fakultas</option>

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
                        </div>

                        <div
                            v-if="hasActiveFilters"
                            class="mt-3 flex justify-end"
                        >
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#f53003] transition hover:text-red-700 dark:text-[#FF4433]"
                                @click="clearFilters"
                            >
                                <X class="h-3.5 w-3.5" />
                                Reset Filter
                            </button>
                        </div>
                    </div>
                </Transition>

                <div
                    class="mt-5 flex flex-col gap-2 border-b border-[#e3e3e0] pb-4 dark:border-[#3E3E3A] sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        Menampilkan
                        <span
                            class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ fromUser ?? 0 }}–{{ toUser ?? 0 }}
                        </span>
                        dari
                        <span
                            class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ totalUsers }}
                        </span>
                        pengguna
                    </div>

                    <div
                        class="flex items-center gap-1.5 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        <Building2 class="h-3.5 w-3.5" />
                        {{ facultyCount }} fakultas pada halaman ini
                    </div>
                </div>

                <div
                    v-if="usersData.length"
                    class="mt-4 overflow-hidden rounded-xl border border-[#e3e3e0] dark:border-[#3E3E3A]"
                    :class="{ 'opacity-60': loading }"
                >
                    <div class="hidden overflow-x-auto lg:block">
                        <table class="w-full min-w-[1100px] text-left text-xs">
                            <thead>
                                <tr
                                    class="border-b border-[#e3e3e0] bg-[#FDFDFC] dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                                >
                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        Pengguna
                                    </th>

                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        Identitas
                                    </th>

                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        Fakultas / Prodi
                                    </th>

                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        Peran
                                    </th>

                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        Status
                                    </th>

                                    <th class="w-14 px-4 py-3"></th>
                                </tr>
                            </thead>

                            <tbody
                                class="divide-y divide-[#e3e3e0]/60 dark:divide-[#3E3E3A]/60"
                            >
                                <tr
                                    v-for="user in usersData"
                                    :key="user.id"
                                    class="group cursor-pointer text-[#1b1b18] transition hover:bg-[#FDFDFC] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                                    @click="openDetail(user)"
                                >
                                    <td class="px-4 py-4">
                                        <div
                                            class="flex min-w-0 items-center gap-3"
                                        >
                                            <div
                                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#fff2f2] text-xs font-bold text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
                                            >
                                                {{ initials(user.name) }}
                                            </div>

                                            <div class="min-w-0">
                                                <div
                                                    class="truncate text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                                >
                                                    {{ user.name }}
                                                </div>

                                                <div
                                                    class="mt-0.5 flex min-w-0 items-center gap-1.5 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                                >
                                                    <Mail
                                                        class="h-3.5 w-3.5 shrink-0"
                                                    />

                                                    <span
                                                        class="min-w-0 truncate"
                                                    >
                                                        {{ user.email }}
                                                    </span>
                                                </div>

                                                <div
                                                    class="mt-1.5 flex flex-wrap items-center gap-1.5"
                                                >
                                                    <span
                                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-bold ring-1 ring-inset"
                                                        :class="
                                                            emailVerificationClass(
                                                                user,
                                                            )
                                                        "
                                                    >
                                                        <CheckCircle2
                                                            v-if="
                                                                isEmailVerified(
                                                                    user,
                                                                )
                                                            "
                                                            class="h-3 w-3"
                                                        />

                                                        <Clock3
                                                            v-else
                                                            class="h-3 w-3"
                                                        />

                                                        {{
                                                            emailVerificationLabel(
                                                                user,
                                                            )
                                                        }}
                                                    </span>

                                                    <span
                                                        v-if="
                                                            isEmailVerified(
                                                                user,
                                                            )
                                                        "
                                                        class="text-[9px] text-[#A1A09A]"
                                                    >
                                                        {{
                                                            formatDate(
                                                                user.email_verified_at,
                                                            )
                                                        }}
                                                    </span>
                                                </div>

                                                <div
                                                    v-if="user.position"
                                                    class="mt-0.5 flex items-center gap-1.5 truncate text-xs text-[#A1A09A]"
                                                >
                                                    <BriefcaseBusiness
                                                        class="h-3 w-3 shrink-0"
                                                    />
                                                    {{ user.position }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4">
                                        <div
                                            class="flex items-center gap-1.5 font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            <Hash
                                                class="h-3.5 w-3.5 text-[#A1A09A]"
                                            />
                                            {{ identityLabel(user) }}
                                        </div>

                                        <div
                                            v-if="user.phone_number"
                                            class="mt-1 flex items-center gap-1.5 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            <Phone class="h-3.5 w-3.5" />
                                            {{ user.phone_number }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-4">
                                        <div
                                            class="max-w-[220px] truncate font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{ facultyName(user) }}
                                        </div>

                                        <div
                                            class="mt-0.5 max-w-[220px] truncate text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            {{
                                                user.department ||
                                                "Program studi belum diatur"
                                            }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-4">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 ring-inset"
                                            :class="roleClass(user.role)"
                                        >
                                            <component
                                                :is="roleIcon(user.role)"
                                                class="h-3.5 w-3.5"
                                            />
                                            {{ roleLabel(user.role) }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-4">
                                        <div class="space-y-1.5">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 ring-inset"
                                                :class="
                                                    statusClass(user.status)
                                                "
                                            >
                                                <component
                                                    :is="
                                                        statusIcon(user.status)
                                                    "
                                                    class="h-3.5 w-3.5"
                                                />
                                                {{ statusLabel(user.status) }}
                                            </span>

                                            <div
                                                class="text-[10px] text-[#A1A09A]"
                                            >
                                                Dibuat
                                                {{
                                                    formatDate(user.created_at)
                                                }}
                                            </div>
                                        </div>
                                    </td>

                                    <td class="relative px-4 py-4 text-right">
                                        <button
                                            type="button"
                                            aria-haspopup="true"
                                            :aria-expanded="
                                                openMenuId === user.id
                                            "
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#706f6c] transition hover:bg-slate-100 hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#20201e] dark:hover:text-[#EDEDEC]"
                                            @click.stop="
                                                openMenuId =
                                                    openMenuId === user.id
                                                        ? null
                                                        : user.id
                                            "
                                        >
                                            <MoreHorizontal class="h-4 w-4" />
                                        </button>

                                        <div
                                            v-if="openMenuId === user.id"
                                            role="menu"
                                            class="absolute right-4 top-12 z-30 w-48 overflow-hidden rounded-xl border border-[#e3e3e0] bg-white p-1.5 text-left shadow-xl dark:border-[#3E3E3A] dark:bg-[#161615]"
                                            @click.stop
                                        >
                                            <button
                                                type="button"
                                                role="menuitem"
                                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                                                @click="openDetail(user)"
                                            >
                                                <Eye class="h-3.5 w-3.5" />
                                                Detail
                                            </button>

                                            <button
                                                type="button"
                                                role="menuitem"
                                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                                                @click="openEditForm(user)"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                                Edit
                                            </button>

                                            <button
                                                v-if="!isEmailVerified(user)"
                                                type="button"
                                                role="menuitem"
                                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-950/30"
                                                @click="verifyEmail(user)"
                                            >
                                                <BadgeCheck
                                                    class="h-3.5 w-3.5"
                                                />
                                                Verifikasi Email
                                            </button>

                                            <button
                                                type="button"
                                                role="menuitem"
                                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-[#f53003] transition hover:bg-[#fff2f2] dark:text-[#FF4433] dark:hover:bg-[#1D0002]"
                                                @click="confirmDelete(user)"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        class="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A] lg:hidden"
                    >
                        <div
                            v-for="user in usersData"
                            :key="user.id"
                            class="cursor-pointer p-4 transition hover:bg-[#FDFDFC] dark:hover:bg-[#20201e]"
                            @click="openDetail(user)"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#fff2f2] text-xs font-bold text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
                                    >
                                        {{ initials(user.name) }}
                                    </div>

                                    <div class="min-w-0">
                                        <div
                                            class="truncate text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{ user.name }}
                                        </div>

                                        <div
                                            class="mt-0.5 flex min-w-0 items-center gap-1.5 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            <Mail
                                                class="h-3.5 w-3.5 shrink-0"
                                            />

                                            <span class="truncate">
                                                {{ user.email }}
                                            </span>
                                        </div>

                                        <div
                                            class="mt-1.5 flex flex-wrap items-center gap-1.5"
                                        >
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-bold ring-1 ring-inset"
                                                :class="
                                                    emailVerificationClass(user)
                                                "
                                            >
                                                <CheckCircle2
                                                    v-if="isEmailVerified(user)"
                                                    class="h-3 w-3"
                                                />

                                                <Clock3
                                                    v-else
                                                    class="h-3 w-3"
                                                />

                                                {{
                                                    emailVerificationLabel(user)
                                                }}
                                            </span>

                                            <span
                                                v-if="isEmailVerified(user)"
                                                class="text-[9px] text-[#A1A09A]"
                                            >
                                                {{
                                                    formatDate(
                                                        user.email_verified_at,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="relative shrink-0">
                                    <button
                                        type="button"
                                        aria-haspopup="true"
                                        :aria-expanded="openMenuId === user.id"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-[#706f6c] transition hover:bg-slate-100 dark:text-[#A1A09A] dark:hover:bg-[#20201e]"
                                        @click.stop="
                                            openMenuId =
                                                openMenuId === user.id
                                                    ? null
                                                    : user.id
                                        "
                                    >
                                        <MoreHorizontal class="h-4 w-4" />
                                    </button>

                                    <div
                                        v-if="openMenuId === user.id"
                                        role="menu"
                                        class="absolute right-0 top-9 z-30 w-48 overflow-hidden rounded-xl border border-[#e3e3e0] bg-white p-1.5 shadow-xl dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        @click.stop
                                    >
                                        <button
                                            type="button"
                                            role="menuitem"
                                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                                            @click="openDetail(user)"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                            Detail
                                        </button>

                                        <button
                                            type="button"
                                            role="menuitem"
                                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-[#1b1b18] transition hover:bg-slate-50 dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                                            @click="openEditForm(user)"
                                        >
                                            <Pencil class="h-3.5 w-3.5" />
                                            Edit
                                        </button>

                                        <button
                                            v-if="!isEmailVerified(user)"
                                            type="button"
                                            role="menuitem"
                                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-950/30"
                                            @click="verifyEmail(user)"
                                        >
                                            <BadgeCheck class="h-3.5 w-3.5" />
                                            Verifikasi Email
                                        </button>

                                        <button
                                            type="button"
                                            role="menuitem"
                                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-[#f53003] transition hover:bg-[#fff2f2] dark:text-[#FF4433] dark:hover:bg-[#1D0002]"
                                            @click="confirmDelete(user)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div
                                    class="rounded-lg border border-[#e3e3e0] bg-[#FDFDFC] p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                                >
                                    <div
                                        class="text-[9px] font-bold uppercase tracking-wider text-[#A1A09A]"
                                    >
                                        Identitas
                                    </div>

                                    <div
                                        class="mt-1 truncate text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                    >
                                        {{ identityLabel(user) }}
                                    </div>
                                </div>

                                <div
                                    class="rounded-lg border border-[#e3e3e0] bg-[#FDFDFC] p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                                >
                                    <div
                                        class="text-[9px] font-bold uppercase tracking-wider text-[#A1A09A]"
                                    >
                                        Fakultas
                                    </div>

                                    <div
                                        class="mt-1 truncate text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                    >
                                        {{ facultyName(user) }}
                                    </div>
                                </div>

                                <div
                                    class="rounded-lg border border-[#e3e3e0] bg-[#FDFDFC] p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                                >
                                    <div
                                        class="text-[9px] font-bold uppercase tracking-wider text-[#A1A09A]"
                                    >
                                        Peran
                                    </div>

                                    <div class="mt-1">
                                        <span
                                            class="inline-flex max-w-full items-center gap-1.5 rounded-full px-2 py-1 text-[9px] font-bold ring-1 ring-inset"
                                            :class="roleClass(user.role)"
                                        >
                                            <component
                                                :is="roleIcon(user.role)"
                                                class="h-3 w-3"
                                            />
                                            <span class="truncate">
                                                {{ roleLabel(user.role) }}
                                            </span>
                                        </span>
                                    </div>
                                </div>

                                <div
                                    class="rounded-lg border border-[#e3e3e0] bg-[#FDFDFC] p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                                >
                                    <div
                                        class="text-[9px] font-bold uppercase tracking-wider text-[#A1A09A]"
                                    >
                                        Status
                                    </div>

                                    <div class="mt-1">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-[9px] font-bold ring-1 ring-inset"
                                            :class="statusClass(user.status)"
                                        >
                                            <component
                                                :is="statusIcon(user.status)"
                                                class="h-3 w-3"
                                            />
                                            {{ statusLabel(user.status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="mt-3 flex flex-col gap-1.5 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                <div
                                    v-if="user.position"
                                    class="flex items-center gap-2"
                                >
                                    <BriefcaseBusiness class="h-3.5 w-3.5" />
                                    {{ user.position }}
                                </div>

                                <div
                                    v-if="user.department"
                                    class="flex items-center gap-2"
                                >
                                    <GraduationCap class="h-3.5 w-3.5" />
                                    {{ user.department }}
                                </div>

                                <div
                                    v-if="user.phone_number"
                                    class="flex items-center gap-2"
                                >
                                    <Phone class="h-3.5 w-3.5" />
                                    {{ user.phone_number }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="mt-4 rounded-xl border border-dashed border-[#e3e3e0] bg-[#FDFDFC] px-6 py-14 text-center dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                >
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#fff2f2] text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
                    >
                        <UsersIcon class="h-6 w-6" />
                    </div>

                    <h3
                        class="mt-4 text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Tidak ada pengguna
                    </h3>

                    <p
                        class="mx-auto mt-1 max-w-md text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Tidak ditemukan pengguna yang sesuai dengan pencarian
                        atau filter yang dipilih.
                    </p>

                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#1b1b18] px-3.5 py-2 text-xs font-medium text-white transition hover:bg-black dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white"
                        @click="clearFilters"
                    >
                        <X class="h-3.5 w-3.5" />
                        Reset Filter
                    </button>
                </div>

                <div
                    v-if="lastPage > 1"
                    class="mt-5 flex flex-col gap-3 border-t border-[#e3e3e0] pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-[#3E3E3A]"
                >
                    <div class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        Halaman
                        <span
                            class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ currentPage }}
                        </span>
                        dari
                        <span
                            class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ lastPage }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1">
                        <template
                            v-for="(link, index) in users.links"
                            :key="index"
                        >
                            <button
                                v-if="link.label.includes('Previous')"
                                type="button"
                                :disabled="!link.url"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[#e3e3e0] bg-white text-[#706f6c] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:bg-[#20201e]"
                                @click="goToPage(link.url)"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </button>

                            <button
                                v-else-if="link.label.includes('Next')"
                                type="button"
                                :disabled="!link.url"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[#e3e3e0] bg-white text-[#706f6c] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:bg-[#20201e]"
                                @click="goToPage(link.url)"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </button>

                            <span
                                v-else-if="
                                    !link.url && link.label.trim() === '...'
                                "
                                class="hidden h-9 min-w-9 items-center justify-center px-2 text-xs text-[#A1A09A] sm:inline-flex"
                            >
                                …
                            </span>

                            <button
                                v-else-if="
                                    link.url &&
                                    link.label.trim() !== '' &&
                                    Number.isInteger(Number(link.label))
                                "
                                type="button"
                                class="hidden h-9 min-w-9 items-center justify-center rounded-lg border px-2 text-xs font-semibold transition sm:inline-flex"
                                :class="
                                    link.active
                                        ? 'border-[#f53003] bg-[#f53003] text-white dark:border-[#FF4433] dark:bg-[#FF4433] dark:text-white'
                                        : 'border-[#e3e3e0] bg-white text-[#706f6c] hover:bg-slate-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:bg-[#20201e]'
                                "
                                @click="goToPage(link.url)"
                            >
                                {{ link.label }}
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div
                class="flex flex-col gap-1 border-t border-[#e3e3e0] pt-3 text-[10px] text-[#A1A09A] sm:flex-row sm:items-center sm:justify-between dark:border-[#3E3E3A]"
            >
                <span>Sistem Manajemen Pengguna</span>
                <span>Data akun dan akses pengguna</span>
            </div>
        </div>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="verifyingUser"
                class="fixed inset-0 z-[110] flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                aria-label="Konfirmasi verifikasi email"
                @click.self="cancelVerifyEmail"
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
                        v-if="verifyingUser"
                        class="relative w-full max-w-md overflow-hidden rounded-2xl border border-[#e3e3e0] bg-white shadow-2xl dark:border-[#3E3E3A] dark:bg-[#161615]"
                    >
                        <div class="h-1 bg-emerald-500"></div>

                        <div class="p-6 sm:p-7">
                            <div
                                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400"
                            >
                                <BadgeCheck class="h-7 w-7" />
                            </div>

                            <div class="mt-5 text-center">
                                <h2
                                    class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Verifikasi Email?
                                </h2>

                                <p
                                    class="mt-2 text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Email pengguna ini akan ditandai sebagai
                                    terverifikasi secara manual.
                                </p>
                            </div>

                            <div
                                class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-emerald-600 shadow-sm dark:bg-[#161615] dark:text-emerald-400"
                                    >
                                        <Mail class="h-4 w-4" />
                                    </div>

                                    <div class="min-w-0">
                                        <div
                                            class="truncate text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{ verifyingUser.name }}
                                        </div>

                                        <div
                                            class="mt-0.5 break-all text-xs text-emerald-700 dark:text-emerald-300"
                                        >
                                            {{ verifyingUser.email }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex flex-col-reverse gap-2 border-t border-[#e3e3e0] bg-[#fafafa] p-4 sm:flex-row sm:justify-end dark:border-[#3E3E3A] dark:bg-[#111110]"
                        >
                            <button
                                type="button"
                                :disabled="processing"
                                class="w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm font-medium text-[#1b1b18] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                                @click="cancelVerifyEmail"
                            >
                                Batal
                            </button>

                            <button
                                type="button"
                                :disabled="processing"
                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                                @click="confirmVerifyEmail"
                            >
                                <Loader2
                                    v-if="processing"
                                    class="h-4 w-4 animate-spin"
                                />

                                <BadgeCheck v-else class="h-4 w-4" />

                                {{
                                    processing
                                        ? "Memverifikasi..."
                                        : "Ya, Verifikasi Email"
                                }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="deletingUser"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                aria-label="Konfirmasi hapus pengguna"
                @click.self="cancelDelete"
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
                        v-if="deletingUser"
                        class="relative w-full max-w-md overflow-hidden rounded-2xl border border-[#e3e3e0] bg-white shadow-2xl dark:border-[#3E3E3A] dark:bg-[#161615]"
                    >
                        <div class="h-1 bg-[#f53003] dark:bg-[#FF4433]"></div>

                        <div class="p-6">
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-400"
                            >
                                <Trash2 class="h-6 w-6" />
                            </div>

                            <div class="mt-4 text-center">
                                <h2
                                    class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    Hapus pengguna?
                                </h2>

                                <p
                                    class="mt-2 text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Pengguna
                                    <span
                                        class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                    >
                                        {{ deletingUser.name }}
                                    </span>
                                    akan dihapus dari sistem. Tindakan ini tidak
                                    dapat dibatalkan.
                                </p>
                            </div>

                            <div
                                class="mt-5 rounded-xl border border-[#e3e3e0] bg-[#FDFDFC] p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="truncate text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{ deletingUser.name }}
                                        </div>

                                        <div
                                            class="mt-0.5 truncate text-[11px] text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            {{ deletingUser.email }}
                                        </div>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-full px-2 py-1 text-[9px] font-bold ring-1 ring-inset"
                                        :class="roleClass(deletingUser.role)"
                                    >
                                        {{ roleLabel(deletingUser.role) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex flex-col-reverse gap-2 border-t border-[#e3e3e0] bg-[#fafafa] p-4 sm:flex-row sm:justify-end dark:border-[#3E3E3A] dark:bg-[#111110]"
                        >
                            <button
                                type="button"
                                :disabled="processing"
                                class="w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm font-medium text-[#1b1b18] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e]"
                                @click="cancelDelete"
                            >
                                Batal
                            </button>

                            <button
                                type="button"
                                :disabled="processing"
                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                                @click="deleteUser"
                            >
                                <Loader2
                                    v-if="processing"
                                    class="h-4 w-4 animate-spin"
                                />

                                <Trash2 v-else class="h-4 w-4" />

                                {{
                                    processing
                                        ? "Menghapus..."
                                        : "Ya, Hapus Pengguna"
                                }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <UserDetailModal
            :show="Boolean(detailUser)"
            :user="detailUser"
            @close="closeDetail"
            @edit="editFromDetail"
            @delete="deleteFromDetail"
        />

        <UserFormModal
            :show="showFormModal"
            :user="editingUser"
            :faculties="faculties"
            @close="closeForm"
            @saved="handleSaved"
        />
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
