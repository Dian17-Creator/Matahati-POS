<?php

namespace App\Http\Controllers\Api\Office;

use App\Http\Controllers\Controller;
use App\Models\MposOutlet;
use App\Models\MposUser;
use App\Models\MposUserSpv;
use App\Models\Muser;
use App\Traits\ResolvesOutletScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficeAuthController extends Controller
{
    use ResolvesOutletScope;

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'cemail' => ['required', 'email'],
            'cpassword' => ['required'],
        ]);

        if (!Auth::attempt(['cemail' => $request->cemail, 'password' => $request->cpassword])) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        /** @var Muser $user */
        $user = Auth::user();
        $mposUser = MposUser::where('nid_user', $user->nid)->first();
        $hasSpvAccess = MposUserSpv::where('nid_user', $user->nid)->exists();

        $hasPosRole = ($mposUser && ($mposUser->fowner || $mposUser->fspv || $mposUser->fcashier || $mposUser->fcaptain))
            || $hasSpvAccess;

        if (!$hasPosRole) {
            Auth::logout();

            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses ke Web Office (Hanya Owner, SPV, Cashier, atau Captain).',
            ], 403);
        }

        $token = $user->createToken('office-tablet')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $this->buildUserPayload($user, $mposUser, $hasSpvAccess),
            ],
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Muser|null $user */
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi.',
            ], 401);
        }

        Auth::setUser($user);
        $mposUser = MposUser::where('nid_user', $user->nid)->first();
        $hasSpvAccess = MposUserSpv::where('nid_user', $user->nid)->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->buildUserPayload($user, $mposUser, $hasSpvAccess),
            ],
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ], 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildUserPayload(Muser $user, ?MposUser $mposUser, bool $hasSpvAccess): array
    {
        $isOwner = (bool) ($mposUser?->fowner ?? false);
        $isSpv = $mposUser ? (bool) $mposUser->fspv : $hasSpvAccess;
        $isCashier = (bool) ($mposUser?->fcashier ?? false);
        $isCaptain = (bool) ($mposUser?->fcaptain ?? false);

        $roleName = match (true) {
            $isOwner => 'Owner',
            $isSpv => 'Supervisor',
            $isCaptain => 'Captain',
            $isCashier => 'Cashier',
            default => 'User',
        };

        $allowedOutletIds = $this->getUserAllowedOutletIds();
        $outlets = MposOutlet::whereIn('nid', $allowedOutletIds)
            ->orderBy('cname')
            ->get(['nid as id', 'cname as name'])
            ->map(fn ($o) => [
                'id' => (int) $o->id,
                'name' => $o->name,
            ])
            ->values();

        $defaultOutlet = $outlets->firstWhere('id', (int) ($mposUser->nid_outlet ?? 0)) ?? $outlets->first();

        return [
            'id' => (int) $user->nid,
            'name' => $user->cname,
            'email' => $user->cemail,
            'role_name' => $roleName,
            'role_owner' => $isOwner,
            'role_spv' => $isSpv,
            'role_cashier' => $isCashier,
            'role_captain' => $isCaptain,
            'is_super_admin' => $this->isSuperAdminUser(),
            'is_outlet_locked' => $this->isOutletLocked(),
            'default_outlet_id' => $defaultOutlet ? (int) $defaultOutlet['id'] : null,
            'default_outlet_name' => $defaultOutlet ? $defaultOutlet['name'] : null,
            'outlets' => $outlets,
        ];
    }
}
