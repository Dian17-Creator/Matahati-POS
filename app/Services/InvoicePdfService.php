<?php

namespace App\Services;

use App\Models\MposSalesH;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class InvoicePdfService
{
    /**
     * Generate PDF invoice thermal receipt and save to public/uploads/invoice/
     *
     * @param MposSalesH $salesH
     * @return string|null Generated filename or null on failure
     */
    public function generate(MposSalesH $salesH): ?string
    {
        try {
            // Eager load necessary relationships
            $salesH->loadMissing(['details.product', 'outlet', 'posUser.user', 'customer']);

            $cleanTrxNo = str_replace('#', '', $salesH->cnotransaction);
            $datePart = $salesH->dtransaction ? Carbon::parse($salesH->dtransaction)->format('Ymd') : now()->format('Ymd');
            $filename = "invoice_{$cleanTrxNo}_{$datePart}.pdf";

            $directory = public_path('uploads/invoice');
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true, true);
            }

            $filePath = $directory . DIRECTORY_SEPARATOR . $filename;

            // Calculate paper height dynamically based on item count for thermal roll receipt
            // 80mm roll width = 226.77 pt (~80mm)
            // Base layout (header, meta, summary, totals, footer): ~300 pt
            // Each line item: ~38 pt
            $itemCount = $salesH->details->count();
            $paperWidth = 226.77; // 80mm
            $paperHeight = max(420, 290 + ($itemCount * 38));

            $pdf = Pdf::loadView('pdf.invoice', ['salesH' => $salesH]);
            $pdf->setPaper([0, 0, $paperWidth, $paperHeight], 'portrait');
            $pdf->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);

            $pdf->save($filePath);

            return $filename;
        } catch (\Throwable $e) {
            Log::error('Gagal generate PDF invoice: ' . $e->getMessage(), [
                'exception' => $e,
                'cnotransaction' => $salesH->cnotransaction ?? null,
            ]);
            return null;
        }
    }
}
