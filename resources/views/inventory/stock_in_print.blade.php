<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Masuk - {{ $stockIn->cno_stock_in }}</title>
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
        <h2>BUKTI STOK MASUK</h2>
        <div>{{ $stockIn->outlet?->cname ?? 'Matahati POS' }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 20%;"><strong>No. Stok Masuk</strong></td>
            <td style="width: 30%;">: {{ $stockIn->cno_stock_in }}</td>
            <td style="width: 20%;"><strong>Tanggal Terima</strong></td>
            <td style="width: 30%;">: {{ $stockIn->dstock_in ? \Carbon\Carbon::parse($stockIn->dstock_in)->format('d/m/Y H:i') : '-' }}</td>
        </tr>
        <tr>
            <td><strong>Supplier</strong></td>
            <td>: {{ $stockIn->supplier?->cname ?? '-' }}</td>
            <td><strong>Diterima Oleh</strong></td>
            <td>: {{ $stockIn->creceived_by ?? ($stockIn->user?->cname ?? '-') }}</td>
        </tr>
        <tr>
            <td><strong>No. Referensi / PO</strong></td>
            <td>: {{ $stockIn->cnote ?? '-' }}</td>
            <td><strong>Status</strong></td>
            <td>: {{ $stockIn->cstatus ?? 'POSTED' }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40px;" class="text-center">No</th>
                <th>Nama Produk / Bahan</th>
                <th class="text-end" style="width: 120px;">Harga Beli</th>
                <th class="text-center" style="width: 80px;">Qty</th>
                <th class="text-end" style="width: 140px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stockIn->details as $idx => $d)
                @php
                    $name = $d->product?->cname ?? ($d->isIngredient() ? ($d->ingredient?->cname ?? '-') : '-');
                    $typeLabel = $d->getItemTypeLabel();
                    $unitLabel = $d->getItemUnit();
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        {{ $name }}
                        <div style="font-size: 10px; color: #666;">{{ $typeLabel }}</div>
                        @if($d->cserial_no)
                            <div style="font-size: 10px; color: #666;">Seri: {{ $d->cserial_no }}</div>
                        @endif
                    </td>
                    <td class="text-end">Rp {{ number_format($d->ncost, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($d->nqty, 0, ',', '.') }} {{ $unitLabel }}</td>
                    <td class="text-end">Rp {{ number_format($d->nsubtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold;">
                <td colspan="3" class="text-end">TOTAL</td>
                <td class="text-center">{{ number_format($stockIn->details->sum('nqty'), 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format($stockIn->details->sum('nsubtotal'), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="signatures">
        <tr>
            <td>
                Petugas Penerima<br><br><br><br>
                ( {{ $stockIn->creceived_by ?? ($stockIn->user?->cname ?? '.......................') }} )
            </td>
            <td>
                Pihak Pengirim / Supplier<br><br><br><br>
                ( ..................................... )
            </td>
        </tr>
    </table>
</body>
</html>

