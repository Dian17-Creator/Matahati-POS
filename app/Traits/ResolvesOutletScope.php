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
     * Check if the user is strictly a cashier (not owner/captain).
     */
    public function isCashierOnlyUser(): bool
    {
        $mposUser = $this->getAuthenticatedMposUser();
        return (bool) ($mposUser && !$mposUser->fowner && !$mposUser->fcaptain && $mposUser->fcashier);
    }

    /**
     * Resolve the default outlet ID for the currently logged-in user.
     * Looks up:
     * 1. mpos_user.nid_outlet for the current user
     * 2. mpos_outlet.nid where nid_dept matches user's niddept
     */
    public function resolveUserDefaultOutletId(): ?int
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return null;
        }

        // 1. Check mpos_user
        $mposUser = $this->getAuthenticatedMposUser();
        if ($mposUser && !empty($mposUser->nid_outlet)) {
            return (int) $mposUser->nid_outlet;
        }

        // 2. Fallback to mpos_outlet where nid_dept == user.niddept
        if (!empty($user->niddept)) {
            $outletId = MposOutlet::where('nid_dept', $user->niddept)->value('nid');
            if ($outletId) {
                return (int) $outletId;
            }
        }

        return null;
    }

    /**
     * Resolve the active outlet ID for a request:
     * - If cashier only, always lock to their outlet.
     * - If request explicitly has the parameter:
     *     - If empty string (''), it means "Semua Outlet".
     *     - If non-empty, use that value.
     * - If request does NOT have the parameter (first visit), default to the logged-in user's outlet.
     */
    public function resolveOutletIdFromRequest(Request $request, string $paramName = 'nid_outlet'): ?int
    {
        $defaultOutletId = $this->resolveUserDefaultOutletId();

        if ($this->isCashierOnlyUser() && !empty($defaultOutletId)) {
            return $defaultOutletId;
        }

        if ($request->has($paramName)) {
            $val = $request->input($paramName);
            return ($val !== null && $val !== '') ? (int) $val : null;
        }

        // First visit / direct link without filter parameter: default to logged-in user's outlet
        return $defaultOutletId;
    }

    /**
     * Get list of outlets available for the user.
     * If cashier only, only return their outlet. Otherwise, return all outlets ordered by cname.
     */
    public function getAvailableOutlets()
    {
        if ($this->isCashierOnlyUser()) {
            $defaultOutletId = $this->resolveUserDefaultOutletId();
            if ($defaultOutletId) {
                return MposOutlet::where('nid', $defaultOutletId)->orderBy('cname')->get();
            }
        }

        return MposOutlet::orderBy('cname')->get();
    }
}
