<script setup lang="ts">
import { computed, ref } from "vue";
import { Head, router } from "@inertiajs/vue3";

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

interface Faculty {
    id: number;
    name: string;
    code?: string | null;

    dean?: string | null;
    dean_nip?: string | null;
    dean_signature?: string | null;

    letterhead_path?: string | null;
}

interface Room {
    id: number;
    name: string;
    code?: string | null;
    building?: string | null;
    floor?: string | null;
}

interface User {
    id: number;
    name: string;
    position?: string | null;
    nip?: string | null;
}

interface Procurement {
    id: number;

    document_number?: string | null;

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

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    procurement: Procurement;
}>();

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

const DEFAULT_LETTERHEAD_URL = "/images/kop-surat.png";

/*
|--------------------------------------------------------------------------
| Storage URL Resolver
|--------------------------------------------------------------------------
|
| Supports:
| - https://...
| - http://...
| - data:image/...
| - /images/...
| - /storage/...
| - storage/...
| - filename/path stored inside Laravel storage
|
*/

function resolveStorageUrl(path?: string | null): string | null {
    if (!path) {
        return null;
    }

    const value = String(path).trim();

    if (!value) {
        return null;
    }

    // External URL
    if (/^https?:\/\//i.test(value)) {
        return value;
    }

    // Base64 image
    if (/^data:image\//i.test(value)) {
        return value;
    }

    // Absolute URL/path
    if (value.startsWith("/")) {
        return value;
    }

    const normalized = value.replace(/^\/+/, "");

    // Already Laravel public storage path
    if (normalized.startsWith("storage/")) {
        return `/${normalized}`;
    }

    // Default Laravel storage path
    return `/storage/${normalized}`;
}

/*
|--------------------------------------------------------------------------
| Date Formatter
|--------------------------------------------------------------------------
*/

function parseDate(value?: string | null): Date | null {
    if (!value) {
        return null;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return null;
    }

    return date;
}

function formatDate(value?: string | null): string {
    const date = parseDate(value);

    if (!date) {
        return "-";
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
    const date = parseDate(value);

    if (!date) {
        return "-";
    }

    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric",
    }).format(date);
}

/*
|--------------------------------------------------------------------------
| Computed - Procurement
|--------------------------------------------------------------------------
*/

const typeLabel = computed(() => {
    switch (props.procurement.type) {
        case "replacement":
            return "Penggantian Barang";

        case "new_item":
            return "Barang Baru";

        default:
            return "-";
    }
});

const statusLabel = computed(() => {
    switch (props.procurement.status) {
        case "pending":
            return "Menunggu Verifikasi";

        case "approved":
            return "Disetujui";

        case "rejected":
            return "Ditolak";

        case "completed":
            return "Selesai";

        default:
            return "-";
    }
});

const statusClass = computed(() => {
    switch (props.procurement.status) {
        case "pending":
            return "status-pending";

        case "approved":
            return "status-approved";

        case "rejected":
            return "status-rejected";

        case "completed":
            return "status-completed";

        default:
            return "status-default";
    }
});

/*
|--------------------------------------------------------------------------
| Computed - Faculty
|--------------------------------------------------------------------------
*/

const facultyName = computed(() => {
    return props.procurement.faculty?.name?.trim() || "-";
});

const facultyCode = computed(() => {
    return props.procurement.faculty?.code?.trim() || "-";
});

const deanName = computed(() => {
    return props.procurement.faculty?.dean?.trim() || "-";
});

const deanNip = computed(() => {
    return props.procurement.faculty?.dean_nip?.trim() || null;
});

/*
|--------------------------------------------------------------------------
| Computed - Room
|--------------------------------------------------------------------------
*/

const roomLabel = computed(() => {
    const room = props.procurement.room;

    if (!room) {
        return "-";
    }

    if (room.code && room.name) {
        return `${room.code} - ${room.name}`;
    }

    return room.name || room.code || "-";
});

