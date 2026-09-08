<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";

import ProcurementFormModal from "./ProcurementFormModal.vue";
import ProcurementDetailModal from "./ProcurementDetailModal.vue";
import ProcurementApprovalModal from "./ProcurementApprovalModal.vue";

/*
|--------------------------------------------------------------------------
| Interfaces
|--------------------------------------------------------------------------
*/

type ToastType = "success" | "error" | "warning" | "info";

interface Toast {
    type: ToastType;
    message: string;
}

interface Faculty {
    id: number;
    code: string;
    name: string;
}

interface Room {
    id: number;
    faculty_id: number | string;
    code: string;
    name: string;
    building?: string | null;
    floor?: string | null;
}

interface User {
    id: number;
    name: string;
    email?: string;
}

interface ExistingAttachment {
    path: string;
    url: string;
    name: string;
}

type ProcurementStatus = "pending" | "approved" | "rejected" | "completed";

type ProcurementType = "replacement" | "new_item";

interface Procurement {
    id: number;

    faculty_id: number | string;
    requested_by: number | string;
    room_id?: number | string | null;

    item_name: string;
    quantity: number | string;

    type: ProcurementType;
    reason: string;

    subject?: string | null;

    attachments?: ExistingAttachment[] | null;

    requester_signature?: string | null;
    requested_at?: string | null;

    status: ProcurementStatus;

    document_number?: string | null;

    processed_by?: number | string | null;
    approver_signature?: string | null;
    processed_at?: string | null;

    admin_note?: string | null;

    faculty?: Faculty | null;
    room?: Room | null;
    requester?: User | null;
    processor?: User | null;
}

interface ProcurementFormData {
    faculty_id: number;
    room_id: number | null;

    item_name: string;
    quantity: number;

    type: ProcurementType;
    reason: string;

    subject: string | null;

    requester_signature: string | null;

    attachments: File[];
    remove_attachments: string[];
}

interface PaginatedProcurements {
    data: Procurement[];
    current_page: number;
    last_page: number;
    per_page?: number;
    total: number;
    from?: number | null;
    to?: number | null;
}

interface Filters {
    search?: string | null;
    status?: ProcurementStatus | string | null;
    type?: ProcurementType | string | null;
    faculty_id?: number | string | null;
}

interface AuthUser {
    id: number;
    name: string;
    role?: string;
}

interface PageProps {
    procurements: PaginatedProcurements;

    faculties?: Faculty[];
    rooms?: Room[];

    filters?: Filters;

    auth?: {
        user?: AuthUser;
    };

    toast?: Toast | null;
}

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<PageProps>();

/*
|--------------------------------------------------------------------------
| Toast
|--------------------------------------------------------------------------
*/

const toastVisible = ref(false);
const toastType = ref<ToastType>("success");
const toastMessage = ref("");

let toastTimeout: ReturnType<typeof setTimeout> | undefined;

const clearToastTimeout = () => {
    if (toastTimeout !== undefined) {
        clearTimeout(toastTimeout);
        toastTimeout = undefined;
    }
};

const showToast = (type: ToastType, message: string) => {
    const normalizedMessage = message?.trim();

    if (!normalizedMessage) {
        return;
    }

    clearToastTimeout();

    toastType.value = type;
    toastMessage.value = normalizedMessage;
    toastVisible.value = true;

    toastTimeout = setTimeout(() => {
        toastVisible.value = false;
        toastTimeout = undefined;
    }, 3500);
};

const closeToast = () => {
    toastVisible.value = false;
    clearToastTimeout();
};

watch(
    () => props.toast,
    (toast) => {
        if (!toast?.message?.trim()) {
            return;
        }

        showToast(toast.type ?? "success", toast.message);
    },
    {
        immediate: true,
    },
);

/*
|--------------------------------------------------------------------------
| Loading
|--------------------------------------------------------------------------
*/

const isLoading = ref(false);

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const searchQuery = ref(props.filters?.search ?? "");

const statusFilter = ref(props.filters?.status ?? "");

const typeFilter = ref(props.filters?.type ?? "");

const facultyFilter = ref(
    props.filters?.faculty_id !== undefined &&
        props.filters?.faculty_id !== null
        ? String(props.filters.faculty_id)
        : "",
);

/*
|--------------------------------------------------------------------------
| Modal State
|--------------------------------------------------------------------------
*/

/* Form */
const isFormModalOpen = ref(false);
const editingProcurement = ref<Procurement | null>(null);
const isFormProcessing = ref(false);

/* Detail */
const isDetailModalOpen = ref(false);
const selectedProcurement = ref<Procurement | null>(null);

/* Approval */
const isApprovalModalOpen = ref(false);
const approvalProcurement = ref<Procurement | null>(null);
const isApprovalProcessing = ref(false);

/* Complete */
const isConfirmModalOpen = ref(false);
const confirmTitle = ref("Konfirmasi");
const confirmMessage = ref("");
const confirmAction = ref<(() => void) | null>(null);
const isConfirmProcessing = ref(false);

/* Delete */
const isDeleteModalOpen = ref(false);
const deleteTarget = ref<Procurement | null>(null);
const deleteTitle = ref("Hapus Pengajuan?");
const deleteMessage = ref("");
const isDeleteProcessing = ref(false);

/*
|--------------------------------------------------------------------------
| Body Scroll Lock
|--------------------------------------------------------------------------
|
| Confirmation modal di Index.vue mempunyai lifecycle sendiri.
| Jangan biarkan modal meninggalkan body dalam keadaan overflow hidden.
|
|--------------------------------------------------------------------------
*/

let previousBodyOverflow = "";

const lockBodyScroll = () => {
    if (typeof document === "undefined") {
        return;
    }

    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
};

const restoreBodyScroll = () => {
    if (typeof document === "undefined") {
        return;
    }

    document.body.style.overflow = previousBodyOverflow;

    previousBodyOverflow = "";
};

/*
|--------------------------------------------------------------------------
| Computed Data
|--------------------------------------------------------------------------
*/

const procurements = computed(() => {
    return props.procurements?.data ?? [];
});

const faculties = computed(() => {
    return props.faculties ?? [];
});

const rooms = computed(() => {
    return props.rooms ?? [];
});

const totalProcurements = computed(() => {
    return props.procurements?.total ?? 0;
});

/*
|--------------------------------------------------------------------------
| Active Filter
|--------------------------------------------------------------------------
*/

const hasActiveFilters = computed(() => {
    return Boolean(
        searchQuery.value.trim() ||
        statusFilter.value ||
        typeFilter.value ||
        facultyFilter.value,
    );
});

/*
|--------------------------------------------------------------------------
| Status Statistics
|--------------------------------------------------------------------------
|
| Statistik berdasarkan data pada halaman pagination aktif.
|
|--------------------------------------------------------------------------
*/

const pendingCount = computed(() => {
    return procurements.value.filter(
        (procurement) => procurement.status === "pending",
    ).length;
});

