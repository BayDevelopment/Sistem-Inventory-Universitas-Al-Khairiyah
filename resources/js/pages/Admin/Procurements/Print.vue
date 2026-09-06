<script setup lang="ts">
import { computed, onMounted } from "vue";
import { Head, usePage } from "@inertiajs/vue3";

interface Faculty {
    id: number;
    code?: string | null;
    name: string;
}

interface Room {
    id: number;
    code?: string | null;
    name: string;
    building?: string | null;
    floor?: string | null;
}

interface User {
    id: number;
    name: string;
}

interface Procurement {
    id: number;
    faculty_id: number;
    requested_by: number;
    room_id?: number | null;

    item_name: string;
    quantity: number;
    type: "replacement" | "new_item";
    reason: string;

    requester_signature?: string | null;
    requested_at?: string | null;

    status: "pending" | "approved" | "rejected" | "completed";

    processed_by?: number | null;
    approver_signature?: string | null;
    processed_at?: string | null;

    admin_note?: string | null;

    faculty?: Faculty | null;
    room?: Room | null;
    requester?: User | null;
    processor?: User | null;
}

const props = defineProps<{
    procurement: Procurement;
}>();

const page = usePage();

const procurement = computed(() => props.procurement);

const typeLabel = computed(() => {
    return procurement.value.type === "replacement"
        ? "Penggantian Barang Rusak"
        : "Pengadaan Barang Baru";
});

const statusLabel = computed(() => {
    const labels: Record<Procurement["status"], string> = {
        pending: "Menunggu Verifikasi",
        approved: "Disetujui",
        rejected: "Ditolak",
        completed: "Selesai",
    };

    return labels[procurement.value.status] ?? procurement.value.status;
});

const statusClass = computed(() => {
    const classes: Record<Procurement["status"], string> = {
        pending: "status-pending",
        approved: "status-approved",
        rejected: "status-rejected",
        completed: "status-completed",
    };

    return classes[procurement.value.status] ?? "";
});

const roomLabel = computed(() => {
    const room = procurement.value.room;

    if (!room) {
        return "-";
    }

    let result = room.name;

    if (room.code) {
        result = `${room.code} - ${result}`;
    }

    return result;
});

const roomLocation = computed(() => {
    const room = procurement.value.room;

    if (!room) {
        return "-";
    }

    const parts: string[] = [];

    if (room.building) {
        parts.push(`Gedung ${room.building}`);
    }

    if (room.floor) {
        parts.push(`Lantai ${room.floor}`);
    }

    return parts.length > 0 ? parts.join(" • ") : "-";
});

function formatDate(value?: string | null): string {
    if (!value) {
        return "-";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
}

function formatDateOnly(value?: string | null): string {
    if (!value) {
        return "-";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric",
    }).format(date);
}

function getSignatureUrl(signature?: string | null): string | null {
    if (!signature) {
        return null;
    }

    if (
        signature.startsWith("http://") ||
        signature.startsWith("https://") ||
        signature.startsWith("/")
    ) {
        return signature;
    }

    return `/storage/${signature}`;
}

const requesterSignatureUrl = computed(() => {
    return getSignatureUrl(procurement.value.requester_signature);
});

const approverSignatureUrl = computed(() => {
    return getSignatureUrl(procurement.value.approver_signature);
});

function printDocument() {
    window.print();
}

