<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MposSalesH;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->leftJoin('mpos_product as p', 'sd.nid_product', '=', 'p.nid')
            ->leftJoin('mpos_grp_product as gp', 'p.nid_category', '=', 'gp.nid')
            ->where('sh.cstatus', MposSalesH::STATUS_PAID) // Only successful/paid transactions
            ->whereDate('sh.dtransaction', '>=', $dateFrom)
            ->whereDate('sh.dtransaction', '<=', $dateTo);

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
            'gp.cname as category_name',
            'sd.nprice as price',
            // Calculate net quantity (qty - void - refund)
            DB::raw('SUM(sd.nqty - sd.nqty_void - sd.nqty_refund) as sold_qty'),
            // Calculate total sales based on net quantity and price
            DB::raw('SUM((sd.nqty - sd.nqty_void - sd.nqty_refund) * sd.nprice) as total_sales')
        ]);

        $query->groupBy(
            'p.nid',
            'sd.cname',
            'gp.cname',
            'sd.nprice'
        );

        // Get the results
        $items = $query->get();

        // Remove items with 0 sold_qty (if everything was refunded/voided)
        $items = $items->filter(function($item) {
            return $item->sold_qty > 0;
        })->values();

        // Format items types (string from DB to expected types)
        $items->transform(function($item) {
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
    }
}
