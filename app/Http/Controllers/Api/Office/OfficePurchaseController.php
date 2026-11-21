<?php

namespace App\Http\Controllers\Api\Office;

use App\Http\Controllers\Controller;
use App\Models\MposOutlet;
use App\Models\MposPurchaseD;
use App\Models\MposPurchaseH;
use App\Models\MposSupplier;
use App\Traits\ResolvesOutletScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficePurchaseController extends Controller
{
    use ResolvesOutletScope;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            $bearer = $request->bearerToken();
            if ($bearer && str_starts_with($bearer, 'logged_in_office_')) {
                $userId = (int) substr($bearer, strlen('logged_in_office_'));
                $user = \App\Models\Muser::find($userId);
            }
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi.',
            ], 401);
        }

        Auth::setUser($user);

        // 1. Tab Resolution
        $rawTab = strtolower((string) $request->input('tab', 'pending'));
        if ($rawTab === 'retur') {
            $rawTab = 'return';
        }
        $tab = in_array($rawTab, ['pending', 'received', 'return', 'cancelled'], true)
            ? $rawTab
            : 'pending';

        // 2. Filters
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $selectedOutletId = $this->resolveOutletIdFromRequest($request, 'nid_outlet');
        $allowedOutletIds = $this->getUserAllowedOutletIds();
        $isSuperAdmin = $this->isSuperAdminUser();
        $search = trim((string) $request->input('search', ''));

        // 3. Counts Query for Tab Badges
        $baseCountQuery = MposPurchaseH::onlyPurchases();
        if (!empty($selectedOutletId)) {
            $baseCountQuery->where('nid_outlet', $selectedOutletId);
        } elseif (!$isSuperAdmin) {
            $baseCountQuery->whereIn('nid_outlet', $allowedOutletIds);
        }

        $countPending = (clone $baseCountQuery)
            ->where('cstatus', '!=', MposPurchaseH::STATUS_CANCELLED)
            ->where(function ($q) {
                $q->where('cstatus', MposPurchaseH::STATUS_PENDING)
                  ->orWhere('cpayment_status', '!=', MposPurchaseH::PAYMENT_PAID)
                  ->orWhereNull('cpayment_status');
            })
            ->count();

        $countReceived = (clone $baseCountQuery)
            ->where('cstatus', MposPurchaseH::STATUS_RECEIVED)
            ->where('cpayment_status', MposPurchaseH::PAYMENT_PAID)
            ->count();

        $countCancelled = (clone $baseCountQuery)
            ->where('cstatus', MposPurchaseH::STATUS_CANCELLED)
            ->count();

        $countReturnQuery = MposPurchaseH::onlyReturns();
        if (!empty($selectedOutletId)) {
            $countReturnQuery->where('nid_outlet', $selectedOutletId);
        } elseif (!$isSuperAdmin) {
            $countReturnQuery->whereIn('nid_outlet', $allowedOutletIds);
        }
        $countReturn = $countReturnQuery->count();

        // 4. Main Query
        $query = MposPurchaseH::with(['supplier', 'outlet', 'user'])
            ->withCount('details');

        if (!empty($selectedOutletId)) {
            $query->where('nid_outlet', $selectedOutletId);
        } elseif (!$isSuperAdmin) {
            $query->whereIn('nid_outlet', $allowedOutletIds);
        }

        // Apply Tab Condition
        if ($tab === 'return') {
            $query->onlyReturns();
        } else {
            $query->onlyPurchases();
            if ($tab === 'pending') {
                $query->where('cstatus', '!=', MposPurchaseH::STATUS_CANCELLED)
                    ->where(function ($q) {
                        $q->where('cstatus', MposPurchaseH::STATUS_PENDING)
                          ->orWhere('cpayment_status', '!=', MposPurchaseH::PAYMENT_PAID)
                          ->orWhereNull('cpayment_status');
                    });
            } elseif ($tab === 'received') {
                $query->where('cstatus', MposPurchaseH::STATUS_RECEIVED)
                    ->where('cpayment_status', MposPurchaseH::PAYMENT_PAID);
            } elseif ($tab === 'cancelled') {
                $query->where('cstatus', MposPurchaseH::STATUS_CANCELLED);
            }
        }

        if (!empty($startDate)) {
            $query->where('dpurchase', '>=', Carbon::parse($startDate)->startOfDay());
        }
        if (!empty($endDate)) {
            $query->where('dpurchase', '<=', Carbon::parse($endDate)->endOfDay());
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('cno_purchase', 'like', "%{$search}%")
                  ->orWhere('cno_reference', 'like', "%{$search}%")
                  ->orWhere('cno_purchase_ref', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('cname', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $paginator = $query->orderBy('dpurchase', 'desc')
            ->orderBy('nid', 'desc')
            ->paginate($perPage);

        $items = collect($paginator->items())->map(function (MposPurchaseH $po) {
            $isPaid = strtoupper((string) $po->cpayment_status) === MposPurchaseH::PAYMENT_PAID;
            $paymentLabel = $isPaid ? 'Lunas' : 'Belum';

            $statusLabel = match (strtoupper((string) $po->cstatus)) {
                MposPurchaseH::STATUS_RECEIVED => 'Diterima',
                MposPurchaseH::STATUS_CANCELLED => 'Dibatalkan',
                MposPurchaseH::STATUS_POSTED => 'Telah Diproses',
                default => 'Proses',
            };

            return [
                'id' => (int) $po->nid,
                'cno_purchase' => $po->cno_purchase ?: ('PO-' . $po->nid),
                'cno_reference' => $po->cno_reference ?: '-',
                'dpurchase' => $po->dpurchase ? $po->dpurchase->format('Y-m-d H:i:s') : null,
                'dpurchase_formatted' => $po->dpurchase ? $po->dpurchase->format('d M Y') : '-',
                'supplier_name' => $po->supplier ? $po->supplier->cname : '-',
                'outlet_name' => $po->outlet ? $po->outlet->cname : '-',
                'total_amount' => (float) $po->ntotal_amount,
                'total_amount_formatted' => 'Rp ' . number_format((float) $po->ntotal_amount, 0, ',', '.'),
                'items_count' => (int) $po->details_count,
                'payment_status' => $po->cpayment_status ?: 'UNPAID',
                'payment_status_label' => $paymentLabel,
                'dpayment' => $po->dpayment ? $po->dpayment->format('Y-m-d H:i:s') : null,
                'dpayment_formatted' => $po->dpayment ? $po->dpayment->format('d M Y') : '-',
                'cstatus' => $po->cstatus ?: 'PENDING',
                'cstatus_label' => $statusLabel,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'tab' => $tab,
                'counts' => [
                    'pending' => $countPending,
                    'received' => $countReceived,
                    'return' => $countReturn,
                    'cancelled' => $countCancelled,
                ],
                'purchases' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'data' => $items,
                ],
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            $bearer = $request->bearerToken();
            if ($bearer && str_starts_with($bearer, 'logged_in_office_')) {
                $userId = (int) substr($bearer, strlen('logged_in_office_'));
                $user = \App\Models\Muser::find($userId);
            }
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi.',
            ], 401);
        }

        Auth::setUser($user);

        $purchase = MposPurchaseH::with([
            'supplier',
            'outlet',
            'user',
            'details.product',
            'details.ingredient',
        ])->find($id);

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi pembelian tidak ditemukan.',
            ], 404);
        }

        $isPaid = strtoupper((string) $purchase->cpayment_status) === MposPurchaseH::PAYMENT_PAID;
        $paymentStatusLabel = $isPaid ? 'Telah Lunas' : 'Belum Bayar';

        $statusLabel = match (strtoupper((string) $purchase->cstatus)) {
            MposPurchaseH::STATUS_RECEIVED => 'Telah Diterima',
            MposPurchaseH::STATUS_CANCELLED => 'Dibatalkan',
            MposPurchaseH::STATUS_POSTED => 'Telah Diproses',
            default => 'Proses',
        };

        $supplierData = [
            'name' => $purchase->supplier ? $purchase->supplier->cname : '-',
            'pic_contact' => $purchase->supplier && !empty($purchase->supplier->cpic) ? $purchase->supplier->cpic : '-',
            'phone_email' => $purchase->supplier && !empty($purchase->supplier->cphone) ? $purchase->supplier->cphone : '-',
            'address' => $purchase->supplier && !empty($purchase->supplier->caddress) ? $purchase->supplier->caddress : '-',
        ];

        $receiptData = [
            'dreceived_formatted' => $purchase->dreceived ? $purchase->dreceived->format('d F Y') : '-',
            'no_receipt_sj' => $purchase->cno_reference ?: '-',
            'received_by' => $purchase->creceived_by ?: '-',
            'stock_added' => strtoupper((string) $purchase->cstatus) === MposPurchaseH::STATUS_RECEIVED,
        ];

        $dueDateData = [
            'due_date_formatted' => $purchase->ddue_date ? $purchase->ddue_date->format('d F Y') : 'Tidak ditentukan',
        ];

        $detailItems = $purchase->details->map(function (MposPurchaseD $detail, $index) use ($purchase) {
            $productName = '-';
            $categoryUnit = '-';
            $currentStock = '-';

            if ($detail->isIngredient() && $detail->ingredient) {
                $ing = $detail.ingredient;
                $productName = $ing->cname;
                $categoryUnit = 'Bahan Baku • Satuan: ' . ($ing->csatuan ?: 'PCS');
                $currentStock = (int) $ing->nstock . ' ' . ($ing->csatuan ?: 'PCS');
            } elseif ($detail->product) {
                $prod = $detail->product;
                $productName = $prod->cname;
                $catName = $prod->category ? $prod->category->cname : 'Umum';
                $unit = $detail->csatuan ?: ($prod->csatuan ?: 'PCS');
                $categoryUnit = strtoupper($catName) . ' • Satuan: ' . strtoupper($unit);
                $currentStock = (int) $prod->nqty . ' ' . strtoupper($unit);
            }

            $qty = (int) $detail->nqty;
            $unit = strtoupper($detail->csatuan ?: 'PCS');
            $cost = (float) $detail->ncost;
            $subtotal = (float) $detail->nsubtotal;

            return [
                'no' => $index + 1,
                'product_name' => $productName,
                'category_unit' => $categoryUnit,
                'item_type' => $detail->getItemTypeLabel(),
                'current_stock' => $currentStock,
                'qty_buy' => '+' . $qty . ' ' . $unit,
                'buy_price' => $cost,
                'buy_price_formatted' => 'Rp ' . number_format($cost, 0, ',', '.'),
                'total_price' => $subtotal,
                'total_price_formatted' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (int) $purchase->nid,
                'cno_purchase' => $purchase->cno_purchase ?: ('PO-' . $purchase->nid),
                'cno_reference' => $purchase->cno_reference ?: '-',
                'cdoc_type' => $purchase->cdoc_type ?: 'PURCHASE',
                'cstatus' => $purchase->cstatus ?: 'PENDING',
                'status_label' => $statusLabel,
                'payment_status' => $purchase->cpayment_status ?: 'UNPAID',
                'payment_status_label' => $paymentStatusLabel,
                'created_by' => $purchase->user ? ($purchase->user->name ?: $purchase->user->cname) : 'Admin',
                'dpurchase_formatted' => $purchase->dpurchase ? $purchase->dpurchase->format('d M Y') : '-',
                'outlet_name' => $purchase->outlet ? $purchase->outlet->cname : '-',
                'supplier' => $supplierData,
                'receipt_info' => $receiptData,
                'note' => $purchase->cnote ?: '-',
                'due_date' => $dueDateData,
                'items_count' => count($detailItems),
                'total_amount' => (float) $purchase->ntotal_amount,
                'total_amount_formatted' => 'Rp ' . number_format((float) $purchase->ntotal_amount, 0, ',', '.'),
                'items' => $detailItems,
            ],
        ]);
    }

    public function printPdf(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user) {
            $bearer = $request->bearerToken() ?: $request->input('token');
            if ($bearer) {
                if (str_starts_with($bearer, 'Bearer ')) {
                    $bearer = substr($bearer, 7);
                }
                if (str_starts_with($bearer, 'logged_in_office_')) {
                    $userId = (int) substr($bearer, strlen('logged_in_office_'));
                    $user = \App\Models\Muser::find($userId);
                }
            }
        }

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak terautentikasi.'], 401);
        }

        Auth::setUser($user);

        $purchase = MposPurchaseH::with([
            'supplier',
            'outlet',
            'user',
            'details.product',
            'details.ingredient',
        ])->find($id);

        if (!$purchase) {
            abort(404, 'Transaksi pembelian tidak ditemukan.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('purchases.print', compact('purchase'));
        $pdf->setPaper('a4', 'portrait');

        $cno = $purchase->cno_purchase ?: ('PO-' . $purchase->nid);
        return $pdf->stream("Purchase_Order_{$cno}.pdf");
    }
}
