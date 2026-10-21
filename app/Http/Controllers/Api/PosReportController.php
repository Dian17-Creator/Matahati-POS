<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MposSalesH;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PosReportController extends Controller
{
    /**
     * Get Product Sales Summary Report
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function productSummary(Request $request)
    {
        try {
            // Validation for optional/required filters
            $validator = Validator::make($request->all(), [
                'date_from' => 'required|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'nid_outlet' => 'nullable|integer',
                'nid_category' => 'nullable|integer',
                'nid_user' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to', $dateFrom); // Default to date_from if not provided

            // Start building the query
            $query = DB::table('mpos_sales_d as sd')
                ->join('mpos_sales_h as sh', 'sd.nid_transaction', '=', 'sh.nid')
                ->leftJoin('mpos_sales_h as parent', 'sh.nid_parent_transaction', '=', 'parent.nid')
                ->leftJoin('mpos_product as p', 'sd.nid_product', '=', 'p.nid')
                ->leftJoin('mpos_grp_product as gp', 'p.nid_category', '=', 'gp.nid')
                ->where(function ($q) {
                    $q->whereIn('sh.cstatus', [
                        MposSalesH::STATUS_PAID,
                        MposSalesH::STATUS_COMPLETED,
                    ])
                    ->orWhereNotNull('sh.nid_parent_transaction');
                })
                ->whereDate(DB::raw('COALESCE(parent.dtransaction, sh.dtransaction)'), '>=', $dateFrom)
                ->whereDate(DB::raw('COALESCE(parent.dtransaction, sh.dtransaction)'), '<=', $dateTo);

            // Apply optional filters
            if ($request->filled('nid_outlet')) {
                $query->where('sh.nid_outlet', $request->input('nid_outlet'));
            }

            if ($request->filled('nid_category')) {
                $query->where('p.nid_category', $request->input('nid_category'));
            }

            if ($request->filled('nid_user')) {
                $query->where('sh.nid_user', $request->input('nid_user'));
            }

            // Group by product and aggregate
            $query->select([
                'p.nid as product_id',
                'sd.cname as product_name',
                DB::raw("COALESCE(gp.cname, 'Lainnya') as category_name"),
                'sd.nprice as price',
                // Calculate net quantity (main sales minus void/refund and minus Storno reversals)
                DB::raw("SUM(
                    CASE
                        WHEN sh.nid_parent_transaction IS NOT NULL THEN -1 * ABS(sd.nqty)
                        ELSE (sd.nqty - COALESCE(sd.nqty_void, 0) - COALESCE(sd.nqty_refund, 0))
                    END
                ) as sold_qty"),
                // Calculate net sales based on net quantity and price
                DB::raw("SUM(
                    CASE
                        WHEN sh.nid_parent_transaction IS NOT NULL THEN -1 * ABS(sd.nqty * sd.nprice)
                        ELSE ((sd.nqty - COALESCE(sd.nqty_void, 0) - COALESCE(sd.nqty_refund, 0)) * sd.nprice)
                    END
                ) as total_sales")
            ]);

            $query->groupBy(
                'p.nid',
                'sd.cname',
                DB::raw("COALESCE(gp.cname, 'Lainnya')"),
                'sd.nprice'
            );

            // Get the results
            $items = $query->get();

            // Remove items with <= 0 sold_qty or total_sales (if everything was refunded/voided)
            $items = $items->filter(function ($item) {
                return $item->sold_qty > 0 && $item->total_sales > 0;
            })->values();

            // Format items types (string from DB to expected types)
            $items->transform(function ($item) {
                $item->product_id = (int) $item->product_id;
                $item->price = (float) $item->price;
                $item->sold_qty = (int) $item->sold_qty;
                $item->total_sales = (float) $item->total_sales;
                return $item;
            });

            // Calculate grand totals
            $grandTotalQty = $items->sum('sold_qty');
            $grandTotalSales = $items->sum('total_sales');

            return response()->json([
                'success' => true,
                'message' => 'Product sales summary retrieved successfully',
                'data' => [
                    'summary' => [
                        'grand_total_qty' => (int) $grandTotalQty,
                        'grand_total_sales' => (float) $grandTotalSales,
                    ],
                    'items' => $items
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal memuat ringkasan penjualan produk: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data ringkasan penjualan produk: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
