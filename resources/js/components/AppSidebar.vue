<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    LayoutGrid,
    ClipboardList,
    History,
    UserRound,
    PackageOpen,
    FilePlus2,
} from 'lucide-vue-next';

import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain, { type NavGroup } from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

import { dashboard } from '@/routes';
import type { NavItem, SharedData } from '@/types';

const page = usePage<SharedData>();

const user = computed(() => page.props.auth?.user ?? null);



const isEmailVerified = computed(() => {
    return Boolean(user.value?.email_verified_at);
});
type UserRole =
    | 'super_admin'
    | 'admin_fakultas'
    | 'sdm'
    | 'dosen'
    | 'mahasiswa';

const userRole = computed<UserRole | null>(() => {
    const role = user.value?.role;

    if (
        role === 'super_admin' ||
        role === 'admin_fakultas' ||
        role === 'sdm' ||
        role === 'dosen' ||
        role === 'mahasiswa'
    ) {
        return role;
    }

    return null;
});

const isUserRole = computed(() => {
    return (
        userRole.value === 'dosen' ||
        userRole.value === 'mahasiswa'
    );
});
/*
|--------------------------------------------------------------------------
| Admin Navigation
|--------------------------------------------------------------------------
*/

const adminNavGroups: NavGroup[] = [
    {
        label: 'Main',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ],
    },
    {
        label: 'Data Master',
        items: [
            {
                title: 'Fakultas & Prodi',
                href: '/admin/faculties',
                icon: PackageOpen,
            },
            {
                title: 'Ruangan & Lokasi',
                href: '/admin/rooms',
                icon: PackageOpen,
            },
            {
                title: 'Kategori Barang',
                href: '/admin/categories',
                icon: PackageOpen,
            },
        ],
    },
    {
        label: 'Inventaris & Operasional',
        items: [
            {
                title: 'Data Barang / Aset',
                href: '/admin/items',
                icon: PackageOpen,
            },
            {
                title: 'Peminjaman',
                href: '/admin/borrowings',
                icon: ClipboardList,
            },
            {
                title: 'Pengadaan Barang',
                href: '/admin/procurements',
                icon: PackageOpen,
            },
        ],
    },
    {
        label: 'Manajemen Akun',
        items: [
            {
                title: 'Data Pengguna',
                href: '/admin/users',
                icon: UserRound,
            },
        ],
    },
];

/*
|--------------------------------------------------------------------------
| User Navigation
|--------------------------------------------------------------------------
|
| Khusus:
| - dosen
| - mahasiswa
|
| Tidak ada menu Procurement.
|
*/

const userNavGroups: NavGroup[] = [
    {
        label: 'Main',
        items: [
            {
                title: 'Dashboard',
                href: '/user/dashboard',
                icon: LayoutGrid,
            },
        ],
    },
    {
        label: 'Peminjaman',
        items: [
            {
                title: 'Ajukan Peminjaman',
                href: '/user/borrowings/create',
                icon: FilePlus2,
            },
            {
                title: 'Peminjaman Saya',
                href: '/user/borrowings',
                icon: ClipboardList,
            },
        ],
    },
];

/*
|--------------------------------------------------------------------------
| Footer Navigation
|--------------------------------------------------------------------------
*/

const adminFooterNavItems: NavItem[] = [
    {
        title: 'Laporan',
        href: '/admin/reports',
        icon: PackageOpen,
    },
    {
        title: 'Log Aktivitas',
        href: '/admin/activity-logs',
        icon: History,
    },
];

const userFooterNavItems: NavItem[] = [
    {
        title: 'Riwayat Peminjaman',
        href: '/user/borrowings/history',
        icon: History,
    },
];

/*
|--------------------------------------------------------------------------
| Dynamic Navigation
|--------------------------------------------------------------------------
*/

const mainNavGroups = computed<NavGroup[]>(() => {
    if (isUserRole.value) {
        return userNavGroups;
    }

    return adminNavGroups;
});

const footerNavItems = computed<NavItem[]>(() => {
    if (isUserRole.value) {
        return userFooterNavItems;
    }

    return adminFooterNavItems;
});

/*
|--------------------------------------------------------------------------
| Skeleton
|--------------------------------------------------------------------------
*/

const skeletonGroupSizes = computed(() => {
    return mainNavGroups.value.map((group) => group.items.length);
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link
                            :href="
                                isUserRole
                                    ? '/user/dashboard'
                                    : dashboard()
                            "
                        >
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain
                v-if="isEmailVerified"
                :groups="mainNavGroups"
            />

            <div
                v-else
                class="flex flex-col gap-5 px-2 py-2"
            >
                <div
                    v-for="(itemCount, groupIndex) in skeletonGroupSizes"
                    :key="groupIndex"
                    class="flex flex-col gap-1.5"
                >
                    <div
                        class="mb-1 h-2.5 w-16 animate-pulse rounded bg-sidebar-accent/50"
                    />

                    <div
                        v-for="itemIndex in itemCount"
                        :key="itemIndex"
                        class="flex items-center gap-2 rounded-md px-2 py-2"
                    >
                        <div
                            class="h-4 w-4 shrink-0 animate-pulse rounded bg-sidebar-accent/60"
                        />

                        <div
                            class="h-2.5 flex-1 animate-pulse rounded bg-sidebar-accent/40"
                        />
                    </div>
                </div>
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter
                v-if="isEmailVerified"
                :items="footerNavItems"
            />

            <div
                v-else
                class="flex flex-col gap-1.5 px-2 py-2"
            >
                <div
                    v-for="itemIndex in footerNavItems.length"
                    :key="itemIndex"
                    class="flex items-center gap-2 rounded-md px-2 py-2"
                >
                    <div
                        class="h-4 w-4 shrink-0 animate-pulse rounded bg-sidebar-accent/60"
                    />

                    <div
                        class="h-2.5 flex-1 animate-pulse rounded bg-sidebar-accent/40"
                    />
                </div>
            </div>

            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