const approvedCount = computed(() => {
    return procurements.value.filter(
        (procurement) => procurement.status === "approved",
    ).length;
});

const rejectedCount = computed(() => {
    return procurements.value.filter(
        (procurement) => procurement.status === "rejected",
    ).length;
});

const completedCount = computed(() => {
    return procurements.value.filter(
        (procurement) => procurement.status === "completed",
    ).length;
});

/*
|--------------------------------------------------------------------------
| Authorization
|--------------------------------------------------------------------------
*/

const currentUser = computed(() => {
    return props.auth?.user ?? null;
});

const currentUserId = computed(() => {
    return currentUser.value?.id ?? null;
});

const currentUserRole = computed(() => {
    return currentUser.value?.role ?? "";
});

const isSuperAdmin = computed(() => {
    return currentUserRole.value === "super_admin";
});

const isFacultyAdmin = computed(() => {
    return currentUserRole.value === "admin_fakultas";
});

const canCreateProcurement = computed(() => {
    return isSuperAdmin.value || isFacultyAdmin.value;
});

const canManageProcurement = computed(() => {
    return isSuperAdmin.value || isFacultyAdmin.value;
});

const canDeleteProcurement = computed(() => {
    return isSuperAdmin.value || isFacultyAdmin.value;
});

const canCompleteProcurement = computed(() => {
    return isSuperAdmin.value;
});

/*
|--------------------------------------------------------------------------
| Row Authorization
|--------------------------------------------------------------------------
*/

const canEditProcurementRow = (procurement: Procurement): boolean => {
    if (!canManageProcurement.value) {
        return false;
    }

    if (procurement.status !== "pending") {
        return false;
    }

    if (isSuperAdmin.value) {
        return true;
    }

    return (
        currentUserId.value !== null &&
        Number(procurement.requested_by) === Number(currentUserId.value)
    );
};

const canDeleteProcurementRow = (procurement: Procurement): boolean => {
    if (!canDeleteProcurement.value) {
        return false;
    }

    if (procurement.status !== "pending") {
        return false;
    }

    if (isSuperAdmin.value) {
        return true;
    }

    return (
        currentUserId.value !== null &&
        Number(procurement.requested_by) === Number(currentUserId.value)
    );
};

/*
|--------------------------------------------------------------------------
| Error Helper
|--------------------------------------------------------------------------
*/

const getErrorMessage = (
    errors: Record<string, string | string[]> | undefined,
    fallback: string,
): string => {
    if (!errors) {
        return fallback;
    }

    const firstError = Object.values(errors)[0];

    if (Array.isArray(firstError)) {
        return firstError[0] || fallback;
    }

    if (typeof firstError === "string" && firstError.trim() !== "") {
        return firstError;
    }

    return fallback;
};

/*
|--------------------------------------------------------------------------
| Date Helper
|--------------------------------------------------------------------------
*/

