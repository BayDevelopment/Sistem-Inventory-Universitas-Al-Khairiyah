<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $this->ensureCanManageUsers();

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => [
                'nullable',
                Rule::in(array_keys($this->roles())),
            ],
            'status' => [
                'nullable',
                Rule::in(array_keys($this->statuses())),
            ],
            'faculty_id' => [
                'nullable',
                'integer',
                'exists:faculties,id',
            ],
        ]);

        $search = trim($validated['search'] ?? '');

        $facultyFilter = $actor->role === 'admin_fakultas'
            ? $actor->faculty_id
            : ($validated['faculty_id'] ?? null);

        $baseQuery = User::query()
            ->when(
                $actor->role === 'admin_fakultas',
                fn ($query) => $query->where(
                    'faculty_id',
                    $actor->faculty_id
                )
            );

        $users = (clone $baseQuery)
            ->with([
                'faculty:id,name,code',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere(
                            'phone_number',
                            'like',
                            "%{$search}%"
                        );
                });
            })
            ->when(
                !empty($validated['role']),
                fn ($query) => $query->where(
                    'role',
                    $validated['role']
                )
            )
            ->when(
                !empty($validated['status']),
                fn ($query) => $query->where(
                    'status',
                    $validated['status']
                )
            )
            ->when(
                $facultyFilter,
                fn ($query) => $query->where(
                    'faculty_id',
                    $facultyFilter
                )
            )
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $stats = (clone $baseQuery)
            ->selectRaw(
                "COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended,
                COUNT(DISTINCT faculty_id) as faculties"
            )
            ->first();

        $faculties = Faculty::query()
            ->select([
                'id',
                'name',
                'code',
            ])
            ->when(
                $actor->role === 'admin_fakultas',
                fn ($query) => $query->where(
                    'id',
                    $actor->faculty_id
                )
            )
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'faculties' => $faculties,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'role' => $validated['role'] ?? '',
                'status' => $validated['status'] ?? '',
                'faculty_id' => $facultyFilter ?? '',
            ],
            'stats' => [
                'total' => (int) $stats->total,
                'active' => (int) $stats->active,
                'pending' => (int) $stats->pending,
                'suspended' => (int) $stats->suspended,
                'faculties' => (int) $stats->faculties,
            ],
        ]);
    }

    public function create(): Response
    {
        $actor = $this->ensureCanManageUsers();

        $faculties = Faculty::query()
            ->select([
                'id',
                'name',
                'code',
            ])
            ->when(
                $actor->role === 'admin_fakultas',
                fn ($query) => $query->where(
                    'id',
                    $actor->faculty_id
                )
            )
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Users/Create', [
            'faculties' => $faculties,
            'roles' => $this->assignableRoleOptions($actor),
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->ensureCanManageUsers();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'position' => [
                'nullable',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers(),
                'confirmed',
            ],
            'role' => [
                'required',
                Rule::in(array_keys($this->roles())),
            ],
            'nip' => [
                'nullable',
                'string',
                'max:255',
                'unique:users,nip',
            ],
            'faculty_id' => [
                'nullable',
                'integer',
                'exists:faculties,id',
            ],
            'department' => [
                'nullable',
                'string',
                'max:255',
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:30',
            ],
            'status' => [
                'required',
                Rule::in(array_keys($this->statuses())),
            ],
        ]);

        $this->assertCanAssignRole(
            $actor,
            $validated['role'],
            $validated['faculty_id'] ?? null
        );

        $user = DB::transaction(function () use ($validated) {
            return User::create([
                'name' => trim($validated['name']),
                'position' => $this->nullableTrim(
                    $validated['position'] ?? null
                ),
                'email' => strtolower(
                    trim($validated['email'])
                ),
                'password' => Hash::make(
                    $validated['password']
                ),
                'role' => $validated['role'],
                'nip' => $this->nullableTrim(
                    $validated['nip'] ?? null
                ),
                'faculty_id' => $validated['faculty_id'] ?? null,
                'department' => $this->nullableTrim(
                    $validated['department'] ?? null
                ),
                'phone_number' => $this->nullableTrim(
                    $validated['phone_number'] ?? null
                ),
                'status' => $validated['status'],
            ]);
        });

        $this->audit('user.created', $actor, [
            'target_user_id' => $user->id,
            'role' => $user->role,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Pengguna berhasil ditambahkan.',
            ]);
    }

    public function show(User $user): Response
    {
        $actor = $this->ensureCanManageUsers();

        $this->assertCanManageTargetUser(
            $actor,
            $user
        );

        $user->load([
            'faculty:id,name,code',
        ]);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user): Response
    {
        $actor = $this->ensureCanManageUsers();

        $this->assertCanManageTargetUser(
            $actor,
            $user
        );

        $user->load([
            'faculty:id,name,code',
        ]);

        $faculties = Faculty::query()
            ->select([
                'id',
                'name',
                'code',
            ])
            ->when(
                $actor->role === 'admin_fakultas',
                fn ($query) => $query->where(
                    'id',
                    $actor->faculty_id
                )
            )
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'faculties' => $faculties,
            'roles' => $this->assignableRoleOptions($actor),
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(
        Request $request,
        User $user
    ): RedirectResponse {
        $actor = $this->ensureCanManageUsers();

        $this->assertCanManageTargetUser(
            $actor,
            $user
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'position' => [
                'nullable',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($user->id),
            ],
            'password' => [
                'nullable',
                'string',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers(),
                'confirmed',
            ],
            'role' => [
                'required',
                Rule::in(array_keys($this->roles())),
            ],
            'nip' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'nip')
                    ->ignore($user->id),
            ],
            'faculty_id' => [
                'nullable',
                'integer',
                'exists:faculties,id',
            ],
            'department' => [
                'nullable',
                'string',
                'max:255',
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:30',
            ],
            'status' => [
                'required',
                Rule::in(array_keys($this->statuses())),
            ],
        ]);

        $this->assertCanAssignRole(
            $actor,
            $validated['role'],
            $validated['faculty_id'] ?? null
        );

        if (
            $user->id === Auth::id() &&
            $validated['status'] !== 'active'
        ) {
            throw ValidationException::withMessages([
                'status' => 'Anda tidak dapat menonaktifkan akun sendiri.',
            ]);
        }

        if (
            $user->role === 'super_admin' &&
            $validated['role'] !== 'super_admin' &&
            $this->isLastSuperAdmin($user)
        ) {
            throw ValidationException::withMessages([
                'role' => 'Pengguna super admin terakhir tidak dapat diubah perannya.',
            ]);
        }

        $newEmail = strtolower(
            trim($validated['email'])
        );

        DB::transaction(function () use (
            $validated,
            $user,
            $newEmail
        ) {
            $data = [
                'name' => trim($validated['name']),
                'position' => $this->nullableTrim(
                    $validated['position'] ?? null
                ),
                'email' => $newEmail,
                'role' => $validated['role'],
                'nip' => $this->nullableTrim(
                    $validated['nip'] ?? null
                ),
                'faculty_id' => $validated['faculty_id'] ?? null,
                'department' => $this->nullableTrim(
                    $validated['department'] ?? null
                ),
                'phone_number' => $this->nullableTrim(
                    $validated['phone_number'] ?? null
                ),
                'status' => $validated['status'],
            ];

            if (!empty($validated['password'])) {
                $data['password'] = Hash::make(
                    $validated['password']
                );

                $data['remember_token'] = null;
            }

            if ($user->email !== $newEmail) {
                $data['email_verified_at'] = null;
            }

            $user->update($data);
        });

        $this->audit('user.updated', $actor, [
            'target_user_id' => $user->id,
            'password_changed' => !empty(
                $validated['password']
            ),
            'email_changed' => $user->email !== $newEmail,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Pengguna berhasil diperbarui.',
            ]);
    }

    public function destroy(User $user): RedirectResponse
    {
        $actor = $this->ensureCanManageUsers();

        $this->assertCanManageTargetUser(
            $actor,
            $user
        );

        if ($user->id === Auth::id()) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Anda tidak dapat menghapus akun yang sedang digunakan.',
            ]);
        }

        if (
            $user->role === 'super_admin' &&
            $this->isLastSuperAdmin($user)
        ) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Pengguna super admin terakhir tidak dapat dihapus.',
            ]);
        }

        DB::transaction(function () use ($user) {
            $user->delete();
        });

        $this->audit('user.deleted', $actor, [
            'target_user_id' => $user->id,
            'target_role' => $user->role,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('toast', [
                'type' => 'success',
                'message' => "Pengguna {$user->name} berhasil dihapus.",
            ]);
    }

    public function sendVerificationEmail(
        User $user
    ): RedirectResponse {
        $actor = $this->ensureCanManageUsers();

        $this->assertCanManageTargetUser(
            $actor,
            $user
        );

        if ($user->hasVerifiedEmail()) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Email pengguna sudah terverifikasi.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        $this->audit('user.email_verification_sent', $actor, [
            'target_user_id' => $user->id,
            'email' => $user->email,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => "Email verifikasi berhasil dikirim ke {$user->email}.",
        ]);
    }

    private function roles(): array
    {
        return [
            'super_admin' => 'Super Admin',
            'admin_fakultas' => 'Admin Fakultas',
            'sdm' => 'SDM',
            'dosen' => 'Dosen',
            'mahasiswa' => 'Mahasiswa',
        ];
    }

    private function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'pending' => 'Pending',
            'suspended' => 'Suspended',
        ];
    }

    private function nullableTrim(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }

    private function manageableRoles(
        string $actingRole
    ): array {
        return match ($actingRole) {
            'sdm' => [
                'admin_fakultas',
                'dosen',
                'mahasiswa',
            ],
            'admin_fakultas' => [
                'dosen',
                'mahasiswa',
            ],
            default => [],
        };
    }

    private function assignableRoleOptions(
        User $actor
    ): array {
        if ($actor->role === 'super_admin') {
            return $this->roles();
        }

        $allowed = $this->manageableRoles(
            $actor->role
        );

        return array_intersect_key(
            $this->roles(),
            array_flip($allowed)
        );
    }

    private function ensureCanManageUsers(): User
    {
        $actor = Auth::user();

        if (!$actor) {
            abort(403);
        }

        if (!in_array(
            $actor->role,
            [
                'super_admin',
                'sdm',
                'admin_fakultas',
            ],
            true
        )) {
            abort(
                403,
                'Anda tidak memiliki akses ke manajemen pengguna.'
            );
        }

        if (
            $actor->role === 'admin_fakultas' &&
            !$actor->faculty_id
        ) {
            abort(
                403,
                'Akun Anda belum terhubung ke fakultas manapun.'
            );
        }

        return $actor;
    }

    private function assertCanManageTargetUser(
        User $actor,
        User $targetUser
    ): void {
        if ($actor->role === 'super_admin') {
            return;
        }

        if ($actor->id === $targetUser->id) {
            return;
        }

        $manageable = $this->manageableRoles(
            $actor->role
        );

        if (!in_array(
            $targetUser->role,
            $manageable,
            true
        )) {
            abort(403);
        }

        if (
            $actor->role === 'admin_fakultas' &&
            $targetUser->faculty_id !== $actor->faculty_id
        ) {
            abort(403);
        }
    }

    private function assertCanAssignRole(
        User $actor,
        string $role,
        ?int $facultyId
    ): void {
        if ($actor->role === 'super_admin') {
            return;
        }

        $manageable = $this->manageableRoles(
            $actor->role
        );

        if (!in_array(
            $role,
            $manageable,
            true
        )) {
            throw ValidationException::withMessages([
                'role' => 'Anda tidak memiliki izin untuk menetapkan peran ini.',
            ]);
        }

        if (
            $actor->role === 'admin_fakultas' &&
            $facultyId !== $actor->faculty_id
        ) {
            throw ValidationException::withMessages([
                'faculty_id' => 'Anda hanya dapat mengelola pengguna pada fakultas Anda sendiri.',
            ]);
        }
    }

    private function isLastSuperAdmin(
        User $user
    ): bool {
        return User::query()
            ->where('role', 'super_admin')
            ->where('id', '!=', $user->id)
            ->count() === 0;
    }

    private function audit(
        string $action,
        User $actor,
        array $context = []
    ): void {
        Log::info(
            $action,
            array_merge(
                [
                    'actor_id' => $actor->id,
                    'actor_role' => $actor->role,
                ],
                $context
            )
        );
    }
}