const roomLocation = computed(() => {
    const room = props.procurement.room;

    if (!room) {
        return "";
    }

    const parts: string[] = [];

    if (room.building?.trim()) {
        parts.push(room.building.trim());
    }

    if (room.floor !== undefined && room.floor !== null) {
        const floor = String(room.floor).trim();

        if (floor) {
            parts.push(`Lt. ${floor}`);
        }
    }

    return parts.join(" · ");
});

/*
|--------------------------------------------------------------------------
| Computed - Requester
|--------------------------------------------------------------------------
*/

const requesterName = computed(() => {
    return props.procurement.requester?.name?.trim() || "-";
});

const requesterPosition = computed(() => {
    return props.procurement.requester?.position?.trim() || "Pemohon";
});

const requesterNip = computed(() => {
    return props.procurement.requester?.nip?.trim() || null;
});

/*
|--------------------------------------------------------------------------
| Computed - Processor
|--------------------------------------------------------------------------
*/

const processorName = computed(() => {
    return props.procurement.processor?.name?.trim() || "-";
});

const processorPosition = computed(() => {
    return props.procurement.processor?.position?.trim() || "Verifikator";
});

const processorNip = computed(() => {
    return props.procurement.processor?.nip?.trim() || null;
});

/*
|--------------------------------------------------------------------------
| Computed - Images
|--------------------------------------------------------------------------
*/

const letterheadUrl = computed(() => {
    return (
        resolveStorageUrl(props.procurement.faculty?.letterhead_path) ??
        DEFAULT_LETTERHEAD_URL
    );
});

const requesterSignatureUrl = computed(() => {
    return resolveStorageUrl(props.procurement.requester_signature);
});

const processorSignatureUrl = computed(() => {
    return resolveStorageUrl(props.procurement.approver_signature);
});

const deanSignatureUrl = computed(() => {
    return resolveStorageUrl(props.procurement.faculty?.dean_signature);
});

const hasRequesterSignature = computed(() => {
    return Boolean(requesterSignatureUrl.value);
});

const hasProcessorSignature = computed(() => {
    return Boolean(processorSignatureUrl.value);
});

const hasDeanSignature = computed(() => {
    return Boolean(deanSignatureUrl.value);
});

/*
|--------------------------------------------------------------------------
| Letterhead Fallback
|--------------------------------------------------------------------------
*/

const letterheadFailed = ref(false);

const activeLetterheadUrl = computed(() => {
    if (letterheadFailed.value) {
        return DEFAULT_LETTERHEAD_URL;
    }

    return letterheadUrl.value;
});

function handleLetterheadError() {
    if (activeLetterheadUrl.value !== DEFAULT_LETTERHEAD_URL) {
        letterheadFailed.value = true;
    }
}

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
*/

function goBack() {
    router.visit("/admin/procurements");
}

/*
|--------------------------------------------------------------------------
| Print (Server-side PDF via dompdf)
|--------------------------------------------------------------------------
*/

function printDocument() {
    window.open(
        route("admin.procurements.pdf", props.procurement.id),
        "_blank"
    );
}
</script>

