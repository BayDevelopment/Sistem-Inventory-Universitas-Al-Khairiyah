<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Procurement;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProcurementController extends Controller
{
    private const MAX_SIGNATURE_BYTES = 1024 * 1024;
    private const MAX_SIGNATURE_WIDTH = 2000;
    private const MAX_SIGNATURE_HEIGHT = 1000;

    private const MAX_ATTACHMENTS = 5;
    private const MAX_ATTACHMENT_KB = 5120;

    private const DOCUMENT_TYPE_CODE = 'PGD';

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Procurement::class);

        $user = $request->user();

        $query = Procurement::query()
            ->with([
                'faculty:id,code,name',
                'requester:id,name',
                'room:id,faculty_id,code,name,building,floor',
                'processor:id,name',
            ])
            ->latest('id');

        if ($this->isFacultyAdmin($user)) {
            $this->ensureUserHasFaculty($user);

            $query->where(
                'faculty_id',
                $user->faculty_id
            );
        }

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
                        ->orWhere(
                            'subject',
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

        if (
            $request->filled('faculty_id') &&
            $this->canViewAllFaculties($user)
        ) {
            $facultyId = $request->integer('faculty_id');

            if ($facultyId > 0) {
                $query->where(
                    'faculty_id',
                    $facultyId
                );
            }
        }

        $procurements = $query
            ->paginate(15)
            ->withQueryString()
            ->through(
                fn (Procurement $procurement) =>
                    $this->transformProcurement(
                        $procurement
                    )
            );

        $faculties = $this->canViewAllFaculties($user)
            ? Faculty::query()
                ->select(
                    'id',
                    'code',
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
                    'code',
                    'name'
                )
                ->orderBy('name')
                ->get();

        return Inertia::render(
            'Admin/Procurements/Index',
            [
                'procurements' => $procurements,

                'faculties' => $faculties,

                'rooms' =>
                    $this->getAvailableRooms($user),

                'filters' => [
                    'search' =>
                        $request->input(
                            'search',
                            ''
                        ),

                    'status' =>
                        $request->input(
                            'status',
                            ''
                        ),

                    'type' =>
                        $request->input(
                            'type',
                            ''
                        ),

                    'faculty_id' =>
                        $request->input(
                            'faculty_id',
                            ''
                        ),
                ],
            ]
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        Gate::authorize(
            'create',
            Procurement::class
        );

        $user = $request->user();

        if ($this->isFacultyAdmin($user)) {
            $this->ensureUserHasFaculty($user);
        }

        $validated = $request->validate([
            'faculty_id' => [
                'nullable',
                'integer',
                'exists:faculties,id',
            ],

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

            'subject' => [
                'nullable',
                'string',
                'max:255',
            ],

            'requester_signature' => [
                'nullable',
                'string',
                'max:1500000',
            ],

            'attachments' => [
                'nullable',
                'array',
                'max:' . self::MAX_ATTACHMENTS,
            ],

            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:' . self::MAX_ATTACHMENT_KB,
            ],
        ]);

        $facultyId = $this->resolveFacultyId(
            $user,
            isset($validated['faculty_id'])
                ? (int) $validated['faculty_id']
                : null
        );

        $this->ensureRoomBelongsToFaculty(
            (int) $validated['room_id'],
            $facultyId
        );

        $signaturePath = $this->resolveNewSignature(
            $validated['requester_signature'] ?? null,
            'requester_signature'
        );

        $storedAttachments = [];

        try {
            DB::transaction(
                function () use (
                    $validated,
                    $user,
                    $facultyId,
                    $signaturePath,
                    $request,
                    &$storedAttachments
                ) {
                    $storedAttachments =
                        $this->storeAttachments(
                            $request->file(
                                'attachments',
                                []
                            )
                        );

                    Procurement::create([
                        'faculty_id' =>
                            $facultyId,

                        'requested_by' =>
                            $user->id,

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

                        'subject' =>
                            $this->resolveSubject(
                                $validated['subject'] ?? null,
                                $validated['type'],
                                $validated['item_name']
                            ),

                        'attachments' =>
                            $storedAttachments ?: null,

                        'requester_signature' =>
                            $signaturePath,

                        'requested_at' =>
                            now(),

                        'status' =>
                            Procurement::STATUS_PENDING,
                    ]);
                }
            );
        } catch (\Throwable $e) {
            $this->deleteSignatureFile(
                $signaturePath
            );

            $this->deleteAttachmentFiles(
                $storedAttachments
            );

            throw $e;
        }

        return back()->with(
            'toast',
            [
                'type' => 'success',
                'message' =>
                    'Pengajuan pengadaan berhasil dibuat.',
            ]
        );
    }

    public function show(
        Request $request,
        Procurement $procurement
    ): Response {
        Gate::authorize(
            'view',
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
                'procurement' =>
                    $this->transformProcurement(
                        $procurement
                    ),
            ]
        );
    }

    public function update(
        Request $request,
        Procurement $procurement
    ): RedirectResponse {
        Gate::authorize(
            'update',
            $procurement
        );

        $validated = $request->validate([
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

            'subject' => [
                'nullable',
                'string',
                'max:255',
            ],

            'requester_signature' => [
                'nullable',
                'string',
                'max:1500000',
            ],

            'attachments' => [
                'nullable',
                'array',
                'max:' . self::MAX_ATTACHMENTS,
            ],

            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:' . self::MAX_ATTACHMENT_KB,
            ],

            'remove_attachments' => [
                'nullable',
                'array',
            ],

            'remove_attachments.*' => [
                'string',
            ],
        ]);

        $newAttachments = [];
        $attachmentsToDelete = [];

        /*
         * Ambil nilai RAW database.
         *
         * Penting:
         * jangan menggunakan $procurement->requester_signature
         * karena accessor model dapat mengubahnya menjadi
         * Markdown/URL presentation.
         */
        $oldSignaturePath =
            $procurement->getRawOriginal(
                'requester_signature'
            );

        $signaturePath = null;

        try {
            DB::transaction(
                function () use (
                    $procurement,
                    $validated,
                    $request,
                    &$newAttachments,
                    &$attachmentsToDelete,
                    &$signaturePath,
                    $oldSignaturePath
                ) {
                    $lockedProcurement =
                        Procurement::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $procurement->id
                            );

                    if (
                        !$lockedProcurement->isPending()
                    ) {
                        throw ValidationException::withMessages([
                            'procurement' =>
                                'Pengajuan ini sudah diproses dan tidak dapat diubah.',
                        ]);
                    }

                    $this->ensureRoomBelongsToFaculty(
                        (int) $validated['room_id'],
                        (int) $lockedProcurement->faculty_id
                    );

                    $existingAttachments =
                        is_array(
                            $lockedProcurement->attachments
                        )
                            ? $lockedProcurement->attachments
                            : [];

                    $removePaths =
                        $validated['remove_attachments']
                            ?? [];

                    $retainedAttachments =
                        array_values(
                            array_filter(
                                $existingAttachments,
                                fn ($attachment) =>
                                    !in_array(
                                        $attachment['path']
                                            ?? null,
                                        $removePaths,
                                        true
                                    )
                            )
                        );

                    $attachmentsToDelete =
                        array_values(
                            array_filter(
                                $existingAttachments,
                                fn ($attachment) =>
                                    in_array(
                                        $attachment['path']
                                            ?? null,
                                        $removePaths,
                                        true
                                    )
                            )
                        );

                    $files =
                        $request->file(
                            'attachments',
                            []
                        );

                    $totalAfterUpdate =
                        count(
                            $retainedAttachments
                        ) +
                        count($files);

                    if (
                        $totalAfterUpdate >
                        self::MAX_ATTACHMENTS
                    ) {
                        throw ValidationException::withMessages([
                            'attachments' =>
                                'Maksimal ' .
                                self::MAX_ATTACHMENTS .
                                ' lampiran per pengajuan.',
                        ]);
                    }

                    $newAttachments =
                        $this->storeAttachments(
                            $files
                        );

                    $signaturePath =
                        $this->resolveUpdateSignature(
                            $validated['requester_signature']
                                ?? null,
                            $oldSignaturePath,
                            'requester_signature'
                        );

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

                        'subject' =>
                            $this->resolveSubject(
                                $validated['subject'] ?? null,
                                $validated['type'],
                                $validated['item_name']
                            ),

                        'attachments' =>
                            array_merge(
                                $retainedAttachments,
                                $newAttachments
                            ) ?: null,

                        'requester_signature' =>
                            $signaturePath,
                    ]);
                }
            );
        } catch (\Throwable $e) {
            if (
                $signaturePath !== null &&
                !$this->sameSignaturePath(
                    $oldSignaturePath,
                    $signaturePath
                )
            ) {
                $this->deleteSignatureFile(
                    $signaturePath
                );
            }

            $this->deleteAttachmentFiles(
                $newAttachments
            );

            throw $e;
        }

        if (
            !$this->sameSignaturePath(
                $oldSignaturePath,
                $signaturePath
            )
        ) {
            $this->deleteSignatureFile(
                $oldSignaturePath
            );
        }

        $this->deleteAttachmentFiles(
            $attachmentsToDelete
        );

        return back()->with(
            'toast',
            [
                'type' => 'success',
                'message' =>
                    'Pengajuan pengadaan berhasil diperbarui.',
            ]
        );
    }

    public function destroy(
        Request $request,
        Procurement $procurement
    ): RedirectResponse {
        Gate::authorize(
            'delete',
            $procurement
        );

        $signaturePath = null;
        $attachments = [];

        DB::transaction(
            function () use (
                $procurement,
                &$signaturePath,
                &$attachments
            ) {
                $lockedProcurement =
                    Procurement::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $procurement->id
                        );

                if (
                    !$lockedProcurement->isPending()
                ) {
                    throw ValidationException::withMessages([
                        'procurement' =>
                            'Pengajuan ini sudah diproses dan tidak dapat dihapus.',
                    ]);
                }

                $signaturePath =
                    $lockedProcurement->getRawOriginal(
                        'requester_signature'
                    );

                $attachments =
                    is_array(
                        $lockedProcurement->attachments
                    )
                        ? $lockedProcurement->attachments
                        : [];

                $lockedProcurement->delete();
            }
        );

        $this->deleteSignatureFile(
            $signaturePath
        );

        $this->deleteAttachmentFiles(
            $attachments
        );

        return back()->with(
            'toast',
            [
                'type' => 'success',
                'message' =>
                    'Pengajuan pengadaan berhasil dihapus.',
            ]
        );
    }

    public function approve(
        Request $request,
        Procurement $procurement
    ): RedirectResponse {
        Gate::authorize(
            'process',
            $procurement
        );

        $user = $request->user();

        $this->ensureSuperAdmin(
            $user,
            'Anda tidak memiliki izin untuk menyetujui pengadaan.'
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

        $signaturePath =
            $this->resolveNewSignature(
                $validated['approver_signature'] ?? null,
                'approver_signature'
            );

        try {
            DB::transaction(
                function () use (
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

                    if (
                        !$lockedProcurement->isPending()
                    ) {
                        throw ValidationException::withMessages([
                            'procurement' =>
                                'Pengajuan ini sudah diproses sebelumnya.',
                        ]);
                    }

                    Faculty::query()
                        ->whereKey(
                            $lockedProcurement->faculty_id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                    $documentNumber =
                        $this->generateDocumentNumber(
                            (int)
                            $lockedProcurement->faculty_id
                        );

                    $lockedProcurement->update([
                        'status' =>
                            Procurement::STATUS_APPROVED,

                        'document_number' =>
                            $documentNumber,

                        'processed_by' =>
                            $user->id,

                        'approver_signature' =>
                            $signaturePath,

                        'processed_at' =>
                            now(),

                        'admin_note' =>
                            isset(
                                $validated['admin_note']
                            )
                                ? trim(
                                    $validated['admin_note']
                                )
                                : null,
                    ]);
                }
            );
        } catch (\Throwable $e) {
            $this->deleteSignatureFile(
                $signaturePath
            );

            throw $e;
        }

        return back()->with(
            'toast',
            [
                'type' => 'success',
                'message' =>
                    'Pengajuan pengadaan berhasil disetujui.',
            ]
        );
    }

    public function reject(
        Request $request,
        Procurement $procurement
    ): RedirectResponse {
        Gate::authorize(
            'process',
            $procurement
        );

        $user = $request->user();

        $this->ensureSuperAdmin(
            $user,
            'Anda tidak memiliki izin untuk menolak pengadaan.'
        );

        $validated = $request->validate([
            'approver_signature' => [
                'nullable',
                'string',
                'max:1500000',
            ],

            'admin_note' => [
                'required',
                'string',
                'min:5',
                'max:5000',
            ],
        ]);

        $signaturePath =
            $this->resolveNewSignature(
                $validated['approver_signature'] ?? null,
                'approver_signature'
            );

        try {
            DB::transaction(
                function () use (
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

                    if (
                        !$lockedProcurement->isPending()
                    ) {
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

                        'document_number' =>
                            null,
                    ]);
                }
            );
        } catch (\Throwable $e) {
            $this->deleteSignatureFile(
                $signaturePath
            );

            throw $e;
        }

        return back()->with(
            'toast',
            [
                'type' => 'success',
                'message' =>
                    'Pengajuan pengadaan berhasil ditolak.',
            ]
        );
    }

    public function complete(
        Request $request,
        Procurement $procurement
    ): RedirectResponse {
        Gate::authorize(
            'process',
            $procurement
        );

        $user = $request->user();

        $this->ensureSuperAdmin(
            $user,
            'Anda tidak memiliki izin untuk menyelesaikan pengadaan.'
        );

        DB::transaction(
            function () use ($procurement) {
                $lockedProcurement =
                    Procurement::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $procurement->id
                        );

                if (
                    !$lockedProcurement->isApproved()
                ) {
                    throw ValidationException::withMessages([
                        'procurement' =>
                            'Hanya pengadaan yang sudah disetujui yang dapat diselesaikan.',
                    ]);
                }

                $lockedProcurement->update([
                    'status' =>
                        Procurement::STATUS_COMPLETED,
                ]);
            }
        );

        return back()->with(
            'toast',
            [
                'type' => 'success',
                'message' =>
                    'Pengadaan berhasil ditandai sebagai selesai.',
            ]
        );
    }

    public function print(
        Request $request,
        Procurement $procurement
    ): Response {
        Gate::authorize(
            'view',
            $procurement
        );

        if (
            !in_array(
                $procurement->status,
                [
                    Procurement::STATUS_APPROVED,
                    Procurement::STATUS_COMPLETED,
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'procurement' =>
                    'Dokumen hanya dapat dicetak setelah pengajuan disetujui.',
            ]);
        }

        if (
            empty(
                $procurement->document_number
            )
        ) {
            throw ValidationException::withMessages([
                'procurement' =>
                    'Nomor dokumen belum tersedia.',
            ]);
        }

        $procurement->load([
            'faculty',
            'requester:id,name',
            'room:id,faculty_id,code,name,building,floor',
            'processor:id,name',
        ]);

        return Inertia::render(
            'Admin/Procurements/Print',
            [
                'procurement' =>
                    $this->transformProcurement(
                        $procurement
                    ),
            ]
        );
    }

    private function transformProcurement(
        Procurement $procurement
    ): array {
        $data = $procurement->toArray();

        $disk = Storage::disk('public');

        $attachments =
            is_array(
                $procurement->attachments
            )
                ? $procurement->attachments
                : [];

        $data['attachments'] =
            collect($attachments)
                ->filter(
                    fn ($attachment) =>
                        is_array($attachment) &&
                        !empty(
                            $attachment['path']
                        )
                )
                ->map(
                    fn ($attachment) => [
                        'path' =>
                            $attachment['path'],

                        'name' =>
                            $attachment['name']
                                ??
                            basename(
                                $attachment['path']
                            ),

                        'url' =>
                            $disk->url(
                                $attachment['path']
                            ),
                    ]
                )
                ->values()
                ->all();

        /*
         * PENTING:
         *
         * Jangan menggunakan:
         *
         * $procurement->requester_signature
         *
         * karena accessor model dapat mengubah
         * nilai database menjadi Markdown.
         *
         * Gunakan raw database value.
         */
        $requesterSignature =
            $procurement->getRawOriginal(
                'requester_signature'
            );

        $approverSignature =
            $procurement->getRawOriginal(
                'approver_signature'
            );

        $data['requester_signature'] =
            $this->signatureUrl(
                $requesterSignature
            );

        $data['approver_signature'] =
            $this->signatureUrl(
                $approverSignature
            );

        return $data;
    }

    private function resolveSubject(
        ?string $subject,
        string $type,
        string $itemName
    ): string {
        $subject = trim(
            (string) $subject
        );

        if ($subject !== '') {
            return $subject;
        }

        $itemName = trim($itemName);

        return $type === Procurement::TYPE_NEW_ITEM
            ? "Permohonan Pengadaan {$itemName}"
            : "Permohonan Penggantian {$itemName}";
    }

    private function generateDocumentNumber(
        int $facultyId
    ): string {
        $faculty =
            Faculty::query()
                ->select(
                    'id',
                    'code'
                )
                ->findOrFail(
                    $facultyId
                );

        $year = now()->year;

        $sequence =
            Procurement::query()
                ->where(
                    'faculty_id',
                    $facultyId
                )
                ->whereYear(
                    'processed_at',
                    $year
                )
                ->whereNotNull(
                    'document_number'
                )
                ->count() + 1;

        $sequencePadded =
            str_pad(
                (string) $sequence,
                3,
                '0',
                STR_PAD_LEFT
            );

        $romanMonth =
            $this->toRoman(
                (int) now()->month
            );

        return
            "{$sequencePadded}/UNIVAL/{$faculty->code}/"
            . self::DOCUMENT_TYPE_CODE
            . "/{$romanMonth}/{$year}";
    }

    private function toRoman(
        int $month
    ): string {
        $map = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];

        return $map[$month]
            ?? (string) $month;
    }

    private function storeAttachments(
        array $files
    ): array {
        $stored = [];

        try {
            foreach ($files as $file) {
                if (!$file) {
                    continue;
                }

                /*
                 * Extension berasal dari file yang telah
                 * divalidasi Laravel, bukan dari nama file
                 * yang dikirim client.
                 */
                $extension =
                    strtolower(
                        $file->extension()
                    );

                $filename =
                    Str::uuid()->toString()
                    . '.'
                    . $extension;

                $path =
                    $file->storeAs(
                        'procurement-attachments',
                        $filename,
                        'public'
                    );

                if (
                    !is_string($path) ||
                    $path === ''
                ) {
                    throw ValidationException::withMessages([
                        'attachments' =>
                            'Salah satu lampiran gagal disimpan.',
                    ]);
                }

                $stored[] = [
                    'path' =>
                        $path,

                    'name' =>
                        $file->getClientOriginalName(),
                ];
            }
        } catch (\Throwable $e) {
            $this->deleteAttachmentFiles(
                $stored
            );

            throw $e;
        }

        return $stored;
    }

    private function deleteAttachmentFiles(
        array $attachments
    ): void {
        $disk = Storage::disk('public');

        foreach ($attachments as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }

            $path =
                $attachment['path']
                    ?? null;

            if (
                !is_string($path) ||
                $path === ''
            ) {
                continue;
            }

            if (
                !str_starts_with(
                    $path,
                    'procurement-attachments/'
                ) ||
                str_contains(
                    $path,
                    '..'
                )
            ) {
                continue;
            }

            if (
                $disk->exists($path)
            ) {
                $disk->delete($path);
            }
        }
    }

    private function ensureRoomBelongsToFaculty(
        int $roomId,
        int $facultyId
    ): void {
        $exists =
            Room::query()
                ->whereKey($roomId)
                ->where(
                    'faculty_id',
                    $facultyId
                )
                ->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'room_id' =>
                    'Ruangan tidak berada dalam fakultas yang dipilih.',
            ]);
        }
    }

    private function resolveFacultyId(
        $user,
        ?int $requestedFacultyId = null
    ): int {
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

            $exists =
                Faculty::query()
                    ->whereKey(
                        $requestedFacultyId
                    )
                    ->exists();

            if (!$exists) {
                throw ValidationException::withMessages([
                    'faculty_id' =>
                        'Fakultas yang dipilih tidak valid.',
                ]);
            }

            return $requestedFacultyId;
        }

        if ($this->isFacultyAdmin($user)) {
            $this->ensureUserHasFaculty(
                $user
            );

            return (int) $user->faculty_id;
        }

        throw ValidationException::withMessages([
            'faculty_id' =>
                'Anda tidak memiliki izin untuk membuat pengajuan pengadaan.',
        ]);
    }

    private function getAvailableRooms(
        $user
    ) {
        $query =
            Room::query()
                ->select(
                    'id',
                    'faculty_id',
                    'code',
                    'name',
                    'building',
                    'floor'
                )
                ->orderBy('name');

        if ($this->isFacultyAdmin($user)) {
            $this->ensureUserHasFaculty(
                $user
            );

            $query->where(
                'faculty_id',
                $user->faculty_id
            );
        }

        return $query->get();
    }

    private function isSuperAdmin(
        $user
    ): bool {
        return $user?->role === 'super_admin';
    }

    private function isFacultyAdmin(
        $user
    ): bool {
        return $user?->role === 'admin_fakultas';
    }

    private function isSdm(
        $user
    ): bool {
        return $user?->role === 'sdm';
    }

    private function ensureSuperAdmin(
        $user,
        string $message
    ): void {
        if (!$this->isSuperAdmin($user)) {
            abort(
                403,
                $message
            );
        }
    }

    private function ensureUserHasFaculty(
        $user
    ): void {
        if (
            empty(
                $user->faculty_id
            )
        ) {
            abort(
                403,
                'Akun Anda belum memiliki fakultas.'
            );
        }
    }

    private function allowedStatuses(): array
    {
        return [
            Procurement::STATUS_PENDING,
            Procurement::STATUS_APPROVED,
            Procurement::STATUS_REJECTED,
            Procurement::STATUS_COMPLETED,
        ];
    }

    private function allowedTypes(): array
    {
        return [
            Procurement::TYPE_REPLACEMENT,
            Procurement::TYPE_NEW_ITEM,
        ];
    }

    private function canViewAllFaculties(
        $user
    ): bool {
        return
            $this->isSuperAdmin($user) ||
            $this->isSdm($user);
    }

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
            !str_starts_with(
                trim($signature),
                'data:image/png;base64,'
            )
        ) {
            throw ValidationException::withMessages([
                $field =>
                    'Tanda tangan harus berupa gambar PNG yang valid.',
            ]);
        }

        return $this->storeSignatureImage(
            trim($signature),
            $field
        );
    }

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

        $signature = trim($signature);

        /*
         * Frontend bisa mengirim kembali URL signature
         * hasil transform controller.
         */
        if (
            $this->sameSignaturePath(
                $oldSignaturePath,
                $signature
            )
        ) {
            return $oldSignaturePath;
        }

        return $this->resolveNewSignature(
            $signature,
            $field
        );
    }

    private function sameSignaturePath(
        ?string $first,
        ?string $second
    ): bool {
        if (
            empty($first) ||
            empty($second)
        ) {
            return
                empty($first) &&
                empty($second);
        }

        $firstPath =
            $this->normalizeSignaturePath(
                $first
            );

        $secondPath =
            $this->normalizeSignaturePath(
                $second
            );

        if (
            $firstPath === null ||
            $secondPath === null
        ) {
            return false;
        }

        return hash_equals(
            $firstPath,
            $secondPath
        );
    }

    private function normalizeSignaturePath(
        string $path
    ): ?string {
        $path = trim($path);

        if ($path === '') {
            return null;
        }

        /*
         * Ambil URL dari Markdown:
         *
         * [Tanda tangan](https://domain/storage/signatures/x.png)
         */
        if (
            preg_match(
                '/\]\((https?:\/\/[^)\s]+)\)/i',
                $path,
                $matches
            )
        ) {
            $path = $matches[1];
        }

        /*
         * Data URL tidak pernah dianggap sebagai
         * existing stored signature.
         */
        if (
            str_starts_with(
                $path,
                'data:image/'
            )
        ) {
            return null;
        }

        /*
         * URL absolut.
         */
        if (
            str_starts_with(
                $path,
                'http://'
            ) ||
            str_starts_with(
                $path,
                'https://'
            )
        ) {
            $parsedPath =
                parse_url(
                    $path,
                    PHP_URL_PATH
                );

            if (
                !is_string($parsedPath) ||
                $parsedPath === ''
            ) {
                return null;
            }

            $path = $parsedPath;
        }

        /*
         * /storage/signatures/...
         */
        $storageMarker =
            '/storage/';

        $position =
            strpos(
                $path,
                $storageMarker
            );

        if (
            $position !== false
        ) {
            $path =
                substr(
                    $path,
                    $position +
                        strlen(
                            $storageMarker
                        )
                );
        }

        /*
         * Relative path normal.
         */
        $path =
            str_replace(
                '\\',
                '/',
                $path
            );

        $path =
            ltrim(
                $path,
                '/'
            );

        /*
         * Hilangkan query string jika ada.
         */
        $path =
            explode(
                '?',
                $path,
                2
            )[0];

        if (
            $path === '' ||
            str_contains(
                $path,
                '..'
            )
        ) {
            return null;
        }

        if (
            !str_starts_with(
                $path,
                'signatures/'
            )
        ) {
            return null;
        }

        if (
            preg_match(
                '#(^|/)\./#',
                $path
            )
        ) {
            return null;
        }

        return $path;
    }

    private function sanitizeSignaturePath(
        string $path
    ): ?string {
        $path =
            str_replace(
                '\\',
                '/',
                $path
            );

        $path =
            ltrim(
                $path,
                '/'
            );

        if (
            $path === '' ||
            str_contains(
                $path,
                '..'
            )
        ) {
            return null;
        }

        if (
            !str_starts_with(
                $path,
                'signatures/'
            )
        ) {
            return null;
        }

        return $path;
    }

    private function storeSignatureImage(
        string $dataUrl,
        string $field
    ): string {
        if (
            !preg_match(
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

        $binary =
            base64_decode(
                $matches['data'],
                true
            );

        if (
            $binary === false
        ) {
            throw ValidationException::withMessages([
                $field =>
                    'Data tanda tangan tidak dapat dibaca.',
            ]);
        }

        if (
            strlen($binary) >
            self::MAX_SIGNATURE_BYTES
        ) {
            throw ValidationException::withMessages([
                $field =>
                    'Ukuran tanda tangan terlalu besar (maksimal 1MB).',
            ]);
        }

        $imageInfo =
            @getimagesizefromstring(
                $binary
            );

        if (
            $imageInfo === false ||
            !isset(
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

        if (
            $imageInfo['mime'] !==
            'image/png'
        ) {
            throw ValidationException::withMessages([
                $field =>
                    'Tanda tangan harus menggunakan format PNG.',
            ]);
        }

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

        $image =
            @imagecreatefromstring(
                $binary
            );

        if (
            $image === false
        ) {
            throw ValidationException::withMessages([
                $field =>
                    'File tanda tangan bukan PNG yang valid.',
            ]);
        }

        imagedestroy(
            $image
        );

        $path =
            'signatures/'
            . Str::uuid()->toString()
            . '.png';

        $disk =
            Storage::disk('public');

        $stored =
            $disk->put(
                $path,
                $binary
            );

        if (!$stored) {
            throw ValidationException::withMessages([
                $field =>
                    'Tanda tangan gagal disimpan.',
            ]);
        }

        return $path;
    }

    private function signatureUrl(
        ?string $path
    ): ?string {
        if (
            empty($path)
        ) {
            return null;
        }

        $path = trim($path);

        if ($path === '') {
            return null;
        }

        /*
         * Support data lama yang tersimpan sebagai Markdown.
         */
        if (
            preg_match(
                '/\]\((https?:\/\/[^)\s]+)\)/i',
                $path,
                $matches
            )
        ) {
            $path = $matches[1];
        }

        /*
         * Jika sudah URL absolut dan bukan Markdown,
         * gunakan langsung.
         */
        if (
            str_starts_with(
                $path,
                'http://'
            ) ||
            str_starts_with(
                $path,
                'https://'
            )
        ) {
            return $path;
        }

        /*
         * Jika /storage/signatures/...
         */
        if (
            str_starts_with(
                $path,
                '/storage/'
            )
        ) {
            return url($path);
        }

        /*
         * Pastikan path yang digunakan hanya signature
         * milik aplikasi.
         */
        $relativePath =
            $this->normalizeSignaturePath(
                $path
            );

        if ($relativePath === null) {
            return null;
        }

        return Storage::disk('public')
            ->url($relativePath);
    }

    private function deleteSignatureFile(
        ?string $urlOrPath
    ): void {
        if (
            empty($urlOrPath)
        ) {
            return;
        }

        $relativePath =
            $this->normalizeSignaturePath(
                $urlOrPath
            );

        if (
            empty($relativePath)
        ) {
            return;
        }

        $disk =
            Storage::disk('public');

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
