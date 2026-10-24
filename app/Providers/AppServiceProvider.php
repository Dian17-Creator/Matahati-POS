<?php

namespace App\Providers;

use App\Models\MposOutlet;
use App\Models\MposUser;
use App\Models\MposUserSpv;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('layouts.app', function ($view) {
            if (!Auth::check()) {
                return;
            }

            try {
                $user = Auth::user();
                $isOwnerLevel = method_exists($user, 'isOwnerLevel') ? (bool) $user->isOwnerLevel() : false;

                $canSwitchOutlet = false;
                $availableOutlets = collect();

                // Check Super Admin / Owner privileges in POS context
                $isSuperAdmin = (bool) (
                    $isOwnerLevel ||
                    (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) ||
                    (!empty($user->fsuper) && (int) $user->fsuper === 1) ||
                    (method_exists($user, 'isAdmin') && $user->isAdmin()) ||
                    (!empty($user->fadmin) && (int) $user->fadmin === 1)
                );

                $mposUser = MposUser::where('nid_user', $user->nid ?? $user->id)->first();
                if ($mposUser && !$mposUser->fowner && ($mposUser->fcaptain || $mposUser->fcashier)) {
                    $isSuperAdmin = false;
                }

                if ($isSuperAdmin) {
                    $canSwitchOutlet = true;
                    $availableOutlets = MposOutlet::orderBy('cname')->get();
                } else {
                    $userId = $user->nid ?? $user->id;
                    $spvOutlets = MposUserSpv::where('nid_user', $userId)->pluck('nid_outlet')->filter()->toArray();
                    $posOutlets = MposUser::where('nid_user', $userId)->pluck('nid_outlet')->filter()->toArray();
                    $allowedIds = array_values(array_unique(array_merge($spvOutlets, $posOutlets)));

                    if (count($allowedIds) > 1) {
                        $canSwitchOutlet = true;
                        $availableOutlets = MposOutlet::whereIn('nid', $allowedIds)->orderBy('cname')->get();
                    } elseif (count($allowedIds) === 1) {
                        $availableOutlets = MposOutlet::whereIn('nid', $allowedIds)->get();
                    }
                }

                $activeOutletId = session('active_outlet_id');
                $activeOutletName = 'Semua Outlet';

                if (!empty($activeOutletId)) {
                    $matched = $availableOutlets->firstWhere('nid', $activeOutletId);
                    if ($matched) {
                        $activeOutletName = $matched->cname;
                    } else {
                        session()->forget('active_outlet_id');
                        $activeOutletId = null;
                        $activeOutletName = 'Semua Outlet';
                    }
                }

                // If user only has exactly 1 outlet (e.g. Cashier / Captain), lock display to that outlet
                if (!$canSwitchOutlet && $availableOutlets->isNotEmpty()) {
                    $activeOutletName = $availableOutlets->first()->cname;
                    $activeOutletId = $availableOutlets->first()->nid;
                }

                $view->with([
                    'headerCanSwitchOutlet' => $canSwitchOutlet,
                    'headerOutlets' => $availableOutlets,
                    'headerActiveOutletId' => $activeOutletId,
                    'headerActiveOutletName' => $activeOutletName,
                    'isOwnerLevelUser' => $isOwnerLevel,
                ]);
            } catch (\Throwable $e) {
                // Fail silently to avoid breaking pages on edge cases
                $view->with([
                    'headerCanSwitchOutlet' => false,
                    'headerOutlets' => collect(),
                    'headerActiveOutletId' => null,
                    'headerActiveOutletName' => 'Semua Outlet',
                    'isOwnerLevelUser' => false,
                ]);
            }
        });
    }
}