<template>
    <Head :title="`Cetak Pengadaan #${procurement.id}`" />

    <div
        class="print-page min-h-screen overflow-x-hidden bg-slate-100 text-[#1b1b18] dark:bg-[#0f0f0e] dark:text-[#EDEDEC]"
    >
        <!-- ============================================================
             Decorative Background
        ============================================================= -->

        <div
            class="pointer-events-none fixed inset-0 overflow-hidden print:hidden"
            aria-hidden="true"
        >
            <div
                class="absolute -left-32 -top-32 h-72 w-72 rounded-full bg-red-500/5 blur-3xl"
            />

            <div
                class="absolute -bottom-32 -right-32 h-80 w-80 rounded-full bg-slate-500/5 blur-3xl"
            />
        </div>

        <!-- ============================================================
             Toolbar
        ============================================================= -->

        <div
            class="print-toolbar relative z-30 mx-auto flex w-full max-w-[210mm] items-center justify-between gap-3 px-3 py-3 sm:px-4 sm:py-4 lg:px-0 print:hidden"
        >
            <button
                type="button"
                @click="goBack"
                class="inline-flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm font-medium text-[#1b1b18] shadow-sm transition hover:border-[#1b1b18] hover:bg-slate-50 active:scale-[0.98] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:border-[#EDEDEC] dark:hover:bg-[#20201e]"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.8"
                    stroke="currentColor"
                    class="h-4 w-4 shrink-0"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
                    />
                </svg>

                <span>Kembali</span>
            </button>

            <button
                type="button"
                @click="printDocument"
                class="inline-flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-lg bg-[#1b1b18] px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-black active:scale-[0.98] dark:bg-white dark:text-[#1b1b18] dark:hover:bg-slate-200"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.8"
                    stroke="currentColor"
                    class="h-4 w-4 shrink-0"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6.75 9V5.25A1.25 1.25 0 0 1 8 4h8a1.25 1.25 0 0 1 1.25 1.25V9m-10.5 6h8.5M7.5 20h9a1.5 1.5 0 0 0 1.5-1.5V14H6v4.5A1.5 1.5 0 0 0 7.5 20ZM5 9h14a2 2 0 0 1 2 2v3h-4v-2H7v2H3v-3a2 2 0 0 1 2-2Z"
                    />
                </svg>

                <span>Cetak Dokumen</span>
            </button>
        </div>

        <!-- ============================================================
             Main
        ============================================================= -->

        <main
            class="relative z-10 w-full px-2 pb-6 sm:px-3 lg:px-4 print:px-0 print:pb-0"
        >
            <!-- ========================================================
                 A4 DOCUMENT
            ========================================================= -->

            <article
                class="a4-page mx-auto w-full max-w-[210mm] overflow-hidden bg-white text-[#1b1b18] shadow-xl dark:bg-[#ffffff] dark:text-[#1b1b18] print:shadow-none"
            >
                <!-- ====================================================
                     Top Accent
                ===================================================== -->

                <div class="h-1.5 w-full bg-[#991b1b]" />

                <div class="h-1 w-full bg-[#1b1b18]" />

                <!-- ====================================================
                     Document Content
                ===================================================== -->

                <div
                    class="document-content px-5 py-5 sm:px-8 sm:py-7 md:px-10 md:py-8"
                >
                    <!-- ==================================================
                         Letterhead
                    =================================================== -->

                    <header class="letterhead mb-6">
                        <img
                            :src="activeLetterheadUrl"
                            alt="Kop surat"
                            class="letterhead-image mx-auto block h-auto object-contain object-center"
                            @error="handleLetterheadError"
                        />
                    </header>

                    <!-- ==================================================
                         Title
                    =================================================== -->

                    <section class="document-title mb-6 text-center">
                        <h1
                            class="text-[22px] font-bold uppercase tracking-[0.08em] leading-tight sm:text-[25px]"
                        >
                            Pengadaan Barang
                        </h1>

                        <p
                            class="mt-2 text-[11px] font-medium text-[#555] sm:text-xs"
                        >
                            Nomor Dokumen:
                            <span class="font-semibold text-[#1b1b18]">
                                {{
                                    procurement.document_number ||
                                    `PROC/${procurement.id}`
                                }}
                            </span>
                        </p>

                        <div class="mt-3 flex justify-center">
                            <span
                                class="inline-flex items-center rounded-full border px-3 py-1 text-[10px] font-bold uppercase tracking-wide"
                                :class="statusClass"
                            >
                                {{ statusLabel }}
                            </span>
                        </div>
                    </section>

                    <!-- ==================================================
                         A. Informasi Pengadaan
                    =================================================== -->

                    <section class="document-section avoid-break">
                        <div class="section-heading">
                            <span class="section-number"> A </span>

                            <h2>Informasi Pengadaan</h2>
                        </div>

                        <div class="detail-grid">
                            <div class="detail-row">
                                <div class="detail-label">Fakultas</div>

                                <div class="detail-value">
                                    {{ facultyName }}
                                </div>
                            </div>

                            <div class="detail-row">
                                <div class="detail-label">Pemohon</div>

                                <div class="detail-value">
                                    {{ requesterName }}
                                </div>
                            </div>

                            <div class="detail-row">
                                <div class="detail-label">Jabatan Pemohon</div>

                                <div class="detail-value">
                                    {{ requesterPosition }}
                                </div>
                            </div>

                            <div v-if="requesterNip" class="detail-row">
                                <div class="detail-label">NIP Pemohon</div>

                                <div class="detail-value">
                                    {{ requesterNip }}
                                </div>
                            </div>

                            <div class="detail-row">
                                <div class="detail-label">
                                    Tanggal Pengajuan
                                </div>

                                <div class="detail-value">
                                    {{ formatDate(procurement.requested_at) }}
                                </div>
                            </div>

                            <div
                                v-if="procurement.processed_at"
                                class="detail-row"
                            >
                                <div class="detail-label">
                                    Tanggal Verifikasi
                                </div>

                                <div class="detail-value">
                                    {{ formatDate(procurement.processed_at) }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ==================================================
                         B. Detail Barang
                    =================================================== -->

                    <section class="document-section avoid-break">
                        <div class="section-heading">
                            <span class="section-number"> B </span>

                            <h2>Detail Barang</h2>
                        </div>

                        <div class="document-table-wrapper overflow-x-auto">
                            <table
                                class="document-table w-full min-w-[560px] border-collapse"
                            >
                                <thead>
                                    <tr>
                                        <th class="w-12">No.</th>

                                        <th>Nama Barang</th>

                                        <th class="w-24">Jumlah</th>

                                        <th class="w-36">Jenis</th>

                                        <th>Ruangan</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td class="text-center">1</td>

                                        <td class="font-semibold">
                                            {{ procurement.item_name }}
                                        </td>

                                        <td class="text-center">
                                            {{ procurement.quantity }}
                                        </td>

                                        <td>
                                            {{ typeLabel }}
                                        </td>

                                        <td>
                                            <div>
                                                {{ roomLabel }}
                                            </div>

                                            <div
                                                v-if="roomLocation"
                                                class="mt-0.5 text-[9px] text-[#666]"
                                            >
                                                {{ roomLocation }}
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- ==================================================
                         C. Alasan Pengadaan
                    =================================================== -->

                    <section class="document-section avoid-break">
                        <div class="section-heading">
                            <span class="section-number"> C </span>

                            <h2>Alasan Pengadaan</h2>
                        </div>

                        <div class="reason-box">
                            <p class="whitespace-pre-line break-words">
                                {{ procurement.reason || "-" }}
                            </p>
                        </div>
                    </section>

                    <!-- ==================================================
                         D. Verifikasi
                    =================================================== -->

                    <section class="document-section avoid-break">
                        <div class="section-heading">
                            <span class="section-number"> D </span>

                            <h2>Verifikasi</h2>
                        </div>

                        <div class="detail-grid">
                            <div class="detail-row">
                                <div class="detail-label">Status</div>

                                <div class="detail-value">
                                    <span
                                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-[9px] font-bold uppercase"
                                        :class="statusClass"
                                    >
                                        {{ statusLabel }}
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="procurement.processor"
                                class="detail-row"
                            >
                                <div class="detail-label">Verifikator</div>

                                <div class="detail-value">
                                    {{ processorName }}
                                </div>
                            </div>

                            <div
                                v-if="procurement.processor"
                                class="detail-row"
                            >
                                <div class="detail-label">
                                    Jabatan Verifikator
                                </div>

                                <div class="detail-value">
                                    {{ processorPosition }}
                                </div>
                            </div>

                            <div v-if="processorNip" class="detail-row">
                                <div class="detail-label">NIP Verifikator</div>

                                <div class="detail-value">
                                    {{ processorNip }}
                                </div>
                            </div>

                            <div
                                v-if="procurement.processed_at"
                                class="detail-row"
                            >
                                <div class="detail-label">Waktu Verifikasi</div>

                                <div class="detail-value">
                                    {{ formatDate(procurement.processed_at) }}
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="procurement.admin_note"
                            class="note-box mt-4"
                        >
                            <div
                                class="mb-1 text-[9px] font-bold uppercase tracking-wide text-[#555]"
                            >
                                Catatan Verifikasi
                            </div>

                            <p class="whitespace-pre-line break-words">
                                {{ procurement.admin_note }}
                            </p>
                        </div>
                    </section>

                    <!-- ==================================================
                         Pernyataan
                    =================================================== -->

                    <section class="document-section statement-box avoid-break">
                        <p>
                            Dengan ini pengajuan pengadaan barang tersebut telah
                            dibuat berdasarkan kebutuhan yang sebenarnya dan
                            dapat dipertanggungjawabkan sesuai dengan ketentuan
                            yang berlaku.
                        </p>
                    </section>

                    <!-- ==================================================
                         E. Tanda Tangan
                    =================================================== -->

                    <section class="document-section avoid-break">
                        <div class="section-heading">
                            <span class="section-number"> E </span>

                            <h2>Tanda Tangan</h2>
                        </div>

                        <div
                            class="signature-grid grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <!-- ==========================================
                                 Pengaju
                            =========================================== -->

                            <div class="signature-card">
                                <div class="signature-role">Pengaju</div>

                                <div class="signature-space">
                                    <img
                                        v-if="hasRequesterSignature"
                                        :src="requesterSignatureUrl!"
                                        alt="Tanda tangan pengaju"
                                        class="signature-image"
                                    />

                                    <span v-else class="signature-placeholder">
                                        Tanda tangan belum tersedia
                                    </span>
                                </div>

                                <div class="signature-name">
                                    {{ requesterName }}
                                </div>

                                <div class="signature-position">
                                    {{ requesterPosition }}
                                </div>

                                <div v-if="requesterNip" class="signature-nip">
                                    NIP. {{ requesterNip }}
                                </div>
                            </div>

                            <!-- ==========================================
                                 Verifikator
                            =========================================== -->

                            <div class="signature-card">
                                <div class="signature-role">Verifikator</div>

                                <div class="signature-space">
                                    <img
                                        v-if="hasProcessorSignature"
                                        :src="processorSignatureUrl!"
                                        alt="Tanda tangan verifikator"
                                        class="signature-image"
                                    />

                                    <span v-else class="signature-placeholder">
                                        Tanda tangan belum tersedia
                                    </span>
                                </div>

                                <div class="signature-name">
                                    {{ processorName }}
                                </div>

                                <div class="signature-position">
                                    {{ processorPosition }}
                                </div>

                                <div v-if="processorNip" class="signature-nip">
                                    NIP. {{ processorNip }}
                                </div>
                            </div>

                            <!-- ==========================================
                                 Dekan
                            =========================================== -->

                            <div class="signature-card">
                                <div class="signature-role">Mengetahui,</div>

                                <div class="signature-subrole">
                                    Dekan {{ facultyName }}
                                </div>

                                <div class="signature-space">
                                    <img
                                        v-if="hasDeanSignature"
                                        :src="deanSignatureUrl!"
                                        alt="Tanda tangan dekan"
                                        class="signature-image"
                                    />

                                    <span v-else class="signature-placeholder">
                                        Tanda tangan belum tersedia
                                    </span>
                                </div>

                                <div class="signature-name">
                                    {{ deanName }}
                                </div>

                                <div class="signature-position">Dekan</div>

                                <div v-if="deanNip" class="signature-nip">
                                    NIP. {{ deanNip }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ==================================================
                         Footer
                    =================================================== -->

                    <footer
                        class="document-footer mt-8 flex flex-col gap-1 border-t border-[#d6d6d2] pt-3 text-[8px] text-[#666] sm:flex-row sm:items-center sm:justify-between"
                    >
                        <span> Dokumen Pengadaan Barang </span>

                        <span>
                            Dicetak:
                            {{ formatDateOnly(new Date().toISOString()) }}
                        </span>
                    </footer>
                </div>

                <!-- ====================================================
                     Bottom Accent
                ===================================================== -->

                <div class="h-1 w-full bg-[#1b1b18]" />

                <div class="h-1.5 w-full bg-[#991b1b]" />
            </article>
        </main>
    </div>
</template>

<style>
/*
|--------------------------------------------------------------------------
| Base
|--------------------------------------------------------------------------
*/

html,
body {
    margin: 0;
    padding: 0;
    min-height: 100%;
}

* {
    box-sizing: border-box;
}

body {
    background: #f1f5f9;
}

/*
|--------------------------------------------------------------------------
| Print Page
|--------------------------------------------------------------------------
*/

.print-page {
    width: 100%;
    min-height: 100vh;
    overflow-x: hidden;
}

/*
|--------------------------------------------------------------------------
| A4 Page
|--------------------------------------------------------------------------
*/

.a4-page {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    position: relative;
    background: #ffffff;
    overflow: hidden;
}

/*
|--------------------------------------------------------------------------
| Document Content
|--------------------------------------------------------------------------
*/

.document-content {
    width: 100%;
}

/*
|--------------------------------------------------------------------------
| Letterhead
|--------------------------------------------------------------------------
*/

.letterhead {
    margin-left: -10mm;
    margin-right: -10mm;
    display: flex;
    justify-content: center;
}

.letterhead-image {
    width: calc(100% + 20mm);
    max-width: none;
    min-height: 34mm;
}

/*
|--------------------------------------------------------------------------
| Section
|--------------------------------------------------------------------------
*/

.document-section {
    margin-top: 7mm;
    break-inside: avoid;
    page-break-inside: avoid;
}

.document-section:first-of-type {
    margin-top: 0;
}

/*
|--------------------------------------------------------------------------
| Section Heading
|--------------------------------------------------------------------------
*/

.section-heading {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 10px;
    padding-bottom: 6px;
    border-bottom: 1px solid #d6d6d2;
}

.section-heading h2 {
    margin: 0;
    font-size: 12px;
    line-height: 1.35;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.section-number {
    display: inline-flex;
    width: 23px;
    height: 23px;
    flex: 0 0 23px;
    align-items: center;
    justify-content: center;
    border-radius: 5px;
    background: #1b1b18;
    color: #ffffff;
    font-size: 10px;
    font-weight: 800;
}

/*
|--------------------------------------------------------------------------
| Detail Grid
|--------------------------------------------------------------------------
*/

.detail-grid {
    width: 100%;
    border: 1px solid #d9d9d5;
    border-radius: 7px;
    overflow: hidden;
}

.detail-row {
    display: grid;
    grid-template-columns: 155px minmax(0, 1fr);
    border-bottom: 1px solid #e5e5e1;
}

.detail-row:last-child {
    border-bottom: 0;
}

.detail-label {
    padding: 8px 10px;
    background: #f7f7f5;
    color: #555550;
    font-size: 9px;
    font-weight: 700;
}

.detail-value {
    min-width: 0;
    padding: 8px 10px;
    color: #1b1b18;
    font-size: 9.5px;
    font-weight: 500;
    overflow-wrap: anywhere;
}

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

.document-table {
    font-size: 9px;
}

.document-table th {
    padding: 8px 7px;
    border: 1px solid #cfcfca;
    background: #f1f1ee;
    color: #252522;
    text-align: left;
    font-size: 8px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.document-table td {
    padding: 9px 7px;
    border: 1px solid #d8d8d3;
    vertical-align: top;
    overflow-wrap: anywhere;
}

/*
|--------------------------------------------------------------------------
| Reason
|--------------------------------------------------------------------------
*/

.reason-box {
    border: 1px solid #d9d9d5;
    border-left: 3px solid #991b1b;
    border-radius: 6px;
    background: #fafaf9;
    padding: 11px 12px;
}

.reason-box p {
    margin: 0;
    color: #343431;
    font-size: 9.5px;
    line-height: 1.65;
}

/*
|--------------------------------------------------------------------------
| Note
|--------------------------------------------------------------------------
*/

.note-box {
    border: 1px solid #d9d9d5;
    border-radius: 6px;
    background: #fafaf9;
    padding: 10px 12px;
}

.note-box p {
    margin: 0;
    color: #343431;
    font-size: 9px;
    line-height: 1.6;
}

/*
|--------------------------------------------------------------------------
| Statement
|--------------------------------------------------------------------------
*/

.statement-box {
    border-radius: 7px;
    border: 1px solid #d9d9d5;
    background: #f7f7f5;
    padding: 11px 13px;
}

.statement-box p {
    margin: 0;
    color: #3f3f3b;
    font-size: 9px;
    line-height: 1.65;
    text-align: justify;
}

/*
|--------------------------------------------------------------------------
| Signature
|--------------------------------------------------------------------------
*/

.signature-grid {
    width: 100%;
}

.signature-card {
    min-width: 0;
    border: 1px solid #d9d9d5;
    border-radius: 7px;
    padding: 12px 10px;
    text-align: center;
    break-inside: avoid;
    page-break-inside: avoid;
}

.signature-role {
    min-height: 15px;
    color: #333330;
    font-size: 9px;
    font-weight: 800;
}

.signature-subrole {
    min-height: 13px;
    margin-top: 2px;
    color: #66635e;
    font-size: 8px;
}

.signature-space {
    display: flex;
    min-height: 90px;
    align-items: center;
    justify-content: center;
    padding: 5px;
}

.signature-image {
    display: block;
    max-width: 100%;
    max-height: 78px;
    width: auto;
    height: auto;
    object-fit: contain;
}

.signature-placeholder {
    color: #999994;
    font-size: 7.5px;
    font-style: italic;
}

.signature-name {
    margin-top: 2px;
    color: #1b1b18;
    font-size: 9px;
    font-weight: 800;
    overflow-wrap: anywhere;
}

.signature-position {
    margin-top: 2px;
    color: #555550;
    font-size: 8px;
    line-height: 1.4;
    overflow-wrap: anywhere;
}

.signature-nip {
    margin-top: 2px;
    color: #66635e;
    font-size: 7.5px;
    overflow-wrap: anywhere;
}

/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

.status-pending {
    border-color: #d6d3d1;
    background: #f5f5f4;
    color: #57534e;
}

.status-approved {
    border-color: #bbf7d0;
    background: #f0fdf4;
    color: #166534;
}

.status-rejected {
    border-color: #fecaca;
    background: #fef2f2;
    color: #b91c1c;
}

.status-completed {
    border-color: #bfdbfe;
    background: #eff6ff;
    color: #1d4ed8;
}

.status-default {
    border-color: #d6d3d1;
    background: #f5f5f4;
    color: #57534e;
}

/*
|--------------------------------------------------------------------------
| Tablet
|--------------------------------------------------------------------------
*/

@media screen and (max-width: 1024px) {
    .a4-page {
        width: min(210mm, calc(100vw - 32px));
        min-height: auto;
        margin: 16px auto;
    }

    .document-content {
        padding: 28px;
    }
}

/*
|--------------------------------------------------------------------------
| Small Tablet
|--------------------------------------------------------------------------
*/

@media screen and (max-width: 768px) {
    .print-toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .print-toolbar > button {
        width: 100%;
    }

    .a4-page {
        width: calc(100vw - 24px);
        min-height: auto;
        margin: 12px auto;
        border-radius: 4px;
    }

    .document-content {
        padding: 22px 18px;
    }

    .document-section {
        margin-top: 20px;
    }

    .signature-grid {
        grid-template-columns: 1fr !important;
    }

    .document-table-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .document-table {
        min-width: 560px;
    }
}

/*
|--------------------------------------------------------------------------
| Mobile
|--------------------------------------------------------------------------
*/

@media screen and (max-width: 640px) {
    .a4-page {
        width: calc(100vw - 16px);
        margin: 8px auto;
    }

    .document-content {
        padding: 18px 13px;
    }

    .letterhead {
        margin-bottom: 18px;
        margin-left: -13px;
        margin-right: -13px;
    }

    .letterhead-image {
        width: calc(100% + 26px);
        max-height: 45mm;
    }

    .document-title {
        margin-bottom: 18px;
    }

    .document-title h1 {
        font-size: 19px;
    }

    .section-heading {
        gap: 7px;
        margin-bottom: 8px;
    }

    .section-heading h2 {
        font-size: 10.5px;
    }

    .section-number {
        width: 21px;
        height: 21px;
        flex-basis: 21px;
        font-size: 9px;
    }

    .detail-row {
        grid-template-columns: 1fr;
    }

    .detail-label {
        padding: 6px 8px 2px;
        font-size: 8px;
    }

    .detail-value {
        padding: 3px 8px 7px;
        font-size: 9px;
    }

    .reason-box,
    .note-box,
    .statement-box {
        padding: 9px 10px;
    }

    .reason-box p,
    .note-box p,
    .statement-box p {
        font-size: 8.5px;
    }

    .signature-card {
        padding: 10px;
    }

    .signature-space {
        min-height: 95px;
    }

    .signature-image {
        max-height: 82px;
    }

    .document-footer {
        margin-top: 20px;
        font-size: 7px;
    }
}

/*
|--------------------------------------------------------------------------
| Very Small Mobile
|--------------------------------------------------------------------------
*/

@media screen and (max-width: 400px) {
    .a4-page {
        width: calc(100vw - 10px);
        margin: 5px auto;
    }

    .document-content {
        padding: 15px 10px;
    }

    .document-title h1 {
        font-size: 17px;
    }

    .document-section {
        margin-top: 15px;
    }

    .signature-space {
        min-height: 85px;
    }
}

/*
|--------------------------------------------------------------------------
| Print
|--------------------------------------------------------------------------
*/

@media print {
    @page {
        size: A4;
        margin: 15mm 12mm;
    }

    html,
    body {
        width: auto;
        min-width: 0;
        margin: 0;
        padding: 0;
        background: #ffffff !important;
    }

    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .print-page {
        width: auto;
        min-height: 0;
        margin: 0;
        padding: 0;
        overflow: visible;
        background: #ffffff !important;
    }

    .a4-page {
        width: 100%;
        min-height: 0;
        margin: 0;
        padding: 0;
        border-radius: 0 !important;
        overflow: visible;
        box-shadow: none !important;
        background: #ffffff !important;
    }

    .letterhead-image {
        min-height: 42mm;
    }

    .document-content {
        width: 100%;
        padding: 0;
    }

    .print-hidden,
    .print\:hidden {
        display: none !important;
    }

    .document-section {
        margin-top: 7mm;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .section-heading {
        break-after: avoid;
        page-break-after: avoid;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        display: table-header-group;
    }

    tbody {
        display: table-row-group;
    }

    tr {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    th,
    td {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .signature-grid {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 4mm !important;
    }

    .signature-card {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .detail-row {
        grid-template-columns: 155px minmax(0, 1fr);
    }

    .document-table-wrapper {
        width: 100%;
        overflow: visible !important;
    }

    .document-table {
        min-width: 0 !important;
    }

    p,
    h1,
    h2,
    h3,
    h4 {
        orphans: 3;
        widows: 3;
    }

    .reason-box,
    .note-box,
    .statement-box,
    .detail-grid,
    .signature-grid {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .document-title h1 {
        font-size: 25px;
    }
}
</style>
