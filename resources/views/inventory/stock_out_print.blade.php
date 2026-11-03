<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Keluar - {{ $stockOut->cno_stock_out }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 0 0 5px 0;
        }
        .info-table, .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 4px 8px;
            vertical-align: top;
        }
        .items-table th, .items-table td {
            border: 1px solid #ccc;
            padding: 6px 8px;
        }
        .items-table th {
            background-color: #f2f2f2;
            text-align: left;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .signatures {
            margin-top: 30px;
            width: 100%;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            padding-top: 50px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 8px 16px; background-color: #b63352; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
            Cetak Dokumen
        </button>
        <button onclick="window.close()" style="padding: 8px 16px; background-color: #6c757d; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
            Tutup
        </button>
    </div>

    <div class="header">
        <h2>BUKTI PENGELUARAN BARANG (STOK KELUAR)</h2>
        <div>{{ $stockOut->outlet?->cname ?? 'Matahati POS' }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 20%;"><strong>No. Stok Keluar</strong></td>
            <td style="width: 30%;">: {{ $stockOut->cno_stock_out }}</td>
            <td style="width: 20%;"><strong>Tanggal Keluar</strong></td>
            <td style="width: 30%;">: {{ $stockOut->dstock_out ? \Carbon\Carbon::parse($stockOut->dstock_out)->format('d/m/Y H:i') : '-' }}</td>
        </tr>
        <tr>
            <td><strong>Jenis / Transfer ke</strong></td>
            <td>: {{ $stockOut->targetOutlet ? 'Transfer ke ' . $stockOut->targetOutlet->cname : 'Pengeluaran Stok' }}</td>
            <td><strong>Dikeluarkan Oleh</strong></td>
            <td>: {{ $stockOut->user?->cname ?: ($stockOut->user?->email ?: 'Admin') }}</td>
        </tr>
        <tr>
            <td><strong>Alasan Pengeluaran</strong></td>
            <td>: {{ $stockOut->creason ?: '-' }}</td>
            <td><strong>Status</strong></td>
            <td>: {{ $stockOut->cstatus ?? 'POSTED' }}</td>
        </tr>
        @if($stockOut->cnote)
        <tr>
            <td><strong>Catatan</strong></td>
            <td colspan="3">: {{ $stockOut->cnote }}</td>
        </tr>
        @endif
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 35px;" class="text-center">No</th>
                <th>Tipe</th>
                <th>Produk / Bahan Baku</th>
                <th>Stock Dari</th>
                <th class="text-center" style="width: 80px;">Qty</th>
                <th class="text-end" style="width: 110px;">Harga Modal</th>
                <th class="text-end" style="width: 120px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalQty = 0;
                $totalCost = 0;
            @endphp
            @forelse($stockOut->details as $idx => $d)
            @php
                $isIng = $d->isIngredient();
                $name = $isIng ? ($d->ingredient?->cname ?? 'Bahan Baku') : ($d->product?->cname ?? 'Produk');
                $satuan = $isIng ? ($d->ingredient?->csatuan ?? '') : 'pcs';
                $totalQty += (float) $d->nqty;
                $totalCost += (float) $d->nsubtotal;
            @endphp
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td>{{ $isIng ? 'Bahan Baku' : 'Produk' }}</td>
                <td>{{ $name }}</td>
                <td>{{ $d->cstock_from ?: '-' }}</td>
                <td class="text-center">{{ rtrim(rtrim(number_format((float) $d->nqty, 2, '.', ''), '0'), '.') }} {{ $satuan }}</td>
                <td class="text-end">Rp {{ number_format((float) $d->ncost, 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format((float) $d->nsubtotal, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">Tidak ada item.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f9f9f9;">
                <td colspan="4" class="text-end">Total:</td>
                <td class="text-center">{{ rtrim(rtrim(number_format($totalQty, 2, '.', ''), '0'), '.') }}</td>
                <td></td>
                <td class="text-end">Rp {{ number_format($totalCost, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="signatures">
        <tr>
            <td>
                Dibuat / Dikeluarkan oleh,<br><br><br><br>
                ( {{ $stockOut->user?->cname ?: 'Petugas Gudang / Kasir' }} )
            </td>
            <td>
                Diterima / Mengetahui,<br><br><br><br>
                ( .................................... )
            </td>
        </tr>
    </table>
</body>
</html>