const formatDate = (date?: string | null): string => {
    if (!date) {
        return "-";
    }

    const parsed = new Date(date);

    if (Number.isNaN(parsed.getTime())) {
        return date;
    }

    return parsed.toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

/*
|--------------------------------------------------------------------------
| Faculty Helpers
|--------------------------------------------------------------------------
*/

const getFaculty = (procurement: Procurement): Faculty | null => {
    if (procurement.faculty) {
        return procurement.faculty;
    }

    if (
        procurement.faculty_id === null ||
        procurement.faculty_id === undefined
    ) {
        return null;
    }

    return (
        faculties.value.find(
            (faculty) => Number(faculty.id) === Number(procurement.faculty_id),
        ) ?? null
    );
};

const getFacultyName = (procurement: Procurement): string => {
    return getFaculty(procurement)?.name ?? "-";
};

/*
|--------------------------------------------------------------------------
| Room Helpers
|--------------------------------------------------------------------------
*/

const getRoom = (procurement: Procurement): Room | null => {
    if (procurement.room) {
        return procurement.room;
    }

    if (procurement.room_id === null || procurement.room_id === undefined) {
        return null;
    }

    return (
        rooms.value.find(
            (room) => Number(room.id) === Number(procurement.room_id),
        ) ?? null
    );
};

const getRoomName = (procurement: Procurement): string => {
    const room = getRoom(procurement);

    if (!room) {
        return "Tidak ditentukan";
    }

    const code = typeof room.code === "string" ? room.code.trim() : "";

    const name = typeof room.name === "string" ? room.name.trim() : "";

    if (code && name) {
        return `${code} - ${name}`;
    }

    return name || code || "Tidak ditentukan";
};

/*
|--------------------------------------------------------------------------
| Requester Helper
|--------------------------------------------------------------------------
*/

const getRequesterName = (procurement: Procurement): string => {
    return procurement.requester?.name ?? "Pengguna";
};

/*
|--------------------------------------------------------------------------
| Status Label
|--------------------------------------------------------------------------
*/

const statusLabel = (status: ProcurementStatus): string => {
    switch (status) {
        case "pending":
            return "Menunggu Verifikasi";

        case "approved":
            return "Disetujui";

        case "rejected":
            return "Ditolak";

        case "completed":
            return "Selesai";

        default:
            return status;
    }
};

/*
|--------------------------------------------------------------------------
| Type Label
|--------------------------------------------------------------------------
*/

const typeLabel = (type: ProcurementType): string => {
    switch (type) {
        case "replacement":
            return "Penggantian";

        case "new_item":
            return "Barang Baru";

        default:
            return type;
    }
};

/*
|--------------------------------------------------------------------------
| Status Class
|--------------------------------------------------------------------------
*/

const statusClass = (status: ProcurementStatus): string => {
    switch (status) {
        case "pending":
            return "border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-400";

        case "approved":
            return "border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-400";

        case "rejected":
            return "border-red-200 bg-red-50 text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-400";

        case "completed":
            return "border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-400";

        default:
            return "border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300";
    }
};

/*
|--------------------------------------------------------------------------
| Type Class
|--------------------------------------------------------------------------
*/

const typeClass = (type: ProcurementType): string => {
    if (type === "replacement") {
        return "border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-400";
    }

    return "border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-900/50 dark:bg-purple-950/30 dark:text-purple-400";
};

/*
|--------------------------------------------------------------------------
| Search & Filter
|--------------------------------------------------------------------------
*/

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

const clearSearchTimeout = () => {
    if (searchTimeout !== undefined) {
        clearTimeout(searchTimeout);
        searchTimeout = undefined;
    }
};

const buildFilterQuery = (): Record<string, string> => {
    const query: Record<string, string> = {};

    const search = searchQuery.value.trim();

    if (search !== "") {
        query.search = search;
    }

    if (statusFilter.value !== "") {
        query.status = String(statusFilter.value);
    }

    if (typeFilter.value !== "") {
        query.type = String(typeFilter.value);
    }

    if (facultyFilter.value !== "") {
        query.faculty_id = facultyFilter.value;
    }

    return query;
};

const applyFilters = () => {
    clearSearchTimeout();

    isLoading.value = true;

    router.get("/admin/procurements", buildFilterQuery(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,

        onError: (errors) => {
            showToast(
                "error",
                getErrorMessage(errors, "Gagal memuat data pengadaan."),
            );
        },

        onFinish: () => {
            isLoading.value = false;
        },
    });
};

watch(searchQuery, () => {
    clearSearchTimeout();

    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

const clearFilters = () => {
    clearSearchTimeout();

    searchQuery.value = "";
    statusFilter.value = "";
    typeFilter.value = "";
    facultyFilter.value = "";

    isLoading.value = true;

    router.get(
        "/admin/procurements",
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,

            onError: (errors) => {
                showToast(
                    "error",
                    getErrorMessage(errors, "Gagal mereset filter pengadaan."),
                );
            },

            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
};

/*
|--------------------------------------------------------------------------
| Create
|--------------------------------------------------------------------------
*/

const openCreateModal = () => {
    if (!canCreateProcurement.value) {
        return;
    }

    editingProcurement.value = null;
    isFormModalOpen.value = true;
};

const closeFormModal = () => {
    if (isFormProcessing.value) {
        return;
    }

    isFormModalOpen.value = false;
    editingProcurement.value = null;
};

/*
|--------------------------------------------------------------------------
| Edit
|--------------------------------------------------------------------------
*/

const openEditModal = (procurement: Procurement) => {
    if (!canEditProcurementRow(procurement)) {
        return;
    }

    editingProcurement.value = {
        ...procurement,

        faculty_id: procurement.faculty_id,

        room_id:
            procurement.room_id !== undefined && procurement.room_id !== null
                ? Number(procurement.room_id)
                : null,

        item_name: procurement.item_name ?? "",

        quantity:
            procurement.quantity !== undefined && procurement.quantity !== null
                ? Number(procurement.quantity)
                : 1,

        type: procurement.type,

        reason: procurement.reason ?? "",

        subject: procurement.subject ?? null,

        requester_signature: procurement.requester_signature ?? null,

        attachments: procurement.attachments
            ? [...procurement.attachments]
            : [],
    };

    isFormModalOpen.value = true;
};

/*
|--------------------------------------------------------------------------
| Save Procurement
|--------------------------------------------------------------------------
*/

const handleSaveProcurement = (data: ProcurementFormData) => {
    if (isFormProcessing.value) {
        return;
    }

    /*
     * CREATE
     */
    if (!editingProcurement.value) {
        if (!canCreateProcurement.value) {
            return;
        }

        isFormProcessing.value = true;

        router.post("/admin/procurements", data, {
            preserveScroll: true,
            forceFormData: true,

            onSuccess: () => {
                isFormModalOpen.value = false;
                editingProcurement.value = null;
            },

            onError: (errors) => {
                showToast(
                    "error",
                    getErrorMessage(
                        errors,
                        "Gagal membuat pengajuan pengadaan.",
                    ),
                );
            },

            onFinish: () => {
                isFormProcessing.value = false;
            },
        });

        return;
    }

    /*
     * UPDATE
     */
    const procurement = editingProcurement.value;

    if (!canEditProcurementRow(procurement)) {
        return;
    }

    isFormProcessing.value = true;

    router.post(
        `/admin/procurements/${procurement.id}`,
        {
            ...data,
            _method: "PUT",
        },
        {
            preserveScroll: true,
            forceFormData: true,

            onSuccess: () => {
                isFormModalOpen.value = false;
                editingProcurement.value = null;
            },

            onError: (errors) => {
                showToast(
                    "error",
                    getErrorMessage(
                        errors,
                        "Gagal memperbarui pengajuan pengadaan.",
                    ),
                );
            },

            onFinish: () => {
                isFormProcessing.value = false;
            },
        },
    );
};

/*
|--------------------------------------------------------------------------
| Detail
|--------------------------------------------------------------------------
*/

const openDetailModal = (procurement: Procurement) => {
    selectedProcurement.value = {
        ...procurement,
        room: getRoom(procurement),
    };

    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedProcurement.value = null;
};

/*
|--------------------------------------------------------------------------
| Approval
|--------------------------------------------------------------------------
*/

const openApprovalModal = (procurement: Procurement) => {
    if (!isSuperAdmin.value) {
        return;
    }

    if (procurement.status !== "pending") {
        return;
    }

    approvalProcurement.value = procurement;

    isApprovalModalOpen.value = true;
};

const closeApprovalModal = () => {
    if (isApprovalProcessing.value) {
        return;
    }

    isApprovalModalOpen.value = false;
    approvalProcurement.value = null;
};

const handleApproval = (data: {
    action: "approve" | "reject";
    approver_signature: string | null;
    admin_note: string | null;
}) => {
    if (isApprovalProcessing.value) {
        return;
    }

    const procurement = approvalProcurement.value;

    if (!procurement) {
        return;
    }

    if (!isSuperAdmin.value) {
        return;
    }

    if (procurement.status !== "pending") {
        showToast("warning", "Pengajuan ini sudah tidak menunggu verifikasi.");
        return;
    }

    isApprovalProcessing.value = true;

    const procurementId = procurement.id;

    const endpoint =
        data.action === "approve"
            ? `/admin/procurements/${procurementId}/approve`
            : `/admin/procurements/${procurementId}/reject`;

    const fallbackMessage =
        data.action === "approve"
            ? "Gagal menyetujui pengajuan pengadaan."
            : "Gagal menolak pengajuan pengadaan.";

    router.post(
        endpoint,
        {
            approver_signature: data.approver_signature,
            admin_note: data.admin_note,
        },
        {
            preserveScroll: true,
            forceFormData: true,

            onSuccess: () => {
                isApprovalModalOpen.value = false;
                approvalProcurement.value = null;
            },

            onError: (errors) => {
                showToast("error", getErrorMessage(errors, fallbackMessage));
            },

            onFinish: () => {
                isApprovalProcessing.value = false;
            },
        },
    );
};

/*
|--------------------------------------------------------------------------
| Complete Confirmation
|--------------------------------------------------------------------------
*/

const resetConfirmState = () => {
    confirmTitle.value = "Konfirmasi";
    confirmMessage.value = "";
    confirmAction.value = null;
};

const openCompleteModal = (procurement: Procurement) => {
    if (!canCompleteProcurement.value) {
        return;
    }

    if (procurement.status !== "approved") {
        return;
    }

    if (isConfirmProcessing.value || isDeleteProcessing.value) {
        return;
    }

    resetConfirmState();

    confirmTitle.value = "Tandai Pengadaan Selesai?";

    confirmMessage.value =
        `Pengajuan "${procurement.item_name}" akan diubah menjadi status selesai. ` +
        "Pastikan proses pengadaan sudah benar-benar selesai.";

    const procurementId = procurement.id;

    confirmAction.value = () => {
        if (isConfirmProcessing.value) {
            return;
        }

        if (!canCompleteProcurement.value) {
            return;
        }

        /*
         * Re-check state sebelum request.
         * Object di tabel bisa saja sudah berubah
         * akibat visit Inertia sebelumnya.
         */
        const currentProcurement = procurements.value.find(
            (item) => item.id === procurementId,
        );

        if (currentProcurement && currentProcurement.status !== "approved") {
            showToast(
                "warning",
                "Pengajuan ini sudah tidak berstatus disetujui.",
            );

            closeConfirmModal();
            return;
        }

        isConfirmProcessing.value = true;

        router.post(
            `/admin/procurements/${procurementId}/complete`,
            {},
            {
                preserveScroll: true,

                onSuccess: () => {
                    closeConfirmModal();
                },

                onError: (errors) => {
                    showToast(
                        "error",
                        getErrorMessage(
                            errors,
                            "Gagal menyelesaikan pengadaan.",
                        ),
                    );
                },

                onFinish: () => {
                    isConfirmProcessing.value = false;
                },
            },
        );
    };

    isConfirmModalOpen.value = true;
};

const closeConfirmModal = () => {
    if (isConfirmProcessing.value) {
        return;
    }

    isConfirmModalOpen.value = false;
    resetConfirmState();
};

const executeConfirm = () => {
    if (isConfirmProcessing.value || !confirmAction.value) {
        return;
    }

    confirmAction.value();
};

/*
|--------------------------------------------------------------------------
| Delete Confirmation
|--------------------------------------------------------------------------
*/

const openDeleteModal = (procurement: Procurement) => {
    if (!canDeleteProcurementRow(procurement)) {
        return;
    }

    if (isDeleteProcessing.value || isConfirmProcessing.value) {
        return;
    }

    deleteTarget.value = procurement;

    deleteTitle.value = "Hapus Pengajuan Pengadaan?";

    deleteMessage.value =
        `Pengajuan "${procurement.item_name}" akan dihapus secara permanen. ` +
        "Data yang sudah dihapus tidak dapat dikembalikan.";

    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    if (isDeleteProcessing.value) {
        return;
    }

    isDeleteModalOpen.value = false;
    deleteTarget.value = null;

    deleteTitle.value = "Hapus Pengajuan?";
    deleteMessage.value = "";
};

const executeDelete = () => {
    if (isDeleteProcessing.value || !deleteTarget.value) {
        return;
    }

    const procurement = deleteTarget.value;

    /*
     * Re-check authorization dan status
     * tepat sebelum request.
     */
    if (!canDeleteProcurementRow(procurement)) {
        showToast(
            "warning",
            "Pengajuan ini sudah tidak dapat dihapus.",
        );

        closeDeleteModal();
        return;
    }

    isDeleteProcessing.value = true;

    router.delete(`/admin/procurements/${procurement.id}`, {
        preserveScroll: true,

        onSuccess: () => {
            closeDeleteModal();
        },

        onError: (errors) => {
            showToast(
                "error",
                getErrorMessage(
                    errors,
                    "Gagal menghapus pengajuan pengadaan.",
                ),
            );
        },

        onFinish: () => {
            isDeleteProcessing.value = false;
        },
    });
};

/*
|--------------------------------------------------------------------------
| Print
|--------------------------------------------------------------------------
*/

const printProcurement = (procurement: Procurement) => {
    if (!procurement?.id) {
        return;
    }

    if (
        procurement.status !== "approved" &&
        procurement.status !== "completed"
    ) {
        return;
    }

    if (typeof window === "undefined") {
        return;
    }

    const printWindow = window.open(
        `/admin/procurements/${procurement.id}/print`,
        "_blank",
        "noopener,noreferrer",
    );

    if (!printWindow) {
        showToast(
            "warning",
            "Popup diblokir oleh browser. Izinkan popup untuk mencetak.",
        );
    }
};

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

const goToPage = (page: number) => {
    const currentPage = props.procurements?.current_page ?? 1;

    const lastPage = props.procurements?.last_page ?? 1;

    if (page < 1 || page > lastPage || page === currentPage) {
        return;
    }

    clearSearchTimeout();

    isLoading.value = true;

    router.get(
        "/admin/procurements",
        {
            ...buildFilterQuery(),
            page: String(page),
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,

            onError: (errors) => {
                showToast(
                    "error",
                    getErrorMessage(errors, "Gagal memuat halaman pengadaan."),
                );
            },

            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
};

const visiblePages = computed(() => {
    const current = props.procurements?.current_page ?? 1;

    const last = props.procurements?.last_page ?? 1;

    const pages: number[] = [];

    const start = Math.max(1, current - 2);

    const end = Math.min(last, current + 2);

    for (let page = start; page <= end; page++) {
        pages.push(page);
    }

    return pages;
});

/*
|--------------------------------------------------------------------------
| Keyboard
|--------------------------------------------------------------------------
*/

const handleGlobalKeydown = (event: KeyboardEvent) => {
    if (event.key !== "Escape") {
        return;
    }

    if (isConfirmModalOpen.value) {
        if (!isConfirmProcessing.value) {
            closeConfirmModal();
        }

        return;
    }

    if (isDeleteModalOpen.value) {
        if (!isDeleteProcessing.value) {
            closeDeleteModal();
        }

        return;
    }
};

/*
|--------------------------------------------------------------------------
| Modal Lifecycle
|--------------------------------------------------------------------------
*/

watch([isConfirmModalOpen, isDeleteModalOpen], ([confirmOpen, deleteOpen]) => {
    if (typeof window === "undefined") {
        return;
    }

    const shouldLock = confirmOpen || deleteOpen;

    if (shouldLock) {
        lockBodyScroll();

        window.addEventListener("keydown", handleGlobalKeydown);

        return;
    }

    window.removeEventListener("keydown", handleGlobalKeydown);

    restoreBodyScroll();
});

/*
|--------------------------------------------------------------------------
| Cleanup
|--------------------------------------------------------------------------
*/

onBeforeUnmount(() => {
    clearSearchTimeout();
    clearToastTimeout();

    if (typeof window !== "undefined") {
        window.removeEventListener("keydown", handleGlobalKeydown);
    }

    restoreBodyScroll();

    isConfirmProcessing.value = false;
    isDeleteProcessing.value = false;

    confirmAction.value = null;
    deleteTarget.value = null;
});
</script>

<template>
    <Head title="Pengadaan Barang - Sistem Inventory" />

    <div class="relative flex flex-1 flex-col gap-6 overflow-hidden p-4 md:p-6">
        <!-- ============================================================ -->
        <!-- BACKGROUND -->
        <!-- ============================================================ -->

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

        <!-- ============================================================ -->
        <!-- MAIN -->
        <!-- ============================================================ -->

        <div
            class="relative z-10 flex flex-1 flex-col gap-6 opacity-100 transition-opacity duration-750 starting:opacity-0"
        >
            <!-- ======================================================== -->
            <!-- STATS -->
            <!-- ======================================================== -->

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <!-- Total -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Total Pengajuan
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
                                    d="M9 12h6m-6 4h6m2.25-14.25H6.75A2.25 2.25 0 0 0 4.5 4v16.5A2.25 2.25 0 0 0 6.75 22.75h10.5A2.25 2.25 0 0 0 19.5 20.5V6.75a2.25 2.25 0 0 0-2.25-2.25ZM15 4.5V2.25H9V4.5"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ totalProcurements }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Semua pengajuan
                        </p>
                    </div>

                    <div
                        class="absolute bottom-0 left-0 h-[2px] w-full bg-[#f53003]/20 opacity-0 transition group-hover:opacity-100 dark:bg-[#FF4433]/30"
                    ></div>
                </div>

                <!-- Pending -->
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
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400"
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
                                    d="M12 6v6l4 2.25M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ pendingCount }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Menunggu verifikasi
                        </p>
                    </div>
                </div>

                <!-- Approved -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Disetujui
                        </span>

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/30 dark:text-blue-400"
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
                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ approvedCount }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Siap diproses
                        </p>
                    </div>
                </div>

                <!-- Rejected -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Ditolak
                        </span>

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600 dark:bg-red-950/30 dark:text-red-400"
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
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ rejectedCount }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Pengajuan ditolak
                        </p>
                    </div>
                </div>

                <!-- Completed -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-black/5 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Selesai
                        </span>

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400"
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
                                    d="m4.5 12.75 6 6 9-13.5"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div
                            class="text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ completedCount }}
                        </div>

                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Pengadaan selesai
                        </p>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- MAIN SECTION -->
            <!-- ======================================================== -->

            <div
                class="flex flex-1 flex-col rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]"
            >
                <!-- Header -->
                <div
                    class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div>
                        <h2
                            class="text-lg font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Pengajuan Pengadaan Barang
                        </h2>

                        <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            Kelola pengajuan penggantian barang rusak dan
                            pengadaan barang baru.
                        </p>
                    </div>

                    <button
                        v-if="canCreateProcurement"
                        type="button"
                        @click="openCreateModal"
                        class="flex items-center justify-center gap-2 rounded-lg bg-[#1b1b18] px-4 py-2.5 text-xs font-medium text-white transition hover:bg-black dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white"
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

                        <span>Ajukan Pengadaan</span>
                    </button>
                </div>

                <!-- ==================================================== -->
                <!-- FILTER -->
                <!-- ==================================================== -->

                <div class="mb-6">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Search -->
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari barang, alasan, pemohon..."
                                @keyup.enter="applyFilters"
                                class="w-full rounded-lg border border-[#e3e3e0] bg-transparent py-2.5 pl-9 pr-3.5 text-xs text-[#1b1b18] placeholder-[#a1a09a] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]"
                            />

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="absolute left-3 top-3 h-4 w-4 text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                                />
                            </svg>
                        </div>

                        <!-- Status -->
                        <select
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-white px-3 py-2.5 text-xs text-[#1b1b18] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                        >
                            <option value="">Semua Status</option>
                            <option value="pending">Menunggu Verifikasi</option>
                            <option value="approved">Disetujui</option>
                            <option value="rejected">Ditolak</option>
                            <option value="completed">Selesai</option>
                        </select>

                        <!-- Type -->
                        <select
                            v-model="typeFilter"
                            @change="applyFilters"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-white px-3 py-2.5 text-xs text-[#1b1b18] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                        >
                            <option value="">Semua Jenis</option>
                            <option value="replacement">
                                Penggantian Barang
                            </option>
                            <option value="new_item">Barang Baru</option>
                        </select>

                        <!-- Faculty -->
                        <select
                            v-model="facultyFilter"
                            @change="applyFilters"
                            class="w-full rounded-lg border border-[#e3e3e0] bg-white px-3 py-2.5 text-xs text-[#1b1b18] transition focus:border-[#f53003] focus:outline-none focus:ring-1 focus:ring-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                        >
                            <option value="">Semua Fakultas</option>

                            <option
                                v-for="faculty in faculties"
                                :key="faculty.id"
                                :value="String(faculty.id)"
                            >
                                {{ faculty.code }} - {{ faculty.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Summary -->
                    <div class="mt-3 flex items-center justify-between">
                        <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            Menampilkan
                            <span
                                class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                {{ totalProcurements }}
                            </span>
                            pengajuan

                            <span v-if="hasActiveFilters">
                                &middot; filter sedang aktif
                            </span>
                        </p>

                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            @click="clearFilters"
                            class="text-xs font-medium text-[#f53003] transition hover:underline dark:text-[#FF4433]"
                        >
                            Reset Filter
                        </button>
                    </div>
                </div>

                <!-- ==================================================== -->
                <!-- DATA -->
                <!-- ==================================================== -->

                <div class="flex-1">
                    <!-- Loading -->
                    <div v-if="isLoading" class="space-y-3">
                        <div
                            v-for="i in 5"
                            :key="i"
                            class="animate-pulse rounded-xl border border-[#e3e3e0] bg-[#FDFDFC] p-4 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="h-10 w-10 rounded-lg bg-slate-200 dark:bg-zinc-800"
                                ></div>

                                <div class="flex-1 space-y-2">
                                    <div
                                        class="h-3 w-48 rounded bg-slate-200 dark:bg-zinc-800"
                                    ></div>

                                    <div
                                        class="h-3 w-72 rounded bg-slate-200 dark:bg-zinc-800"
                                    ></div>
                                </div>

                                <div
                                    class="h-7 w-20 rounded-lg bg-slate-200 dark:bg-zinc-800"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div
                        v-else-if="procurements.length > 0"
                        class="overflow-x-auto rounded-xl border border-[#e3e3e0] dark:border-[#3E3E3A]"
                    >
                        <table class="w-full min-w-[1050px] text-left text-xs">
                            <thead class="bg-[#fafafa] dark:bg-[#111110]">
                                <tr
                                    class="border-b border-[#e3e3e0] text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]"
                                >
                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider"
                                    >
                                        Pengajuan
                                    </th>
                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider"
                                    >
                                        Fakultas
                                    </th>
                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider"
                                    >
                                        Ruangan
                                    </th>
                                    <th
                                        class="px-4 py-3 text-center font-medium uppercase tracking-wider"
                                    >
                                        Jumlah
                                    </th>
                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider"
                                    >
                                        Jenis
                                    </th>
                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider"
                                    >
                                        Status
                                    </th>
                                    <th
                                        class="px-4 py-3 font-medium uppercase tracking-wider"
                                    >
                                        Pemohon
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right font-medium uppercase tracking-wider"
                                    >
                                        Aksi
                                    </th>
                                </tr>
                            </thead>

                            <tbody
                                class="divide-y divide-[#e3e3e0]/70 dark:divide-[#3E3E3A]/70"
                            >
                                <tr
                                    v-for="procurement in procurements"
                                    :key="procurement.id"
                                    class="bg-white transition hover:bg-slate-50/70 dark:bg-[#161615] dark:hover:bg-[#20201e]"
                                >
                                    <!-- Pengajuan -->
                                    <td class="px-4 py-4">
                                        <div class="flex items-start gap-3">
                                            <div
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#fff2f2] text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
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
                                                        d="M20.25 7.5v9a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 16.5v-9A2.25 2.25 0 0 1 6 5.25h12a2.25 2.25 0 0 1 2.25 2.25Z"
                                                    />
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M3.75 7.5 12 12.75 20.25 7.5"
                                                    />
                                                </svg>
                                            </div>

                                            <div class="min-w-0">
                                                <div
                                                    class="max-w-[230px] truncate font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                                >
                                                    {{ procurement.item_name }}
                                                </div>

                                                <div
                                                    class="mt-1 max-w-[230px] truncate text-[11px] text-[#706f6c] dark:text-[#A1A09A]"
                                                >
                                                    {{
                                                        formatDate(
                                                            procurement.requested_at,
                                                        )
                                                    }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Faculty -->
                                    <td class="px-4 py-4">
                                        <div
                                            class="max-w-[180px] truncate font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{ getFacultyName(procurement) }}
                                        </div>
                                    </td>

                                    <!-- Room -->
                                    <td class="px-4 py-4">
                                        <div
                                            class="max-w-[180px] truncate text-[#706f6c] dark:text-[#A1A09A]"
                                        >
                                            {{ getRoomName(procurement) }}
                                        </div>

                                        <template v-if="getRoom(procurement)">
                                            <div
                                                v-if="
                                                    getRoom(procurement)
                                                        ?.building ||
                                                    getRoom(procurement)?.floor
                                                "
                                                class="mt-1 text-[10px] text-[#A1A09A]"
                                            >
                                                <span
                                                    v-if="
                                                        getRoom(procurement)
                                                            ?.building
                                                    "
                                                >
                                                    {{
                                                        getRoom(procurement)
                                                            ?.building
                                                    }}
                                                </span>

                                                <span
                                                    v-if="
                                                        getRoom(procurement)
                                                            ?.building &&
                                                        getRoom(procurement)
                                                            ?.floor
                                                    "
                                                >
                                                    &middot;
                                                </span>

                                                <span
                                                    v-if="
                                                        getRoom(procurement)
                                                            ?.floor
                                                    "
                                                >
                                                    Lt.
                                                    {{
                                                        getRoom(procurement)
                                                            ?.floor
                                                    }}
                                                </span>
                                            </div>
                                        </template>
                                    </td>

                                    <!-- Quantity -->
                                    <td class="px-4 py-4 text-center">
                                        <span
                                            class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{ procurement.quantity }}
                                        </span>
                                    </td>

                                    <!-- Type -->
                                    <td class="px-4 py-4">
                                        <span
                                            class="inline-flex rounded-md border px-2 py-1 text-[10px] font-semibold"
                                            :class="typeClass(procurement.type)"
                                        >
                                            {{ typeLabel(procurement.type) }}
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-4">
                                        <span
                                            class="inline-flex whitespace-nowrap rounded-md border px-2 py-1 text-[10px] font-semibold"
                                            :class="
                                                statusClass(procurement.status)
                                            "
                                        >
                                            {{
                                                statusLabel(procurement.status)
                                            }}
                                        </span>
                                    </td>

                                    <!-- Requester -->
                                    <td class="px-4 py-4">
                                        <div
                                            class="max-w-[130px] truncate font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{ getRequesterName(procurement) }}
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-4 py-4">
                                        <div
                                            class="flex items-center justify-end gap-1.5"
                                        >
                                            <!-- Detail -->
                                            <button
                                                type="button"
                                                @click="
                                                    openDetailModal(procurement)
                                                "
                                                class="rounded-lg border border-[#e3e3e0] bg-white p-1.5 text-[#706f6c] transition hover:border-[#1b1b18] hover:text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:border-[#EDEDEC] dark:hover:text-[#EDEDEC]"
                                                title="Lihat Detail"
                                                aria-label="Lihat Detail"
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
                                                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.644C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.577 3.01 9.964 7.178.07.21.07.434 0 .644C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.01-9.964-7.178Z"
                                                    />
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                                    />
                                                </svg>
                                            </button>

                                            <!-- Print -->
                                            <button
                                                v-if="
                                                    procurement.status ===
                                                        'approved' ||
                                                    procurement.status ===
                                                        'completed'
                                                "
                                                type="button"
                                                @click="
                                                    printProcurement(
                                                        procurement,
                                                    )
                                                "
                                                class="rounded-lg border border-[#e3e3e0] bg-white p-1.5 text-[#706f6c] transition hover:border-[#1b1b18] hover:text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:border-[#EDEDEC] dark:hover:text-[#EDEDEC]"
                                                title="Cetak Dokumen"
                                                aria-label="Cetak Dokumen"
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
                                                        d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"
                                                    />
                                                </svg>
                                            </button>

                                            <!-- Edit -->
                                            <button
                                                v-if="
                                                    canEditProcurementRow(
                                                        procurement,
                                                    )
                                                "
                                                type="button"
                                                @click="
                                                    openEditModal(procurement)
                                                "
                                                class="rounded-lg border border-[#e3e3e0] bg-white p-1.5 text-[#706f6c] transition hover:border-[#f53003] hover:text-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:border-[#FF4433] dark:hover:text-[#FF4433]"
                                                title="Edit Pengajuan"
                                                aria-label="Edit Pengajuan"
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
                                                v-if="
                                                    canDeleteProcurementRow(
                                                        procurement,
                                                    )
                                                "
                                                type="button"
                                                @click="
                                                    openDeleteModal(procurement)
                                                "
                                                :disabled="isDeleteProcessing"
                                                class="rounded-lg border border-red-200 bg-red-50 p-1.5 text-red-600 transition hover:border-red-300 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-400 dark:hover:border-red-800 dark:hover:bg-red-950/50"
                                                title="Hapus Pengajuan"
                                                aria-label="Hapus Pengajuan"
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
                                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12.856 0c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0C8.91 2.13 8 3.114 8 4.294v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                                    />
                                                </svg>
                                            </button>

                                            <!-- Verify -->
                                            <button
                                                v-if="
                                                    isSuperAdmin &&
                                                    procurement.status ===
                                                        'pending'
                                                "
                                                type="button"
                                                @click="
                                                    openApprovalModal(
                                                        procurement,
                                                    )
                                                "
                                                class="rounded-lg bg-[#f53003] p-1.5 text-white transition hover:bg-[#d92800] dark:bg-[#FF4433] dark:hover:bg-red-500"
                                                title="Verifikasi Pengajuan"
                                                aria-label="Verifikasi Pengajuan"
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
                                                        d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                                    />
                                                </svg>
                                            </button>

                                            <!-- Complete -->
                                            <button
                                                v-if="
                                                    canCompleteProcurement &&
                                                    procurement.status ===
                                                        'approved'
                                                "
                                                type="button"
                                                @click="
                                                    openCompleteModal(
                                                        procurement,
                                                    )
                                                "
                                                :disabled="isConfirmProcessing"
                                                class="rounded-lg border border-emerald-200 bg-emerald-50 p-1.5 text-emerald-600 transition hover:border-emerald-300 hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-400 dark:hover:border-emerald-800 dark:hover:bg-emerald-950/50"
                                                title="Tandai Selesai"
                                                aria-label="Tandai Selesai"
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
                                                        d="m4.5 12.75 6 6 9-13.5"
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
                        class="rounded-xl border border-dashed border-[#e3e3e0] p-12 text-center dark:border-[#3E3E3A]"
                    >
                        <div
                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#fff2f2] text-[#f53003] dark:bg-[#1D0002] dark:text-[#FF4433]"
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
                                    d="M20.25 7.5v9a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25v-9A2.25 2.25 0 0 1 6 5.25h12a2.25 2.25 0 0 1 2.25 2.25Z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m3.75 7.5 8.25 5.25 8.25-5.25"
                                />
                            </svg>
                        </div>

                        <h3
                            class="mt-4 text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Belum ada pengajuan
                        </h3>

                        <p
                            class="mx-auto mt-1 max-w-md text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            {{
                                hasActiveFilters
                                    ? "Tidak ada pengajuan yang sesuai dengan filter yang dipilih."
                                    : "Belum terdapat pengajuan pengadaan barang."
                            }}
                        </p>

                        <button
                            v-if="canCreateProcurement"
                            type="button"
                            @click="openCreateModal"
                            class="mt-5 rounded-lg bg-[#1b1b18] px-4 py-2 text-xs font-medium text-white transition hover:bg-black dark:bg-[#EDEDEC] dark:text-[#1c1c1a] dark:hover:bg-white"
                        >
                            + Ajukan Pengadaan
                        </button>
                    </div>
                </div>

                <!-- ==================================================== -->
                <!-- PAGINATION -->
                <!-- ==================================================== -->

                <div
                    v-if="
                        !isLoading && (props.procurements?.last_page ?? 1) > 1
                    "
                    class="mt-6 flex flex-col gap-3 border-t border-[#e3e3e0] pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-[#3E3E3A]"
                >
                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        Menampilkan
                        <span
                            class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ props.procurements?.from ?? 0 }}
                        </span>
                        -
                        <span
                            class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ props.procurements?.to ?? 0 }}
                        </span>
                        dari
                        <span
                            class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            {{ props.procurements?.total ?? 0 }}
                        </span>
                        pengajuan
                    </p>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            :disabled="
                                props.procurements.current_page <= 1 ||
                                isLoading
                            "
                            @click="
                                goToPage(props.procurements.current_page - 1)
                            "
                            class="rounded-lg border border-[#e3e3e0] bg-white px-2.5 py-1.5 text-xs text-[#706f6c] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:bg-[#20201e]"
                            aria-label="Halaman sebelumnya"
                        >
                            &lsaquo;
                        </button>

                        <button
                            v-for="page in visiblePages"
                            :key="page"
                            type="button"
                            :disabled="isLoading"
                            @click="goToPage(page)"
                            class="min-w-[32px] rounded-lg border px-2.5 py-1.5 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-50"
                            :class="
                                page === props.procurements.current_page
                                    ? 'border-[#f53003] bg-[#f53003] text-white dark:border-[#FF4433] dark:bg-[#FF4433]'
                                    : 'border-[#e3e3e0] bg-white text-[#706f6c] hover:bg-slate-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:bg-[#20201e]'
                            "
                        >
                            {{ page }}
                        </button>

                        <button
                            type="button"
                            :disabled="
                                props.procurements.current_page >=
                                    props.procurements.last_page || isLoading
                            "
                            @click="
                                goToPage(props.procurements.current_page + 1)
                            "
                            class="rounded-lg border border-[#e3e3e0] bg-white px-2.5 py-1.5 text-xs text-[#706f6c] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:bg-[#20201e]"
                            aria-label="Halaman berikutnya"
                        >
                            &rsaquo;
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- PROCUREMENT FORM MODAL -->
    <!-- ================================================================ -->

    <ProcurementFormModal
        :show="isFormModalOpen"
        :procurement="editingProcurement"
        :faculties="faculties"
        :rooms="rooms"
        :processing="isFormProcessing"
        @close="closeFormModal"
        @submit="handleSaveProcurement"
    />

    <!-- ================================================================ -->
    <!-- PROCUREMENT DETAIL MODAL -->
    <!-- ================================================================ -->

    <ProcurementDetailModal
        :show="isDetailModalOpen"
        :procurement="selectedProcurement"
        @close="closeDetailModal"
    />

    <!-- ================================================================ -->
    <!-- PROCUREMENT APPROVAL MODAL -->
    <!-- ================================================================ -->

    <ProcurementApprovalModal
        :show="isApprovalModalOpen"
        :procurement="approvalProcurement"
        :processing="isApprovalProcessing"
        @close="closeApprovalModal"
        @submit="handleApproval"
    />

    <!-- ================================================================ -->
    <!-- COMPLETE CONFIRMATION MODAL -->
    <!-- ================================================================ -->

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
                v-if="isConfirmModalOpen"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="procurement-confirm-title"
                tabindex="-1"
                @keydown.esc="closeConfirmModal"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/50 backdrop-blur-sm dark:bg-black/70"
                    @click.self="closeConfirmModal"
                ></div>

                <Transition
                    appear
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="scale-95 opacity-0"
                    enter-to-class="scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="scale-100 opacity-100"
                    leave-to-class="scale-95 opacity-0"
                >
                    <div
                        v-if="isConfirmModalOpen"
                        class="relative w-full max-w-md overflow-hidden rounded-2xl border border-[#e3e3e0] bg-white shadow-2xl dark:border-[#3E3E3A] dark:bg-[#161615]"
                        @click.stop
                    >
                        <!-- Content -->
                        <div class="p-6">
                            <!-- Icon -->
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                    class="h-6 w-6"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m4.5 12.75 6 6 9-13.5"
                                    />
                                </svg>
                            </div>

                            <!-- Text -->
                            <div class="mt-4 text-center">
                                <h3
                                    id="procurement-confirm-title"
                                    class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ confirmTitle }}
                                </h3>

                                <p
                                    class="mt-2 text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    {{ confirmMessage }}
                                </p>

                                <!-- Selected procurement -->
                                <div
                                    v-if="selectedProcurement"
                                    class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50/70 p-3 text-left dark:border-emerald-900/50 dark:bg-emerald-950/20"
                                >
                                    <div
                                        class="text-xs font-semibold text-emerald-800 dark:text-emerald-300"
                                    >
                                        {{ selectedProcurement.item_name }}
                                    </div>

                                    <div
                                        class="mt-1 text-[11px] leading-5 text-emerald-700 dark:text-emerald-400"
                                    >
                                        Jumlah:
                                        {{ selectedProcurement.quantity }}

                                        <span class="mx-1"> &middot; </span>

                                        Status:
                                        {{
                                            statusLabel(
                                                selectedProcurement.status,
                                            )
                                        }}
                                    </div>
                                </div>

                                <!-- Information -->
                                <div
                                    class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-left dark:border-emerald-900/50 dark:bg-emerald-950/20"
                                >
                                    <div class="flex gap-3">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.8"
                                            stroke="currentColor"
                                            class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M13 16h-1v-4h-1m1-4h.01M12 20.5a8.5 8.5 0 1 1 0-17 8.5 8.5 0 0 1 0 17Z"
                                            />
                                        </svg>

                                        <p
                                            class="text-xs leading-5 text-emerald-700 dark:text-emerald-400"
                                        >
                                            Status ini menandakan bahwa proses
                                            pengadaan sudah selesai dilakukan.
                                            Data inventaris tidak dibuat secara
                                            otomatis oleh aksi ini.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex flex-col-reverse gap-2 border-t border-[#e3e3e0] bg-[#fafafa] p-4 dark:border-[#3E3E3A] dark:bg-[#111110] sm:flex-row sm:justify-end"
                        >
                            <button
                                type="button"
                                :disabled="isConfirmProcessing"
                                @click="closeConfirmModal"
                                class="w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm font-medium text-[#1b1b18] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e] sm:w-auto"
                            >
                                Batal
                            </button>

                            <button
                                type="button"
                                :disabled="isConfirmProcessing"
                                @click="executeConfirm"
                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-emerald-600 dark:hover:bg-emerald-500 sm:w-auto"
                            >
                                <svg
                                    v-if="isConfirmProcessing"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4 animate-spin"
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
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4Z"
                                    />
                                </svg>

                                <span>
                                    {{
                                        isConfirmProcessing
                                            ? "Memproses..."
                                            : "Ya, Tandai Selesai"
                                    }}
                                </span>
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>

    <!-- ================================================================ -->
    <!-- DELETE CONFIRMATION MODAL -->
    <!-- ================================================================ -->

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
                class="fixed inset-0 z-[100] flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="procurement-delete-title"
                tabindex="-1"
                @keydown.esc="closeDeleteModal"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/50 backdrop-blur-sm dark:bg-black/70"
                    @click.self="closeDeleteModal"
                ></div>

                <Transition
                    appear
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="scale-95 opacity-0"
                    enter-to-class="scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="scale-100 opacity-100"
                    leave-to-class="scale-95 opacity-0"
                >
                    <div
                        v-if="isDeleteModalOpen"
                        class="relative w-full max-w-md overflow-hidden rounded-2xl border border-[#e3e3e0] bg-white shadow-2xl dark:border-[#3E3E3A] dark:bg-[#161615]"
                        @click.stop
                    >
                        <!-- Content -->
                        <div class="p-6">
                            <!-- Warning Icon -->
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                    class="h-6 w-6"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 9v3.75m0 3h.008v.008H12v-.008ZM10.29 3.86 2.82 17.5a2.25 2.25 0 0 0 1.97 3.375h14.42a2.25 2.25 0 0 0 1.97-3.375L13.71 3.86a2.25 2.25 0 0 0-3.42 0Z"
                                    />
                                </svg>
                            </div>

                            <!-- Text -->
                            <div class="mt-4 text-center">
                                <h3
                                    id="procurement-delete-title"
                                    class="text-base font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    {{ deleteTitle }}
                                </h3>

                                <p
                                    class="mt-2 text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    {{ deleteMessage }}
                                </p>

                                <!-- Selected procurement -->
                                <div
                                    v-if="selectedProcurement"
                                    class="mt-4 rounded-lg border border-red-200 bg-red-50/70 p-3 text-left dark:border-red-900/50 dark:bg-red-950/20"
                                >
                                    <div
                                        class="text-xs font-semibold text-red-800 dark:text-red-300"
                                    >
                                        {{ selectedProcurement.item_name }}
                                    </div>

                                    <div
                                        class="mt-1 text-[11px] leading-5 text-red-700 dark:text-red-400"
                                    >
                                        Jumlah:
                                        {{ selectedProcurement.quantity }}

                                        <span class="mx-1"> &middot; </span>

                                        Status:
                                        {{
                                            statusLabel(
                                                selectedProcurement.status,
                                            )
                                        }}
                                    </div>
                                </div>

                                <!-- Warning -->
                                <div
                                    class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-left dark:border-red-900/50 dark:bg-red-950/20"
                                >
                                    <div class="flex gap-3">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.8"
                                            stroke="currentColor"
                                            class="mt-0.5 h-5 w-5 shrink-0 text-red-600 dark:text-red-400"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M12 9v3.75m0 3h.008v.008H12v-.008ZM10.29 3.86 2.82 17.5a2.25 2.25 0 0 0 1.97 3.375h14.42a2.25 2.25 0 0 0 1.97-3.375L13.71 3.86a2.25 2.25 0 0 0-3.42 0Z"
                                            />
                                        </svg>

                                        <p
                                            class="text-xs leading-5 text-red-700 dark:text-red-400"
                                        >
                                            Data pengajuan yang dihapus tidak
                                            dapat dikembalikan. Pastikan Anda
                                            benar-benar ingin menghapus
                                            pengajuan ini.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex flex-col-reverse gap-2 border-t border-[#e3e3e0] bg-[#fafafa] p-4 dark:border-[#3E3E3A] dark:bg-[#111110] sm:flex-row sm:justify-end"
                        >
                            <button
                                type="button"
                                :disabled="isDeleteProcessing"
                                @click="closeDeleteModal"
                                class="w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm font-medium text-[#1b1b18] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#20201e] sm:w-auto"
                            >
                                Batal
                            </button>

                            <button
                                type="button"
                                :disabled="isDeleteProcessing"
                                @click="executeDelete"
                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-red-600 dark:hover:bg-red-500 sm:w-auto"
                            >
                                <svg
                                    v-if="isDeleteProcessing"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4 animate-spin"
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
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4Z"
                                    />
                                </svg>

                                <span>
                                    {{
                                        isDeleteProcessing
                                            ? "Menghapus..."
                                            : "Ya, Hapus Pengajuan"
                                    }}
                                </span>
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
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
