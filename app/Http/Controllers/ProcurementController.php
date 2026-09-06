<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Procurement;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProcurementController extends Controller
{
    /**
     * Roles yang boleh mengakses modul procurement.
     */
    private const ADMIN_ROLES = [
        'super_admin',
        'admin_fakultas',
        'sdm',
    ];

    /**
     * Roles yang boleh melakukan approval/rejection.
     *
     * Sesuaikan jika SDM nantinya juga menjadi approver.
     */
    private const APPROVER_ROLES = [
        'super_admin',
        'admin_fakultas',
    ];

    /**
     * Display procurement list.
     */
    public function index(Request $request): Response
    {
        $this->authorizeAdminRole($request);

        $user = $request->user();

        $query = Procurement::query()
            ->with([
                'faculty:id,name',
                'requester:id,name',
                'room:id,name',
                'processor:id,name',
            ])
            ->latest('id');

        /*
         * ADMIN FAKULTAS:
         * hanya boleh melihat pengadaan fakultasnya sendiri.
         */
        if ($this->isFacultyAdmin($user)) {
            $this->ensureUserHasFaculty($user);

            $query->where('faculty_id', $user->faculty_id);
        }

        /*
         * Filter pencarian.
         */
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('item_name', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%");
                });
            }
        }

        /*
         * Filter status.
         */
        if ($request->filled('status')) {
            $status = $request->input('status');

            if (in_array($status, $this->allowedStatuses(), true)) {
                $query->where('status', $status);
            }
        }

        /*
         * Filter tipe pengadaan.
         */
        if ($request->filled('type')) {
            $type = $request->input('type');

            if (in_array($type, $this->allowedTypes(), true)) {
                $query->where('type', $type);
            }
        }

        /*
         * Filter faculty hanya untuk super admin / SDM.
         */
        if (
            $request->filled('faculty_id') &&
            $this->canViewAllFaculties($user)
        ) {
            $query->where(
                'faculty_id',
                $request->integer('faculty_id')
            );
        }

        return Inertia::render('Admin/Procurements/Index', [
            'procurements' => $query
                ->paginate(15)
                ->withQueryString(),

            'faculties' => $this->canViewAllFaculties($user)
                ? Faculty::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                : Faculty::query()
                ->where('id', $user->faculty_id)
                ->select('id', 'name')
                ->get(),

            'rooms' => $this->getAvailableRooms($user),

            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'type' => $request->input('type', ''),
                'faculty_id' => $request->input('faculty_id', ''),
            ],
        ]);
    }

    /**
     * Store procurement request.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdminRole($request);

        $user = $request->user();

        /*
         * Procurement hanya boleh dibuat oleh:
         * - super_admin
         * - admin_fakultas
         *
         * SDM tidak otomatis boleh membuat pengajuan.
         */
        if (
            ! $this->isSuperAdmin($user) &&
            ! $this->isFacultyAdmin($user)
        ) {
            abort(403, 'Anda tidak memiliki izin untuk membuat pengajuan pengadaan.');
        }

        if ($this->isFacultyAdmin($user)) {
            $this->ensureUserHasFaculty($user);
        }

        $validated = $request->validate([
            'room_id' => [
                'nullable',
                'integer',
                'exists:rooms,id',
            ],

            'item_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100000',
            ],

            'type' => [
                'required',
                Rule::in($this->allowedTypes()),
            ],

            'reason' => [
                'required',
                'string',
                'min:5',
                'max:5000',
            ],

            'requester_signature' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        /*
         * Jika admin fakultas mengajukan:
         * faculty_id WAJIB berasal dari akun user.
         */
        $facultyId = $this->resolveFacultyId($user);

        /*
         * Pastikan room berada di fakultas yang sama.
         */
        if (! empty($validated['room_id'])) {
            $this->ensureRoomBelongsToFaculty(
                (int) $validated['room_id'],
                $facultyId
            );
        }

        DB::transaction(function () use (
            $validated,
            $user,
            $facultyId
        ) {
            Procurement::create([
                'faculty_id' => $facultyId,

                /*
                 * Tidak mengambil requested_by dari request.
                 * Selalu menggunakan user login.
                 */
                'requested_by' => $user->id,

                'room_id' => $validated['room_id'] ?? null,

                'item_name' => trim($validated['item_name']),

                'quantity' => (int) $validated['quantity'],

                'type' => $validated['type'],

                'reason' => trim($validated['reason']),

                'requester_signature' =>
                $validated['requester_signature'] ?? null,

                'requested_at' => now(),

                /*
                 * Status selalu pending ketika dibuat.
                 */
                'status' => Procurement::STATUS_PENDING,
            ]);
        });

        return back()->with(
            'success',
            'Pengajuan pengadaan berhasil dibuat.'
        );
    }

    /**
     * Show procurement detail.
     */
    public function show(
        Request $request,
        Procurement $procurement
    ): Response {
        $this->authorizeAdminRole($request);

        $this->ensureProcurementAccessible(
            $request->user(),
            $procurement
        );

        $procurement->load([
            'faculty:id,name',
            'requester:id,name',
            'room:id,name',
            'processor:id,name',
        ]);

        return Inertia::render(
            'Admin/Procurements/Show',
            [
                'procurement' => $procurement,
            ]
        );
    }

    /**
     * Update procurement request.
     *
     * Hanya pending yang boleh diedit.
     */
    public function update(
        Request $request,
        Procurement $procurement
    ): RedirectResponse {
        $this->authorizeAdminRole($request);

        $user = $request->user();

        $this->ensureProcurementAccessible(
            $user,
            $procurement
        );

        /*
         * Setelah diproses, data tidak boleh diedit lagi.
         */
        if (! $procurement->isPending()) {
            throw ValidationException::withMessages([
                'procurement' =>
                'Pengajuan yang sudah diproses tidak dapat diubah.',
            ]);
        }

        /*
         * Hanya pengaju sendiri atau super admin
         * yang boleh mengubah pengajuan.
         */
        if (
            ! $this->isSuperAdmin($user) &&
            $procurement->requested_by !== $user->id
        ) {
            abort(
                403,
                'Anda tidak memiliki izin untuk mengubah pengajuan ini.'
            );
        }

        $validated = $request->validate([
            'room_id' => [
                'nullable',
                'integer',
                'exists:rooms,id',
            ],

            'item_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100000',
            ],

            'type' => [
                'required',
                Rule::in($this->allowedTypes()),
            ],

            'reason' => [
                'required',
                'string',
                'min:5',
                'max:5000',
            ],

            'requester_signature' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        if (! empty($validated['room_id'])) {
            $this->ensureRoomBelongsToFaculty(
                (int) $validated['room_id'],
                $procurement->faculty_id
            );
        }

        $procurement->update([
            'room_id' => $validated['room_id'] ?? null,
            'item_name' => trim($validated['item_name']),
            'quantity' => (int) $validated['quantity'],
            'type' => $validated['type'],
            'reason' => trim($validated['reason']),
            'requester_signature' =>
            $validated['requester_signature'] ?? null,
        ]);

        return back()->with(
            'success',
            'Pengajuan pengadaan berhasil diperbarui.'
        );
    }

    /**
     * Delete procurement.
     *
     * Hanya pending yang boleh dihapus.
     */
    public function destroy(
        Request $request,
        Procurement $procurement
    ): RedirectResponse {
        $this->authorizeAdminRole($request);

        $user = $request->user();

        $this->ensureProcurementAccessible(
            $user,
            $procurement
        );

        if (! $procurement->isPending()) {
            throw ValidationException::withMessages([
                'procurement' =>
                'Pengajuan yang sudah diproses tidak dapat dihapus.',
            ]);
        }

        if (
            ! $this->isSuperAdmin($user) &&
            $procurement->requested_by !== $user->id
        ) {
            abort(
                403,
                'Anda tidak memiliki izin untuk menghapus pengajuan ini.'
            );
        }

        $procurement->delete();

        return back()->with(
            'success',
            'Pengajuan pengadaan berhasil dihapus.'
        );
    }

    /**
     * Approve procurement.
     */
    public function approve(
        Request $request,
        Procurement $procurement
    ): RedirectResponse {
        $this->authorizeApprovalRole($request);

        $user = $request->user();

        $this->ensureProcurementAccessible(
            $user,
            $procurement
        );

        $validated = $request->validate([
            'approver_signature' => [
                'nullable',
                'string',
                'max:255',
            ],

            'admin_note' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        DB::transaction(function () use (
            $procurement,
            $user,
            $validated
        ) {
            /*
             * Lock row untuk mencegah race condition:
             * dua admin approve procurement yang sama secara bersamaan.
             */
            $lockedProcurement = Procurement::query()
                ->lockForUpdate()
                ->findOrFail($procurement->id);

            if (! $lockedProcurement->isPending()) {
                throw ValidationException::withMessages([
                    'procurement' =>
                    'Pengajuan ini sudah diproses sebelumnya.',
                ]);
            }

            $lockedProcurement->update([
                'status' => Procurement::STATUS_APPROVED,

                /*
                 * Processor selalu berasal dari authenticated user.
                 */
                'processed_by' => $user->id,

                'approver_signature' =>
                $validated['approver_signature'] ?? null,

                'processed_at' => now(),

                'admin_note' =>
                isset($validated['admin_note'])
                    ? trim($validated['admin_note'])
                    : null,
            ]);
        });

        return back()->with(
            'success',
            'Pengajuan pengadaan berhasil disetujui.'
        );
    }

    /**
     * Reject procurement.
     */
    public function reject(
        Request $request,
        Procurement $procurement
    ): RedirectResponse {
        $this->authorizeApprovalRole($request);

        $user = $request->user();

        $this->ensureProcurementAccessible(
            $user,
            $procurement
        );

        $validated = $request->validate([
            'approver_signature' => [
                'nullable',
                'string',
                'max:255',
            ],

            'admin_note' => [
                'required',
                'string',
                'min:5',
                'max:5000',
            ],
        ]);

        DB::transaction(function () use (
            $procurement,
            $user,
            $validated
        ) {
            $lockedProcurement = Procurement::query()
                ->lockForUpdate()
                ->findOrFail($procurement->id);

            if (! $lockedProcurement->isPending()) {
                throw ValidationException::withMessages([
                    'procurement' =>
                    'Pengajuan ini sudah diproses sebelumnya.',
                ]);
            }

            $lockedProcurement->update([
                'status' => Procurement::STATUS_REJECTED,

                'processed_by' => $user->id,

                'approver_signature' =>
                $validated['approver_signature'] ?? null,

                'processed_at' => now(),

                'admin_note' => trim($validated['admin_note']),
            ]);
        });

        return back()->with(
            'success',
            'Pengajuan pengadaan berhasil ditolak.'
        );
    }

    /**
     * Mark approved procurement as completed.
     */
    public function complete(
        Request $request,
        Procurement $procurement
    ): RedirectResponse {
        $this->authorizeApprovalRole($request);

        $user = $request->user();

        $this->ensureProcurementAccessible(
            $user,
            $procurement
        );

        DB::transaction(function () use (
            $procurement,
            $user
        ) {
            $lockedProcurement = Procurement::query()
                ->lockForUpdate()
                ->findOrFail($procurement->id);

            if (! $lockedProcurement->isApproved()) {
                throw ValidationException::withMessages([
                    'procurement' =>
                    'Hanya pengadaan yang sudah disetujui yang dapat diselesaikan.',
                ]);
            }

            $lockedProcurement->update([
                'status' => Procurement::STATUS_COMPLETED,

                /*
                 * Tetap catat siapa yang melakukan aksi terakhir.
                 */
                'processed_by' => $user->id,

                'processed_at' => now(),
            ]);
        });

        return back()->with(
            'success',
            'Pengadaan berhasil ditandai sebagai selesai.'
        );
    }

    /**
     * Authorization dasar untuk area admin.
     */
    private function authorizeAdminRole(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user &&
                in_array(
                    $user->role,
                    self::ADMIN_ROLES,
                    true
                ),
            403,
            'Anda tidak memiliki akses ke modul pengadaan.'
        );
    }

    /**
     * Authorization untuk approval.
     */
    private function authorizeApprovalRole(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user &&
                in_array(
                    $user->role,
                    self::APPROVER_ROLES,
                    true
                ),
            403,
            'Anda tidak memiliki izin untuk memproses pengadaan.'
        );
    }

    /**
     * Pastikan procurement berada dalam scope user.
     */
    private function ensureProcurementAccessible(
        $user,
        Procurement $procurement
    ): void {
        if ($this->isSuperAdmin($user)) {
            return;
        }

        /*
         * SDM boleh melihat seluruh fakultas.
         */
        if ($this->isSdm($user)) {
            return;
        }

        /*
         * Admin fakultas hanya fakultasnya sendiri.
         */
        if ($this->isFacultyAdmin($user)) {
            $this->ensureUserHasFaculty($user);

            abort_unless(
                (int) $procurement->faculty_id ===
                    (int) $user->faculty_id,
                403,
                'Anda tidak memiliki akses ke pengadaan fakultas lain.'
            );

            return;
        }

        abort(403);
    }

    /**
     * Pastikan room berasal dari fakultas yang benar.
     */
    private function ensureRoomBelongsToFaculty(
        int $roomId,
        int $facultyId
    ): void {
        $exists = Room::query()
            ->whereKey($roomId)
            ->where('faculty_id', $facultyId)
            ->exists();

        abort_unless(
            $exists,
            422,
            'Ruangan tidak berada dalam fakultas yang dipilih.'
        );
    }

    /**
     * Tentukan faculty_id berdasarkan user.
     */
    private function resolveFacultyId($user): int
    {
        if (
            $this->isSuperAdmin($user) ||
            $this->isSdm($user)
        ) {
            /*
             * Super admin / SDM harus mengirim faculty_id
             * jika membuat procurement lintas fakultas.
             */
            $facultyId = request()->validate([
                'faculty_id' => [
                    'required',
                    'integer',
                    'exists:faculties,id',
                ],
            ])['faculty_id'];

            return (int) $facultyId;
        }

        $this->ensureUserHasFaculty($user);

        return (int) $user->faculty_id;
    }

    /**
     * Ambil room sesuai scope user.
     */
    private function getAvailableRooms($user)
    {
        $query = Room::query()
            ->select('id', 'faculty_id', 'name')
            ->orderBy('name');

        if (
            $this->isFacultyAdmin($user)
        ) {
            $this->ensureUserHasFaculty($user);

            $query->where(
                'faculty_id',
                $user->faculty_id
            );
        }

        return $query->get();
    }

    /**
     * Role helper.
     */
    private function isSuperAdmin($user): bool
    {
        return $user?->role === 'super_admin';
    }

    private function isFacultyAdmin($user): bool
    {
        return $user?->role === 'admin_fakultas';
    }

    private function isSdm($user): bool
    {
        return $user?->role === 'sdm';
    }

    /**
     * User harus mempunyai faculty_id.
     */
    private function ensureUserHasFaculty($user): void
    {
        abort_unless(
            ! empty($user->faculty_id),
            403,
            'Akun Anda belum memiliki fakultas.'
        );
    }

    /**
     * Allowed statuses.
     */
    private function allowedStatuses(): array
    {
        return [
            Procurement::STATUS_PENDING,
            Procurement::STATUS_APPROVED,
            Procurement::STATUS_REJECTED,
            Procurement::STATUS_COMPLETED,
        ];
    }

    /**
     * Allowed procurement types.
     */
    private function allowedTypes(): array
    {
        return [
            Procurement::TYPE_REPLACEMENT,
            Procurement::TYPE_NEW_ITEM,
        ];
    }

    /**
     * Apakah user boleh melihat seluruh fakultas?
     */
    private function canViewAllFaculties($user): bool
    {
        return $this->isSuperAdmin($user) ||
            $this->isSdm($user);
    }
}
