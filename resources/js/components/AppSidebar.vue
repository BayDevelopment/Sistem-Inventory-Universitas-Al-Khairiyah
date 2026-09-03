<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Building2,
    DoorOpen,
    Tags,
    Package,
    ClipboardList,
    ShoppingCart,
    Users,
    FileText,
    History,
    FolderGit2,
    BookOpen,
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
import type { NavItem } from '@/types';

const mainNavGroups: NavGroup[] = [
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
                icon: Building2,
            },
            {
                title: 'Ruangan & Lokasi',
                href: '/admin/rooms',
                icon: DoorOpen,
            },
            {
                title: 'Kategori Barang',
                href: '/admin/categories',
                icon: Tags,
            },
        ],
    },
    {
        label: 'Inventaris & Operasional',
        items: [
            {
                title: 'Data Barang / Aset',
                href: '/admin/items',
                icon: Package,
            },
            {
                title: 'Peminjaman',
                href: '/admin/borrowings',
                icon: ClipboardList,
            },
            {
                title: 'Pengadaan Barang',
                href: '/admin/procurements',
                icon: ShoppingCart,
            },
        ],
    },
    {
        label: 'Manajemen Akun',
        items: [
            {
                title: 'Data Pengguna',
                href: '/admin/users',
                icon: Users,
            },
        ],
    },
    {
        label: 'Sistem & Laporan',
        items: [
            {
                title: 'Laporan',
                href: '/admin/reports',
                icon: FileText,
            },
            {
                title: 'Log Aktivitas',
                href: '/admin/activity-logs',
                icon: History,
            },
        ],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :groups="mainNavGroups" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
