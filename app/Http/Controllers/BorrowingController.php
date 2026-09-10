<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\Faculty;
use App\Models\RoomInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class BorrowingController extends Controller
{
    /**
     * Status yang benar-benar mengunci inventory.
     *
     * pending TIDAK dimasukkan karena sistem menggunakan
     * mekanisme antrean approval.
     */
    private const BLOCKING_STATUSES = [
        'approved',
        'borrowed',
    ];

    /**
     * Status yang masih menunggu keputusan admin.
     */
    private const PENDING_STATUS = 'pending';

    /**
     * Display borrowing list.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless($user, 401);

        Gate::authorize('viewAny', Borrowing::class);

        /*
    |--------------------------------------------------------------------------
    | Data Peminjaman
    |--------------------------------------------------------------------------
    */

        $borrowingsQuery = Borrowing::query()
            ->with([
                'user:id,name,email',
                'faculty:id,name,code',
                'roomInventory:id,room_id,item_id,asset_code,condition,is_borrowable',
                'roomInventory.room:id,name,code,faculty_id,is_active',
                'roomInventory.item:id,name',
                'approver:id,name,email',
            ])
            ->latest('id');

        /*
    |--------------------------------------------------------------------------
    | Scope Peminjaman Berdasarkan Role
    |--------------------------------------------------------------------------
    |
    | - super_admin         : lihat SEMUA data, lintas fakultas.
    | - admin_fakultas & sdm: hanya peminjaman fakultasnya sendiri.
    |   (sdm read-only, lihat Gate 'create' & authorizeApprovalRole()
    |   yang tidak mengizinkan sdm create/approve/reject/return/cancel).
    | - dosen/mahasiswa     : hanya peminjaman miliknya sendiri.
    |--------------------------------------------------------------------------
    */

        if ($user->role === 'super_admin') {
            // Tidak difilter.
        } elseif (in_array($user->role, ['admin_fakultas', 'sdm'], true)) {
            $borrowingsQuery->where('faculty_id', $user->faculty_id);
        } elseif (in_array($user->role, ['dosen', 'mahasiswa'], true)) {
            $borrowingsQuery->where('user_id', $user->id);
        } else {
            $borrowingsQuery->whereRaw('1 = 0');
        }

        $borrowings = $borrowingsQuery->get();

        /*
    |--------------------------------------------------------------------------
    | Data Fakultas
    |--------------------------------------------------------------------------
    |
    | - super_admin              : semua fakultas.
    | - admin_fakultas/sdm       : fakultas sendiri saja.
    | - dosen/mahasiswa          : fakultas sendiri saja (untuk auto-isi
    |                              faculty_id di form pengajuan).
    |--------------------------------------------------------------------------
    */

        $facultiesQuery = Faculty::query()
            ->select(['id', 'name', 'code'])
            ->orderBy('name');

        if ($user->role === 'super_admin') {
            // Tidak difilter.
        } elseif (
            in_array($user->role, ['admin_fakultas', 'sdm'], true)
            || in_array($user->role, ['dosen', 'mahasiswa'], true)
        ) {
            $facultiesQuery->whereKey($user->faculty_id);
        } else {
            $facultiesQuery->whereRaw('1 = 0');
        }

        $faculties = $facultiesQuery->get();

        $availabilityValidated = $request->validate([
            'borrow_date' => ['nullable', 'date'],
            'expected_return_date' => ['nullable', 'date', 'after_or_equal:borrow_date'],
        ]);

        $borrowDate = $availabilityValidated['borrow_date'] ?? null;
        $expectedReturnDate = $availabilityValidated['expected_return_date'] ?? null;

        /*
    |--------------------------------------------------------------------------
    | Data Inventaris yang Bisa Dipinjam
    |--------------------------------------------------------------------------
    |
    | - super_admin              : semua inventaris borrowable, lintas fakultas.
    | - admin_fakultas/sdm       : inventaris fakultasnya saja.
    | - dosen/mahasiswa          : inventaris fakultasnya saja (untuk dipilih
    |                              saat mengajukan/mengedit peminjaman).
    |--------------------------------------------------------------------------
    */

        $roomInventoriesQuery = RoomInventory::query()
            ->with(['room:id,name,code,faculty_id,is_active', 'item:id,name'])
            ->select(['id', 'room_id', 'item_id', 'asset_code', 'condition', 'is_borrowable'])
            ->where('is_borrowable', true)
            ->where('condition', '!=', 'damaged_heavy')
            ->whereHas('room', function ($query) {
                $query->where('is_active', true);
            });

        if (
            in_array($user->role, ['admin_fakultas', 'sdm'], true)
            || (in_array($user->role, ['dosen', 'mahasiswa'], true) && $user->faculty_id)
        ) {
            $roomInventoriesQuery->whereHas('room', function ($query) use ($user) {
                $query->where('faculty_id', $user->faculty_id)->where('is_active', true);
            });
        }
        // super_admin: sengaja tidak difilter — lihat semua inventaris lintas fakultas.

        $roomInventories = $roomInventoriesQuery->orderBy('asset_code')->get();

        /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    |
    | dosen/mahasiswa                     -> halaman self-service.
    | super_admin/admin_fakultas/sdm      -> halaman admin
    |                                        (sdm = read-only, faculty-scoped).
    |--------------------------------------------------------------------------
    */

        $isSelfServiceRole = in_array($user->role, ['dosen', 'mahasiswa'], true);

        return Inertia::render(
            $isSelfServiceRole ? 'Users/Borrowings/Index' : 'Admin/Borrowings/Index',
            [
                'borrowings' => $borrowings,

                'faculties' => $faculties,

                'roomInventories' => $roomInventories,

                'canDelete' => $user->role === 'super_admin',

                // Dipakai Admin/Borrowings/Index.vue untuk sembunyikan
                // tombol approve/reject/return/cancel dari role 'sdm'.
                'canManageWorkflow' => in_array(
                    $user->role,
                    ['super_admin', 'admin_fakultas'],
                    true
                ),

                'availability' => [
                    'borrow_date' => $borrowDate,
                    'expected_return_date' => $expectedReturnDate,
                ],
            ]
        );
    }

    /**
     * Store a new borrowing.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        abort_unless($user, 401);

        Gate::authorize(
            'create',
            Borrowing::class
        );

        /*
        |--------------------------------------------------------------------------
        | Validasi Request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'faculty_id' => [
                'required',
                'integer',
                'exists:faculties,id',
            ],

            'room_inventory_id' => [
                'required',
                'integer',
                'exists:room_inventories,id',
            ],

            'borrow_date' => [
                'required',
                'date',
            ],

            'expected_return_date' => [
                'required',
                'date',
                'after_or_equal:borrow_date',
            ],

            'purpose' => [
                'required',
                'string',
                'max:1000',
            ],

            'applicant_signature' => [
                'nullable',
                'string',
                'max:3000000',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Validasi Fakultas User
        |--------------------------------------------------------------------------
        */

        $this->validateFacultyAccess(
            $user,
            (int) $validated['faculty_id']
        );

        $signaturePath = null;

        try {
            DB::transaction(
                function () use (
                    $validated,
                    $user,
                    &$signaturePath
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | Lock Inventory
                    |--------------------------------------------------------------------------
                    */

                    $roomInventory =
                        RoomInventory::query()
                        ->with([
                            'room:id,name,code,faculty_id,is_active',
                            'item:id,name',
                        ])
                        ->whereKey(
                            $validated['room_inventory_id']
                        )
                        ->lockForUpdate()
                        ->first();

                    if (!$roomInventory) {
                        throw ValidationException::withMessages([
                            'room_inventory_id' =>
                            'Inventaris tidak ditemukan.',
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Validasi Inventory
                    |--------------------------------------------------------------------------
                    */

                    $this->validateBorrowableInventory(
                        $roomInventory
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Validasi Fakultas Inventory
                    |--------------------------------------------------------------------------
                    */

                    $this->validateInventoryFacultyAccess(
                        $user,
                        $roomInventory
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | STORE:
                    |
                    | Pending lain TIDAK dianggap bentrok.
                    |
                    | Yang diblok hanya:
                    |
                    | approved
                    | borrowed
                    |
                    | Dengan tanggal overlap.
                    |--------------------------------------------------------------------------
                    */

                    $this->ensureInventoryAvailableForNewRequest(
                        (int) $roomInventory->id,
                        $validated['borrow_date'],
                        $validated['expected_return_date']
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Signature
                    |--------------------------------------------------------------------------
                    */

                    $signaturePath =
                        $this->storeSignature(
                            $validated['applicant_signature'] ?? null,
                            'applicant_signature'
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Create Borrowing
                    |--------------------------------------------------------------------------
                    */

                    Borrowing::create([
                        'user_id' =>
                        $user->id,

                        'faculty_id' =>
                        $validated['faculty_id'],

                        'room_inventory_id' =>
                        $roomInventory->id,

                        'borrow_date' =>
                        $validated['borrow_date'],

                        'expected_return_date' =>
                        $validated['expected_return_date'],

                        'purpose' =>
                        trim(
                            $validated['purpose']
                        ),

                        'applicant_signature' =>
                        $signaturePath,

                        'signed_at' =>
                        $signaturePath
                            ? now()
                            : null,

                        'status' =>
                        self::PENDING_STATUS,
                    ]);
                }
            );
        } catch (\Throwable $e) {
            if ($signaturePath) {
                Storage::disk('public')->delete(
                    $signaturePath
                );
            }

            throw $e;
        }

        return redirect()
            ->back()
            ->with(
                'toast',
                [
                    'type' =>
                    'success',

                    'message' =>
                    'Peminjaman berhasil diajukan dan menunggu persetujuan.',
                ]
            );
    }

    /**
     * Update borrowing.
     */
    public function update(
        Request $request,
        Borrowing $borrowing
    ) {
        $user = $request->user();

        abort_unless($user, 401);

        Gate::authorize('update', $borrowing);

        /*
    |--------------------------------------------------------------------------
    | Workflow Action
    |--------------------------------------------------------------------------
    */

        if ($request->has('status')) {
            return $this->processWorkflow($request, $borrowing, $user);
        }

        /*
    |--------------------------------------------------------------------------
    | EDIT BIASA
    |--------------------------------------------------------------------------
    */

        if (
            in_array(
                $borrowing->status,
                ['approved', 'borrowed', 'returned', 'cancelled'],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'borrowing' => 'Peminjaman yang sudah diproses tidak dapat diedit.',
            ]);
        }

        $validated = $request->validate([
            'faculty_id' => ['required', 'integer', 'exists:faculties,id'],
            'room_inventory_id' => ['required', 'integer', 'exists:room_inventories,id'],
            'borrow_date' => ['required', 'date'],
            'expected_return_date' => ['required', 'date', 'after_or_equal:borrow_date'],
            'purpose' => ['required', 'string', 'max:1000'],
            'applicant_signature' => ['nullable', 'string', 'max:3000000'],
        ]);

        $this->validateFacultyAccess($user, (int) $validated['faculty_id']);

        $newSignaturePath = null;
        $oldSignaturePath = null;

        try {
            DB::transaction(function () use (
                $borrowing,
                $validated,
                $user,
                &$newSignaturePath,
                &$oldSignaturePath
            ) {
                /*
            |--------------------------------------------------------------------------
            | Lock Borrowing
            |--------------------------------------------------------------------------
            */

                $lockedBorrowing = Borrowing::query()
                    ->whereKey($borrowing->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedBorrowing) {
                    throw ValidationException::withMessages([
                        'borrowing' => 'Data peminjaman tidak ditemukan.',
                    ]);
                }

                if (
                    !in_array(
                        $lockedBorrowing->status,
                        [self::PENDING_STATUS, 'rejected'],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'borrowing' => 'Peminjaman yang sudah diproses tidak dapat diedit.',
                    ]);
                }

                // Ditentukan di sini, dipakai nanti setelah $data dibuat.
                $isResubmit = $lockedBorrowing->status === 'rejected';

                /*
            |--------------------------------------------------------------------------
            | Lock Inventory
            |--------------------------------------------------------------------------
            */

                $roomInventory = RoomInventory::query()
                    ->with(['room:id,name,code,faculty_id,is_active', 'item:id,name'])
                    ->whereKey($validated['room_inventory_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$roomInventory) {
                    throw ValidationException::withMessages([
                        'room_inventory_id' => 'Inventaris tidak ditemukan.',
                    ]);
                }

                $this->validateBorrowableInventory($roomInventory);
                $this->validateInventoryFacultyAccess($user, $roomInventory);

                $this->ensureInventoryAvailableForNewRequest(
                    (int) $roomInventory->id,
                    $validated['borrow_date'],
                    $validated['expected_return_date'],
                    (int) $lockedBorrowing->id
                );

                $data = [
                    'faculty_id' => $validated['faculty_id'],
                    'room_inventory_id' => $roomInventory->id,
                    'borrow_date' => $validated['borrow_date'],
                    'expected_return_date' => $validated['expected_return_date'],
                    'purpose' => trim($validated['purpose']),
                ];

                // Resubmit: rejected -> pending lagi, hapus catatan penolakan lama.
                if ($isResubmit) {
                    $data['status'] = self::PENDING_STATUS;
                    $data['rejection_note'] = null;
                }

                /*
            |--------------------------------------------------------------------------
            | Signature Baru
            |--------------------------------------------------------------------------
            */

                if (!empty($validated['applicant_signature'])) {
                    $newSignaturePath = $this->storeSignature(
                        $validated['applicant_signature'],
                        'applicant_signature'
                    );

                    $oldSignaturePath = $lockedBorrowing->applicant_signature;
                    $data['applicant_signature'] = $newSignaturePath;
                    $data['signed_at'] = now();
                }

                $lockedBorrowing->update($data);
            });
        } catch (\Throwable $e) {
            if ($newSignaturePath) {
                Storage::disk('public')->delete($newSignaturePath);
            }

            throw $e;
        }

        if ($oldSignaturePath) {
            Storage::disk('public')->delete($oldSignaturePath);
        }

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Data peminjaman berhasil diperbarui.',
        ]);
    }

    /**
     * Delete borrowing.
     */
    public function destroy(
        Request $request,
        Borrowing $borrowing
    ) {
        $user = $request->user();

        abort_unless($user, 401);


        Gate::authorize(
            'delete',
            $borrowing
        );

        if (
            in_array(
                $borrowing->status,
                [
                    'pending',
                    'approved',
                    'borrowed',
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'borrowing' =>
                'Peminjaman yang masih aktif tidak dapat dihapus. Selesaikan atau batalkan peminjaman terlebih dahulu.',
            ]);
        }


        if (
            !in_array(
                $borrowing->status,
                [
                    'rejected',
                    'returned',
                    'cancelled',
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'borrowing' =>
                'Status peminjaman tidak dapat dihapus.',
            ]);
        }

        $applicantSignature =
            $borrowing->applicant_signature;

        $approverSignature =
            $borrowing->approver_signature;


        DB::transaction(
            function () use ($borrowing) {

                $lockedBorrowing =
                    Borrowing::query()
                    ->whereKey(
                        $borrowing->id
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$lockedBorrowing) {
                    throw ValidationException::withMessages([
                        'borrowing' =>
                        'Data peminjaman tidak ditemukan.',
                    ]);
                }

                if (
                    !in_array(
                        $lockedBorrowing->status,
                        [
                            'rejected',
                            'returned',
                            'cancelled',
                        ],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'borrowing' =>
                        'Data peminjaman tidak dapat dihapus karena statusnya sudah berubah.',
                    ]);
                }

                $lockedBorrowing->delete();
            }
        );

        if ($applicantSignature) {
            Storage::disk('public')->delete(
                $applicantSignature
            );
        }

        if ($approverSignature) {
            Storage::disk('public')->delete(
                $approverSignature
            );
        }
        return redirect()
            ->back()
            ->with(
                'toast',
                [
                    'type' =>
                    'success',

                    'message' =>
                    'Data peminjaman berhasil dihapus secara permanen.',
                ]
            );
    }

    /**
     * Validate faculty access.
     */
    private function validateFacultyAccess(
        $user,
        int $facultyId
    ): void {
        if (
            $user->role === 'admin_fakultas'
            && (int) $facultyId
            !== (int) $user->faculty_id
        ) {
            throw ValidationException::withMessages([
                'faculty_id' =>
                'Anda tidak memiliki akses ke fakultas tersebut.',
            ]);
        }

        if (
            in_array(
                $user->role,
                [
                    'dosen',
                    'mahasiswa',
                ],
                true
            )
            && $user->faculty_id
            && (int) $facultyId
            !== (int) $user->faculty_id
        ) {
            throw ValidationException::withMessages([
                'faculty_id' =>
                'Peminjaman harus menggunakan fakultas Anda.',
            ]);
        }
    }

    /**
     * Validate faculty access for approval workflow.
     */
    private function validateApprovalFacultyAccess(
        $user,
        Borrowing $borrowing
    ): void {
        if (
            $user->role === 'admin_fakultas'
            && (int) $borrowing->faculty_id
            !== (int) $user->faculty_id
        ) {
            abort(
                403,
                'Anda tidak memiliki akses untuk memproses peminjaman dari fakultas lain.'
            );
        }
    }

    /**
     * Validate inventory faculty access.
     */
    private function validateInventoryFacultyAccess(
        $user,
        RoomInventory $roomInventory
    ): void {
        if (
            !in_array(
                $user->role,
                [
                    'admin_fakultas',
                    'dosen',
                    'mahasiswa',
                ],
                true
            )
            || !$user->faculty_id
        ) {
            return;
        }

        $belongsToFaculty =
            $roomInventory
            ->room()
            ->where(
                'faculty_id',
                $user->faculty_id
            )
            ->where(
                'is_active',
                true
            )
            ->exists();

        if (!$belongsToFaculty) {
            throw ValidationException::withMessages([
                'room_inventory_id' =>
                'Inventaris tersebut tidak tersedia untuk fakultas Anda.',
            ]);
        }
    }

    /**
     * Validate basic borrowable inventory condition.
     */
    private function validateBorrowableInventory(
        RoomInventory $roomInventory
    ): void {
        if (
            !$roomInventory->is_borrowable
        ) {
            throw ValidationException::withMessages([
                'room_inventory_id' =>
                'Inventaris tersebut tidak dapat dipinjam.',
            ]);
        }

        if (
            $roomInventory->condition
            === 'damaged_heavy'
        ) {
            throw ValidationException::withMessages([
                'room_inventory_id' =>
                'Inventaris tersebut mengalami kerusakan berat dan tidak dapat dipinjam.',
            ]);
        }

        if (
            !$roomInventory->room
            || !$roomInventory->room->is_active
        ) {
            throw ValidationException::withMessages([
                'room_inventory_id' =>
                'Ruangan inventaris tersebut tidak aktif.',
            ]);
        }
    }

    /**
     * Ensure inventory is available for a NEW borrowing request.
     *
     * IMPORTANT:
     *
     * pending tidak dianggap sebagai blocker.
     *
     * Hanya:
     * - approved
     * - borrowed
     *
     * yang memblokir inventory.
     */
    private function ensureInventoryAvailableForNewRequest(
        int $inventoryId,
        string $borrowDate,
        string $expectedReturnDate,
        ?int $exceptBorrowingId = null
    ): void {
        $query = Borrowing::query()
            ->where(
                'room_inventory_id',
                $inventoryId
            )
            ->whereIn(
                'status',
                self::BLOCKING_STATUSES
            )
            ->where(
                'borrow_date',
                '<=',
                $expectedReturnDate
            )
            ->where(
                'expected_return_date',
                '>=',
                $borrowDate
            );

        if ($exceptBorrowingId) {
            $query->where(
                'id',
                '!=',
                $exceptBorrowingId
            );
        }

        $existingBorrowing =
            $query->first();

        if ($existingBorrowing) {
            throw ValidationException::withMessages([
                'room_inventory_id' =>
                'Aset tersebut sedang digunakan pada rentang tanggal yang dipilih. Silakan pilih tanggal lain atau aset lain.',
            ]);
        }
    }

    /**
     * Ensure inventory is available when approving.
     *
     * Saat approval, pending lain TIDAK dianggap blocker.
     *
     * Hanya approved / borrowed yang dapat menyebabkan
     * approval gagal.
     */
    private function ensureInventoryAvailableForApproval(
        int $inventoryId,
        string $borrowDate,
        string $expectedReturnDate,
        ?int $exceptBorrowingId = null
    ): void {
        $query = Borrowing::query()
            ->where(
                'room_inventory_id',
                $inventoryId
            )
            ->whereIn(
                'status',
                self::BLOCKING_STATUSES
            )
            ->where(
                'borrow_date',
                '<=',
                $expectedReturnDate
            )
            ->where(
                'expected_return_date',
                '>=',
                $borrowDate
            );

        if ($exceptBorrowingId) {
            $query->where(
                'id',
                '!=',
                $exceptBorrowingId
            );
        }

        $existingBorrowing =
            $query->first();

        if ($existingBorrowing) {
            throw ValidationException::withMessages([
                'status' =>
                'Peminjaman tidak dapat disetujui karena aset sudah digunakan pada rentang tanggal tersebut.',
            ]);
        }
    }

    /**
     * Automatically reject pending borrowings that overlap
     * with the borrowing that has just been approved.
     *
     * Contoh:
     *
     * A -> Asset 01 -> 10-12 -> APPROVED
     * B -> Asset 01 -> 11-13 -> REJECTED
     * C -> Asset 01 -> 15-17 -> PENDING
     *
     * C tidak ditolak karena tidak overlap.
     */
    private function rejectConflictingPendingBorrowings(
        int $inventoryId,
        string $borrowDate,
        string $expectedReturnDate,
        int $approvedBorrowingId
    ): void {
        Borrowing::query()
            ->where(
                'room_inventory_id',
                $inventoryId
            )
            ->where(
                'status',
                self::PENDING_STATUS
            )
            ->where(
                'id',
                '!=',
                $approvedBorrowingId
            )
            ->where(
                'borrow_date',
                '<=',
                $expectedReturnDate
            )
            ->where(
                'expected_return_date',
                '>=',
                $borrowDate
            )
            ->update([
                'status' =>
                'rejected',

                'rejection_note' =>
                'Pengajuan otomatis ditolak karena aset telah disetujui untuk peminjaman lain pada rentang tanggal yang sama.',

                'approved_by' =>
                null,

                'approved_at' =>
                null,

                'approver_signature' =>
                null,
            ]);
    }

    /**
     * Find borrowable inventory.
     *
     * Method ini digunakan untuk kebutuhan yang memerlukan
     * inventory yang secara fisik layak dipinjam.
     */
    private function findBorrowableInventory(
        int $inventoryId,
        string $borrowDate,
        string $expectedReturnDate,
        ?int $exceptBorrowingId = null
    ): RoomInventory {
        $inventory =
            RoomInventory::query()
            ->with([
                'room:id,name,code,faculty_id,is_active',
                'item:id,name',
            ])
            ->whereKey(
                $inventoryId
            )
            ->first();

        if (!$inventory) {
            throw ValidationException::withMessages([
                'room_inventory_id' =>
                'Inventaris tidak ditemukan.',
            ]);
        }

        $this->validateBorrowableInventory(
            $inventory
        );

        $this->ensureInventoryAvailableForNewRequest(
            $inventory->id,
            $borrowDate,
            $expectedReturnDate,
            $exceptBorrowingId
        );

        return $inventory;
    }

    /**
     * Authorize approval / return / cancellation role.
     */
    private function authorizeApprovalRole(
        $user
    ): void {
        if (
            !in_array(
                $user->role,
                [
                    'super_admin',
                    'admin_fakultas',
                ],
                true
            )
        ) {
            abort(
                403,
                'Anda tidak memiliki akses untuk memproses peminjaman.'
            );
        }
    }

    /**
     * Simpan signature Base64 sebagai file.
     */
    private function storeSignature(
        ?string $signature,
        string $validationKey = 'applicant_signature'
    ): ?string {
        if (!$signature) {
            return null;
        }

        if (
            !preg_match(
                '/^data:image\/(png|jpeg|jpg);base64,(.+)$/s',
                $signature,
                $matches
            )
        ) {
            throw ValidationException::withMessages([
                $validationKey =>
                'Format tanda tangan tidak valid.',
            ]);
        }

        $base64 = str_replace(
            ' ',
            '+',
            $matches[2]
        );

        $image = base64_decode(
            $base64,
            true
        );

        if ($image === false) {
            throw ValidationException::withMessages([
                $validationKey =>
                'Data tanda tangan tidak valid.',
            ]);
        }

        if (
            strlen($image)
            >
            2 * 1024 * 1024
        ) {
            throw ValidationException::withMessages([
                $validationKey =>
                'Ukuran tanda tangan maksimal 2 MB.',
            ]);
        }

        $imageInfo =
            @getimagesizefromstring(
                $image
            );

        if ($imageInfo === false) {
            throw ValidationException::withMessages([
                $validationKey =>
                'File tanda tangan bukan gambar yang valid.',
            ]);
        }

        $allowedMimeTypes = [
            'image/png',
            'image/jpeg',
        ];

        if (
            !in_array(
                $imageInfo['mime'] ?? '',
                $allowedMimeTypes,
                true
            )
        ) {
            throw ValidationException::withMessages([
                $validationKey =>
                'Format gambar tidak diperbolehkan.',
            ]);
        }

        $width =
            $imageInfo[0] ?? 0;

        $height =
            $imageInfo[1] ?? 0;

        if (
            $width < 1
            || $height < 1
            || $width > 2000
            || $height > 1000
        ) {
            throw ValidationException::withMessages([
                $validationKey =>
                'Dimensi gambar tanda tangan tidak valid.',
            ]);
        }

        $extension =
            $imageInfo['mime'] === 'image/png'
            ? 'png'
            : 'jpg';

        $filename =
            'signatures/borrowings/'
            . bin2hex(
                random_bytes(24)
            )
            . '.'
            . $extension;

        $stored =
            Storage::disk('public')->put(
                $filename,
                $image
            );

        if (!$stored) {
            throw new \RuntimeException(
                'Gagal menyimpan tanda tangan.'
            );
        }

        return $filename;
    }
}
