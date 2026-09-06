<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Procurement;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
     * Hanya Super Admin yang boleh:
     * - approve
     * - reject
     * - complete
     */
    private const APPROVER_ROLES = [
        'super_admin',
    ];

    /**
     * Maksimal ukuran signature setelah decode.
     */
    private const MAX_SIGNATURE_BYTES = 1024 * 1024; // 1MB

    /**
     * Maksimal dimensi signature.
     *
     * Mencegah image decompression yang terlalu besar.
     */
    private const MAX_SIGNATURE_WIDTH = 2000;
    private const MAX_SIGNATURE_HEIGHT = 1000;

    /**
     * Display procurement list.
     */
    public function index(Request $request): Response
    {
        $this->authorizeAdminRole($request);

        $user = $request->user();

        $query = Procurement::query()
            ->with([
                'faculty:id,code,name',
                'requester:id,name',
                'room:id,faculty_id,code,name,building,floor',
                'processor:id,name',
            ])
            ->latest('id');

        /*
         * ADMIN FAKULTAS:
         * hanya boleh melihat pengadaan fakultasnya sendiri.
         */
        if ($this->isFacultyAdmin($user)) {
            $this->ensureUserHasFaculty($user);

            $query->where(
                'faculty_id',
                $user->faculty_id
            );
        }

        /*
         * Filter pencarian.
         */
        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where(
                        'item_name',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhere(
                            'reason',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'requester',
                            function ($q) use ($search) {
                                $q->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                );
                            }
                        );
                });
            }
        }

        /*
         * Filter status.
         */
        if ($request->filled('status')) {
            $status = (string) $request->input('status');

            if (
                in_array(
                    $status,
                    $this->allowedStatuses(),
                    true
                )
            ) {
                $query->where(
                    'status',
                    $status
                );
            }
        }

        /*
         * Filter tipe pengadaan.
         */
        if ($request->filled('type')) {
            $type = (string) $request->input('type');

            if (
                in_array(
                    $type,
                    $this->allowedTypes(),
                    true
                )
            ) {
                $query->where(
                    'type',
                    $type
                );
            }
        }

        /*
         * Filter fakultas hanya untuk:
         * - super_admin
         * - sdm
         *
         * Admin fakultas tetap dikunci
         * ke fakultas akun.
         */
        if (
            $request->filled('faculty_id') &&
            $this->canViewAllFaculties($user)
        ) {
            $facultyId = $request->integer(
                'faculty_id'
            );

            if ($facultyId > 0) {
                $query->where(
                    'faculty_id',
                    $facultyId
                );
            }
        }

        return Inertia::render(
            'Admin/Procurements/Index',
            [
                'procurements' => $query
                    ->paginate(15)
                    ->withQueryString(),

                'faculties' => $this->canViewAllFaculties($user)
                    ? Faculty::query()
                        ->select(
                            'id',
                            'name'
                        )
                        ->orderBy('name')
                        ->get()
                    : Faculty::query()
                        ->where(
                            'id',
                            $user->faculty_id
                        )
                        ->select(
                            'id',
                            'name'
                        )
                        ->orderBy('name')
                        ->get(),

                'rooms' => $this->getAvailableRooms($user),

                'filters' => [
                    'search' => $request->input(
                        'search',
                        ''
                    ),
                    'status' => $request->input(
                        'status',
                        ''
                    ),
                    'type' => $request->input(
                        'type',
                        ''
                    ),
                    'faculty_id' => $request->input(
                        'faculty_id',
                        ''
                    ),
                ],
            ]
        );
    }

    /**
     * Store procurement request.
     *
     * Hanya:
     * - super_admin
     * - admin_fakultas
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdminRole($request);

        $user = $request->user();

        /*
         * SDM hanya monitoring.
         */
        if (
            ! $this->isSuperAdmin($user) &&
            ! $this->isFacultyAdmin($user)
        ) {
            abort(
                403,
                'Anda tidak memiliki izin untuk membuat pengajuan pengadaan.'
            );
        }

        if ($this->isFacultyAdmin($user)) {
            $this->ensureUserHasFaculty($user);
        }

        $validated = $request->validate([
            /*
             * Nullable di request karena:
             * - Super Admin memilih fakultas.
             * - Admin Fakultas menggunakan fakultas akun.
             */
            'faculty_id' => [
                'nullable',
                'integer',
                'exists:faculties,id',
            ],

            /*
             * Room WAJIB.
             */
            'room_id' => [
                'required',
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
                Rule::in(
                    $this->allowedTypes()
                ),
            ],

            'reason' => [
                'required',
                'string',
                'min:5',
                'max:5000',
            ],

            /*
             * Optional.
             */
            'requester_signature' => [
                'nullable',
                'string',
                'max:1500000',
            ],
        ]);

        /*
         * Tentukan fakultas berdasarkan role.
         */
        $facultyId = $this->resolveFacultyId(
            $user,
            isset($validated['faculty_id'])
                ? (int) $validated['faculty_id']
                : null
        );

        /*
         * Room WAJIB dan harus berasal dari fakultas
         * yang sama.
         */
        $this->ensureRoomBelongsToFaculty(
            (int) $validated['room_id'],
            $facultyId
        );

        /*
         * Signature baru hanya boleh berupa PNG data URI.
         */
        $signaturePath = $this->resolveNewSignature(
            $validated['requester_signature'] ?? null,
            'requester_signature'
        );

        try {
            DB::transaction(function () use (
                $validated,
                $user,
                $facultyId,
                $signaturePath
            ) {
                Procurement::create([
                    'faculty_id' => $facultyId,

                    /*
                     * Tidak boleh diambil dari request.
                     */
                    'requested_by' => $user->id,

                    /*
                     * Room wajib.
                     */
                    'room_id' => (int) $validated['room_id'],

                    'item_name' => trim(
                        $validated['item_name']
                    ),

                    'quantity' => (int) $validated['quantity'],

                    'type' => $validated['type'],

                    'reason' => trim(
                        $validated['reason']
                    ),

                    'requester_signature' => $signaturePath,

                    'requested_at' => now(),

                    /*
                     * Tidak boleh ditentukan client.
                     */
                    'status' => Procurement::STATUS_PENDING,
                ]);
            });
        } catch (\Throwable $e) {
            /*
             * Hapus signature baru jika transaksi gagal.
             */
            $this->deleteSignatureFile(
                $signaturePath
            );

            throw $e;
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Pengajuan pengadaan berhasil dibuat.',
        ]);
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
            'faculty:id,code,name',
            'requester:id,name',
            'room:id,faculty_id,code,name,building,floor',
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
     * Hanya pending.
     *
     * Super Admin:
     * - boleh edit pending.
     *
     * Admin Fakultas:
     * - hanya pengajuan miliknya.
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
         * Hanya pending yang boleh diedit.
         */
        if (! $procurement->isPending()) {
            throw ValidationException::withMessages([
                'procurement' =>
                'Pengajuan yang sudah diproses tidak dapat diubah.',
            ]);
        }

        /*
         * Super Admin boleh edit semua pending.
         *
         * Admin Fakultas hanya pemilik pengajuan.
         */
        if (
            ! $this->isSuperAdmin($user) &&
            (int) $procurement->requested_by !==
            (int) $user->id
        ) {
            abort(
                403,
                'Anda tidak memiliki izin untuk mengubah pengajuan ini.'
            );
        }

        $validated = $request->validate([
            /*
             * Room WAJIB.
             */
            'room_id' => [
                'required',
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
                Rule::in(
                    $this->allowedTypes()
                ),
            ],

            'reason' => [
                'required',
                'string',
                'min:5',
                'max:5000',
            ],

            /*
             * Bisa:
             * - null untuk menghapus signature.
             * - data URI PNG untuk mengganti signature.
             * - signature lama yang memang sudah tersimpan.
             */
            'requester_signature' => [
                'nullable',
                'string',
                'max:1500000',
            ],
        ]);

        /*
         * Room harus tetap berada di fakultas procurement.
         */
        $this->ensureRoomBelongsToFaculty(
            (int) $validated['room_id'],
            (int) $procurement->faculty_id
        );

        $oldSignaturePath =
            $procurement->requester_signature;

        /*
         * Resolve signature dengan validasi bahwa
         * URL/path lama memang signature milik record ini.
         */
        $signaturePath = $this->resolveUpdateSignature(
            $validated['requester_signature'] ?? null,
            $oldSignaturePath,
            'requester_signature'
        );

        try {
            DB::transaction(function () use (
                $procurement,
                $validated,
                $signaturePath
            ) {
                /*
                 * Lock record ketika update.
                 *
                 * Mencegah update bersamaan dengan
                 * approval/rejection.
                 */
                $lockedProcurement =
                    Procurement::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $procurement->id
                        );

                if (! $lockedProcurement->isPending()) {
                    throw ValidationException::withMessages([
                        'procurement' =>
                        'Pengajuan ini sudah diproses dan tidak dapat diubah.',
                    ]);
                }

                $lockedProcurement->update([
                    'room_id' =>
                        (int) $validated['room_id'],

                    'item_name' =>
                        trim(
                            $validated['item_name']
                        ),

                    'quantity' =>
                        (int) $validated['quantity'],

                    'type' =>
                        $validated['type'],

                    'reason' =>
                        trim(
                            $validated['reason']
                        ),

                    'requester_signature' =>
                        $signaturePath,
                ]);
            });
        } catch (\Throwable $e) {
            /*
             * Hanya signature BARU yang boleh dibersihkan.
             * Signature lama jangan pernah dihapus di sini.
             */
            if (
                $signaturePath !== $oldSignaturePath
            ) {
                $this->deleteSignatureFile(
                    $signaturePath
                );
            }

            throw $e;
        }

        /*
         * Hapus signature lama setelah transaksi berhasil.
         */
        $this->deleteOldSignatureIfReplaced(
            $oldSignaturePath,
            $signaturePath
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Pengajuan pengadaan berhasil diperbarui.',
        ]);
    }

    /**
     * Delete procurement.
     *
     * Hanya pending.
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

        /*
         * Approved, rejected, completed immutable.
         */
        if (! $procurement->isPending()) {
            throw ValidationException::withMessages([
                'procurement' =>
                'Pengajuan yang sudah diproses tidak dapat dihapus.',
            ]);
        }

        /*
         * Super Admin boleh menghapus semua pending.
         *
         * Admin Fakultas hanya pengajuan sendiri.
         */
        if (
            ! $this->isSuperAdmin($user) &&
            (int) $procurement->requested_by !==
            (int) $user->id
        ) {
            abort(
                403,
                'Anda tidak memiliki izin untuk menghapus pengajuan ini.'
            );
        }

        $signaturePath =
            $procurement->requester_signature;

        DB::transaction(function () use (
            $procurement
        ) {
            /*
             * Lock sebelum delete.
             */
            $lockedProcurement =
                Procurement::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $procurement->id
                    );

            if (! $lockedProcurement->isPending()) {
                throw ValidationException::withMessages([
                    'procurement' =>
                    'Pengajuan ini sudah diproses dan tidak dapat dihapus.',
                ]);
            }

            $lockedProcurement->delete();
        });

        /*
         * Hapus file setelah DB berhasil.
         */
        $this->deleteSignatureFile(
            $signaturePath
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Pengajuan pengadaan berhasil dihapus.',
        ]);
    }

    /**
     * Approve procurement.
     *
     * Hanya Super Admin.
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
                'max:1500000',
            ],

            'admin_note' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        /*
         * Approval signature harus signature BARU
         * atau null.
         */
        $signaturePath = $this->resolveNewSignature(
            $validated['approver_signature'] ?? null,
            'approver_signature'
        );

        try {
            DB::transaction(function () use (
                $procurement,
                $user,
                $validated,
                $signaturePath
            ) {
                $lockedProcurement =
                    Procurement::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $procurement->id
                        );

                /*
                 * Hanya pending → approved.
                 */
                if (! $lockedProcurement->isPending()) {
                    throw ValidationException::withMessages([
                        'procurement' =>
                        'Pengajuan ini sudah diproses sebelumnya.',
                    ]);
                }

                $lockedProcurement->update([
                    'status' =>
                        Procurement::STATUS_APPROVED,

                    'processed_by' =>
                        $user->id,

                    'approver_signature' =>
                        $signaturePath,

                    'processed_at' =>
                        now(),

                    'admin_note' =>
                        isset($validated['admin_note'])
                            ? trim(
                                $validated['admin_note']
                            )
                            : null,
                ]);
            });
        } catch (\Throwable $e) {
            /*
             * Hanya signature baru yang dibuat pada request ini.
             */
            $this->deleteSignatureFile(
                $signaturePath
            );

            throw $e;
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Pengajuan pengadaan berhasil disetujui.',
        ]);
    }

    /**
     * Reject procurement.
     *
     * Hanya Super Admin.
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
                'max:1500000',
            ],

            /*
             * Alasan reject WAJIB.
             */
            'admin_note' => [
                'required',
                'string',
                'min:5',
                'max:5000',
            ],
        ]);

        $signaturePath = $this->resolveNewSignature(
            $validated['approver_signature'] ?? null,
            'approver_signature'
        );

        try {
            DB::transaction(function () use (
                $procurement,
                $user,
                $validated,
                $signaturePath
            ) {
                $lockedProcurement =
                    Procurement::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $procurement->id
                        );

                /*
                 * Hanya pending → rejected.
                 */
                if (! $lockedProcurement->isPending()) {
                    throw ValidationException::withMessages([
                        'procurement' =>
                        'Pengajuan ini sudah diproses sebelumnya.',
                    ]);
                }

                $lockedProcurement->update([
                    'status' =>
                        Procurement::STATUS_REJECTED,

                    'processed_by' =>
                        $user->id,

                    'approver_signature' =>
                        $signaturePath,

                    'processed_at' =>
                        now(),

                    'admin_note' =>
                        trim(
                            $validated['admin_note']
                        ),
                ]);
            });
        } catch (\Throwable $e) {
            $this->deleteSignatureFile(
                $signaturePath
            );

            throw $e;
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Pengajuan pengadaan berhasil ditolak.',
        ]);
    }

    /**
     * Mark approved procurement as completed.
     *
     * Hanya Super Admin.
     *
     * approved → completed
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
            $lockedProcurement =
                Procurement::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $procurement->id
                    );

            /*
             * Hanya approved → completed.
             */
            if (! $lockedProcurement->isApproved()) {
                throw ValidationException::withMessages([
                    'procurement' =>
                    'Hanya pengadaan yang sudah disetujui yang dapat diselesaikan.',
                ]);
            }

            $lockedProcurement->update([
                'status' =>
                    Procurement::STATUS_COMPLETED,

                /*
                 * Catat user yang melakukan aksi terakhir.
                 */
                'processed_by' =>
                    $user->id,

                'processed_at' =>
                    now(),
            ]);
        });

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Pengadaan berhasil ditandai sebagai selesai.',
        ]);
    }

    /**
     * Authorization dasar untuk area admin.
     */
    private function authorizeAdminRole(
        Request $request
    ): void {
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
     * Authorization approval/rejection/completion.
     *
     * HANYA SUPER ADMIN.
     */
    private function authorizeApprovalRole(
        Request $request
    ): void {
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
        /*
         * Super Admin global.
         */
        if ($this->isSuperAdmin($user)) {
            return;
        }

        /*
         * SDM boleh monitoring seluruh fakultas.
         */
        if ($this->isSdm($user)) {
            return;
        }

        /*
         * Admin Fakultas hanya fakultas sendiri.
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
            ->where(
                'faculty_id',
                $facultyId
            )
            ->exists();

        abort_unless(
            $exists,
            422,
            'Ruangan tidak berada dalam fakultas yang dipilih.'
        );
    }

    /**
     * Tentukan faculty_id berdasarkan role.
     *
     * Super Admin:
     * - mengambil faculty_id dari request.
     *
     * Admin Fakultas:
     * - selalu menggunakan faculty_id akun.
     */
    private function resolveFacultyId(
        $user,
        ?int $requestedFacultyId = null
    ): int {
        /*
         * Super Admin boleh memilih fakultas.
         */
        if ($this->isSuperAdmin($user)) {
            if (
                $requestedFacultyId === null ||
                $requestedFacultyId <= 0
            ) {
                throw ValidationException::withMessages([
                    'faculty_id' =>
                    'Fakultas wajib dipilih.',
                ]);
            }

            $exists = Faculty::query()
                ->whereKey($requestedFacultyId)
                ->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    'faculty_id' =>
                    'Fakultas yang dipilih tidak valid.',
                ]);
            }

            return $requestedFacultyId;
        }

        /*
         * Admin Fakultas selalu menggunakan
         * faculty_id dari akun.
         */
        if ($this->isFacultyAdmin($user)) {
            $this->ensureUserHasFaculty($user);

            return (int) $user->faculty_id;
        }

        abort(
            403,
            'Anda tidak memiliki izin untuk membuat pengajuan pengadaan.'
        );
    }

    /**
     * Ambil room sesuai scope user.
     *
     * Field disesuaikan dengan kebutuhan frontend:
     * - id
     * - faculty_id
     * - code
     * - name
     * - building
     * - floor
     */
    private function getAvailableRooms($user)
    {
        $query = Room::query()
            ->select(
                'id',
                'faculty_id',
                'code',
                'name',
                'building',
                'floor'
            )
            ->orderBy('name');

        /*
         * Admin Fakultas hanya melihat room fakultasnya.
         */
        if ($this->isFacultyAdmin($user)) {
            $this->ensureUserHasFaculty($user);

            $query->where(
                'faculty_id',
                $user->faculty_id
            );
        }

        /*
         * Super Admin dan SDM melihat semua room.
         */
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

    /**
     * Resolve signature BARU.
     *
     * Hanya menerima:
     * - null
     * - data:image/png;base64,...
     *
     * Tidak menerima arbitrary URL/path dari client.
     */
    private function resolveNewSignature(
        ?string $signature,
        string $field
    ): ?string {
        if (
            $signature === null ||
            trim($signature) === ''
        ) {
            return null;
        }

        if (
            ! str_starts_with(
                $signature,
                'data:image/png;base64,'
            )
        ) {
            throw ValidationException::withMessages([
                $field =>
                'Tanda tangan harus berupa gambar PNG yang valid.',
            ]);
        }

        return $this->storeSignatureImage(
            $signature,
            $field
        );
    }

    /**
     * Resolve signature saat update.
     *
     * Allowed:
     * - null → hapus signature
     * - data URI PNG → ganti signature
     * - signature lama yang sama persis → pertahankan
     */
    private function resolveUpdateSignature(
        ?string $signature,
        ?string $oldSignaturePath,
        string $field
    ): ?string {
        if (
            $signature === null ||
            trim($signature) === ''
        ) {
            return null;
        }

        /*
         * Jika frontend mengirim signature lama,
         * hanya izinkan jika benar-benar sama dengan
         * signature yang tersimpan.
         */
        if (
            $oldSignaturePath !== null &&
            hash_equals(
                $oldSignaturePath,
                $signature
            )
        ) {
            return $oldSignaturePath;
        }

        /*
         * Selain signature lama harus berupa PNG baru.
         */
        return $this->resolveNewSignature(
            $signature,
            $field
        );
    }

    /**
     * Decode data URI PNG,
     * validasi format, ukuran, MIME, dan dimensi,
     * kemudian simpan ke storage public.
     */
    private function storeSignatureImage(
        string $dataUrl,
        string $field
    ): string {
        if (
            ! preg_match(
                '/^data:image\/png;base64,(?<data>[A-Za-z0-9+\/=\r\n]+)$/',
                $dataUrl,
                $matches
            )
        ) {
            throw ValidationException::withMessages([
                $field =>
                'Format tanda tangan tidak valid.',
            ]);
        }

        /*
         * Decode base64 secara strict.
         */
        $binary = base64_decode(
            $matches['data'],
            true
        );

        if ($binary === false) {
            throw ValidationException::withMessages([
                $field =>
                'Data tanda tangan tidak dapat dibaca.',
            ]);
        }

        /*
         * Batasi ukuran binary hasil decode.
         */
        if (
            strlen($binary) >
            self::MAX_SIGNATURE_BYTES
        ) {
            throw ValidationException::withMessages([
                $field =>
                'Ukuran tanda tangan terlalu besar (maksimal 1MB).',
            ]);
        }

        /*
         * Pastikan benar-benar image.
         */
        $imageInfo = @getimagesizefromstring(
            $binary
        );

        if (
            $imageInfo === false ||
            ! isset(
                $imageInfo['mime'],
                $imageInfo[0],
                $imageInfo[1]
            )
        ) {
            throw ValidationException::withMessages([
                $field =>
                'File tanda tangan bukan gambar yang valid.',
            ]);
        }

        /*
         * Pastikan MIME benar-benar PNG.
         */
        if (
            $imageInfo['mime'] !== 'image/png'
        ) {
            throw ValidationException::withMessages([
                $field =>
                'Tanda tangan harus menggunakan format PNG.',
            ]);
        }

        /*
         * Batasi dimensi gambar.
         */
        if (
            $imageInfo[0] >
                self::MAX_SIGNATURE_WIDTH ||
            $imageInfo[1] >
                self::MAX_SIGNATURE_HEIGHT
        ) {
            throw ValidationException::withMessages([
                $field =>
                'Dimensi tanda tangan terlalu besar.',
            ]);
        }

        /*
         * Pastikan GD dapat membaca PNG tersebut.
         */
        $image = @imagecreatefromstring(
            $binary
        );

        if ($image === false) {
            throw ValidationException::withMessages([
                $field =>
                'File tanda tangan bukan PNG yang valid.',
            ]);
        }

        imagedestroy($image);

        /*
         * Generate nama file random/UUID.
         */
        $path =
            'signatures/' .
            Str::uuid()->toString() .
            '.png';

        $disk = Storage::disk('public');

        /*
         * Simpan binary.
         */
        $stored = $disk->put(
            $path,
            $binary
        );

        if (! $stored) {
            throw ValidationException::withMessages([
                $field =>
                'Tanda tangan gagal disimpan.',
            ]);
        }

        return $disk->url($path);
    }

    /**
     * Hapus signature lama jika sudah diganti.
     */
    private function deleteOldSignatureIfReplaced(
        ?string $oldPath,
        ?string $newPath
    ): void {
        if (
            empty($oldPath) ||
            $oldPath === $newPath
        ) {
            return;
        }

        $this->deleteSignatureFile(
            $oldPath
        );
    }

    /**
     * Hapus file signature dari storage.
     *
     * Hanya file di:
     * signatures/
     *
     * yang boleh dihapus.
     */
    private function deleteSignatureFile(
        ?string $urlOrPath
    ): void {
        if (
            empty($urlOrPath)
        ) {
            return;
        }

        $disk = Storage::disk('public');

        $relativePath = null;

        /*
         * Jika nilai merupakan relative path.
         */
        if (
            str_starts_with(
                $urlOrPath,
                'signatures/'
            )
        ) {
            $relativePath =
                ltrim(
                    $urlOrPath,
                    '/'
                );
        }

        /*
         * Jika nilai merupakan URL public storage.
         */
        if ($relativePath === null) {
            $publicPrefix = $disk->url('');

            if (
                str_starts_with(
                    $urlOrPath,
                    $publicPrefix
                )
            ) {
                $relativePath = ltrim(
                    substr(
                        $urlOrPath,
                        strlen($publicPrefix)
                    ),
                    '/'
                );
            }
        }

        /*
         * Jika nilai berupa absolute URL,
         * ambil path setelah /storage/.
         */
        if ($relativePath === null) {
            $parsedPath = parse_url(
                $urlOrPath,
                PHP_URL_PATH
            );

            if (
                is_string($parsedPath)
            ) {
                $storageMarker = '/storage/';

                $position = strpos(
                    $parsedPath,
                    $storageMarker
                );

                if ($position !== false) {
                    $relativePath = ltrim(
                        substr(
                            $parsedPath,
                            $position +
                                strlen($storageMarker)
                        ),
                        '/'
                    );
                }
            }
        }

        if (
            empty($relativePath)
        ) {
            return;
        }

        /*
         * Normalisasi slash.
         */
        $relativePath =
            str_replace(
                '\\',
                '/',
                $relativePath
            );

        /*
         * Path traversal protection.
         */
        if (
            str_contains(
                $relativePath,
                '..'
            )
        ) {
            return;
        }

        /*
         * Hanya signature procurement.
         */
        if (
            ! str_starts_with(
                $relativePath,
                'signatures/'
            )
        ) {
            return;
        }

        /*
         * Jangan izinkan nested traversal aneh.
         */
        if (
            preg_match(
                '#(^|/)\./#',
                $relativePath
            )
        ) {
            return;
        }

        if (
            $disk->exists(
                $relativePath
            )
        ) {
            $disk->delete(
                $relativePath
            );
        }
    }
}