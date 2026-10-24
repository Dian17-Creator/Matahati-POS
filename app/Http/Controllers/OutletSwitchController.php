<?php

namespace App\Http\Controllers;

use App\Models\MposOutlet;
use App\Traits\ResolvesOutletScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OutletSwitchController extends Controller
{
    use ResolvesOutletScope;

    /**
     * Switch global active outlet for current session.
     */
    public function switch(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $outletId = $request->input('outlet_id');

        // Check if clearing / selecting "Semua Outlet"
        if ($outletId === null || $outletId === '' || $outletId === 'all') {
            // Only users who can view all outlets are allowed to clear filter
            if ($this->isSuperAdminUser() || !$this->isOutletLocked()) {
                session()->forget('active_outlet_id');
            }
        } else {
            $outletId = (int) $outletId;
            $allowedIds = $this->getUserAllowedOutletIds();

            if ($this->isSuperAdminUser() || in_array($outletId, $allowedIds, true)) {
                $exists = MposOutlet::where('nid', $outletId)->exists();
                if ($exists) {
                    session(['active_outlet_id' => $outletId]);
                }
            }
        }

        // Redirect back cleanly, removing page-level query params that could conflict
        $referer = $request->headers->get('referer');
        if ($referer) {
            $parsed = parse_url($referer);
            if (!empty($parsed['query'])) {
                parse_str($parsed['query'], $queryParams);
                unset($queryParams['nid_outlet'], $queryParams['outlet_id'], $queryParams['page']);
                $newQuery = http_build_query($queryParams);
                $cleanUrl = ($parsed['scheme'] ?? 'http') . '://'
                    . ($parsed['host'] ?? 'localhost')
                    . (isset($parsed['port']) ? ':' . $parsed['port'] : '')
                    . ($parsed['path'] ?? '/')
                    . ($newQuery ? '?' . $newQuery : '');
                return redirect()->to($cleanUrl);
            }
            return redirect()->to($referer);
        }

        return redirect()->route('dashboard');
    }
}
