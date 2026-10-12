<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Imports\OlseraSalesImport;
use App\Models\MposOutlet;
use App\Models\MposSalesH;
use App\Models\MposUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class SalesDetailReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $endDateParsed = Carbon::parse($endDate)->endOfDay();
        
        $status = $request->input('status', '');
        $outletId = $request->input('outlet_id', '');

        // Handle authorization for outlet
        $user = Auth::user();
        $mposUser = null;
        if ($user) {
            $mposUser = MposUser::where('nid_user', $user->id ?? $user->nid)->first();
        }
        
        // If cashier and not owner/captain, force their outlet
        $isCashierOnly = $mposUser && !$mposUser->fowner && !$mposUser->fcaptain && $mposUser->fcashier;
        
        if ($isCashierOnly) {
            $outletId = $mposUser->nid_outlet;
        }

        $outlets = MposOutlet::orderBy('cname')->get();

        $query = DB::table('mpos_sales_h')
            ->leftJoin('mpos_payment', 'mpos_sales_h.nid_payment', '=', 'mpos_payment.nid')
            ->leftJoin('mpos_user', 'mpos_sales_h.nid_user', '=', 'mpos_user.nid')
            ->leftJoin('muser', 'mpos_user.nid_user', '=', 'muser.nid')
            ->leftJoin('mpos_cust', 'mpos_sales_h.nid_customer', '=', 'mpos_cust.nid')
            ->where('mpos_sales_h.dtransaction', '>=', $startDate)
            ->where('mpos_sales_h.dtransaction', '<=', $endDateParsed);

        if (!empty($status)) {
            $query->where('mpos_sales_h.cstatus', $status);
        }

        if (!empty($outletId)) {
            $query->where('mpos_sales_h.nid_outlet', $outletId);
        }

        // Subquery for Modal & Valid Void/Refund per transaction
        // To avoid N+1 and complex looping in blade.
        $itemDeductions = DB::table('mpos_sales_d')
            ->select('nid_transaction', 
                DB::raw('SUM((nqty_void + nqty_refund) * nprice) as total_void_refund'),
                DB::raw('SUM(ncost * (nqty - nqty_void - nqty_refund)) as total_cost'),
                DB::raw('MIN(ncost) as min_cost') // if < 0, cost is invalid
            )
            ->groupBy('nid_transaction');

        $query->leftJoinSub($itemDeductions, 'items', function ($join) {
            $join->on('mpos_sales_h.nid', '=', 'items.nid_transaction');
        });

        $query->select(
            'mpos_sales_h.*',
            'mpos_payment.cname as payment_name',
            'muser.cname as cashier_name',
            DB::raw('COALESCE(mpos_sales_h.cname_customer, mpos_cust.cname, \'Umum\') as final_customer_name'),
            DB::raw('COALESCE(items.total_void_refund, 0) as total_void_refund'),
            DB::raw('COALESCE(items.total_cost, 0) as total_cost'),
            'items.min_cost'
        );

        $query->orderBy('mpos_sales_h.dtransaction', 'desc');

        $transactions = $query->paginate(10)->withQueryString();

        // Calculate Summary for PAID transactions only
        $summaryQuery = DB::table('mpos_sales_h')
            ->where('mpos_sales_h.dtransaction', '>=', $startDate)
            ->where('mpos_sales_h.dtransaction', '<=', $endDateParsed)
            ->where('mpos_sales_h.cstatus', MposSalesH::STATUS_PAID);

        if (!empty($outletId)) {
            $summaryQuery->where('mpos_sales_h.nid_outlet', $outletId);
        }

        $summaryQuery->leftJoinSub($itemDeductions, 'items', function ($join) {
            $join->on('mpos_sales_h.nid', '=', 'items.nid_transaction');
        });

        // Summary metrics: strictly preserve POS calculations and cleanly map Olsera fields
        $summary = $summaryQuery->select(
            DB::raw("SUM(CASE WHEN mpos_sales_h.csource = 'OLSERA' THEN mpos_sales_h.nsubtotal ELSE mpos_sales_h.ngrandtotal END) as gross_sales"),
            DB::raw("SUM(CASE WHEN mpos_sales_h.csource = 'OLSERA' THEN mpos_sales_h.ngrandtotal ELSE (mpos_sales_h.ngrandtotal - COALESCE(items.total_void_refund, 0)) END) as net_sales"),
            DB::raw("SUM(CASE WHEN mpos_sales_h.csource = 'OLSERA' THEN 0 ELSE COALESCE(items.total_void_refund, 0) END) as total_void_refund"),
            DB::raw('SUM(mpos_sales_h.ndiscount) as total_discount'),
            DB::raw('SUM(mpos_sales_h.ntax) as total_tax'),
            DB::raw('SUM(mpos_sales_h.nservice_charge) as total_service'),
            DB::raw('SUM(mpos_sales_h.nrounding) as total_rounding'),
            DB::raw('SUM(mpos_sales_h.nvisitor) as total_visitor'),
            DB::raw("SUM(CASE 
                WHEN mpos_sales_h.csource = 'OLSERA' THEN 
                    CASE WHEN mpos_sales_h.ntotal_cost IS NOT NULL THEN mpos_sales_h.ntotal_cost ELSE 0 END
                WHEN items.min_cost >= 0 THEN 
                    items.total_cost 
                ELSE 0 
            END) as valid_total_cost"),
            DB::raw("SUM(CASE 
                WHEN mpos_sales_h.csource = 'OLSERA' AND mpos_sales_h.ntotal_cost IS NULL THEN 1
                WHEN (mpos_sales_h.csource IS NULL OR mpos_sales_h.csource != 'OLSERA') AND items.min_cost < 0 THEN 1
                ELSE 0 
            END) as invalid_cost_count"),
            DB::raw("SUM(CASE 
                WHEN mpos_sales_h.csource = 'OLSERA' THEN 
                    CASE WHEN mpos_sales_h.ntotal_profit IS NOT NULL THEN mpos_sales_h.ntotal_profit ELSE 0 END
                WHEN items.min_cost >= 0 THEN 
                    (mpos_sales_h.ngrandtotal - COALESCE(items.total_void_refund, 0) - COALESCE(items.total_cost, 0))
                ELSE 0 
            END) as valid_total_laba"),
            DB::raw("SUM(CASE 
                WHEN mpos_sales_h.csource = 'OLSERA' AND mpos_sales_h.ntotal_profit IS NULL THEN 1
                WHEN (mpos_sales_h.csource IS NULL OR mpos_sales_h.csource != 'OLSERA') AND items.min_cost < 0 THEN 1
                ELSE 0 
            END) as invalid_profit_count")
        )->first();

        $netSales = $summary->net_sales ?? 0;

        return view('reports.sales_detail.index', compact(
            'transactions',
            'startDate',
            'endDate',
            'status',
            'outletId',
            'outlets',
            'isCashierOnly',
            'summary',
            'netSales'
        ));
    }

    /**
     * Langkah 1: Upload & Dry-Run Preview Import Olsera (Tanpa Insert ke DB)
     */
    public function importPreview(Request $request)
    {
        $request->validate([
            'outlet_id' => 'required|exists:mpos_outlet,nid',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        $user = Auth::user();
        $mposUser = null;
        if ($user) {
            $mposUser = MposUser::where('nid_user', $user->id ?? $user->nid)->first();
            if ($mposUser && !$mposUser->fowner && !$mposUser->fcaptain && $mposUser->fcashier) {
                if ((int)$request->outlet_id !== (int)$mposUser->nid_outlet) {
                    return redirect()->back()->with('error', 'Anda hanya memiliki hak akses untuk mengimpor transaksi ke outlet Anda sendiri.');
                }
            }
        }

        // Resolusi ID mpos_user yang valid untuk foreign key fk_mpos_sales_h_user
        $mposUserId = $mposUser?->nid 
            ?? MposUser::where('nid_outlet', $request->outlet_id)->first()?->nid 
            ?? MposUser::first()?->nid;

        try {
            $uploadedFile = $request->file('file');
            $fileHash = hash_file('sha256', $uploadedFile->getRealPath());
            $previewToken = 'olsera_import_' . Str::random(32);
            $extension = $uploadedFile->getClientOriginalExtension() ?: 'xlsx';
            
            // Simpan file sementara di storage disk local
            $tempPath = $uploadedFile->storeAs('temp_imports', $previewToken . '.' . $extension, 'local');
            $fullTempPath = Storage::disk('local')->path($tempPath);

            // Jalankan parser dalam mode dry-run (isDryRun = true)
            $importer = new OlseraSalesImport((int)$request->outlet_id, $mposUserId, true);
            Excel::import($importer, $fullTempPath);

            $outletName = MposOutlet::find($request->outlet_id)?->cname ?? 'Outlet #' . $request->outlet_id;

            // Simpan metadata preview ke Cache selama 15 menit
            Cache::put($previewToken, [
                'outlet_id' => (int)$request->outlet_id,
                'outlet_name' => $outletName,
                'mpos_user_id' => $mposUserId,
                'file_hash' => $fileHash,
                'temp_path' => $tempPath,
                'original_filename' => $uploadedFile->getClientOriginalName(),
                'results' => $importer->results,
            ], now()->addMinutes(15));

            return redirect()->back()
                ->with('preview_token', $previewToken)
                ->with('preview_data', Cache::get($previewToken));

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memproses preview file: ' . $e->getMessage());
        }
    }

    /**
     * Langkah 2: Konfirmasi dan Simpan Hasil Import ke mpos_sales_h
     */
    public function importConfirm(Request $request)
    {
        $request->validate([
            'preview_token' => 'required|string',
        ]);

        $previewToken = $request->input('preview_token');
        $previewData = Cache::get($previewToken);

        if (!$previewData) {
            return redirect()->route('reports.sales-detail')
                ->with('error', 'Sesi preview telah kedaluwarsa atau tidak valid. Silakan upload ulang file Anda.');
        }

        $outletId = $previewData['outlet_id'];
        $lockKey = "import_olsera_lock_outlet_{$outletId}";
        $lock = Cache::lock($lockKey, 300);

        if (!$lock->get()) {
            return redirect()->route('reports.sales-detail')
                ->with('error', 'Proses import untuk outlet ini sedang berjalan oleh sesi lain. Harap tunggu beberapa saat.');
        }

        $fullTempPath = Storage::disk('local')->path($previewData['temp_path']);

        // Verifikasi fisik file dan hash file untuk mencegah pergantian file
        if (!Storage::disk('local')->exists($previewData['temp_path']) || hash_file('sha256', $fullTempPath) !== $previewData['file_hash']) {
            $lock->release();
            return redirect()->route('reports.sales-detail')
                ->with('error', 'File import sementara tidak ditemukan atau telah mengalami perubahan. Silakan upload ulang file.');
        }

        DB::beginTransaction();
        try {
            // Jalankan import dalam mode execute (isDryRun = false)
            $importingUserId = $previewData['mpos_user_id'] ?? $previewData['user_id'] ?? null;
            $importer = new OlseraSalesImport($outletId, $importingUserId, false);
            Excel::import($importer, $fullTempPath);

            DB::commit();

            // Bersihkan file sementara dan cache token
            Storage::disk('local')->delete($previewData['temp_path']);
            Cache::forget($previewToken);
            $lock->release();

            $savedCount = $importer->results['saved_count'];
            $dupCount = $importer->results['duplicate_skipped'];
            $confCount = $importer->results['conflict_skipped'];
            $invCount = $importer->results['invalid_rows'];
            $totalNetFormatted = number_format($importer->results['valid_net_amount'], 0, ',', '.');

            return redirect()->route('reports.sales-detail')
                ->with('import_success', [
                    'outlet_name' => $previewData['outlet_name'],
                    'saved_count' => $savedCount,
                    'duplicate_skipped' => $dupCount,
                    'conflict_skipped' => $confCount,
                    'invalid_rows' => $invCount,
                    'total_net_amount' => $totalNetFormatted,
                ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            $lock->release();

            return redirect()->route('reports.sales-detail')
                ->with('error', 'Gagal menyimpan transaksi ke database: ' . $e->getMessage());
        }
    }
}