onMounted(() => {
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<template>
    <Head>
        <title>
            Cetak Pengadaan #{{ procurement.id }}
        </title>
    </Head>

    <div class="print-page min-h-screen bg-gray-100 py-8">
        <!-- Action Bar -->
        <div class="print:hidden mx-auto mb-5 flex w-full max-w-[210mm] items-center justify-between px-4">
            <button
                type="button"
                onclick="window.close()"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
            >
                Kembali
            </button>

            <button
                type="button"
                @click="printDocument"
                class="rounded-lg bg-[#f53003] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d92a00]"
            >
                Cetak / Simpan PDF
            </button>
        </div>

        <!-- A4 Document -->
        <main
            class="a4-page mx-auto w-full max-w-[210mm] min-h-[297mm] bg-white px-[15mm] py-[12mm] text-[#1b1b18] shadow-lg"
        >
            <!-- ========================================= -->
            <!-- KOP SURAT -->
            <!-- ========================================= -->
            <header class="border-b-2 border-black pb-3">
                <img
                    src="/images/kop-surat.png"
                    alt="Kop Surat"
                    class="block h-auto w-full object-contain"
                />
            </header>

            <!-- ========================================= -->
            <!-- JUDUL -->
            <!-- ========================================= -->
            <section class="mt-7 text-center">
                <h1 class="text-[18px] font-bold uppercase tracking-wide">
                    LAPORAN PENGADAAN BARANG
                </h1>

                <p class="mt-1 text-sm">
                    Nomor Pengadaan:
                    <span class="font-semibold">
                        #{{ procurement.id }}
                    </span>
                </p>
            </section>

            <!-- ========================================= -->
            <!-- INFORMASI PENGADAAN -->
            <!-- ========================================= -->
            <section class="mt-7">
                <h2 class="mb-3 border-b border-gray-300 pb-2 text-sm font-bold uppercase">
                    A. Informasi Pengadaan
                </h2>

                <table class="w-full border-collapse text-sm">
                    <tbody>
                        <tr>
                            <td class="w-[35%] py-1.5 font-medium">
                                Fakultas
                            </td>

                            <td class="w-[5%] py-1.5">
                                :
                            </td>

                            <td class="py-1.5">
                                {{ procurement.faculty?.name ?? "-" }}

                                <span
                                    v-if="procurement.faculty?.code"
                                    class="text-gray-600"
                                >
                                    ({{ procurement.faculty.code }})
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td class="py-1.5 font-medium">
                                Ruangan
                            </td>

                            <td class="py-1.5">
                                :
                            </td>

                            <td class="py-1.5">
                                {{ roomLabel }}

                                <span
                                    v-if="roomLocation !== '-'"
                                    class="text-gray-600"
                                >
                                    — {{ roomLocation }}
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td class="py-1.5 font-medium">
                                Jenis Pengadaan
                            </td>

                            <td class="py-1.5">
                                :
                            </td>

                            <td class="py-1.5">
                                {{ typeLabel }}
                            </td>
                        </tr>

                        <tr>
                            <td class="py-1.5 font-medium">
                                Tanggal Pengajuan
                            </td>

                            <td class="py-1.5">
                                :
                            </td>

                            <td class="py-1.5">
                                {{ formatDateOnly(procurement.requested_at) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="py-1.5 font-medium">
                                Pengaju
                            </td>

                            <td class="py-1.5">
                                :
                            </td>

                            <td class="py-1.5">
                                {{ procurement.requester?.name ?? "-" }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- ========================================= -->
            <!-- DETAIL BARANG -->
            <!-- ========================================= -->
            <section class="mt-7">
                <h2 class="mb-3 border-b border-gray-300 pb-2 text-sm font-bold uppercase">
                    B. Detail Barang
                </h2>

                <table class="w-full border-collapse border border-black text-sm">
                    <thead>
                        <tr>
                            <th
                                class="w-[8%] border border-black px-3 py-2 text-center"
                            >
                                No.
                            </th>

                            <th
                                class="border border-black px-3 py-2 text-left"
                            >
                                Nama Barang
                            </th>

                            <th
                                class="w-[18%] border border-black px-3 py-2 text-center"
                            >
                                Jumlah
                            </th>

                            <th
                                class="w-[25%] border border-black px-3 py-2 text-center"
                            >
                                Jenis
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td class="border border-black px-3 py-3 text-center">
                                1
                            </td>

                            <td class="border border-black px-3 py-3">
                                {{ procurement.item_name }}
                            </td>

                            <td class="border border-black px-3 py-3 text-center">
                                {{ procurement.quantity }} Unit
                            </td>

                            <td class="border border-black px-3 py-3 text-center">
                                {{ typeLabel }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- ========================================= -->
            <!-- ALASAN -->
            <!-- ========================================= -->
            <section class="mt-7">
                <h2 class="mb-3 border-b border-gray-300 pb-2 text-sm font-bold uppercase">
                    C. Alasan Pengadaan
                </h2>

                <div
                    class="min-h-[80px] rounded border border-gray-300 p-4 text-sm leading-6"
                >
                    {{ procurement.reason }}
                </div>
            </section>

            <!-- ========================================= -->
            <!-- STATUS VERIFIKASI -->
            <!-- ========================================= -->
            <section class="mt-7">
                <h2 class="mb-3 border-b border-gray-300 pb-2 text-sm font-bold uppercase">
                    D. Verifikasi
                </h2>

                <table class="w-full border-collapse text-sm">
                    <tbody>
                        <tr>
                            <td class="w-[35%] py-1.5 font-medium">
                                Status
                            </td>

                            <td class="w-[5%] py-1.5">
                                :
                            </td>

                            <td class="py-1.5">
                                <span
                                    class="font-bold uppercase"
                                    :class="statusClass"
                                >
                                    {{ statusLabel }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="procurement.processor">
                            <td class="py-1.5 font-medium">
                                Diverifikasi Oleh
                            </td>

                            <td class="py-1.5">
                                :
                            </td>

                            <td class="py-1.5">
                                {{ procurement.processor.name }}
                            </td>
                        </tr>

                        <tr v-if="procurement.processed_at">
                            <td class="py-1.5 font-medium">
                                Tanggal Verifikasi
                            </td>

                            <td class="py-1.5">
                                :
                            </td>

                            <td class="py-1.5">
                                {{ formatDate(procurement.processed_at) }}
                            </td>
                        </tr>

                        <tr v-if="procurement.admin_note">
                            <td class="py-1.5 align-top font-medium">
                                Catatan
                            </td>

                            <td class="py-1.5 align-top">
                                :
                            </td>

                            <td class="py-1.5">
                                {{ procurement.admin_note }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- ========================================= -->
            <!-- PERNYATAAN -->
            <!-- ========================================= -->
            <section class="mt-7 text-sm leading-6">
                <p>
                    Demikian laporan pengadaan barang ini dibuat untuk
                    dipergunakan sebagaimana mestinya dan sebagai dokumen
                    administrasi pengadaan barang pada lingkungan universitas.
                </p>
            </section>

            <!-- ========================================= -->
            <!-- TANDA TANGAN -->
            <!-- ========================================= -->
            <section class="mt-14">
                <div class="grid grid-cols-2 gap-12 text-center text-sm">
                    <!-- Pengaju -->
                    <div>
                        <p class="mb-1">
                            Pengaju,
                        </p>

                        <p class="font-semibold">
                            Admin Fakultas
                        </p>

                        <div class="mx-auto flex h-[85px] items-center justify-center">
                            <template v-if="requesterSignatureUrl">
                                <img
                                    :src="requesterSignatureUrl"
                                    alt="Tanda tangan pengaju"
                                    class="max-h-[75px] max-w-[150px] object-contain"
                                />
                            </template>

                            <template v-else>
                                <div class="h-[75px]" />
                            </template>
                        </div>

                        <div class="mx-auto w-[180px] border-b border-black" />

                        <p class="mt-2 font-semibold">
                            {{ procurement.requester?.name ?? "-" }}
                        </p>

                        <p class="mt-1 text-xs text-gray-600">
                            Tanggal:
                            {{ formatDateOnly(procurement.requested_at) }}
                        </p>
                    </div>

                    <!-- Verifikator -->
                    <div>
                        <p class="mb-1">
                            Diverifikasi oleh,
                        </p>

                        <p class="font-semibold">
                            Super Admin
                        </p>

                        <div class="mx-auto flex h-[85px] items-center justify-center">
                            <template v-if="approverSignatureUrl">
                                <img
                                    :src="approverSignatureUrl"
                                    alt="Tanda tangan verifikator"
                                    class="max-h-[75px] max-w-[150px] object-contain"
                                />
                            </template>

                            <template v-else>
                                <div class="h-[75px]" />
                            </template>
                        </div>

                        <div class="mx-auto w-[180px] border-b border-black" />

                        <p class="mt-2 font-semibold">
                            {{ procurement.processor?.name ?? "-" }}
                        </p>

                        <p class="mt-1 text-xs text-gray-600">
                            Tanggal:
                            {{ formatDateOnly(procurement.processed_at) }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- ========================================= -->
            <!-- FOOTER -->
            <!-- ========================================= -->
            <footer class="mt-12 border-t border-gray-300 pt-3 text-center text-[10px] text-gray-500">
                <p>
                    Dokumen ini dicetak dari Sistem Informasi Inventaris.
                </p>

                <p class="mt-1">
                    Nomor Pengadaan #{{ procurement.id }}
                </p>
            </footer>
        </main>
    </div>
</template>

<style>
@page {
    size: A4;
    margin: 0;
}

html,
body {
    margin: 0;
    padding: 0;
}

@media print {
    body {
        background: white !important;
    }

    .print-page {
        min-height: auto !important;
        padding: 0 !important;
        background: white !important;
    }

    .a4-page {
        width: 210mm !important;
        min-height: 297mm !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 12mm 15mm !important;
        box-shadow: none !important;
    }

    .print\:hidden {
        display: none !important;
    }
}

@media screen {
    .a4-page {
        min-height: 297mm;
    }
}

.status-pending {
    color: #b45309;
}

.status-approved {
    color: #15803d;
}

.status-rejected {
    color: #dc2626;
}

.status-completed {
    color: #1d4ed8;
}
</style>
