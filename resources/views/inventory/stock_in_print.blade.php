<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Stok Masuk #{{ $stockIn->cno_stock_in }}</title>
    <style>
        @page {
            margin: 15mm 20mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #111827;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        /* Kop / Header Format Kita */
        .header {
            text-align: center;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .header h2 {
            margin: 0 0 4px 0;
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header .doc-number {
            font-size: 12px;
            color: #334155;
            font-weight: normal;
        }

        /* Tabel Metadata */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .meta-table td {
            padding: 2.5px 0;
            font-size: 11px;
            vertical-align: top;
        }

        .meta-label {
            width: 130px;
            font-weight: bold;
            color: #0f172a;
        }

        .meta-sep {
            width: 14px;
            font-weight: bold;
            color: #0f172a;
        }

        .meta-val {
            color: #1e293b;
        }

        /* Tabel Produk (3 Kolom Sesuai Olsera) */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            margin-bottom: 20px;
        }

        .items-table th, 
        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            font-size: 11px;
        }

        .items-table th {
            background-color: #f8fafc;
            color: #0f172a;
            font-weight: bold;
        }

        /* Catatan di Bawah Tabel */
        .bottom-note {
            font-size: 11px;
            color: #1e293b;
            margin-top: 15px;
        }

        .bottom-note strong {
            color: #0f172a;
        }
    </style>
</head>
<body>
    {{-- 1. Kop Dokumen Format Kita dengan No. Stok Masuk --}}
    <div class="header">
        <h2>BUKTI PENERIMAAN BARANG (STOK MASUK)</h2>
        <div class="doc-number">No. Stok Masuk #{{ $stockIn->cno_stock_in }}</div>
    </div>

    {{-- 2. Blok Info Metadata Transaksi --}}
    <table class="meta-table">
        <tr>
            <td class="meta-label">Tanggal</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">{{ $stockIn->dstock_in ? \Carbon\Carbon::parse($stockIn->dstock_in)->format('d-M-Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Diterima dari</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">{{ $stockIn->targetOutlet ? $stockIn->targetOutlet->cname : (!empty($stockIn->supplier) ? $stockIn->supplier->cname : ($stockIn->creason ?: 'Input Stok Masuk Manual')) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Posted</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">{{ $stockIn->creceived_by ?? ($stockIn->user?->cemail ?: ($stockIn->user?->email ?: ($stockIn->user?->cname ?: '-'))) }}</td>
        </tr>
    </table>

    {{-- 3. Tabel Item Produk (3 Kolom Sederhana: #, Produk, Qty) --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">#</th>
                <th style="width: 80%; text-align: left;">Produk</th>
                <th style="width: 14%; text-align: right;">Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stockIn->details as $idx => $d)
            @php
                $isIng = $d->isIngredient();
                $name = $d->product?->cname ?? ($isIng ? ($d->ingredient?->cname ?? 'Bahan Baku') : 'Produk');
                $qty = is_numeric($d->nqty) ? (fmod((float)$d->nqty, 1) === 0.0 ? (int)$d->nqty : (float)$d->nqty) : $d->nqty;
            @endphp
            <tr>
                <td style="text-align: center;">{{ $idx + 1 }}</td>
                <td style="text-align: left;">{{ $name }}</td>
                <td style="text-align: right;">{{ $qty }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align: center; color: #94a3b8; padding: 15px;">Tidak ada item.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- 4. Catatan di Bawah Tabel --}}
    <div class="bottom-note">
        <strong>Catatan :</strong> {{ $stockIn->cnote ?: '-' }}
    </div>
</body>
</html>
