<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Updates legacy PO numbers (PO + 6 date + 4 seq = 10 digits) to Olsera format (PO + 6 date + 8 seq = 14 digits).
     */
    public function up(): void
    {
        $purchases = DB::table('mpos_purchase_h')->get();

        foreach ($purchases as $item) {
            $cno = trim($item->cno_purchase ?? '');
            // Check if matches PO + 10 digits (old format: PO followed by 6 date digits and 4 seq digits)
            if (preg_match('/^PO(\d{6})(\d{4})$/', $cno, $matches)) {
                $datePart = $matches[1];
                $seqPart = (int) $matches[2];
                $newCno = 'PO' . $datePart . str_pad($seqPart, 8, '0', STR_PAD_LEFT);

                DB::table('mpos_purchase_h')
                    ->where('nid', $item->nid)
                    ->update(['cno_purchase' => $newCno]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $purchases = DB::table('mpos_purchase_h')->get();

        foreach ($purchases as $item) {
            $cno = trim($item->cno_purchase ?? '');
            // Check if matches PO + 14 digits (PO followed by 6 date digits and 8 seq digits)
            if (preg_match('/^PO(\d{6})(\d{8})$/', $cno, $matches)) {
                $datePart = $matches[1];
                $seqPart = (int) $matches[2];
                $oldCno = 'PO' . $datePart . str_pad($seqPart, 4, '0', STR_PAD_LEFT);

                DB::table('mpos_purchase_h')
                    ->where('nid', $item->nid)
                    ->update(['cno_purchase' => $oldCno]);
            }
        }
    }
};
