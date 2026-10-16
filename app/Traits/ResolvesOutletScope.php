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
     * Check if the authenticated user is a Super Admin or Owner.
     */
    public function isSuperAdminUser(): bool
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return false;
        }

        return (bool) (
            (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) ||
            (!empty($user->fsuper) && (int)$user->fsuper === 1) ||
            $this->isOwnerUser()
        );
    }

    /**
     * Determine all outlet IDs that the authenticated user is allowed to access.
     * Super Admin has access to all outlets.
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

        $outletIds = [];

        // 1. From mpos_user
        $posOutlets = MposUser::where('nid_user', $user->nid ?? $user->id)
            ->pluck('nid_outlet')
            ->filter()
            ->toArray();
        foreach ($posOutlets as $id) {
            $outletIds[] = (int) $id;
        }

        // 2. From mpos_outlet where nid_dept matches user's department
        if (!empty($user->niddept)) {
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

        return count($this->getUserAllowedOutletIds()) <= 1;
    }

    /**
     * Kept for backward compatibility with existing blade views.
     * Non-superadmins cannot see or switch to arbitrary/all outlets.
     */
    public function isCashierOnlyUser(): bool
    {
        return !$this->isSuperAdminUser();
    }

    /**
     * Resolve the default outlet ID for the currently logged-in user.
     * Prioritizes:
     * 1. The outlet explicitly assigned to the user in mpos_user.
     * 2. The outlet matching the user's department in mpos_outlet.
     * 3. The first allowed outlet ID.
     */
    public function resolveUserDefaultOutletId(): ?int
    {
        // 1. Check mpos_user for explicitly assigned outlet
        $mposUser = $this->getAuthenticatedMposUser();
        if ($mposUser && !empty($mposUser->nid_outlet)) {
            return (int) $mposUser->nid_outlet;
        }

        // 2. Fallback to mpos_outlet matching user's department
        $user = $this->getAuthenticatedUser();
        if ($user && !empty($user->niddept)) {
            $deptOutletId = MposOutlet::where('nid_dept', $user->niddept)->value('nid');
            if ($deptOutletId) {
                return (int) $deptOutletId;
            }
        }

        // 3. Fallback to first allowed outlet ID
        $allowed = $this->getUserAllowedOutletIds();
        return !empty($allowed) ? (int) $allowed[0] : null;
    }

    /**
     * Resolve the active outlet ID for a request:
     * - If NOT Super Admin:
     *     - Strictly enforce that the user can ONLY view an outlet within their allowed outlets.
     *     - If user provided an outlet ID that they are allowed to see, return it.
     *     - Otherwise (including trying to view "Semua Outlet" or an unauthorized outlet),
     *       override and force to their default allowed outlet ID.
     * - If Super Admin:
     *     - If parameter exists:
     *         - Non-empty value => specific outlet ID.
     *         - Empty string ('') => null ("Semua Outlet").
     *     - If parameter is omitted: default to their assigned outlet or null.
     */
    public function resolveOutletIdFromRequest(Request $request, string $paramName = 'nid_outlet'): ?int
    {
        $allowedIds = $this->getUserAllowedOutletIds();
        $defaultOutletId = $this->resolveUserDefaultOutletId();

        // Non-superadmin: strictly locked to allowed outlet(s)
        if (!$this->isSuperAdminUser()) {
            if (empty($allowedIds)) {
                return null;
            }

            // Check if request provided an outlet parameter (check both nid_outlet and outlet_id)
            $inputVal = null;
            if ($request->has($paramName)) {
                $inputVal = $request->input($paramName);
            } elseif ($paramName === 'nid_outlet' && $request->has('outlet_id')) {
                $inputVal = $request->input('outlet_id');
            } elseif ($paramName === 'outlet_id' && $request->has('nid_outlet')) {
                $inputVal = $request->input('nid_outlet');
            }

            if ($inputVal !== null && $inputVal !== '') {
                $intVal = (int) $inputVal;
                if (in_array($intVal, $allowedIds, true)) {
                    return $intVal;
                }
            }

            // Fallback: force default allowed outlet
            return $defaultOutletId;
        }

        // Super Admin & Owner: full access to all outlets and "Semua Outlet"
        $inputVal = null;
        if ($request->has($paramName)) {
            $inputVal = $request->input($paramName);
        } elseif ($paramName === 'nid_outlet' && $request->has('outlet_id')) {
            $inputVal = $request->input('outlet_id');
        } elseif ($paramName === 'outlet_id' && $request->has('nid_outlet')) {
            $inputVal = $request->input('nid_outlet');
        }

        if ($inputVal !== null) {
            return ($inputVal !== '') ? (int) $inputVal : null;
        }

        // When no outlet parameter is specified, default to null ("Semua Outlet") so all data is shown
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
