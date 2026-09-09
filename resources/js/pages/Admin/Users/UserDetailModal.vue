<script setup lang="ts">
import { computed } from "vue";
import {
    X,
    Pencil,
    Trash2,
    Mail,
    Phone,
    Building2,
    BriefcaseBusiness,
    Hash,
    GraduationCap,
    ShieldCheck,
    UserCog,
    Users as UsersIcon,
    CheckCircle2,
    Clock3,
    Ban,
    BadgeCheck,
    ShieldAlert,
    ShieldQuestion,
    CalendarClock,
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
    email_verified_at?: string | null;
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
    two_factor_confirmed_at?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    faculty?: Faculty | null;
}

const props = defineProps<{
    show: boolean;
    user?: User | null;
}>();

const emit = defineEmits<{
    (event: "close"): void;
    (event: "edit", user: User): void;
    (event: "delete", user: User): void;
}>();

const roleLabels: Record<User["role"], string> = {
    super_admin: "Super Admin",
    admin_fakultas: "Admin Fakultas",
    sdm: "SDM",
    dosen: "Dosen",
    mahasiswa: "Mahasiswa",
};

const statusLabels: Record<User["status"], string> = {
    active: "Aktif",
    pending: "Pending",
    suspended: "Suspended",
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
        active:
            "bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900/60",
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

const initials = computed(() => {
    if (!props.user?.name) return "";
    return props.user.name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join("")
        .toUpperCase();
});

const formatDate = (value?: string | null) => {
    if (!value) return "—";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "—";
    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

const close = () => emit("close");

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === "Escape") close();
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
            v-if="show && user"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-label="Detail Pengguna"
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
                    v-if="show && user"
                    class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-[#e3e3e0] bg-white shadow-2xl dark:border-[#3E3E3A] dark:bg-[#161615]"
                >
                    <div class="h-1 bg-[#f53003] dark:bg-[#FF4433]"></div>

                    <div
                        class="flex items-start justify-between gap-3 border-b border-[#e3e3e0] px-6 py-5 dark:border-[#3E3E3A]"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#fff2f2] text-sm font-bold text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
                            >
                                {{ initials }}
                            </div>

                            <div class="min-w-0">
                                <h2
                                    class="truncate text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ user.name }}
                                </h2>

                                <p
                                    class="mt-0.5 truncate text-xs text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    {{ user.position || "Jabatan belum diatur" }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[#706f6c] transition hover:bg-slate-100 dark:text-[#A1A09A] dark:hover:bg-[#20201e]"
                            @click="close"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 ring-inset"
                                :class="roleClass(user.role)"
                            >
                                <component
                                    :is="roleIcon(user.role)"
                                    class="h-3.5 w-3.5"
                                />
                                {{ roleLabels[user.role] }}
                            </span>

                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 ring-inset"
                                :class="statusClass(user.status)"
                            >
                                <component
                                    :is="statusIcon(user.status)"
                                    class="h-3.5 w-3.5"
                                />
                                {{ statusLabels[user.status] }}
                            </span>

                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 ring-inset"
                                :class="
                                    user.email_verified_at
                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900/60'
                                        : 'bg-slate-50 text-slate-600 ring-slate-200 dark:bg-slate-900/40 dark:text-slate-300 dark:ring-slate-800'
                                "
                            >
                                <BadgeCheck class="h-3.5 w-3.5" />
                                {{
                                    user.email_verified_at
                                        ? "Email Terverifikasi"
                                        : "Email Belum Terverifikasi"
                                }}
                            </span>

                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 ring-inset"
                                :class="
                                    user.two_factor_confirmed_at
                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900/60'
                                        : 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-900/60'
                                "
                            >
                                <component
                                    :is="
                                        user.two_factor_confirmed_at
                                            ? ShieldCheck
                                            : ShieldQuestion
                                    "
                                    class="h-3.5 w-3.5"
                                />
                                {{
                                    user.two_factor_confirmed_at
                                        ? "2FA Aktif"
                                        : "2FA Nonaktif"
                                }}
                            </span>
                        </div>

                        <dl
                            class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2"
                        >
                            <div
                                class="rounded-lg border border-[#e3e3e0] bg-[#FDFDFC] p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                            >
                                <dt
                                    class="flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-wider text-[#A1A09A]"
                                >
                                    <Mail class="h-3 w-3" />
                                    Email
                                </dt>
                                <dd
                                    class="mt-1 truncate text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ user.email }}
                                </dd>
                            </div>

                            <div
                                class="rounded-lg border border-[#e3e3e0] bg-[#FDFDFC] p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                            >
                                <dt
                                    class="flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-wider text-[#A1A09A]"
                                >
                                    <Phone class="h-3 w-3" />
                                    Telepon
                                </dt>
                                <dd
                                    class="mt-1 truncate text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ user.phone_number || "—" }}
                                </dd>
                            </div>

                            <div
                                class="rounded-lg border border-[#e3e3e0] bg-[#FDFDFC] p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                            >
                                <dt
                                    class="flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-wider text-[#A1A09A]"
                                >
                                    <Hash class="h-3 w-3" />
                                    NIP / NIM
                                </dt>
                                <dd
                                    class="mt-1 truncate text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ user.nip || "—" }}
                                </dd>
                            </div>

                            <div
                                class="rounded-lg border border-[#e3e3e0] bg-[#FDFDFC] p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                            >
                                <dt
                                    class="flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-wider text-[#A1A09A]"
                                >
                                    <BriefcaseBusiness class="h-3 w-3" />
                                    Jabatan
                                </dt>
                                <dd
                                    class="mt-1 truncate text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ user.position || "—" }}
                                </dd>
                            </div>

                            <div
                                class="rounded-lg border border-[#e3e3e0] bg-[#FDFDFC] p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                            >
                                <dt
                                    class="flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-wider text-[#A1A09A]"
                                >
                                    <Building2 class="h-3 w-3" />
                                    Fakultas
                                </dt>
                                <dd
                                    class="mt-1 truncate text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ user.faculty?.name || "Tidak terafiliasi" }}
                                </dd>
                            </div>

                            <div
                                class="rounded-lg border border-[#e3e3e0] bg-[#FDFDFC] p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                            >
                                <dt
                                    class="flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-wider text-[#A1A09A]"
                                >
                                    <GraduationCap class="h-3 w-3" />
                                    Program Studi
                                </dt>
                                <dd
                                    class="mt-1 truncate text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ user.department || "—" }}
                                </dd>
                            </div>

                            <div
                                class="rounded-lg border border-[#e3e3e0] bg-[#FDFDFC] p-3 dark:border-[#3E3E3A] dark:bg-[#0a0a0a] sm:col-span-2"
                            >
                                <dt
                                    class="flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-wider text-[#A1A09A]"
                                >
                                    <CalendarClock class="h-3 w-3" />
                                    Riwayat
                                </dt>
                                <dd
                                    class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    <span>
                                        Dibuat: {{ formatDate(user.created_at) }}
                                    </span>
                                    <span>
                                        Diperbarui:
                                        {{ formatDate(user.updated_at) }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div
                        class="flex flex-col-reverse gap-2 border-t border-[#e3e3e0] bg-[#fafafa] p-4 sm:flex-row sm:justify-end dark:border-[#3E3E3A] dark:bg-[#111110]"
                    >
                        <button
                            type="button"
                            class="flex w-full items-center justify-center gap-2 rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm font-medium text-[#f53003] transition hover:bg-[#fff2f2] sm:w-auto dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#FF4433] dark:hover:bg-[#1D0002]"
                            @click="emit('delete', user)"
                        >
                            <Trash2 class="h-4 w-4" />
                            Hapus
                        </button>

                        <button
                            type="button"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#1b1b18] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-black sm:w-auto dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white"
                            @click="emit('edit', user)"
                        >
                            <Pencil class="h-4 w-4" />
                            Edit Pengguna
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>
