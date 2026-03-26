<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditService;
use App\Services\FileUploadService;
use App\Services\UserIdGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController
{
    public function __construct(
        private readonly AuditService      $audit,
        private readonly FileUploadService $fileUpload,
    ) {}

    // =========================================================================
    // POST /api/login
    // Public — no token required
    // =========================================================================
    public function login(LoginRequest $request): JsonResponse
    {
        // 1. Find user by email
        $user = User::with('roles')->where('email', $request->email)->first();

        // 2. Verify user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Invalid email or password.', 401);
        }

        // 3. Block inactive accounts — tell them why, not just 401
        if (!$user->is_active) {
            return $this->error(
                'Your account is deactivated. Please contact the administrator.',
                403
            );
        }

        // 4. Revoke any existing tokens for clean single-session login
        $user->tokens()->delete();

        // 5. Issue a fresh Sanctum token
        $token = $user->createToken('ahms-api')->plainTextToken;

        // 6. Audit trail
        $this->audit->log(
            event:       'auth.login',
            description: "Login: {$user->email} | Role: {$user->getRoleNames()->implode(', ')}",
            userId:      $user->id,
            properties:  ['ip' => $request->ip(), 'user_agent' => $request->userAgent()],
        );

        // 7. Return token + full user payload
        return $this->success(
            data:    $this->buildAuthPayload($user, $token),
            message: 'Login successful.',
        );
    }

    // =========================================================================
    // POST /api/logout
    // Protected — requires valid token
    // =========================================================================
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Revoke only the token used for this request
        $user->currentAccessToken()->delete();

        $this->audit->log(
            event:       'auth.logout',
            description: "Logout: {$user->email}",
            userId:      $user->id,
        );

        return $this->success(null, 'Logged out successfully.');
    }

    // =========================================================================
    // GET /api/me
    // Protected — returns full authenticated user with roles & permissions
    // =========================================================================
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->load('roles');

        return $this->success(
            data:    new UserResource($user),
            message: 'Authenticated user retrieved.',
        );
    }

    // =========================================================================
    // POST /api/register
    // Protected — requires users.create permission (admin/super_admin only)
    // =========================================================================
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),   // Explicit hash — no cast double-hashing
            'phone'       => $request->phone,
            'gender'      => $request->gender,
            'employee_id' => UserIdGenerator::next(),
            'is_active'   => true,
        ]);

        // Assign the requested role via Spatie
        $user->assignRole($request->role);
        $user->load('roles');

        $this->audit->log(
            event:       'user.created',
            description: "New user registered: {$user->email} | Role: {$request->role}",
            userId:      $request->user()->id,
            properties:  ['new_user_id' => $user->id, 'role' => $request->role],
        );

        return $this->created(
            data:    new UserResource($user),
            message: 'User registered successfully.',
        );
    }

    // =========================================================================
    // PUT /api/profile
    // Protected — user updates their own profile
    // =========================================================================
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->only('name', 'email', 'phone', 'gender');

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->fileUpload->replace(
                $request->file('photo'),
                $user->photo,
                'avatars'
            );
        }

        $user->update($data);
        $user->load('roles');

        return $this->success(
            data:    new UserResource($user),
            message: 'Profile updated successfully.',
        );
    }

    // =========================================================================
    // PUT /api/change-password
    // Protected — user changes their own password
    // =========================================================================
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect.', 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        // Force re-login on ALL devices by revoking every token
        $user->tokens()->delete();

        $this->audit->log(
            event:       'auth.password_changed',
            description: "Password changed: {$user->email}",
            userId:      $user->id,
        );

        return $this->success(null, 'Password changed. Please log in again.');
    }

    // =========================================================================
    // PRIVATE — Build the auth payload returned on login
    // =========================================================================
    private function buildAuthPayload(User $user, string $token): array
    {
        return [
            'token'       => $token,
            'token_type'  => 'Bearer',
            'user'        => new UserResource($user),  // Contains roles + permissions via UserResource

            // Also expose at top level for quick frontend access
            // (no need to dig into user.roles or user.permissions)
            'roles'       => $user->getRoleNames(),                        // ["doctor"]
            'permissions' => $user->getAllPermissions()->pluck('name'),    // ["patients.view", ...]
        ];
    }
}
