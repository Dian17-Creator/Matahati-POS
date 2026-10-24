<?php

namespace App\Traits;

use App\Models\MposOutlet;
use App\Models\MposUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait ResolvesOutletScope
{
    /**
     * Get the authenticated user.
     */
    public function getAuthenticatedUser()
    {
        return Auth::user();
    }

    /**
     * Get the authenticated MposUser model if exists.
     */
    public function getAuthenticatedMposUser(): ?MposUser
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return null;
        }

        return MposUser::where('nid_user', $user->nid ?? $user->id)->first();
    }

    /**
     * Check if the authenticated user is an Owner (fowner = 1 in mpos_user).
     */
    public function isOwnerUser(): bool
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isOwner') && $user->isOwner()) {
            return true;
        }

        $mposUser = $this->getAuthenticatedMposUser();
        if ($mposUser && (int) $mposUser->fowner === 1) {
            return true;
        }

        return MposUser::where('nid_user', $user->nid ?? $user->id)
            ->where('fowner', 1)
            ->exists();
    }

    /**
     * Check if the authenticated user is an SPV (Supervisor).
     */
    public function isSpvUser(): bool
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isSpv') && $user->isSpv()) {
            return true;
        }

        $userId = $user->nid ?? $user->id;
        if (\App\Models\MposUserSpv::where('nid_user', $userId)->exists()) {
            return true;
        }

        $mposUser = $this->getAuthenticatedMposUser();
        if ($mposUser && (bool) $mposUser->fspv) {
            return true;
        }

        return MposUser::where('nid_user', $userId)->where('fspv', 1)->exists();
    }

    /**
     * Check if the authenticated user is a Captain (fcaptain = 1 in mpos_user).
     */
    public function isCaptainUser(): bool
    {
        $mposUser = $this->getAuthenticatedMposUser();
        return (bool) ($mposUser && (int) $mposUser->fcaptain === 1);
    }

    /**
     * Check if the authenticated user is a Cashier (fcashier = 1 in mpos_user).
     */
    public function isCashierUser(): bool
    {
        $mposUser = $this->getAuthenticatedMposUser();
        return (bool) ($mposUser && (int) $mposUser->fcashier === 1);
    }

    /**
     * Check if the authenticated user is a Super Admin, Admin, or Owner.
     */
    public function isSuperAdminUser(): bool
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return false;
        }

        // If user is explicitly defined as Captain, Cashier, or SPV in POS,
        // they are NOT Super Admin in POS, even if muser.fadmin or muser.fsuper is set in absensi.
        $mposUser = $this->getAuthenticatedMposUser();
        if ($mposUser) {
            if ((int) $mposUser->fowner === 1) {
                return true;
            }
            if ((int) $mposUser->fcaptain === 1 || (int) $mposUser->fcashier === 1 || (int) $mposUser->fspv === 1) {
                return false;
            }
        }

        if ($this->isSpvUser()) {
            return false;
        }

        return (bool) (
            (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) ||
            (!empty($user->fsuper) && (int)$user->fsuper === 1) ||
            (method_exists($user, 'isAdmin') && $user->isAdmin()) ||
            (!empty($user->fadmin) && (int)$user->fadmin === 1) ||
            $this->isOwnerUser()
        );
    }

    /**
     * Determine all outlet IDs that the authenticated user is allowed to access.
     * Super Admin has access to all outlets.
     * SPV has access to their assigned outlet(s) via mpos_user_spv and mpos_user.
     * Captain & Cashier are strictly locked to their single assigned outlet in mpos_user.
     * Non-superadmins only have access to their assigned outlet(s) via mpos_user or department.
     *
     * @return int[]
     */
    public function getUserAllowedOutletIds(): array
    {
        if ($this->isSuperAdminUser()) {
            return MposOutlet::pluck('nid')->map(fn($id) => (int)$id)->toArray();
        }

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [];
        }

        $mposUser = $this->getAuthenticatedMposUser();

        // 1. If user is Captain or Cashier (strictly assigned to 1 outlet in mpos_user)
        if ($mposUser && !$mposUser->fowner && !$mposUser->fspv && ($mposUser->fcaptain || $mposUser->fcashier)) {
            return !empty($mposUser->nid_outlet) ? [(int) $mposUser->nid_outlet] : [];
        }

        $outletIds = [];

        // 2. From mpos_user_spv (for SPV multi-outlet assignments)
        $spvOutlets = \App\Models\MposUserSpv::where('nid_user', $user->nid ?? $user->id)
            ->pluck('nid_outlet')
            ->filter()
            ->toArray();
        foreach ($spvOutlets as $id) {
            $outletIds[] = (int) $id;
        }

        // 3. From mpos_user
        $posOutlets = MposUser::where('nid_user', $user->nid ?? $user->id)
            ->pluck('nid_outlet')
            ->filter()
            ->toArray();
        foreach ($posOutlets as $id) {
            $outletIds[] = (int) $id;
        }

        // 4. From mpos_outlet where nid_dept matches user's department ONLY if no explicit POS assignment
        if (empty($outletIds) && !empty($user->niddept)) {
            $deptOutlets = MposOutlet::where('nid_dept', $user->niddept)
                ->pluck('nid')
                ->filter()
                ->toArray();
            foreach ($deptOutlets as $id) {
                $outletIds[] = (int) $id;
            }
        }

        return array_values(array_unique(array_filter($outletIds)));
    }

    /**
     * Check if the user is locked to their assigned outlet (cannot view all outlets).
     */
    public function isOutletLocked(): bool
    {
        if ($this->isSuperAdminUser()) {
            return false;
        }

        $mposUser = $this->getAuthenticatedMposUser();
        if ($mposUser && ((int) $mposUser->fcaptain === 1 || (int) $mposUser->fcashier === 1)) {
            return true;
        }

        if ($this->isSpvUser()) {
            return false;
        }

        return count($this->getUserAllowedOutletIds()) <= 1;
    }

    /**
     * Kept for backward compatibility with existing blade views.
     * Non-superadmins cannot see or switch to arbitrary/all outlets.
     */
    public function isCashierOnlyUser(): bool
    {
        if ($this->isSuperAdminUser()) {
            return false;
        }

        $mposUser = $this->getAuthenticatedMposUser();
        if ($mposUser && ((int) $mposUser->fcaptain === 1 || (int) $mposUser->fcashier === 1)) {
            return true;
        }

        if ($this->isSpvUser()) {
            return false;
        }

        return count($this->getUserAllowedOutletIds()) <= 1;
    }

    /**
     * Resolve the default outlet ID for the currently logged-in user.
     * Prioritizes:
     * 1. The outlet explicitly assigned to the user in mpos_user.
     * 2. The first outlet in mpos_user_spv.
     * 3. The outlet matching the user's department in mpos_outlet.
     * 4. The first allowed outlet ID.
     */
    public function resolveUserDefaultOutletId(): ?int
    {
        // 1. Check mpos_user for explicitly assigned outlet
        $mposUser = $this->getAuthenticatedMposUser();
        if ($mposUser && !empty($mposUser->nid_outlet)) {
            return (int) $mposUser->nid_outlet;
        }

        // 2. Check mpos_user_spv
        $user = $this->getAuthenticatedUser();
        if ($user) {
            $spvFirstOutlet = \App\Models\MposUserSpv::where('nid_user', $user->nid ?? $user->id)->value('nid_outlet');
            if ($spvFirstOutlet) {
                return (int) $spvFirstOutlet;
            }
        }

        // 3. Fallback to mpos_outlet matching user's department
        if ($user && !empty($user->niddept)) {
            $deptOutletId = MposOutlet::where('nid_dept', $user->niddept)->value('nid');
            if ($deptOutletId) {
                return (int) $deptOutletId;
            }
        }

        // 4. Fallback to first allowed outlet ID
        $allowed = $this->getUserAllowedOutletIds();
        return !empty($allowed) ? (int) $allowed[0] : null;
    }

    /**
     * Resolve the active outlet ID for a request:
     * - If parameter exists:
     *     - Non-empty value => specific outlet ID (validated against allowed outlets for non-superadmin).
     *     - Empty string ('') => null ("Semua Outlet").
     * - If parameter is omitted: default to null ("Semua Outlet") so all allowed data is shown by default.
     *   *NOTE*: For single-outlet locked users (e.g., Captain / Cashier), always return their assigned outlet ID!
     */
    public function resolveOutletIdFromRequest(Request $request, string $paramName = 'nid_outlet'): ?int
    {
        $allowedIds = $this->getUserAllowedOutletIds();

        // Check if request provided an explicit outlet parameter (check both nid_outlet and outlet_id)
        $inputVal = null;
        if ($request->has($paramName)) {
            $inputVal = $request->input($paramName);
        } elseif ($paramName === 'nid_outlet' && $request->has('outlet_id')) {
            $inputVal = $request->input('outlet_id');
        } elseif ($paramName === 'outlet_id' && $request->has('nid_outlet')) {
            $inputVal = $request->input('nid_outlet');
        }

        // If no explicit parameter in request URL, fallback to global active outlet stored in session
        if ($inputVal === null && session()->has('active_outlet_id')) {
            $sessionVal = session('active_outlet_id');
            if ($sessionVal !== null && $sessionVal !== '' && $sessionVal !== 'all') {
                $inputVal = $sessionVal;
            }
        }

        // Non-superadmin: strictly locked to allowed outlet(s)
        if (!$this->isSuperAdminUser()) {
            if (empty($allowedIds)) {
                return null;
            }

            // If user only has access to exactly 1 outlet (e.g. Captain, Cashier, single outlet user),
            // ALWAYS lock them to that outlet! Never return null ("Semua Outlet").
            if (count($allowedIds) === 1) {
                return (int) $allowedIds[0];
            }

            if ($inputVal !== null && $inputVal !== '') {
                $intVal = (int) $inputVal;
                if (in_array($intVal, $allowedIds, true)) {
                    return $intVal;
                }
            }

            // If empty string ("Semua Outlet") or omitted (default view) for multi-outlet users (like SPV):
            // return null so queries filter across all user's allowed outlets (whereIn)
            return null;
        }

        // Super Admin, Admin & Owner: full access to all outlets and "Semua Outlet"
        if ($inputVal !== null) {
            return ($inputVal !== '') ? (int) $inputVal : null;
        }

        // When no outlet parameter is specified and no active session, default to null ("Semua Outlet")
        return null;
    }

    /**
     * Get list of outlets available for the user.
     * Super Admin sees all outlets.
     * Non-superadmins see only their allowed outlet(s).
     */
    public function getAvailableOutlets()
    {
        if ($this->isSuperAdminUser()) {
            return MposOutlet::orderBy('cname')->get();
        }

        $allowedIds = $this->getUserAllowedOutletIds();
        if (empty($allowedIds)) {
            return MposOutlet::whereRaw('1 = 0')->get();
        }

        return MposOutlet::whereIn('nid', $allowedIds)->orderBy('cname')->get();
    }
}
